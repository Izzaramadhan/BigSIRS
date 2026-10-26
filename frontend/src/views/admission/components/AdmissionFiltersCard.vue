<script setup>
import { computed } from 'vue';

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
  guarantors: {
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

const internalQuery = computed({
  get: () => props.searchQuery,
  set: (val) => emit('update:searchQuery', val)
});

const handleKeydown = (e) => {
  if (e.key === 'Enter') {
    emit('apply');
  }
};

const clearSearch = () => {
  emit('update:searchQuery', '');
  emit('apply');
};
</script>

<template>
  <div class="card filters-card">
    <!-- Top Row: Search Input & Quick Period Filter -->
    <div class="filters-top-row">
      <div class="search-field-wrapper">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>

        <input autocomplete="off"
          v-model="internalQuery"
          type="text"
          class="form-input search-input"
          placeholder="Cari No. RM, NIK, nama pasien, atau dokter..."
          aria-label="Pencarian Admisi Rawat Jalan"
          @keydown="handleKeydown"
        />

        <button 
          v-if="internalQuery" 
          type="button" 
          class="btn-clear-search" 
          @click="clearSearch"
          title="Hapus kata kunci"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Filter Periode Admisi -->
      <div class="filter-item-wrapper period-wrapper">
        <label class="filter-label" for="filter-period">Periode Admisi</label>
        <select 
          id="filter-period" 
          v-model="filters.period" 
          class="form-select"
        >
          <option value="today">Hari Ini</option>
          <option value="yesterday">Kemarin</option>
          <option value="7days">7 Hari Terakhir</option>
          <option value="30days">30 Hari Terakhir</option>
          <option value="all">Semua Periode</option>
          <option value="custom">Rentang Tanggal</option>
        </select>
      </div>
    </div>

    <!-- Custom Date Range Row (If 'custom' selected) -->
    <div v-if="filters.period === 'custom'" class="custom-date-row">
      <div class="filter-item-wrapper">
        <label class="filter-label" for="filter-start-date">Dari Tanggal</label>
        <input autocomplete="off" 
          id="filter-start-date" 
          v-model="filters.customStartDate" 
          type="date" 
          class="form-input" 
        />
      </div>
      <div class="filter-item-wrapper">
        <label class="filter-label" for="filter-end-date">Sampai Tanggal</label>
        <input autocomplete="off" 
          id="filter-end-date" 
          v-model="filters.customEndDate" 
          type="date" 
          class="form-input" 
        />
      </div>
    </div>

    <!-- Bottom Row: Polyclinic, Doctor, Guarantor, Service Status, and Actions -->
    <div class="filters-multi-grid">
      <!-- Filter Poliklinik -->
      <div class="filter-item-wrapper">
        <label class="filter-label" for="filter-poly">Poliklinik Tujuan</label>
        <select id="filter-poly" v-model="filters.polyclinic" class="form-select">
          <option value="all">Semua Poliklinik</option>
          <option v-for="p in polyclinics" :key="p" :value="p">{{ p }}</option>
        </select>
      </div>

      <!-- Filter Dokter -->
      <div class="filter-item-wrapper">
        <label class="filter-label" for="filter-doctor">Dokter Spesialis</label>
        <select id="filter-doctor" v-model="filters.doctor" class="form-select">
          <option value="all">Semua Dokter</option>
          <option v-for="d in doctors" :key="d" :value="d">{{ d }}</option>
        </select>
      </div>

      <!-- Filter Jenis Penjamin -->
      <div class="filter-item-wrapper">
        <label class="filter-label" for="filter-guarantor">Jenis Penjamin</label>
        <select id="filter-guarantor" v-model="filters.guarantor" class="form-select">
          <option value="all">Semua Penjamin</option>
          <option value="BPJS Kesehatan">BPJS Kesehatan</option>
          <option value="Pasien Umum / Mandiri">Pasien Umum / Mandiri</option>
          <option value="Asuransi Swasta">Asuransi Swasta</option>
        </select>
      </div>

      <!-- Filter Status Pelayanan -->
      <div class="filter-item-wrapper">
        <label class="filter-label" for="filter-status">Status Pelayanan</label>
        <select id="filter-status" v-model="filters.status" class="form-select">
          <option value="all">Semua Status Pelayanan</option>
          <option value="Menunggu">Menunggu</option>
          <option value="Sedang Diperiksa">Sedang Diperiksa</option>
          <option value="Selesai">Selesai</option>
          <option value="Dibatalkan">Dibatalkan</option>
        </select>
      </div>

      <!-- Buttons: Terapkan Filter & Reset Filter -->
      <div class="filter-buttons-wrapper">
        <button 
          type="button" 
          class="btn-outline btn-reset" 
          @click="emit('reset')"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
            <path d="M3 3v5h5"></path>
          </svg>
          <span>Reset Filter</span>
        </button>

        <button 
          type="button" 
          class="btn-primary btn-apply" 
          :disabled="loading"
          @click="emit('apply')"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>Terapkan Filter</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.filters-card {
  padding: 1.25rem 1.5rem;
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.filters-top-row {
  display: grid;
  grid-template-columns: 1fr 240px;
  gap: 1rem;
  align-items: flex-end;
}

.search-field-wrapper {
  position: relative;
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 0.7rem 2.5rem 0.7rem 2.75rem;
  font-size: 0.9rem;
}

.btn-clear-search {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-clear-search:hover {
  color: var(--color-text-navy);
  background: #f1f5f9;
}

.btn-clear-search svg {
  width: 16px;
  height: 16px;
}

.filter-item-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.filter-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.custom-date-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px dashed var(--color-border-soft);
}

.filters-multi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr) auto;
  gap: 1rem;
  align-items: flex-end;
}

.filter-buttons-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-reset {
  padding: 0.62rem 0.85rem;
  font-size: 0.85rem;
  white-space: nowrap;
}

.btn-reset svg,
.btn-apply svg {
  width: 15px;
  height: 15px;
}

.btn-apply {
  padding: 0.62rem 1.15rem;
  font-size: 0.85rem;
  white-space: nowrap;
}

@media (max-width: 1200px) {
  .filters-multi-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .filter-buttons-wrapper {
    grid-column: 1 / -1;
    justify-content: flex-end;
  }
}

@media (max-width: 768px) {
  .filters-top-row {
    grid-template-columns: 1fr;
  }

  .filters-multi-grid {
    grid-template-columns: 1fr;
  }

  .filter-buttons-wrapper {
    flex-direction: column-reverse;
  }

  .btn-reset,
  .btn-apply {
    width: 100%;
    justify-content: center;
  }
}
</style>
