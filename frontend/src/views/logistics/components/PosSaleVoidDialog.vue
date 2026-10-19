<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
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

const voidReason = ref('');

watch(
  () => props.isOpen,
  (val) => {
    if (val) {
      voidReason.value = 'Pembatalan transaksi oleh kasir (koreksi data kasir / permintaan pembeli)';
    }
  }
);

const handleConfirm = () => {
  emit('confirm', {
    id: props.sale?.id,
    reason: voidReason.value.trim() || 'Pembatalan transaksi oleh kasir'
  });
};
</script>

<template>
  <div v-if="isOpen && sale" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-dialog">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="void-danger-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
          </svg>
        </div>
        <div>
          <h3 class="modal-title">Batalkan Transaksi Kasir (Void)</h3>
          <p class="modal-subtitle">Tindakan ini akan membatalkan status pembayaran nota kasir.</p>
        </div>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <div class="trans-info-box">
          <div class="info-row">
            <span class="info-label">No. Transaksi</span>
            <span class="info-val font-mono font-bold">{{ sale.transactionNumber }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Nama Pembeli</span>
            <span class="info-val font-medium">{{ sale.customerName }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Total Pembayaran</span>
            <span class="info-val font-mono text-red font-bold">
              Rp {{ Number(sale.totalAmount).toLocaleString('id-ID') }}
            </span>
          </div>
        </div>

        <div class="warning-callout">
          <svg class="warning-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
          <div class="warning-text">
            <strong>Perhatian:</strong> Seluruh item sediaan obat/BMHP dalam transaksi ini akan secara otomatis dikembalikan ke stok inventori kasir lokal.
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="void-reason">Alasan Pembatalan / Void <span class="required">*</span></label>
          <textarea
            id="void-reason"
            v-model="voidReason"
            class="form-textarea"
            rows="3"
            placeholder="Tuliskan alasan pembatalan transaksi..."
            required
          ></textarea>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button
          type="button"
          class="btn-cancel"
          :disabled="submitting"
          @click="emit('close')"
        >
          Tutup
        </button>
        <button
          type="button"
          class="btn-danger-confirm"
          :disabled="submitting"
          @click="handleConfirm"
        >
          <svg v-if="submitting" class="spin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
          </svg>
          Ya, Batalkan Transaksi
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
  max-width: 500px;
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

.void-danger-icon {
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

.void-danger-icon svg {
  width: 22px;
  height: 22px;
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
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.trans-info-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.82rem;
}

.info-label {
  color: #64748b;
}

.info-val {
  color: #17243d;
}

.text-red {
  color: #dc2626 !important;
}

.warning-callout {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  background: #fffbeb;
  border: 1px solid #fef3c7;
  border-radius: 8px;
  padding: 0.75rem 0.95rem;
}

.warning-icon {
  width: 18px;
  height: 18px;
  color: #d97706;
  flex-shrink: 0;
  margin-top: 2px;
}

.warning-text {
  font-size: 0.78rem;
  color: #92400e;
  line-height: 1.4;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.required {
  color: #dc2626;
}

.form-textarea {
  padding: 0.65rem 0.85rem;
  font-size: 0.82rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 7px;
  color: var(--color-text-navy, #17243d);
  resize: vertical;
}

.form-textarea:focus {
  outline: none;
  border-color: #dc2626;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
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

.btn-danger-confirm svg {
  width: 15px;
  height: 15px;
}

.spin-icon {
  animation: spin 1s linear infinite;
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

.font-medium {
  font-weight: 500;
}

.font-bold {
  font-weight: 700;
}
</style>
