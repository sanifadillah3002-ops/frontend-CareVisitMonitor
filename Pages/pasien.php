<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

$patientsRes = callAPI('GET', '/pasien');
$patients    = ($patientsRes['status_code'] === 200 && isset($patientsRes['response']['data'])) ? $patientsRes['response']['data'] : [];

function calculateAge($dob) {
    if (empty($dob)) return '-';
    $birthDate = new DateTime($dob);
    $today     = new DateTime();
    return $today->diff($birthDate)->y . ' Thn';
}

function getStatusBadge($status) {
    $s = strtolower($status ?? '');
    if (str_contains($s, 'stable') || str_contains($s, 'stabil')) {
        return '<span class="sv-badge sv-badge-stable">✅ Stabil</span>';
    } elseif (str_contains($s, 'referral') || str_contains($s, 'rujukan')) {
        return '<span class="sv-badge sv-badge-referral">🚨 Perlu Rujukan</span>';
    } elseif (str_contains($s, 'control') || str_contains($s, 'kontrol')) {
        return '<span class="sv-badge sv-badge-control">⚠️ Perlu Kontrol</span>';
    }
    return '<span class="sv-badge" style="background:#F2F4F7;color:#636366;">–</span>';
}

function getCategoryBadge($cat) {
    $badges = [
        'lansia'      => ['#FFF4E5','#8A4E00','🧓'],
        'hipertensi'  => ['#FFF0EF','#C0291F','❤️'],
        'diabetes'    => ['#F5EEFF','#7B35A0','🩸'],
        'pasca rawat' => ['#E8F1FF','#0058D0','🏥'],
        'lainnya'     => ['#F2F4F7','#636366','📋'],
    ];
    $key = strtolower($cat ?? '');
    foreach ($badges as $k => [$bg, $color, $icon]) {
        if (str_contains($key, $k)) {
            return "<span class='sv-badge' style='background:{$bg};color:{$color};'>{$icon} {$cat}</span>";
        }
    }
    return "<span class='sv-badge' style='background:#F2F4F7;color:#636366;'>{$cat}</span>";
}

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
    <title>Daftar Pasien — SIVISIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="globals.css" rel="stylesheet">
    <link href="global.css" rel="stylesheet">
    <link href="table.css" rel="stylesheet">
    <link href="modal.css" rel="stylesheet">
    <style>
        .patient-avatar {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background: #F2F4F7;
            flex-shrink: 0;
        }
        .search-filter-bar {
            background: white;
            border: 1px solid #D8DCE6;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-filter-bar input,
        .search-filter-bar select {
            border: 1.5px solid #D8DCE6;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13.5px;
            font-family: 'Inter', sans-serif;
            outline: none;
            color: #1C1C1E;
            background: #FAFBFC;
            transition: all 0.2s;
        }
        .search-filter-bar input:focus,
        .search-filter-bar select:focus {
            border-color: #007AFF;
            box-shadow: 0 0 0 3px rgba(0,122,255,0.1);
            background: white;
        }
        .search-filter-bar input { flex: 1; min-width: 200px; }

        /* Modal override */
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
        .modal-header { border-bottom: 1px solid #F0F2F5; padding: 20px 24px; border-radius: 16px 16px 0 0; }
        .modal-body { padding: 24px; }
        .modal-footer { border-top: 1px solid #F0F2F5; padding: 16px 24px; border-radius: 0 0 16px 16px; }

        .detail-row { display: flex; flex-direction: column; gap: 2px; padding: 10px 0; border-bottom: 1px solid #F2F4F7; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #8E8E93; }
        .detail-value { font-size: 14px; font-weight: 500; color: #1C1C1E; }

        .monitoring-mini-table th { font-size: 11px; }
        .monitoring-mini-table td { font-size: 12.5px; }
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
            <?php if (isset($_GET['success'])): ?>
                <?php if ($_GET['success'] === 'deleted'): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4 sv-animate-in" role="alert">
                        <span>🎉</span><span>Data pasien berhasil dihapus secara permanen.</span>
                    </div>
                <?php elseif ($_GET['success'] === 'updated'): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4 sv-animate-in" role="alert">
                        <span>🎉</span><span>Data pasien berhasil diperbarui.</span>
                    </div>
                <?php endif; ?>
            <?php elseif (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] === 'delete_failed'): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 sv-animate-in" role="alert">
                        <span>⚠️</span><span>Gagal menghapus data pasien. Silakan coba lagi.</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <!-- Page Header -->
            <div class="sv-page-header sv-animate-in">
                <div>
                    <h1>Daftar Pasien Binaan</h1>
                    <p>Total <strong><?= count($patients) ?></strong> pasien terdaftar dalam sistem.</p>
                </div>
                <a href="tambah-pasien.php" class="btn btn-primary">➕ Tambah Pasien Baru</a>
            </div>

            <!-- Search & Filter -->
            <div class="search-filter-bar sv-animate-in">
                <span style="font-size:13px;font-weight:600;color:#636366;white-space:nowrap;">Filter:</span>
                <input type="text" id="searchInput" placeholder="🔍 Cari nama, NIK, kode pasien...">
                <select id="categoryFilter">
                    <option value="">Semua Kategori</option>
                    <option value="lansia">🧓 Lansia</option>
                    <option value="hipertensi">❤️ Hipertensi</option>
                    <option value="diabetes">🩸 Diabetes</option>
                    <option value="pasca rawat">🏥 Pasca Rawat</option>
                    <option value="lainnya">📋 Lainnya</option>
                </select>
                <select id="statusFilter">
                    <option value="">Semua Status</option>
                    <option value="stabil">✅ Stabil</option>
                    <option value="kontrol">⚠️ Perlu Kontrol</option>
                    <option value="rujukan">🚨 Perlu Rujukan</option>
                </select>
            </div>

            <!-- Table -->
            <div class="sv-table-wrap sv-animate-in">
                <div class="sv-section-header">
                    <h5>📋 Semua Pasien</h5>
                    <span style="font-size:12px;color:#8E8E93;" id="rowCount"><?= count($patients) ?> pasien</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0" id="patientTable">
                        <thead>
                            <tr>
                                <th style="width:40px;">No</th>
                                <th>Pasien</th>
                                <th>Kode / NIK</th>
                                <th>Usia</th>
                                <th>Kategori</th>
                                <th>Status Terakhir</th>
                                <th>Kontak Keluarga</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="patientBody">
                            <?php if (empty($patients)): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="sv-empty-state">
                                            <div class="empty-icon">👥</div>
                                            <p>Belum ada data pasien. <a href="tambah-pasien.php">Tambah pasien pertama →</a></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($patients as $p):
                                    // Get latest monitoring status
                                    $latestStatus = '';
                                    if (!empty($p['monitorings'])) {
                                        $mons = $p['monitorings'];
                                        usort($mons, fn($a,$b) => strtotime($b['monitoring_date'] ?? '') <=> strtotime($a['monitoring_date'] ?? ''));
                                        $latestStatus = $mons[0]['status'] ?? '';
                                    }
                                    $gender = ($p['gender'] ?? '') === 'Male' ? '👨' : '👩';
                                ?>
                                <tr
                                    data-name="<?= strtolower($p['patient_name'] ?? '') ?>"
                                    data-nik="<?= $p['nik_dummy'] ?? '' ?>"
                                    data-id="<?= strtolower($p['patient_id'] ?? '') ?>"
                                    data-category="<?= strtolower($p['patient_category'] ?? '') ?>"
                                    data-status="<?= strtolower($latestStatus) ?>"
                                >
                                    <td style="color:#8E8E93;"><?= $no++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="patient-avatar"><?= $gender ?></div>
                                            <div>
                                                <div style="font-weight:600;font-size:13.5px;"><?= htmlspecialchars($p['patient_name'] ?? '-') ?></div>
                                                <div style="font-size:11px;color:#8E8E93;"><?= htmlspecialchars($p['address'] ?? '-') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size:12px;font-weight:600;color:#007AFF;"><?= htmlspecialchars($p['patient_id'] ?? '-') ?></div>
                                        <div style="font-size:11px;color:#8E8E93;">NIK: <?= htmlspecialchars($p['nik_dummy'] ?? '-') ?></div>
                                    </td>
                                    <td style="font-weight:500;"><?= calculateAge($p['datebirth'] ?? '') ?></td>
                                    <td><?= getCategoryBadge($p['patient_category'] ?? '-') ?></td>
                                    <td><?= getStatusBadge($latestStatus) ?></td>
                                    <td style="font-size:13px;color:#636366;"><?= htmlspecialchars($p['family_phone'] ?? '-') ?></td>
                                    <td style="text-align:right;">
                                        <button
                                            class="btn btn-sm btn-outline-primary"
                                            style="font-size:12px;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalPasien<?= htmlspecialchars($p['patient_id']) ?>"
                                        >Detail</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer style="padding:20px 24px;border-top:1px solid #E8ECF0;background:#FAFBFC;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <span style="font-size:12px;color:#8E8E93;">Sivisit-Kelompok 9 S1 Informatika UAS Pemrograman WEB ITSK Rs Dr Soepraoen Malang</span>
                <span style="font-size:11px;color:#8E8E93;font-style:italic;">⚠️ Data simulasi/dummy. Bukan diagnosis medis. Rekomendasi hanya tindak lanjut administratif.</span>
            </div>
        </footer>
    </div>
</div>

<!-- ── MODALS ── -->
<?php foreach ($patients as $p):
    $mons = $p['monitorings'] ?? [];
    if (!empty($mons)) {
        usort($mons, fn($a,$b) => strtotime($b['monitoring_date'] ?? '') <=> strtotime($a['monitoring_date'] ?? ''));
    }
    $latestStatus = $mons[0]['status'] ?? '';
?>
<div class="modal fade" id="modalPasien<?= htmlspecialchars($p['patient_id']) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="patient-avatar" style="width:44px;height:44px;font-size:22px;background:#E8F1FF;">
                        <?= ($p['gender'] ?? '') === 'Male' ? '👨' : '👩' ?>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" style="font-size:16px;font-weight:700;"><?= htmlspecialchars($p['patient_name'] ?? '') ?></h5>
                        <div style="font-size:12px;color:#8E8E93;"><?= htmlspecialchars($p['patient_id'] ?? '') ?> • <?= getCategoryBadge($p['patient_category'] ?? '') ?></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Patient Info Grid -->
                <h6 style="font-size:12px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#8E8E93;margin-bottom:12px;">INFORMASI PASIEN</h6>
                <div class="row g-0" style="border:1px solid #F0F2F5;border-radius:10px;overflow:hidden;margin-bottom:24px;">
                    <div class="col-6">
                        <div class="detail-row px-3">
                            <span class="detail-label">Kode Pasien / No. RM</span>
                            <span class="detail-value" style="color:#007AFF;"><?= htmlspecialchars($p['patient_id'] ?? '-') ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="detail-row px-3">
                            <span class="detail-label">NIK Dummy</span>
                            <span class="detail-value"><?= htmlspecialchars($p['nik_dummy'] ?? '-') ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="detail-row px-3">
                            <span class="detail-label">Jenis Kelamin</span>
                            <span class="detail-value"><?= ($p['gender'] ?? '') === 'Male' ? '👨 Laki-laki' : '👩 Perempuan' ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="detail-row px-3">
                            <span class="detail-label">Tanggal Lahir</span>
                            <span class="detail-value"><?= isset($p['datebirth']) ? date('d M Y', strtotime($p['datebirth'])) : '-' ?> (<?= calculateAge($p['datebirth'] ?? '') ?>)</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="detail-row px-3">
                            <span class="detail-label">Kategori Pasien</span>
                            <span class="detail-value"><?= htmlspecialchars($p['patient_category'] ?? '-') ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="detail-row px-3">
                            <span class="detail-label">No. HP Keluarga</span>
                            <span class="detail-value"><?= htmlspecialchars($p['family_phone'] ?? '-') ?></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="detail-row px-3">
                            <span class="detail-label">Alamat Lengkap</span>
                            <span class="detail-value"><?= htmlspecialchars($p['address'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Monitoring History -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 style="font-size:12px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#8E8E93;margin:0;">RIWAYAT MONITORING KESEHATAN</h6>
                    <a href="tambah-monitoring.php?patient_id=<?= urlencode($p['patient_id']) ?>" class="btn btn-sm btn-primary" style="font-size:12px;">🩺 Catat Monitoring</a>
                </div>

                <?php if (empty($mons)): ?>
                    <div class="sv-empty-state" style="padding:24px;">
                        <div class="empty-icon">🩺</div>
                        <p>Belum ada catatan monitoring untuk pasien ini.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm monitoring-mini-table">
                            <thead style="background:#F8F9FA;">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tensi Darah</th>
                                    <th>Suhu (°C)</th>
                                    <th>Keluhan</th>
                                    <th>Rekomendasi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mons as $mon): ?>
                                <tr>
                                    <td style="white-space:nowrap;">
                                        <?= isset($mon['monitoring_date']) ? date('d M Y', strtotime($mon['monitoring_date'])) : '-' ?>
                                        <div style="font-size:10px;color:#8E8E93;">
                                            <?= isset($mon['monitoring_time']) ? date('H:i', strtotime($mon['monitoring_time'])) . ' WIB' : '' ?>
                                        </div>
                                    </td>
                                    <td style="font-weight:600;"><?= htmlspecialchars($mon['blood_pressure'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($mon['body_temperature'] ?? '-') ?></td>
                                    <td style="max-width:150px;white-space:normal;"><?= htmlspecialchars($mon['symptoms'] ?? '-') ?></td>
                                    <td style="max-width:150px;white-space:normal;"><?= htmlspecialchars($mon['notes'] ?? '-') ?></td>
                                    <td><?= getStatusBadge($mon['status'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="tambah-monitoring.php?patient_id=<?= urlencode($p['patient_id']) ?>" class="btn btn-sm btn-primary">🩺 Catat Monitoring Baru</a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Live search & filter
    const searchInput    = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter   = document.getElementById('statusFilter');
    const rows           = document.querySelectorAll('#patientBody tr[data-name]');
    const rowCount       = document.getElementById('rowCount');

    function filterTable() {
        const q   = searchInput.value.toLowerCase();
        const cat = categoryFilter.value.toLowerCase();
        const sts = statusFilter.value.toLowerCase();
        let visible = 0;

        rows.forEach(row => {
            const name     = row.dataset.name || '';
            const nik      = row.dataset.nik  || '';
            const id       = row.dataset.id   || '';
            const category = row.dataset.category || '';
            const status   = row.dataset.status   || '';

            const matchQ   = !q   || name.includes(q) || nik.includes(q) || id.includes(q);
            const matchCat = !cat || category.includes(cat);
            const matchSts = !sts || status.includes(sts);

            const show = matchQ && matchCat && matchSts;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        rowCount.textContent = visible + ' pasien';
    }

    searchInput.addEventListener('input', filterTable);
    categoryFilter.addEventListener('change', filterTable);
    statusFilter.addEventListener('change', filterTable);

    // Global search redirect
    document.getElementById('globalSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim()) {
            window.location.href = 'cari-pasien.php?q=' + encodeURIComponent(this.value.trim());
        }
    });
</script>
</body>
</html>
