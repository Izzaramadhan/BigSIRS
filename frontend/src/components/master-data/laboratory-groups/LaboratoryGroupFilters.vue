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
  }
});

const emit = defineEmits(['filter', 'refresh']);

const localSearch = ref(props.filters.search || '');
let searchTimeout = null;

watch(localSearch, (newVal) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    emit('filter', { key: 'search', value: newVal });
  }, 400);
});

watch(
  () => props.filters.search,
  (newVal) => {
    if (localSearch.value !== newVal) {
      localSearch.value = newVal || '';
    }
  }
);
</script>

<template>
  <div class="filters-container">
    <MasterDataSearchInput
      v-model="localSearch"
      placeholder="Cari grup lab..."
    />
    <div class="filter-controls">
      <!-- Filter Status -->
      <select
        :value="filters.is_active === null ? '' : String(filters.is_active)"
        @change="emit('filter', {
          key: 'is_active',
          value: $event.target.value === '' ? null : $event.target.value === 'true'
        })"
        class="filter-select"
        :disabled="loading"
        aria-label="Filter status aktif"
      >
        <option value="">Semua Status</option>
        <option value="true">Aktif</option>
        <option value="false">Nonaktif</option>
      </select>

      <!-- Refresh Button -->
      <button
        type="button"
        class="btn-icon"
        @click="emit('refresh')"
        title="Refresh Data"
        :disabled="loading"
        aria-label="Refresh data"
      >
        <svg
          :class="{ spin: loading }"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
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
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.filter-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

.filter-select {
  padding: 0.5rem 2rem 0.5rem 0.75rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  font-size: 0.875rem;
  color: var(--color-text-navy, #0f172a);
  background-color: #ffffff;
  cursor: pointer;
  appearance: none;
  min-width: 160px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.5rem center;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.filter-select:focus {
  outline: none;
  border-color: var(--color-primary, #0f766e);
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
}

.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  padding: 0;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  background-color: #ffffff;
  color: var(--color-text-secondary, #475569);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover:not(:disabled) {
  background-color: var(--color-bg-hover, #f8fafc);
  color: var(--color-primary, #0f766e);
  border-color: var(--color-primary, #0f766e);
}

.btn-icon:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-icon svg {
  width: 18px;
  height: 18px;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 768px) {
  .filters-container {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-controls {
    justify-content: flex-start;
  }

  .filter-select {
    flex: 1;
    min-width: 140px;
  }
}
</style>
