<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

// Fetch all monitorings
$monitoringsRes = callAPI('GET', '/monitoring');
$monitorings    = ($monitoringsRes['status_code'] === 200 && isset($monitoringsRes['response']['data']))
    ? $monitoringsRes['response']['data']
    : [];

// Sort by date desc
usort($monitorings, fn($a,$b) =>
    strtotime($b['monitoring_date'] ?? '') <=> strtotime($a['monitoring_date'] ?? '')
);

// Count by status
$countStable   = 0; $countControl = 0; $countReferral = 0;
foreach ($monitorings as $m) {
    $s = strtolower($m['status'] ?? '');
    if (str_contains($s,'stable') || str_contains($s,'stabil'))     $countStable++;
    elseif (str_contains($s,'referral') || str_contains($s,'rujukan')) $countReferral++;
    else                                                               $countControl++;
}

$user        = $_SESSION['user'] ?? [];
$userName    = htmlspecialchars($user['name']  ?? 'Petugas');
$userInitial = strtoupper(substr($user['name'] ?? 'P', 0, 1));
$userEmail   = htmlspecialchars($user['email'] ?? '');

function getStatusBadge($status) {
    $s = strtolower($status ?? '');
    if (str_contains($s,'stable') || str_contains($s,'stabil'))
        return '<span class="sv-badge sv-badge-stable">✅ Stabil</span>';
    if (str_contains($s,'referral') || str_contains($s,'rujukan'))
        return '<span class="sv-badge sv-badge-referral">🚨 Perlu Rujukan</span>';
    return '<span class="sv-badge sv-badge-control">⚠️ Perlu Kontrol</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Monitoring — SIVISIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="globals.css" rel="stylesheet">
    <link href="global.css" rel="stylesheet">
    <link href="table.css" rel="stylesheet">
    <style>
        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: 1.5px solid #D8DCE6;
            background: white;
            color: #636366;
            transition: all 0.2s;
        }
        .filter-tab:hover,
        .filter-tab.active {
            border-color: #007AFF;
            background: #007AFF;
            color: white;
        }
        .filter-tab.tab-stable.active  { background: #34C759; border-color: #34C759; }
        .filter-tab.tab-control.active { background: #FF9500; border-color: #FF9500; }
        .filter-tab.tab-referral.active{ background: #FF3B30; border-color: #FF3B30; }

        .vitals-cell { font-size: 13px; }
        .vitals-cell .vital-val { font-weight: 600; color: #1C1C1E; }
        .vitals-cell .vital-lbl { font-size: 10.5px; color: #8E8E93; }
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
                <input type="text" placeholder="Cari pasien..." id="globalSearch" autocomplete="off">
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
                    <h1>Data Monitoring Kesehatan</h1>
                    <p>Seluruh catatan monitoring pasien home care — diurutkan terbaru.</p>
                </div>
                <a href="tambah-monitoring.php" class="btn btn-primary">🩺 Catat Monitoring</a>
            </div>

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-4 sv-animate-in sv-animate-in-1">
                    <div class="sv-stat-card" style="--accent-color:#34C759;">
                        <div class="stat-icon">✅</div>
                        <div class="stat-label">Stabil</div>
                        <div class="stat-value" style="color:#34C759;"><?= $countStable ?></div>
                        <div class="stat-sub">Catatan monitoring</div>
                    </div>
                </div>
                <div class="col-4 sv-animate-in sv-animate-in-2">
                    <div class="sv-stat-card" style="--accent-color:#FF9500;">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-label">Perlu Kontrol</div>
                        <div class="stat-value" style="color:#FF9500;"><?= $countControl ?></div>
                        <div class="stat-sub">Butuh tindak lanjut</div>
                    </div>
                </div>
                <div class="col-4 sv-animate-in sv-animate-in-3">
                    <div class="sv-stat-card" style="--accent-color:#FF3B30;">
                        <div class="stat-icon">🚨</div>
                        <div class="stat-label">Perlu Rujukan</div>
                        <div class="stat-value" style="color:#FF3B30;"><?= $countReferral ?></div>
                        <div class="stat-sub">Segera dirujuk</div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs + Search -->
            <div class="sv-card mb-3 sv-animate-in" style="padding:14px 16px;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="all">Semua (<?= count($monitorings) ?>)</button>
                        <button class="filter-tab tab-stable"   data-filter="stabil">✅ Stabil (<?= $countStable ?>)</button>
                        <button class="filter-tab tab-control"  data-filter="kontrol">⚠️ Perlu Kontrol (<?= $countControl ?>)</button>
                        <button class="filter-tab tab-referral" data-filter="rujukan">🚨 Perlu Rujukan (<?= $countReferral ?>)</button>
                    </div>
                    <div style="position:relative;">
                        <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#8E8E93;pointer-events:none;">🔍</span>
                        <input type="text" id="tableSearch"
                            placeholder="Cari nama pasien..."
                            style="padding:7px 12px 7px 32px;border:1.5px solid #D8DCE6;border-radius:8px;font-size:13px;font-family:inherit;outline:none;color:#1C1C1E;transition:all .2s;min-width:200px;"
                            onfocus="this.style.borderColor='#007AFF';this.style.boxShadow='0 0 0 3px rgba(0,122,255,.1)'"
                            onblur="this.style.borderColor='#D8DCE6';this.style.boxShadow=''">
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="sv-table-wrap sv-animate-in">
                <div class="sv-section-header">
                    <h5>📋 Riwayat Monitoring</h5>
                    <span style="font-size:12px;color:#8E8E93;" id="monCount"><?= count($monitorings) ?> catatan</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0" id="monTable">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Pasien</th>
                                <th>Tekanan Darah</th>
                                <th>Suhu (°C)</th>
                                <th>Keluhan</th>
                                <th>Rekomendasi / Catatan</th>
                                <th>Status</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody id="monBody">
                            <?php if (empty($monitorings)): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="sv-empty-state">
                                            <div class="empty-icon">🩺</div>
                                            <p>Belum ada catatan monitoring. <a href="tambah-monitoring.php">Catat monitoring pertama →</a></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($monitorings as $m):
                                    $statusRaw = strtolower($m['status'] ?? '');
                                    if (str_contains($statusRaw,'stable') || str_contains($statusRaw,'stabil')) $filterKey = 'stabil';
                                    elseif (str_contains($statusRaw,'referral') || str_contains($statusRaw,'rujukan')) $filterKey = 'rujukan';
                                    else $filterKey = 'kontrol';
                                    $patientName = htmlspecialchars($m['patient']['patient_name'] ?? '-');
                                    $patientId   = htmlspecialchars($m['patient']['patient_id']   ?? '-');
                                ?>
                                <tr data-status="<?= $filterKey ?>" data-name="<?= strtolower($m['patient']['patient_name'] ?? '') ?>">
                                    <td style="white-space:nowrap;">
                                        <div style="font-weight:600;font-size:13px;"><?= isset($m['monitoring_date']) ? date('d M Y', strtotime($m['monitoring_date'])) : '-' ?></div>
                                        <div style="font-size:11px;color:#8E8E93;"><?= isset($m['monitoring_time']) ? date('H:i', strtotime($m['monitoring_time'])) . ' WIB' : '' ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;font-size:13.5px;"><?= $patientName ?></div>
                                        <div style="font-size:11px;color:#007AFF;"><?= $patientId ?></div>
                                    </td>
                                    <td class="vitals-cell">
                                        <div class="vital-val"><?= htmlspecialchars($m['blood_pressure'] ?? '-') ?></div>
                                        <div class="vital-lbl">mmHg</div>
                                    </td>
                                    <td class="vitals-cell">
                                        <div class="vital-val"><?= htmlspecialchars($m['body_temperature'] ?? '-') ?></div>
                                        <div class="vital-lbl">°C</div>
                                    </td>
                                    <td style="font-size:13px;max-width:160px;white-space:normal;">
                                        <?= htmlspecialchars($m['symptoms'] ?? '-') ?>
                                    </td>
                                    <td style="font-size:13px;max-width:180px;white-space:normal;color:#636366;">
                                        <?= htmlspecialchars($m['notes'] ?? '-') ?>
                                    </td>
                                    <td><?= getStatusBadge($m['status'] ?? '') ?></td>
                                    <td style="font-size:12px;color:#636366;">
                                        <?= htmlspecialchars($m['user']['name'] ?? 'Petugas') ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const rows    = document.querySelectorAll('#monBody tr[data-status]');
    const monCount = document.getElementById('monCount');
    let activeFilter = 'all';

    function applyFilters() {
        const q = document.getElementById('tableSearch').value.toLowerCase();
        let visible = 0;
        rows.forEach(row => {
            const matchFilter = activeFilter === 'all' || row.dataset.status === activeFilter;
            const matchSearch = !q || row.dataset.name.includes(q);
            const show = matchFilter && matchSearch;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        monCount.textContent = visible + ' catatan';
    }

    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            activeFilter = this.dataset.filter;
            applyFilters();
        });
    });

    document.getElementById('tableSearch').addEventListener('input', applyFilters);

    document.getElementById('globalSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim())
            window.location.href = 'cari-pasien.php?q=' + encodeURIComponent(this.value.trim());
    });
</script>
</body>
</html>
