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
          <div class="header-title-box">
            <span class="header-danger-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </span>
            <h3 class="modal-title text-danger">Konfirmasi Hapus Data</h3>
          </div>
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
          <p class="confirmation-message">
            Apakah Anda yakin ingin menghapus data pengeluaran barang ini?
          </p>

          <div class="issue-details-box">
            <div class="detail-row">
              <span class="label text-muted">Tanggal:</span>
              <span class="val font-semibold text-navy">{{ issue.formattedDate || issue.dateDisplay || '-' }}</span>
            </div>
            <div class="detail-row">
              <span class="label text-muted">Petugas:</span>
              <span class="val font-semibold text-navy">{{ issue.officerName || issue.officer_name || '-' }}</span>
            </div>
            <div class="detail-row">
              <span class="label text-muted">Keperluan:</span>
              <span class="val text-navy">{{ issue.notes || issue.purpose || '-' }}</span>
            </div>
          </div>

          <p class="warning-text text-danger font-medium">
            Tindakan ini akan menghapus data pengeluaran dari sistem.
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
            <svg v-if="isSubmitting" class="spin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="12" cy="12" r="10" stroke-width="4" stroke-dasharray="32" stroke-linecap="round"></circle>
            </svg>
            <span>{{ isSubmitting ? 'Menghapus...' : 'Ya, Hapus Data' }}</span>
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
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1060;
  padding: 1rem;
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 460px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: modalScale 0.2s ease-out;
}

@keyframes modalScale {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #fee2e2;
  background: #fef2f2;
}

.header-title-box {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-danger-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #fee2e2;
  color: #ef4444;
}

.header-danger-icon svg {
  width: 18px;
  height: 18px;
}

.modal-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
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
}

.btn-close:hover {
  background: #fecaca;
  color: #991b1b;
}

.btn-close svg {
  width: 18px;
  height: 18px;
}

.modal-body {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.confirmation-message {
  margin: 0;
  font-size: 0.9rem;
  color: #334155;
  line-height: 1.45;
}

.issue-details-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  font-size: 0.85rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.warning-text {
  margin: 0;
  font-size: 0.82rem;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-top: 1px solid #f1f5f9;
  background: #f8fafc;
}

.btn-cancel,
.btn-danger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  height: 38px;
  padding: 0 1.25rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-cancel {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
}

.btn-cancel:hover:not(:disabled) {
  background: #f1f5f9;
  color: #1e293b;
}

.btn-danger {
  background: #ef4444;
  border: 1px solid #ef4444;
  color: #ffffff;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
  border-color: #dc2626;
}

.btn-cancel:disabled,
.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spin-icon {
  width: 15px;
  height: 15px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.text-danger { color: #ef4444; }
.text-navy { color: var(--color-text-navy, #17243d); }
.text-muted { color: #64748b; }
.font-semibold { font-weight: 600; }
.font-medium { font-weight: 500; }
</style>
