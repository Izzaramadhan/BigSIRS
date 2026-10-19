<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  filters: {
    type: Object,
    required: true
  },
  warehouses: {
    type: Array,
    default: () => []
  },
  officers: {
    type: Array,
    default: () => []
  },
  purposes: {
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
  issueNumber: props.filters.issueNumber || '',
  startDate: props.filters.startDate || '',
  endDate: props.filters.endDate || '',
  warehouse: props.filters.warehouse || 'Semua Gudang Asal',
  officer: props.filters.officer || 'Semua Petugas',
  purpose: props.filters.purpose || 'Semua Keperluan',
  notes: props.filters.notes || ''
});

watch(
  () => props.filters,
  (newVal) => {
    Object.assign(localFilters, {
      issueNumber: newVal.issueNumber || '',
      startDate: newVal.startDate || '',
      endDate: newVal.endDate || '',
      warehouse: newVal.warehouse || 'Semua Gudang Asal',
      officer: newVal.officer || 'Semua Petugas',
      purpose: newVal.purpose || 'Semua Keperluan',
      notes: newVal.notes || ''
    });
  },
  { deep: true }
);

const handleApply = () => {
  emit('apply', { ...localFilters });
};

const handleReset = () => {
  localFilters.issueNumber = '';
  localFilters.startDate = '';
  localFilters.endDate = '';
  localFilters.warehouse = 'Semua Gudang Asal';
  localFilters.officer = 'Semua Petugas';
  localFilters.purpose = 'Semua Keperluan';
  localFilters.notes = '';
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
        <h3 class="filter-card-title">Filter Pengeluaran Nonpenjualan</h3>
      </div>
      <span class="sop-badge">SOP LOG-FAR/VII/2026</span>
    </div>

    <form class="filter-form" @submit.prevent="handleApply">
      <div class="filter-grid">
        <!-- Field 1: Nomor Pengeluaran -->
        <div class="form-group">
          <label class="form-label" for="filter-issue-no">Nomor Pengeluaran</label>
          <div class="input-with-icon">
            <span class="input-prefix">#</span>
            <input
              id="filter-issue-no"
              v-model="localFilters.issueNumber"
              type="text"
              class="form-input with-prefix"
              placeholder="OUT-202609-0012"
            />
          </div>
        </div>

        <!-- Field 2: Rentang Tanggal -->
        <div class="form-group">
          <label class="form-label">Rentang Tanggal</label>
          <div class="date-range-inputs">
            <input
              v-model="localFilters.startDate"
              type="date"
              class="form-input date-input"
              aria-label="Tanggal Mulai"
              title="Tanggal Mulai"
            />
            <span class="date-sep">-</span>
            <input
              v-model="localFilters.endDate"
              type="date"
              class="form-input date-input"
              aria-label="Tanggal Selesai"
              title="Tanggal Selesai"
            />
          </div>
        </div>

        <!-- Field 3: Gudang Asal -->
        <div class="form-group">
          <label class="form-label" for="filter-warehouse">Gudang Asal</label>
          <div class="select-wrapper">
            <select
              id="filter-warehouse"
              v-model="localFilters.warehouse"
              class="form-select"
            >
              <option value="Semua Gudang Asal">Semua Gudang Asal</option>
              <option v-for="wh in warehouses" :key="wh" :value="wh">
                {{ wh }}
              </option>
            </select>
          </div>
        </div>

        <!-- Field 4: Petugas Otorisasi -->
        <div class="form-group">
          <label class="form-label" for="filter-officer">Petugas Otorisasi</label>
          <div class="select-wrapper">
            <select
              id="filter-officer"
              v-model="localFilters.officer"
              class="form-select"
            >
              <option value="Semua Petugas">Semua Petugas</option>
              <option v-for="off in officers" :key="off" :value="off">
                {{ off }}
              </option>
            </select>
          </div>
        </div>

        <!-- Field 5: Jenis Keperluan -->
        <div class="form-group">
          <label class="form-label" for="filter-purpose">Jenis Keperluan</label>
          <div class="select-wrapper">
            <select
              id="filter-purpose"
              v-model="localFilters.purpose"
              class="form-select"
            >
              <option value="Semua Keperluan">Semua Keperluan</option>
              <option v-for="pur in purposes" :key="pur" :value="pur">
                {{ pur }}
              </option>
            </select>
          </div>
        </div>

        <!-- Field 6: Catatan / Disposisi -->
        <div class="form-group">
          <label class="form-label" for="filter-notes">Catatan / Disposisi</label>
          <div class="input-with-icon">
            <svg class="search-prefix-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              id="filter-notes"
              v-model="localFilters.notes"
              type="text"
              class="form-input with-icon"
              placeholder="No. BAP, catatan, disposisi..."
            />
          </div>
        </div>
      </div>

      <!-- Footer Action & Note -->
      <div class="filter-actions-row">
        <div class="filter-note-left">
          <span class="note-bullet"></span>
          <span>Menyaring pencatatan logistik farmasi (Non-Kasir/Non-Billing)</span>
        </div>

        <div class="filter-actions">
          <button
            type="button"
            class="btn-cancel"
            :disabled="loading"
            @click="handleReset"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="1 4 1 10 7 10"></polyline>
              <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
            </svg>
            Reset Filter
          </button>

          <button
            type="submit"
            class="btn-primary"
            :disabled="loading"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            Tampilkan Data
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<style scoped>
.filter-card {
  padding: 1.25rem 1.4rem;
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
  margin-bottom: 1.25rem;
}

.filter-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 0.5rem;
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
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
  display: flex;
  align-items: center;
  justify-content: center;
}

.filter-icon-box svg {
  width: 15px;
  height: 15px;
}

.filter-card-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.sop-badge {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
  background: #f1f5f9;
  color: #475569;
  letter-spacing: 0.02em;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.form-input,
.form-select {
  width: 100%;
  padding: 0.55rem 0.75rem;
  font-size: 0.85rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  background: #ffffff;
  color: var(--color-text-navy, #17243d);
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.input-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.input-prefix {
  position: absolute;
  left: 0.75rem;
  color: #94a3b8;
  font-weight: 600;
  font-size: 0.85rem;
  pointer-events: none;
}

.search-prefix-icon {
  position: absolute;
  left: 0.75rem;
  width: 14px;
  height: 14px;
  color: #94a3b8;
  pointer-events: none;
}

.form-input.with-prefix {
  padding-left: 1.6rem;
}

.form-input.with-icon {
  padding-left: 2rem;
}

.form-input:focus,
.form-select:focus {
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.12);
}

.date-range-inputs {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.date-input {
  flex: 1;
  min-width: 0;
}

.date-sep {
  color: #94a3b8;
  font-size: 0.85rem;
}

.select-wrapper {
  position: relative;
}

.select-wrapper::after {
  content: '';
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 0;
  height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 5px solid #64748b;
  pointer-events: none;
}

.form-select {
  appearance: none;
  padding-right: 2rem;
  cursor: pointer;
}

.filter-actions-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1rem;
  padding-top: 0.85rem;
  border-top: 1px dashed var(--color-border-soft, #e2e8f0);
  flex-wrap: wrap;
  gap: 0.75rem;
}

.filter-note-left {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.76rem;
  color: var(--color-text-secondary, #64748b);
}

.note-bullet {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--color-primary, #087f78);
  flex-shrink: 0;
}

.filter-actions {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.btn-cancel,
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-cancel svg,
.btn-primary svg {
  width: 14px;
  height: 14px;
}

.btn-cancel {
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  color: var(--color-text-navy, #17243d);
}

.btn-cancel:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #94a3b8;
}

.btn-primary {
  background: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: #066761;
}

.btn-cancel:disabled,
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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
  .filter-actions-row {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-actions {
    justify-content: flex-end;
  }
}
</style>
