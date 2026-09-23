# Mapping Tabel Legacy

## Temuan Audit Database Lama

- **Akses File:** SQL dump legacy telah disediakan secara lokal dan dianalisis secara *read-only* (termasuk *Data Profiling* untuk menguji kualitas referensi lama).
- **Tabel Legacy Ditemukan:**
  - `ref_poliklinik` (sebagai basis untuk `Polyclinic`)
  - `ref_jenis_asuransi` (sebagai basis untuk `Guarantor`)
- **Detail Pemetaan:**
  Analisis lengkap *CREATE TABLE*, struktur kunci, dan rekomendasi target telah dipisahkan ke dalam dokumen khusus:
  - [Mapping Poliklinik](polyclinic-mapping.md)
  - [Mapping Penjamin](guarantor-mapping.md)

## Catatan Relasi
Berdasarkan struktur DDL:
- **Confirmed Relasi:** `ref_poliklinik` terbukti memiliki `FOREIGN KEY` dari `trx_jadwal_dokter` dan `trx_visit`.
- **Inferred Relasi:** `ref_jenis_asuransi` tidak memiliki `FOREIGN KEY` secara database dari tabel transaksi, namun di-*infer* dari penamaan kolom `id_jenis_asuransi` di `ref_pasien` dan `trx_admisi`.

Tabel mapping ini akan diisi secara lengkap setelah *absolute path* file SQL dump *legacy* diberikan.
