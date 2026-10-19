<script setup>
defineProps({
  stats: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <section class="stats-grid" aria-label="Statistik Pemeriksaan Rawat Jalan">
    <!-- Card 1: Menunggu Pemeriksaan -->
    <div class="card stat-card stat-waiting">
      <div class="stat-icon-wrapper icon-amber">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
      </div>
      <div class="stat-content">
        <span class="stat-label">MENUNGGU PEMERIKSAAN</span>
        <div class="stat-value text-amber">{{ stats.waiting }} <span class="stat-unit">pasien</span></div>
        <p class="stat-desc">Perlu segera dilayani &bull; <span class="stat-extra">Rata-rata: {{ stats.avgWaitTime }}</span></p>
      </div>
    </div>

    <!-- Card 2: Sedang Diperiksa -->
    <div class="card stat-card stat-active">
      <div class="stat-icon-wrapper icon-teal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
        </svg>
      </div>
      <div class="stat-content">
        <span class="stat-label">SEDANG DIPERIKSA</span>
        <div class="stat-value text-teal">{{ stats.inService }} <span class="stat-unit">ruang aktif</span></div>
        <p class="stat-desc">Di {{ stats.activePolyCount }} Poliklinik Spesialis &bull; <span class="stat-extra">{{ stats.activeDoctorsCount }} Dokter</span></p>
      </div>
    </div>

    <!-- Card 3: Pemeriksaan Selesai -->
    <div class="card stat-card stat-completed">
      <div class="stat-icon-wrapper icon-green">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
      </div>
      <div class="stat-content">
        <span class="stat-label">PEMERIKSAAN SELESAI</span>
        <div class="stat-value text-green">{{ stats.completed }} <span class="stat-unit">pasien</span></div>
        <p class="stat-desc">{{ stats.completedPercentage }} selesai hari ini</p>
      </div>
    </div>

    <!-- Card 4: Dibatalkan / No Show -->
    <div class="card stat-card stat-cancelled">
      <div class="stat-icon-wrapper icon-rose">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="15" y1="9" x2="9" y2="15"></line>
          <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
      </div>
      <div class="stat-content">
        <span class="stat-label">DIBATALKAN / NO SHOW</span>
        <div class="stat-value text-rose">{{ stats.cancelled }} <span class="stat-unit">berkas</span></div>
        <p class="stat-desc">Pasien batal / rujukan luar</p>
      </div>
    </div>
  </section>
</template>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
  margin-bottom: 0.5rem;
}

.stat-card {
  padding: 1.25rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
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

.stat-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon-wrapper svg {
  width: 22px;
  height: 22px;
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

.stat-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.stat-label {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--color-text-secondary, #64748b);
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.65rem;
  font-weight: 800;
  line-height: 1.2;
  color: var(--color-text-navy, #1e293b);
  margin-bottom: 0.25rem;
  display: flex;
  align-items: baseline;
  gap: 0.35rem;
}

.stat-unit {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-secondary, #64748b);
}

.stat-desc {
  margin: 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary, #64748b);
  line-height: 1.3;
}

.stat-extra {
  font-weight: 500;
  color: var(--color-text-primary, #334155);
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

@media (max-width: 1100px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .stat-card {
    padding: 1rem;
  }
}
</style>
