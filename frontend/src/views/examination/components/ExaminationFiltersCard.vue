<script setup>
import { ref } from 'vue';

const props = defineProps({
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
  doctors: {
    type: Array,
    default: () => []
  },
  periodOptions: {
    type: Array,
    default: () => []
  },
  statusOptions: {
    type: Array,
    default: () => []
  },
  guarantorOptions: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'update:searchQuery',
  'apply',
  'reset'
]);

const handleSearchInput = (e) => {
  emit('update:searchQuery', e.target.value);
};

const handleSearchKeydown = (e) => {
  if (e.key === 'Enter') {
    emit('apply');
  }
};
</script>

<template>
  <div class="card filter-card">
    <!-- Row 1: Search Bar & Quick Search Button -->
    <div class="search-row">
      <div class="search-input-wrapper">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input autocomplete="off"
          id="examination-search-input"
          type="text"
          class="search-input"
          placeholder="Cari NIK (16 digit), No. RM, nomor antrean, atau nama pasien..."
          :value="searchQuery"
          @input="handleSearchInput"
          @keydown="handleSearchKeydown"
          aria-label="Cari NIK, No. RM, nomor antrean, atau nama pasien"
        />
        <button
          v-if="searchQuery"
          type="button"
          class="btn-clear-search"
          @click="emit('update:searchQuery', ''); emit('apply')"
          aria-label="Hapus kata kunci pencarian"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <button
        type="button"
        class="btn-primary btn-quick-search"
        @click="emit('apply')"
        :disabled="loading"
        aria-label="Cari Cepat"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <span>Cari Cepat</span>
      </button>
    </div>

    <!-- Row 2: Dropdown Multi-Filters Grid -->
    <div class="filters-grid">
      <!-- 1. Periode Tanggal -->
      <div class="filter-group">
        <label for="filter-period" class="filter-label">Periode Tanggal</label>
        <select
          id="filter-period"
          v-model="filters.period"
          class="form-select"
          aria-label="Filter Periode Tanggal"
        >
          <option v-for="opt in periodOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </div>

      <!-- 2. Poliklinik Tujuan -->
      <div class="filter-group">
        <label for="filter-polyclinic" class="filter-label">Poliklinik Tujuan</label>
        <select
          id="filter-polyclinic"
          v-model="filters.polyclinic"
          class="form-select"
          aria-label="Filter Poliklinik Tujuan"
        >
          <option value="Semua Poliklinik">Semua Poliklinik</option>
          <option v-for="poly in polyclinics" :key="poly" :value="poly">
            {{ poly }}
          </option>
        </select>
      </div>

      <!-- 3. Dokter Pemeriksa -->
      <div class="filter-group">
        <label for="filter-doctor" class="filter-label">Dokter Pemeriksa</label>
        <select
          id="filter-doctor"
          v-model="filters.doctor"
          class="form-select"
          aria-label="Filter Dokter Pemeriksa"
        >
          <option value="Semua Dokter Pemeriksa">Semua Dokter Pemeriksa</option>
          <option v-for="doc in doctors" :key="doc" :value="doc">
            {{ doc }}
          </option>
        </select>
      </div>

      <!-- 4. Status Pemeriksaan -->
      <div class="filter-group">
        <label for="filter-status" class="filter-label">Status Pemeriksaan</label>
        <select
          id="filter-status"
          v-model="filters.status"
          class="form-select"
          aria-label="Filter Status Pemeriksaan"
        >
          <option v-for="st in statusOptions" :key="st" :value="st">
            {{ st }}
          </option>
        </select>
      </div>

      <!-- 5. Jenis Penjamin -->
      <div class="filter-group">
        <label for="filter-guarantor" class="filter-label">Jenis Penjamin</label>
        <select
          id="filter-guarantor"
          v-model="filters.guarantor"
          class="form-select"
          aria-label="Filter Jenis Penjamin"
        >
          <option value="Semua Penjamin">Semua Penjamin</option>
          <option v-for="g in guarantorOptions" :key="g" :value="g">
            {{ g }}
          </option>
        </select>
      </div>
    </div>

    <!-- Date Range Custom Inputs (Shown if 'custom' is selected) -->
    <div v-if="filters.period === 'custom'" class="custom-date-row">
      <div class="date-input-group">
        <label for="start-date" class="filter-label">Tanggal Mulai</label>
        <input autocomplete="off" id="start-date" type="date" v-model="filters.customStartDate" class="form-input" />
      </div>
      <div class="date-input-group">
        <label for="end-date" class="filter-label">Tanggal Akhir</label>
        <input autocomplete="off" id="end-date" type="date" v-model="filters.customEndDate" class="form-input" />
      </div>
    </div>

    <!-- Row 3: Action Buttons (Terapkan Filter & Reset Filter) -->
    <div class="filter-actions-row">
      <div class="actions-group">
        <button
          type="button"
          class="btn-primary btn-apply"
          @click="emit('apply')"
          :disabled="loading"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>Terapkan Filter</span>
        </button>

        <button
          type="button"
          class="btn-secondary btn-reset"
          @click="emit('reset')"
          :disabled="loading"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
            <path d="M3 3v5h5"></path>
          </svg>
          <span>Reset Filter</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.filter-card {
  padding: 1.25rem;
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

/* Search Row */
.search-row {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.search-input-wrapper {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 1rem;
  width: 18px;
  height: 18px;
  color: var(--color-text-secondary, #64748b);
  pointer-events: none;
}

.search-input {
  width: 100%;
  height: 42px;
  padding: 0 2.5rem 0 2.75rem;
  font-size: 0.875rem;
  color: var(--color-text-navy, #1e293b);
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 8px;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.15);
}

.search-input::placeholder {
  color: #94a3b8;
}

.btn-clear-search {
  position: absolute;
  right: 0.75rem;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  border-radius: 50%;
}

.btn-clear-search:hover {
  color: var(--color-text-navy, #1e293b);
  background: #f1f5f9;
}

.btn-quick-search {
  height: 42px;
  padding: 0 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  white-space: nowrap;
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-quick-search:hover:not(:disabled) {
  background: var(--color-primary-dark, #06635e);
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.25);
}

.btn-quick-search svg {
  width: 16px;
  height: 16px;
}

/* Filters Grid */
.filters-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.85rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.filter-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-navy, #334155);
}

.form-select, .form-input {
  height: 38px;
  padding: 0 0.75rem;
  font-size: 0.825rem;
  color: var(--color-text-navy, #1e293b);
  background-color: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.form-select:focus, .form-input:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 2px rgba(8, 127, 120, 0.15);
}

/* Custom Date Row */
.custom-date-row {
  display: flex;
  gap: 1rem;
  padding-top: 0.25rem;
}

.date-input-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

/* Actions Row */
.filter-actions-row {
  display: flex;
  justify-content: flex-end;
  padding-top: 0.25rem;
  border-top: 1px dashed var(--color-border-soft, #e2e8f0);
}

.actions-group {
  display: flex;
  gap: 0.75rem;
}

.btn-apply, .btn-reset {
  height: 36px;
  padding: 0 1rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.825rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-apply {
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: none;
}

.btn-apply:hover:not(:disabled) {
  background: var(--color-primary-dark, #06635e);
}

.btn-reset {
  background: #ffffff;
  color: var(--color-text-secondary, #64748b);
  border: 1px solid var(--color-border-soft, #cbd5e1);
}

.btn-reset:hover:not(:disabled) {
  background: #f8fafc;
  color: var(--color-text-navy, #1e293b);
  border-color: #94a3b8;
}

.btn-apply svg, .btn-reset svg {
  width: 15px;
  height: 15px;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
  .filters-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .filters-grid {
    grid-template-columns: 1fr;
  }
  .search-row {
    flex-direction: column;
  }
  .btn-quick-search {
    width: 100%;
    justify-content: center;
  }
  .filter-actions-row {
    justify-content: stretch;
  }
  .actions-group {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
  }
  .btn-apply, .btn-reset {
    justify-content: center;
  }
}
</style>
