<script setup>
defineProps({
  items: {
    type: Array,
    required: true
  }
});

const getThemeClass = (type) => {
  switch(type) {
    case 'info': return 'theme-info';
    case 'warning': return 'theme-warning';
    case 'critical': return 'theme-critical';
    default: return 'theme-info';
  }
};
</script>

<template>
  <div class="attention-panel card">
    <div class="card-header">
      <div class="header-text">
        <div class="title-row">
          <svg class="attention-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <h3 class="card-title">Perlu Perhatian</h3>
        </div>
        <p class="card-subtitle">Kendala operasional rawat jalan membutuhkan tindakan segera.</p>
      </div>
      <div class="badge-count">{{ items.length }} Kasus</div>
    </div>
    
    <div class="attention-list">
      <div 
        v-for="item in items" 
        :key="item.id" 
        class="attention-item"
        :class="getThemeClass(item.type)"
      >
        <div class="item-header">
          <svg v-if="item.type === 'info'" class="item-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          <svg v-else-if="item.type === 'critical'" class="item-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          <svg v-else class="item-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          <h4 class="item-title">{{ item.title }}</h4>
        </div>
        <p class="item-desc">{{ item.description }}</p>
        <div class="item-footer">
          <span class="item-time">{{ item.time }}</span>
        </div>
      </div>
    </div>
    
    <div class="card-footer">
      <button class="btn-link">Lihat Seluruh Notifikasi Rawat Jalan &rarr;</button>
    </div>
  </div>
</template>

<style scoped>
.attention-panel {
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

.title-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.attention-icon {
  color: var(--color-danger);
}

.card-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-navy);
  margin: 0;
}

.card-subtitle {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  margin: 0;
}

.badge-count {
  background: var(--color-danger-bg);
  color: var(--color-danger);
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.attention-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  flex: 1;
}

.attention-item {
  padding: 1rem;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  border-left: 3px solid transparent;
}

.item-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.item-title {
  font-size: 0.85rem;
  font-weight: 700;
  margin: 0;
}

.item-desc {
  font-size: 0.75rem;
  margin: 0;
  line-height: 1.4;
}

.item-time {
  font-size: 0.7rem;
  font-weight: 600;
}

/* Theme Info (Teal/Blue) */
.theme-info {
  background: #f0f9ff;
  border-left-color: #0ea5e9;
}
.theme-info .item-title, .theme-info .item-icon { color: #0369a1; }
.theme-info .item-desc { color: #0c4a6e; }
.theme-info .item-time { color: #0ea5e9; }

/* Theme Critical (Red) */
.theme-critical {
  background: var(--color-danger-bg);
  border-left-color: var(--color-danger);
}
.theme-critical .item-title, .theme-critical .item-icon { color: #b91c1c; }
.theme-critical .item-desc { color: #7f1d1d; }
.theme-critical .item-time { color: var(--color-danger); }

/* Theme Warning (Yellow/Orange) */
.theme-warning {
  background: #fefce8;
  border-left-color: #eab308;
}
.theme-warning .item-title, .theme-warning .item-icon { color: #a16207; }
.theme-warning .item-desc { color: #713f12; }
.theme-warning .item-time { color: #eab308; }

.card-footer {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px dashed var(--color-border-soft);
}

.btn-link {
  background: none;
  border: none;
  color: var(--color-primary);
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}

.btn-link:hover {
  text-decoration: underline;
}
</style>
