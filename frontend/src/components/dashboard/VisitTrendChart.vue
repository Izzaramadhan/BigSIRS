<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: {
    type: Object,
    required: true
  }
});

// Calculate viewBox based on data length
const width = 800;
const height = 250;
const paddingX = 40;
const paddingY = 40;
const chartWidth = width - (paddingX * 2);
const chartHeight = height - (paddingY * 2);

const maxVal = computed(() => {
  const allVals = [...props.data.terdaftar, ...props.data.selesai];
  return Math.max(...allVals) * 1.1; // add 10% headroom
});

const getCoordinates = (values) => {
  const stepX = chartWidth / (values.length - 1);
  return values.map((val, index) => {
    const x = paddingX + (index * stepX);
    const y = paddingY + chartHeight - ((val / maxVal.value) * chartHeight);
    return { x, y, val };
  });
};

const terdaftarCoords = computed(() => getCoordinates(props.data.terdaftar));
const selesaiCoords = computed(() => getCoordinates(props.data.selesai));

const createPath = (coords) => {
  if (coords.length === 0) return '';
  let path = `M ${coords[0].x} ${coords[0].y}`;
  for (let i = 1; i < coords.length; i++) {
    // Basic curve smoothing could be added here, but straight lines are fine for now
    path += ` L ${coords[i].x} ${coords[i].y}`;
  }
  return path;
};

const createArea = (coords) => {
  if (coords.length === 0) return '';
  let path = createPath(coords);
  path += ` L ${coords[coords.length - 1].x} ${paddingY + chartHeight}`;
  path += ` L ${coords[0].x} ${paddingY + chartHeight} Z`;
  return path;
};

const terdaftarPath = computed(() => createPath(terdaftarCoords.value));
const selesaiPath = computed(() => createPath(selesaiCoords.value));
const terdaftarArea = computed(() => createArea(terdaftarCoords.value));

const yLabels = computed(() => {
  const steps = 4;
  return Array.from({ length: steps + 1 }, (_, i) => {
    const val = (maxVal.value / steps) * i;
    return Math.round(val);
  });
});
</script>

<template>
  <div class="visit-trend card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Tren Kunjungan Rawat Jalan</h3>
        <p class="card-subtitle">Perbandingan volume pendaftaran vs pasien selesai tertangani 7 hari terakhir.</p>
      </div>
      <div class="chart-legend">
        <div class="legend-item">
          <span class="legend-color color-terdaftar"></span>
          <span>Terdaftar</span>
        </div>
        <div class="legend-item">
          <span class="legend-color color-selesai"></span>
          <span>Selesai</span>
        </div>
      </div>
    </div>
    
    <div class="chart-container">
      <svg :viewBox="`0 0 ${width} ${height}`" class="trend-svg" aria-label="Grafik Tren Kunjungan">
        <!-- Grid lines -->
        <g class="grid-lines">
          <line 
            v-for="(label, idx) in yLabels" 
            :key="idx"
            :x1="paddingX" 
            :y1="paddingY + chartHeight - (idx * (chartHeight / (yLabels.length - 1)))" 
            :x2="width - paddingX" 
            :y2="paddingY + chartHeight - (idx * (chartHeight / (yLabels.length - 1)))"
          />
        </g>
        
        <!-- Y-axis labels -->
        <g class="y-labels">
          <text 
            v-for="(label, idx) in yLabels" 
            :key="idx"
            x="30" 
            :y="paddingY + chartHeight - (idx * (chartHeight / (yLabels.length - 1))) + 4"
            text-anchor="end"
          >{{ label }}</text>
        </g>

        <!-- Area for Terdaftar -->
        <path :d="terdaftarArea" class="area-terdaftar" />
        
        <!-- Lines -->
        <path :d="selesaiPath" class="line-selesai" />
        <path :d="terdaftarPath" class="line-terdaftar" />
        
        <!-- Points for Terdaftar -->
        <circle 
          v-for="(coord, idx) in terdaftarCoords" 
          :key="`t-${idx}`"
          :cx="coord.x" 
          :cy="coord.y" 
          r="4" 
          class="point-terdaftar"
        />

        <!-- X-axis labels -->
        <g class="x-labels">
          <text 
            v-for="(label, idx) in data.labels" 
            :key="idx"
            :x="terdaftarCoords[idx].x" 
            :y="height - 10"
            text-anchor="middle"
          >{{ label }}</text>
        </g>
      </svg>
    </div>
  </div>
</template>

<style scoped>
.visit-trend {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
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

.chart-legend {
  display: flex;
  gap: 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.legend-color {
  width: 10px;
  height: 10px;
  border-radius: 2px;
}

.color-terdaftar {
  background-color: var(--color-primary);
}

.color-selesai {
  background-color: #64748b;
}

.chart-container {
  flex: 1;
  width: 100%;
  min-height: 200px;
}

.trend-svg {
  width: 100%;
  height: 100%;
}

.grid-lines line {
  stroke: var(--color-border-soft);
  stroke-width: 1;
  stroke-dasharray: 4 4;
}

.y-labels text, .x-labels text {
  font-size: 10px;
  fill: var(--color-text-secondary);
}

.area-terdaftar {
  fill: var(--color-primary-light);
  opacity: 0.4;
}

.line-terdaftar {
  fill: none;
  stroke: var(--color-primary);
  stroke-width: 3;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.line-selesai {
  fill: none;
  stroke: #64748b;
  stroke-width: 2;
  stroke-dasharray: 4 4;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.point-terdaftar {
  fill: #ffffff;
  stroke: var(--color-primary);
  stroke-width: 2;
}

@media (max-width: 640px) {
  .x-labels text {
    font-size: 8px;
  }
}
</style>
