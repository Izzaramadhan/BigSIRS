<script setup>
import { computed } from 'vue';

const props = defineProps({
  records: {
    type: Array,
    required: true
  },
  totalCount: {
    type: Number,
    required: true
  },
  currentPage: {
    type: Number,
    default: 1
  },
  lastPage: {
    type: Number,
    default: 1
  },
  perPage: {
    type: Number,
    default: 10
  },
  from: {
    type: Number,
    default: 0
  },
  to: {
    type: Number,
    default: 0
  },
  searchQuery: {
    type: String,
    default: ''
  },
  filters: {
    type: Object,
    required: true
  },
  polyclinics: {
    type: Array,
    default: () => []
  },
  sortKey: {
    type: String,
    default: ''
  },
  sortDirection: {
    type: String,
    default: 'asc'
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'update:searchQuery',
  'update:perPage',
  'page-change',
  'sort',
  'search',
  'reset',
  'open-bill',
  'call-patient'
]);

const perPageOptions = [10, 25, 50, 100];

const longMonthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const internalSearch = computed({
  get: () => props.searchQuery,
  set: (val) => emit('update:searchQuery', val)
});

const formatDateLong = (isoDate) => {
  if (!isoDate) return '—';
  const parts = String(isoDate).split('-');
  if (parts.length !== 3) return isoDate;
  const year = parts[0];
  const monthIdx = Number(parts[1]) - 1;
  const day = Number(parts[2]);
  return `${String(day).padStart(2, '0')} ${longMonthNames[monthIdx] || parts[1]} ${year}`;
};

const formatTimeWithSeconds = (timeStr) => {
  if (!timeStr) return '00:00:00';
  const clean = String(timeStr).replace(/\s*WIB/i, '').trim();
  if (/^\d{2}:\d{2}$/.test(clean)) return `${clean}:00`;
  return clean;
};

const formatGuarantor = (val) => {
  if (!val) return 'UMUM';
  const lower = String(val).toLowerCase();
  if (lower.includes('umum') || lower.includes('tunai') || lower.includes('mandiri')) {
    if (lower.includes('inhealth')) return 'Mandiri Inhealth';
    return 'UMUM';
  }
  return val;
};

const formatPaymentStatusLabel = (status) => {
  if (status === 'Lunas') return 'Lunas';
  if (status === 'Dibayar Sebagian') return 'Dibayar Sebagian';
  if (status === 'Dibatalkan') return 'Dibatalkan';
  return 'Belum Bayar';
};

const getPaymentBadgeClass = (status) => {
  if (status === 'Lunas') return 'pill-success';
  if (status === 'Dibayar Sebagian') return 'pill-warning';
  if (status === 'Dibatalkan') return 'pill-neutral';
  return 'pill-danger';
};

const getExamBadgeClass = (status) => {
  if (status === 'Sudah Dilayani') return 'pill-success';
  if (status === 'Sedang Diperiksa') return 'pill-info';
  return 'pill-danger';
};

const getPharmacyBadgeClass = (status) => {
  if (status === 'Sudah Diterima') return 'pill-success';
  if (status === 'Menunggu Obat') return 'pill-warning';
  return 'pill-danger';
};

const clearSearch = () => {
  emit('update:searchQuery', '');
  emit('search');
};

const pageNumbers = computed(() => {
  const current = props.currentPage;
  const last = props.lastPage;
  const delta = 1;
  const left = current - delta;
  const right = current + delta;
  const range = [];
  const rangeWithDots = [];
  let l;

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= left && i <= right)) {
      range.push(i);
    }
  }

  for (const i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1);
      } else if (i - l !== 1) {
        rangeWithDots.push('...');
      }
    }
    rangeWithDots.push(i);
    l = i;
  }

  return rangeWithDots;
});
</script>

<template>
  <div class="card billing-daily-card">
    <!-- Card Header -->
    <div class="card-header-bar">
      <div class="header-left">
        <div class="header-icon-box" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="8" y1="6" x2="21" y2="6"></line>
            <line x1="8" y1="12" x2="21" y2="12"></line>
            <line x1="8" y1="18" x2="21" y2="18"></line>
            <line x1="3" y1="6" x2="3.01" y2="6"></line>
            <line x1="3" y1="12" x2="3.01" y2="12"></line>
            <line x1="3" y1="18" x2="3.01" y2="18"></line>
          </svg>
        </div>
        <div>
          <div class="title-badge-row">
            <h2 class="card-title">DAFTAR PASIEN</h2>
            <span class="count-badge">{{ totalCount }} Data</span>
          </div>
          <p class="card-subtitle">Filter tagihan pasien berdasarkan identitas, periode kunjungan, status bayar, poliklinik, dan jaminan</p>
        </div>
      </div>
    </div>

    <!-- Filters Form Section (2 Kolom + Tombol Aksi Terpusat) -->
    <form autocomplete="off" class="filters-section" @submit.prevent="emit('search')">
      <div class="filters-2col-grid">
        <!-- Kolom Kiri: NIK/No. RM/Nama & Periode -->
        <div class="filter-col">
          <div class="horizontal-field">
            <label class="horizontal-label" for="billing-search-input">NIK / No. RM / Nama</label>
            <div class="input-icon-wrap">
              <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input
                id="billing-search-input"
                v-model="internalSearch"
                type="text"
                class="form-input billing-search-input"
                placeholder="Masukkan Data Pasien (NIK / No. RM / Nama)"
                autocomplete="off"
              />
              <button
                v-if="internalSearch"
                type="button"
                class="btn-clear-input"
                @click="clearSearch"
                title="Hapus kata kunci"
                aria-label="Hapus kata kunci"
              >
                &times;
              </button>
            </div>
          </div>

          <div class="horizontal-field period-field">
            <label class="horizontal-label" for="billing-start-date">Periode</label>
            <div class="period-dates-row">
              <div class="date-input-col">
                <input
                  id="billing-start-date"
                  v-model="filters.customStartDate"
                  type="date"
                  class="form-input period-date-input"
                  aria-label="Tanggal Awal"
                />
                <span class="date-helper-text">*) tanggal awal</span>
              </div>
              <div class="date-input-col">
                <input
                  id="billing-end-date"
                  v-model="filters.customEndDate"
                  type="date"
                  class="form-input period-date-input"
                  aria-label="Tanggal Akhir"
                />
                <span class="date-helper-text">*) tanggal akhir</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Status Bayar, Poliklinik, Jenis Jaminan -->
        <div class="filter-col">
          <div class="horizontal-field">
            <label class="horizontal-label" for="billing-payment-status-filter">Status Bayar</label>
            <div class="select-wrapper">
              <select
                id="billing-payment-status-filter"
                v-model="filters.status"
                class="filter-select"
                aria-label="Filter Status Bayar"
              >
                <option value="Semua">---- Semua ----</option>
                <option value="Belum Bayar">Belum Bayar</option>
                <option value="Lunas">Lunas</option>
                <option value="Dibayar Sebagian">Dibayar Sebagian</option>
                <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
                <option value="Dibatalkan">Dibatalkan</option>
              </select>
              <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </div>

          <div class="horizontal-field">
            <label class="horizontal-label" for="billing-polyclinic-filter">Poliklinik</label>
            <div class="select-wrapper">
              <select
                id="billing-polyclinic-filter"
                v-model="filters.polyclinic"
                class="filter-select"
                aria-label="Filter Poliklinik"
              >
                <option value="Semua Poliklinik">---- Semua Klinik ----</option>
                <option v-for="poly in polyclinics" :key="poly" :value="poly">
                  {{ poly }}
                </option>
              </select>
              <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </div>

          <div class="horizontal-field">
            <label class="horizontal-label" for="billing-guarantor-filter">Jenis Jaminan</label>
            <div class="select-wrapper">
              <select
                id="billing-guarantor-filter"
                v-model="filters.guarantor"
                class="filter-select"
                aria-label="Filter Jenis Jaminan"
              >
                <option value="Semua Jenis Penjamin">---- Semua Jaminan ----</option>
                <option value="Pasien Umum">UMUM</option>
                <option value="BPJS Kesehatan">BPJS Kesehatan</option>
                <option value="Mandiri Inhealth">Mandiri Inhealth</option>
                <option value="Asuransi Lainnya">Asuransi Swasta Lainnya</option>
              </select>
              <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Centered Filter Buttons -->
      <div class="filter-actions-centered">
        <button type="submit" class="btn-primary btn-search-billing" :disabled="loading">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <span>Cari Data Pasien</span>
        </button>

        <button type="button" class="btn-reset-billing" :disabled="loading" @click="emit('reset')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21.5 2v6h-6"></path>
            <path d="M21.34 15.57a10 10 0 1 1-.57-8.38L21.5 8"></path>
          </svg>
          <span>Reset Filter</span>
        </button>
      </div>
    </form>

    <!-- Records Selector Bar -->
    <div class="table-controls-bar">
      <div class="records-control">
        <div class="records-select-wrapper">
          <select
            id="billing-records-perpage"
            :value="perPage"
            class="records-select"
            aria-label="Jumlah data per halaman"
            :disabled="loading"
            @change="emit('update:perPage', Number($event.target.value))"
          >
            <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
          </select>
          <svg class="records-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
        <span class="records-label">records</span>
      </div>
    </div>

    <!-- Data Table (9 Kolom sesuai gambar referensi) -->
    <div class="table-container">
      <table class="data-table" aria-label="Tabel Daftar Billing Pasien">
        <thead>
          <tr>
            <th class="col-no text-center">No</th>
            <th
              class="col-queue sortable"
              :class="{ 'sorted': sortKey === 'antrian' }"
              @click="emit('sort', 'antrian')"
            >
              <div class="th-content">
                <span>No. Antrian</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'antrian' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'antrian' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'antrian' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
                  </svg>
                </span>
              </div>
            </th>
            <th
              class="col-time sortable"
              :class="{ 'sorted': sortKey === 'waktu' }"
              @click="emit('sort', 'waktu')"
            >
              <div class="th-content">
                <span>Waktu</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'waktu' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'waktu' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'waktu' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
                  </svg>
                </span>
              </div>
            </th>
            <th
              class="col-name sortable"
              :class="{ 'sorted': sortKey === 'nama' }"
              @click="emit('sort', 'nama')"
            >
              <div class="th-content">
                <span>Nama</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'nama' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'nama' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'nama' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
                  </svg>
                </span>
              </div>
            </th>
            <th
              class="col-clinic sortable"
              :class="{ 'sorted': sortKey === 'klinik' }"
              @click="emit('sort', 'klinik')"
            >
              <div class="th-content">
                <span>Klinik</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'klinik' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'klinik' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'klinik' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
                  </svg>
                </span>
              </div>
            </th>
            <th
              class="col-status sortable"
              :class="{ 'sorted': sortKey === 'exam_status' }"
              @click="emit('sort', 'exam_status')"
            >
              <div class="th-content">
                <span>Status Pemeriksaan</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'exam_status' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'exam_status' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'exam_status' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
                  </svg>
                </span>
              </div>
            </th>
            <th
              class="col-status sortable"
              :class="{ 'sorted': sortKey === 'pharmacy_status' }"
              @click="emit('sort', 'pharmacy_status')"
            >
              <div class="th-content">
                <span>Status Farmasi</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'pharmacy_status' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'pharmacy_status' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'pharmacy_status' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
                  </svg>
                </span>
              </div>
            </th>
            <th
              class="col-status sortable"
              :class="{ 'sorted': sortKey === 'payment_status' }"
              @click="emit('sort', 'payment_status')"
            >
              <div class="th-content">
                <span>Status Bayar</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'payment_status' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'payment_status' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'payment_status' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
                  </svg>
                </span>
              </div>
            </th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <template v-if="loading">
            <tr v-for="n in 5" :key="'skel-' + n" class="skeleton-row">
              <td v-for="c in 9" :key="c"><div class="skel-bone"></div></td>
            </tr>
          </template>

          <tr v-else-if="records.length === 0">
            <td colspan="9" class="empty-cell">
              <div class="empty-wrapper">
                <div class="empty-icon-circle">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                </div>
                <h3 class="empty-title">Tidak ada data billing pasien ditemukan.</h3>
                <p class="empty-desc">
                  Silakan ubah kata kunci pencarian, periode tanggal, status bayar, klinik, atau jenis jaminan.
                </p>
                <button type="button" class="btn-primary btn-empty-reset" @click="emit('reset')">
                  Reset Filter
                </button>
              </div>
            </td>
          </tr>

          <template v-else>
            <tr v-for="(item, idx) in records" :key="item.id">
              <!-- 1. No -->
              <td class="col-no text-center text-muted">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>

              <!-- 2. No. Antrian -->
              <td class="col-queue">
                <span class="queue-pill font-mono">{{ item.queue_number || item.visit_number }}</span>
              </td>

              <!-- 3. Waktu -->
              <td class="col-time">
                <div class="time-cell">
                  <span class="visit-date">{{ formatDateLong(item.service_date) }}</span>
                  <span class="visit-clock font-mono">{{ formatTimeWithSeconds(item.service_time) }}</span>
                </div>
              </td>

              <!-- 4. Nama -->
              <td class="col-name">
                <div class="patient-cell">
                  <span class="patient-name">{{ item.patient_name }}</span>
                  <span class="patient-meta-italic">
                    {{ item.medical_record_number }} | {{ item.gender }} | {{ item.birth_date }} | {{ formatGuarantor(item.guarantor_type) }}
                  </span>
                </div>
              </td>

              <!-- 5. Klinik -->
              <td class="col-clinic">
                <span class="clinic-name">{{ item.polyclinic }}</span>
              </td>

              <!-- 6. Status Pemeriksaan -->
              <td class="col-status">
                <span class="status-pill" :class="getExamBadgeClass(item.exam_status)">
                  {{ item.exam_status || 'Belum Dilayani' }}
                </span>
              </td>

              <!-- 7. Status Farmasi -->
              <td class="col-status">
                <span class="status-pill" :class="getPharmacyBadgeClass(item.pharmacy_status)">
                  {{ item.pharmacy_status || 'Tidak Ada Resep' }}
                </span>
              </td>

              <!-- 8. Status Bayar -->
              <td class="col-status">
                <span class="status-pill" :class="getPaymentBadgeClass(item.status)">
                  {{ formatPaymentStatusLabel(item.status) }}
                </span>
              </td>

              <!-- 9. Aksi (+ Billing & Panggil Speaker) -->
              <td class="col-actions">
                <div class="actions-pair">
                  <button
                    type="button"
                    class="btn-action-square btn-action-bill"
                    title="Proses Pembayaran / Detail Tagihan"
                    aria-label="Proses Tagihan Pasien"
                    @click="emit('open-bill', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="5" x2="12" y2="19"></line>
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                  </button>

                  <button
                    type="button"
                    class="btn-action-square btn-action-call"
                    title="Panggil Antrean Kasir"
                    aria-label="Panggil Antrean Kasir"
                    @click="emit('call-patient', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                      <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Footer Pagination -->
    <div class="table-footer-bar">
      <div class="entries-info">
        <template v-if="totalCount > 0">
          Menampilkan <strong class="entries-num">{{ from }}</strong> sampai <strong class="entries-num">{{ to }}</strong> dari <strong class="entries-num">{{ totalCount }}</strong> entri
        </template>
        <template v-else>
          Menampilkan 0 entri
        </template>
      </div>

      <div class="pagination-controls" v-if="totalCount > 0">
        <button
          type="button"
          class="page-btn page-btn-nav"
          :disabled="currentPage <= 1 || loading"
          aria-label="Halaman Sebelumnya"
          @click="emit('page-change', currentPage - 1)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>

        <div class="page-numbers">
          <template v-for="(p, i) in pageNumbers" :key="i">
            <span v-if="p === '...'" class="page-dots">...</span>
            <button
              v-else
              type="button"
              class="page-btn page-btn-num"
              :class="{ 'active': p === currentPage }"
              :disabled="loading"
              :aria-current="p === currentPage ? 'page' : undefined"
              @click="p !== currentPage ? emit('page-change', p) : null"
            >
              {{ p }}
            </button>
          </template>
        </div>

        <button
          type="button"
          class="page-btn page-btn-nav"
          :disabled="currentPage >= lastPage || loading"
          aria-label="Halaman Selanjutnya"
          @click="emit('page-change', currentPage + 1)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.billing-daily-card {
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  overflow: hidden;
}

.card-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.15rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #ffffff;
  gap: 1rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.header-icon-box {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon-box svg {
  width: 19px;
  height: 19px;
}

.title-badge-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.card-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: -0.01em;
}

.count-badge {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.6rem;
  border-radius: 9999px;
}

.card-subtitle {
  margin: 0.15rem 0 0 0;
  font-size: 0.825rem;
  color: var(--color-text-secondary);
}

/* Filters 2-Column Layout */
.filters-section {
  padding: 1.4rem 1.5rem 1.2rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #ffffff;
}

.filters-2col-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem 2.25rem;
  align-items: start;
}

.filter-col {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.horizontal-field {
  display: grid;
  grid-template-columns: 150px 1fr;
  gap: 0.75rem;
  align-items: center;
}

.horizontal-field.period-field {
  align-items: start;
}

.horizontal-field.period-field .horizontal-label {
  padding-top: 0.55rem;
}

.horizontal-label {
  font-size: 0.84rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.input-icon-wrap {
  position: relative;
}

.input-leading-icon {
  position: absolute;
  left: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #94a3b8;
  pointer-events: none;
}

.billing-search-input {
  width: 100%;
  height: 40px;
  padding: 0 2.1rem 0 2.45rem;
  font-size: 0.85rem;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  background: #ffffff;
  box-sizing: border-box;
  font-family: inherit;
}

.billing-search-input::placeholder {
  color: #94a3b8;
}

.btn-clear-input {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  width: 22px;
  height: 22px;
  border: none;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
  padding: 0;
}

.btn-clear-input:hover {
  color: var(--color-text-navy);
}

.period-dates-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.date-input-col {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.period-date-input {
  width: 100%;
  height: 40px;
  padding: 0 0.75rem;
  font-size: 0.84rem;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  background: #ffffff;
  box-sizing: border-box;
  font-family: inherit;
}

.date-helper-text {
  font-size: 0.74rem;
  font-style: italic;
  color: var(--color-text-secondary);
}

.select-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.filter-select {
  appearance: none;
  -webkit-appearance: none;
  width: 100%;
  height: 40px;
  padding: 0 2.25rem 0 0.85rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  background: #ffffff;
  color: var(--color-text-navy);
  font-size: 0.85rem;
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
}

.filter-select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.select-chevron {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 15px;
  height: 15px;
  color: var(--color-text-secondary);
  pointer-events: none;
}

/* Centered Action Buttons */
.filter-actions-centered {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin-top: 1.25rem;
  flex-wrap: wrap;
}

.btn-search-billing {
  height: 38px;
  padding: 0 1.25rem;
  border-radius: 8px;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  font-size: 0.85rem;
  font-weight: 600;
  font-family: inherit;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.2);
  transition: all 0.15s ease;
  white-space: nowrap;
}

.btn-search-billing:hover:not(:disabled) {
  background: var(--color-primary-dark);
  box-shadow: 0 4px 12px rgba(8, 127, 120, 0.28);
  transform: translateY(-1px);
}

.btn-search-billing svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

.btn-reset-billing {
  height: 38px;
  padding: 0 1.15rem;
  border-radius: 8px;
  background: #fffbeb;
  color: #b45309;
  border: 1px solid rgba(217, 119, 6, 0.4);
  font-size: 0.85rem;
  font-weight: 600;
  font-family: inherit;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.btn-reset-billing:hover:not(:disabled) {
  background: #fef3c7;
  border-color: #d97706;
  color: #92400e;
  transform: translateY(-1px);
}

.btn-reset-billing svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

/* Controls Bar */
.table-controls-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.9rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #ffffff;
}

.records-control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.records-select-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.records-select {
  appearance: none;
  -webkit-appearance: none;
  height: 34px;
  padding: 0 1.85rem 0 0.7rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  background: #ffffff;
  color: var(--color-text-navy);
  font-size: 0.825rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
}

.records-select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.records-chevron {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  width: 13px;
  height: 13px;
  color: var(--color-text-secondary);
  pointer-events: none;
}

.records-label {
  font-size: 0.825rem;
  color: var(--color-text-secondary);
  font-weight: 500;
}

/* Table */
.table-container {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  border: none;
  border-radius: 0;
  box-shadow: none;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.84rem;
  min-width: 1040px;
}

.data-table th {
  background: #f8fafc;
  padding: 0.85rem 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  font-size: 0.81rem;
  white-space: nowrap;
}

.data-table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.15s ease;
}

.data-table th.sortable:hover {
  background: #f1f5f9;
}

.data-table th.sorted {
  color: var(--color-primary-dark);
}

.th-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.45rem;
}

.sort-indicator {
  display: inline-flex;
  align-items: center;
  color: #cbd5e1;
}

.sort-indicator.active {
  color: var(--color-primary);
}

.sort-indicator svg {
  width: 13px;
  height: 13px;
}

.data-table td {
  padding: 0.9rem 1rem;
  border-bottom: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  vertical-align: middle;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.col-no { width: 54px; }
.col-queue { width: 110px; }
.col-time { width: 150px; }
.col-actions { width: 108px; }

.text-center { text-align: center !important; }
.text-muted { color: var(--color-text-secondary); }

.queue-pill {
  display: inline-block;
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-weight: 700;
  font-size: 0.78rem;
  padding: 0.2rem 0.55rem;
  border-radius: 6px;
  white-space: nowrap;
}

.time-cell {
  display: flex;
  flex-direction: column;
  gap: 0.12rem;
  white-space: nowrap;
}

.visit-date {
  font-weight: 500;
  color: var(--color-text-navy);
  font-size: 0.825rem;
}

.visit-clock {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.patient-cell {
  display: flex;
  flex-direction: column;
  gap: 0.12rem;
}

.patient-name {
  font-weight: 600;
  color: var(--color-text-navy);
}

.patient-meta-italic {
  font-style: italic;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  line-height: 1.3;
}

.clinic-name {
  font-weight: 500;
  color: var(--color-text-navy);
}

/* Status Pills */
.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.22rem 0.65rem;
  border-radius: 6px;
  font-size: 0.74rem;
  font-weight: 700;
  white-space: nowrap;
}

.pill-danger {
  background: #fff1f2;
  color: #e11d48;
  border: 1px solid #fecdd3;
}

.pill-success {
  background: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.pill-warning {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}

.pill-info {
  background: #e0f2fe;
  color: #0369a1;
  border: 1px solid #bae6fd;
}

.pill-neutral {
  background: #f1f5f9;
  color: var(--color-text-secondary);
  border: 1px solid var(--color-border-soft);
}

/* Action Buttons (+ & Speaker) */
.actions-pair {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
}

.btn-action-square {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  padding: 0;
}

.btn-action-square svg {
  width: 15px;
  height: 15px;
}

.btn-action-bill {
  background: #e11d48;
  color: #ffffff;
  box-shadow: 0 2px 4px rgba(225, 29, 72, 0.2);
}

.btn-action-bill:hover {
  background: #be123c;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(225, 29, 72, 0.28);
}

.btn-action-call {
  background: var(--color-primary);
  color: #ffffff;
  box-shadow: 0 2px 4px rgba(8, 127, 120, 0.2);
}

.btn-action-call:hover {
  background: var(--color-primary-dark);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(8, 127, 120, 0.28);
}

/* Footer Pagination */
.table-footer-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  background: #ffffff;
}

.entries-info {
  font-size: 0.825rem;
  color: var(--color-text-secondary);
}

.entries-num {
  color: var(--color-text-navy);
  font-weight: 600;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.page-numbers {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.page-btn {
  height: 32px;
  min-width: 32px;
  padding: 0 0.55rem;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft);
  background: #ffffff;
  color: var(--color-text-navy);
  font-size: 0.825rem;
  font-weight: 500;
  font-family: inherit;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
}

.page-btn:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.page-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  background: #f8fafc;
}

.page-btn-num.active {
  background: var(--color-primary);
  color: #ffffff;
  border-color: var(--color-primary);
  font-weight: 600;
}

.page-btn-nav svg {
  width: 14px;
  height: 14px;
}

.page-dots {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  font-size: 0.825rem;
  color: var(--color-text-secondary);
}

/* Empty & Skeleton */
.empty-cell {
  padding: 3rem 1.5rem !important;
}

.empty-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.5rem;
}

.empty-icon-circle {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-icon-circle svg {
  width: 22px;
  height: 22px;
}

.empty-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.empty-desc {
  margin: 0;
  font-size: 0.825rem;
  color: var(--color-text-secondary);
}

.btn-empty-reset {
  margin-top: 0.4rem;
  height: 36px;
  font-size: 0.825rem;
}

.skel-bone {
  height: 15px;
  border-radius: 4px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

@media (max-width: 1024px) {
  .filters-2col-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}

@media (max-width: 640px) {
  .horizontal-field {
    grid-template-columns: 1fr;
    gap: 0.35rem;
  }

  .period-dates-row {
    grid-template-columns: 1fr;
  }

  .filter-actions-centered {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-search-billing,
  .btn-reset-billing {
    width: 100%;
  }

  .table-footer-bar {
    flex-direction: column;
    align-items: flex-start;
  }

  .pagination-controls {
    width: 100%;
    justify-content: center;
  }
}
</style>

