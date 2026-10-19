<script setup>
import { ref, computed } from 'vue';
import { formatRupiah } from '@/utils/billingFormatters';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  records: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['close']);

const searchHistory = ref('');

// Filter paid and partial records as historical transactions
const historyTransactions = computed(() => {
  const list = props.records.filter((b) => b.status === 'Lunas' || b.status === 'Dibayar Sebagian' || b.paid_amount > 0);
  if (!searchHistory.value || searchHistory.value.trim() === '') {
    return list.slice(0, 20);
  }
  const q = searchHistory.value.toLowerCase().trim();
  return list.filter((b) =>
    b.patient_name.toLowerCase().includes(q) ||
    b.invoice_number.toLowerCase().includes(q) ||
    b.medical_record_number.toLowerCase().includes(q)
  ).slice(0, 20);
});
</script>

<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card history-modal-card" role="dialog" aria-modal="true" aria-labelledby="history-title">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="header-icon icon-teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 8v4l3 3"></path>
              <circle cx="12" cy="12" r="9"></circle>
              <path d="M3.05 11a9 9 0 0 1 .5-2m14-5.05A9 9 0 0 1 21 12"></path>
            </svg>
          </div>
          <div>
            <h3 id="history-title" class="title">Riwayat Transaksi Kasir Rawat Jalan</h3>
            <p class="subtitle">Log pencatatan transaksi pelunasan pasien pada loket kasir</p>
          </div>
        </div>

        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Search Input -->
      <div class="search-bar-wrap">
        <input
          type="text"
          v-model="searchHistory"
          class="history-search-input"
          placeholder="Cari riwayat nama pasien, No. RM, atau nomor invoice..."
        />
      </div>

      <!-- Body / Table -->
      <div class="modal-body">
        <div class="history-table-wrapper">
          <table class="history-table">
            <thead>
              <tr>
                <th>No. Kuitansi / Tagihan</th>
                <th>Waktu</th>
                <th>Pasien & No. RM</th>
                <th>Metode</th>
                <th class="text-right">Nominal</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tx in historyTransactions" :key="tx.id">
                <td class="font-mono font-medium">
                  <div>KWT-{{ tx.invoice_number.replace('INV-RJ-', '') }}</div>
                  <span class="text-muted text-xs">{{ tx.invoice_number }}</span>
                </td>
                <td class="font-mono text-xs">
                  <div>{{ tx.service_date }}</div>
                  <span class="text-muted">{{ tx.service_time }}</span>
                </td>
                <td>
                  <div class="font-semibold">{{ tx.patient_name }}</div>
                  <span class="text-muted font-mono text-xs">{{ tx.medical_record_number }} &bull; {{ tx.polyclinic }}</span>
                </td>
                <td>
                  <span class="method-chip">{{ tx.payment_method || 'Transfer' }}</span>
                </td>
                <td class="text-right font-mono font-bold">
                  {{ formatRupiah(tx.paid_amount || tx.total_amount) }}
                </td>
                <td>
                  <span class="status-chip chip-paid">Sukses ({{ tx.status }})</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn-secondary" @click="emit('close')">
          Tutup
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
  padding: 1.5rem;
}

.history-modal-card {
  width: 100%;
  max-width: 820px;
  max-height: 85vh;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
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

.search-bar-wrap {
  padding: 0.85rem 1.5rem 0.5rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.history-search-input {
  width: 100%;
  height: 38px;
  padding: 0 0.85rem;
  font-size: 0.85rem;
  color: #1e293b;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
}

.history-search-input:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
}

.modal-body {
  padding: 1.25rem 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.history-table-wrapper {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow-x: auto;
}

.history-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}

.history-table th {
  background: #f8fafc;
  padding: 0.65rem 0.85rem;
  border-bottom: 1px solid #e2e8f0;
  color: #475569;
  font-weight: 700;
  text-align: left;
}

.history-table td {
  padding: 0.65rem 0.85rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  vertical-align: middle;
}

.method-chip {
  font-size: 0.72rem;
  padding: 0.15rem 0.45rem;
  background: #e0f2fe;
  color: #0369a1;
  border-radius: 4px;
  font-weight: 600;
}

.status-chip {
  font-size: 0.72rem;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  font-weight: 600;
}

.chip-paid {
  background: #dcfce7;
  color: #15803d;
}

.modal-footer {
  padding: 0.85rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
}

.btn-secondary {
  height: 36px;
  padding: 0 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.btn-secondary:hover {
  background: #f1f5f9;
  color: #0f172a;
}
</style>
