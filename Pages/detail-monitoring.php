<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

$monId      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$patientId  = $_GET['patient_id'] ?? '';

// Fetch all monitorings to find the target record
$monitoringsRes = callAPI('GET', '/monitoring');
$allMonitorings = ($monitoringsRes['status_code'] === 200 && isset($monitoringsRes['response']['data']))
    ? $monitoringsRes['response']['data'] : [];

// Find the specific monitoring record
$monitoring = null;
foreach ($allMonitorings as $m) {
    if ((int)($m['id'] ?? 0) === $monId) {
        $monitoring = $m;
        break;
    }
}

// If not found by id, try by patient_id (show latest)
if (!$monitoring && $patientId) {
    $filtered = array_filter($allMonitorings, fn($m) => ($m['patient_id'] ?? '') === $patientId);
    if (!empty($filtered)) {
        usort($filtered, fn($a, $b) => strtotime($b['monitoring_date'] ?? '') <=> strtotime($a['monitoring_date'] ?? ''));
        $monitoring = array_values($filtered)[0];
    }
}

// Redirect if truly not found
if (!$monitoring) {
    header("Location: monitoring.php?error=not_found");
    exit;
}

// Get all monitorings for this patient (for history)
$patId = $monitoring['patient_id'] ?? '';
$patientMonitorings = array_filter($allMonitorings, fn($m) => ($m['patient_id'] ?? '') === $patId);
usort($patientMonitorings, fn($a, $b) => strtotime($b['monitoring_date'] ?? '') <=> strtotime($a['monitoring_date'] ?? ''));
$patientMonitorings = array_values($patientMonitorings);

// Parse vital signs
$bpRaw  = $monitoring['blood_pressure'] ?? '';
$bpParts = explode('/', $bpRaw);
$sys = (int)($bpParts[0] ?? 0);
$dia = (int)($bpParts[1] ?? 0);
$temp = (float)($monitoring['body_temperature'] ?? 0);
$hr   = (int)($monitoring['heart_rate'] ?? 0);
$rr   = (int)($monitoring['respiratory_rate'] ?? 0);
$spo2 = (float)($monitoring['oxygen_saturation'] ?? 0);

// Status helpers
function getStatusClass($status) {
    $s = strtolower($status ?? '');
    if (str_contains($s, 'stable') || str_contains($s, 'stabil')) return 'stable';
    if (str_contains($s, 'referral') || str_contains($s, 'rujukan')) return 'referral';
    return 'control';
}
function getStatusLabel($status) {
    $s = strtolower($status ?? '');
    if (str_contains($s, 'stable') || str_contains($s, 'stabil')) return '✅ Stabil';
    if (str_contains($s, 'referral') || str_contains($s, 'rujukan')) return '🚨 Perlu Rujukan';
    return '⚠️ Perlu Kontrol';
}
function getBpStatus($sys, $dia) {
    if ($sys >= 140 || $dia >= 90) return ['danger', 'Hipertensi'];
    if ($sys <= 90 || $dia <= 60) return ['danger', 'Hipotensi'];
    if ($sys >= 120 || $dia >= 80) return ['warning', 'Pre-Hipertensi'];
    return ['normal', 'Normal'];
}
function getTempStatus($temp) {
    if ($temp >= 38.0) return ['danger', '🔴 Demam'];
    if ($temp < 36.0) return ['warning', '🔵 Hipotermi'];
    return ['normal', '🟢 Normal'];
}

[$bpClass, $bpLabel] = getBpStatus($sys, $dia);
[$tempClass, $tempLabel] = getTempStatus($temp);

$statusClass = getStatusClass($monitoring['status'] ?? '');
$statusLabel = getStatusLabel($monitoring['status'] ?? '');

$monDate  = isset($monitoring['monitoring_date']) ? date('d F Y', strtotime($monitoring['monitoring_date'])) : '-';
$monTime  = isset($monitoring['monitoring_time']) ? date('H:i', strtotime($monitoring['monitoring_time'])) . ' WIB' : '';
$patName  = htmlspecialchars($monitoring['patient']['patient_name'] ?? $monitoring['patient_id'] ?? '-');
$patIdDisp = htmlspecialchars($monitoring['patient']['patient_id'] ?? $monitoring['patient_id'] ?? '-');
$patAddr  = htmlspecialchars($monitoring['patient']['address'] ?? '-');
$petugas  = htmlspecialchars($monitoring['user']['name'] ?? 'Petugas');

$user        = $_SESSION['user'] ?? [];
$userName    = htmlspecialchars($user['name']  ?? 'Petugas');
$userInitial = strtoupper(substr($user['name'] ?? 'P', 0, 1));
$userEmail   = htmlspecialchars($user['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pemeriksaan — SIVISIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="globals.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .detail-section {
            background: white;
            border: 1px solid var(--sv-border);
            border-radius: var(--sv-radius);
            overflow: hidden;
            margin-bottom: 16px;
            box-shadow: var(--sv-shadow-sm);
        }
        .detail-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-bottom: 1px solid #F0F2F5;
            background: #FAFBFC;
        }
        .detail-section-header .sec-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
        }
        .detail-section-header h6 {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
            color: var(--sv-text-main);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-section-body { padding: 20px; }
        .info-pair {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .info-pair .label {
            font-size: 10.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--sv-text-muted);
        }
        .info-pair .value {
            font-size: 14px;
            font-weight: 500;
            color: var(--sv-text-main);
        }
        .history-mini {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .history-mini-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: var(--sv-radius-sm);
            border: 1px solid var(--sv-border);
            cursor: pointer;
            transition: var(--sv-transition);
            text-decoration: none;
        }
        .history-mini-item:hover {
            border-color: var(--sv-blue);
            background: var(--sv-blue-light);
        }
        .history-mini-item.active-item {
            border-color: var(--sv-blue);
            background: var(--sv-blue-light);
        }
        .history-mini-item .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .dot-stable   { background: var(--sv-green); }
        .dot-control  { background: var(--sv-yellow); }
        .dot-referral { background: var(--sv-red); }
        @media print {
            .sv-sidebar, .sv-topbar, .no-print { display: none !important; }
            .sv-main { margin-left: 0 !important; }
            .sv-content { padding: 0 !important; }
            .detail-section { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
</head>
<body>
<div class="sv-layout">
    <?php require_once 'components/sidebar.php'; ?>

    <div class="sv-main">
        <!-- Topbar -->
        <div class="sv-topbar">
            <div class="sv-topbar-search">
                <?php include 'components/search-icon.php'; ?>
                <input type="text" placeholder="Cari pasien, NIK, atau kode pasien..." id="globalSearch" autocomplete="off">
            </div>
            <div class="sv-topbar-right">
                <div class="sv-user-info">
                    <div class="user-text">
                        <div class="user-name"><?= $userName ?></div>
                        <div class="user-role"><?= $userEmail ?></div>
                    </div>
                    <div class="sv-avatar"><?= $userInitial ?></div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="sv-content">

            <!-- Header -->
            <div class="sv-page-header sv-animate-in">
                <div>
                    <h1>Detail Pemeriksaan Umum</h1>
                    <p>
                        <span style="color:var(--sv-text-muted);"><?= $monDate ?></span>
                        <?php if ($monTime): ?>
                            <span style="color:var(--sv-border);margin:0 6px;">•</span>
                            <span style="color:var(--sv-text-muted);"><?= $monTime ?></span>
                        <?php endif; ?>
                        <span style="color:var(--sv-border);margin:0 6px;">•</span>
                        <span style="color:var(--sv-blue);font-weight:600;"><?= $petugas ?></span>
                    </p>
                </div>
                <div class="d-flex gap-2 no-print">
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">🖨️ Cetak</button>
                    <a href="tambah-monitoring.php?patient_id=<?= urlencode($patId) ?>" class="btn btn-primary btn-sm">🩺 Monitoring Baru</a>
                    <a href="monitoring.php" class="btn btn-outline-secondary btn-sm">← Kembali</a>
                </div>
            </div>

            <div class="row g-3">

                <!-- Main Content -->
                <div class="col-12 col-xl-8">

                    <!-- Patient Info -->
                    <div class="detail-section sv-animate-in sv-animate-in-1">
                        <div class="detail-section-header">
                            <div class="sec-icon" style="background:#E8F1FF;">👤</div>
                            <h6>Informasi Pasien</h6>
                        </div>
                        <div class="detail-section-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="info-pair">
                                        <span class="label">Nama Pasien</span>
                                        <span class="value" style="font-weight:700;font-size:16px;"><?= $patName ?></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-pair">
                                        <span class="label">Kode Pasien / No. RM</span>
                                        <span class="value" style="color:var(--sv-blue);font-weight:600;"><?= $patIdDisp ?></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-pair">
                                        <span class="label">Tanggal Pemeriksaan</span>
                                        <span class="value"><?= $monDate ?></span>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="info-pair">
                                        <span class="label">Jam Kunjungan</span>
                                        <span class="value"><?= $monTime ?: '-' ?></span>
                                    </div>
                                </div>
                                <?php if ($patAddr !== '-'): ?>
                                <div class="col-12">
                                    <div class="info-pair">
                                        <span class="label">Alamat Pasien</span>
                                        <span class="value" style="color:var(--sv-text-sub);"><?= $patAddr ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Vital Signs -->
                    <div class="detail-section sv-animate-in sv-animate-in-2">
                        <div class="detail-section-header">
                            <div class="sec-icon" style="background:#FFF0EF;">🩺</div>
                            <h6>Tanda-Tanda Vital</h6>
                            <span class="ms-auto <?= 'sv-status-pill ' . $statusClass ?>"><?= $statusLabel ?></span>
                        </div>
                        <div class="detail-section-body">
                            <div class="row g-3">
                                <!-- Tekanan Darah -->
                                <div class="col-6 col-md-4">
                                    <div class="sv-vital-card">
                                        <div class="vital-label">
                                            <span>❤️</span> Tekanan Darah
                                        </div>
                                        <?php if ($sys && $dia): ?>
                                        <div class="sv-vital-big">
                                            <span class="val"><?= $sys ?></span>
                                            <span class="sep">/</span>
                                            <span class="val"><?= $dia ?></span>
                                            <span class="unit">mmHg</span>
                                        </div>
                                        <div class="vital-status vital-<?= $bpClass ?>"><?= $bpLabel ?></div>
                                        <?php else: ?>
                                        <div class="sv-vital-big"><span class="val" style="font-size:24px;"><?= htmlspecialchars($bpRaw ?: '-') ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Suhu Tubuh -->
                                <div class="col-6 col-md-4">
                                    <div class="sv-vital-card">
                                        <div class="vital-label">
                                            <span>🌡️</span> Suhu Tubuh
                                        </div>
                                        <div class="sv-vital-big">
                                            <span class="val"><?= $temp ? number_format($temp, 1) : '-' ?></span>
                                            <span class="unit">°C</span>
                                        </div>
                                        <?php if ($temp): ?>
                                        <div class="vital-status vital-<?= $tempClass ?>"><?= $tempLabel ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Nadi -->
                                <?php if ($hr): ?>
                                <div class="col-6 col-md-4">
                                    <div class="sv-vital-card">
                                        <div class="vital-label"><span>💓</span> Nadi</div>
                                        <div class="sv-vital-big">
                                            <span class="val"><?= $hr ?></span>
                                            <span class="unit">bpm</span>
                                        </div>
                                        <div class="vital-status vital-<?= ($hr >= 60 && $hr <= 100) ? 'normal' : 'warning' ?>">
                                            <?= ($hr >= 60 && $hr <= 100) ? 'Normal' : 'Di luar normal' ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Laju Napas -->
                                <?php if ($rr): ?>
                                <div class="col-6 col-md-4">
                                    <div class="sv-vital-card">
                                        <div class="vital-label"><span>🫁</span> Laju Napas</div>
                                        <div class="sv-vital-big">
                                            <span class="val"><?= $rr ?></span>
                                            <span class="unit">x/mnt</span>
                                        </div>
                                        <div class="vital-status vital-<?= ($rr >= 12 && $rr <= 20) ? 'normal' : 'warning' ?>">
                                            <?= ($rr >= 12 && $rr <= 20) ? 'Normal' : 'Di luar normal' ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- SpO2 -->
                                <?php if ($spo2): ?>
                                <div class="col-6 col-md-4">
                                    <div class="sv-vital-card">
                                        <div class="vital-label"><span>🩸</span> Saturasi O₂</div>
                                        <div class="sv-vital-big">
                                            <span class="val"><?= number_format($spo2, 0) ?></span>
                                            <span class="unit">%</span>
                                        </div>
                                        <div class="vital-status vital-<?= $spo2 >= 95 ? 'normal' : 'danger' ?>">
                                            <?= $spo2 >= 95 ? 'Normal' : '⚠️ Rendah' ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Lokasi Pasien -->
                    <div class="detail-section sv-animate-in sv-animate-in-3">
                        <div class="detail-section-header">
                            <div class="sec-icon" style="background:#E8F1FF;">📍</div>
                            <h6>Lokasi Pasien</h6>
                            <span id="mapStatus" style="font-size:12px;color:var(--sv-text-muted);">Memuat peta...</span>
                        </div>
                        <div class="detail-section-body" style="padding:0;">
                            <div id="patientMap" style="height:280px;border-radius:0 0 var(--sv-radius) var(--sv-radius);"></div>
                            <div id="mapFallback" style="display:none;padding:24px;text-align:center;color:var(--sv-text-muted);">
                                <div style="font-size:32px;margin-bottom:8px;">🗺️</div>
                                <p id="mapFallbackText">Alamat tidak dapat ditemukan di peta.</p>
                                <p style="font-size:12px;">Alamat: <?= $patAddr ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Keluhan & Catatan -->
                    <div class="detail-section sv-animate-in sv-animate-in-4">
                        <div class="detail-section-header">
                            <div class="sec-icon" style="background:#E8F8ED;">📝</div>
                            <h6>Keluhan &amp; Catatan Administratif</h6>
                        </div>
                        <div class="detail-section-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-pair">
                                        <span class="label">Keluhan / Kondisi Pasien</span>
                                        <p class="value mt-1" style="line-height:1.7;color:var(--sv-text-sub);">
                                            <?= nl2br(htmlspecialchars($monitoring['symptoms'] ?? 'Tidak ada catatan keluhan.')) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-pair">
                                        <span class="label">Rekomendasi Tindak Lanjut</span>
                                        <p class="value mt-1" style="line-height:1.7;color:var(--sv-text-sub);">
                                            <?= nl2br(htmlspecialchars($monitoring['notes'] ?? 'Tidak ada catatan rekomendasi.')) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 p-3 rounded" style="background:#F8F9FA;font-size:12px;color:#8E8E93;line-height:1.6;">
                                ⚠️ <strong>Disclaimer:</strong> Rekomendasi di atas bersifat administratif dan tidak merupakan diagnosis atau nasihat medis.
                                Data ini merupakan simulasi untuk keperluan akademik.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: Riwayat Kunjungan -->
                <div class="col-12 col-xl-4 sv-animate-in">
                    <div class="sv-card h-100">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                            <h5 style="font-size:14px;font-weight:700;margin:0;">📂 Riwayat Kunjungan</h5>
                            <a href="rekam-medis.php?patient_id=<?= urlencode($patId) ?>"
                               style="font-size:12px;color:var(--sv-blue);">Lihat Semua</a>
                        </div>
                        <div class="history-mini">
                            <?php foreach (array_slice($patientMonitorings, 0, 8) as $hist):
                                $histClass = getStatusClass($hist['status'] ?? '');
                                $histIsActive = (int)($hist['id'] ?? -1) === (int)($monitoring['id'] ?? 0);
                            ?>
                            <a href="detail-monitoring.php?id=<?= (int)($hist['id'] ?? 0) ?>"
                               class="history-mini-item <?= $histIsActive ? 'active-item' : '' ?>">
                                <div class="dot dot-<?= $histClass ?>"></div>
                                <div style="flex:1;overflow:hidden;">
                                    <div style="font-size:12px;font-weight:600;color:var(--sv-text-main);">
                                        <?= isset($hist['monitoring_date']) ? date('d M Y', strtotime($hist['monitoring_date'])) : '-' ?>
                                    </div>
                                    <div style="font-size:11px;color:var(--sv-text-muted);">
                                        <?= htmlspecialchars($hist['blood_pressure'] ?? '-') ?> mmHg
                                        <?php if ($hist['body_temperature'] ?? ''): ?>
                                            · <?= htmlspecialchars($hist['body_temperature']) ?>°C
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($histIsActive): ?>
                                <span style="font-size:10px;color:var(--sv-blue);font-weight:600;">AKTIF</span>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                            <?php if (empty($patientMonitorings)): ?>
                            <div class="sv-empty-state" style="padding:24px 0;">
                                <div class="empty-icon">🩺</div>
                                <p>Belum ada riwayat monitoring.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3 pt-3" style="border-top:1px solid var(--sv-border);">
                            <a href="tambah-monitoring.php?patient_id=<?= urlencode($patId) ?>"
                               class="btn btn-primary w-100 btn-sm">
                                🩺 Tambah Monitoring Baru
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div><!-- /.sv-content -->

        <footer style="padding:20px 24px;border-top:1px solid #E8ECF0;background:#FAFBFC;" class="no-print">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <span style="font-size:12px;color:#8E8E93;">Sivisit-Kelompok 9 S1 Informatika UAS Pemrograman WEB ITSK Rs Dr Soepraoen Malang</span>
                <span style="font-size:11px;color:#8E8E93;font-style:italic;">⚠️ Data simulasi/dummy. Bukan diagnosis medis. Rekomendasi hanya tindak lanjut administratif.</span>
            </div>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.getElementById('globalSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim())
            window.location.href = 'cari-pasien.php?q=' + encodeURIComponent(this.value.trim());
    });

    // ─── Map Lokasi Pasien ──────────────────────────────────
    (function() {
        const address = <?= json_encode($patAddr !== '-' ? $patAddr : '') ?>;
        const mapEl = document.getElementById('patientMap');
        const fallbackEl = document.getElementById('mapFallback');
        const statusEl = document.getElementById('mapStatus');

        if (!address) {
            mapEl.style.display = 'none';
            fallbackEl.style.display = 'block';
            document.getElementById('mapFallbackText').textContent = 'Tidak ada alamat pasien.';
            statusEl.textContent = 'Tidak ada data';
            return;
        }

        // Try geocoding via Nominatim
        const apiUrl = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address + ', Indonesia') + '&limit=1';

        fetch(apiUrl, {
            headers: { 'Accept': 'application/json', 'User-Agent': 'SIVISIT-CareVisitMonitor/1.0' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data || data.length === 0) {
                mapEl.style.display = 'none';
                fallbackEl.style.display = 'block';
                statusEl.textContent = 'Alamat tidak ditemukan';
                return;
            }

            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);

            statusEl.textContent = '📍 ' + (data[0].display_name || '').split(',').slice(0,3).join(',');

            const map = L.map('patientMap', {
                center: [lat, lng],
                zoom: 15,
                zoomControl: true,
                attributionControl: false,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(map);

            L.marker([lat, lng]).addTo(map)
                .bindPopup(`
                    <strong><?= $patName ?></strong><br>
                    <small><?= $patIdDisp ?></small><br>
                    <small>📍 ${data[0].display_name || address}</small>
                `)
                .openPopup();

            setTimeout(() => map.invalidateSize(), 500);
        })
        .catch(err => {
            console.warn('Geocoding failed:', err);
            mapEl.style.display = 'none';
            fallbackEl.style.display = 'block';
            statusEl.textContent = 'Gagal memuat peta';
        });
    })();
</script>
</body>
</html>
