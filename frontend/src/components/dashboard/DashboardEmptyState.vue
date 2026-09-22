<script setup>
</script>

<template>
  <div class="empty-state card">
    <div class="empty-content">
      <div class="empty-icon-wrapper">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
      </div>
      <h3 class="empty-title">Data dashboard belum terhubung</h3>
      <p class="empty-desc">
        Fitur ini sedang dalam tahap pengembangan. API dashboard untuk production belum tersedia saat ini.
      </p>
      <div class="empty-actions">
        <p class="dev-hint">💡 Aktifkan <code>VITE_USE_DASHBOARD_MOCK=true</code> di <code>.env.local</code> untuk melihat simulasi UI.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  min-height: 400px;
}

.empty-content {
  text-align: center;
  max-width: 500px;
}

.empty-icon-wrapper {
  color: var(--color-text-secondary);
  margin-bottom: 1.5rem;
  opacity: 0.5;
}

.empty-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-navy);
  margin: 0 0 0.5rem 0;
}

.empty-desc {
  font-size: 0.9rem;
  color: var(--color-text-secondary);
  line-height: 1.5;
  margin: 0 0 2rem 0;
}

.empty-actions {
  background: var(--color-page-bg);
  padding: 1rem;
  border-radius: 8px;
  border: 1px dashed var(--color-border-soft);
}

.dev-hint {
  font-size: 0.8rem;
  color: var(--color-text-navy);
  margin: 0;
}

code {
  background: #ffffff;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  color: var(--color-primary);
  font-family: monospace;
}
</style>
