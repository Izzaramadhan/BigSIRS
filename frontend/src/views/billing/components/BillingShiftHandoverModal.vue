<script setup>
import { formatRupiah } from '@/utils/billingFormatters';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  shift: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="handover-title">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="header-icon icon-teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="8.5" cy="7" r="4"></circle>
              <polyline points="17 11 19 13 23 9"></polyline>
            </svg>
          </div>
          <div>
            <h3 id="handover-title" class="title">Serah Terima & Closing Shift Kasir</h3>
            <p class="subtitle">{{ shift.shift_title }} &bull; {{ shift.shift_time }}</p>
          </div>
        </div>

        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <!-- Cashier Officer Banner -->
        <div class="officer-box">
          <span class="officer-title">Petugas Kasir Shift Berjalan:</span>
          <span class="officer-name font-semibold">{{ shift.cashier_fullname }}</span>
          <span class="officer-loc text-muted text-xs">{{ shift.location }}</span>
        </div>

        <!-- Shift Breakdown Grid -->
        <div class="breakdown-grid">
          <div class="breakdown-card">
            <span class="b-label">Total Transaksi Tunai</span>
            <span class="b-val font-mono">{{ formatRupiah(shift.total_cash) }}</span>
          </div>
          <div class="breakdown-card">
            <span class="b-label">Total Transaksi QRIS</span>
            <span class="b-val font-mono">{{ formatRupiah(shift.total_qris) }}</span>
          </div>
          <div class="breakdown-card">
            <span class="b-label">Total Transaksi EDC</span>
            <span class="b-val font-mono">{{ formatRupiah(shift.total_edc) }}</span>
          </div>
          <div class="breakdown-card">
            <span class="b-label">Total Transaksi Transfer</span>
            <span class="b-val font-mono">{{ formatRupiah(shift.total_transfer) }}</span>
          </div>
        </div>

        <!-- Total Grand Summary -->
        <div class="grand-summary-box">
          <div class="sum-row">
            <span class="sum-lbl">Total Pembayaran Pasien Shift Ini:</span>
            <span class="sum-val font-mono font-bold">{{ formatRupiah(shift.total_income) }}</span>
          </div>
          <div class="sum-row">
            <span class="sum-lbl">Selisih Kas Fisik vs Sistem:</span>
            <span class="sum-val font-mono text-green font-bold">
              {{ formatRupiah(shift.cash_variance) }} (Cocok / Seimbang)
            </span>
          </div>
        </div>

        <div class="simulation-alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <div class="alert-text">
            <strong>Mode Simulasi Closing Kasir:</strong> Tindakan serah terima ini bersifat demonstrasi tampilan dan tidak mengubah buku kas maupun jurnal akuntansi rumah sakit.
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn-secondary" @click="emit('close')">
          Tutup
        </button>
        <button type="button" class="btn-primary" @click="emit('confirm')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>Konfirmasi Serah Terima Shift (Simulasi)</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.modal-card {
  width: 100%;
  max-width: 600px;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid #e2e8f0;
}

.header-left {
  display: flex;
  gap: 0.85rem;
  align-items: center;
}

.header-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-teal {
  background: var(--color-primary-light, #e6f6f5);
  color: var(--color-primary, #087f78);
}

.title {
  margin: 0 0 0.2rem 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
}

.subtitle {
  margin: 0;
  font-size: 0.8rem;
  color: #64748b;
}

.btn-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 6px;
}

.btn-close:hover {
  color: #0f172a;
  background: #f1f5f9;
}

.btn-close svg {
  width: 20px;
  height: 20px;
}

.modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.officer-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.officer-title {
  font-size: 0.72rem;
  color: #64748b;
}

.officer-name {
  font-size: 0.95rem;
  color: #0f172a;
}

.breakdown-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.breakdown-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.b-label {
  font-size: 0.725rem;
  color: #64748b;
  font-weight: 600;
}

.b-val {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1e293b;
}

.grand-summary-box {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 1rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.sum-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sum-lbl {
  font-size: 0.825rem;
  color: #166534;
}

.sum-val {
  font-size: 1.1rem;
  color: #15803d;
}

.text-green {
  color: #15803d;
}

.simulation-alert {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.75rem 0.85rem;
  background: #f0fdfa;
  border: 1px solid #ccfbf1;
  border-radius: 8px;
  color: #0f766e;
  font-size: 0.775rem;
  line-height: 1.4;
}

.simulation-alert svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  margin-top: 0.1rem;
}

.modal-footer {
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-secondary, .btn-primary {
  height: 38px;
  padding: 0 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  transition: all 0.15s ease;
}

.btn-secondary {
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.btn-secondary:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.btn-primary {
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: none;
}

.btn-primary:hover {
  background: var(--color-primary-dark, #06635e);
}
</style>
