# Panduan Deploy / Konfigurasi Maps — SIVISIT CareVisit Monitor

## Ringkasan

SIVISIT menggunakan **Leaflet.js + OpenStreetMap (Nominatim)** — solusi peta **100% gratis**, tanpa API key, tanpa biaya berlangganan. Fitur peta mencakup:

- **Lokasi Pasien** — Geocoding alamat pasien ke koordinat
- **Monitoring Petugas** — GPS real-time petugas home care
- **Pasien Terdekat** — Cari pasien dalam radius tertentu

---

## 1. Sumber Daya Peta (CDN) — WAJIB AKTIF

### Leaflet CSS
```html
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
```

### Leaflet JS
```html
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
```

### OpenStreetMap Tile Layer (gratis)
```js
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> | SIVISIT'
}).addTo(map);
```

### Nominatim Geocoding API (gratis)
```
https://nominatim.openstreetmap.org/search?format=json&q={ADDRESS}&limit=1
```

---

## 2. Persyaratan Perangkat & Browser

### ✅ Didukung (dengan GPS real-time):
| Perangkat | Browser | GPS |
|-----------|---------|-----|
| iOS Safari 12+ | Safari, Chrome | ✅ |
| Android Chrome 80+ | Chrome, Firefox | ✅ |
| Windows 10/11 (Laptop) | Chrome, Edge, Firefox | ❌ (fallback lokasi manual) |
| macOS (Laptop) | Safari, Chrome | ❌ (fallback geocoding alamat) |
| Linux (Desktop) | Chrome, Firefox | ❌ (fallback geocoding alamat) |

> **Catatan:** GPS hardware hanya tersedia di perangkat mobile (HP/tablet). Di laptop/desktop, lokasi diperkirakan berdasarkan alamat IP (kurang akurat). Untuk akurasi maksimal di perangkat non-mobile, gunakan fitur **"Deteksi lokasi dari alamat"** (geocoding).

### ⚙️ Pengaturan Browser yang Diperlukan:
1. **Aktifkan Lokasi (Location Access):**
   - Chrome: `Settings → Privacy and Security → Site Settings → Location` → Set ke **Allow**
   - Firefox: `Preferences → Privacy & Security → Permissions → Location` → Set ke **Allow**
   - Safari: `Preferences → Websites → Location` → Set ke **Allow**
   - Edge: `Settings → Cookies and site permissions → Location` → Set ke **Allow**

2. **Non-aktifkan Ad Blocker / Tracker Blocker:**
   - uBlock Origin, Ghostery, dll. dapat memblokir request ke `tile.openstreetmap.org` dan `nominatim.openstreetmap.org`
   - Cara: Nonaktifkan extension atau whitelist domain:
     - `*.tile.openstreetmap.org`
     - `nominatim.openstreetmap.org`
     - `unpkg.com`

3. **HTTPS Wajib untuk GPS:**
   - Browser **WAJIB HTTPS** untuk mengakses GPS (kecuali `localhost`).
   - Pastikan frontend (`usivisit.gt.tc`) menggunakan **HTTPS**.
   - Fallback: Di `localhost` saat development, GPS tetap berfungsi tanpa HTTPS.

---

## 3. Konfigurasi Domain

### Frontend → Backend Communication
| Domain | Peran | HTTPS |
|--------|-------|-------|
| `usivisit.gt.tc` | Frontend PHP Native | ✅ |
| `sivisit.gt.tc` | Backend Laravel API | ✅ |

### Frontend menggunakan dua metode komunikasi:
1. **PHP cURL (Server-side):** Untuk form submit, load data awal.
   - Konfigurasi di `config.php` (point API ke `https://sivisit.gt.tc/api/`)
2. **JavaScript Fetch (Client-side):** Untuk lokasi real-time, GPS tracking.
   - Via `api_proxy.php` yang akan meneruskan ke Laravel backend.

Konfigurasi `config.php` sudah auto-detect berdasarkan HTTP_HOST:

```php
// Jika domain = usivisit.gt.tc atau sivisit.gt.tc
define('API_BASE_URL', 'https://sivisit.gt.tc/api');
```

> **Pastikan domain backend `sivisit.gt.tc` sudah memiliki CORS policy yang mengizinkan request dari `usivisit.gt.tc`.**

---

## 4. Keamanan & Best Practice

### Rate Limiting Nominatim
Nominatim gratis memiliki batas **1 request per detik**. Jika banyak pasien, koordinat akan di-geocode satu per satu. Gunakan fitur:
- **Penyimpanan koordinat** — Setelah geocode berhasil, simpan `latitude`/`longitude` di database (tidak perlu geocode ulang setiap load halaman).

### CORS Backend (Laravel)
Pastikan middleware CORS di Laravel mengizinkan frontend domain:

```php
// Di App/Http/Middleware/CorsMiddleware.php atau config/cors.php
'allow_origins' => ['https://usivisit.gt.tc', 'http://localhost'],
'allow_headers' => ['Content-Type', 'Authorization', 'Accept'],
'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
```

### API Rate Limiting
Laravel secara default membatasi 60 request/menit untuk API. Untuk endpoint lokasi yang sering dipanggil, pertimbangkan:
- Menambah limit di `App\Providers\RouteServiceProvider`:
```php
RateLimiter::for('api', fn () => Limit::perMinute(120));
```

### Session & Token
- Semua endpoint lokasi menggunakan **auth:sanctum** middleware.
- Frontend menyimpan token di `$_SESSION['api_token']`.
- JavaScript mengambil token dari PHP `json_encode` dan mengirim via header `Authorization: Bearer`.

---

## 5. Troubleshooting

### ❌ Peta tidak muncul / Putih
- Periksa console browser (F12) untuk error JavaScript
- Pastikan CDN Leaflet tidak diblokir (ad blocker / firewall)
- Coba gunakan alternatif CDN:
  ```html
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
  ```

### ❌ GPS tidak berfungsi
- Pastikan browser mendukung Geolocation API (Chrome 80+, Safari 12+, Firefox 70+)
- Periksa pengaturan izin lokasi browser (Allow)
- Di Chrome: klik icon 🔒 di address bar → Site Settings → Location → Allow
- Pastikan koneksi menggunakan **HTTPS** (atau `localhost`)
- Coba restart browser setelah mengubah pengaturan

### ❌ Alamat pasien tidak muncul di peta
- Pastikan alamat ditulis lengkap (sertakan kota, kecamatan, provinsi)
- Contoh: "Jl. Kerto Raharjo No. 12, Malang, Jawa Timur"
- Nama jalan yang tidak terdaftar di OpenStreetMap mungkin tidak ditemukan
- Gunakan Google Maps terlebih dahulu untuk mencari koordinat, lalu input manual

### ❌ Api proxy error (401/403)
- Login kembali ke sistem (token mungkin expired)
- Periksa koneksi frontend-backend: `config.php` sudah mendeteksi domain dengan benar
- Cek log Laravel: `storage/logs/laravel.log`

### ❌ Error CORS
- Pastikan backend menerima request dari frontend domain
- Tes dengan curl:
  ```bash
  curl -H "Origin: https://usivisit.gt.tc" -H "Access-Control-Request-Method: GET" -X OPTIONS https://sivisit.gt.tc/api/pasien -v
  ```
- Jika gagal, periksa CorsMiddleware atau `config/cors.php` di Laravel

---

## 6. Pengembangan Lokal (Localhost)

### Laragon
Backend Laravel:
```bash
cd C:\laragon\www\sivisit_CareVisitMonitor
php artisan migrate
php artisan serve --port=8000
```

Frontend PHP Native:
- Akses via `http://localhost/frontend-CareVisitMonitor/Pages/dashboard.php`
- Atau buat alias Virtual Host: `usivisit.test` → `C:\laragon\www\frontend-CareVisitMonitor`
- `config.php` sudah auto-detect localhost → `http://localhost/sivisit_CareVisitMonitor/public/api`

### XAMPP
Sama seperti Laragon. Pastikan `mod_rewrite` aktif untuk Laravel.

---

## 7. Daftar File yang Dimodifikasi

### Backend (Laravel) — `sivisit_CareVisitMonitor/`
| File | Perubahan |
|------|-----------|
| `routes/api.php` | + login, register, logout, pasien CRUD, monitoring index, location routes |
| `app/Models/Patient.php` | + fillable: latitude, longitude |
| `app/Models/User.php` | + fillable: latitude, longitude, last_location_at |
| `app/Models/LocationLog.php` | Baru |
| `app/Http/Controllers/Api/LocationController.php` | Baru |
| `app/Http/Controllers/Api/PatientController.php` | + store, update, destroy |
| `app/Http/Controllers/Api/MonitoringController.php` | + index |
| `database/migrations/2026_06_24_000001_add_location_fields_to_patients.php` | Baru |
| `database/migrations/2026_06_24_000002_add_location_fields_to_users.php` | Baru |
| `database/migrations/2026_06_24_000003_create_location_logs_table.php` | Baru |

### Frontend (PHP Native) — `frontend-CareVisitMonitor/`
| File | Perubahan |
|------|-----------|
| `config.php` | + mock location endpoints, /pasien → /patients normalization |
| `api_proxy.php` | Baru (proxy JS fetch ke backend) |
| `Pages/lokasi-petugas.php` | Baru (halaman monitoring lokasi dengan Leaflet map) |
| `Pages/detail-monitoring.php` | + Leaflet map lokasi pasien (geocoding dari alamat) |
| `Pages/tambah-pasien.php` | + hidden lat/lng, geocoding button, GPS lokasi saya, mini map |
| `Pages/edit-pasien.php` | + hidden lat/lng, geocoding button, mini map |
| `Pages/components/sidebar.php` | + menu "Monitoring Lokasi" |
| `Pages/monitoring.php` | + Fix CSS path |
| `Pages/pasien.php` | + Fix CSS path |

---

## 8. Perintah Deploy

```bash
# 1. Jalankan migrasi database (Backend Laravel)
cd /path/to/sivisit_CareVisitMonitor
php artisan migrate

# 2. Optimasi Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Set permission storage (Linux)
chmod -R 775 storage bootstrap/cache

# 4. Konfigurasi Virtual Host Frontend (Apache/Nginx)
# Frontend: usivisit.gt.tc → /path/to/frontend-CareVisitMonitor

# 5. Test
# Buka https://usivisit.gt.tc/ → login → buka menu "Monitoring Lokasi"
```

---

## 9. Lisensi Peta

- **Leaflet** — BSD 2-Clause License (gratis untuk penggunaan apapun)
- **OpenStreetMap tiles** — Open Database License (ODbL) — gratis, dengan atribusi
- **Nominatim** — gratis untuk penggunaan wajar (max 1 req/detik, wajib sertakan User-Agent)

Tidak ada biaya lisensi atau API key yang diperlukan untuk fitur peta SIVISIT.
