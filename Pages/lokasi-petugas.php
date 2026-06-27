<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

$user        = $_SESSION['user'] ?? [];
$userName    = htmlspecialchars($user['name']  ?? 'Petugas');
$userInitial = strtoupper(substr($user['name'] ?? 'P', 0, 1));
$userEmail   = htmlspecialchars($user['email'] ?? '');
$userId      = $user['id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lokasi Petugas — SIVISIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="globals.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #mapContainer {
            height: 70vh;
            border-radius: var(--sv-radius);
            border: 1px solid var(--sv-border);
            overflow: hidden;
            position: relative;
        }
        #mapContainer .leaflet-container {
            border-radius: var(--sv-radius);
        }
        .loc-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: var(--sv-radius-sm);
            background: white;
            border: 1px solid var(--sv-border);
            font-size: 13px;
        }
        .loc-status .pulse-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: var(--sv-green);
            animation: pulse 1.5s infinite;
            flex-shrink: 0;
        }
        .loc-status .pulse-dot.inactive {
            background: var(--sv-red);
            animation: none;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(52,199,89,0.6); }
            70% { box-shadow: 0 0 0 8px rgba(52,199,89,0); }
            100% { box-shadow: 0 0 0 0 rgba(52,199,89,0); }
        }
        .loc-card {
            background: white;
            border: 1px solid var(--sv-border);
            border-radius: var(--sv-radius);
            padding: 14px 16px;
            transition: var(--sv-transition);
        }
        .loc-card:hover {
            box-shadow: var(--sv-shadow);
            transform: translateY(-1px);
        }
        .loc-card .officer-name {
            font-weight: 600;
            font-size: 13.5px;
            color: var(--sv-text-main);
        }
        .loc-card .officer-time {
            font-size: 11.5px;
            color: var(--sv-text-muted);
        }
        .loc-card .officer-loc {
            font-size: 12px;
            color: var(--sv-text-sub);
        }
        #locateBtn {
            position: absolute;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--sv-border);
            box-shadow: var(--sv-shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            transition: var(--sv-transition);
        }
        #locateBtn:hover {
            background: var(--sv-blue-light);
            border-color: var(--sv-blue);
        }
        #locateBtn.tracking {
            background: var(--sv-blue);
            border-color: var(--sv-blue);
            color: white;
        }
        .leaflet-popup-content {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }
        .leaflet-popup-content strong {
            display: block;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .custom-marker-petugas {
            background: var(--sv-blue);
            border: 3px solid white;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .custom-marker-pasien {
            background: var(--sv-red);
            border: 3px solid white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 11px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .leaflet-control-zoom a {
            font-size: 18px !important;
        }
        @media (max-width: 768px) {
            #mapContainer { height: 55vh; }
        }
    </style>
</head>
<body>
<div class="sv-layout">
    <?php require_once 'components/sidebar.php'; ?>

    <div class="sv-main">
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

        <div class="sv-content">
            <div class="sv-page-header">
                <div>
                    <h1>📍 Monitoring Lokasi Petugas</h1>
                    <p>Pantau lokasi petugas home care secara real-time &amp; temukan pasien terdekat.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshAll()">🔄 Refresh</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
                </div>
            </div>

            <!-- Status Bar -->
            <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                <div class="loc-status" id="gpsStatus">
                    <span class="pulse-dot" id="gpsDot"></span>
                    <span id="gpsText">Mendeteksi lokasi...</span>
                </div>
                <div class="loc-status">
                    <span>🛰️</span>
                    <span id="coordDisplay">--等待 sinyal GPS</span>
                </div>
                <div class="loc-status" id="petugasCount">
                    <span>👥</span>
                    <span id="onlineCount">0 petugas online</span>
                </div>
            </div>

            <div class="row g-3">
                <!-- Map Column -->
                <div class="col-12 col-lg-8">
                    <div id="mapContainer">
                        <div id="map"></div>
                        <button id="locateBtn" onclick="toggleTracking()" title="Aktifkan pelacakan GPS">📍</button>
                    </div>

                    <!-- Legend -->
                    <div class="d-flex gap-4 mt-2 flex-wrap" style="font-size:12px;color:var(--sv-text-sub);">
                        <span><span style="display:inline-block;width:12px;height:12px;background:var(--sv-blue);border-radius:50%;margin-right:4px;"></span> Petugas Online</span>
                        <span><span style="display:inline-block;width:12px;height:12px;background:#8E8E93;border-radius:50%;margin-right:4px;"></span> Petugas Offline</span>
                        <span><span style="display:inline-block;width:12px;height:12px;background:var(--sv-red);border-radius:50%;margin-right:4px;"></span> Lokasi Pasien</span>
                        <span><span style="display:inline-block;width:12px;height:12px;background:var(--sv-green);border-radius:50%;margin-right:4px;"></span> Lokasi Saya</span>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="col-12 col-lg-4">
                    <div class="d-flex flex-column gap-3">
                        <!-- Nearby Search -->
                        <div class="sv-card">
                            <h5 style="font-size:14px;font-weight:700;margin:0 0 12px;">🔍 Cari Pasien Terdekat</h5>
                            <div class="d-flex gap-2">
                                <input type="number" id="radiusInput" class="form-control form-control-sm" value="5" min="1" max="50" style="width:80px;">
                                <span style="line-height:32px;font-size:13px;color:var(--sv-text-muted);">km</span>
                                <button class="btn btn-primary btn-sm ms-auto" onclick="findNearby()">Cari</button>
                            </div>
                            <div id="nearbyResults" style="margin-top:12px;max-height:180px;overflow-y:auto;"></div>
                        </div>

                        <!-- Officers Online List -->
                        <div class="sv-card">
                            <h5 style="font-size:14px;font-weight:700;margin:0 0 12px;">👥 Petugas Online</h5>
                            <div id="officerList" style="max-height:250px;overflow-y:auto;">
                                <div class="sv-empty-state" style="padding:16px 0;">
                                    <p style="font-size:13px;">Memuat data petugas...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="sv-card">
                            <h5 style="font-size:14px;font-weight:700;margin:0 0 12px;">⚡ Aksi Cepat</h5>
                            <div class="d-flex flex-column gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="centerOnMyLocation()">📍 Fokus ke Lokasi Saya</button>
                                <button class="btn btn-outline-primary btn-sm" onclick="loadAllPatients()">🏠 Tampilkan Semua Pasien</button>
                                <button class="btn btn-outline-primary btn-sm" onclick="refreshPetugas()">🔄 Refresh Petugas</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer style="padding:16px 24px;border-top:1px solid var(--sv-border);text-align:center;background:var(--sv-surface);">
            <div style="font-size:12px;color:var(--sv-text-muted);">Sivisit-Kelompok 9 S1 Informatika UAS Pemrograman WEB ITSK Rs Dr Soepraoen Malang</div>
            <div style="font-size:11px;color:var(--sv-text-muted);font-style:italic;margin-top:4px;">⚠️ Data simulasi/dummy. Bukan diagnosis medis. Rekomendasi hanya tindak lanjut administratif.</div>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // ─── State ──────────────────────────────────────────
    let map, myMarker, trackingId = null, isTracking = false;
    let petugasMarkers = {}, pasienMarkers = {};
    let myLat = null, myLng = null;
    let petugasData = [], pasienData = [];

    // ─── Konstanta ──────────────────────────────────────
    const USER_ID = <?= json_encode($userId) ?>;
    const API_PROXY = '../api_proxy.php';

    function getApiUrl(endpoint) { return API_PROXY + endpoint; }

    // ─── Inisialisasi Peta ──────────────────────────────
    function initMap(lat, lng) {
        if (map) return;
        map = L.map('map', {
            center: [lat, lng],
            zoom: 14,
            zoomControl: true,
            attributionControl: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> | SIVISIT',
        }).addTo(map);

        // My location marker
        myMarker = L.circleMarker([lat, lng], {
            radius: 10,
            fillColor: '#34C759',
            color: '#fff',
            weight: 3,
            opacity: 1,
            fillOpacity: 0.8,
        }).addTo(map).bindPopup('<strong>📍 Lokasi Saya</strong><br>Lat: ' + lat.toFixed(6) + '<br>Lng: ' + lng.toFixed(6));

        // Adjust map to container
        setTimeout(() => map.invalidateSize(), 300);
    }

    // ─── GPS Tracking ───────────────────────────────────
    function startTracking() {
        if (!navigator.geolocation) {
            setGpsStatus('GPS tidak didukung browser ini', false);
            return;
        }
        setGpsStatus('Mendapatkan lokasi...', true);
        trackingId = navigator.geolocation.watchPosition(
            function(pos) {
                myLat = pos.coords.latitude;
                myLng = pos.coords.longitude;
                const acc = pos.coords.accuracy;

                document.getElementById('coordDisplay').textContent =
                    myLat.toFixed(6) + ', ' + myLng.toFixed(6) + ' (±' + Math.round(acc) + 'm)';
                setGpsStatus('GPS aktif — akurasi ±' + Math.round(acc) + 'm', true);

                if (!map) {
                    initMap(myLat, myLng);
                    loadPetugas();
                    loadAllPatients();
                    return;
                }

                if (myMarker) {
                    myMarker.setLatLng([myLat, myLng]);
                    myMarker.setRadius(acc < 50 ? 10 : 8);
                }

                // Kirim lokasi ke server
                sendLocation(myLat, myLng, acc, pos.coords);
            },
            function(err) {
                console.warn('GPS error:', err.message);
                setGpsStatus('Gagal: ' + err.message, false);
                if (!map) initMap(-7.9666, 112.6326); // Default Malang
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
        );
        isTracking = true;
        document.getElementById('locateBtn').classList.add('tracking');
    }

    function stopTracking() {
        if (trackingId !== null) {
            navigator.geolocation.clearWatch(trackingId);
            trackingId = null;
        }
        isTracking = false;
        setGpsStatus('Pelacakan GPS dihentikan', false);
        document.getElementById('locateBtn').classList.remove('tracking');
    }

    function toggleTracking() {
        isTracking ? stopTracking() : startTracking();
    }

    function setGpsStatus(text, active) {
        document.getElementById('gpsText').textContent = text;
        const dot = document.getElementById('gpsDot');
        dot.className = 'pulse-dot' + (active ? '' : ' inactive');
    }

    // ─── Kirim Lokasi ke Server ─────────────────────────
    function sendLocation(lat, lng, accuracy, coords) {
        const data = {
            latitude: lat,
            longitude: lng,
            accuracy: accuracy || null,
            altitude: coords?.altitude || null,
            speed: coords?.speed || null,
            heading: coords?.heading || null,
            source: 'gps',
        };

        fetch(getApiUrl('/location/update'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + getToken(),
            },
            body: JSON.stringify(data),
        }).catch(() => {});
    }

    function getToken() {
        return <?= json_encode($_SESSION['api_token'] ?? '') ?>;
    }

    // ─── Load Petugas ───────────────────────────────────
    function loadPetugas() {
        fetch(getApiUrl('/location/petugas'), {
            headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + getToken() },
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            petugasData = res.data || [];
            updatePetugasMarkers();
            updatePetugasList();
            document.getElementById('onlineCount').textContent =
                petugasData.length + ' petugas online';
        })
        .catch(() => {});
    }

    function updatePetugasMarkers() {
        // Remove old markers
        Object.values(petugasMarkers).forEach(m => map?.removeLayer(m));
        petugasMarkers = {};

        petugasData.forEach(p => {
            const lat = parseFloat(p.latitude);
            const lng = parseFloat(p.longitude);
            if (isNaN(lat) || isNaN(lng)) return;

            const isMe = p.id == USER_ID;
            const icon = L.divIcon({
                className: '',
                html: `<div class="custom-marker-petugas" style="background:${isMe ? '#34C759' : '#007AFF'}">${p.name.charAt(0).toUpperCase()}</div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16],
            });

            const marker = L.marker([lat, lng], { icon }).addTo(map)
                .bindPopup(`
                    <strong>${isMe ? '👤 ' : ''}${p.name}</strong>
                    ${p.role ? '<br><small>' + p.role + '</small>' : ''}
                    ${p.last_location_at_diff ? '<br><small>🕐 ' + p.last_location_at_diff + '</small>' : ''}
                    <br><small>📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}</small>
                `);

            petugasMarkers[p.id] = marker;
        });
    }

    function updatePetugasList() {
        const list = document.getElementById('officerList');
        if (petugasData.length === 0) {
            list.innerHTML = '<div class="sv-empty-state" style="padding:16px 0;"><p style="font-size:13px;">Belum ada petugas online.</p></div>';
            return;
        }
        list.innerHTML = petugasData.map(p => {
            const isMe = p.id == USER_ID;
            const lat = parseFloat(p.latitude) || 0;
            const lng = parseFloat(p.longitude) || 0;
            return `
                <div class="loc-card mb-2" onclick="focusMarker(${p.id})" style="cursor:pointer;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:${isMe ? '#34C759' : '#007AFF'};display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:12px;flex-shrink:0;">
                            ${p.name.charAt(0).toUpperCase()}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div class="officer-name">${isMe ? '👤 ' : ''}${p.name}</div>
                            <div class="officer-time">${p.last_location_at_diff || 'baru saja'}</div>
                            <div class="officer-loc">${lat.toFixed(6)}, ${lng.toFixed(6)}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function focusMarker(id) {
        const m = petugasMarkers[id];
        if (m) {
            map.setView(m.getLatLng(), 16);
            m.openPopup();
        }
    }

    // ─── Load Pasien ────────────────────────────────────
    function loadAllPatients() {
        fetch(getApiUrl('/pasien'), {
            headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + getToken() },
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            pasienData = (res.data || []).filter(p => p.latitude && p.longitude);
            updatePasienMarkers();
        })
        .catch(() => {});
    }

    function updatePasienMarkers() {
        Object.values(pasienMarkers).forEach(m => map?.removeLayer(m));
        pasienMarkers = {};

        pasienData.forEach(p => {
            const lat = parseFloat(p.latitude);
            const lng = parseFloat(p.longitude);
            if (isNaN(lat) || isNaN(lng)) return;

            const icon = L.divIcon({
                className: '',
                html: `<div class="custom-marker-pasien">${p.patient_name.charAt(0).toUpperCase()}</div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14],
            });

            const popupContent = `
                <strong>🏠 ${p.patient_name}</strong>
                <small>${p.patient_id || ''}</small><br>
                ${p.address ? '<small>📍 ' + p.address + '</small><br>' : ''}
                ${p.patient_category ? '<small>📋 ' + p.patient_category + '</small>' : ''}
                <br><a href="detail-monitoring.php?patient_id=${encodeURIComponent(p.patient_id)}" target="_blank" style="font-size:12px;">🔍 Lihat Detail</a>
            `;

            const marker = L.marker([lat, lng], { icon }).addTo(map)
                .bindPopup(popupContent);

            pasienMarkers[p.patient_id] = marker;
        });
    }

    // ─── Cari Pasien Terdekat ───────────────────────────
    function findNearby() {
        const radius = document.getElementById('radiusInput').value || 5;
        if (!myLat || !myLng) {
            alert('Aktifkan GPS terlebih dahulu untuk mencari pasien terdekat.');
            return;
        }

        fetch(getApiUrl('/location/nearby?latitude=' + myLat + '&longitude=' + myLng + '&radius=' + radius), {
            headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + getToken() },
        })
        .then(r => r.json())
        .then(res => {
            const results = document.getElementById('nearbyResults');
            if (!res.success || !res.data || res.data.length === 0) {
                results.innerHTML = '<div style="padding:8px 0;font-size:13px;color:var(--sv-text-muted);">Tidak ada pasien dalam radius ' + radius + ' km.</div>';
                return;
            }

            // Update patient markers
            pasienData = res.data;
            updatePasienMarkers();

            results.innerHTML = '<div style="font-size:12px;font-weight:600;color:var(--sv-text-muted);margin-bottom:8px;">Ditemukan ' + res.data.length + ' pasien:</div>' +
                res.data.map(p => `
                    <div class="loc-card mb-1" style="padding:8px 10px;cursor:pointer;" onclick="focusPasien('${p.patient_id}')">
                        <div style="font-weight:600;font-size:13px;">${p.patient_name}</div>
                        <div style="font-size:11.5px;color:var(--sv-text-muted);">
                            📍 ${parseFloat(p.distance).toFixed(2)} km —
                            ${p.patient_category || '-'}
                        </div>
                    </div>
                `).join('') +
                '<div style="margin-top:8px;"><a href="pasien.php" style="font-size:12px;">🔍 Lihat semua pasien →</a></div>';
        })
        .catch(() => {
            document.getElementById('nearbyResults').innerHTML = '<div style="padding:8px 0;font-size:13px;color:var(--sv-red);">Gagal memuat data.</div>';
        });
    }

    function focusPasien(patientId) {
        const m = pasienMarkers[patientId];
        if (m) {
            map.setView(m.getLatLng(), 16);
            m.openPopup();
        }
    }

    // ─── Utility ────────────────────────────────────────
    function refreshAll() {
        loadPetugas();
        loadAllPatients();
    }

    function refreshPetugas() {
        loadPetugas();
    }

    function centerOnMyLocation() {
        if (myLat && myLng && map) {
            map.setView([myLat, myLng], 16);
        } else {
            startTracking();
        }
    }

    // ─── Auto Refresh ───────────────────────────────────
    setInterval(() => { if (isTracking) loadPetugas(); }, 30000);

    // ─── Init ───────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        // Fallback: if GPS not available, init map with default location
        setTimeout(() => {
            if (!map) {
                initMap(-7.9666, 112.6326);
                loadPetugas();
                loadAllPatients();
            }
        }, 3000);
        startTracking();

        // Global search
        document.getElementById('globalSearch').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && this.value.trim())
                window.location.href = 'cari-pasien.php?q=' + encodeURIComponent(this.value.trim());
        });
    });
</script>
</body>
</html>
