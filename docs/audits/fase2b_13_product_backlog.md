# FASE 2B.13 — PRODUCT BACKLOG & USULAN SPRINT STABILISASI (2 MINGGU)

**Tanggal:** 2026-11-26  
**Tujuan Sprint Usulan:** Menuntaskan seluruh temuan `P1/P2`, memverifikasi UAT browser seluruh Master Data `PENDING`/`UNVERIFIED`, dan mempersiapkan fondasi integrasi klinis tanpa regresi.

---

## 1. Product Backlog Terprioritaskan

| Order | ID Backlog | Temuan | Item / User Story | Prioritas | Estimasi | Dependensi | Bukti Verifikasi Diperlukan |
|---|---|---|---|---|---|---|---|
| 1 | **BL-01** | FND-01 | **Perbaiki Filter Jenis Tarif pada Ekspor Excel Tindakan Medis** — Sebagai admin tarif, saya ingin hasil ekspor Excel tindakan sesuai dengan filter jenis tarif yang dipilih agar laporan akurat. | `P1` | `3 SP` | `MedicalProcedureController`, Export class | `MedicalProcedureTest::test_export_with_tariff_type_filter` lulus `1/1` & full PHPUnit hijau |
| 2 | **BL-02** | FND-02 | **Perbaiki Validasi `province_id` pada `Store/UpdateRegencyRequest`** — Sebagai pengelola wilayah, saya dapat membuat dan mengubah data Kabupaten di mode DB internal maupun legacy tanpa 422 palsu. | `P1` | `2 SP` | `Province` / `LegacyProvince` | `RegencyControllerTest` lulus `9/9` |
| 3 | **BL-03** | FND-03 | **Isolasi Test Legacy pada `LegacyDoctorProfilePersistenceTest`** — Sebagai tim developer, suite `php artisan test` penuh harus berjalan deterministik tanpa kegagalan akibat sisa state koneksi `legacy` dari test sebelumnya. | `P2` | `2 SP` | `LegacyDoctorAndEmployeeCrudTest`, `LegacyAccess` | `php artisan test` mencapai **0 failures** untuk seluruh modul terkait |
| 4 | **BL-04** | FND-06 | **Eksekusi UAT Browser Batch 1 (Surat-Surat, Dokter, Pegawai, Pendidikan/Pekerjaan/Jabatan)** — Verifikasi manual desktop `1280px` & mobile `375px`, CRUD DB dev terisolasi, toast, validasi, & zero Console/Network errors. | `P2` | `3 SP` | Docker MySQL dev | Bukti checklist UAT `PASS` lengkap pada `fase2b_13_uat_matrix.md` |
| 5 | **BL-05** | FND-06 | **Eksekusi UAT Browser Batch 2 (7 Modul Data Bed: Gedung–Bed, Kelas, SIRS UI)** — Verifikasi alur hierarki dropdown bertingkat (Gedung → Lantai → Bangsal → Kamar → Bed + Kelas) & mobile `375px`. | `P2` | `2 SP` | Docker MySQL dev | Bukti checklist UAT `PASS` semua 7 rute `/master-data/bed/*` |
| 6 | **BL-06** | FND-04 | **Integrasi Dinamis Penjamin (`Guarantor`) ke Admisi & Pendaftaran + Guard Hapus Internal** — Sebagai petugas admisi, pilihan Penjamin berasal dari Master Data aktif (`/lookups/guarantors`) dan data terpakai terlindungi dari penghapusan. | `P2` | `5 SP` | BL-04, modul Registrasi & Admisi | PHPUnit integrasi `Guarantor`/`Admission` (`786 passed`) + Vitest Admisi/Pendaftaran (`1.200 passed`) **RESOLVED [CODE][TEST]** *(Fase 2B.15; UAT browser pending)* |
| 7 | **BL-07** | FND-05 | **Desain & Integrasi Backend Ketersediaan Bed SIRS** — Menghubungkan `SirsBedAvailabilityView` ke agregasi bed internal / bridging API Kemenkes SIRS Online. | `P2` | `8 SP` | BL-05, kredensial SIRS Kemenkes | API contract test + Vitest + UAT pada DB dev |
| 8 | **BL-08** | FND-07 | **Evaluasi Kebutuhan Sorting Dinamis Backend (`DietType`, `ActivityType`, `Position`)** — Menambahkan support query `sort`/`direction` ber-whitelist bila pengguna meminta pengurutan kolom di tabel. | `P2` | `1 SP` | Konfirmasi PO | PHPUnit sort test + Vitest |
| 9 | **BL-09** | FND-08 | **Pembersihan Lint Global Bertahap (Oxlint `14` & ESLint `214`)** — Menyelesaikan `vue/no-mutating-props` dan `no-unused-vars` saat standarisasi modul Klinis, Logistik, & Master Data lanjutan. | `P3` | `8 SP` | Standarisasi UI Pemeriksaan/Registrasi/Logistik | Penurunan progresif hingga `0` ESLint/Oxlint global |

---

## 2. Usulan Sprint Stabilisasi & Integrasi (Durasi: 2 Minggu / `15 SP` Kapasitas Fokus)

### Tujuan Sprint
Menjadikan seluruh backend suite `php artisan test` **0 kegagalan**, menuntaskan seluruh UAT visual Master Data Fase 2A & 2B yang tertunda (`9` PENDING + `4` UNVERIFIED), dan menyiapkan integrasi Master Data Penjamin ke Admisi/Pendaftaran.

### Ruang Lingkup Sprint (Minggu 1 & Minggu 2)

#### Minggu 1 — Stabilisasi Backend & Test Suite (`7 SP`)
- **BL-01 (`3 SP`):** Audit & perbaikan filter `tariff_type_id` pada ekspor Excel `MedicalProcedureTest` + regresi ekspor.
- **BL-02 (`2 SP`):** Audit & perbaikan koneksi validasi `province_id` pada `Regency` + verifikasi `RegencyControllerTest` `9/9` lulus.
- **BL-03 (`2 SP`):** Isolasi cleanup/reset koneksi `legacy` pada `LegacyDoctorProfilePersistenceTest` → target: `php artisan test` **0 failed**.

#### Minggu 2 — Penuntasan UAT Visual & Integrasi Penjamin (`8 SP`)
- **BL-04 (`3 SP`):** Sesi UAT terstruktur pada browser `1280px` & `375px` untuk `Surat-Surat`, `Dokter`, `Pegawai`, `Pendidikan`, `Pekerjaan`, `Jabatan` (menggunakan DB MySQL dev terisolasi & akun uji).
- **BL-05 (`2 SP`):** Sesi UAT terstruktur pada browser `1280px` & `375px` untuk seluruh hierarki `Data Bed` (Gedung → Bed, Kelas Kamar, & verifikasi state prototipe SIRS).
- **Rancang Desain BL-06 (`3 SP` bagian analisis/backend awal atau dipindah penuh ke Sprint berikutnya jika BL-04/BL-05 memakan waktu):** Tambah endpoint `GET /api/v1/lookups/guarantors` & proteksi hapus relasi `guarantor_id` pada mode DB internal.

### Definition of Done (DoD) Sprint
1. `php artisan test` lulus **100%** (hanya *intentional skip* bila MySQL live legacy tidak dikonek di environment in-memory).
2. `npx vitest run --pool=threads --maxWorkers=2`, `npx vite build`, serta lint `app/design-system` seluruhnya `0` kegagalan.
3. Matriks UAT [fase2b_13_uat_matrix.md](file:///d:/Magang%20SISfo/BigSirs/docs/audits/fase2b_13_uat_matrix.md) untuk minimal seluruh `18` modul CRUD aktif memiliki catatan verifikasi desktop `1280px` dan mobile `375px` dengan status `PASS`.
4. Snapshot backup SHA256 diperbarui (`0` mismatch) dan tidak menyentuh DB rumah sakit aktif.
