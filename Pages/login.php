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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — SIVISIT</title>
    <meta name="description" content="Login ke sistem SIVISIT untuk memantau pasien home care.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sv-blue:      #007AFF;
            --sv-blue-dark: #0058D0;
            --sv-navy:      #001A42;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
        }

        /* ── Left Panel ── */
        .sv-login-left {
            width: 45%;
            background: linear-gradient(160deg, var(--sv-navy) 0%, #002866 60%, #003A8C 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 60px 56px;
            position: relative;
            overflow: hidden;
        }

        .sv-login-left::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(0,122,255,0.25) 0%, transparent 65%);
            top: -100px; right: -100px;
            border-radius: 50%;
        }

        .sv-login-left::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(52,199,89,0.12) 0%, transparent 65%);
            bottom: -80px; left: -80px;
            border-radius: 50%;
        }

        .sv-left-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 56px;
            position: relative;
            z-index: 1;
        }

        .sv-left-brand .logo-box {
            width: 44px; height: 44px;
            background: var(--sv-blue);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: 700;
        }

        .sv-left-brand .brand-name {
            font-size: 20px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.3px;
        }

        .sv-left-content {
            position: relative;
            z-index: 1;
        }

        .sv-left-content h2 {
            font-size: 32px;
            font-weight: 800;
            color: white;
            letter-spacing: -0.8px;
            line-height: 1.2;
            margin-bottom: 16px;
        }

        .sv-left-content p {
            font-size: 15px;
            color: rgba(255,255,255,0.55);
            line-height: 1.7;
            max-width: 340px;
            margin-bottom: 40px;
        }

        .sv-feature-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .sv-feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            color: rgba(255,255,255,0.7);
        }

        .sv-feature-item .dot {
            width: 6px; height: 6px;
            background: var(--sv-blue);
            border-radius: 50%;
            flex-shrink: 0;
        }

        .sv-left-disclaimer {
            position: absolute;
            bottom: 28px;
            left: 56px;
            right: 56px;
            font-size: 11px;
            color: rgba(255,255,255,0.3);
            line-height: 1.6;
            z-index: 1;
        }

        /* ── Right Panel ── */
        .sv-login-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #FAFBFC;
            padding: 60px 40px;
        }

        .sv-login-form-wrap {
            width: 100%;
            max-width: 400px;
        }

        .sv-back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #636366;
            text-decoration: none;
            margin-bottom: 40px;
            transition: all 0.2s;
        }

        .sv-back-link:hover { color: var(--sv-blue); }

        .sv-login-form-wrap h1 {
            font-size: 26px;
            font-weight: 700;
            color: #1C1C1E;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .sv-login-form-wrap .subtitle {
            font-size: 14px;
            color: #636366;
            margin-bottom: 36px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #1C1C1E;
            margin-bottom: 6px;
        }

        .form-control {
            border-radius: 10px;
            border: 1.5px solid #D8DCE6;
            padding: 12px 14px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #1C1C1E;
            background: white;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--sv-blue);
            box-shadow: 0 0 0 3px rgba(0,122,255,0.12);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap .form-control {
            padding-right: 44px;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #8E8E93;
            font-size: 14px;
            padding: 0;
            transition: color 0.2s;
        }

        .password-toggle:hover { color: var(--sv-blue); }

        .btn-login {
            width: 100%;
            background: var(--sv-blue);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }

        .btn-login:hover {
            background: var(--sv-blue-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,122,255,0.3);
        }

        .btn-login:active { transform: translateY(0); }

        .sv-login-alert {
            background: #FFF0EF;
            border: 1px solid #FFD0CC;
            color: #C0291F;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .sv-login-footer {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: #8E8E93;
        }

        @media (max-width: 768px) {
            .sv-login-left { display: none; }
            .sv-login-right { padding: 40px 24px; }
        }

        @keyframes fadeInUp {
            from { opacity:0; transform:translateY(16px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .sv-login-form-wrap { animation: fadeInUp 0.4s ease both; }
    </style>
</head>
<body>

<!-- ════ LEFT PANEL ════ -->
<div class="sv-login-left">
    <div class="sv-left-brand">
        <div class="logo-box">SV</div>
        <span class="brand-name">SIVISIT</span>
    </div>

    <div class="sv-left-content">
        <h2>Sistem Monitoring<br>Pasien Home Care</h2>
        <p>Platform digital untuk petugas kesehatan dalam memantau dan mencatat kondisi pasien binaan secara terstruktur.</p>

        <div class="sv-feature-list">
            <div class="sv-feature-item">
                <div class="dot"></div>
                Catat kunjungan & monitoring kesehatan
            </div>
            <div class="sv-feature-item">
                <div class="dot"></div>
                Pantau status: Stabil, Perlu Kontrol, Perlu Rujukan
            </div>
            <div class="sv-feature-item">
                <div class="dot"></div>
                Riwayat monitoring per pasien
            </div>
            <div class="sv-feature-item">
                <div class="dot"></div>
                Akses publik via kode pasien / NIK
            </div>
        </div>
    </div>

    <div class="sv-left-disclaimer">
        ⚠️ Seluruh data bersifat simulasi/dummy. Sistem ini tidak memberikan diagnosis medis.
    </div>
</div>

<!-- ════ RIGHT PANEL ════ -->
<div class="sv-login-right">
    <div class="sv-login-form-wrap">
        <a href="../index.php" class="sv-back-link">
            ← Kembali ke Beranda
        </a>

        <h1>Selamat Datang</h1>
        <p class="subtitle">Masuk ke akun petugas / admin Anda</p>

        <?php if (!empty($error)): ?>
            <div class="sv-login-alert">
                <span>⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="loginForm">
            <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="form-control"
                    placeholder="petugas@sivisit.id"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="password-wrap">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle" id="togglePassword" title="Tampilkan password">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                Masuk ke Sistem
            </button>
        </form>

        <div class="sv-login-footer">
            Butuh akses keluarga / pasien?
            <a href="cari-pasien.php" style="color:var(--sv-blue);font-weight:500;">Cari Riwayat Pasien →</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle password visibility
    const toggleBtn = document.getElementById('togglePassword');
    const pwdInput  = document.getElementById('password');

    toggleBtn.addEventListener('click', () => {
        const isPassword = pwdInput.type === 'password';
        pwdInput.type = isPassword ? 'text' : 'password';
        toggleBtn.textContent = isPassword ? '🙈' : '👁️';
    });

    // Loading state on submit
    document.getElementById('loginForm').addEventListener('submit', () => {
        const btn = document.getElementById('loginBtn');
        btn.textContent = 'Memproses...';
        btn.disabled = true;
    });
</script>
</body>
</html>