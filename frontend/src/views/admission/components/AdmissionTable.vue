<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

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
  perPage: {
    type: Number,
    default: 10
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'view-detail',
  'edit-admission',
  'print-document',
  'cancel-admission',
  'reset-filter'
]);

// Active dropdown state
const activeDropdownId = ref(null);
const activeDropdownItem = ref(null);
const dropdownCoords = ref({ top: 0, left: 0 });

const toggleDropdown = (event, item) => {
  if (activeDropdownId.value === item.id) {
    closeDropdown();
    return;
  }

  activeDropdownId.value = item.id;
  activeDropdownItem.value = item;

  // Calculate position relative to viewport
  const rect = event.currentTarget.getBoundingClientRect();
  const dropdownWidth = 220;
  const dropdownHeight = 290;

  // Horizontal position (align to right of button if space, else left)
  let left = rect.right - dropdownWidth;
  if (left < 10) left = 10;

  // Vertical position (check bottom space)
  let top = rect.bottom + 4;
  if (top + dropdownHeight > window.innerHeight) {
    top = rect.top - dropdownHeight - 4;
  }

  dropdownCoords.value = {
    top: `${top}px`,
    left: `${left}px`
  };
};

const closeDropdown = () => {
  activeDropdownId.value = null;
  activeDropdownItem.value = null;
};

// Global click outside and escape listeners
const handleDocClick = (e) => {
  if (activeDropdownId.value && !e.target.closest('.dropdown-portal') && !e.target.closest('.btn-kebab')) {
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

// Dropdown Action Triggers
const handleAction = (actionType, docType = '') => {
  const item = activeDropdownItem.value;
  closeDropdown();
  if (!item) return;

  if (actionType === 'detail') {
    emit('view-detail', item);
  } else if (actionType === 'edit') {
    emit('edit-admission', item);
  } else if (actionType === 'print') {
    emit('print-document', { item, documentType: docType });
  } else if (actionType === 'cancel') {
    emit('cancel-admission', item);
  }
};
</script>

<template>
  <div class="card table-card">
    <!-- Header with result count and auto-sync notice -->
    <div class="table-card-header">
      <div class="header-left">
        <h2 class="table-title">Daftar Admisi Rawat Jalan</h2>
        <div class="header-badges-row">
          <span class="count-badge">
            Ditemukan {{ totalCount }} data admisi rawat jalan
          </span>
          <span class="page-entries-sub">
            Menampilkan {{ (currentPage - 1) * perPage + 1 }}–{{ Math.min(currentPage * perPage, totalCount) }} data per halaman
          </span>
        </div>
      </div>

      <div class="header-right">
        <div class="sync-status-badge" title="Mode simulasi frontend tanpa polling backend">
          <span class="sync-dot"></span>
          <span>Auto-sync: Tidak Aktif (Mode Demo)</span>
        </div>
      </div>
    </div>

    <!-- Table Container -->
    <div class="table-container">
      <table class="data-table" aria-label="Tabel Admisi Rawat Jalan">
        <thead>
          <tr>
            <th class="col-no">No.</th>
            <th class="col-queue">No. Antrean</th>
            <th class="col-time">Waktu Admisi</th>
            <th class="col-rm">No. RM</th>
            <th class="col-patient">Nama Pasien</th>
            <th class="col-poly">Poliklinik</th>
            <th class="col-doctor">Dokter</th>
            <th class="col-guarantor">Jenis Penjamin</th>
            <th class="col-consent">General Consent</th>
            <th class="col-status">Status Pelayanan</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <!-- Loading State (Skeleton Rows) -->
          <template v-if="loading">
            <tr v-for="n in 5" :key="'skel-' + n" class="skeleton-row">
              <td class="text-center"><div class="skel-bone bone-sm"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-sm"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-lg"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-badge"></div></td>
              <td><div class="skel-bone bone-badge"></div></td>
              <td class="text-center"><div class="skel-bone bone-btn"></div></td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else-if="admissions.length === 0">
            <td colspan="11" class="empty-cell">
              <div class="empty-wrapper">
                <div class="empty-icon-circle">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                </div>
                <h3 class="empty-title">Tidak ada data admisi yang ditemukan.</h3>
                <p class="empty-desc">
                  Coba ubah kata kunci pencarian atau reset filter untuk menampilkan data lainnya.
                </p>
                <button type="button" class="btn-primary" @click="emit('reset-filter')">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                    <path d="M3 3v5h5"></path>
                  </svg>
                  <span>Reset Filter</span>
                </button>
              </div>
            </td>
          </tr>

          <!-- Data Rows (Exact Columns 1 to 11) -->
          <template v-else>
            <tr 
              v-for="(adm, idx) in admissions" 
              :key="adm.id"
              :class="{ 'row-cancelled': adm.status === 'Dibatalkan' }"
            >
              <!-- 1. No. -->
              <td class="col-no text-center font-medium">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>

              <!-- 2. No. Antrean -->
              <td class="col-queue">
                <span class="queue-badge" :class="'queue-' + adm.queue_number.charAt(0).toLowerCase()">
                  {{ adm.queue_number }}
                </span>
              </td>

              <!-- 3. Waktu Admisi -->
              <td class="col-time font-mono">
                {{ adm.admission_time }}
              </td>

              <!-- 4. No. RM -->
              <td class="col-rm">
                <span class="rm-tag font-mono">
                  {{ adm.medical_record_number }}
                </span>
              </td>

              <!-- 5. Nama Pasien (Nama, Gender • Usia) -->
              <td class="col-patient">
                <div class="patient-cell-content">
                  <span class="patient-name font-semibold">{{ adm.patient_name }}</span>
                  <span class="patient-demographic">
                    {{ adm.gender === 'L' ? 'Laki-laki' : 'Perempuan' }} &bull; {{ adm.age }} Tahun
                  </span>
                </div>
              </td>

              <!-- 6. Poliklinik -->
              <td class="col-poly">
                <span class="poly-name">{{ adm.polyclinic }}</span>
              </td>

              <!-- 7. Dokter -->
              <td class="col-doctor">
                <span class="doctor-name">{{ adm.doctor }}</span>
              </td>

              <!-- 8. Jenis Penjamin -->
              <td class="col-guarantor">
                <div class="guarantor-cell-content">
                  <span class="guarantor-type font-medium">{{ adm.guarantor_type }}</span>
                  <span class="guarantor-detail text-muted">{{ adm.guarantor_detail }}</span>
                </div>
              </td>

              <!-- 9. General Consent -->
              <td class="col-consent">
                <span 
                  class="consent-badge"
                  :class="{
                    'consent-signed': adm.general_consent === 'Sudah TTD',
                    'consent-unsigned': adm.general_consent === 'Belum TTD',
                    'consent-uncreated': adm.general_consent === 'Belum Dibuat',
                    'consent-cancelled': adm.general_consent === 'Dibatalkan'
                  }"
                >
                  <svg v-if="adm.general_consent === 'Sudah TTD'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                  <svg v-else-if="adm.general_consent === 'Belum TTD'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                  <span>{{ adm.general_consent }}</span>
                </span>
              </td>

              <!-- 10. Status Pelayanan -->
              <td class="col-status">
                <span 
                  class="service-status-badge"
                  :class="{
                    'status-waiting': adm.status === 'Menunggu',
                    'status-in-progress': adm.status === 'Sedang Diperiksa',
                    'status-done': adm.status === 'Selesai',
                    'status-void': adm.status === 'Dibatalkan'
                  }"
                >
                  <span class="status-indicator-dot"></span>
                  <span>{{ adm.status }}</span>
                </span>
              </td>

              <!-- 11. Aksi (Three Vertical Dots Button) -->
              <td class="col-actions text-center">
                <button
                  type="button"
                  class="btn-kebab"
                  :class="{ 'btn-kebab-active': activeDropdownId === adm.id }"
                  @click="toggleDropdown($event, adm)"
                  :aria-expanded="activeDropdownId === adm.id"
                  aria-haspopup="true"
                  title="Menu Aksi Admisi"
                  aria-label="Menu Aksi Admisi"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="1"></circle>
                    <circle cx="12" cy="5" r="1"></circle>
                    <circle cx="12" cy="19" r="1"></circle>
                  </svg>
                </button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Teleported Floating Dropdown Menu (Prevents clipping by table overflow) -->
    <Teleport to="body">
      <div 
        v-if="activeDropdownId" 
        class="dropdown-portal"
        :style="dropdownCoords"
      >
        <ul class="dropdown-list" role="menu">
          <!-- Kelompok 1: Aksi Admisi Pasien -->
          <li class="dropdown-header">AKSI ADMISI PASIEN</li>
          <li role="menuitem">
            <button 
              type="button" 
              class="dropdown-action-btn" 
              @click="handleAction('detail')"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              <span>Lihat Detail Admisi</span>
            </button>
          </li>
          <li role="menuitem">
            <button 
              type="button" 
              class="dropdown-action-btn" 
              :disabled="activeDropdownItem?.status === 'Dibatalkan'"
              @click="handleAction('edit')"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
              <span>Edit Data Admisi</span>
            </button>
          </li>

          <!-- Divider -->
          <li class="dropdown-divider"></li>

          <!-- Kelompok 2: Cetak Dokumen -->
          <li class="dropdown-header">CETAK DOKUMEN</li>
          <li role="menuitem">
            <button 
              type="button" 
              class="dropdown-action-btn" 
              @click="handleAction('print', 'No. Antrean')"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
              <span>Cetak No. Antrean</span>
            </button>
          </li>
          <li role="menuitem">
            <button 
              type="button" 
              class="dropdown-action-btn" 
              @click="handleAction('print', 'Tracer Rekam Medis')"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
              <span>Cetak Tracer Rekam Medis</span>
            </button>
          </li>
          <li role="menuitem">
            <button 
              type="button" 
              class="dropdown-action-btn" 
              @click="handleAction('print', 'Label Barcode Pasien')"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 6v12M8 6v12M12 6v12M16 6v12M20 6v12"></path>
              </svg>
              <span>Cetak Label Barcode</span>
            </button>
          </li>
          <li role="menuitem">
            <button 
              type="button" 
              class="dropdown-action-btn" 
              @click="handleAction('print', 'Formulir General Consent')"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <path d="M9 15l2 2 4-4"></path>
              </svg>
              <span>Cetak General Consent</span>
            </button>
          </li>

          <!-- Divider -->
          <li class="dropdown-divider"></li>

          <!-- Kelompok 3: Pembatalan -->
          <li role="menuitem">
            <button 
              type="button" 
              class="dropdown-action-btn text-danger" 
              :disabled="activeDropdownItem?.status === 'Dibatalkan'"
              @click="handleAction('cancel')"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
              <span>Batalkan Admisi</span>
            </button>
          </li>
        </ul>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.table-card {
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  overflow: hidden;
}

.table-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  flex-wrap: wrap;
  gap: 1rem;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.table-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.header-badges-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.count-badge {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.8rem;
  font-weight: 700;
  padding: 0.2rem 0.65rem;
  border-radius: 9999px;
}

.page-entries-sub {
  font-size: 0.825rem;
  color: var(--color-text-secondary);
}

.sync-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-secondary);
  font-size: 0.775rem;
  font-weight: 600;
  padding: 0.3rem 0.75rem;
  border-radius: 8px;
}

.sync-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background-color: #94a3b8;
}

.table-container {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
  min-width: 1200px;
}

.data-table th {
  background: #f8fafc;
  padding: 0.9rem 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  font-size: 0.825rem;
  letter-spacing: 0.02em;
  white-space: nowrap;
}

.data-table td {
  padding: 0.95rem 1rem;
  border-bottom: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  vertical-align: middle;
}

.data-table tbody tr {
  transition: background-color 0.15s ease;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr.row-cancelled {
  opacity: 0.65;
  background-color: #fffafb;
}

/* Columns width specifications */
.col-no { width: 45px; }
.col-queue { width: 110px; }
.col-time { width: 105px; white-space: nowrap; }
.col-rm { width: 115px; }
.col-patient { min-width: 170px; }
.col-poly { min-width: 140px; }
.col-doctor { min-width: 160px; }
.col-guarantor { min-width: 150px; }
.col-consent { width: 125px; }
.col-status { width: 135px; }
.col-actions { width: 65px; }

/* Cell Content Styles */
.queue-badge {
  display: inline-block;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.85rem;
  font-weight: 700;
  padding: 0.25rem 0.55rem;
  border-radius: 6px;
  background: #f1f5f9;
  border: 1px solid var(--color-border-soft);
}

.queue-a {
  background: #dff7f4;
  color: #06655f;
  border-color: #b0eedf;
}

.queue-b {
  background: #e0f2fe;
  color: #0369a1;
  border-color: #bae6fd;
}

.queue-c {
  background: #fef3c7;
  color: #b45309;
  border-color: #fde68a;
}

.queue-d {
  background: #f3e8ff;
  color: #7e22ce;
  border-color: #e9d5ff;
}

.rm-tag {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-navy);
  background: #f1f5f9;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.patient-cell-content {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.patient-name {
  color: var(--color-text-navy);
  font-size: 0.9rem;
}

.patient-demographic {
  font-size: 0.775rem;
  color: var(--color-text-secondary);
}

.guarantor-cell-content {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.guarantor-type {
  font-size: 0.85rem;
  color: var(--color-text-navy);
}

.guarantor-detail {
  font-size: 0.75rem;
}

/* Consent Badges */
.consent-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
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
  color: #b45309;
}

.consent-uncreated {
  background: #f1f5f9;
  color: #64748b;
}

.consent-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

/* Service Status Badges */
.service-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  white-space: nowrap;
}

.status-indicator-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-waiting {
  background: #fef3c7;
  color: #b45309;
}
.status-waiting .status-indicator-dot {
  background: #d97706;
}

.status-in-progress {
  background: #e0f2fe;
  color: #0369a1;
}
.status-in-progress .status-indicator-dot {
  background: #0284c7;
}

.status-done {
  background: #dcfce7;
  color: #166534;
}
.status-done .status-indicator-dot {
  background: #16a34a;
}

.status-void {
  background: #fee2e2;
  color: #991b1b;
}
.status-void .status-indicator-dot {
  background: #dc2626;
}

/* Kebab Button */
.btn-kebab {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: transparent;
  border: 1px solid transparent;
  color: var(--color-text-secondary);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.btn-kebab:hover,
.btn-kebab-active {
  background: #f1f5f9;
  border-color: var(--color-border-soft);
  color: var(--color-text-navy);
}

.btn-kebab svg {
  width: 18px;
  height: 18px;
}

/* Floating Portal Dropdown Menu */
.dropdown-portal {
  position: fixed;
  z-index: 9999;
  width: 220px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  padding: 0.35rem 0;
  animation: dropdownFade 0.15s ease-out;
}

@keyframes dropdownFade {
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
  padding: 0;
  margin: 0;
}

.dropdown-header {
  font-size: 0.675rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #94a3b8;
  padding: 0.45rem 0.85rem 0.2rem 0.85rem;
}

.dropdown-action-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.5rem 0.85rem;
  background: none;
  border: none;
  font-size: 0.825rem;
  font-weight: 500;
  color: var(--color-text-navy);
  cursor: pointer;
  text-align: left;
  transition: all 0.15s;
}

.dropdown-action-btn:hover:not(:disabled) {
  background: #f8fafc;
  color: var(--color-primary);
}

.dropdown-action-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.dropdown-action-btn svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
  color: #64748b;
}

.dropdown-action-btn:hover:not(:disabled) svg {
  color: var(--color-primary);
}

.dropdown-action-btn.text-danger {
  color: var(--color-danger);
}

.dropdown-action-btn.text-danger svg {
  color: var(--color-danger);
}

.dropdown-action-btn.text-danger:hover:not(:disabled) {
  background: var(--color-danger-bg);
}

.dropdown-divider {
  height: 1px;
  background: var(--color-border-soft);
  margin: 0.35rem 0;
}

/* Empty State */
.empty-cell {
  padding: 4rem 1.5rem !important;
  text-align: center;
}

.empty-wrapper {
  max-width: 440px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-icon-circle {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  margin-bottom: 1rem;
}

.empty-icon-circle svg {
  width: 28px;
  height: 28px;
}

.empty-title {
  margin: 0 0 0.35rem 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.empty-desc {
  margin: 0 0 1.25rem 0;
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  line-height: 1.4;
}

/* Skeleton Loading */
.skeleton-row td {
  padding: 1rem;
}

.skel-bone {
  height: 14px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

.bone-sm { width: 35px; margin: 0 auto; }
.bone-md { width: 90px; }
.bone-lg { width: 140px; }
.bone-badge { width: 75px; height: 22px; border-radius: 9999px; }
.bone-btn { width: 28px; height: 28px; border-radius: 6px; margin: 0 auto; }

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.text-center { text-align: center; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
</style>
