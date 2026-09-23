# Review Model Existing

Berdasarkan hasil audit pada kode aplikasi (terutama di `backend/app/Models` dan `backend/database/migrations`), berikut adalah analisis kandidat Master Data terhadap apa yang sudah tersedia di proyek:

## Daftar Model Existing

1. **User**
   - **Lokasi Model:** `backend/app/Models/User.php`
   - **Tabel:** `users` (dibuat di `0001_01_01_000000_create_users_table.php`)
   - **Tipe Data:** Data Pengguna / Sistem
   - **Klasifikasi Status:** A (Sudah tersedia dan sesuai) / B (Sudah tersedia tetapi perlu diperluas, misal jika perlu penambahan role/akses).
   - **Catatan:** Jangan dibuat ulang. Merupakan autentikasi dasar (sudah mendukung username dan password via API).

2. **Personal Access Token**
   - **Lokasi Model:** Bawaan Laravel Sanctum
   - **Tabel:** `personal_access_tokens` (dibuat di `2026_09_22_044233_create_personal_access_tokens_table.php`)
   - **Tipe Data:** Data Sistem
   - **Klasifikasi Status:** A (Sudah tersedia dan sesuai)

3. **Job & Cache**
   - **Lokasi Model:** Bawaan Laravel
   - **Tabel:** `jobs`, `cache`, dll.
   - **Tipe Data:** Data Sistem
   - **Klasifikasi Status:** A (Sudah tersedia dan sesuai)

## Analisis Model yang Menjadi Perhatian Khusus

## Analisis Model yang Menjadi Perhatian Khusus

Berdasarkan pemeriksaan faktual dan riwayat Git (`git log --all --oneline`), berikut adalah klasifikasi per lapisan untuk model-model yang dipertanyakan. Laporan sebelumnya menyatakan model ini kosong karena **memang tidak ditemukan satupun bukti keberadaannya di *branch* manapun dalam repositori ini** (pada path `backend/app/Models/...`).

### 1. Polyclinic
- **Migration/tabel target:** Missing / New
- **Model Eloquent:** Missing / New
- **Factory/seeder:** Missing
- **API controller:** Missing
- **FormRequest:** Missing
- **API Resource:** Missing
- **Route API:** Missing
- **Frontend service:** Missing
- **Frontend view CRUD:** Missing
- **Legacy mapping:** Unknown (membutuhkan file `backup_db_simrs_masking-202609101406.sql`)

### 2. MedicalPersonnel
- **Migration/tabel target:** Missing / New
- **Model Eloquent:** Missing / New
- **Factory/seeder:** Missing
- **API controller:** Missing
- **FormRequest:** Missing
- **API Resource:** Missing
- **Route API:** Missing
- **Frontend service:** Missing
- **Frontend view CRUD:** Missing
- **Legacy mapping:** Unknown (membutuhkan file `backup_db_simrs_masking-202609101406.sql`)

### 3. Guarantor
- **Migration/tabel target:** Missing / New
- **Model Eloquent:** Missing / New
- **Factory/seeder:** Missing
- **API controller:** Missing
- **FormRequest:** Missing
- **API Resource:** Missing
- **Route API:** Missing
- **Frontend service:** Missing
- **Frontend view CRUD:** Missing
- **Legacy mapping:** Unknown (membutuhkan file `backup_db_simrs_masking-202609101406.sql`)

### 4. Patient
- **Klasifikasi Khusus:** Core business entity / data pasien. 
- Catatan: `Patient` bukanlah master referensi statis seperti Poliklinik atau Penjamin. Entitas ini sebaiknya dikerjakan bersama modul pasien atau pendaftaran, dan tidak dimasukkan dalam *implementation slice* Master Data generik pertama.
- **Migration/tabel target:** Missing / New
- **Model Eloquent:** Missing / New
- **Factory/seeder:** Missing
- **API controller:** Missing
- **FormRequest:** Missing
- **API Resource:** Missing
- **Route API:** Missing
- **Frontend service:** Missing
- **Frontend view CRUD:** Missing
- **Legacy mapping:** Unknown (membutuhkan file `backup_db_simrs_masking-202609101406.sql`)

## Kesimpulan

Berdasarkan bukti repositori saat ini, fondasi Rawat Jalan (model, migration, dll) benar-benar belum tersedia di *branch* manapun. Oleh karena itu, klaim bahwa model tersebut seharusnya sudah pernah dibuat belum dapat dibuktikan dari *source code* yang ada di repositori ini. Sebagian besar kebutuhan Master Data untuk modul Rawat Jalan dan Logistik perlu dibangun dari awal, kecuali entitas `User`.

