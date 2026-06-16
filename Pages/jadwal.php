<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config.php';

$query = trim($_GET['q'] ?? '');
$categoryFilter = $_GET['category'] ?? '';
$result = null;
$error = '';
$history = [];

if (!empty($query)) {
    // Hit Laravel API (singular endpoint /patients/{id}/monitoring)
    $apiResult = callAPI('GET', '/patients/' . urlencode($query) . '/monitoring');

    if ($apiResult['status_code'] === 200 && isset($apiResult['response']['data'])) {
        $data = $apiResult['response']['data'];
        
        // Filter by category if requested
        if (empty($categoryFilter) || strtolower($data['patient_category'] ?? '') === strtolower($categoryFilter)) {
            $result = $data;
            $history = $data['monitorings'] ?? [];
            
            // Sort monitorings desc
            if (!empty($history)) {
                usort($history, fn($a, $b) => 
                    strtotime(($b['monitoring_date'] ?? '') . ' ' . ($b['monitoring_time'] ?? '00:00:00')) <=> 
                    strtotime(($a['monitoring_date'] ?? '') . ' ' . ($a['monitoring_time'] ?? '00:00:00'))
                );
            }
        } else {
            $error = 'Pasien ditemukan, namun kategori tidak cocok dengan filter "' . htmlspecialchars($categoryFilter) . '".';
        }
    } elseif ($apiResult['status_code'] === 404) {
        $error = 'Pasien dengan kode/NIK "<strong>' . htmlspecialchars($query) . '</strong>" tidak ditemukan dalam database.';
    } else {
        $error = $apiResult['response']['message'] ?? 'Terjadi kesalahan teknis saat mencari data.';
    }
}

function getStatusBadge($status) {
    $s = strtolower($status ?? '');
    if (str_contains($s, 'stable') || str_contains($s, 'stabil')) {
        return '<span class="sv-badge sv-badge-stable">✅ Stabil</span>';
    }
    if (str_contains($s, 'referral') || str_contains($s, 'rujukan')) {
        return '<span class="sv-badge sv-badge-referral">🚨 Perlu Rujukan</span>';
    }
    return '<span class="sv-badge sv-badge-control">⚠️ Perlu Kontrol</span>';
}

function calculateAge($dob) {
    if (empty($dob)) return '-';
    try {
        return (new DateTime())->diff(new DateTime($dob))->y . ' Tahun';
    } catch (Exception $e) {
        return '-';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal & Pencarian Pasien — MediaAdmin</title>
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
            padding-top: 68px;
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
            margin-bottom: 20px;
        }

        .sv-breadcrumbs a {
            color: var(--sv-text-muted);
            text-decoration: none;
        }

        .sv-breadcrumbs a:hover {
            color: var(--sv-blue);
        }

        /* ── Cards ── */
        .sv-content-card {
            background: white;
            border-radius: var(--sv-radius-lg);
            border: 1px solid var(--sv-border);
            padding: 30px;
            box-shadow: var(--sv-shadow);
            margin-bottom: 24px;
        }

        .sv-content-card h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--sv-navy);
            letter-spacing: -0.8px;
            margin-bottom: 12px;
        }

        .sv-card-subtitle {
            font-size: 14px;
            color: var(--sv-text-sub);
            line-height: 1.6;
            margin-bottom: 24px;
        }

        /* Lists */
        .sv-list-item {
            margin-bottom: 20px;
        }

        .sv-list-item h5 {
            font-size: 14px;
            font-weight: 700;
            color: var(--sv-text-main);
            margin-bottom: 4px;
        }

        .sv-list-item p {
            font-size: 13px;
            color: var(--sv-text-sub);
            margin: 0;
            line-height: 1.5;
        }

        /* Callout Box */
        .sv-callout {
            background: #FFF4E5;
            border: 1px solid #FFE0A3;
            color: #8A4E00;
            border-radius: 12px;
            padding: 16px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.6;
        }

        /* Agenda Timeline */
        .sv-agenda-timeline {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .sv-agenda-item {
            background: var(--sv-bg);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .sv-agenda-info h6 {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--sv-navy);
            margin-bottom: 4px;
        }

        .sv-agenda-info p {
            font-size: 12px;
            color: var(--sv-text-sub);
            margin: 0;
        }

        /* Visual Banner */
        .sv-visual-banner {
            background: linear-gradient(135deg, var(--sv-navy) 0%, var(--sv-navy-mid) 100%);
            border-radius: var(--sv-radius-lg);
            padding: 40px;
            color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 240px;
            margin-bottom: 24px;
        }

        .sv-visual-banner::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(0, 122, 255, 0.15) 0%, transparent 70%);
            bottom: -100px; right: -50px;
            border-radius: 50%;
        }

        /* Form fields */
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--sv-navy);
            margin-bottom: 6px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1.5px solid var(--sv-border);
            padding: 10px 14px;
            font-size: 14px;
            transition: var(--sv-transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--sv-blue);
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
        }

        /* Vitals Grid */
        .sv-vitals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .sv-vital-card {
            background: var(--sv-bg);
            border-radius: 10px;
            padding: 12px;
            text-align: center;
        }

        .sv-vital-val {
            font-size: 16px;
            font-weight: 800;
            color: var(--sv-navy);
        }

        .sv-vital-lbl {
            font-size: 10.5px;
            color: var(--sv-text-muted);
            margin-top: 2px;
        }

        /* Badges */
        .sv-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
        }

        .sv-badge-stable { background: #E8F8ED; color: #1A7A35; }
        .sv-badge-control { background: #FFF4E5; color: #8A4E00; }
        .sv-badge-referral { background: #FFF0EF; color: #C0291F; }

        /* Timeline */
        .sv-timeline {
            position: relative;
            padding-left: 24px;
            margin-top: 24px;
        }

        .sv-timeline::before {
            content: '';
            position: absolute;
            left: 7px; top: 8px; bottom: 8px;
            width: 2px;
            background: var(--sv-border);
        }

        .sv-timeline-item {
            position: relative;
            margin-bottom: 24px;
        }

        .sv-timeline-item::before {
            content: '';
            position: absolute;
            left: -21px; top: 6px;
            width: 12px; height: 12px;
            background: white;
            border: 3px solid var(--sv-blue);
            border-radius: 50%;
            z-index: 1;
        }

        .sv-timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            margin-bottom: 8px;
        }

        /* Patient Info Card Header */
        .sv-patient-header {
            background: linear-gradient(135deg, var(--sv-navy) 0%, var(--sv-navy-mid) 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .sv-patient-avatar {
            font-size: 32px;
            background: rgba(255,255,255,0.15);
            width: 54px; height: 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Detail fields list */
        .sv-patient-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--sv-border);
        }

        .sv-patient-detail-cell {
            padding: 12px 16px;
            border-bottom: 1px solid var(--sv-border);
            border-right: 1px solid var(--sv-border);
        }
        .sv-patient-detail-cell:nth-child(2n) { border-right: none; }
        .sv-patient-detail-cell:nth-last-child(1), .sv-patient-detail-cell:nth-last-child(2) { border-bottom: none; }

        .sv-patient-detail-cell .lbl {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--sv-text-muted);
            margin-bottom: 2px;
        }

        .sv-patient-detail-cell .val {
            font-size: 13px;
            font-weight: 600;
            color: var(--sv-text-main);
        }

        /* Footer */
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
            <a href="about.php">Tentang Kami</a>
            <a href="jadwal.php" class="active">Cek Jadwal</a>
            <a href="#kontak">Kontak</a>
            <a href="login.php" class="btn-sv-primary ms-3">Masuk Admin</a>
        </div>
    </nav>

    <!-- ════ MAIN CONTAINER ════ -->
    <div class="container py-5">
        
        <!-- Breadcrumbs -->
        <div class="sv-breadcrumbs">
            <a href="../index.php">Beranda</a> &gt; 
            <a href="jadwal.php" style="color: var(--sv-blue);">Cek Jadwal Kunjungan</a>
            <?php if (!empty($query)): ?>
                &gt; <span style="color: var(--sv-blue);">Cari Data Riwayat Pasien</span>
            <?php endif; ?>
        </div>

        <?php if (empty($query) && empty($error)): ?>
            <!-- VIEW 1: TRANSPARANSI JADWAL KUNJUNGAN PETUGAS -->
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="sv-content-card">
                        <h2>Transparansi Jadwal Kunjungan Petugas</h2>
                        <div class="sv-card-subtitle">
                            Silakan pantau lini masa dan estimasi waktu kehadiran petugas kesehatan home care untuk wilayah monitoring administratif Anda.
                        </div>

                        <div class="sv-list-item">
                            <h5>📅 Penyusunan Jadwal</h5>
                            <p>Sistem menyusun rentang waktu kunjungan secara berkala setiap bulan untuk efisiensi pelaporan.</p>
                        </div>

                        <div class="sv-list-item">
                            <h5>🔔 Konfirmasi Kehadiran</h5>
                            <p>Petugas lapangan mengirimkan notifikasi validasi kehadiran sebelum menuju ke lokasi pasien binaan.</p>
                        </div>

                        <div class="sv-list-item">
                            <h5>⚙️ Fleksibilitas Waktu</h5>
                            <p>Perubahan waktu log kunjungan dapat dikoordinasikan langsung melalui kontak administratif resmi.</p>
                        </div>

                        <div class="sv-callout mt-4">
                            ⚠️ PENTING: JADWAL INI BERSIFAT SIMULASI ADMINISTRATIF BERKALA UNTUK KEPERLUAN MONITORING, BUKAN LAYANAN AMBULANS ATAU PENANGANAN MEDIS DARURAT.
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="sv-content-card d-flex flex-column justify-content-between">
                        <div>
                            <h2>Agenda Kunjungan Bulan Ini</h2>
                            <div class="sv-card-subtitle">
                                Lini masa agenda pemantauan dan rekapitulasi data pasien wilayah binaan.
                            </div>

                            <div class="sv-agenda-timeline">
                                <div class="sv-agenda-item">
                                    <div class="sv-agenda-info">
                                        <h6>Minggu 1-2: Pemeriksaan Fisik Sederhana & Input Log Awal</h6>
                                        <p>Evaluasi menyeluruh tanda vital dan keluhan awal.</p>
                                    </div>
                                    <span style="background: #E8F8ED; color: #1A7A35; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px;">Selesai</span>
                                </div>
                                <div class="sv-agenda-item" style="border: 1.5px solid var(--sv-blue);">
                                    <div class="sv-agenda-info">
                                        <h6>Minggu 3: Monitoring Evaluasi Kepatuhan Obat</h6>
                                        <p>Fokus pada kontrol tensi, suhu tubuh, dan saturasi.</p>
                                    </div>
                                    <span style="background: var(--sv-blue-light); color: var(--sv-blue-dark); font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px;">Berjalan</span>
                                </div>
                                <div class="sv-agenda-item">
                                    <div class="sv-agenda-info">
                                        <h6>Minggu 4: Rekapitulasi Laporan Akhir Bulanan</h6>
                                        <p>Penyusunan berkas administratif status pasien binaan.</p>
                                    </div>
                                    <span style="background: #F0F2F5; color: var(--sv-text-muted); font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px;">Antrean</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="sv-badge" style="background:#E8F1FF;color:#007AFF;">Siklus Kunjungan Rutin</span>
                                <span class="sv-badge" style="background:#E8F1FF;color:#007AFF;">Status Log Diperbarui</span>
                                <span class="sv-badge" style="background:#E8F1FF;color:#007AFF;">Metode Home Care</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Banner to Search -->
            <div class="sv-visual-banner">
                <div>
                    <h3 style="font-weight:800; font-size:24px; margin-bottom:8px;">Visualisasi Monitoring Real-time</h3>
                    <p style="font-size:14px; opacity:0.85; max-width:560px;">Sistem terintegrasi kami memastikan setiap log dari petugas lapangan langsung dapat diakses oleh keluarga pasien binaan guna kenyamanan bersama.</p>
                </div>
                <div>
                    <button class="btn btn-sv-primary px-4 py-3" onclick="document.getElementById('search-panel').scrollIntoView({behavior: 'smooth'})">
                        Cari Data Riwayat Pasien Sekarang →
                    </button>
                </div>
            </div>

            <!-- Search Panel -->
            <div id="search-panel" class="row g-4 mt-2">
                <div class="col-lg-6">
                    <div class="sv-content-card">
                        <h2>Form Pencarian Riwayat Pasien</h2>
                        <div class="sv-card-subtitle">Masukkan Kode Pasien atau NIK Dummy Anda untuk memuat dashboard klinis pribadi.</div>
                        
                        <form action="jadwal.php" method="GET">
                            <div class="mb-4">
                                <label for="q" class="form-label">Masukkan Kode Pasien / NIK Dummy</label>
                                <input type="text" name="q" id="q" class="form-control" placeholder="Contoh: RM-2026-0001 atau NIK Dummy..." required>
                            </div>
                            <div class="mb-4">
                                <label for="category" class="form-label">Kategori Binaan Pasien (Opsional)</label>
                                <select name="category" id="category" class="form-select">
                                    <option value="">Pilih Kategori Binaan</option>
                                    <option value="Lansia">Lansia</option>
                                    <option value="Hipertensi">Hipertensi</option>
                                    <option value="Diabetes">Diabetes</option>
                                    <option value="Pasca Rawat">Pasca Rawat</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sv-primary w-100 py-3">Validasi & Cari Data</button>
                        </form>

                        <div class="sv-callout mt-4" style="background:#FFF0EF; border-color:#FFD0CC; color:#C0291F;">
                            ⚠️ PENTING: Pastikan kode pasien atau NIK dimasukkan dengan benar. Akses tidak sah akan dicatat dalam log keamanan sistem.
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="sv-content-card d-flex flex-column justify-content-between">
                        <div>
                            <h2>Informasi Sistem Validasi</h2>
                            <div class="sv-card-subtitle">Bagaimana kami mengelola dan memverifikasi data Anda.</div>

                            <div class="sv-list-item">
                                <h5>1. Input Valid</h5>
                                <p>Sistem menerima dan memverifikasi format kode pasien sesuai standar administrasi instansi kesehatan.</p>
                            </div>
                            <div class="sv-list-item">
                                <h5>2. Pencocokan Database</h5>
                                <p>Sinkronisasi aman dengan rekam medis elektronik terpusat guna memverifikasi integritas logs.</p>
                            </div>
                            <div class="sv-list-item">
                                <h5>3. Tampilan Dasbor</h5>
                                <p>Penyajian data riwayat pemantauan secara komprehensif, dilengkapi status klinis dan catatan petugas.</p>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="sv-badge sv-badge-stable">Siklus Kunjungan Rut</span>
                                <span class="sv-badge sv-badge-stable">Status Log Diperbarui</span>
                                <span class="sv-badge sv-badge-stable">Metode Home Care</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- VIEW 2: SEARCH RESULTS & LIVE VALIDATION -->
            <div class="row g-4">
                <div class="col-lg-5">
                    <!-- Search Panel (Persistent) -->
                    <div class="sv-content-card">
                        <h2>Cari & Validasi Data Riwayat Pasien</h2>
                        <div class="sv-card-subtitle">Masukkan Kode Pasien atau NIK Dummy untuk melihat riwayat kunjungan home care dan status pemantauan klinis.</div>

                        <form action="jadwal.php" method="GET">
                            <div class="mb-3">
                                <label for="q" class="form-label">Masukkan Kode Pasien / NIK Dummy</label>
                                <input type="text" name="q" id="q" class="form-control" placeholder="Contoh: RM-2026-0001..." value="<?= htmlspecialchars($query) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Kategori Binaan Pasien (Opsional)</label>
                                <select name="category" id="category" class="form-select">
                                    <option value="">Pilih Kategori Binaan</option>
                                    <option value="Lansia" <?= $categoryFilter === 'Lansia' ? 'selected' : '' ?>>Lansia</option>
                                    <option value="Hipertensi" <?= $categoryFilter === 'Hipertensi' ? 'selected' : '' ?>>Hipertensi</option>
                                    <option value="Diabetes" <?= $categoryFilter === 'Diabetes' ? 'selected' : '' ?>>Diabetes</option>
                                    <option value="Pasca Rawat" <?= $categoryFilter === 'Pasca Rawat' ? 'selected' : '' ?>>Pasca Rawat</option>
                                    <option value="Lainnya" <?= $categoryFilter === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sv-primary w-100 py-3">Validasi & Cari Data</button>
                        </form>

                        <div class="sv-callout mt-4" style="background:#FFF0EF; border-color:#FFD0CC; color:#C0291F; font-size:11px;">
                            ⚠️ Pastikan kode pasien atau NIK dimasukkan dengan benar. Akses tidak sah akan dicatat dalam log keamanan sistem.
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <?php if (!empty($error)): ?>
                        <!-- ERROR DISPLAY -->
                        <div class="sv-content-card text-center py-5">
                            <span style="font-size: 48px;">🔍</span>
                            <h3 class="mt-3">Hasil Tidak Ditemukan</h3>
                            <p class="text-muted" style="max-width: 400px; margin: 8px auto 0;"><?= $error ?></p>
                            <a href="jadwal.php" class="btn btn-sv-outline mt-4">Kembali ke Lini Masa</a>
                        </div>
                    <?php else: ?>
                        <!-- PATIENT VIEW CARD -->
                        <div class="sv-content-card">
                            <div class="sv-patient-header">
                                <div class="sv-patient-avatar">
                                    <?= ($result['gender'] ?? '') === 'Male' ? '👨' : '👩' ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h4 style="font-weight:800; margin:0;" class="text-white"><?= htmlspecialchars($result['patient_name'] ?? '-') ?></h4>
                                    <span style="font-size:12px; opacity:0.8;"><?= htmlspecialchars($result['patient_id'] ?? '-') ?> &bull; <?= htmlspecialchars($result['patient_category'] ?? '-') ?></span>
                                </div>
                                <div>
                                    <?php if (!empty($history)): ?>
                                        <?= getStatusBadge($history[0]['status'] ?? '') ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="sv-patient-details-grid">
                                <div class="sv-patient-detail-cell">
                                    <div class="lbl">Kode Pasien / No. RM</div>
                                    <div class="val" style="color:var(--sv-blue);"><?= htmlspecialchars($result['patient_id'] ?? '-') ?></div>
                                </div>
                                <div class="sv-patient-detail-cell">
                                    <div class="lbl">NIK Dummy</div>
                                    <div class="val"><?= htmlspecialchars($result['nik_dummy'] ?? '-') ?></div>
                                </div>
                                <div class="sv-patient-detail-cell">
                                    <div class="lbl">Kategori Binaan</div>
                                    <div class="val"><?= htmlspecialchars($result['patient_category'] ?? '-') ?></div>
                                </div>
                                <div class="sv-patient-detail-cell">
                                    <div class="lbl">Usia Pasien</div>
                                    <div class="val"><?= calculateAge($result['datebirth'] ?? '') ?></div>
                                </div>
                                <div class="sv-patient-detail-cell">
                                    <div class="lbl">Alamat Kunjungan</div>
                                    <div class="val"><?= htmlspecialchars($result['address'] ?? '-') ?></div>
                                </div>
                                <div class="sv-patient-detail-cell">
                                    <div class="lbl">Kontak Darurat Keluarga</div>
                                    <div class="val"><?= htmlspecialchars($result['family_phone'] ?? '-') ?></div>
                                </div>
                            </div>

                            <!-- Interactive SVG Vital Chart -->
                            <?php if (!empty($history)): 
                                $tempValues = []; $bpSysValues = [];
                                foreach (array_reverse($history) as $h) {
                                    $tempValues[] = floatval($h['body_temperature'] ?? 36.5);
                                    $bp = explode('/', $h['blood_pressure'] ?? '120/80');
                                    $bpSysValues[] = intval($bp[0]);
                                }
                                $pointsTemp = ""; $pointsBP = "";
                                $xStep = count($tempValues) > 1 ? 260 / (count($tempValues) - 1) : 260;
                                foreach ($tempValues as $idx => $t) {
                                    $x = 20 + ($idx * $xStep);
                                    // Scale temp from 35C (y=100) to 40C (y=10)
                                    $yT = 100 - (($t - 35) * 18);
                                    $pointsTemp .= "$x,$yT ";
                                    
                                    // Scale BP Sys from 100 (y=100) to 160 (y=10)
                                    $sys = $bpSysValues[$idx] ?? 120;
                                    $yB = 100 - (($sys - 100) * 1.5);
                                    $pointsBP .= "$x,$yB ";
                                }
                            ?>
                            <div class="mt-4">
                                <h6 style="font-weight:700; color:var(--sv-navy); font-size:11px; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:8px;">Visualisasi Tren Vitals (Tekanan Darah & Suhu)</h6>
                                <div style="background:#FAFBFC; border-radius:10px; padding:12px; border:1px solid var(--sv-border);">
                                    <svg viewBox="0 0 300 120" style="width:100%; height:auto;">
                                        <!-- Grid Lines -->
                                        <line x1="20" y1="10" x2="280" y2="10" stroke="#F0F2F5" stroke-width="1"/>
                                        <line x1="20" y1="55" x2="280" y2="55" stroke="#F0F2F5" stroke-width="1"/>
                                        <line x1="20" y1="100" x2="280" y2="100" stroke="#F0F2F5" stroke-width="1"/>
                                        <!-- BP Path (Blue) -->
                                        <polyline fill="none" stroke="var(--sv-blue)" stroke-width="2.5" points="<?= trim($pointsBP) ?>"/>
                                        <!-- Temp Path (Green) -->
                                        <polyline fill="none" stroke="#34C759" stroke-width="2" points="<?= trim($pointsTemp) ?>" stroke-dasharray="3,3"/>
                                        <!-- Label legends -->
                                        <text x="25" y="112" font-size="7" fill="var(--sv-text-muted)">Awal</text>
                                        <text x="255" y="112" font-size="7" fill="var(--sv-text-muted)">Terkini</text>
                                    </svg>
                                    <div class="d-flex justify-content-center gap-3 mt-1" style="font-size:10px;">
                                        <span><span style="color:var(--sv-blue);">●</span> Sistolik (mmHg)</span>
                                        <span><span style="color:#34C759;">- -</span> Suhu (°C)</span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- TIMELINE OF LOGS -->
                            <h5 style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--sv-text-muted); margin: 30px 0 10px;">📋 Riwayat Rekam Kunjungan — <?= count($history) ?> Catatan</h5>
                            
                            <?php if (empty($history)): ?>
                                <p class="text-muted" style="font-size:13px;">Belum ada riwayat rekam kunjungan kesehatan.</p>
                            <?php else: ?>
                                <div class="sv-timeline">
                                    <?php foreach ($history as $idx => $h): ?>
                                        <div class="sv-timeline-item">
                                            <div class="sv-timeline-header">
                                                <strong style="color:var(--sv-navy);"><?= date('d F Y', strtotime($h['monitoring_date'])) ?></strong>
                                                <span><?= getStatusBadge($h['status'] ?? '') ?></span>
                                            </div>
                                            <div style="font-size:12px; color:var(--sv-text-muted); margin-bottom:8px;">Petugas Pemeriksa: <?= htmlspecialchars($h['user']['name'] ?? 'Ns. Budi Santoso') ?></div>
                                            <div class="sv-vitals-grid">
                                                <div class="sv-vital-card">
                                                    <div class="sv-vital-val"><?= htmlspecialchars($h['blood_pressure'] ?? '-') ?></div>
                                                    <div class="sv-vital-lbl">Tensi (mmHg)</div>
                                                </div>
                                                <div class="sv-vital-card">
                                                    <div class="sv-vital-val"><?= htmlspecialchars($h['body_temperature'] ?? '-') ?>°C</div>
                                                    <div class="sv-vital-lbl">Suhu Tubuh</div>
                                                </div>
                                                <div class="sv-vital-card">
                                                    <div class="sv-vital-val"><?= htmlspecialchars($h['heart_rate'] ?? '-') ?> bpm</div>
                                                    <div class="sv-vital-lbl">Nadi</div>
                                                </div>
                                                <div class="sv-vital-card">
                                                    <div class="sv-vital-val"><?= htmlspecialchars($h['oxygen_saturation'] ?? '-') ?>%</div>
                                                    <div class="sv-vital-lbl">Saturasi O₂</div>
                                                </div>
                                            </div>
                                            <?php if (!empty($h['symptoms'])): ?>
                                                <div class="mt-2 p-2 bg-light rounded" style="font-size:12.5px;">
                                                    <strong>Keluhan:</strong> <?= htmlspecialchars($h['symptoms']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($h['notes'])): ?>
                                                <div class="mt-1 p-2 rounded" style="font-size:12.5px; background:#F2F4F7;">
                                                    <strong>Rekomendasi Petugas:</strong> <?= htmlspecialchars($h['notes']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                <a href="jadwal.php" class="btn btn-sv-outline">Kembali ke Jadwal</a>
                                <button class="btn btn-sv-primary" onclick="window.print()">Unduh Laporan Riwayat Kunjungan (PDF)</button>
                            </div>
                        </div>
                    <?php endif; ?>
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
