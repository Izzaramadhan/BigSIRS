<script setup>
defineProps({
  data: {
    type: Array,
    required: true
  }
});
</script>

<template>
  <div class="disease-ranking card">
    <div class="card-header">
      <div>
        <h3 class="card-title">10 Besar Penyakit Rawat Jalan</h3>
        <p class="card-subtitle">Diagnosis tersering berdasarkan kode standar ICD-10.</p>
      </div>
      <button class="btn-link">Lihat Semua</button>
    </div>
    
    <div class="ranking-list">
      <div v-for="item in data" :key="item.icd" class="rank-item">
        <div class="rank-badge">#{{ item.rank }}</div>
        
        <div class="rank-info">
          <div class="rank-name-row">
            <span class="icd-code">{{ item.icd }}</span>
            <span class="disease-name">{{ item.name }}</span>
          </div>
          <div class="poly-name">{{ item.poly }}</div>
        </div>
        
        <div class="rank-stats">
          <span class="stat-count">{{ item.count }} Kasus</span>
          <span class="stat-pct">{{ item.percentage }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.disease-ranking {
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
  cursor: pointer;
}

.btn-link:hover {
  text-decoration: underline;
}

.ranking-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.rank-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--color-page-bg);
}

.rank-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.rank-badge {
  width: 32px;
  height: 32px;
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
}

.rank-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.rank-name-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.icd-code {
  font-size: 0.7rem;
  font-weight: 700;
  color: #fff;
  background: var(--color-primary);
  padding: 0.1rem 0.3rem;
  border-radius: 4px;
}

.disease-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.poly-name {
  font-size: 0.7rem;
  color: var(--color-text-secondary);
}

.rank-stats {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.1rem;
}

.stat-count {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.stat-pct {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--color-primary);
}
</style>
