<script setup>
defineProps({
  isSearch: {
    type: Boolean,
    default: false
  }
});
</script>

<template>
  <div class="empty-state">
    <div class="empty-state__icon">
      <svg v-if="isSearch" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
      </svg>
    </div>
    
    <h3 class="empty-state__title">
      {{ isSearch ? 'Poliklinik tidak ditemukan' : 'Belum ada data Poliklinik' }}
    </h3>
    
    <p class="empty-state__desc">
      {{ isSearch ? 'Coba ubah kata kunci atau filter.' : 'Tambahkan Poliklinik pertama untuk memulai.' }}
    </p>
    
    <div v-if="!isSearch" class="empty-state__action">
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
}

.empty-state__icon {
  width: 64px;
  height: 64px;
  background: var(--color-page-bg);
  color: #94a3b8;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
}

.empty-state__icon svg {
  width: 32px;
  height: 32px;
}

.empty-state__title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin: 0 0 0.5rem 0;
}

.empty-state__desc {
  font-size: 0.9rem;
  color: var(--color-text-secondary);
  margin: 0 0 1.5rem 0;
  max-width: 300px;
}
</style>
