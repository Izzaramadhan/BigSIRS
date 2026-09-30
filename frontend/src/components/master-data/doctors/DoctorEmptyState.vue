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
    <div class="empty-state-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
        <circle cx="9" cy="7" r="4"></circle>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
      </svg>
    </div>
    
    <template v-if="isSearch">
      <h3>Data tidak ditemukan</h3>
      <p>Tidak ada dokter yang cocok dengan kriteria pencarian Anda. Silakan coba kata kunci atau filter lain.</p>
    </template>
    <template v-else>
      <h3>Belum ada data Dokter</h3>
      <p>Data dokter masih kosong. Silakan tambahkan data dokter pertama Anda.</p>
      <div class="empty-state-action">
        <slot name="action"></slot>
      </div>
    </template>
  </div>
</template>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  background: #ffffff;
  border-radius: 8px;
  border: 1px dashed var(--color-border-soft);
  text-align: center;
}

.empty-state-icon {
  width: 64px;
  height: 64px;
  background: var(--color-page-bg);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
  color: var(--color-text-secondary);
}

.empty-state-icon svg {
  width: 32px;
  height: 32px;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  color: var(--color-text-navy);
}

.empty-state p {
  margin: 0 0 1.5rem 0;
  color: var(--color-text-secondary);
  max-width: 400px;
  line-height: 1.5;
}

.empty-state-action {
  margin-top: 0.5rem;
}
</style>
