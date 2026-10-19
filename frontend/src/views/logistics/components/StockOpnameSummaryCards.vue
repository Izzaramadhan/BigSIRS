<script setup>
defineProps({
  summary: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <div class="opname-summary-grid">
    <!-- Card 1: Sedang Berlangsung -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">SEDANG BERLANGSUNG</span>
        <div class="card-icon icon-teal" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number">{{ summary.inProgressSessions }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <span class="status-indicator-bar bar-teal"></span>
        <span class="bottom-label">{{ summary.inProgressNote }}</span>
      </div>
    </div>

    <!-- Card 2: Menunggu Peninjauan -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">MENUNGGU PENINJAUAN</span>
        <div class="card-icon icon-blue" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number">{{ summary.pendingReviewWarehouses }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <svg class="mini-icon text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
        </svg>
        <span class="bottom-label">{{ summary.pendingReviewNote }}</span>
      </div>
    </div>

    <!-- Card 3: Selesai Bulan Ini -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">SELESAI BULAN INI</span>
        <div class="card-icon icon-green" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number">{{ summary.completedThisMonthSessions }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <svg class="mini-icon text-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
          <polyline points="17 6 23 6 23 12"></polyline>
        </svg>
        <span class="bottom-label text-green font-medium">{{ summary.completedThisMonthNote }}</span>
      </div>
    </div>

    <!-- Card 4: Total Selisih Ditemukan -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">TOTAL SELISIH DITEMUKAN</span>
        <div class="card-icon icon-red" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="9" y1="15" x2="15" y2="15"></line>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number text-red">{{ summary.discrepancyItems }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom justify-between">
        <div class="card-bottom-left">
          <span class="bottom-label">Valuasi:</span>
          <span class="bottom-value font-bold text-navy">{{ summary.discrepancyValue }}</span>
        </div>
        <span class="ratio-pill">{{ summary.discrepancyRatio }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.opname-summary-grid {
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

.icon-green {
  background: #ecfdf5;
  color: #059669;
}

.icon-red {
  background: #fef2f2;
  color: #dc2626;
}

.card-metric {
  display: flex;
  align-items: baseline;
  gap: 0.6rem;
  margin-bottom: 0.85rem;
  min-height: 2.25rem;
}

.metric-number {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  line-height: 1.1;
}

.text-red {
  color: #dc2626;
}

.text-green {
  color: #059669;
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

.justify-between {
  justify-content: space-between;
}

.card-bottom-left {
  display: flex;
  align-items: center;
  gap: 0.35rem;
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

.mini-icon {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.bottom-label {
  color: var(--color-text-secondary, #64748b);
}

.bottom-value {
  color: var(--color-text-navy, #17243d);
}

.ratio-pill {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  background: #f1f5f9;
  color: #475569;
}

.text-navy {
  color: var(--color-text-navy, #17243d);
}

.text-muted {
  color: var(--color-text-secondary, #64748b);
}

.font-bold {
  font-weight: 700;
}

.font-medium {
  font-weight: 600;
}

@media (max-width: 1280px) {
  .opname-summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .opname-summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
