<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config.php';
require_once 'components/sf-icons.php';

$query = trim($_GET['q'] ?? '');
$categoryFilter = $_GET['category'] ?? '';
$result = null;
$error = '';
$history = [];

if (!empty($query)) {
    // Hit Laravel API /pasien/{kode_pasien}/monitoring
    $apiResult = callAPI('GET', '/pasien/' . urlencode($query) . '/monitoring');

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
        return '<span class="sv-badge sv-badge-stable">' . sf_icon('checkmark-circle', 14) . ' Stabil</span>';
    }
    if (str_contains($s, 'referral') || str_contains($s, 'rujukan')) {
        return '<span class="sv-badge sv-badge-referral">' . sf_icon('exclamation-triangle', 14) . ' Perlu Rujukan</span>';
    }
    return '<span class="sv-badge sv-badge-control">' . sf_icon('exclamation-triangle', 14) . ' Perlu Kontrol</span>';
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
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal & Pencarian Pasien — SIVISIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="global.css" rel="stylesheet">
    <link href="table.css" rel="stylesheet">
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

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.12; }
            50% { transform: translateY(-30px) scale(1.05); opacity: 0.22; }
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
            background: rgba(0,122,255,0.1);
            top: -100px; right: -100px;
            animation: float 8s ease-in-out infinite;
        }
        .bg-orb-2 {
            width: 400px; height: 400px;
            background: rgba(0,122,255,0.07);
            bottom: -80px; left: -80px;
            animation: float 10s ease-in-out infinite reverse;
        }
        .bg-orb-3 {
            width: 300px; height: 300px;
            background: rgba(52,199,89,0.05);
            top: 50%; left: 50%;
            animation: float 12s ease-in-out infinite;
        }
        .content-img {
            width: 100%; height: 200px;
            background-size: cover; background-position: center;
            border-radius: 12px; margin-bottom: 20px;
        }
        .sv-content-card, .sv-footer, .sv-visual-banner { position: relative; z-index: 1; }

        /* ── Interactive Info Accordion ── */
        .sv-info-acc { display: flex; flex-direction: column; gap: 10px; }
        .sv-info-item {
            background: var(--sv-bg);
            border-radius: 12px;
            border: 1.5px solid transparent;
            overflow: hidden;
            cursor: pointer;
            transition: var(--sv-transition);
        }
        .sv-info-item:hover { border-color: var(--sv-blue-light); }
        .sv-info-item.active { border-color: var(--sv-blue); background: #fff; }
        .sv-info-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            user-select: none;
        }
        .sv-info-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: var(--sv-transition);
        }
        .sv-info-icon svg { display: block; }
        .sv-info-item.active .sv-info-icon { background: var(--sv-blue-light); }
        .sv-info-icon.cal { background: #E8F1FF; color: #007AFF; }
        .sv-info-icon.heart { background: #FFEBEE; color: #C0291F; }
        .sv-info-icon.family { background: #E8F8ED; color: #1A7A35; }
        .sv-info-label { flex: 1; font-size: 14px; font-weight: 700; color: var(--sv-navy); }
        .sv-info-arrow {
            font-size: 12px; color: var(--sv-text-muted);
            transition: transform 0.3s ease;
        }
        .sv-info-item.active .sv-info-arrow { transform: rotate(180deg); color: var(--sv-blue); }
        .sv-info-body {
            max-height: 0; overflow: hidden;
            transition: max-height 0.35s ease, padding 0.35s ease;
            padding: 0 16px;
        }
        .sv-info-item.active .sv-info-body {
            max-height: 400px;
            padding: 0 16px 16px;
        }
        .sv-info-body p { font-size: 13px; color: var(--sv-text-sub); line-height: 1.7; margin: 0; }
        .sv-info-body ul { margin: 8px 0 0; padding: 0 0 0 18px; font-size: 12.5px; color: var(--sv-text-sub); }
        .sv-info-body ul li { margin-bottom: 4px; }

        /* ── Interactive Steps ── */
        .sv-steps { display: flex; flex-direction: column; gap: 12px; }
        .sv-step {
            background: var(--sv-bg);
            border-radius: 12px;
            border: 1.5px solid transparent;
            overflow: hidden;
            cursor: pointer;
            transition: var(--sv-transition);
        }
        .sv-step:hover { border-color: var(--sv-blue-light); }
        .sv-step.active { border-color: var(--sv-blue); background: #fff; }
        .sv-step-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            user-select: none;
        }
        .sv-step-num {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: #E8F1FF;
            color: #007AFF;
            font-size: 13px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: var(--sv-transition);
        }
        .sv-step.active .sv-step-num { background: var(--sv-blue); color: #fff; }
        .sv-step-label { flex: 1; font-size: 14px; font-weight: 700; color: var(--sv-navy); }
        .sv-step-arrow {
            font-size: 12px; color: var(--sv-text-muted);
            transition: transform 0.3s ease;
        }
        .sv-step.active .sv-step-arrow { transform: rotate(180deg); color: var(--sv-blue); }
        .sv-step-body {
            max-height: 0; overflow: hidden;
            transition: max-height 0.35s ease, padding 0.35s ease;
            padding: 0 16px;
        }
        .sv-step.active .sv-step-body {
            max-height: 400px;
            padding: 0 16px 16px 62px;
        }
        .sv-step-body p { font-size: 13px; color: var(--sv-text-sub); line-height: 1.7; margin: 0 0 8px; }
        .sv-step-body .sv-step-detail {
            background: #fff;
            border: 1px solid var(--sv-border);
            border-radius: 10px;
            padding: 12px;
            font-size: 12px;
            color: var(--sv-text-sub);
            line-height: 1.6;
        }
        .sv-step-body .sv-step-detail strong { color: var(--sv-navy); }
    </style>
</head>
<body>

    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-orb bg-orb-3"></div>

    <?php $navActive = 'jadwal'; $navFromPages = true; include 'components/public-navbar.php'; ?>

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
                        <h2>Informasi Monitoring Pasien</h2>
                        <div class="sv-card-subtitle">
                            Pantau jadwal kunjungan dan riwayat monitoring pasien home care secara transparan dan real-time.
                        </div>

                        <div class="sv-info-acc">
                            <div class="sv-info-item" onclick="toggleInfo(this)">
                                <div class="sv-info-header">
                                    <div class="sv-info-icon cal"><?= sf_icon('calendar', 20) ?></div>
                                    <span class="sv-info-label">Jadwal Kunjungan Rutin</span>
                                    <span class="sv-info-arrow">▾</span>
                                </div>
                                <div class="sv-info-body">
                                    <p>Petugas melakukan kunjungan monitoring secara berkala sesuai jadwal yang telah ditentukan untuk setiap pasien binaan.</p>
                                    <ul>
                                        <li>Kunjungan rutin dilakukan 1-2 kali per minggu</li>
                                        <li>Setiap kunjungan tercatat dalam sistem monitoring</li>
                                        <li>Riwayat kunjungan dapat diakses oleh keluarga pasien</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="sv-info-item" onclick="toggleInfo(this)">
                                <div class="sv-info-header">
                                    <div class="sv-info-icon heart"><?= sf_icon('heart', 20) ?></div>
                                    <span class="sv-info-label">Pencatatan Data Kesehatan</span>
                                    <span class="sv-info-arrow">▾</span>
                                </div>
                                <div class="sv-info-body">
                                    <p>Setiap kunjungan mencatat data tanda vital pasien secara lengkap untuk memantau perkembangan kesehatan.</p>
                                    <ul>
                                        <li>Tensi darah (sistolik/diastolik)</li>
                                        <li>Suhu tubuh dan detak jantung (nadi)</li>
                                        <li>Saturasi oksigen (SpO₂)</li>
                                        <li>Keluhan dan catatan perkembangan</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="sv-info-item" onclick="toggleInfo(this)">
                                <div class="sv-info-header">
                                    <div class="sv-info-icon family"><?= sf_icon('person-2', 22) ?></div>
                                    <span class="sv-info-label">Akses Informasi Keluarga</span>
                                    <span class="sv-info-arrow">▾</span>
                                </div>
                                <div class="sv-info-body">
                                    <p>Keluarga dapat mencari dan melihat riwayat monitoring pasien kapan saja melalui form pencarian di portal publik.</p>
                                    <ul>
                                        <li>Cukup masukkan kode pasien atau NIK</li>
                                        <li>Lihat riwayat monitoring lengkap dengan grafik tren</li>
                                        <li>Pantau status kesehatan pasien secara real-time</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="sv-callout mt-4">
                            ⚠️ PENTING: JADWAL INI BERSIFAT SIMULASI ADMINISTRATIF BERKALA UNTUK KEPERLUAN MONITORING, BUKAN LAYANAN AMBULANS ATAU PENANGANAN MEDIS DARURAT.
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="sv-content-card d-flex flex-column justify-content-between">
                        <div>
                            <div class="content-img" style="background-image:url('https://images.unsplash.com/photo-1584515933487-779824d29309?w=600&h=300&fit=crop');"></div>
                            <h2>Cara Menggunakan</h2>
                            <div class="sv-card-subtitle">
                                Ikuti langkah mudah untuk mencari dan melihat data monitoring pasien.
                            </div>

                            <div class="sv-steps">
                                <div class="sv-step" onclick="toggleStep(this)">
                                    <div class="sv-step-header">
                                        <div class="sv-step-num">1</div>
                                        <span class="sv-step-label">Siapkan Kode Pasien atau NIK</span>
                                        <span class="sv-step-arrow">▾</span>
                                    </div>
                                    <div class="sv-step-body">
                                        <p>Dapatkan kode pasien (RM-2026-XXXX) atau NIK dari petugas kesehatan yang menangani.</p>
                                        <div class="sv-step-detail">
                                            <strong>✏️ Tips:</strong> Kode pasien biasanya terdiri dari format <strong>RM-TAHUN-NOMOR</strong>. Contoh: RM-2026-0001. Simpan kode ini untuk akses selanjutnya.
                                        </div>
                                    </div>
                                </div>

                                <div class="sv-step" onclick="toggleStep(this)">
                                    <div class="sv-step-header">
                                        <div class="sv-step-num">2</div>
                                        <span class="sv-step-label">Masukkan ke Form Pencarian</span>
                                        <span class="sv-step-arrow">▾</span>
                                    </div>
                                    <div class="sv-step-body">
                                        <p>Klik tombol <strong>"Cari Data Pasien"</strong> di banner bawah, lalu masukkan kode atau NIK pasien pada form yang muncul.</p>
                                        <div class="sv-step-detail">
                                            <strong>🔍 Proses:</strong> Sistem akan memvalidasi dan mencocokkan data dengan database rekam medis terpusat secara aman.
                                        </div>
                                    </div>
                                </div>

                                <div class="sv-step" onclick="toggleStep(this)">
                                    <div class="sv-step-header">
                                        <div class="sv-step-num">3</div>
                                        <span class="sv-step-label">Lihat Riwayat Monitoring</span>
                                        <span class="sv-step-arrow">▾</span>
                                    </div>
                                    <div class="sv-step-body">
                                        <p>Sistem akan menampilkan data pasien dan riwayat monitoring lengkap dengan grafik tren perkembangan.</p>
                                        <div class="sv-step-detail">
                                            <strong>📊 Informasi yang tampil:</strong> Data identitas pasien, riwayat kunjungan, tensi darah, suhu tubuh, detak jantung, saturasi oksigen, serta rekomendasi petugas.
                                        </div>
                                    </div>
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

        <?php else: ?>
            <!-- VIEW 2: SEARCH RESULTS & LIVE VALIDATION -->
            <div class="row g-4">
                <div class="col-lg-5" id="search-panel">
                    <!-- Search Panel -->
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
                            <div class="empty-icon" style="justify-content:center;"><?= sf_icon('magnifyingglass', 48) ?></div>
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

        <!-- Bottom Action Banner to Search -->
        <div class="sv-visual-banner">
            <div>
                <h3 style="font-weight:800; font-size:24px; margin-bottom:8px;">Cari Data Monitoring Pasien</h3>
                <p style="font-size:14px; opacity:0.85; max-width:560px;">Masukkan kode pasien atau NIK untuk melihat riwayat monitoring kesehatan dan status terkini pasien.</p>
            </div>
            <div>
                <button class="btn btn-sv-primary px-4 py-3" id="toggleSearchBtn" onclick="toggleSearchPanel()">
                    Cari Data Pasien
                </button>
            </div>
        </div>

        <!-- Search Panel (hidden by default) -->
        <div id="search-panel" class="row g-4 mt-2" style="display:none;">
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
                        <div class="content-img" style="background-image:url('https://images.unsplash.com/photo-1550831107-1553da8c8464?w=600&h=300&fit=crop');"></div>
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
    function toggleInfo(el) {
        el.classList.toggle('active');
    }

    function toggleStep(el) {
        el.classList.toggle('active');
    }

    function toggleSearchPanel() {
        const panel = document.getElementById('search-panel');
        const btn = document.getElementById('toggleSearchBtn');
        const isHidden = window.getComputedStyle(panel).display === 'none';
        if (isHidden) {
            panel.style.display = '';
            setTimeout(function () { panel.scrollIntoView({ behavior: 'smooth' }); }, 100);
            btn.textContent = 'Tutup Pencarian';
        } else {
            panel.style.display = 'none';
            btn.textContent = 'Cari Data Pasien';
        }
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
