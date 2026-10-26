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

watch(() => props.filters.search, (newVal) => {
  if (localSearch.value !== newVal) {
    localSearch.value = newVal || '';
  }
});
</script>

<template>
  <div class="mapping-toolbar">
    <div class="mapping-search-wrapper">
      <svg 
        class="mapping-search-icon" 
        width="18" 
        height="18" 
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
      <input autocomplete="off" 
        type="text" 
        v-model="localSearch" 
        class="mapping-search-input" 
        placeholder="Cari tindakan atau pegawai..." 
        aria-label="Cari tindakan atau pegawai"
      />
    </div>

    <div class="mapping-toolbar-actions">
      <button 
        type="button" 
        class="mapping-btn-refresh" 
        @click="emit('refresh')" 
        title="Refresh Data"
        :disabled="loading"
        aria-label="Refresh data"
      >
        <svg 
          :class="{ 'spin': loading }" 
          width="18" 
          height="18" 
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
.mapping-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.mapping-search-wrapper {
  position: relative;
  width: 360px;
  max-width: 100%;
}

.mapping-search-icon {
  position: absolute;
  left: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  width: 18px !important;
  height: 18px !important;
  min-width: 18px;
  max-width: 18px;
  min-height: 18px;
  max-height: 18px;
  color: var(--color-text-secondary, #94a3b8);
  pointer-events: none;
  flex-shrink: 0;
}

.mapping-search-input {
  width: 100%;
  height: 40px;
  padding: 0.5rem 1rem 0.5rem 2.5rem;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  font-size: 0.9rem;
  color: var(--color-text-navy, #0f172a);
  background-color: #ffffff;
  outline: none;
  transition: all 0.2s;
  box-sizing: border-box;
}

.mapping-search-input:focus {
  border-color: var(--color-primary, #0284c7);
  box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}

.mapping-toolbar-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.mapping-btn-refresh {
  width: 40px;
  height: 40px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--color-text-secondary, #64748b);
  transition: all 0.2s;
  padding: 0;
  flex-shrink: 0;
}

.mapping-btn-refresh:hover:not(:disabled) {
  background: var(--color-page-bg, #f8fafc);
  color: var(--color-text-navy, #0f172a);
  border-color: var(--color-border, #cbd5e1);
}

.mapping-btn-refresh:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.mapping-btn-refresh svg {
  width: 18px !important;
  height: 18px !important;
  min-width: 18px;
  max-width: 18px;
  flex-shrink: 0;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
  .mapping-toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .mapping-search-wrapper {
    width: 100%;
  }

  .mapping-toolbar-actions {
    justify-content: flex-end;
  }
}
</style>
