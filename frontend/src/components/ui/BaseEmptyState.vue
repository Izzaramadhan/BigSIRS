<script setup>
defineProps({
  /** Komponen ikon dari lucide-vue-next, mis. `Inbox`. */
  icon: {
    type: [Object, Function],
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  tone: {
    type: String,
    default: 'neutral',
    validator: (value) => ['neutral', 'danger'].includes(value),
  },
})
</script>

<template>
  <div class="flex flex-col items-center px-6 py-12 text-center">
    <div
      class="mb-4 flex size-12 items-center justify-center rounded-full"
      :class="tone === 'danger' ? 'bg-danger-subtle text-danger' : 'bg-canvas text-fg-muted'"
    >
      <component :is="icon" class="size-6" aria-hidden="true" />
    </div>
    <h3 class="text-base font-semibold text-fg">{{ title }}</h3>
    <p v-if="description" class="mt-1 max-w-sm text-sm text-fg-muted">{{ description }}</p>
    <div v-if="$slots.action" class="mt-5">
      <slot name="action" />
    </div>
  </div>
</template>
