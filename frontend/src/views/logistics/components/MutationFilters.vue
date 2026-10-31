<script setup>
import { reactive, computed, watch } from 'vue';

const props = defineProps({
  filters: {
    type: Object,
    required: true
  },
  warehouses: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['apply', 'reset']);

const normalizedWarehouses = computed(() => {
  return props.warehouses.map((wh) => {
    if (typeof wh === 'object' && wh !== null) {
      return {
        id: wh.id,
        code: wh.code || '',
        name: wh.name || ''
      };
    }
    return { id: wh, code: '', name: wh };
  });
});

const localFilters = reactive({
  startDate: props.filters.startDate || '',
  endDate: props.filters.endDate || '',
  sourceWarehouseId: props.filters.sourceWarehouseId || props.filters.sourceWarehouse || '',
  targetWarehouseId: props.filters.targetWarehouseId || props.filters.targetWarehouse || '',
  keyword: props.filters.keyword || ''
});

watch(
  () => props.filters,
  (newVal) => {
    Object.assign(localFilters, {
      startDate: newVal.startDate || '',
      endDate: newVal.endDate || '',
      sourceWarehouseId: newVal.sourceWarehouseId || newVal.sourceWarehouse || '',
      targetWarehouseId: newVal.targetWarehouseId || newVal.targetWarehouse || '',
      keyword: newVal.keyword || ''
    });
  },
  { deep: true }
);

const handleApply = () => {
  emit('apply', { ...localFilters });
};

const handleReset = () => {
  localFilters.startDate = '';
  localFilters.endDate = '';
  localFilters.sourceWarehouseId = '';
  localFilters.targetWarehouseId = '';
  localFilters.keyword = '';
  emit('reset');
};
</script>

<template>
  <div class="filter-card card">
    <div class="filter-card-header">
      <div class="title-with-icon">
        <div class="filter-icon-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
          </svg>
        </div>
        <h3 class="filter-card-title">Pencarian & Filter Mutasi</h3>
      </div>
    </div>

    <form autocomplete="off" class="filter-form" @submit.prevent="handleApply">
      <div class="filter-grid">
        <!-- Field 1: Rentang Tanggal Mutasi -->
        <div class="form-group">
          <label class="form-label">Rentang Tanggal Mutasi</label>
          <div class="date-range-inputs">
            <input autocomplete="off"
              v-model="localFilters.startDate"
              type="date"
              class="form-input date-input"
              aria-label="Tanggal Mutasi Mulai"
              title="Tanggal Mutasi Mulai"
            />
            <span class="date-sep">-</span>
            <input autocomplete="off"
              v-model="localFilters.endDate"
              type="date"
              class="form-input date-input"
              aria-label="Tanggal Mutasi Selesai"
              title="Tanggal Mutasi Selesai"
            />
          </div>
        </div>

        <!-- Field 2: Gudang Asal -->
        <div class="form-group">
          <label class="form-label" for="filter-source">Gudang Asal</label>
          <div class="select-wrapper">
            <select
              id="filter-source"
              v-model="localFilters.sourceWarehouseId"
              class="form-select"
            >
              <option value="">Semua Gudang Asal</option>
              <option
                v-for="warehouse in normalizedWarehouses"
                :key="warehouse.id"
                :value="warehouse.id"
              >
                {{ warehouse.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Field 3: Gudang Tujuan -->
        <div class="form-group">
          <label class="form-label" for="filter-target">Gudang Tujuan</label>
          <div class="select-wrapper">
            <select
              id="filter-target"
              v-model="localFilters.targetWarehouseId"
              class="form-select"
            >
              <option value="">Semua Gudang Tujuan</option>
              <option
                v-for="warehouse in normalizedWarehouses"
                :key="warehouse.id"
                :value="warehouse.id"
                :disabled="Boolean(localFilters.sourceWarehouseId && warehouse.id === localFilters.sourceWarehouseId)"
              >
                {{ warehouse.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Field 4: Kata Kunci -->
        <div class="form-group">
          <label class="form-label" for="filter-keyword">Kata Kunci</label>
          <div class="input-with-icon">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input autocomplete="off"
              id="filter-keyword"
              v-model="localFilters.keyword"
              type="text"
              class="form-input with-icon"
              placeholder="Cari nomor mutasi atau catatan keperluan"
            />
          </div>
        </div>
      </div>

      <div class="filter-actions-bar">
        <button
          type="button"
          class="btn-cancel btn-with-icon"
          @click="handleReset"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
            <path d="M3 3v5h5"></path>
          </svg>
          Reset Filter
        </button>

        <button
          type="submit"
          class="btn-primary btn-with-icon"
          :disabled="loading"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          Tampilkan Data
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.filter-card {
  padding: 1.25rem 1.5rem;
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
}

.filter-card-header {
  margin-bottom: 1.25rem;
}

.title-with-icon {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.filter-icon-box {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary, #087f78);
  background: var(--color-primary-light, #dff7f4);
}

.filter-icon-box svg {
  width: 16px;
  height: 16px;
}

.filter-card-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  align-items: flex-start;
}

.form-group {
  margin-bottom: 0;
  display: flex;
  flex-direction: column;
}

.form-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
  margin-bottom: 0.4rem;
}

.date-range-inputs {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.date-input {
  flex: 1;
  padding: 0.5rem 0.5rem;
  font-size: 0.8rem;
  min-width: 0;
}

.date-sep {
  color: var(--color-text-secondary, #64748b);
  font-weight: 600;
}

.input-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  width: 16px;
  height: 16px;
  color: var(--color-text-secondary, #94a3b8);
  pointer-events: none;
}

.form-input.with-icon {
  padding-left: 2.25rem;
}

.filter-actions-bar {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.75rem;
  margin-top: 1.25rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border-soft, #e8edf3);
}

.btn-with-icon {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 1.15rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-with-icon svg {
  width: 16px;
  height: 16px;
}

@media (max-width: 1024px) {
  .filter-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }

  .filter-actions-bar {
    flex-direction: column-reverse;
  }

  .filter-actions-bar button {
    width: 100%;
    justify-content: center;
  }
}
</style>
