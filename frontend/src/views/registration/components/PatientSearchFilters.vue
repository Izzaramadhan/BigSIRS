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
  isOpen: {
    type: Boolean,
    default: false
  },
  validationError: {
    type: String,
    default: ''
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'update:searchQuery',
  'search',
  'toggle-advanced',
  'reset'
]);

const internalQuery = computed({
  get: () => props.searchQuery,
  set: (val) => emit('update:searchQuery', val)
});

const handleKeydown = (e) => {
  if (e.key === 'Enter') {
    emit('search');
  }
};

const clearSearch = () => {
  emit('update:searchQuery', '');
};
</script>

<template>
  <div class="card search-card">
    <div class="search-header">
      <div class="search-titles">
        <h2 class="search-title">Cari Data Pasien</h2>
        <p class="search-description">
          Gunakan salah satu informasi berikut untuk menemukan data rekam medis pasien.
        </p>
      </div>
      
      <button 
        type="button" 
        class="btn-toggle-advanced" 
        @click="emit('toggle-advanced')"
        :aria-expanded="isOpen"
      >
        <span>{{ isOpen ? 'Sembunyikan Filter Lanjutan' : 'Tampilkan Filter Lanjutan' }}</span>
        <svg 
          class="chevron-icon" 
          :class="{ 'rotate-180': isOpen }" 
          viewBox="0 0 24 24" 
          fill="none" 
          stroke="currentColor" 
          stroke-width="2" 
          stroke-linecap="round" 
          stroke-linejoin="round"
        >
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </button>
    </div>

    <!-- Main Search Bar -->
    <div class="search-bar-row">
      <div class="search-input-wrapper">
        <svg 
          class="input-icon" 
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
        
        <input
          v-model="internalQuery"
          type="text"
          class="form-input search-input"
          :class="{ 'has-error': Boolean(validationError) }"
          placeholder="Cari berdasarkan Nama Pasien, No. RM, atau NIK..."
          aria-label="Pencarian Pasien"
          @keydown="handleKeydown"
        />

        <button 
          v-if="internalQuery" 
          type="button" 
          class="btn-clear" 
          @click="clearSearch"
          aria-label="Hapus kata kunci"
          title="Hapus kata kunci"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <button 
        type="button" 
        class="btn-primary btn-search" 
        :disabled="loading" 
        @click="emit('search')"
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
        <span v-if="loading" class="spinner-small"></span>
        <span>{{ loading ? 'Mencari...' : 'Cari Pasien' }}</span>
      </button>
    </div>

    <!-- Light Validation Error Message -->
    <div v-if="validationError" class="validation-error-row" role="alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
      <span>{{ validationError }}</span>
    </div>

    <!-- Collapsible Advanced Filter Section -->
    <Transition name="expand">
      <div v-if="isOpen" class="advanced-filters-panel">
        <div class="filters-grid">
          <!-- Filter 1: Jenis Kelamin -->
          <div class="filter-group">
            <label class="filter-label" for="filter-gender">Jenis Kelamin</label>
            <select id="filter-gender" v-model="filters.gender" class="form-select">
              <option value="all">Semua Jenis Kelamin</option>
              <option value="L">Laki-laki</option>
              <option value="P">Perempuan</option>
            </select>
          </div>

          <!-- Filter 2: Status Pasien -->
          <div class="filter-group">
            <label class="filter-label" for="filter-status">Status Pasien</label>
            <select id="filter-status" v-model="filters.status" class="form-select">
              <option value="all">Semua Status</option>
              <option value="active">Aktif</option>
              <option value="inactive">Tidak Aktif</option>
            </select>
          </div>

          <!-- Filter 3: Tanggal Lahir -->
          <div class="filter-group">
            <label class="filter-label" for="filter-birthdate">Tanggal Lahir</label>
            <input 
              id="filter-birthdate" 
              v-model="filters.birthDate" 
              type="date" 
              class="form-input" 
            />
          </div>

          <!-- Filter 4: Nomor Rekam Medis -->
          <div class="filter-group">
            <label class="filter-label" for="filter-rm">Nomor Rekam Medis</label>
            <input 
              id="filter-rm" 
              v-model="filters.medicalRecordNumber" 
              type="text" 
              class="form-input" 
              placeholder="Contoh: RM-000245"
            />
          </div>
        </div>

        <div class="advanced-filters-actions">
          <button 
            type="button" 
            class="btn-reset" 
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
            @click="emit('search')"
          >
            <span>Terapkan Filter</span>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.search-card {
  padding: 1.5rem;
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.search-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.search-title {
  margin: 0 0 0.25rem 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.search-description {
  margin: 0;
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.btn-toggle-advanced {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: transparent;
  border: 1px solid var(--color-border-soft);
  color: var(--color-primary);
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.5rem 0.85rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-toggle-advanced:hover {
  background: var(--color-primary-light);
  border-color: var(--color-primary);
}

.chevron-icon {
  width: 16px;
  height: 16px;
  transition: transform 0.2s ease;
}

.rotate-180 {
  transform: rotate(180deg);
}

.search-bar-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.search-input-wrapper {
  position: relative;
  flex: 1;
}

.input-icon {
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
  padding: 0.75rem 2.5rem 0.75rem 2.75rem;
  font-size: 0.925rem;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  color: var(--color-text-navy);
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.search-input.has-error {
  border-color: var(--color-danger);
  background-color: var(--color-danger-bg);
}

.btn-clear {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s;
}

.btn-clear:hover {
  color: var(--color-text-navy);
  background: #f1f5f9;
}

.btn-clear svg {
  width: 16px;
  height: 16px;
}

.btn-search {
  padding: 0.75rem 1.5rem;
  font-size: 0.925rem;
  white-space: nowrap;
  flex-shrink: 0;
}

.btn-search svg {
  width: 18px;
  height: 18px;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.validation-error-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.6rem;
  padding: 0.5rem 0.75rem;
  background: var(--color-danger-bg);
  border-radius: 6px;
  color: var(--color-danger);
  font-size: 0.825rem;
  font-weight: 500;
}

.validation-error-row svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* Advanced Filters Panel */
.advanced-filters-panel {
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px dashed var(--color-border-soft);
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.filter-label {
  font-size: 0.825rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.advanced-filters-actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.75rem;
}

.btn-reset {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-secondary);
  padding: 0.55rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-reset:hover {
  background: #f1f5f9;
  color: var(--color-text-navy);
}

.btn-reset svg {
  width: 16px;
  height: 16px;
}

.btn-apply {
  padding: 0.55rem 1.25rem;
  font-size: 0.875rem;
}

/* Expand Transition */
.expand-enter-active,
.expand-leave-active {
  transition: all 0.25s ease-out;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-8px);
}

.expand-enter-to,
.expand-leave-from {
  opacity: 1;
  max-height: 300px;
  transform: translateY(0);
}

@media (max-width: 1024px) {
  .filters-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .search-card {
    padding: 1rem;
  }
  
  .search-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .btn-toggle-advanced {
    justify-content: center;
  }

  .search-bar-row {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-search {
    justify-content: center;
  }

  .filters-grid {
    grid-template-columns: 1fr;
  }

  .advanced-filters-actions {
    flex-direction: column-reverse;
  }

  .btn-reset, .btn-apply {
    width: 100%;
    justify-content: center;
  }
}
</style>
