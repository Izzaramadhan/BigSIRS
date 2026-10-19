<script setup>
defineProps({
  activeFilter: {
    type: String,
    default: 'all'
  },
  counts: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['select']);

const quickItems = [
  { key: 'all', label: 'Semua Tagihan', countKey: 'all', icon: 'M4 6h16M4 12h16M4 18h16' },
  { key: 'ready_cashier', label: 'Siap Bayar Kasir', countKey: 'ready_cashier', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
  { key: 'verify_bpjs', label: 'Verifikasi BPJS', countKey: 'verify_bpjs', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
  { key: 'general_today', label: 'Pasien Umum Hari Ini', countKey: 'general_today', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
  { key: 'tariff_adjustment', label: 'Perlu Penyesuaian Tarif', countKey: 'tariff_adjustment', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' }
];
</script>

<template>
  <div class="quick-filters-container" aria-label="Filter Cepat Billing">
    <div class="quick-filters-label">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
      </svg>
      <span>Filter Cepat:</span>
    </div>

    <div class="chips-list">
      <button
        v-for="item in quickItems"
        :key="item.key"
        type="button"
        class="quick-chip"
        :class="{ 'is-active': activeFilter === item.key }"
        @click="emit('select', item.key)"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="chip-icon">
          <path :d="item.icon"></path>
        </svg>
        <span class="chip-text">{{ item.label }}</span>
        <span class="chip-count"> ({{ counts[item.countKey] || 0 }})</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.quick-filters-container {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.75rem 1.15rem;
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 10px;
  overflow-x: auto;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.quick-filters-label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.775rem;
  font-weight: 700;
  color: #475569;
  white-space: nowrap;
}

.quick-filters-label svg {
  width: 15px;
  height: 15px;
  color: var(--color-primary, #087f78);
}

.chips-list {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: nowrap;
}

.quick-chip {
  height: 32px;
  padding: 0 0.85rem;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.775rem;
  font-weight: 600;
  color: #475569;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.quick-chip:hover {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #cbd5e1;
}

.quick-chip.is-active {
  background: var(--color-primary, #087f78);
  border-color: var(--color-primary, #087f78);
  color: #ffffff;
}

.quick-chip.is-active .chip-icon {
  color: #ffffff;
}

.quick-chip.is-active .chip-count {
  color: #e6f6f5;
  font-weight: 700;
}

.chip-icon {
  width: 14px;
  height: 14px;
  color: #64748b;
}

.chip-count {
  font-size: 0.75rem;
  color: #64748b;
  font-family: monospace;
}
</style>
