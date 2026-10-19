// Mock data for Logistik Farmasi - Penjualan Bebas (OTC / Cashier POS)
// Realistic mock fixtures for pharmacy over-the-counter sales, cashiers, payments, and receipts

export const mockPosLocations = [
  'Semua Lokasi',
  'Farmasi Pusat (Kasir 1)',
  'Farmasi Pusat (Kasir 2)',
  'Apotek Rawat Jalan'
];

export const mockPaymentMethods = [
  'Semua Metode',
  'Tunai',
  'QRIS',
  'Debit BCA',
  'Debit Mandiri'
];

export const mockTransactionStatuses = [
  'Semua Status',
  'Lunas',
  'Draft',
  'Dibatalkan'
];

export const mockOtcMedicines = [
  { id: 1, name: 'Paracetamol 500 mg Tablet', category: 'Obat Bebas', price: 15000, unit: 'Strip', availableStock: 450 },
  { id: 2, name: 'Vitamin C 500 mg IPI', category: 'Suplemen', price: 10000, unit: 'Strip', availableStock: 600 },
  { id: 3, name: 'Mylanta Cair 150 ml', category: 'Obat Bebas', price: 48000, unit: 'Botol', availableStock: 120 },
  { id: 4, name: 'Panadol Extra Merah', category: 'Obat Bebas Terbatas', price: 18500, unit: 'Strip', availableStock: 350 },
  { id: 5, name: 'Betadine Antiseptik 60 ml', category: 'BMHP / P3K', price: 95000, unit: 'Botol', availableStock: 80 },
  { id: 6, name: 'Hansaplast Plester Kain (Isi 10)', category: 'BMHP / P3K', price: 8500, unit: 'Kotak', availableStock: 500 },
  { id: 7, name: 'Tolak Angin Cair Herbal', category: 'Jamu / Herbal', price: 5000, unit: 'Sachet', availableStock: 800 },
  { id: 8, name: 'Minyak Kayu Putih Cap Lang 120 ml', category: 'Obat Luar', price: 42000, unit: 'Botol', availableStock: 140 },
  { id: 9, name: 'Sanmol Sirup Anak 60 ml', category: 'Obat Bebas', price: 26000, unit: 'Botol', availableStock: 95 },
  { id: 10, name: 'Enervon-C Multivitamin (Isi 30)', category: 'Suplemen', price: 45000, unit: 'Botol', availableStock: 150 }
];

export const initialPosSales = [
  {
    id: 1,
    transactionNumber: 'TR-OTC-20260915-0042',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '11:24 WIB',
    dateTime: '2026-09-15 11:24',
    customerName: 'Bpk. Rahmat Hidayat',
    customerType: 'Umum',
    itemCount: 3,
    itemCountText: '3 Jenis',
    totalQtyText: '4 Strip, 1 Btl',
    totalAmount: 145000,
    paymentMethod: 'QRIS',
    paymentMethodDisplay: 'QRIS',
    status: 'Lunas',
    cashier: 'Rina Marlina (Kasir 1)',
    location: 'Farmasi Pusat (Kasir 1)',
    paidAmount: 145000,
    changeAmount: 0,
    voidReason: '',
    items: [
      { id: 1, name: 'Paracetamol 500 mg Tablet', price: 15000, qty: 2, unit: 'Strip', subtotal: 30000 },
      { id: 2, name: 'Vitamin C 500 mg IPI', price: 10000, qty: 2, unit: 'Strip', subtotal: 20000 },
      { id: 3, name: 'Betadine Antiseptik 60 ml', price: 95000, qty: 1, unit: 'Botol', subtotal: 95000 }
    ]
  },
  {
    id: 2,
    transactionNumber: 'TR-OTC-20260915-0041',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '10:50 WIB',
    dateTime: '2026-09-15 10:50',
    customerName: 'Ibu Siti Nurhaliza',
    customerType: 'Umum',
    itemCount: 1,
    itemCountText: '1 Jenis',
    totalQtyText: '1 Botol',
    totalAmount: 68500,
    paymentMethod: 'Tunai',
    paymentMethodDisplay: 'Tunai (Cash)',
    status: 'Dibatalkan',
    cashier: 'Rina Marlina (Kasir 1)',
    location: 'Farmasi Pusat (Kasir 1)',
    paidAmount: 100000,
    changeAmount: 31500,
    voidReason: 'Salah input nominal pembayaran oleh kasir, transaksi dibatalkan dan dibuat nota baru.',
    items: [
      { id: 1, name: 'Mylanta Cair 150 ml', price: 48000, qty: 1, unit: 'Botol', subtotal: 48000 },
      { id: 2, name: 'Panadol Extra Merah', price: 20500, qty: 1, unit: 'Strip', subtotal: 20500 }
    ]
  },
  {
    id: 3,
    transactionNumber: 'TR-OTC-20260915-0040',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '10:45 WIB',
    dateTime: '2026-09-15 10:45',
    customerName: 'Walk-in Customer',
    customerType: 'Umum',
    itemCount: 5,
    itemCountText: '5 Jenis',
    totalQtyText: '8 Kotak Sediaan',
    totalAmount: 520000,
    paymentMethod: 'Debit BCA',
    paymentMethodDisplay: 'Debit BCA',
    status: 'Lunas',
    cashier: 'Apt. Budi Santoso',
    location: 'Farmasi Pusat (Kasir 2)',
    paidAmount: 520000,
    changeAmount: 0,
    voidReason: '',
    items: [
      { id: 1, name: 'Enervon-C Multivitamin (Isi 30)', price: 45000, qty: 4, unit: 'Botol', subtotal: 180000 },
      { id: 2, name: 'Betadine Antiseptik 60 ml', price: 95000, qty: 2, unit: 'Botol', subtotal: 190000 },
      { id: 3, name: 'Hansaplast Plester Kain', price: 8500, qty: 10, unit: 'Kotak', subtotal: 85000 },
      { id: 4, name: 'Panadol Extra Merah', price: 18500, qty: 2, unit: 'Strip', subtotal: 37000 },
      { id: 5, name: 'Tolak Angin Cair Herbal', price: 5000, qty: 5, unit: 'Sachet', subtotal: 28000 }
    ]
  },
  {
    id: 4,
    transactionNumber: 'TR-OTC-20260915-0039',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '10:15 WIB',
    dateTime: '2026-09-15 10:15',
    customerName: 'dr. Hendra (Pribadi)',
    customerType: 'Staf RS',
    itemCount: 2,
    itemCountText: '2 Jenis',
    totalQtyText: '2 Pcs Suplemen',
    totalAmount: 35000,
    paymentMethod: 'Belum Dipilih',
    paymentMethodDisplay: 'Belum Dipilih',
    status: 'Draft',
    cashier: 'Dimas Prasetyo',
    location: 'Apotek Rawat Jalan',
    paidAmount: 0,
    changeAmount: 0,
    voidReason: '',
    items: [
      { id: 1, name: 'Vitamin C 500 mg IPI', price: 10000, qty: 2, unit: 'Strip', subtotal: 20000 },
      { id: 2, name: 'Paracetamol 500 mg Tablet', price: 15000, qty: 1, unit: 'Strip', subtotal: 15000 }
    ]
  },
  {
    id: 5,
    transactionNumber: 'TR-OTC-20260915-0038',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '09:50 WIB',
    dateTime: '2026-09-15 09:50',
    customerName: 'Ny. Maria Ulfa',
    customerType: 'Umum',
    itemCount: 4,
    itemCountText: '4 Jenis',
    totalQtyText: '6 Strip',
    totalAmount: 210000,
    paymentMethod: 'Tunai',
    paymentMethodDisplay: 'Tunai (Cash)',
    status: 'Lunas',
    cashier: 'Dimas Prasetyo',
    location: 'Apotek Rawat Jalan',
    paidAmount: 250000,
    changeAmount: 40000,
    voidReason: '',
    items: [
      { id: 1, name: 'Minyak Kayu Putih Cap Lang 120 ml', price: 42000, qty: 2, unit: 'Botol', subtotal: 84000 },
      { id: 2, name: 'Panadol Extra Merah', price: 18500, qty: 4, unit: 'Strip', subtotal: 74000 },
      { id: 3, name: 'Hansaplast Plester Kain', price: 8500, qty: 4, unit: 'Kotak', subtotal: 34000 },
      { id: 4, name: 'Tolak Angin Cair Herbal', price: 5000, qty: 3, unit: 'Sachet', subtotal: 18000 }
    ]
  },
  {
    id: 6,
    transactionNumber: 'TR-OTC-20260915-0037',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '09:12 WIB',
    dateTime: '2026-09-15 09:12',
    customerName: 'Ahmad Fauzi',
    customerType: 'Umum',
    itemCount: 1,
    itemCountText: '1 Jenis',
    totalQtyText: '2 Botol Sirup',
    totalAmount: 64000,
    paymentMethod: 'Debit Mandiri',
    paymentMethodDisplay: 'Debit Mandiri',
    status: 'Lunas',
    cashier: 'Rina Marlina (Kasir 1)',
    location: 'Farmasi Pusat (Kasir 1)',
    paidAmount: 64000,
    changeAmount: 0,
    voidReason: '',
    items: [
      { id: 1, name: 'Sanmol Sirup Anak 60 ml', price: 26000, qty: 2, unit: 'Botol', subtotal: 52000 },
      { id: 2, name: 'Tolak Angin Cair Herbal', price: 6000, qty: 2, unit: 'Sachet', subtotal: 12000 }
    ]
  },
  {
    id: 7,
    transactionNumber: 'TR-OTC-20260915-0036',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '08:40 WIB',
    dateTime: '2026-09-15 08:40',
    customerName: 'Hj. Endang Rahayu',
    customerType: 'Pasien',
    itemCount: 2,
    itemCountText: '2 Jenis',
    totalQtyText: '2 Botol',
    totalAmount: 73500,
    paymentMethod: 'QRIS',
    paymentMethodDisplay: 'QRIS',
    status: 'Lunas',
    cashier: 'Apt. Budi Santoso',
    location: 'Farmasi Pusat (Kasir 2)',
    paidAmount: 73500,
    changeAmount: 0,
    voidReason: '',
    items: [
      { id: 1, name: 'Mylanta Cair 150 ml', price: 48000, qty: 1, unit: 'Botol', subtotal: 48000 },
      { id: 2, name: 'Sanmol Sirup Anak 60 ml', price: 25500, qty: 1, unit: 'Botol', subtotal: 25500 }
    ]
  },
  {
    id: 8,
    transactionNumber: 'TR-OTC-20260915-0035',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '08:15 WIB',
    dateTime: '2026-09-15 08:15',
    customerName: 'Yusuf Maulana',
    customerType: 'Umum',
    itemCount: 3,
    itemCountText: '3 Jenis',
    totalQtyText: '5 Strip',
    totalAmount: 115000,
    paymentMethod: 'Tunai',
    paymentMethodDisplay: 'Tunai (Cash)',
    status: 'Lunas',
    cashier: 'Rina Marlina (Kasir 1)',
    location: 'Farmasi Pusat (Kasir 1)',
    paidAmount: 120000,
    changeAmount: 5000,
    voidReason: '',
    items: [
      { id: 1, name: 'Paracetamol 500 mg Tablet', price: 15000, qty: 3, unit: 'Strip', subtotal: 45000 },
      { id: 2, name: 'Vitamin C 500 mg IPI', price: 10000, qty: 3, unit: 'Strip', subtotal: 30000 },
      { id: 3, name: 'Minyak Kayu Putih Cap Lang', price: 40000, qty: 1, unit: 'Botol', subtotal: 40000 }
    ]
  },
  {
    id: 9,
    transactionNumber: 'TR-OTC-20260915-0034',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '07:55 WIB',
    dateTime: '2026-09-15 07:55',
    customerName: 'Sdr. Kevin Wijaya',
    customerType: 'Umum',
    itemCount: 1,
    itemCountText: '1 Jenis',
    totalQtyText: '1 Kotak',
    totalAmount: 73500,
    paymentMethod: 'Tunai',
    paymentMethodDisplay: 'Tunai (Cash)',
    status: 'Dibatalkan',
    cashier: 'Rina Marlina (Kasir 1)',
    location: 'Farmasi Pusat (Kasir 1)',
    paidAmount: 73500,
    changeAmount: 0,
    voidReason: 'Pembeli membatalkan pembelian karena ingin mengganti obat sirup.',
    items: [
      { id: 1, name: 'Betadine Antiseptik 60 ml', price: 73500, qty: 1, unit: 'Botol', subtotal: 73500 }
    ]
  },
  {
    id: 10,
    transactionNumber: 'TR-OTC-20260915-0033',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '07:30 WIB',
    dateTime: '2026-09-15 07:30',
    customerName: 'Ns. Anisa Fitri',
    customerType: 'Staf RS',
    itemCount: 2,
    itemCountText: '2 Jenis',
    totalQtyText: '3 Pcs',
    totalAmount: 55000,
    paymentMethod: 'QRIS',
    paymentMethodDisplay: 'QRIS',
    status: 'Lunas',
    cashier: 'Dimas Prasetyo',
    location: 'Apotek Rawat Jalan',
    paidAmount: 55000,
    changeAmount: 0,
    voidReason: '',
    items: [
      { id: 1, name: 'Vitamin C 500 mg IPI', price: 10000, qty: 2, unit: 'Strip', subtotal: 20000 },
      { id: 2, name: 'Mylanta Cair 150 ml', price: 35000, qty: 1, unit: 'Botol', subtotal: 35000 }
    ]
  },
  {
    id: 11,
    transactionNumber: 'TR-OTC-20260914-0032',
    dateDisplay: '14 Sep 2026',
    timeDisplay: '19:40 WIB',
    dateTime: '2026-09-14 19:40',
    customerName: 'Bpk. Gunawan',
    customerType: 'Umum',
    itemCount: 2,
    itemCountText: '2 Jenis',
    totalQtyText: '3 Strip',
    totalAmount: 65000,
    paymentMethod: 'Tunai',
    paymentMethodDisplay: 'Tunai (Cash)',
    status: 'Lunas',
    cashier: 'Dimas Prasetyo',
    location: 'Farmasi Pusat (Kasir 1)',
    paidAmount: 70000,
    changeAmount: 5000,
    voidReason: '',
    items: [
      { id: 1, name: 'Paracetamol 500 mg Tablet', price: 15000, qty: 2, unit: 'Strip', subtotal: 30000 },
      { id: 2, name: 'Enervon-C Multivitamin', price: 35000, qty: 1, unit: 'Botol', subtotal: 35000 }
    ]
  },
  {
    id: 12,
    transactionNumber: 'TR-OTC-20260914-0031',
    dateDisplay: '14 Sep 2026',
    timeDisplay: '18:15 WIB',
    dateTime: '2026-09-14 18:15',
    customerName: 'Walk-in Pasien Poli',
    customerType: 'Pasien',
    itemCount: 3,
    itemCountText: '3 Jenis',
    totalQtyText: '4 Unit Sediaan',
    totalAmount: 185000,
    paymentMethod: 'Debit BCA',
    paymentMethodDisplay: 'Debit BCA',
    status: 'Lunas',
    cashier: 'Apt. Budi Santoso',
    location: 'Farmasi Pusat (Kasir 2)',
    paidAmount: 185000,
    changeAmount: 0,
    voidReason: '',
    items: [
      { id: 1, name: 'Minyak Kayu Putih Cap Lang', price: 42000, qty: 1, unit: 'Botol', subtotal: 42000 },
      { id: 2, name: 'Betadine Antiseptik 60 ml', price: 95000, qty: 1, unit: 'Botol', subtotal: 95000 },
      { id: 3, name: 'Hansaplast Plester Kain', price: 8500, qty: 2, unit: 'Kotak', subtotal: 17000 }
    ]
  }
];
