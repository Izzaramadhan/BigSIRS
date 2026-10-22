<script setup>
import { ref, watch } from 'vue'
import MasterDataSearchInput from '@/components/master-data/shared/MasterDataSearchInput.vue'

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['filter', 'refresh'])

const localSearch = ref(props.filters.search)
let searchTimeout = null

watch(localSearch, (newVal) => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    emit('filter', { key: 'search', value: newVal })
  }, 400)
})

watch(
  () => props.filters.search,
  (newVal) => {
    if (localSearch.value !== newVal) {
      localSearch.value = newVal
    }
  },
)
</script>

<template>
  <div class="filters-container">
    <MasterDataSearchInput
      v-model="localSearch"
      placeholder="Cari nama bed, kamar, bangsal, atau kelas..."
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
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.filter-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 8px;
  background-color: #ffffff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  color: var(--color-text-secondary, #64748b);
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-icon:hover:not(:disabled) {
  background-color: var(--color-page-bg, #f8fafc);
  color: var(--color-primary, #0ea5e9);
  border-color: var(--color-primary, #0ea5e9);
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
</style>
