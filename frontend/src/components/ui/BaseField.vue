<script setup>
import { computed, useId } from 'vue'

/*
 * Label + kontrol + pesan error/hint, dengan id & aria terhubung otomatis.
 *
 *   <BaseField v-slot="field" label="Nama Bed" required :error="errors.name">
 *     <BaseInput v-bind="field" v-model="form.name" />
 *   </BaseField>
 *
 * `field` berisi { id, invalid, 'aria-describedby' }.
 */
const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  id: {
    type: String,
    default: '',
  },
})

const generatedId = useId()
const controlId = computed(() => props.id || generatedId)
const messageId = computed(() => `${controlId.value}-message`)
const message = computed(() => props.error || props.hint)

// Diteruskan sebagai SATU objek: prop slot bernama (`:aria-describedby`) akan
// di-camelCase oleh Vue menjadi `ariaDescribedby` dan tidak sampai ke <input>.
const controlAttrs = computed(() => ({
  id: controlId.value,
  invalid: Boolean(props.error),
  'aria-describedby': message.value ? messageId.value : undefined,
}))
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label :for="controlId" class="text-sm font-medium text-fg">
      {{ label }}
      <span v-if="required" class="text-danger" aria-hidden="true">*</span>
    </label>

    <slot v-bind="controlAttrs" />

    <p
      v-if="message"
      :id="messageId"
      class="text-xs"
      :class="error ? 'text-danger-strong' : 'text-fg-muted'"
    >
      {{ message }}
    </p>
  </div>
</template>
