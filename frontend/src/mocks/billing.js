// Mock Data Billing Rawat Jalan untuk SIMRS BigSIRS
// Data simulasi fiktif yang aman untuk frontend tanpa backend

export const mockPolyclinics = [
  'Poli Penyakit Dalam',
  'Poli Kebidanan & Kandungan',
  'Poli Bedah Umum',
  'Poli Gigi & Mulut',
  'Poli Anak',
  'Poli Jantung & Pembuluh',
  'Poli Saraf',
  'Poli Mata',
  'Poli THT-KL',
  'Poli Kulit & Kelamin'
];

export const mockDoctors = [
  'dr. Siti Rahma, Sp.PD',
  'dr. Budi Santoso, Sp.PD',
  'dr. Hendra Pratama, Sp.OG',
  'dr. Siti Aminah, Sp.A',
  'dr. Bambang Setyadi, Sp.JP',
  'dr. Rudi Hermawan, Sp.S',
  'dr. Melani Safitri, Sp.Perio',
  'dr. Dian Anggraini, Sp.M',
  'dr. Bayu Aji Pamungkas, Sp.B',
  'dr. Clarissa Amanda, Sp.KK'
];

export const mockGuarantorOptions = [
  'BPJS Kesehatan',
  'Pasien Umum',
  'Mandiri Inhealth',
  'Asuransi Lainnya'
];

export const mockPeriodOptions = [
  { label: 'Hari Ini', value: 'today' },
  { label: 'Kemarin', value: 'yesterday' },
  { label: '7 Hari Terakhir', value: 'last_7_days' },
  { label: '30 Hari Terakhir', value: 'last_30_days' },
  { label: 'Rentang Tanggal', value: 'custom' }
];

export const mockStatusOptions = [
  'Semua',
  'Menunggu Verifikasi',
  'Siap Dibayar',
  'Dibayar Sebagian',
  'Lunas',
  'Belum Lengkap',
  'Dibatalkan'
];

export const mockPaymentMethods = [
  'Semua',
  'Tunai',
  'Transfer',
  'QRIS',
  'EDC'
];

export const mockCashierShift = {
  shift_title: 'Validasi Closing Shift Kasir #1',
  shift_time: '07:00 – 14:00 WIB',
  cashier_username: 'Kasir_Poli_04',
  cashier_fullname: 'Sri Wahyuni (Kasir Loket 04)',
  location: 'Loket Kasir Sentral Rawat Jalan Gedung A',
  total_cash: 12450000,
  total_qris: 8650000,
  total_edc: 18350000,
  total_transfer: 8800000,
  total_income: 48250000,
  yesterday_income: 42180000,
  growth_percentage: '+14,2%',
  cash_variance: 0 // Selisih kas simulasi Rp 0
};

// Seed profiles for realistic top table entries
const patientSeeds = [
  // 1. Reference Patient 1
  {
    name: 'Ahmad Pratama',
    gender: 'L',
    age: 40,
    nik: '3174012004860001',
    poly: 'Poli Penyakit Dalam',
    doctor: 'dr. Siti Rahma, Sp.PD',
    guarantorType: 'BPJS Non-PBI',
    guarantorDetail: 'Mandiri • Faskes 1',
    sepStatus: 'SEP Terbit',
    total: 625000,
    guarantorAmount: 625000,
    paid: 0,
    patientNote: '100% Ditanggung',
    status: 'Menunggu Verifikasi',
    method: null
  },
  // 2. Reference Patient 2
  {
    name: 'H. Soedirman Wibowo',
    gender: 'L',
    age: 74,
    nik: '3174021204520002',
    poly: 'Poli Penyakit Dalam',
    doctor: 'dr. Budi Santoso, Sp.PD',
    guarantorType: 'Pasien Umum / Tunai',
    guarantorDetail: 'Pembayaran Tunai / EDC',
    sepStatus: 'Non-BPJS',
    total: 450000,
    guarantorAmount: 0,
    paid: 0,
    patientNote: 'Menunggu Kasir',
    status: 'Siap Dibayar',
    method: null
  },
  // 3. Reference Patient 3
  {
    name: 'Ny. Hj. Siti Fatimah',
    gender: 'P',
    age: 65,
    nik: '3174035908610003',
    poly: 'Poli Jantung & Pembuluh',
    doctor: 'dr. Bambang Setyadi, Sp.JP',
    guarantorType: 'BPJS PBI',
    guarantorDetail: 'JKN-PBI Kelas 3',
    sepStatus: 'SEP Terbit',
    total: 1850000,
    guarantorAmount: 1850000,
    paid: 0,
    patientNote: 'Ter-Klaim Penuh',
    status: 'Lunas',
    method: 'Transfer'
  },
  // 4. Reference Patient 4 (Ekses Asuransi)
  {
    name: 'Tn. Raden Mas Hardjodipuro',
    gender: 'L',
    age: 77,
    nik: '3174060501490004',
    poly: 'Poli Saraf',
    doctor: 'dr. Rudi Hermawan, Sp.S',
    guarantorType: 'Mandiri Inhealth',
    guarantorDetail: 'Gold Coverage',
    sepStatus: 'Klaim Inhealth',
    total: 980000,
    guarantorAmount: 860000,
    paid: 0,
    patientNote: 'Ekses Obat Non-Cover',
    status: 'Dibayar Sebagian',
    method: null
  },
  // 5. Reference Patient 5 (Belum Lengkap)
  {
    name: 'Ny. Dian Kartika Sari',
    gender: 'P',
    age: 31,
    nik: '3174056311950005',
    poly: 'Poli Kebidanan & Kandungan',
    doctor: 'dr. Hendra Pratama, Sp.OG',
    guarantorType: 'BPJS Non-PBI',
    guarantorDetail: 'Mandiri • Faskes 1',
    sepStatus: 'SEP Verifikasi',
    total: 720000,
    guarantorAmount: 720000,
    paid: 0,
    patientNote: 'Menunggu E-Klaim',
    status: 'Belum Lengkap',
    method: null
  },
  // 6. Reference Patient 6 (Lunas Umum)
  {
    name: 'Nn. Clarissa Amanda Putri',
    gender: 'P',
    age: 25,
    nik: '3174077006010006',
    poly: 'Poli Gigi & Mulut',
    doctor: 'dr. Melani Safitri, Sp.Perio',
    guarantorType: 'Pasien Umum / Tunai',
    guarantorDetail: 'Mandiri / Pribadi',
    sepStatus: 'Non-BPJS',
    total: 520000,
    guarantorAmount: 0,
    paid: 520000,
    patientNote: 'Lunas',
    status: 'Lunas',
    method: 'QRIS'
  },
  // 7. Reference Patient 7 (Dibatalkan)
  {
    name: 'Tn. Sugiyanto Subagyo',
    gender: 'L',
    age: 58,
    nik: '3174090812680007',
    poly: 'Poli Bedah Umum',
    doctor: 'dr. Bayu Aji Pamungkas, Sp.B',
    guarantorType: 'Pasien Umum / Tunai',
    guarantorDetail: 'Tunai',
    sepStatus: 'Batal Pelayanan',
    total: 350000,
    guarantorAmount: 0,
    paid: 0,
    patientNote: 'Tagihan Batal',
    status: 'Dibatalkan',
    method: null
  }
];

const poolFirstNames = [
  'Ahmad', 'Bambang', 'Siti', 'Dewi', 'Hendra', 'Putri', 'I Made', 'Sri',
  'Muhammad', 'Endang', 'Sugiyanto', 'Anisa', 'Yohanes', 'Nabila', 'Andi',
  'Riana', 'Ferry', 'Tuti', 'Bayu', 'Clarissa', 'Hardjodipuro', 'Melani',
  'Zaky', 'Theresia', 'Rudi', 'Laksmi', 'Stephanie', 'Dimas', 'Eko',
  'Nur', 'Farhan', 'Rina', 'Agus', 'Wahyudi', 'Tri', 'Ratna', 'Dian', 'Reza'
];

const poolLastNames = [
  'Pratama', 'Setyadi', 'Rahmawati', 'Lestari', 'Kusuma', 'Anindita', 'Suardana',
  'Wahyuni', 'Rizky', 'Sulistiyowati', 'Azizah', 'Wibowo', 'Syahrani', 'Baso',
  'Hutapea', 'Sanjaya', 'Maryati', 'Pamungkas', 'Permata', 'Safitri', 'Al Ghifari',
  'Anggraini', 'Hermawan', 'Dewi', 'Santoso', 'Tan', 'Hidayat', 'Saputra',
  'Wijaya', 'Siregar', 'Kurniawan', 'Nugroho', 'Subagyo', 'Purwanto'
];

/**
 * Generate 248 mock billing records deterministically matching all statistics & chips:
 * Total: 248
 * - Menunggu Verifikasi: 18 (4 Inhealth, 14 BPJS)
 * - Siap Dibayar: 24 (6 Lansia, 18 Tunai/EDC)
 * - Lunas: 193
 * - Belum Lunas: 9 (5 Dibayar Sebagian + 4 Belum Lengkap, total ekses Rp 3.420.000)
 * - Dibatalkan: 4
 *
 * Quick filters:
 * - Semua Tagihan: 248
 * - Siap Bayar Kasir: 24
 * - Verifikasi BPJS: 18 (Menunggu Verifikasi with BPJS)
 * - Pasien Umum Hari Ini: 57
 * - Perlu Penyesuaian Tarif: 3
 */
export function generateMockBillingRecords() {
  const records = [];
  const total = 248;

  const targetWaiting = 18;
  const targetReady = 24;
  const targetPending = 9; // Belum Lunas
  const targetCancelled = 4;
  const targetPaid = 193;

  let generalCount = 0; // count of Pasien Umum
  let tariffAdjustCount = 0; // count of needs tariff adjustment

  for (let i = 1; i <= total; i++) {
    let status = 'Lunas';
    let guarantorType = 'BPJS Non-PBI';
    let guarantorDetail = 'Mandiri • Faskes 1';
    let sepStatus = 'SEP Terbit';
    let totalAmount = 625000;
    let guarantorAmount = 625000;
    let paidAmount = 0;
    let patientNote = '100% Ditanggung';
    let paymentMethod = 'QRIS';
    let priority = 'Normal';
    let cancellationReason = null;
    let needsTariffAdjustment = false;

    // Service Time
    const hour = 8 + Math.floor((i * 3) / 60);
    const min = (i * 7) % 60;
    const serviceTime = `${String(hour).padStart(2, '0')}:${String(min).padStart(2, '0')} WIB`;

    if (i <= targetWaiting) {
      // 18 records: Menunggu Verifikasi
      status = 'Menunggu Verifikasi';
      // 4 Inhealth, 14 BPJS
      if (i <= 4) {
        guarantorType = 'Mandiri Inhealth';
        guarantorDetail = 'Gold Coverage';
        sepStatus = 'Klaim Inhealth';
        totalAmount = 820000;
        guarantorAmount = 700000; // Ekses 120.000
        patientNote = 'Ekses Obat Non-Cover';
      } else {
        guarantorType = i % 2 === 0 ? 'BPJS Non-PBI' : 'BPJS PBI';
        guarantorDetail = i % 2 === 0 ? 'Mandiri • Faskes 1' : 'JKN-PBI Kelas 3';
        sepStatus = 'SEP Terbit';
        totalAmount = 650000 + (i * 20000);
        guarantorAmount = totalAmount;
        patientNote = 'Menunggu E-Klaim';
      }
      paidAmount = 0;
      paymentMethod = null;
    } else if (i <= targetWaiting + targetReady) {
      // 24 records: Siap Dibayar
      status = 'Siap Dibayar';
      const readyIndex = i - targetWaiting;
      // 6 Lansia, 18 Tunai/EDC
      if (readyIndex <= 6) {
        priority = 'Lansia';
      }
      guarantorType = 'Pasien Umum / Tunai';
      guarantorDetail = readyIndex % 2 === 0 ? 'Pembayaran Tunai' : 'Debit EDC Mandiri';
      sepStatus = 'Non-BPJS';
      generalCount++;
      totalAmount = 450000 + ((readyIndex * 15000) % 200000);
      guarantorAmount = 0;
      paidAmount = 0;
      patientNote = 'Menunggu Kasir';
      paymentMethod = null;
    } else if (i <= targetWaiting + targetReady + targetPending) {
      // 9 records: Belum Lunas (Dibayar Sebagian & Belum Lengkap)
      const pIdx = i - targetWaiting - targetReady;
      if (pIdx <= 5) {
        status = 'Dibayar Sebagian';
        guarantorType = 'Mandiri Inhealth';
        guarantorDetail = 'Silver Plan';
        sepStatus = 'Klaim Inhealth';
        totalAmount = 950000;
        guarantorAmount = 570000;
        // Total ekses Rp 3.420.000 across pending records
        const excess = 380000;
        guarantorAmount = totalAmount - excess;
        paidAmount = 100000; // paid partial
        patientNote = 'Ekses Asuransi Sebagian';
        paymentMethod = 'Tunai';
      } else {
        status = 'Belum Lengkap';
        guarantorType = 'BPJS Non-PBI';
        guarantorDetail = 'Mandiri • Faskes 1';
        sepStatus = 'Resume Belum TTD';
        totalAmount = 1100000;
        guarantorAmount = 720000;
        paidAmount = 0;
        patientNote = 'Resume Ditahan';
        paymentMethod = null;
      }
    } else if (i <= targetWaiting + targetReady + targetPending + targetCancelled) {
      // 4 records: Dibatalkan
      status = 'Dibatalkan';
      guarantorType = 'Pasien Umum / Tunai';
      guarantorDetail = 'Tunai';
      sepStatus = 'Batal';
      generalCount++;
      totalAmount = 350000;
      guarantorAmount = 0;
      paidAmount = 0;
      patientNote = 'Tagihan Batal';
      paymentMethod = null;
      const reasons = [
        'Pasien membatalkan konsultasi karena dialihkan ke IGD Rumah Sakit',
        'Pemeriksaan dibatalkan atas permintaan keluarga pasien',
        'Salah input admisi poliklinik (Duplikasi Tagihan)',
        'Pasien tidak hadir setelah dipanggil ke poli (No Show)'
      ];
      cancellationReason = reasons[(i - 1) % reasons.length];
    } else {
      // 193 records: Lunas
      status = 'Lunas';
      const paidIdx = i - (targetWaiting + targetReady + targetPending + targetCancelled);
      if (paidIdx % 5 === 0 && generalCount < 57) {
        guarantorType = 'Pasien Umum / Tunai';
        guarantorDetail = 'Tunai / QRIS';
        sepStatus = 'Non-BPJS';
        generalCount++;
        totalAmount = 350000 + ((paidIdx * 25000) % 300000);
        guarantorAmount = 0;
        paidAmount = totalAmount;
        patientNote = 'Lunas Kasir';
        paymentMethod = paidIdx % 2 === 0 ? 'QRIS' : 'Tunai';
      } else if (paidIdx % 3 === 0) {
        guarantorType = 'BPJS PBI';
        guarantorDetail = 'JKN-PBI Kelas 3';
        sepStatus = 'SEP Terbit';
        totalAmount = 580000 + ((paidIdx * 15000) % 400000);
        guarantorAmount = totalAmount;
        paidAmount = 0;
        patientNote = 'Ter-Klaim Penuh';
        paymentMethod = 'Transfer';
      } else {
        guarantorType = 'BPJS Non-PBI';
        guarantorDetail = 'Mandiri • Faskes 1';
        sepStatus = 'SEP Terbit';
        totalAmount = 620000 + ((paidIdx * 30000) % 500000);
        guarantorAmount = totalAmount;
        paidAmount = 0;
        patientNote = 'Ter-Klaim Penuh';
        paymentMethod = paidIdx % 3 === 0 ? 'EDC' : 'Transfer';
      }
    }

    // Flag 3 records for "Perlu Penyesuaian Tarif"
    if (tariffAdjustCount < 3 && (i === 12 || i === 25 || i === 48)) {
      needsTariffAdjustment = true;
      tariffAdjustCount++;
    }

    // Patient profile from seeds or pool
    const seed = patientSeeds[(i - 1) % patientSeeds.length];
    const fn = poolFirstNames[(i * 3) % poolFirstNames.length];
    const ln = poolLastNames[(i * 5) % poolLastNames.length];
    const patientName = i <= 7 ? seed.name : `${fn} ${ln}`;
    const gender = i <= 7 ? seed.gender : (i % 2 === 1 ? 'L' : 'P');
    const age = i <= 7 ? seed.age : (20 + (i % 55));
    const birthYear = 2026 - age;
    const birthDate = `${birthYear}-${String(1 + (i % 12)).padStart(2, '0')}-${String(1 + (i % 28)).padStart(2, '0')}`;
    const nik = i <= 7 ? seed.nik : `3174${String(100000000000 + i).slice(1)}`;

    const polyclinic = mockPolyclinics[(i - 1) % mockPolyclinics.length];
    const doctor = mockDoctors[(i - 1) % mockDoctors.length];

    const invoiceNum = `INV-RJ-20260912-${String(i).padStart(4, '0')}`;
    const visitNum = `#KJ-${String(90800 + i)}`;
    const rmNum = `RM-${String(240 + i).padStart(6, '0')}`;

    // Financial calculations
    const patientLiability = Math.max(0, totalAmount - guarantorAmount);
    const remainingBalance = Math.max(0, patientLiability - paidAmount);

    // Itemized fees breakdown for Detail Tagihan & Kuitansi
    const consultationFee = Math.round(totalAmount * 0.35);
    const labFee = Math.round(totalAmount * 0.25);
    const pharmacyFee = Math.round(totalAmount * 0.30);
    const adminFee = totalAmount - (consultationFee + labFee + pharmacyFee);

    records.push({
      id: `BILL-${String(i).padStart(4, '0')}`,
      invoice_number: invoiceNum,
      visit_number: visitNum,
      service_date: '2026-09-12',
      service_time: serviceTime,
      medical_record_number: rmNum,
      nik: nik,
      patient_name: patientName,
      gender: gender,
      age: age,
      birth_date: birthDate,
      priority: priority,
      polyclinic: polyclinic,
      doctor: doctor,
      guarantor_type: guarantorType,
      guarantor_detail: guarantorDetail,
      sep_status: sepStatus,
      total_amount: totalAmount,
      guarantor_amount: guarantorAmount,
      patient_responsibility: patientLiability,
      paid_amount: paidAmount,
      remaining_balance: remainingBalance,
      patient_note: patientNote,
      payment_method: paymentMethod,
      status: status,
      cancellation_reason: cancellationReason,
      needs_tariff_adjustment: needsTariffAdjustment,
      items: [
        {
          name: `Konsultasi & Pemeriksaan ${polyclinic}`,
          category: 'Jasa Medis DPJP',
          qty: 1,
          unit_price: consultationFee,
          total: consultationFee
        },
        {
          name: 'Pemeriksaan Laboratorium & Diagnostik Rutin',
          category: 'Laboratorium',
          qty: 1,
          unit_price: labFee,
          total: labFee
        },
        {
          name: 'Paket Obat Resep Farmasi Rawat Jalan',
          category: 'Farmasi & Alkes',
          qty: 1,
          unit_price: pharmacyFee,
          total: pharmacyFee
        },
        {
          name: 'Biaya Administrasi & Rekam Medis Elektronik',
          category: 'Administrasi',
          qty: 1,
          unit_price: adminFee,
          total: adminFee
        }
      ]
    });
  }

  return records;
}
