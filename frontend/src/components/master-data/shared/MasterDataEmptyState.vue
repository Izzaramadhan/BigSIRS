<script setup>
defineProps({
  isSearch: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Data tidak ditemukan'
  },
  message: {
    type: String,
    default: 'Belum ada data yang ditambahkan.'
  },
  searchMessage: {
    type: String,
    default: 'Tidak ada data yang cocok dengan kriteria pencarian.'
  }
});
</script>

<template>
  <div class="empty-state">
    <div class="empty-icon">
      <svg v-if="isSearch" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
        <line x1="12" y1="22.08" x2="12" y2="12"></line>
      </svg>
    </div>
    <h3 class="empty-title">{{ isSearch ? 'Pencarian Tidak Ditemukan' : title }}</h3>
    <p class="empty-message">{{ isSearch ? searchMessage : message }}</p>
    <div class="empty-actions" v-if="$slots.action">
      <slot name="action"></slot>
    </div>
  </div>
</template>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
  background: var(--color-card-bg);
  border-radius: 12px;
  border: 1px solid var(--color-border-soft);
}

.empty-icon {
  width: 64px;
  height: 64px;
  background: #f1f5f9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.25rem;
  color: #94a3b8;
}

.empty-icon svg {
  width: 32px;
  height: 32px;
}

.empty-title {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.empty-message {
  margin: 0 0 1.5rem 0;
  color: var(--color-text-secondary);
  max-width: 400px;
}

.empty-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}
</style>
