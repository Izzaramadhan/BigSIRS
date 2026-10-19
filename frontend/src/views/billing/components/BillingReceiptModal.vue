<script setup>
import { formatRupiah, formatBillingDate } from '@/utils/billingFormatters';

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

const emit = defineEmits(['close', 'print']);

const handlePrint = () => {
  emit('print', props.bill);
};
</script>

<template>
  <div v-if="isOpen && bill" class="modal-backdrop" @click.self="emit('close')">
    <div class="receipt-modal-card" role="dialog" aria-modal="true" aria-labelledby="receipt-title">
      <!-- Header -->
      <div class="receipt-header">
        <div class="header-left">
          <div class="receipt-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
          </div>
          <div>
            <h3 id="receipt-title" class="title">Pratinjau Kuitansi Pembayaran Rawat Jalan</h3>
            <p class="subtitle">Bukti pembayaran resmi kasir terintegrasi SIMRS BigSIRS</p>
          </div>
        </div>

        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Receipt Paper View -->
      <div class="receipt-body">
        <div class="paper-sheet">
          <!-- Watermark -->
          <div class="watermark-overlay">DEMO / SIMULASI</div>

          <!-- Hospital Header -->
          <div class="hospital-head">
            <div class="head-left">
              <h4 class="hospital-name">RS SISFOMEDIKA UTAMA</h4>
              <p class="hospital-address">Jl. Kesehatan No. 128, Jakarta Pusat &bull; Telp: (021) 555-0199</p>
            </div>
            <div class="receipt-badge">
              <span class="badge-title">KUITANSI RAWAT JALAN</span>
              <span class="badge-num font-mono">No: KWT-{{ bill.invoice_number.replace('INV-RJ-', '') }}</span>
            </div>
          </div>

          <div class="divider-line"></div>

          <!-- Receipt Details Grid -->
          <div class="receipt-info-grid">
            <div class="info-row">
              <span class="row-label">Telah Terima Dari:</span>
              <span class="row-val font-semibold">{{ bill.patient_name }}</span>
            </div>
            <div class="info-row">
              <span class="row-label">No. Rekam Medis (RM):</span>
              <span class="row-val font-mono">{{ bill.medical_record_number }} (NIK: {{ bill.nik }})</span>
            </div>
            <div class="info-row">
              <span class="row-label">Nomor Kunjungan / Tagihan:</span>
              <span class="row-val font-mono">{{ bill.visit_number }} &bull; {{ bill.invoice_number }}</span>
            </div>
            <div class="info-row">
              <span class="row-label">Poliklinik Tujuan:</span>
              <span class="row-val">{{ bill.polyclinic }} (DPJP: {{ bill.doctor }})</span>
            </div>
            <div class="info-row">
              <span class="row-label">Jenis Penjamin:</span>
              <span class="row-val">{{ bill.guarantor_type }} ({{ bill.guarantor_detail }})</span>
            </div>
            <div class="info-row">
              <span class="row-label">Tanggal & Jam Pelayanan:</span>
              <span class="row-val font-mono">{{ formatBillingDate(bill.service_date) }} &bull; {{ bill.service_time }}</span>
            </div>
            <div class="info-row">
              <span class="row-label">Metode Pembayaran:</span>
              <span class="row-val font-semibold text-teal">{{ bill.payment_method || 'Tunai / Non-Tunai' }}</span>
            </div>
          </div>

          <!-- Amount Banner -->
          <div class="amount-banner">
            <div class="amount-box">
              <span class="amount-label">Jumlah Pembayaran Diterima</span>
              <span class="amount-value font-mono font-bold">
                {{ formatRupiah(bill.paid_amount || bill.total_amount) }}
              </span>
            </div>
            <div class="status-stamp font-mono">LUNAS</div>
          </div>

          <!-- Footer Sign -->
          <div class="receipt-footer-row">
            <div class="note-col">
              <p class="footer-note">* Kuitansi ini sah diterbitkan oleh sistem kasir SIMRS BigSIRS.</p>
              <p class="footer-note">* Harap simpan bukti kuitansi ini untuk pengambilan obat atau arsip asuransi.</p>
            </div>
            <div class="sign-col">
              <span class="sign-date">Jakarta, {{ formatBillingDate(bill.service_date) }}</span>
              <div class="sign-space"></div>
              <span class="sign-officer font-semibold">Kasir_Poli_04</span>
              <span class="sign-sub text-muted text-xs">Petugas Kasir Rawat Jalan</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="receipt-actions-footer">
        <button type="button" class="btn-secondary" @click="emit('close')">
          Tutup
        </button>
        <button type="button" class="btn-primary" @click="handlePrint">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
          <span>Cetak Kuitansi (Simulasi)</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1.5rem;
}

.receipt-modal-card {
  width: 100%;
  max-width: 680px;
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

.receipt-header {
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid #e2e8f0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.receipt-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #dcfce7;
  color: #16a34a;
  display: flex;
  align-items: center;
  justify-content: center;
}

.receipt-icon svg {
  width: 22px;
  height: 22px;
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

.receipt-body {
  padding: 1.5rem;
  overflow-y: auto;
  background: #f8fafc;
  flex: 1;
}

.paper-sheet {
  position: relative;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
  overflow: hidden;
}

.watermark-overlay {
  position: absolute;
  top: 45%;
  left: 20%;
  transform: rotate(-30deg);
  font-size: 3.5rem;
  font-weight: 900;
  color: rgba(220, 38, 38, 0.06);
  pointer-events: none;
  user-select: none;
  letter-spacing: 0.1em;
  white-space: nowrap;
}

.hospital-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.hospital-name {
  margin: 0 0 0.25rem 0;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--color-primary, #087f78);
  letter-spacing: 0.03em;
}

.hospital-address {
  margin: 0;
  font-size: 0.775rem;
  color: #64748b;
}

.receipt-badge {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.badge-title {
  font-size: 0.85rem;
  font-weight: 800;
  color: #0f172a;
}

.badge-num {
  font-size: 0.75rem;
  color: #64748b;
}

.divider-line {
  height: 2px;
  background: #334155;
}

.receipt-info-grid {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  font-size: 0.825rem;
}

.info-row {
  display: flex;
  gap: 0.75rem;
}

.row-label {
  min-width: 190px;
  color: #64748b;
}

.row-val {
  color: #1e293b;
}

.text-teal {
  color: var(--color-primary, #087f78);
}

.amount-banner {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 1rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.amount-box {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.amount-label {
  font-size: 0.75rem;
  color: #166534;
  font-weight: 600;
}

.amount-value {
  font-size: 1.45rem;
  color: #15803d;
}

.status-stamp {
  border: 2px solid #16a34a;
  color: #16a34a;
  font-weight: 800;
  font-size: 1rem;
  padding: 0.35rem 0.85rem;
  border-radius: 6px;
  transform: rotate(-5deg);
  letter-spacing: 0.08em;
}

.receipt-footer-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  padding-top: 0.5rem;
}

.note-col {
  max-width: 320px;
}

.footer-note {
  margin: 0.2rem 0;
  font-size: 0.7rem;
  color: #94a3b8;
}

.sign-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  font-size: 0.775rem;
}

.sign-space {
  height: 40px;
}

.receipt-actions-footer {
  padding: 1rem 1.5rem;
  background: #ffffff;
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

.btn-primary svg {
  width: 16px;
  height: 16px;
}
</style>
