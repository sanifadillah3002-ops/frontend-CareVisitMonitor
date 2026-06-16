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
                    <a href="jadwal.php" class="nav-link text-white px-3 py-2 d-block rounded">📅 Jadwal Kunjungan</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="rekam-medis.php" class="nav-link text-white px-3 py-2 d-block rounded">📝 Rekam Medis</a>
                </li>
            </ul>
            <hr>
            <div>
                <a href="login.php" class="btn btn-danger btn-sm w-100 fw-medium">🚪 Keluar</a>
            </div>
        </div>

        <!-- Bagian Konten Utama -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 fw-bold text-secondary">Selamat Datang, Perawat!</h1>
                <div class="text-muted small">Hari ini: <?php echo date('d M Y'); ?></div>
            </div>

            <!-- Ringkasan Ringkas (Cards) -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-white p-3">
                        <div class="text-muted small fw-medium">Total Pasien Homecare</div>
                        <div class="fs-3 fw-bold text-primary">12 Orang</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-white p-3">
                        <div class="text-muted small fw-medium">Kunjungan Hari Ini</div>
                        <div class="fs-3 fw-bold text-warning">3 Pasien</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-white p-3">
                        <div class="text-muted small fw-medium">Tugas Selesai</div>
                        <div class="fs-3 fw-bold text-success">8 Selesai</div>
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
                            <tr>
                                <td>08:00 WIB</td>
                                <td class="fw-medium">Bpk. Slamet</td>
                                <td>Jl. Merdeka No. 10, Malang</td>
                                <td><span class="badge bg-success">Selesai</span></td>
                                <td><button class="btn btn-sm btn-outline-primary py-1">Detail</button></td>
                            </tr>
                            <tr>
                                <td>10:30 WIB</td>
                                <td class="fw-medium">Ibu Aminah</td>
                                <td>Perumahan Pakis, Gang 3</td>
                                <td><span class="badge bg-warning text-dark">Tertunda</span></td>
                                <td><button class="btn btn-sm btn-outline-primary py-1">Detail</button></td>
                            </tr>
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