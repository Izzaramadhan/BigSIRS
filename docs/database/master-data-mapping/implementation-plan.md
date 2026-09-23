# Rencana Implementasi

Berdasarkan hasil audit faktual pada *branch* `feature/master-data-foundation`, tidak ditemukan satupun fondasi Master Data (model, migration, maupun controller). Seluruh kebutuhan master data untuk operasional Rawat Jalan dan Logistik belum tersedia sama sekali.

Disarankan untuk tidak melakukan rilis (*Pull Request*) semua Master Data sekaligus. Sebaiknya dibagi menjadi *slice* kecil yang independen dan mudah divalidasi.

## Rekomendasi Slice Pertama (Terkoreksi)

**Slice Terpilih:** Master Data Bersama & Rawat Jalan Dasar (Level 1)
- **Fokus Utama:** `Polyclinic` dan `Guarantor`.

### Analisis Kondisi Slice:
- **Yang sudah ada:** Tidak ada (0%). Model, *migration*, API, dan UI CRUD untuk kedua entitas ini terbukti belum ada di *repository* (*Missing*).
- **Yang belum ada:** *Migration*, Model, API Controller, *FormRequest*, API Resource, Route API, *Frontend service*, dan UI CRUD. Seluruhnya harus dibangun dari awal.
- **Yang perlu diubah:** Tidak ada yang perlu diubah dari struktur yang sudah ada karena memang entitasnya belum pernah dibuat. 
- **Yang tidak perlu dibuat ulang:** Otentikasi dan *endpoint* dasar (menggunakan struktur `User` yang sudah tersedia, sehingga tidak perlu merancang ulang sistem proteksi API).

### Alasan Pemilihan:
1. **Paling Sedikit Dependensi:** Keduanya adalah entitas referensi dasar yang berdiri mandiri tanpa *Foreign Key* ke tabel lain.
2. **Dibutuhkan Oleh Banyak Modul:** Entitas ini menjadi syarat mutlak sebelum pendaftaran pasien, admisi, atau penugasan tenaga medis dapat dilakukan.

### Rencana Eksekusi (Jika Disetujui)

**Scope:**
Membuat manajemen data *end-to-end* (Tabel hingga UI) untuk `Polyclinic` dan `Guarantor`.

**Dependensi:**
Tidak ada.

**Backend (Laravel):**
- **Migration:** Membuat tabel `polyclinics` dan `guarantors` (membutuhkan file sql target yang disetujui).
- **Model:** Membuat model `Polyclinic` dan `Guarantor` dengan Eloquent dan *Soft Deletes*.
- **Factory:** Membuat data palsu terstruktur untuk *testing*.
- **Seeder:** Menyiapkan *development seeder* untuk mempermudah QA/Testing.
- **FormRequest:** Melakukan validasi *request* (rules *unique*, *required*, dll).
- **API Resource:** Standarisasi respons format JSON.
- **Controller:** API Controller standar (Index, Store, Show, Update, Destroy).
- **Route API:** Mendaftarkan *endpoints* CRUD ke dalam `routes/api.php`.
- **Feature Test:** Menulis tes *end-to-end* (CRUD) untuk *endpoints* terkait (`PolyclinicTest`, `GuarantorTest`).

**Frontend (Vue):**
- **Frontend Service:** Menyiapkan `src/services/polyclinic.js` dan `guarantor.js` untuk integrasi Axios.
- **Halaman Daftar:** Menampilkan tabel antarmuka pengguna, dilengkapi fitur *Search, Filter,* dan *Pagination*.
- **Form Tambah/Edit:** Membuat *modal* form untuk proses *create* dan *update*.
- **Aktivasi/Nonaktif:** Tombol toggle (ubah *status active/inactive*).
- **Unit Test Frontend:** Menulis *component test* sederhana di Vitest.

**Risiko & Keputusan Bisnis yang Dibutuhkan:**
- Apakah Kode (Poli/Penjamin) harus *auto-generate* atau manual? Berdasarkan *profiling* data, 7 dari 8 data Penjamin (*legacy*) tidak memiliki kode, sehingga *auto-generate* mutlak diperlukan jika kode diwajibkan `UNIQUE`.
- Bagaimana menyelesaikan konflik duplikasi kode pada Poliklinik (terdapat kode ganda `OP0013`) tanpa mematahkan integrasi eksternal?
- Konfirmasi skema kolom yang tepat (apakah hierarki poli/`parent_id` dibangun ulang) harus diputuskan bersama sebelum *migration* ditulis.
