# FASE 2B.13 — REGISTER TEMUAN & RISIKO MASTER DATA BIGSIRS

**Tanggal:** 2026-11-26  
**Klasifikasi Prioritas:** `P0` (Kritis) · `P1` (Tinggi) · `P2` (Sedang) · `P3` (Rendah)

*(Catatan: Seluruh `19` modul Fase 2A & 2B lulus linting Design System (`0` error) dan Vitest (`149` file / `1.195` test). Tidak ditemukan temuan `P0` pada modul yang telah dimigrasi.)*

---

## Temuan `P1` — Prioritas Tinggi

### FND-01: Kegagalan Ekspor Tindakan Medis dengan Filter Jenis Tarif (`MedicalProcedureTest`)
- **Modul:** Master Data Tindakan (`MedicalProcedure` — di luar kelompok 2A/2B, ditemukan pada regresi backend gabungan).
- **Ringkasan:** Test `test_export_with_tariff_type_filter` gagal baik dalam suite gabungan maupun standalone karena baris yang seharusnya tersaring (`"Tariff Type Two"`) masih muncul di file XML ekspor Excel.
- **Bukti:** [MedicalProcedureTest.php](file:///d:/Magang%20SISfo/BigSirs/backend/tests/Feature/Api/V1/MasterData/MedicalProcedureTest.php#L570) — eksekusi `php artisan test tests/Feature/Api/V1/MasterData/MedicalProcedureTest.php --filter=test_export_with_tariff_type_filter` → `Failed asserting that '...Tariff Type Two...' does not contain "Tariff Type Two"` (`0/1` lulus).
- **Reproduksi:** Jalankan filter test di atas dengan SQLite in-memory.
- **Dampak:** Laporan ekspor Excel Tindakan dengan filter jenis tarif berpotensi mengekspor data yang tidak sesuai filter.
- **Rekomendasi:** Audit query filter `tariff_type_id` pada endpoint ekspor `MedicalProcedureController` dan query export class terkait.
- **Kompleksitas Relatif:** Sedang (`3` SP).
- **Dependensi:** Tidak ada pada modul 2A/2B.
- **Status Verifikasi:** **TERKONFIRMASI [TEST]** (reproduksibel standalone).

### FND-02: Kegagalan Create/Update Kabupaten karena Validasi `province_id` (`RegencyControllerTest`)
- **Modul:** Master Data Wilayah — Kabupaten (`Regency` — di luar kelompok 2A/2B).
- **Ringkasan:** Dua test CRUD (`test_can_create_regency` dan `test_can_update_regency`) gagal HTTP `422` (`The selected province id is invalid`) baik dalam suite gabungan maupun standalone.
- **Bukti:** [RegencyControllerTest.php](file:///d:/Magang%20SISfo/BigSirs/backend/tests/Feature/Api/V1/MasterData/RegencyControllerTest.php#L78-L119) — eksekusi standalone menghasilkan `7/9` lulus, `2` gagal.
- **Reproduksi:** Jalankan `php artisan test tests/Feature/Api/V1/MasterData/RegencyControllerTest.php`.
- **Dampak:** Pembuatan/perubahan data Kabupaten pada mode database internal/SQLite berisiko ditolak validasi `exists` pada `province_id` (indikasi ketidaksinkronan rule legacy vs internal atau koneksi DB pada rule validasi).
- **Rekomendasi:** Periksa `StoreRegencyRequest` / `UpdateRegencyRequest` untuk penentuan koneksi rule `Rule::exists` antara mode legacy dan internal (pola serupa perbaikan Fase 2B.9.1/2B.12.1).
- **Kompleksitas Relatif:** Rendah–Sedang (`2` SP).
- **Dependensi:** `Province` / `LegacyProvince`.
- **Status Verifikasi:** **TERKONFIRMASI [TEST]** (reproduksibel standalone).

---

## Temuan `P2` — Prioritas Sedang

### FND-03: Interferensi Urutan Eksekusi Test pada `LegacyDoctorProfilePersistenceTest`
- **Modul:** Dokter (`Doctor` — Mode Legacy).
- **Ringkasan:** `LegacyDoctorProfilePersistenceTest` lulus `3/3` (`84` assertions) saat dijalankan standalone atau setelah `DoctorTest`, tetapi gagal `2/3` (`line 241` dan `line 378`) ketika dijalankan dalam suite gabungan setelah `LegacyDoctorAndEmployeeCrudTest`.
- **Bukti:** Suite penuh `php artisan test` (`763` test) gagal di `line 241` (`null === '35'`) dan `line 378` (`null === 'Latex'`); eksekusi standalone lulus `3/3`.
- **Reproduksi:** Jalankan full `php artisan test`.
- **Dampak:** Mengganggu keandalan CI/CD pipeline backend saat menjalankan suite penuh secara berurutan, meski perilaku produksi endpoint aman saat terisolasi.
- **Rekomendasi:** Pastikan `setUp()`/`tearDown()` pada test legacy mereset konfigurasi koneksi `'legacy'`, menjalankan `LegacyAccess::clearCache()`, dan membersihkan schema in-memory `DB::connection('legacy')` agar tidak bocor antar kelas test.
- **Kompleksitas Relatif:** Sedang (`2` SP).
- **Dependensi:** `LegacyDoctorAndEmployeeCrudTest`, `LegacyAccess`.
- **Status Verifikasi:** **TERKONFIRMASI [TEST]** (order-dependent failure).

### FND-04: Modul Admisi & Pendaftaran Belum Memakai Master Data Penjamin (`Guarantor`) Dinamis
- **Modul:** Jenis Asuransi / Penjamin (`Guarantor`) ↔ Admisi & Pendaftaran.
- **Ringkasan:** Master Data Penjamin sudah mendukung CRUD kategori `BPJS`, `ASURANSI`, `PERUSAHAAN`, `UMUM` dan proteksi hapus legacy terhadap `trx_admisi`. Namun modul klinis frontend ([AdmissionEditView.vue](file:///d:/Magang%20SISfo/BigSirs/frontend/src/views/admission/AdmissionEditView.vue), [NewPatientView.vue](file:///d:/Magang%20SISfo/BigSirs/frontend/src/views/registration/NewPatientView.vue), `useAdmissions.js`) masih memakai daftar string hardcoded dan belum ada endpoint `/api/v1/lookups/guarantors`, serta `GuarantorController::destroy()` pada mode non-legacy belum memeriksa relasi admisi internal.
- **Bukti:** Pencarian `git grep` lintas `admission/`, `registration/`, `routes/api.php`, dan [GuarantorController.php](file:///d:/Magang%20SISfo/BigSirs/backend/app/Http/Controllers/Api/V1/MasterData/GuarantorController.php#L154-L189).
- **Dampak:** Penjamin baru yang ditambahkan admin melalui Master Data Jenis Asuransi tidak otomatis muncul sebagai pilihan di Pendaftaran/Admisi, dan di DB internal penjamin terpakai tidak terlindungi saat dihapus.
- **Rekomendasi:** Tambahkan endpoint lookup aktif `/lookups/guarantors` + guard hapus internal di `GuarantorController::destroy()` pada fase integrasi klinis terpisah, serta migrasikan dropdown Admisi/Pendaftaran ke lookup tersebut.
- **Kompleksitas Relatif:** Tinggi (`5` SP).
- **Dependensi:** Fase stabilisasi/migrasi Pendaftaran & Admisi.
- **Status Verifikasi:** **RESOLVED [CODE][TEST]** *(Fase 2B.15 — `/api/v1/lookups/guarantors`, `GuarantorValidation`, proteksi `destroy()` legacy `trx_admisi`/`ref_pasien` + dynamic internal table check, composable `useGuarantorOptions`, serta sinkronisasi Pendaftaran & Admisi teruji PHPUnit & Vitest; UAT browser end-to-end tetap menunggu eksekusi manual).*

### FND-05: Halaman Ketersediaan Bed SIRS Masih Prototipe Frontend-Only
- **Modul:** Ketersediaan Bed SIRS (`SirsBedAvailabilityView`).
- **Ringkasan:** Halaman tampil sesuai Design System namun belum terhubung ke endpoint backend SIRS Online Kemenkes maupun kalkulasi data Bed internal (`useSirsBedAvailability.js` diinisialisasi dengan array kosong `[]`).
- **Bukti:** [useSirsBedAvailability.js](file:///d:/Magang%20SISfo/BigSirs/frontend/src/composables/useSirsBedAvailability.js#L5-L23).
- **Dampak:** Fitur Ketersediaan Bed SIRS belum menampilkan data ketersediaan kamar rumah sakit sesungguhnya di environment produksi.
- **Rekomendasi:** Implementasikan endpoint agregasi ketersediaan bed / integrasi bridging SIRS Online di backend sebelum go-live pelaporan Kemenkes.
- **Kompleksitas Relatif:** Tinggi (`8` SP).
- **Dependensi:** Master Data Bed, Bangsal, Kelas, dan kredensial API SIRS Kemenkes.
- **Status Verifikasi:** **TERKONFIRMASI [CODE]**.

### FND-06: Cakupan UAT Browser Lintas Modul Belum Lengkap (`PENDING` / `UNVERIFIED`)
- **Modul:** Surat-Surat (`2B.12`), Dokter pasca-`2B.5.2`, seluruh 7 modul Bed (`2A`), serta `Pendidikan`, `Pekerjaan`, `Jabatan`, `Pegawai`.
- **Ringkasan:** Meskipun seluruh modul lolos Vitest, Vite Build, dan Design System lint, konfirmasi UAT browser aktual (`1280px` & `375px` + Console/Network bebas error) baru tercatat `PASS` untuk `6` modul (Jadwal Dokter, Poliklinik, Jenis Layanan, Jenis Asuransi, Asuhan Gizi, Jenis Kegiatan).
- **Bukti:** Matriks UAT [fase2b_13_uat_matrix.md](file:///d:/Magang%20SISfo/BigSirs/docs/audits/fase2b_13_uat_matrix.md).
- **Dampak:** Risiko regresi visual spesifik browser (mis. modal overflow, focus trap, responsivitas tabel mobile, atau error jaringan integrasi Docker MySQL) pada modul yang belum di-UAT tetap terbuka.
- **Rekomendasi:** Jadwalkan sesi eksekusi UAT manual/Playwright terstruktur pada database development terisolasi untuk seluruh modul `PENDING`/`UNVERIFIED`.
- **Kompleksitas Relatif:** Sedang (`3` SP).
- **Dependensi:** Docker MySQL development + akun uji.
- **Status Verifikasi:** **TERKONFIRMASI [UAT]**.

### FND-07: Backend Sorting Fixed pada `DietType`, `ActivityType`, & `Position`
- **Modul:** Asuhan Gizi (`DietType`), Jenis Kegiatan (`ActivityType`), Jabatan (`Position`).
- **Ringkasan:** Backend `DietTypeService::getPaginated`, `ActivityTypeService::getPaginated`, dan `PositionController::index` melakukan `orderBy` statis (`name`/`nama`/`jabatan asc`) tanpa membaca parameter sorting dinamis dari query request.
- **Bukti:** [ActivityTypeService.php](file:///d:/Magang%20SISfo/BigSirs/backend/app/Services/MasterData/ActivityTypeService.php#L36-L59), [DietTypeService.php](file:///d:/Magang%20SISfo/BigSirs/backend/app/Services/MasterData/DietTypeService.php#L31-L47), [PositionController.php](file:///d:/Magang%20SISfo/BigSirs/backend/app/Http/Controllers/Api/V1/MasterData/PositionController.php#L23-L38).
- **Dampak:** Jika UI nantinya menambahkan interaksi klik sort header tabel pada modul tersebut, urutan server tetap fixed ascending. Saat ini dampak fungsional minim karena header tabel ketiga modul tidak mengekspos tombol sort klik.
- **Rekomendasi:** Pertahankan saat ini; tambahkan dukungan `sort`/`direction` ber-whitelist pada backend hanya jika dibutuhkan pengguna.
- **Kompleksitas Relatif:** Rendah (`1` SP).
- **Dependensi:** Tidak ada.
- **Status Verifikasi:** **TERKONFIRMASI [CODE]**.

---

## Temuan `P3` — Prioritas Rendah & Technical Debt

### FND-08: Technical Debt Lint Global pada Modul Lama (`14` Oxlint, `214` ESLint)
- **Modul:** Pemeriksaan Pasien, Registrasi (`PatientSearchFilters.vue`), Logistik (`InvoiceView`, `MutationView`, `StockOpnameTable`, `PosSaleStockSelect`), dan Master Data non-2A/2B (`ProcedurePackageView.vue`).
- **Ringkasan:** Terdapat `14` error Oxlint dan `214` error ESLint pra-existing (dominan `vue/no-mutating-props` & `no-unused-vars`) di luar cakupan `19` modul yang sudah dimigrasi.
- **Bukti:** Output `npx oxlint .` (`686` file) dan `npx eslint --no-cache .`.
- **Dampak:** Menghambat penerapan hard-fail CI lint global sampai modul klinis/logistik tersebut ikut dimigrasi/dibersihkan.
- **Rekomendasi:** Bersihkan bertahap saat standarisasi UI modul Pemeriksaan, Registrasi, Logistik, dan Master Data lanjutan.
- **Kompleksitas Relatif:** Tinggi (`8` SP lintas modul).
- **Dependensi:** Standarisasi UI fase berikutnya.
- **Status Verifikasi:** **TERKONFIRMASI [TEST]**.

### FND-09: Perbedaan Semantik Endpoint Jenis Layanan (`Polyclinic` Enum vs `ServiceType` CRUD)
- **Modul:** Poliklinik (`Polyclinic`) & Jenis Layanan (`ServiceType`).
- **Ringkasan:** `/master-data/polyclinics/service-types` mengembalikan daftar statis `service_type` khusus atribut Poliklinik, berbeda dengan master data CRUD `/master-data/service-types` (`ref_jenis_layanan`).
- **Bukti:** [polyclinic.js](file:///d:/Magang%20SISfo/BigSirs/frontend/src/services/polyclinic.js#L12-L14) vs [serviceType.service.js](file:///d:/Magang%20SISfo/BigSirs/frontend/src/services/serviceType.service.js#L4).
- **Dampak:** Potensi kebingungan developer apabila mengira dropdown Jenis Layanan pada Poliklinik berasal dari master `ServiceTypeView`.
- **Rekomendasi:** Pertahankan pemisahan kontrak dan dokumentasikan jelas di arsitektur API; hindari menggabungkan tanpa pemetaan skema database legacy.
- **Kompleksitas Relatif:** Rendah (`1` SP dokumentasi).
- **Dependensi:** `Polyclinic` & `ServiceType`.
- **Status Verifikasi:** **TERKONFIRMASI [CODE]**.

### FND-10: Kompatibilitas Wrapper Service Mix di `Polyclinic` & `Bed` Modules vs `DietType`/`LetterType`
- **Modul:** Lintas service frontend (`services/` vs `services/master-data/`).
- **Ringkasan:** Sebagian service terletak di `src/services/*.js` (`polyclinic.js`, `employee.service.js`, `serviceType.service.js`, `dietType.service.js`, `activityType.service.js`, `letterType.service.js`), sebagian di `src/services/master-data/*.service.js` (`buildings`, `doctors`, `guarantors`, dll.), dan `PolyclinicFormModal` masih memiliki `axios` response shape khusus di `polyclinic.js`.
- **Bukti:** Inventarisasi direktori `frontend/src/services/`.
- **Dampak:** Kosmetik / inkonsistensi struktur folder ringan; seluruh fungsi berjalan normal dan seluruh alias kompatibilitas dijaga oleh unit test.
- **Rekomendasi:** Tidak perlu memindahkan path file yang sudah stabil agar tidak memicu regresi import pada modul lain.
- **Kompleksitas Relatif:** Rendah (`1` SP).
- **Dependensi:** Tidak ada.
- **Status Verifikasi:** **TERKONFIRMASI [CODE]**.
