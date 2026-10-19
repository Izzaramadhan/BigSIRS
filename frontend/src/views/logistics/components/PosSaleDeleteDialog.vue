<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  sale: {
    type: Object,
    default: null
  },
  submitting: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
  <div v-if="isOpen && sale" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-dialog">
      <div class="modal-header">
        <div class="delete-icon-box" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
        </div>
        <div>
          <h3 class="modal-title">Hapus Draft Transaksi</h3>
          <p class="modal-subtitle">Apakah Anda yakin ingin menghapus draft transaksi kasir ini?</p>
        </div>
      </div>

      <div class="modal-body">
        <p class="delete-warning-text">
          Draft transaksi dengan nomor <strong class="font-mono">{{ sale.transactionNumber }}</strong> untuk pembeli
          <strong>{{ sale.customerName }}</strong> akan dihapus secara permanen dari daftar antrean kasir.
        </p>
      </div>

      <div class="modal-footer">
        <button
          type="button"
          class="btn-cancel"
          :disabled="submitting"
          @click="emit('close')"
        >
          Batal
        </button>
        <button
          type="button"
          class="btn-danger-confirm"
          :disabled="submitting"
          @click="emit('confirm')"
        >
          <svg v-if="submitting" class="spin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
          </svg>
          <span v-else>Ya, Hapus Draft</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1060;
  padding: 1rem;
}

.modal-dialog {
  background: #ffffff;
  border-radius: 14px;
  width: 100%;
  max-width: 460px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.modal-header {
  padding: 1.25rem 1.4rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  display: flex;
  align-items: center;
  gap: 0.85rem;
  background: #fef2f2;
}

.delete-icon-box {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #fee2e2;
  color: #dc2626;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.delete-icon-box svg {
  width: 20px;
  height: 20px;
}

.modal-title {
  font-size: 1rem;
  font-weight: 700;
  color: #991b1b;
  margin: 0;
}

.modal-subtitle {
  font-size: 0.78rem;
  color: #7f1d1d;
  margin: 0.2rem 0 0 0;
}

.modal-body {
  padding: 1.25rem 1.4rem;
}

.delete-warning-text {
  font-size: 0.84rem;
  color: #334155;
  line-height: 1.5;
  margin: 0;
}

.modal-footer {
  padding: 0.95rem 1.4rem;
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
  background: #f8fafc;
  display: flex;
  justify-content: flex-end;
  gap: 0.65rem;
}

.btn-cancel {
  height: 38px;
  padding: 0 1.15rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  cursor: pointer;
}

.btn-danger-confirm {
  height: 38px;
  padding: 0 1.25rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #ffffff;
  background: #dc2626;
  border: 1px solid #dc2626;
  border-radius: 7px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  transition: all 0.15s ease;
}

.btn-danger-confirm:hover:not(:disabled) {
  background: #b91c1c;
}

.spin-icon {
  animation: spin 1s linear infinite;
  width: 15px;
  height: 15px;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
</style>
