<script setup>
import { ref, reactive, watch } from 'vue';

const props = defineProps({
  filters: {
    type: Object,
    required: true
  },
  locations: {
    type: Array,
    default: () => []
  },
  paymentMethods: {
    type: Array,
    default: () => []
  },
  statuses: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['apply', 'reset']);

const isFilterCollapsed = ref(false);

const toggleCollapse = () => {
  isFilterCollapsed.value = !isFilterCollapsed.value;
};

const localFilters = reactive({
  transactionNumber: props.filters.transactionNumber || '',
  customerName: props.filters.customerName || '',
  startDate: props.filters.startDate || '',
  endDate: props.filters.endDate || '',
  location: props.filters.location || 'Semua Lokasi',
  paymentMethod: props.filters.paymentMethod || 'Semua Metode',
  status: props.filters.status || 'Semua Status'
});

watch(
  () => props.filters,
  (newVal) => {
    Object.assign(localFilters, {
      transactionNumber: newVal.transactionNumber || '',
      customerName: newVal.customerName || '',
      startDate: newVal.startDate || '',
      endDate: newVal.endDate || '',
      location: newVal.location || 'Semua Lokasi',
      paymentMethod: newVal.paymentMethod || 'Semua Metode',
      status: newVal.status || 'Semua Status'
    });
  },
  { deep: true }
);

const handleApply = () => {
  emit('apply', { ...localFilters });
};

const handleReset = () => {
  localFilters.transactionNumber = '';
  localFilters.customerName = '';
  localFilters.startDate = '';
  localFilters.endDate = '';
  localFilters.location = 'Semua Lokasi';
  localFilters.paymentMethod = 'Semua Metode';
  localFilters.status = 'Semua Status';
  emit('reset');
};
</script>

<template>
  <div class="filter-card card">
    <div class="filter-card-header">
      <div class="title-with-desc">
        <div class="header-main-title">
          <div class="filter-icon-box" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
            </svg>
          </div>
          <h3 class="filter-card-title">Filter Transaksi Penjualan Bebas</h3>
        </div>
        <p class="filter-card-subtitle">Saring riwayat pembayaran kasir berdasarkan parameter transaksi</p>
      </div>

      <button
        type="button"
        class="btn-toggle-filter"
        :aria-expanded="!isFilterCollapsed"
        @click="toggleCollapse"
      >
        <svg
          class="toggle-chevron"
          :class="{ 'chevron-rotated': isFilterCollapsed }"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
        <span>{{ isFilterCollapsed ? 'Tampilkan Filter' : 'Sembunyikan Filter' }}</span>
      </button>
    </div>

    <!-- Collapsible Filter Body -->
    <transition name="collapse-anim">
      <div v-show="!isFilterCollapsed" class="filter-collapsible-wrapper">
        <form class="filter-form" @submit.prevent="handleApply">
          <div class="filter-grid">
            <!-- Field 1: Nomor Transaksi -->
            <div class="form-group">
              <label class="form-label" for="filter-trans-no">Nomor Transaksi</label>
              <div class="input-with-icon">
                <span class="input-prefix">#</span>
                <input
                  id="filter-trans-no"
                  v-model="localFilters.transactionNumber"
                  type="text"
                  class="form-input with-prefix"
                  placeholder="TR-OTC-2026..."
                />
              </div>
            </div>

            <!-- Field 2: Nama Pembeli -->
            <div class="form-group">
              <label class="form-label" for="filter-cust-name">Nama Pembeli</label>
              <div class="input-with-icon">
                <svg class="input-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <input
                  id="filter-cust-name"
                  v-model="localFilters.customerName"
                  type="text"
                  class="form-input with-icon"
                  placeholder="Nama pembeli / umum..."
                />
              </div>
            </div>

            <!-- Field 3: Rentang Tanggal -->
            <div class="form-group">
              <label class="form-label">Rentang Tanggal</label>
              <div class="date-range-inputs">
                <input
                  v-model="localFilters.startDate"
                  type="date"
                  class="form-input date-input"
                  aria-label="Tanggal Mulai"
                />
                <span class="date-separator">–</span>
                <input
                  v-model="localFilters.endDate"
                  type="date"
                  class="form-input date-input"
                  aria-label="Tanggal Akhir"
                />
              </div>
            </div>

            <!-- Field 4: Gudang / Lokasi Kasir -->
            <div class="form-group">
              <label class="form-label" for="filter-location">Gudang / Lokasi Kasir</label>
              <select
                id="filter-location"
                v-model="localFilters.location"
                class="form-select"
              >
                <option v-for="loc in locations" :key="loc" :value="loc">
                  {{ loc }}
                </option>
              </select>
            </div>

            <!-- Field 5: Metode Pembayaran -->
            <div class="form-group">
              <label class="form-label" for="filter-payment">Metode Pembayaran</label>
              <select
                id="filter-payment"
                v-model="localFilters.paymentMethod"
                class="form-select"
              >
                <option v-for="pm in paymentMethods" :key="pm" :value="pm">
                  {{ pm }}
                </option>
              </select>
            </div>

            <!-- Field 6: Status Transaksi -->
            <div class="form-group">
              <label class="form-label" for="filter-status">Status Transaksi</label>
              <select
                id="filter-status"
                v-model="localFilters.status"
                class="form-select"
              >
                <option v-for="st in statuses" :key="st" :value="st">
                  {{ st }}
                </option>
              </select>
            </div>
          </div>

          <!-- Bottom Footer Bar -->
          <div class="filter-actions-bar">
            <div class="filter-sync-info">
              <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              <span>Menampilkan transaksi kasir yang sinkron dengan inventori lokal</span>
            </div>

            <div class="filter-buttons">
              <button
                type="button"
                class="btn-reset"
                :disabled="loading"
                @click="handleReset"
              >
                Reset Filter
              </button>
              <button
                type="submit"
                class="btn-submit"
                :disabled="loading"
              >
                <svg v-if="loading" class="spin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Tampilkan Data
              </button>
            </div>
          </div>
        </form>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.filter-card {
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
  padding: 1.25rem 1.4rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.filter-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.title-with-desc {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.header-main-title {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.filter-icon-box {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.filter-icon-box svg {
  width: 15px;
  height: 15px;
}

.filter-card-title {
  font-size: 0.96rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.filter-card-subtitle {
  font-size: 0.8rem;
  color: var(--color-text-secondary, #64748b);
  margin: 0;
  padding-left: 2.4rem;
}

.btn-toggle-filter {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: transparent;
  border: none;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-primary, #087f78);
  cursor: pointer;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  transition: background-color 0.15s ease;
  flex-shrink: 0;
}

.btn-toggle-filter:hover {
  background-color: var(--color-primary-light, #dff7f4);
}

.toggle-chevron {
  width: 16px;
  height: 16px;
  transition: transform 0.25s ease;
}

.chevron-rotated {
  transform: rotate(180deg);
}

.filter-collapsible-wrapper {
  margin-top: 1.2rem;
  padding-top: 1.1rem;
  border-top: 1px dashed var(--color-border-soft, #e2e8f0);
}

/* Collapse Transition */
.collapse-anim-enter-active,
.collapse-anim-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.collapse-anim-enter-from,
.collapse-anim-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-8px);
}

.collapse-anim-enter-to,
.collapse-anim-leave-from {
  opacity: 1;
  max-height: 500px;
  transform: translateY(0);
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem 1.25rem;
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
  height: 38px;
  padding: 0 0.85rem;
  font-size: 0.82rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 7px;
  background-color: #ffffff;
  color: var(--color-text-navy, #17243d);
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  width: 100%;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.12);
}

.input-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.input-prefix {
  position: absolute;
  left: 0.85rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #94a3b8;
  pointer-events: none;
}

.input-icon-svg {
  position: absolute;
  left: 0.85rem;
  width: 15px;
  height: 15px;
  color: #94a3b8;
  pointer-events: none;
}

.form-input.with-prefix,
.form-input.with-icon {
  padding-left: 2.1rem;
}

.date-range-inputs {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.date-input {
  flex: 1;
  font-size: 0.78rem;
}

.date-separator {
  color: #94a3b8;
  font-weight: 700;
}

.filter-actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.25rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border-soft, #f1f5f9);
  flex-wrap: wrap;
  gap: 0.75rem;
}

.filter-sync-info {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.78rem;
  color: #10b981;
  font-weight: 500;
}

.check-icon {
  width: 16px;
  height: 16px;
  color: #10b981;
}

.filter-buttons {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-reset {
  height: 38px;
  padding: 0 1.2rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
  background-color: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-reset:hover {
  background-color: #f1f5f9;
  color: #1e293b;
  border-color: #94a3b8;
}

.btn-submit {
  height: 38px;
  padding: 0 1.35rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #ffffff;
  background-color: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  border-radius: 7px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  transition: all 0.15s ease;
  box-shadow: 0 1px 2px rgba(8, 127, 120, 0.15);
}

.btn-submit:hover {
  background-color: #076d67;
  border-color: #076d67;
}

.btn-submit svg {
  width: 15px;
  height: 15px;
}

.spin-icon {
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

@media (max-width: 1024px) {
  .filter-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .filter-card-subtitle {
    padding-left: 0;
  }
}

@media (max-width: 640px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }
  .filter-card-header {
    flex-direction: column;
    align-items: flex-start;
  }
  .filter-actions-bar {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-buttons {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
