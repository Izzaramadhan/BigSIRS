<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    default: 'Konfirmasi Hapus'
  },
  itemName: {
    type: String,
    default: ''
  },
  isSubmitting: {
    type: Boolean,
    default: false
  },
  warningMessage: {
    type: String,
    default: 'Tindakan ini tidak dapat dibatalkan.'
  }
});

defineEmits(['close', 'confirm']);
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h3 class="text-danger">{{ title }}</h3>
          <button class="btn-close" @click="$emit('close')" :disabled="isSubmitting">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <p>Apakah Anda yakin ingin menghapus <strong>{{ itemName }}</strong>?</p>
          <p class="text-secondary" style="font-size: 0.85rem; margin-top: 0.5rem;">{{ warningMessage }}</p>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="$emit('close')" :disabled="isSubmitting">Batal</button>
          <button class="btn-danger" @click="$emit('confirm')" :disabled="isSubmitting">
            {{ isSubmitting ? 'Menghapus...' : 'Ya, Hapus' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.text-danger {
  color: var(--color-danger);
}
</style>
