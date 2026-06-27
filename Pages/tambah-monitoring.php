<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

// Fetch patients for dropdown
$patientsRes = callAPI('GET', '/pasien');
$patients    = ($patientsRes['status_code'] === 200 && isset($patientsRes['response']['data']))
    ? $patientsRes['response']['data']
    : [];

$error      = '';
$prePatient = $_GET['patient_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── Validasi Tekanan Darah (format: sistolik/diastolik, e.g. 120/80)
    $bloodPressure = trim($_POST['blood_pressure'] ?? '');
    $bpValid = preg_match('/^\d{2,3}\/\d{2,3}$/', $bloodPressure);
    if ($bpValid) {
        [$sys, $dia] = explode('/', $bloodPressure);
        if ((int)$sys < 60 || (int)$sys > 250 || (int)$dia < 40 || (int)$dia > 150) {
            $bpValid = false;
        }
    }

    // ── Validasi Suhu Tubuh (35.0 – 42.0 °C)
    $temp    = (float)str_replace(',', '.', $_POST['body_temperature'] ?? '0');
    $tmpValid = ($temp >= 35.0 && $temp <= 42.0);

    if (!$bpValid) {
        $error = 'Format tekanan darah tidak valid. Gunakan format sistolik/diastolik (contoh: 120/80). Nilai sistolik: 60–250, diastolik: 40–150.';
    } elseif (!$tmpValid) {
        $error = 'Suhu tubuh tidak valid. Nilai harus antara 35.0°C dan 42.0°C.';
    } else {
        $payload = [
            'patient_id'       => $_POST['patient_id']       ?? '',
            'user_id'          => $_SESSION['user']['id']    ?? 1,
            'monitoring_date'  => $_POST['monitoring_date']  ?? '',
            'monitoring_time'  => $_POST['monitoring_time']  ?? '',
            'blood_pressure'   => $bloodPressure,
            'body_temperature' => $temp,
            'heart_rate'       => (int)($_POST['heart_rate']       ?? 0),
            'respiratory_rate' => (int)($_POST['respiratory_rate'] ?? 0),
            'oxygen_saturation'=> (float)($_POST['oxygen_saturation'] ?? 0),
            'symptoms'         => trim($_POST['symptoms']    ?? ''),
            'notes'            => trim($_POST['notes']       ?? ''),
            'status'           => $_POST['status']           ?? '',
        ];

        $result = callAPI('POST', '/monitoring', $payload);

        if (in_array($result['status_code'], [200, 201])) {
            header("Location: monitoring.php?success=1");
            exit;
        } else {
            $errors = $result['response']['errors'] ?? [];
            if (!empty($errors)) {
                $msgs = [];
                foreach ($errors as $f => $fe) $msgs[] = implode(', ', (array)$fe);
                $error = implode(' | ', $msgs);
            } else {
                $error = $result['response']['message'] ?? 'Gagal menyimpan. Periksa kembali data Anda.';
            }
        }
    }
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
    <title>Catat Monitoring — SIVISIT</title>
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

        .validation-hint {
            font-size: 11.5px;
            color: #8E8E93;
            margin-top: 4px;
        }
        .validation-hint.error { color: #FF3B30; }
        .validation-hint.ok    { color: #34C759; }

        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
        }

        /* Status Radio Buttons */
        .status-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .status-option input[type="radio"] { display: none; }
        .status-option label {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 10px;
            border: 1.5px solid #D8DCE6;
            cursor: pointer;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s;
            user-select: none;
        }
        .status-option input[value="Stable"]:checked    + label,
        .status-option input[value="Stabil"]:checked    + label { border-color:#34C759; background:#E8F8ED; color:#1A7A35; }
        .status-option input[value="Need Control"]:checked + label,
        .status-option input[value="Perlu Kontrol"]:checked + label { border-color:#FF9500; background:#FFF4E5; color:#8A4E00; }
        .status-option input[value="Need Referral"]:checked + label,
        .status-option input[value="Perlu Rujukan"]:checked + label { border-color:#FF3B30; background:#FFF0EF; color:#C0291F; }
        .status-option label:hover { border-color:#007AFF; }
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
                    <h1>Catat Monitoring Kesehatan</h1>
                    <p>Isi hasil pemeriksaan kondisi pasien pada kunjungan ini.</p>
                </div>
                <a href="monitoring.php" class="btn btn-outline-secondary">← Kembali</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-start gap-2 mb-4 sv-animate-in" role="alert">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="monForm" novalidate>
                <div class="row g-3">

                    <!-- Section 1: Pasien & Jadwal -->
                    <div class="col-12 sv-animate-in sv-animate-in-1">
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="section-icon" style="background:#E8F1FF;">📋</div>
                                <div>
                                    <h6>Informasi Kunjungan</h6>
                                    <p>Pilih pasien dan tanggal kunjungan</p>
                                </div>
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label for="patient_id" class="form-label">Pasien <span style="color:#FF3B30;">*</span></label>
                                        <select name="patient_id" id="patient_id" class="form-select" required>
                                            <option value="" disabled <?= empty($_POST['patient_id']) && empty($prePatient) ? 'selected' : '' ?>>— Pilih Pasien —</option>
                                            <?php foreach ($patients as $p): ?>
                                                <option value="<?= htmlspecialchars($p['patient_id']) ?>"
                                                    <?= (($_POST['patient_id'] ?? $prePatient) === $p['patient_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($p['patient_name']) ?> — <?= htmlspecialchars($p['patient_id']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="monitoring_date" class="form-label">Tanggal Monitoring <span style="color:#FF3B30;">*</span></label>
                                        <input type="date" name="monitoring_date" id="monitoring_date" class="form-control"
                                            value="<?= htmlspecialchars($_POST['monitoring_date'] ?? date('Y-m-d')) ?>"
                                            max="<?= date('Y-m-d') ?>"
                                            required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="monitoring_time" class="form-label">Jam Kunjungan</label>
                                        <input type="time" name="monitoring_time" id="monitoring_time" class="form-control"
                                            value="<?= htmlspecialchars($_POST['monitoring_time'] ?? date('H:i')) ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Petugas Pemeriksa</label>
                                        <input type="text" class="form-control"
                                            style="background:#F2F4F7;color:#636366;"
                                            value="<?= $userName ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Tanda Vital -->
                    <div class="col-12 sv-animate-in sv-animate-in-2">
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="section-icon" style="background:#FFF0EF;">🩺</div>
                                <div>
                                    <h6>Tanda-Tanda Vital</h6>
                                    <p>Hasil pengukuran fisik pasien — wajib diisi dengan benar</p>
                                </div>
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <!-- Blood Pressure -->
                                    <div class="col-md-4">
                                        <label for="blood_pressure" class="form-label">
                                            Tekanan Darah (mmHg) <span style="color:#FF3B30;">*</span>
                                        </label>
                                        <input type="text" name="blood_pressure" id="blood_pressure" class="form-control"
                                            placeholder="Contoh: 120/80"
                                            value="<?= htmlspecialchars($_POST['blood_pressure'] ?? '') ?>"
                                            required
                                            pattern="\d{2,3}\/\d{2,3}">
                                        <div class="validation-hint" id="bpHint">Format: sistolik/diastolik (mis. 120/80)</div>
                                    </div>

                                    <!-- Body Temperature -->
                                    <div class="col-md-4">
                                        <label for="body_temperature" class="form-label">
                                            Suhu Tubuh (°C) <span style="color:#FF3B30;">*</span>
                                        </label>
                                        <input type="number" name="body_temperature" id="body_temperature" class="form-control"
                                            placeholder="Contoh: 36.5"
                                            value="<?= htmlspecialchars($_POST['body_temperature'] ?? '') ?>"
                                            min="35.0" max="42.0" step="0.1"
                                            required>
                                        <div class="validation-hint" id="tempHint">Rentang normal: 35.0°C – 42.0°C</div>
                                    </div>

                                    <!-- Heart Rate -->
                                    <div class="col-md-4">
                                        <label for="heart_rate" class="form-label">Nadi (bpm)</label>
                                        <input type="number" name="heart_rate" id="heart_rate" class="form-control"
                                            placeholder="Contoh: 80"
                                            value="<?= htmlspecialchars($_POST['heart_rate'] ?? '') ?>"
                                            min="30" max="250">
                                        <div class="validation-hint">Normal: 60–100 bpm</div>
                                    </div>

                                    <!-- Respiratory Rate -->
                                    <div class="col-md-4">
                                        <label for="respiratory_rate" class="form-label">Laju Napas (x/menit)</label>
                                        <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control"
                                            placeholder="Contoh: 18"
                                            value="<?= htmlspecialchars($_POST['respiratory_rate'] ?? '') ?>"
                                            min="5" max="60">
                                        <div class="validation-hint">Normal: 12–20 x/menit</div>
                                    </div>

                                    <!-- Oxygen Saturation -->
                                    <div class="col-md-4">
                                        <label for="oxygen_saturation" class="form-label">Saturasi O₂ (%)</label>
                                        <input type="number" name="oxygen_saturation" id="oxygen_saturation" class="form-control"
                                            placeholder="Contoh: 98"
                                            value="<?= htmlspecialchars($_POST['oxygen_saturation'] ?? '') ?>"
                                            min="50" max="100" step="0.1">
                                        <div class="validation-hint">Normal: ≥ 95%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Keluhan & Catatan -->
                    <div class="col-12 sv-animate-in sv-animate-in-3">
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="section-icon" style="background:#E8F8ED;">📝</div>
                                <div>
                                    <h6>Keluhan & Catatan Petugas</h6>
                                    <p>Keluhan pasien dan rekomendasi tindak lanjut</p>
                                </div>
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="symptoms" class="form-label">Keluhan / Kondisi Pasien <span style="color:#FF3B30;">*</span></label>
                                        <textarea name="symptoms" id="symptoms" class="form-control" rows="3"
                                            placeholder="Deskripsikan keluhan atau kondisi yang ditemukan pada kunjungan ini..."
                                            required><?= htmlspecialchars($_POST['symptoms'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="notes" class="form-label">Rekomendasi Tindak Lanjut</label>
                                        <textarea name="notes" id="notes" class="form-control" rows="3"
                                            placeholder="Contoh: Jadwalkan kontrol ulang dalam 3 hari, anjurkan diet rendah garam..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                                        <div class="validation-hint">
                                            ⚠️ Rekomendasi hanya berupa tindak lanjut administratif, bukan nasihat medis spesifik.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Status -->
                    <div class="col-12 sv-animate-in sv-animate-in-4">
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="section-icon" style="background:#FFF4E5;">🏷️</div>
                                <div>
                                    <h6>Status Kondisi Pasien</h6>
                                    <p>Tentukan status kondisi berdasarkan hasil monitoring</p>
                                </div>
                            </div>
                            <div class="form-section-body">
                                <label class="form-label d-block mb-3">Status <span style="color:#FF3B30;">*</span></label>
                                <div class="status-options">
                                    <div class="status-option">
                                        <input type="radio" name="status" id="statusStable" value="Stable"
                                            <?= ($_POST['status'] ?? '') === 'Stable' ? 'checked' : '' ?> required>
                                        <label for="statusStable">✅ Stabil</label>
                                    </div>
                                    <div class="status-option">
                                        <input type="radio" name="status" id="statusControl" value="Need Control"
                                            <?= ($_POST['status'] ?? '') === 'Need Control' ? 'checked' : '' ?>>
                                        <label for="statusControl">⚠️ Perlu Kontrol</label>
                                    </div>
                                    <div class="status-option">
                                        <input type="radio" name="status" id="statusReferral" value="Need Referral"
                                            <?= ($_POST['status'] ?? '') === 'Need Referral' ? 'checked' : '' ?>>
                                        <label for="statusReferral">🚨 Perlu Rujukan</label>
                                    </div>
                                </div>
                                <div class="mt-3 p-3 rounded" style="background:#F8F9FA;font-size:12.5px;color:#636366;line-height:1.7;">
                                    <strong>Panduan status:</strong><br>
                                    <span style="color:#1A7A35;">✅ Stabil</span> — Kondisi pasien baik, tidak ada perubahan signifikan.<br>
                                    <span style="color:#8A4E00;">⚠️ Perlu Kontrol</span> — Ada temuan yang perlu ditindaklanjuti dengan kunjungan ulang atau pemeriksaan tambahan.<br>
                                    <span style="color:#C0291F;">🚨 Perlu Rujukan</span> — Kondisi memerlukan penanganan lebih lanjut di fasilitas kesehatan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="col-12 sv-animate-in sv-animate-in-4">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary">🔄 Reset</button>
                            <button type="submit" class="btn btn-primary px-4" id="submitBtn">💾 Simpan Monitoring</button>
                        </div>
                        <p class="text-end mt-2" style="font-size:12px;color:#8E8E93;">
                            ⚠️ Sistem ini tidak memberikan diagnosis medis. Rekomendasi hanya bersifat administratif.
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
<script>
    // ── Real-time Blood Pressure Validation
    document.getElementById('blood_pressure').addEventListener('input', function() {
        const val  = this.value.trim();
        const hint = document.getElementById('bpHint');
        const regex = /^\d{2,3}\/\d{2,3}$/;
        if (!val) { hint.className = 'validation-hint'; hint.textContent = 'Format: sistolik/diastolik (mis. 120/80)'; return; }
        if (!regex.test(val)) { hint.className = 'validation-hint error'; hint.textContent = '✗ Format salah. Gunakan format: 120/80'; return; }
        const [sys, dia] = val.split('/').map(Number);
        if (sys < 60 || sys > 250 || dia < 40 || dia > 150) {
            hint.className = 'validation-hint error';
            hint.textContent = '✗ Nilai di luar rentang. Sistolik: 60–250, Diastolik: 40–150';
        } else {
            hint.className = 'validation-hint ok';
            hint.textContent = '✓ Tekanan darah valid';
        }
    });

    // ── Real-time Temperature Validation
    document.getElementById('body_temperature').addEventListener('input', function() {
        const val  = parseFloat(this.value);
        const hint = document.getElementById('tempHint');
        if (!this.value) { hint.className = 'validation-hint'; hint.textContent = 'Rentang normal: 35.0°C – 42.0°C'; return; }
        if (val < 35 || val > 42) {
            hint.className = 'validation-hint error';
            hint.textContent = '✗ Suhu di luar rentang valid (35.0 – 42.0°C)';
        } else {
            hint.className = 'validation-hint ok';
            const status = val >= 37.5 ? '🔴 Demam' : (val < 36.0 ? '🔵 Hipotermi' : '🟢 Normal');
            hint.textContent = `✓ Suhu valid — ${status}`;
        }
    });

    // Loading on submit
    document.getElementById('monForm').addEventListener('submit', function() {
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
