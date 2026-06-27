<?php
require '../config.php';

if (isset($_SESSION['api_token'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = [
        'email'    => $_POST['email']    ?? '',
        'password' => $_POST['password'] ?? ''
    ];

    if (!empty($data['email']) && !empty($data['password'])) {
        $apiCall = callAPI('POST', '/login', $data);

        if ($apiCall['status_code'] == 200 && isset($apiCall['response']['success']) && $apiCall['response']['success'] == true) {
            $_SESSION['api_token'] = $apiCall['response']['access_token'];
            $_SESSION['user']      = $apiCall['response']['user'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = $apiCall['response']['message'] ?? 'Gagal terhubung ke server atau kredensial salah. Pastikan backend Laravel menyala.';
        }
    } else {
        $error = 'Email dan password wajib diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIVISIT-SIVISIT-CareVisitMonitor</title>
    <link href="https://cdn.jsdelivr.net/css?family=Poppins:300,400,500,600,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .login-container {
            margin-top: 10%;
        }
        .sv-logo-box {
            width: 56px; height: 56px; 
            background: rgba(13, 110, 253, 0.1); 
            border-radius: 14px; 
            display: flex; align-items: center; justify-content: center; 
            margin: 0 auto 16px auto;
        }
        .sv-logo-box svg {
            fill: #0d6efd;
        }
    </style>
</head>
<body>

<div class="container login-container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 px-3 py-4">
                <div class="card-body">
                    <div class="text-center">
                        <div class="sv-logo-box">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 16 16">
                                <path d="M1.475 9C2.702 10.84 4.779 12.871 8 15c3.221-2.129 5.298-4.16 6.525-6H12a.5.5 0 0 1-.464-.314l-1.457-3.642-1.598 5.593a.5.5 0 0 1-.945.049L5.889 6.568l-1.473 2.21A.5.5 0 0 1 4 9z"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-center fw-bold text-primary mb-2">SIVISIT</h3>
                    <p class="text-muted text-center small mb-4">Silakan masuk ke akun Anda</p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label small fw-medium">Email</label>
                            <input type="email" name="email" class="form-control" id="username" placeholder="Masukkan email"
                                required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label small fw-medium">Password</label>
                            <input type="password" name="password" class="form-control" id="password" placeholder="••••••••"
                                required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-medium">Masuk Aplikasi</button>
                        </div>
                    </form>
                    
                    <div style="margin-top: 1.5rem; text-align: center; font-size: 13px; color: #6c757d;">
                        <a href="../index.php" style="color: #0d6efd; text-decoration: none;">← Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
