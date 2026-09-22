// Development-only dashboard fixture.
// This data must never be presented as real hospital production data.

export const dashboardMock = {
  lastUpdated: '09:45',
  metrics: {
    kunjunganHariIni: 248,
    pasienBaru: 42,
    pasienLama: 206,
    menunggu: 37,
    sedangDiperiksa: 18,
    pelayananSelesai: 193
  },
  visitTrend: {
    labels: ['06 Sep', '07 Sep', '08 Sep', '09 Sep', '10 Sep', '11 Sep (Puncak)', '12 Sep (Hari Ini)'],
    terdaftar: [180, 210, 195, 205, 190, 260, 248],
    selesai: [160, 200, 190, 195, 185, 250, 193]
  },
  guarantorComposition: [
    { name: 'BPJS Kesehatan', count: 154, percentage: 62 },
    { name: 'Pasien Umum/Tunai', count: 57, percentage: 23 },
    { name: 'Asuransi Swasta & PT', count: 37, percentage: 15 }
  ],
  polyclinicDistribution: [
    { name: 'Poli Umum', count: 68, percentage: 27.4 },
    { name: 'Poli Penyakit Dalam', count: 55, percentage: 22.1 },
    { name: 'Poli Anak', count: 47, percentage: 18.9 },
    { name: 'Poli Gigi & Mulut', count: 42, percentage: 16.9 },
    { name: 'Poli Obstetri & Ginekologi', count: 36, percentage: 14.5 }
  ],
  diseaseRanking: [
    { rank: 1, icd: 'I10', name: 'Hipertensi Esensial (Primer)', poly: 'Poli Penyakit Dalam & Umum', count: 42, percentage: 16.9 },
    { rank: 2, icd: 'J06.9', name: 'Infeksi Saluran Pernapasan Akut', poly: 'Poli Anak & Umum', count: 35, percentage: 14.1 },
    { rank: 3, icd: 'E11.9', name: 'Diabetes Mellitus Tipe 2', poly: 'Poli Penyakit Dalam', count: 29, percentage: 11.7 },
    { rank: 4, icd: 'K30', name: 'Dispepsia (Gangguan Lambung)', poly: 'Poli Umum & Penyakit Dalam', count: 24, percentage: 9.7 },
    { rank: 5, icd: 'M54.5', name: 'Low Back Pain (Nyeri Punggung)', poly: 'Poli Saraf & Poli Umum', count: 21, percentage: 8.5 }
  ],
  procedureRanking: [
    { rank: 1, name: 'Konsultasi Dokter Spesialis', poly: 'Poli Umum & Spesialis Terpadu', count: 186, change: '+12%' },
    { rank: 2, name: 'Pemeriksaan Tanda Vital Lengkap', poly: 'Ruang Triage & Keperawatan', count: 174, change: '+8%' },
    { rank: 3, name: 'Pemeriksaan Gula Darah Sewaktu', poly: 'Lab Point-of-Care & Penyakit Dalam', count: 68, change: '+6%' },
    { rank: 4, name: 'Nebulisasi Inhalasi Saluran Napas', poly: 'Poli Anak & Poli Paru', count: 45, change: '-2%' },
    { rank: 5, name: 'Perawatan Luka & Ganti Balut', poly: 'Poli Bedah & Poli Umum', count: 38, change: '+4%' }
  ],
  polyclinicOperations: [
    { id: 1, name: 'Poli Umum', doctor: 'dr. Budi Santoso', total: 60, waiting: 0, examining: 4, finished: 56, avgWait: 16, status: 'Normal' },
    { id: 2, name: 'Poli Penyakit Dalam', doctor: 'dr. Siti Rahma, Sp.PD', total: 95, waiting: 18, examining: 3, finished: 74, avgWait: 28, status: 'Padat' },
    { id: 3, name: 'Poli Anak', doctor: 'dr. Aditya Putra, Sp.A', total: 47, waiting: 12, examining: 2, finished: 33, avgWait: 45, status: 'Lambat' },
    { id: 4, name: 'Poli Gigi & Mulut', doctor: 'drg. Maya Lestari', total: 42, waiting: 5, examining: 2, finished: 35, avgWait: 18, status: 'Normal' },
    { id: 5, name: 'Poli Obstetri & Ginekologi', doctor: 'dr. Risa Kartika, Sp.O.G', total: 36, waiting: 4, examining: 2, finished: 30, avgWait: 16, status: 'Normal' }
  ],
  attentionItems: [
    { id: 1, type: 'info', title: 'Antrean Padat', description: 'Poli Penyakit Dalam memiliki 18 pasien dalam daftar tunggu antrean.', time: 'Sejak 28 menit yang lalu', action: null },
    { id: 2, type: 'critical', title: 'Melebihi SLA Pelayanan', description: 'Rata-rata waktu tunggu Poli Anak mencapai 45 menit (Target standar: < 30 menit).', time: 'Segera alokasi perawat pendamping', action: null },
    { id: 3, type: 'warning', title: 'Poli Belum Buka', description: 'dr. Hendra Kusuma, Sp.M (Poli Mata) belum melakukan verifikasi kehadiran di SIMRS.', time: 'Jadwal mulai: 09.00 WIB', action: null }
  ]
};
