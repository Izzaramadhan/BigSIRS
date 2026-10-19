// Mock data for Logistik Farmasi - Pengeluaran Barang (Goods Issue / Non-Sale Outflow)
// Realistic mock fixtures for pharmacy non-sale goods issue, BAP disposal, and internal transfers

export const mockIssueWarehouses = [
  'Gudang Utama Farmasi',
  'Apotek Rawat Jalan',
  'Gudang Logistik Sentral',
  'Depo Rawat Inap Lt. 3',
  'Apotek IGD 24 Jam'
];

export const mockIssueOfficers = [
  'Apt. Budi Santoso, S.Farm',
  'Rina Marlina, A.Md.Farm',
  'Dimas Prasetyo, S.Farm',
  'Novita Anggraini'
];

export const mockIssuePurposes = [
  'Kebutuhan Internal',
  'Barang Rusak',
  'Kegiatan Sosial',
  'Kedaluwarsa',
  'Buffer Ruang Isolasi',
  'Penelitian/Uji Mutu'
];

export const mockIssueMedicinesCatalog = [
  { id: 1, name: 'Paracetamol 500 mg Tablet', unit: 'Tablet', defaultBatch: 'PCT-202604', expDate: '2027-04-15', availableStock: 1200 },
  { id: 2, name: 'Amoxicillin 500 mg Kapsul', unit: 'Kapsul', defaultBatch: 'AMX-202511', expDate: '2026-11-20', availableStock: 850 },
  { id: 3, name: 'Omeprazole 20 mg Kapsul', unit: 'Kapsul', defaultBatch: 'OMP-202608', expDate: '2027-08-10', availableStock: 600 },
  { id: 4, name: 'Ringer Laktat 500 ml', unit: 'Kolf', defaultBatch: 'RL-202601', expDate: '2027-01-30', availableStock: 350 },
  { id: 5, name: 'Dextrose 5% 500 ml', unit: 'Kolf', defaultBatch: 'D5-202512', expDate: '2026-12-15', availableStock: 280 },
  { id: 6, name: 'Morfin Injeksi 10 mg/ml', unit: 'Ampul', defaultBatch: 'MRF-202603', expDate: '2027-03-25', availableStock: 80 },
  { id: 7, name: 'Diazepam 5 mg/ml Injeksi', unit: 'Ampul', defaultBatch: 'DZP-202605', expDate: '2027-05-18', availableStock: 120 },
  { id: 8, name: 'Spuit 3cc Disposable Terumo', unit: 'Pcs', defaultBatch: 'SPT-202701', expDate: '2028-01-01', availableStock: 1500 },
  { id: 9, name: 'Infus Set Dewasa Makro', unit: 'Pcs', defaultBatch: 'IFS-202609', expDate: '2028-09-30', availableStock: 500 },
  { id: 10, name: 'Kasa Steril 16x16 Husada', unit: 'Pcs', defaultBatch: 'KSA-202602', expDate: '2028-02-28', availableStock: 800 },
  { id: 11, name: 'Vitamin C 500 mg IPI', unit: 'Strip', defaultBatch: 'VTC-202607', expDate: '2027-07-22', availableStock: 950 },
  { id: 12, name: 'Cefotaxime Injeksi 1 g', unit: 'Vial', defaultBatch: 'CFX-202509', expDate: '2026-09-10', availableStock: 160 }
];

export const initialGoodsIssues = [
  {
    id: 1,
    issueNumber: 'OUT-202609-0028',
    referenceNumber: 'Ref: SBPB-098/FAR/26',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '10:15 WIB',
    dateTime: '2026-09-15 10:15',
    warehouse: 'Gudang Utama Farmasi (GOF-01)',
    locationDetail: 'Rak A-12 (Injeksi & Parenteral)',
    officerName: 'Apt. Budi Santoso, S.Farm',
    officerRole: 'SIP: 19820412/SIPA/2021',
    purpose: 'Kebutuhan Internal (IGD & ICU)',
    purposeKey: 'Kebutuhan Internal',
    itemCount: 4,
    totalQuantity: 85,
    quantityUnit: 'Unit',
    quantitySubtitle: 'Ampul & Infus',
    status: 'Selesai',
    validationNote: '',
    notes: 'Pengeluaran sediaan darurat untuk kebutuhan penanganan intensif IGD dan ICU lantai 1.',
    items: [
      { id: 1, name: 'Morfin Injeksi 10 mg/ml', batchNumber: 'MRF-202603', expDate: '2027-03-25', quantity: 20, unit: 'Ampul', availableStock: 80, notes: 'Ampul reguler' },
      { id: 2, name: 'Diazepam 5 mg/ml Injeksi', batchNumber: 'DZP-202605', expDate: '2027-05-18', quantity: 25, unit: 'Ampul', availableStock: 120, notes: 'Stok darurat' },
      { id: 3, name: 'Ringer Laktat 500 ml', batchNumber: 'RL-202601', expDate: '2027-01-30', quantity: 20, unit: 'Kolf', availableStock: 350, notes: 'Cairan infus' },
      { id: 4, name: 'Dextrose 5% 500 ml', batchNumber: 'D5-202512', expDate: '2026-12-15', quantity: 20, unit: 'Kolf', availableStock: 280, notes: 'Cairan infus' }
    ]
  },
  {
    id: 2,
    issueNumber: 'OUT-202609-0027',
    referenceNumber: 'BAP: #BAP-2026/09/04',
    dateDisplay: '15 Sep 2026',
    timeDisplay: '08:45 WIB',
    dateTime: '2026-09-15 08:45',
    warehouse: 'Gudang Utama Farmasi (GOF-01)',
    locationDetail: 'Area Karantina Sementara',
    officerName: 'Rina Marlina, A.Md.Farm',
    officerRole: 'NIP: 199308142019032038',
    purpose: 'Barang Rusak (Pecah Transit)',
    purposeKey: 'Barang Rusak',
    itemCount: 2,
    totalQuantity: 14,
    quantityUnit: 'Unit',
    quantitySubtitle: 'Pecah Kemasan',
    status: 'Selesai',
    validationNote: '',
    notes: 'Berita acara pemusnahan botol infus dan ampul yang pecah saat proses unboxing pengiriman distributor.',
    items: [
      { id: 1, name: 'Ringer Laktat 500 ml', batchNumber: 'RL-202601', expDate: '2027-01-30', quantity: 10, unit: 'Kolf', availableStock: 330, notes: 'Kemasan bocor' },
      { id: 2, name: 'Diazepam 5 mg/ml Injeksi', batchNumber: 'DZP-202605', expDate: '2027-05-18', quantity: 4, unit: 'Ampul', availableStock: 95, notes: 'Ampul retak' }
    ]
  },
  {
    id: 3,
    issueNumber: 'OUT-202609-0026',
    referenceNumber: 'Disposisi: DIR-MED/IX/02',
    dateDisplay: '14 Sep 2026',
    timeDisplay: '14:20 WIB',
    dateTime: '2026-09-14 14:20',
    warehouse: 'Apotek Rawat Jalan',
    locationDetail: 'Rak C-05 (Analgetik & Vitamin)',
    officerName: 'Dimas Prasetyo, S.Farm',
    officerRole: 'SIP: 19890111/SIPA/2020',
    purpose: 'Kegiatan Sosial (Baksos RS)',
    purposeKey: 'Kegiatan Sosial',
    itemCount: 6,
    totalQuantity: 340,
    quantityUnit: 'Strip',
    quantitySubtitle: 'Vitamin & Analgetik',
    status: 'Selesai',
    validationNote: '',
    notes: 'Alokasi obat dan suplemen untuk bakti sosial HUT Rumah Sakit di Desa Binaan binaan PKRS.',
    items: [
      { id: 1, name: 'Paracetamol 500 mg Tablet', batchNumber: 'PCT-202604', expDate: '2027-04-15', quantity: 120, unit: 'Strip', availableStock: 1000, notes: 'Baksos umum' },
      { id: 2, name: 'Vitamin C 500 mg IPI', batchNumber: 'VTC-202607', expDate: '2027-07-22', quantity: 100, unit: 'Strip', availableStock: 800, notes: 'Suplemen lansia' },
      { id: 3, name: 'Amoxicillin 500 mg Kapsul', batchNumber: 'AMX-202511', expDate: '2026-11-20', quantity: 60, unit: 'Strip', availableStock: 700, notes: 'Resep dokter baksos' },
      { id: 4, name: 'Kasa Steril 16x16 Husada', batchNumber: 'KSA-202602', expDate: '2028-02-28', quantity: 60, unit: 'Pcs', availableStock: 600, notes: 'Rawat luka gratis' }
    ]
  },
  {
    id: 4,
    issueNumber: 'OUT-202609-0025',
    referenceNumber: 'Karantina ED Q3-2026',
    dateDisplay: '14 Sep 2026',
    timeDisplay: '11:00 WIB',
    dateTime: '2026-09-14 11:00',
    warehouse: 'Gudang Logistik Sentral',
    locationDetail: 'Gudang Isolasi Pemusnahan',
    officerName: 'Apt. Budi Santoso, S.Farm',
    officerRole: 'SIP: 19820412/SIPA/2021',
    purpose: 'Kedaluwarsa (Pemusnahan)',
    purposeKey: 'Kedaluwarsa',
    itemCount: 5,
    totalQuantity: 65,
    quantityUnit: 'Box',
    quantitySubtitle: 'Expired Terverifikasi',
    status: 'Selesai',
    validationNote: '',
    notes: 'Penarikan sediaan kedaluwarsa periode Triwulan III 2026 untuk proses pemusnahan resmi pihak ketiga BPOM.',
    items: [
      { id: 1, name: 'Cefotaxime Injeksi 1 g', batchNumber: 'CFX-202509', expDate: '2026-08-31', quantity: 25, unit: 'Box', availableStock: 0, notes: 'Kadaluwarsa Agustus 2026' },
      { id: 2, name: 'Amoxicillin 500 mg Kapsul', batchNumber: 'AMX-202511', expDate: '2026-08-15', quantity: 40, unit: 'Box', availableStock: 0, notes: 'Kadaluwarsa Agustus 2026' }
    ]
  },
  {
    id: 5,
    issueNumber: 'OUT-202609-0024',
    referenceNumber: 'Draft Permintaan Isolasi',
    dateDisplay: '14 Sep 2026',
    timeDisplay: '09:10 WIB',
    dateTime: '2026-09-14 09:10',
    warehouse: 'Depo Rawat Inap Lt. 3',
    locationDetail: 'Lemari Narkotika & Psikotropika',
    officerName: 'Novita Anggraini',
    officerRole: 'NIP: 199505202021022011',
    purpose: 'Buffer Ruang Isolasi (Internal)',
    purposeKey: 'Buffer Ruang Isolasi',
    itemCount: 3,
    totalQuantity: 45,
    quantityUnit: 'Unit',
    quantitySubtitle: 'Kebutuhan Shift Malam',
    status: 'Draft',
    validationNote: '',
    notes: 'Draft permohonan alokasi buffer sediaan ruang isolasi infeksius lantai 3 shift malam.',
    items: [
      { id: 1, name: 'Omeprazole 20 mg Kapsul', batchNumber: 'OMP-202608', expDate: '2027-08-10', quantity: 15, unit: 'Kapsul', availableStock: 400, notes: 'Buffer rutin' },
      { id: 2, name: 'Paracetamol 500 mg Tablet', batchNumber: 'PCT-202604', expDate: '2027-04-15', quantity: 20, unit: 'Tablet', availableStock: 800, notes: 'Demam isolasi' },
      { id: 3, name: 'Spuit 3cc Disposable Terumo', batchNumber: 'SPT-202701', expDate: '2028-01-01', quantity: 10, unit: 'Pcs', availableStock: 1200, notes: 'Alat suntik' }
    ]
  },
  {
    id: 6,
    issueNumber: 'OUT-202609-0023',
    referenceNumber: 'Salah input alokasi gudang',
    dateDisplay: '12 Sep 2026',
    timeDisplay: '16:00 WIB',
    dateTime: '2026-09-12 16:00',
    warehouse: 'Apotek IGD 24 Jam',
    locationDetail: 'Depo Emergency',
    officerName: 'Rina Marlina, A.Md.Farm',
    officerRole: 'NIP: 199308142019032038',
    purpose: 'Penelitian/Uji Mutu',
    purposeKey: 'Penelitian/Uji Mutu',
    itemCount: 1,
    totalQuantity: 16,
    quantityUnit: 'Vial',
    quantitySubtitle: 'Stok tidak dipotong',
    status: 'Dibatalkan',
    validationNote: 'Salah input alokasi gudang',
    notes: 'Dokumen dibatalkan karena terjadi kesalahan pemilihan depo asal saat input permohonan sampel uji laboratorium.',
    items: [
      { id: 1, name: 'Cefotaxime Injeksi 1 g', batchNumber: 'CFX-202509', expDate: '2026-09-10', quantity: 16, unit: 'Vial', availableStock: 160, notes: 'Sampel uji sterilitas' }
    ]
  },
  {
    id: 7,
    issueNumber: 'OUT-202609-0022',
    referenceNumber: 'Ref: SBPB-095/FAR/26',
    dateDisplay: '11 Sep 2026',
    timeDisplay: '11:30 WIB',
    dateTime: '2026-09-11 11:30',
    warehouse: 'Gudang Utama Farmasi (GOF-01)',
    locationDetail: 'Zona Infus Lantai 1',
    officerName: 'Apt. Budi Santoso, S.Farm',
    officerRole: 'SIP: 19820412/SIPA/2021',
    purpose: 'Kebutuhan Internal (IGD & ICU)',
    purposeKey: 'Kebutuhan Internal',
    itemCount: 2,
    totalQuantity: 120,
    quantityUnit: 'Kolf',
    quantitySubtitle: 'Cairan Resusitasi',
    status: 'Selesai',
    validationNote: '',
    notes: 'Pemenuhan buffer cairan darurat akhir pekan ruang rawat darurat.',
    items: [
      { id: 1, name: 'Ringer Laktat 500 ml', batchNumber: 'RL-202601', expDate: '2027-01-30', quantity: 80, unit: 'Kolf', availableStock: 300, notes: '' },
      { id: 2, name: 'Dextrose 5% 500 ml', batchNumber: 'D5-202512', expDate: '2026-12-15', quantity: 40, unit: 'Kolf', availableStock: 250, notes: '' }
    ]
  },
  {
    id: 8,
    issueNumber: 'OUT-202609-0021',
    referenceNumber: 'BAP: #BAP-2026/09/02',
    dateDisplay: '10 Sep 2026',
    timeDisplay: '15:15 WIB',
    dateTime: '2026-09-10 15:15',
    warehouse: 'Apotek Rawat Jalan',
    locationDetail: 'Depo 2 Poliklinik',
    officerName: 'Dimas Prasetyo, S.Farm',
    officerRole: 'SIP: 19890111/SIPA/2020',
    purpose: 'Barang Rusak (Pecah Transit)',
    purposeKey: 'Barang Rusak',
    itemCount: 1,
    totalQuantity: 12,
    quantityUnit: 'Botol',
    quantitySubtitle: 'Sirup Pecah',
    status: 'Selesai',
    validationNote: '',
    notes: 'Pecah botol sirup akibat guncangan rak trolley obat.',
    items: [
      { id: 1, name: 'Paracetamol Sirup 120 mg/5ml', batchNumber: 'PCT-S2026', expDate: '2027-05-10', quantity: 12, unit: 'Botol', availableStock: 80, notes: 'Botol pecah' }
    ]
  },
  {
    id: 9,
    issueNumber: 'OUT-202609-0020',
    referenceNumber: 'Disposisi: DIR-UM/IX/01',
    dateDisplay: '09 Sep 2026',
    timeDisplay: '09:40 WIB',
    dateTime: '2026-09-09 09:40',
    warehouse: 'Gudang Logistik Sentral',
    locationDetail: 'Area BHP & Kasa',
    officerName: 'Novita Anggraini',
    officerRole: 'NIP: 199505202021022011',
    purpose: 'Kegiatan Sosial (Baksos RS)',
    purposeKey: 'Kegiatan Sosial',
    itemCount: 3,
    totalQuantity: 250,
    quantityUnit: 'Unit',
    quantitySubtitle: 'BHP Khitanan Massal',
    status: 'Selesai',
    validationNote: '',
    notes: 'BHP disposable untuk program khitanan massal RS.',
    items: [
      { id: 1, name: 'Kasa Steril 16x16 Husada', batchNumber: 'KSA-202602', expDate: '2028-02-28', quantity: 150, unit: 'Pcs', availableStock: 500, notes: '' },
      { id: 2, name: 'Spuit 3cc Disposable Terumo', batchNumber: 'SPT-202701', expDate: '2028-01-01', quantity: 100, unit: 'Pcs', availableStock: 900, notes: '' }
    ]
  },
  {
    id: 10,
    issueNumber: 'OUT-202609-0019',
    referenceNumber: 'Sampel Laboratorium BPOM',
    dateDisplay: '08 Sep 2026',
    timeDisplay: '13:00 WIB',
    dateTime: '2026-09-08 13:00',
    warehouse: 'Gudang Utama Farmasi (GOF-01)',
    locationDetail: 'Rak Sampel Uji Mutu',
    officerName: 'Apt. Budi Santoso, S.Farm',
    officerRole: 'SIP: 19820412/SIPA/2021',
    purpose: 'Penelitian/Uji Mutu',
    purposeKey: 'Penelitian/Uji Mutu',
    itemCount: 2,
    totalQuantity: 20,
    quantityUnit: 'Ampul',
    quantitySubtitle: 'Uji Potensi & Sterilitas',
    status: 'Selesai',
    validationNote: '',
    notes: 'Pengambilan sampel acak berkala untuk uji mutu laboratorium eksternal.',
    items: [
      { id: 1, name: 'Cefotaxime Injeksi 1 g', batchNumber: 'CFX-202509', expDate: '2026-09-10', quantity: 10, unit: 'Vial', availableStock: 150, notes: '' },
      { id: 2, name: 'Morfin Injeksi 10 mg/ml', batchNumber: 'MRF-202603', expDate: '2027-03-25', quantity: 10, unit: 'Ampul', availableStock: 70, notes: '' }
    ]
  },
  {
    id: 11,
    issueNumber: 'OUT-202609-0018',
    referenceNumber: 'Draft Buffer Hemodialisa',
    dateDisplay: '07 Sep 2026',
    timeDisplay: '10:00 WIB',
    dateTime: '2026-09-07 10:00',
    warehouse: 'Depo Rawat Inap Lt. 3',
    locationDetail: 'Depo HD Unit',
    officerName: 'Rina Marlina, A.Md.Farm',
    officerRole: 'NIP: 199308142019032038',
    purpose: 'Kebutuhan Internal (IGD & ICU)',
    purposeKey: 'Kebutuhan Internal',
    itemCount: 2,
    totalQuantity: 50,
    quantityUnit: 'Kolf',
    quantitySubtitle: 'Buffer HD',
    status: 'Draft',
    validationNote: '',
    notes: 'Menunggu konfirmasi kepala unit cuci darah.',
    items: [
      { id: 1, name: 'Ringer Laktat 500 ml', batchNumber: 'RL-202601', expDate: '2027-01-30', quantity: 50, unit: 'Kolf', availableStock: 250, notes: '' }
    ]
  },
  {
    id: 12,
    issueNumber: 'OUT-202609-0017',
    referenceNumber: 'Ref: EXP-Q2-2026',
    dateDisplay: '05 Sep 2026',
    timeDisplay: '09:00 WIB',
    dateTime: '2026-09-05 09:00',
    warehouse: 'Gudang Logistik Sentral',
    locationDetail: 'Ruang Karantina BAP',
    officerName: 'Dimas Prasetyo, S.Farm',
    officerRole: 'SIP: 19890111/SIPA/2020',
    purpose: 'Kedaluwarsa (Pemusnahan)',
    purposeKey: 'Kedaluwarsa',
    itemCount: 3,
    totalQuantity: 40,
    quantityUnit: 'Box',
    quantitySubtitle: 'Pemusnahan Batch 1',
    status: 'Selesai',
    validationNote: '',
    notes: 'Pemusnahan tablet antibiotik yang sudah melewati masa simpan.',
    items: [
      { id: 1, name: 'Amoxicillin 500 mg Kapsul', batchNumber: 'AMX-202511', expDate: '2026-08-01', quantity: 40, unit: 'Box', availableStock: 0, notes: '' }
    ]
  }
];
