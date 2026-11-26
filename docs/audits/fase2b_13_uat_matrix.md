# FASE 2B.13 — MATRIKS UAT & RESPONSIVITAS MASTER DATA BIGSIRS

**Tanggal Audit:** 2026-11-26  
**Aturan Pelaporan:** Status `PASS` hanya diberikan untuk modul yang memiliki konfirmasi UAT browser eksplisit dari pengguna/sesi. Kelulusan Vitest, Vite Build, maupun PHPUnit **tidak** dihitung sebagai bukti kelulusan UAT visual.

| # | Modul | Fase | Route | Desktop `1280px` | Mobile `375px` | CRUD Manual (Dev DB) | Console / Network | Bukti Terverifikasi | Catatan / Masalah | Status Akhir |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | **Gedung** | 2A | `/master-data/bed/buildings` | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Vitest + DS lint `0` **[TEST]** | Termasuk kelompok Bed yang UAT-nya tercatat masih sebagian tertunda | **PENDING / UNVERIFIED** |
| 2 | **Lantai** | 2A | `/master-data/bed/floors` | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Vitest + DS lint `0` **[TEST]** | Bergantung lookup Gedung | **PENDING / UNVERIFIED** |
| 3 | **Bangsal** | 2A | `/master-data/bed/wards` | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Vitest + DS lint `0` **[TEST]** | Bergantung lookup Lantai | **PENDING / UNVERIFIED** |
| 4 | **Kamar** | 2A | `/master-data/bed/rooms` | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Vitest + DS lint `0` **[TEST]** | Termasuk `AddRoomClassDialog` | **PENDING / UNVERIFIED** |
| 5 | **Bed** | 2A | `/master-data/bed/beds` | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Vitest + DS lint `0` **[TEST]** | Termasuk kelompok Bed tertunda | **PENDING / UNVERIFIED** |
| 6 | **Kelas Kamar** | 2A | `/master-data/bed/room-classes` | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Belum diverifikasi ulang | Vitest + PHPUnit + DS lint `0` **[TEST]** | Stabilisasi filter di 2A.10 | **PENDING / UNVERIFIED** |
| 7 | **Ketersediaan Bed SIRS** | 2A | `/master-data/bed/sirs-availability` | Belum terkonfirmasi final | Belum terkonfirmasi final | N/A (belum ada API) | N/A (prototipe lokal) | Vitest + DS lint `0` **[TEST]** | Masih prototipe frontend kosong (`initialRows = []`) | **PENDING** |
| 8 | **Pendidikan** | 2B.1 | `/master-data/basic-data/educations` | Belum diverifikasi di sesi ini | Belum diverifikasi di sesi ini | Belum diverifikasi di sesi ini | Belum diverifikasi di sesi ini | Vitest + DS lint `0` **[TEST]** | Perlindungan hapus `409` terverifikasi di kode | **UNVERIFIED** |
| 9 | **Pekerjaan** | 2B.2 | `/master-data/basic-data/occupations` | Belum diverifikasi di sesi ini | Belum diverifikasi di sesi ini | Belum diverifikasi di sesi ini | Belum diverifikasi di sesi ini | Vitest + DS lint `0` **[TEST]** | Perlindungan hapus `409` terverifikasi di kode | **UNVERIFIED** |
| 10 | **Jabatan** | 2B.3 | `/master-data/positions` | Belum diverifikasi di sesi ini | Belum diverifikasi di sesi ini | Belum diverifikasi di sesi ini | Belum diverifikasi di sesi ini | Vitest + DS lint `0` **[TEST]** | Dipakai lookup Jabatan Pegawai | **UNVERIFIED** |
| 11 | **Pegawai** | 2B.4 / 2B.5.1 | `/master-data/employees` | Belum diverifikasi pasca-2B.5.1 | Belum diverifikasi pasca-2B.5.1 | Belum diverifikasi pasca-2B.5.1 | Belum diverifikasi pasca-2B.5.1 | Vitest + DS lint `0` **[TEST]** | Perlu verifikasi form pasca-perbaikan 2B.5.1 | **PENDING / UNVERIFIED** |
| 12 | **Dokter** | 2B.5–2B.5.2 | `/master-data/doctors` | **PENDING** | **PENDING** | **PENDING** | **PENDING** | Vitest + `DoctorTest` **[TEST]** | Stabilisasi duplikasi pegawai & nullable 2B.5.2 menunggu konfirmasi UAT | **PENDING** |
| 13 | **Jadwal Dokter** | 2B.6 | `/master-data/doctor-schedules` | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | Konfirmasi UAT Fase 2B.7 + Vitest **[UAT][TEST]** | Termasuk tabel rekap mingguan & lookup dokter/poliklinik | **PASS** |
| 14 | **Poliklinik** | 2B.7 / 2B.8.1 | `/master-data/polyclinics` | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | Konfirmasi UAT Fase 2B.8 + Vitest **[UAT][TEST]** | Finalisasi registrasi DS & race-condition di 2B.8.1 | **PASS** |
| 15 | **Jenis Layanan** | 2B.8 | `/master-data/service-types` | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | Konfirmasi UAT Fase 2B.9 + Vitest **[UAT][TEST]** | Adapter `order` terverifikasi | **PASS** |
| 16 | **Jenis Asuransi / Penjamin** | 2B.9 / 2B.9.1 | `/master-data/basic-data/insurance-types` | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | Konfirmasi UAT Fase 2B.10 + Vitest **[UAT][TEST]** | Backend `UpdateGuarantorRequest` sudah distabilkan 2B.9.1 | **PASS** |
| 17 | **Asuhan Gizi** | 2B.10 | `/master-data/nutrition-care` | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | Konfirmasi UAT pengguna sebelum 2B.11 + `DietTypeTest` **[UAT][TEST]** | 4 kolom tabel + 2 field form tanpa kolom Status | **PASS** |
| 18 | **Jenis Kegiatan** | 2B.11 | `/master-data/activity-types` | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | PASS **[UAT]** | Konfirmasi UAT pengguna sebelum 2B.12 + `ActivityTypeTest` **[UAT][TEST]** | Dropdown Induk, indentasi anak, & penolakan hapus induk `422` | **PASS** |
| 19 | **Surat-Surat** | 2B.12 / 2B.12.1 | `/master-data/letter-types` | **PENDING** | **PENDING** | **PENDING** | **PENDING** | Vitest `16/16` + `LetterTypeTest` `11/11` **[TEST]** | Menunggu UAT visual tabel 5 kolom, form 3 field, `403`/`404`/`422`, & `Lihat Format Lama` aman | **PENDING** |

### Ringkasan Matriks UAT
- **Terkonfirmasi `PASS`:** `6` modul (Jadwal Dokter, Poliklinik, Jenis Layanan, Jenis Asuransi/Penjamin, Asuhan Gizi, Jenis Kegiatan).
- **Status `PENDING`:** `9` modul (Surat-Surat, Dokter pasca-stabilisasi, serta 7 modul Data Bed termasuk Ketersediaan Bed SIRS).
- **Status `UNVERIFIED` di sesi ini:** `4` modul awal Fase 2B (Pendidikan, Pekerjaan, Jabatan, Pegawai).
