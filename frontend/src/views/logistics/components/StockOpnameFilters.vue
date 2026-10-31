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

const warehouseOptions = computed(() => {
  return props.warehouses.map((w) => {
    if (typeof w === 'object' && w !== null) {
      return { id: w.id, name: w.name };
    }
    return { id: w, name: String(w) };
  });
});

const localFilters = reactive({
  warehouse_id: props.filters.warehouse_id ?? '',
  hide_zero_stock: props.filters.hide_zero_stock ?? true,
  search: props.filters.search || ''
});

watch(
  () => props.filters,
  (newVal) => {
    Object.assign(localFilters, {
      warehouse_id: newVal.warehouse_id ?? '',
      hide_zero_stock: newVal.hide_zero_stock ?? true,
      search: newVal.search || ''
    });
  },
  { deep: true }
);

const handleApply = () => {
  emit('apply', { ...localFilters });
};

const handleReset = () => {
  localFilters.warehouse_id = '';
  localFilters.hide_zero_stock = true;
  localFilters.search = '';
  emit('reset');
};
</script>

<template>
  <div class="filter-card card">
    <div class="filter-card-header">
      <div class="title-with-icon">
        <div class="filter-icon-box" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
          </svg>
        </div>
        <h3 class="filter-card-title">Filter Saldo Stok Logistik</h3>
      </div>
      <span class="filter-context-note">Sesuaikan tampilan gudang dan stok sediaan</span>
    </div>

    <form autocomplete="off" class="filter-form" @submit.prevent="handleApply">
      <div class="filter-grid">
        <!-- 1. Filter Gudang -->
        <div class="form-group">
          <label class="form-label" for="filter-warehouse">Gudang</label>
          <div class="select-wrapper">
            <select
              id="filter-warehouse"
              v-model="localFilters.warehouse_id"
              class="form-select"
              @change="handleApply"
            >
              <option value="">Semua Gudang</option>
              <option
                v-for="wh in warehouseOptions"
                :key="wh.id"
                :value="wh.id"
              >
                {{ wh.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- 2. Filter Tampilan Stok Nol -->
        <div class="form-group">
          <label class="form-label" for="filter-hide-zero">Status Stok</label>
          <div class="select-wrapper">
            <select
              id="filter-hide-zero"
              v-model="localFilters.hide_zero_stock"
              class="form-select"
              @change="handleApply"
            >
              <option :value="true">Sembunyikan stok 0</option>
              <option :value="false">Tampilkan stok 0</option>
            </select>
          </div>
        </div>

        <!-- 3. Pencarian -->
        <div class="form-group search-group">
          <label class="form-label" for="filter-search">Pencarian Sediaan</label>
          <div class="search-input-wrap">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              id="filter-search"
              v-model="localFilters.search"
              type="text"
              class="form-input search-input"
              placeholder="Cari sediaan, kode, batch, satuan..."
            />
            <button
              v-if="localFilters.search"
              type="button"
              class="clear-search-btn"
              title="Hapus pencarian"
              @click="localFilters.search = ''; handleApply()"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div class="filter-actions">
        <button
          type="button"
          class="btn btn-secondary"
          :disabled="loading"
          @click="handleReset"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-icon">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
            <path d="M3 3v5h5"></path>
          </svg>
          Reset Filter
        </button>

        <button
          type="submit"
          class="btn btn-primary"
          :disabled="loading"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-icon">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          Terapkan Filter
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.filter-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  margin-bottom: 1.5rem;
}

.filter-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 0.875rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.title-with-icon {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.filter-icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #f0fdfa;
  color: #0d9488;
}

.filter-icon-box svg {
  width: 18px;
  height: 18px;
}

.filter-card-title {
  font-size: 1rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}

.filter-context-note {
  font-size: 0.8125rem;
  color: #64748b;
}

.filter-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1.5fr;
  gap: 1.25rem;
  align-items: flex-end;
}

@media (max-width: 900px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #334155;
}

.select-wrapper {
  position: relative;
}

.form-select,
.form-input {
  width: 100%;
  height: 40px;
  padding: 0 0.875rem;
  font-size: 0.875rem;
  color: #1e293b;
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  transition: all 0.2s ease;
  outline: none;
}

.form-select:focus,
.form-input:focus {
  border-color: #0d9488;
  box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
}

.search-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.875rem;
  width: 18px;
  height: 18px;
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  padding-left: 2.5rem;
  padding-right: 2.25rem;
}

.clear-search-btn {
  position: absolute;
  right: 0.625rem;
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.clear-search-btn:hover {
  color: #475569;
  background: #f1f5f9;
}

.clear-search-btn svg {
  width: 16px;
  height: 16px;
}

.filter-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.25rem;
  padding-top: 1rem;
  border-top: 1px solid #f1f5f9;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  height: 38px;
  padding: 0 1.125rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.btn-icon {
  width: 16px;
  height: 16px;
}

.btn-secondary {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #475569;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #94a3b8;
  color: #1e293b;
}

.btn-primary {
  background: #0d9488;
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: #0f766e;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
