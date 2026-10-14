<script setup>
import { ref, watch } from 'vue';
import MasterDataSearchInput from '@/components/master-data/shared/MasterDataSearchInput.vue';

const props = defineProps({
  filters: {
    type: Object,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  categories: {
    type: Array,
    default: () => []
  },
  categoriesLoading: {
    type: Boolean,
    default: false
  },
  tariffTypes: {
    type: Array,
    default: () => []
  },
  tariffTypesLoading: {
    type: Boolean,
    default: false
  },
  exporting: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['filter', 'refresh', 'export']);

const localSearch = ref(props.filters.search || '');
let searchTimeout = null;

watch(localSearch, (newVal) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    emit('filter', { key: 'search', value: newVal });
  }, 400);
});

watch(() => props.filters.search, (newVal) => {
  if (localSearch.value !== newVal) {
    localSearch.value = newVal || '';
  }
});
</script>

<template>
  <div class="filters-container">
    <MasterDataSearchInput 
      v-model="localSearch" 
      placeholder="Cari nama, kode atau ICD-9..." 
    />
    <div class="filter-controls">
      <!-- 2. Dropdown Kategori -->
      <select 
        :value="filters.category_id || ''" 
        @change="emit('filter', { 
          key: 'category_id', 
          value: $event.target.value ? Number($event.target.value) : '' 
        })"
        class="filter-select"
        :disabled="loading || categoriesLoading || exporting"
        aria-label="Filter Kategori Tindakan"
      >
        <option value="">{{ categoriesLoading ? 'Memuat kategori...' : 'Semua Kategori' }}</option>
        <option 
          v-for="cat in categories" 
          :key="cat.value ?? cat.id" 
          :value="cat.value ?? cat.id"
        >
          {{ cat.label ?? cat.name }}
        </option>
      </select>

      <!-- 3. Dropdown Jenis Tarif -->
      <select 
        :value="filters.tariff_type_id || ''" 
        @change="emit('filter', { 
          key: 'tariff_type_id', 
          value: $event.target.value ? Number($event.target.value) : '' 
        })"
        class="filter-select"
        :disabled="loading || tariffTypesLoading || exporting"
        aria-label="Filter Jenis Tarif"
      >
        <option value="">{{ tariffTypesLoading ? 'Memuat jenis tarif...' : 'Semua Jenis Tarif' }}</option>
        <option 
          v-for="tt in tariffTypes" 
          :key="tt.value ?? tt.id" 
          :value="tt.value ?? tt.id"
        >
          {{ tt.label ?? tt.name }}
        </option>
      </select>

      <!-- 4. Tombol Refresh -->
      <button 
        type="button" 
        class="btn-icon" 
        @click="emit('refresh')" 
        title="Refresh Data"
        :disabled="loading || categoriesLoading || tariffTypesLoading || exporting"
        aria-label="Refresh data"
      >
        <svg :class="{ 'spin': loading || categoriesLoading || tariffTypesLoading }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="23 4 23 10 17 10"></polyline>
          <polyline points="1 20 1 14 7 14"></polyline>
          <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
        </svg>
      </button>

      <!-- 5. Tombol Download Excel -->
      <button 
        type="button" 
        class="btn-export" 
        @click="emit('export')" 
        :disabled="loading || exporting || categoriesLoading || tariffTypesLoading"
        title="Download data tindakan dalam format Excel"
        aria-label="Download data tindakan dalam format Excel"
      >
        <svg v-if="!exporting" class="btn-export-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="7 10 12 15 17 10"></polyline>
          <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        <svg v-else class="btn-export-icon spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="23 4 23 10 17 10"></polyline>
          <polyline points="1 20 1 14 7 14"></polyline>
          <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
        </svg>
        <span>{{ exporting ? 'Mengunduh...' : 'Download Excel' }}</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.filters-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.filter-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

.filter-select {
  height: 42px;
  padding: 0.5rem 2.25rem 0.5rem 0.875rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 8px;
  font-size: 0.9rem;
  color: var(--color-text-navy, #1e293b);
  background-color: #fff;
  cursor: pointer;
  appearance: none;
  min-width: 180px;
  max-width: 250px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.65rem center;
  transition: all 0.2s;
}

.filter-select:focus {
  outline: none;
  border-color: var(--color-primary, #3b82f6);
  box-shadow: 0 0 0 3px var(--color-primary-light, rgba(59, 130, 246, 0.2));
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 8px;
  color: var(--color-text-secondary, #64748b);
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-icon:hover:not(:disabled) {
  background: var(--color-page-bg, #f8fafc);
  color: var(--color-text-navy, #1e293b);
}

.btn-icon svg {
  width: 18px;
  height: 18px;
}

.btn-export {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  height: 42px;
  padding: 0 1rem;
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 8px;
  color: var(--color-text-navy, #1e293b);
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
  flex-shrink: 0;
}

.btn-export:hover:not(:disabled) {
  background: var(--color-page-bg, #f8fafc);
  border-color: var(--color-primary, #3b82f6);
  color: var(--color-primary, #3b82f6);
}

.btn-export-icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

button:disabled, select:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .filters-container {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-controls {
    flex-direction: column;
    align-items: stretch;
    width: 100%;
  }

  .filter-select {
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .btn-icon {
    width: 100%;
  }

  .btn-export {
    width: 100%;
  }
}
</style>
