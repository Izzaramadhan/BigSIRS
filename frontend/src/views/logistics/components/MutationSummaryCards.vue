<script setup>
defineProps({
  summary: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <div class="mutation-summary-grid">
    <!-- Card 1: Total Mutasi -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">TOTAL MUTASI BULAN INI</span>
        <div class="card-icon icon-teal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="17 1 21 5 17 9"></polyline>
            <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
            <polyline points="7 23 3 19 7 15"></polyline>
            <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-number">{{ summary.totalTransactions }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <span class="bottom-label">Estimasi Nilai:</span>
        <span class="bottom-value text-navy font-bold">{{ summary.estimatedValue }}</span>
      </div>
    </div>

    <!-- Card 2: Gudang Asal Teraktif -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">GUDANG ASAL TERAKTIF</span>
        <div class="card-icon icon-blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-text text-navy">{{ summary.topSource }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <span class="counter-badge">{{ summary.topSourceCount ?? 0 }}</span>
        <span class="bottom-label">{{ summary.topSourceSubtitle }}</span>
      </div>
    </div>

    <!-- Card 3: Unit Penerima Terbanyak -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">UNIT PENERIMA TERBANYAK</span>
        <div class="card-icon icon-green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            <line x1="12" y1="11" x2="12" y2="17"></line>
            <line x1="9" y1="14" x2="15" y2="14"></line>
          </svg>
        </div>
      </div>
      <div class="card-metric">
        <span class="metric-text text-navy">{{ summary.topTarget }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <span class="counter-badge badge-green">{{ summary.topTargetCount ?? 0 }}</span>
        <span class="bottom-label">{{ summary.topTargetSubtitle }}</span>
      </div>
    </div>

    <!-- Card 4: Integritas Kartu Stok -->
    <div class="summary-card card">
      <div class="card-top">
        <span class="card-label">INTEGRITAS KARTU STOK</span>
        <div class="card-icon icon-shield">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            <polyline points="9 12 11 14 15 10"></polyline>
          </svg>
        </div>
      </div>
      <div class="card-metric align-items-center">
        <span class="metric-number">{{ summary.integrity }}</span>
        <span class="pill-badge pill-success">{{ summary.integrityBadge }}</span>
      </div>
      <div class="card-divider"></div>
      <div class="card-bottom">
        <span class="bottom-label text-muted">{{ summary.integritySubtitle }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mutation-summary-grid {
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

.icon-shield {
  background: #f0fdfa;
  color: #0d9488;
}

.card-metric {
  display: flex;
  align-items: baseline;
  gap: 0.6rem;
  margin-bottom: 0.85rem;
  min-height: 2.25rem;
}

.align-items-center {
  align-items: center;
}

.metric-number {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  line-height: 1.1;
}

.metric-text {
  font-size: 1.35rem;
  font-weight: 700;
  line-height: 1.2;
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

.bottom-label {
  color: var(--color-text-secondary, #64748b);
}

.bottom-value {
  color: var(--color-text-navy, #17243d);
}

.counter-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.15rem 0.45rem;
  font-size: 0.7rem;
  font-weight: 700;
  border-radius: 4px;
  background: #e0f2fe;
  color: #0369a1;
}

.badge-green {
  background: #dcfce7;
  color: #15803d;
}

.pill-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.68rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  line-height: 1;
}

.pill-success {
  background: #d1fae5;
  color: #047857;
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

@media (max-width: 1280px) {
  .mutation-summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .mutation-summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
