<script setup>
import { 
  formatDateWithAge, 
  maskNik, 
  formatGender 
} from '@/utils/patientFormatters';

defineProps({
  patients: {
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
  },
  selectedPatientId: {
    type: [Number, String],
    default: null
  }
});

const emit = defineEmits([
  'view-detail',
  'select-patient',
  'open-register',
  'reset-search'
]);
</script>

<template>
  <div class="card table-card">
    <div class="table-card-header">
      <div class="header-left">
        <h2 class="table-title">Hasil Pencarian Pasien</h2>
        <span class="count-badge">
          Ditemukan {{ totalCount }} pasien
        </span>
      </div>
    </div>

    <!-- Table Container with Horizontal Scroll Support -->
    <div class="table-container">
      <table class="data-table" aria-label="Tabel Hasil Pencarian Pasien">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th class="col-rm">No. RM</th>
            <th class="col-nik">NIK</th>
            <th class="col-name">Nama Pasien</th>
            <th class="col-birth">Tgl Lahir (Usia)</th>
            <th class="col-gender">Gender</th>
            <th class="col-phone">No. Telepon</th>
            <th class="col-address">Alamat Domisili</th>
            <th class="col-status text-center">Status</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <!-- Loading State (Skeleton Rows) -->
          <template v-if="loading">
            <tr v-for="n in 5" :key="'skeleton-' + n" class="skeleton-row">
              <td class="text-center"><div class="skeleton-bone bone-sm"></div></td>
              <td><div class="skeleton-bone bone-md"></div></td>
              <td><div class="skeleton-bone bone-lg"></div></td>
              <td><div class="skeleton-bone bone-lg"></div></td>
              <td><div class="skeleton-bone bone-md"></div></td>
              <td><div class="skeleton-bone bone-sm"></div></td>
              <td><div class="skeleton-bone bone-md"></div></td>
              <td><div class="skeleton-bone bone-xl"></div></td>
              <td class="text-center"><div class="skeleton-bone bone-badge"></div></td>
              <td class="text-center"><div class="skeleton-bone bone-actions"></div></td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else-if="patients.length === 0">
            <td colspan="10" class="empty-cell">
              <div class="empty-state-wrapper">
                <div class="empty-icon-circle">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                </div>
                <h3 class="empty-state-title">Data pasien tidak ditemukan.</h3>
                <p class="empty-state-desc">
                  Periksa kembali informasi pencarian atau lakukan registrasi pasien baru.
                </p>
                <div class="empty-state-actions">
                  <button 
                    type="button" 
                    class="btn-primary" 
                    @click="emit('open-register')"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                      <circle cx="9" cy="7" r="4"></circle>
                      <line x1="19" y1="8" x2="19" y2="14"></line>
                      <line x1="22" y1="11" x2="16" y2="11"></line>
                    </svg>
                    <span>Registrasi Pasien Baru</span>
                  </button>
                  <button 
                    type="button" 
                    class="btn-cancel" 
                    @click="emit('reset-search')"
                  >
                    Reset Pencarian
                  </button>
                </div>
              </div>
            </td>
          </tr>

          <!-- Data Rows (Exact Column Order 1 to 10) -->
          <template v-else>
            <tr 
              v-for="(patient, index) in patients" 
              :key="patient.id"
              :class="{ 'row-selected': patient.id === selectedPatientId }"
            >
              <!-- 1. No -->
              <td class="col-no text-center font-medium">
                {{ (currentPage - 1) * perPage + index + 1 }}
              </td>

              <!-- 2. No. RM -->
              <td class="col-rm">
                <span class="rm-badge">
                  {{ patient.medical_record_number }}
                </span>
              </td>

              <!-- 3. NIK -->
              <td class="col-nik font-mono">
                {{ maskNik(patient.nik) }}
              </td>

              <!-- 4. Nama Pasien -->
              <td class="col-name font-semibold patient-name">
                {{ patient.name }}
              </td>

              <!-- 5. Tgl Lahir (Usia) -->
              <td class="col-birth">
                {{ formatDateWithAge(patient.birth_date) }}
              </td>

              <!-- 6. Gender -->
              <td class="col-gender">
                <div class="gender-pill" :class="patient.gender === 'L' ? 'gender-male' : 'gender-female'">
                  <svg v-if="patient.gender === 'L'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="10" cy="14" r="5"></circle>
                    <line x1="19" y1="5" x2="13.6" y2="10.4"></line>
                    <polyline points="14 5 19 5 19 10"></polyline>
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="9" r="5"></circle>
                    <line x1="12" y1="14" x2="12" y2="21"></line>
                    <line x1="9" y1="18" x2="15" y2="18"></line>
                  </svg>
                  <span>{{ formatGender(patient.gender) }}</span>
                </div>
              </td>

              <!-- 7. No. Telepon -->
              <td class="col-phone">
                {{ patient.phone || '—' }}
              </td>

              <!-- 8. Alamat Domisili (Text Truncation + Tooltip) -->
              <td class="col-address">
                <div 
                  class="address-truncate" 
                  :title="patient.address"
                >
                  {{ patient.address || '—' }}
                </div>
              </td>

              <!-- 9. Status -->
              <td class="col-status text-center">
                <span 
                  class="status-badge" 
                  :class="patient.is_active ? 'badge-active' : 'badge-inactive'"
                >
                  <span class="status-dot"></span>
                  {{ patient.is_active ? 'Aktif' : 'Tidak Aktif' }}
                </span>
              </td>

              <!-- 10. Aksi -->
              <td class="col-actions">
                <div class="actions-group">
                  <!-- Aksi 1: Detail Pasien (Ikon Mata) -->
                  <button 
                    type="button" 
                    class="btn-action-icon" 
                    @click="emit('view-detail', patient)" 
                    title="Lihat Detail Pasien"
                    aria-label="Detail Pasien"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  </button>

                  <!-- Aksi 2: Pilih Pasien (Button) -->
                  <button 
                    type="button" 
                    class="btn-select-patient" 
                    :class="{ 'btn-selected': patient.id === selectedPatientId }"
                    @click="emit('select-patient', patient)"
                    :title="patient.id === selectedPatientId ? 'Pasien Terpilih' : 'Pilih Pasien untuk Pelayanan'"
                  >
                    <svg v-if="patient.id === selectedPatientId" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>{{ patient.id === selectedPatientId ? 'Terpilih' : 'Pilih Pasien' }}</span>
                  </button>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.table-card {
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.table-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  flex-wrap: wrap;
}

.table-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.count-badge {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
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
  min-width: 1050px;
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
  padding: 1rem;
  border-bottom: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  vertical-align: middle;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr {
  transition: background-color 0.15s ease;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr.row-selected {
  background: rgba(8, 127, 120, 0.06);
}

/* Columns width specifications */
.col-no { width: 48px; }
.col-rm { width: 115px; }
.col-nik { width: 155px; }
.col-name { min-width: 170px; }
.col-birth { min-width: 165px; white-space: nowrap; }
.col-gender { width: 130px; }
.col-phone { width: 135px; white-space: nowrap; }
.col-address { max-width: 240px; }
.col-status { width: 115px; }
.col-actions { width: 160px; }

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.85rem;
  color: #475569;
}

.patient-name {
  color: var(--color-text-navy);
  font-weight: 600;
}

.rm-badge {
  display: inline-block;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-weight: 700;
  font-size: 0.825rem;
  background: #f1f5f9;
  color: var(--color-primary-dark);
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft);
}

.gender-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
  font-weight: 500;
}

.gender-pill svg {
  width: 14px;
  height: 14px;
}

.gender-male {
  color: #0284c7;
}

.gender-female {
  color: #db2777;
}

.address-truncate {
  max-width: 240px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--color-text-secondary);
  font-size: 0.825rem;
}

/* Status Badges */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.badge-active {
  background-color: #dcfce7;
  color: #166534;
}

.badge-active .status-dot {
  background-color: #16a34a;
}

.badge-inactive {
  background-color: #f1f5f9;
  color: #64748b;
}

.badge-inactive .status-dot {
  background-color: #94a3b8;
}

/* Actions Group */
.actions-group {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
}

.btn-action-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-action-icon:hover {
  color: var(--color-primary);
  background: var(--color-primary-light);
  border-color: var(--color-primary);
}

.btn-action-icon svg {
  width: 16px;
  height: 16px;
}

.btn-select-patient {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #ffffff;
  color: var(--color-primary);
  border: 1px solid var(--color-primary);
  padding: 0.38rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-select-patient:hover {
  background: var(--color-primary-light);
}

.btn-select-patient.btn-selected {
  background: var(--color-primary);
  color: #ffffff;
  border-color: var(--color-primary);
}

.btn-select-patient svg {
  width: 13px;
  height: 13px;
}

/* Empty State */
.empty-cell {
  padding: 3.5rem 1.5rem !important;
  text-align: center;
}

.empty-state-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  max-width: 440px;
  margin: 0 auto;
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

.empty-state-title {
  margin: 0 0 0.4rem 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.empty-state-desc {
  margin: 0 0 1.25rem 0;
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  line-height: 1.5;
}

.empty-state-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: center;
}

.empty-state-actions .btn-primary {
  padding: 0.55rem 1.15rem;
  font-size: 0.875rem;
}

.empty-state-actions .btn-cancel {
  padding: 0.55rem 1.15rem;
  font-size: 0.875rem;
}

/* Skeleton Rows for Loading State */
.skeleton-row td {
  padding: 1.1rem 1rem;
}

.skeleton-bone {
  height: 14px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

.bone-sm { width: 30px; margin: 0 auto; }
.bone-md { width: 85px; }
.bone-lg { width: 130px; }
.bone-xl { width: 180px; }
.bone-badge { width: 65px; height: 22px; border-radius: 9999px; margin: 0 auto; }
.bone-actions { width: 100px; height: 28px; border-radius: 6px; margin: 0 auto; }

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.text-center { text-align: center; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
</style>
