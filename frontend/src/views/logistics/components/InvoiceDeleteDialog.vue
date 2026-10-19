<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  invoice: {
    type: Object,
    default: null
  },
  isSubmitting: {
    type: Boolean,
    default: false
  }
});

defineEmits(['close', 'confirm']);
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen && invoice" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h3 class="text-danger">Konfirmasi Hapus Faktur</h3>
          <button
            type="button"
            class="btn-close"
            :disabled="isSubmitting"
            @click="$emit('close')"
            aria-label="Tutup"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <p>
            Apakah Anda yakin ingin menghapus faktur
            <strong>{{ invoice.invoiceNumber }}</strong> ({{ invoice.supplierName }})?
          </p>
          <p class="text-secondary delete-warning-text">
            Data ini hanya akan dihapus dari daftar lokal sementara. Tindakan ini tidak dapat dibatalkan.
          </p>
        </div>

        <div class="modal-footer">
          <button
            type="button"
            class="btn-cancel"
            :disabled="isSubmitting"
            @click="$emit('close')"
          >
            Batal
          </button>
          <button
            type="button"
            class="btn-danger"
            :disabled="isSubmitting"
            @click="$emit('confirm')"
          >
            {{ isSubmitting ? 'Menghapus...' : 'Ya, Hapus Faktur' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.text-danger {
  color: var(--color-danger, #d64545);
}

.delete-warning-text {
  font-size: 0.85rem;
  margin-top: 0.5rem;
  color: var(--color-text-secondary, #64748b);
}
</style>
