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
        
        // Ensure category matches (case-insensitive checking)
        $cat = strtolower($data['patient_category'] ?? '');
        if (strpos($cat, 'hipertensi') !== false || strpos($cat, 'diabetes') !== false || strpos($cat, 'lansia') !== false) {
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
            $error = 'Pasien ditemukan, namun kategori pasien bukan Hipertensi atau Diabetes.';
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
    <title>Hipertensi & Diabetes — MediaAdmin</title>
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
            <a href="hipertensi-diabetes.php" style="color: var(--sv-blue);">Hipertensi & Diabetes</a>
        </div>

        <?php if (empty($query)): ?>
            <!-- VIEW 1: SERVICE OVERVIEW -->
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="sv-content-card">
                        <h2>Layanan Pemantauan Hipertensi & Diabetes</h2>
                        <div class="sv-card-subtitle">
                            Sistem pemantauan berkala dan pendampingan terstruktur untuk manajemen tensi dan gula darah pasien binaan.
                        </div>

                        <div class="sv-list-item">
                            <h5>🩺 1. Pemantauan Tekanan Darah</h5>
                            <p>Pencatatan sistolik dan diastolik harian untuk memetakan tingkat kepatuhan terapi obat dan tren sirkulasi.</p>
                        </div>

                        <div class="sv-list-item">
                            <h5>🧪 2. Pemeriksaan Gula Darah Berkala</h5>
                            <p>Pencatatan log glukosa acak/puasa untuk mendeteksi fluktuasi metabolisme tubuh secara dini.</p>
                        </div>

                        <div class="sv-list-item">
                            <h5>🥗 3. Edukasi Gaya Hidup & Kepatuhan</h5>
                            <p>Fasilitasi konsultasi gizi mandiri dan evaluasi pola aktivitas fisik harian keluarga pasien binaan.</p>
                        </div>

                        <div class="sv-callout-red mt-4">
                            ⚠️ PENTING: LAYANAN INI BERSIFAT MONITORING ADMINISTRATIF UNTUK KEPERLUAN REKAPITULASI DUMMY, BUKAN DIAGNOSIS MEDIS ATAU PENENTU DOSIS TERAPI OBAT.
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="sv-content-card d-flex flex-column justify-content-between">
                        <div>
                            <h2>Visualisasi Tren Kesehatan</h2>
                            <div class="sv-card-subtitle">
                                Grafik estimasi fluktuasi sirkulasi dan kepatuhan pasien binaan.
                            </div>

                            <!-- Beautiful SVG Line Chart Mockup -->
                            <div style="background:#FAFBFC; border-radius:12px; padding:20px; border:1px solid var(--sv-border);">
                                <svg viewBox="0 0 300 120" style="width:100%; height:auto;">
                                    <line x1="20" y1="10" x2="280" y2="10" stroke="#E5E8ED" stroke-width="1"/>
                                    <line x1="20" y1="55" x2="280" y2="55" stroke="#E5E8ED" stroke-width="1"/>
                                    <line x1="20" y1="100" x2="280" y2="100" stroke="#E5E8ED" stroke-width="1"/>
                                    
                                    <polyline fill="none" stroke="var(--sv-blue)" stroke-width="3" points="20,80 70,70 120,40 170,45 220,30 280,25"/>
                                    <polyline fill="none" stroke="#AF52DE" stroke-width="2.5" points="20,95 70,85 120,60 170,55 220,50 280,48" stroke-dasharray="2,2"/>
                                    
                                    <circle cx="280" cy="25" r="4" fill="var(--sv-blue)"/>
                                </svg>
                                <div class="d-flex justify-content-between mt-3 text-muted" style="font-size:11px;">
                                    <span>Minggu 1</span>
                                    <span>Minggu 2</span>
                                    <span>Minggu 3</span>
                                    <span>Minggu 4</span>
                                    <span>Minggu 5</span>
                                    <span>Minggu 6</span>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-4">
                                    <div style="font-size:11px;color:var(--sv-text-muted);">Sirkulasi Darah</div>
                                    <div style="font-size:18px;font-weight:800;color:#34C759;">118 <span style="font-size:10px;font-weight:500;">mmHg</span></div>
                                    <div style="font-size:9px;color:#34C759;font-weight:700;">Normal</div>
                                </div>
                                <div class="col-4">
                                    <div style="font-size:11px;color:var(--sv-text-muted);">Gula Darah Rata2</div>
                                    <div style="font-size:18px;font-weight:800;color:var(--sv-blue);">84 <span style="font-size:10px;font-weight:500;">mg/dL</span></div>
                                    <div style="font-size:9px;color:var(--sv-blue);font-weight:700;">Terkendali</div>
                                </div>
                                <div class="col-4">
                                    <div style="font-size:11px;color:var(--sv-text-muted);">Tingkat Kepatuhan</div>
                                    <div style="font-size:18px;font-weight:800;color:#AF52DE;">92%</div>
                                    <div style="font-size:9px;color:#AF52DE;font-weight:700;">Sangat Baik</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <!-- Clickable Search trigger -->
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
                        <form action="hipertensi-diabetes.php" method="GET">
                            <div class="mb-3">
                                <label for="q" class="form-label">Kode Pasien / No. RM (Hipertensi & Diabetes)</label>
                                <input type="text" name="q" id="q" class="form-control" placeholder="Contoh: RM-2026-0002..." required>
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
                        <form action="hipertensi-diabetes.php" method="GET">
                            <div class="mb-3">
                                <input type="text" name="q" class="form-control" placeholder="Contoh: RM-2026-0002..." value="<?= htmlspecialchars($query) ?>" required>
                            </div>
                            <button type="submit" class="btn btn-sv-primary w-100 py-3">Cari Ulang Pasien</button>
                        </form>
                        <a href="hipertensi-diabetes.php" class="btn btn-sv-outline w-100 mt-2 py-3 text-center">Kembali ke Panduan</a>
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
                                <div class="sv-patient-avatar">🩺</div>
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

                            <!-- SVG Vital Graph from Patient history -->
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
                                    $yT = 100 - (($t - 35) * 18);
                                    $pointsTemp .= "$x,$yT ";
                                    
                                    $sys = $bpSysValues[$idx] ?? 120;
                                    $yB = 100 - (($sys - 100) * 1.5);
                                    $pointsBP .= "$x,$yB ";
                                }
                            ?>
                            <div class="mb-4">
                                <h6 style="font-weight:700; color:var(--sv-navy); font-size:11px; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:8px;">Visualisasi Tren Vitals Pasien</h6>
                                <div style="background:#FAFBFC; border-radius:10px; padding:12px; border:1px solid var(--sv-border);">
                                    <svg viewBox="0 0 300 120" style="width:100%; height:auto;">
                                        <line x1="20" y1="10" x2="280" y2="10" stroke="#F0F2F5" stroke-width="1"/>
                                        <line x1="20" y1="55" x2="280" y2="55" stroke="#F0F2F5" stroke-width="1"/>
                                        <line x1="20" y1="100" x2="280" y2="100" stroke="#F0F2F5" stroke-width="1"/>
                                        <polyline fill="none" stroke="var(--sv-blue)" stroke-width="2.5" points="<?= trim($pointsBP) ?>"/>
                                        <polyline fill="none" stroke="#34C759" stroke-width="2" points="<?= trim($pointsTemp) ?>" stroke-dasharray="3,3"/>
                                    </svg>
                                    <div class="d-flex justify-content-center gap-3 mt-1" style="font-size:10px;">
                                        <span><span style="color:var(--sv-blue);">●</span> Sistolik (mmHg)</span>
                                        <span><span style="color:#34C759;">- -</span> Suhu (°C)</span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

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
                                                <div class="sv-vital-val"><?= htmlspecialchars($h['heart_rate'] ?? '-') ?> bpm</div>
                                                <div class="sv-vital-lbl">Nadi</div>
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
