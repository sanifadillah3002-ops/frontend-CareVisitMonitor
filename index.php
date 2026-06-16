<?php
require 'config.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['api_token'])) {
    header("Location: Pages/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaAdmin — CareVisit Monitor</title>
    <meta name="description" content="Sistem Informasi Terpadu untuk mengecek hasil monitoring kunjungan dan status administratif pasien home care secara real-time dan transparan.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sv-blue: #007AFF;
            --sv-blue-dark: #0058D0;
            --sv-blue-light: #E8F1FF;
            --sv-navy: #001A42;
            --sv-navy-mid: #002866;
            --sv-bg: #FAFBFC;
            --sv-surface: #FFFFFF;
            --sv-border: #E8ECF0;
            --sv-text-main: #1C1C1E;
            --sv-text-sub: #636366;
            --sv-text-muted: #8E8E93;
            
            --sv-radius-sm: 8px;
            --sv-radius: 14px;
            --sv-radius-lg: 20px;
            --sv-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
            --sv-shadow-lg: 0 16px 40px rgba(0, 0, 0, 0.08);
            --sv-transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--sv-bg);
            color: var(--sv-text-main);
            margin: 0;
            padding: 0;
        }

        /* ── Header / Navbar ── */
        .sv-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0 32px;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: var(--sv-transition);
        }

        .sv-navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .sv-navbar-brand .logo-box {
            width: 38px;
            height: 38px;
            background: var(--sv-blue);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 3px 10px rgba(0, 122, 255, 0.3);
        }

        .sv-navbar-brand .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .sv-navbar-brand .brand-name {
            font-size: 17px;
            font-weight: 700;
            color: var(--sv-navy);
            letter-spacing: -0.3px;
        }

        .sv-navbar-brand .brand-sub {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.8px;
            color: var(--sv-text-muted);
            text-transform: uppercase;
        }

        .sv-navbar-links {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sv-navbar-links a {
            text-decoration: none;
            font-size: 14.5px;
            font-weight: 500;
            color: var(--sv-text-sub);
            padding: 8px 16px;
            border-radius: 8px;
            transition: var(--sv-transition);
        }

        .sv-navbar-links a:hover, .sv-navbar-links a.active {
            color: var(--sv-blue);
            background: rgba(0, 122, 255, 0.06);
        }

        .btn-sv-primary {
            background: var(--sv-blue);
            color: white !important;
            border-radius: 10px;
            padding: 9px 22px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            box-shadow: 0 4px 14px rgba(0, 122, 255, 0.2);
            transition: var(--sv-transition);
            border: none;
        }

        .btn-sv-primary:hover {
            background: var(--sv-blue-dark) !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 122, 255, 0.3);
        }

        /* ── Hero Search Section ── */
        .sv-hero {
            padding: 140px 0 80px;
            background: radial-gradient(circle at 80% 20%, rgba(0, 122, 255, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 10% 80%, rgba(52, 199, 89, 0.03) 0%, transparent 40%),
                        #FFFFFF;
            border-bottom: 1px solid var(--sv-border);
            text-align: center;
        }

        .sv-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--sv-blue-light);
            color: var(--sv-blue-dark);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 24px;
        }

        .sv-hero h1 {
            font-size: clamp(32px, 5vw, 54px);
            font-weight: 800;
            color: var(--sv-navy);
            letter-spacing: -2px;
            line-height: 1.15;
            max-width: 800px;
            margin: 0 auto 20px;
        }

        .sv-hero h1 span {
            background: linear-gradient(135deg, var(--sv-blue) 0%, #0058D0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sv-hero p.lead {
            font-size: clamp(15px, 2vw, 18px);
            color: var(--sv-text-sub);
            max-width: 650px;
            margin: 0 auto 36px;
            line-height: 1.6;
        }

        /* Search Form */
        .sv-search-wrap {
            max-width: 660px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .sv-search-box {
            background: white;
            border-radius: var(--sv-radius-lg);
            padding: 8px 8px 8px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1.5px solid var(--sv-border);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            transition: var(--sv-transition);
        }

        .sv-search-box:focus-within {
            border-color: var(--sv-blue);
            box-shadow: 0 12px 36px rgba(0, 122, 255, 0.12);
        }

        .sv-search-box .search-icon {
            font-size: 20px;
            color: var(--sv-text-muted);
            flex-shrink: 0;
        }

        .sv-search-box input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 16px;
            color: var(--sv-text-main);
            background: transparent;
            padding: 10px 0;
        }

        .sv-search-box input::placeholder {
            color: var(--sv-text-muted);
            font-weight: 400;
        }

        .sv-search-box button {
            background: var(--sv-blue);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 28px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--sv-transition);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sv-search-box button:hover {
            background: var(--sv-blue-dark);
            transform: translateY(-1px);
        }

        /* ── Categories Grid ── */
        .sv-categories {
            padding: 80px 0;
            background: #FAFBFC;
        }

        .sv-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }

        .sv-category-card {
            background: white;
            border: 1px solid var(--sv-border);
            border-radius: var(--sv-radius-lg);
            padding: 36px 30px;
            height: 100%;
            transition: var(--sv-transition);
            display: flex;
            flex-direction: column;
            box-shadow: var(--sv-shadow);
        }

        .sv-category-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--sv-shadow-lg);
            border-color: rgba(0, 122, 255, 0.15);
        }

        .sv-card-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 24px;
            transition: var(--sv-transition);
        }

        .sv-category-card h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--sv-navy);
            margin-bottom: 12px;
            letter-spacing: -0.4px;
        }

        .sv-category-card p {
            font-size: 14px;
            color: var(--sv-text-sub);
            line-height: 1.6;
            margin-bottom: 24px;
            flex-grow: 1;
        }

        .sv-card-link-text {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--sv-blue);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: auto;
        }

        .sv-category-card:hover .sv-card-link-text {
            color: var(--sv-blue-dark);
        }

        /* ── Feature Highlight / Tablet Section ── */
        .sv-highlight {
            padding: 80px 0;
            background: white;
            border-top: 1px solid var(--sv-border);
            border-bottom: 1px solid var(--sv-border);
        }

        .sv-highlight-content {
            padding-right: 40px;
        }

        .sv-highlight-content h2 {
            font-size: clamp(28px, 4vw, 40px);
            font-weight: 800;
            color: var(--sv-navy);
            letter-spacing: -1.2px;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .sv-highlight-content p {
            font-size: 16px;
            color: var(--sv-text-sub);
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .btn-sv-outline {
            background: white;
            color: var(--sv-navy);
            border: 1.5px solid var(--sv-border);
            border-radius: 12px;
            padding: 12px 28px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--sv-transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-sv-outline:hover {
            border-color: var(--sv-navy);
            background: var(--sv-bg);
            color: var(--sv-navy);
        }

        /* Interactive Premium Simulated Tablet Mockup */
        .sv-tablet-mockup {
            background: #0A1128;
            border: 14px solid #1C2333;
            border-radius: 36px;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.25);
            aspect-ratio: 4 / 3;
            width: 100%;
            max-width: 540px;
            overflow: hidden;
            position: relative;
            padding: 12px;
            display: flex;
            flex-direction: column;
        }

        .sv-tablet-screen {
            background: #F4F6F9;
            flex: 1;
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            font-size: 11px;
        }

        .sv-tablet-header {
            background: white;
            height: 38px;
            border-bottom: 1px solid #E5E8ED;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 14px;
        }

        .sv-tablet-body {
            padding: 14px;
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sv-tablet-card {
            background: white;
            border-radius: 10px;
            border: 1px solid #E5E8ED;
            padding: 12px;
        }

        .sv-tablet-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #F0F2F5;
            padding: 8px 0;
        }
        .sv-tablet-row:last-child { border-bottom: none; }

        /* ── Footer ── */
        .sv-footer {
            background: #090E1A;
            color: rgba(255, 255, 255, 0.45);
            padding: 40px 32px;
            font-size: 13px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .sv-footer-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
            gap: 16px;
        }

        .sv-footer-links {
            display: flex;
            gap: 20px;
        }

        .sv-footer-links a {
            color: rgba(255, 255, 255, 0.45);
            text-decoration: none;
            transition: var(--sv-transition);
        }

        .sv-footer-links a:hover {
            color: white;
        }

        @media (max-width: 768px) {
            .sv-navbar { padding: 0 16px; }
            .sv-navbar-links { display: none; }
            .sv-footer-container {
                flex-direction: column;
                text-align: center;
            }
            .sv-footer-links {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>

    <!-- ════ NAVBAR ════ -->
    <nav class="sv-navbar">
        <a href="index.php" class="sv-navbar-brand">
            <div class="logo-box">SV</div>
            <div class="brand-text">
                <span class="brand-name">MediaAdmin</span>
                <span class="brand-sub">CareVisit Monitor</span>
            </div>
        </a>
        <div class="sv-navbar-links">
            <a href="index.php" class="active">Beranda</a>
            <a href="Pages/about.php">Tentang Kami</a>
            <a href="Pages/jadwal.php">Cek Jadwal</a>
            <a href="#kontak">Kontak</a>
            <a href="Pages/login.php" class="btn-sv-primary ms-3">Masuk Admin</a>
        </div>
    </nav>

    <!-- ════ HERO SEARCH SECTION ════ -->
    <section class="sv-hero">
        <div class="container">
            <div class="sv-hero-badge animate-in">
                🏥 SISTEM MONITORING TERPADU
            </div>
            <h1 class="animate-in">
                Pantau Perkembangan Kesehatan<br>
                <span>Keluarga dari Rumah</span>
            </h1>
            <p class="lead animate-in">
                Sistem informasi terpadu untuk mengecek hasil monitoring kunjungan dan status administratif pasien home care secara real-time dan transparan.
            </p>

            <div class="sv-search-wrap animate-in">
                <form action="Pages/jadwal.php" method="GET">
                    <div class="sv-search-box">
                        <span class="search-icon">🔍</span>
                        <input 
                            type="text" 
                            name="q" 
                            placeholder="Masukkan Kode Pasien atau NIK Dummy Anda..." 
                            required
                            autocomplete="off"
                        >
                        <button type="submit">Cari Data Monitoring →</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- ════ CATEGORIES SECTION ════ -->
    <section class="sv-categories">
        <div class="container">
            <div class="row g-4 justify-content-center">
                <!-- Card 1 -->
                <div class="col-md-4">
                    <a href="Pages/hipertensi-diabetes.php" class="sv-card-link">
                        <div class="sv-category-card">
                            <div class="sv-card-icon" style="background: var(--sv-blue-light); color: var(--sv-blue);">🩺</div>
                            <h3>Kategori Hipertensi & Diabetes</h3>
                            <p>Pemeriksaan tekanan darah dan gula darah berkala yang dicatat secara digital oleh tenaga medis profesional kami di lapangan.</p>
                            <div class="sv-card-link-text">Lihat Detail Layanan →</div>
                        </div>
                    </a>
                </div>

                <!-- Card 2 -->
                <div class="col-md-4">
                    <a href="Pages/lansia-pascarawat.php" class="sv-card-link">
                        <div class="sv-category-card">
                            <div class="sv-category-card">
                                <div class="sv-card-icon" style="background: #E8F8ED; color: #34C759;">🚶‍♂️</div>
                                <h3>Kategori Lansia & Pasca Rawat</h3>
                                <p>Monitoring pemulihan dan kondisi fisik oleh petugas lapangan untuk memastikan transisi pemulihan di rumah berjalan optimal.</p>
                                <div class="sv-card-link-text">Lihat Detail Layanan →</div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Card 3 -->
                <div class="col-md-4">
                    <a href="Pages/about.php" class="sv-card-link">
                        <div class="sv-category-card">
                            <div class="sv-card-icon" style="background: #F5EEFF; color: #AF52DE;">📋</div>
                            <h3>Catatan & Rekomendasi</h3>
                            <p>Akses cepat melihat hasil kunjungan petugas lapangan tanpa memberikan diagnosis medis mandiri, sebagai bahan konsultasi dokter.</p>
                            <div class="sv-card-link-text">Lihat Detail Layanan →</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ════ HIGHLIGHT / TABLET SECTION ════ -->
    <section class="sv-highlight">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="sv-highlight-content">
                        <h2>Efisiensi Monitoring<br>Dalam Satu Genggaman</h2>
                        <p>Antarmuka modern yang dirancang khusus untuk kemudahan navigasi keluarga pasien dalam memantau jadwal kunjungan dan rekam administratif harian.</p>
                        <a href="Pages/about.php" class="btn-sv-outline">Pelajari Lebih Lanjut →</a>
                    </div>
                </div>
                <div class="col-lg-6 d-flex justify-content-center">
                    <!-- High-fidelity CSS Simulated Tablet UI Mockup -->
                    <div class="sv-tablet-mockup">
                        <div class="sv-tablet-screen">
                            <div class="sv-tablet-header">
                                <span style="font-weight: 700; color: var(--sv-navy);">📋 SIVISIT Admin Dashboard</span>
                                <span style="background: #E8F1FF; color: var(--sv-blue); font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 20px;">Local DB Connected</span>
                            </div>
                            <div class="sv-tablet-body">
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="sv-tablet-card text-center" style="background: #FFF4E5;">
                                            <div style="font-size: 14px; font-weight: 800; color: #FF9500;">24</div>
                                            <div style="font-size: 8px; color: #8A4E00; font-weight: 600;">Lansia Binaan</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="sv-tablet-card text-center" style="background: #E8F8ED;">
                                            <div style="font-size: 14px; font-weight: 800; color: #34C759;">98%</div>
                                            <div style="font-size: 8px; color: #1A7A35; font-weight: 600;">Saturasi Stabil</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="sv-tablet-card text-center" style="background: #E8F1FF;">
                                            <div style="font-size: 14px; font-weight: 800; color: var(--sv-blue);">3</div>
                                            <div style="font-size: 8px; color: var(--sv-blue-dark); font-weight: 600;">Petugas Aktif</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="sv-tablet-card" style="flex: 1; display: flex; flex-direction: column;">
                                    <div style="font-weight: 700; color: var(--sv-navy); margin-bottom: 6px; font-size: 10px;">Laporan Terkini Kunjungan</div>
                                    <div class="sv-tablet-row">
                                        <div><strong>RM-001</strong> - Slamet</div>
                                        <span style="background:#E8F8ED;color:#1A7A35;padding:2px 6px;border-radius:4px;font-size:8px;font-weight:700;">STABIL</span>
                                    </div>
                                    <div class="sv-tablet-row">
                                        <div><strong>RM-002</strong> - Aminah</div>
                                        <span style="background:#FFF4E5;color:#8A4E00;padding:2px 6px;border-radius:4px;font-size:8px;font-weight:700;">KONTROL</span>
                                    </div>
                                    <div class="sv-tablet-row">
                                        <div><strong>RM-003</strong> - Rian</div>
                                        <span style="background:#E8F8ED;color:#1A7A35;padding:2px 6px;border-radius:4px;font-size:8px;font-weight:700;">STABIL</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ════ FOOTER ════ -->
    <footer class="sv-footer" id="kontak">
        <div class="sv-footer-container">
            <div>
                © 2026 MediaAdmin. Data encrypted (AES-256). ISO 27001 Certified.
            </div>
            <div class="sv-footer-links">
                <a href="#accessibility">Accessibility</a>
                <a href="#privacy">Privacy Policy</a>
                <a href="#terms">Terms of Service</a>
                <a href="#security">Security Disclosure</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>