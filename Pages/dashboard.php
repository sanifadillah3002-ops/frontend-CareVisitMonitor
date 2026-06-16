<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

// Fetch data
$patientsRes  = callAPI('GET', '/patients');
$patients     = ($patientsRes['status_code'] === 200 && isset($patientsRes['response']['data'])) ? $patientsRes['response']['data'] : [];

$monitoringsRes = callAPI('GET', '/monitorings');
$monitorings    = ($monitoringsRes['status_code'] === 200 && isset($monitoringsRes['response']['data'])) ? $monitoringsRes['response']['data'] : [];

// Statistics
$totalPatients  = count($patients);
$todayDate      = date('Y-m-d');
$todayVisits    = 0;
$todayFinished  = 0;
$needControl    = 0;
$needReferral   = 0;
$todayAgenda    = [];

foreach ($monitorings as $m) {
    $status = strtolower($m['status'] ?? '');
    if (str_contains($status, 'control') || str_contains($status, 'kontrol')) $needControl++;
    if (str_contains($status, 'referral') || str_contains($status, 'rujukan')) $needReferral++;

    if (($m['monitoring_date'] ?? '') === $todayDate) {
        $todayVisits++;
        if ($status === 'stable' || $status === 'stabil') $todayFinished++;
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
        return '<span class="sv-badge sv-badge-stable">✅ Stabil</span>';
    } elseif (str_contains($s, 'referral') || str_contains($s, 'rujukan')) {
        return '<span class="sv-badge sv-badge-referral">🚨 Perlu Rujukan</span>';
    } else {
        return '<span class="sv-badge sv-badge-control">⚠️ Perlu Kontrol</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SIVISIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="globals.css" rel="stylesheet">
</head>
<body>
<div class="sv-layout">

    <?php require_once 'components/sidebar.php'; ?>

    <div class="sv-main">
        <!-- Topbar -->
        <div class="sv-topbar">
            <div class="sv-topbar-search">
                <span class="search-icon">🔍</span>
                <input
                    type="text"
                    placeholder="Cari pasien, NIK, atau kode pasien..."
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

        <!-- Content -->
        <div class="sv-content">

            <!-- Page Header -->
            <div class="sv-page-header sv-animate-in">
                <div>
                    <h1>Selamat Datang, <?= $userName ?> 👋</h1>
                    <p>Berikut ringkasan kondisi pasien home care Anda hari ini, <?= date('d F Y') ?>.</p>
                </div>
                <a href="tambah-pasien.php" class="btn btn-primary">
                    ➕ Tambah Pasien
                </a>
            </div>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3 sv-animate-in sv-animate-in-1">
                    <div class="sv-stat-card" style="--accent-color: #007AFF;">
                        <div class="stat-icon">👥</div>
                        <div class="stat-label">Total Pasien</div>
                        <div class="stat-value" style="color:#007AFF;"><?= $totalPatients ?></div>
                        <div class="stat-sub">Pasien terdaftar</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 sv-animate-in sv-animate-in-2">
                    <div class="sv-stat-card" style="--accent-color: #FF9500;">
                        <div class="stat-icon">📅</div>
                        <div class="stat-label">Kunjungan Hari Ini</div>
                        <div class="stat-value" style="color:#FF9500;"><?= $todayVisits ?></div>
                        <div class="stat-sub">Monitoring tercatat</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 sv-animate-in sv-animate-in-3">
                    <div class="sv-stat-card" style="--accent-color: #FF3B30;">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-label">Perlu Kontrol</div>
                        <div class="stat-value" style="color:#FF3B30;"><?= $needControl ?></div>
                        <div class="stat-sub">Butuh tindak lanjut</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 sv-animate-in sv-animate-in-4">
                    <div class="sv-stat-card" style="--accent-color: #34C759;">
                        <div class="stat-icon">✅</div>
                        <div class="stat-label">Status Stabil</div>
                        <div class="stat-value" style="color:#34C759;"><?= $todayFinished ?></div>
                        <div class="stat-sub">Selesai hari ini</div>
                    </div>
                </div>
            </div>

            <!-- Agenda & Quick Lookup -->
            <div class="row g-3">

                <!-- Today's Agenda Table -->
                <div class="col-12 col-xl-8 sv-animate-in">
                    <div class="sv-table-wrap">
                        <div class="sv-section-header">
                            <h5>📋 Agenda Kunjungan Hari Ini</h5>
                            <a href="monitoring.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                        </div>
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Jam</th>
                                    <th>Nama Pasien</th>
                                    <th>Alamat</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($todayAgenda)): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="sv-empty-state">
                                                <div class="empty-icon">📅</div>
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
                                            <td style="font-weight:500;">
                                                <?= htmlspecialchars($ag['patient']['patient_name'] ?? '-') ?>
                                            </td>
                                            <td style="color:#636366;">
                                                <?= htmlspecialchars($ag['patient']['address'] ?? '-') ?>
                                            </td>
                                            <td>
                                                <?= getStatusBadge($ag['status'] ?? '') ?>
                                            </td>
                                            <td>
                                                <a href="pasien.php" class="btn btn-sm btn-outline-primary py-0" style="font-size:12px;">Detail</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Lookup -->
                <div class="col-12 col-xl-4 sv-animate-in">
                    <div class="sv-card h-100">
                        <h5 style="font-size:15px;font-weight:600;margin-bottom:6px;">🔍 Cari Pasien Cepat</h5>
                        <p style="font-size:13px;color:#636366;margin-bottom:16px;">Masukkan kode pasien atau NIK untuk melihat riwayat monitoring.</p>
                        <form action="cari-pasien.php" method="GET">
                            <div class="mb-3">
                                <input
                                    type="text"
                                    name="q"
                                    class="form-control"
                                    placeholder="Kode pasien / NIK dummy..."
                                    id="quickSearch"
                                >
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                Cari Data Monitoring
                            </button>
                        </form>

                        <hr style="border-color:#F0F2F5;margin:20px 0;">

                        <h6 style="font-size:13px;font-weight:600;color:#636366;margin-bottom:12px;">AKSI CEPAT</h6>
                        <div class="d-flex flex-column gap-2">
                            <a href="tambah-pasien.php" class="btn btn-sm btn-outline-primary">➕ Tambah Pasien Baru</a>
                            <a href="tambah-monitoring.php" class="btn btn-sm btn-outline-secondary">🩺 Catat Monitoring</a>
                            <a href="monitoring.php" class="btn btn-sm btn-outline-secondary">📋 Lihat Semua Monitoring</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <footer style="padding:20px 24px;border-top:1px solid #E8ECF0;background:#FAFBFC;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <span style="font-size:12px;color:#8E8E93;">© 2026 SIVISIT — CareVisit Monitor. Informatika Kesehatan.</span>
                <span style="font-size:11px;color:#8E8E93;font-style:italic;">⚠️ Seluruh data bersifat simulasi/dummy. Bukan diagnosis medis.</span>
            </div>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Global search redirect to cari-pasien
    document.getElementById('globalSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim()) {
            window.location.href = 'cari-pasien.php?q=' + encodeURIComponent(this.value.trim());
        }
    });
</script>
</body>
</html>