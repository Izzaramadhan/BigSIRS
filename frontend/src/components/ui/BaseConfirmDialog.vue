<script setup>
import BaseButton from './BaseButton.vue'
import BaseModal from './BaseModal.vue'

defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  message: {
    type: String,
    required: true,
  },
  confirmText: {
    type: String,
    default: 'Ya, Lanjutkan',
  },
  /** `danger` untuk aksi destruktif (hapus). */
  tone: {
    type: String,
    default: 'danger',
    validator: (value) => ['primary', 'danger'].includes(value),
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
  <BaseModal :open="open" :title="title" size="sm" :persistent="loading" @close="emit('close')">
    <p class="text-sm text-fg">{{ message }}</p>
    <p class="mt-2 text-xs text-fg-muted">Tindakan ini tidak dapat dibatalkan.</p>

    <template #footer>
      <BaseButton variant="secondary" :disabled="loading" @click="emit('close')">Batal</BaseButton>
      <BaseButton :variant="tone" :loading="loading" @click="emit('confirm')">
        {{ confirmText }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
