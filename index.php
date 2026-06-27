<?php
require_once 'config.php';
require_once 'Pages/components/sf-icons.php';

$query = trim($_GET['q'] ?? '');
$result = null;
$error = '';

if (!empty($query)) {
    $apiResult = callAPI('GET', '/pasien/' . urlencode($query) . '/monitoring');
    if ($apiResult['status_code'] === 200 && isset($apiResult['response']['data'])) {
        $result = $apiResult['response']['data'];
    } elseif ($apiResult['status_code'] === 404) {
        $error = 'Pasien dengan kode/NIK "<strong>' . htmlspecialchars($query) . '</strong>" tidak ditemukan.';
    } else {
        $error = $apiResult['response']['message'] ?? 'Terjadi kesalahan teknis saat mencari data.';
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIVISIT — Monitoring Pasien Home Care</title>
    <meta name="description" content="Sistem monitoring pasien home care terpadu.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="Pages/public-nav.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --blue: #007AFF;
            --blue-dark: #0055CC;
            --blue-light: #E8F1FF;
            --navy: #0A1628;
            --text: #1C1C1E;
            --text-secondary: #636366;
            --text-muted: #8E8E93;
            --border: #D8DCE6;
            --bg: #F5F7FA;
            --surface: #FFFFFF;
            --radius: 12px;
            --shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding-top: 64px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        a { text-decoration: none; color: var(--blue); }
        a:hover { color: var(--blue-dark); }

        .hero {
            text-align: center;
            padding: 64px 24px 48px;
            max-width: 720px;
            margin: 0 auto;
        }
        .hero h1 {
            font-size: 40px;
            font-weight: 800;
            letter-spacing: -1.2px;
            color: var(--navy);
            margin-bottom: 14px;
            line-height: 1.15;
        }
        .hero h1 span { color: var(--blue); }
        .hero p {
            font-size: 16px;
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 28px;
        }




        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
            width: 100%;
        }



        .card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow);
        }



        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-stable { background: #E8F5E9; color: #1B7A2E; }
        .badge-control { background: #FFF8E1; color: #9A6B00; }
        .badge-referral { background: #FFEBEE; color: #B71C1C; }
        .badge-none { background: #F2F4F7; color: var(--text-muted); }
        .badge-lansia { background: #FFF4E5; color: #8A4E00; }
        .badge-hipertensi { background: #FFEBEE; color: #C0291F; }
        .badge-diabetes { background: #F5EEFF; color: #7B35A0; }
        .badge-rawat { background: #E8F1FF; color: #0058D0; }
        .badge-lain { background: #F2F4F7; color: var(--text-secondary); }

        .detail-btn {
            padding: 6px 14px;
            border: 1.5px solid var(--blue);
            border-radius: 8px;
            background: transparent;
            color: var(--blue);
            font-size: 12px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .detail-btn:hover {
            background: var(--blue);
            color: #fff;
        }



        .footer {
            margin-top: auto;
            background: var(--navy);
            color: rgba(255,255,255,0.5);
            padding: 28px 24px;
            text-align: center;
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 28px; }
            .hero { padding: 40px 20px 32px; }
            .hero::before { display: none; }
        }

        .kontak-section {
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 60px 24px;
            position: relative;
            z-index: 1;
        }
        .kontak-container {
            max-width: 880px;
            margin: 0 auto;
            text-align: center;
        }
        .kontak-container h2 {
            font-size: 26px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.8px;
            margin-bottom: 12px;
        }
        .kontak-container > p {
            font-size: 15px;
            color: var(--text-secondary);
            margin-bottom: 36px;
        }
        .kontak-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .kontak-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px 16px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .kontak-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }
        .kontak-icon {
            width: 48px; height: 48px;
            background: var(--blue-light);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
        }
        .kontak-card h4 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
        }
        .kontak-card p {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        @media (max-width: 768px) {
            .kontak-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .kontak-grid { grid-template-columns: 1fr; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.12; }
            50% { transform: translateY(-30px) scale(1.05); opacity: 0.22; }
        }
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(80px);
        }
        .bg-orb-1 {
            width: 500px; height: 500px;
            background: rgba(0,122,255,0.1);
            top: -100px; right: -100px;
            animation: float 8s ease-in-out infinite;
        }
        .bg-orb-2 {
            width: 400px; height: 400px;
            background: rgba(0,122,255,0.07);
            bottom: -80px; left: -80px;
            animation: float 10s ease-in-out infinite reverse;
        }
        .bg-orb-3 {
            width: 300px; height: 300px;
            background: rgba(52,199,89,0.05);
            top: 50%; left: 50%;
            animation: float 12s ease-in-out infinite;
        }
        .hero, .container, .footer { position: relative; z-index: 1; }
        .hero::before {
            content: '';
            position: absolute;
            inset: -40px;
            background-image: url('https://images.unsplash.com/photo-1551076805-e1869033e561?w=1200&h=600&fit=crop');
            background-size: cover;
            background-position: center 30%;
            opacity: 0.06;
            z-index: -1;
            border-radius: 24px;
            pointer-events: none;
        }
        @media (max-width: 600px) {
            .hero::before { display: none; }
        }

        .desc-section {
            max-width: 880px;
            margin: 0 auto;
            padding: 0 24px 48px;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .desc-section h2 {
            font-size: 26px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.8px;
            margin-bottom: 16px;
        }
        .desc-section p {
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.8;
            max-width: 640px;
            margin: 0 auto;
        }
        .desc-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 32px;
        }
        .desc-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px 20px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .desc-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }
        .desc-card .card-img {
            width: 100%;
            height: 180px;
            background-size: cover;
            background-position: center;
            border-radius: 12px 12px 0 0;
            margin: -28px -20px 16px;
            width: calc(100% + 40px);
        }
        .desc-card .icon {
            width: 48px; height: 48px;
            background: var(--blue-light);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 22px;
        }
        .desc-card h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
        }
        .desc-card p {
            font-size: 12.5px;
            color: var(--text-secondary);
            line-height: 1.6;
            max-width: 100%;
        }
        @media (max-width: 768px) {
            .desc-grid { grid-template-columns: 1fr; }
            .desc-section h2 { font-size: 22px; }
        }

        .hero-search {
            max-width: 480px;
            margin: 20px auto 0;
            position: relative;
        }
        .hero-search .search-wrap {
            display: flex;
            gap: 0;
            position: relative;
        }
        .hero-search input {
            flex: 1;
            padding: 14px 20px 14px 48px;
            border: 1.5px solid var(--border);
            border-right: none;
            border-radius: 14px 0 0 14px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            outline: none;
            background: var(--surface);
            transition: all 0.2s;
        }
        .hero-search input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(0,122,255,0.12);
        }
        .hero-search .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            color: var(--text-muted);
            pointer-events: none;
            z-index: 1;
        }
        .hero-search .search-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 14px 22px;
            border: 1.5px solid var(--blue);
            border-radius: 0 14px 14px 0;
            background: var(--blue);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .hero-search .search-btn:hover {
            background: var(--blue-dark);
            border-color: var(--blue-dark);
        }

        /* ── Responsive Tables & Results ── */
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-wrap table { min-width: 500px; }

        .patient-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid #F0F2F5;
            border-radius: 10px;
            overflow: hidden;
        }
        .patient-grid .cell {
            padding: 12px 16px;
            border-bottom: 1px solid #F0F2F5;
        }
        .patient-grid .cell:nth-child(odd) { border-right: 1px solid #F0F2F5; }
        .patient-grid .cell:nth-last-child(2),
        .patient-grid .cell:nth-last-child(1) { border-bottom: none; }
        .patient-grid .cell .lbl {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            color: var(--text-muted); margin-bottom: 2px;
        }
        .patient-grid .cell .val {
            font-size: 13px; font-weight: 600; word-break: break-word;
        }

        @media (max-width: 600px) {
            .patient-grid { grid-template-columns: 1fr; }
            .patient-grid .cell:nth-child(odd) { border-right: none; }
            .patient-grid .cell { border-right: none !important; }
            .hero-search .search-btn {
                padding: 14px 16px;
                font-size: 13px;
            }
            .hero-search input {
                font-size: 14px;
                padding: 14px 14px 14px 44px;
            }
            .card { border-radius: 0; border-left: none; border-right: none; }
            .desc-card .card-img { height: 140px; }
            .bg-orb { display: none; }
        }

        .result-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #F0F2F5;
            flex-wrap: wrap;
            gap: 12px;
        }
    </style>
</head>
<body>

    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-orb bg-orb-3"></div>

<?php $navActive = 'beranda'; $navFromPages = false; include 'Pages/components/public-navbar.php'; ?>

<section class="hero">
    <h1>Monitoring Pasien <span>Home Care</span></h1>
    <p>Pantau kondisi pasien binaan secara terstruktur, transparan, dan real-time.</p>
    <form class="hero-search" method="GET" action="index.php">
        <div class="search-wrap">
            <span class="search-icon"><?= sf_icon('magnifyingglass', 18) ?></span>
            <input type="text" name="q" placeholder="Masukkan NIK atau kode pasien..." autocomplete="off" required>
            <button type="submit" class="search-btn"><?= sf_icon('magnifyingglass', 16) ?> Cari</button>
        </div>
    </form>
</section>

<?php if (!empty($query)): ?>
<main class="container" style="padding-bottom:48px;padding-top:20px;">
    <?php if (!empty($error)): ?>
        <div class="card" style="text-align:center;padding:48px 24px;">
            <div style="font-size:48px;margin-bottom:16px;opacity:0.4;"><?= sf_icon('magnifyingglass', 48) ?></div>
            <h3 style="color:var(--text);margin-bottom:8px;">Hasil Tidak Ditemukan</h3>
            <p style="color:var(--text-secondary);font-size:14px;"><?= $error ?></p>
            <a href="index.php" style="display:inline-flex;margin-top:20px;padding:10px 24px;border-radius:8px;background:var(--blue);color:#fff;font-size:13px;font-weight:600;text-decoration:none;">Kembali ke Beranda</a>
        </div>
    <?php else: ?>
        <div class="card" style="padding:24px;">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #F0F2F5;">
                <div style="width:54px;height:54px;border-radius:12px;background:var(--blue-light);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;">
                    <?= ($result['gender'] ?? '') === 'Male' ? '👨' : '👩' ?>
                </div>
                <div style="flex:1;">
                    <h3 style="font-size:18px;font-weight:700;color:var(--navy);margin:0;"><?= htmlspecialchars($result['patient_name'] ?? '-') ?></h3>
                    <p style="font-size:12px;color:var(--text-muted);margin:2px 0 0;"><?= htmlspecialchars($result['patient_id'] ?? '-') ?> &bull; <?= htmlspecialchars($result['patient_category'] ?? '-') ?></p>
                </div>
            </div>

            <div class="patient-grid">
                <div class="cell">
                    <div class="lbl">Kode Pasien</div>
                    <div class="val" style="color:var(--blue);"><?= htmlspecialchars($result['patient_id'] ?? '-') ?></div>
                </div>
                <div class="cell">
                    <div class="lbl">NIK</div>
                    <div class="val"><?= htmlspecialchars($result['nik_dummy'] ?? '-') ?></div>
                </div>
                <div class="cell">
                    <div class="lbl">Kategori</div>
                    <div class="val"><?= htmlspecialchars($result['patient_category'] ?? '-') ?></div>
                </div>
                <div class="cell">
                    <div class="lbl">Alamat</div>
                    <div class="val"><?= htmlspecialchars($result['address'] ?? '-') ?></div>
                </div>
                <div class="cell">
                    <div class="lbl">Usia</div>
                    <div class="val"><?= htmlspecialchars($result['datebirth'] ?? '-') ?></div>
                </div>
                <div class="cell">
                    <div class="lbl">Kontak Keluarga</div>
                    <div class="val"><?= htmlspecialchars($result['family_phone'] ?? '-') ?></div>
                </div>
            </div>

            <?php 
            $history = $result['monitorings'] ?? [];
            if (!empty($history)):
                usort($history, fn($a,$b) => strtotime($b['monitoring_date'] ?? '') <=> strtotime($a['monitoring_date'] ?? ''));
            ?>
                <div style="margin-top:24px;">
                    <h6 style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-muted);margin-bottom:12px;">Riwayat Monitoring</h6>
                    <div class="table-wrap">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="font-size:11px;font-weight:600;color:var(--text-muted);text-align:left;padding:8px 12px;border-bottom:1px solid #F0F2F5;background:#FAFBFC;">Tanggal</th>
                                <th style="font-size:11px;font-weight:600;color:var(--text-muted);text-align:left;padding:8px 12px;border-bottom:1px solid #F0F2F5;background:#FAFBFC;">Tensi</th>
                                <th style="font-size:11px;font-weight:600;color:var(--text-muted);text-align:left;padding:8px 12px;border-bottom:1px solid #F0F2F5;background:#FAFBFC;">Suhu</th>
                                <th style="font-size:11px;font-weight:600;color:var(--text-muted);text-align:left;padding:8px 12px;border-bottom:1px solid #F0F2F5;background:#FAFBFC;">Keluhan</th>
                                <th style="font-size:11px;font-weight:600;color:var(--text-muted);text-align:left;padding:8px 12px;border-bottom:1px solid #F0F2F5;background:#FAFBFC;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td style="font-size:12.5px;padding:10px 12px;border-bottom:1px solid #F0F2F5;white-space:nowrap;"><?= date('d M Y', strtotime($h['monitoring_date'])) ?></td>
                                <td style="font-size:12.5px;padding:10px 12px;border-bottom:1px solid #F0F2F5;font-weight:600;"><?= htmlspecialchars($h['blood_pressure'] ?? '-') ?></td>
                                <td style="font-size:12.5px;padding:10px 12px;border-bottom:1px solid #F0F2F5;"><?= htmlspecialchars($h['body_temperature'] ?? '-') ?>°C</td>
                                <td style="font-size:12.5px;padding:10px 12px;border-bottom:1px solid #F0F2F5;max-width:200px;"><?= htmlspecialchars($h['symptoms'] ?? '-') ?></td>
                                <td style="font-size:12.5px;padding:10px 12px;border-bottom:1px solid #F0F2F5;">
                                    <?php
                                    $s = strtolower($h['status'] ?? '');
                                    if (str_contains($s, 'stable')) echo '<span class="badge-stable">Stabil</span>';
                                    elseif (str_contains($s, 'referral')) echo '<span class="badge-referral">Perlu Rujukan</span>';
                                    elseif (str_contains($s, 'control')) echo '<span class="badge-control">Perlu Kontrol</span>';
                                    else echo '<span class="badge-none">—</span>';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align:center;padding:32px;color:var(--text-muted);font-size:14px;margin-top:16px;">Belum ada catatan monitoring.</div>
            <?php endif; ?>

            <div class="result-actions">
                <a href="Pages/jadwal.php" style="display:inline-flex;padding:8px 20px;border:1.5px solid var(--blue);border-radius:8px;background:transparent;color:var(--blue);font-size:12px;font-weight:600;text-decoration:none;">Lihat Detail Lengkap</a>
                <a href="index.php" style="font-size:13px;color:var(--text-muted);padding:8px 0;text-decoration:none;">Cari Ulang &rarr;</a>
            </div>
        </div>
    <?php endif; ?>
</main>
<?php endif; ?>

<section class="desc-section">
    <h2>Tentang SIVISIT CareVisit Monitor</h2>
    <p>Sistem monitoring pasien home care yang dikembangkan oleh Kelompok 9 S1 Informatika ITSK Rs Dr Soepraoen Malang untuk memudahkan pemantauan kondisi kesehatan pasien binaan secara transparan dan real-time.</p>
    <div class="desc-grid">
        <div class="desc-card">
            <div class="card-img" style="background-image:url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=400&h=250&fit=crop');"></div>
            <h4>Catat Monitoring</h4>
            <p>Petugas mencatat data tanda vital pasien seperti tensi, suhu, detak jantung, dan keluhan secara digital.</p>
        </div>
        <div class="desc-card">
            <div class="card-img" style="background-image:url('https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=400&h=250&fit=crop');"></div>
            <h4>Pantau Perkembangan</h4>
            <p>Lihat riwayat monitoring dan grafik tren kesehatan pasien dari waktu ke waktu.</p>
        </div>
        <div class="desc-card">
            <div class="card-img" style="background-image:url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=400&h=250&fit=crop');"></div>
            <h4>Akses Keluarga</h4>
            <p>Keluarga pasien dapat mencari dan memantau data monitoring melalui portal publik dengan kode pasien.</p>
        </div>
    </div>
</section>

<section class="kontak-section" id="kontak">
    <div class="kontak-container">
        <h2>Hubungi Kami</h2>
        <p>Jika ada pertanyaan atau informasi lebih lanjut, silakan hubungi tim pengembang.</p>
        <div class="kontak-grid">
            <div class="kontak-card">
                <div class="kontak-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#007AFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></div>
                <h4>Email</h4>
                <p>kelompok9.informatika@itss-soepraoen.ac.id</p>
            </div>
            <div class="kontak-card">
                <div class="kontak-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#007AFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                <h4>Telepon</h4>
                <p>+62 812-3456-7890</p>
            </div>
            <div class="kontak-card">
                <div class="kontak-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#007AFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></div>
                <h4>Alamat</h4>
                <p>ITSK Rs Dr Soepraoen, Jl. S. Supriadi No.22, Malang</p>
            </div>
            <div class="kontak-card">
                <div class="kontak-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#007AFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <h4>Jam Operasional</h4>
                <p>Senin - Jumat, 08:00 - 16:00 WIB</p>
            </div>
        </div>
    </div>
</section>

<footer class="footer">
    <div>SIVISIT — Kelompok 9 S1 Informatika UAS Pemrograman WEB ITSK Rs Dr Soepraoen Malang</div>
    <div style="margin-top:6px;font-style:italic;">Data simulasi/dummy. Tidak memberikan diagnosis medis. Rekomendasi hanya tindak lanjut administratif.</div>
</footer>


</body>
</html>
