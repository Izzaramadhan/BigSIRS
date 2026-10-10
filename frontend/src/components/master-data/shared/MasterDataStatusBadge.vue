<script setup>
defineProps({
  isActive: {
    type: Boolean,
    required: true
  }
});
</script>

<template>
  <span 
    class="status-badge" 
    :class="isActive ? 'badge-active' : 'badge-inactive'"
  >
    {{ isActive ? 'Aktif' : 'Nonaktif' }}
  </span>
</template>

<style scoped>
.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.badge-active {
  background-color: #dcfce7;
  color: #166534;
}

.badge-inactive {
  background-color: #f3f4f6;
  color: #4b5563;
}
</style>
