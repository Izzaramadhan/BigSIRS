<script setup>
import { ref } from 'vue';
import { formatRupiah } from '@/utils/billingFormatters';

defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'settle-batch']);

const edcTerminals = ref([
  {
    name: 'EDC Mandiri Terminal 01',
    tid: 'MNDR-88019',
    mid: '000109923847',
    batch: 'BATCH-0042',
    transactions: 28,
    amount: 9850000,
    status: 'Settled'
  },
  {
    name: 'EDC BCA Terminal 02',
    tid: 'BCA-44201',
    mid: '000287119283',
    batch: 'BATCH-0019',
    transactions: 19,
    amount: 5400000,
    status: 'Ready to Settle'
  },
  {
    name: 'EDC BNI Terminal 03',
    tid: 'BNI-11904',
    mid: '000377489201',
    batch: 'BATCH-0027',
    transactions: 12,
    amount: 3100000,
    status: 'Ready to Settle'
  },
  {
    name: 'QRIS Dinamis Bank Indonesia',
    tid: 'QRIS-MERCH-01',
    mid: 'ID102009384711',
    batch: 'SETTLE-TODAY',
    transactions: 35,
    amount: 8650000,
    status: 'Auto-Settled'
  }
]);

const handleSettle = () => {
  emit('settle-batch');
};
</script>

<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="recon-title">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="header-icon icon-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
              <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
          </div>
          <div>
            <h3 id="recon-title" class="title">Rekonsiliasi Mesin EDC & Terminal Bank</h3>
            <p class="subtitle">Validasi settlement transaksi non-tunai kasir poliklinik</p>
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
        <div class="terminals-list">
          <div v-for="t in edcTerminals" :key="t.tid" class="terminal-card">
            <div class="term-left">
              <span class="term-name font-semibold">{{ t.name }}</span>
              <span class="term-sub font-mono text-xs">TID: {{ t.tid }} &bull; {{ t.batch }}</span>
              <span class="term-tx text-xs text-muted">{{ t.transactions }} Transaksi Tercatat</span>
            </div>
            <div class="term-right">
              <span class="term-nominal font-mono font-bold">{{ formatRupiah(t.amount) }}</span>
              <span class="status-chip" :class="t.status.includes('Settle') ? 'status-ready' : 'status-done'">
                {{ t.status }}
              </span>
            </div>
          </div>
        </div>

        <div class="simulation-alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <div class="alert-text">
            <strong>Mode Simulasi EDC:</strong> Data rekonsiliasi merupakan mock peragaan antarmuka dan tidak menghubungi host perbankan atau mesin EDC fisik.
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn-secondary" @click="emit('close')">
          Tutup
        </button>
        <button type="button" class="btn-primary" @click="handleSettle">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>Proses Settlement Batch (Simulasi)</span>
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
  max-width: 620px;
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
}

.icon-blue {
  background: #e0f2fe;
  color: #0284c7;
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

.terminals-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.terminal-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.85rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.term-left {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.term-name {
  font-size: 0.9rem;
  color: #0f172a;
}

.term-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.term-nominal {
  font-size: 1rem;
  color: #1e293b;
}

.status-chip {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.status-ready {
  background: #fef3c7;
  color: #b45309;
}

.status-done {
  background: #dcfce7;
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
