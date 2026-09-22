<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: {
    type: Array,
    required: true
  }
});

const maxCount = computed(() => {
  if (!props.data || props.data.length === 0) return 100;
  return Math.max(...props.data.map(item => item.count)) * 1.1; // 10% headroom
});
</script>

<template>
  <div class="poly-distribution card">
    <div class="card-header">
      <div class="header-text">
        <h3 class="card-title">Distribusi Pasien per Poliklinik</h3>
        <p class="card-subtitle">5 poliklinik dengan beban kunjungan tertinggi hari ini.</p>
      </div>
      <button class="btn-link">
        Lihat Semua Poli
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
      </button>
    </div>
    
    <div class="distribution-list">
      <div v-for="(item, idx) in data" :key="idx" class="dist-item">
        <div class="dist-info">
          <span class="dist-name">{{ idx + 1 }}. {{ item.name }}</span>
          <span class="dist-stats">
            <span class="dist-count">{{ item.count }} Pasien</span>
            <span class="dist-pct">({{ item.percentage }}%)</span>
          </span>
        </div>
        <div class="progress-bg">
          <div 
            class="progress-bar" 
            :style="{ width: `${(item.count / maxCount) * 100}%` }"
            :class="{'progress-warning': (item.count / maxCount) > 0.85}"
          ></div>
        </div>
      </div>
    </div>
    
    <div class="card-footer">
      <span>Kapasitas Rata-rata Pelayanan Poli: 82%</span>
      <span>Target Selesai per Pasien: &lt; 20 mnt</span>
    </div>
  </div>
</template>

<style scoped>
.poly-distribution {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.card-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-navy);
  margin: 0 0 0.25rem 0;
}

.card-subtitle {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  margin: 0;
}

.btn-link {
  background: none;
  border: none;
  color: var(--color-primary);
  font-size: 0.75rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  cursor: pointer;
}

.btn-link:hover {
  text-decoration: underline;
}

.distribution-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  flex: 1;
}

.dist-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.dist-info {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
}

.dist-name {
  font-weight: 600;
  color: var(--color-text-navy);
}

.dist-stats {
  display: flex;
  gap: 0.5rem;
}

.dist-count {
  font-weight: 600;
  color: var(--color-primary);
}

.dist-pct {
  color: var(--color-text-secondary);
}

.progress-bg {
  height: 8px;
  background-color: var(--color-page-bg);
  border-radius: 4px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background-color: var(--color-primary);
  border-radius: 4px;
  transition: width 1s ease-out;
}

.progress-warning {
  background-color: var(--color-warning);
}

.card-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px dashed var(--color-border-soft);
  font-size: 0.7rem;
  color: var(--color-text-secondary);
}
</style>
