<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  filters: {
    type: Object,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  serviceTypes: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['filter', 'reset', 'refresh']);

const localSearch = ref(props.filters.search);
let searchTimeout = null;

watch(localSearch, (newVal) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    emit('filter', { key: 'search', value: newVal });
  }, 400);
});

watch(() => props.filters.search, (newVal) => {
  if (localSearch.value !== newVal) {
    localSearch.value = newVal;
  }
});
</script>

<template>
  <div class="filters-container">
    <div class="search-box">
      <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <input 
        type="text" 
        v-model="localSearch" 
        placeholder="Cari kode, nama, atau kode BPJS..." 
        class="search-input"
        :disabled="loading"
        aria-label="Cari poliklinik"
      >
    </div>
    
    <div class="filter-controls">
      <select 
        :value="filters.is_active" 
        @change="emit('filter', { key: 'is_active', value: $event.target.value === 'null' ? null : $event.target.value === 'true' })"
        class="filter-select"
        :disabled="loading"
        aria-label="Filter status aktif"
      >
        <option value="null">Semua Status</option>
        <option value="true">Aktif</option>
        <option value="false">Nonaktif</option>
      </select>
      
      <select 
        :value="filters.service_type" 
        @change="emit('filter', { key: 'service_type', value: $event.target.value === 'null' ? null : $event.target.value })"
        class="filter-select"
        :disabled="loading"
        aria-label="Filter jenis layanan"
      >
        <option value="null">Semua Jenis</option>
        <option v-for="st in serviceTypes" :key="st.value" :value="st.value">
          {{ st.label }}
        </option>
      </select>
      
      <button 
        type="button" 
        class="btn-icon" 
        @click="emit('reset')" 
        title="Reset Filter"
        :disabled="loading"
        aria-label="Reset filter"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      
      <button 
        type="button" 
        class="btn-icon" 
        @click="emit('refresh')" 
        title="Refresh Data"
        :disabled="loading"
        aria-label="Refresh data"
      >
        <svg :class="{ 'spin': loading }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="23 4 23 10 17 10"></polyline>
          <polyline points="1 20 1 14 7 14"></polyline>
          <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
        </svg>
      </button>
    </div>
  </div>
</template>

<style scoped>
.filters-container {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
  justify-content: space-between;
  align-items: center;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 250px;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  color: var(--color-text-secondary);
}

.search-input {
  width: 100%;
  padding: 0.5rem 1rem 0.5rem 2.5rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.9rem;
  transition: all 0.2s;
  box-sizing: border-box;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.filter-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

.filter-select {
  padding: 0.5rem 2rem 0.5rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.9rem;
  background-color: #fff;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.5rem center;
}

.filter-select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover:not(:disabled) {
  background: var(--color-page-bg);
  color: var(--color-text-navy);
}

.btn-icon svg {
  width: 18px;
  height: 18px;
}

button:disabled, input:disabled, select:disabled {
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
  .search-box {
    max-width: 100%;
  }
}
</style>
