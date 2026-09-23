# Guarantor Mapping

Berdasarkan audit dari SQL dump legacy (`backup_db_simrs_masking-202609101406.sql`), berikut adalah pemetaan untuk entitas Penjamin (Asuransi/Payer).

## Tabel Legacy
- **Nama Tabel:** `ref_jenis_asuransi`
- **Primary Key:** `id` (int(11) NOT NULL AUTO_INCREMENT)
- **Unique Constraint:** Tidak ditemukan unique constraint eksplisit pada field `kode`.
- **Foreign Key:** Tidak ada foreign key eksplisit ke tabel lain.

### Struktur Kolom Legacy
- `id` int(11)
- `jenis_asuransi` varchar(50)
- `pemerintah` tinyint(1)
- `status` int(1)
- `created_at` datetime
- `updated_at` datetime
- `deleted_at` datetime
- `id_user` int(11)
- `type` enum('UMUM','BPJS','PRIVATE')
- `id_inacbg` varchar(10)
- `kode` varchar(50)

## Analisis Referensi
- **Tabel Transaksi yang mereferensikan:**
  - `ref_pasien` (kolom `id_jenis_asuransi` - Inferred via nama kolom)
  - `trx_admisi` (kolom `id_jenis_asuransi` - Inferred via nama kolom)
- **Tipe identifier:** `int(11)`. Relasi tidak memiliki constraint `FOREIGN KEY` secara database level pada struktur yang ditinjau.

## Profiling Data Legacy
Statistik berdasarkan 8 baris dari `ref_jenis_asuransi`:
- **Semantik Entitas:** Tabel ini mencampuradukkan "Cara Bayar" (contoh: `UMUM`, `GRATIS`) dengan "Klasifikasi Penjamin BPJS" (contoh: `BPJS Pensiunan/Veteran`, `PEKERJA MANDIRI`). Ini lebih tepat dimodelkan sebagai entitas `guarantors` dengan pengelompokan (kategori).
- **Kualitas Kode:** Sangat buruk. 7 dari 8 record memiliki kode `NULL`. Hanya 1 record yang memiliki kode (yaitu `13`). Tidak ada duplikat pada kode maupun nama.
- **Kualitas Data:**
  - Semua record (8) berstatus Aktif (`1`).
  - Tidak ada record yang ter-*soft-delete*.
  - `pemerintah` memiliki distribusi 5 (Ya) dan 3 (Tidak).
  - `id_inacbg` hanya digunakan pada 2 record.

## Rancangan Target Minimal (Evaluasi)

Berdasarkan hasil *profiling*, skema target `guarantors` dievaluasi sebagai berikut:

| Target field | Legacy source | Keputusan | Alasan | Confidence |
| ------------ | ------------- | --------- | ------ | ---------- |
| `id` | `id` | **Approved** | Standar Laravel (`bigint unsigned auto_increment`). | Confirmed |
| `legacy_id` | `id` | **Approved** | Untuk penelusuran data lama saat migrasi. | Confirmed |
| `code` | `kode` | **Needs Decision** | Di legacy nyaris kosong (7 dari 8 NULL). Membuatnya `UNIQUE` menuntut pembuatan format *auto-generate* untuk semua data. | Confirmed |
| `name` | `jenis_asuransi` | **Approved** | (Rename) Nama cara bayar/penjamin. | Confirmed |
| `type` | `type` | **Approved** | (UMUM, BPJS, PRIVATE). Valid karena menampung cara bayar utama. | Confirmed |
| `bpjs_code` | - | **Rejected** | Tidak ada dukungan data di legacy (kosong). | Confirmed |
| `is_active` | `status` | **Approved** | Ubah dari integer 1/0 ke boolean. | Confirmed |
| - | `pemerintah` | **Drop** | Bisa dimasukkan ke logika/relasi *type* BPJS tanpa harus hardcode kolom di sini. | Inferred |
| - | `id_inacbg` | **Drop** | Hampir tidak digunakan. Logika tarif casemix seharusnya diatur di modul Casemix. | Inferred |

## Aturan Cleansing Data
- **Pembuatan Kode Baru:** Karena kode lama kosong, sistem *migrator* harus meng-generate kode baru secara *sequensial* (contoh: `ASR-001`).
- **Normalisasi Nama:** Wajib *Trim* dan *Title Case*.
- **Mapping Orphan:** Tabel transaksi yang mengarah ke asuransi yang sudah dihapus/tidak wajar perlu di-*fallback* ke entitas "UMUM".

## Keputusan Teknis yang Membutuhkan Supervisor
1. **Generasi Kode Asuransi:** Haruskah kita meng-generate kode otomatis saat migrasi karena 7 dari 8 data legacy tidak memiliki kode?
2. **Ketergantungan Eksternal (id_inacbg):** Apakah penghapusan field `id_inacbg` disetujui untuk di-refactor ke modul terpisah?
