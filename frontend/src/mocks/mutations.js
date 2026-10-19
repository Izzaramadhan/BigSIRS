// Mock data for Logistik Farmasi - Mutasi Barang (Stock Mutation)
// Realistic mock fixtures for pharmacy stock transfer between storage units

export const mockWarehouses = [
  'Gudang Utama',
  'Gudang Internal',
  'Apotek Rawat Jalan',
  'Apotek Rawat Inap',
  'Apotek IGD'
];

export const mockMutationMedicines = [
  { id: 1, name: 'Paracetamol 500 mg Tablet', availableStock: 1200, unit: 'Unit' },
  { id: 2, name: 'Amoxicillin 500 mg Kapsul', availableStock: 850, unit: 'Unit' },
  { id: 3, name: 'Omeprazole 20 mg Kapsul', availableStock: 600, unit: 'Unit' },
  { id: 4, name: 'Amlodipine 10 mg Tablet', availableStock: 750, unit: 'Unit' },
  { id: 5, name: 'Metformin 500 mg Tablet', availableStock: 900, unit: 'Unit' },
  { id: 6, name: 'Cefixime 100 mg Kapsul', availableStock: 420, unit: 'Unit' },
  { id: 7, name: 'Ringer Laktat 500 ml', availableStock: 350, unit: 'Unit' },
  { id: 8, name: 'Dextrose 5% 500 ml', availableStock: 280, unit: 'Unit' },
  { id: 9, name: 'Morfin Injeksi 10 mg/ml', availableStock: 80, unit: 'Ampul' },
  { id: 10, name: 'Diazepam 5 mg/ml Injeksi', availableStock: 120, unit: 'Ampul' },
  { id: 11, name: 'Spuit 3cc Disposable', availableStock: 1500, unit: 'Unit' },
  { id: 12, name: 'Infus Set Dewasa', availableStock: 500, unit: 'Unit' },
  { id: 13, name: 'Kasa Steril 16x16', availableStock: 800, unit: 'Unit' },
  { id: 14, name: 'Cetirizine 10 mg Tablet', availableStock: 650, unit: 'Unit' },
  { id: 15, name: 'Ibuprofen 400 mg Tablet', availableStock: 500, unit: 'Unit' }
];

export const initialMutations = [
  {
    id: 1,
    mutationNumber: 'MUT-202609-0042',
    date: '2026-09-12 14:15',
    dateDisplay: '12 Sep 2026, 14:15 WIB',
    sourceWarehouse: 'Gudang Utama',
    targetWarehouse: 'Apotek Rawat Jalan',
    itemCount: 6,
    totalQuantity: 450,
    unit: 'Unit',
    notes: 'Restock harian poli rawat jalan shift sore',
    createdBy: 'Apt. Novita Anggraini',
    createdInitials: 'NA',
    items: [
      { id: 1, medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 150, unit: 'Unit' },
      { id: 2, medicineId: 2, medicineName: 'Amoxicillin 500 mg Kapsul', quantity: 100, unit: 'Unit' },
      { id: 3, medicineId: 3, medicineName: 'Omeprazole 20 mg Kapsul', quantity: 80, unit: 'Unit' },
      { id: 4, medicineId: 4, medicineName: 'Amlodipine 10 mg Tablet', quantity: 60, unit: 'Unit' },
      { id: 5, medicineId: 5, medicineName: 'Metformin 500 mg Tablet', quantity: 40, unit: 'Unit' },
      { id: 6, medicineId: 6, medicineName: 'Cefixime 100 mg Kapsul', quantity: 20, unit: 'Unit' }
    ]
  },
  {
    id: 2,
    mutationNumber: 'MUT-202609-0041',
    date: '2026-09-12 11:30',
    dateDisplay: '12 Sep 2026, 11:30 WIB',
    sourceWarehouse: 'Gudang Utama',
    targetWarehouse: 'Apotek IGD',
    itemCount: 12,
    totalQuantity: 820,
    unit: 'Unit',
    notes: 'Buffer darurat ampul analgesik & antikonvulsan',
    createdBy: 'Budi Santoso (Logistik)',
    createdInitials: 'BS',
    items: [
      { id: 1, medicineId: 9, medicineName: 'Morfin Injeksi 10 mg/ml', quantity: 30, unit: 'Ampul' },
      { id: 2, medicineId: 10, medicineName: 'Diazepam 5 mg/ml Injeksi', quantity: 40, unit: 'Ampul' },
      { id: 3, medicineId: 7, medicineName: 'Ringer Laktat 500 ml', quantity: 250, unit: 'Unit' },
      { id: 4, medicineId: 11, medicineName: 'Spuit 3cc Disposable', quantity: 500, unit: 'Unit' }
    ]
  },
  {
    id: 3,
    mutationNumber: 'MUT-202609-0040',
    date: '2026-09-11 16:45',
    dateDisplay: '11 Sep 2026, 16:45 WIB',
    sourceWarehouse: 'Gudang Utama',
    targetWarehouse: 'Apotek Rawat Inap',
    itemCount: 8,
    totalQuantity: 340,
    unit: 'Unit',
    notes: 'Drop infus RL, D5, dan antibiotik infus lantai 3',
    createdBy: 'Budi Santoso (Logistik)',
    createdInitials: 'BS',
    items: [
      { id: 1, medicineId: 7, medicineName: 'Ringer Laktat 500 ml', quantity: 180, unit: 'Unit' },
      { id: 2, medicineId: 8, medicineName: 'Dextrose 5% 500 ml', quantity: 120, unit: 'Unit' },
      { id: 3, medicineId: 6, medicineName: 'Cefixime 100 mg Kapsul', quantity: 40, unit: 'Unit' }
    ]
  },
  {
    id: 4,
    mutationNumber: 'MUT-202609-0039',
    date: '2026-09-11 09:20',
    dateDisplay: '11 Sep 2026, 09:20 WIB',
    sourceWarehouse: 'Gudang Internal',
    targetWarehouse: 'Gudang Utama',
    itemCount: 4,
    totalQuantity: 1200,
    unit: 'Unit',
    notes: 'Relokasi kemasan sediaan batch karantina selesai',
    createdBy: 'Hendra S.',
    createdInitials: 'HS',
    items: [
      { id: 1, medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 600, unit: 'Unit' },
      { id: 2, medicineId: 5, medicineName: 'Metformin 500 mg Tablet', quantity: 400, unit: 'Unit' },
      { id: 3, medicineId: 14, medicineName: 'Cetirizine 10 mg Tablet', quantity: 200, unit: 'Unit' }
    ]
  },
  {
    id: 5,
    mutationNumber: 'MUT-202609-0038',
    date: '2026-09-10 15:10',
    dateDisplay: '10 Sep 2026, 15:10 WIB',
    sourceWarehouse: 'Apotek Rawat Inap',
    targetWarehouse: 'Apotek IGD',
    itemCount: 2,
    totalQuantity: 50,
    unit: 'Ampul',
    notes: 'Pinjam darurat Morfin Inj & Diazepam antarunit',
    createdBy: 'Apt. Siti Rahma',
    createdInitials: 'SR',
    items: [
      { id: 1, medicineId: 9, medicineName: 'Morfin Injeksi 10 mg/ml', quantity: 20, unit: 'Ampul' },
      { id: 2, medicineId: 10, medicineName: 'Diazepam 5 mg/ml Injeksi', quantity: 30, unit: 'Ampul' }
    ]
  },
  {
    id: 6,
    mutationNumber: 'MUT-202609-0037',
    date: '2026-09-10 10:05',
    dateDisplay: '10 Sep 2026, 10:05 WIB',
    sourceWarehouse: 'Gudang Utama',
    targetWarehouse: 'Apotek Rawat Jalan',
    itemCount: 5,
    totalQuantity: 280,
    unit: 'Unit',
    notes: 'Tambahan sediaan antihipertensi dan sirup anak',
    createdBy: 'Apt. Novita Anggraini',
    createdInitials: 'NA',
    items: [
      { id: 1, medicineId: 4, medicineName: 'Amlodipine 10 mg Tablet', quantity: 150, unit: 'Unit' },
      { id: 2, medicineId: 15, medicineName: 'Ibuprofen 400 mg Tablet', quantity: 130, unit: 'Unit' }
    ]
  },
  {
    id: 7,
    mutationNumber: 'MUT-202609-0036',
    date: '2026-09-09 13:40',
    dateDisplay: '09 Sep 2026, 13:40 WIB',
    sourceWarehouse: 'Gudang Utama',
    targetWarehouse: 'Apotek Rawat Inap',
    itemCount: 10,
    totalQuantity: 620,
    unit: 'Unit',
    notes: 'Distribusi spuit, infus set, dan kassa steril',
    createdBy: 'Budi Santoso (Logistik)',
    createdInitials: 'BS',
    items: [
      { id: 1, medicineId: 11, medicineName: 'Spuit 3cc Disposable', quantity: 300, unit: 'Unit' },
      { id: 2, medicineId: 12, medicineName: 'Infus Set Dewasa', quantity: 120, unit: 'Unit' },
      { id: 3, medicineId: 13, medicineName: 'Kasa Steril 16x16', quantity: 200, unit: 'Unit' }
    ]
  },
  {
    id: 8,
    mutationNumber: 'MUT-202609-0035',
    date: '2026-09-08 16:00',
    dateDisplay: '08 Sep 2026, 16:00 WIB',
    sourceWarehouse: 'Apotek Rawat Jalan',
    targetWarehouse: 'Gudang Utama',
    itemCount: 3,
    totalQuantity: 60,
    unit: 'Unit',
    notes: 'Retur kelebihan stok obat oral ED panjang',
    createdBy: 'Apt. Novita Anggraini',
    createdInitials: 'NA',
    items: [
      { id: 1, medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 30, unit: 'Unit' },
      { id: 2, medicineId: 2, medicineName: 'Amoxicillin 500 mg Kapsul', quantity: 20, unit: 'Unit' },
      { id: 3, medicineId: 3, medicineName: 'Omeprazole 20 mg Kapsul', quantity: 10, unit: 'Unit' }
    ]
  },
  {
    id: 9,
    mutationNumber: 'MUT-202609-0034',
    date: '2026-09-07 11:15',
    dateDisplay: '07 Sep 2026, 11:15 WIB',
    sourceWarehouse: 'Gudang Utama',
    targetWarehouse: 'Apotek IGD',
    itemCount: 4,
    totalQuantity: 210,
    unit: 'Unit',
    notes: 'Permintaan cito analgesik dan antipiretik',
    createdBy: 'Budi Santoso (Logistik)',
    createdInitials: 'BS',
    items: [
      { id: 1, medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 100, unit: 'Unit' },
      { id: 2, medicineId: 15, medicineName: 'Ibuprofen 400 mg Tablet', quantity: 110, unit: 'Unit' }
    ]
  },
  {
    id: 10,
    mutationNumber: 'MUT-202609-0033',
    date: '2026-09-05 14:00',
    dateDisplay: '05 Sep 2026, 14:00 WIB',
    sourceWarehouse: 'Gudang Internal',
    targetWarehouse: 'Gudang Utama',
    itemCount: 5,
    totalQuantity: 750,
    unit: 'Unit',
    notes: 'Transfer masuk dari distributor karantina logistik',
    createdBy: 'Hendra S.',
    createdInitials: 'HS',
    items: [
      { id: 1, medicineId: 2, medicineName: 'Amoxicillin 500 mg Kapsul', quantity: 300, unit: 'Unit' },
      { id: 2, medicineId: 4, medicineName: 'Amlodipine 10 mg Tablet', quantity: 250, unit: 'Unit' },
      { id: 3, medicineId: 5, medicineName: 'Metformin 500 mg Tablet', quantity: 200, unit: 'Unit' }
    ]
  }
];
