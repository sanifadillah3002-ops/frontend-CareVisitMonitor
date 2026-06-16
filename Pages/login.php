<?php
require_once '../config.php';

// Redirect if already logged in
if (isset($_SESSION['api_token'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $result = callAPI('POST', '/login', [
            'email' => $email,
            'password' => $password
        ]);

        if ($result['status_code'] === 200 && isset($result['response']['success']) && $result['response']['success'] === true) {
            $_SESSION['api_token'] = $result['response']['access_token'];
            $_SESSION['user'] = $result['response']['user'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = $result['response']['message'] ?? 'Login gagal. Silakan coba lagi.';
        }
    } else {
        $error = 'Email dan password wajib diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CareVisitMonitor</title>
    <link href="https://cdn.jsdelivr.net/css?family=Poppins:300,400,500,600,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .login-container {
            margin-top: 10%;
        }
    </style>
</head>
<body>

<div class="container login-container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 px-3 py-4">
                <div class="card-body">
                    <h3 class="text-center fw-bold text-primary mb-2">CareVisitMonitor</h3>
                    <p class="text-muted text-center small mb-4">Silakan masuk ke akun perawat Anda</p>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label small fw-medium">Email</label>
                            <input type="email" name="email" class="form-control" id="username" placeholder="Masukkan email" required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label small fw-medium">Password</label>
                            <input type="password" name="password" class="form-control" id="password" placeholder="••••••••" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-medium">Masuk Aplikasi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>