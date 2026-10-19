<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { calculateAge } from '@/mocks/examinations';

const props = defineProps({
  examinations: {
    type: Array,
    required: true
  },
  totalCount: {
    type: Number,
    required: true
  },
  activeQueueCount: {
    type: Number,
    default: 55
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
  },
  error: {
    type: String,
    default: null
  }
});

const emit = defineEmits([
  'start-exam',
  'enter-clinic',
  'open-rme',
  'view-resume',
  'view-cancel-detail',
  'view-detail',
  'view-queue-info',
  'view-history',
  'reset-filter',
  'refresh'
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
  const dropdownHeight = 220;

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

const closeDropdown = () => {
  activeDropdownId.value = null;
  activeDropdownItem.value = null;
};

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

const handleDropdownAction = (actionType) => {
  const item = activeDropdownItem.value;
  closeDropdown();
  if (!item) return;

  if (actionType === 'detail') {
    emit('view-detail', item);
  } else if (actionType === 'queue-info') {
    emit('view-queue-info', item);
  } else if (actionType === 'history') {
    emit('view-history', item);
  } else if (actionType === 'cancel-detail') {
    emit('view-cancel-detail', item);
  }
};
</script>

<template>
  <div class="card table-card">
    <!-- Table Header Summary Banner -->
    <div class="table-card-header">
      <div class="header-left">
        <h2 class="table-title">Daftar Antrean & Pemeriksaan Poliklinik</h2>
        <div class="header-badges-row">
          <span class="count-badge">
            Menampilkan {{ examinations.length }} dari {{ totalCount }} pasien dalam antrean aktif poliklinik
          </span>
          <span class="active-badge" v-if="activeQueueCount > 0">
            {{ activeQueueCount }} Antrean Berjalan Hari Ini
          </span>
        </div>
      </div>

      <div class="header-right">
        <div class="sync-status-badge" title="Mode simulasi frontend tanpa backend realtime">
          <span class="sync-dot"></span>
          <span>Bridging: Mode Demo (Tidak Terhubung)</span>
        </div>
      </div>
    </div>

    <!-- Table Container -->
    <div class="table-container">
      <table class="data-table" aria-label="Tabel Pemeriksaan Rawat Jalan">
        <thead>
          <tr>
            <!-- Exact 11 Columns in Strict Required Order -->
            <th class="col-queue">1. No. Antrean</th>
            <th class="col-time">2. Waktu</th>
            <th class="col-rm">3. No. RM</th>
            <th class="col-patient">4. Pasien & Prioritas</th>
            <th class="col-demog">5. Usia / Gender</th>
            <th class="col-poly">6. Poliklinik Tujuan</th>
            <th class="col-doctor">7. Dokter Pemeriksa</th>
            <th class="col-guarantor">8. Penjamin</th>
            <th class="col-wait">9. Waktu Tunggu</th>
            <th class="col-status">10. Status</th>
            <th class="col-actions text-center">11. Aksi Klinis</th>
          </tr>
        </thead>
        <tbody>
          <!-- State 1: Error State Fallback -->
          <tr v-if="error">
            <td colspan="11" class="empty-cell">
              <div class="empty-wrapper">
                <div class="empty-icon-circle icon-error">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                </div>
                <h3 class="empty-title">Terjadi Kesalahan Pemuatan Data Lokal</h3>
                <p class="empty-desc">{{ error }}</p>
                <button type="button" class="btn-primary" @click="emit('refresh')">
                  <span>Muat Ulang</span>
                </button>
              </div>
            </td>
          </tr>

          <!-- State 2: Skeleton Loading State -->
          <template v-else-if="loading">
            <tr v-for="n in 5" :key="'skel-' + n" class="skeleton-row">
              <td><div class="skel-bone bone-queue"></div></td>
              <td><div class="skel-bone bone-sm"></div></td>
              <td><div class="skel-bone bone-sm"></div></td>
              <td><div class="skel-bone bone-lg"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-lg"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-sm"></div></td>
              <td><div class="skel-bone bone-badge"></div></td>
              <td class="text-center"><div class="skel-bone bone-btn"></div></td>
            </tr>
          </template>

          <!-- State 3: Empty State -->
          <tr v-else-if="examinations.length === 0">
            <td colspan="11" class="empty-cell">
              <div class="empty-wrapper">
                <div class="empty-icon-circle">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                </div>
                <h3 class="empty-title">Tidak ada pasien pemeriksaan yang ditemukan.</h3>
                <p class="empty-desc">
                  Periksa kembali kata kunci atau ubah filter pencarian.
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

          <!-- State 4: Default Data Rows -->
          <template v-else>
            <tr
              v-for="item in examinations"
              :key="item.id"
              :class="{
                'row-in-service': item.status === 'Sedang Diperiksa',
                'row-called': item.status === 'Dipanggil',
                'row-cancelled': item.status === 'Dibatalkan'
              }"
            >
              <!-- 1. No. Antrean -->
              <td class="col-queue">
                <span class="queue-badge" :class="'queue-' + item.queue_number.charAt(0).toLowerCase()">
                  {{ item.queue_number }}
                </span>
              </td>

              <!-- 2. Waktu -->
              <td class="col-time font-mono">
                {{ item.admission_time }}
              </td>

              <!-- 3. No. RM -->
              <td class="col-rm">
                <span class="rm-tag font-mono">
                  {{ item.medical_record_number }}
                </span>
              </td>

              <!-- 4. Pasien & Prioritas -->
              <td class="col-patient">
                <div class="patient-cell-content">
                  <span class="patient-name font-semibold">{{ item.patient_name }}</span>
                  <div class="patient-priority-row" v-if="item.priority && item.priority !== 'Normal'">
                    <span
                      class="priority-badge"
                      :class="{
                        'priority-cito': item.priority === 'Prioritas Medis Cito',
                        'priority-elderly': item.priority === 'Lansia',
                        'priority-fall': item.priority === 'Risiko Jatuh',
                        'priority-pediatric': item.priority === 'Pediatri / Balita',
                        'priority-pregnant': item.priority === 'Ibu Hamil'
                      }"
                    >
                      <svg v-if="item.priority === 'Prioritas Medis Cito'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                      </svg>
                      <span>{{ item.priority_note || item.priority }}</span>
                    </span>
                  </div>
                </div>
              </td>

              <!-- 5. Usia / Gender -->
              <td class="col-demog">
                <div class="demog-cell-content">
                  <span class="font-medium">{{ calculateAge(item.birth_date) }} Thn</span>
                  <span class="text-muted text-xs">
                    {{ item.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                  </span>
                </div>
              </td>

              <!-- 6. Poliklinik Tujuan -->
              <td class="col-poly">
                <div class="poly-cell-content">
                  <span class="poly-name font-medium">{{ item.polyclinic }}</span>
                  <span class="poly-location text-muted text-xs">
                    {{ item.building }} {{ item.floor }} &bull; {{ item.room }}
                  </span>
                </div>
              </td>

              <!-- 7. Dokter Pemeriksa -->
              <td class="col-doctor">
                <div class="doctor-cell-content">
                  <div class="doctor-avatar">
                    {{ item.doctor_initials }}
                  </div>
                  <span class="doctor-name">{{ item.doctor }}</span>
                </div>
              </td>

              <!-- 8. Penjamin -->
              <td class="col-guarantor">
                <div class="guarantor-cell-content">
                  <span class="guarantor-type font-medium">{{ item.guarantor_type }}</span>
                  <span class="guarantor-detail text-muted text-xs">{{ item.guarantor_detail }}</span>
                </div>
              </td>

              <!-- 9. Waktu Tunggu -->
              <td class="col-wait">
                <div class="wait-cell-content">
                  <template v-if="item.status === 'Selesai'">
                    <span class="wait-completed text-xs font-semibold">
                      Selesai: {{ item.completed_time || '10:30' }}
                    </span>
                    <span class="wait-duration text-muted text-xs">
                      Durasi: {{ item.wait_time_minutes }} Menit
                    </span>
                  </template>
                  <template v-else-if="item.status === 'Dibatalkan'">
                    <span class="text-rose text-xs font-medium">Batal</span>
                  </template>
                  <template v-else>
                    <span
                      class="wait-badge"
                      :class="{ 'wait-warning': item.wait_time_minutes > 60 }"
                      :title="item.wait_time_minutes > 60 ? 'Waktu tunggu simulasi melampaui 60 menit' : ''"
                    >
                      <svg v-if="item.wait_time_minutes > 60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                      </svg>
                      <span>{{ item.wait_time_minutes }} Menit</span>
                    </span>
                  </template>
                </div>
              </td>

              <!-- 10. Status -->
              <td class="col-status">
                <span
                  class="status-badge"
                  :class="{
                    'status-waiting': item.status === 'Menunggu',
                    'status-called': item.status === 'Dipanggil',
                    'status-inservice': item.status === 'Sedang Diperiksa',
                    'status-completed': item.status === 'Selesai',
                    'status-cancelled': item.status === 'Dibatalkan'
                  }"
                >
                  <span class="status-dot"></span>
                  <span>{{ item.status }}</span>
                </span>
              </td>

              <!-- 11. Aksi Klinis -->
              <td class="col-actions text-center">
                <div class="actions-wrapper">
                  <!-- Contextual Action Button based on status -->
                  <!-- Menunggu: Mulai Periksa -->
                  <button
                    v-if="item.status === 'Menunggu'"
                    type="button"
                    class="btn-clinical btn-start-exam"
                    @click="emit('start-exam', item)"
                    title="Mulai Pemeriksaan Pasien"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <span>Mulai Periksa</span>
                  </button>

                  <!-- Dipanggil: Masuk Poli -->
                  <button
                    v-else-if="item.status === 'Dipanggil'"
                    type="button"
                    class="btn-clinical btn-enter-clinic"
                    @click="emit('enter-clinic', item)"
                    title="Konfirmasi Pasien Masuk Poli"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                      <polyline points="10 17 15 12 10 7"></polyline>
                      <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span>Masuk Poli</span>
                  </button>

                  <!-- Sedang Diperiksa: Lanjutkan RME -->
                  <button
                    v-else-if="item.status === 'Sedang Diperiksa'"
                    type="button"
                    class="btn-clinical btn-open-rme"
                    @click="emit('open-rme', item)"
                    title="Buka Rekam Medis Elektronik"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                      <polyline points="14 2 14 8 20 8"></polyline>
                      <line x1="16" y1="13" x2="8" y2="13"></line>
                      <line x1="16" y1="17" x2="8" y2="17"></line>
                      <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Lanjutkan RME</span>
                  </button>

                  <!-- Selesai: Resume Medis -->
                  <button
                    v-else-if="item.status === 'Selesai'"
                    type="button"
                    class="btn-clinical btn-resume"
                    @click="emit('view-resume', item)"
                    title="Lihat Pratinjau Resume Medis"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                      <polyline points="14 2 14 8 20 8"></polyline>
                      <polyline points="9 15 12 18 15 15"></polyline>
                    </svg>
                    <span>Resume Medis</span>
                  </button>

                  <!-- Dibatalkan: Detail Batal -->
                  <button
                    v-else-if="item.status === 'Dibatalkan'"
                    type="button"
                    class="btn-clinical btn-cancel-detail"
                    @click="emit('view-cancel-detail', item)"
                    title="Lihat Alasan Pembatalan Pasien"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="12" y1="8" x2="12" y2="12"></line>
                      <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>Detail Batal</span>
                  </button>

                  <!-- 3 Dots Dropdown Menu (Kebab) -->
                  <button
                    type="button"
                    class="btn-kebab"
                    :class="{ 'is-active': activeDropdownId === item.id }"
                    @click="toggleDropdown($event, item)"
                    aria-label="Aksi tambahan"
                    title="Aksi Tambahan"
                  >
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <circle cx="12" cy="5" r="2"></circle>
                      <circle cx="12" cy="12" r="2"></circle>
                      <circle cx="12" cy="19" r="2"></circle>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Teleport Portal for 3-Dots Dropdown Menu -->
    <Teleport to="body">
      <div
        v-if="activeDropdownId"
        class="dropdown-portal"
        :style="dropdownCoords"
      >
        <div class="dropdown-header">
          <span class="dropdown-patient-title">{{ activeDropdownItem?.patient_name }}</span>
          <span class="dropdown-queue-title">{{ activeDropdownItem?.queue_number }} &bull; {{ activeDropdownItem?.medical_record_number }}</span>
        </div>
        <div class="dropdown-menu-list">
          <!-- Item 1: Detail Pasien -->
          <button
            type="button"
            class="dropdown-item"
            @click="handleDropdownAction('detail')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Lihat Detail Pasien</span>
          </button>

          <!-- Item 2: Info Antrean -->
          <button
            type="button"
            class="dropdown-item"
            @click="handleDropdownAction('queue-info')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <span>Lihat Informasi Antrean</span>
          </button>

          <!-- Item 3: Riwayat Kunjungan (Placeholder) -->
          <button
            type="button"
            class="dropdown-item"
            @click="handleDropdownAction('history')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 8v4l3 3"></path>
              <circle cx="12" cy="12" r="9"></circle>
              <path d="M3.05 11a9 9 0 0 1 .5-2m14-5.05A9 9 0 0 1 21 12"></path>
            </svg>
            <span>Riwayat Kunjungan</span>
          </button>

          <!-- Item 4: Detail Pembatalan (if cancelled) -->
          <button
            v-if="activeDropdownItem?.status === 'Dibatalkan'"
            type="button"
            class="dropdown-item text-danger"
            @click="handleDropdownAction('cancel-detail')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="15" y1="9" x2="9" y2="15"></line>
              <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <span>Detail Pembatalan</span>
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.table-card {
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  display: flex;
  flex-direction: column;
}

.table-card-header {
  padding: 1rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  flex-wrap: wrap;
  gap: 0.75rem;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.table-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-navy, #1e293b);
}

.header-badges-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.count-badge {
  font-size: 0.825rem;
  font-weight: 600;
  color: var(--color-primary, #087f78);
  background: var(--color-primary-light, #e6f6f5);
  padding: 0.2rem 0.65rem;
  border-radius: 6px;
}

.active-badge {
  font-size: 0.775rem;
  color: var(--color-text-secondary, #64748b);
  background: #f1f5f9;
  padding: 0.2rem 0.55rem;
  border-radius: 6px;
}

.sync-status-badge {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.75rem;
  color: var(--color-text-secondary, #64748b);
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 0.3rem 0.65rem;
  border-radius: 20px;
}

.sync-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #94a3b8;
}

/* Table container */
.table-container {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  text-align: left;
  font-size: 0.85rem;
  min-width: 1100px;
}

.data-table th {
  background: #f8fafc;
  color: #475569;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  padding: 0.85rem 0.85rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  white-space: nowrap;
}

.data-table td {
  padding: 0.85rem 0.85rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  vertical-align: middle;
}

.data-table tr:hover td {
  background: #f8fafc;
}

.row-in-service td {
  background: #f0fdf4;
}

.row-called td {
  background: #eff6ff;
}

.row-cancelled td {
  opacity: 0.75;
}

/* Column specific styles */
.queue-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem 0.65rem;
  font-size: 0.85rem;
  font-weight: 800;
  font-family: monospace;
  border-radius: 6px;
  letter-spacing: 0.05em;
}

.queue-a {
  background: #e0f2fe;
  color: #0369a1;
}

.queue-b {
  background: #fef3c7;
  color: #b45309;
}

.queue-c {
  background: #dcfce7;
  color: #15803d;
}

.queue-d {
  background: #f3e8ff;
  color: #7e22ce;
}

.rm-tag {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  background: #f1f5f9;
  color: #334155;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 600;
}

.patient-cell-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 170px;
}

.patient-name {
  color: var(--color-text-navy, #0f172a);
}

.patient-priority-row {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.priority-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.priority-badge svg {
  width: 12px;
  height: 12px;
}

.priority-cito {
  background: #ffe4e6;
  color: #e11d48;
  border: 1px solid #fecdd3;
  font-weight: 700;
}

.priority-elderly {
  background: #fef3c7;
  color: #b45309;
}

.priority-fall {
  background: #ffedd5;
  color: #c2410c;
}

.priority-pediatric {
  background: #e0f2fe;
  color: #0284c7;
}

.priority-pregnant {
  background: #fce7f3;
  color: #be185d;
}

.demog-cell-content {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  white-space: nowrap;
}

.poly-cell-content {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 150px;
}

.poly-name {
  color: #1e293b;
}

.doctor-cell-content {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  min-width: 170px;
}

.doctor-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #ccfbf1;
  color: var(--color-primary, #087f78);
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.doctor-name {
  font-size: 0.825rem;
  font-weight: 500;
  color: #1e293b;
}

.guarantor-cell-content {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 130px;
}

.wait-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
  background: #f1f5f9;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
}

.wait-warning {
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
}

.wait-warning svg {
  width: 14px;
  height: 14px;
}

/* Status Badges */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.25rem 0.65rem;
  border-radius: 20px;
  font-size: 0.775rem;
  font-weight: 600;
  white-space: nowrap;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-waiting {
  background: #fef3c7;
  color: #b45309;
}
.status-waiting .status-dot {
  background: #f59e0b;
}

.status-called {
  background: #e0f2fe;
  color: #0369a1;
}
.status-called .status-dot {
  background: #0284c7;
}

.status-inservice {
  background: var(--color-primary-light, #e6f6f5);
  color: var(--color-primary, #087f78);
}
.status-inservice .status-dot {
  background: var(--color-primary, #087f78);
}

.status-completed {
  background: #dcfce7;
  color: #15803d;
}
.status-completed .status-dot {
  background: #16a34a;
}

.status-cancelled {
  background: #ffe4e6;
  color: #e11d48;
}
.status-cancelled .status-dot {
  background: #e11d48;
}

/* Actions */
.actions-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
}

.btn-clinical {
  height: 32px;
  padding: 0 0.75rem;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.775rem;
  font-weight: 600;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.btn-clinical svg {
  width: 14px;
  height: 14px;
}

.btn-start-exam {
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
}
.btn-start-exam:hover {
  background: #fde68a;
}

.btn-enter-clinic {
  background: #e0f2fe;
  color: #0369a1;
  border: 1px solid #bae6fd;
}
.btn-enter-clinic:hover {
  background: #bae6fd;
}

.btn-open-rme {
  background: var(--color-primary, #087f78);
  color: #ffffff;
}
.btn-open-rme:hover {
  background: var(--color-primary-dark, #06635e);
}

.btn-resume {
  background: #dcfce7;
  color: #15803d;
  border: 1px solid #bbf7d0;
}
.btn-resume:hover {
  background: #bbf7d0;
}

.btn-cancel-detail {
  background: #fff1f2;
  color: #be123c;
  border: 1px solid #fecdd3;
}
.btn-cancel-detail:hover {
  background: #fecdd3;
}

.btn-kebab {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 6px;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-kebab:hover, .btn-kebab.is-active {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #cbd5e1;
}

.btn-kebab svg {
  width: 16px;
  height: 16px;
}

/* Teleported Dropdown Portal */
.dropdown-portal {
  position: fixed;
  z-index: 9999;
  width: 220px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  animation: dropdownFade 0.15s ease-out;
}

@keyframes dropdownFade {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-header {
  padding: 0.65rem 0.85rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.dropdown-patient-title {
  font-size: 0.8rem;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dropdown-queue-title {
  font-size: 0.72rem;
  color: #64748b;
  font-family: monospace;
}

.dropdown-menu-list {
  padding: 0.35rem 0;
}

.dropdown-item {
  width: 100%;
  padding: 0.55rem 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-size: 0.8rem;
  color: #334155;
  background: transparent;
  border: none;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s ease;
}

.dropdown-item:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.dropdown-item svg {
  width: 15px;
  height: 15px;
  color: #64748b;
}

.dropdown-item.text-danger {
  color: #e11d48;
}
.dropdown-item.text-danger svg {
  color: #e11d48;
}

/* Empty & Skeleton States */
.empty-cell {
  padding: 3rem 1rem !important;
  text-align: center;
}

.empty-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  max-width: 400px;
  margin: 0 auto;
}

.empty-icon-circle {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-icon-circle.icon-error {
  background: #fee2e2;
  color: #ef4444;
}

.empty-icon-circle svg {
  width: 28px;
  height: 28px;
}

.empty-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.empty-desc {
  margin: 0;
  font-size: 0.85rem;
  color: #64748b;
  line-height: 1.4;
}

/* Skeleton Loading Bones */
.skeleton-row td {
  padding: 1rem 0.85rem;
}

.skel-bone {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
  height: 16px;
}

.bone-queue {
  width: 60px;
  height: 26px;
  border-radius: 6px;
}

.bone-sm {
  width: 70px;
}

.bone-md {
  width: 110px;
}

.bone-lg {
  width: 160px;
}

.bone-badge {
  width: 80px;
  height: 22px;
  border-radius: 20px;
}

.bone-btn {
  width: 90px;
  height: 28px;
  border-radius: 6px;
  margin: 0 auto;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>
