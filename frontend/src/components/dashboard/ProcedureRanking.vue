<script setup>
defineProps({
  data: {
    type: Array,
    required: true
  }
});

const isPositive = (changeStr) => {
  return changeStr.startsWith('+');
};
</script>

<template>
  <div class="procedure-ranking card">
    <div class="card-header">
      <div>
        <h3 class="card-title">10 Besar Tindakan Medis</h3>
        <p class="card-subtitle">Tindakan dan prosedur poliklinik terbanyak hari ini.</p>
      </div>
      <button class="btn-link">Lihat Semua</button>
    </div>
    
    <div class="ranking-list">
      <div v-for="item in data" :key="item.rank" class="rank-item">
        <div class="rank-badge">{{ item.rank }}</div>
        
        <div class="rank-info">
          <div class="procedure-name">{{ item.name }}</div>
          <div class="poly-name">{{ item.poly }}</div>
        </div>
        
        <div class="rank-stats">
          <span class="stat-count">{{ item.count }} Tindakan</span>
          <span class="stat-change" :class="isPositive(item.change) ? 'change-up' : 'change-down'">
            <svg v-if="isPositive(item.change)" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg>
            {{ item.change }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.procedure-ranking {
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
  background: var(--color-page-bg);
  color: var(--color-text-secondary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 700;
}

.rank-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.procedure-name {
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
  color: var(--color-primary);
}

.stat-change {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.7rem;
  font-weight: 700;
}

.change-up {
  color: #059669; /* green */
}

.change-down {
  color: var(--color-danger);
}
</style>
