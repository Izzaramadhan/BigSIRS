<script setup>
defineProps({
  summary: {
    type: Object,
    required: true
  },
  formatRupiah: {
    type: Function,
    required: true
  }
});
</script>

<template>
  <div class="summary-cards-grid">
    <!-- Card 1: Total Faktur -->
    <div class="summary-card card">
      <div class="card-top">
        <div class="card-meta">
          <span class="card-label">TOTAL FAKTUR</span>
        </div>
        <div class="card-icon icon-teal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
          </svg>
        </div>
      </div>

      <div class="card-metric">
        <span class="metric-number">{{ summary.totalCount }}</span>
        <span class="metric-unit">Faktur</span>
      </div>

      <div class="card-divider"></div>

      <div class="card-bottom">
        <span class="bottom-label">Total Nilai Tagihan</span>
        <span class="bottom-value text-navy">{{ formatRupiah(summary.totalAmount) }}</span>
      </div>
    </div>

    <!-- Card 2: Faktur Lunas -->
    <div class="summary-card card">
      <div class="card-top">
        <div class="card-meta">
          <span class="card-label">FAKTUR LUNAS</span>
          <span class="pill-badge pill-success">{{ summary.paidPercentage }}%</span>
        </div>
        <div class="card-icon icon-success">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
      </div>

      <div class="card-metric">
        <span class="metric-number">{{ summary.paidCount }}</span>
        <span class="metric-unit">Faktur</span>
      </div>

      <div class="card-divider"></div>

      <div class="card-bottom">
        <span class="bottom-label">Nilai Terbayar</span>
        <span class="bottom-value text-primary">{{ formatRupiah(summary.paidAmount) }}</span>
      </div>
    </div>

    <!-- Card 3: Faktur Belum Lunas -->
    <div class="summary-card card">
      <div class="card-top">
        <div class="card-meta">
          <span class="card-label">FAKTUR BELUM LUNAS</span>
          <span v-if="summary.overdueCount > 0" class="pill-badge pill-danger">
            <span class="dot-indicator"></span>
            {{ summary.overdueCount }} Lewat Jatuh Tempo
          </span>
        </div>
        <div class="card-icon icon-danger">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        </div>
      </div>

      <div class="card-metric">
        <span class="metric-number">{{ summary.unpaidCount }}</span>
        <span class="metric-unit">Faktur ({{ summary.unpaidPercentage }}%)</span>
      </div>

      <div class="card-divider"></div>

      <div class="card-bottom">
        <span class="bottom-label">Sisa Tagihan</span>
        <span class="bottom-value text-danger">{{ formatRupiah(summary.unpaidAmount) }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.summary-cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
}

.summary-card {
  padding: 1.25rem 1.5rem;
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s;
}

.summary-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.card-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.card-label {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--color-text-secondary, #64748b);
  text-transform: uppercase;
}

.card-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.card-icon svg {
  width: 20px;
  height: 20px;
}

.icon-teal {
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
}

.icon-success {
  background: #ecfdf5;
  color: #059669;
}

.icon-danger {
  background: #fef2f2;
  color: #dc2626;
}

.card-metric {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.metric-number {
  font-size: 2.25rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  line-height: 1;
}

.metric-unit {
  font-size: 0.95rem;
  color: var(--color-text-secondary, #64748b);
  font-weight: 500;
}

.card-divider {
  height: 1px;
  background: var(--color-border-soft, #e8edf3);
  margin-bottom: 0.85rem;
}

.card-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
}

.bottom-label {
  color: var(--color-text-secondary, #64748b);
}

.bottom-value {
  font-weight: 700;
  font-size: 0.95rem;
}

.text-navy {
  color: var(--color-text-navy, #17243d);
}

.text-primary {
  color: var(--color-primary, #087f78);
}

.text-danger {
  color: var(--color-danger, #d64545);
}

.pill-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  line-height: 1;
}

.pill-success {
  background: #d1fae5;
  color: #047857;
}

.pill-danger {
  background: #fee2e2;
  color: #b91c1c;
}

.dot-indicator {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
}

@media (max-width: 1024px) {
  .summary-cards-grid {
    grid-template-columns: 1fr;
  }
}
</style>
