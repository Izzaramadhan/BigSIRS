<script setup>
defineProps({
  isActive: {
    type: Boolean,
    required: true,
  },
})
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="
      isActive ? 'bg-success-subtle text-success-strong' : 'bg-danger-subtle text-danger-strong'
    "
  >
    {{ isActive ? 'Aktif' : 'Nonaktif' }}
  </span>
</template>
