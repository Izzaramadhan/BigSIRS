<script setup>
import { formatRupiah } from '@/utils/billingFormatters';

defineProps({
  stats: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <section class="billing-stats-grid" aria-label="Statistik Billing Rawat Jalan">
    <!-- Card 1: Menunggu Verifikasi -->
    <div class="card stat-card stat-waiting">
      <div class="stat-header">
        <div class="stat-icon-wrapper icon-amber">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
        </div>
        <span class="status-pill pill-amber">Perlu Review</span>
      </div>
      <div class="stat-content">
        <span class="stat-label">Menunggu Verifikasi</span>
        <div class="stat-value text-amber">{{ stats.waiting }} <span class="stat-unit">Pasien</span></div>
        <p class="stat-desc">Klaim BPJS & Dokumen RME</p>
        <div class="stat-footer-info">
          <span>Klaim Inhealth: <strong>{{ stats.inhealthClaims }}</strong></span>
          <span class="dot-sep">&bull;</span>
          <span>BPJS: <strong>{{ stats.bpjsClaims }}</strong></span>
        </div>
      </div>
    </div>

    <!-- Card 2: Siap Dibayar -->
    <div class="card stat-card stat-ready">
      <div class="stat-header">
        <div class="stat-icon-wrapper icon-teal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
            <line x1="12" y1="8" x2="12" y2="16"></line>
            <line x1="8" y1="12" x2="16" y2="12"></line>
          </svg>
        </div>
        <span class="status-pill pill-teal">Siap Kasir</span>
      </div>
      <div class="stat-content">
        <span class="stat-label">Siap Dibayar</span>
        <div class="stat-value text-teal">{{ stats.ready }} <span class="stat-unit">Antrean</span></div>
        <p class="stat-desc">Telah diverifikasi petugas</p>
        <div class="stat-footer-info">
          <span>Prioritas Lansia: <strong>{{ stats.elderlyReady }}</strong></span>
          <span class="dot-sep">&bull;</span>
          <span>Tunai / EDC: <strong>{{ stats.cashEdcReady }}</strong></span>
        </div>
      </div>
    </div>

    <!-- Card 3: Lunas Hari Ini -->
    <div class="card stat-card stat-paid">
      <div class="stat-header">
        <div class="stat-icon-wrapper icon-green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <span class="status-pill pill-green">Selesai</span>
      </div>
      <div class="stat-content">
        <span class="stat-label">Lunas Hari Ini</span>
        <div class="stat-value text-green">{{ stats.paid }} <span class="stat-unit">Kuitansi</span></div>
        <p class="stat-desc">Pelunasan sukses diverifikasi</p>
        <div class="stat-footer-info">
          <span>Rasio Selesai: <strong>{{ stats.completionRatio }}</strong></span>
        </div>
      </div>
    </div>

    <!-- Card 4: Belum Lunas -->
    <div class="card stat-card stat-pending">
      <div class="stat-header">
        <div class="stat-icon-wrapper icon-rose">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <span class="status-pill pill-rose">Pending Piutang</span>
      </div>
      <div class="stat-content">
        <span class="stat-label">Belum Lunas</span>
        <div class="stat-value text-rose">{{ stats.pending }} <span class="stat-unit">Berkas</span></div>
        <p class="stat-desc">Ekses asuransi & resume ditahan</p>
        <div class="stat-footer-info">
          <span>Total Ekses: <strong>{{ formatRupiah(stats.totalExcess) }}</strong></span>
        </div>
      </div>
    </div>

    <!-- Card 5: Pendapatan Hari Ini -->
    <div class="card stat-card stat-income">
      <div class="stat-header">
        <div class="stat-icon-wrapper icon-indigo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="1" x2="12" y2="23"></line>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
          </svg>
        </div>
        <span class="status-pill pill-growth">{{ stats.growth }}</span>
      </div>
      <div class="stat-content">
        <span class="stat-label">Pendapatan Hari Ini</span>
        <div class="stat-value text-indigo font-currency">{{ formatRupiah(stats.todayIncome) }}</div>
        <p class="stat-desc">Tunai, QRIS, EDC & Bridging</p>
        <div class="stat-footer-info">
          <span>VS Kemarin: <strong>{{ formatRupiah(stats.yesterdayIncome) }}</strong></span>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.billing-stats-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.stat-card {
  padding: 1.15rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 155px;
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.stat-icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon-wrapper svg {
  width: 20px;
  height: 20px;
}

.icon-amber {
  background: #fef3c7;
  color: #d97706;
}

.icon-teal {
  background: var(--color-primary-light, #e6f6f5);
  color: var(--color-primary, #087f78);
}

.icon-green {
  background: #dcfce7;
  color: #16a34a;
}

.icon-rose {
  background: #ffe4e6;
  color: #e11d48;
}

.icon-indigo {
  background: #e0e7ff;
  color: #4f46e5;
}

.status-pill {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
  border-radius: 20px;
  letter-spacing: 0.02em;
}

.pill-amber {
  background: #fef3c7;
  color: #b45309;
}

.pill-teal {
  background: #e0f2fe;
  color: #0369a1;
}

.pill-green {
  background: #dcfce7;
  color: #15803d;
}

.pill-rose {
  background: #fee2e2;
  color: #b91c1c;
}

.pill-growth {
  background: #dcfce7;
  color: #15803d;
  font-family: monospace;
}

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  color: var(--color-text-secondary, #64748b);
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1.2;
  color: var(--color-text-navy, #1e293b);
  margin-bottom: 0.25rem;
  display: flex;
  align-items: baseline;
  gap: 0.35rem;
}

.stat-value.font-currency {
  font-size: 1.35rem;
  letter-spacing: -0.02em;
}

.stat-unit {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-secondary, #64748b);
}

.stat-desc {
  margin: 0;
  font-size: 0.775rem;
  color: var(--color-text-secondary, #64748b);
  line-height: 1.3;
}

.stat-footer-info {
  margin-top: 0.4rem;
  padding-top: 0.35rem;
  border-top: 1px dashed #e2e8f0;
  font-size: 0.725rem;
  color: #475569;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-wrap: wrap;
}

.dot-sep {
  color: #cbd5e1;
}

.text-amber {
  color: #d97706;
}

.text-teal {
  color: var(--color-primary, #087f78);
}

.text-green {
  color: #15803d;
}

.text-rose {
  color: #e11d48;
}

.text-indigo {
  color: #4338ca;
}

@media (max-width: 1300px) {
  .billing-stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 840px) {
  .billing-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .billing-stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
