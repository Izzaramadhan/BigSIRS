/**
 * Mock data ketersediaan tempat tidur SIRS khusus kebutuhan pengujian dan development UI.
 *
 * PERHATIAN:
 * Data ini murni prototipe tampilan frontend sementara.
 * Jangan digunakan sebagai data produksi atau fallback ketika koneksi gagal.
 * Struktur final menunggu kontrak API resmi dari Kementerian Kesehatan / SIRS Online.
 *
 * @typedef {Object} SirsBedAvailability
 * @property {number|string} id
 * @property {string} className
 * @property {string} roomName
 * @property {number} capacity
 * @property {number} available
 * @property {string|null} lastUpdatedAt
 */

export const mockSirsBedAvailability = [
  {
    id: 1,
    className: 'Kelas 1',
    roomName: 'Ruang Melati 101',
    capacity: 4,
    available: 1,
    lastUpdatedAt: '2026-10-22T09:30:00',
  },
  {
    id: 2,
    className: 'Kelas 2',
    roomName: 'Ruang Melati 102',
    capacity: 6,
    available: 3,
    lastUpdatedAt: '2026-10-22T09:15:00',
  },
  {
    id: 3,
    className: 'Kelas 3',
    roomName: 'Ruang Mawar 201',
    capacity: 12,
    available: 5,
    lastUpdatedAt: '2026-10-22T08:45:00',
  },
  {
    id: 4,
    className: 'VIP',
    roomName: 'Ruang Anggrek VIP 1',
    capacity: 2,
    available: 0,
    lastUpdatedAt: '2026-10-22T09:00:00',
  },
  {
    id: 5,
    className: 'VVIP',
    roomName: 'Ruang Anggrek VVIP 1',
    capacity: 1,
    available: 1,
    lastUpdatedAt: '2026-10-22T07:30:00',
  },
  {
    id: 6,
    className: 'ICU',
    roomName: 'Ruang Intensif ICU',
    capacity: 8,
    available: 2,
    lastUpdatedAt: '2026-10-22T09:20:00',
  },
  {
    id: 7,
    className: 'ICCU',
    roomName: 'Ruang Intensif ICCU',
    capacity: 4,
    available: 1,
    lastUpdatedAt: null,
  },
  {
    id: 8,
    className: 'NICU',
    roomName: 'Ruang Neonatus NICU',
    capacity: 5,
    available: 2,
    lastUpdatedAt: '2026-10-21T18:00:00',
  },
  {
    id: 9,
    className: 'PICU',
    roomName: 'Ruang Anak PICU',
    capacity: 4,
    available: 4,
    lastUpdatedAt: '2026-10-22T06:15:00',
  },
  {
    id: 10,
    className: 'Isolasi',
    roomName: 'Ruang Isolasi Infeksi',
    capacity: 6,
    available: 0,
    lastUpdatedAt: '2026-10-22T08:00:00',
  },
  {
    id: 11,
    className: 'HCU',
    roomName: 'Ruang Intermediate HCU',
    capacity: 5,
    available: 3,
    lastUpdatedAt: '2026-10-22T09:10:00',
  },
  {
    id: 12,
    className: 'Kelas 3',
    roomName: 'Ruang Teratai 301',
    capacity: 10,
    available: 6,
    lastUpdatedAt: '2026-10-22T09:25:00',
  },
]
