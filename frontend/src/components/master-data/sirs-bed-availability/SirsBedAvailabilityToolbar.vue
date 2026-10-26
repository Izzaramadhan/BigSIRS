<script setup>
import { computed } from 'vue'

const props = defineProps({
  search: {
    type: String,
    default: '',
  },
  perPage: {
    type: Number,
    default: 10,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:search', 'update:perPage', 'refresh'])

const localSearch = computed({
  get: () => props.search,
  set: (val) => emit('update:search', val),
})

const localPerPage = computed({
  get: () => props.perPage,
  set: (val) => emit('update:perPage', Number(val)),
})

const perPageOptions = [10, 25, 50, 100]
</script>

<template>
  <div class="toolbar-container">
    <!-- Pilihan Jumlah Data (Entries Selector) -->
    <div class="entries-selector">
      <label for="per-page-select" class="entries-label">Tampilkan</label>
      <select
        id="per-page-select"
        v-model="localPerPage"
        class="entries-select"
        aria-label="Pilihan jumlah data per halaman"
      >
        <option v-for="opt in perPageOptions" :key="opt" :value="opt">
          {{ opt }}
        </option>
      </select>
      <span class="entries-suffix">entri</span>
    </div>

    <!-- Controls kanan: Pencarian dan Refresh -->
    <div class="toolbar-actions">
      <div class="search-group">
        <label for="sirs-bed-search" class="search-label">Cari</label>
        <div class="search-input-wrapper">
          <svg
            class="search-icon"
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
            id="sirs-bed-search"
            v-model="localSearch"
            type="text"
            class="search-input"
            placeholder="Cari kelas atau ruang"
            aria-label="Cari kelas atau ruang"
          />
        </div>
      </div>

      <!-- Tombol Refresh -->
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
.toolbar-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}

.entries-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--color-text-secondary, #64748b);
  font-size: 0.875rem;
}

.entries-label,
.entries-suffix {
  font-weight: 500;
}

.entries-select {
  padding: 0.45rem 2rem 0.45rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #1e293b);
  background-color: #ffffff;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.25em 1.25em;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  cursor: pointer;
  appearance: none;
  transition: all 0.2s ease;
}

.entries-select:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px var(--color-primary-light, #e6f6f5);
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.search-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.search-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-navy, #1e293b);
}

.search-input-wrapper {
  position: relative;
  min-width: 240px;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: var(--color-text-secondary, #64748b);
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 0.5rem 0.75rem 0.5rem 2.25rem;
  font-size: 0.875rem;
  color: var(--color-text-navy, #1e293b);
  background-color: #ffffff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px var(--color-primary-light, #e6f6f5);
}

.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background-color: #ffffff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  color: var(--color-text-secondary, #64748b);
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-icon:hover:not(:disabled) {
  background-color: var(--color-page-bg, #f8fafc);
  color: var(--color-primary, #087f78);
  border-color: var(--color-primary, #087f78);
}

.btn-icon:disabled {
  opacity: 0.5;
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

@media (max-width: 640px) {
  .toolbar-container {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-actions {
    justify-content: space-between;
  }

  .search-group {
    flex: 1;
  }

  .search-input-wrapper {
    min-width: 0;
    flex: 1;
  }
}
</style>
