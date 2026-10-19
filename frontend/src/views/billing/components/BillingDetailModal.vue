<script setup>
import { formatRupiah, formatBillingDate, getBillingStatusBadgeClass } from '@/utils/billingFormatters';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  bill: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close']);
</script>

<template>
  <div v-if="isOpen && bill" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card detail-modal-card" role="dialog" aria-modal="true" aria-labelledby="detail-bill-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="header-icon icon-teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
          </div>
          <div>
            <h3 id="detail-bill-title" class="modal-title">Rincian Lengkap Tagihan Pasien</h3>
            <p class="modal-subtitle font-mono">{{ bill.invoice_number }} &bull; {{ bill.visit_number }}</p>
          </div>
        </div>

        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <!-- Patient & Visit Info Grid -->
        <div class="info-card-section">
          <div class="info-2col">
            <div class="info-group">
              <span class="label">Nama Lengkap Pasien:</span>
              <span class="val font-semibold">{{ bill.patient_name }}</span>
            </div>
            <div class="info-group">
              <span class="label">No. Rekam Medis (RM):</span>
              <span class="val font-mono font-semibold">{{ bill.medical_record_number }}</span>
            </div>
            <div class="info-group">
              <span class="label">NIK / Kependudukan:</span>
              <span class="val font-mono">{{ bill.nik }}</span>
            </div>
            <div class="info-group">
              <span class="label">Demografi:</span>
              <span class="val">{{ bill.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}, {{ bill.age }} Tahun</span>
            </div>
            <div class="info-group">
              <span class="label">Poliklinik Tujuan:</span>
              <span class="val">{{ bill.polyclinic }}</span>
            </div>
            <div class="info-group">
              <span class="label">Dokter DPJP:</span>
              <span class="val font-medium">{{ bill.doctor }}</span>
            </div>
            <div class="info-group">
              <span class="label">Penjamin:</span>
              <span class="val">{{ bill.guarantor_type }} ({{ bill.guarantor_detail }})</span>
            </div>
            <div class="info-group">
              <span class="label">Status Tagihan:</span>
              <span class="billing-status-badge" :class="getBillingStatusBadgeClass(bill.status)">
                <span class="status-dot"></span>
                <span>{{ bill.status }}</span>
              </span>
            </div>
          </div>
        </div>

        <!-- Itemized Fees Table -->
        <div class="items-section">
          <h4 class="section-title">Rincian Komponen Biaya & Tindakan</h4>
          <div class="items-table-wrapper">
            <table class="items-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Uraian Komponen / Tindakan</th>
                  <th>Kategori</th>
                  <th class="text-center">Qty</th>
                  <th class="text-right">Harga Satuan</th>
                  <th class="text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in bill.items" :key="idx">
                  <td>{{ idx + 1 }}</td>
                  <td class="font-medium">{{ item.name }}</td>
                  <td><span class="cat-pill">{{ item.category }}</span></td>
                  <td class="text-center font-mono">{{ item.qty }}</td>
                  <td class="text-right font-mono">{{ formatRupiah(item.unit_price) }}</td>
                  <td class="text-right font-mono font-bold">{{ formatRupiah(item.total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Financial Summary Breakdown Box -->
        <div class="financial-breakdown-box">
          <div class="summary-line">
            <span class="lbl">Total Tagihan Bruto:</span>
            <span class="val font-mono font-bold">{{ formatRupiah(bill.total_amount) }}</span>
          </div>
          <div class="summary-line text-green">
            <span class="lbl">Tanggungan Penjamin (Klaim):</span>
            <span class="val font-mono font-bold">- {{ formatRupiah(bill.guarantor_amount) }}</span>
          </div>
          <div class="summary-line">
            <span class="lbl">Total Kewajiban Bayar Pasien:</span>
            <span class="val font-mono font-bold">{{ formatRupiah(bill.patient_responsibility) }}</span>
          </div>
          <div class="summary-line">
            <span class="lbl">Pembayaran Telah Diterima Kasir:</span>
            <span class="val font-mono text-teal">{{ formatRupiah(bill.paid_amount) }}</span>
          </div>
          <div class="summary-line total-highlight">
            <span class="lbl">Sisa Kewajiban Tagihan:</span>
            <span class="val font-mono font-bold text-liability">{{ formatRupiah(bill.remaining_balance) }}</span>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
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

.detail-modal-card {
  width: 100%;
  max-width: 740px;
  max-height: 90vh;
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
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.info-card-section {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem 1.25rem;
}

.info-2col {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.65rem 1.25rem;
  font-size: 0.825rem;
}

.info-group {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.label {
  font-size: 0.725rem;
  color: #64748b;
}

.val {
  color: #1e293b;
}

.section-title {
  margin: 0 0 0.65rem 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: #1e293b;
}

.items-table-wrapper {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow-x: auto;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}

.items-table th {
  background: #f8fafc;
  padding: 0.65rem 0.85rem;
  border-bottom: 1px solid #e2e8f0;
  color: #475569;
  font-weight: 700;
}

.items-table td {
  padding: 0.65rem 0.85rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}

.cat-pill {
  font-size: 0.7rem;
  background: #f1f5f9;
  color: #475569;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.financial-breakdown-box {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  font-size: 0.85rem;
}

.summary-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-line.text-green {
  color: #15803d;
}

.summary-line.text-teal {
  color: #0d9488;
}

.summary-line.total-highlight {
  padding-top: 0.5rem;
  margin-top: 0.25rem;
  border-top: 2px dashed #cbd5e1;
  font-size: 1rem;
}

.text-liability {
  color: #be123c;
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
