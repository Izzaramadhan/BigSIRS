# Arsitektur Master Data Laboratorium

## Keputusan Final

Struktur domain yang dipertahankan:

```
Kategori Lab (laboratory_categories)
    | one-to-many
Grup/Pemeriksaan Lab (ref_grup_lab)
    | many-to-many melalui map_grup_item_lab
Item/Parameter Lab (laboratory_items)
```

## Item Lab (branch ini)

- Sumber legacy tunggal: `ref_item_lab`.
- Tabel target: `laboratory_items`.
- Item Lab **tidak** memiliki relasi langsung ke Kategori Lab dan tidak memiliki `laboratory_category_id`.
- `standar_normal` dipertahankan sebagai teks pada `reference_value` (tidak di-parse menjadi min/max).
- `satuan` dipetakan ke `unit` dan boleh kosong (`null`).
- Importer memakai `legacy_id` sebagai kunci idempotensi sehingga aman dijalankan berulang dan tidak menggandakan item berdasarkan grup.

### Mapping

| Legacy `ref_item_lab` | Target `laboratory_items` |
|---|---|
| `id` | `legacy_id` |
| `nama` | `name` |
| `standar_normal` | `reference_value` |
| `satuan` | `unit` |
| `status` | `is_active` |

## Modul Berikutnya (belum diimplementasikan pada branch ini)

Modul Grup/Pemeriksaan Lab akan mengelola:

- sumber `ref_grup_lab`;
- relasi ke Kategori Lab;
- relasi many-to-many ke Item Lab melalui `map_grup_item_lab`;
- kemungkinan harga/deskripsi dari Grup Lab.

Branch Item Lab ini tidak membuat tabel grup, pivot group-item, CRUD Grup Lab, importer Grup Lab, atau menu Grup Lab.