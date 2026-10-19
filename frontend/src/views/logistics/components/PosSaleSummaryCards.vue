<script setup>
defineProps({
  summary: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <div class="pos-summary-grid">
    <!-- Card 1: Transaksi Hari Ini -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">TRANSAKSI HARI INI</span>
        <div class="card-icon icon-teal" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number">{{ summary.todayTransactions }}</span>
        <span class="metric-unit">{{ summary.todayUnit }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <div class="trend-badge trend-up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
            <polyline points="17 6 23 6 23 12"></polyline>
          </svg>
          <span>{{ summary.todayChange }}</span>
        </div>
        <span class="bottom-separator">•</span>
        <span class="bottom-subtext">{{ summary.todayCashier }}</span>
      </div>
    </div>

    <!-- Card 2: Produk Terjual -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">PRODUK TERJUAL</span>
        <div class="card-icon icon-blue" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
            <line x1="12" y1="11" x2="12" y2="15"></line>
            <line x1="10" y1="13" x2="14" y2="13"></line>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number">{{ summary.productsSold }}</span>
        <span class="metric-unit">{{ summary.productsUnit }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <span class="status-indicator-bar bar-blue"></span>
        <span class="bottom-label">{{ summary.productsSubtitle }}</span>
      </div>
    </div>

    <!-- Card 3: Total Penjualan -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">TOTAL PENJUALAN</span>
        <div class="card-icon icon-cyan" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
            <line x1="1" y1="10" x2="23" y2="10"></line>
            <circle cx="18" cy="15" r="1.5"></circle>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-currency">{{ summary.totalSalesCurrency }}</span>
        <span class="metric-number">{{ summary.totalSalesValue }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom card-bottom-split">
        <div class="bottom-left-info">
          <span class="status-indicator-bar bar-teal"></span>
          <span class="bottom-label font-medium text-teal">{{ summary.totalSalesNet }}</span>
        </div>
        <span class="bottom-average">{{ summary.totalSalesAverage }}</span>
      </div>
    </div>

    <!-- Card 4: Transaksi Dibatalkan -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">TRANSAKSI DIBATALKAN</span>
        <div class="card-icon icon-red" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number text-red">{{ summary.cancelledTransactions }}</span>
        <span class="metric-unit text-red font-medium">{{ summary.cancelledUnit }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom card-bottom-split">
        <div class="bottom-left-info">
          <span class="status-indicator-bar bar-red"></span>
          <span class="bottom-label text-red font-medium">Total: {{ summary.cancelledTotal }}</span>
        </div>
        <span class="bottom-note">{{ summary.cancelledNote }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pos-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
}

.summary-card {
  padding: 1.25rem 1.4rem;
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
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

.card-label {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--color-text-secondary, #64748b);
  text-transform: uppercase;
}

.card-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.card-icon svg {
  width: 18px;
  height: 18px;
}

.icon-teal {
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
}

.icon-blue {
  background: #eff6ff;
  color: #2563eb;
}

.icon-cyan {
  background: #ecfeff;
  color: #0891b2;
}

.icon-red {
  background: #fef2f2;
  color: #dc2626;
}

.card-metric {
  display: flex;
  align-items: baseline;
  gap: 0.45rem;
  margin-bottom: 0.85rem;
  min-height: 2.25rem;
}

.metric-currency {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
}

.metric-number {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  line-height: 1.1;
}

.metric-unit {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--color-text-secondary, #64748b);
}

.text-red {
  color: #dc2626 !important;
}

.text-teal {
  color: var(--color-primary, #087f78) !important;
}

.card-divider {
  height: 1px;
  background: var(--color-border-soft, #e8edf3);
  margin-bottom: 0.75rem;
}

.card-bottom {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.78rem;
  color: var(--color-text-secondary, #64748b);
  flex-wrap: wrap;
}

.card-bottom-split {
  justify-content: space-between;
}

.bottom-left-info {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.trend-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.74rem;
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: 6px;
}

.trend-badge svg {
  width: 12px;
  height: 12px;
}

.trend-up {
  background: #dcfce7;
  color: #15803d;
}

.bottom-separator {
  color: #cbd5e1;
}

.bottom-subtext {
  font-size: 0.76rem;
  color: #64748b;
  font-weight: 500;
}

.status-indicator-bar {
  width: 3px;
  height: 14px;
  border-radius: 2px;
  flex-shrink: 0;
}

.bar-teal {
  background: var(--color-primary, #087f78);
}

.bar-blue {
  background: #2563eb;
}

.bar-cyan {
  background: #0891b2;
}

.bar-red {
  background: #dc2626;
}

.bottom-label {
  color: var(--color-text-secondary, #64748b);
}

.bottom-average {
  font-size: 0.74rem;
  color: #64748b;
  font-weight: 500;
}

.bottom-note {
  font-size: 0.74rem;
  color: #94a3b8;
  font-style: italic;
}

.font-medium {
  font-weight: 600;
}

@media (max-width: 1280px) {
  .pos-summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .pos-summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
