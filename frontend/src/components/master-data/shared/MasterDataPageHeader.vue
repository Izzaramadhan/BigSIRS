<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    required: true
  },
  breadcrumbs: {
    type: Array,
    default: () => [] // Array of { label: '', active: false }
  },
  badge: {
    type: String,
    default: ''
  }
});
</script>

<template>
  <div class="page-header">
    <div class="header-content">
      <div class="breadcrumbs" v-if="breadcrumbs.length > 0">
        <template v-for="(item, index) in breadcrumbs" :key="index">
          <span :class="{ 'current': item.active }">{{ item.label }}</span>
          <span v-if="index < breadcrumbs.length - 1" class="separator">/</span>
        </template>
      </div>
      <div class="title-row">
        <h1 class="page-title">{{ title }}</h1>
        <slot name="badge">
          <span v-if="badge" class="header-badge">{{ badge }}</span>
        </slot>
      </div>
      <p class="page-subtitle">{{ subtitle }}</p>
    </div>
    <div class="header-actions">
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<style scoped>
.title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 0.25rem;
}

.title-row .page-title {
  margin-bottom: 0;
}

.header-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.65rem;
  font-size: 0.775rem;
  font-weight: 700;
  color: var(--color-primary, #087f78);
  background: var(--color-primary-light, #e6f6f5);
  border: 1px solid rgba(8, 127, 120, 0.2);
  border-radius: 6px;
  letter-spacing: 0.02em;
}
</style>
