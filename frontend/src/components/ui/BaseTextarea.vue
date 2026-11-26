<script setup>
import { fieldControlClass } from './fieldControlClass'

const model = defineModel({ type: String, default: '' })

defineProps({
  invalid: {
    type: Boolean,
    default: false,
  },
  rows: {
    type: Number,
    default: 3,
  },
})
</script>

<template>
  <textarea
    v-model="model"
    :rows="rows"
    :aria-invalid="invalid || undefined"
    :class="[fieldControlClass({ invalid }), 'min-h-20 resize-y']"
  />
</template>
