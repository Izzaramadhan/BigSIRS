<script setup>
defineProps({
  isActive: {
    type: Boolean,
    required: true
  }
});
</script>

<template>
  <span class="status-badge" :class="isActive ? 'status-active' : 'status-inactive'">
    {{ isActive ? 'Aktif' : 'Nonaktif' }}
  </span>
</template>

<style scoped>
.status-badge {
  display: inline-block;
  padding: 0.25rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-align: center;
}

.status-active {
  background-color: #dcfce7;
  color: #166534;
}

.status-inactive {
  background-color: #fee2e2;
  color: #991b1b;
}
</style>
