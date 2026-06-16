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
            background-color: rgba(255,255,255,0.1);
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
                            <tr>
                                <td>1</td>
                                <td class="fw-medium">Bpk. Slamet Riyadi</td>
                                <td>65 Tahun</td>
                                <td><span class="badge bg-danger-subtle text-danger">Hipertensi</span></td>
                                <td>Jl. Merdeka No. 10, Malang</td>
                                <td>
                                    <button class="btn btn-sm btn-info text-white py-1 me-1">Lihat</button>
                                    <button class="btn btn-sm btn-warning text-white py-1">Edit</button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td class="fw-medium">Ibu Aminah</td>
                                <td>58 Tahun</td>
                                <td><span class="badge bg-warning-subtle text-warning">Diabetes Melitus</span></td>
                                <td>Perumahan Pakis, Gang 3</td>
                                <td>
                                    <button class="btn btn-sm btn-info text-white py-1 me-1">Lihat</button>
                                    <button class="btn btn-sm btn-warning text-white py-1">Edit</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>