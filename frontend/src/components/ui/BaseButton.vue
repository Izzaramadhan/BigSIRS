<script setup>
import { computed } from 'vue'
import { LoaderCircle } from 'lucide-vue-next'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) =>
      ['primary', 'secondary', 'danger', 'ghost', 'ghost-danger'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'icon'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

/*
 * Butuh tampilan baru? Tambahkan VARIANT di sini — JANGAN menimpa lewat
 * atribut `class` dari luar. Dua utility yang bertabrakan (mis. `hover:bg-canvas`
 * dan `hover:bg-danger-subtle`) tidak bisa ditebak mana yang menang.
 */
// Setiap variant menetapkan warna border DAN background sendiri: komponen tidak
// boleh bergantung pada reset Preflight (gaya bawaan <button> dari browser).
const VARIANTS = {
  primary: 'border-transparent bg-primary text-white hover:bg-primary-strong',
  secondary: 'border-line-strong bg-surface text-fg hover:border-primary hover:text-primary',
  danger: 'border-transparent bg-danger text-white hover:bg-danger-strong',
  ghost: 'border-transparent bg-transparent text-fg-muted hover:bg-canvas hover:text-fg',
  'ghost-danger':
    'border-transparent bg-transparent text-fg-muted hover:bg-danger-subtle hover:text-danger-strong',
}

const SIZES = {
  sm: 'h-8 gap-1.5 px-3 text-xs',
  md: 'h-10 gap-2 px-4 text-sm',
  icon: 'size-9 p-0',
}

const classes = computed(() => [
  'inline-flex shrink-0 cursor-pointer items-center justify-center rounded-lg border font-sans font-medium whitespace-nowrap transition-colors',
  'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
  'disabled:pointer-events-none disabled:opacity-60',
  VARIANTS[props.variant],
  SIZES[props.size],
])
</script>

<template>
  <button
    :type="type"
    :class="classes"
    :disabled="disabled || loading"
    :aria-busy="loading || undefined"
  >
    <LoaderCircle v-if="loading" class="size-4 animate-spin" aria-hidden="true" />
    <slot v-else name="icon" />
    <slot />
  </button>
</template>
