# Urutan Implementasi Master Data

Untuk menghindari masalah *foreign key constraint* dan memastikan alur kerja pengembangan (termasuk *seeding*) berjalan mulus, tabel Master Data harus diimplementasikan berdasarkan hierarki dependensi. Master data referensi harus dibuat lebih dulu sebelum tabel lain yang bergantung padanya.

## Urutan Implementasi (Berdasarkan Hierarki)

### 1. Master Data Tanpa Foreign Key (Independent)
Ini adalah tabel referensi paling dasar yang berdiri sendiri dan tidak bergantung pada entitas manapun di sistem.
- `polyclinics` (Poliklinik)
- `guarantors` (Penjamin / Asuransi / BPJS)
- `suppliers` (Pemasok)
- `warehouses` (Gudang / Unit Logistik)
- `icd_10` (Master Diagnosa Statis)

*Catatan: `item_categories` dan `item_units` belum dimasukkan ke sini karena masih memerlukan keputusan apakah akan dijadikan Enum atau tabel Master Data.*

### 2. Master Data Referensi Bersama
Tabel yang mungkin memiliki referensi silang sederhana dengan entitas di level 1.
- `medical_personnels` (Tenaga Medis)
  - *Dependensi:* Membutuhkan relasi ke tabel `polyclinics` jika satu tenaga medis terikat pada poliklinik tertentu (contoh: `polyclinic_id`). 
  - *Catatan:* Jika relasinya *many-to-many*, akan dibutuhkan pivot table tambahan `medical_personnel_polyclinic` setelah keduanya terbuat.

### 3. Master Data Logistik (Barang)
Tabel yang menampung data produk fisik, yang bergantung pada referensi dasar logistik.
- `items` / `drugs` (Barang / Obat)
  - *Dependensi:* Membutuhkan `item_categories` (`category_id`) dan `item_units` (`unit_id`).

### 4. Data Pasien (Core Business Entity)
Walaupun sering disebut bersamaan, `Patient` bukan entitas master referensi statis melainkan entitas inti transaksi medis. Sebaiknya tidak digabung dalam perancangan generik *Master Data* dan dikerjakan bersama modul Pendaftaran.

### 5. Tabel Transaksi / Fitur (Out of Scope saat ini)
Entitas yang *tidak boleh* dibuat pada fase implementasi Master Data (hanya digambarkan untuk menunjukkan batas dependensi).
- **Rawat Jalan:** `registrations` (Pendaftaran), `admissions` (Admisi), `examinations` (Pemeriksaan).
- **Billing:** `billings`, `invoices`.
- **Logistik:** `invoices` (Faktur Pembelian), `mutations` (Mutasi), `stock_opnames`, dll.

## Pendekatan Migration
Berdasarkan urutan di atas, file migration harus dinamai dan dibuat berdasarkan kelompok tahapan (misalnya `01_create_polyclinics`, `02_create_categories`, lalu baru `03_create_items`, dst.).
