<script setup>
import { fieldControlClass } from './fieldControlClass'

const model = defineModel({ type: [String, Number], default: '' })

defineProps({
  invalid: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <!-- autocomplete="off" sebagai default, bisa ditimpa lewat atribut dari parent -->
  <input
    v-model="model"
    type="text"
    autocomplete="off"
    :aria-invalid="invalid || undefined"
    :class="fieldControlClass({ invalid })"
  />
</template>
