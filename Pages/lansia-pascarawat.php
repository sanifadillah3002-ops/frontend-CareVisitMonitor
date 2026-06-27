<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config.php';

$query = trim($_GET['q'] ?? '');
$result = null;
$error = '';
$history = [];

if (!empty($query)) {
    // Hit Laravel API
    $apiResult = callAPI('GET', '/pasien/' . urlencode($query) . '/monitoring');

    if ($apiResult['status_code'] === 200 && isset($apiResult['response']['data'])) {
        $data = $apiResult['response']['data'];
        
        // Ensure category matches Lansia / Pasca Rawat (case-insensitive checking)
        $cat = strtolower($data['patient_category'] ?? '');
        if (strpos($cat, 'lansia') !== false || strpos($cat, 'rawat') !== false || strpos($cat, 'hipertensi') !== false) {
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
            $error = 'Pasien ditemukan, namun kategori pasien bukan Lansia atau Pasca Rawat.';
        }
    } elseif ($apiResult['status_code'] === 404) {
        $error = 'Pasien dengan kode/NIK "' . htmlspecialchars($query) . '" tidak ditemukan.';
    } else {
        $error = 'Terjadi kesalahan sistem saat memuat data.';
    }
}

function getStatusBadge($status) {
    $s = strtolower($status ?? '');
    if (str_contains($s, 'stable') || str_contains($s, 'stabil')) {
        return '<span class="sv-badge sv-badge-stable">✅ Stabil</span>';
    }
    if (str_contains($s, 'referral') || str_contains($s, 'rujukan')) {
        return '<span class="sv-badge sv-badge-referral">🚨 Rujukan</span>';
    }
    return '<span class="sv-badge sv-badge-control">⚠️ Perlu Kontrol</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lansia & Pasca Rawat — MediaAdmin</title>
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

        /* Breadcrumbs */
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

        /* Content Card */
        .sv-content-card {
            background: white;
            border-radius: var(--sv-radius-lg);
            border: 1px solid var(--sv-border);
            padding: 36px;
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
            margin-bottom: 30px;
        }

        /* Items */
        .sv-list-item {
            margin-bottom: 24px;
        }

        .sv-list-item h5 {
            font-size: 15px;
            font-weight: 700;
            color: var(--sv-navy);
            margin-bottom: 6px;
        }

        .sv-list-item p {
            font-size: 13.5px;
            color: var(--sv-text-sub);
            margin: 0;
            line-height: 1.6;
        }

        .sv-callout-red {
            background: #FFF0EF;
            border: 1px solid #FFD0CC;
            border-radius: 12px;
            padding: 16px;
            color: #C0291F;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.6;
        }

        /* Progress Steps */
        .sv-progress-step {
            background: var(--sv-bg);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 14px;
        }

        .sv-progress-step h6 {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--sv-navy);
            margin-bottom: 4px;
        }

        .sv-progress-step p {
            font-size: 12px;
            color: var(--sv-text-sub);
            margin: 0 0 10px;
        }

        .sv-progressbar-wrap {
            background: #E5E8ED;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
        }

        .sv-progressbar-fill {
            background: var(--sv-blue);
            height: 100%;
        }

        /* Badge styles */
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

        /* Patient Header card */
        .sv-patient-header {
            background: linear-gradient(135deg, var(--sv-navy) 0%, var(--sv-navy-mid) 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
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

        /* Timeline list */
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
            font-size: 13.5px;
            margin-bottom: 8px;
        }

        .sv-vitals-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .sv-vital-box {
            background: var(--sv-bg);
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }

        .sv-vital-val { font-size: 15px; font-weight: 800; color: var(--sv-navy); }
        .sv-vital-lbl { font-size: 10px; color: var(--sv-text-muted); margin-top: 2px; }

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

        .sv-footer-links a:hover { color: white; }
        @media (max-width: 768px) {
            .sv-content-card { padding: 20px; }
        }
    </style>
</head>
<body>

    <?php $navActive = ''; $navFromPages = true; $showAdminBtn = true; include 'components/public-navbar.php'; ?>

    <!-- ════ MAIN CONTAINER ════ -->
    <div class="container py-5">
        
        <!-- Breadcrumbs -->
        <div class="sv-breadcrumbs">
            <a href="../index.php">Beranda</a> &gt; 
            <span>Layanan Pemantauan</span> &gt;
            <a href="lansia-pascarawat.php" style="color: var(--sv-blue);">Lansia & Pasca Rawat</a>
        </div>

        <?php if (empty($query)): ?>
            <!-- VIEW 1: SERVICE OVERVIEW -->
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="sv-content-card">
                        <h2>Layanan Pemantauan Lansia & Pasca Rawat</h2>
                        <div class="sv-card-subtitle">
                            Informasi alur pemantauan, pendampingan aktivitas fisik, dan pencatatan administratif log kunjungan petugas home care bagi lansia binaan di rumah.
                        </div>

                        <div class="sv-list-item">
                            <h5>👴 1. Monitoring Pemulihan Fisik</h5>
                            <p>Evaluasi bertahap aktivitas mobilitas mandiri pasien, pendampingan aktivitas fisik harian, dan pencegahan risiko cedera jatuh di rumah.</p>
                        </div>

                        <div class="sv-list-item">
                            <h5>🩺 2. Pemeriksaan Tanda Vital Sederhana</h5>
                            <p>Pencatatan log suhu tubuh, denyut nadi, saturasi oksigen, serta keluhan fisik secara rutin setiap kunjungan petugas lapangan.</p>
                        </div>

                        <div class="sv-list-item">
                            <h5>📅 3. Transparansi Jadwal Kunjungan</h5>
                            <p>Penjadwalan transparan dan terstruktur agar keluarga dapat mempersiapkan kehadiran petugas kesehatan secara kondusif.</p>
                        </div>

                        <div class="sv-callout-red mt-4">
                            ⚠️ PENTING: Layanan ini hanya bersifat monitoring administratif untuk rekapitulasi bantuan, bukan memberikan nasihat medis atau diagnosis medis profesional.
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="sv-content-card d-flex flex-column justify-content-between">
                        <div>
                            <h2>Visualisasi Jadwal & Lini Masa Kunjungan</h2>
                            <div class="sv-card-subtitle">
                                Lini masa fase tahapan pemulihan terstruktur bagi lansia dan pasca rawat.
                            </div>

                            <div class="sv-progress-step">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6>Tahap 1: Inisiasi Data Pasien</h6>
                                    <span style="background: #E8F8ED; color: #1A7A35; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Selesai</span>
                                </div>
                                <p>Pendaftaran administrasi, pencatatan NIK dummy, dan penjadwalan awal.</p>
                                <div class="sv-progressbar-wrap"><div class="sv-progressbar-fill" style="width: 100%; background: #34C759;"></div></div>
                            </div>

                            <div class="sv-progress-step" style="border: 1px solid var(--sv-blue);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6>Tahap 2: Monitoring Mingguan Kemajuan Fisik</h6>
                                    <span style="background: var(--sv-blue-light); color: var(--sv-blue-dark); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Berjalan</span>
                                </div>
                                <p>Fokus monitoring mobilitas, denyut nadi, saturasi oksigen, serta kepatuhan obat harian.</p>
                                <div class="sv-progressbar-wrap"><div class="sv-progressbar-fill" style="width: 65%;"></div></div>
                            </div>

                            <div class="sv-progress-step">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6>Tahap 3: Pelaporan Rekapitulasi Akhir Bulanan</h6>
                                    <span style="background: #F0F2F5; color: var(--sv-text-muted); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Antrean</span>
                                </div>
                                <p>Rekapitulasi log monitoring untuk instansi kesehatan rujukan.</p>
                                <div class="sv-progressbar-wrap"><div class="sv-progressbar-fill" style="width: 0%;"></div></div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-sv-primary w-100 py-3" onclick="document.getElementById('search-panel').scrollIntoView({behavior: 'smooth'})">
                                Cek Riwayat Kunjungan Saya Sekarang →
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Panel -->
            <div id="search-panel" class="row g-4 mt-2 justify-content-center">
                <div class="col-lg-6">
                    <div class="sv-content-card">
                        <h2>Masukkan Kode Pasien untuk Cek Riwayat</h2>
                        <form action="lansia-pascarawat.php" method="GET">
                            <div class="mb-3">
                                <label for="q" class="form-label">Kode Pasien / No. RM (Lansia & Pasca Rawat)</label>
                                <input type="text" name="q" id="q" class="form-control" placeholder="Contoh: RM-2026-0001..." required>
                            </div>
                            <button type="submit" class="btn btn-sv-primary w-100 py-3">Muat Riwayat Pasien</button>
                        </form>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- VIEW 2: DETAILED LIVE HISTORY -->
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="sv-content-card">
                        <h2>Cek Riwayat Pasien</h2>
                        <form action="lansia-pascarawat.php" method="GET">
                            <div class="mb-3">
                                <input type="text" name="q" class="form-control" placeholder="Contoh: RM-2026-0001..." value="<?= htmlspecialchars($query) ?>" required>
                            </div>
                            <button type="submit" class="btn btn-sv-primary w-100 py-3">Cari Ulang Pasien</button>
                        </form>
                        <a href="lansia-pascarawat.php" class="btn btn-sv-outline w-100 mt-2 py-3 text-center">Kembali ke Panduan</a>
                    </div>
                </div>

                <div class="col-lg-8">
                    <?php if (!empty($error)): ?>
                        <div class="sv-content-card text-center py-5">
                            <span style="font-size:48px;">🔍</span>
                            <h3 class="mt-3">Data Tidak Ditemukan</h3>
                            <p class="text-muted"><?= $error ?></p>
                        </div>
                    <?php else: ?>
                        <div class="sv-content-card">
                            <div class="sv-patient-header">
                                <div class="sv-patient-avatar">👴</div>
                                <div class="flex-grow-1">
                                    <h4 style="margin:0;" class="text-white"><?= htmlspecialchars($result['patient_name'] ?? '-') ?></h4>
                                    <span style="font-size:12px;opacity:0.8;"><?= htmlspecialchars($result['patient_id']) ?> &bull; <?= htmlspecialchars($result['patient_category']) ?></span>
                                </div>
                                <div>
                                    <?php if (!empty($history)): ?>
                                        <?= getStatusBadge($history[0]['status'] ?? '') ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row g-3" style="font-size:13px; margin-bottom: 24px;">
                                <div class="col-6"><strong>NIK Dummy:</strong> <?= htmlspecialchars($result['nik_dummy'] ?? '-') ?></div>
                                <div class="col-6"><strong>Kontak Darurat:</strong> <?= htmlspecialchars($result['family_phone'] ?? '-') ?></div>
                                <div class="col-12"><strong>Alamat Kunjungan:</strong> <?= htmlspecialchars($result['address'] ?? '-') ?></div>
                            </div>

                            <!-- Progress Dashboard Mockup from data -->
                            <div class="p-3 mb-4 rounded" style="background:#FAFBFC; border:1px solid var(--sv-border);">
                                <h6 style="font-weight:700; color:var(--sv-navy); font-size:11px; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:12px;">Metrik Aktivitas & Saturasi Pasien Binaan</h6>
                                <div class="row text-center g-2">
                                    <div class="col-4">
                                        <div style="font-size:10px; color:var(--sv-text-muted);">Rata-rata Nadi</div>
                                        <div style="font-size:18px; font-weight:800; color:var(--sv-blue);">
                                            <?php
                                                $hr = array_filter(array_column($history, 'heart_rate'));
                                                echo !empty($hr) ? round(array_sum($hr)/count($hr)) : 80;
                                            ?> bpm
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div style="font-size:10px; color:var(--sv-text-muted);">Saturasi O₂ Terkini</div>
                                        <div style="font-size:18px; font-weight:800; color:#34C759;">
                                            <?= !empty($history[0]['oxygen_saturation']) ? $history[0]['oxygen_saturation'] . '%' : '98%' ?>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div style="font-size:10px; color:var(--sv-text-muted);">Suhu Tubuh Rata2</div>
                                        <div style="font-size:18px; font-weight:800; color:#FF9500;">
                                            <?php
                                                $t = array_filter(array_column($history, 'body_temperature'));
                                                echo !empty($t) ? round(array_sum($t)/count($t), 1) : 36.6;
                                            ?> °C
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--sv-text-muted); margin-bottom:12px;">Timeline Log Kunjungan</h5>
                            <div class="sv-timeline">
                                <?php foreach ($history as $h): ?>
                                    <div class="sv-timeline-item">
                                        <div class="sv-timeline-header">
                                            <strong><?= date('d M Y', strtotime($h['monitoring_date'])) ?></strong>
                                            <span><?= getStatusBadge($h['status'] ?? '') ?></span>
                                        </div>
                                        <div class="sv-vitals-row">
                                            <div class="sv-vital-box">
                                                <div class="sv-vital-val"><?= htmlspecialchars($h['blood_pressure'] ?? '-') ?></div>
                                                <div class="sv-vital-lbl">Tensi (mmHg)</div>
                                            </div>
                                            <div class="sv-vital-box">
                                                <div class="sv-vital-val"><?= htmlspecialchars($h['body_temperature'] ?? '-') ?>°C</div>
                                                <div class="sv-vital-lbl">Suhu</div>
                                            </div>
                                            <div class="sv-vital-box">
                                                <div class="sv-vital-val"><?= htmlspecialchars($h['oxygen_saturation'] ?? '-') ?>%</div>
                                                <div class="sv-vital-lbl">Saturasi O₂</div>
                                            </div>
                                        </div>
                                        <?php if (!empty($h['notes'])): ?>
                                            <div class="p-2 rounded mt-1 bg-light" style="font-size:12.5px;">
                                                <strong>Rekomendasi:</strong> <?= htmlspecialchars($h['notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
