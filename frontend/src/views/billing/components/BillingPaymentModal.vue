<script setup>
import { ref, computed, watch } from 'vue';
import { formatRupiah } from '@/utils/billingFormatters';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  bill: {
    type: Object,
    default: null
  },
  isSettleMode: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'confirm']);

const selectedMethod = ref('Tunai');
const paymentAmount = ref(0);
const cashReceived = ref(0);
const referenceNumber = ref('');

watch(
  () => props.bill,
  (newBill) => {
    if (newBill) {
      paymentAmount.value = newBill.remaining_balance || newBill.patient_responsibility || 0;
      cashReceived.value = paymentAmount.value;
      selectedMethod.value = 'Tunai';
      referenceNumber.value = `REF-${Math.floor(100000 + Math.random() * 900000)}`;
    }
  },
  { immediate: true }
);

const changeAmount = computed(() => {
  if (selectedMethod.value !== 'Tunai') return 0;
  return Math.max(0, cashReceived.value - paymentAmount.value);
});

const isAmountValid = computed(() => {
  if (!paymentAmount.value || paymentAmount.value <= 0) return false;
  if (!props.bill) return false;
  if (paymentAmount.value > props.bill.remaining_balance) return false;
  if (selectedMethod.value === 'Tunai' && cashReceived.value < paymentAmount.value) return false;
  return true;
});

const handleConfirm = () => {
  if (!isAmountValid.value) return;
  emit('confirm', {
    billId: props.bill.id,
    amount: paymentAmount.value,
    method: selectedMethod.value,
    cashReceived: cashReceived.value,
    changeAmount: changeAmount.value,
    referenceNumber: referenceNumber.value
  });
};
</script>

<template>
  <div v-if="isOpen && bill" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="payment-modal-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="header-icon icon-teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2"></rect>
              <line x1="12" y1="8" x2="12" y2="16"></line>
              <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>
          </div>
          <div>
            <h3 id="payment-modal-title" class="modal-title">
              {{ isSettleMode ? 'Pelunasan Sisa Tagihan Pasien' : 'Simulasi Pembayaran Tagihan Rawat Jalan' }}
            </h3>
            <p class="modal-subtitle">{{ bill.invoice_number }} &bull; {{ bill.visit_number }}</p>
          </div>
        </div>

        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup modal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <!-- Patient Info Card -->
        <div class="patient-summary-box">
          <div class="patient-name-row">
            <div>
              <h4 class="patient-name">{{ bill.patient_name }}</h4>
              <span class="patient-rm font-mono text-muted text-xs">{{ bill.medical_record_number }} &bull; NIK: {{ bill.nik }}</span>
            </div>
            <span class="poly-chip">{{ bill.polyclinic }}</span>
          </div>

          <div class="financial-summary-grid">
            <div class="fin-item">
              <span class="fin-label">Total Tagihan</span>
              <span class="fin-val font-mono">{{ formatRupiah(bill.total_amount) }}</span>
            </div>
            <div class="fin-item">
              <span class="fin-label">Ditanggung Penjamin</span>
              <span class="fin-val font-mono text-green">{{ formatRupiah(bill.guarantor_amount) }}</span>
            </div>
            <div class="fin-item">
              <span class="fin-label">Kewajiban Pasien</span>
              <span class="fin-val font-mono">{{ formatRupiah(bill.patient_responsibility) }}</span>
            </div>
            <div class="fin-item highlight">
              <span class="fin-label">Sisa Tagihan Bayar</span>
              <span class="fin-val font-mono text-liability">{{ formatRupiah(bill.remaining_balance) }}</span>
            </div>
          </div>
        </div>

        <!-- Payment Method Selector -->
        <div class="form-group">
          <label class="form-label font-semibold">Pilih Metode Pembayaran</label>
          <div class="method-chips">
            <button
              v-for="m in ['Tunai', 'QRIS', 'EDC', 'Transfer']"
              :key="m"
              type="button"
              class="method-btn"
              :class="{ 'is-active': selectedMethod === m }"
              @click="selectedMethod = m"
            >
              {{ m }}
            </button>
          </div>
        </div>

        <!-- Payment Calculation Inputs -->
        <div class="calculation-box">
          <div class="calc-row">
            <label for="pay-amount" class="calc-label">Nominal Pembayaran:</label>
            <div class="input-currency-wrapper">
              <span class="curr-prefix">Rp</span>
              <input autocomplete="off"
                id="pay-amount"
                type="number"
                v-model.number="paymentAmount"
                class="calc-input font-mono"
                min="0"
                :max="bill.remaining_balance"
              />
            </div>
          </div>

          <div v-if="selectedMethod === 'Tunai'" class="calc-row">
            <label for="cash-received" class="calc-label">Uang Diterima (Tunai):</label>
            <div class="input-currency-wrapper">
              <span class="curr-prefix">Rp</span>
              <input autocomplete="off"
                id="cash-received"
                type="number"
                v-model.number="cashReceived"
                class="calc-input font-mono"
                min="0"
              />
            </div>
          </div>

          <div v-if="selectedMethod === 'Tunai'" class="calc-row result-row">
            <span class="calc-label font-bold">Kembalian:</span>
            <span class="change-val font-mono font-bold">{{ formatRupiah(changeAmount) }}</span>
          </div>

          <div v-else class="calc-row">
            <label for="ref-number" class="calc-label">No. Referensi / Batch EDC:</label>
            <input autocomplete="off"
              id="ref-number"
              type="text"
              v-model="referenceNumber"
              class="calc-input font-mono"
              placeholder="Contoh: REF-891230"
            />
          </div>
        </div>

        <!-- Demo Notice Alert -->
        <div class="simulation-alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <div class="alert-text">
            <strong>Mode Simulasi Kasir:</strong> Pembayaran ini tidak menghubungkan payment gateway maupun mencatat transaksi ke database pusat.
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button type="button" class="btn-secondary" @click="emit('close')">
          Batal
        </button>
        <button
          type="button"
          class="btn-primary"
          :disabled="!isAmountValid"
          @click="handleConfirm"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>Konfirmasi Simulasi Pembayaran</span>
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
  max-width: 580px;
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
  width: 42px;
  height: 42px;
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

.header-icon svg {
  width: 22px;
  height: 22px;
}

.modal-title {
  margin: 0 0 0.2rem 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
}

.modal-subtitle {
  margin: 0;
  font-size: 0.8rem;
  color: #64748b;
  font-family: monospace;
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

.patient-summary-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.patient-name-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px dashed #cbd5e1;
  padding-bottom: 0.65rem;
}

.patient-name {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.poly-chip {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  background: var(--color-primary-light, #e6f6f5);
  color: var(--color-primary, #087f78);
  border-radius: 4px;
}

.financial-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.65rem 1rem;
}

.fin-item {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.fin-item.highlight {
  background: #fff1f2;
  border: 1px solid #fecdd3;
  padding: 0.4rem 0.65rem;
  border-radius: 6px;
}

.fin-label {
  font-size: 0.725rem;
  color: #64748b;
  font-weight: 600;
}

.fin-val {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
}

.text-green {
  color: #15803d;
}

.text-liability {
  color: #be123c;
  font-size: 1.05rem;
}

.method-chips {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.35rem;
}

.method-btn {
  flex: 1;
  height: 36px;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.825rem;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s ease;
}

.method-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.method-btn.is-active {
  background: var(--color-primary, #087f78);
  border-color: var(--color-primary, #087f78);
  color: #ffffff;
}

.calculation-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.calc-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.calc-row.result-row {
  padding-top: 0.5rem;
  border-top: 1px dashed #e2e8f0;
}

.calc-label {
  font-size: 0.8rem;
  color: #334155;
  min-width: 150px;
}

.input-currency-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  max-width: 220px;
  width: 100%;
}

.curr-prefix {
  position: absolute;
  left: 0.75rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: #64748b;
  pointer-events: none;
}

.calc-input {
  width: 100%;
  height: 36px;
  padding: 0 0.75rem 0 2rem;
  font-size: 0.9rem;
  font-weight: 700;
  color: #1e293b;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  text-align: right;
}

.calc-input:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 2px rgba(8, 127, 120, 0.15);
}

.change-val {
  font-size: 1.1rem;
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

.btn-primary:hover:not(:disabled) {
  background: var(--color-primary-dark, #06635e);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
