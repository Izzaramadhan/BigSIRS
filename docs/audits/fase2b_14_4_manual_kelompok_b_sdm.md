# FASE 2B.14.4-M — PANDUAN & LEMBAR CATAT UAT MANUAL KELOMPOK B (DATA DASAR & SDM)

**Target Aplikasi:** `http://localhost:5173` (akun uji dev: `admin`)  
**Direktori Screenshot Manual:** `docs/audits/evidence/fase2b_14_4_manual/`  
**Status Kelompok B Saat Ini:** **`UNVERIFIED`** *(Panduan disiapkan; BELUM diuji).*

> **PENGAMANAN DATABASE (WAJIB DIPERHATIKAN):**  
> `simrs_legacy` pada `bigsirs-mysql` memuat **data impor sistem lama** (`ref_pegawai=87`, `ref_dokter=27`, `ref_pasien=364`).  
> - **Selama database UAT disposable belum tersedia**, jalankan **pengujian READ-ONLY** saja: cek Desktop `1280 × 800`, Mobile `375 × 812`, tabel/search/pagination, Console, Network Fetch/XHR (`200`), serta **inspeksi modal Edit secara read-only (Buka → cek hidrasi field & lookup → klik Batal, JANGAN Simpan)**, lalu tandai CRUD sebagai **`PENDING`**.  
> - Jangan mengubah atau menghapus record legacy eksisting.

---

## Persiapan Google Chrome & DevTools

1. Buka Chrome ke `http://localhost:5173` (login `admin`).
2. Buka DevTools (`F12`):
   - **Console:** centang *Preserve log*; cek `document.documentElement.scrollWidth <= window.innerWidth` (`true` di `1280 × 800` dan `375 × 812`).
   - **Network:** filter **Fetch/XHR**, centang *Preserve log*.
   - Cocokkan **isi data** di tabel dengan respons JSON Network (`200`), bukan sekadar kode statusnya.

---

## Panduan Langkah Uji Per Modul (4 Modul)

### 1. Pendidikan — `/master-data/basic-data/educations`
- **Desktop (`1280px`) & Mobile (`375px`):** Periksa header, breadcrumb, tombol Tambah, kolom pencarian, tombol Refresh, pagination, dan kolom tabel (`EducationTable.vue`):
  - `No` · `Pendidikan` · `Aksi`
- **Network & Search/Pagination (READ-ONLY):**
  - Pastikan `GET /api/v1/master-data/educations` mengembalikan `200` dan data tampil rapi.
  - Uji ketik pencarian + reset + tombol Refresh + pindah halaman (pastikan nomor baris mengikuti offset).
- **Modal & Validasi (tanpa simpan jika READ-ONLY):**
  - Klik **Tambah** → pastikan modal `Tambah Pendidikan` (field `Nama Pendidikan`) terbuka rapi di `1280px` & `375px`, uji submit kosong untuk cek validasi client-side, lalu klik **Batal**.
  - Klik **Edit** pada satu baris → pastikan `Edit Pendidikan` mengisi `Nama Pendidikan` lama → klik **Batal** (jangan simpan).
- **CRUD (hanya jika DB disposable aman = `PENDING` saat READ-ONLY):**
  - Tambah dummy → Edit → reload (`F5`) → uji proteksi hapus `409` jika dipakai pegawai → hapus dummy.

### 2. Pekerjaan — `/master-data/basic-data/occupations`
- **Desktop (`1280px`) & Mobile (`375px`):** Periksa header, breadcrumb, tombol Tambah, search, Refresh, pagination, dan kolom tabel (`OccupationTable.vue`):
  - `No` · `Pekerjaan` · `Aksi`
- **Network & Search/Pagination (READ-ONLY):**
  - Pastikan `GET /api/v1/master-data/occupations` (`200`), uji search, reset, Refresh, pagination.
- **Modal & Validasi (tanpa simpan jika READ-ONLY):**
  - Buka `Tambah Pekerjaan` (field `Nama Pekerjaan`), cek validasi kosong, klik **Batal**; buka `Edit Pekerjaan`, cek nilai lama terhidrasi, klik **Batal**.
- **CRUD (`PENDING` saat READ-ONLY):** uji proteksi hapus `409` + lifecycle dummy hanya di DB disposable.

### 3. Jabatan — `/master-data/positions`
- **Desktop (`1280px`) & Mobile (`375px`):** Periksa header, breadcrumb, search, Refresh, pagination, dan kolom tabel (`PositionTable.vue`):
  - `No` · `Nama Jabatan` · `Deskripsi` · `Aksi`
- **Network & Search/Pagination (READ-ONLY):**
  - Pastikan `GET /api/v1/master-data/positions` (`200`), uji search/reset/Refresh/pagination. *(Catatan backlog `FND-07`: backend mengurutkan ascending tetap).*
- **Modal & Validasi (tanpa simpan jika READ-ONLY):**
  - Buka `Tambah Jabatan` / `Edit Jabatan` (`Nama Jabatan`, `Deskripsi`), cek responsivitas di `375px` & validasi wajib, klik **Batal**.
- **CRUD (`PENDING` saat READ-ONLY):** cek proteksi hapus `409` saat dipakai pegawai + lifecycle dummy hanya di DB disposable.

### 4. Pegawai — `/master-data/employees`
- **Fokus Khusus:** Verifikasi form pasca-perbaikan Fase 2B.5.1, konsistensi lookup Jabatan/Pendidikan/Pekerjaan/Wilayah, No. HP, serta tampilan Tanda Tangan Digital.
- **Desktop (`1280px`) & Mobile (`375px`):**
  - Periksa header, breadcrumb, filter/search, tombol Refresh, pagination, dan kolom tabel (`EmployeeTable.vue`):
    `No` · `NIP / ID` · `Nama Pegawai` · `Jabatan` · `Aksi`
  - Cek konsistensi kolom `Jabatan` terhadap Master Data Jabatan.
- **Network & Inspeksi Form Read-Only:**
  - Pastikan `GET /api/v1/master-data/employees` (`200`) memuat data pegawai (`87` record di legacy atau sesuai DB).
  - Klik **Tambah** (`Tambah Data Pegawai`): verifikasi Network memanggil lookup (`/lookups/positions`, `/lookups/educations`, `/lookups/occupations`, `/lookups/provinces`) tanpa error, periksa seluruh field form (NIK, Nama Lengkap, Tempat/Tgl Lahir, Gol. Darah, Agama, Status Pernikahan, Kewarganegaraan, Alergi, Pendidikan Terakhir, Pekerjaan, Alamat Lengkap, Wilayah bertingkat, Kode Pos, **No. Telepon / HP**, NIP, **Jabatan**, **Tanda Tangan Digital**) tampil rapi tanpa terpotong di `1280px` & `375px`. Uji submit kosong untuk melihat pesan validasi wajib (`NIK`, `Nama Lengkap`, `Jabatan`), lalu klik **Batal**.
  - Klik **Edit** pada salah satu pegawai (`Edit Data Pegawai`): pastikan `GET /api/v1/master-data/employees/{id}` (`200`) menghidrasi seluruh biodata, wilayah (`regencies`/`districts`/`villages`), **No. HP**, dropdown **Jabatan** terpilih sesuai tabel, dan pratinjau tanda tangan (jika ada) tanpa error Console — lalu klik **Batal** (**JANGAN klik Simpan** agar data legacy tidak berubah).
- **CRUD (`PENDING` saat READ-ONLY):** Tambah/Edit/Simpan/Reload/Upload tanda tangan/Hapus hanya dilakukan pada DB disposable.

---

## Format Pencatatan Hasil Kelompok B (Untuk Pengisian Berikutnya)

```markdown
### Hasil UAT Manual Kelompok B — Data Dasar & SDM
- **Mode Pengujian:** READ-ONLY (simrs_legacy berisi data impor) / CRUD DB Disposable
- **Lokasi Screenshot:** `docs/audits/evidence/fase2b_14_4_manual/`

| Modul | Desktop 1280px | Mobile 375px | List/Search/Pagination | Modal/Lookup Read-Only | Console/Network | CRUD | Screenshot | Masalah |
|---|---|---|---|---|---|---|---|---|
| Pendidikan (`/master-data/basic-data/educations`) | | | | | | PENDING | | |
| Pekerjaan (`/master-data/basic-data/occupations`) | | | | | | PENDING | | |
| Jabatan (`/master-data/positions`) | | | | | | PENDING | | |
| Pegawai (`/master-data/employees`) | | | | | | PENDING | | |
```
