<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

$user        = $_SESSION['user'] ?? [];
$userName    = htmlspecialchars($user['name']  ?? 'Ns. Petugas');
$userInitial = strtoupper(substr($user['name'] ?? 'P', 0, 1));
$userEmail   = htmlspecialchars($user['email'] ?? 'petugas@carevisit.dev');
$userRole    = htmlspecialchars($user['role']  ?? 'Petugas Kesehatan');
$userId      = $user['id'] ?? 1;

// Fetch stats from monitorings
$monitoringsRes = callAPI('GET', '/monitoring');
$allMonitorings = ($monitoringsRes['status_code'] === 200 && isset($monitoringsRes['response']['data']))
    ? $monitoringsRes['response']['data'] : [];

$patientsRes = callAPI('GET', '/pasien');
$patients    = ($patientsRes['status_code'] === 200 && isset($patientsRes['response']['data']))
    ? $patientsRes['response']['data'] : [];

$totalPatients   = count($patients);
$totalMonitoring = count($allMonitorings);

// Handle profile update (mock — update session)
$successMsg = '';
$errorMsg   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action']) && $_POST['_action'] === 'update_profile') {
    $newName  = trim($_POST['name'] ?? '');
    $newRole  = trim($_POST['role'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    if (empty($newName)) {
        $errorMsg = 'Nama tidak boleh kosong.';
    } else {
        $_SESSION['user']['name']  = $newName;
        $_SESSION['user']['role']  = $newRole;
        $_SESSION['user']['email'] = $newEmail;
        $successMsg = 'Profil berhasil diperbarui.';
        // Refresh variables
        $userName  = htmlspecialchars($newName);
        $userRole  = htmlspecialchars($newRole);
        $userEmail = htmlspecialchars($newEmail);
        $userInitial = strtoupper(substr($newName, 0, 1));
    }
}

// Mock profile fields
$nip      = $user['nip'] ?? '19' . date('Y') . '0' . str_pad($userId, 6, '0', STR_PAD_LEFT);
$phone    = $user['phone'] ?? '08' . rand(100000000, 999999999);
$location = $user['location'] ?? 'Jl. Medika No. 46, Jakarta Selatan';
$joined   = $user['joined'] ?? date('d M Y', strtotime('-' . rand(180, 730) . ' days'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Petugas — SIVISIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="globals.css" rel="stylesheet">
    <style>
        .profile-stat-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 20px;
        }
        .profile-stat-item {
            background: rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 14px 16px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .profile-stat-item .psi-val {
            font-size: 22px;
            font-weight: 800;
            color: white;
            line-height: 1;
        }
        .profile-stat-item .psi-lbl {
            font-size: 10.5px;
            font-weight: 500;
            color: rgba(255,255,255,0.55);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-top: 4px;
        }
        .edit-modal-btn {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: var(--sv-radius-sm);
            padding: 7px 16px;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--sv-transition);
        }
        .edit-modal-btn:hover {
            background: rgba(255,255,255,0.22);
        }
        .info-card {
            background: var(--sv-surface);
            border: 1px solid var(--sv-border);
            border-radius: var(--sv-radius);
            box-shadow: var(--sv-shadow-sm);
            overflow: hidden;
            margin-bottom: 16px;
        }
        .info-card-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--sv-border);
            background: #FAFBFC;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--sv-text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-card-body {
            padding: 8px 20px;
        }
        .badge-role {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            background: rgba(0,122,255,0.15);
            color: #4DA1FF;
            border: 1px solid rgba(0,122,255,0.25);
            margin-top: 4px;
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

            <!-- Page Header -->
            <div class="sv-page-header sv-animate-in">
                <div>
                    <h1>Profil Petugas</h1>
                    <p>Informasi akun dan data diri petugas kesehatan.</p>
                </div>
            </div>

            <?php if ($successMsg): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4 sv-animate-in" role="alert">
                <span>✅</span><span><?= htmlspecialchars($successMsg) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 sv-animate-in" role="alert">
                <span>⚠️</span><span><?= htmlspecialchars($errorMsg) ?></span>
            </div>
            <?php endif; ?>

            <div class="row g-3">

                <!-- Profile Hero Card -->
                <div class="col-12 col-lg-4 sv-animate-in sv-animate-in-1">
                    <div class="sv-profile-hero">
                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="sv-profile-avatar"><?= $userInitial ?></div>
                            <div class="sv-profile-info">
                                <div class="profile-name"><?= $userName ?></div>
                                <div class="badge-role">🏥 <?= $userRole ?></div>
                                <div class="profile-id">ID: PM-<?= date('Y') ?>-<?= str_pad($userId, 4, '0', STR_PAD_LEFT) ?></div>
                            </div>
                        </div>

                        <div class="profile-stat-row">
                            <div class="profile-stat-item">
                                <div class="psi-val"><?= $totalPatients ?></div>
                                <div class="psi-lbl">Pasien Binaan</div>
                            </div>
                            <div class="profile-stat-item">
                                <div class="psi-val"><?= $totalMonitoring ?></div>
                                <div class="psi-lbl">Total Kunjungan</div>
                            </div>
                        </div>

                        <div class="mt-4" style="position:relative;z-index:1;">
                            <button class="edit-modal-btn w-100" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                ✎ Edit Profil
                            </button>
                        </div>
                    </div>

                    <!-- Quick actions -->
                    <div class="sv-card">
                        <h6 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:var(--sv-text-muted);margin-bottom:14px;">Aksi Cepat</h6>
                        <div class="d-flex flex-column gap-2">
                            <a href="tambah-pasien.php" class="btn btn-sm btn-outline-primary">➕ Tambah Pasien Baru</a>
                            <a href="tambah-monitoring.php" class="btn btn-sm btn-outline-secondary">🩺 Catat Monitoring</a>
                            <a href="rekam-medis.php" class="btn btn-sm btn-outline-secondary">📂 Lihat Rekam Medis</a>
                            <a href="logout.php" class="btn btn-sm btn-outline-danger mt-2">🚪 Keluar</a>
                        </div>
                    </div>
                </div>

                <!-- Info Detail -->
                <div class="col-12 col-lg-8 sv-animate-in sv-animate-in-2">

                    <!-- Informasi Pribadi -->
                    <div class="info-card">
                        <div class="info-card-header">
                            👤 Informasi Pribadi
                        </div>
                        <div class="info-card-body">
                            <div class="sv-info-row">
                                <div class="info-icon" style="background:#E8F1FF;">📛</div>
                                <div class="info-content">
                                    <div class="info-label">Nama Lengkap</div>
                                    <div class="info-value"><?= $userName ?></div>
                                </div>
                            </div>
                            <div class="sv-info-row">
                                <div class="info-icon" style="background:#E8F8ED;">🏷️</div>
                                <div class="info-content">
                                    <div class="info-label">NIP / Kode Petugas</div>
                                    <div class="info-value" style="font-family:monospace;font-size:15px;"><?= $nip ?></div>
                                </div>
                            </div>
                            <div class="sv-info-row">
                                <div class="info-icon" style="background:#FFF4E5;">⚕️</div>
                                <div class="info-content">
                                    <div class="info-label">Jabatan / Role</div>
                                    <div class="info-value"><?= $userRole ?></div>
                                </div>
                            </div>
                            <div class="sv-info-row">
                                <div class="info-icon" style="background:#F5EEFF;">📅</div>
                                <div class="info-content">
                                    <div class="info-label">Bergabung Sejak</div>
                                    <div class="info-value"><?= $joined ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Kontak -->
                    <div class="info-card">
                        <div class="info-card-header">
                            📞 Informasi Kontak
                        </div>
                        <div class="info-card-body">
                            <div class="sv-info-row">
                                <div class="info-icon" style="background:#E8F1FF;">✉️</div>
                                <div class="info-content">
                                    <div class="info-label">Alamat Email</div>
                                    <div class="info-value"><?= $userEmail ?: '— Belum diset —' ?></div>
                                </div>
                            </div>
                            <div class="sv-info-row">
                                <div class="info-icon" style="background:#E8F8ED;">📱</div>
                                <div class="info-content">
                                    <div class="info-label">No. HP (Dummy)</div>
                                    <div class="info-value"><?= $phone ?></div>
                                </div>
                            </div>
                            <div class="sv-info-row">
                                <div class="info-icon" style="background:#FFF0EF;">📍</div>
                                <div class="info-content">
                                    <div class="info-label">Lokasi / Wilayah Tugas</div>
                                    <div class="info-value"><?= htmlspecialchars($location) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Disclaimer -->
                    <div class="sv-card" style="background:#FFFBEC;border-color:#FDEAB0;">
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <span style="font-size:20px;">⚠️</span>
                            <div>
                                <div style="font-weight:700;font-size:13px;color:#7A5500;margin-bottom:4px;">Data Simulasi Akademik</div>
                                <p style="font-size:12.5px;color:#8A6200;line-height:1.7;margin:0;">
                                    Seluruh data profil dan informasi pasien pada sistem ini bersifat simulasi/dummy
                                    untuk keperluan UAS Pemrograman Web — Informatika Kesehatan.
                                    Tidak ada data nyata pasien yang digunakan.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div><!-- /.sv-content -->

        <footer style="padding:20px 24px;border-top:1px solid #E8ECF0;background:#FAFBFC;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <span style="font-size:12px;color:#8E8E93;">Sivisit-Kelompok 9 S1 Informatika UAS Pemrograman WEB ITSK Rs Dr Soepraoen Malang</span>
                <span style="font-size:11px;color:#8E8E93;font-style:italic;">⚠️ Data simulasi/dummy. Bukan diagnosis medis. Rekomendasi hanya tindak lanjut administratif.</span>
            </div>
        </footer>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <form action="" method="POST">
                <input type="hidden" name="_action" value="update_profile">
                <div class="modal-header" style="border-bottom:1px solid #F0F2F5;padding:20px 24px;border-radius:16px 16px 0 0;">
                    <h5 class="modal-title" style="font-size:16px;font-weight:700;">✎ Edit Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:24px;">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span style="color:#FF3B30;">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                               placeholder="Nama petugas" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jabatan / Role</label>
                        <input type="text" name="role" class="form-control"
                               value="<?= htmlspecialchars($user['role'] ?? 'Petugas Kesehatan') ?>"
                               placeholder="Contoh: Perawat, Bidan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                               placeholder="email@example.com">
                    </div>
                    <div class="p-3 rounded" style="background:#F8F9FA;font-size:12px;color:#636366;">
                        ℹ️ Perubahan hanya berlaku pada sesi ini (data disimpan di session mock).
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #F0F2F5;padding:16px 24px;border-radius:0 0 16px 16px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">💾 Simpan Perubahan</button>
                </div>
            </form>
        </div>
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
