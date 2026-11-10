# Panduan Menjalankan & Onboarding Project BigSIRS

Dokumentasi resmi ini menjelaskan arsitektur terbaru **BigSIRS** serta panduan langkah demi langkah untuk menjalankan project secara lokal dari awal (*zero to running*), baik menggunakan **Full Docker Compose** (direkomendasikan) maupun **Mode Hybrid** (MySQL di Docker, Laravel & Vite di host).

> [!IMPORTANT]
> **Pembaruan Arsitektur Terbaru (Direct Legacy DB Access):**  
> BigSIRS saat ini sudah terhubung **langsung** ke database asli SIMRS (`simrs_legacy`) untuk seluruh modul **Master Data**, **Lookups**, dan **Logistik Farmasi** (`USE_DIRECT_LEGACY=true` secara default). Setelah database `simrs_legacy` di-restore, **Anda tidak lagi wajib menjalankan perintah sinkronisasi `php artisan legacy:import-all`**.

---

## Daftar Isi
1. [Arsitektur Layanan, Port & Status Modul](#1-arsitektur-layanan-port--status-modul)
2. [Prasyarat Sistem (Prerequisites)](#2-prasyarat-sistem-prerequisites)
3. [Langkah 1: Persiapan Environment File (.env)](#langkah-1-persiapan-environment-file-env)
4. [Langkah 2: Menjalankan Container (Pilih Opsi A atau B)](#langkah-2-menjalankan-container-pilih-opsi-a-atau-b)
5. [Langkah 3: Restore Database `simrs_legacy` & Migrasi `bigsirs_dev`](#langkah-3-restore-database-simrs_legacy--migrasi-bigsirs_dev)
6. [Langkah 4: Menjalankan Server & Login Aplikasi](#langkah-4-menjalankan-server--login-aplikasi)
7. [Pengujian (Test), Lint & Reset Cache](#pengujian-test-lint--reset-cache)
8. [Mode Opsional: Sinkronisasi Manual ke `bigsirs_dev`](#mode-opsional-sinkronisasi-manual-ke-bigsirs_dev)
9. [Rangkuman Troubleshooting Cepat](#rangkuman-troubleshooting-cepat)

---

## 1. Arsitektur Layanan, Port & Status Modul

### Port & Komponen Aplikasi
| Komponen | Service Compose | Teknologi / Image | Port Host | Akses / Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **Frontend Web** | `frontend` | Vue 3 + Vite 8 (`node:24-alpine`) | `5173` | [http://localhost:5173](http://localhost:5173) |
| **Backend API** | `backend` | Laravel 13 (`php:8.5.12-cli`) | `8000` | [http://127.0.0.1:8000](http://127.0.0.1:8000) (Prefix: `/api/v1`) |
| **Database** | `mysql` (`bigsirs-mysql`) | MySQL 8.4 | `3307` | Host `127.0.0.1:3307` (`bigsirs_dev` & `simrs_legacy`) |

### Peran Kedua Database
* **`simrs_legacy` (Koneksi `legacy`)**: Database utama SIMRS asli yang diakses langsung (baca & tulis) oleh **Master Data**, **Lookups**, dan **Logistik Farmasi** (`Faktur`, `Mutasi`, `Stok Opname`, `Pengeluaran Barang`, `Penjualan Bebas`).
* **`bigsirs_dev` (Koneksi default `mysql`)**: Database aplikasi Laravel baru untuk autentikasi (`users`), token Sanctum (`personal_access_tokens`), `migrations`, `cache`, serta tabel fallback bila mode direct legacy dimatikan.

### Ringkasan Status Modul Saat Ini
| Grup Menu | Status Koneksi Data |
| :--- | :--- |
| **Master Data** (Poliklinik, Data Dasar, Dokter, Pegawai, Tindakan, Lab, Radiologi, Obat/Logistik, Bed, ICD-10, ICD-9-CM, Penanganan Lanjutan) | Terhubung langsung ke `simrs_legacy` *(kecuali **Ketersediaan Bed SIRS** yang masih prototipe UI)*. |
| **Logistik Farmasi** (Faktur, Mutasi, Stok Opname, Pengeluaran Barang, Penjualan Bebas) | Terhubung langsung ke `simrs_legacy` (`lgs_faktur`, `lgs_mutasi`, `lgs_log_stok`, `lgs_stok_opname`, `lgs_pengeluaran`). |
| **Pelayanan Klinis** (Dashboard, Pendaftaran, Admisi, Pemeriksaan, Billing Rawat Jalan) | Masih menggunakan data mock / prototipe frontend. |
| **Laporan & Pengaturan** | Dinonaktifkan sementara di sidebar (`disabled`). |

---

## 2. Prasyarat Sistem (Prerequisites)

* **Wajib (Semua Mode):**
  * **Git** versi terbaru
  * **Docker Desktop** (pastikan daemon Docker sudah dalam status **Running**)
  * File dump database legacy (`backups/backup_db_simrs_masking.sql` atau dump SQL `simrs_legacy` terbaru dari tim)
* **Tambahan hanya jika memakai Opsi B (menjalankan Backend/Frontend di Host tanpa container):**
  * **PHP `^8.5`** (ekstensi: `pdo_mysql`, `intl`, `zip`, `bcmath`, `pcntl`, `mbstring`, `openssl`)
  * **Composer v2.x**
  * **Node.js `^22.18.0` atau `>=24.12.0`** & **npm**

---

## Langkah 1: Persiapan Environment File (`.env`)

> [!WARNING]
> **Format Encoding `.env` (Penting di Windows!):**  
> Seluruh file `.env` wajib disimpan dalam format **UTF-8 (without BOM)**. Jangan memakai redirect PowerShell (`>` atau `>>`) tanpa `-Encoding utf8` karena menghasilkan UTF-16 null-byte (`Encountered an invalid name at [L\000E...]`).  
> Khusus `backend/.env.example`, bagian bawahnya memiliki sisa karakter UTF-16 pada komentar legacy sehingga disarankan memeriksa/merapikan bagian bawah `backend/.env` setelah menyalinnya.

### A. Konfigurasi Docker di Root Folder Project
Buat folder `backups` (taruh file dump SQL `simrs_legacy` di sini), lalu salin `.env.docker.example` menjadi **`.env.docker` dan `.env`** (di root folder, agar perintah `docker compose` tanpa `--env-file` tetap membaca `MYSQL_VOLUME_NAME` dengan benar):

```powershell
# Windows PowerShell
if (-not (Test-Path backups)) { New-Item -ItemType Directory -Path backups }
Copy-Item .env.docker.example .env.docker
Copy-Item .env.docker.example .env
```

```bash
# Linux / macOS / Git Bash
mkdir -p backups
cp .env.docker.example .env.docker
cp .env.docker.example .env
```

Isi standar `.env.docker` & `.env` root:
```env
MYSQL_DATABASE=bigsirs_dev
MYSQL_USER=bigsirs_user
MYSQL_PASSWORD=change_me
MYSQL_ROOT_PASSWORD=change_root_password
MYSQL_VOLUME_NAME=bigsirs_mysql_data
```

### B. Konfigurasi Backend (`backend/.env`)
Salin template ke `backend/.env`:

```powershell
# Windows PowerShell
Copy-Item backend\.env.example backend\.env

# Linux / macOS
cp backend/.env.example backend/.env
```

Buka `backend/.env` dan pastikan bagian penting berikut tertulis bersih dalam UTF-8:

```env
APP_NAME=Laravel
APP_ENV=local
DEV_ADMIN_USERNAME=admin
DEV_ADMIN_PASSWORD=12345
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Koneksi Database Baru (untuk akses dari host gunakan 127.0.0.1:3307)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=bigsirs_dev
DB_USERNAME=root
DB_PASSWORD=change_root_password

# Koneksi Database Legacy SIMRS (Direct Access)
USE_DIRECT_LEGACY=true
LEGACY_DB_CONNECTION=mysql
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3307
LEGACY_DB_DATABASE=simrs_legacy
LEGACY_DB_USERNAME=root
LEGACY_DB_PASSWORD=change_root_password
```

> [!NOTE]
> Jika menjalankan **Opsi A (Full Docker)**, Anda tetap memakai nilai `127.0.0.1:3307` di `backend/.env` (berguna saat menjalankan perintah dari host). Di dalam container `backend`, `docker-compose.yml` otomatis menimpa `DB_HOST=mysql`, `DB_PORT=3306`, `LEGACY_DB_HOST=mysql`, dan `LEGACY_DB_PORT=3306`.

### C. Konfigurasi Frontend (`frontend/.env`)
Salin template ke `frontend/.env`:

```powershell
# Windows PowerShell
Copy-Item frontend\.env.example frontend\.env

# Linux / macOS
cp frontend/.env.example frontend/.env
```

Pastikan isi `frontend/.env`:
```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api/v1
VITE_USE_DASHBOARD_MOCK=true
```

---

## Langkah 2: Menjalankan Container (Pilih Opsi A atau B)

Buat volume persistent MySQL terlebih dahulu (wajib karena `docker-compose.yml` menandainya sebagai `external: true`):

```powershell
docker volume create bigsirs_mysql_data
```

### Opsi A: Full Docker Compose (Direkomendasikan)
Menjalankan sekaligus container `mysql`, `backend` (Laravel 13 di port `8000`), dan `frontend` (Vite di port `5173`) tanpa perlu menginstal PHP 8.5 atau Node 24 di komputer lokal:

```powershell
docker compose up -d --build
```

Verifikasi bahwa seluruh layanan berstatus **Up** dan `bigsirs-mysql` sudah **healthy**:

```powershell
docker compose ps
```

### Opsi B: Mode Hybrid (MySQL di Docker, Backend & Frontend di Host)
Gunakan opsi ini jika Anda ingin menjalankan `php artisan serve` dan `npm run dev` langsung di terminal lokal tanpa container backend/frontend (mencegah benturan port `8000` dan `5173`):

```powershell
docker compose up -d mysql
```

Setelah `bigsirs-mysql` berstatus **Up (healthy)**, pasang dependensi lokal di masing-masing folder:

```powershell
# Install dependensi Backend
cd backend
composer install
cd ..

# Install dependensi Frontend
cd frontend
npm install
cd ..
```

---

## Langkah 3: Restore Database `simrs_legacy` & Migrasi `bigsirs_dev`

### 1. Restore Database Asli (`simrs_legacy`)
Pastikan file SQL dump asli sudah diletakkan di folder `backups/` (misalnya `backups/backup_db_simrs_masking.sql`), lalu jalankan dari **root direktori project**:

```powershell
# a. Buat database simrs_legacy
docker exec -i bigsirs-mysql mysql -uroot -pchange_root_password -e "CREATE DATABASE IF NOT EXISTS simrs_legacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# b. Restore file dump SQL ke simrs_legacy
docker exec -i bigsirs-mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" simrs_legacy < /backups/backup_db_simrs_masking.sql'
```
*(Proses restore biasanya memakan waktu sekitar 10–25 detik tergantung spesifikasi mesin).*

### 2. Inisialisasi Skema `bigsirs_dev` & Akun Admin
Jalankan sesuai mode yang Anda pilih pada Langkah 2:

**Jika memakai Opsi A (Full Docker):**
```powershell
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate
docker compose exec backend php artisan db:seed --class=AdminDevelopmentSeeder
docker compose exec backend php artisan optimize:clear
```

**Jika memakai Opsi B (Lokal di Host):**
```powershell
cd backend
php artisan key:generate
php artisan migrate
php artisan db:seed --class=AdminDevelopmentSeeder
php artisan optimize:clear
cd ..
```

*Output sukses seeder:* `Admin user seeded successfully.`

> [!TIP]
> Tidak perlu menjalankan `php artisan legacy:import-all`. Begitu `simrs_legacy` selesai di-restore, seluruh modul Master Data dan Logistik Farmasi otomatis membaca/menulis langsung ke tabel asli di `simrs_legacy`.

---

## Langkah 4: Menjalankan Server & Login Aplikasi

### Menjalankan Server
* **Jika memakai Opsi A (Full Docker):**  
  Container `backend` dan `frontend` sudah langsung berjalan otomatis setelah `docker compose up -d`.
  *(Jika sebelumnya Anda baru menjalankan `key:generate`, cukup lanjut akses aplikasi atau jalankan `docker compose restart backend` bila konfigurasi belum terbaca).*
* **Jika memakai Opsi B (Lokal di Host):**  
  Buka **2 terminal terpisah** dari root project:
  ```powershell
  # Terminal 1 - Backend API (port 8000)
  cd backend
  php artisan serve
  ```
  ```powershell
  # Terminal 2 - Frontend Web (port 5173)
  cd frontend
  npm run dev
  ```

### Akses & Kredensial Login
Buka browser ke **[http://localhost:5173](http://localhost:5173)**:

* **Username:** `admin`
* **Password:** `12345`
* **Security Code / CAPTCHA:** Masukkan 5 karakter CAPTCHA yang tampil di layar.

> [!NOTE]
> Username `admin` pada `bigsirs_dev.users` otomatis dipetakan ke `simrs_legacy.tbl_user` (`id = 103`) saat aplikasi mencatat kolom audit `id_user` ke database asli. Jika membuat user baru di `bigsirs_dev.users`, pastikan `username`-nya juga terdaftar di `simrs_legacy.tbl_user`.

---

## Pengujian (Test), Lint & Reset Cache

### Menjalankan Automated Test & Lint
```powershell
# Backend PHPUnit / Laravel Test (Opsi A - Docker)
docker compose exec backend php artisan test

# Backend Test (Opsi B - Host)
cd backend
php artisan test

# Frontend Unit Test, Lint & Build (Opsi A - Docker)
docker compose exec frontend npm run test:unit -- --run
docker compose exec frontend npm run lint
docker compose exec frontend npm run build

# Frontend (Opsi B - Host)
cd frontend
npm run test:unit -- --run
npm run lint
npm run build
```

### Protokol Reset Cache Saat Update Kode / `.env`
1. **Hard Refresh Browser:** `Ctrl + Shift + R` (Windows/Linux) atau `Cmd + Shift + R` (macOS).
2. **Bersihkan Cache Config Laravel:**
   ```powershell
   # Docker
   docker compose exec backend php artisan optimize:clear

   # Host
   cd backend; php artisan optimize:clear
   ```
3. **Jika ada perubahan `composer.json` / `package.json` di mode Docker:**  
   Rebuild image agar named volume (`backend_vendor` / `frontend_node_modules`) ikut diperbarui:
   ```powershell
   docker compose up -d --build
   ```

---

## Mode Opsional: Sinkronisasi Manual ke `bigsirs_dev`

Bagian ini **hanya diperlukan** jika Anda sengaja mematikan akses langsung (`USE_DIRECT_LEGACY=false` di `backend/.env`) dan ingin menyalin data master dari `simrs_legacy` ke `bigsirs_dev`:

<details>
<summary><b>Klik untuk melihat perintah import legacy ke bigsirs_dev (tidak perlu untuk mode Direct Legacy)</b></summary>

Jalankan di dalam container (`docker compose exec backend ...`) atau folder `backend/`:

```powershell
# Impor semua master data kecuali kelurahan (~75.000+ baris)
php artisan legacy:import-all

# Atau impor kelurahan saja / seluruhnya termasuk kelurahan
php artisan legacy:import-villages
php artisan legacy:import-all --with-villages
```

</details>

---

## Rangkuman Troubleshooting Cepat

| Gejala / Pesan Error | Penyebab | Solusi |
| :--- | :--- | :--- |
| `The "MYSQL_VOLUME_NAME" variable is not set` | File `.env` di root folder belum ada saat menjalankan `docker compose` | Salin `.env.docker.example` ke `.env` dan `.env.docker` di root, lalu buat volume `docker volume create bigsirs_mysql_data`. |
| `Conflict. The container name "/bigsirs-mysql" is already in use` | Container lama tertinggal di Docker | Jalankan `docker rm -f bigsirs-mysql` lalu jalankan `docker compose up -d`. |
| URL frontend terbuka di `http://localhost:5174` atau port `8000` bentrok | Container `frontend`/`backend` di Docker masih menyala sementara Anda juga menjalankan `npm run dev` / `php artisan serve` di host | Pilih salah satu mode: hentikan container (`docker compose stop backend frontend`) jika ingin run di host, atau akses container langsung di port `5173`/`8000`. |
| `Failed to parse dotenv file. Encountered an invalid name at [L\000E...]` | File `backend/.env` mengandung karakter UTF-16 null-byte (terutama sisa di bawah `backend/.env.example`) | Pastikan `backend/.env` disimpan ulang dalam format **UTF-8 without BOM** sesuai contoh pada Langkah 1B. |
| `SQLSTATE[HY000] [1045] Access denied` | Password MySQL di `backend/.env` tidak cocok dengan `.env.docker` | Pastikan `DB_PASSWORD` / `LEGACY_DB_PASSWORD` sesuai dengan `MYSQL_ROOT_PASSWORD` di `.env.docker`, lalu jalankan `php artisan optimize:clear`. |
| `SQLSTATE[HY000] [1049] Unknown database 'simrs_legacy'` | Database `simrs_legacy` belum dibuat/di-restore di `bigsirs-mysql` | Jalankan perintah `CREATE DATABASE` dan restore SQL pada Langkah 3 (Bagian 1). |
| `Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.` | `username` yang login di `bigsirs_dev.users` belum ada di `simrs_legacy.tbl_user` | Pastikan login memakai akun `admin` (`AdminDevelopmentSeeder`) atau pastikan `username` tersedia dan aktif di `simrs_legacy.tbl_user`. |
| `POST http://localhost:8000/api/auth/login 404` | `VITE_API_BASE_URL` di `frontend/.env` belum menyertakan `/api/v1` | Ubah ke `VITE_API_BASE_URL=http://127.0.0.1:8000/api/v1` lalu restart server frontend. |
| `Data dashboard belum terhubung` / Dashboard kosong | `VITE_USE_DASHBOARD_MOCK` masih `false` di `frontend/.env` | Set `VITE_USE_DASHBOARD_MOCK=true` di `frontend/.env` (pada mode Docker compose nilai ini sudah otomatis `true`). |
| Class/paket Composer atau npm baru tidak ditemukan di Docker | Named volume `backend_vendor` atau `frontend_node_modules` masih menyimpan versi lama | Jalankan `docker compose up -d --build` atau `docker compose exec backend composer install` / `docker compose exec frontend npm ci`. |
