<script setup>
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
  guarantorOptions: {
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
  paymentMethods: {
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
    <!-- Row 1: Search Bar -->
    <div class="search-row">
      <div class="search-input-wrapper">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input autocomplete="off"
          id="billing-search-input"
          type="text"
          class="search-input"
          placeholder="Cari No. RM, NIK, nama pasien, nomor kunjungan, atau nomor tagihan..."
          :value="searchQuery"
          @input="handleSearchInput"
          @keydown="handleSearchKeydown"
          aria-label="Cari No RM, NIK, nama pasien, kunjungan, tagihan"
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
        class="btn-primary btn-search-action"
        @click="emit('apply')"
        :disabled="loading"
        aria-label="Filter Tagihan"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
        </svg>
        <span>Filter</span>
      </button>

      <button
        type="button"
        class="btn-secondary btn-reset-action"
        @click="emit('reset')"
        :disabled="loading"
        aria-label="Reset Filter Tagihan"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
          <path d="M3 3v5h5"></path>
        </svg>
        <span>Reset</span>
      </button>
    </div>

    <!-- Row 2: Dropdown Multi-Filters Grid (6 Dropdowns) -->
    <div class="filters-grid">
      <!-- 1. Periode -->
      <div class="filter-group">
        <label for="b-filter-period" class="filter-label">Periode</label>
        <select
          id="b-filter-period"
          v-model="filters.period"
          class="form-select"
          aria-label="Filter Periode Tanggal"
        >
          <option v-for="opt in periodOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </div>

      <!-- 2. Poliklinik -->
      <div class="filter-group">
        <label for="b-filter-poly" class="filter-label">Poliklinik</label>
        <select
          id="b-filter-poly"
          v-model="filters.polyclinic"
          class="form-select"
          aria-label="Filter Poliklinik"
        >
          <option value="Semua Poliklinik">Semua Poliklinik</option>
          <option v-for="p in polyclinics" :key="p" :value="p">
            {{ p }}
          </option>
        </select>
      </div>

      <!-- 3. Dokter DPJP -->
      <div class="filter-group">
        <label for="b-filter-doctor" class="filter-label">Dokter DPJP</label>
        <select
          id="b-filter-doctor"
          v-model="filters.doctor"
          class="form-select"
          aria-label="Filter Dokter DPJP"
        >
          <option value="Semua Dokter DPJP">Semua Dokter DPJP</option>
          <option v-for="d in doctors" :key="d" :value="d">
            {{ d }}
          </option>
        </select>
      </div>

      <!-- 4. Jenis Penjamin -->
      <div class="filter-group">
        <label for="b-filter-guarantor" class="filter-label">Jenis Penjamin</label>
        <select
          id="b-filter-guarantor"
          v-model="filters.guarantor"
          class="form-select"
          aria-label="Filter Jenis Penjamin"
        >
          <option value="Semua Jenis Penjamin">Semua Jenis Penjamin</option>
          <option v-for="g in guarantorOptions" :key="g" :value="g">
            {{ g }}
          </option>
        </select>
      </div>

      <!-- 5. Status Tagihan -->
      <div class="filter-group">
        <label for="b-filter-status" class="filter-label">Status Tagihan</label>
        <select
          id="b-filter-status"
          v-model="filters.status"
          class="form-select"
          aria-label="Filter Status Tagihan"
        >
          <option v-for="st in statusOptions" :key="st" :value="st">
            {{ st }}
          </option>
        </select>
      </div>

      <!-- 6. Metode Pembayaran -->
      <div class="filter-group">
        <label for="b-filter-method" class="filter-label">Metode Pembayaran</label>
        <select
          id="b-filter-method"
          v-model="filters.paymentMethod"
          class="form-select"
          aria-label="Filter Metode Pembayaran"
        >
          <option v-for="pm in paymentMethods" :key="pm" :value="pm">
            {{ pm }}
          </option>
        </select>
      </div>
    </div>

    <!-- Custom Date Range Row -->
    <div v-if="filters.period === 'custom'" class="custom-date-row">
      <div class="date-input-group">
        <label for="b-start-date" class="filter-label">Tanggal Mulai</label>
        <input autocomplete="off" id="b-start-date" type="date" v-model="filters.customStartDate" class="form-input" />
      </div>
      <div class="date-input-group">
        <label for="b-end-date" class="filter-label">Tanggal Akhir</label>
        <input autocomplete="off" id="b-end-date" type="date" v-model="filters.customEndDate" class="form-input" />
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
  gap: 1rem;
}

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

.btn-search-action, .btn-reset-action {
  height: 42px;
  padding: 0 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.btn-search-action {
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: none;
}

.btn-search-action:hover:not(:disabled) {
  background: var(--color-primary-dark, #06635e);
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.25);
}

.btn-reset-action {
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.btn-reset-action:hover:not(:disabled) {
  background: #f8fafc;
  color: #0f172a;
}

.btn-search-action svg, .btn-reset-action svg {
  width: 15px;
  height: 15px;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 0.75rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.filter-label {
  font-size: 0.725rem;
  font-weight: 600;
  color: var(--color-text-navy, #334155);
}

.form-select, .form-input {
  height: 38px;
  padding: 0 0.65rem;
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

.custom-date-row {
  display: flex;
  gap: 1rem;
  padding-top: 0.25rem;
}

.date-input-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

@media (max-width: 1300px) {
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
  .btn-search-action, .btn-reset-action {
    width: 100%;
    justify-content: center;
  }
}
</style>
