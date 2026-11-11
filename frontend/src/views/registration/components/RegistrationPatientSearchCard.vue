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
  'register-new',
  'open-kyc',
  'view-detail',
  'admit-patient'
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

const formatDateDMY = (isoDate) => {
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
</script>

<template>
  <div class="card registration-search-card">
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
          <h2 class="card-title">FORM PENCARIAN PASIEN</h2>
          <p class="card-subtitle">Cari pasien terdaftar atau mulai registrasi pasien baru dan verifikasi SatuSehat KYC</p>
        </div>
      </div>
    </div>

    <!-- Search Body -->
    <div class="search-card-body">
      <form autocomplete="off" class="patient-search-form" @submit.prevent="emit('search')">
        <div class="search-controls-row">
          <!-- Input 1: No. KTP / NIK / No. RM / Nama Pasien -->
          <div class="search-input-group">
            <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              id="reg-patient-query"
              v-model="internalKeyword"
              type="text"
              class="form-input patient-query-input"
              :class="{ 'has-error': Boolean(validationError) }"
              placeholder="Masukkan Data Pasien (No. KTP / NIK / No. RM / Nama Pasien)"
              aria-label="Masukkan Data Pasien (No. KTP / NIK / No. RM / Nama Pasien)"
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
              id="reg-patient-birthdate"
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
          <div class="search-submit-group">
            <button
              type="submit"
              class="btn-primary btn-cari-pasien"
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
              class="btn-cancel btn-reset-search"
              @click="emit('reset')"
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

        <!-- Quick Registration & SatuSehat KYC Buttons Row -->
        <div class="quick-actions-row">
          <button
            type="button"
            class="btn-primary btn-register-patient"
            @click="emit('register-new')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Registrasi Pasien Baru</span>
          </button>

          <button
            type="button"
            class="btn-satusehat-kyc"
            @click="emit('open-kyc')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              <polyline points="9 12 11 14 15 10"></polyline>
            </svg>
            <span>SatuSehat KYC</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Expandable Search Results Panel -->
    <Transition name="fade">
      <div v-if="hasSearched" class="search-results-panel">
        <div class="results-header-bar">
          <div class="results-title-group">
            <span class="results-heading">Hasil Pencarian Pasien</span>
            <span class="results-count-pill">Ditemukan {{ results.length }} pasien</span>
          </div>
          <button type="button" class="btn-close-results" @click="emit('reset')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
            <span>Tutup</span>
          </button>
        </div>

        <div v-if="results.length > 0" class="results-table-wrap">
          <table class="results-table" aria-label="Hasil Pencarian Pasien">
            <thead>
              <tr>
                <th>No. RM</th>
                <th>NIK</th>
                <th>Nama Pasien</th>
                <th>Tgl Lahir / Gender</th>
                <th>No. HP</th>
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
                  <div>{{ formatDateDMY(patient.birth_date) }}</div>
                  <div class="text-muted-sm">{{ formatGender(patient.gender) }}</div>
                </td>
                <td>{{ patient.phone || '—' }}</td>
                <td>
                  <div class="result-actions-group">
                    <button
                      type="button"
                      class="btn-detail-sm"
                      @click="emit('view-detail', patient)"
                      title="Lihat Detail Pasien"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                      </svg>
                    </button>
                    <button
                      type="button"
                      class="btn-admit-sm"
                      @click="emit('admit-patient', patient)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                      </svg>
                      <span>Admisi</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="results-empty-state">
          <div class="empty-info">
            <strong>Data pasien tidak ditemukan.</strong>
            <span>Silakan periksa kembali kata kunci atau daftarkan sebagai pasien baru.</span>
          </div>
          <button type="button" class="btn-primary btn-register-fallback" @click="emit('register-new')">
            <span>+ Registrasi Pasien Baru</span>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.registration-search-card {
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.card-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.05rem 1.4rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #ffffff;
  gap: 1rem;
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

.card-title {
  margin: 0;
  font-size: 0.975rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: -0.01em;
}

.card-subtitle {
  margin: 0.12rem 0 0 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.search-card-body {
  padding: 1.25rem 1.4rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.patient-search-form {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.search-controls-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.search-input-group {
  position: relative;
  flex: 1;
  min-width: 0;
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

.patient-query-input {
  width: 100%;
  height: 40px;
  padding: 0 2.25rem 0 2.45rem;
  font-size: 0.85rem;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  background: #ffffff;
  box-sizing: border-box;
  font-family: inherit;
}

.patient-query-input::placeholder {
  color: #94a3b8;
}

.btn-clear-input {
  position: absolute;
  right: 0.55rem;
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
  width: 185px;
  flex-shrink: 0;
}

.birth-date-input {
  width: 100%;
  height: 40px;
  padding: 0 0.8rem;
  font-size: 0.85rem;
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
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 15px;
  height: 15px;
  color: #94a3b8;
  pointer-events: none;
}

.search-submit-group {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex-shrink: 0;
}

.btn-cari-pasien {
  height: 40px;
  padding: 0 1.2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  font-size: 0.85rem;
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

.btn-cari-pasien:hover:not(:disabled) {
  background: var(--color-primary-dark);
  box-shadow: 0 4px 10px rgba(8, 127, 120, 0.28);
  transform: translateY(-1px);
}

.btn-cari-pasien svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

.btn-reset-search {
  height: 40px;
  padding: 0 0.85rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  font-size: 0.825rem;
  font-weight: 600;
  border-radius: 8px;
  background: #ffffff;
  color: var(--color-text-secondary);
  border: 1px solid var(--color-border-soft);
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
}

.btn-reset-search:hover {
  background: #f8fafc;
  color: var(--color-text-navy);
}

.btn-reset-search svg {
  width: 14px;
  height: 14px;
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
  font-size: 0.8rem;
  color: var(--color-danger);
  background: var(--color-danger-bg);
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  width: fit-content;
}

.validation-alert svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

/* Quick Actions Row */
.quick-actions-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.btn-register-patient {
  height: 38px;
  padding: 0 1.05rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  font-size: 0.84rem;
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

.btn-register-patient:hover {
  background: var(--color-primary-dark);
  box-shadow: 0 4px 10px rgba(8, 127, 120, 0.28);
  transform: translateY(-1px);
}

.btn-register-patient svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.btn-satusehat-kyc {
  height: 38px;
  padding: 0 1.05rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  font-size: 0.84rem;
  font-weight: 600;
  border-radius: 8px;
  background: #f0fdfa;
  color: #0f766e;
  border: 1px solid rgba(15, 118, 110, 0.35);
  cursor: pointer;
  transition: all 0.15s ease;
  font-family: inherit;
  white-space: nowrap;
}

.btn-satusehat-kyc:hover {
  background: #ccfbf1;
  border-color: #0f766e;
  color: #064e3b;
  transform: translateY(-1px);
}

.btn-satusehat-kyc svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* Search Results Panel */
.search-results-panel {
  border-top: 1px solid var(--color-border-soft);
  background: #f8fafc;
}

.results-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1.4rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #f8fafc;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.results-title-group {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.results-heading {
  font-size: 0.825rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.results-count-pill {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.12rem 0.55rem;
  border-radius: 9999px;
}

.btn-close-results {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-secondary);
  font-size: 0.76rem;
  font-weight: 600;
  padding: 0.25rem 0.6rem;
  border-radius: 6px;
  cursor: pointer;
  font-family: inherit;
}

.btn-close-results:hover {
  background: #f1f5f9;
  color: var(--color-text-navy);
}

.btn-close-results svg {
  width: 13px;
  height: 13px;
}

.results-table-wrap {
  width: 100%;
  overflow-x: auto;
  background: #ffffff;
}

.results-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.825rem;
  min-width: 640px;
}

.results-table th {
  background: #f8fafc;
  padding: 0.65rem 0.9rem;
  text-align: left;
  font-size: 0.76rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  white-space: nowrap;
}

.results-table td {
  padding: 0.75rem 0.9rem;
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
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: 5px;
}

.patient-result-name {
  font-weight: 600;
  color: var(--color-text-navy);
}

.patient-result-address {
  font-size: 0.74rem;
  color: var(--color-text-secondary);
  max-width: 220px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.text-muted-sm {
  font-size: 0.74rem;
  color: var(--color-text-secondary);
}

.result-actions-group {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
}

.btn-detail-sm {
  width: 30px;
  height: 30px;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft);
  background: #ffffff;
  color: var(--color-text-secondary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.btn-detail-sm:hover {
  background: #f1f5f9;
  color: var(--color-text-navy);
}

.btn-detail-sm svg {
  width: 14px;
  height: 14px;
}

.btn-admit-sm {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
  height: 30px;
  padding: 0 0.65rem;
  border-radius: 6px;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
}

.btn-admit-sm:hover {
  background: var(--color-primary-dark);
}

.btn-admit-sm svg {
  width: 13px;
  height: 13px;
}

.results-empty-state {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.85rem;
  padding: 1.1rem 1.4rem;
  background: #ffffff;
}

.empty-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  font-size: 0.84rem;
  color: var(--color-text-navy);
}

.empty-info span {
  font-size: 0.78rem;
  color: var(--color-text-secondary);
}

.btn-register-fallback {
  height: 36px;
  padding: 0 0.9rem;
  font-size: 0.8rem;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.text-center {
  text-align: center !important;
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
  .search-controls-row {
    flex-direction: column;
    align-items: stretch;
  }

  .date-input-group {
    width: 100%;
  }

  .search-submit-group {
    width: 100%;
  }

  .btn-cari-pasien,
  .btn-reset-search {
    flex: 1;
  }

  .quick-actions-row {
    width: 100%;
  }

  .btn-register-patient,
  .btn-satusehat-kyc {
    flex: 1;
  }
}
</style>

