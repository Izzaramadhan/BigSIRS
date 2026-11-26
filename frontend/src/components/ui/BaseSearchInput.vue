<script setup>
import { Search } from 'lucide-vue-next'
import { fieldControlClass } from './fieldControlClass'

// Root komponen ini <div>; atribut seperti `placeholder` harus diteruskan ke <input>.
defineOptions({ inheritAttrs: false })

const model = defineModel({ type: String, default: '' })

defineProps({
  label: {
    type: String,
    default: 'Cari',
  },
})
</script>

<template>
  <div class="relative w-full max-w-md">
    <Search
      class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-fg-muted"
      aria-hidden="true"
    />
    <input
      v-bind="$attrs"
      v-model="model"
      type="search"
      autocomplete="off"
      :aria-label="label"
      :class="fieldControlClass({ withIcon: true })"
    />
  </div>
</template>
