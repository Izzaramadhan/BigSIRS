<script setup>
defineProps({
  stats: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <section class="stats-grid" aria-label="Statistik Admisi Rawat Jalan">
    <!-- Card 1: Total Admisi Hari Ini -->
    <div class="card stat-card stat-total">
      <div class="stat-icon-wrapper icon-teal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
      </div>
      <div class="stat-content">
        <span class="stat-label">TOTAL ADMISI HARI INI</span>
        <div class="stat-value">{{ stats.total }}</div>
        <p class="stat-desc">Termasuk BPJS & Pasien Umum</p>
      </div>
    </div>

    <!-- Card 2: Menunggu Pelayanan -->
    <div class="card stat-card stat-waiting">
      <div class="stat-icon-wrapper icon-amber">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
      </div>
      <div class="stat-content">
        <span class="stat-label">MENUNGGU PELAYANAN</span>
        <div class="stat-value text-amber">{{ stats.waiting }}</div>
        <p class="stat-desc">Di ruang tunggu 14 poliklinik</p>
      </div>
    </div>

    <!-- Card 3: Sedang Dilayani -->
    <div class="card stat-card stat-active">
      <div class="stat-icon-wrapper icon-blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
        </svg>
      </div>
      <div class="stat-content">
        <span class="stat-label">SEDANG DILAYANI</span>
        <div class="stat-value text-blue">{{ stats.inService }}</div>
        <p class="stat-desc">Aktif di 8 poliklinik spesialis</p>
      </div>
    </div>

    <!-- Card 4: Pelayanan Selesai -->
    <div class="card stat-card stat-completed">
      <div class="stat-icon-wrapper icon-green">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
      </div>
      <div class="stat-content">
        <span class="stat-label">PELAYANAN SELESAI</span>
        <div class="stat-value text-green">{{ stats.completed }}</div>
        <p class="stat-desc">{{ stats.percentage }} dari target kuota harian</p>
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
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
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

.icon-teal {
  background: var(--color-primary-light);
  color: var(--color-primary);
}

.icon-amber {
  background: #fef3c7;
  color: #d97706;
}

.icon-blue {
  background: #e0f2fe;
  color: #0284c7;
}

.icon-green {
  background: #dcfce7;
  color: #16a34a;
}

.stat-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.stat-label {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--color-text-secondary);
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 800;
  line-height: 1.2;
  color: var(--color-text-navy);
  margin-bottom: 0.25rem;
}

.stat-desc {
  margin: 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  line-height: 1.3;
}

.text-amber {
  color: #d97706;
}

.text-blue {
  color: #0284c7;
}

.text-green {
  color: #15803d;
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
