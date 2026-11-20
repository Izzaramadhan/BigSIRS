<script setup>
import { ref, computed, watch } from 'vue';
import { calculateDetailedAge, formatGenderCode } from '@/utils/patientFormatters';

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
  'view-detail',
  'register-new'
]);

const isBirthDateFocused = ref(false);
const perPage = ref(5);
const currentPage = ref(1);

// Sort state
const sortKey = ref('no');
const sortOrder = ref('asc');

// Toast notification for unpaid bills
const showUnpaidToast = ref(false);
const unpaidToastMessage = ref(
  'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.'
);

const internalKeyword = computed({
  get: () => props.keyword,
  set: (val) => emit('update:keyword', val)
});

const internalBirthDate = computed({
  get: () => props.birthDate,
  set: (val) => emit('update:birthDate', val)
});

const isDateInputMode = computed(() => isBirthDateFocused.value || Boolean(props.birthDate));

// Reset page when results change
watch(
  () => props.results,
  (newResults) => {
    currentPage.value = 1;
    // If any patient has unpaid bills, display the legacy notification toast
    if (newResults && newResults.some((p) => p.has_unpaid_bills)) {
      showUnpaidToast.value = true;
    } else {
      showUnpaidToast.value = false;
    }
  },
  { immediate: true }
);

// Watch perPage changes to reset to page 1
watch(perPage, () => {
  currentPage.value = 1;
});

// Sorted results
const sortedResults = computed(() => {
  if (!props.results || props.results.length === 0) return [];
  const list = [...props.results];

  return list.sort((a, b) => {
    let valA = '';
    let valB = '';

    if (sortKey.value === 'rm') {
      valA = String(a.medical_record_number || a.no_rm || '');
      valB = String(b.medical_record_number || b.no_rm || '');
    } else if (sortKey.value === 'ktp') {
      valA = String(a.no_ktp || a.nik || '');
      valB = String(b.no_ktp || b.nik || '');
    } else if (sortKey.value === 'name') {
      valA = String(a.name || a.nama || '').toLowerCase();
      valB = String(b.name || b.nama || '').toLowerCase();
    } else if (sortKey.value === 'birth') {
      valA = String(a.birth_date || a.tgl_lahir || '');
      valB = String(b.birth_date || b.tgl_lahir || '');
    } else if (sortKey.value === 'address') {
      valA = String(a.address || a.alamat || '').toLowerCase();
      valB = String(b.address || b.alamat || '').toLowerCase();
    } else {
      valA = a.id || 0;
      valB = b.id || 0;
    }

    if (valA < valB) return sortOrder.value === 'asc' ? -1 : 1;
    if (valA > valB) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
});

// Paginated results
const totalEntries = computed(() => sortedResults.value.length);
const totalPages = computed(() => Math.ceil(totalEntries.value / perPage.value) || 1);

const startIndex = computed(() => (currentPage.value - 1) * perPage.value);
const endIndex = computed(() => Math.min(startIndex.value + perPage.value, totalEntries.value));

const paginatedList = computed(() => {
  return sortedResults.value.slice(startIndex.value, endIndex.value);
});

const showingFrom = computed(() => (totalEntries.value === 0 ? 0 : startIndex.value + 1));
const showingTo = computed(() => endIndex.value);

const handleSort = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortOrder.value = 'asc';
  }
};

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

const handleSubmit = () => {
  emit('search', {
    keyword: internalKeyword.value,
    birthDate: internalBirthDate.value
  });
};

let unpaidToastTimer = null;

const handleSelectPatient = (patient) => {
  if (patient.has_unpaid_bills) {
    unpaidToastMessage.value =
      patient.unpaid_billing_message ||
      'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.';
    showUnpaidToast.value = true;
    if (unpaidToastTimer) clearTimeout(unpaidToastTimer);
    unpaidToastTimer = setTimeout(() => {
      showUnpaidToast.value = false;
    }, 8000);
    return;
  }
  showUnpaidToast.value = false;
  emit('select-patient', patient);
};

const handleViewDetail = (patient) => {
  emit('view-detail', patient);
};

const closeUnpaidToast = () => {
  if (unpaidToastTimer) clearTimeout(unpaidToastTimer);
  showUnpaidToast.value = false;
};
</script>

<template>
  <div class="card admission-search-card">
    <!-- Card Header: Title with search icon, no subtitle -->
    <div class="card-header-bar">
      <div class="header-left">
        <svg
          class="header-search-icon"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <h2 class="card-title">FORM PENCARIAN PASIEN LAMA</h2>
      </div>
    </div>

    <!-- Search Form Bar -->
    <div class="search-card-body">
      <form autocomplete="off" class="old-patient-search-form" @submit.prevent="handleSubmit">
        <div class="search-controls-row">
          <!-- Text Input (NIK / No. RM / Nama Pasien) -->
          <div class="search-input-group">
            <input
              id="old-patient-query"
              v-model="internalKeyword"
              type="text"
              class="form-input old-patient-input"
              :class="{ 'has-error': Boolean(validationError) }"
              placeholder="Masukkan Data Pasien (No. KTP / NIK / No. RM / Nama Pasien)"
              aria-label="Masukkan Data Pasien (No. KTP / NIK / No. RM / Nama Pasien)"
              autocomplete="off"
            />
          </div>

          <!-- Date Input (Tanggal Lahir) -->
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

          <!-- Search Button (Blue button without Reset button) -->
          <div class="search-action-buttons">
            <button
              type="submit"
              class="btn-primary btn-search-old"
              :disabled="loading"
            >
              <svg
                v-if="!loading"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <span v-else class="btn-spinner"></span>
              <span>Cari</span>
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

    <!-- Divider Line -->
    <div v-if="hasSearched" class="card-divider"></div>

    <!-- Results Panel: Shown after search has been executed -->
    <div v-if="hasSearched" class="search-results-panel">
      <template v-if="results && results.length > 0">
        <!-- Entries selector on top-left (no header banner) -->
        <div class="entries-control-row">
          <div class="entries-selector">
            <select id="entries-per-page" v-model.number="perPage" class="entries-select" aria-label="Entries per page">
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="entries-label">entries</span>
          </div>
        </div>

        <!-- Table Section: Exactly 7 columns -->
        <div class="results-table-wrap">
          <table class="results-table legacy-table" aria-label="Tabel Pencarian Pasien Lama">
            <thead>
              <tr>
                <th class="th-sortable col-no" @click="handleSort('no')">
                  <div class="th-content">
                    <span>No.</span>
                    <span class="sort-arrows" :class="{ active: sortKey === 'no' }">
                      <span :class="{ 'arrow-active': sortKey === 'no' && sortOrder === 'asc' }">&#9650;</span>
                    </span>
                  </div>
                </th>
                <th class="th-sortable col-rm" @click="handleSort('rm')">
                  <div class="th-content">
                    <span>No. RM</span>
                    <span class="sort-arrows" :class="{ active: sortKey === 'rm' }">
                      <span :class="{ 'arrow-active': sortKey === 'rm' && sortOrder === 'asc' }">&#9650;</span>
                    </span>
                  </div>
                </th>
                <th class="th-sortable col-ktp" @click="handleSort('ktp')">
                  <div class="th-content">
                    <span>No. KTP</span>
                    <span class="sort-arrows" :class="{ active: sortKey === 'ktp' }">
                      <span :class="{ 'arrow-active': sortKey === 'ktp' && sortOrder === 'asc' }">&#9650;</span>
                    </span>
                  </div>
                </th>
                <th class="th-sortable col-name" @click="handleSort('name')">
                  <div class="th-content">
                    <span>Nama Lengkap</span>
                    <span class="sort-arrows" :class="{ active: sortKey === 'name' }">
                      <span :class="{ 'arrow-active': sortKey === 'name' && sortOrder === 'asc' }">&#9650;</span>
                    </span>
                  </div>
                </th>
                <th class="th-sortable col-birth" @click="handleSort('birth')">
                  <div class="th-content">
                    <span>Lahir</span>
                    <span class="sort-arrows" :class="{ active: sortKey === 'birth' }">
                      <span :class="{ 'arrow-active': sortKey === 'birth' && sortOrder === 'asc' }">&#9650;</span>
                    </span>
                  </div>
                </th>
                <th class="th-sortable col-address" @click="handleSort('address')">
                  <div class="th-content">
                    <span>Alamat</span>
                    <span class="sort-arrows" :class="{ active: sortKey === 'address' }">
                      <span :class="{ 'arrow-active': sortKey === 'address' && sortOrder === 'asc' }">&#9650;</span>
                    </span>
                  </div>
                </th>
                <th class="th-sortable col-action text-center">
                  <div class="th-content justify-center">
                    <span>Aksi</span>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(patient, idx) in paginatedList" :key="patient.id || idx">
                <!-- Column 1: No. -->
                <td class="col-no text-center">
                  {{ startIndex + idx + 1 }}
                </td>

                <!-- Column 2: No. RM -->
                <td class="col-rm font-mono">
                  {{ patient.medical_record_number || patient.no_rm || '—' }}
                </td>

                <!-- Column 3: No. KTP -->
                <td class="col-ktp font-mono">
                  {{ patient.no_ktp || patient.nik || '—' }}
                </td>

                <!-- Column 4: Nama Lengkap -->
                <td class="col-name">
                  <div class="patient-name-title">
                    {{ patient.name || patient.nama || '—' }},
                  </div>
                  <div class="patient-age-gender">
                    ({{ calculateDetailedAge(patient.birth_date || patient.tgl_lahir) }}) ({{ formatGenderCode(patient.gender) }})
                  </div>
                </td>

                <!-- Column 5: Lahir -->
                <td class="col-birth">
                  <div v-if="patient.birth_place || patient.tempat_lahir" class="patient-birth-place">
                    {{ patient.birth_place || patient.tempat_lahir }},
                  </div>
                  <div class="patient-birth-date font-mono">
                    {{ patient.birth_date || patient.tgl_lahir || '—' }}
                  </div>
                </td>

                <!-- Column 6: Alamat -->
                <td class="col-address">
                  <div class="patient-address-text">
                    {{ patient.address || patient.alamat || '—' }}
                  </div>
                </td>

                <!-- Column 7: Aksi -->
                <td class="col-action text-center">
                  <div class="action-btn-group">
                    <!-- Button 1: Eye icon (Detail / View) -->
                    <button
                      type="button"
                      class="btn-action-view"
                      title="Lihat Detail Pasien"
                      aria-label="Lihat Detail Pasien"
                      @click="handleViewDetail(patient)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                      </svg>
                    </button>

                    <!-- Button 2: Paper plane icon (Green square button for Admit) -->
                    <button
                      type="button"
                      class="btn-action-admit btn-admit-patient"
                      title="Daftarkan Admisi"
                      aria-label="Daftarkan Admisi"
                      @click="handleSelectPatient(patient)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer: Showing entries & Pagination -->
        <div class="results-footer-bar">
          <div class="entries-info-text">
            Showing {{ showingFrom }} to {{ showingTo }} of {{ totalEntries }} entries
          </div>

          <div v-if="totalPages > 1" class="pagination-buttons">
            <button
              type="button"
              class="btn-page-nav"
              :disabled="currentPage <= 1"
              aria-label="Halaman Sebelumnya"
              @click="goToPage(currentPage - 1)"
            >
              &lt;
            </button>

            <button
              v-for="page in totalPages"
              :key="page"
              type="button"
              class="btn-page-number"
              :class="{ active: page === currentPage }"
              @click="goToPage(page)"
            >
              {{ page }}
            </button>

            <button
              type="button"
              class="btn-page-nav"
              :disabled="currentPage >= totalPages"
              aria-label="Halaman Berikutnya"
              @click="goToPage(currentPage + 1)"
            >
              &gt;
            </button>
          </div>
        </div>
      </template>

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

    <!-- Floating Red Notification Toast: Legacy Notification (Bottom Right) -->
    <Teleport to="body">
      <Transition name="slide-fade">
        <div v-if="showUnpaidToast" class="unpaid-billing-toast" role="alert">
          <div class="toast-header">
            <div class="toast-title-text">Notifications</div>
            <button type="button" class="toast-close-btn" aria-label="Tutup notifikasi" @click="closeUnpaidToast">
              &times;
            </button>
          </div>
          <div class="toast-body">
            <div class="toast-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="toast-message-content">
              {{ unpaidToastMessage }}
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.admission-search-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  margin-bottom: 24px;
}

/* Header */
.card-header-bar {
  padding: 16px 20px 12px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.header-search-icon {
  width: 18px;
  height: 18px;
  color: #1e293b;
}

.card-title {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

/* Form */
.search-card-body {
  padding: 0 20px 18px 20px;
}

.search-controls-row {
  display: flex;
  gap: 12px;
  align-items: center;
}

.search-input-group {
  flex: 1;
}

.old-patient-input {
  width: 100%;
  height: 38px;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  padding: 8px 12px;
  font-size: 13px;
  color: #1e293b;
  background-color: #ffffff;
  transition: border-color 0.15s ease-in-out;
}

.old-patient-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}

.date-input-group {
  position: relative;
  width: 200px;
}

.birth-date-input {
  width: 100%;
  height: 38px;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  padding: 8px 12px;
  font-size: 13px;
  color: #1e293b;
  background-color: #ffffff;
}

.birth-date-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}

.date-trailing-icon {
  position: absolute;
  right: 10px;
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
}

.btn-search-old {
  height: 38px;
  padding: 0 18px;
  background-color: #337ab7;
  border: 1px solid #2e6da4;
  border-radius: 4px;
  color: #ffffff;
  font-size: 13px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  transition: background-color 0.15s ease-in-out;
}

.btn-search-old:hover:not(:disabled) {
  background-color: #286090;
  border-color: #204d74;
}

.btn-search-old:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-search-old svg {
  width: 15px;
  height: 15px;
}

.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid #ffffff;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.validation-alert {
  margin-top: 10px;
  padding: 8px 12px;
  border-radius: 4px;
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.validation-alert svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* Divider Line */
.card-divider {
  height: 1px;
  background-color: #e2e8f0;
  margin: 0;
}

/* Results Panel */
.search-results-panel {
  padding: 16px 20px 20px 20px;
}

.entries-control-row {
  margin-bottom: 12px;
  display: flex;
  align-items: center;
}

.entries-selector {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #334155;
}

.entries-select {
  height: 32px;
  padding: 0 24px 0 10px;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  background-color: #ffffff;
  color: #1e293b;
  font-size: 13px;
  cursor: pointer;
}

.entries-label {
  font-size: 13px;
  color: #475569;
}

/* Table Styling matching Legacy DataTables */
.results-table-wrap {
  overflow-x: auto;
  border-top: 1px solid #e2e8f0;
}

.legacy-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
  text-align: left;
}

.legacy-table th {
  padding: 10px 12px;
  border-bottom: 2px solid #cbd5e1;
  color: #1e293b;
  font-weight: 700;
  font-size: 13px;
  white-space: nowrap;
  user-select: none;
}

.th-sortable {
  cursor: pointer;
}

.th-content {
  display: flex;
  align-items: center;
  gap: 6px;
}

.justify-center {
  justify-content: center;
}

.sort-arrows {
  display: inline-flex;
  flex-direction: column;
  font-size: 9px;
  color: #94a3b8;
  opacity: 0.5;
}

.sort-arrows.active {
  opacity: 1;
}

.arrow-active {
  color: #2563eb;
}

.legacy-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
  color: #334155;
}

.legacy-table tbody tr:hover {
  background-color: #f8fafc;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: 12.5px;
}

.text-center {
  text-align: center;
}

/* Column specific widths */
.col-no {
  width: 50px;
}

.col-rm {
  width: 90px;
  color: #1e293b;
  font-weight: 500;
}

.col-ktp {
  width: 160px;
  color: #334155;
}

.col-name {
  min-width: 180px;
}

.patient-name-title {
  font-weight: 600;
  color: #1e293b;
  line-height: 1.3;
}

.patient-age-gender {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}

.col-birth {
  min-width: 130px;
}

.patient-birth-place {
  color: #1e293b;
  line-height: 1.3;
}

.patient-birth-date {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}

.col-address {
  min-width: 160px;
  color: #334155;
}

.patient-address-text {
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Action Buttons */
.col-action {
  width: 100px;
}

.action-btn-group {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  justify-content: center;
}

.btn-action-view {
  width: 32px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  color: #334155;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-action-view:hover {
  background-color: #f1f5f9;
  border-color: #94a3b8;
  color: #0f172a;
}

.btn-action-view svg {
  width: 16px;
  height: 16px;
}

.btn-action-admit {
  width: 32px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: #10b981;
  border: 1px solid #059669;
  border-radius: 4px;
  color: #ffffff;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-action-admit:hover {
  background-color: #059669;
  border-color: #047857;
}

.btn-action-admit svg {
  width: 15px;
  height: 15px;
}

/* Footer / Pagination */
.results-footer-bar {
  margin-top: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.entries-info-text {
  font-size: 13px;
  color: #64748b;
}

.pagination-buttons {
  display: inline-flex;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  overflow: hidden;
}

.btn-page-nav,
.btn-page-number {
  height: 32px;
  min-width: 34px;
  padding: 0 10px;
  background-color: #ffffff;
  border: none;
  border-right: 1px solid #cbd5e1;
  font-size: 13px;
  color: #334155;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s;
}

.btn-page-nav:last-child,
.btn-page-number:last-child {
  border-right: none;
}

.btn-page-nav:hover:not(:disabled),
.btn-page-number:hover:not(.active) {
  background-color: #f1f5f9;
}

.btn-page-nav:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.btn-page-number.active {
  background-color: #337ab7;
  color: #ffffff;
  font-weight: 600;
  cursor: default;
}

/* Empty State */
.results-empty-state {
  padding: 32px 16px;
  text-align: center;
  background-color: #f8fafc;
  border-radius: 6px;
  border: 1px dashed #cbd5e1;
}

.empty-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  color: #64748b;
  font-size: 13px;
  margin-bottom: 14px;
}

.empty-info strong {
  color: #1e293b;
  font-size: 14px;
}

.btn-register-fallback {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background-color: #0d9488;
  color: #ffffff;
  border: none;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
}

.btn-register-fallback svg {
  width: 16px;
  height: 16px;
}

/* Red Floating Notification Toast (Legacy bottom-right toast) */
.unpaid-billing-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 320px;
  background-color: #d9534f;
  color: #ffffff;
  border-radius: 6px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
  z-index: 9999;
  overflow: hidden;
  font-family: inherit;
}

.toast-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  background-color: rgba(0, 0, 0, 0.12);
}

.toast-title-text {
  font-weight: 700;
  font-size: 13.5px;
  color: #ffffff;
}

.toast-close-btn {
  background: none;
  border: none;
  color: #ffffff;
  font-size: 18px;
  line-height: 1;
  cursor: pointer;
  opacity: 0.8;
  padding: 0 4px;
}

.toast-close-btn:hover {
  opacity: 1;
}

.toast-body {
  padding: 12px 14px 14px 14px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.toast-icon-wrap {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toast-icon-wrap svg {
  width: 22px;
  height: 22px;
}

.toast-message-content {
  font-size: 12.5px;
  line-height: 1.45;
  color: #ffffff;
}

/* Transitions */
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.25s ease-out;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(12px);
  opacity: 0;
}
</style>
