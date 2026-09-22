# BigSIRS

Proyek ini menggunakan arsitektur terpisah antara backend (Laravel) dan frontend (Vue + Vite). Database menggunakan MySQL 8.4 via Docker Compose.

## ⚠️ PERHATIAN PENTING
**DILARANG KERAS** meng-commit file environment seperti `.env`, `.env.docker`, atau file rahasia lainnya ke dalam repository.
Saat ini, proyek **belum** mengimplementasikan fitur Autentikasi maupun Master Data.

## Prasyarat
Pastikan sistem Anda telah terpasang:
- PHP (>= 8.2)
- Composer
- Node.js (>= 22.x) & npm
- Git
- Docker & Docker Compose

## Persiapan Environment

1. **Docker (MySQL):**
   Salin file `.env.docker.example` menjadi `.env.docker`:
   ```bash
   cp .env.docker.example .env.docker
   ```

2. **Backend (Laravel):**
   Masuk ke folder `backend`, lalu salin `.env.example` menjadi `.env`:
   ```bash
   cd backend
   cp .env.example .env
   ```
   *Catatan:* Konfigurasi default `.env` sudah diarahkan ke koneksi Docker MySQL.

## Perbedaan Koneksi Database (DB_HOST)
- Jika menjalankan `php artisan` dari mesin host (Windows/Mac/Linux), gunakan `DB_HOST=127.0.0.1` dan `DB_PORT=3307`.
- Jika backend nantinya dijalankan di dalam container Docker yang satu jaringan dengan MySQL, gunakan `DB_HOST=mysql` (sesuai nama service) dan `DB_PORT=3306`.

## Menjalankan Proyek

### 1. Menyalakan Database (MySQL)
Dari root proyek:
```bash
# Menyalakan
docker compose --env-file .env.docker up -d

# Mematikan
docker compose --env-file .env.docker down
```

### 2. Menjalankan Backend (Laravel)
```bash
cd backend
php artisan serve
```
Backend akan berjalan di **http://localhost:8000**

### 3. Menjalankan Frontend (Vue)
```bash
cd frontend
npm install
npm run dev
```
Frontend akan berjalan di **http://localhost:5173**