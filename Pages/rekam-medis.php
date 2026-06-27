<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

// Fetch all patients
$patientsRes = callAPI('GET', '/pasien');
$patients    = ($patientsRes['status_code'] === 200 && isset($patientsRes['response']['data']))
    ? $patientsRes['response']['data'] : [];

// Fetch all monitorings
$monitoringsRes = callAPI('GET', '/monitoring');
$allMonitorings = ($monitoringsRes['status_code'] === 200 && isset($monitoringsRes['response']['data']))
    ? $monitoringsRes['response']['data'] : [];

// Build patient → monitorings map
$patientMap = [];
foreach ($patients as $p) {
    $patientMap[$p['patient_id']] = $p;
}
$monByPatient = [];
foreach ($allMonitorings as $m) {
    $pid = $m['patient_id'] ?? '';
    if ($pid) $monByPatient[$pid][] = $m;
}
// Sort each patient's monitorings by date desc
foreach ($monByPatient as $pid => &$mons) {
    usort($mons, fn($a, $b) => strtotime($b['monitoring_date'] ?? '') <=> strtotime($a['monitoring_date'] ?? ''));
}
unset($mons);

// Filter: if patient_id specified, focus on that patient
$filterPatient = $_GET['patient_id'] ?? '';
$filterStatus  = strtolower($_GET['status'] ?? '');

// Status helpers
function getStatusClass($s) {
    $s = strtolower($s ?? '');
    if (str_contains($s, 'stable') || str_contains($s, 'stabil')) return 'stable';
    if (str_contains($s, 'referral') || str_contains($s, 'rujukan')) return 'referral';
    return 'control';
}
function getStatusLabel($s) {
    $s = strtolower($s ?? '');
    if (str_contains($s, 'stable') || str_contains($s, 'stabil')) return '✅ Stabil';
    if (str_contains($s, 'referral') || str_contains($s, 'rujukan')) return '🚨 Perlu Rujukan';
    return '⚠️ Perlu Kontrol';
}
function getStatusBadgeHtml($status) {
    $cls = getStatusClass($status);
    $lbl = getStatusLabel($status);
    $colors = [
        'stable'   => 'background:var(--sv-green-light);color:#1A7A35;',
        'control'  => 'background:var(--sv-yellow-light);color:#8A4E00;',
        'referral' => 'background:var(--sv-red-light);color:#C0291F;',
    ];
    return '<span class="sv-badge" style="' . ($colors[$cls] ?? '') . '">' . htmlspecialchars($lbl) . '</span>';
}

function calculateAge($dob) {
    if (empty($dob)) return '-';
    $d = new DateTime($dob);
    return (new DateTime())->diff($d)->y . ' Thn';
}

$user        = $_SESSION['user'] ?? [];
$userName    = htmlspecialchars($user['name']  ?? 'Petugas');
$userInitial = strtoupper(substr($user['name'] ?? 'P', 0, 1));
$userEmail   = htmlspecialchars($user['email'] ?? '');

// Filter patients to show
$displayPatients = $patients;
if ($filterPatient) {
    $displayPatients = array_filter($patients, fn($p) => ($p['patient_id'] ?? '') === $filterPatient);
}

// Count stats
$totalVisits = count($allMonitorings);
$countStable = $countControl = $countReferral = 0;
foreach ($allMonitorings as $m) {
    $cls = getStatusClass($m['status'] ?? '');
    if ($cls === 'stable')   $countStable++;
    elseif ($cls === 'referral') $countReferral++;
    else                    $countControl++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekam Medis — SIVISIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="globals.css" rel="stylesheet">
    <style>
        .patient-rm-card {
            background: var(--sv-surface);
            border: 1px solid var(--sv-border);
            border-radius: var(--sv-radius);
            overflow: hidden;
            box-shadow: var(--sv-shadow-sm);
            margin-bottom: 16px;
            transition: var(--sv-transition);
        }
        .patient-rm-card:hover {
            box-shadow: var(--sv-shadow);
        }
        .patient-rm-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            background: linear-gradient(135deg, var(--sv-navy) 0%, var(--sv-navy-mid) 100%);
            cursor: pointer;
        }
        .patient-rm-header .rm-avatar {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .patient-rm-header .rm-name {
            font-size: 15px;
            font-weight: 700;
            color: white;
            margin-bottom: 2px;
        }
        .patient-rm-header .rm-meta {
            font-size: 11.5px;
            color: rgba(255,255,255,0.6);
        }
        .patient-rm-header .rm-count {
            margin-left: auto;
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            text-align: right;
        }
        .patient-rm-header .rm-count strong {
            display: block;
            font-size: 20px;
            font-weight: 800;
            color: white;
            line-height: 1;
        }
        .patient-rm-body {
            padding: 20px;
        }
        .no-visits-msg {
            text-align: center;
            padding: 28px;
            color: var(--sv-text-muted);
            font-size: 13px;
        }
        .filter-bar {
            background: white;
            border: 1px solid var(--sv-border);
            border-radius: var(--sv-radius);
            padding: 12px 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filter-chip {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 500;
            border: 1.5px solid var(--sv-border);
            background: white;
            color: var(--sv-text-sub);
            cursor: pointer;
            text-decoration: none;
            transition: var(--sv-transition);
        }
        .filter-chip:hover,
        .filter-chip.active {
            border-color: var(--sv-blue);
            background: var(--sv-blue-light);
            color: var(--sv-blue-dark);
        }
        .filter-chip.stable.active   { border-color: var(--sv-green);  background: var(--sv-green-light);  color: #1A7A35; }
        .filter-chip.control.active  { border-color: var(--sv-yellow); background: var(--sv-yellow-light); color: #8A4E00; }
        .filter-chip.referral.active { border-color: var(--sv-red);    background: var(--sv-red-light);    color: #C0291F; }
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

            <!-- Page Header -->
            <div class="sv-page-header sv-animate-in">
                <div>
                    <h1>Rekam Medis Pasien</h1>
                    <p>Riwayat seluruh kunjungan monitoring per pasien binaan.</p>
                </div>
                <a href="tambah-monitoring.php" class="btn btn-primary">🩺 Catat Monitoring</a>
            </div>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-4 sv-animate-in sv-animate-in-1">
                    <div class="sv-stat-card" style="--accent-color:#007AFF;">
                        <div class="stat-icon">📂</div>
                        <div class="stat-label">Total Kunjungan</div>
                        <div class="stat-value" style="color:#007AFF;"><?= $totalVisits ?></div>
                        <div class="stat-sub">Seluruh monitoring tercatat</div>
                    </div>
                </div>
                <div class="col-4 sv-animate-in sv-animate-in-2">
                    <div class="sv-stat-card" style="--accent-color:#34C759;">
                        <div class="stat-icon">✅</div>
                        <div class="stat-label">Stabil</div>
                        <div class="stat-value" style="color:#34C759;"><?= $countStable ?></div>
                        <div class="stat-sub">Kondisi terkontrol</div>
                    </div>
                </div>
                <div class="col-4 sv-animate-in sv-animate-in-3">
                    <div class="sv-stat-card" style="--accent-color:#FF3B30;">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-label">Perlu Tindak Lanjut</div>
                        <div class="stat-value" style="color:#FF3B30;"><?= $countControl + $countReferral ?></div>
                        <div class="stat-sub">Kontrol + rujukan</div>
                    </div>
                </div>
            </div>

            <!-- Filter bar -->
            <div class="filter-bar sv-animate-in">
                <span style="font-size:12.5px;font-weight:600;color:var(--sv-text-muted);">Filter Pasien:</span>
                <a href="rekam-medis.php"
                   class="filter-chip <?= !$filterPatient ? 'active' : '' ?>">
                   Semua (<?= count($patients) ?>)
                </a>
                <?php foreach ($patients as $p): ?>
                <a href="rekam-medis.php?patient_id=<?= urlencode($p['patient_id']) ?>"
                   class="filter-chip <?= $filterPatient === $p['patient_id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($p['patient_name']) ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Patient Records -->
            <?php if (empty($displayPatients)): ?>
            <div class="sv-empty-state" style="padding:60px 24px;">
                <div class="empty-icon">📂</div>
                <p>Tidak ada data pasien ditemukan.</p>
            </div>
            <?php else: ?>
                <?php foreach ($displayPatients as $p):
                    $pid   = $p['patient_id'];
                    $pMons = $monByPatient[$pid] ?? [];
                    $gender = ($p['gender'] ?? '') === 'Male' ? '👨' : '👩';
                    $latestStatus = $pMons[0]['status'] ?? '';
                    $latestClass  = $latestStatus ? getStatusClass($latestStatus) : '';
                    $age   = calculateAge($p['datebirth'] ?? '');
                ?>
                <div class="patient-rm-card sv-animate-in">
                    <!-- Patient Header -->
                    <a href="rekam-medis.php?patient_id=<?= urlencode($pid) ?>"
                       class="patient-rm-header text-decoration-none">
                        <div class="rm-avatar"><?= $gender ?></div>
                        <div>
                            <div class="rm-name"><?= htmlspecialchars($p['patient_name'] ?? '-') ?></div>
                            <div class="rm-meta">
                                <?= htmlspecialchars($pid) ?> &nbsp;·&nbsp;
                                <?= $age ?> &nbsp;·&nbsp;
                                <?= htmlspecialchars($p['patient_category'] ?? '-') ?>
                            </div>
                            <div class="rm-meta" style="margin-top:3px;">
                                📍 <?= htmlspecialchars(mb_strimwidth($p['address'] ?? '-', 0, 50, '…')) ?>
                            </div>
                        </div>
                        <div class="rm-count">
                            <strong><?= count($pMons) ?></strong>
                            kunjungan
                        </div>
                    </a>

                    <!-- Monitoring Timeline -->
                    <div class="patient-rm-body">
                        <?php if (empty($pMons)): ?>
                        <div class="no-visits-msg">
                            🩺 Belum ada catatan monitoring untuk pasien ini.
                            <a href="tambah-monitoring.php?patient_id=<?= urlencode($pid) ?>" style="color:var(--sv-blue);margin-left:6px;">
                                Catat sekarang →
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="rm-timeline">
                            <?php foreach ($pMons as $mon):
                                $mClass = getStatusClass($mon['status'] ?? '');
                                $mDate  = isset($mon['monitoring_date']) ? date('d M Y', strtotime($mon['monitoring_date'])) : '-';
                                $mTime  = isset($mon['monitoring_time']) ? date('H:i', strtotime($mon['monitoring_time'])) . ' WIB' : '';
                                $monId  = (int)($mon['id'] ?? 0);
                            ?>
                            <div class="rm-timeline-item <?= $mClass ?>">
                                <a href="detail-monitoring.php?id=<?= $monId ?>" class="rm-timeline-card text-decoration-none d-block">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div>
                                            <div class="rm-date"><?= $mDate ?> <?= $mTime ? '· ' . $mTime : '' ?></div>
                                            <div class="rm-title">Monitoring Umum</div>
                                            <div class="rm-vitals">
                                                <?php if ($mon['blood_pressure'] ?? ''): ?>
                                                <span>❤️ <?= htmlspecialchars($mon['blood_pressure']) ?> mmHg</span>
                                                <?php endif; ?>
                                                <?php if ($mon['body_temperature'] ?? ''): ?>
                                                <span>🌡️ <?= htmlspecialchars($mon['body_temperature']) ?>°C</span>
                                                <?php endif; ?>
                                                <?php if ($mon['heart_rate'] ?? ''): ?>
                                                <span>💓 <?= htmlspecialchars($mon['heart_rate']) ?> bpm</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($mon['symptoms'] ?? ''): ?>
                                            <div style="font-size:11.5px;color:var(--sv-text-muted);margin-top:6px;line-height:1.5;">
                                                📋 <?= htmlspecialchars(mb_strimwidth($mon['symptoms'], 0, 80, '…')) ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <?= getStatusBadgeHtml($mon['status'] ?? '') ?>
                                            <div style="font-size:11px;color:var(--sv-blue);margin-top:6px;font-weight:500;">
                                                Lihat Detail →
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-end mt-3">
                            <a href="tambah-monitoring.php?patient_id=<?= urlencode($pid) ?>"
                               class="btn btn-sm btn-outline-primary">
                                🩺 Tambah Monitoring
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div><!-- /.sv-content -->

        <footer style="padding:20px 24px;border-top:1px solid #E8ECF0;background:#FAFBFC;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <span style="font-size:12px;color:#8E8E93;">Sivisit-Kelompok 9 S1 Informatika UAS Pemrograman WEB ITSK Rs Dr Soepraoen Malang</span>
                <span style="font-size:11px;color:#8E8E93;font-style:italic;">⚠️ Data simulasi/dummy. Bukan diagnosis medis. Rekomendasi hanya tindak lanjut administratif.</span>
            </div>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('globalSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim())
            window.location.href = 'cari-pasien.php?q=' + encodeURIComponent(this.value.trim());
    });
</script>
</body>
</html>
