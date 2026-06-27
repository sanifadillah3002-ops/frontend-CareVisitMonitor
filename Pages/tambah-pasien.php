<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'patient_id'       => trim($_POST['patient_id']       ?? ''),
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

    $result = callAPI('POST', '/pasien', $payload);

    if ($result['status_code'] === 201 || $result['status_code'] === 200) {
        header("Location: pasien.php?success=1");
        exit;
    } else {
        $errors = $result['response']['errors'] ?? [];
        if (!empty($errors)) {
            $msgs = [];
            foreach ($errors as $field => $fieldErrors) {
                $msgs[] = implode(', ', (array)$fieldErrors);
            }
            $error = implode(' | ', $msgs);
        } else {
            $error = $result['response']['message'] ?? 'Gagal menyimpan data. Periksa kembali isian Anda.';
        }
    }
}

$user        = $_SESSION['user'] ?? [];
$userName    = htmlspecialchars($user['name']  ?? 'Petugas');
$userInitial = strtoupper(substr($user['name'] ?? 'P', 0, 1));
$userEmail   = htmlspecialchars($user['email'] ?? '');

// Auto-generate patient ID suggestion
$suggestedId = 'RM-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pasien — SIVISIT</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .form-section-header h6 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: #1C1C1E;
        }
        .form-section-header p {
            margin: 2px 0 0;
            font-size: 12px;
            color: #8E8E93;
        }
        .form-section-body {
            padding: 20px;
        }
        .form-control.readonly-field {
            background: #F2F4F7;
            color: #636366;
            cursor: not-allowed;
        }
        .char-count {
            font-size: 11px;
            color: #8E8E93;
            text-align: right;
            margin-top: 3px;
        }
        .field-hint {
            font-size: 11.5px;
            color: #8E8E93;
            margin-top: 4px;
        }
        .generate-btn {
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 8px;
            white-space: nowrap;
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
                    <h1>Tambah Pasien Baru</h1>
                    <p>Isi formulir di bawah untuk mendaftarkan pasien binaan baru ke sistem.</p>
                </div>
                <a href="pasien.php" class="btn btn-outline-secondary">← Kembali</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-start gap-2 mb-4 sv-animate-in" role="alert">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="addPatientForm" novalidate>
                <div class="row g-3">

                    <!-- Section 1: Petugas -->
                    <div class="col-12 sv-animate-in sv-animate-in-1">
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="section-icon" style="background:#E8F1FF;">📋</div>
                                <div>
                                    <h6>Informasi Petugas</h6>
                                    <p>Data petugas yang mendaftarkan pasien</p>
                                </div>
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">ID Petugas</label>
                                        <input type="text" class="form-control readonly-field"
                                            value="PM-<?= date('Y') ?>-<?= htmlspecialchars($_SESSION['user']['id'] ?? '1') ?>"
                                            readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Petugas</label>
                                        <input type="text" class="form-control readonly-field"
                                            value="<?= $userName ?>"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Identitas Pasien -->
                    <div class="col-12 sv-animate-in sv-animate-in-2">
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="section-icon" style="background:#E8F8ED;">👤</div>
                                <div>
                                    <h6>Identitas Pasien</h6>
                                    <p>Data diri dan dokumen identitas pasien</p>
                                </div>
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="patient_id" class="form-label">
                                            Nomor Rekam Medis (Kode Pasien) <span style="color:#FF3B30;">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" name="patient_id" id="patient_id" class="form-control"
                                                placeholder="Contoh: RM-2026-0089"
                                                value="<?= htmlspecialchars($_POST['patient_id'] ?? $suggestedId) ?>"
                                                required>
                                            <button type="button" class="btn btn-outline-secondary generate-btn" id="generateId">
                                                🔄 Generate
                                            </button>
                                        </div>
                                        <div class="field-hint">Kode unik untuk mengidentifikasi pasien dalam sistem.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="nik_dummy" class="form-label">
                                            NIK Dummy (16 Digit) <span style="color:#FF3B30;">*</span>
                                        </label>
                                        <input type="text" name="nik_dummy" id="nik_dummy" class="form-control"
                                            placeholder="Contoh: 3578012345670001"
                                            value="<?= htmlspecialchars($_POST['nik_dummy'] ?? '') ?>"
                                            maxlength="16"
                                            pattern="\d{16}"
                                            required>
                                        <div class="char-count" id="nikCount">0 / 16 digit</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="patient_name" class="form-label">
                                            Nama Lengkap Pasien <span style="color:#FF3B30;">*</span>
                                        </label>
                                        <input type="text" name="patient_name" id="patient_name" class="form-control"
                                            placeholder="Masukkan nama lengkap"
                                            value="<?= htmlspecialchars($_POST['patient_name'] ?? '') ?>"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="patient_category" class="form-label">
                                            Kategori Pasien <span style="color:#FF3B30;">*</span>
                                        </label>
                                        <select name="patient_category" id="patient_category" class="form-select" required>
                                            <option value="" disabled <?= empty($_POST['patient_category']) ? 'selected' : '' ?>>— Pilih Kategori —</option>
                                            <option value="Lansia"      <?= ($_POST['patient_category'] ?? '') === 'Lansia'      ? 'selected' : '' ?>>🧓 Lansia (Lanjut Usia)</option>
                                            <option value="Hipertensi"  <?= ($_POST['patient_category'] ?? '') === 'Hipertensi'  ? 'selected' : '' ?>>❤️ Hipertensi</option>
                                            <option value="Diabetes"    <?= ($_POST['patient_category'] ?? '') === 'Diabetes'    ? 'selected' : '' ?>>🩸 Diabetes</option>
                                            <option value="Pasca Rawat" <?= ($_POST['patient_category'] ?? '') === 'Pasca Rawat' ? 'selected' : '' ?>>🏥 Pasca Rawat</option>
                                            <option value="Lainnya"     <?= ($_POST['patient_category'] ?? '') === 'Lainnya'     ? 'selected' : '' ?>>📋 Lainnya</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Data Pribadi -->
                    <div class="col-12 sv-animate-in sv-animate-in-3">
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="section-icon" style="background:#FFF4E5;">🗓️</div>
                                <div>
                                    <h6>Data Pribadi & Kontak</h6>
                                    <p>Informasi demografis dan kontak darurat keluarga</p>
                                </div>
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="gender" class="form-label">
                                            Jenis Kelamin <span style="color:#FF3B30;">*</span>
                                        </label>
                                        <select name="gender" id="gender" class="form-select" required>
                                            <option value="" disabled <?= empty($_POST['gender']) ? 'selected' : '' ?>>— Pilih —</option>
                                            <option value="Male"   <?= ($_POST['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>👨 Laki-laki</option>
                                            <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>👩 Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="datebirth" class="form-label">
                                            Tanggal Lahir <span style="color:#FF3B30;">*</span>
                                        </label>
                                        <input type="date" name="datebirth" id="datebirth" class="form-control"
                                            value="<?= htmlspecialchars($_POST['datebirth'] ?? '') ?>"
                                            max="<?= date('Y-m-d') ?>"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Usia (Otomatis)</label>
                                        <input type="text" id="ageDisplay" class="form-control readonly-field"
                                            value="— Isi tanggal lahir" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="address" class="form-label">
                                            Alamat Lengkap <span style="color:#FF3B30;">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" name="address" id="address" class="form-control"
                                                placeholder="Jalan, No. Rumah, RT/RW, Kelurahan, Kecamatan, Kota"
                                                value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
                                                required>
                                            <button type="button" class="btn btn-outline-primary" id="detectLocationBtn"
                                                title="Deteksi lokasi dari alamat">
                                                🗺️
                                            </button>
                                        </div>
                                        <div id="geoStatus" class="field-hint" style="margin-top:4px;">📍 Klik 🗺️ untuk deteksi lokasi otomatis dari alamat</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Latitude</label>
                                        <input type="text" name="latitude" id="latitude" class="form-control"
                                            value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>"
                                            readonly style="background:#F2F4F7;font-size:12px;">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Longitude</label>
                                        <input type="text" name="longitude" id="longitude" class="form-control"
                                            value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>"
                                            readonly style="background:#F2F4F7;font-size:12px;">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Ambil Lokasi Saya</label>
                                        <button type="button" class="btn btn-outline-success btn-sm w-100" id="myLocationBtn"
                                            style="font-size:12px;">
                                            📍 GPS Saya
                                        </button>
                                    </div>
                                    <div class="col-md-12">
                                        <div id="miniMap" style="height:0;transition:height 0.3s ease;border-radius:8px;overflow:hidden;"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="family_phone" class="form-label">
                                            No. HP Keluarga (Darurat) <span style="color:#FF3B30;">*</span>
                                        </label>
                                        <input type="tel" name="family_phone" id="family_phone" class="form-control"
                                            placeholder="Contoh: 08123456789"
                                            value="<?= htmlspecialchars($_POST['family_phone'] ?? '') ?>"
                                            required>
                                        <div class="field-hint">Nomor dummy, tidak dihubungi secara nyata.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 sv-animate-in sv-animate-in-4">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('addPatientForm').reset(); document.getElementById('ageDisplay').value='— Isi tanggal lahir'; document.getElementById('nikCount').textContent='0 / 16 digit';">
                                🔄 Reset Form
                            </button>
                            <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                                💾 Simpan Data Pasien
                            </button>
                        </div>
                        <p class="text-end mt-2" style="font-size:12px;color:#8E8E93;">
                            ⚠️ Pastikan data yang dimasukkan adalah data dummy/simulasi sesuai ketentuan akademik.
                        </p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // NIK counter
    document.getElementById('nik_dummy').addEventListener('input', function() {
        const len = this.value.replace(/\D/g, '').length;
        const el = document.getElementById('nikCount');
        el.textContent = len + ' / 16 digit';
        el.style.color = len === 16 ? '#34C759' : (len > 16 ? '#FF3B30' : '#8E8E93');
        this.value = this.value.replace(/\D/g, '').slice(0, 16);
    });

    // Auto-calculate age
    document.getElementById('datebirth').addEventListener('change', function() {
        if (!this.value) return;
        const dob = new Date(this.value);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
        document.getElementById('ageDisplay').value = age + ' Tahun';
    });

    // Generate patient ID
    document.getElementById('generateId').addEventListener('click', function() {
        const year = new Date().getFullYear();
        const num  = String(Math.floor(Math.random() * 9999) + 1).padStart(4, '0');
        document.getElementById('patient_id').value = 'RM-' + year + '-' + num;
    });

    // ─── Deteksi Lokasi dari Alamat (Geocoding) ──────────
    let miniMap = null, miniMarker = null;

    document.getElementById('detectLocationBtn').addEventListener('click', function() {
        const addr = document.getElementById('address').value.trim();
        if (!addr) {
            document.getElementById('geoStatus').textContent = '⚠️ Isi alamat terlebih dahulu.';
            document.getElementById('geoStatus').style.color = '#FF3B30';
            return;
        }

        const status = document.getElementById('geoStatus');
        status.textContent = '🔍 Mendeteksi lokasi...';
        status.style.color = '#8E8E93';

        const url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(addr + ', Indonesia') + '&limit=1';
        fetch(url, { headers: { 'Accept': 'application/json', 'User-Agent': 'SIVISIT-CareVisitMonitor/1.0' } })
        .then(r => r.json())
        .then(data => {
            if (!data || data.length === 0) {
                status.textContent = '⚠️ Alamat tidak ditemukan. Coba perjelas alamat (tambah kota/kecamatan).';
                status.style.color = '#FF3B30';
                return;
            }

            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
            status.textContent = '✅ Lokasi ditemukan! (' + lat.toFixed(4) + ', ' + lng.toFixed(4) + ')';
            status.style.color = '#34C759';

            // Show mini map
            const mapEl = document.getElementById('miniMap');
            mapEl.style.height = '200px';
            if (!miniMap) {
                miniMap = L.map('miniMap', {
                    center: [lat, lng],
                    zoom: 15,
                    zoomControl: true,
                    attributionControl: false,
                });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(miniMap);
                miniMarker = L.marker([lat, lng]).addTo(miniMap).bindPopup('📍 ' + addr).openPopup();
            } else {
                miniMap.setView([lat, lng], 15);
                if (miniMarker) miniMarker.setLatLng([lat, lng]).setPopupContent('📍 ' + addr).openPopup();
                else miniMarker = L.marker([lat, lng]).addTo(miniMap).bindPopup('📍 ' + addr).openPopup();
            }
            setTimeout(() => miniMap?.invalidateSize(), 400);
        })
        .catch(err => {
            status.textContent = '⚠️ Gagal mendeteksi lokasi. Coba lagi.';
            status.style.color = '#FF3B30';
        });
    });

    // ─── Ambil Lokasi GPS Saya ───────────────────────────
    document.getElementById('myLocationBtn').addEventListener('click', function() {
        if (!navigator.geolocation) {
            document.getElementById('geoStatus').textContent = '⚠️ GPS tidak didukung browser ini.';
            document.getElementById('geoStatus').style.color = '#FF3B30';
            return;
        }

        const status = document.getElementById('geoStatus');
        status.textContent = '🛰️ Mendapatkan lokasi GPS...';
        status.style.color = '#8E8E93';

        navigator.geolocation.getCurrentPosition(
            function(pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                document.getElementById('latitude').value = lat.toFixed(6);
                document.getElementById('longitude').value = lng.toFixed(6);
                document.getElementById('address').value = lat.toFixed(6) + ', ' + lng.toFixed(6);
                status.textContent = '✅ Lokasi GPS: ' + lat.toFixed(4) + ', ' + lng.toFixed(4);
                status.style.color = '#34C759';

                // Show mini map
                const mapEl = document.getElementById('miniMap');
                mapEl.style.height = '200px';
                if (!miniMap) {
                    miniMap = L.map('miniMap', { center: [lat, lng], zoom: 15, zoomControl: true, attributionControl: false });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(miniMap);
                    miniMarker = L.marker([lat, lng]).addTo(miniMap).bindPopup('📍 Lokasi Saya').openPopup();
                } else {
                    miniMap.setView([lat, lng], 15);
                    if (miniMarker) miniMarker.setLatLng([lat, lng]).setPopupContent('📍 Lokasi Saya').openPopup();
                    else miniMarker = L.marker([lat, lng]).addTo(miniMap).bindPopup('📍 Lokasi Saya').openPopup();
                }
                setTimeout(() => miniMap?.invalidateSize(), 400);
            },
            function(err) {
                status.textContent = '⚠️ Gagal: ' + err.message;
                status.style.color = '#FF3B30';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });

    // Loading on submit
    document.getElementById('addPatientForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.textContent = 'Menyimpan...';
        btn.disabled = true;
    });

    // Global search
    document.getElementById('globalSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim())
            window.location.href = 'cari-pasien.php?q=' + encodeURIComponent(this.value.trim());
    });
</script>
</body>
</html>
