<script setup>
defineProps({
  error: {
    type: String,
    default: 'Terjadi kesalahan saat memuat data dashboard.'
  }
});

const emit = defineEmits(['retry']);
</script>

<template>
  <div class="error-state card">
    <div class="error-content">
      <div class="error-icon-wrapper">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
      </div>
      <h3 class="error-title">Gagal Memuat Dashboard</h3>
      <p class="error-desc">{{ error }}</p>
      
      <button class="btn btn-primary" @click="emit('retry')">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
        Coba Lagi
      </button>
    </div>
  </div>
</template>

<style scoped>
.error-state {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  min-height: 400px;
}

.error-content {
  text-align: center;
  max-width: 500px;
}

.error-icon-wrapper {
  color: var(--color-danger);
  margin-bottom: 1.5rem;
}

.error-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-navy);
  margin: 0 0 0.5rem 0;
}

.error-desc {
  font-size: 0.9rem;
  color: var(--color-text-secondary);
  line-height: 1.5;
  margin: 0 0 2rem 0;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: var(--color-primary);
  color: #fff;
}

.btn-primary:hover {
  background: var(--color-primary-dark);
}
</style>
