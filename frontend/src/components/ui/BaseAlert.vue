<script setup>
import { computed } from 'vue'
import { CircleAlert, Info, TriangleAlert } from 'lucide-vue-next'

const props = defineProps({
  tone: {
    type: String,
    default: 'info',
    validator: (value) => ['info', 'warning', 'danger'].includes(value),
  },
})

const TONES = {
  info: { icon: Info, class: 'border-primary/20 bg-primary-subtle text-primary-strong' },
  warning: {
    icon: TriangleAlert,
    class: 'border-warning/30 bg-warning-subtle text-warning-strong',
  },
  danger: { icon: CircleAlert, class: 'border-danger/20 bg-danger-subtle text-danger-strong' },
}

const current = computed(() => TONES[props.tone])
</script>

<template>
  <div
    :role="tone === 'danger' ? 'alert' : 'status'"
    class="flex items-center gap-3 rounded-lg border px-3.5 py-3 text-sm"
    :class="current.class"
  >
    <component :is="current.icon" class="size-4 shrink-0" aria-hidden="true" />
    <div class="flex-1"><slot /></div>
    <slot name="action" />
  </div>
</template>
