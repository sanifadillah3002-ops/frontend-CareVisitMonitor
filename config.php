<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-detect backend API URL
if (!defined('API_BASE_URL')) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    // Laravel artisan serve on port 8000 / 8080
    if (str_contains($host, '8080') || str_contains($host, '8000')) {
        define('API_BASE_URL', 'http://127.0.0.1:8000/api');
    // Production on usivisit.gt.tc / sivisit.gt.tc
    } elseif (str_contains($host, 'usivisit.gt.tc') || str_contains($host, 'sivisit.gt.tc')) {
        define('API_BASE_URL', 'https://sivisit.gt.tc/api');
    // InfinityFree subdomain
    } elseif (str_contains($host, 'infinityfreeapp.com') || str_contains($host, 'infinityfree')) {
        $parts = explode('.', $host);
        $subdomain = $parts[0] ?? '';
        define('API_BASE_URL', $protocol . '://' . $host . '/sivisit_CareVisitMonitor/public/api');
    // Local Laragon / XAMPP — project accessible from localhost
    } elseif (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
        define('API_BASE_URL', 'http://localhost/sivisit_CareVisitMonitor/public/api');
    // Fallback — try same host with path
    } else {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($basePath === '' || $basePath === '/') {
            define('API_BASE_URL', $protocol . '://' . $host . '/sivisit_CareVisitMonitor/public/api');
        } else {
            define('API_BASE_URL', $protocol . '://' . $host . $basePath . '/sivisit_CareVisitMonitor/public/api');
        }
    }
}

function initMockData()
{
    if (!isset($_SESSION['mock_patients'])) {
        $_SESSION['mock_patients'] = [
            [
                'patient_id' => 'RM-2026-0001',
                'patient_name' => 'Bpk. Slamet',
                'nik_dummy' => '3578010101010001',
                'datebirth' => '1955-08-14',
                'gender' => 'Male',
                'address' => 'Jl. Kerto Raharjo No. 12, Malang',
                'family_phone' => '081234567890',
                'patient_category' => 'Lansia',
            ],
            [
                'patient_id' => 'RM-2026-0002',
                'patient_name' => 'Ibu Aminah',
                'nik_dummy' => '3578010101010002',
                'datebirth' => '1961-04-20',
                'gender' => 'Female',
                'address' => 'Perumahan Pakis Indah Blok B-3, Malang',
                'family_phone' => '085678901234',
                'patient_category' => 'Hipertensi',
            ],
            [
                'patient_id' => 'RM-2026-0003',
                'patient_name' => 'Sdr. Rian',
                'nik_dummy' => '3578010101010003',
                'datebirth' => '2001-11-12',
                'gender' => 'Male',
                'address' => 'Kost Joyogrand Kav. 5, Malang',
                'family_phone' => '089912345678',
                'patient_category' => 'Lainnya',
            ],
        ];
    }

    if (!isset($_SESSION['mock_monitorings'])) {
        $_SESSION['mock_monitorings'] = [
            [
                'id' => 1,
                'patient_id' => 'RM-2026-0001',
                'user_id' => 1,
                'monitoring_date' => date('Y-m-d'),
                'monitoring_time' => '08:30:00',
                'blood_pressure' => '140/90',
                'body_temperature' => '36.8',
                'heart_rate' => 84,
                'respiratory_rate' => 18,
                'oxygen_saturation' => 97,
                'symptoms' => 'Kepala terasa agak pusing setelah bangun tidur',
                'notes' => 'Kurangi konsumsi makanan asin, istirahat cukup, minum obat antihipertensi teratur.',
                'status' => 'Need Control',
                'user' => ['name' => 'Ns. Budi Santoso'],
            ],
            [
                'id' => 2,
                'patient_id' => 'RM-2026-0002',
                'user_id' => 1,
                'monitoring_date' => date('Y-m-d', strtotime('-1 day')),
                'monitoring_time' => '10:00:00',
                'blood_pressure' => '120/80',
                'body_temperature' => '36.5',
                'heart_rate' => 78,
                'respiratory_rate' => 16,
                'oxygen_saturation' => 99,
                'symptoms' => 'Tidak ada keluhan berat, lemas berkurang',
                'notes' => 'Pertahankan diet seimbang, olahraga ringan jalan santai pagi.',
                'status' => 'Stable',
                'user' => ['name' => 'Ns. Budi Santoso'],
            ],
        ];
    }
}

function handleMockAPI($method, $endpoint, $data)
{
    initMockData();
    $method   = strtoupper($method);
    $parsed   = parse_url($endpoint);
    $path     = $parsed['path'] ?? '';
    
    if (strpos($path, '/api') === 0) {
        $path = substr($path, 4);
    }

    // Normalize /pasien to /patients for mock compatibility
    $originalPath = $path;
    $path = preg_replace('#^/pasien#', '/patients', $path);

    if ($path === '/login' && $method === 'POST') {
        return [
            'status_code' => 200,
            'response' => [
                'success' => true,
                'access_token' => 'mock_token_123',
                'user' => [
                    'id' => 1,
                    'name' => 'Ns. Budi Santoso',
                    'email' => $data['email'] ?? 'budi@carevisit.dev',
                    'role' => 'Petugas Kesehatan'
                ]
            ]
        ];
    }

    if ($path === '/patients') {
        if ($method === 'GET') {
            $patients = $_SESSION['mock_patients'];
            foreach ($patients as &$p) {
                $pMons = [];
                foreach ($_SESSION['mock_monitorings'] as $m) {
                    if ($m['patient_id'] === $p['patient_id']) {
                        $pMons[] = $m;
                    }
                }
                usort($pMons, fn($a, $b) => strtotime($b['monitoring_date'] . ' ' . ($b['monitoring_time'] ?? '00:00:00')) <=> strtotime($a['monitoring_date'] . ' ' . ($a['monitoring_time'] ?? '00:00:00')));
                $p['monitorings'] = $pMons;
            }
            return [
                'status_code' => 200,
                'response' => ['data' => $patients]
            ];
        }
        if ($method === 'POST') {
            $pid = !empty($data['patient_id']) ? $data['patient_id'] : 'RM-2026-' . sprintf('%04d', count($_SESSION['mock_patients']) + 1);
            $newPatient = [
                'patient_id' => $pid,
                'patient_name' => $data['patient_name'] ?? '',
                'nik_dummy' => $data['nik_dummy'] ?? '',
                'datebirth' => $data['datebirth'] ?? '',
                'gender' => $data['gender'] ?? 'Male',
                'address' => $data['address'] ?? '',
                'family_phone' => $data['family_phone'] ?? '',
                'patient_category' => $data['patient_category'] ?? 'Lainnya',
            ];
            $_SESSION['mock_patients'][] = $newPatient;
            return [
                'status_code' => 201,
                'response' => ['success' => true, 'data' => $newPatient]
            ];
        }
    }

    if (preg_match('#^/patients/([^/]+)/monitoring(s)?$#', $path, $matches)) {
        $query = urldecode($matches[1]);
        $foundPatient = null;
        foreach ($_SESSION['mock_patients'] as $p) {
            if ($p['patient_id'] === $query || $p['nik_dummy'] === $query) {
                $foundPatient = $p;
                break;
            }
        }
        if (!$foundPatient) {
            return ['status_code' => 404, 'response' => ['message' => 'Pasien tidak ditemukan.']];
        }
        
        $pMons = [];
        foreach ($_SESSION['mock_monitorings'] as $m) {
            if ($m['patient_id'] === $foundPatient['patient_id']) {
                $pMons[] = $m;
            }
        }
        foreach ($pMons as &$m) {
            if (!isset($m['user'])) $m['user'] = ['name' => 'Ns. Budi Santoso'];
        }
        
        return [
            'status_code' => 200,
            'response' => [
                'data' => array_merge($foundPatient, ['monitorings' => $pMons])
            ]
        ];
    }

    if (preg_match('#^/patients/([^/]+)$#', $path, $matches)) {
        $id = urldecode($matches[1]);
        $index = -1;
        foreach ($_SESSION['mock_patients'] as $idx => $p) {
            if ($p['patient_id'] === $id) {
                $index = $idx;
                break;
            }
        }
        
        if ($index === -1) {
            return ['status_code' => 404, 'response' => ['message' => 'Pasien tidak ditemukan.']];
        }
        
        if ($method === 'GET') {
            return [
                'status_code' => 200,
                'response' => ['data' => $_SESSION['mock_patients'][$index]]
            ];
        }
        
        if ($method === 'PUT' || $method === 'PATCH') {
            foreach ($data as $k => $v) {
                if (array_key_exists($k, $_SESSION['mock_patients'][$index])) {
                    $_SESSION['mock_patients'][$index][$k] = $v;
                }
            }
            return [
                'status_code' => 200,
                'response' => ['success' => true, 'data' => $_SESSION['mock_patients'][$index]]
            ];
        }
        
        if ($method === 'DELETE') {
            unset($_SESSION['mock_patients'][$index]);
            $_SESSION['mock_patients'] = array_values($_SESSION['mock_patients']);
            $_SESSION['mock_monitorings'] = array_values(array_filter($_SESSION['mock_monitorings'], fn($m) => $m['patient_id'] !== $id));
            return [
                'status_code' => 200,
                'response' => ['success' => true]
            ];
        }
    }

    // ─── Location Endpoints ────────────────────────────────────
    if ($path === '/location/petugas' && $method === 'GET') {
        $mockLocations = [
            [
                'id' => 1,
                'name' => 'Ns. Budi Santoso',
                'role' => 'Petugas Kesehatan',
                'latitude' => '-7.966620',
                'longitude' => '112.632630',
                'last_location_at' => date('Y-m-d H:i:s'),
                'last_location_at_diff' => 'baru saja',
                'location' => 'Puskesmas Dinoyo, Malang',
            ],
            [
                'id' => 2,
                'name' => 'Ns. Siti Rahmawati',
                'role' => 'Perawat Home Care',
                'latitude' => '-7.975000',
                'longitude' => '112.640000',
                'last_location_at' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
                'last_location_at_diff' => '5 menit yang lalu',
                'location' => 'Jl. Veteran, Malang',
            ],
        ];
        // Hapus current user from mock if exists
        $currentUserId = $_SESSION['user']['id'] ?? null;
        if ($currentUserId) {
            $mockLocations = array_filter($mockLocations, fn($l) => $l['id'] !== $currentUserId);
        }
        return [
            'status_code' => 200,
            'response' => [
                'success' => true,
                'message' => 'Lokasi petugas berhasil diambil.',
                'data' => array_values($mockLocations),
            ]
        ];
    }

    if ($path === '/location/update' && $method === 'POST') {
        return [
            'status_code' => 200,
            'response' => [
                'success' => true,
                'message' => 'Lokasi berhasil diperbarui.',
                'data' => [
                    'latitude' => $data['latitude'] ?? 0,
                    'longitude' => $data['longitude'] ?? 0,
                    'recorded_at' => date('Y-m-d H:i:s'),
                ]
            ]
        ];
    }

    if ($path === '/location/nearby' && $method === 'GET') {
        $mockNearby = [];
        foreach ($_SESSION['mock_patients'] as $p) {
            $lat = $data['latitude'] ?? -7.9666;
            $lng = $data['longitude'] ?? 112.6326;
            $mockLat = $lat + (mt_rand(-100, 100) / 1000);
            $mockLng = $lng + (mt_rand(-100, 100) / 1000);
            $distance = round(sqrt(pow(($mockLat - $lat) * 111, 2) + pow(($mockLng - $lng) * 111 * cos(deg2rad($lat)), 2)), 2);
            if ($distance <= ($data['radius'] ?? 5)) {
                $p['latitude'] = $mockLat;
                $p['longitude'] = $mockLng;
                $p['distance'] = $distance;
                $mockNearby[] = $p;
            }
        }
        usort($mockNearby, fn($a, $b) => $a['distance'] <=> $b['distance']);
        return [
            'status_code' => 200,
            'response' => [
                'success' => true,
                'message' => 'Pasien dalam radius berhasil diambil.',
                'data' => $mockNearby,
            ]
        ];
    }

    if ($path === '/monitorings' || $path === '/monitoring') {
        if ($method === 'GET') {
            $monitorings = $_SESSION['mock_monitorings'];
            foreach ($monitorings as &$m) {
                $m['patient'] = null;
                foreach ($_SESSION['mock_patients'] as $p) {
                    if ($p['patient_id'] === $m['patient_id']) {
                        $m['patient'] = $p;
                        break;
                    }
                }
            }
            return [
                'status_code' => 200,
                'response' => ['data' => $monitorings]
            ];
        }
        if ($method === 'POST') {
            $newMon = [
                'id' => count($_SESSION['mock_monitorings']) + 1,
                'patient_id' => $data['patient_id'] ?? '',
                'user_id' => 1,
                'monitoring_date' => $data['monitoring_date'] ?? date('Y-m-d'),
                'monitoring_time' => $data['monitoring_time'] ?? date('H:i:s'),
                'blood_pressure' => $data['blood_pressure'] ?? '',
                'body_temperature' => $data['body_temperature'] ?? '',
                'heart_rate' => $data['heart_rate'] ?? '',
                'respiratory_rate' => $data['respiratory_rate'] ?? '',
                'oxygen_saturation' => $data['oxygen_saturation'] ?? '',
                'symptoms' => $data['symptoms'] ?? '',
                'notes' => $data['notes'] ?? '',
                'status' => $data['status'] ?? 'Stable',
                'user' => ['name' => $_SESSION['user']['name'] ?? 'Ns. Budi Santoso']
            ];
            $_SESSION['mock_monitorings'][] = $newMon;
            return [
                'status_code' => 201,
                'response' => ['success' => true, 'data' => $newMon]
            ];
        }
    }

    return [
        'status_code' => 404,
        'response' => ['message' => 'Endpoint mock tidak ditemukan.']
    ];
}

function callAPI($method, $endpoint, $data = false)
{
    $curl = curl_init();
    $url = API_BASE_URL . $endpoint;

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    if (isset($_SESSION['api_token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['api_token'];
    }

    switch (strtoupper($method)) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case "PATCH":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case "DELETE":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        default:
            if ($data) {
                $url = sprintf("%s?%s", $url, http_build_query($data));
            }
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($curl);

    if ($result === false) {
        curl_close($curl);
        return handleMockAPI($method, $endpoint, $data);
    }

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode === 0 || $httpCode >= 500) {
        return handleMockAPI($method, $endpoint, $data);
    }

    return [
        'status_code' => $httpCode,
        'response' => json_decode($result, true)
    ];
}
