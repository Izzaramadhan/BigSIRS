# Pertanyaan untuk Supervisor / Stakeholder

Pada proses audit Master Data tahap awal, terdapat beberapa hal yang perlu dikonfirmasi atau membutuhkan kejelasan lebih lanjut sebelum eksekusi (*coding*) dilakukan:

## 1. Migrasi Data dan Identifier Inti (Primary Key)
Struktur *legacy* menggunakan `int(11) AUTO_INCREMENT` untuk Poliklinik dan Penjamin.
- **Pertanyaan:** Apakah kita tetap mempertahankan tipe integer agar selaras dengan skema lama (sehingga jika sewaktu-waktu ada migrasi data *hard-copy*, ID-nya tetap sama), atau sistem baru akan bermigrasi penuh menggunakan arsitektur UUID?

## 2. Penegakan Relasi (Foreign Key Constraint)
Berdasarkan audit, tabel `ref_jenis_asuransi` di legacy tidak memiliki *Foreign Key constraint* secara database ke tabel transaksinya (meski nama kolomnya bersesuaian, misal `id_jenis_asuransi`).
- **Pertanyaan:** Apakah di sistem baru relasi ini akan secara ketat ditegakkan (*Strict Foreign Key*)? Ini mungkin akan berdampak saat migrasi data jika data lama memiliki *orphan records*.

## 3. Strategi Pengkodean dan Unique Constraint
Legacy tidak memiliki *unique constraint* eksplisit untuk field `kode` pada Poliklinik maupun Asuransi, dan hasil *profiling* menemukan adanya anomali:
- **Poliklinik:** Terdapat duplikat kode `OP0013`. Apakah *SOP* pembersihan datanya adalah menambahkan *suffix* (contoh: `OP0013A`) untuk salah satunya?
- **Asuransi/Penjamin:** 7 dari 8 data tidak memiliki kode sama sekali. Jika *database* baru mewajibkan *constraint* `UNIQUE`, apakah sistem migrator berwenang melakukan *auto-generate* kode (misal `ASR-001`) untuk seluruh entitas penjamin lama?

## 4. Hierarki Poliklinik
- **Pertanyaan:** Profiling menunjukkan 90 dari 142 poliklinik menggunakan `parent_id`. Apakah fitur hierarki Poliklinik (induk/sub-poli) harus dibangun di sistem baru, atau diabaikan karena hanya untuk pelaporan *legacy*?
Pasien secara teknis merupakan inti dari transaksi rekam medis, namun sering dianggap sebagai Master Data demografis.
- **Pertanyaan:** Apakah modul Master Data Pasien masuk dalam prioritas rilis Master Data tahap awal ini, atau akan dikerjakan bersamaan dengan modul Pendaftaran?

## 3. Strategi Pengkodean (Primary Key vs Business Code)
Sesuai prinsip target model, *primary key* internal akan menggunakan standar project (umumnya `id` *Auto-increment* atau UUID). Namun, Master Data seperti Poliklinik biasanya memiliki "Kode Poli".
- **Pertanyaan:** Apakah kode bisnis (seperti Kode Poli, Kode Obat) harus di-generate secara otomatis oleh sistem dengan format tertentu (contoh: `POL-001`), atau diinput manual oleh admin pada saat penambahan data?

## 4. Klasifikasi Barang Logistik
Pada bagian farmasi/logistik, kategori dan satuan barang sering kali cukup statis.
- **Pertanyaan:** Apakah `Kategori Barang` dan `Satuan Barang` cukup dijadikan *Enum/Const* di level aplikasi, atau perlu memiliki tabel Master Data tersendiri yang dinamis (CRUD) di dalam database?

## 5. Scope Modul Rawat Jalan vs Logistik
Master Data yang ditemukan di navigasi terbagi ke dua modul besar (Pelayanan Klinis dan Logistik Farmasi).
- **Pertanyaan:** Apakah implementasi fitur (UI/API) untuk Master Data tersebut akan dikerjakan secara paralel (berbarengan) atau berurutan (misal: Selesaikan Master Data Rawat Jalan dahulu sampai *production*, baru lanjut ke Logistik)?
