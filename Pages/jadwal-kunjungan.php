<?php
require_once '../config.php';
require_once 'components/sf-icons.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

// ── Fetch Schedules ──────────────────────────────
$jadwalsRes = callAPI('GET', '/jadwal');
$jadwals = ($jadwalsRes['status_code'] === 200 && isset($jadwalsRes['response']['data'])) ? $jadwalsRes['response']['data'] : [];

// ── AJAX / POST handler ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $payload = [
            'patient_id' => trim($_POST['patient_id'] ?? ''),
            'tanggal'    => trim($_POST['tanggal']    ?? date('Y-m-d')),
            'jam'        => trim($_POST['jam']         ?? '08:00'),
            'durasi'     => (int)($_POST['durasi']     ?? 60),
            'tujuan'     => trim($_POST['tujuan']      ?? ''),
            'status'     => 'scheduled',
            'catatan'    => trim($_POST['catatan']     ?? ''),
            'user_id'    => $_SESSION['user']['id'] ?? null,
        ];
        
        $res = callAPI('POST', '/jadwal', $payload);
        header('Content-Type: application/json');
        echo json_encode(['success' => $res['status_code'] === 201, 'data' => $res['response']['data'] ?? null]);
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $payload = [
            'patient_id' => trim($_POST['patient_id'] ?? ''),
            'tanggal'    => trim($_POST['tanggal']    ?? ''),
            'jam'        => trim($_POST['jam']         ?? ''),
            'durasi'     => (int)($_POST['durasi']     ?? 60),
            'tujuan'     => trim($_POST['tujuan']      ?? ''),
            'status'     => trim($_POST['status']      ?? 'scheduled'),
            'catatan'    => trim($_POST['catatan']     ?? ''),
        ];
        $payload = array_filter($payload, fn($val) => $val !== '');
        
        $res = callAPI('PUT', '/jadwal/' . $id, $payload);
        header('Content-Type: application/json');
        echo json_encode(['success' => $res['status_code'] === 200, 'data' => $res['response']['data'] ?? null]);
        exit;
    }

    if ($action === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $res = callAPI('DELETE', '/jadwal/' . $id);
        header('Content-Type: application/json');
        echo json_encode(['success' => $res['status_code'] === 200]);
        exit;
    }

    if ($action === 'selesai') {
        $id = (int)($_POST['id'] ?? 0);
        $res = callAPI('PUT', '/jadwal/' . $id, ['status' => 'done']);
        header('Content-Type: application/json');
        echo json_encode(['success' => $res['status_code'] === 200, 'data' => $res['response']['data'] ?? null]);
        exit;
    }
}

// ── Fetch patients for dropdown ────────────────────────────────
$patientsRes = callAPI('GET', '/pasien');
$patients    = ($patientsRes['status_code'] === 200 && isset($patientsRes['response']['data']))
    ? $patientsRes['response']['data'] : [];

// Build patient map
$patientMap = [];
foreach ($patients as $p) {
    $patientMap[$p['patient_id']] = $p;
}

// ── Compute stats ──────────────────────────────────────────────
$today        = date('Y-m-d');
$jadwals      = $_SESSION['jadwal_kunjungan'];
$todayJadwals = array_filter($jadwals, fn($j) => $j['tanggal'] === $today);
$totalToday   = count($todayJadwals);
$doneToday    = count(array_filter($todayJadwals, fn($j) => $j['status'] === 'done'));
$pending      = count(array_filter($jadwals, fn($j) => $j['status'] === 'scheduled' && $j['tanggal'] >= $today));
$weekStart    = date('Y-m-d', strtotime('monday this week'));
$weekEnd      = date('Y-m-d', strtotime('sunday this week'));

// ── Week view data ─────────────────────────────────────────────
$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $d = date('Y-m-d', strtotime($weekStart . " +$i days"));
    $dayJadwals = array_filter($jadwals, fn($j) => $j['tanggal'] === $d);
    usort($dayJadwals, fn($a, $b) => $a['jam'] <=> $b['jam']);
    $weekDays[] = ['date' => $d, 'jadwals' => array_values($dayJadwals)];
}

// ── User info ──────────────────────────────────────────────────
$user        = $_SESSION['user'] ?? [];
$userName    = htmlspecialchars($user['name']  ?? 'Petugas');
$userInitial = strtoupper(substr($user['name'] ?? 'P', 0, 1));
$userEmail   = htmlspecialchars($user['email'] ?? '');

// ── Upcoming: next 7 days all schedules sorted ─────────────────
$upcoming = array_filter($jadwals, fn($j) => $j['tanggal'] >= $today);
usort($upcoming, fn($a, $b) => ($a['tanggal'] . $a['jam']) <=> ($b['tanggal'] . $b['jam']));
$upcoming = array_values($upcoming);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Kunjungan — SIVISIT</title>
    <meta name="description" content="Kelola jadwal kunjungan petugas ke pasien home care secara terstruktur dan terorganisir.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="globals.css" rel="stylesheet">
    <style>
        /* ── Calendar Week View ── */
        .week-calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .week-day-col {
            background: var(--sv-surface);
            border-radius: 12px;
            border: 1.5px solid var(--sv-border);
            overflow: hidden;
            transition: var(--sv-transition);
            min-height: 160px;
        }

        .week-day-col.today-col {
            border-color: var(--sv-blue);
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
        }

        .week-day-header {
            padding: 10px 12px 8px;
            border-bottom: 1px solid var(--sv-border);
            text-align: center;
        }

        .week-day-col.today-col .week-day-header {
            background: var(--sv-blue);
        }

        .week-day-name {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--sv-text-muted);
        }

        .week-day-col.today-col .week-day-name { color: rgba(255,255,255,0.8); }

        .week-day-num {
            font-size: 20px;
            font-weight: 800;
            color: var(--sv-text-main);
            line-height: 1;
            margin-top: 2px;
        }

        .week-day-col.today-col .week-day-num { color: white; }

        .week-day-events {
            padding: 8px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .week-event {
            background: var(--sv-blue-light);
            border-left: 3px solid var(--sv-blue);
            border-radius: 0 6px 6px 0;
            padding: 5px 8px;
            cursor: pointer;
            transition: var(--sv-transition);
        }

        .week-event:hover {
            background: #D0E4FF;
            transform: translateX(2px);
        }

        .week-event.done {
            background: var(--sv-green-light);
            border-left-color: var(--sv-green);
            opacity: 0.75;
        }

        .week-event-time {
            font-size: 10px;
            font-weight: 700;
            color: var(--sv-blue);
        }

        .week-event.done .week-event-time { color: #1A7A35; }

        .week-event-name {
            font-size: 10.5px;
            font-weight: 600;
            color: var(--sv-navy);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 1px;
        }

        .week-day-empty {
            font-size: 11px;
            color: var(--sv-text-muted);
            text-align: center;
            padding: 16px 8px;
            font-style: italic;
        }

        /* ── Schedule List ── */
        .sched-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            border-bottom: 1px solid #F0F2F5;
            transition: var(--sv-transition);
            cursor: pointer;
        }

        .sched-item:last-child { border-bottom: none; }
        .sched-item:hover { background: #FAFBFC; }

        .sched-time-col {
            text-align: center;
            min-width: 54px;
            flex-shrink: 0;
        }

        .sched-time {
            font-size: 16px;
            font-weight: 800;
            color: var(--sv-navy);
            line-height: 1;
        }

        .sched-date-lbl {
            font-size: 10px;
            color: var(--sv-text-muted);
            margin-top: 2px;
        }

        .sched-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--sv-blue);
            flex-shrink: 0;
            box-shadow: 0 0 0 3px rgba(0,122,255,0.15);
        }

        .sched-dot.done { background: var(--sv-green); box-shadow: 0 0 0 3px rgba(52,199,89,0.15); }
        .sched-dot.cancelled { background: var(--sv-red); box-shadow: 0 0 0 3px rgba(255,59,48,0.15); }

        .sched-info { flex: 1; min-width: 0; }

        .sched-patient {
            font-size: 14px;
            font-weight: 700;
            color: var(--sv-text-main);
        }

        .sched-tujuan {
            font-size: 12px;
            color: var(--sv-text-sub);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sched-meta {
            font-size: 11px;
            color: var(--sv-text-muted);
            margin-top: 3px;
        }

        /* ── Status Badge ── */
        .sched-badge-scheduled {
            background: var(--sv-blue-light);
            color: var(--sv-blue-dark);
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .sched-badge-done {
            background: var(--sv-green-light);
            color: #1A7A35;
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .sched-badge-cancelled {
            background: var(--sv-red-light);
            color: #C0291F;
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        /* ── Action menu ── */
        .sched-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        .sched-btn {
            width: 32px;
            height: 32px;
            border: 1.5px solid var(--sv-border);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--sv-transition);
            color: var(--sv-text-muted);
        }

        .sched-btn:hover.edit-btn { border-color: var(--sv-blue); color: var(--sv-blue); background: var(--sv-blue-light); }
        .sched-btn:hover.done-btn { border-color: var(--sv-green); color: #1A7A35; background: var(--sv-green-light); }
        .sched-btn:hover.del-btn  { border-color: var(--sv-red);  color: #C0291F; background: var(--sv-red-light); }

        /* ── Modal ── */
        .sv-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .sv-modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .sv-modal {
            background: white;
            border-radius: 20px;
            padding: 32px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.2);
            transform: translateY(20px) scale(0.97);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            max-height: 90vh;
            overflow-y: auto;
        }

        .sv-modal-overlay.open .sv-modal {
            transform: translateY(0) scale(1);
        }

        .sv-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .sv-modal-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--sv-navy);
        }

        .sv-modal-close {
            width: 32px;
            height: 32px;
            border: none;
            background: var(--sv-bg);
            border-radius: 50%;
            font-size: 16px;
            cursor: pointer;
            color: var(--sv-text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--sv-transition);
        }

        .sv-modal-close:hover { background: var(--sv-red-light); color: var(--sv-red); }

        /* ── Stat cards for schedule ── */
        .sched-stat {
            background: white;
            border-radius: 14px;
            border: 1.5px solid var(--sv-border);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: var(--sv-transition);
        }

        .sched-stat:hover { box-shadow: var(--sv-shadow); transform: translateY(-2px); }

        .sched-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 20px;
        }

        .sched-stat-val {
            font-size: 26px;
            font-weight: 800;
            color: var(--sv-navy);
            line-height: 1;
        }

        .sched-stat-lbl {
            font-size: 12px;
            color: var(--sv-text-muted);
            margin-top: 2px;
        }

        /* ── Tab filter ── */
        .sv-tabs {
            display: flex;
            gap: 4px;
            background: var(--sv-bg);
            padding: 4px;
            border-radius: 12px;
        }

        .sv-tab {
            padding: 8px 16px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 500;
            color: var(--sv-text-muted);
            cursor: pointer;
            border: none;
            background: transparent;
            transition: var(--sv-transition);
        }

        .sv-tab.active {
            background: white;
            color: var(--sv-blue);
            font-weight: 700;
            box-shadow: var(--sv-shadow-sm);
        }

        /* ── Confirmation ── */
        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(4px);
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
        }

        .confirm-overlay.open { opacity: 1; pointer-events: all; }

        .confirm-box {
            background: white;
            border-radius: 18px;
            padding: 28px 28px 24px;
            max-width: 380px;
            width: 90%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }

        .confirm-overlay.open .confirm-box { transform: scale(1); }

        .confirm-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .confirm-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--sv-navy);
            margin-bottom: 8px;
        }

        .confirm-msg {
            font-size: 13px;
            color: var(--sv-text-sub);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        /* ── Toast ── */
        .sv-toast {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: var(--sv-navy);
            color: white;
            padding: 12px 24px;
            border-radius: 40px;
            font-size: 13.5px;
            font-weight: 600;
            z-index: 9999;
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
            white-space: nowrap;
        }

        .sv-toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        .sv-toast.success { background: #1A7A35; }
        .sv-toast.error   { background: #C0291F; }

        /* ── Progress bar for today ── */
        .today-progress {
            height: 6px;
            background: var(--sv-border);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
        }

        .today-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--sv-blue), #34C759);
            border-radius: 3px;
            transition: width 0.6s ease;
        }

        @media (max-width: 768px) {
            .week-calendar { grid-template-columns: repeat(4, 1fr); }
            .week-day-col:nth-child(5),
            .week-day-col:nth-child(6),
            .week-day-col:nth-child(7) { display: none; }
        }

        @media (max-width: 576px) {
            .week-calendar { grid-template-columns: repeat(3, 1fr); }
            .week-day-col:nth-child(4) { display: none; }
            .sched-item { gap: 10px; padding: 12px 14px; }
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
                <input type="text" placeholder="Cari jadwal atau pasien..." id="globalSearch" autocomplete="off">
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
                    <h1>📅 Jadwal Kunjungan Pasien</h1>
                    <p>Rencanakan dan kelola jadwal kunjungan home care petugas secara terstruktur.</p>
                </div>
                <button class="btn btn-primary" id="btnTambahJadwal">
                    <?= sf_icon('plus-circle', 16) ?>
                    &nbsp;Tambah Jadwal
                </button>
            </div>

            <!-- Stats Row -->
            <div class="row g-3 mb-4 sv-animate-in sv-animate-in-1">
                <div class="col-sm-6 col-lg-3">
                    <div class="sched-stat">
                        <div class="sched-stat-icon" style="background:#E8F1FF;">📅</div>
                        <div>
                            <div class="sched-stat-val" id="statToday"><?= $totalToday ?></div>
                            <div class="sched-stat-lbl">Kunjungan Hari Ini</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="sched-stat">
                        <div class="sched-stat-icon" style="background:#E8F8ED;">✅</div>
                        <div>
                            <div class="sched-stat-val" id="statDone"><?= $doneToday ?></div>
                            <div class="sched-stat-lbl">Selesai Hari Ini</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="sched-stat">
                        <div class="sched-stat-icon" style="background:#FFF4E5;">⏳</div>
                        <div>
                            <div class="sched-stat-val" id="statPending"><?= $pending ?></div>
                            <div class="sched-stat-lbl">Belum Dikunjungi</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="sched-stat">
                        <div class="sched-stat-icon" style="background:#F5EEFF;">👥</div>
                        <div>
                            <div class="sched-stat-val"><?= count($patients) ?></div>
                            <div class="sched-stat-lbl">Total Pasien Binaan</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today Progress -->
            <?php if ($totalToday > 0): ?>
            <div class="sv-card mb-4 sv-animate-in sv-animate-in-2" style="padding:16px 20px;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span style="font-size:13px;font-weight:600;color:var(--sv-navy);">Progress Kunjungan Hari Ini</span>
                    <span style="font-size:12px;color:var(--sv-text-muted);"><?= $doneToday ?> / <?= $totalToday ?> selesai</span>
                </div>
                <div class="today-progress">
                    <div class="today-progress-fill" style="width:<?= $totalToday > 0 ? round($doneToday / $totalToday * 100) : 0 ?>%;"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Week Calendar -->
            <div class="sv-card mb-4 sv-animate-in sv-animate-in-2" style="padding:20px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 style="font-size:15px;font-weight:700;color:var(--sv-navy);margin:0;">
                            📆 Minggu Ini
                        </h5>
                        <span style="font-size:12px;color:var(--sv-text-muted);">
                            <?= date('d M', strtotime($weekStart)) ?> – <?= date('d M Y', strtotime($weekEnd)) ?>
                        </span>
                    </div>
                </div>
                <div class="week-calendar">
                    <?php
                    $dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
                    foreach ($weekDays as $idx => $wd):
                        $isToday = $wd['date'] === $today;
                    ?>
                    <div class="week-day-col <?= $isToday ? 'today-col' : '' ?>">
                        <div class="week-day-header">
                            <div class="week-day-name"><?= $dayNames[$idx] ?></div>
                            <div class="week-day-num"><?= date('j', strtotime($wd['date'])) ?></div>
                        </div>
                        <div class="week-day-events">
                            <?php if (empty($wd['jadwals'])): ?>
                                <div class="week-day-empty">–</div>
                            <?php else: ?>
                                <?php foreach ($wd['jadwals'] as $ev):
                                    $evPatient = $patientMap[$ev['patient_id']] ?? null;
                                    $evName = $evPatient ? (explode(' ', $evPatient['patient_name'])[0]) : $ev['patient_id'];
                                ?>
                                <div class="week-event <?= $ev['status'] === 'done' ? 'done' : '' ?>"
                                     onclick="openViewModal(<?= $ev['id'] ?>)"
                                     title="<?= htmlspecialchars($evPatient['patient_name'] ?? $ev['patient_id']) ?> — <?= htmlspecialchars($ev['tujuan']) ?>">
                                    <div class="week-event-time"><?= date('H:i', strtotime($ev['jam'])) ?></div>
                                    <div class="week-event-name"><?= htmlspecialchars($evName) ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filter Tabs + Upcoming Schedule List -->
            <div class="sv-table-wrap sv-animate-in sv-animate-in-3">
                <div class="sv-section-header">
                    <h5>📋 Daftar Jadwal Kunjungan</h5>
                    <div class="sv-tabs">
                        <button class="sv-tab active" id="tabAll" onclick="filterSched('all', this)">Semua</button>
                        <button class="sv-tab" id="tabToday" onclick="filterSched('today', this)">Hari Ini</button>
                        <button class="sv-tab" id="tabScheduled" onclick="filterSched('scheduled', this)">Terjadwal</button>
                        <button class="sv-tab" id="tabDone" onclick="filterSched('done', this)">Selesai</button>
                    </div>
                </div>

                <div id="schedList">
                    <?php
                    // Sort all jadwals: upcoming first, then past; within same day sort by time
                    $allSorted = $jadwals;
                    usort($allSorted, fn($a, $b) => ($a['tanggal'] . $a['jam']) <=> ($b['tanggal'] . $b['jam']));
                    ?>
                    <?php if (empty($allSorted)): ?>
                        <div class="sv-empty-state">
                            <div class="empty-icon">📅</div>
                            <p>Belum ada jadwal kunjungan. <button class="btn btn-link p-0" onclick="openAddModal()">Tambah jadwal sekarang →</button></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($allSorted as $j):
                            $jp = $patientMap[$j['patient_id']] ?? null;
                            $isPast = $j['tanggal'] < $today;
                            $isJadwalToday = $j['tanggal'] === $today;
                        ?>
                        <div class="sched-item"
                             data-id="<?= $j['id'] ?>"
                             data-status="<?= $j['status'] ?>"
                             data-date="<?= $j['tanggal'] ?>"
                             data-istoday="<?= $isJadwalToday ? '1' : '0' ?>">
                            <!-- Time -->
                            <div class="sched-time-col">
                                <div class="sched-time"><?= date('H:i', strtotime($j['jam'])) ?></div>
                                <div class="sched-date-lbl"><?= date('d M', strtotime($j['tanggal'])) ?></div>
                            </div>

                            <!-- Dot -->
                            <div class="sched-dot <?= $j['status'] ?>"></div>

                            <!-- Info -->
                            <div class="sched-info">
                                <div class="sched-patient">
                                    <?= htmlspecialchars($jp['patient_name'] ?? $j['patient_id']) ?>
                                </div>
                                <div class="sched-tujuan"><?= htmlspecialchars($j['tujuan']) ?></div>
                                <div class="sched-meta">
                                    <?= $j['durasi'] ?> menit
                                    <?php if ($jp): ?> · <?= htmlspecialchars($jp['patient_category']) ?><?php endif; ?>
                                    <?php if (!empty($j['catatan'])): ?> · <em><?= htmlspecialchars($j['catatan']) ?></em><?php endif; ?>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="d-none d-sm-block">
                                <?php if ($j['status'] === 'done'): ?>
                                    <span class="sched-badge-done">✅ Selesai</span>
                                <?php elseif ($j['status'] === 'cancelled'): ?>
                                    <span class="sched-badge-cancelled">❌ Dibatalkan</span>
                                <?php else: ?>
                                    <span class="sched-badge-scheduled">⏰ Terjadwal</span>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="sched-actions">
                                <?php if ($j['status'] === 'scheduled'): ?>
                                <button class="sched-btn done-btn"
                                        onclick="markDone(<?= $j['id'] ?>, event)"
                                        title="Tandai Selesai">
                                    <?= sf_icon('checkmark-circle', 16) ?>
                                </button>
                                <?php endif; ?>
                                <button class="sched-btn edit-btn"
                                        onclick="openEditModal(<?= $j['id'] ?>, event)"
                                        title="Edit Jadwal">
                                    <?= sf_icon('pencil', 16) ?>
                                </button>
                                <button class="sched-btn del-btn"
                                        onclick="confirmHapus(<?= $j['id'] ?>, event)"
                                        title="Hapus Jadwal">
                                    <?= sf_icon('trash', 16) ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.sv-content -->

        <footer style="padding:16px 24px;border-top:1px solid var(--sv-border);text-align:center;color:var(--sv-text-muted);font-size:12px;background:var(--sv-surface);">
            SIVISIT — Kelompok 9 S1 Informatika ITSK Rs. Dr. Soepraoen Malang &nbsp;·&nbsp; Data simulasi, bukan diagnosis medis.
        </footer>
    </div><!-- /.sv-main -->
</div><!-- /.sv-layout -->

<!-- ══════════════════════════════════════════════════════════
     MODAL: TAMBAH / EDIT JADWAL
══════════════════════════════════════════════════════════ -->
<div class="sv-modal-overlay" id="modalOverlay" onclick="closeModal(event)">
    <div class="sv-modal" onclick="event.stopPropagation()">
        <div class="sv-modal-header">
            <div class="sv-modal-title" id="modalTitle">Tambah Jadwal Kunjungan</div>
            <button class="sv-modal-close" onclick="closeModal()">✕</button>
        </div>

        <form id="jadwalForm">
            <input type="hidden" id="formAction" name="action" value="tambah">
            <input type="hidden" id="formId" name="id" value="">

            <div class="mb-3">
                <label class="form-label" for="formPatient">Pasien <span class="text-danger">*</span></label>
                <select class="form-select" id="formPatient" name="patient_id" required>
                    <option value="">— Pilih Pasien —</option>
                    <?php foreach ($patients as $p): ?>
                    <option value="<?= htmlspecialchars($p['patient_id']) ?>">
                        <?= htmlspecialchars($p['patient_name']) ?> (<?= htmlspecialchars($p['patient_id']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-7">
                    <label class="form-label" for="formTanggal">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="formTanggal" name="tanggal"
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-5">
                    <label class="form-label" for="formJam">Jam <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" id="formJam" name="jam" value="08:00" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="formDurasi">Durasi Kunjungan</label>
                <select class="form-select" id="formDurasi" name="durasi">
                    <option value="30">30 menit</option>
                    <option value="45">45 menit</option>
                    <option value="60" selected>60 menit (1 jam)</option>
                    <option value="90">90 menit (1,5 jam)</option>
                    <option value="120">120 menit (2 jam)</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label" for="formTujuan">Tujuan Kunjungan <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="formTujuan" name="tujuan"
                       placeholder="Contoh: Monitoring rutin tekanan darah" required>
            </div>

            <div class="mb-3" id="statusGroup" style="display:none;">
                <label class="form-label" for="formStatus">Status</label>
                <select class="form-select" id="formStatus" name="status">
                    <option value="scheduled">⏰ Terjadwal</option>
                    <option value="done">✅ Selesai</option>
                    <option value="cancelled">❌ Dibatalkan</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label" for="formCatatan">Catatan (opsional)</label>
                <textarea class="form-control" id="formCatatan" name="catatan" rows="2"
                          placeholder="Peralatan yang dibawa, instruksi khusus, dll."></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 py-2" id="submitBtn">
                    Simpan Jadwal
                </button>
                <button type="button" class="btn btn-outline-secondary px-4" onclick="closeModal()">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     CONFIRM DELETE
══════════════════════════════════════════════════════════ -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-icon">🗑️</div>
        <div class="confirm-title">Hapus Jadwal?</div>
        <div class="confirm-msg">Tindakan ini tidak dapat dibatalkan. Jadwal kunjungan akan dihapus permanen.</div>
        <div class="d-flex gap-2">
            <button class="btn btn-danger flex-grow-1" id="confirmDelBtn">Ya, Hapus</button>
            <button class="btn btn-outline-secondary flex-grow-1" onclick="closeConfirm()">Batal</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="sv-toast" id="toast"></div>

<!-- ════════ DATA EMBEDDED FOR JS ════════ -->
<script>
const PATIENTS = <?= json_encode(array_values($patientMap)) ?>;
const TODAY    = '<?= $today ?>';

// ─── All schedules stored in JS state ───────────────────────
let schedules = <?= json_encode(array_values($jadwals)) ?>;

// ─── Toast ──────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `sv-toast ${type} show`;
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 3000);
}

// ─── Modal open/close ────────────────────────────────────────
const overlay = document.getElementById('modalOverlay');

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Jadwal Kunjungan';
    document.getElementById('formAction').value = 'tambah';
    document.getElementById('formId').value = '';
    document.getElementById('jadwalForm').reset();
    document.getElementById('formTanggal').value = TODAY;
    document.getElementById('formJam').value = '08:00';
    document.getElementById('formDurasi').value = '60';
    document.getElementById('statusGroup').style.display = 'none';
    document.getElementById('submitBtn').textContent = 'Simpan Jadwal';
    overlay.classList.add('open');
}

function openEditModal(id, e) {
    if (e) e.stopPropagation();
    const j = schedules.find(s => s.id == id);
    if (!j) return;
    document.getElementById('modalTitle').textContent = 'Edit Jadwal Kunjungan';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = j.id;
    document.getElementById('formPatient').value = j.patient_id;
    document.getElementById('formTanggal').value = j.tanggal;
    document.getElementById('formJam').value = j.jam;
    document.getElementById('formDurasi').value = j.durasi;
    document.getElementById('formTujuan').value = j.tujuan;
    document.getElementById('formStatus').value = j.status;
    document.getElementById('formCatatan').value = j.catatan;
    document.getElementById('statusGroup').style.display = '';
    document.getElementById('submitBtn').textContent = 'Simpan Perubahan';
    overlay.classList.add('open');
}

function openViewModal(id) {
    openEditModal(id);
}

function closeModal(e) {
    if (e && e.target !== overlay) return;
    overlay.classList.remove('open');
}

// ─── Mark Done ───────────────────────────────────────────────
function markDone(id, e) {
    if (e) e.stopPropagation();
    const fd = new FormData();
    fd.append('action', 'selesai');
    fd.append('id', id);
    fetch('jadwal-kunjungan.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const idx = schedules.findIndex(s => s.id == id);
                if (idx !== -1) schedules[idx].status = 'done';
                reRenderList();
                updateWeekCalendar();
                updateStats();
                showToast('✅ Kunjungan ditandai selesai!');
            }
        });
}

// ─── Confirm Delete ──────────────────────────────────────────
let pendingDelId = null;
function confirmHapus(id, e) {
    if (e) e.stopPropagation();
    pendingDelId = id;
    document.getElementById('confirmOverlay').classList.add('open');
}
function closeConfirm() {
    document.getElementById('confirmOverlay').classList.remove('open');
    pendingDelId = null;
}
document.getElementById('confirmDelBtn').addEventListener('click', function () {
    if (!pendingDelId) return;
    const fd = new FormData();
    fd.append('action', 'hapus');
    fd.append('id', pendingDelId);
    fetch('jadwal-kunjungan.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                schedules = schedules.filter(s => s.id != pendingDelId);
                closeConfirm();
                reRenderList();
                updateWeekCalendar();
                updateStats();
                showToast('🗑️ Jadwal berhasil dihapus', 'error');
            }
        });
});

// ─── Form Submit (Add / Edit) ────────────────────────────────
document.getElementById('jadwalForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('jadwal-kunjungan.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const action = fd.get('action');
                if (action === 'tambah') {
                    schedules.push(res.data);
                    showToast('📅 Jadwal baru berhasil ditambahkan!');
                } else {
                    const idx = schedules.findIndex(s => s.id == res.data.id);
                    if (idx !== -1) schedules[idx] = res.data;
                    showToast('✏️ Jadwal berhasil diperbarui!');
                }
                overlay.classList.remove('open');
                reRenderList();
                updateWeekCalendar();
                updateStats();
            }
        });
});

// ─── Button Tambah ───────────────────────────────────────────
document.getElementById('btnTambahJadwal').addEventListener('click', openAddModal);

// ─── Filter Tabs ─────────────────────────────────────────────
let activeFilter = 'all';
function filterSched(filter, btn) {
    activeFilter = filter;
    document.querySelectorAll('.sv-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    applyFilter();
}

function applyFilter() {
    document.querySelectorAll('#schedList .sched-item').forEach(row => {
        const status   = row.dataset.status;
        const isToday  = row.dataset.istoday === '1';
        let show = true;
        if (activeFilter === 'today')     show = isToday;
        if (activeFilter === 'scheduled') show = status === 'scheduled';
        if (activeFilter === 'done')      show = status === 'done';
        row.style.display = show ? '' : 'none';
    });
}

// ─── Build Patient Name Map ───────────────────────────────────
const patientMap = {};
PATIENTS.forEach(p => { patientMap[p.patient_id] = p; });

function getPatientName(pid) {
    return patientMap[pid] ? patientMap[pid].patient_name : pid;
}
function getPatientCat(pid) {
    return patientMap[pid] ? patientMap[pid].patient_category : '';
}

// ─── Re-render list ──────────────────────────────────────────
function reRenderList() {
    const sorted = [...schedules].sort((a, b) =>
        (a.tanggal + a.jam).localeCompare(b.tanggal + b.jam)
    );
    const container = document.getElementById('schedList');

    if (sorted.length === 0) {
        container.innerHTML = `
            <div class="sv-empty-state">
                <div class="empty-icon">📅</div>
                <p>Belum ada jadwal kunjungan. <button class="btn btn-link p-0" onclick="openAddModal()">Tambah jadwal sekarang →</button></p>
            </div>`;
        return;
    }

    container.innerHTML = sorted.map(j => {
        const pName = getPatientName(j.patient_id);
        const pCat  = getPatientCat(j.patient_id);
        const isToday = j.tanggal === TODAY ? '1' : '0';

        const badgeHtml = j.status === 'done'
            ? `<span class="sched-badge-done">✅ Selesai</span>`
            : j.status === 'cancelled'
            ? `<span class="sched-badge-cancelled">❌ Dibatalkan</span>`
            : `<span class="sched-badge-scheduled">⏰ Terjadwal</span>`;

        const doneBtn = j.status === 'scheduled'
            ? `<button class="sched-btn done-btn" onclick="markDone(${j.id}, event)" title="Tandai Selesai">
                 <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
               </button>` : '';

        const timeStr = j.jam.slice(0, 5);
        const dateStr = new Date(j.tanggal + 'T00:00:00').toLocaleDateString('id-ID', { day:'numeric', month:'short' });
        const dur = j.durasi;
        const catat = j.catatan ? ` · <em>${escHtml(j.catatan)}</em>` : '';

        return `
        <div class="sched-item" data-id="${j.id}" data-status="${j.status}" data-date="${j.tanggal}" data-istoday="${isToday}">
            <div class="sched-time-col">
                <div class="sched-time">${timeStr}</div>
                <div class="sched-date-lbl">${dateStr}</div>
            </div>
            <div class="sched-dot ${j.status}"></div>
            <div class="sched-info">
                <div class="sched-patient">${escHtml(pName)}</div>
                <div class="sched-tujuan">${escHtml(j.tujuan)}</div>
                <div class="sched-meta">${dur} menit${pCat ? ' · ' + escHtml(pCat) : ''}${catat}</div>
            </div>
            <div class="d-none d-sm-block">${badgeHtml}</div>
            <div class="sched-actions">
                ${doneBtn}
                <button class="sched-btn edit-btn" onclick="openEditModal(${j.id}, event)" title="Edit Jadwal">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button class="sched-btn del-btn" onclick="confirmHapus(${j.id}, event)" title="Hapus Jadwal">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
            </div>
        </div>`;
    }).join('');

    applyFilter();
}

function escHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ─── Update weekly calendar ───────────────────────────────────
function updateWeekCalendar() {
    const dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    const cols     = document.querySelectorAll('.week-day-col');
    cols.forEach((col, idx) => {
        const dateAttr = col.querySelector('.week-day-num').parentElement.parentElement.__date;
        if (!dateAttr) return; // fallback, handled separately
    });

    // Rebuild week calendar events for each column
    const cols2 = document.querySelectorAll('.week-day-col');
    cols2.forEach((col, idx) => {
        const numEl = col.querySelector('.week-day-num');
        const dayNum = parseInt(numEl.textContent.trim());
        const evContainer = col.querySelector('.week-day-events');

        // Find the date for this column
        const weekDayDates = getWeekDates();
        const colDate = weekDayDates[idx];
        const dayScheds = schedules.filter(s => s.tanggal === colDate)
            .sort((a, b) => a.jam.localeCompare(b.jam));

        if (dayScheds.length === 0) {
            evContainer.innerHTML = `<div class="week-day-empty">–</div>`;
        } else {
            evContainer.innerHTML = dayScheds.map(ev => {
                const pName = getPatientName(ev.patient_id);
                const firstName = pName.split(' ').slice(-1)[0]; // last word
                return `<div class="week-event ${ev.status === 'done' ? 'done' : ''}"
                             onclick="openViewModal(${ev.id})"
                             title="${escHtml(pName)} — ${escHtml(ev.tujuan)}">
                    <div class="week-event-time">${ev.jam.slice(0,5)}</div>
                    <div class="week-event-name">${escHtml(firstName)}</div>
                </div>`;
            }).join('');
        }
    });
}

function getWeekDates() {
    // Build same 7 dates as PHP
    const dates = [];
    <?php foreach ($weekDays as $idx => $wd): ?>
    dates.push('<?= $wd['date'] ?>');
    <?php endforeach; ?>
    return dates;
}

// ─── Update stats ────────────────────────────────────────────
function updateStats() {
    const todayItems    = schedules.filter(s => s.tanggal === TODAY);
    const doneItems     = todayItems.filter(s => s.status === 'done');
    const pendingItems  = schedules.filter(s => s.status === 'scheduled' && s.tanggal >= TODAY);

    document.getElementById('statToday').textContent  = todayItems.length;
    document.getElementById('statDone').textContent   = doneItems.length;
    document.getElementById('statPending').textContent = pendingItems.length;

    // Update progress bar
    const progFill = document.querySelector('.today-progress-fill');
    const progLabel = document.querySelector('.today-progress')?.previousElementSibling?.querySelector('span:last-child');
    if (progFill && todayItems.length > 0) {
        const pct = Math.round(doneItems.length / todayItems.length * 100);
        progFill.style.width = pct + '%';
        if (progLabel) progLabel.textContent = `${doneItems.length} / ${todayItems.length} selesai`;
    }
}

// ─── Global Search ───────────────────────────────────────────
document.getElementById('globalSearch').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && this.value.trim()) {
        window.location.href = 'cari-pasien.php?q=' + encodeURIComponent(this.value.trim());
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
