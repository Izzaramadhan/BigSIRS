// Mock Data Admisi Rawat Jalan untuk SIMRS BigSIRS
// Data simulasi fiktif yang aman untuk pengembangan frontend tanpa backend

export const mockPolyclinics = [
  'Poli Penyakit Dalam',
  'Poli Umum',
  'Poli Anak',
  'Poli Gigi & Mulut',
  'Poli Kandungan',
  'Poli Mata',
  'Poli Saraf',
  'Poli Jantung & Pembuluh Darah',
  'Poli THT-KL',
  'Poli Bedah Umum',
  'Poli Kulit & Kelamin',
  'Poli Paru',
  'Poli Ortopedi',
  'Poli Jiwa / Psikiatri'
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

export const mockGuarantors = [
  { label: 'BPJS Kesehatan', detail: 'JKN-Non PBI' },
  { label: 'BPJS Kesehatan', detail: 'JKN-PBI' },
  { label: 'Pasien Umum / Mandiri', detail: 'Mandiri / Pribadi' },
  { label: 'Asuransi Swasta', detail: 'Mandiri Inhealth' },
  { label: 'Asuransi Swasta', detail: 'Prudential' },
  { label: 'Asuransi Swasta', detail: 'Allianz' }
];

export const mockLoketInfo = {
  name: 'Loket Admisi 02',
  description: 'Loket Utama Rawat Jalan',
  status: 'Aktif',
  officer: 'Admin SISFOMEDIKA',
  location: 'Gedung A, Lantai 1 (Front Office)',
  audioStatus: 'Audio Online (Mode Simulasi)'
};

// Realistic mock patient pool for generating 248 admissions
const firstNames = [
  'Ahmad', 'Bambang', 'Siti', 'Dewi', 'Hendra', 'Putri', 'I Made', 'Sri',
  'Muhammad', 'Endang', 'Sugiyanto', 'Anisa', 'Yohanes', 'Nabila', 'Andi',
  'Riana', 'Ferry', 'Tuti', 'Bayu', 'Clarissa', 'Hardjodipuro', 'Melani',
  'Zaky', 'Theresia', 'Rudi', 'Laksmi', 'Stephanie', 'Dimas', 'Eko',
  'Nur', 'Farhan', 'Rina', 'Agus', 'Wahyudi', 'Tri', 'Ratna', 'Dian', 'Reza'
];

const lastNames = [
  'Pratama', 'Setyadi', 'Rahmawati', 'Lestari', 'Kusuma', 'Anindita', 'Suardana',
  'Wahyuni', 'Rizky', 'Sulistiyowati', 'Azizah', 'Wibowo', 'Syahrani', 'Baso',
  'Hutapea', 'Sanjaya', 'Maryati', 'Pamungkas', 'Permata', 'Safitri', 'Al Ghifari',
  'Anggraini', 'Hermawan', 'Dewi', 'Santoso', 'Tan', 'Hidayat', 'Saputra',
  'Wijaya', 'Siregar', 'Kurniawan', 'Nugroho', 'Subagyo', 'Purwanto'
];

const birthPlaces = [
  'Yogyakarta', 'Sleman', 'Bantul', 'Semarang', 'Surabaya', 'Jakarta', 'Solo', 'Magelang', 'Sumbawa', 'Purworejo'
];

const sampleAddresses = [
  'Jl. Wiyoro No. 1, Wiyoro Baru, Baturetno, Banguntapan',
  'Jl. Malioboro No. 45, Sosromenduran, Gedongtengen, Yogyakarta',
  'Jl. Kaliurang Km 5.2 No. 18, Caturtunggal, Depok, Sleman',
  'Jl. Parangtritis No. 82, Brontokusuman, Mergangsan, Yogyakarta',
  'Jl. Solo Km 7 No. 12, Maguwoharjo, Depok, Sleman',
  'Jl. Bantul Km 4 No. 33, Dongkelan, Sewon, Bantul',
  'Jl. Gejayan No. 21, Condongcatur, Depok, Sleman',
  'Jl. Kusumanegara No. 54, Muja Muju, Umbulharjo, Yogyakarta',
  'Jl. Magelang Km 6 No. 9, Sinduadi, Mlati, Sleman',
  'Jl. Brigjen Katamso No. 14, Prawirodirjan, Gondomanan, Yogyakarta'
];

const clinicalNotes = [
  'Kontrol rutin hipertensi & cek lab berkala',
  'Demam tinggi 3 hari disertai batuk pilek',
  'Pemeriksaan USG kehamilan trimester 2',
  'Konsultasi scaling gigi & penambalan karies',
  'Kontrol pasca rawat inap penyakit dalam',
  'Nyeri dada berkala saat aktivitas fisik',
  'Keluhan pandangan kabur pada mata kanan',
  'Pusing berputar (vertigo) dan mual',
  'Nyeri sendi lutut dan bahu kanan',
  'Pemeriksaan spirometri & evaluasi asma',
  'Pemeriksaan rutin bayi dan imunisasi DPT',
  'Pemeriksaan dermatologi alergi ruam kulit'
];

/**
 * Generate 248 mock admissions deterministically matching the statistics requirements:
 * Total: 248
 * Menunggu: 37
 * Sedang Diperiksa: 18
 * Selesai: 193
 */
function generateMockAdmissions() {
  const items = [];

  // Current today date in ISO YYYY-MM-DD
  const today = new Date();
  const formatIsoDate = (d) => d.toISOString().split('T')[0];
  const todayStr = formatIsoDate(today);

  const yesterday = new Date(today);
  yesterday.setDate(today.getDate() - 1);
  const yesterdayStr = formatIsoDate(yesterday);

  const threeDaysAgo = new Date(today);
  threeDaysAgo.setDate(today.getDate() - 3);
  const threeDaysAgoStr = formatIsoDate(threeDaysAgo);

  const tenDaysAgo = new Date(today);
  tenDaysAgo.setDate(today.getDate() - 10);
  const tenDaysAgoStr = formatIsoDate(tenDaysAgo);

  // Define counts for today's status matching exact specs:
  // Exactly 37 Menunggu, 18 Sedang Diperiksa, 193 Selesai = 248
  // item24 is Sedang Diperiksa and item25 is Menunggu, so the remaining 246 items get:
  // 36 Menunggu, 17 Sedang Diperiksa, 193 Selesai
  const queueLetters = ['A', 'B', 'C', 'D'];

  for (let i = 1; i <= 248; i++) {
    const fn = firstNames[(i * 7) % firstNames.length];
    const ln = lastNames[(i * 11) % lastNames.length];
    const fullName = `${fn} ${ln}`;
    const gender = i % 2 === 0 ? 'L' : 'P';
    const birthYear = 1950 + (i * 13) % 55; // 1950 - 2005
    const birthMonth = String(1 + (i % 12)).padStart(2, '0');
    const birthDay = String(1 + ((i * 3) % 28)).padStart(2, '0');
    const birthDate = `${birthYear}-${birthMonth}-${birthDay}`;
    const age = today.getFullYear() - birthYear;

    const rmNumber = `RM-${String(1000 + i).padStart(6, '0')}`;
    const nik = `3471${String(100000000000 + i * 98765).slice(0, 12)}`;

    const poly = mockPolyclinics[i % mockPolyclinics.length];
    const doc = mockDoctors[i % mockDoctors.length];
    const guarantor = mockGuarantors[i % mockGuarantors.length];

    // Initial status distribution
    let status = 'Selesai';
    if (i <= 36) {
      status = 'Menunggu';
    } else if (i <= 36 + 17) {
      status = 'Sedang Diperiksa';
    }

    // General Consent status
    let generalConsent = 'Sudah TTD';
    if (status === 'Menunggu' && i % 3 === 0) {
      generalConsent = 'Belum TTD';
    } else if (status === 'Menunggu' && i % 5 === 0) {
      generalConsent = 'Belum Dibuat';
    }

    // Queue Letter & Sequence
    // Priority queues A (Umum & Penyakit Dalam), B (Anak & Kandungan), C (Gigi & Spesialis), D (Lainnya)
    const letter = queueLetters[i % queueLetters.length];
    const numInLetter = Math.ceil(i / 4);
    const queueNo = `${letter}-${String(numInLetter).padStart(3, '0')}`;

    // Admission Time
    const hour = 7 + Math.floor((i - 1) / 35); // 07:00 to 14:00
    const minute = ((i * 7) % 60);
    const timeFormatted = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')} WIB`;

    // Date distribution: majority today (to hit exactly 248 for today in mock aggregate),
    // but we support date property
    let itemDate = todayStr;
    if (i > 240) {
      // 8 records distributed across yesterday and past days for filter demonstration
      if (i % 3 === 0) itemDate = yesterdayStr;
      else if (i % 3 === 1) itemDate = threeDaysAgoStr;
      else itemDate = tenDaysAgoStr;
    }

    items.push({
      id: i,
      admission_date: itemDate,
      queue_number: queueNo,
      admission_time: timeFormatted,
      medical_record_number: rmNumber,
      nik: nik,
      patient_name: fullName,
      gender: gender,
      birth_place: birthPlaces[i % birthPlaces.length],
      birth_date: birthDate,
      age: age,
      phone: `08${1 + (i % 9)}${String(10000000 + i * 3421).slice(0, 8)}`,
      address: sampleAddresses[i % sampleAddresses.length],
      polyclinic: poly,
      doctor: doc,
      guarantor_type: guarantor.label,
      guarantor_detail: guarantor.detail,
      insurance_number: guarantor.label === 'BPJS Kesehatan'
        ? `000182736${String(1000 + i).slice(-4)}`
        : (guarantor.label === 'Asuransi Swasta' ? `INS-${String(50000 + i)}` : ''),
      referral_source: i % 3 === 0 ? 'FKT Primer' : 'Datang Sendiri',
      referral_name: i % 3 === 0 ? 'Puskesmas Gondokusuman I' : '',
      visit_type: 'Rawat Jalan',
      case_type: i % 2 === 0 ? 'Pemeriksaan Kasus Baru' : 'Pemeriksaan Kasus Lama',
      general_consent: generalConsent,
      status: status, // 'Menunggu' | 'Sedang Diperiksa' | 'Selesai' | 'Dibatalkan'
      notes: clinicalNotes[i % clinicalNotes.length],
      emergency_contact: {
        name: `Keluarga ${ln}`,
        relationship: i % 2 === 0 ? 'Saudara' : 'Istri / Suami',
        phone: `0812-9988-${String(1000 + i).slice(0, 4)}`,
        address: sampleAddresses[(i + 2) % sampleAddresses.length]
      }
    });
  }

  // Ensure record A-024 and A-025 exist prominently for queue demonstration as per prompt specs
  // Find or format item around A-024
  const item24 = items.find(it => it.queue_number === 'A-024');
  if (item24) {
    item24.admission_date = todayStr;
    item24.status = 'Sedang Diperiksa';
    item24.admission_time = '09:30 WIB';
    item24.patient_name = 'Ahmad Pratama';
    item24.gender = 'L';
    item24.birth_date = '1986-05-12';
    item24.medical_record_number = 'RM-000245';
    item24.polyclinic = 'Poli Penyakit Dalam';
    item24.doctor = 'dr. Budi Santoso, Sp.PD';
    item24.guarantor_type = 'BPJS Kesehatan';
    item24.guarantor_detail = 'JKN-Non PBI';
    item24.general_consent = 'Sudah TTD';
  }

  const item25 = items.find(it => it.queue_number === 'A-025');
  if (item25) {
    item25.admission_date = todayStr;
    item25.status = 'Menunggu';
    item25.admission_time = '09:35 WIB';
    item25.patient_name = 'Siti Rahmawati';
    item25.gender = 'P';
    item25.birth_date = '1992-08-15';
    item25.medical_record_number = 'RM-000246';
    item25.polyclinic = 'Poli Penyakit Dalam';
    item25.doctor = 'dr. Budi Santoso, Sp.PD';
    item25.guarantor_type = 'BPJS Kesehatan';
    item25.guarantor_detail = 'JKN-PBI';
    item25.general_consent = 'Sudah TTD';
  }

  return items;
}

export const mockAdmissions = generateMockAdmissions();

/**
 * Summary statistics matching Section 4
 */
export function getMockStatistics(admissionsList = mockAdmissions) {
  // Compute dynamically from current dataset
  const total = admissionsList.length;
  const waiting = admissionsList.filter(a => a.status === 'Menunggu').length;
  const inService = admissionsList.filter(a => a.status === 'Sedang Diperiksa').length;
  const completed = admissionsList.filter(a => a.status === 'Selesai').length;
  const cancelled = admissionsList.filter(a => a.status === 'Dibatalkan').length;

  return {
    total: 248, // Base benchmark for today
    currentFilteredTotal: total,
    waiting: waiting || 37,
    inService: inService || 18,
    completed: completed || 193,
    cancelled: cancelled,
    completionPercentage: '77,8%'
  };
}
