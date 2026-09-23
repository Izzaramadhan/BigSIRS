# Inventaris Master Data

Berdasarkan audit menu Navigasi (Pendaftaran, Admisi, Pemeriksaan, Billing Rawat Jalan, Faktur, Mutasi, Stok Opname, Pengeluaran, Penjualan Bebas) dan analisis kebutuhan standar SIMRS, berikut adalah kandidat Master Data yang diidentifikasi:

## Matriks Inventaris Master Data

| Nama Master Data | Domain | Tabel Lama | Tabel Target | Model Existing | Status | Primary Key | Field Utama | Unique Constraint | Soft Delete | Dependensi | Digunakan Oleh | Sumber Bukti | Tingkat Keyakinan | Catatan |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Pengguna Sistem | Sistem | `Unknown` | `users` | `User` | A (Existing) | `id` (Auto-inc/UUID) | `name`, `username`, `email`, `password` | `username`, `email` | Ya | - | Semua Modul | Model `User` | Confirmed | Tabel existing Laravel |
| Poliklinik | Rawat Jalan | `ref_poliklinik` | `polyclinics` | - | Missing | `id` (Auto-inc/UUID) | `code`, `name`, `is_active` | `code` | Ya | - | Pendaftaran, Admisi | SQL Dump | Confirmed | Tabel legacy ada |
| Tenaga Medis (Dokter/Perawat) | Bersama | `Unknown` | `medical_personnels` | - | Missing | `id` (Auto-inc/UUID) | `code`, `name`, `type`, `is_active` | `code` | Ya | `polyclinics` (nullable) | Admisi, Pemeriksaan | Instruksi | Unknown | Mungkin memiliki relasi *many-to-many* dengan Poliklinik |
| Penjamin (Asuransi/BPJS) | Billing | `ref_jenis_asuransi` | `guarantors` | - | Missing | `id` (Auto-inc/UUID) | `code`, `name`, `is_active` | `code` | Ya | - | Pendaftaran, Billing | SQL Dump | Confirmed | Berdasarkan `ref_jenis_asuransi` |
| Pasien | Data Pasien | `Unknown` | `patients` | - | Missing | `id` (Auto-inc/UUID) | `medical_record_no`, `nik`, `name`, `dob`, `gender` | `medical_record_no`, `nik` | Ya | - | Pendaftaran, Admisi, Pemeriksaan | Instruksi | Unknown | **Core business entity / data pasien**. Sebaiknya tidak digabung di Master Data generik. |
| Barang/Obat | Logistik | `Unknown` | `items` / `drugs` | - | Missing | `id` (Auto-inc/UUID) | `code`, `name`, `category_id`, `unit_id`, `price` | `code` | Ya | Kategori, Satuan | Faktur, Mutasi, Stok, Penjualan | Menu Navigasi | Unknown | |
| Kategori Barang | Logistik | `Unknown` | *Needs decision* | - | Missing | - | - | - | - | - | Barang/Obat | Standard | Unknown | **Needs decision:** Apakah tabel dinamis atau enum statis. |
| Satuan Barang | Logistik | `Unknown` | *Needs decision* | - | Missing | - | - | - | - | - | Barang/Obat | Standard | Unknown | **Needs decision:** Apakah tabel dinamis atau enum statis. |
| Pemasok (Supplier) | Logistik | `Unknown` | `suppliers` | - | Missing | `id` (Auto-inc/UUID) | `code`, `name`, `address`, `phone` | `code` | Ya | - | Faktur | Menu Navigasi | Unknown | Diperlukan untuk penerimaan barang (Faktur) |
| Gudang / Unit Farmasi | Bersama | `Unknown` | `warehouses` | - | Missing | `id` (Auto-inc/UUID) | `code`, `name`, `is_active` | `code` | Ya | - | Mutasi, Stok Opname | Menu Navigasi | Unknown | Tempat stok obat disimpan |
| ICD-10 (Diagnosa) | Rawat Jalan | `Unknown` | `icd_10` | - | Missing | `id` (Auto-inc/UUID) | `code`, `description` | `code` | Tidak | - | Pemeriksaan | Standard | Unknown | Standar global, biasanya di-import statis |

### Keterangan Status:
- **A**: Sudah tersedia dan sesuai
- **B**: Sudah tersedia tetapi perlu diperluas
- **C**: Belum tersedia dan perlu dibuat
- **D**: Sudah diwakili tabel lain
- **E**: Tidak termasuk scope
- **F**: Perlu konfirmasi supervisor
