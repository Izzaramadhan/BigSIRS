# FASE 2B.14.4-M — PANDUAN & LEMBAR CATAT UAT MANUAL KELOMPOK A (MASTER DATA BED)

**Target Aplikasi:** `http://localhost:5173` (akun uji dev: `admin`)  
**Direktori Screenshot Manual:** `docs/audits/evidence/fase2b_14_4_manual/`  
**Status Awal Kelompok A:** Menunggu eksekusi & bukti pengguna (`UNVERIFIED` / `PENDING`).

> **PENGAMANAN DATABASE (WAJIB DIBACA SEBELUM CRUD):**  
> Pemeriksaan *read-only* pada `bigsirs-mysql` menunjukkan database `simrs_legacy` memuat **data impor sistem lama** (`ref_gedung=19`, `ref_pegawai=87`, `ref_dokter=27`, `ref_pasien=364`, `ref_jenis_asuransi=8`).  
> - **Jika Anda belum memiliki backup/snapshot `simrs_legacy` atau belum memastikan DB aman untuk diubah:** jalankan **Tahap 1–3 (Desktop 1280px, Mobile 375px, List/Search/Pagination, & Console/Network) saja secara READ-ONLY**, dan tandai skenario CRUD sebagai `PENDING`.  
> - **Jika DB telah di-snapshot dan dinyatakan aman untuk uji tulis sementara:** gunakan **data dummy berawalan `UAT-2B144-`** (JANGAN ubah atau hapus `19` gedung / record eksisting), lalu hapus kembali data dummy dengan urutan terbalik (`Bed -> Kamar -> Kelas Uji -> Bangsal -> Lantai -> Gedung`).

---

## Persiapan Google Chrome & DevTools

1. Buka Google Chrome biasa ke `http://localhost:5173/login`, login sebagai `admin`.
2. Tekan `F12` (buka Chrome DevTools):
   - Tab **Console**: aktifkan *Preserve log*, pantau error/exception JS baru.
   - Tab **Network**: aktifkan filter **Fetch/XHR** dan *Preserve log*, pantau request API (`200`, `201`, `422`, `404`, `500`) dan kecocokan payload/response terhadap data di tabel.
   - **Device Toolbar (`Ctrl+Shift+M`):** gunakan lebar **`1280 × 800`** untuk Desktop dan **`375 × 812`** untuk Mobile. Pastikan pada kedua viewport tidak muncul *horizontal scrollbar* pada halaman (`document.documentElement.scrollWidth <= window.innerWidth` di Console harus `true`).

---

## Panduan Langkah Uji Per Modul (7 Modul)

### 1. Gedung — `/master-data/bed/buildings`
- **Desktop (`1280px`) & Mobile (`375px`):** Periksa header, breadcrumb (`Dashboard / Master Data / Data Bed / Gedung`), tombol Tambah, kolom cari, tombol Refresh, pagination, dan kolom tabel: `No` · `Nama Gedung` · `Deskripsi` · `Aksi`. Pastikan modal `Tambah Gedung`/`Edit Gedung` tidak terpotong di `375px`.
- **Read-Only API & List:** Buka/Refresh → `GET /api/v1/master-data/buildings` (`200`), data tampil (`19` data legacy / sesuai DB), coba pencarian + reset pencarian + pindah halaman.
- **CRUD (hanya jika DB aman):**
  - Uji validasi: submit `Tambah Gedung` kosong → error validasi field wajib.
  - Tambah dummy `Nama Gedung`: `UAT-2B144-Gedung`, Deskripsi: `Uji UAT Bed` → `POST /buildings` (`201`), toast sukses, baris muncul.
  - Edit `UAT-2B144-Gedung` → `UAT-2B144-Gedung Edit` → `PUT /buildings/{id}` (`200`), **reload halaman (F5)** dan cek perubahan persisten.
  - **Simpan record ini sementara** untuk parent modul Lantai (nanti di akhir uji tolak hapus `422` saat punya anak Lantai, baru hapus setelah Lantai dihapus).

### 2. Lantai — `/master-data/bed/floors`
- **Kolom Tabel:** `No` · `Nama Gedung` · `Nama Lantai` · `Aksi`.
- **Desktop (`1280px`) & Mobile (`375px`):** Cek tampilan, header, breadcrumb, search, refresh, pagination, dan modal `Tambah Lantai` / `Edit Lantai`.
- **Read-Only API & Relasi:** `GET /api/v1/master-data/floors` (`200`) & lookup Gedung. Pastikan kolom `Nama Gedung` tampil sesuai relasi parent.
- **CRUD (hanya jika DB aman):**
  - Tambah `UAT-2B144-Lantai 1` dengan memilih Gedung `UAT-2B144-Gedung Edit` → `POST /floors` (`201`).
  - Edit → cek dropdown Gedung terhidrasi nilai lama, ubah nama ke `UAT-2B144-Lantai 1B`, simpan (`200`), **reload (F5)** dan pastikan relasi Gedung tetap terjaga.

### 3. Bangsal — `/master-data/bed/wards`
- **Kolom Tabel:** `No` · `Nama Lantai` · `Nama Bangsal` · `Aksi`.
- **Desktop (`1280px`) & Mobile (`375px`):** Cek header, breadcrumb, search, refresh, pagination, modal `Tambah Bangsal` / `Edit Bangsal`.
- **Read-Only API & Relasi:** `GET /api/v1/master-data/wards` (`200`) & lookup Lantai. Pastikan kolom `Nama Lantai` (beserta konteks Gedung bila ditampilkan) konsisten.
- **CRUD (hanya jika DB aman):**
  - Tambah `UAT-2B144-Bangsal Mawar` memilih parent `UAT-2B144-Lantai 1B` → `POST /wards` (`201`).
  - Edit → cek Lantai terhidrasi, simpan perubahan (`200`), **reload (F5)**.

### 4. Kelas Kamar — `/master-data/bed/room-classes`
- **Kolom Tabel:** `No` · `Nama Kelas` · `Harga Kelas` · `Deskripsi` · `Kode Kelas BPJS` · `Kode Kelas SIRS` · `Aksi`.
- **Form (`Tambah Kelas` / `Edit Kelas`):** `Nama Kelas`, `Kode Kelas`, `Harga Kelas` (`Rp ...`), `Deskripsi`, `Kode Aplicare`, `Kode SIRS`.
- **Desktop (`1280px`) & Mobile (`375px`):** Cek format harga Rupiah, kolom BPJS/SIRS tidak terpotong di mobile, search/filter & pagination berfungsi.
- **CRUD (hanya jika DB aman):**
  - Uji validasi wajib (`Nama`, `Kode`, `Harga`).
  - Tambah `UAT-2B144-Kelas VIP` (Kode `UAT-VIP`, Harga `750000`, Aplicare `V1`, SIRS `VIP`) → `POST /room-classes` (`201`).
  - Edit & **reload (F5)** → pastikan semua nilai (termasuk harga, BPJS/Aplicare, SIRS) terhidrasi utuh.

### 5. Kamar — `/master-data/bed/rooms`
- **Kolom Tabel:** `No` · `Nama Bangsal` · `Nama Kamar` · `Nama Kelas` · `Aksi`.
- **Form (`Tambah Kamar` / `Edit Kamar`):** `Nama Bangsal` (`SearchableSelect`), `Kode Kamar`, `Nama Kamar`, `Kelas` (`SearchableSelect` + `server-search` + `creatable`: ketik nama baru menampilkan `Tambah kelas “[name]”` yang membuka dialog `Tambah Kelas Baru` / `AddRoomClassDialog`), `Deskripsi`.
- **Desktop (`1280px`) & Mobile (`375px`):** Cek dropdown Bangsal & Kelas, pastikan modal bertingkat `AddRoomClassDialog` tampil rapi pada desktop dan mobile.
- **CRUD & Fitur Khusus (hanya jika DB aman):**
  - Saat menambah Kamar `UAT-2B144-Kamar 101` (Kode `UAT-K101`, Bangsal `UAT-2B144-Bangsal Mawar`), uji **opsi A** memilih `UAT-2B144-Kelas VIP` ATAU **opsi B** mengetik `UAT-2B144-Kelas Inline` di field `Kelas` → klik `Tambah kelas "UAT-2B144-Kelas Inline"` → simpan di dialog `Tambah Kelas Baru` → pastikan otomatis terpilih di form Kamar!
  - Simpan Kamar (`POST /rooms` `201`), Edit → cek Bangsal & Kelas terhidrasi benar, **reload (F5)**.

### 6. Bed — `/master-data/bed/beds`
- **Kolom Tabel:** `No` · `Nama Kamar` · `Nama Kelas` · `Nama Bed` · `Deskripsi` · `Aksi`.
- **Desktop (`1280px`) & Mobile (`375px`):** Cek header, breadcrumb, search, refresh, pagination, relasi Kamar & Kelas tampil pada tabel.
- **CRUD & Proteksi Hapus Berurutan (hanya jika DB aman):**
  - Tambah `UAT-2B144-Bed A` memilih Kamar `UAT-2B144-Kamar 101` → `POST /beds` (`201`).
  - Edit `UAT-2B144-Bed A` → pastikan dropdown Kamar terhidrasi, simpan (`200`), **reload (F5)**.
  - **Uji Proteksi Hapus (`422`):** Sebelum menghapus Bed, buka Gedung → klik Hapus pada `UAT-2B144-Gedung Edit` → konfirmasi → pastikan ditolak (`422` / pesan bahwa Gedung masih digunakan Lantai). Ulangi batal pada dialog hapus untuk memastikan pembatalan aman.
  - **Pembersihan Data Dummy (Wajib Urut Daun ke Akar):**
    1. Hapus `UAT-2B144-Bed A`
    2. Hapus `UAT-2B144-Kamar 101`
    3. Hapus `UAT-2B144-Kelas VIP` (dan `UAT-2B144-Kelas Inline` jika dibuat)
    4. Hapus `UAT-2B144-Bangsal Mawar`
    5. Hapus `UAT-2B144-Lantai 1B`
    6. Hapus `UAT-2B144-Gedung Edit`

### 7. Ketersediaan Bed SIRS — `/master-data/bed/sirs-availability`
- **Catatan Khusus:** **Prototipe Frontend-Only** (`useSirsBedAvailability.js` berisi array kosong `[]`, belum terhubung ke backend/SIRS Kemenkes).
- **Yang Diperiksa (READ-ONLY):**
  - Desktop `1280px` & Mobile `375px`: Header (`Ketersediaan Bed SIRS`), breadcrumb (`Dashboard / Master Data / Data Bed / Ketersediaan Bed SIRS`), banner info biru (`"Halaman ini masih dalam tahap persiapan dan belum terintegrasi dengan layanan SIRS."`), toolbar (search, per-page, refresh), empty state (`"Belum ada data ketersediaan Bed SIRS"`), serta state saat mengetik pencarian (`"Data ketersediaan tidak ditemukan"`).
  - Network: Konfirmasi tidak ada request API error `404`/`500` palsu.
  - Console: `0` JS error.
  - **Status Backend/Integrasi:** Tetap **`NOT IMPLEMENTED`** meskipun UI `PASS`.

---

## Format Pencatatan Hasil (Salin & Isi Setelah Pengujian)

Simpan screenshot ke `docs/audits/evidence/fase2b_14_4_manual/` (mis. `bed_01_buildings_desktop.png`, `bed_01_buildings_mobile.png`, dst.) lalu balas dengan template berikut:

```markdown
### Hasil UAT Kelompok A — Master Data Bed
- **Status Keamanan DB:** [READ-ONLY SAJA / SUDAH SNAPSHOT & CRUD DUMMY AMAN]

1. **Gedung (`/master-data/bed/buildings`)**
   - Desktop 1280px: PASS / FAIL (catatan)
   - Mobile 375px: PASS / FAIL (horizontal overflow? Y/N)
   - List/Search/Refresh/Pagination: PASS / FAIL
   - CRUD (Tambah/Edit/Reload/Hapus): PASS / FAIL / PENDING (DB read-only)
   - Console errors: 0 / detail
   - Network (Fetch/XHR): status kode & catatan
   - Screenshot: nama file di `docs/audits/evidence/fase2b_14_4_manual/`
   - Masalah ditemukan: Tidak ada / jelaskan

2. **Lantai (`/master-data/bed/floors`)** — ...
3. **Bangsal (`/master-data/bed/wards`)** — ...
4. **Kelas Kamar (`/master-data/bed/room-classes`)** — ...
5. **Kamar (`/master-data/bed/rooms`)** — (sertakan hasil `AddRoomClassDialog`!)
6. **Bed (`/master-data/bed/beds`)** — (sertakan hasil uji tolak hapus `422` & cleanup dummy!)
7. **Ketersediaan Bed SIRS (`/master-data/bed/sirs-availability`)** — (UI/Console/Network saja; backend tetap NOT IMPLEMENTED)
```
