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
    <MasterDataSearchInput 
      v-model="localSearch" 
      placeholder="Cari nama jabatan..." 
    />
    <div class="filter-controls">
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

button:disabled {
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
</style>
