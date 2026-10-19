<script setup>
defineProps({
  summary: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <div class="issue-summary-grid">
    <!-- Card 1: Pengeluaran Hari Ini -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">PENGELUARAN HARI INI</span>
        <div class="card-icon icon-teal" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number">{{ summary.todayIssues }}</span>
        <span class="metric-unit">{{ summary.todayUnit }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <span class="status-indicator-bar bar-teal"></span>
        <span class="bottom-label">{{ summary.todayNote }}</span>
      </div>
    </div>

    <!-- Card 2: Total Item Keluar -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">TOTAL ITEM KELUAR</span>
        <div class="card-icon icon-blue" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number">{{ summary.totalItemOut }}</span>
        <span class="metric-unit">{{ summary.totalItemUnit }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <span class="status-indicator-bar bar-blue"></span>
        <span class="bottom-label">{{ summary.totalItemNote }}</span>
      </div>
    </div>

    <!-- Card 3: Barang Rusak (BAP) -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">BARANG RUSAK (BAP)</span>
        <div class="card-icon icon-cyan" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number">{{ summary.damagedItem }}</span>
        <span class="metric-unit">{{ summary.damagedUnit }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <span class="status-indicator-bar bar-cyan"></span>
        <span class="bottom-label">{{ summary.damagedNote }}</span>
      </div>
    </div>

    <!-- Card 4: Barang Kedaluwarsa -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">BARANG KEDALUWARSA</span>
        <div class="card-icon icon-red" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
            <line x1="10" y1="14" x2="14" y2="18"></line>
            <line x1="14" y1="14" x2="10" y2="18"></line>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number text-red">{{ summary.expiredItem }}</span>
        <span class="metric-unit text-red">{{ summary.expiredUnit }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <span class="status-indicator-bar bar-red"></span>
        <span class="bottom-label text-red font-medium">{{ summary.expiredNote }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.issue-summary-grid {
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

.font-medium {
  font-weight: 600;
}

@media (max-width: 1280px) {
  .issue-summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .issue-summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
