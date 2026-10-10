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
      <h1 class="page-title">{{ title }}</h1>
      <p class="page-subtitle">{{ subtitle }}</p>
    </div>
    <div class="header-actions">
      <slot name="actions"></slot>
    </div>
  </div>
</template>
