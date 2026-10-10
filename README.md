# Panduan Onboarding & Setup Lingkungan Lokal BigSIRS Rebuild

Dokumentasi ini ditujukan khusus bagi pengembang yang baru pertama kali melakukan *clone* repositori **BigSIRS** pada perangkat baru. Panduan ini mencakup alur instalasi backend (Laravel 13), frontend (Vue 3 / Vite), kontainer database Docker, serta solusi penanganan kendala yang kerap terjadi saat proses inisialisasi.

---

## 1. Prasyarat Sistem (Prerequisites)

Pastikan perangkat lokal Anda telah terpasang perangkat lunak berikut:
* **Git** (versi terbaru)
* **Docker Desktop** (versi terbaru, pastikan daemon Docker sedang aktif)
* **PHP** (minimal v8.2) & **Composer**
* **Node.js** (minimal v20.x LTS) & **npm**
* Akses ke database legacy BigSIRS (MySQL lama / monolitik CI3) jika memerlukan sinkronisasi data master

---

## 2. Persiapan Environment File (Wajib Sebelum Menyalakan Docker)

Docker Compose dan aplikasi backend memerlukan variabel environment yang valid sebelum kontainer dapat dibangun.

> ⚠️ **PENTING (Format Encoding .env):**  
> Pastikan seluruh file `.env` tersimpan dalam format **UTF-8 (without BOM)**. Jangan membuat atau menyalin file `.env` menggunakan redirect PowerShell yang menghasilkan format UTF-16, karena akan memicu null-byte error:  
> `Failed to parse dotenv file. Encountered an invalid name at [L\000E\000G\000A...]`.

### A. Konfigurasi Docker (Root Folder)
Buka terminal di root direktori proyek (`D:\SIMRS`), lalu salin template konfigurasi Docker:

```powershell
# Windows CMD / PowerShell
copy .env.docker.example .env.docker

# Linux / macOS
cp .env.docker.example .env.docker
```

### B. Konfigurasi Backend Laravel
Masuk ke folder `backend` dan salin template konfigurasi aplikasi:

```powershell
cd backend

# Windows CMD / PowerShell
copy .env.example .env

# Linux / macOS
cp .env.example .env

# Kembali ke root folder
cd ..
```

### C. Konfigurasi Frontend (Vue 3 / Vite)
Masuk ke folder `frontend` dan salin template konfigurasi:

```powershell
cd frontend

# Windows CMD / PowerShell
copy .env.example .env

# Linux / macOS
cp .env.example .env

# Kembali ke root folder
cd ..
```

---

## 3. Menjalankan Kontainer Database (Docker)

Jalankan kontainer database MySQL melalui Docker Compose dari root proyek:

```powershell
docker compose --env-file .env.docker up -d
```

### Verifikasi Kontainer:
Periksa apakah kontainer database telah berjalan secara sehat (*healthy*):

```powershell
docker ps
```
Pastikan kontainer `bigsirs-mysql` berstatus **Up (healthy)** atau **Up** dan memetakan port host `3307:3306`.

### Penanganan Kendala (Troubleshooting Docker):

1. **Error: Conflict. The container name "/bigsirs-mysql" is already in use**  
   *Penyebab:* Kontainer database sudah pernah dibuat di sistem atau sedang berjalan di editor/terminal lain.  
   *Solusi:* Hapus kontainer lama yang menabrak lalu jalankan ulang compose:
   ```powershell
   docker rm -f bigsirs-mysql
   docker compose --env-file .env.docker up -d
   ```

2. **Error: Bind for 0.0.0.0:8000 failed: port is already allocated**  
   *Penyebab:* Port 8000 host Anda sedang dipakai oleh proses aplikasi lain atau `php artisan serve` yang masih aktif di latar belakang.  
   *Solusi:* Cari dan hentikan proses di port 8000:
   ```powershell
   # Windows PowerShell
   Get-Process -Id (Get-NetTCPConnection -LocalPort 8000).OwningProcess | Stop-Process -Force
   
   # Jalankan kembali Docker Compose
   docker compose --env-file .env.docker up -d
   ```

---

## 4. Setup dan Inisialisasi Backend (Laravel)

Buka terminal dan masuk ke folder `backend`:

```powershell
cd backend
```

### A. Pasang Dependensi PHP
```powershell
composer install
```

### B. Generate Application Key & Bersihkan Cache
Wajib dijalankan agar Laravel menghasilkan kunci enkripsi unik di `.env`:
```powershell
php artisan key:generate
php artisan config:clear
```

### C. Validasi Manual Konfigurasi `backend/.env`
Buka file `backend/.env` dan verifikasi parameter koneksi database:

```env
# Koneksi Database Docker Baru (MySQL 8.4)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307                  # Port host yang diarahkan ke container MySQL
DB_DATABASE=bigsirs_dev
DB_USERNAME=root              # Gunakan root atau user container yang aktif
DB_PASSWORD=secret            # Samakan dengan MYSQL_ROOT_PASSWORD di .env.docker

# Koneksi Database Legacy CI3 (Read-Only)
LEGACY_DB_CONNECTION=mysql
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306           # Sesuaikan dengan port MySQL database legacy lokal
LEGACY_DB_DATABASE=db_bigsirs_lama
LEGACY_DB_USERNAME=root
LEGACY_DB_PASSWORD=
```

> ⚠️ **Catatan Error `Access denied for user 'bigsirs_user' / 'your_username'`:**  
> Jika saat migrasi muncul error `Access denied`, pastikan `DB_USERNAME=root` dan `DB_PASSWORD` sesuai dengan password root pada kontainer Docker. Setelah diubah, jalankan `php artisan config:clear`.

### D. Migrasi Skema & Seeder Admin Lokal
Jalankan migrasi skema tabel baru dan pembuatan kredensial admin development:

```powershell
# 1. Bangun seluruh tabel skema sistem baru
php artisan migrate

# 2. Inisialisasi akun login pengembang
php artisan db:seed --class=AdminDevelopmentSeeder
```

### E. (Opsional) Sinkronisasi Data Master dari Database Legacy
Perintah import pada proyek ini bersifat terpecah (*modular*). Jalankan sesuai kebutuhan:

```powershell
# Data Pegawai & Profesi
php artisan import:legacy-positions
php artisan import:legacy-employees

# Data Master Logistik Farmasi
php artisan import:medicine-units
php artisan import:medicine-categories
php artisan import:medicine-routes
php artisan import:legacy-warehouses
php artisan import:legacy-suppliers
php artisan import:legacy-medicines

# Data Penunjang Medis (Lab & Radiologi)
php artisan import:legacy-laboratory-categories
php artisan import:laboratory-groups
php artisan import:legacy-laboratory-items
php artisan import:radiology-item-groups
php artisan import:radiology-types

# Data Surat & Aktivitas
php artisan import:legacy-activity-types
php artisan import:legacy-letter-types
```

### F. Bersihkan Cache & Jalankan Server Backend
```powershell
php artisan optimize:clear
php artisan serve
```
Backend API sekarang aktif di: **`http://127.0.0.1:8000`**

---

## 5. Setup dan Menjalankan Frontend (Vue 3 / Vite)

Buka jendela terminal baru khusus antarmuka web, lalu masuk ke folder `frontend`:

```powershell
cd frontend
```

### A. Pasang Dependensi Node.js
```powershell
npm install
```

### B. Validasi Konfigurasi `frontend/.env`
Buka file `frontend/.env` dan pastikan konfigurasi berikut terisi:

```env
# URL Endpoint API Backend Laravel
VITE_API_BASE_URL=http://localhost:8000/api

# Aktifkan Mock Dashboard UI (Opsional)
# Gunakan true jika API dashboard backend belum tersedia
VITE_USE_DASHBOARD_MOCK=true
```

> ⚠️ **Catatan Error `Uncaught Error: VITE_API_BASE_URL belum dikonfigurasi`:**  
> Terjadi bila file `frontend/.env` belum dibuat atau variabel `VITE_API_BASE_URL` kosong. Setiap kali mengubah file `.env`, server Vite wajib dimatikan (`Ctrl + C`) dan dijalankan ulang.

### C. Jalankan Development Server
```powershell
npm run dev
```

Jika sebelumnya terjadi perubahan data atau tampilan yang tertahan di cache Vite, jalankan dengan flag pembersih cache:
```powershell
npm run dev -- --force
```

Aplikasi web sekarang aktif di: **`http://localhost:5173`** (atau `http://localhost:5174`).

---

## 6. Protokol Reset Jika Terjadi Masalah Data / Tampilan

Apabila data yang baru diperbarui tidak langsung muncul di antarmuka atau terjadi inkonsistensi state:

1. **Bersihkan cache Laravel:**
   ```powershell
   cd backend
   php artisan optimize:clear
   ```
2. **Bersihkan cache browser:**  
   Lakukan **Hard Refresh** pada browser dengan menekan `Ctrl + Shift + R` (Windows) atau `Cmd + Shift + R` (macOS).
3. **Restart Docker dengan Bersih:**
   ```powershell
   docker compose --env-file .env.docker down
   docker compose --env-file .env.docker up -d
   ```

---

## 7. Rangkuman Troubleshooting Cepat

| Pesan Kendala | Akar Masalah | Solusi Cepat |
| :--- | :--- | :--- |
| `Conflict. The container name "/bigsirs-mysql" is already in use` | Kontainer lama tertinggal di daemon Docker | Jalankan `docker rm -f bigsirs-mysql` lalu jalankan kembali `docker compose up -d`. |
| `Bind for 0.0.0.0:8000 failed: port is already allocated` | Port 8000 dipakai proses lain | Hentikan proses yang memblokir port 8000 atau matikan `artisan serve` yang berjalan di latar belakang. |
| `Failed to parse dotenv file. Encountered an invalid name at [L\000E...]` | File `.env` tersimpan dalam format UTF-16 (null-byte) | Salin ulang dari `.env.example` dan pastikan tersimpan sebagai format **UTF-8 without BOM**. |
| `SQLSTATE[HY000] [1045] Access denied for user` | User atau password MySQL di `.env` tidak sesuai container | Sesuaikan `DB_USERNAME=root` dan `DB_PASSWORD=secret`, lalu jalankan `php artisan config:clear`. |
| `Command "import:legacy-master" is not defined` | Perintah import legacy dibuat modular per modul | Jalankan sub-command spesifik seperti `import:legacy-medicines`, `import:legacy-employees`, dll. |
| `VITE_API_BASE_URL belum dikonfigurasi` | File `frontend/.env` belum tersedia | Salin `frontend/.env.example` ke `frontend/.env`, isi URL backend, dan restart dev server. |
| `Data dashboard belum terhubung` | Endpoint dashboard backend belum ada di rilis saat ini | Tambahkan `VITE_USE_DASHBOARD_MOCK=true` di `frontend/.env` untuk menampilkan simulasi data UI. |