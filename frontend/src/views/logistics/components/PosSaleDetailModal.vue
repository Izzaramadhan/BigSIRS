<script setup>
import PosSaleStatusBadge from './PosSaleStatusBadge.vue';

defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  sale: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'print']);

const formatRupiah = (val) => {
  if (val == null) return 'Rp 0';
  return 'Rp ' + Number(val).toLocaleString('id-ID');
};
</script>

<template>
  <div v-if="isOpen && sale" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-dialog">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="receipt-icon-box" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
          </div>
          <div>
            <div class="title-with-badge">
              <h3 class="modal-title">Struk Transaksi Penjualan OTC</h3>
              <PosSaleStatusBadge type="status" :value="sale.status" />
            </div>
            <p class="modal-subtitle">No. Transaksi: <span class="font-mono font-semibold">{{ sale.transactionNumber }}</span></p>
          </div>
        </div>

        <button type="button" class="btn-close" aria-label="Tutup Modal" @click="emit('close')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <!-- Void Alert Banner if Cancelled -->
        <div v-if="sale.status === 'Dibatalkan'" class="void-alert-card">
          <div class="void-icon-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
          </div>
          <div class="void-content">
            <h4 class="void-title">Transaksi Dibatalkan (Void Kasir)</h4>
            <p class="void-reason">{{ sale.voidReason || 'Transaksi dibatalkan dan stok dikembalikan ke inventori.' }}</p>
          </div>
        </div>

        <!-- Meta Grid -->
        <div class="detail-meta-grid">
          <div class="meta-item">
            <span class="meta-label">Tanggal & Waktu</span>
            <span class="meta-value">{{ sale.dateDisplay }} {{ sale.timeDisplay }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Lokasi Kasir</span>
            <span class="meta-value">{{ sale.location }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Petugas Kasir</span>
            <span class="meta-value">{{ sale.cashier }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Nama Pembeli</span>
            <div class="cust-info-flex">
              <span class="meta-value font-semibold">{{ sale.customerName }}</span>
              <PosSaleStatusBadge v-if="sale.customerType" type="customer" :value="sale.customerType" />
            </div>
          </div>
          <div class="meta-item">
            <span class="meta-label">Metode Pembayaran</span>
            <PosSaleStatusBadge type="payment" :value="sale.paymentMethodDisplay || sale.paymentMethod" />
          </div>
        </div>

        <!-- Items Table -->
        <div class="items-section">
          <h4 class="section-title">Rincian Obat & BMHP</h4>
          <div class="table-responsive">
            <table class="detail-table">
              <thead>
                <tr>
                  <th class="text-center col-sm">NO</th>
                  <th>NAMA PRODUK</th>
                  <th class="text-right">HARGA SATUAN</th>
                  <th class="text-center">QTY</th>
                  <th class="text-right">SUBTOTAL</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(it, idx) in sale.items" :key="idx">
                  <td class="text-center font-mono">{{ idx + 1 }}</td>
                  <td>
                    <span class="font-medium text-navy">{{ it.name }}</span>
                    <span v-if="it.category" class="item-cat-sub">({{ it.category }})</span>
                  </td>
                  <td class="text-right font-mono">{{ formatRupiah(it.price) }}</td>
                  <td class="text-center">
                    <span class="qty-pill">{{ it.qty }} {{ it.unit || 'Unit' }}</span>
                  </td>
                  <td class="text-right font-mono font-semibold">{{ formatRupiah(it.subtotal || (it.price * it.qty)) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Payment Breakdown -->
        <div class="payment-breakdown">
          <div class="breakdown-card">
            <div class="breakdown-row">
              <span class="breakdown-label">Total Belanja (Bruto)</span>
              <span class="breakdown-val font-mono">{{ formatRupiah(sale.totalAmount) }}</span>
            </div>
            <div class="breakdown-row total-highlight">
              <span class="breakdown-label font-bold text-navy">Total Pembayaran</span>
              <span class="breakdown-val font-mono font-bold text-teal text-lg">{{ formatRupiah(sale.totalAmount) }}</span>
            </div>
            <div v-if="sale.paidAmount" class="breakdown-row">
              <span class="breakdown-label">Nominal Diterima</span>
              <span class="breakdown-val font-mono">{{ formatRupiah(sale.paidAmount) }}</span>
            </div>
            <div v-if="sale.changeAmount !== undefined" class="breakdown-row">
              <span class="breakdown-label">Kembalian</span>
              <span class="breakdown-val font-mono text-success">{{ formatRupiah(sale.changeAmount) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button
          type="button"
          class="btn-print"
          @click="emit('print', sale)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
          Cetak Struk Kasir
        </button>
        <button
          type="button"
          class="btn-close-modal"
          @click="emit('close')"
        >
          Tutup
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
  z-index: 1050;
  padding: 1rem;
}

.modal-dialog {
  background: #ffffff;
  border-radius: 14px;
  width: 100%;
  max-width: 680px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: #f8fafc;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.receipt-icon-box {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.receipt-icon-box svg {
  width: 22px;
  height: 22px;
}

.title-with-badge {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.modal-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.modal-subtitle {
  font-size: 0.8rem;
  color: var(--color-text-secondary, #64748b);
  margin: 0.2rem 0 0 0;
}

.btn-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.4rem;
  border-radius: 6px;
  transition: all 0.15s ease;
}

.btn-close:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-close svg {
  width: 18px;
  height: 18px;
}

.modal-body {
  padding: 1.4rem 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Void Banner */
.void-alert-card {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 0.85rem 1rem;
}

.void-icon-box {
  color: #dc2626;
  margin-top: 2px;
}

.void-icon-box svg {
  width: 18px;
  height: 18px;
}

.void-title {
  font-size: 0.86rem;
  font-weight: 700;
  color: #dc2626;
  margin: 0 0 0.15rem 0;
}

.void-reason {
  font-size: 0.78rem;
  color: #991b1b;
  margin: 0;
}

/* Meta Grid */
.detail-meta-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.85rem 1.25rem;
  background: #f8fafc;
  padding: 1rem 1.15rem;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.meta-label {
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #64748b;
}

.meta-value {
  font-size: 0.84rem;
  color: #17243d;
}

.cust-info-flex {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

/* Items Table */
.items-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.section-title {
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.detail-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}

.detail-table thead th {
  background: #f1f5f9;
  color: #475569;
  font-weight: 700;
  font-size: 0.72rem;
  padding: 0.65rem 0.85rem;
  border-bottom: 1px solid #cbd5e1;
}

.detail-table tbody td {
  padding: 0.75rem 0.85rem;
  border-bottom: 1px solid #f1f5f9;
}

.item-cat-sub {
  font-size: 0.72rem;
  color: #94a3b8;
  margin-left: 0.35rem;
}

.qty-pill {
  display: inline-block;
  background: #f1f5f9;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.74rem;
  font-weight: 600;
  color: #334155;
}

/* Payment Breakdown */
.payment-breakdown {
  display: flex;
  justify-content: flex-end;
}

.breakdown-card {
  width: 100%;
  max-width: 320px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.breakdown-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
  color: #475569;
}

.total-highlight {
  padding-top: 0.45rem;
  margin-top: 0.25rem;
  border-top: 1px dashed #cbd5e1;
}

.text-teal {
  color: var(--color-primary, #087f78) !important;
}

.text-success {
  color: #059669 !important;
  font-weight: 600;
}

.text-lg {
  font-size: 1.05rem;
}

/* Modal Footer */
.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
  background: #f8fafc;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-print {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  height: 38px;
  padding: 0 1.25rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #ffffff;
  background: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-print:hover {
  background: #076d67;
}

.btn-print svg {
  width: 15px;
  height: 15px;
}

.btn-close-modal {
  height: 38px;
  padding: 0 1.25rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-close-modal:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.font-medium {
  font-weight: 500;
}

.font-semibold {
  font-weight: 600;
}

.font-bold {
  font-weight: 700;
}

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.col-sm {
  width: 45px;
}
</style>
