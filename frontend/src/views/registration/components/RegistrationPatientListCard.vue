<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  admissions: {
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
  guarantorFilter: {
    type: String,
    default: 'all'
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
  },
  error: {
    type: String,
    default: ''
  },
  guarantorOptions: {
    type: Array,
    default: () => []
  },
  loadingGuarantors: {
    type: Boolean,
    default: false
  },
  guarantors: {
    type: Array,
    default: () => []
  },
  guarantorsLoading: {
    type: Boolean,
    default: false
  },
  guarantorsError: {
    type: String,
    default: ''
  }
});

const effectiveGuarantors = computed(() =>
  props.guarantors.length > 0 ? props.guarantors : props.guarantorOptions
);

const emit = defineEmits([
  'update:guarantorFilter',
  'update:perPage',
  'page-change',
  'sort',
  'admit-patient',
  'edit-admission',
  'delete-admission',
  'print-document',
  'reset-filter',
  'retry',
  'retry-guarantors'
]);

const perPageOptions = [10, 25, 50];

const shortMonthNames = [
  'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
  'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
];

const formatDateShort = (isoDate) => {
  if (!isoDate) return '—';
  const parts = String(isoDate).split('-');
  if (parts.length !== 3) return isoDate;
  const year = parts[0];
  const monthIdx = Number(parts[1]) - 1;
  const day = Number(parts[2]);
  return `${String(day).padStart(2, '0')} ${shortMonthNames[monthIdx] || parts[1]} ${year}`;
};

const formatBirthDateWithExactAge = (isoDate) => {
  if (!isoDate) return '-';
  const parts = String(isoDate).split('-');
  if (parts.length !== 3) return isoDate;
  const dmy = `${parts[2]}-${parts[1]}-${parts[0]}`;

  const birth = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
  const today = new Date();
  if (Number.isNaN(birth.getTime()) || birth > today) return dmy;

  let years = today.getFullYear() - birth.getFullYear();
  let months = today.getMonth() - birth.getMonth();
  let days = today.getDate() - birth.getDate();

  if (days < 0) {
    months--;
    const prevMonthLastDay = new Date(today.getFullYear(), today.getMonth(), 0).getDate();
    days += prevMonthLastDay;
  }
  if (months < 0) {
    years--;
    months += 12;
  }
  return `${dmy} (${years} Th ${months} Bl ${days} hr)`;
};

const formatGuarantor = (val) => {
  if (!val) return 'UMUM';
  if (typeof val === 'object' && val !== null) {
    return val.name || val.jenis_asuransi || 'UMUM';
  }
  if (val === 'Pasien Umum / Mandiri' || val === 'Umum / Pribadi' || val === 'UMUM') {
    return 'UMUM';
  }
  return val;
};

const getInsuranceBadgeClass = (val) => {
  const label = String(formatGuarantor(val)).toUpperCase();
  if (label.includes('UMUM') || label.includes('GRATIS') || label.includes('MANDIRI')) return 'badge-umum';
  if (label.includes('BPJS') || label.includes('JKN')) return 'badge-bpjs';
  return 'badge-swasta';
};

const isConsentPrinted = (status) => {
  if (!status) return false;
  const s = String(status).toLowerCase().trim();
  return s.includes('sudah') || s.includes('print') || s === 'sudah diprint' || s === 'sudah ttd';
};

const getConsentContainerClass = (status) => {
  return isConsentPrinted(status) ? 'consent-container-green' : 'consent-container-red';
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

// Teleported Tools Dropdown Menu
const activeDropdownId = ref(null);
const activeDropdownItem = ref(null);
const dropdownCoords = ref({ top: '0px', left: '0px' });

const closeDropdown = () => {
  activeDropdownId.value = null;
  activeDropdownItem.value = null;
};

const toggleDropdown = (event, item) => {
  if (activeDropdownId.value === item.id) {
    closeDropdown();
    return;
  }

  activeDropdownId.value = item.id;
  activeDropdownItem.value = item;

  const rect = event.currentTarget.getBoundingClientRect();
  const dropdownWidth = 195;
  const dropdownHeight = 230;

  let left = rect.right - dropdownWidth;
  if (left < 10) left = 10;

  let top = rect.bottom + 4;
  if (top + dropdownHeight > window.innerHeight) {
    top = rect.top - dropdownHeight - 4;
  }

  dropdownCoords.value = {
    top: `${top}px`,
    left: `${left}px`
  };
};

const handleAction = (actionType, docType = '') => {
  const item = activeDropdownItem.value;
  closeDropdown();
  if (!item) return;

  if (actionType === 'admit') {
    emit('admit-patient', item);
  } else if (actionType === 'edit') {
    emit('edit-admission', item);
  } else if (actionType === 'delete') {
    emit('delete-admission', item);
  } else if (actionType === 'print') {
    emit('print-document', { item, documentType: docType });
  }
};

const handleDocClick = (e) => {
  if (activeDropdownId.value && !e.target.closest('.dropdown-portal') && !e.target.closest('.btn-tools')) {
    closeDropdown();
  }
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && activeDropdownId.value) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener('click', handleDocClick);
  document.addEventListener('keydown', handleKeyDown);
  window.addEventListener('resize', closeDropdown);
  window.addEventListener('scroll', closeDropdown, true);
});

onUnmounted(() => {
  document.removeEventListener('click', handleDocClick);
  document.removeEventListener('keydown', handleKeyDown);
  window.removeEventListener('resize', closeDropdown);
  window.removeEventListener('scroll', closeDropdown, true);
});
</script>

<template>
  <div class="card registration-list-card">
    <!-- Card Header Bar -->
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
          <p class="card-subtitle">Daftar pasien dan kunjungan admisi aktif pada unit pendaftaran</p>
        </div>
      </div>

      <!-- Top-Right Guarantor Filter -->
      <div class="header-right">
        <div class="guarantor-select-wrapper">
          <select
            id="reg-guarantor-filter"
            :value="guarantorFilter"
            class="guarantor-filter-select"
            aria-label="Filter Jenis Asuransi"
            :disabled="guarantorsLoading || loadingGuarantors"
            @change="emit('update:guarantorFilter', $event.target.value)"
          >
            <option value="all">Semua Jenis Asuransi</option>
            <option
              v-for="g in effectiveGuarantors"
              :key="g.id ?? g.name"
              :value="String(g.id ?? g.name)"
            >
              {{ g.label || g.name || g.jenis_asuransi }}
            </option>
          </select>
          <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
        <div v-if="guarantorsError" class="guarantor-filter-status">
          <span>{{ guarantorsError }}</span>
          <button type="button" class="btn-retry-guarantor" @click="emit('retry-guarantors')">Muat Ulang</button>
        </div>
      </div>
    </div>

    <!-- Table Controls Bar (10 records & Search:) -->
    <div class="table-controls-bar">
      <div class="records-control">
        <div class="records-select-wrapper">
          <select
            id="reg-records-perpage"
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

    <!-- Data Table (10 Kolom sesuai referensi gambar) -->
    <div class="table-container">
      <table class="data-table" aria-label="Tabel Daftar Pasien Pendaftaran">
        <thead>
          <tr>
            <th class="col-no text-center">No.</th>
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
                <span>Waktu Admisi</span>
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
              class="col-phone sortable"
              :class="{ 'sorted': sortKey === 'hp' }"
              @click="emit('sort', 'hp')"
            >
              <div class="th-content">
                <span>No. HP</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'hp' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'hp' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'hp' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
                  </svg>
                </span>
              </div>
            </th>
            <th
              class="col-address sortable"
              :class="{ 'sorted': sortKey === 'alamat' }"
              @click="emit('sort', 'alamat')"
            >
              <div class="th-content">
                <span>Alamat</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'alamat' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'alamat' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'alamat' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
                  </svg>
                </span>
              </div>
            </th>
            <th
              class="col-unit sortable"
              :class="{ 'sorted': sortKey === 'admisi_ke' }"
              @click="emit('sort', 'admisi_ke')"
            >
              <div class="th-content">
                <span>Admisi ke</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'admisi_ke' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'admisi_ke' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'admisi_ke' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
                  </svg>
                </span>
              </div>
            </th>
            <th
              class="col-insurance sortable"
              :class="{ 'sorted': sortKey === 'asuransi' }"
              @click="emit('sort', 'asuransi')"
            >
              <div class="th-content">
                <span>Jenis Asuransi</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'asuransi' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'asuransi' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'asuransi' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
                  </svg>
                </span>
              </div>
            </th>
            <th
              class="col-consent sortable"
              :class="{ 'sorted': sortKey === 'consent' }"
              @click="emit('sort', 'consent')"
            >
              <div class="th-content">
                <span>General Consent</span>
                <span class="sort-indicator" :class="{ 'active': sortKey === 'consent' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 15 12 20 17 15" :opacity="sortKey === 'consent' && sortDirection === 'asc' ? 0.3 : 1"></polyline>
                    <polyline points="7 9 12 4 17 9" :opacity="sortKey === 'consent' && sortDirection === 'desc' ? 0.3 : 1"></polyline>
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
              <td v-for="c in 10" :key="c"><div class="skel-bone"></div></td>
            </tr>
          </template>

          <tr v-else-if="error">
            <td colspan="10" class="empty-cell">
              <div class="empty-wrapper" role="alert">
                <h3 class="empty-title text-danger">Gagal memuat data pasien dari server.</h3>
                <p class="empty-desc">{{ error }}</p>
                <button
                  type="button"
                  class="btn-primary btn-reset-empty"
                  @click="emit('retry')"
                >
                  Muat Ulang
                </button>
              </div>
            </td>
          </tr>

          <tr v-else-if="admissions.length === 0">
            <td colspan="10" class="empty-cell">
              <div class="empty-wrapper">
                <div class="empty-icon-circle">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                </div>
                <h3 class="empty-title">Tidak ada data pasien yang ditemukan.</h3>
                <p class="empty-desc">Ubah filter jenis asuransi untuk menampilkan data lainnya.</p>
                <button
                  v-if="guarantorFilter !== 'all'"
                  type="button"
                  class="btn-primary btn-reset-empty"
                  @click="emit('reset-filter')"
                >
                  Reset Filter Daftar
                </button>
              </div>
            </td>
          </tr>

          <template v-else>
            <tr v-for="(adm, idx) in admissions" :key="adm.id">
              <!-- 1. No. -->
              <td class="col-no text-center text-muted">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>

              <!-- 2. No. Antrian -->
              <td class="col-queue">
                <span class="queue-pill font-mono">—</span>
              </td>

              <!-- 3. Waktu Admisi -->
              <td class="col-time">
                <div class="time-cell">
                  <span class="adm-date">{{ formatDateShort(adm.admission_date) }}</span>
                  <span class="adm-clock font-mono">{{ adm.admission_time }}</span>
                </div>
              </td>

              <!-- 4. Nama -->
              <td class="col-name">
                <div class="patient-cell">
                  <span class="patient-name">{{ adm.patient_name }}</span>
                  <span class="patient-meta-italic">
                    {{ adm.medical_record_number }} | {{ adm.gender }} | {{ formatBirthDateWithExactAge(adm.birth_date) }}
                  </span>
                </div>
              </td>

              <!-- 5. No. HP -->
              <td class="col-phone font-mono-sm">
                {{ adm.phone || '—' }}
              </td>

              <!-- 6. Alamat -->
              <td class="col-address">
                <div class="address-cell" :title="adm.address">
                  {{ adm.address || '—' }}
                </div>
              </td>

              <!-- 7. Admisi ke -->
              <td class="col-unit">
                <div class="unit-cell">
                  <span class="unit-name">{{ adm.polyclinic }}</span>
                  <span v-if="adm.notes" class="unit-notes">({{ adm.notes }})</span>
                </div>
              </td>

              <!-- 8. Jenis Asuransi -->
              <td class="col-insurance">
                <span class="insurance-badge" :class="getInsuranceBadgeClass(adm.guarantor_type)">
                  {{ formatGuarantor(adm.guarantor_type) }}
                </span>
              </td>

              <!-- 9. General Consent -->
              <td class="col-consent">
                <span
                  class="consent-container-badge"
                  :class="getConsentContainerClass(adm.general_consent)"
                >
                  {{ isConsentPrinted(adm.general_consent) ? 'Sudah diprint' : 'Belum diprint' }}
                </span>
              </td>

              <!-- 10. Aksi (Tools Dropdown) -->
              <td class="col-actions text-center">
                <button
                  type="button"
                  class="btn-tools"
                  :class="{ 'active': activeDropdownId === adm.id }"
                  :aria-expanded="activeDropdownId === adm.id"
                  aria-haspopup="true"
                  @click="toggleDropdown($event, adm)"
                >
                  <span>Tools</span>
                  <svg class="tools-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Table Footer Pagination -->
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

    <!-- Teleported Tools Dropdown Menu (sesuai referensi gambar) -->
    <Teleport to="body">
      <div v-if="activeDropdownId" class="dropdown-portal" :style="dropdownCoords">
        <ul class="dropdown-list" role="menu">
          <li role="menuitem">
            <button type="button" class="dropdown-action-btn" @click="handleAction('edit')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
              <span>Edit Admisi</span>
            </button>
          </li>

          <li role="menuitem">
            <button type="button" class="dropdown-action-btn text-danger" @click="handleAction('delete')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
              <span>Hapus</span>
            </button>
          </li>

          <li class="dropdown-divider"></li>

          <li role="menuitem">
            <button type="button" class="dropdown-action-btn" @click="handleAction('print', 'Tracer Rekam Medis')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
              </svg>
              <span>Cetak Tracer</span>
            </button>
          </li>

          <li role="menuitem">
            <button type="button" class="dropdown-action-btn" @click="handleAction('print', 'Label Barcode Pasien')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
              </svg>
              <span>Cetak Label</span>
            </button>
          </li>

          <li role="menuitem">
            <button type="button" class="dropdown-action-btn" @click="handleAction('print', 'Formulir General Consent')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <polyline points="9 15 11 17 15 13"></polyline>
              </svg>
              <span>Cetak General Consent</span>
            </button>
          </li>
        </ul>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.registration-list-card {
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
  padding: 1.05rem 1.4rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #ffffff;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.header-icon-box {
  width: 36px;
  height: 36px;
  border-radius: 9px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon-box svg {
  width: 18px;
  height: 18px;
}

.title-badge-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.card-title {
  margin: 0;
  font-size: 0.975rem;
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
  margin: 0.12rem 0 0 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.guarantor-select-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.guarantor-filter-select {
  appearance: none;
  -webkit-appearance: none;
  height: 38px;
  min-width: 220px;
  padding: 0 2.25rem 0 0.85rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  background: #ffffff;
  color: var(--color-text-navy);
  font-size: 0.85rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
}

.guarantor-filter-select:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.guarantor-filter-select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.select-chevron {
  position: absolute;
  right: 0.7rem;
  top: 50%;
  transform: translateY(-50%);
  width: 15px;
  height: 15px;
  color: var(--color-text-secondary);
  pointer-events: none;
}

/* Controls Bar */
.table-controls-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.9rem 1.4rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #ffffff;
  gap: 1rem;
  flex-wrap: wrap;
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
  min-width: 1120px;
}

.data-table th {
  background: #f8fafc;
  padding: 0.82rem 0.9rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  font-size: 0.8rem;
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
  gap: 0.4rem;
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
  padding: 0.85rem 0.9rem;
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

.col-no { width: 52px; }
.col-actions { width: 105px; }

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
  gap: 0.1rem;
  white-space: nowrap;
}

.adm-date {
  font-weight: 500;
  color: var(--color-text-navy);
  font-size: 0.82rem;
}

.adm-clock {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.patient-cell {
  display: flex;
  flex-direction: column;
  gap: 0.12rem;
  min-width: 190px;
}

.patient-name {
  font-weight: 600;
  color: var(--color-text-navy);
}

.patient-meta-italic {
  font-style: italic;
  font-size: 0.74rem;
  color: var(--color-text-secondary);
  line-height: 1.3;
}

.font-mono-sm {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.78rem;
  white-space: nowrap;
}

.address-cell {
  max-width: 195px;
  font-size: 0.8rem;
  line-height: 1.35;
  color: var(--color-text-navy);
}

.unit-cell {
  display: flex;
  flex-direction: column;
  gap: 0.12rem;
  min-width: 165px;
}

.unit-name {
  font-weight: 500;
  color: var(--color-text-navy);
  font-size: 0.82rem;
}

.unit-notes {
  font-size: 0.74rem;
  color: var(--color-text-secondary);
}

.insurance-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.74rem;
  font-weight: 600;
  white-space: nowrap;
}

.badge-umum {
  background: #f1f5f9;
  color: var(--color-text-navy);
}

.badge-bpjs {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
}

.badge-swasta {
  background: #eef2ff;
  color: #4338ca;
}

.consent-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.2rem 0.55rem;
  border-radius: 6px;
  font-size: 0.74rem;
  font-weight: 600;
  white-space: nowrap;
}

.consent-badge svg {
  width: 12px;
  height: 12px;
}

.consent-signed {
  background: #dcfce7;
  color: #166534;
}

.consent-unsigned {
  background: #fef3c7;
  color: #92400e;
}

.consent-uncreated {
  background: #f1f5f9;
  color: var(--color-text-secondary);
}

.consent-cancelled {
  background: var(--color-danger-bg);
  color: var(--color-danger);
}

/* General Consent Status Container (Sesuai arahan: merah jika belum, hijau jika sudah) */
.consent-container-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px 10px;
  font-size: 0.775rem;
  font-weight: 600;
  border-radius: 4px;
  white-space: nowrap;
  letter-spacing: 0.01em;
  transition: all 0.2s ease;
}

.consent-container-red {
  background-color: #ef4444;
  color: #ffffff;
  border: 1px solid #dc2626;
  box-shadow: 0 1px 2px rgba(239, 68, 68, 0.2);
}

.consent-container-green {
  background-color: #16a34a;
  color: #ffffff;
  border: 1px solid #15803d;
  box-shadow: 0 1px 2px rgba(22, 163, 74, 0.2);
}

/* Standardized Tools Button */
.btn-tools {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  height: 32px;
  padding: 0 0.75rem;
  border-radius: 6px;
  background: #ffffff;
  border: 1px solid rgba(8, 127, 120, 0.35);
  color: var(--color-primary);
  font-size: 0.78rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.btn-tools:hover,
.btn-tools.active {
  background: var(--color-primary-light);
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

.tools-chevron {
  width: 13px;
  height: 13px;
}

/* Footer Pagination */
.table-footer-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 1rem 1.4rem;
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

.btn-reset-empty {
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

/* Teleported Dropdown Portal */
.dropdown-portal {
  position: fixed;
  width: 195px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.14);
  z-index: 9999;
  padding: 0.4rem 0;
  animation: popIn 0.12s ease-out;
}

@keyframes popIn {
  from {
    opacity: 0;
    transform: translateY(-4px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.dropdown-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.dropdown-action-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.5rem 0.9rem;
  border: none;
  background: transparent;
  color: var(--color-text-navy);
  font-size: 0.825rem;
  font-weight: 500;
  font-family: inherit;
  text-align: left;
  cursor: pointer;
  transition: background 0.12s ease;
}

.dropdown-action-btn:hover {
  background: #f8fafc;
  color: var(--color-primary-dark);
}

.dropdown-action-btn.text-danger {
  color: var(--color-danger);
}

.dropdown-action-btn.text-danger:hover {
  background: var(--color-danger-bg);
}

.dropdown-action-btn svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

.dropdown-divider {
  height: 1px;
  background: var(--color-border-soft);
  margin: 0.35rem 0;
}

.guarantor-filter-status {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-top: 0.25rem;
  font-size: 0.72rem;
  color: var(--color-danger);
}

.btn-retry-guarantor {
  border: 1px solid currentColor;
  background: transparent;
  color: inherit;
  border-radius: 4px;
  padding: 0.08rem 0.4rem;
  font-size: 0.7rem;
  font-weight: 600;
  cursor: pointer;
}

@media (max-width: 640px) {
  .guarantor-select-wrapper,
  .guarantor-filter-select {
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

