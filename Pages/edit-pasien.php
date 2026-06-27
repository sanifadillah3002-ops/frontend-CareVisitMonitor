<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

$patientId = $_GET['id'] ?? '';
if (empty($patientId)) {
    header("Location: pasien.php");
    exit;
}

// Fetch patient data
$patientRes = callAPI('GET', '/pasien/' . urlencode($patientId));
$patient    = ($patientRes['status_code'] === 200 && isset($patientRes['response']['data']))
    ? $patientRes['response']['data']
    : null;

if ($patient === null) {
    header("Location: pasien.php?error=not_found");
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'patient_name'     => trim($_POST['patient_name']     ?? ''),
        'nik_dummy'        => trim($_POST['nik_dummy']        ?? ''),
        'datebirth'        => trim($_POST['datebirth']        ?? ''),
        'gender'           => trim($_POST['gender']           ?? ''),
        'address'          => trim($_POST['address']          ?? ''),
        'latitude'         => trim($_POST['latitude']         ?? ''),
        'longitude'        => trim($_POST['longitude']        ?? ''),
        'family_phone'     => trim($_POST['family_phone']     ?? ''),
        'patient_category' => trim($_POST['patient_category'] ?? ''),
        'user_id'          => $_SESSION['user']['id'] ?? 1,
    ];

    $result = callAPI('PUT', '/pasien/' . urlencode($patientId), $payload);

    if (in_array($result['status_code'], [200, 201])) {
        header("Location: pasien.php?success=updated");
        exit;
    } else {
        $errors = $result['response']['errors'] ?? [];
        if (!empty($errors)) {
            $msgs = [];
            foreach ($errors as $f => $fe) $msgs[] = implode(', ', (array)$fe);
            $error = implode(' | ', $msgs);
        } else {
            $error = $result['response']['message'] ?? 'Gagal memperbarui data. Coba lagi.';
        }
    }
}

// Use POST data on error, otherwise use patient data
$data = ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($error)) ? $_POST : $patient;

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
    <title>Edit Pasien — SIVISIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="globals.css" rel="stylesheet">
    <link href="global.css" rel="stylesheet">
    <style>
        .form-section {
            background: white;
            border: 1px solid #D8DCE6;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .form-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 20px;
            border-bottom: 1px solid #F0F2F5;
            background: #FAFBFC;
        }
        .form-section-header .section-icon {
            width: 34px; height: 34px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }
        .form-section-header h6 { margin:0; font-size:14px; font-weight:700; color:#1C1C1E; }
        .form-section-header p  { margin:2px 0 0; font-size:12px; color:#8E8E93; }
        .form-section-body { padding: 20px; }
        .readonly-field { background:#F2F4F7; color:#636366; cursor:not-allowed; }
        .field-hint { font-size:11.5px; color:#8E8E93; margin-top:4px; }
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
            <div class="sv-page-header sv-animate-in">
                <div>
                    <h1>Edit Data Pasien</h1>
                    <p>Memperbarui data pasien: <strong><?= htmlspecialchars($patient['patient_name'] ?? '') ?></strong> — <span style="color:#007AFF;"><?= htmlspecialchars($patientId) ?></span></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="cetak-monitoring.php?patient_id=<?= urlencode($patientId) ?>" class="btn btn-outline-secondary" target="_blank">🖨️ Cetak Ringkasan</a>
                    <a href="pasien.php" class="btn btn-outline-secondary">← Kembali</a>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-start gap-2 mb-4 sv-animate-in" role="alert">
                    <span>⚠️</span><span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="editForm" novalidate>
                <div class="row g-3">

                    <!-- Section 1: Identitas (readonly) -->
                    <div class="col-12 sv-animate-in sv-animate-in-1">
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="section-icon" style="background:#E8F1FF;">🔖</div>
                                <div>
                                    <h6>Identifikasi Pasien</h6>
                                    <p>Kode pasien tidak dapat diubah setelah pendaftaran</p>
                                </div>
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Kode Pasien / No. RM</label>
                                        <input type="text" class="form-control readonly-field" value="<?= htmlspecialchars($patientId) ?>" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="nik_dummy" class="form-label">NIK Dummy <span style="color:#FF3B30;">*</span></label>
                                        <input type="text" name="nik_dummy" id="nik_dummy" class="form-control"
                                            value="<?= htmlspecialchars($data['nik_dummy'] ?? '') ?>"
                                            maxlength="16" pattern="\d{16}" required>
                                        <div class="field-hint" id="nikCount"><?= strlen($data['nik_dummy'] ?? '') ?> / 16 digit</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="patient_category" class="form-label">Kategori Pasien <span style="color:#FF3B30;">*</span></label>
                                        <select name="patient_category" id="patient_category" class="form-select" required>
                                            <?php $cat = $data['patient_category'] ?? ''; ?>
                                            <option value="Lansia"      <?= $cat === 'Lansia'      ? 'selected' : '' ?>>🧓 Lansia</option>
                                            <option value="Hipertensi"  <?= $cat === 'Hipertensi'  ? 'selected' : '' ?>>❤️ Hipertensi</option>
                                            <option value="Diabetes"    <?= $cat === 'Diabetes'    ? 'selected' : '' ?>>🩸 Diabetes</option>
                                            <option value="Pasca Rawat" <?= $cat === 'Pasca Rawat' ? 'selected' : '' ?>>🏥 Pasca Rawat</option>
                                            <option value="Lainnya"     <?= $cat === 'Lainnya'     ? 'selected' : '' ?>>📋 Lainnya</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Data Pribadi -->
                    <div class="col-12 sv-animate-in sv-animate-in-2">
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="section-icon" style="background:#E8F8ED;">👤</div>
                                <div>
                                    <h6>Data Pribadi</h6>
                                    <p>Informasi identitas dan demografis pasien</p>
                                </div>
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="patient_name" class="form-label">Nama Lengkap <span style="color:#FF3B30;">*</span></label>
                                        <input type="text" name="patient_name" id="patient_name" class="form-control"
                                            value="<?= htmlspecialchars($data['patient_name'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="gender" class="form-label">Jenis Kelamin <span style="color:#FF3B30;">*</span></label>
                                        <select name="gender" id="gender" class="form-select" required>
                                            <option value="Male"   <?= ($data['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>👨 Laki-laki</option>
                                            <option value="Female" <?= ($data['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>👩 Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="datebirth" class="form-label">Tanggal Lahir <span style="color:#FF3B30;">*</span></label>
                                        <input type="date" name="datebirth" id="datebirth" class="form-control"
                                            value="<?= htmlspecialchars($data['datebirth'] ?? '') ?>"
                                            max="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="address" class="form-label">Alamat Lengkap <span style="color:#FF3B30;">*</span></label>
                                        <div class="input-group">
                                            <input type="text" name="address" id="address" class="form-control"
                                                value="<?= htmlspecialchars($data['address'] ?? '') ?>" required>
                                            <button type="button" class="btn btn-outline-primary" id="detectLocationBtn" title="Deteksi lokasi dari alamat">🗺️</button>
                                        </div>
                                        <div id="geoStatus" class="field-hint">📍 Klik 🗺️ untuk deteksi lokasi otomatis</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Latitude</label>
                                        <input type="text" name="latitude" id="latitude" class="form-control"
                                            value="<?= htmlspecialchars($data['latitude'] ?? '') ?>"
                                            readonly style="background:#F2F4F7;font-size:12px;">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Longitude</label>
                                        <input type="text" name="longitude" id="longitude" class="form-control"
                                            value="<?= htmlspecialchars($data['longitude'] ?? '') ?>"
                                            readonly style="background:#F2F4F7;font-size:12px;">
                                    </div>
                                    <div class="col-md-12">
                                        <div id="miniMap" style="height:0;transition:height 0.3s ease;border-radius:8px;overflow:hidden;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Danger Zone -->
                    <div class="col-12 sv-animate-in sv-animate-in-3">
                        <div class="form-section" style="border-color:#FFD0CC;">
                            <div class="form-section-header" style="background:#FFF0EF;">
                                <div class="section-icon" style="background:#FFD0CC;">⚠️</div>
                                <div>
                                    <h6 style="color:#C0291F;">Zona Berbahaya</h6>
                                    <p>Tindakan ini tidak dapat dibatalkan</p>
                                </div>
                            </div>
                            <div class="form-section-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div style="font-size:14px;font-weight:600;color:#1C1C1E;">Hapus Data Pasien Ini</div>
                                        <div style="font-size:13px;color:#636366;margin-top:2px;">Menghapus semua data pasien dan riwayat monitoring secara permanen.</div>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="deleteBtn"
                                        data-patient-id="<?= htmlspecialchars($patientId) ?>"
                                        data-patient-name="<?= htmlspecialchars($patient['patient_name'] ?? '') ?>">
                                        🗑️ Hapus Pasien
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="col-12 sv-animate-in sv-animate-in-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="pasien.php" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary px-4" id="submitBtn">💾 Simpan Perubahan</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <footer style="padding:20px 24px;border-top:1px solid #E8ECF0;background:#FAFBFC;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <span style="font-size:12px;color:#8E8E93;">Sivisit-Kelompok 9 S1 Informatika UAS Pemrograman WEB ITSK Rs Dr Soepraoen Malang</span>
                <span style="font-size:11px;color:#8E8E93;font-style:italic;">⚠️ Data simulasi/dummy. Bukan diagnosis medis.</span>
            </div>
        </footer>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px;border:none;">
            <div class="modal-header" style="background:#FFF0EF;border-bottom:1px solid #FFD0CC;">
                <h5 class="modal-title" style="color:#C0291F;">⚠️ Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Anda akan menghapus pasien <strong id="deletePatientName"></strong>.</p>
                <p style="color:#636366;font-size:13px;">Seluruh data pasien dan riwayat monitoring akan dihapus secara permanen dari sistem.</p>
                <div style="background:#FFF4E5;border:1px solid #FFE0A3;border-radius:8px;padding:10px 12px;font-size:12.5px;color:#8A4E00;">
                    ⚠️ Tindakan ini tidak dapat dibatalkan.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <form id="deleteForm" action="" method="POST">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="button" class="btn btn-danger btn-sm" id="confirmDelete">Ya, Hapus Pasien</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // NIK counter
    document.getElementById('nik_dummy').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 16);
        const len = this.value.length;
        const el = document.getElementById('nikCount');
        el.textContent = len + ' / 16 digit';
        el.style.color = len === 16 ? '#34C759' : (len > 0 && len < 16 ? '#FF9500' : '#8E8E93');
    });

    // Delete modal
    document.getElementById('deleteBtn').addEventListener('click', function() {
        document.getElementById('deletePatientName').textContent = this.dataset.patientName;
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    });

    document.getElementById('confirmDelete').addEventListener('click', function() {
        const patientId = <?= json_encode($patientId) ?>;
        window.location.href = 'hapus-pasien.php?id=' + encodeURIComponent(patientId);
    });

    // ─── Geocoding ──────────────────────────────────────
    let miniMap = null, miniMarker = null;
    document.getElementById('detectLocationBtn')?.addEventListener('click', function() {
        const addr = document.getElementById('address').value.trim();
        if (!addr) { document.getElementById('geoStatus').textContent = '⚠️ Isi alamat dulu.'; return; }
        const status = document.getElementById('geoStatus');
        status.textContent = '🔍 Mendeteksi...';
        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(addr + ', Indonesia') + '&limit=1',
            { headers: { 'User-Agent': 'SIVISIT-CareVisitMonitor/1.0' } })
        .then(r => r.json()).then(data => {
            if (!data || data.length === 0) { status.textContent = '⚠️ Tidak ditemukan.'; return; }
            const lat = parseFloat(data[0].lat), lng = parseFloat(data[0].lon);
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
            status.textContent = '✅ ' + lat.toFixed(4) + ', ' + lng.toFixed(4);
            status.style.color = '#34C759';
            const mapEl = document.getElementById('miniMap');
            mapEl.style.height = '200px';
            if (!miniMap) {
                miniMap = L.map('miniMap', { center: [lat, lng], zoom: 15, attributionControl: false });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(miniMap);
                miniMarker = L.marker([lat, lng]).addTo(miniMap).bindPopup('📍 ' + addr).openPopup();
            } else {
                miniMap.setView([lat, lng], 15);
                miniMarker.setLatLng([lat, lng]).setPopupContent('📍 ' + addr).openPopup();
            }
            setTimeout(() => miniMap?.invalidateSize(), 400);
        }).catch(() => { status.textContent = '⚠️ Gagal.'; });
    });

    // Submit loading
    document.getElementById('editForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.textContent = 'Menyimpan...';
        btn.disabled = true;
    });

    // Global search
    document.getElementById('globalSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim())
            window.location.href = 'cari-pasien.php?q=' + encodeURIComponent(this.value.trim());
    });

    // Show mini map if lat/lng already exist
    document.addEventListener('DOMContentLoaded', function() {
        const lat = document.getElementById('latitude')?.value;
        const lng = document.getElementById('longitude')?.value;
        if (lat && lng && !isNaN(parseFloat(lat)) && !isNaN(parseFloat(lng))) {
            document.getElementById('detectLocationBtn')?.click();
        }
    });
</script>
</body>
</html>
