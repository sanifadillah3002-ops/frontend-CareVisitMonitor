<?php
require_once '../config.php';
require_once 'components/sf-icons.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

$patientsRes  = callAPI('GET', '/pasien');
$patients     = ($patientsRes['status_code'] === 200 && isset($patientsRes['response']['data'])) ? $patientsRes['response']['data'] : [];

$monitoringsRes = callAPI('GET', '/monitoring');
$monitorings    = ($monitoringsRes['status_code'] === 200 && isset($monitoringsRes['response']['data'])) ? $monitoringsRes['response']['data'] : [];

$totalPatients  = count($patients);
$todayDate      = date('Y-m-d');
$todayVisits    = 0;
$needControl    = 0;
$todayAgenda    = [];

foreach ($monitorings as $m) {
    $status = strtolower($m['status'] ?? '');
    if (str_contains($status, 'control') || str_contains($status, 'kontrol')) $needControl++;
    if (($m['monitoring_date'] ?? '') === $todayDate) {
        $todayVisits++;
        $todayAgenda[] = $m;
    }
}

$user = $_SESSION['user'] ?? [];
$userName    = htmlspecialchars($user['name']  ?? 'Petugas');
$userInitial = strtoupper(substr($user['name'] ?? 'P', 0, 1));
$userEmail   = htmlspecialchars($user['email'] ?? '');

function getStatusBadge($status) {
    $s = strtolower($status ?? '');
    if (str_contains($s, 'stable') || str_contains($s, 'stabil')) {
        return '<span class="sv-badge sv-badge-stable"> Stabil</span>';
    } elseif (str_contains($s, 'referral') || str_contains($s, 'rujukan')) {
        return '<span class="sv-badge sv-badge-referral"> Perlu Rujukan</span>';
    } else {
        return '<span class="sv-badge sv-badge-control"> Perlu Kontrol</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SIVISIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="globals.css" rel="stylesheet">
    <style>
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>
<div class="sv-layout">

    <?php require_once 'components/sidebar.php'; ?>

    <div class="sv-main">
        <div class="sv-topbar">
            <div class="sv-topbar-search">
                <?php include 'components/search-icon.php'; ?>
                <input
                    type="text"
                    placeholder="Cari pasien, NIK, atau kode..."
                    id="globalSearch"
                    autocomplete="off"
                >
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

        <div class="sv-content">
            <div class="sv-page-header">
                <div>
                    <h1>Selamat Datang, <?= $userName ?> 👋</h1>
                    <p>Berikut ringkasan kondisi pasien home care Anda hari ini, <?= date('d F Y') ?>.</p>
                </div>
                <a href="tambah-pasien.php" class="btn btn-primary">
                     Tambah Pasien
                </a>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
                <div class="sv-stat-card" style="--accent-color:var(--sv-blue);">
                    <div class="stat-label">Total Pasien</div>
                    <div class="stat-value"><?= $totalPatients ?></div>
                    <div class="stat-sub">Pasien terdaftar</div>
                    <div class="stat-icon"><?= sf_icon('person-2', 36) ?></div>
                </div>
                <div class="sv-stat-card" style="--accent-color:var(--sv-yellow);">
                    <div class="stat-label">Kunjungan Hari Ini</div>
                    <div class="stat-value"><?= $todayVisits ?></div>
                    <div class="stat-sub">Agenda monitoring</div>
                    <div class="stat-icon"><?= sf_icon('calendar', 36) ?></div>
                </div>
                <div class="sv-stat-card" style="--accent-color:var(--sv-red);">
                    <div class="stat-label">Perlu Kontrol</div>
                    <div class="stat-value"><?= $needControl ?></div>
                    <div class="stat-sub">Butuh perhatian</div>
                    <div class="stat-icon"><?= sf_icon('exclamation-triangle', 36) ?></div>
                </div>
                <div class="sv-stat-card" style="--accent-color:var(--sv-green);">
                    <div class="stat-label">Total Monitoring</div>
                    <div class="stat-value"><?= count($monitorings) ?></div>
                    <div class="stat-sub">Semua catatan</div>
                    <div class="stat-icon"><?= sf_icon('clipboard', 36) ?></div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;" class="dashboard-grid">
                <div class="sv-card" style="padding:0;">
                    <div class="sv-section-header">
                        <h5>📋 Agenda Kunjungan Hari Ini</h5>
                        <a href="monitoring.php" class="btn btn-outline-primary btn-sm" style="text-decoration:none;">Lihat Semua</a>
                    </div>
                    <div class="sv-table-wrap" style="border:none;border-radius:0;box-shadow:none;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Jam</th>
                                    <th>Nama Pasien</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($todayAgenda)): ?>
                                    <tr>
                                        <td colspan="4">
                                            <div class="sv-empty-state">
                                                <div class="empty-icon"><?= sf_icon('calendar', 40) ?></div>
                                                <p>Tidak ada agenda kunjungan hari ini.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($todayAgenda as $ag): ?>
                                        <tr>
                                            <td style="font-weight:600;">
                                                <?= isset($ag['monitoring_time']) ? date('H:i', strtotime($ag['monitoring_time'])) : '--:--' ?> WIB
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($ag['patient']['patient_name'] ?? '-') ?>
                                                <br><small style="color:var(--sv-text-muted);"><?= htmlspecialchars($ag['patient']['address'] ?? '') ?></small>
                                            </td>
                                            <td><?= getStatusBadge($ag['status'] ?? '') ?></td>
                                            <td><a href="detail-monitoring.php?id=<?= $ag['id'] ?>" class="btn btn-outline-primary btn-sm" style="text-decoration:none;">Detail</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="sv-card" style="display:flex;flex-direction:column;gap:16px;">
                    <h5 style="font-size:15px;font-weight:600;margin:0;">🔍 Cari Cepat</h5>
                    <p style="font-size:13px;color:var(--sv-text-muted);margin:0;">Masukkan kode pasien atau NIK untuk riwayat.</p>
                    <form action="cari-pasien.php" method="GET" style="display:flex;flex-direction:column;gap:8px;">
                        <input type="text" name="q" class="form-control" placeholder="Kode pasien / NIK dummy...">
                        <button type="submit" class="btn btn-primary">Cari Data</button>
                    </form>
                    <hr style="border:none;border-top:1px solid var(--sv-border);margin:8px 0;">
                    <h5 style="font-size:13px;font-weight:600;text-transform:uppercase;color:var(--sv-text-muted);margin:0;">Aksi Cepat</h5>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <a href="tambah-pasien.php" class="btn btn-outline-primary" style="text-decoration:none;text-align:left;"> Tambah Pasien Baru</a>
                        <a href="tambah-monitoring.php" class="btn btn-outline-primary" style="text-decoration:none;text-align:left;">🩺 Catat Monitoring</a>
                        <a href="lokasi-petugas.php" class="btn btn-outline-primary" style="text-decoration:none;text-align:left;">📍 Monitoring Lokasi</a>
                        <a href="monitoring.php" class="btn btn-outline-primary" style="text-decoration:none;text-align:left;">📋 Semua Monitoring</a>
                    </div>
                </div>
            </div>
        </div>

        <footer style="padding:16px 24px;border-top:1px solid var(--sv-border);text-align:center;color:var(--sv-text-muted);font-size:13px;background:var(--sv-surface);">
            Sivisit-Kelompok 9 S1 Informatika UAS Pemrograman WEB ITSK Rs Dr Soepraoen Malang — Data simulasi, bukan diagnosis medis.
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('globalSearch')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim()) {
            window.location.href = 'cari-pasien.php?q=' + encodeURIComponent(this.value.trim());
        }
    });
</script>
</body>
</html>
