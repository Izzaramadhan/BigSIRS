// Mock data for Logistik - Faktur (Invoice)
// Realistic mock fixtures for pharmaceutical procurement

export const mockSuppliers = [
  {
    id: 1,
    name: 'PT Kimia Farma Trading & Distribution',
    city: 'Jakarta Pusat',
    npwp: '01.324.861.2-073.000',
    address: 'Jl. Budi Utomo No. 1, Jakarta Pusat',
    phone: '(021) 3847709'
  },
  {
    id: 2,
    name: 'PT Kalbe Farma Tbk',
    city: 'Cikarang',
    npwp: '01.328.712.1-413.000',
    address: 'Kawasan Industri Delta Silicon, Cikarang',
    phone: '(021) 89907333'
  },
  {
    id: 3,
    name: 'PT Enseval Putera Megatrading',
    city: 'Jakarta Timur',
    npwp: '01.345.890.3-005.000',
    address: 'Jl. Pulo Lentut No. 10, Kawasan Industri Pulogadung',
    phone: '(021) 4609042'
  },
  {
    id: 4,
    name: 'PT Mensa Binasukses',
    city: 'Tangerang',
    npwp: '01.312.445.6-418.000',
    address: 'Jl. Otto Iskandardinata No. 54, Tangerang',
    phone: '(021) 5523114'
  },
  {
    id: 5,
    name: 'PT Anugrah Argon Medica',
    city: 'Bekasi',
    npwp: '01.298.113.4-323.000',
    address: 'Titan Center Lt. 3, Jl. Boulevard Bintaro Block B7/B1 No. 5',
    phone: '(021) 7458899'
  },
  {
    id: 6,
    name: 'PT Tempo Scan Pacific Tbk',
    city: 'Jakarta Selatan',
    npwp: '01.319.821.5-062.000',
    address: 'Tempo Scan Tower, Jl. H.R. Rasuna Said Kav. 3-4',
    phone: '(021) 29208888'
  },
  {
    id: 7,
    name: 'PT Dexa Medica',
    city: 'Palembang',
    npwp: '01.309.654.7-301.000',
    address: 'Jl. Jenderal Bambang Utoyo No. 138, Palembang',
    phone: '(0711) 711136'
  },
  {
    id: 8,
    name: 'PT Sanbe Farma',
    city: 'Bandung',
    npwp: '01.243.567.8-422.000',
    address: 'Jl. Tamansari No. 10, Bandung',
    phone: '(022) 4207725'
  }
];

export const mockMedicines = [
  { id: 1, code: 'MED-001', name: 'Paracetamol 500 mg Tablet', category: 'Analgesik & Antipiretik', unit: 'Box (100 Tab)', price: 45000 },
  { id: 2, code: 'MED-002', name: 'Amoxicillin 500 mg Kapsul', category: 'Antibiotik', unit: 'Box (100 Kap)', price: 78000 },
  { id: 3, code: 'MED-003', name: 'Omeprazole 20 mg Kapsul', category: 'Saluran Cerna', unit: 'Box (30 Kap)', price: 92000 },
  { id: 4, code: 'MED-004', name: 'Amlodipine 10 mg Tablet', category: 'Antihipertensi', unit: 'Box (30 Tab)', price: 65000 },
  { id: 5, code: 'MED-005', name: 'Metformin 500 mg Tablet', category: 'Antidiabetes', unit: 'Box (100 Tab)', price: 54000 },
  { id: 6, code: 'MED-006', name: 'Cefixime 100 mg Kapsul', category: 'Antibiotik', unit: 'Box (30 Kap)', price: 145000 },
  { id: 7, code: 'MED-007', name: 'Cetirizine 10 mg Tablet', category: 'Antihistamin', unit: 'Box (100 Tab)', price: 48000 },
  { id: 8, code: 'MED-008', name: 'Ibuprofen 400 mg Tablet', category: 'Analgesik / NSAID', unit: 'Box (100 Tab)', price: 58000 },
  { id: 9, code: 'MED-009', name: 'Ciprofloxacin 500 mg Tablet', category: 'Antibiotik', unit: 'Box (50 Tab)', price: 110000 },
  { id: 10, code: 'MED-010', name: 'Ranitidine 150 mg Tablet', category: 'Saluran Cerna', unit: 'Box (100 Tab)', price: 62000 },
  { id: 11, code: 'MED-011', name: 'Infus Ringer Laktat 500 ml', category: 'Cairan Infus', unit: 'Karton (20 Botol)', price: 240000 },
  { id: 12, code: 'MED-012', name: 'Ceftriaxone 1 gr Injeksi', category: 'Antibiotik Injeksi', unit: 'Vial', price: 85000 }
];

export const initialInvoices = [
  {
    id: 1,
    invoiceNumber: 'FKT-KF-202609-0892',
    referenceNumber: 'PO-2026-0814',
    invoiceDate: '2026-09-12',
    dueDate: '2026-10-12',
    supplierId: 1,
    supplierName: 'PT Kimia Farma Trading & Distribution',
    supplierCity: 'Jakarta Pusat',
    supplierNpwp: '01.324.861.2-073.000',
    status: 'Belum Lunas',
    notes: 'Pengiriman batch 1 obat rawat inap',
    paidAt: null,
    items: [
      { id: 1, medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 250, unit: 'Box (100 Tab)', price: 45000, discount: 0, subtotal: 11250000 },
      { id: 2, medicineId: 2, medicineName: 'Amoxicillin 500 mg Kapsul', quantity: 250, unit: 'Box (100 Kap)', price: 78000, discount: 0, subtotal: 19500000 },
      { id: 3, medicineId: 3, medicineName: 'Omeprazole 20 mg Kapsul', quantity: 150, unit: 'Box (30 Kap)', price: 92000, discount: 5, subtotal: 13110000 },
      { id: 4, medicineId: 5, medicineName: 'Metformin 500 mg Tablet', quantity: 90, unit: 'Box (100 Tab)', price: 54000, discount: 0, subtotal: 4890000 }
    ],
    totalAmount: 48750000
  },
  {
    id: 2,
    invoiceNumber: 'FKT-KLB-202609-0415',
    referenceNumber: 'PO-2026-0810',
    invoiceDate: '2026-09-11',
    dueDate: '2026-10-11',
    supplierId: 2,
    supplierName: 'PT Kalbe Farma Tbk',
    supplierCity: 'Cikarang',
    supplierNpwp: '01.328.712.1-413.000',
    status: 'Lunas',
    notes: 'Pelunasan termin 1 via transfer bank',
    paidAt: '2026-09-12',
    items: [
      { id: 1, medicineId: 6, medicineName: 'Cefixime 100 mg Kapsul', quantity: 400, unit: 'Box (30 Kap)', price: 145000, discount: 0, subtotal: 58000000 },
      { id: 2, medicineId: 11, medicineName: 'Infus Ringer Laktat 500 ml', quantity: 250, unit: 'Karton (20 Botol)', price: 240000, discount: 0, subtotal: 60000000 },
      { id: 3, medicineId: 8, medicineName: 'Ibuprofen 400 mg Tablet', quantity: 120, unit: 'Box (100 Tab)', price: 58000, discount: 2.5, subtotal: 6800000 }
    ],
    totalAmount: 124800000
  },
  {
    id: 3,
    invoiceNumber: 'FKT-ENS-202609-0122',
    referenceNumber: 'PO-2026-0795',
    invoiceDate: '2026-09-09',
    dueDate: '2026-09-08',
    supplierId: 3,
    supplierName: 'PT Enseval Putera Megatrading',
    supplierCity: 'Jakarta Timur',
    supplierNpwp: '01.345.890.3-005.000',
    status: 'Belum Lunas',
    notes: 'Menunggu verifikasi faktur pajak',
    paidAt: null,
    items: [
      { id: 1, medicineId: 9, medicineName: 'Ciprofloxacin 500 mg Tablet', quantity: 380, unit: 'Box (50 Tab)', price: 110000, discount: 0, subtotal: 41800000 },
      { id: 2, medicineId: 12, medicineName: 'Ceftriaxone 1 gr Injeksi', quantity: 450, unit: 'Vial', price: 85000, discount: 0, subtotal: 38250000 },
      { id: 3, medicineId: 7, medicineName: 'Cetirizine 10 mg Tablet', quantity: 135, unit: 'Box (100 Tab)', price: 48000, discount: 0, subtotal: 6370000 }
    ],
    totalAmount: 86420000
  },
  {
    id: 4,
    invoiceNumber: 'FKT-MBS-202609-0731',
    referenceNumber: 'PO-2026-0782',
    invoiceDate: '2026-09-08',
    dueDate: '2026-10-09',
    supplierId: 4,
    supplierName: 'PT Mensa Binasukses',
    supplierCity: 'Tangerang',
    supplierNpwp: '01.312.445.6-418.000',
    status: 'Lunas',
    notes: 'Lunas sesuai PO 0782',
    paidAt: '2026-09-10',
    items: [
      { id: 1, medicineId: 4, medicineName: 'Amlodipine 10 mg Tablet', quantity: 320, unit: 'Box (30 Tab)', price: 65000, discount: 0, subtotal: 20800000 },
      { id: 2, medicineId: 5, medicineName: 'Metformin 500 mg Tablet', quantity: 210, unit: 'Box (100 Tab)', price: 54000, discount: 0, subtotal: 11350000 }
    ],
    totalAmount: 32150000
  },
  {
    id: 5,
    invoiceNumber: 'FKT-AAM-202609-0294',
    referenceNumber: 'PO-2026-0774',
    invoiceDate: '2026-09-06',
    dueDate: '2026-10-06',
    supplierId: 5,
    supplierName: 'PT Anugrah Argon Medica',
    supplierCity: 'Bekasi',
    supplierNpwp: '01.298.113.4-323.000',
    status: 'Belum Lunas',
    notes: 'Faktur tempo 30 hari',
    paidAt: null,
    items: [
      { id: 1, medicineId: 11, medicineName: 'Infus Ringer Laktat 500 ml', quantity: 300, unit: 'Karton (20 Botol)', price: 240000, discount: 0, subtotal: 72000000 },
      { id: 2, medicineId: 10, medicineName: 'Ranitidine 150 mg Tablet', quantity: 375, unit: 'Box (100 Tab)', price: 62000, discount: 0, subtotal: 23300000 }
    ],
    totalAmount: 95300000
  },
  {
    id: 6,
    invoiceNumber: 'FKT-KF-202609-0850',
    referenceNumber: 'PO-2026-0761',
    invoiceDate: '2026-09-05',
    dueDate: '2026-10-05',
    supplierId: 1,
    supplierName: 'PT Kimia Farma Trading & Distribution',
    supplierCity: 'Jakarta Pusat',
    supplierNpwp: '01.324.861.2-073.000',
    status: 'Lunas',
    notes: 'Selesai serah terima farmasi',
    paidAt: '2026-09-07',
    items: [
      { id: 1, medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 600, unit: 'Box (100 Tab)', price: 45000, discount: 0, subtotal: 27000000 },
      { id: 2, medicineId: 3, medicineName: 'Omeprazole 20 mg Kapsul', quantity: 400, unit: 'Box (30 Kap)', price: 92000, discount: 0, subtotal: 36800000 },
      { id: 3, medicineId: 7, medicineName: 'Cetirizine 10 mg Tablet', quantity: 10, unit: 'Box (100 Tab)', price: 48000, discount: 16.6, subtotal: 400000 }
    ],
    totalAmount: 64200000
  },
  {
    id: 7,
    invoiceNumber: 'FKT-TEM-202609-0045',
    referenceNumber: 'PO-2026-0744',
    invoiceDate: '2026-09-03',
    dueDate: '2026-09-01',
    supplierId: 6,
    supplierName: 'PT Tempo Scan Pacific Tbk',
    supplierCity: 'Jakarta Selatan',
    supplierNpwp: '01.319.821.5-062.000',
    status: 'Belum Lunas',
    notes: 'Perlu konfirmasi ulang bagian keuangan',
    paidAt: null,
    items: [
      { id: 1, medicineId: 8, medicineName: 'Ibuprofen 400 mg Tablet', quantity: 500, unit: 'Box (100 Tab)', price: 58000, discount: 0, subtotal: 29000000 },
      { id: 2, medicineId: 4, medicineName: 'Amlodipine 10 mg Tablet', quantity: 200, unit: 'Box (30 Tab)', price: 65000, discount: 0, subtotal: 13000000 },
      { id: 3, medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 20, unit: 'Box (100 Tab)', price: 45000, discount: 11.1, subtotal: 800000 }
    ],
    totalAmount: 41900000
  },
  {
    id: 8,
    invoiceNumber: 'FKT-KLB-202609-0390',
    referenceNumber: 'PO-2026-0738',
    invoiceDate: '2026-09-02',
    dueDate: '2026-10-02',
    supplierId: 2,
    supplierName: 'PT Kalbe Farma Tbk',
    supplierCity: 'Cikarang',
    supplierNpwp: '01.328.712.1-413.000',
    status: 'Lunas',
    notes: 'Pengadaan besar sediaan injeksi',
    paidAt: '2026-09-04',
    items: [
      { id: 1, medicineId: 12, medicineName: 'Ceftriaxone 1 gr Injeksi', quantity: 1200, unit: 'Vial', price: 85000, discount: 0, subtotal: 102000000 },
      { id: 2, medicineId: 6, medicineName: 'Cefixime 100 mg Kapsul', quantity: 500, unit: 'Box (30 Kap)', price: 145000, discount: 0, subtotal: 72500000 },
      { id: 3, medicineId: 5, medicineName: 'Metformin 500 mg Tablet', quantity: 75, unit: 'Box (100 Tab)', price: 54000, discount: 0, subtotal: 4100000 }
    ],
    totalAmount: 178600000
  },
  {
    id: 9,
    invoiceNumber: 'FKT-DEX-202609-0118',
    referenceNumber: 'PO-2026-0711',
    invoiceDate: '2026-09-01',
    dueDate: '2026-10-01',
    supplierId: 7,
    supplierName: 'PT Dexa Medica',
    supplierCity: 'Palembang',
    supplierNpwp: '01.309.654.7-301.000',
    status: 'Lunas',
    notes: 'Lunas via bilyet giro',
    paidAt: '2026-09-03',
    items: [
      { id: 1, medicineId: 2, medicineName: 'Amoxicillin 500 mg Kapsul', quantity: 450, unit: 'Box (100 Kap)', price: 78000, discount: 0, subtotal: 35100000 },
      { id: 2, medicineId: 3, medicineName: 'Omeprazole 20 mg Kapsul', quantity: 200, unit: 'Box (30 Kap)', price: 92000, discount: 0, subtotal: 18400000 }
    ],
    totalAmount: 53400000
  },
  {
    id: 10,
    invoiceNumber: 'FKT-ENS-202608-0988',
    referenceNumber: 'PO-2026-0689',
    invoiceDate: '2026-08-28',
    dueDate: '2026-09-28',
    supplierId: 3,
    supplierName: 'PT Enseval Putera Megatrading',
    supplierCity: 'Jakarta Timur',
    supplierNpwp: '01.345.890.3-005.000',
    status: 'Lunas',
    notes: 'Selesai pembayaran termin akhir Agustus',
    paidAt: '2026-08-30',
    items: [
      { id: 1, medicineId: 7, medicineName: 'Cetirizine 10 mg Tablet', quantity: 300, unit: 'Box (100 Tab)', price: 48000, discount: 0, subtotal: 14400000 },
      { id: 2, medicineId: 10, medicineName: 'Ranitidine 150 mg Tablet', quantity: 210, unit: 'Box (100 Tab)', price: 62000, discount: 0, subtotal: 13020000 },
      { id: 3, medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 5, unit: 'Box (100 Tab)', price: 45000, discount: 0, subtotal: 230000 }
    ],
    totalAmount: 27650000
  },
  {
    id: 11,
    invoiceNumber: 'FKT-SNB-202608-0512',
    referenceNumber: 'PO-2026-0650',
    invoiceDate: '2026-08-20',
    dueDate: '2026-09-20',
    supplierId: 8,
    supplierName: 'PT Sanbe Farma',
    supplierCity: 'Bandung',
    supplierNpwp: '01.243.567.8-422.000',
    status: 'Lunas',
    notes: 'Pengadaan antibiotik sediaan sirup & kapsul',
    paidAt: '2026-08-22',
    items: [
      { id: 1, medicineId: 6, medicineName: 'Cefixime 100 mg Kapsul', quantity: 350, unit: 'Box (30 Kap)', price: 145000, discount: 0, subtotal: 50750000 },
      { id: 2, medicineId: 2, medicineName: 'Amoxicillin 500 mg Kapsul', quantity: 200, unit: 'Box (100 Kap)', price: 78000, discount: 0, subtotal: 15600000 }
    ],
    totalAmount: 66350000
  },
  {
    id: 12,
    invoiceNumber: 'FKT-KF-202608-0410',
    referenceNumber: 'PO-2026-0622',
    invoiceDate: '2026-08-15',
    dueDate: '2026-08-30',
    supplierId: 1,
    supplierName: 'PT Kimia Farma Trading & Distribution',
    supplierCity: 'Jakarta Pusat',
    supplierNpwp: '01.324.861.2-073.000',
    status: 'Belum Lunas',
    notes: 'Tertunda proses audit internal',
    paidAt: null,
    items: [
      { id: 1, medicineId: 9, medicineName: 'Ciprofloxacin 500 mg Tablet', quantity: 400, unit: 'Box (50 Tab)', price: 110000, discount: 0, subtotal: 44000000 },
      { id: 2, medicineId: 8, medicineName: 'Ibuprofen 400 mg Tablet', quantity: 300, unit: 'Box (100 Tab)', price: 58000, discount: 0, subtotal: 17400000 }
    ],
    totalAmount: 61400000
  },
  {
    id: 13,
    invoiceNumber: 'FKT-KLB-202608-0301',
    referenceNumber: 'PO-2026-0598',
    invoiceDate: '2026-08-10',
    dueDate: '2026-09-10',
    supplierId: 2,
    supplierName: 'PT Kalbe Farma Tbk',
    supplierCity: 'Cikarang',
    supplierNpwp: '01.328.712.1-413.000',
    status: 'Lunas',
    notes: 'Lunas',
    paidAt: '2026-08-14',
    items: [
      { id: 1, medicineId: 11, medicineName: 'Infus Ringer Laktat 500 ml', quantity: 350, unit: 'Karton (20 Botol)', price: 240000, discount: 0, subtotal: 84000000 },
      { id: 2, medicineId: 3, medicineName: 'Omeprazole 20 mg Kapsul', quantity: 180, unit: 'Box (30 Kap)', price: 92000, discount: 0, subtotal: 16560000 }
    ],
    totalAmount: 100560000
  },
  {
    id: 14,
    invoiceNumber: 'FKT-MBS-202608-0199',
    referenceNumber: 'PO-2026-0570',
    invoiceDate: '2026-08-05',
    dueDate: '2026-09-05',
    supplierId: 4,
    supplierName: 'PT Mensa Binasukses',
    supplierCity: 'Tangerang',
    supplierNpwp: '01.312.445.6-418.000',
    status: 'Lunas',
    notes: 'Pesanan reguler farmasi rawat jalan',
    paidAt: '2026-08-08',
    items: [
      { id: 1, medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 500, unit: 'Box (100 Tab)', price: 45000, discount: 0, subtotal: 22500000 },
      { id: 2, medicineId: 4, medicineName: 'Amlodipine 10 mg Tablet', quantity: 250, unit: 'Box (30 Tab)', price: 65000, discount: 0, subtotal: 16250000 }
    ],
    totalAmount: 38750000
  },
  {
    id: 15,
    invoiceNumber: 'FKT-AAM-202607-0940',
    referenceNumber: 'PO-2026-0512',
    invoiceDate: '2026-07-28',
    dueDate: '2026-08-28',
    supplierId: 5,
    supplierName: 'PT Anugrah Argon Medica',
    supplierCity: 'Bekasi',
    supplierNpwp: '01.298.113.4-323.000',
    status: 'Lunas',
    notes: 'Lunas via transfer',
    paidAt: '2026-08-02',
    items: [
      { id: 1, medicineId: 12, medicineName: 'Ceftriaxone 1 gr Injeksi', quantity: 500, unit: 'Vial', price: 85000, discount: 0, subtotal: 42500000 },
      { id: 2, medicineId: 10, medicineName: 'Ranitidine 150 mg Tablet', quantity: 200, unit: 'Box (100 Tab)', price: 62000, discount: 0, subtotal: 12400000 }
    ],
    totalAmount: 54900000
  }
];
