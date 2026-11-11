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
  }
});

const emit = defineEmits([
  'update:guarantorFilter',
  'update:perPage',
  'page-change',
  'sort',
  'edit-admission',
  'delete-admission',
  'print-document',
  'reset-filter'
]);

const perPageOptions = [10, 25, 50, 100];

const monthNamesIndo = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const formatDateLong = (isoDate) => {
  if (!isoDate) return '—';
  const parts = String(isoDate).split('-');
  if (parts.length !== 3) return isoDate;
  const year = parts[0];
  const monthIdx = Number(parts[1]) - 1;
  const day = Number(parts[2]);
  const monthName = monthNamesIndo[monthIdx] || parts[1];
  return `${String(day).padStart(2, '0')} ${monthName} ${year}`;
};

const formatBirthDateDMY = (isoDate) => {
  if (!isoDate) return '-';
  const parts = String(isoDate).split('-');
  if (parts.length !== 3) return isoDate;
  return `${parts[2]}-${parts[1]}-${parts[0]}`;
};

const formatGuarantor = (val) => {
  if (!val) return 'UMUM';
  if (val === 'Pasien Umum / Mandiri' || val === 'Umum / Pribadi' || val === 'UMUM') {
    return 'UMUM';
  }
  return val;
};

const getInsuranceBadgeClass = (val) => {
  const label = formatGuarantor(val);
  if (label === 'UMUM') return 'badge-umum';
  if (label === 'BPJS Kesehatan') return 'badge-bpjs';
  return 'badge-swasta';
};

// Pagination page numbers with ellipsis
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
  const dropdownWidth = 185;
  const dropdownHeight = 225;

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

  if (actionType === 'edit') {
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
  <div class="card admission-list-card">
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
          <p class="card-subtitle">Daftar pasien yang telah terdaftar dalam unit admisi pelayanan</p>
        </div>
      </div>

      <!-- Top-Right Filter Jenis Asuransi (sesuai referensi UI) -->
      <div class="header-right">
        <div class="guarantor-select-wrapper">
          <select
            id="admission-guarantor-filter"
            :value="guarantorFilter"
            class="guarantor-filter-select"
            aria-label="Filter Jenis Asuransi"
            @change="emit('update:guarantorFilter', $event.target.value)"
          >
            <option value="all">Semua Jenis Asuransi</option>
            <option value="UMUM">UMUM</option>
            <option value="BPJS Kesehatan">BPJS Kesehatan</option>
            <option value="Asuransi Swasta">Asuransi Swasta</option>
          </select>
          <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
      </div>
    </div>

    <!-- Table Controls Toolbar (10 records) -->
    <div class="table-controls-bar">
      <div class="records-control">
        <div class="records-select-wrapper">
          <select
            id="admission-records-perpage"
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

      <div v-if="guarantorFilter !== 'all'" class="active-filter-info">
        <span class="active-filter-pill">
          Asuransi: <strong>{{ guarantorFilter }}</strong>
          <button
            type="button"
            class="btn-clear-filter"
            @click="emit('reset-filter')"
            title="Hapus filter asuransi"
            aria-label="Hapus filter asuransi"
          >
            &times;
          </button>
        </span>
      </div>
    </div>

    <!-- Data Table (6 Kolom sesuai referensi gambar) -->
    <div class="table-container">
      <table class="data-table" aria-label="Tabel Daftar Pasien Admisi">
        <thead>
          <tr>
            <th class="col-no text-center">No.</th>
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
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading Skeleton -->
          <template v-if="loading">
            <tr v-for="n in 5" :key="'skel-' + n" class="skeleton-row">
              <td class="text-center"><div class="skel-bone bone-xs"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-lg"></div></td>
              <td><div class="skel-bone bone-lg"></div></td>
              <td><div class="skel-bone bone-sm"></div></td>
              <td class="text-center"><div class="skel-bone bone-btn"></div></td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else-if="admissions.length === 0">
            <td colspan="6" class="empty-cell">
              <div class="empty-wrapper">
                <div class="empty-icon-circle">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                </div>
                <h3 class="empty-title">Tidak ada data pasien admisi ditemukan.</h3>
                <p class="empty-desc">
                  Silakan ubah filter jenis asuransi atau lakukan pencarian pasien lama untuk mendaftarkan admisi.
                </p>
                <button
                  v-if="guarantorFilter !== 'all'"
                  type="button"
                  class="btn-primary btn-reset-empty"
                  @click="emit('reset-filter')"
                >
                  Tampilkan Semua Asuransi
                </button>
              </div>
            </td>
          </tr>

          <!-- Data Rows -->
          <template v-else>
            <tr
              v-for="(adm, idx) in admissions"
              :key="adm.id"
              :class="{ 'row-cancelled': adm.status === 'Dibatalkan' }"
            >
              <!-- 1. No. -->
              <td class="col-no text-center text-muted">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>

              <!-- 2. Waktu Admisi -->
              <td class="col-time">
                <div class="time-cell">
                  <span class="adm-date">{{ formatDateLong(adm.admission_date) }}</span>
                  <span class="adm-clock font-mono">{{ adm.admission_time }}</span>
                </div>
              </td>

              <!-- 3. Nama -->
              <td class="col-name">
                <div class="patient-cell">
                  <div class="patient-name-line">
                    <span class="patient-name">{{ adm.patient_name }}</span>
                    <span v-if="adm.status === 'Dibatalkan'" class="cancelled-tag">Dibatalkan</span>
                  </div>
                  <div class="patient-meta-italic">
                    {{ adm.medical_record_number }} | {{ adm.gender }} | {{ formatBirthDateDMY(adm.birth_date) }}
                  </div>
                </div>
              </td>

              <!-- 4. Admisi ke -->
              <td class="col-unit">
                <span class="unit-name">{{ adm.polyclinic }}</span>
              </td>

              <!-- 5. Jenis Asuransi -->
              <td class="col-insurance">
                <span class="insurance-badge" :class="getInsuranceBadgeClass(adm.guarantor_type)">
                  {{ formatGuarantor(adm.guarantor_type) }}
                </span>
              </td>

              <!-- 6. Aksi (Tools Dropdown Button) -->
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

    <!-- Card Footer / Pagination -->
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

    <!-- Teleported Tools Menu -->
    <Teleport to="body">
      <div
        v-if="activeDropdownId"
        class="dropdown-portal"
        :style="dropdownCoords"
      >
        <ul class="dropdown-list" role="menu">
          <li role="menuitem">
            <button type="button" class="dropdown-action-btn" @click="handleAction('edit')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
              <span>Edit</span>
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
            <button type="button" class="dropdown-action-btn" @click="handleAction('print', 'No. Antrean')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
              </svg>
              <span>Cetak Antrian</span>
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
        </ul>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.admission-list-card {
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
  flex-wrap: wrap;
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
  gap: 0.65rem;
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

.header-right {
  display: flex;
  align-items: center;
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
  transition: all 0.15s ease;
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

/* Controls Toolbar */
.table-controls-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.9rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #ffffff;
  gap: 1rem;
  flex-wrap: wrap;
}

.records-control {
  display: flex;
  align-items: center;
  gap: 0.55rem;
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

.active-filter-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-radius: 9999px;
  padding: 0.25rem 0.65rem;
  font-size: 0.78rem;
  color: var(--color-text-secondary);
}

.active-filter-pill strong {
  color: var(--color-primary-dark);
}

.btn-clear-filter {
  border: none;
  background: #e2e8f0;
  color: var(--color-text-navy);
  width: 17px;
  height: 17px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  line-height: 1;
  cursor: pointer;
  padding: 0;
}

.btn-clear-filter:hover {
  background: #cbd5e1;
}

/* Table Styles */
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
  font-size: 0.875rem;
  min-width: 780px;
}

.data-table th {
  background: #f8fafc;
  padding: 0.85rem 1.25rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  font-size: 0.825rem;
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
  gap: 0.5rem;
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
  width: 14px;
  height: 14px;
}

.data-table td {
  padding: 0.9rem 1.25rem;
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

.row-cancelled {
  background: #fffafa;
  opacity: 0.78;
}

.col-no {
  width: 64px;
}

.col-time {
  width: 190px;
}

.col-actions {
  width: 120px;
}

.text-center {
  text-align: center !important;
}

.text-muted {
  color: var(--color-text-secondary);
}

.time-cell {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.adm-date {
  font-weight: 500;
  color: var(--color-text-navy);
  font-size: 0.85rem;
}

.adm-clock {
  font-size: 0.775rem;
  color: var(--color-text-secondary);
}

.patient-cell {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.patient-name-line {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.patient-name {
  font-weight: 600;
  color: var(--color-text-navy);
}

.cancelled-tag {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.1rem 0.45rem;
  border-radius: 4px;
  background: var(--color-danger-bg);
  color: var(--color-danger);
}

.patient-meta-italic {
  font-style: italic;
  font-size: 0.78rem;
  color: var(--color-text-secondary);
}

.unit-name {
  font-weight: 500;
  color: var(--color-text-navy);
}

.insurance-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.775rem;
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
  font-size: 0.8rem;
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
  transition: transform 0.15s ease;
}

.btn-tools.active .tools-chevron {
  transform: rotate(180deg);
}

/* Footer / Pagination */
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
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.25rem;
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
  margin-top: 0.5rem;
  height: 36px;
  font-size: 0.825rem;
}

.skel-bone {
  height: 16px;
  border-radius: 4px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}

.bone-xs { width: 28px; margin: 0 auto; }
.bone-sm { width: 80px; }
.bone-md { width: 130px; }
.bone-lg { width: 180px; }
.bone-btn { width: 72px; height: 28px; margin: 0 auto; }

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
  width: 185px;
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

.dropdown-header {
  padding: 0.35rem 0.9rem;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #94a3b8;
  text-transform: uppercase;
}

.dropdown-action-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0.6rem;
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

.dropdown-action-btn:hover:not(:disabled) {
  background: #f8fafc;
  color: var(--color-primary-dark);
}

.dropdown-action-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.dropdown-action-btn.text-danger {
  color: var(--color-danger);
}

.dropdown-action-btn.text-danger:hover:not(:disabled) {
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

