<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config.php';

$activeTab = $_GET['tab'] ?? 'tentang';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami — SIVISIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="global.css" rel="stylesheet">
    <link href="landing.css" rel="stylesheet">
    <link href="public-nav.css" rel="stylesheet">
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
            padding-top: 64px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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

        /* Badges */
        .sv-badge-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .sv-badge-wrap { position: relative; }

        .sv-badge {
            background: var(--sv-bg);
            color: var(--sv-text-sub);
            font-size: 11px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
            border: 1.5px solid var(--sv-border);
            cursor: pointer;
            transition: var(--sv-transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            user-select: none;
        }
        .sv-badge:hover { border-color: var(--sv-blue); background: #fff; }
        .sv-badge.active { border-color: var(--sv-blue); background: var(--sv-blue-light); color: var(--sv-blue-dark); }

        .sv-badge-blue {
            background: var(--sv-blue-light);
            color: var(--sv-blue-dark);
            border-color: rgba(0, 122, 255, 0.15);
        }

        .sv-badge-body {
            max-height: 0; overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            padding: 0 12px;
            font-size: 12px;
            color: var(--sv-text-sub);
            line-height: 1.6;
        }
        .sv-badge-body.open {
            max-height: 200px;
            padding: 10px 12px 4px;
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

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.15; }
            50% { transform: translateY(-30px) scale(1.05); opacity: 0.25; }
        }
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(80px);
        }
        .bg-orb-1 {
            width: 500px; height: 500px;
            background: rgba(0,122,255,0.12);
            top: -100px; right: -100px;
            animation: float 8s ease-in-out infinite;
        }
        .bg-orb-2 {
            width: 400px; height: 400px;
            background: rgba(0,122,255,0.08);
            bottom: -80px; left: -80px;
            animation: float 10s ease-in-out infinite reverse;
        }
        .bg-orb-3 {
            width: 300px; height: 300px;
            background: rgba(52,199,89,0.06);
            top: 50%; left: 50%;
            animation: float 12s ease-in-out infinite;
        }
        .content-img {
            width: 100%; height: 200px;
            background-size: cover; background-position: center;
            border-radius: 12px; margin-bottom: 20px;
        }
        .sv-content-card, .sv-footer { position: relative; z-index: 1; }

        /* ── Interactive About Accordion ── */
        .sv-about-acc { display: flex; flex-direction: column; gap: 10px; }
        .sv-about-item {
            background: var(--sv-bg);
            border-radius: 12px;
            border: 1.5px solid transparent;
            overflow: hidden;
            cursor: pointer;
            transition: var(--sv-transition);
        }
        .sv-about-item:hover { border-color: var(--sv-blue-light); }
        .sv-about-item.active { border-color: var(--sv-blue); background: #fff; }
        .sv-about-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            user-select: none;
        }
        .sv-about-dot {
            width: 10px; height: 10px;
            background: var(--sv-blue);
            border-radius: 50%;
            flex-shrink: 0;
            transition: var(--sv-transition);
        }
        .sv-about-item.active .sv-about-dot { background: var(--sv-blue-dark); box-shadow: 0 0 0 3px var(--sv-blue-light); }
        .sv-about-label { flex: 1; font-size: 14px; font-weight: 700; color: var(--sv-navy); }
        .sv-about-arrow {
            font-size: 12px; color: var(--sv-text-muted);
            transition: transform 0.3s ease;
        }
        .sv-about-item.active .sv-about-arrow { transform: rotate(180deg); color: var(--sv-blue); }
        .sv-about-body {
            max-height: 0; overflow: hidden;
            transition: max-height 0.35s ease, padding 0.35s ease;
            padding: 0 16px;
        }
        .sv-about-item.active .sv-about-body {
            max-height: 400px;
            padding: 0 16px 16px;
        }
        .sv-about-body p { font-size: 13px; color: var(--sv-text-sub); line-height: 1.7; margin: 0; }
        .sv-about-body ul { margin: 8px 0 0; padding: 0 0 0 18px; font-size: 12.5px; color: var(--sv-text-sub); }
        .sv-about-body ul li { margin-bottom: 4px; }

        /* ── Interactive Step Cards ── */
        .sv-about-steps { display: flex; flex-direction: column; gap: 10px; }
        .sv-about-step {
            background: var(--sv-bg);
            border-radius: 12px;
            border: 1.5px solid transparent;
            overflow: hidden;
            cursor: pointer;
            transition: var(--sv-transition);
        }
        .sv-about-step:hover { border-color: var(--sv-blue-light); }
        .sv-about-step.active { border-color: var(--sv-blue); background: #fff; }
        .sv-about-step-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            user-select: none;
        }
        .sv-about-step-num {
            width: 36px; height: 36px;
            background: var(--sv-blue);
            color: white;
            font-size: 15px;
            font-weight: 700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: var(--sv-transition);
        }
        .sv-about-step.active .sv-about-step-num { background: var(--sv-blue-dark); box-shadow: 0 0 0 4px var(--sv-blue-light); }
        .sv-about-step-label { flex: 1; font-size: 14px; font-weight: 700; color: var(--sv-navy); }
        .sv-about-step-arrow {
            font-size: 12px; color: var(--sv-text-muted);
            transition: transform 0.3s ease;
        }
        .sv-about-step.active .sv-about-step-arrow { transform: rotate(180deg); color: var(--sv-blue); }
        .sv-about-step-body {
            max-height: 0; overflow: hidden;
            transition: max-height 0.35s ease, padding 0.35s ease;
            padding: 0 16px;
        }
        .sv-about-step.active .sv-about-step-body {
            max-height: 400px;
            padding: 0 16px 16px 66px;
        }
        .sv-about-step-body p { font-size: 13px; color: var(--sv-text-sub); line-height: 1.7; margin: 0; }
        .sv-about-step-body .sv-step-tip {
            background: #fff;
            border: 1px solid var(--sv-border);
            border-radius: 10px;
            padding: 12px;
            font-size: 12px;
            color: var(--sv-text-sub);
            line-height: 1.6;
            margin-top: 8px;
        }
        .sv-about-step-body .sv-step-tip strong { color: var(--sv-navy); }
    </style>
</head>
<body>

    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-orb bg-orb-3"></div>

    <?php $navActive = 'about'; $navFromPages = true; include 'components/public-navbar.php'; ?>

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
                Tentang SIVISIT
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
                        <h2>Tentang SIVISIT CareVisit Monitor</h2>
                        <p style="font-size: 14.5px; color: var(--sv-text-sub); line-height: 1.7; margin-bottom: 24px;">
                            Sistem monitoring pasien home care terpadu yang dikembangkan oleh Kelompok 9 S1 Informatika — ITSK Rs Dr Soepraoen Malang. Platform ini menjembatani transparansi pemantauan antara petugas lapangan dan keluarga pasien.
                        </p>

                        <div class="sv-about-acc">
                            <div class="sv-about-item" onclick="toggleAbout(this)">
                                <div class="sv-about-header">
                                    <div class="sv-about-dot"></div>
                                    <span class="sv-about-label">Visi & Misi Kami</span>
                                    <span class="sv-about-arrow">▾</span>
                                </div>
                                <div class="sv-about-body">
                                    <p>Menciptakan ekosistem monitoring kesehatan berbasis masyarakat yang transparan, efisien, dan mudah diakses oleh semua pihak.</p>
                                    <ul>
                                        <li>Meningkatkan kualitas pelayanan home care melalui digitalisasi</li>
                                        <li>Memberdayakan keluarga dalam memantau kondisi pasien</li>
                                        <li>Mendukung percepatan data rekam medis berbasis teknologi</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="sv-about-item" onclick="toggleAbout(this)">
                                <div class="sv-about-header">
                                    <div class="sv-about-dot"></div>
                                    <span class="sv-about-label">Transparansi Pemantauan</span>
                                    <span class="sv-about-arrow">▾</span>
                                </div>
                                <div class="sv-about-body">
                                    <p>Setiap kunjungan tercatat secara digital dan dapat diakses keluarga pasien kapan saja melalui portal pencarian.</p>
                                    <ul>
                                        <li>Riwayat monitoring tersimpan lengkap dengan timestamp</li>
                                        <li>Akses aman dengan kode pasien unik</li>
                                        <li>Data real-time dari petugas ke sistem pusat</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="sv-about-item" onclick="toggleAbout(this)">
                                <div class="sv-about-header">
                                    <div class="sv-about-dot"></div>
                                    <span class="sv-about-label">Efisiensi Administratif</span>
                                    <span class="sv-about-arrow">▾</span>
                                </div>
                                <div class="sv-about-body">
                                    <p>Mengurangi beban administrasi manual petugas dengan pencatatan data kesehatan terstruktur dan real-time.</p>
                                    <ul>
                                        <li>Form monitoring digital terintegrasi</li>
                                        <li>Rekapitulasi data otomatis</li>
                                        <li>Eliminasi duplikasi catatan manual</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="sv-about-item" onclick="toggleAbout(this)">
                                <div class="sv-about-header">
                                    <div class="sv-about-dot"></div>
                                    <span class="sv-about-label">Kemudahan Akses</span>
                                    <span class="sv-about-arrow">▾</span>
                                </div>
                                <div class="sv-about-body">
                                    <p>Keluarga dapat memantau riwayat monitoring, jadwal kunjungan, dan status kesehatan pasien secara mandiri.</p>
                                    <ul>
                                        <li>Cukup dengan kode pasien dari petugas</li>
                                        <li>Akses 24/7 dari perangkat desktop</li>
                                        <li>Tampilan data yang informatif dan mudah dipahami</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="sv-callout-red">
                            ⚠️ PENTING: PLATFORM INI DIKEMBANGKAN SEBAGAI SISTEM MONITORING ADMINISTRATIF DAN SIMULASI DATA, BUKAN UNTUK MEMBERIKAN DIAGNOSIS MEDIS ATAU LAYANAN DARURAT.
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="sv-content-card d-flex flex-column justify-content-between">
                        <div>
                            <div class="content-img" style="background-image:url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600&h=300&fit=crop');"></div>
                            <h2>Alur Sistem SIVISIT CareVisit Monitor</h2>
                            <p style="font-size: 14.5px; color: var(--sv-text-sub); line-height: 1.7; margin-bottom: 30px;">
                                Tiga fase terintegrasi yang menjamin akurasi dan kemudahan penyampaian hasil monitoring home care.
                            </p>

                            <div class="sv-about-steps">
                                <div class="sv-about-step" onclick="toggleAboutStep(this)">
                                    <div class="sv-about-step-header">
                                        <div class="sv-about-step-num">1</div>
                                        <span class="sv-about-step-label">Kunjungan & Pencatatan</span>
                                        <span class="sv-about-step-arrow">▾</span>
                                    </div>
                                    <div class="sv-about-step-body">
                                        <p>Petugas mencatat data monitoring pasien seperti tensi, suhu, detak jantung, dan keluhan melalui sistem.</p>
                                        <div class="sv-step-tip">
                                            <strong>📋 Yang dicatat:</strong> Tensi darah, suhu tubuh, detak jantung (nadi), saturasi oksigen (SpO₂), keluhan pasien, serta rekomendasi awal petugas.
                                        </div>
                                    </div>
                                </div>
                                <div class="sv-about-step" onclick="toggleAboutStep(this)">
                                    <div class="sv-about-step-header">
                                        <div class="sv-about-step-num">2</div>
                                        <span class="sv-about-step-label">Validasi & Rekapitulasi</span>
                                        <span class="sv-about-step-arrow">▾</span>
                                    </div>
                                    <div class="sv-about-step-body">
                                        <p>Sistem memvalidasi dan merekapitulasi data monitoring untuk memastikan kelengkapan informasi.</p>
                                        <div class="sv-step-tip">
                                            <strong>🔎 Proses:</strong> Data diverifikasi formatnya, direkapitulasi, dan disimpan dalam database terpusat untuk diakses oleh keluarga pasien.
                                        </div>
                                    </div>
                                </div>
                                <div class="sv-about-step" onclick="toggleAboutStep(this)">
                                    <div class="sv-about-step-header">
                                        <div class="sv-about-step-num">3</div>
                                        <span class="sv-about-step-label">Akses Keluarga & Publik</span>
                                        <span class="sv-about-step-arrow">▾</span>
                                    </div>
                                    <div class="sv-about-step-body">
                                        <p>Keluarga pasien dapat memantau riwayat monitoring dan status terkini melalui portal pencarian.</p>
                                        <div class="sv-step-tip">
                                            <strong>👨‍👩‍👧‍👦 Cara akses:</strong> Masukkan kode pasien atau NIK pada form pencarian di halaman <strong>Cek Jadwal</strong>. Data monitoring lengkap dengan grafik tren akan ditampilkan.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="sv-badge-row">
                                <div class="sv-badge-wrap">
                                    <span class="sv-badge sv-badge-blue" onclick="toggleBadge(this)"><?= sf_icon('lock', 14) ?> Sertifikasi Valid/Aman</span>
                                    <div class="sv-badge-body">Data monitoring tersimpan dalam sistem terenkripsi dengan akses terbatas dan teraudit.</div>
                                </div>
                                <div class="sv-badge-wrap">
                                    <span class="sv-badge" onclick="toggleBadge(this)"><?= sf_icon('doc-text', 14) ?> Tipe Data Simulasi</span>
                                    <div class="sv-badge-body">Seluruh data yang ditampilkan adalah data simulasi/dummy untuk keperluan pengembangan sistem.</div>
                                </div>
                                <div class="sv-badge-wrap">
                                    <span class="sv-badge" onclick="toggleBadge(this)"><?= sf_icon('monitor', 14) ?> Skala Akses Desktop</span>
                                    <div class="sv-badge-body">Sistem dioptimalkan untuk akses desktop/laptop guna kenyamanan pemantauan data.</div>
                                </div>
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
                        <h2>Panduan Pengguna SIVISIT CareVisit Monitor</h2>
                        <p style="font-size: 14.5px; color: var(--sv-text-sub); line-height: 1.7; margin-bottom: 30px;">
                            Pelajari cara menggunakan platform monitoring pasien home care untuk memantau kondisi kesehatan keluarga Anda.
                        </p>

                        <h4 style="font-size: 16px; font-weight: 700; color: var(--sv-navy); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                            <span style="width:8px;height:8px;background:var(--sv-blue);border-radius:50%;display:inline-block;"></span>
                            Panduan untuk Keluarga Pasien
                        </h4>

                        <div class="sv-about-steps">
                            <div class="sv-about-step" onclick="toggleAboutStep(this)">
                                <div class="sv-about-step-header">
                                    <div class="sv-about-step-num" style="background:#E5F0FF; color:var(--sv-blue);">1</div>
                                    <span class="sv-about-step-label">Akses Menu Pencarian</span>
                                    <span class="sv-about-step-arrow">▾</span>
                                </div>
                                <div class="sv-about-step-body">
                                    <p>Buka halaman utama dan pilih menu <strong>"Cek Jadwal"</strong> atau klik tombol <strong>"Cari Data Pasien"</strong> di banner.</p>
                                    <div class="sv-step-tip">
                                        <strong>📍 Navigasi:</strong> Menu "Cek Jadwal" tersedia di navbar atas. Form pencarian dapat dibuka dengan mengklik tombol <strong>"Cari Data Pasien"</strong>.
                                    </div>
                                </div>
                            </div>
                            <div class="sv-about-step" onclick="toggleAboutStep(this)">
                                <div class="sv-about-step-header">
                                    <div class="sv-about-step-num" style="background:#E5F0FF; color:var(--sv-blue);">2</div>
                                    <span class="sv-about-step-label">Masukkan Kode / NIK Pasien</span>
                                    <span class="sv-about-step-arrow">▾</span>
                                </div>
                                <div class="sv-about-step-body">
                                    <p>Ketik kode pasien (contoh: RM-2026-0001) atau NIK yang telah diberikan oleh petugas kesehatan.</p>
                                    <div class="sv-step-tip">
                                        <strong>✏️ Contoh:</strong> <code>RM-2026-0001</code> atau nomor NIK 16 digit. Pastikan tidak ada spasi di awal/akhir.
                                    </div>
                                </div>
                            </div>
                            <div class="sv-about-step" onclick="toggleAboutStep(this)">
                                <div class="sv-about-step-header">
                                    <div class="sv-about-step-num" style="background:#E5F0FF; color:var(--sv-blue);">3</div>
                                    <span class="sv-about-step-label">Tinjau Riwayat Monitoring</span>
                                    <span class="sv-about-step-arrow">▾</span>
                                </div>
                                <div class="sv-about-step-body">
                                    <p>Periksa catatan monitoring terbaru, lihat grafik tren tensi dan suhu, serta rekomendasi dari petugas kesehatan.</p>
                                    <div class="sv-step-tip">
                                        <strong>📊 Data yang tampil:</strong> Profil pasien, riwayat kunjungan, grafik tren tekanan darah & suhu, serta status kondisi terkini.
                                    </div>
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
                            <div class="content-img" style="background-image:url('https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=600&h=300&fit=crop');"></div>
                            <h2>Informasi Data Monitoring</h2>
                            <p style="font-size: 14.5px; color: var(--sv-text-sub); line-height: 1.7; margin-bottom: 30px;">
                                Data yang ditampilkan dalam sistem ini merupakan data simulasi untuk keperluan pengembangan dan demonstrasi.
                            </p>

                            <div class="sv-about-steps">
                                <div class="sv-about-step" onclick="toggleAboutStep(this)">
                                    <div class="sv-about-step-header">
                                        <div class="sv-about-step-num">1</div>
                                        <span class="sv-about-step-label">Data Pasien</span>
                                        <span class="sv-about-step-arrow">▾</span>
                                    </div>
                                    <div class="sv-about-step-body">
                                        <p>Informasi identitas pasien, kategori binaan, dan kontak keluarga yang terdaftar dalam sistem monitoring.</p>
                                        <div class="sv-step-tip">
                                            <strong>🏷️ Detail:</strong> Nama, kode pasien (RM), NIK, kategori binaan (Lansia/Hipertensi/Diabetes/dll), usia, alamat, dan nomor kontak darurat keluarga.
                                        </div>
                                    </div>
                                </div>
                                <div class="sv-about-step" onclick="toggleAboutStep(this)">
                                    <div class="sv-about-step-header">
                                        <div class="sv-about-step-num">2</div>
                                        <span class="sv-about-step-label">Catatan Monitoring</span>
                                        <span class="sv-about-step-arrow">▾</span>
                                    </div>
                                    <div class="sv-about-step-body">
                                        <p>Riwayat pemeriksaan tanda vital seperti tensi darah, suhu tubuh, detak jantung, dan saturasi oksigen.</p>
                                        <div class="sv-step-tip">
                                            <strong>📈 Tanda vital:</strong> Tensi darah (sistolik/diastolik), suhu tubuh (°C), detak jantung/nadi (bpm), saturasi oksigen (%), serta keluhan dan catatan petugas.
                                        </div>
                                    </div>
                                </div>
                                <div class="sv-about-step" onclick="toggleAboutStep(this)">
                                    <div class="sv-about-step-header">
                                        <div class="sv-about-step-num">3</div>
                                        <span class="sv-about-step-label">Status & Rekomendasi</span>
                                        <span class="sv-about-step-arrow">▾</span>
                                    </div>
                                    <div class="sv-about-step-body">
                                        <p>Sistem memberikan status kondisi pasien (Stabil, Perlu Kontrol, Perlu Rujukan) beserta rekomendasi tindak lanjut.</p>
                                        <div class="sv-step-tip">
                                            <strong>🟢🟡🔴 Status:</strong> <strong>Stabil</strong> (hijau) — kondisi baik, <strong>Perlu Kontrol</strong> (kuning) — perlu pemantauan lanjut, <strong>Perlu Rujukan</strong> (merah) — memerlukan penanganan medis lebih lanjut.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="sv-badge-row">
                                <div class="sv-badge-wrap">
                                    <span class="sv-badge sv-badge-blue" onclick="toggleBadge(this)"><?= sf_icon('checkmark-circle', 14) ?> Stabil/Aman</span>
                                    <div class="sv-badge-body">Sistem berjalan dengan baik dan data monitoring tersimpan secara aman dalam database terpusat.</div>
                                </div>
                                <div class="sv-badge-wrap">
                                    <span class="sv-badge" onclick="toggleBadge(this)"><?= sf_icon('globe', 14) ?> Akses Publik</span>
                                    <div class="sv-badge-body">Keluarga pasien dapat mengakses data monitoring kapan saja melalui portal publik dengan kode pasien.</div>
                                </div>
                                <div class="sv-badge-wrap">
                                    <span class="sv-badge" onclick="toggleBadge(this)"><?= sf_icon('doc-text', 14) ?> Format Digital</span>
                                    <div class="sv-badge-body">Seluruh catatan monitoring tersaji dalam format digital terstruktur, mudah dibaca dan diunduh.</div>
                                </div>
                            </div>


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
                Sivisit-Kelompok 9 S1 Informatika UAS Pemrograman WEB ITSK Rs Dr Soepraoen Malang — Data simulasi, bukan diagnosis medis.
            </div>
            <div class="sv-footer-links">
                <a href="#accessibility">Accessibility</a>
                <a href="#privacy">Privacy Policy</a>
                <a href="#terms">Terms of Service</a>
                <a href="#security">Security Disclosure</a>
            </div>
        </div>
    </footer>

    <script>
    function toggleAbout(el) {
        el.classList.toggle('active');
    }

    function toggleAboutStep(el) {
        el.classList.toggle('active');
    }

    function toggleBadge(el) {
        el.classList.toggle('active');
        const body = el.nextElementSibling;
        if (body) body.classList.toggle('open');
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
