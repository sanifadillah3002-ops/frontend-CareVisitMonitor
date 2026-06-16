<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

$patientsRes = callAPI('GET', '/patients');
$patients = ($patientsRes['status_code'] === 200 && isset($patientsRes['response']['data'])) ? $patientsRes['response']['data'] : [];

function calculateAge($dob) {
    if (empty($dob)) return '-';
    $birthDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    return $age . ' Tahun';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pasien - CareVisitMonitor</title>
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
            background-color: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar p-3 d-flex flex-column">
                <h4 class="fw-bold text-center mb-4">CareVisit</h4>
                <hr>
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item mb-2">
                        <a href="dashboard.php" class="nav-link text-white px-3 py-2 d-block rounded">🏠 Dashboard</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="pasien.php" class="nav-link active bg-white text-primary fw-medium">👥 Daftar Pasien</a>
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

            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 fw-bold text-secondary">Kelola Data Pasien</h1>
                    <a href="tambah-pasien.php" class="btn btn-primary btn-sm fw-medium">+ Tambah Pasien Baru</a>
                </div>

                <div class="card shadow-sm border-0 p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pasien</th>
                                    <th>Usia</th>
                                    <th>Diagnosa Medis</th>
                                    <th>Alamat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($patients)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Tidak ada data pasien.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($patients as $p): ?>
                                        <?php
                                        $diagnosa = '-';
                                        if (!empty($p['monitorings'])) {
                                            $monitoringsList = $p['monitorings'];
                                            usort($monitoringsList, function($a, $b) {
                                                $dateA = strtotime($a['created_at'] ?? $a['monitoring_date'] ?? '');
                                                $dateB = strtotime($b['created_at'] ?? $b['monitoring_date'] ?? '');
                                                return $dateB <=> $dateA;
                                            });
                                            $diagnosa = $monitoringsList[0]['symptoms'] ?? '-';
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td class="fw-medium"><?php echo htmlspecialchars($p['patient_name'] ?? '-'); ?></td>
                                            <td><?php echo calculateAge($p['datebirth'] ?? ''); ?></td>
                                            <td>
                                                <?php if ($diagnosa !== '-' && !empty($diagnosa)): ?>
                                                    <span class="badge bg-danger-subtle text-danger"><?php echo htmlspecialchars($diagnosa); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($p['address'] ?? '-'); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info text-white py-1 me-1" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo htmlspecialchars($p['patient_id']); ?>">Lihat</button>
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

    <!-- Modals for details -->
    <?php foreach ($patients as $p): ?>
        <div class="modal fade" id="viewModal<?php echo htmlspecialchars($p['patient_id']); ?>" tabindex="-1" aria-labelledby="viewModalLabel<?php echo htmlspecialchars($p['patient_id']); ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="viewModalLabel<?php echo htmlspecialchars($p['patient_id']); ?>">Detail Pasien: <?php echo htmlspecialchars($p['patient_name'] ?? ''); ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <span class="text-muted small d-block">ID Pasien / No. RM</span>
                                <strong class="fs-6"><?php echo htmlspecialchars($p['patient_id'] ?? '-'); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">NIK Pasien</span>
                                <strong class="fs-6"><?php echo htmlspecialchars($p['nik_dummy'] ?? '-'); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Kategori & Gender</span>
                                <strong class="fs-6"><?php echo htmlspecialchars($p['patient_category'] ?? '-'); ?> (<?php echo htmlspecialchars($p['gender'] ?? '-'); ?>)</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Tanggal Lahir</span>
                                <strong class="fs-6"><?php echo isset($p['datebirth']) ? date('d M Y', strtotime($p['datebirth'])) : '-'; ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Alamat Lengkap</span>
                                <strong class="fs-6"><?php echo htmlspecialchars($p['address'] ?? '-'); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Nomor Telepon Darurat</span>
                                <strong class="fs-6"><?php echo htmlspecialchars($p['family_phone'] ?? '-'); ?></strong>
                            </div>
                        </div>

                        <hr>

                        <h6 class="fw-bold text-primary mb-3">🩺 Riwayat Monitoring Kesehatan</h6>
                        <?php if (empty($p['monitorings'])): ?>
                            <div class="alert alert-light text-muted small mb-0">Belum ada catatan monitoring kesehatan untuk pasien ini.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle text-center small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal & Jam</th>
                                            <th>Tensi</th>
                                            <th>Nadi</th>
                                            <th>Nafas</th>
                                            <th>Suhu</th>
                                            <th>Saturasi O2</th>
                                            <th>Gejala/Kondisi</th>
                                            <th>Catatan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($p['monitorings'] as $mon): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    echo isset($mon['monitoring_date']) ? date('d-m-Y', strtotime($mon['monitoring_date'])) : '';
                                                    echo isset($mon['monitoring_time']) ? ' ' . date('H:i', strtotime($mon['monitoring_time'])) : '';
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($mon['blood_pressure'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($mon['heart_rate'] ?? '-'); ?> bpm</td>
                                                <td><?php echo htmlspecialchars($mon['respiratory_rate'] ?? '-'); ?> x/m</td>
                                                <td><?php echo htmlspecialchars($mon['body_temperature'] ?? '-'); ?> °C</td>
                                                <td><?php echo htmlspecialchars($mon['oxygen_saturation'] ?? '-'); ?>%</td>
                                                <td><?php echo htmlspecialchars($mon['symptoms'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($mon['notes'] ?? '-'); ?></td>
                                                <td>
                                                    <span class="badge <?php echo ($mon['status'] === 'Stable') ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo htmlspecialchars($mon['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>