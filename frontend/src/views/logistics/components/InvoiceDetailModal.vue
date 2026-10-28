<script setup>
import InvoiceStatusBadge from './InvoiceStatusBadge.vue';

defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  invoice: {
    type: Object,
    default: null
  },
  formatRupiah: {
    type: Function,
    required: true
  },
  formatDateIndo: {
    type: Function,
    required: true
  },
  isOverdue: {
    type: Function,
    required: true
  },
  getDueStatusText: {
    type: Function,
    required: true
  }
});

defineEmits(['close']);

const printInvoice = () => {
  window.print();
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen && invoice" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-lg">
        <!-- Header -->
        <div class="modal-header">
          <div class="header-left">
            <div class="title-row">
              <h3>{{ invoice.invoiceNumber }}</h3>
              <InvoiceStatusBadge
                :status="invoice.status"
                :is-overdue="isOverdue(invoice.dueDate, invoice.status)"
              />
            </div>
            <p>Rincian lengkap data faktur pembelian farmasi</p>
          </div>
          <button type="button" class="btn-close" @click="$emit('close')" aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <!-- Information Grid -->
          <div class="info-grid">
            <div class="info-card">
              <span class="info-label">Supplier</span>
              <strong class="info-value text-navy">{{ invoice.supplierName }}</strong>
              <span class="info-subtext" v-if="invoice.supplierCity || invoice.supplierNpwp">
                {{ invoice.supplierCity }} <template v-if="invoice.supplierNpwp">• NPWP: {{ invoice.supplierNpwp }}</template>
              </span>
            </div>

            <div class="info-card">
              <span class="info-label">Nomor Referensi / PO</span>
              <strong class="info-value text-navy">{{ invoice.referenceNumber || '-' }}</strong>
            </div>

            <div class="info-card">
              <span class="info-label">Tanggal Faktur</span>
              <strong class="info-value text-navy">{{ formatDateIndo(invoice.invoiceDate) }}</strong>
            </div>

            <div class="info-card">
              <span class="info-label">Tanggal Jatuh Tempo</span>
              <div class="due-container">
                <template v-if="invoice.status === 'Lunas'">
                  <strong class="info-value text-muted">-</strong>
                </template>
                <template v-else-if="invoice.dueDate">
                  <strong
                    class="info-value"
                    :class="isOverdue(invoice.dueDate, invoice.status) ? 'text-danger' : 'text-navy'"
                  >
                    {{ formatDateIndo(invoice.dueDate) }}
                  </strong>
                  <span
                    v-if="getDueStatusText(invoice)"
                    class="due-pill"
                    :class="{
                      'pill-overdue': isOverdue(invoice.dueDate, invoice.status),
                      'pill-pending': !isOverdue(invoice.dueDate, invoice.status)
                    }"
                  >
                    {{ getDueStatusText(invoice) }}
                  </span>
                </template>
                <template v-else>
                  <strong class="info-value text-muted">-</strong>
                </template>
              </div>
            </div>
            <div class="info-card">
              <span class="info-label">Gudang Tujuan</span>
              <strong class="info-value text-navy">{{ invoice.warehouseName || 'Gudang Utama' }}</strong>
            </div>

            <div class="info-card">
              <span class="info-label">Pajak (PPN)</span>
              <strong class="info-value text-navy">{{ invoice.taxType === 'ppn_11' ? 'PPN (11%)' : 'Non PPN' }}</strong>
            </div>
          </div>

          <div v-if="invoice.notes" class="notes-box">
            <span class="notes-label">Catatan:</span>
            <p class="notes-text">{{ invoice.notes }}</p>
          </div>

          <!-- Items Table -->
          <div class="detail-items-section">
            <h4 class="section-title">Item Obat / Sediaan Terdaftar</h4>
            <div class="items-table-container">
              <table class="detail-table">
                <thead>
                  <tr>
                    <th style="width: 40px; text-align: center;">No</th>
                    <th>Nama Obat / Sediaan</th>
                    <th style="width: 140px;">No. Batch / Exp</th>
                    <th style="width: 80px; text-align: right;">Jumlah</th>
                    <th style="width: 90px;">Satuan</th>
                    <th style="width: 120px; text-align: right;">Harga Satuan</th>
                    <th style="width: 80px; text-align: right;">Diskon</th>
                    <th style="width: 130px; text-align: right;">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(it, idx) in invoice.items" :key="it.id || idx">
                    <td style="text-align: center;" class="text-muted">{{ idx + 1 }}</td>
                    <td class="font-medium text-navy">
                      <div>{{ it.medicineName }}</div>
                      <span v-if="it.isRepack" class="text-xs text-muted">(Repack: Ya)</span>
                    </td>
                    <td class="text-xs text-muted">
                      <div v-if="it.batchNumber">Batch: {{ it.batchNumber }}</div>
                      <div v-if="it.expiredDate">Exp: {{ formatDateIndo(it.expiredDate) }}</div>
                      <div v-if="!it.batchNumber && !it.expiredDate">-</div>
                    </td>
                    <td style="text-align: right;">{{ it.quantity }}</td>
                    <td class="text-muted">{{ it.unit }}</td>
                    <td style="text-align: right;">{{ formatRupiah(it.price) }}</td>
                    <td style="text-align: right;" class="text-muted">
                      {{ it.discount ? (it.discountType === 'nominal' ? formatRupiah(it.discount) : it.discount + '%') : '-' }}
                    </td>
                    <td style="text-align: right;" class="font-semibold text-navy">{{ formatRupiah(it.subtotal) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Grand Total Box -->
            <div class="grand-total-container">
              <div class="total-row">
                <span class="total-label">Total Nilai Faktur:</span>
                <span class="total-amount text-primary">{{ formatRupiah(invoice.totalAmount) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="button" class="btn-outline" @click="printInvoice">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
              <polyline points="6 9 6 2 18 2 18 9"></polyline>
              <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
              <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>Cetak Faktur</span>
          </button>
          <button type="button" class="btn-cancel" @click="$emit('close')">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.title-row h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.85rem;
  margin-bottom: 1.25rem;
}

.info-card {
  padding: 0.85rem 1rem;
  background: #f8fafc;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  display: flex;
  flex-direction: column;
}

.info-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-text-secondary, #64748b);
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin-bottom: 0.25rem;
}

.info-value {
  font-size: 0.95rem;
}

.info-subtext {
  font-size: 0.75rem;
  color: var(--color-text-secondary, #64748b);
  margin-top: 0.2rem;
}

.due-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.due-pill {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.pill-overdue {
  background: #fee2e2;
  color: #b91c1c;
}

.pill-paid {
  background: #d1fae5;
  color: #065f46;
}

.pill-pending {
  background: #f1f5f9;
  color: #475569;
}

.notes-box {
  padding: 0.75rem 1rem;
  background: #fffbeb;
  border: 1px solid #fef3c7;
  border-radius: 8px;
  margin-bottom: 1.25rem;
}

.notes-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: #92400e;
}

.notes-text {
  margin: 0.25rem 0 0;
  font-size: 0.85rem;
  color: #78350f;
}

.detail-items-section {
  margin-top: 1.25rem;
}

.section-title {
  margin: 0 0 0.75rem 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
}

.items-table-container {
  overflow-x: auto;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  background: #ffffff;
}

.detail-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
  min-width: 650px;
}

.detail-table th {
  background: #f8fafc;
  padding: 0.65rem 0.75rem;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-text-secondary, #64748b);
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.detail-table td {
  padding: 0.65rem 0.75rem;
  border-bottom: 1px solid var(--color-border-soft, #f1f5f9);
  color: var(--color-text-navy, #17243d);
}

.detail-table tbody tr:last-child td {
  border-bottom: none;
}

.grand-total-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-radius: 8px;
}

.total-row {
  display: flex;
  align-items: baseline;
  gap: 1.5rem;
}

.total-label {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.total-amount {
  font-size: 1.3rem;
  font-weight: 800;
}

.font-medium {
  font-weight: 500;
}

.font-semibold {
  font-weight: 600;
}

.text-navy {
  color: var(--color-text-navy, #17243d);
}

.text-primary {
  color: var(--color-primary, #087f78);
}

.text-danger {
  color: var(--color-danger, #d64545);
}

.text-muted {
  color: var(--color-text-secondary, #64748b);
}

.text-xs {
  font-size: 0.72rem;
}

@media (max-width: 640px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
}
</style>
