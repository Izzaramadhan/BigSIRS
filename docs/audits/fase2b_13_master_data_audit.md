# FASE 2B.13 — AUDIT MENYELURUH MASTER DATA BIGSIRS

**Tanggal Audit:** 2026-11-26  
**Scope:** Seluruh 19 Modul Master Data Hasil Pengerjaan Fase 2A (Data Bed) & Fase 2B.1–2B.12 (Data Dasar, SDM/Dokter, Poliklinik, Layanan, Penjamin, Gizi, Kegiatan, Surat-Surat)  
**Mode Pengerjaan:** **100% READ-ONLY** terhadap source code, test, schema database, dan manifest backup. Tidak ada kode aplikasi, konfigurasi, maupun database yang diubah.

---

## 1. Executive Summary

- **Status Konsistensi Design System (19 Modul):** **TERVERIFIKASI MEMENUHI STANDAR** melalui pemindaian statis terhadap `80` file `.vue` di 19 direktori modul + View terkait (`0` `<style>`, `0` `master-data.css`, `0` `AppToast`, `0` komponen `MasterData*`, `0` warna hex `#...`, `0` SVG inline manual, `0` `BaseAlert` salah prop) serta **0 error / 0 warning** pada ESLint (`app/design-system`) maupun Oxlint terfokus. Tailwind Preflight global tetap dinonaktifkan (`source(none)` + `@source` selektif di `frontend/src/assets/styles/index.css`).
- **Status Pengujian Terotomatisasi:**
  - **Frontend Full Vitest:** **`149` file / `1.195` test LULUS** (`63.49s`, `0` gagal).
  - **Vite Production Build:** **BERHASIL** (`1.76s`).
  - **Oxlint & ESLint Global:** `14` error Oxlint lama (`686` file) dan `214` error ESLint lama (`0` warning) — **seluruhnya berada di luar modul yang sudah dimigrasi** (`0` error baru).
  - **Backend PHPUnit (SQLite in-memory):** **`763` test, `757` lulus, `5` gagal pra-existing (`0` di modul 2B.1–2B.12 pada eksekusi standalone selain interferensi urutan test), `1` skipped (`DirectAccessVerificationTest`), `5.760` assertions**. Perbaikan otorisasi `LetterTypeTest` (Fase 2B.12.1) terkonfirmasi stabil (`11/11` lulus).
- **Status Keamanan & Integritas Backup:** Verifikasi menyeluruh `43` manifest SHA256 (`416` entri) mencatat **`0` mismatch**. Working tree Git memiliki `251` entri WIP pra-eksisting; belum ada `commit` atau `push`.
- **Keputusan Integrasi Lanjutan (`GO` vs `NO-GO`):** **`NO-GO` untuk deklarasi UAT final lintas seluruh Master Data** dan **`HOLD` integrasi langsung ke modul klinis** sampai:
  1. Seluruh UAT visual browser yang masih `PENDING` (terutama **Surat-Surat Fase 2B.12**, **Dokter pasca-2B.5.2**, serta sub-modul **Bed/Pendidikan/Pekerjaan/Jabatan/Pegawai** yang belum terkonfirmasi di sesi ini) diverifikasi `PASS`.
  2. Tiga temuan backend test/bug pra-existing (`MedicalProcedureTest` ekspor filter, `RegencyControllerTest` validasi `province_id`, dan interferensi urutan test `LegacyDoctorProfilePersistenceTest`) serta backlog `P1/P2` diselesaikan pada sprint stabilisasi terpisah.

---

## 2. Metodologi & Tagging Bukti Verifikasi

Setiap status pada dokumen ini dibedakan tegas berdasarkan bukti aktual yang diamati:
- **[CODE]** — Terverifikasi langsung dari source code / konfigurasi pada repo `D:\Magang SISfo\BigSirs`.
- **[TEST]** — Terverifikasi langsung dari eksekusi Vitest / PHPUnit / Vite Build / ESLint / Oxlint pada Fase 2B.13.
- **[UAT]** — Terverifikasi dari konfirmasi UAT pengguna yang tercatat eksplisit dalam laporan sesi.
- **[UNVERIFIED]** — Belum memiliki bukti konfirmasi UAT visual browser pada catatan sesi ini.

---

## 3. Inventarisasi Menyeluruh 19 Modul Master Data

### 3A. Master Data Bed — Fase 2A (`7` Modul)

| # | Modul | Route Name & Path | View & Komponen UI | Composable & Service | Helper & Unit Test | Backend Controller / Internal & Legacy Model | Status UI & DS | Status UAT | Dependensi / Catatan |
|---|---|---|---|---|---|---|---|---|---|
| 1 | **Gedung** | `master-data.buildings` (`/master-data/bed/buildings`) **[CODE]** | `bed/BuildingView.vue`, `buildings/{BuildingFilters,BuildingTable,BuildingFormModal}.vue` **[CODE]** | `useBuildings.js`, `buildings.service.js` **[CODE]** | `building.helpers.js`, `building.helpers.spec.js`, `BuildingView.spec.js` **[CODE][TEST]** | `BuildingController`, `Building`, `LegacyBuilding` (`ref_gedung`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PENDING / [UNVERIFIED]** (sub-modul Bed tertunda) | Induk hierarki Lantai/Bangsal/Kamar/Bed |
| 2 | **Lantai** | `master-data.floors` (`/master-data/bed/floors`) **[CODE]** | `bed/FloorView.vue`, `floors/{FloorFilters,FloorTable,FloorFormModal}.vue` **[CODE]** | `useFloors.js`, `useBuildingOptions.js`, `floors.service.js` **[CODE]** | `floor.helpers.js`, `floor.helpers.spec.js`, `FloorView.spec.js` **[CODE][TEST]** | `FloorController`, `Floor`, `LegacyFloor` (`ref_lantai`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PENDING / [UNVERIFIED]** | Bergantung lookup Gedung (`Building`) |
| 3 | **Bangsal** | `master-data.wards` (`/master-data/bed/wards`) **[CODE]** | `bed/WardView.vue`, `wards/{WardFilters,WardTable,WardFormModal}.vue` **[CODE]** | `useWards.js`, `useFloorOptions.js`, `wards.service.js` **[CODE]** | `ward.helpers.js`, `ward.helpers.spec.js`, `WardView.spec.js` **[CODE][TEST]** | `WardController`, `Ward`, `LegacyWard` (`ref_bangsal`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PENDING / [UNVERIFIED]** | Bergantung lookup Lantai (`Floor`) |
| 4 | **Kamar** | `master-data.rooms` (`/master-data/bed/rooms`) **[CODE]** | `bed/RoomView.vue`, `rooms/{RoomFilters,RoomTable,RoomFormModal,AddRoomClassDialog}.vue` **[CODE]** | `useRooms.js`, `useWardOptions.js`, `useRoomClassOptions.js`, `rooms.service.js` **[CODE]** | `room.helpers.js`, `room.helpers.spec.js`, `RoomView.spec.js`, `AddRoomClassDialog.spec.js` **[CODE][TEST]** | `RoomController`, `Room`, `LegacyRoom` (`ref_kamar`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PENDING / [UNVERIFIED]** | Bergantung Bangsal & Kelas (`/room-classes/options`) |
| 5 | **Bed** | `master-data.beds` (`/master-data/bed/beds`) **[CODE]** | `bed/BedView.vue`, `beds/{BedFilters,BedTable,BedFormModal}.vue` **[CODE]** | `useBeds.js`, `useRoomOptions.js`, `beds.service.js` **[CODE]** | `bed.helpers.js`, `bed.helpers.spec.js`, `BedView.spec.js` **[CODE][TEST]** | `BedController`, `Bed`, `LegacyBed` (`ref_bed`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PENDING / [UNVERIFIED]** | Bergantung Kamar (`Room`) |
| 6 | **Kelas Kamar** | `master-data.room-classes` (`/master-data/bed/room-classes`) **[CODE]** | `bed/RoomClassView.vue`, `room-classes/{RoomClassFilters,RoomClassTable,RoomClassFormModal}.vue` **[CODE]** | `useRoomClasses.js`, `useRoomClassOptions.js`, `roomClasses.service.js` **[CODE]** | `room-class.helpers.js`, `room-class.helpers.spec.js`, `RoomClassView.spec.js` **[CODE][TEST]** | `RoomClassController`, `RoomClass`, `LegacyRoomClass` (`ref_kelas`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PENDING / [UNVERIFIED]** | Dipakai oleh `RoomView` & `AddRoomClassDialog` |
| 7 | **Ketersediaan Bed SIRS** | `master-data.sirs-bed-availability` (`/master-data/bed/sirs-availability`) **[CODE]** | `bed/SirsBedAvailabilityView.vue`, `sirs-bed-availability/{SirsBedAvailabilityToolbar,SirsBedAvailabilityTable}.vue` **[CODE]** | `useSirsBedAvailability.js` (**prototipe frontend-only**, tanpa API service) **[CODE]** | `sirs-bed-availability.helpers.js`, `sirs-bed-availability.helpers.spec.js`, `SirsBedAvailabilityView.spec.js` **[CODE][TEST]** | **Belum ada endpoint backend / model** **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PENDING** (prototipe UI) | Belum terhubung API Kemenkes / SIRS atau data bed aktual |

### 3B. Master Data Dasar & Dokter — Fase 2B (`12` Modul)

| # | Modul | Route Name & Path | View & Komponen UI | Composable & Service | Helper & Unit Test | Backend Controller / Internal & Legacy Model | Status UI & DS | Status UAT | Dependensi / Catatan |
|---|---|---|---|---|---|---|---|---|---|
| 8 | **Pendidikan** | `master-data.educations` (`/master-data/basic-data/educations`) **[CODE]** | `EducationView.vue`, `educations/{EducationFilters,EducationTable,EducationFormModal}.vue` **[CODE]** | `useEducations.js`, `educations.service.js` (adapter `sort_by`/`sort_desc`) **[CODE]** | `education.helpers.js`, `education.helpers.spec.js`, `EducationView.spec.js` **[CODE][TEST]** | `EducationController`, `Education`, `LegacyEducation` (`ref_pendidikan`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **[UNVERIFIED]** | Dipakai lookup `/lookups/educations` (Pegawai/Dokter); proteksi hapus `409` |
| 9 | **Pekerjaan** | `master-data.occupations` (`/master-data/basic-data/occupations`) **[CODE]** | `OccupationView.vue`, `occupations/{OccupationFilters,OccupationTable,OccupationFormModal}.vue` **[CODE]** | `useOccupations.js`, `occupations.service.js` (adapter `sort_by`/`sort_desc`) **[CODE]** | `occupation.helpers.js`, `occupation.helpers.spec.js`, `OccupationView.spec.js` **[CODE][TEST]** | `OccupationController`, `Occupation`, `LegacyOccupation` (`ref_pekerjaan`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **[UNVERIFIED]** | Dipakai lookup `/lookups/occupations`; proteksi hapus `409` |
| 10 | **Jabatan** | `master-data.positions` (`/master-data/positions`) **[CODE]** | `PositionView.vue`, `positions/{PositionFilters,PositionTable,PositionFormModal}.vue` **[CODE]** | `usePositions.js`, `positions.service.js` **[CODE]** | `position.helpers.js`, `position.helpers.spec.js`, `PositionView.spec.js` **[CODE][TEST]** | `PositionController`, `Position`, `LegacyPosition` (`ref_jabatan`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **[UNVERIFIED]** | Dipakai lookup `/lookups/positions` (`employee.service.js`) |
| 11 | **Pegawai** | `master-data.employees` (`/master-data/employees`) **[CODE]** | `EmployeeView.vue`, `employees/{EmployeeFilters,EmployeeTable}.vue`, `EmployeeFormModal.vue` **[CODE]** | `useEmployees.js`, `employee.service.js` (adapter `sort_by`/`sort_desc` & unwrapper paginator) **[CODE]** | `employee.helpers.js`, `employee.helpers.spec.js`, `EmployeeView.spec.js`, `EmployeeFormModal.spec.js` **[CODE][TEST]** | `EmployeeController`, `Employee`, `LegacyEmployee` (`ref_pegawai`) + `LegacyPerson` (`master_person_index`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **[UNVERIFIED]** (perbaikan 2B.5.1) | Dipakai `/lookups/employees` oleh modul Dokter (`AsyncEmployeeSelect`) |
| 12 | **Dokter** | `master-data.doctors` (`/master-data/doctors`) **[CODE]** | `DoctorView.vue`, `doctors/{DoctorFilters,DoctorTable,DoctorFormModal,DoctorDetailModal,DoctorEmptyState}.vue` **[CODE]** | `useDoctors.js`, `doctors.service.js` (adapter `sort_by`/`sort_desc`) **[CODE]** | `doctor.helpers.js`, `doctor.helpers.spec.js`, `Doctor{View,Table,FormModal,DetailModal}.spec.js` **[CODE][TEST]** | `DoctorController`, `Doctor`, `LegacyDoctor` (`ref_dokter`) + `LegacyEmployee`/`LegacyPerson` **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PENDING** pasca-stabilisasi 2B.5.2 **[UAT]** | Dipakai `/lookups/doctors` oleh `DoctorScheduleFormModal` & klinis |
| 13 | **Jadwal Dokter** | `master-data.doctor-schedules` (`/master-data/doctor-schedules`) **[CODE]** | `DoctorScheduleView.vue`, `doctor-schedules/{DoctorScheduleFilters,DoctorScheduleTable,DoctorScheduleRecapTable,DoctorScheduleFormModal}.vue` **[CODE]** | `useDoctorSchedules.js`, `doctor-schedules.service.js` (adapter `sort_by`/`sort_dir`) **[CODE]** | `doctor-schedule.helpers.js`, `doctor-schedule.helpers.spec.js`, `DoctorSchedule{View,FormModal,RecapTable}.spec.js` **[CODE][TEST]** | `DoctorScheduleController`, `DoctorSchedule`, `LegacyDoctorSchedule` (`trx_jadwal_dokter`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PASS** **[UAT]** | Konsumsi `/lookups/doctors` dan `/master-data/polyclinics` |
| 14 | **Poliklinik** | `master-data.polyclinics` (`/master-data/polyclinics`) **[CODE]** | `PolyclinicView.vue`, `polyclinics/{PolyclinicFilters,PolyclinicTable,PolyclinicFormModal,PolyclinicStatusBadge}.vue` **[CODE]** | `usePolyclinics.js`, `polyclinic.js` **[CODE]** | `polyclinic.helpers.js`, `polyclinic.helpers.spec.js`, `Polyclinic{View,FormModal}.spec.js` **[CODE][TEST]** | `PolyclinicController`, `Polyclinic`, `LegacyPolyclinic` (`ref_poliklinik`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PASS** **[UAT]** | Dipakai `DoctorSchedule`, `Procedure`, `LegacyVisit` (`trx_visit`); proteksi hapus `409` |
| 15 | **Jenis Layanan** | `master-data.service-types` (`/master-data/service-types`) **[CODE]** | `ServiceTypeView.vue`, `service-types/{ServiceTypeFilters,ServiceTypeTable,ServiceTypeFormModal}.vue` **[CODE]** | `useServiceTypes.js`, `serviceType.service.js` (adapter `direction` -> `order`) **[CODE]** | `service-type.helpers.js`, `service-type.helpers.spec.js`, `ServiceType{View,FormModal}.spec.js` **[CODE][TEST]** | `ServiceTypeController`, `ServiceType`, `LegacyServiceType` (`ref_jenis_layanan`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PASS** **[UAT]** | Terpisah dari enum `/master-data/polyclinics/service-types` |
| 16 | **Jenis Asuransi / Penjamin** | `master-data.insurance-types` (`/master-data/basic-data/insurance-types`) **[CODE]** | `InsuranceTypeView.vue`, `insurance-types/{InsuranceTypeFilters,InsuranceTypeTable,InsuranceTypeFormModal}.vue` **[CODE]** | `useGuarantors.js`, `guarantors.service.js` **[CODE]** | `insurance-type.helpers.js`, `insurance-type.helpers.spec.js`, `InsuranceType{View,FormModal}.spec.js` **[CODE][TEST]** | `GuarantorController`, `Guarantor`, `LegacyGuarantor` (`ref_jenis_asuransi`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PASS** **[UAT]** | Proteksi hapus legacy terhadap `trx_admisi` (`422`); frontend Admisi belum memakai lookup dinamis |
| 17 | **Asuhan Gizi** | `master-data.nutrition-care` (`/master-data/nutrition-care`) **[CODE]** | `DietTypeView.vue`, `nutrition-care/{DietTypeFilters,DietTypeTable,DietTypeFormModal}.vue` **[CODE]** | `useDietTypes.js`, `dietType.service.js` **[CODE]** | `diet-type.helpers.js`, `diet-type.helpers.spec.js`, `DietType{View,FormModal}.spec.js` **[CODE][TEST]** | `DietTypeController`, `DietType`, `LegacyDietType` (`ref_jenis_diet`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PASS** **[UAT]** | Backend fixed sort `name`/`nama asc` |
| 18 | **Jenis Kegiatan** | `master-data.activity-types` (`/master-data/activity-types`) **[CODE]** | `ActivityTypeView.vue`, `activity-type/{ActivityTypeFilters,ActivityTypeTable,ActivityTypeFormModal}.vue` **[CODE]** | `useActivityTypes.js`, `activityType.service.js` (`/lookup`) **[CODE]** | `activity-type.helpers.js`, `activity-type.helpers.spec.js`, `ActivityType{View,FormModal}.spec.js` **[CODE][TEST]** | `ActivityTypeController`, `ActivityType`, `LegacyActivityType` (`ref_jenis_kegiatan`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PASS** **[UAT]** | Dipakai `RadiologyGroupModal` (`/activity-types/lookup`); proteksi hapus induk `422` |
| 19 | **Surat-Surat** | `master-data.letter-types` (`/master-data/letter-types`) **[CODE]** | `LetterTypeView.vue`, `letter-types/{LetterTypeFilters,LetterTypeTable}.vue`, `LetterTypeFormModal.vue` **[CODE]** | `useLetterTypes.js`, `letterType.service.js` (adapter `sort_by`/`sort_dir`) **[CODE]** | `letter-type.helpers.js`, `letter-type.helpers.spec.js`, `letterType.service.spec.js`, `LetterType{View,FormModal}.spec.js` **[CODE][TEST]** | `LetterTypeController`, `LetterType`, `LegacyLetterType` (`ref_jenis_surat`) **[CODE]** | Migrasi DS lengkap, ESLint/Oxlint `0` **[TEST]** | **PENDING** **[UAT]** | Otorisasi update diperbaiki Fase 2B.12.1; guard XSS `legacy_resource` |

---

## 4. Hasil Audit Design System (`80` File Vue Migrasi)

- **Tailwind CSS v4 & Tokens:** `frontend/src/assets/styles/index.css` memuat `@layer theme, base, legacy, components, utilities;`, mempertahankan `@import 'tailwindcss/utilities.css' layer(utilities) source(none);` (Tailwind Preflight nonaktif aman untuk legacy views), dan mendaftarkan `@source` untuk seluruh direktori komponen & view ke-19 modul.
- **Guard ESLint (`app/design-system`):** `frontend/eslint.config.js` mendaftarkan seluruh `21` glob komponen & view (`components/ui` + 19 modul).
- **Hasil Verifikasi Statis & Linter:**
  - `npx eslint --no-cache <21 targets>`: **`0` error, `0` warning**.
  - `npx oxlint <21 targets>`: **`0` error, `0` warning** (`Finished in 12ms on 21 files`).
  - Pemindaian regex lintas `80` file `.vue` di 19 modul:
    - `<style` / `master-data.css`: **`0` temuan**.
    - `AppToast` lokal / `<MasterData*>`: **`0` temuan**.
    - Warna hex `#...` / `<svg>` manual inline: **`0` temuan**.
    - `BaseAlert` dengan prop `:title` keliru: **`0` temuan**.

---

## 5. Audit CRUD, Kontrak API, & Database Internal vs Legacy

### 5A. Pemetaan Parameter Sorting (Service ↔ Backend)
- **`sort` + `direction` langsung (tanpa adapter):** `buildings`, `floors`, `wards`, `rooms`, `beds`, `roomClasses`, `polyclinics`, `guarantors` → sesuai controller masing-masing **[CODE]**.
- **Adapter `sort`/`direction` → `sort_by`/`sort_desc`:** `educations`, `occupations`, `employees`, `doctors` **[CODE]**.
- **Adapter `sort`/`direction` → `sort_by`/`sort_dir`:** `doctor-schedules`, `letter-types` **[CODE]**.
- **Adapter `direction` → `order`:** `service-types` (`sort` & `order`) **[CODE]**.
- **Fixed backend sorting (`orderBy('name'/'nama'/'jabatan', 'asc')`):** `positions`, `diet-types`, `activity-types` — composable mengirim parameter standar namun backend tidak membaca query sort dinamis **[CODE]**.
- **Tanpa endpoint backend (frontend-only prototype):** `sirs-bed-availability` **[CODE]**.

### 5B. SoftDeletes, Perlindungan Hapus, & Kompatibilitas Legacy
- Seluruh 18 modul yang terhubung database mendukung mode ganda **Eloquent internal** dan **Legacy (`LegacyAccess::isAvailable(...)`)** dengan `SoftDeletes`.
- Perlindungan hapus relasional terverifikasi pada backend:
  - `Education` (`409`), `Occupation` (`409`), `Polyclinic` (`409`), `ActivityType` (`422` pada `errors.id` jika punya turunan), `Guarantor` (`422` pada mode legacy bila direferensikan `trx_admisi.id_jenis_asuransi`).
- Update parsial & field nullable: `Doctor` & `UpdateGuarantorRequest` sudah distabilkan pada fase 2B.5.2 dan 2B.9.1; `UpdateLetterTypeRequest` distabilkan pada Fase 2B.12.1.

---

## 6. Audit Integrasi Antarmodul

1. **Dokter & Jadwal Dokter (`Doctor` ↔ `DoctorSchedule`):** `DoctorScheduleFormModal` memanggil `/lookups/doctors` via `lookupService`, mempertahankan opsi dokter tersimpan selama edit melalui `ensureSavedDoctorOptions()`, dan menampilkan rekap mingguan (`DoctorScheduleRecapTable`) **[CODE][TEST]**.
2. **Poliklinik & Jadwal Dokter (`Polyclinic` ↔ `DoctorSchedule`):** `DoctorScheduleFormModal` memanggil `polyclinicService.getPolyclinics(...)`, menggunakan `ensureSavedPolyclinicOptions()`, dan tabel/rekap merender nama Poliklinik secara konsisten **[CODE][TEST]**.
3. **Poliklinik vs Jenis Layanan (`Polyclinic::serviceTypes` vs `ServiceType`):** `PolyclinicFormModal` memakai endpoint `/master-data/polyclinics/service-types` (opsi enum `service_type` poliklinik), terpisah dari master data CRUD `/master-data/service-types` (`ref_jenis_layanan`). Tidak terjadi konflik kontrak **[CODE]**.
4. **Jenis Asuransi (`Guarantor`) & Admisi/Pendaftaran:** Backend legacy melindungi hapus `ref_jenis_asuransi` bila dipakai `trx_admisi`. Namun di **frontend** `AdmissionView`, `AdmissionEditView`, `AdmissionEditModal`, `NewPatientView`, dan `useAdmissions`, opsi/filter penjamin masih hardcoded string/enum (`'UMUM'`, `'BPJS Kesehatan'`) dan belum tersedia `/api/v1/lookups/guarantors` **[CODE]** → dicatat sebagai temuan `P2`.
5. **Jenis Kegiatan & Radiologi (`ActivityType` ↔ `RadiologyGroup`):** `RadiologyGroupModal` mengonsumsi `/master-data/activity-types/lookup` (dengan fallback `?per_page=200`). Kontrak terjaga utuh (`RadiologyGroupsView.spec.js` & `RadiologyGroupModal.spec.js` lulus) **[CODE][TEST]**.
6. **Surat-Surat (`LetterType`):** `UpdateLetterTypeRequest::authorize()` telah me-resolve model pada mode internal & legacy (`11/11` PHPUnit lulus), `Lihat Format Lama` mengamankan `legacy_resource` dari skema `javascript:`/`data:`, dan tidak ditemukan pemanggil klinis langsung yang terlewat **[CODE][TEST]**.

---

## 7. Ringkasan Eksekusi Pengujian Terkini (Fase 2B.13)

| Pengujian / Pemeriksaan | Hasil Aktual | Catatan |
|---|---|---|
| **Backend `php artisan test` (SQLite in-memory)** | `763` test: **`757` lulus**, `5` gagal, `1` skipped (`5.760` assertions, `12.42s`) | Membaik dari `6` gagal (Fase 2B.10) setelah fix `LetterTypeTest`. Seluruh `5` gagal adalah technical debt pra-eksisting di modul lain. |
| **`LegacyDoctorProfilePersistenceTest` (standalone)** | **`3/3` lulus** (`84` assertions, `623ms`) | Kegagalan di full suite (`2/3` fail) terkonfirmasi akibat interferensi urutan test. |
| **`MedicalProcedureTest` filter ekspor (standalone)** | **`0/1` gagal** (`line 570`) | Reproduksibel secara terpisah (bug pra-eksisting di luar Master Data 2A/2B). |
| **`RegencyControllerTest` (standalone)** | **`7/9` lulus, `2` gagal** (`lines 78, 119`: `422 province_id`) | Reproduksibel secara terpisah (bug pra-eksisting validasi wilayah). |
| **Frontend Full Vitest (`--pool=threads --maxWorkers=2`)** | **`149` file / `1.195` test LULUS** (`63.49s`, `0` gagal) | Sama dengan baseline Fase 2B.12. |
| **Vite Production Build (`npx vite build`)** | **BERHASIL** (`1.76s`) | Zero build warnings/errors pada modul migrasi. |
| **Oxlint Global (`npx oxlint .`)** | `14` error lama, `0` warning (`686` file) | **`0` error baru** (`app/design-system` `0` error). |
| **ESLint Global (`npx eslint --no-cache .`)** | `214` error lama, `0` warning | **`0` error baru** (`app/design-system` `0` error). |
| **Backup & Integritas SHA256** | `43` manifest, `416` entri, **`0` mismatch** | Snapshot lengkap dari Fase 0 hingga 2B.12. |

---

## 8. Keputusan GO / NO-GO & Prasyarat

- **Status Kelengkapan Teknis Kode & Test Modul 2A–2B.12:** **MEMADAI (dengan 2 celah integrasi fungsional non-blocking: `SirsBedAvailability` API & lookup `Guarantor` di Admisi/Pendaftaran)**.
- **Keputusan Akhir Audit Fase 2B.13:** **`NO-GO` untuk penutupan UAT final / merge produksi**, dengan status **`CONDITIONAL GO`** untuk masuk ke Sprint Stabilisasi & Penyelesaian UAT apabila prasyarat berikut dipenuhi:
  1. **Selesaikan UAT Browser** untuk **Surat-Surat (Fase 2B.12)**, **Dokter (pasca-2B.5.2)**, dan seluruh modul Bed/SDM yang masih `PENDING` / `BELUM DIVERIFIKASI`.
  2. **Selesaikan Technical Debt Backend `P1/P2`** (`RegencyControllerTest`, `MedicalProcedureTest::test_export_with_tariff_type_filter`, dan isolasi test `LegacyDoctorProfilePersistenceTest`) pada fase stabilisasi terpisah.
  3. **Selesaikan Integrasi `P2`** lookup Penjamin dinamis di Admisi/Pendaftaran & integrasi API Ketersediaan Bed SIRS sebelum rilis klinis end-to-end.
