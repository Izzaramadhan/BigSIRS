<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  filters: {
    type: Object,
    required: true
  },
  suppliers: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['apply', 'reset']);

const localFilters = reactive({
  invoiceNumber: props.filters.invoiceNumber || '',
  supplierId: props.filters.supplierId || '',
  status: props.filters.status || '',
  invoiceDateStart: props.filters.invoiceDateStart || '',
  invoiceDateEnd: props.filters.invoiceDateEnd || '',
  dueDateStart: props.filters.dueDateStart || '',
  dueDateEnd: props.filters.dueDateEnd || ''
});

watch(
  () => props.filters,
  (newVal) => {
    Object.assign(localFilters, {
      invoiceNumber: newVal.invoiceNumber || '',
      supplierId: newVal.supplierId || '',
      status: newVal.status || '',
      invoiceDateStart: newVal.invoiceDateStart || '',
      invoiceDateEnd: newVal.invoiceDateEnd || '',
      dueDateStart: newVal.dueDateStart || '',
      dueDateEnd: newVal.dueDateEnd || ''
    });
  },
  { deep: true }
);

const handleApply = () => {
  emit('apply', { ...localFilters });
};

const handleReset = () => {
  localFilters.invoiceNumber = '';
  localFilters.supplierId = '';
  localFilters.status = '';
  localFilters.invoiceDateStart = '';
  localFilters.invoiceDateEnd = '';
  localFilters.dueDateStart = '';
  localFilters.dueDateEnd = '';
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
        <h3 class="filter-card-title">Pencarian Faktur</h3>
      </div>
    </div>

    <form autocomplete="off" class="filter-form" @submit.prevent="handleApply">
      <div class="filter-grid">
        <!-- Field 1: Nomor Faktur -->
        <div class="form-group">
          <label class="form-label" for="filter-invoice-number">Nomor Faktur</label>
          <div class="input-with-icon">
            <span class="input-prefix">#</span>
            <input autocomplete="off"
              id="filter-invoice-number"
              v-model="localFilters.invoiceNumber"
              type="text"
              class="form-input with-prefix"
              placeholder="Contoh: FKT-2026/09/..."
            />
          </div>
        </div>

        <!-- Field 2: Supplier -->
        <div class="form-group">
          <label class="form-label" for="filter-supplier">Supplier</label>
          <div class="select-wrapper">
            <select
              id="filter-supplier"
              v-model="localFilters.supplierId"
              class="form-select"
            >
              <option value="">Semua Supplier</option>
              <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">
                {{ sup.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Field 3: Status Faktur -->
        <div class="form-group">
          <label class="form-label" for="filter-status">Status Faktur</label>
          <div class="select-wrapper">
            <select
              id="filter-status"
              v-model="localFilters.status"
              class="form-select"
            >
              <option value="">Semua Status</option>
              <option value="Lunas">Lunas</option>
              <option value="Belum Lunas">Belum Lunas</option>
            </select>
          </div>
        </div>

        <!-- Field 4: Rentang Tanggal Faktur -->
        <div class="form-group">
          <label class="form-label">Tanggal Faktur</label>
          <div class="date-range-inputs">
            <input autocomplete="off"
              v-model="localFilters.invoiceDateStart"
              type="date"
              class="form-input date-input"
              aria-label="Tanggal Faktur Mulai"
              title="Tanggal Faktur Mulai"
            />
            <span class="date-sep">-</span>
            <input autocomplete="off"
              v-model="localFilters.invoiceDateEnd"
              type="date"
              class="form-input date-input"
              aria-label="Tanggal Faktur Selesai"
              title="Tanggal Faktur Selesai"
            />
          </div>
        </div>

        <!-- Field 5: Rentang Tanggal Jatuh Tempo -->
        <div class="form-group">
          <label class="form-label">Tanggal Jatuh Tempo</label>
          <div class="date-range-inputs">
            <input autocomplete="off"
              v-model="localFilters.dueDateStart"
              type="date"
              class="form-input date-input"
              aria-label="Tanggal Jatuh Tempo Mulai"
              title="Tanggal Jatuh Tempo Mulai"
            />
            <span class="date-sep">-</span>
            <input autocomplete="off"
              v-model="localFilters.dueDateEnd"
              type="date"
              class="form-input date-input"
              aria-label="Tanggal Jatuh Tempo Selesai"
              title="Tanggal Jatuh Tempo Selesai"
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
          Reset
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
  grid-template-columns: repeat(5, 1fr);
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

.input-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.input-prefix {
  position: absolute;
  left: 0.75rem;
  color: var(--color-text-secondary, #64748b);
  font-size: 0.9rem;
  font-weight: 600;
  pointer-events: none;
}

.form-input.with-prefix {
  padding-left: 1.75rem;
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

@media (max-width: 1280px) {
  .filter-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
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
