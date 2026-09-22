<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: {
    type: Array,
    required: true
  }
});

const total = computed(() => props.data.reduce((acc, curr) => acc + curr.count, 0));

// Conic gradient colors matching the design
const colors = ['var(--color-primary)', '#3b82f6', '#0ea5e9', '#64748b'];

const conicGradient = computed(() => {
  let currentPercentage = 0;
  const gradientStops = props.data.map((item, idx) => {
    const start = currentPercentage;
    const end = start + item.percentage;
    currentPercentage = end;
    const color = colors[idx % colors.length];
    return `${color} ${start}% ${end}%`;
  });
  return `conic-gradient(${gradientStops.join(', ')})`;
});
</script>

<template>
  <div class="guarantor-composition card">
    <div class="card-header">
      <h3 class="card-title">Komposisi Penjamin</h3>
      <p class="card-subtitle">Distribusi metode pembiayaan rawat jalan hari ini.</p>
    </div>
    
    <div class="chart-body">
      <div class="donut-wrapper">
        <div class="donut-chart" :style="{ background: conicGradient }">
          <div class="donut-hole">
            <span class="total-val">{{ total }}</span>
            <span class="total-label">Total Pasien</span>
          </div>
        </div>
      </div>
      
      <div class="legend-list">
        <div v-for="(item, idx) in data" :key="idx" class="legend-row">
          <div class="legend-name">
            <span class="color-dot" :style="{ backgroundColor: colors[idx % colors.length] }"></span>
            {{ item.name }}
          </div>
          <div class="legend-stats">
            <span class="stat-count">{{ item.count }} Pasien</span>
            <span class="stat-pct">({{ item.percentage }}%)</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.guarantor-composition {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.card-header {
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

.chart-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.donut-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 2rem;
}

.donut-chart {
  width: 180px;
  height: 180px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.donut-hole {
  width: 130px;
  height: 130px;
  background-color: var(--color-card-bg);
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
}

.total-val {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-navy);
  line-height: 1;
}

.total-label {
  font-size: 0.7rem;
  color: var(--color-text-secondary);
  margin-top: 0.25rem;
}

.legend-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.legend-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
}

.legend-name {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--color-text-navy);
  font-weight: 500;
}

.color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.legend-stats {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.stat-count {
  font-weight: 600;
  color: var(--color-primary);
}

.stat-pct {
  color: var(--color-text-secondary);
  width: 45px;
  text-align: right;
}
</style>
