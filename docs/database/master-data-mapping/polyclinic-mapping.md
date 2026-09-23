# Polyclinic Mapping

Berdasarkan audit dari SQL dump legacy (`backup_db_simrs_masking-202609101406.sql`), berikut adalah pemetaan untuk entitas Poliklinik.

## Tabel Legacy
- **Nama Tabel:** `ref_poliklinik`
- **Primary Key:** `id` (int(11) NOT NULL AUTO_INCREMENT)
- **Unique Constraint:** Tidak ditemukan unique constraint eksplisit pada field `kode`.
- **Foreign Key:** Tidak ada foreign key eksplisit ke tabel lain. Terdapat KEY pada `id_user`.

### Struktur Kolom Legacy
- `id` int(11)
- `kode` varchar(11)
- `parent_id` varchar(11)
- `id_gudang` int(11)
- `nama` varchar(50)
- `jenis` enum('rawat-jalan','rawat-inap','igd','layanan-penunjang','farmasi','billing','operasi','icu','ponek','vk')
- `deskripsi` varchar(100)
- `tampil` enum('0','1')
- `status_gudang` enum('0','1')
- `status` enum('0','1')
- `created_at` datetime
- `updated_at` datetime
- `deleted_at` datetime
- `id_user` int(11)
- `kode_bpjs` varchar(255)
- `fhir_resource_id` varchar(255)
- `kuota` int(11)
- `kuota_jkn` int(11)
- `ihs_id_location` varchar(255)
- `ihs_id_organization` varchar(255)
- `tampil_online` enum('1','0')
- `tampil_antrian` int(11)
- `kode_antrian` varchar(1)
- `fhir_id_location` varchar(255)
- `suara_antrian` varchar(255)

## Analisis Referensi
- **Tabel Transaksi yang mereferensikan:**
  - `trx_jadwal_dokter` (kolom `id_poliklinik` - Confirmed via FOREIGN KEY)
  - `trx_visit` (kolom `id_poliklinik` - Confirmed via FOREIGN KEY)
  - `trx_rujukan_internal_klinik` (kolom `id_poliklinik` - Inferred)
- **Tipe identifier:** `int(11)` digunakan lintas cabang.
- **Status Multi-facility:** Ada indikasi field `ihs_id_organization` dan `ihs_id_location`, sehingga poli ini mungkin di-mapping per faskes (terkait integrasi SatuSehat).

## Profiling Data Legacy
Statistik berdasarkan 142 baris dari `ref_poliklinik`:
- **Kualitas Kode:** 1 record memiliki kode kosong/spasi. Panjang maksimum kode 7 karakter (contoh: `OP0001`). 
- **Duplikasi:** 
  - Terdapat 1 kode duplikat: `OP0013` (muncul 2 kali).
  - Terdapat 3 nama duplikat: `poliklinik mata`, `instalasi gawat darurat`, `poliklinik onkologi`.
- **Kualitas Data:**
  - Rekord aktif: 137, Nonaktif: 5.
  - Soft-deleted: 6 record.
  - `parent_id` digunakan oleh 90 record (mengindikasikan poli sub-spesialis).
  - `tampil_antrian` semuanya bernilai 0 (field tidak terpakai/ambigu).

## Rancangan Target Minimal (Evaluasi)

Berdasarkan *profiling*, evaluasi target *schema* baru untuk `polyclinics`:

| Target field | Legacy source | Keputusan | Alasan | Confidence |
| ------------ | ------------- | --------- | ------ | ---------- |
| `id` | `id` | **Approved** | Standar Laravel (`bigint unsigned auto_increment`). | Confirmed |
| `legacy_id` | `id` | **Approved** | Dibutuhkan untuk referensi relasi (migration tanpa memutus data transaksi lama). | Confirmed |
| `code` | `kode` | **Approved** (Unique) | Digunakan untuk identifikasi integrasi. | Confirmed |
| `name` | `nama` | **Approved** | Nama poliklinik utama. | Confirmed |
| `short_name` | - | **Rejected** | Tidak tersedia di legacy dan tidak ada urgensi bisnis saat ini. | Inferred |
| `bpjs_code` | `kode_bpjs` | **Approved** | Kebutuhan bridging V-Claim BPJS. | Confirmed |
| `satusehat_code` | `ihs_id_location` | **Approved** | Kebutuhan bridging SatuSehat. | Confirmed |
| `is_active` | `status` | **Approved** | Ubah dari enum '0'/'1' ke boolean. | Confirmed |
| `sort_order` | `tampil_antrian` | **Rejected** | Karena 100% data di legacy bernilai `0`, field ini terbukti tidak terpakai. | Confirmed |
| - | `id_gudang` | **Drop** | Logistik gudang harus dipisah, jangan diikat statis di tabel master poli. | Confirmed |
| - | `parent_id` | **Needs Decision** | Ada 90 poli yang memilikinya. Apakah target sistem menuntut relasi *self-referencing* untuk hierarki poli? | Confirmed |

## Aturan Cleansing Data
- **Normalisasi Kode:** Wajib *Trim*. Kode kosong/spasi (1 record) harus dibuatkan kode *generate* baru. Kode duplikat (`OP0013`) harus direvisi salah satunya (misal menjadi `OP0013A`).
- **Normalisasi Nama:** Wajib *Trim* dan *Title Case*. Nama duplikat (misal: "poliklinik mata") harus dimerge, atau diberi penanda cabang jika memang 2 entitas berbeda, atau dihapus jika salah satunya tidak digunakan.
- **Soft Delete:** Rekord dengan status `0` atau memiliki `deleted_at` tidak perlu dimigrasikan sebagai data aktif.

## Keputusan Teknis yang Membutuhkan Supervisor
1. **Hierarki Poli (parent_id):** Apakah fitur hierarki (Poli Utama -> Sub Poli) akan dibangun, mengingat di sistem lama fitur ini dipakai oleh 90 poli?
2. **Strategi Primary Key:** Kami merancang `id` baru (`bigint`) dan `legacy_id` untuk menyimpan ID integer lama. Apakah disetujui?
3. **Resolusi Duplikasi Kode:** Bagaimana SOP tim untuk memecahkan duplikasi kode yang sudah terjadi (misalnya `OP0013`)? Mengubah kode lama bisa berdampak ke integrasi eksternal.
