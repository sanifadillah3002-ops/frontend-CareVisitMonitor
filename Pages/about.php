<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config.php';

$activeTab = $_GET['tab'] ?? 'tentang';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami — MediaAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sv-blue: #007AFF;
            --sv-blue-dark: #0058D0;
            --sv-blue-light: #E8F1FF;
            --sv-navy: #001A42;
            --sv-navy-mid: #002866;
            --sv-bg: #F4F6F9;
            --sv-surface: #FFFFFF;
            --sv-border: #E8ECF0;
            --sv-text-main: #1C1C1E;
            --sv-text-sub: #636366;
            --sv-text-muted: #8E8E93;
            --sv-radius: 14px;
            --sv-radius-lg: 20px;
            --sv-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
            --sv-shadow-lg: 0 16px 40px rgba(0, 0, 0, 0.08);
            --sv-transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--sv-bg);
            color: var(--sv-text-main);
            margin: 0; padding: 0;
            padding-top: 68px; /* For fixed navbar */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Navbar ── */
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
        }

        /* ── Breadcrumbs ── */
        .sv-breadcrumbs {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--sv-text-muted);
            margin-bottom: 12px;
        }

        .sv-breadcrumbs a {
            color: var(--sv-text-muted);
            text-decoration: none;
            transition: var(--sv-transition);
        }

        .sv-breadcrumbs a:hover {
            color: var(--sv-blue);
        }

        /* ── Tabs Controls ── */
        .sv-tab-controls {
            display: flex;
            gap: 8px;
            background: rgba(0, 26, 66, 0.04);
            padding: 6px;
            border-radius: 14px;
            display: inline-flex;
            margin-bottom: 30px;
        }

        .sv-tab-btn {
            border: none;
            background: transparent;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 600;
            color: var(--sv-text-sub);
            border-radius: 10px;
            cursor: pointer;
            transition: var(--sv-transition);
        }

        .sv-tab-btn.active {
            background: white;
            color: var(--sv-navy);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        /* ── Cards ── */
        .sv-content-card {
            background: white;
            border-radius: var(--sv-radius-lg);
            border: 1px solid var(--sv-border);
            padding: 36px;
            box-shadow: var(--sv-shadow);
            height: 100%;
        }

        .sv-content-card h2 {
            font-size: 26px;
            font-weight: 800;
            color: var(--sv-navy);
            letter-spacing: -0.8px;
            margin-bottom: 24px;
        }

        /* Visi & Misi Items */
        .sv-list-item {
            margin-bottom: 24px;
        }

        .sv-list-item h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--sv-text-main);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sv-list-item h4 .dot {
            width: 8px; height: 8px;
            background: var(--sv-blue);
            border-radius: 50%;
        }

        .sv-list-item p {
            font-size: 13.5px;
            color: var(--sv-text-sub);
            line-height: 1.6;
            margin: 0;
            padding-left: 16px;
        }

        /* Alert Callout */
        .sv-callout-red {
            background: #FFF0EF;
            border: 1px solid #FFD0CC;
            border-radius: 12px;
            padding: 18px;
            color: #C0291F;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.6;
            letter-spacing: 0.2px;
            margin-top: 24px;
        }

        /* Steps */
        .sv-step-flow {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sv-step-card {
            background: var(--sv-bg);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .sv-step-number {
            width: 36px; height: 36px;
            background: var(--sv-blue);
            color: white;
            font-size: 16px;
            font-weight: 700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sv-step-text h5 {
            font-size: 14px;
            font-weight: 700;
            color: var(--sv-navy);
            margin-bottom: 4px;
        }

        .sv-step-text p {
            font-size: 12.5px;
            color: var(--sv-text-sub);
            margin: 0;
            line-height: 1.5;
        }

        /* Badges */
        .sv-badge-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .sv-badge {
            background: var(--sv-bg);
            color: var(--sv-text-sub);
            font-size: 11px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid var(--sv-border);
        }

        .sv-badge-blue {
            background: var(--sv-blue-light);
            color: var(--sv-blue-dark);
            border-color: rgba(0, 122, 255, 0.15);
        }

        /* Guide Interactive Elements */
        .sv-simulated-input {
            background: var(--sv-bg);
            border: 1px solid var(--sv-border);
            border-radius: 8px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            margin: 12px 0;
            max-width: 240px;
        }

        .btn-simulated-download {
            background: var(--sv-bg);
            color: var(--sv-text-main);
            border: 1px solid var(--sv-border);
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--sv-transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
        }

        .btn-simulated-download:hover {
            background: #E8ECF0;
        }

        /* ── Footer ── */
        .sv-footer {
            background: #090E1A;
            color: rgba(255, 255, 255, 0.45);
            padding: 40px 32px;
            font-size: 13px;
            border-top: 1px solid rgba(255,255,255,0.06);
            margin-top: auto;
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
    </style>
</head>
<body>

    <!-- ════ NAVBAR ════ -->
    <nav class="sv-navbar">
        <a href="../index.php" class="sv-navbar-brand">
            <div class="logo-box">SV</div>
            <div class="brand-text">
                <span class="brand-name">MediaAdmin</span>
                <span class="brand-sub">CareVisit Monitor</span>
            </div>
        </a>
        <div class="sv-navbar-links">
            <a href="../index.php">Beranda</a>
            <a href="about.php" class="active">Tentang Kami</a>
            <a href="jadwal.php">Cek Jadwal</a>
            <a href="#kontak">Kontak</a>
            <a href="login.php" class="btn-sv-primary ms-3">Masuk Admin</a>
        </div>
    </nav>

    <!-- ════ MAIN CONTAINER ════ -->
    <div class="container py-5">
        
        <!-- Breadcrumbs -->
        <div class="sv-breadcrumbs">
            <a href="../index.php">Beranda</a> &gt; 
            <a href="about.php">Tentang Kami</a>
            <?php if ($activeTab === 'panduan'): ?>
                &gt; <span style="color: var(--sv-blue);">Panduan Pengguna Sistem</span>
            <?php endif; ?>
        </div>

        <!-- Tab Selector -->
        <div class="sv-tab-controls">
            <button class="sv-tab-btn <?= $activeTab === 'tentang' ? 'active' : '' ?>" onclick="window.location.href='about.php?tab=tentang'">
                Tentang MediaAdmin
            </button>
            <button class="sv-tab-btn <?= $activeTab === 'panduan' ? 'active' : '' ?>" onclick="window.location.href='about.php?tab=panduan'">
                Panduan Pengguna Sistem
            </button>
        </div>

        <!-- Tab Content 1: Tentang Kami -->
        <?php if ($activeTab === 'tentang'): ?>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="sv-content-card">
                        <h2>Tentang MediaAdmin CareVisit Monitor</h2>
                        <p style="font-size: 14.5px; color: var(--sv-text-sub); line-height: 1.7; margin-bottom: 30px;">
                            Mengenal sistem monitoring terpadu yang menjembatani transparansi pelaporan administratif antara petugas lapangan, institusi kesehatan, dan keluarga pasien.
                        </p>

                        <div class="sv-list-item">
                            <h4><div class="dot"></div> Visi & Misi Kami</h4>
                        </div>

                        <div class="sv-list-item">
                            <h4><div class="dot"></div> Transparansi Pelaporan</h4>
                            <p>Memastikan setiap kunjungan tercatat secara real-time untuk audit yang objektif dan transparan.</p>
                        </div>

                        <div class="sv-list-item">
                            <h4><div class="dot"></div> Efisiensi Administratif</h4>
                            <p>Reduksi beban kerja manual petugas lapangan melalui otomatisasi rekapitulasi data klinis sederhana.</p>
                        </div>

                        <div class="sv-list-item">
                            <h4><div class="dot"></div> Akurasi Data Kunjungan</h4>
                            <p>Verifikasi geografis dan stempel waktu digital untuk validasi operasional yang akuntabel.</p>
                        </div>

                        <div class="sv-callout-red">
                            ⚠️ PENTING: PLATFORM INI DIKEMBANGKAN KHUSUS SEBAGAI LAYANAN MONITORING ADMINISTRATIF DAN SIMULASI DATA, BUKAN UNTUK MEMBERIKAN LAYANAN DIAGNOSIS MEDIS DARURAT MANDIRI.
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="sv-content-card d-flex flex-column justify-content-between">
                        <div>
                            <h2>Alur Sistem CareVisit Monitor</h2>
                            <p style="font-size: 14.5px; color: var(--sv-text-sub); line-height: 1.7; margin-bottom: 30px;">
                                Tiga fase terintegrasi yang menjamin akurasi dan kemudahan penyampaian hasil rekapitulasi data home care.
                            </p>

                            <div class="sv-step-flow">
                                <div class="sv-step-card">
                                    <div class="sv-step-number">1</div>
                                    <div class="sv-step-text">
                                        <h5>Kunjungan & Input Log</h5>
                                        <p>Petugas lapangan menginput log kunjungan melalui aplikasi terminal atau dashboard resmi.</p>
                                    </div>
                                </div>
                                <div class="sv-step-card">
                                    <div class="sv-step-number">2</div>
                                    <div class="sv-step-text">
                                        <h5>Rekapitulasi & Validasi</h5>
                                        <p>Sistem MediaAdmin memvalidasi data berdasarkan parameter parameter instansi terkait.</p>
                                    </div>
                                </div>
                                <div class="sv-step-card">
                                    <div class="sv-step-number">3</div>
                                    <div class="sv-step-text">
                                        <h5>Akses Publik & Unduh</h5>
                                        <p>Dashboard tersedia untuk pemantauan keluarga dan laporan rekapitulasi administratif.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="sv-badge-row">
                                <span class="sv-badge sv-badge-blue">Sertifikasi Valid/Aman</span>
                                <span class="sv-badge">Tipe Data Simulasi</span>
                                <span class="sv-badge">Skala Akses Desktop</span>
                            </div>

                            <a href="about.php?tab=panduan" class="btn btn-sv-primary w-100 text-center py-3 mt-4">
                                Pelajari Panduan Penggunaan Sistem →
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Tab Content 2: Panduan Pengguna -->
        <?php else: ?>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="sv-content-card">
                        <h2>Panduan Pengguna Sistem CareVisit Monitor</h2>
                        <p style="font-size: 14.5px; color: var(--sv-text-sub); line-height: 1.7; margin-bottom: 30px;">
                            Pelajari langkah operasional penggunaan platform pelaporan administratif untuk memastikan transparansi pemantauan pasien antara petugas lapangan dan keluarga.
                        </p>

                        <div class="sv-list-item">
                            <h4 style="font-size: 16px; color: var(--sv-navy);"><div class="dot"></div> Panduan untuk Keluarga Pasien</h4>
                        </div>

                        <div class="sv-step-flow mt-3">
                            <div class="sv-step-card" style="background: white; border: 1px solid var(--sv-border);">
                                <div class="sv-step-number" style="background:#E5F0FF; color:var(--sv-blue);">1</div>
                                <div class="sv-step-text">
                                    <h5>Akses Menu Pencarian</h5>
                                    <p>Navigasi ke halaman utama dan pilih menu "Cek Jadwal" atau gunakan kotak pencarian global di sudut kanan atas layout.</p>
                                </div>
                            </div>
                            <div class="sv-step-card" style="background: white; border: 1px solid var(--sv-border);">
                                <div class="sv-step-number" style="background:#E5F0FF; color:var(--sv-blue);">2</div>
                                <div class="sv-step-text">
                                    <h5>Masukkan Kode Unik Pasien</h5>
                                    <p>Ketik kode pasien yang telah diberikan oleh pihak administrasi rumah sakit.</p>
                                    <div class="sv-simulated-input">
                                        <span>🔑 PS003</span>
                                        <span style="color:var(--sv-blue);font-weight:700;font-size:10px;">VALID</span>
                                    </div>
                                </div>
                            </div>
                            <div class="sv-step-card" style="background: white; border: 1px solid var(--sv-border);">
                                <div class="sv-step-number" style="background:#E5F0FF; color:var(--sv-blue);">3</div>
                                <div class="sv-step-text">
                                    <h5>Tinjau Riwayat & Unduh Berkas</h5>
                                    <p>Periksa log kunjungan terbaru. Anda dapat mengunduh laporan detail dalam format PDF untuk arsip pribadi.</p>
                                    <button class="btn-simulated-download">💾 Unduh Laporan PDF</button>
                                </div>
                            </div>
                        </div>

                        <div class="sv-callout-red" style="font-size:11px;">
                            ⚠️ PENTING: Panduan ini disusun untuk mempermudah navigasi administratif sistem. Jika terjadi kendala teknis pada validasi kode pasien, segera hubungi pihak instansi kesehatan terkait.
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="sv-content-card d-flex flex-column justify-content-between">
                        <div>
                            <h2>Alur Operasional Petugas Lapangan</h2>
                            <p style="font-size: 14.5px; color: var(--sv-text-sub); line-height: 1.7; margin-bottom: 30px;">
                                Tata cara pencatatan rekam medis sederhana untuk memastikan integritas dan sinkronisasi data real-time.
                            </p>

                            <div class="sv-step-flow">
                                <div class="sv-step-card">
                                    <div class="sv-step-number">1</div>
                                    <div class="sv-step-text">
                                        <h5>Input Log Lapangan</h5>
                                        <p>Petugas lapangan memasukkan data pemeriksaan real-time ke dalam sistem melalui perangkat mobile, termasuk tanda vital dasar dan catatan klinis ringkas.</p>
                                    </div>
                                </div>
                                <div class="sv-step-card">
                                    <div class="sv-step-number">2</div>
                                    <div class="sv-step-text">
                                        <h5>Sinkronisasi Data</h5>
                                        <p>Sistem secara otomatis mengenkripsi (AES-256) dan menyinkronkan data ke server pusat MediaAdmin untuk divalidasi oleh sistem pakar.</p>
                                    </div>
                                </div>
                                <div class="sv-step-card">
                                    <div class="sv-step-number">3</div>
                                    <div class="sv-step-text">
                                        <h5>Penerbitan Rekomendasi</h5>
                                        <p>Setelah divalidasi, sistem menerbitkan ringkasan status dan rekomendasi tindakan lanjut yang dapat diakses langsung oleh keluarga melalui portal pencarian.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="sv-badge-row">
                                <span class="sv-badge sv-badge-blue">Stabil/Aman</span>
                                <span class="sv-badge">Akses Publik</span>
                                <span class="sv-badge">Format Digital</span>
                            </div>

                            <p style="font-size: 12px; color: var(--sv-text-muted); margin-top: 24px; display: flex; align-items: center; gap: 6px;">
                                💻 Sistem ini dioptimalkan untuk akses desktop guna kenyamanan pemantauan data.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

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
