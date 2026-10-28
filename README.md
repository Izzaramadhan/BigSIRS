# Panduan Onboarding & Setup Lokal BigSIRS Rebuild

Dokumentasi resmi ini ditujukan bagi pengembang yang baru pertama kali melakukan *clone* repositori **BigSIRS**. Panduan ini mencakup alur instalasi dari awal (*zero to running*), mulai dari konfigurasi *environment*, kontainer database Docker, migrasi & seeder Laravel 13, sinkronisasi data master riil dari database *legacy*, hingga menjalankan antarmuka web Vue 3 / Vite pada *localhost*.

---

## Daftar Isi
1. [Arsitektur & Port Layanan](#1-arsitektur--port-layanan)
2. [Prasyarat Sistem (Prerequisites)](#2-prasyarat-sistem-prerequisites)
3. [Langkah 1: Persiapan Environment File (.env)](#langkah-1-persiapan-environment-file-env)
4. [Langkah 2: Menjalankan Kontainer Database (Docker)](#langkah-2-menjalankan-kontainer-database-docker)
5. [Langkah 3: Setup & Inisialisasi Backend (Laravel 13)](#langkah-3-setup--inisialisasi-backend-laravel-13)
6. [Langkah 4: Sinkronisasi Data Master dari Database Legacy (Wajib untuk Data Awal)](#langkah-4-sinkronisasi-data-master-dari-database-legacy-wajib-untuk-data-awal)
7. [Langkah 5: Menjalankan Server Backend](#langkah-5-menjalankan-server-backend)
8. [Langkah 6: Setup & Menjalankan Frontend (Vue 3 / Vite)](#langkah-6-setup--menjalankan-frontend-vue-3--vite)
9. [Kredensial Login Sistem](#kredensial-login-sistem)
10. [Rangkuman Troubleshooting Cepat](#rangkuman-troubleshooting-cepat)

---

## 1. Arsitektur & Port Layanan

Aplikasi berjalan pada lingkungan lokal menggunakan konfigurasi port berikut:

| Komponen | Teknologi | Port Host | URL / Alamat Akses |
| :--- | :--- | :--- | :--- |
| **Frontend Web** | Vue 3 + Vite | `5173` | [http://localhost:5173](http://localhost:5173) |
| **Backend API** | Laravel 13 (PHP 8.3+) | `8000` | [http://127.0.0.1:8000](http://127.0.0.1:8000) (Prefix: `/api/v1`) |
| **Database Kontainer** | MySQL 8.4 (Docker) | `3307` | `127.0.0.1:3307` (Database: `bigsirs_dev` & `simrs_legacy`) |

---

## 2. Prasyarat Sistem (Prerequisites)

Pastikan perangkat lokal Anda telah terpasang perangkat lunak berikut:
* **Git** (versi terbaru)
* **Docker Desktop** (versi terbaru, pastikan daemon Docker dalam status **Running**)
* **PHP** (minimal v8.3, direkomendasikan PHP 8.4/8.5) dengan ekstensi `pdo_mysql`, `mbstring`, `curl`, `openssl`, `zip`.
* **Composer** (minimal v2.x)
* **Node.js** (minimal v20.x atau v22.x LTS) & **npm**

---

## Langkah 1: Persiapan Environment File (.env)

> ⚠️ **PENTING (Format Encoding .env):**  
> Seluruh file `.env` wajib tersimpan dalam format **UTF-8 (without BOM)**. Jangan menyalin/mengedit file `.env` menggunakan redirect PowerShell (`>` atau `>>`) tanpa `-Encoding utf8`, karena dapat menghasilkan format UTF-16 yang memicu error null-byte: `Failed to parse dotenv file. Encountered an invalid name at [L\000E\000G\000A...]`.

### A. Konfigurasi Docker (Root Folder Proyek)
Buka terminal di root direktori proyek, buat folder `backups` jika belum ada, lalu salin file konfigurasi:

```powershell
# Windows PowerShell
if (-not (Test-Path backups)) { New-Item -ItemType Directory -Path backups }
copy .env.docker.example .env.docker

# Linux / macOS
mkdir -p backups
cp .env.docker.example .env.docker
```

### B. Konfigurasi Backend Laravel
Masuk ke folder `backend` dan salin file template:

```powershell
cd backend

# Windows CMD / PowerShell
copy .env.example .env

# Linux / macOS
cp .env.example .env

# Kembali ke root folder
cd ..
```

*Verifikasi parameter penting di `backend/.env`:*
```env
# Akun Login Admin Development
DEV_ADMIN_USERNAME=admin
DEV_ADMIN_PASSWORD=12345

# Koneksi Database Baru (MySQL 8.4 di Container Docker)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=bigsirs_dev
DB_USERNAME=root
DB_PASSWORD=change_root_password

# Koneksi Database Legacy (di Container MySQL yang sama)
LEGACY_DB_CONNECTION=mysql
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3307
LEGACY_DB_DATABASE=simrs_legacy
LEGACY_DB_USERNAME=root
LEGACY_DB_PASSWORD=change_root_password
```

### C. Konfigurasi Frontend (Vue 3 / Vite)
Masuk ke folder `frontend` dan salin file template:

```powershell
cd frontend

# Windows CMD / PowerShell
copy .env.example .env

# Linux / macOS
cp .env.example .env

# Kembali ke root folder
cd ..
```

*Verifikasi parameter penting di `frontend/.env`:*
```env
# URL API Backend (Wajib menyertakan prefix /api/v1)
VITE_API_BASE_URL=http://127.0.0.1:8000/api/v1

# Aktifkan Mock Dashboard UI agar metrik dan grafik dashboard tampil lengkap
VITE_USE_DASHBOARD_MOCK=true
```

---

## Langkah 2: Menjalankan Kontainer Database (Docker)

Buat volume Docker terlebih dahulu (jika baru pertama kali), lalu jalankan service database MySQL dari root direktori proyek:

> 💡 **PENTING:** Gunakan opsi `mysql` pada perintah `docker compose up` agar hanya kontainer database yang dijalankan. Hal ini mencegah benturan port 8000 dan 5173 dengan server development lokal di host.

```powershell
# 1. Buat volume eksternal jika belum pernah dibuat
docker volume create bigsirs_mysql_data

# 2. Jalankan kontainer database MySQL
docker compose --env-file .env.docker up -d mysql
```

### Verifikasi Kontainer:
Periksa apakah kontainer database telah berjalan secara sehat (*healthy*):
```powershell
docker ps
```
Pastikan kontainer `bigsirs-mysql` berstatus **Up (healthy)** dan memetakan port `3307:3306`.

### 3. Inisialisasi & Restore Database Legacy (SIMRS Lama)
Database legacy (`simrs_legacy`) diperlukan untuk sinkronisasi data master riil (Langkah 4). Kontainer MySQL Docker hanya membuat database `bigsirs_dev` secara default. Jalankan perintah berikut dari **root direktori proyek** untuk membuat database `simrs_legacy` dan merestore file dump SQL (`backups/backup_db_simrs_masking.sql`):

```powershell
# Windows PowerShell / CMD
# a. Buat database simrs_legacy
docker exec -i bigsirs-mysql mysql -uroot -pchange_root_password -e "CREATE DATABASE IF NOT EXISTS simrs_legacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# b. Restore file dump SQL ke database simrs_legacy
docker exec -i bigsirs-mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" simrs_legacy < /backups/backup_db_simrs_masking.sql'

# Linux / macOS
docker exec -i bigsirs-mysql mysql -uroot -pchange_root_password -e "CREATE DATABASE IF NOT EXISTS simrs_legacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
docker exec -i bigsirs-mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" simrs_legacy < /backups/backup_db_simrs_masking.sql'
```
*(Proses restore membutuhkan waktu sekitar 10–20 detik).*

---

## Langkah 3: Setup & Inisialisasi Backend (Laravel 13)

Buka terminal dan masuk ke folder `backend`:

```powershell
cd backend
```

### A. Pasang Dependensi PHP
```powershell
composer install
```

### B. Generate Application Key & Bersihkan Cache
```powershell
php artisan key:generate
php artisan config:clear
```

### C. Jalankan Migrasi Skema & Seeder Admin
```powershell
# 1. Bangun seluruh tabel skema sistem BigSIRS baru
php artisan migrate

# 2. Inisialisasi akun login admin development
php artisan db:seed --class=AdminDevelopmentSeeder
```
*Output sukses: `Admin user seeded successfully.`*

---

## Langkah 4: Sinkronisasi Data Master dari Database Legacy (Wajib untuk Data Awal)

Agar modul Master Data langsung terisi dengan data riil (bukan data dummy buatan), jalankan proses sinkronisasi dari database `simrs_legacy` yang sudah terpasang di kontainer Docker.

### Cara Cepat (Rekomendasi - Cukup 1 Baris Perintah):
Jalankan satu perintah berikut di dalam folder `backend` untuk menyinkronkan seluruh modul secara otomatis dan berurutan:

```powershell
php artisan legacy:import-all
```
*(Catatan: Perintah di atas secara default sengaja melewati data Kelurahan agar proses sinkronisasi awal berjalan cepat, karena terdapat ~75.000+ data kelurahan di seluruh Indonesia).*

**Untuk mengimpor data Kelurahan / Desa:**
```powershell
# Jalankan perintah khusus kelurahan (direkomendasikan, tidak perlu mengulang modul lain):
php artisan legacy:import-villages

# Atau jalankan import-all beserta kelurahan sekaligus:
php artisan legacy:import-all --with-villages
```
*(Proses import kelurahan membutuhkan waktu sekitar 3–7 menit tergantung performa PC).*

<details>
<summary><b>Klik di sini jika ingin menjalankan sinkronisasi per modul secara manual</b></summary>

```powershell
# 1. Data Wilayah (Provinsi, Kabupaten, Kecamatan, Kelurahan) & Referensi Dasar
php artisan legacy:import-references

# 2. Pelayanan Klinis (Poliklinik, Dokter, Jadwal, Pegawai, Jabatan, Penjamin, Jenis Layanan, Asuhan Gizi, Kegiatan, Surat)
php artisan legacy:import-polyclinics
php artisan legacy:import-guarantors
php artisan legacy:import-doctors
php artisan legacy:import-doctor-specializations
php artisan legacy:import-doctor-schedules
php artisan import:legacy-employees
php artisan import:legacy-positions
php artisan legacy:import-service-types
php artisan legacy:import-diet-types
php artisan legacy:import-activity-types
php artisan import:legacy-letter-types

# 3. Fasilitas & Tempat Tidur (Gedung, Lantai, Bangsal, Kelas Rawat, Kamar, Bed)
php artisan legacy:import-buildings
php artisan legacy:import-floors
php artisan legacy:import-wards
php artisan legacy:import-room-classes
php artisan legacy:import-rooms
php artisan legacy:import-beds

# 4. Logistik Farmasi (Gudang, Supplier, Satuan, Kategori, Rute, Obat, Paket Obat, Signa)
php artisan import:legacy-warehouses
php artisan import:legacy-suppliers
php artisan import:medicine-categories
php artisan import:medicine-units
php artisan import:medicine-routes
php artisan import:legacy-medicines
php artisan legacy:import-medication-signas
php artisan legacy:import-medicine-packages
php artisan legacy:link-polyclinic-warehouses

# 5. Diagnosis, Prosedur & Penunjang Medis (ICD-10, ICD-9-CM, Tindakan, Lab, Radiologi)
php artisan legacy:import-icd10
php artisan legacy:import-icd9-cms
php artisan legacy:import-procedure-categories
php artisan legacy:import-tariff-components
php artisan legacy:import-tariff-types
php artisan legacy:import-procedures
php artisan legacy:import-procedure-packages
php artisan legacy:import-procedure-user-mappings
php artisan legacy:import-report-groups
php artisan import:legacy-laboratory-categories
php artisan import:laboratory-groups
php artisan import:legacy-laboratory-items
php artisan import:radiology-types
php artisan import:radiology-item-groups
php artisan legacy:import-radiology-categories
php artisan legacy:import-radiology-groups
php artisan legacy:import-radiology-items
php artisan legacy:import-follow-up-handlings
```
</details>

---

## Langkah 5: Menjalankan Server Backend

Setelah seluruh dependensi dan data selesai disiapkan, bersihkan cache dan jalankan server API Laravel:

```powershell
php artisan optimize:clear
php artisan serve
```

Backend API sekarang aktif di: **`http://127.0.0.1:8000`**

---

## Langkah 6: Setup & Menjalankan Frontend (Vue 3 / Vite)

Buka jendela terminal baru khusus untuk antarmuka web, lalu masuk ke folder `frontend`:

```powershell
cd frontend
```

### A. Pasang Dependensi Node.js
```powershell
npm install
```

### B. Jalankan Development Server
```powershell
npm run dev
```

Aplikasi web sekarang aktif dan dapat dibuka di: **`http://localhost:5173`**

---

## Kredensial Login Sistem

Buka browser di **[http://localhost:5173](http://localhost:5173)**:

* **Username:** `admin`
* **Password:** `12345`
* **Security Code / CAPTCHA:** Ketik 5 karakter CAPTCHA yang tampil pada kotak input.

---

## Protokol Reset Data / Cache Tampilan

Apabila Anda baru saja memperbarui data atau file environment namun tampilan di browser belum ter-update:

1. **Hard Refresh Browser:** Tekan `Ctrl + Shift + R` (Windows) atau `Cmd + Shift + R` (macOS).
2. **Bersihkan Cache Backend:**
   ```powershell
   cd backend
   php artisan optimize:clear
   ```
3. **Restart Vite Server dengan Flag Pembersih Cache:**
   ```powershell
   cd frontend
   npm run dev -- --force
   ```

---

## Rangkuman Troubleshooting Cepat

| Pesan Kendala | Akar Masalah | Solusi Cepat |
| :--- | :--- | :--- |
| `Conflict. The container name "/bigsirs-mysql" is already in use` | Kontainer lama tertinggal di daemon Docker | Jalankan `docker rm -f bigsirs-mysql` lalu jalankan kembali `docker compose --env-file .env.docker up -d mysql`. |
| `Bind for 0.0.0.0:8000 failed: port is already allocated` | Service backend Docker ikut menyala saat `compose up` | Hentikan service backend kontainer dengan `docker compose --env-file .env.docker stop backend frontend`, atau jalankan hanya `docker compose up -d mysql`. |
| `Failed to parse dotenv file. Encountered an invalid name at [L\000E...]` | File `.env` tersimpan dalam format UTF-16 (null-byte) | Salin ulang dari `.env.example` yang sudah bersih dan pastikan editor menyimpan dengan format **UTF-8 without BOM**. |
| `SQLSTATE[HY000] [1045] Access denied for user 'root'` | Password MySQL di `backend/.env` tidak sesuai kontainer | Samakan `DB_PASSWORD=change_root_password` sesuai `MYSQL_ROOT_PASSWORD` di `.env.docker`, lalu jalankan `php artisan config:clear`. |
| `SQLSTATE[HY000] [1049] Unknown database 'simrs_legacy'` | Database legacy belum dibuat atau direstore di kontainer MySQL | Jalankan perintah pembuatan database `simrs_legacy` dan restore dump SQL pada Langkah 2 (Bagian 3). |
| `DEV_ADMIN_USERNAME atau DEV_ADMIN_PASSWORD belum diatur di .env. Seeder diabaikan.` | Variabel admin kosong di `backend/.env` | Pastikan `DEV_ADMIN_USERNAME=admin` dan `DEV_ADMIN_PASSWORD=12345` terisi di `backend/.env`, lalu jalankan ulang `php artisan db:seed --class=AdminDevelopmentSeeder`. |
| `POST http://localhost:8000/api/auth/login 404 (Not Found)` | `VITE_API_BASE_URL` di `frontend/.env` belum menyertakan `/v1` | Ubah menjadi `VITE_API_BASE_URL=http://127.0.0.1:8000/api/v1` dan restart Vite (`npm run dev`). |
| `Data dashboard belum terhubung` / Tampilan dashboard kosong | Variabel mock dashboard disetel false | Ubah menjadi `VITE_USE_DASHBOARD_MOCK=true` pada `frontend/.env` lalu restart Vite server. |
| `Tabel Master Data (Kabupaten/Kecamatan/Obat) kosong` | Perintah sinkronisasi legacy belum dijalankan | Jalankan perintah import pada Langkah 4, lalu lakukan Hard Refresh di browser (`Ctrl + Shift + R`). |
| `VITE_API_BASE_URL belum dikonfigurasi` | File `frontend/.env` belum dibuat | Salin `frontend/.env.example` ke `frontend/.env`, verifikasi nilainya, dan jalankan `npm run dev`. |