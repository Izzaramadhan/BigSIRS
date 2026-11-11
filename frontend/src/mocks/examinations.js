// Mock Data Pemeriksaan Rawat Jalan untuk SIMRS BigSIRS
// Data simulasi fiktif yang aman untuk frontend tanpa backend

export const mockPolyclinics = [
  'Poli Penyakit Dalam',
  'Poli Umum',
  'Poli Anak & Tumbuh Kembang',
  'Poli Gigi & Mulut',
  'Poli Kandungan',
  'Poli Jantung & Vaskular',
  'Poli Mata',
  'Poli Saraf',
  'Poli THT-KL',
  'Poli Bedah Umum',
  'Poli Kulit & Kelamin',
  'Poli Paru'
];

export const mockDoctors = [
  'dr. Budi Santoso, Sp.PD',
  'dr. Siti Aminah, Sp.A',
  'dr. Hendra Pratama, Sp.OG',
  'dr. Dian Anggraini, Sp.M',
  'dr. Melani Safitri, Sp.Perio',
  'dr. Rudi Hermawan, Sp.S',
  'dr. Bambang Setyadi, Sp.JP',
  'dr. Putri Anindita, M.Ked.Klin',
  'dr. Bayu Aji Pamungkas, Sp.B',
  'dr. Clarissa Amanda, Sp.KK',
  'dr. Ahmad Fauzi Ridwan, Sp.P',
  'dr. Ferry Sanjaya, Sp.OT'
];

export const mockMidwives = [
  'Bdn. Siti Rahmawati, S.Keb',
  'Bdn. Dewi Lestari, A.Md.Keb',
  'Bdn. Nurul Hidayah, S.Tr.Keb',
  'Bdn. Rina Wulandari, A.Md.Keb',
  'Bdn. Maya Puspita, S.Keb'
];

export const mockBidanPhysicalHistory = [
  {
    id: 'BPH-1',
    date: '17-10-2026',
    tfu: '28 cm',
    tbj: '2.480 gram',
    djj: '142 x/menit (Teratur)',
    position: 'Memanjang / Kepala / PUKA',
    midwife: 'Bdn. Siti Rahmawati, S.Keb'
  },
  {
    id: 'BPH-2',
    date: '28-09-2026',
    tfu: '25 cm',
    tbj: '1.860 gram',
    djj: '140 x/menit (Teratur)',
    position: 'Memanjang / Kepala / PUKI',
    midwife: 'Bdn. Dewi Lestari, A.Md.Keb'
  }
];

export const mockGuarantorOptions = [
  'BPJS Kesehatan',
  'Pasien Umum / Tunai',
  'Asuransi Swasta'
];

export const mockPeriodOptions = [
  { label: 'Hari Ini', value: 'today' },
  { label: 'Kemarin', value: 'yesterday' },
  { label: '7 Hari Terakhir', value: 'last_7_days' },
  { label: '30 Hari Terakhir', value: 'last_30_days' },
  { label: 'Rentang Tanggal', value: 'custom' }
];

export const mockPriorityOptions = [
  'Normal',
  'Lansia',
  'Risiko Jatuh',
  'Pediatri / Balita',
  'Ibu Hamil',
  'Prioritas Medis Cito'
];

export const mockStatusOptions = [
  'Semua Status',
  'Menunggu',
  'Dipanggil',
  'Sedang Diperiksa',
  'Selesai',
  'Dibatalkan'
];

// Room & Building mappings for polyclinics
export const polyclinicRooms = {
  'Poli Penyakit Dalam': { building: 'Gedung A', floor: 'Lt. 1', room: 'R.102' },
  'Poli Umum': { building: 'Gedung A', floor: 'Lt. 1', room: 'R.101' },
  'Poli Anak & Tumbuh Kembang': { building: 'Gedung A', floor: 'Lt. 2', room: 'R.201' },
  'Poli Gigi & Mulut': { building: 'Gedung B', floor: 'Lt. 1', room: 'R.105' },
  'Poli Kandungan': { building: 'Gedung A', floor: 'Lt. 2', room: 'R.204' },
  'Poli Jantung & Vaskular': { building: 'Gedung A', floor: 'Lt. 3', room: 'R.302' },
  'Poli Mata': { building: 'Gedung B', floor: 'Lt. 2', room: 'R.202' },
  'Poli Saraf': { building: 'Gedung A', floor: 'Lt. 3', room: 'R.305' },
  'Poli THT-KL': { building: 'Gedung B', floor: 'Lt. 2', room: 'R.206' },
  'Poli Bedah Umum': { building: 'Gedung B', floor: 'Lt. 3', room: 'R.301' },
  'Poli Kulit & Kelamin': { building: 'Gedung B', floor: 'Lt. 1', room: 'R.108' },
  'Poli Paru': { building: 'Gedung A', floor: 'Lt. 3', room: 'R.308' }
};

// Helper for initials
export const getDoctorInitials = (name) => {
  if (!name) return 'DR';
  const clean = name.replace(/^dr\.\s*/i, '').trim();
  const parts = clean.split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return clean.slice(0, 2).toUpperCase();
};

// Calculate age from birth date string (YYYY-MM-DD)
export const calculateAge = (birthDateStr) => {
  if (!birthDateStr) return 0;
  const birth = new Date(birthDateStr);
  const today = new Date();
  let age = today.getFullYear() - birth.getFullYear();
  const m = today.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
    age--;
  }
  return Math.max(0, age);
};

// Raw patient seed data
const patientSeeds = [
  // 1. Reference Patient 1
  {
    name: 'H. Soedirman Wibowo',
    gender: 'L',
    birthPlace: 'Bantul',
    birthDate: '1952-04-12', // 74 yrs in 2026
    address: 'Jl. Parangtritis KM 8,5, Sewon, KABUPATEN BANTUL, DI YOGYAKARTA',
    nik: '3174021204520001',
    priority: 'Lansia',
    priorityNote: 'Lansia / Risiko Jatuh',
    poly: 'Poli Penyakit Dalam',
    doctor: 'dr. Budi Santoso, Sp.PD',
    guarantorType: 'BPJS Kesehatan',
    guarantorDetail: 'Mandiri • Faskes 1',
    clinicalNote: 'Kontrol berkala hipertensi stadium 2 & keluhan sesak nafas ringan saat berbaring',
    allergies: [
      { id: 'ALG-1', category: 'Obat', substance: 'Captopril', reaction: 'Batuk kering persisten', severity: 'Sedang' }
    ]
  },
  // 2. Reference Patient 2 (Cito)
  {
    name: 'Ny. Hj. Siti Fatimah',
    gender: 'P',
    birthPlace: 'Sumbawa',
    birthDate: '1961-08-19',
    address: 'Jl. Wiyoro No.1, Wiyoro Baru, Baturetno, BANGUNTAPAN, KABUPATEN BANTUL, DI YOGYAKARTA',
    nik: '3174035908610002',
    priority: 'Prioritas Medis Cito',
    priorityNote: 'Prioritas Medis Cito - Nyeri Dada Akut',
    poly: 'Poli Jantung & Vaskular',
    doctor: 'dr. Bambang Setyadi, Sp.JP',
    guarantorType: 'BPJS Kesehatan',
    guarantorDetail: 'JKN-PBI Kelas 3',
    clinicalNote: 'Angina pectoris tidak stabil, rujukan puskesmas dengan rekomendasi observasi EKG segera',
    allergies: [
      { id: 'ALG-2', category: 'Obat', substance: 'Amoxicillin', reaction: 'Ruam kulit & gatal', severity: 'Ringan' }
    ]
  },
  // 3. Reference Patient 3 (Pediatri)
  {
    name: 'An. Rafka Alvaro Pratama',
    gender: 'L',
    birthPlace: 'Sleman',
    birthDate: '2022-09-14',
    address: 'Jl. Kaliurang KM 11, Gentan, Sinduharjo, NGAGLIK, KABUPATEN SLEMAN, DI YOGYAKARTA',
    nik: '3174041409220003',
    priority: 'Pediatri / Balita',
    priorityNote: 'Balita / Febris Hari ke-3',
    poly: 'Poli Anak & Tumbuh Kembang',
    doctor: 'dr. Siti Aminah, Sp.A',
    guarantorType: 'Pasien Umum / Tunai',
    guarantorDetail: 'Tunai / QRIS',
    clinicalNote: 'Demam 38.8C naik turun, batuk kering dan nafsu makan menurun',
    allergies: []
  },
  // 4. Reference Patient 4 (Ibu Hamil)
  {
    name: 'Ny. Dian Kartika Sari',
    gender: 'P',
    birthPlace: 'Yogyakarta',
    birthDate: '1995-11-23',
    address: 'Jl. Magelang KM 6, Kutu Dukuh, Sinduadi, MLATI, KABUPATEN SLEMAN, DI YOGYAKARTA',
    nik: '3174056311950004',
    priority: 'Ibu Hamil',
    priorityNote: 'Trimester 3 / Evaluasi Letak Sungsang',
    poly: 'Poli Kandungan',
    doctor: 'dr. Hendra Pratama, Sp.OG',
    guarantorType: 'BPJS Kesehatan',
    guarantorDetail: 'Mandiri • Faskes 1',
    clinicalNote: 'Pemeriksaan USG antenatal care kehamilan 34 minggu',
    allergies: [
      { id: 'ALG-4', category: 'Makanan', substance: 'Udang / Seafood', reaction: 'Urtikaria ringan', severity: 'Ringan' }
    ]
  },
  // 5. Reference Patient 5 (Risiko Jatuh)
  {
    name: 'Tn. Raden Mas Hardjodipuro',
    gender: 'L',
    birthPlace: 'Surakarta',
    birthDate: '1949-01-05',
    address: 'Jl. Taman Siswa No. 42, Wirogunan, MERGANGSAN, KOTA YOGYAKARTA, DI YOGYAKARTA',
    nik: '3174060501490005',
    priority: 'Risiko Jatuh',
    priorityNote: 'Risiko Jatuh Tinggi / Pasca Stroke Iskemik',
    poly: 'Poli Saraf',
    doctor: 'dr. Rudi Hermawan, Sp.S',
    guarantorType: 'Asuransi Swasta',
    guarantorDetail: 'Mandiri Inhealth Gold',
    clinicalNote: 'Hemiparesis dextra kontrol rehabilitasi medik dan penyesuaian terapi antiplatelet',
    allergies: []
  },
  // 6. Reference Patient 6 (Gigi)
  {
    name: 'Nn. Clarissa Amanda Putri',
    gender: 'P',
    birthPlace: 'Bandung',
    birthDate: '2001-06-30',
    address: 'Jl. Gejayan No. 18, Caturtunggal, DEPOK, KABUPATEN SLEMAN, DI YOGYAKARTA',
    nik: '3174077006010006',
    priority: 'Normal',
    priorityNote: 'Antrean Reguler Poliklinik',
    poly: 'Poli Gigi & Mulut',
    doctor: 'dr. Melani Safitri, Sp.Perio',
    guarantorType: 'Pasien Umum / Tunai',
    guarantorDetail: 'Pembayaran Debit EDC',
    clinicalNote: 'Gingivitis marginalis kronis & konsultasi rencana tindakan scaling',
    allergies: []
  },
  // 7. Reference Patient 7 (Mata)
  {
    name: 'Tn. Ahmad Fauzi Ridwan',
    gender: 'L',
    birthPlace: 'Semarang',
    birthDate: '1970-03-15',
    address: 'Jl. Solo KM 9, Maguwoharjo, DEPOK, KABUPATEN SLEMAN, DI YOGYAKARTA',
    nik: '3174081503700007',
    priority: 'Normal',
    priorityNote: 'Antrean Reguler Poliklinik',
    poly: 'Poli Mata',
    doctor: 'dr. Dian Anggraini, Sp.M',
    guarantorType: 'BPJS Kesehatan',
    guarantorDetail: 'JKN-Non PBI',
    clinicalNote: 'Visus menurun mata kanan, evaluasi katarak senilis imatur',
    allergies: []
  },
  // 8. Reference Patient 8 (Paru)
  {
    name: 'Tn. Sugiyanto Subagyo',
    gender: 'L',
    birthPlace: 'Klaten',
    birthDate: '1968-12-08',
    address: 'Jl. Wonosari KM 7, Potorono, BANGUNTAPAN, KABUPATEN BANTUL, DI YOGYAKARTA',
    nik: '3174090812680008',
    priority: 'Normal',
    priorityNote: 'Antrean Reguler Poliklinik',
    poly: 'Poli Paru',
    doctor: 'dr. Ahmad Fauzi Ridwan, Sp.P',
    guarantorType: 'BPJS Kesehatan',
    guarantorDetail: 'Mandiri • Faskes 1',
    clinicalNote: 'Kontrol rutin program OAT fase lanjutan bulan ke-4',
    allergies: []
  },
  // 9. Reference Patient 9 (Bedah)
  {
    name: 'Tn. Ferry Sanjaya Pratama',
    gender: 'L',
    birthPlace: 'Jakarta',
    birthDate: '1984-07-22',
    address: 'Jl. Timoho No. 90, Baciro, GONDOKUSUMAN, KOTA YOGYAKARTA, DI YOGYAKARTA',
    nik: '3174102207840009',
    priority: 'Normal',
    priorityNote: 'Antrean Reguler Poliklinik',
    poly: 'Poli Bedah Umum',
    doctor: 'dr. Bayu Aji Pamungkas, Sp.B',
    guarantorType: 'Asuransi Swasta',
    guarantorDetail: 'Prudential Prime Healthcare',
    clinicalNote: 'Evaluasi benjolan lipoma regio scapula sinistra rencana eksisi',
    allergies: []
  },
  // 10. Reference Patient 10 (Kulit)
  {
    name: 'Ny. Melani Safitri Dewi',
    gender: 'P',
    birthPlace: 'Magelang',
    birthDate: '1992-05-18',
    address: 'Jl. Godean KM 5, Ambarketawang, GAMPING, KABUPATEN SLEMAN, DI YOGYAKARTA',
    nik: '3174115805920010',
    priority: 'Normal',
    priorityNote: 'Antrean Reguler Poliklinik',
    poly: 'Poli Kulit & Kelamin',
    doctor: 'dr. Clarissa Amanda, Sp.KK',
    guarantorType: 'Pasien Umum / Tunai',
    guarantorDetail: 'Tunai / Cash',
    clinicalNote: 'Dermatitis kontak alergika pada kedua telapak tangan',
    allergies: []
  }
];

const poolFirstNames = [
  'Ahmad', 'Bambang', 'Siti', 'Dewi', 'Hendra', 'Putri', 'I Made', 'Sri',
  'Muhammad', 'Endang', 'Sugiyanto', 'Anisa', 'Yohanes', 'Nabila', 'Andi',
  'Riana', 'Ferry', 'Tuti', 'Bayu', 'Clarissa', 'Hardjodipuro', 'Melani',
  'Zaky', 'Theresia', 'Rudi', 'Laksmi', 'Stephanie', 'Dimas', 'Eko',
  'Nur', 'Farhan', 'Rina', 'Agus', 'Wahyudi', 'Tri', 'Ratna', 'Dian', 'Reza',
  'Irwan', 'Nurul', 'Kurniawan', 'Mega', 'Surya', 'Fajar', 'Ade', 'Joko'
];

const poolLastNames = [
  'Pratama', 'Setyadi', 'Rahmawati', 'Lestari', 'Kusuma', 'Anindita', 'Suardana',
  'Wahyuni', 'Rizky', 'Sulistiyowati', 'Azizah', 'Wibowo', 'Syahrani', 'Baso',
  'Hutapea', 'Sanjaya', 'Maryati', 'Pamungkas', 'Permata', 'Safitri', 'Al Ghifari',
  'Anggraini', 'Hermawan', 'Dewi', 'Santoso', 'Tan', 'Hidayat', 'Saputra',
  'Wijaya', 'Siregar', 'Kurniawan', 'Nugroho', 'Subagyo', 'Purwanto'
];

const birthPlacePool = [
  'Yogyakarta', 'Bantul', 'Sleman', 'Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Surakarta', 'Magelang', 'Sumbawa'
];

const addressPool = [
  'Jl. Parangtritis KM 6,5, Sewon, KABUPATEN BANTUL, DI YOGYAKARTA',
  'Jl. Kaliurang KM 9, Gondangan, NGAGLIK, KABUPATEN SLEMAN, DI YOGYAKARTA',
  'Jl. Wonosari KM 5, Banguntapan, KABUPATEN BANTUL, DI YOGYAKARTA',
  'Jl. Kusumanegara No. 28, Semaki, UMBULHARJO, KOTA YOGYAKARTA, DI YOGYAKARTA',
  'Jl. Magelang KM 7, Sendangadi, MLATI, KABUPATEN SLEMAN, DI YOGYAKARTA'
];

export const mockIcd10Catalog = [
  { code: 'I10', name: 'Essential (primary) hypertension' },
  { code: 'E11.9', name: 'Type 2 diabetes mellitus without complications' },
  { code: 'J06.9', name: 'Acute upper respiratory infection, unspecified' },
  { code: 'K30', name: 'Functional dyspepsia' },
  { code: 'R50.9', name: 'Fever, unspecified' },
  { code: 'G43.9', name: 'Migraine, unspecified' },
  { code: 'Z34.0', name: 'Supervision of normal first pregnancy' },
  { code: 'L23.9', name: 'Allergic contact dermatitis, unspecified cause' },
  { code: 'K05.1', name: 'Chronic gingivitis' },
  { code: 'I20.0', name: 'Unstable angina' }
];

export const mockIcd9Catalog = [
  { code: '89.03', name: 'Interview and evaluation, described as comprehensive' },
  { code: '89.52', name: 'Electrocardiogram (EKG 12-Lead)' },
  { code: '88.76', name: 'Diagnostic ultrasound of abdomen and retroperitoneum' },
  { code: '88.78', name: 'Diagnostic ultrasound of gravid uterus' },
  { code: '87.44', name: 'Routine chest X-ray, so described (Foto Thorax)' },
  { code: '93.59', name: 'Other immobilization, pressure, and attention to wound' },
  { code: '99.18', name: 'Injection or infusion of electrolytes' },
  { code: '86.22', name: 'Excisional debridement of wound, infection, or burn' }
];

export const mockClinicalProcedures = [
  { code: 'TND-001', name: 'Konsultasi & Pemeriksaan Dokter Spesialis', tariff: 120000 },
  { code: 'TND-002', name: 'Pemeriksaan Tanda Vital & Anamnesis Rawat Jalan', tariff: 35000 },
  { code: 'TND-003', name: 'Elektrokardiografi (EKG) 12-Lead', tariff: 85000 },
  { code: 'TND-004', name: 'Terapi Inhalasi / Nebulisasi', tariff: 65000 },
  { code: 'TND-005', name: 'Pemeriksaan USG Obstetri / Abdomen', tariff: 210000 },
  { code: 'TND-006', name: 'Pemasangan & Observasi Infus Intravena', tariff: 95000 },
  { code: 'TND-007', name: 'Perawatan Luka Ringan / Ganti Balutan', tariff: 55000 }
];

export const mockProcedurePackages = [
  { code: 'PKT-001', name: 'Paket Pemeriksaan ANC Terpadu (Konsul + USG + Lab)', tariff: 325000 },
  { code: 'PKT-002', name: 'Paket Observasi Kardiovaskular (Konsul + EKG 12-Lead)', tariff: 190000 },
  { code: 'PKT-003', name: 'Paket Perawatan Luka Steril & Medikasi', tariff: 145000 },
  { code: 'PKT-004', name: 'Paket Inhalasi Pernapasan & Oksimetri', tariff: 115000 }
];

export const mockParamedics = [
  'Ns. Ratna Puspita, S.Kep',
  'Ns. Adi Nugroho, A.Md.Kep',
  'Bdn. Siti Rahmawati, S.Keb',
  'Perawat Poliklinik'
];

export const mockMedicinesCatalog = [
  { code: 'OBT-001', name: 'Paracetamol 500 mg Tablet', unit: 'Tablet', defaultSigna: '3 x 1', usageRule: 'Sesudah makan', price: 3500, warehouse: 'Gudang Utama' },
  { code: 'OBT-002', name: 'Amlodipine 5 mg Tablet', unit: 'Tablet', defaultSigna: '1 x 1', usageRule: 'Pagi hari', price: 6500, warehouse: 'Gudang Utama' },
  { code: 'OBT-003', name: 'Omeprazole 20 mg Kapsul', unit: 'Kapsul', defaultSigna: '2 x 1', usageRule: 'Sebelum makan', price: 8500, warehouse: 'Gudang Utama' },
  { code: 'OBT-004', name: 'Cetirizine 10 mg Tablet', unit: 'Tablet', defaultSigna: '1 x 1', usageRule: 'Malam sebelum tidur', price: 4500, warehouse: 'Depo Farmasi Rawat Jalan' },
  { code: 'OBT-005', name: 'Ambroxol 30 mg Tablet', unit: 'Tablet', defaultSigna: '3 x 1', usageRule: 'Sesudah makan', price: 4000, warehouse: 'Gudang Utama' },
  { code: 'OBT-006', name: 'Vitamin B Complex Tablet', unit: 'Tablet', defaultSigna: '1 x 1', usageRule: 'Sesudah makan', price: 3000, warehouse: 'Gudang Utama' }
];

export const mockPrescriptionPackages = [
  { code: 'PKO-001', name: 'Paket Terapi ISPA Ringan (Paracetamol + Ambroxol + Vit B)', unit: 'Paket', defaultSigna: '3 x 1', usageRule: 'Sesudah makan', price: 25000, warehouse: 'Gudang Utama' },
  { code: 'PKO-002', name: 'Paket Kontrol Hipertensi Dasar (Amlodipine + Vit B Complex)', unit: 'Paket', defaultSigna: '1 x 1', usageRule: 'Pagi hari', price: 28000, warehouse: 'Gudang Utama' },
  { code: 'PKO-003', name: 'Paket Terapi Dispepsia (Omeprazole + Antasida)', unit: 'Paket', defaultSigna: '2 x 1', usageRule: 'Sebelum makan', price: 32000, warehouse: 'Depo Farmasi Rawat Jalan' }
];

export const mockPrescriptionHistory = [
  {
    id: 'RXH-1',
    date: '17-10-2026 14:19:00',
    unit: 'POLIKLINIK UMUM',
    doctor: 'dr. Putri Anindita, M.Ked.Klin',
    items: [
      { code: 'OBT-001', name: 'Paracetamol 500 mg Tablet', signa: '3 x 1', qty: 10, unit: 'Tablet', usageRule: 'Sesudah makan', price: 3500 },
      { code: 'OBT-006', name: 'Vitamin B Complex Tablet', signa: '1 x 1', qty: 10, unit: 'Tablet', usageRule: 'Sesudah makan', price: 3000 }
    ]
  },
  {
    id: 'RXH-2',
    date: '01-10-2026 14:23:00',
    unit: 'INSTALASI GAWAT DARURAT',
    doctor: 'dr. Bayu Aji Pamungkas, Sp.B',
    items: [
      { code: 'OBT-003', name: 'Omeprazole 20 mg Kapsul', signa: '2 x 1', qty: 6, unit: 'Kapsul', usageRule: 'Sebelum makan', price: 8500 }
    ]
  }
];

export const mockLaboratoryCatalog = [
  // Kolom Kiri
  {
    id: 'CAT-HEM-RUTIN',
    column: 'left',
    category: 'Hematologi Rutin',
    items: [
      { code: 'LAB-HR-01', name: 'Hitung Jenis Leukosit', price: 65000 }
    ]
  },
  {
    id: 'CAT-FUNGSI-GINJAL',
    column: 'left',
    category: 'Fungsi Ginjal',
    items: [
      { code: 'LAB-FG-01', name: 'UREUM', price: 45000 },
      { code: 'LAB-FG-02', name: 'KREATININ', price: 45000 },
      { code: 'LAB-FG-03', name: 'ASAM URAT', price: 50000 }
    ]
  },
  {
    id: 'CAT-GLUKOSA',
    column: 'left',
    category: 'Glukosa',
    items: [
      { code: 'LAB-GL-01', name: 'Glukosa Puasa', price: 35000 },
      { code: 'LAB-GL-02', name: 'Glukosa 2 Jam PP', price: 35000 },
      { code: 'LAB-GL-03', name: 'Glukosa Sewaktu', price: 35000 },
      { code: 'LAB-GL-04', name: 'HbA1C', price: 185000 }
    ]
  },
  {
    id: 'CAT-ELEKTROLIT',
    column: 'left',
    category: 'Elektrolit',
    items: [
      { code: 'LAB-EL-01', name: 'ELEKTROLIT', price: 145000 }
    ]
  },
  {
    id: 'CAT-FAECES',
    column: 'left',
    category: 'Faeces',
    items: [
      { code: 'LAB-FC-01', name: 'MALARIA', price: 75000 }
    ]
  },
  {
    id: 'CAT-HEPATITIS',
    column: 'left',
    category: 'Hepatitis',
    items: [
      { code: 'LAB-HEP-01', name: 'HbsAg', price: 95000 },
      { code: 'LAB-HEP-02', name: 'ANTI HAV', price: 185000 },
      { code: 'LAB-HEP-03', name: 'HBsAb', price: 135000 },
      { code: 'LAB-HEP-04', name: 'ANTI HBc IgM', price: 210000 },
      { code: 'LAB-HEP-05', name: 'Anti HCV', price: 240000 }
    ]
  },
  {
    id: 'CAT-HEMATOLOGI',
    column: 'left',
    category: 'Hematologi',
    items: [
      { code: 'LAB-HM-01', name: 'Retikulosit', price: 55000 },
      { code: 'LAB-HM-02', name: 'GOLONGAN DARAH ABO', price: 35000 },
      { code: 'LAB-HM-03', name: 'Gambaran Darah Tepi', price: 120000 },
      { code: 'LAB-HM-04', name: 'GOLONGAN DARAH RHESUS', price: 40000 }
    ]
  },
  {
    id: 'CAT-PARASITOLOGI',
    column: 'left',
    category: 'Parasitologi',
    items: [
      { code: 'LAB-PR-01', name: 'Malaria', price: 75000 }
    ]
  },
  {
    id: 'CAT-DARAH-PMI',
    column: 'left',
    category: 'Darah Donor PMI',
    items: [
      { code: 'LAB-PMI-01', name: 'Whole Blood (WB)', price: 360000 },
      { code: 'LAB-PMI-02', name: 'PRC', price: 360000 },
      { code: 'LAB-PMI-03', name: 'Trombosit Concentrate (TC)', price: 380000 }
    ]
  },
  {
    id: 'CAT-HISTOLOGI',
    column: 'left',
    category: 'Histologi Anatomi',
    items: [
      { code: 'LAB-PA-01', name: 'Histologi Anatomi Jaringan Kecil', price: 325000 },
      { code: 'LAB-PA-02', name: 'Histologi Anatomi Jaringan Sedang', price: 450000 },
      { code: 'LAB-PA-03', name: 'Histologi Anatomi Jaringan Besar', price: 625000 }
    ]
  },
  {
    id: 'CAT-NAFAS-ATAS',
    column: 'left',
    category: 'Saluran Pernafasan Atas',
    items: [
      { code: 'LAB-NFA-01', name: 'Swab Faring', price: 95000 },
      { code: 'LAB-NFA-02', name: 'Swab Tenggorokan', price: 110000 }
    ]
  },
  {
    id: 'CAT-NAFAS-BAWAH',
    column: 'left',
    category: 'Saluran Pernafasan Bawah',
    items: [
      { code: 'LAB-NFB-01', name: 'Sputum', price: 90000 },
      { code: 'LAB-NFB-02', name: 'Bilasan Bronkus', price: 140000 },
      { code: 'LAB-NFB-03', name: 'Aspirat Trakea', price: 140000 },
      { code: 'LAB-NFB-04', name: 'BAL (Bronchoalveolar lavage)', price: 185000 }
    ]
  },
  {
    id: 'CAT-GENITALIA',
    column: 'left',
    category: 'Saluran Genitalia',
    items: [
      { code: 'LAB-GEN-01', name: 'Swab Vagina', price: 95000 },
      { code: 'LAB-GEN-02', name: 'Swab Uretra', price: 95000 },
      { code: 'LAB-GEN-03', name: 'Swab Serviks', price: 110000 },
      { code: 'LAB-GEN-04', name: 'Penis', price: 90000 },
      { code: 'LAB-GEN-05', name: 'Sekret Uretra', price: 95000 },
      { code: 'LAB-GEN-06', name: 'Sekret Vagina', price: 95000 },
      { code: 'LAB-GEN-07', name: 'Vulva', price: 90000 }
    ]
  },

  // Kolom Kanan
  {
    id: 'CAT-FUNGSI-HATI',
    column: 'right',
    category: 'Fungsi Hati',
    items: [
      { code: 'LAB-FH-01', name: 'PROTEIN TOTAL', price: 45000 },
      { code: 'LAB-FH-02', name: 'ALBUMIN', price: 50000 },
      { code: 'LAB-FH-03', name: 'GLOBULIN', price: 45000 },
      { code: 'LAB-FH-04', name: 'ALKALI PHOSPHATASE', price: 65000 },
      { code: 'LAB-FH-05', name: 'BILIRUBIN TOTAL', price: 55000 },
      { code: 'LAB-FH-06', name: 'BILIRUBIN TOTAL DIREK INDIREK', price: 95000 },
      { code: 'LAB-FH-07', name: 'SGOT', price: 45000 },
      { code: 'LAB-FH-08', name: 'SGPT', price: 45000 },
      { code: 'LAB-FH-09', name: 'GAMMA GT', price: 75000 }
    ]
  },
  {
    id: 'CAT-PROFIL-LIPID',
    column: 'right',
    category: 'Profil Lipid',
    items: [
      { code: 'LAB-LP-01', name: 'KOLESTEROL TOTAL', price: 45000 },
      { code: 'LAB-LP-02', name: 'KOLESTEROL HDL', price: 60000 },
      { code: 'LAB-LP-03', name: 'KOLESTEROL LDL', price: 75000 },
      { code: 'LAB-LP-04', name: 'TRIGLISERIDA', price: 55000 }
    ]
  },
  {
    id: 'CAT-ENZIM-JANTUNG',
    column: 'right',
    category: 'Enzim Jantung',
    items: [
      { code: 'LAB-EJ-01', name: 'CKMB', price: 175000 }
    ]
  },
  {
    id: 'CAT-PROFIL-BESI',
    column: 'right',
    category: 'Profil Besi',
    items: [
      { code: 'LAB-PB-01', name: 'FERRITIN', price: 225000 }
    ]
  },
  {
    id: 'CAT-THYPOID',
    column: 'right',
    category: 'Pemeriksaan Thypoid',
    items: [
      { code: 'LAB-TH-01', name: 'WIDAL', price: 75000 },
      { code: 'LAB-TH-02', name: 'IGM ANTI SALMONELLA', price: 215000 }
    ]
  },
  {
    id: 'CAT-URINALISIS',
    column: 'right',
    category: 'Urinalisis',
    items: [
      { code: 'LAB-UR-01', name: 'URINALISA MAKROSKOPIS', price: 45000 },
      { code: 'LAB-UR-02', name: 'URINALISA MIKROSKOPIS', price: 55000 },
      { code: 'LAB-UR-03', name: 'TES KEHAMILAN', price: 40000 },
      { code: 'LAB-UR-04', name: 'Protein Urine', price: 35000 }
    ]
  },
  {
    id: 'CAT-MIKROBIOLOGI',
    column: 'right',
    category: 'Mikrobiologi',
    items: []
  },
  {
    id: 'CAT-SEROLOGI',
    column: 'right',
    category: 'Serologi',
    items: [
      { code: 'LAB-SR-01', name: 'DENGUE IgG / IgM', price: 245000 },
      { code: 'LAB-SR-02', name: 'ANTI SARS CoV-2', price: 150000 },
      { code: 'LAB-SR-03', name: 'HIV', price: 165000 }
    ]
  },
  {
    id: 'CAT-FAAL-HEMOSTASIS',
    column: 'right',
    category: 'Faal Hemostasis',
    items: [
      { code: 'LAB-HS-01', name: 'Waktu Perdarahan', price: 40000 },
      { code: 'LAB-HS-02', name: 'Waktu Pembekuan', price: 40000 },
      { code: 'LAB-HS-03', name: 'Waktu Protrombin', price: 115000 },
      { code: 'LAB-HS-04', name: 'INR', price: 115000 },
      { code: 'LAB-HS-05', name: 'APTT', price: 120000 },
      { code: 'LAB-HS-06', name: 'Fibrinogen', price: 185000 }
    ]
  },
  {
    id: 'CAT-TIROID',
    column: 'right',
    category: 'Fungsi Tiroid',
    items: [
      { code: 'LAB-TR-01', name: 'TSH', price: 195000 },
      { code: 'LAB-TR-02', name: 'FT4', price: 215000 }
    ]
  },
  {
    id: 'CAT-TROPONIN',
    column: 'right',
    category: 'Troponin Kualitatif',
    items: [
      { code: 'LAB-TP-01', name: 'Troponin Kualitatif', price: 225000 }
    ]
  },
  {
    id: 'CAT-KIMIA-KLINIK',
    column: 'right',
    category: 'Kimia Klinik',
    items: [
      { code: 'LAB-KK-01', name: 'Kalsium', price: 65000 },
      { code: 'LAB-KK-02', name: 'Magnesium', price: 85000 }
    ]
  },
  {
    id: 'CAT-PROTEIN-SPESIFIK',
    column: 'right',
    category: 'Protein spesifik',
    items: [
      { code: 'LAB-PS-01', name: 'RF Kualitatif', price: 85000 },
      { code: 'LAB-PS-02', name: 'CRP Kualitatif', price: 95000 }
    ]
  },
  {
    id: 'CAT-CAIRAN-TUBUH',
    column: 'right',
    category: 'Darah dan Bahan Tubuh Lainnya',
    items: [
      { code: 'LAB-CT-01', name: 'Darah', price: 110000 },
      { code: 'LAB-CT-02', name: 'CSF / cairan otak', price: 165000 },
      { code: 'LAB-CT-03', name: 'Cairan sendi', price: 145000 },
      { code: 'LAB-CT-04', name: 'Cairan pleura', price: 145000 },
      { code: 'LAB-CT-05', name: 'Ludah', price: 90000 }
    ]
  },
  {
    id: 'CAT-URIN-KEMIH',
    column: 'right',
    category: 'Urin dan Saluran Kemih',
    items: [
      { code: 'LAB-UK-01', name: 'Urin porsi tengah', price: 95000 },
      { code: 'LAB-UK-02', name: 'Urin Cystoscopy', price: 125000 },
      { code: 'LAB-UK-03', name: 'Urin kateter', price: 110000 },
      { code: 'LAB-UK-04', name: 'Urin suprapubic', price: 125000 },
      { code: 'LAB-UK-05', name: 'Urin aspirasi suprapubik', price: 125000 }
    ]
  },
  {
    id: 'CAT-GASTROINTESTINAL',
    column: 'right',
    category: 'Saluran Gastrointestinal',
    items: [
      { code: 'LAB-GI-01', name: 'Feses', price: 95000 },
      { code: 'LAB-GI-02', name: 'Swab Rektal', price: 110000 }
    ]
  },
  {
    id: 'CAT-LUKA-ABSES',
    column: 'right',
    category: 'Luka/Abses/Eksudat/Bahan Jaringan',
    items: [
      { code: 'LAB-LA-01', name: 'Luka / Eksudat', price: 115000 },
      { code: 'LAB-LA-02', name: 'Jaringan', price: 135000 },
      { code: 'LAB-LA-03', name: 'Ujung Kateter IV', price: 125000 },
      { code: 'LAB-LA-04', name: 'Ujung Tube', price: 125000 },
      { code: 'LAB-LA-05', name: 'Aspirat', price: 120000 },
      { code: 'LAB-LA-06', name: 'Kerokan Kulit', price: 110000 }
    ]
  },
  {
    id: 'CAT-HIDUNG-NARES',
    column: 'right',
    category: 'Hidung / Nares',
    items: [
      { code: 'LAB-HN-01', name: 'Swab Hidung', price: 95000 },
      { code: 'LAB-HN-02', name: 'Nasofaringeal', price: 110000 },
      { code: 'LAB-HN-03', name: 'Swab Nares', price: 95000 }
    ]
  },
  {
    id: 'CAT-MATA-TELINGA-HIDUNG',
    column: 'right',
    category: 'Mata/Telinga/Hidung',
    items: [
      { code: 'LAB-MTH-01', name: 'Swab mata kanan', price: 95000 },
      { code: 'LAB-MTH-02', name: 'Swab mata kiri', price: 95000 },
      { code: 'LAB-MTH-03', name: 'Konjungtiva', price: 95000 },
      { code: 'LAB-MTH-04', name: 'Kornea', price: 110000 },
      { code: 'LAB-MTH-05', name: 'Swab telinga kanan', price: 95000 },
      { code: 'LAB-MTH-06', name: 'Swab telinga kiri', price: 95000 },
      { code: 'LAB-MTH-07', name: 'Aspirasi cairan telinga tengah', price: 135000 },
      { code: 'LAB-MTH-08', name: 'Kanalis eksterna', price: 95000 },
      { code: 'LAB-MTH-09', name: 'Lubang gendang telinga', price: 110000 },
      { code: 'LAB-MTH-10', name: 'Swab hidung kanan', price: 95000 },
      { code: 'LAB-MTH-11', name: 'Swab hidung kiri', price: 95000 }
    ]
  }
];

export const mockLabDpjpDoctors = [
  'dr. Hendra Wijaya, Sp.PK',
  'dr. Ratna Dewi Puspita, Sp.PK',
  'dr. Arif Budiman, Sp.PK(K)'
];

export const mockLabClinicalStaff = [
  'Siti Rahmawati, S.Tr.AK',
  'Dian Permata Sari, A.Md.AK',
  'Budi Hartono, S.Tr.Kes',
  'Rina Andriani, A.Md.AK'
];

export const mockLabHistory = [];

export const mockRadiologyCatalog = {
  umum: [
    {
      id: 'RAD-UMUM-ANALOG',
      category: 'ANALOG/KONVENSIONAL',
      items: [
        { code: 'RAD-UA-01', name: 'Periapikal', price: 85000 },
        { code: 'RAD-UA-02', name: 'Tube shift Method', price: 110000 },
        { code: 'RAD-UA-03', name: 'Bitewing x', price: 95000 },
        { code: 'RAD-UA-04', name: 'Oklusal', price: 95000 },
        { code: 'RAD-UA-05', name: 'Panoramik(OPG)', price: 185000 },
        { code: 'RAD-UA-06', name: 'Sefalometri', price: 185000 }
      ]
    },
    {
      id: 'RAD-UMUM-DIG-CD',
      category: 'DIGITAL (CD)',
      items: [
        { code: 'RAD-UDC-01', name: 'Periapikal', price: 105000 },
        { code: 'RAD-UDC-02', name: 'Tube shift Method', price: 130000 },
        { code: 'RAD-UDC-03', name: 'Bitewing', price: 115000 },
        { code: 'RAD-UDC-04', name: 'Oklusal', price: 115000 },
        { code: 'RAD-UDC-05', name: 'Panoramik(OPG)', price: 215000 },
        { code: 'RAD-UDC-06', name: 'Sefalometri', price: 215000 },
        { code: 'RAD-UDC-07', name: 'Copy 1 CD Digital per radiograf', price: 50000 }
      ]
    },
    {
      id: 'RAD-UMUM-DIG-FILM',
      category: 'DIGITAL (Film)',
      items: [
        { code: 'RAD-UDF-01', name: 'Periapikal', price: 115000 },
        { code: 'RAD-UDF-02', name: 'Tube shift Method', price: 140000 },
        { code: 'RAD-UDF-03', name: 'Bitewing', price: 125000 },
        { code: 'RAD-UDF-04', name: 'Oklusal', price: 125000 },
        { code: 'RAD-UDF-05', name: 'Panoramik(OPG)', price: 235000 },
        { code: 'RAD-UDF-06', name: 'Sefalometri', price: 235000 }
      ]
    },
    {
      id: 'RAD-UMUM-CBCT',
      category: 'CBCT',
      items: [
        { code: 'RAD-UC-01', name: 'Panoramic View', price: 450000 },
        { code: 'RAD-UC-02', name: 'TMJ View (Kanan dan Kiri)', price: 525000 },
        { code: 'RAD-UC-03', name: 'Pasien tidak mampu', price: 250000 },
        { code: 'RAD-UC-04', name: 'Penelitian antropologi', price: 350000 },
        { code: 'RAD-UC-05', name: 'Copy 1 CD CBCT per pasien', price: 65000 }
      ]
    }
  ],
  koas: [
    {
      id: 'RAD-KOAS-ANALOG',
      category: 'ANALOG/KONVENSIONAL',
      items: [
        { code: 'RAD-KA-01', name: 'Periapikal', price: 65000 },
        { code: 'RAD-KA-02', name: 'Tube shift Method', price: 85000 },
        { code: 'RAD-KA-03', name: 'Bitewing', price: 75000 },
        { code: 'RAD-KA-04', name: 'Oklusal koas', price: 75000 },
        { code: 'RAD-KA-05', name: 'Panoramik(OPG)', price: 145000 },
        { code: 'RAD-KA-06', name: 'Sefalometri', price: 145000 }
      ]
    },
    {
      id: 'RAD-KOAS-DIG-CD',
      category: 'DIGITAL (CD)',
      items: [
        { code: 'RAD-KDC-01', name: 'Periapikal', price: 85000 },
        { code: 'RAD-KDC-02', name: 'Tube shift Method', price: 105000 },
        { code: 'RAD-KDC-03', name: 'Bitewing', price: 95000 },
        { code: 'RAD-KDC-04', name: 'Oklusal', price: 95000 },
        { code: 'RAD-KDC-05', name: 'Panoramik(OPG)', price: 175000 },
        { code: 'RAD-KDC-06', name: 'Sefalometri', price: 175000 },
        { code: 'RAD-KDC-07', name: 'Copy 1 CD Digital per radiograf', price: 45000 },
        { code: 'RAD-KDC-08', name: 'CCC', price: 120000 }
      ]
    },
    {
      id: 'RAD-KOAS-DIG-FILM',
      category: 'DIGITAL (Film)',
      items: [
        { code: 'RAD-KDF-01', name: 'Periapikal koas', price: 95000 },
        { code: 'RAD-KDF-02', name: 'Tube shift Method', price: 115000 },
        { code: 'RAD-KDF-03', name: 'Bitewing', price: 105000 },
        { code: 'RAD-KDF-04', name: 'Oklusal', price: 105000 },
        { code: 'RAD-KDF-05', name: 'Panoramik(OPG)', price: 195000 },
        { code: 'RAD-KDF-06', name: 'Sefalometri', price: 195000 }
      ]
    },
    {
      id: 'RAD-KOAS-CBCT',
      category: 'CBCT',
      items: [
        { code: 'RAD-KC-01', name: 'Panoramic View', price: 375000 },
        { code: 'RAD-KC-02', name: 'TMJ View (Kanan dan Kiri)', price: 425000 },
        { code: 'RAD-KC-03', name: 'Pasien tidak mampu', price: 200000 },
        { code: 'RAD-KC-04', name: 'Penelitian antropologi', price: 295000 },
        { code: 'RAD-KC-05', name: 'Copy 1 CD CBCT per pasien', price: 60000 }
      ]
    }
  ]
};

export const mockRadiologyHistory = [];

export const mockVkSurgeryOptions = {
  specialties: [
    'Poli Kandungan & Kebidanan (VK)',
    'Obstetri & Ginekologi',
    'Fetomaternal',
    'Uroginekologi',
    'Poli Umum'
  ],
  classes: ['VIP', 'Kelas I', 'Kelas II', 'Kelas III'],
  roomsByClass: {
    VIP: [
      { name: 'Ruang VK VIP Bougenville', beds: ['Bed VIP-01', 'Bed VIP-02'] }
    ],
    'Kelas I': [
      { name: 'Ruang VK Mawar I', beds: ['Bed 01 (VK Mawar)', 'Bed 02 (VK Mawar)'] },
      { name: 'Ruang VK Melati I', beds: ['Bed 03 (VK Melati)', 'Bed 04 (VK Melati)'] }
    ],
    'Kelas II': [
      { name: 'Ruang VK Anggrek II', beds: ['Bed 05 (VK Anggrek)', 'Bed 06 (VK Anggrek)'] }
    ],
    'Kelas III': [
      { name: 'Ruang VK Cempaka III', beds: ['Bed 07 (VK Cempaka)', 'Bed 08 (VK Cempaka)'] }
    ]
  }
};

export const mockIbsSurgeryOptions = {
  specialties: [
    'Poli Bedah Umum',
    'Bedah Ortopedi',
    'Bedah Saraf',
    'Obstetri & Ginekologi',
    'THT-KL',
    'Bedah Digestif'
  ],
  classes: ['VIP', 'Kelas I', 'Kelas II', 'Kelas III'],
  roomsByClass: {
    VIP: [
      { name: 'Kamar Operasi VIP (OK-VIP)', beds: ['Meja OK-VIP 01', 'Meja OK-VIP 02'] }
    ],
    'Kelas I': [
      { name: 'Kamar Operasi Sentral 01 (OK-01)', beds: ['Meja OK-01A', 'Meja OK-01B'] },
      { name: 'Kamar Operasi Sentral 02 (OK-02)', beds: ['Meja OK-02A', 'Meja OK-02B'] }
    ],
    'Kelas II': [
      { name: 'Kamar Operasi Sentral 03 (OK-03)', beds: ['Meja OK-03A', 'Meja OK-03B'] }
    ],
    'Kelas III': [
      { name: 'Kamar Operasi Cito / Darurat (OK-04)', beds: ['Meja OK-04A', 'Meja OK-04B'] }
    ]
  }
};

const formatDateDMYStr = (isoDate) => {
  if (!isoDate) return '10-11-2026';
  const parts = String(isoDate).split('-');
  if (parts.length !== 3) return isoDate;
  return `${parts[2]}-${parts[1]}-${parts[0]}`;
};

const buildMockVisitHistory = (admissionDate, admissionTime, polyclinicName, doctorName, clinicalNote) => {
  const currentDMY = formatDateDMYStr(admissionDate);
  const cleanTime = String(admissionTime || '11:01').replace(/\s*WIB/i, '').trim();
  const hms = /^\d{2}:\d{2}$/.test(cleanTime) ? `${cleanTime}:00` : cleanTime;
  const upperPoly = String(polyclinicName || 'Poliklinik Umum').toUpperCase();

  return [
    {
      id: 'HIS-1',
      visit_datetime: `${currentDMY} ${hms}`,
      units: [upperPoly],
      doctor: doctorName,
      subjective: clinicalNote || 'Pasien kontrol rutin poliklinik.',
      objective: 'TD: 120/80 mmHg, Nadi: 80x/mnt, Suhu: 36.6 C, RR: 18x/mnt, SpO2: 98%',
      assessment: `Kunjungan aktif ${polyclinicName}`,
      diagnosis: 'I10 - Essential (primary) hypertension',
      procedures: ['Pemeriksaan Dokter Spesialis'],
      prescriptions: ['Amlodipine 5 mg (1 x 1 pagi)', 'Vitamin B Complex (1 x 1)'],
      notes: 'Evaluasi klinis berjalan, lanjutkan observasi pengobatan.'
    },
    {
      id: 'HIS-2',
      visit_datetime: '17-10-2026 14:19:00',
      units: ['POLIKLINIK UMUM'],
      doctor: 'dr. Putri Anindita, M.Ked.Klin',
      subjective: 'Keluhan pusing ringan dan pegal pada tengkuk sejak 2 hari.',
      objective: 'TD: 130/85 mmHg, Nadi: 84x/mnt, Suhu: 36.5 C, RR: 18x/mnt',
      assessment: 'Hipertensi terkontrol baik, cephalgia ringan.',
      diagnosis: 'R51 - Headache / Observasi tekanan darah',
      procedures: ['Pemeriksaan Tanda Vital', 'Konsultasi Dokter Umum'],
      prescriptions: ['Paracetamol 500 mg (3 x 1 kp)', 'Vitamin B Complex (1 x 1)'],
      notes: 'Edukasi istirahat cukup dan kurangi konsumsi garam.'
    },
    {
      id: 'HIS-3',
      visit_datetime: '01-10-2026 14:23:00',
      units: ['INSTALASI GAWAT DARURAT'],
      doctor: 'dr. Bayu Aji Pamungkas, Sp.B',
      subjective: 'Mual muntah 2 kali disertai nyeri ulu hati akut.',
      objective: 'TD: 118/78 mmHg, Nadi: 88x/mnt, Suhu: 36.8 C, Nyeri epigastrium (+)',
      assessment: 'Dispepsia akut tanpa tanda dehidrasi berat.',
      diagnosis: 'K30 - Functional dyspepsia',
      procedures: ['Observasi IGD', 'Pemberian terapi PPI'],
      prescriptions: ['Omeprazole 20 mg (2 x 1 ac)', 'Sucralfate syr (3 x 15 ml)'],
      notes: 'Keluhan membaik pasca observasi 2 jam, pasien dipulangkan.'
    },
    {
      id: 'HIS-4',
      visit_datetime: '29-09-2026 08:07:00',
      units: ['INSTALASI GAWAT DARURAT', 'UNIT RAWAT INAP'],
      doctor: 'dr. Budi Santoso, Sp.PD',
      subjective: 'Demam tinggi hari ke-4 disertai lemas dan nafsu makan menurun.',
      objective: 'TD: 110/70 mmHg, Nadi: 96x/mnt, Suhu: 38.4 C, Trombosit 132.000/uL',
      assessment: 'Observasi febris viral infection dengan trombositopenia ringan.',
      diagnosis: 'R50.9 - Fever, unspecified',
      procedures: ['Pemasangan Infus RL', 'Pemeriksaan Darah Lengkap Serial'],
      prescriptions: ['Paracetamol infus 1g/8 jam', 'Rehidrasi cairan'],
      notes: 'Perawatan inap singkat 2 hari, kondisi klinis membaik dan stabil saat pulang.'
    },
    {
      id: 'HIS-5',
      visit_datetime: '28-09-2026 14:31:00',
      units: ['POLIKLINIK GIGI DAN MULUT'],
      doctor: 'dr. Melani Safitri, Sp.Perio',
      subjective: 'Kontrol kebersihan karang gigi rutin.',
      objective: 'Kalkulus supragingiva ringan regio posterior bawah, mukosa tenang.',
      assessment: 'Gingivitis kronis ringan.',
      diagnosis: 'K05.1 - Chronic gingivitis',
      procedures: ['Scaling & Polishing Gigi'],
      prescriptions: ['Obat kumur antiseptik (2 x 1)'],
      notes: 'Kontrol ulang berkala setiap 6 bulan.'
    }
  ];
};

/**
 * Generate 252 mock examination records deterministically:
 * - Menunggu: 37
 * - Sedang Diperiksa: 18 (first 2 Dipanggil)
 * - Selesai: 193
 * - Dibatalkan: 4
 * Active Queue = 37 + 18 = 55 patients!
 */
export function generateMockExaminations() {
  const records = [];
  const todayStr = new Date().toISOString().split('T')[0];
  const total = 252;
  const waitingTarget = 37;
  const inServiceTarget = 18;
  const finishedTarget = 193;
  const cancelledTarget = 4;

  let waitingCount = 0;
  let inServiceCount = 0;
  let finishedCount = 0;
  let cancelledCount = 0;

  for (let i = 1; i <= total; i++) {
    let status = 'Menunggu';
    let admissionTime = '08:00';
    let waitMinutes = 20;
    let completedTime = null;
    let cancellationReason = null;

    if (i <= waitingTarget) {
      status = 'Menunggu';
      waitingCount++;
      // Wait time ranges between 15 and 85 mins
      waitMinutes = 15 + ((i * 7) % 75);
      const hour = 8 + Math.floor((i * 4) / 60);
      const min = (i * 4) % 60;
      admissionTime = `${String(hour).padStart(2, '0')}:${String(min).padStart(2, '0')}`;
    } else if (i <= waitingTarget + inServiceTarget) {
      inServiceCount++;
      // Make 2 of these 'Dipanggil' to showcase the Dipanggil state
      status = (inServiceCount <= 2) ? 'Dipanggil' : 'Sedang Diperiksa';
      waitMinutes = 25 + ((i * 3) % 40);
      const hour = 8 + Math.floor((i * 3) / 60);
      const min = (i * 3) % 60;
      admissionTime = `${String(hour).padStart(2, '0')}:${String(min).padStart(2, '0')}`;
    } else if (i <= waitingTarget + inServiceTarget + cancelledTarget) {
      status = 'Dibatalkan';
      cancelledCount++;
      waitMinutes = 10 + i;
      admissionTime = `07:${40 + (i % 15)}`;
      const reasons = [
        'Pasien batal / rujukan luar RS jejaring',
        'Pasien tidak hadir setelah 3x pemanggilan antrean (No Show)',
        'Pasien membatalkan permohonan karena dialihkan ke IGD',
        'Keluarga pasien meminta penundaan jadwal periksa'
      ];
      cancellationReason = reasons[(cancelledCount - 1) % reasons.length];
    } else {
      status = 'Selesai';
      finishedCount++;
      waitMinutes = 35 + ((i * 2) % 30);
      const hour = 7 + Math.floor((i * 2) / 60);
      const min = (i * 2) % 60;
      admissionTime = `${String(hour).padStart(2, '0')}:${String(min).padStart(2, '0')}`;
      const finHour = Math.min(hour + 1, 14);
      const finMin = (min + 25) % 60;
      completedTime = `${String(finHour).padStart(2, '0')}:${String(finMin).padStart(2, '0')}`;
    }

    // Patient profile from seeds or generative pool
    const seedIndex = (i - 1) % patientSeeds.length;
    const seed = patientSeeds[seedIndex];
    const fn = poolFirstNames[(i * 3) % poolFirstNames.length];
    const ln = poolLastNames[(i * 5) % poolLastNames.length];
    const isMale = (i % 2 === 1);
    const patientName = i <= 10 ? seed.name : `${fn} ${ln}`;
    const gender = i <= 10 ? seed.gender : (isMale ? 'L' : 'P');
    const birthPlace = i <= 10 ? seed.birthPlace : birthPlacePool[i % birthPlacePool.length];
    const patientAddress = i <= 10 ? seed.address : addressPool[i % addressPool.length];

    // Birthdate calculation
    const baseYear = 1950 + (i % 55);
    const baseMonth = 1 + (i % 12);
    const baseDay = 1 + (i % 28);
    const birthDate = i <= 10
      ? seed.birthDate
      : `${baseYear}-${String(baseMonth).padStart(2, '0')}-${String(baseDay).padStart(2, '0')}`;

    // Priority
    let priority = 'Normal';
    let priorityNote = 'Antrean Reguler Poliklinik';
    if (i <= 10) {
      priority = seed.priority;
      priorityNote = seed.priorityNote;
    } else {
      const pIdx = i % 14;
      if (pIdx === 1) {
        priority = 'Lansia';
        priorityNote = 'Lansia / Risiko Jatuh Ringan';
      } else if (pIdx === 2) {
        priority = 'Prioritas Medis Cito';
        priorityNote = 'Prioritas Medis Cito - Evaluasi Segera';
      } else if (pIdx === 3) {
        priority = 'Pediatri / Balita';
        priorityNote = 'Pediatri / Balita Febris';
      } else if (pIdx === 4 && gender === 'P') {
        priority = 'Ibu Hamil';
        priorityNote = 'Ibu Hamil Trimester 2';
      } else if (pIdx === 5) {
        priority = 'Risiko Jatuh';
        priorityNote = 'Risiko Jatuh Sedang';
      }
    }

    // Polyclinic and Doctor
    const polyclinicName = mockPolyclinics[(i - 1) % mockPolyclinics.length];
    const roomInfo = polyclinicRooms[polyclinicName] || { building: 'Gedung A', floor: 'Lt. 1', room: 'R.101' };
    const doctorName = mockDoctors[(i - 1) % mockDoctors.length];
    const doctorInitials = getDoctorInitials(doctorName);

    // Guarantor
    let guarantorType = 'BPJS Kesehatan';
    let guarantorDetail = 'Mandiri • Faskes 1';
    if (i % 3 === 0) {
      guarantorType = 'Pasien Umum / Tunai';
      guarantorDetail = 'Pembayaran Tunai / QRIS';
    } else if (i % 5 === 0) {
      guarantorType = 'Asuransi Swasta';
      guarantorDetail = 'Mandiri Inhealth Gold';
    } else if (i % 4 === 0) {
      guarantorType = 'BPJS Kesehatan';
      guarantorDetail = 'JKN-PBI Kelas 3';
    }

    // Queue letter and number
    const queuePrefixes = ['A', 'B', 'C', 'D'];
    const prefix = queuePrefixes[(i - 1) % queuePrefixes.length];
    const queueNum = `${prefix}-${String(((i - 1) % 40) + 1).padStart(3, '0')}`;
    const rmNum = `RM-${String(180 + i).padStart(6, '0')}`;
    const dummyNik = `3174${String(100000000000 + i).slice(1)}`;
    const patientNik = i <= 10 ? seed.nik : dummyNik;
    const clinicalNote = i <= 10
      ? seed.clinicalNote
      : 'Pemeriksaan rutin rawat jalan poliklinik spesialis.';

    records.push({
      id: `EXM-${String(i).padStart(4, '0')}`,
      admission_date: todayStr,
      queue_number: queueNum,
      admission_time: admissionTime,
      medical_record_number: rmNum,
      nik: patientNik,
      patient_name: patientName,
      birth_place: birthPlace,
      birth_date: birthDate,
      gender: gender,
      address: patientAddress,
      priority: priority,
      priority_note: priorityNote,
      polyclinic: polyclinicName,
      building: roomInfo.building,
      floor: roomInfo.floor,
      room: roomInfo.room,
      doctor: doctorName,
      doctor_initials: doctorInitials,
      guarantor_type: guarantorType,
      guarantor_detail: guarantorDetail,
      wait_time_minutes: waitMinutes,
      status: status,
      completed_time: completedTime,
      cancellation_reason: cancellationReason,
      clinical_note: clinicalNote,
      allergies: i <= 10 ? [...(seed.allergies || [])] : [],
      visit_history: buildMockVisitHistory(todayStr, admissionTime, polyclinicName, doctorName, clinicalNote),
      // SOAP placeholder for RME demo
      soap: {
        subjective: `Pasien datang dengan keluhan ${clinicalNote}.`,
        objective: 'TD: 120/80 mmHg, N: 82x/mnt, RR: 18x/mnt, SpO2: 98% room air, Suhu: 36.6C.',
        assessment: `Evaluasi poliklinik: ${polyclinicName} - Kondisi stabil terkompensasi baik.`,
        plan: 'Edukasi pola hidup sehat, anjuran kontrol rutin 1 bulan ke depan, resep obat rawat jalan.'
      }
    });
  }

  return records;
}
