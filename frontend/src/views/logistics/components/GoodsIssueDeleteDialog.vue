<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  issue: {
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
    <div v-if="isOpen && issue" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h3 class="text-danger">Konfirmasi Hapus Draft Pengeluaran</h3>
          <button
            type="button"
            class="btn-close"
            :disabled="isSubmitting"
            aria-label="Tutup"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <p>
            Apakah Anda yakin ingin membatalkan/menghapus draft pengeluaran
            <strong>{{ issue.issueNumber }}</strong> ({{ issue.purpose }})?
          </p>
          <p class="text-secondary delete-warning-text">
            Data draft ini akan dihapus dari daftar pengeluaran barang. Tindakan ini tidak dapat dibatalkan.
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
            {{ isSubmitting ? 'Menghapus...' : 'Ya, Hapus Draft' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.modal-content {
  background: #ffffff;
  border-radius: 14px;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: modalEnter 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  overflow: hidden;
}

@keyframes modalEnter {
  from {
    opacity: 0;
    transform: scale(0.97) translateY(8px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.modal-header h3 {
  font-size: 1.05rem;
  font-weight: 700;
  margin: 0;
}

.btn-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #334155;
}

.btn-close svg {
  width: 18px;
  height: 18px;
}

.modal-body {
  padding: 1.25rem 1.5rem;
  font-size: 0.9rem;
  color: var(--color-text-navy, #17243d);
}

.text-danger {
  color: #dc2626;
}

.delete-warning-text {
  font-size: 0.82rem;
  margin-top: 0.5rem;
  color: var(--color-text-secondary, #64748b);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.65rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
  background: #f8fafc;
}

.btn-cancel {
  padding: 0.5rem 1rem;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 6px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  color: var(--color-text-navy, #17243d);
  cursor: pointer;
}

.btn-cancel:hover:not(:disabled) {
  background: #f1f5f9;
}

.btn-danger {
  padding: 0.5rem 1.1rem;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 6px;
  background: #dc2626;
  border: 1px solid #dc2626;
  color: #ffffff;
  cursor: pointer;
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
}

.btn-cancel:disabled,
.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
