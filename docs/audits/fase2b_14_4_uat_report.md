# FASE 2B.14.4 — LAPORAN FINALISASI UAT, VERIFIKASI BLOCKER BROWSER, & KESIAPAN 19 MASTER DATA

**Tanggal:** 2026-11-26  
**Lingkup:** Pemulihan/diagnostik koneksi Chrome DevTools MCP, verifikasi kesiapan lingkungan UAT terisolasi, audit lanjutan (`Tahap K` cache Eloquent & `Tahap L` backlog Provinsi), validasi baseline teknis, serta penyusunan matriks + checklist UAT manual seluruh `19` Master Data BigSIRS.  
**Kebijakan Eksekusi:** **0% perubahan source code aplikasi, test, migrasi, backup historis, maupun database operasional.**

---

## 1. Ringkasan Eksekutif & Keputusan (`GO` / `NO-GO`)

- **Keputusan Akhir (`GO` vs `NO-GO`):** **`NO-GO` untuk penandatanganan `FULL UAT PASS` 19 modul Master Data.**
  - **`CONDITIONAL PASS` untuk pengujian otomatis & stabilitas backend (`[TEST]`):** `FND-01`, `FND-02`, dan `FND-03` selesai dengan full PHPUnit **`768 passed, 0 failed, 1 skipped`** (dua eksekusi berurutan identik pada Fase 2B.14.3), full Vitest **`149 files / 1.195 passed`**, dan Vite build berhasil. *(Bukan bukti UAT browser).*
  - **`UI PASS [USER-CONFIRMED]` pada Kelompok A (7 Modul Bed — READ-ONLY):** Pengguna mengonfirmasi bahwa seluruh pemeriksaan manual yang dilakukan di Google Chrome menghasilkan **PASS** (`[USER-CONFIRMED]`), mencakup `7` modul Kelompok A (`Gedung`, `Lantai`, `Bangsal`, `Kelas Kamar`, `Kamar`, `Bed`, dan `Ketersediaan Bed SIRS` UI saja) pada `1280 × 800` dan `375 × 812` mode **READ-ONLY**.
  - **Cakupan yang Masih `PENDING` / `BLOCKED` / `NOT IMPLEMENTED`:**
    1. **`FND-11` (`BLOCKED` Chrome DevTools MCP):** Tetap `BLOCKED` (`DevToolsActivePort` tidak ada). `0` screenshot (`docs/audits/evidence/fase2b_14_4/` & `docs/audits/evidence/fase2b_14_4_manual/`) maupun log Console/Network rinci dilampirkan.
    2. **CRUD `12` Modul (`PENDING`):** `simrs_legacy` memuat data hasil impor sehingga Tambah/Edit/Hapus belum dilakukan pada DB disposable (`CRUD = PENDING`).
    3. **Kelompok B (`4` Modul: `Pendidikan`, `Pekerjaan`, `Jabatan`, `Pegawai`) & Kelompok C (`2` Modul: `Dokter`, `Surat-Surat`):** Konfirmasi umum pengguna belum merinci cakupan Kelompok B & C maupun skenario wajib Dokter (`Alergi kosong + reload`, `duplikat pegawai`) dan Surat-Surat (`5 kolom`, `3 field`, `Lihat Format Lama`), sehingga statusnya tetap **`PENDING` / `UNVERIFIED`** hingga ada konfirmasi & bukti spesifik per modul.
    4. **Ketersediaan Bed SIRS (`FND-05`):** UI dikonfirmasi **`UI PASS [USER-CONFIRMED]`**, namun integrasi backend tetap **`NOT IMPLEMENTED`**.

### Pemisahan Kategori Bukti & Status (Fase 2B.14.4-M)

| Kategori | Jumlah Modul | Rincian |
|---|---:|---|
| **PASS — Konfirmasi Pengguna (`FULL UAT PASS` historis 2B.7–2B.12)** | **6** | `Jadwal Dokter`, `Poliklinik`, `Jenis Layanan`, `Jenis Asuransi / Penjamin` *(modul master)*, `Asuhan Gizi`, `Jenis Kegiatan` |
| **UI PASS — Konfirmasi Pengguna (`[USER-CONFIRMED]` READ-ONLY; Status Akhir `PENDING` / `NOT IMPLEMENTED`)** | **7** | `Gedung`, `Lantai`, `Bangsal`, `Kelas Kamar`, `Kamar`, `Bed`, `Ketersediaan Bed SIRS` *(UI saja; CRUD `PENDING` / backend `NOT IMPLEMENTED`; screenshot & log Console/Network belum dilampirkan)* |
| **PASS — Bukti Screenshot / Browser Automation Evidence** | **0** | Chrome MCP masih `BLOCKED` (`FND-11`); `0` screenshot tersimpan |
| **PASS — Pengujian Otomatis (`[TEST]`)** | **19** *(lapisan kode/test)* | `768/769` PHPUnit (`1` skipped) + `1.195/1.195` Vitest + Vite build |
| **PENDING — Belum Jelas Cakupannya / CRUD Belum Diuji** | **12 CRUD** | `6` modul Kelompok B+C (UI & CRUD belum ada konfirmasi spesifik) + `6` modul Kelompok A (CRUD pada DB disposable & log DevTools belum tersedia) |
| **NOT IMPLEMENTED — Fitur Backend Belum Tersedia** | **1** *(+1 integrasi)* | `Ketersediaan Bed SIRS` (`FND-05`), plus gap lookup `Guarantor` ↔ Admisi (`FND-04`) |

---

## 2. Diagnosis & Pemulihan Koneksi Chrome DevTools MCP

### 2.1 Hasil Pemeriksaan Diagnostik Aktual (Windows Host)

| # | Item Pemeriksaan | Hasil Teramati | Bukti / Detail |
|---|---|---|---|
| 1 | Chrome DevTools MCP aktif | **Ya** | Plugin `chrome-devtools-plugin` (`v0.21.0`) aktif; proses Node `PID 27180` menjalankan `chrome-devtools-mcp.js --no-usage-statistics --no-performance-crux --autoConnect`. |
| 2 | Browser Chrome berjalan | **Ya** | Proses utama `chrome.exe` (`PID 20412`) berjalan dengan argumen `--profile-directory="Default"` dan `User Data` di `C:\Users\ACER\AppData\Local\Google\Chrome\User Data`. |
| 3 | MCP memakai `--autoConnect` | **Ya** | Terlihat langsung di command line `PID 26600`, `28260`, `10352`, dan `27180` (`--autoConnect`). |
| 4 | Target profil yang dicari MCP | **Profil Default Chrome** | Analisis `BrowserManager.js` (baris `387-429`) menunjukkan saat `--autoConnect` aktif tanpa `--userDataDir`/`--browserUrl`, Puppeteer mencari `C:\Users\ACER\AppData\Local\Google\Chrome\User Data\DevToolsActivePort`. Pencarian rekursif pada folder `Google\Chrome` dan `%TEMP%` menemukan **0 berkas `DevToolsActivePort`**. |
| 5 | Endpoint `http://127.0.0.1:9222/json/version` | **Tidak Tersedia** | Request ke `http://127.0.0.1:9222/json/version` mengalami *timeout* karena Chrome `PID 20412` tidak membuka port debugging `9222`. |
| 6 | Profil khusus UAT dibuka | **Tidak** | Tidak ada instance Chrome terpisah dengan `--remote-debugging-port` atau profil khusus UAT. |
| 7 | Kesamaan host Agent & Chrome | **Ya (Host Windows Sama)** | Agent dan Chrome sama-sama berjalan langsung di host Windows `ACER`, bukan terpisah di dalam container. |
| 8 | Hambatan port / antar-container | **Bukan kendala antar-container** | Frontend (`:5173`), backend (`:8000`), dan MySQL (`:3307`) di Docker ter-publish ke `0.0.0.0` host Windows; akar kegagalan murni karena **remote debugging belum diaktifkan pada Chrome yang berjalan** + MCP terikat `--autoConnect` tanpa konfigurasi `--browser-url`. Selain itu `C:\Users\ACER\.gemini\config\mcp_config.json` kosong (0 byte) dan `chrome-devtools-plugin` tidak memiliki `.mcp.json` lokal yang dapat di-restart dari sesi agent. |

### 2.2 Langkah Pemulihan Koneksi yang Sah untuk Pengguna

Pilih salah satu jalur aman berikut:

- **Opsi A (Paling Cepat — Tetap Pakai `--autoConnect` Bawaan):**
  1. Di jendela Chrome yang sudah terbuka, kunjungi `chrome://inspect/#remote-debugging`.
  2. Aktifkan sakelar/izin **Remote Debugging** agar Chrome menulis berkas `DevToolsActivePort` resmi di `User Data`.
  3. Mulai/lanjutkan subagent browser untuk menguji `http://localhost:5173`.
- **Opsi B (Profil UAT Terpisah di Port `9222`):**
  1. Jalankan Chrome UAT terpisah (tidak menyentuh profil pribadi):
     ```powershell
     & "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" --remote-debugging-port=9222 --user-data-dir="$env:TEMP\bigsirs-uat-chrome" "http://localhost:5173"
     ```
  2. Verifikasi `http://127.0.0.1:9222/json/version` mengembalikan JSON `Browser` & `webSocketDebuggerUrl`.
  3. Konfigurasikan Chrome DevTools MCP dengan `--browser-url=http://127.0.0.1:9222` (**hapus `--autoConnect`** agar tidak bentrok validasi opsi `mcp-options.js`), lalu restart sesi IDE/MCP.

### 2.3 Hasil Uji Gate Koneksi Ulang (`list_pages` dengan `--autoConnect`)

- **Gate Koneksi:** **`FAIL`**
- **Error Spesifik Teramati:**
  - `Could not connect to Chrome. Check if Chrome is running and remote debugging is enabled by going to chrome://inspect/#remote-debugging.`
  - `Cause: Could not find DevToolsActivePort for chrome at C:\Users\ACER\AppData\Local\Google\Chrome\User Data\DevToolsActivePort`
  - `Cause: ENOENT: no such file or directory, open 'C:\Users\ACER\AppData\Local\Google\Chrome\User Data\DevToolsActivePort'`
- **Pengecekan Host Ulang:** `Get-ChildItem -Path "C:\Users\ACER\AppData\Local\Google\Chrome" -Filter "DevToolsActivePort" -Recurse` tetap mengembalikan kosong (remote debugging belum aktif menghasilkan port file di `User Data`), sehingga percobaan dihentikan sesuai aturan agar tidak mengulang kegagalan identik.

---

## 3. Verifikasi Persiapan & Isolasi Lingkungan (`Tahap A` & Pengamanan Database)

- **Stack Development Terisolasi (`docker ps` & [docker-compose.yml](file:///d:/Magang%20SISfo/BigSirs/docker-compose.yml)):**
  - `bigsirs-frontend-1`: port `5173->5173` (`VITE_API_BASE_URL=http://127.0.0.1:8000/api/v1` sesuai [frontend/.env](file:///d:/Magang%20SISfo/BigSirs/frontend/.env)).
  - `bigsirs-backend-1`: port `8000->8000`.
  - `bigsirs-mysql` (`mysql:8.4`, healthy): port `3307->3306`, mengarah ke database lokal Docker `bigsirs_dev` (`DB_DATABASE`) dan `simrs_legacy` (`LEGACY_DB_DATABASE`) di `127.0.0.1:3307` sesuai [backend/.env](file:///d:/Magang%20SISfo/BigSirs/backend/.env) & [.env.docker](file:///d:/Magang%20SISfo/BigSirs/.env.docker).
- **Hasil Audit Read-Only Isi Database (`bigsirs-mysql`):**
  - `bigsirs_dev.users`: `1` baris (akun dev `DEV_ADMIN_USERNAME=admin`).
  - `simrs_legacy.ref_gedung`: `19` baris.
  - `simrs_legacy.ref_pegawai`: `87` baris.
  - `simrs_legacy.ref_dokter`: `27` baris.
  - `simrs_legacy.ref_pasien`: `364` baris.
  - `simrs_legacy.ref_jenis_asuransi`: `8` baris.
  - **Kesimpulan Pengamanan Database:** Walaupun host MySQL berada di container Docker lokal (terisolasi dari database rumah sakit operasional), database `simrs_legacy` berisi **data impor sistem lama** (`364` pasien, `87` pegawai, `27` dokter, `19` gedung). Oleh karena itu:
    1. Tidak ada migrasi, seeder, import, truncate, atau reset database yang dijalankan.
    2. Operasi tulis pada UAT browser kelak **hanya** diizinkan untuk membuat data dummy berawalan `UAT-2B144-*` dan menghapus kembali **hanya ID dummy baru tersebut** (atau membatasi modul sensitif ke mode READ-ONLY apabila rollback tidak dapat dipastikan), serta **dilarang** mengubah/menghapus data master maupun pasien eksisting.
    3. Karena Gate Koneksi browser gagal, **`0` operasi tulis** dilakukan ke `bigsirs_dev` maupun `simrs_legacy`.
- **Inventarisasi Route Aktual (`19` Modul + Rute Integrasi):** Seluruh rute telah diverifikasi langsung pada [frontend/src/router/index.js](file:///d:/Magang%20SISfo/BigSirs/frontend/src/router/index.js).

---

## 4. Rekap Status UAT & Kesiapan 19 Modul (`Tahap I`)

Lihat tabel 15-kolom lengkap pada [fase2b_14_4_uat_matrix.md](file:///d:/Magang%20SISfo/BigSirs/docs/audits/fase2b_14_4_uat_matrix.md):

- **FULL UAT PASS (6 Modul — konfirmasi UAT sesi 2B.7–2B.12 + regresi otomatis `[TEST]`):**
  1. **Jadwal Dokter** (`/master-data/doctor-schedules`)
  2. **Poliklinik** (`/master-data/polyclinics`)
  3. **Jenis Layanan** (`/master-data/service-types`)
  4. **Jenis Asuransi / Penjamin** (`/master-data/basic-data/insurance-types`) — *Catatan: master modul `PASS`, integrasi dinamis ke Admisi/Pendaftaran masih terbuka pada `FND-04`.*
  5. **Asuhan Gizi** (`/master-data/nutrition-care`)
  6. **Jenis Kegiatan** (`/master-data/activity-types`)
- **UI PASS `[USER-CONFIRMED]` READ-ONLY / CRUD `PENDING` (6 Modul CRUD Kelompok A — Master Data Bed):**
  - **Gedung**, **Lantai**, **Bangsal**, **Kelas Kamar**, **Kamar**, **Bed**: tampilan desktop `1280 × 800`, mobile `375 × 812`, & list/search READ-ONLY dikonfirmasi `PASS` oleh pengguna (`[USER-CONFIRMED]`); screenshot, log Console/Network rinci, dan uji CRUD (Tambah/Edit/Hapus serta relasi parent-child / `AddRoomClassDialog`) masih **`PENDING`**.
- **UI PASS `[USER-CONFIRMED]` / Backend `NOT IMPLEMENTED` (1 Modul):**
  - **Ketersediaan Bed SIRS** (`/master-data/bed/sirs-availability`): tampilan UI dikonfirmasi `PASS` (`[USER-CONFIRMED]`), namun modul tetap prototipe frontend-only (`useSirsBedAvailability.js` berisi `[]`, belum ada endpoint backend/bridging Kemenkes sesuai `FND-05`).
- **`PENDING` / `UNVERIFIED` — Belum Ada Konfirmasi Spesifik Per Kelompok (6 Modul — Kelompok B & C):**
  - **Kelompok B (`4` modul):** **Pendidikan**, **Pekerjaan**, **Jabatan**, **Pegawai** (panduan disiapkan di [fase2b_14_4_manual_kelompok_b_sdm.md](file:///d:/Magang%20SISfo/BigSirs/docs/audits/fase2b_14_4_manual_kelompok_b_sdm.md); belum dikonfirmasi terpisah sebagai telah diuji).
  - **Kelompok C (`2` modul):** **Dokter** (pasca-2B.5.2 & FND-03), **Surat-Surat** (pasca-2B.12 & 2B.12.1) — belum memiliki bukti pengujian manual desktop/mobile, Console/Network, maupun skenario khusus (`Alergi kosong + reload`, `Lihat Format Lama`).

---

## 5. Checklist UAT Manual Terpandu (13 Modul & Integrasi Lintas Modul)

Gunakan akun uji `admin` pada `http://localhost:5173`. Untuk pengujian CRUD, **selalu gunakan prefix dummy `UAT-2B144-...`**, verifikasi **Desktop `1280px`** dan **Mobile `375px`** (cek tidak ada horizontal scroll/elemen terpotong), pantau **DevTools Console & Network (`>= 400`)**, dan hapus data dummy di akhir uji.

### 5.1 Kelompok A — Master Data Bed (`7` Modul, Urutan Relasi)
1. **Gedung** (`/master-data/bed/buildings`): `[ ] 1280px` `[ ] 375px` `[ ] Search & Refresh` `[ ] Validasi Wajib` `[ ] Tambah "UAT-2B144-Gedung"` `[ ] Edit` `[ ] Tolak Hapus 409/422 saat punya Lantai` `[ ] Console/Network 0 error`
2. **Lantai** (`/master-data/bed/floors`): `[ ] 1280px` `[ ] 375px` `[ ] Dropdown Gedung terisi & tersimpan` `[ ] Edit mempertahankan Gedung` `[ ] Nama parent tampil di tabel` `[ ] Console/Network 0 error`
3. **Bangsal** (`/master-data/bed/wards`): `[ ] 1280px` `[ ] 375px` `[ ] Dropdown Lantai terisi & tersimpan` `[ ] Edit mempertahankan Lantai` `[ ] Search & Pagination` `[ ] Console/Network 0 error`
4. **Kelas Kamar** (`/master-data/bed/room-classes`): `[ ] 1280px` `[ ] 375px` `[ ] Data Harga/Tarif, kode BPJS & SIRS sesuai` `[ ] Filter Status` `[ ] Tambah/Edit/Hapus` `[ ] Console/Network 0 error`
5. **Kamar** (`/master-data/bed/rooms`): `[ ] 1280px` `[ ] 375px` `[ ] Dropdown Bangsal & Kelas Kamar` `[ ] Dialog "Tambah Kelas" inline (AddRoomClassDialog) dari form Kamar` `[ ] Edit mempertahankan relasi` `[ ] Console/Network 0 error`
6. **Bed** (`/master-data/bed/beds`): `[ ] 1280px` `[ ] 375px` `[ ] Dropdown Kamar` `[ ] Nomor/Identitas Bed` `[ ] Tambah -> Edit -> Hapus berurutan (Bed -> Room -> RoomClass -> Ward -> Floor -> Building)` `[ ] Console/Network 0 error`
7. **Ketersediaan Bed SIRS** (`/master-data/bed/sirs-availability`): `[ ] 1280px` `[ ] 375px` `[ ] Header, Breadcrumb, Navigasi & Empty state tampil rapi tanpa JS error` *(Tandai backend sebagai `NOT IMPLEMENTED`, jangan klaim PASS integrasi)*

### 5.2 Kelompok B — Dokter & Surat-Surat (`2` Modul)
8. **Dokter** (`/master-data/doctors`):
   - `[ ] 1280px & 375px` (tabel, breadcrumb, modal Tambah/Edit, modal Detail, No. HP, tanda tangan, IHS Number, alamat/wilayah).
   - **Skenario Prioritas Alergi & Reload:** `[ ] Buka Edit Dokter dengan Alergi terisi` → `[ ] Kosongkan Alergi` → `[ ] Simpan (toast sukses)` → `[ ] Reload browser penuh (F5)` → `[ ] Buka Detail & Edit lagi -> Alergi tetap kosong (`null`/kosong, bukan nilai lama maupun string `"null"`) dan biodata lain (wilayah, IHS, tanda tangan) tidak berubah`.
   - `[ ] Uji penolakan duplikasi Dokter untuk Pegawai (`employee_id`) yang sudah menjadi Dokter`.
   - `[ ] Console/Network bersih (selain HTTP 422 uji duplikasi)`.
9. **Surat-Surat** (`/master-data/letter-types`):
   - `[ ] 1280px & 375px` `[ ] Tabel 5 kolom (No, Nama Surat, Deskripsi, Resource, Aksi)` `[ ] Form 3 field` `[ ] Validasi panjang input` `[ ] Tambah -> Edit -> Konfirmasi Hapus (Batal tidak menghapus -> Hapus berhasil)` `[ ] Penanganan 403/404/422` `[ ] Global toast`.
   - **Khusus `Lihat Format Lama`:** `[ ] Resource valid dapat dibuka` `[ ] Resource kosong tidak menampilkan aksi` `[ ] Skema berbahaya (`javascript:`/`data:`) tidak dieksekusi` `[ ] URL relatif mengikuti konfigurasi legacy`.

### 5.3 Kelompok C — Master Data Awal (`4` Modul)
10. **Pendidikan** (`/master-data/basic-data/educations`): `[ ] 1280px & 375px` `[ ] Search/Pagination` `[ ] Tambah/Edit/Hapus` `[ ] Proteksi hapus 409 jika dipakai Pegawai` `[ ] Console/Network bersih`.
11. **Pekerjaan** (`/master-data/basic-data/occupations`): `[ ] 1280px & 375px` `[ ] Search/Pagination` `[ ] Tambah/Edit/Hapus` `[ ] Proteksi hapus 409 jika dipakai` `[ ] Console/Network bersih`.
12. **Jabatan** (`/master-data/positions`): `[ ] 1280px & 375px` `[ ] Search/Pagination` `[ ] Tambah/Edit/Hapus` `[ ] Console/Network bersih`.
13. **Pegawai** (`/master-data/employees`): `[ ] 1280px & 375px` `[ ] Form pasca-2B.5.1 (No. HP, Jabatan, Tanda tangan, Wilayah)` `[ ] Tambah/Edit/Hapus` `[ ] Console/Network bersih`.

### 5.4 Verifikasi Integrasi Antarmodul (`Tahap G` — Non-Destruktif)
- `[ ]` **Dokter → Jadwal Dokter** (`/master-data/doctor-schedules`): Lookup Dokter terisi, tersimpan di jadwal praktik, tampil di rekap mingguan.
- `[ ]` **Poliklinik → Jadwal Dokter** (`/master-data/doctor-schedules`): Lookup Poliklinik tampil, pengelompokan rekap sesuai.
- `[ ]` **Jenis Kegiatan → Radiologi** (`/master-data/radiology-groups`): Buka modal Group Radiologi (`RadiologyGroupModal`), lookup Jenis Kegiatan tampil dan relasi induk dapat dipilih.
- `[ ]` **Jenis Asuransi → Admisi/Pendaftaran** (`/admisi`, `/pendaftaran/baru`): Periksa tampilan data Penjamin historis; konfirmasi bahwa dropdown input masih memakai daftar statis (`FND-04` terbuka — **jangan** diubah otomatis).
- `[ ]` **Master Data Bed:** Verifikasi keterkaitan Gedung → Lantai → Bangsal → Kamar/Kelas → Bed.

---

## 6. Register Temuan Terbaru & Status Perbaikan (`Tahap J`)

| ID | Modul / Komponen | Kategori | Severity | Deskripsi & Dampak | Status Verifikasi | Rekomendasi |
|---|---|---|---|---|---|---|
| **FND-01** | `MedicalProcedureController::export()` | Backend | P1 | Filter `tariff_type_id` sebelumnya tidak membatasi eager load `tariffs`. | **RESOLVED [TEST]** *(Fase 2B.14.1, `MedicalProcedureTest` `23/23`)* | Pertahankan & verifikasi di UAT ekspor jika relevan. |
| **FND-02** | `StoreRegencyRequest` / `UpdateRegencyRequest` | Backend | P1 | Validasi `provinces` internal gagal karena `whereNull('deleted_at')`. | **RESOLVED [TEST]** *(Fase 2B.14.2, `RegencyControllerTest` `12/12`)* | Pertahankan. |
| **FND-03** | `LegacyDoctorProfilePersistenceTest` | Backend Test | P2 | `GuardsAttributes::$guardableColumns` bocor antar-test PHPUnit. | **RESOLVED [TEST]** *(Fase 2B.14.3, 2x full PHPUnit `768/768`)* | Lihat catatan teknis `FND-13` (Tahap K). |
| **FND-04** | `Guarantor` ↔ Admisi/Pendaftaran | Integrasi | P2 | Dropdown Admisi/Pendaftaran hardcoded & `destroy()` non-legacy belum cek relasi admisi. | **RESOLVED [CODE][TEST]** *(Fase 2B.15; UAT browser manual pending)* | Lakukan spot-check browser Chrome pada `/pendaftaran/baru` & `/admisi` terhadap DB dev/UAT. |
| **FND-05** | `SirsBedAvailabilityView` | Integrasi / Backend | P2 | Prototipe frontend-only (`initialRows = []`), belum ada API SIRS/Bed. | **OPEN [CODE]** | Kerjakan endpoint agregasi & bridging SIRS pada sprint khusus. |
| **FND-06** | UAT Browser 13 Modul | QA / UAT | P2 | Cakupan UAT browser 13 modul belum selesai (`6 PASS`, `12 BLOCKED`, `1 NOT IMPLEMENTED`). | **BLOCKED (`FND-11`)** | Jalankan checklist manual Bagian 5 setelah koneksi Chrome pulih. |
| **FND-07** | `DietType`, `ActivityType`, `Position` | Backend | P2 | Sorting server fixed ascending tanpa query param sort dinamis. | **OPEN [CODE]** | Tambah hanya jika dibutuhkan UI. |
| **FND-08** | Modul Lama Klinis/Logistik | Frontend Lint | P3 | `14` error Oxlint & `214` error ESLint pra-existing di luar `19` modul Master Data. | **OPEN [TEST]** | Bersihkan bertahap saat standarisasi modul klinis/logistik. |
| **FND-09** | `Polyclinic` vs `ServiceType` | Dokumentasi/API | P3 | Perbedaan semantik enum `/polyclinics/service-types` vs master `/service-types`. | **OPEN [CODE]** | Dokumentasikan kontrak API. |
| **FND-10** | `src/services/` vs `src/services/master-data/` | Arsitektur FE | P3 | Variasi lokasi file service & wrapper response; fungsi berjalan normal. | **OPEN [CODE]** | Pertahankan path agar tidak memicu regresi import. |
| **FND-11** | Chrome DevTools MCP (`--autoConnect`) | Lingkungan UAT | **P1 (Blocker UAT)** | Koneksi browser automation gagal: Chrome berjalan tanpa remote debugging (`DevToolsActivePort` tidak ada, port `9222` timeout). | **TERKONFIRMASI [ENV]** | Aktifkan `chrome://inspect/#remote-debugging` atau gunakan Chrome port `9222` + `--browser-url=http://127.0.0.1:9222`. |
| **FND-12** | `StoreProvinceRequest` & `UpdateProvinceRequest` | Backend | **P2** | `Rule::unique('provinces', 'name')->whereNull('deleted_at')` pada mode internal sementara tabel `provinces` tidak punya kolom `deleted_at`. | **TERKONFIRMASI [CODE]** *(Tahap L)* | Tambah PHPUnit internal mode Provinsi + hapus `whereNull('deleted_at')` pada cabang internal di fase perbaikan backend berikutnya. |
| **FND-13** | `LegacyAccess::clearCache()` (`Model::$guardableColumns`) | Arsitektur / Maint. | **P3** | `Closure::bind` terhadap properti internal Laravel `Model::$guardableColumns` berada di class produksi `app/Support/LegacyAccess.php`. | **TERKONFIRMASI [CODE]** *(Tahap K)* | Pindahkan reset internal Eloquent ke helper test (`tests/TestCase.php` / trait khusus test) pada fase refactor terpisah. |

---

## 7. Audit Ringan Cache Eloquent (`Tahap K` — Read-Only)

Pemeriksaan terhadap [LegacyAccess.php](file:///d:/Magang%20SISfo/BigSirs/backend/app/Support/LegacyAccess.php#L51-L63) dan [TestCase.php](file:///d:/Magang%20SISfo/BigSirs/backend/tests/TestCase.php#L10-L17):

1. **Apakah `LegacyAccess::clearCache()` dipanggil di production?**
   **Tidak.** Hasil pemindaian penuh direktori `app/`, `routes/`, `database/`, dan `config/` menunjukkan **0 pemanggil** `clearCache()` di luar definisinya sendiri; seluruh `39` baris pemanggilan berada di `backend/tests/`.
2. **Apakah reset `Model::$guardableColumns` diperlukan pada production?**
   **Tidak.** Pada runtime production dengan MySQL persisten, skema tabel tidak berubah-ubah antar-request dalam satu proses; cache ini hanya bermasalah pada PHPUnit SQLite `:memory:` ketika beberapa kelas test membangun definisi kolom berbeda untuk tabel `master_person_index` / `ref_dokter` di proses PHP yang sama.
3. **Apakah pemisahan utilitas testing lebih aman?**
   **Ya.** Menaruh manipulasi internal properti private/protected Laravel (`Closure::bind` ke `Model::$guardableColumns`) di `app/Support/` mencampurkan kebutuhan test isolation ke kode aplikasi produksi. Akan lebih bersih bila logika reset `$guardableColumns` dipindahkan ke layer test (`tests/TestCase.php` atau helper test tersendiri) agar `LegacyAccess::clearCache()` hanya mengelola `$tableExistsCache`.
4. **Risiko kompatibilitas upgrade framework Laravel:**
   Penggunaan `Closure::bind(..., null, Model::class)` bergantung pada nama properti internal `Illuminate\Database\Eloquent\Concerns\GuardsAttributes::$guardableColumns`. Jika upgrade Laravel mayor mengubah nama atau visibilitas properti tersebut dan `clearCache()` dipanggil (dalam test), baris tersebut dapat memicu error saat menjalankan test suite (berdampak ke test, tidak berdampak ke request production selama `clearCache()` tidak dipanggil). Tercatat sebagai **`FND-13` (P3 Technical Debt)**.

---

## 8. Backlog Validasi Provinsi (`Tahap L` — Read-Only)

Pemeriksaan pada [StoreProvinceRequest.php](file:///d:/Magang%20SISfo/BigSirs/backend/app/Http/Requests/MasterData/StoreProvinceRequest.php#L18-L20), [UpdateProvinceRequest.php](file:///d:/Magang%20SISfo/BigSirs/backend/app/Http/Requests/MasterData/UpdateProvinceRequest.php#L20-L22), [Province.php](file:///d:/Magang%20SISfo/BigSirs/backend/app/Models/Province.php), dan [2026_09_30_031445_create_provinces_table.php](file:///d:/Magang%20SISfo/BigSirs/backend/database/migrations/2026_09_30_031445_create_provinces_table.php):

- **Temuan:** Kedua FormRequest memanggil `Rule::unique('provinces', 'name')->whereNull('deleted_at')` ketika `LegacyAccess::isAvailable('ref_provinsi')` bernilai `false` (mode database internal). Namun tabel `provinces` pada migrasi internal hanya memiliki kolom `id`, `legacy_id`, `name`, `created_at`, `updated_at` (**tanpa `deleted_at`**) dan model `Province` tidak memakai `SoftDeletes`.
- **Cakupan Pengujian Saat Ini:** Pemindaian seluruh `backend/tests/` menunjukkan endpoint `POST`/`PUT` `/api/v1/master-data/provinces` **baru diuji pada mode legacy (`ref_provinsi`)** di [LegacyAdministrativeRegionCrudTest.php](file:///d:/Magang%20SISfo/BigSirs/backend/tests/Feature/Api/V1/MasterData/LegacyAdministrativeRegionCrudTest.php#L106-L136), dan **belum memiliki PHPUnit test pada mode DB internal**.
- **Rekomendasi Fase Perbaikan Berikutnya (`FND-12`):**
  1. Buat test terisolasi (mis. `ProvinceControllerTest`) yang menguji create/update/duplicate validation `provinces` saat `DB::purge('legacy')` + `ref_provinsi` tidak tersedia, sekaligus memeriksa perilaku field `code`/`is_active` pada `ProvinceController::store/update` terhadap skema tabel `provinces`.
  2. Hapus `->whereNull('deleted_at')` pada cabang internal `Rule::unique('provinces', 'name')` di `StoreProvinceRequest` dan `UpdateProvinceRequest` (tetap pertahankan `->whereNull('deleted_at')` pada cabang `legacy.ref_provinsi`).

---

## 9. Verifikasi Teknis, Integritas SHA256, & Git (`Tahap H & M`)

- **Backend PHPUnit (Baseline Tervalidasi Fase 2B.14.3, 0 perubahan source code sejak eksekusi):**
  - 2 eksekusi berurutan identik: **`769` tests (`768 passed`, `0 failed`, `1 skipped` [`DirectAccessVerificationTest`], `5.886` assertions)**.
- **Frontend (Baseline Tervalidasi, 0 perubahan source code sejak eksekusi):**
  - Vitest: **`149` test files / `1.195` tests passed**.
  - Vite Build: **berhasil (`1.56s`)**.
  - Oxlint global: **`14` error lama (`686` file)**.
  - ESLint global (`--no-cache`): **`214` error lama (`0` warning)**.
- **Integritas SHA256 Backup:** `49` manifest / `436` entri / **`0` mismatch**.
- **Keamanan Repository:** Tidak ada refactor source code, tidak ada perubahan test/schema/migrasi, tidak ada modifikasi backup historis, dan tidak ada `git commit`/`push`/`checkout`/`reset`/`stash`. Dokumen baru ditambahkan hanya di `docs/audits/`.
