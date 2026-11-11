<script setup>
import { ref, computed } from 'vue';
import { maskNik, formatGender } from '@/utils/patientFormatters';

const props = defineProps({
  keyword: {
    type: String,
    default: ''
  },
  birthDate: {
    type: String,
    default: ''
  },
  results: {
    type: Array,
    default: () => []
  },
  hasSearched: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  validationError: {
    type: String,
    default: ''
  }
});

const emit = defineEmits([
  'update:keyword',
  'update:birthDate',
  'search',
  'reset',
  'select-patient',
  'register-new'
]);

const isBirthDateFocused = ref(false);

const internalKeyword = computed({
  get: () => props.keyword,
  set: (val) => emit('update:keyword', val)
});

const internalBirthDate = computed({
  get: () => props.birthDate,
  set: (val) => emit('update:birthDate', val)
});

const isDateInputMode = computed(() => isBirthDateFocused.value || Boolean(props.birthDate));

const hasInputOrResults = computed(() => {
  return Boolean(props.keyword.trim() || props.birthDate || props.hasSearched);
});

const formatDateIndo = (isoDate) => {
  if (!isoDate) return '—';
  const parts = String(isoDate).split('-');
  if (parts.length !== 3) return isoDate;
  return `${parts[2]}-${parts[1]}-${parts[0]}`;
};

const clearKeyword = () => {
  emit('update:keyword', '');
  if (!props.birthDate && props.hasSearched) {
    emit('reset');
  }
};

const handleSubmit = () => {
  emit('search');
};

const handleReset = () => {
  emit('reset');
};
</script>

<template>
  <div class="card admission-search-card">
    <!-- Card Header -->
    <div class="card-header-bar">
      <div class="header-left">
        <div class="header-icon-box" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </div>
        <div>
          <h2 class="card-title">FORM PENCARIAN PASIEN LAMA</h2>
          <p class="card-subtitle">Cari pasien terdaftar berdasarkan NIK, No. Rekam Medis, Nama Pasien, atau Tanggal Lahir</p>
        </div>
      </div>
    </div>

    <!-- Search Form Body -->
    <div class="search-card-body">
      <form autocomplete="off" class="old-patient-search-form" @submit.prevent="handleSubmit">
        <div class="search-controls-row">
          <!-- Input 1: NIK / No. RM / Nama Pasien -->
          <div class="search-input-group">
            <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              id="old-patient-query"
              v-model="internalKeyword"
              type="text"
              class="form-input old-patient-input"
              :class="{ 'has-error': Boolean(validationError) }"
              placeholder="Masukkan Data Pasien (NIK / No. Rekam Medis / Nama Pasien)"
              aria-label="Masukkan Data Pasien (NIK / No. Rekam Medis / Nama Pasien)"
              autocomplete="off"
            />
            <button
              v-if="internalKeyword"
              type="button"
              class="btn-clear-input"
              @click="clearKeyword"
              title="Hapus kata kunci"
              aria-label="Hapus kata kunci"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>

          <!-- Input 2: Tanggal Lahir -->
          <div class="date-input-group">
            <input
              id="old-patient-birthdate"
              v-model="internalBirthDate"
              :type="isDateInputMode ? 'date' : 'text'"
              class="form-input birth-date-input"
              placeholder="Tanggal Lahir"
              aria-label="Tanggal Lahir"
              autocomplete="off"
              @focus="isBirthDateFocused = true"
              @blur="isBirthDateFocused = false"
            />
            <svg
              v-if="!isDateInputMode"
              class="date-trailing-icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>

          <!-- Action Buttons -->
          <div class="search-action-buttons">
            <button
              type="submit"
              class="btn-primary btn-search-old"
              :disabled="loading"
            >
              <svg v-if="!loading" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <span v-else class="btn-spinner"></span>
              <span>Cari</span>
            </button>

            <button
              v-if="hasInputOrResults"
              type="button"
              class="btn-cancel btn-reset-old"
              @click="handleReset"
              title="Reset Pencarian"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                <path d="M3 3v5h5"></path>
              </svg>
              <span>Reset</span>
            </button>
          </div>
        </div>

        <!-- Inline Validation Message -->
        <div v-if="validationError" class="validation-alert" role="alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <span>{{ validationError }}</span>
        </div>
      </form>
    </div>

    <!-- Expandable Results Area (only after user executes search) -->
    <Transition name="fade">
      <div v-if="hasSearched" class="search-results-panel">
        <div class="results-header-bar">
          <div class="results-title-group">
            <span class="results-heading">Hasil Pencarian Pasien Lama</span>
            <span class="results-count-pill">Ditemukan {{ results.length }} pasien</span>
          </div>
          <button type="button" class="btn-close-results" @click="handleReset">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
            <span>Tutup Hasil</span>
          </button>
        </div>

        <div v-if="results.length > 0" class="results-table-wrap">
          <table class="results-table" aria-label="Hasil Pencarian Pasien Lama">
            <thead>
              <tr>
                <th>No. RM</th>
                <th>NIK</th>
                <th>Nama Pasien</th>
                <th>Tgl Lahir / Gender</th>
                <th>No. HP</th>
                <th>Jaminan</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="patient in results" :key="patient.id">
                <td>
                  <span class="rm-badge font-mono">{{ patient.medical_record_number }}</span>
                </td>
                <td class="font-mono text-secondary">{{ maskNik(patient.nik) }}</td>
                <td>
                  <div class="patient-result-name">{{ patient.name }}</div>
                  <div class="patient-result-address" :title="patient.address">{{ patient.address }}</div>
                </td>
                <td>
                  <div>{{ formatDateIndo(patient.birth_date) }}</div>
                  <div class="text-muted-sm">{{ formatGender(patient.gender) }}</div>
                </td>
                <td>{{ patient.phone || '—' }}</td>
                <td>
                  <span class="guarantor-chip">{{ patient.guarantor || 'UMUM' }}</span>
                </td>
                <td class="text-center">
                  <button
                    type="button"
                    class="btn-admit-patient"
                    @click="emit('select-patient', patient)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="5" x2="12" y2="19"></line>
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Daftarkan Admisi</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty Search State -->
        <div v-else class="results-empty-state">
          <div class="empty-info">
            <strong>Pasien lama tidak ditemukan.</strong>
            <span>Periksa kembali NIK, No. Rekam Medis, Nama, atau Tanggal Lahir yang dimasukkan.</span>
          </div>
          <button type="button" class="btn-primary btn-register-fallback" @click="emit('register-new')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <line x1="19" y1="8" x2="19" y2="14"></line>
              <line x1="22" y1="11" x2="16" y2="11"></line>
            </svg>
            <span>Registrasi Pasien Baru</span>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.admission-search-card {
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

.card-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: -0.01em;
}

.card-subtitle {
  margin: 0.15rem 0 0 0;
  font-size: 0.825rem;
  color: var(--color-text-secondary);
}

.search-card-body {
  padding: 1.5rem 1.75rem;
}

.old-patient-search-form {
  max-width: 920px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.search-controls-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.search-input-group {
  position: relative;
  flex: 1;
}

.input-leading-icon {
  position: absolute;
  left: 0.9rem;
  top: 50%;
  transform: translateY(-50%);
  width: 17px;
  height: 17px;
  color: #94a3b8;
  pointer-events: none;
}

.old-patient-input {
  width: 100%;
  height: 40px;
  padding: 0 2.35rem 0 2.55rem;
  font-size: 0.875rem;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  background: #ffffff;
  box-sizing: border-box;
  font-family: inherit;
}

.old-patient-input::placeholder {
  color: #94a3b8;
}

.btn-clear-input {
  position: absolute;
  right: 0.6rem;
  top: 50%;
  transform: translateY(-50%);
  width: 24px;
  height: 24px;
  border: none;
  background: transparent;
  color: #94a3b8;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 0;
  transition: all 0.15s ease;
}

.btn-clear-input:hover {
  background: #f1f5f9;
  color: var(--color-text-navy);
}

.btn-clear-input svg {
  width: 14px;
  height: 14px;
}

.date-input-group {
  position: relative;
  width: 210px;
  flex-shrink: 0;
}

.birth-date-input {
  width: 100%;
  height: 40px;
  padding: 0 0.85rem;
  font-size: 0.875rem;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  background: #ffffff;
  box-sizing: border-box;
  font-family: inherit;
}

.birth-date-input::placeholder {
  color: #94a3b8;
}

.date-trailing-icon {
  position: absolute;
  right: 0.8rem;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #94a3b8;
  pointer-events: none;
}

.search-action-buttons {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.btn-search-old {
  height: 40px;
  padding: 0 1.35rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 8px;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.2);
  transition: all 0.15s ease;
  font-family: inherit;
  white-space: nowrap;
}

.btn-search-old:hover:not(:disabled) {
  background: var(--color-primary-dark);
  box-shadow: 0 4px 12px rgba(8, 127, 120, 0.3);
  transform: translateY(-1px);
}

.btn-search-old svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.btn-reset-old {
  height: 40px;
  padding: 0 1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  background: #ffffff;
  color: var(--color-text-secondary);
  border: 1px solid var(--color-border-soft);
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
}

.btn-reset-old:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: var(--color-text-navy);
}

.btn-reset-old svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

.btn-spinner {
  width: 15px;
  height: 15px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.validation-alert {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.825rem;
  color: var(--color-danger);
  background: var(--color-danger-bg);
  padding: 0.45rem 0.75rem;
  border-radius: 6px;
  width: fit-content;
}

.validation-alert svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

/* Results Panel */
.search-results-panel {
  border-top: 1px solid var(--color-border-soft);
  background: #f8fafc;
}

.results-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #f8fafc;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.results-title-group {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.results-heading {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.results-count-pill {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.6rem;
  border-radius: 9999px;
}

.btn-close-results {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-secondary);
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.3rem 0.65rem;
  border-radius: 6px;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s ease;
}

.btn-close-results:hover {
  background: #f1f5f9;
  color: var(--color-text-navy);
}

.btn-close-results svg {
  width: 14px;
  height: 14px;
}

.results-table-wrap {
  width: 100%;
  overflow-x: auto;
  background: #ffffff;
}

.results-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  min-width: 780px;
}

.results-table th {
  background: #f8fafc;
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  white-space: nowrap;
}

.results-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  vertical-align: middle;
}

.results-table tbody tr:last-child td {
  border-bottom: none;
}

.results-table tbody tr:hover {
  background: #f8fafc;
}

.rm-badge {
  display: inline-block;
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.78rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
  border-radius: 5px;
}

.patient-result-name {
  font-weight: 600;
  color: var(--color-text-navy);
}

.patient-result-address {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  max-width: 240px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 0.1rem;
}

.text-muted-sm {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.guarantor-chip {
  display: inline-block;
  background: #f1f5f9;
  color: var(--color-text-navy);
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  white-space: nowrap;
}

.btn-admit-patient {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  height: 32px;
  padding: 0 0.8rem;
  border-radius: 6px;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.btn-admit-patient:hover {
  background: var(--color-primary-dark);
}

.btn-admit-patient svg {
  width: 14px;
  height: 14px;
}

.results-empty-state {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  background: #ffffff;
}

.empty-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  font-size: 0.85rem;
  color: var(--color-text-navy);
}

.empty-info span {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.btn-register-fallback {
  height: 38px;
  padding: 0 1rem;
  font-size: 0.825rem;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.18s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (max-width: 768px) {
  .search-card-body {
    padding: 1.25rem;
  }

  .search-controls-row {
    flex-direction: column;
    align-items: stretch;
  }

  .date-input-group {
    width: 100%;
  }

  .search-action-buttons {
    width: 100%;
  }

  .btn-search-old,
  .btn-reset-old {
    flex: 1;
  }
}
</style>

