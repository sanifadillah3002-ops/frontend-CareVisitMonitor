<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

$patientsRes = callAPI('GET', '/patients');
$patients = ($patientsRes['status_code'] === 200 && isset($patientsRes['response']['data'])) ? $patientsRes['response']['data'] : [];

$monitoringsRes = callAPI('GET', '/monitorings');
$monitorings = ($monitoringsRes['status_code'] === 200 && isset($monitoringsRes['response']['data'])) ? $monitoringsRes['response']['data'] : [];

// Statistics
$totalPatients = count($patients);

$todayDate = date('Y-m-d');
$todayVisits = 0;
$todayFinished = 0;
$todayAgenda = [];

foreach ($monitorings as $m) {
    if ($m['monitoring_date'] === $todayDate) {
        $todayVisits++;
        if (($m['status'] ?? '') === 'Stable') {
            $todayFinished++;
        }
        $todayAgenda[] = $m;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CareVisitMonitor</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/css?family=Poppins:300,400,500,600,700" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #0d6efd;
            color: white;
        }
        .sidebar a {
            text-decoration: none;
        }
        .sidebar a.text-white:hover {
            color: white !important;
            background-color: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Bagian Sidebar Navigasi -->
        <div class="col-md-3 col-lg-2 sidebar p-3 d-flex flex-column">
            <h4 class="fw-bold text-center mb-4">CareVisit</h4>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item mb-2">
                    <!-- Dashboard Aktif (Warna Putih) -->
                    <a href="dashboard.php" class="nav-link active bg-white text-primary fw-medium">🏠 Dashboard</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="pasien.php" class="nav-link text-white px-3 py-2 d-block rounded">👥 Daftar Pasien</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="#" class="nav-link text-white px-3 py-2 d-block rounded opacity-50">📅 Jadwal Kunjungan</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="#" class="nav-link text-white px-3 py-2 d-block rounded opacity-50">📝 Rekam Medis</a>
                </li>
            </ul>
            <hr>
            <div>
                <a href="logout.php" class="btn btn-danger btn-sm w-100 fw-medium">🚪 Keluar</a>
            </div>
        </div>

        <!-- Bagian Konten Utama -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 fw-bold text-secondary">Selamat Datang, <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Perawat'); ?>!</h1>
                <div class="text-muted small">Hari ini: <?php echo date('d M Y'); ?></div>
            </div>

            <!-- Ringkasan Ringkas (Cards) -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-white p-3">
                        <div class="text-muted small fw-medium">Total Pasien Homecare</div>
                        <div class="fs-3 fw-bold text-primary"><?php echo $totalPatients; ?> Orang</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-white p-3">
                        <div class="text-muted small fw-medium">Kunjungan Hari Ini</div>
                        <div class="fs-3 fw-bold text-warning"><?php echo $todayVisits; ?> Pasien</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-white p-3">
                        <div class="text-muted small fw-medium">Tugas Selesai</div>
                        <div class="fs-3 fw-bold text-success"><?php echo $todayFinished; ?> Selesai</div>
                    </div>
                </div>
            </div>

            <!-- Tabel Jadwal Singkat -->
            <div class="card shadow-sm border-0 p-4">
                <h5 class="fw-bold mb-3 text-secondary">Agenda Kunjungan Rumah Hari Ini</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Jam</th>
                                <th>Nama Pasien</th>
                                <th>Alamat Rumah</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($todayAgenda)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Tidak ada agenda kunjungan hari ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($todayAgenda as $agenda): ?>
                                    <tr>
                                        <td><?php echo isset($agenda['monitoring_time']) ? date('H:i', strtotime($agenda['monitoring_time'])) : '--:--'; ?> WIB</td>
                                        <td class="fw-medium"><?php echo htmlspecialchars($agenda['patient']['patient_name'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($agenda['patient']['address'] ?? '-'); ?></td>
                                        <td>
                                            <?php if (($agenda['status'] ?? '') === 'Stable'): ?>
                                                <span class="badge bg-success">Selesai</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Tertunda / Belum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="pasien.php" class="btn btn-sm btn-outline-primary py-1">Detail</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>