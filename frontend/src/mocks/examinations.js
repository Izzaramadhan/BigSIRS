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
    birthDate: '1952-04-12', // 74 yrs in 2026
    nik: '3174021204520001',
    priority: 'Lansia',
    priorityNote: 'Lansia / Risiko Jatuh',
    poly: 'Poli Penyakit Dalam',
    doctor: 'dr. Budi Santoso, Sp.PD',
    guarantorType: 'BPJS Kesehatan',
    guarantorDetail: 'Mandiri • Faskes 1',
    clinicalNote: 'Kontrol berkala hipertensi stadium 2 & keluhan sesak nafas ringan saat berbaring'
  },
  // 2. Reference Patient 2 (Cito)
  {
    name: 'Ny. Hj. Siti Fatimah',
    gender: 'P',
    birthDate: '1961-08-19',
    nik: '3174035908610002',
    priority: 'Prioritas Medis Cito',
    priorityNote: 'Prioritas Medis Cito - Nyeri Dada Akut',
    poly: 'Poli Jantung & Vaskular',
    doctor: 'dr. Bambang Setyadi, Sp.JP',
    guarantorType: 'BPJS Kesehatan',
    guarantorDetail: 'JKN-PBI Kelas 3',
    clinicalNote: 'Angina pectoris tidak stabil, rujukan puskesmas dengan rekomendasi observasi EKG segera'
  },
  // 3. Reference Patient 3 (Pediatri)
  {
    name: 'An. Rafka Alvaro Pratama',
    gender: 'L',
    birthDate: '2022-09-14',
    nik: '3174041409220003',
    priority: 'Pediatri / Balita',
    priorityNote: 'Balita / Febris Hari ke-3',
    poly: 'Poli Anak & Tumbuh Kembang',
    doctor: 'dr. Siti Aminah, Sp.A',
    guarantorType: 'Pasien Umum / Tunai',
    guarantorDetail: 'Tunai / QRIS',
    clinicalNote: 'Demam 38.8C naik turun, batuk kering dan nafsu makan menurun'
  },
  // 4. Reference Patient 4 (Ibu Hamil)
  {
    name: 'Ny. Dian Kartika Sari',
    gender: 'P',
    birthDate: '1995-11-23',
    nik: '3174056311950004',
    priority: 'Ibu Hamil',
    priorityNote: 'Trimester 3 / Evaluasi Letak Sungsang',
    poly: 'Poli Kandungan',
    doctor: 'dr. Hendra Pratama, Sp.OG',
    guarantorType: 'BPJS Kesehatan',
    guarantorDetail: 'Mandiri • Faskes 1',
    clinicalNote: 'Pemeriksaan USG antenatal care kehamilan 34 minggu'
  },
  // 5. Reference Patient 5 (Risiko Jatuh)
  {
    name: 'Tn. Raden Mas Hardjodipuro',
    gender: 'L',
    birthDate: '1949-01-05',
    nik: '3174060501490005',
    priority: 'Risiko Jatuh',
    priorityNote: 'Risiko Jatuh Tinggi / Pasca Stroke Iskemik',
    poly: 'Poli Saraf',
    doctor: 'dr. Rudi Hermawan, Sp.S',
    guarantorType: 'Asuransi Swasta',
    guarantorDetail: 'Mandiri Inhealth Gold',
    clinicalNote: 'Hemiparesis dextra kontrol rehabilitasi medik dan penyesuaian terapi antiplatelet'
  },
  // 6. Reference Patient 6 (Gigi)
  {
    name: 'Nn. Clarissa Amanda Putri',
    gender: 'P',
    birthDate: '2001-06-30',
    nik: '3174077006010006',
    priority: 'Normal',
    priorityNote: 'Antrean Reguler Poliklinik',
    poly: 'Poli Gigi & Mulut',
    doctor: 'dr. Melani Safitri, Sp.Perio',
    guarantorType: 'Pasien Umum / Tunai',
    guarantorDetail: 'Pembayaran Debit EDC',
    clinicalNote: 'Gingivitis marginalis kronis & konsultasi rencana tindakan scaling'
  },
  // 7. Reference Patient 7 (Mata)
  {
    name: 'Tn. Ahmad Fauzi Ridwan',
    gender: 'L',
    birthDate: '1970-03-15',
    nik: '3174081503700007',
    priority: 'Normal',
    priorityNote: 'Antrean Reguler Poliklinik',
    poly: 'Poli Mata',
    doctor: 'dr. Dian Anggraini, Sp.M',
    guarantorType: 'BPJS Kesehatan',
    guarantorDetail: 'JKN-Non PBI',
    clinicalNote: 'Visus menurun mata kanan, evaluasi katarak senilis imatur'
  },
  // 8. Reference Patient 8 (Paru)
  {
    name: 'Tn. Sugiyanto Subagyo',
    gender: 'L',
    birthDate: '1968-12-08',
    nik: '3174090812680008',
    priority: 'Normal',
    priorityNote: 'Antrean Reguler Poliklinik',
    poly: 'Poli Paru',
    doctor: 'dr. Ahmad Fauzi Ridwan, Sp.P',
    guarantorType: 'BPJS Kesehatan',
    guarantorDetail: 'Mandiri • Faskes 1',
    clinicalNote: 'Kontrol rutin program OAT fase lanjutan bulan ke-4'
  },
  // 9. Reference Patient 9 (Bedah)
  {
    name: 'Tn. Ferry Sanjaya Pratama',
    gender: 'L',
    birthDate: '1984-07-22',
    nik: '3174102207840009',
    priority: 'Normal',
    priorityNote: 'Antrean Reguler Poliklinik',
    poly: 'Poli Bedah Umum',
    doctor: 'dr. Bayu Aji Pamungkas, Sp.B',
    guarantorType: 'Asuransi Swasta',
    guarantorDetail: 'Prudential Prime Healthcare',
    clinicalNote: 'Evaluasi benjolan lipoma regio scapula sinistra rencana eksisi'
  },
  // 10. Reference Patient 10 (Kulit)
  {
    name: 'Ny. Melani Safitri Dewi',
    gender: 'P',
    birthDate: '1992-05-18',
    nik: '3174115805920010',
    priority: 'Normal',
    priorityNote: 'Antrean Reguler Poliklinik',
    poly: 'Poli Kulit & Kelamin',
    doctor: 'dr. Clarissa Amanda, Sp.KK',
    guarantorType: 'Pasien Umum / Tunai',
    guarantorDetail: 'Tunai / Cash',
    clinicalNote: 'Dermatitis kontak alergika pada kedua telapak tangan'
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

    records.push({
      id: `EXM-${String(i).padStart(4, '0')}`,
      queue_number: queueNum,
      admission_time: admissionTime,
      medical_record_number: rmNum,
      nik: patientNik,
      patient_name: patientName,
      birth_date: birthDate,
      gender: gender,
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
      clinical_note: seed.clinicalNote || 'Pemeriksaan rutin rawat jalan poliklinik spesialis.',
      // SOAP placeholder for RME demo
      soap: {
        subjective: `Pasien datang dengan keluhan ${seed.clinicalNote || 'keluhan poliklinik rutin'}. Tidak ada riwayat alergi obat berat.`,
        objective: 'TD: 120/80 mmHg, N: 82x/mnt, RR: 18x/mnt, SpO2: 98% room air, Suhu: 36.6C.',
        assessment: `Evaluasi poliklinik: ${polyclinicName} - Kondisi stabil terkompensasi baik.`,
        plan: 'Edukasi pola hidup sehat, anjuran kontrol rutin 1 bulan ke depan, resep obat rawat jalan.'
      }
    });
  }

  return records;
}
