<script setup>
// Audit Trail Information Banner
</script>

<template>
  <div class="audit-banner">
    <div class="banner-icon-box">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="16" x2="12" y2="12"></line>
        <line x1="12" y1="8" x2="12.01" y2="8"></line>
      </svg>
    </div>
    <div class="banner-content">
      <p class="banner-text">
        <strong>Audit Trail:</strong> Mutasi yang telah divalidasi tidak dapat diubah atau dihapus. Perpindahan stok akan tercatat pada kartu stok.
      </p>
    </div>
  </div>
</template>

<style scoped>
.audit-banner {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.85rem 1.25rem;
  background: #f0fdfa;
  border: 1px solid #ccfbf1;
  border-radius: 10px;
}

.banner-icon-box {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary, #087f78);
  flex-shrink: 0;
}

.banner-icon-box svg {
  width: 18px;
  height: 18px;
}

.banner-content {
  flex: 1;
}

.banner-text {
  margin: 0;
  font-size: 0.84rem;
  color: var(--color-text-navy, #17243d);
  line-height: 1.4;
}

.banner-text strong {
  color: var(--color-primary, #087f78);
}
</style>
