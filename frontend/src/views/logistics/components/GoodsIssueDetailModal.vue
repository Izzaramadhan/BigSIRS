<script setup>
import GoodsIssueStatusBadge from './GoodsIssueStatusBadge.vue';

defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  issue: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'print', 'finalize', 'cancel']);
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen && issue" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-lg">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <div class="header-badge-row">
              <span class="issue-number font-bold text-primary">{{ issue.issueNumber }}</span>
              <GoodsIssueStatusBadge type="status" :value="issue.status" />
              <GoodsIssueStatusBadge type="purpose" :value="issue.purpose" />
            </div>
            <h3 class="modal-title">{{ issue.purpose }}</h3>
          </div>
          <button
            type="button"
            class="btn-close"
            aria-label="Tutup"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <!-- Validation / Cancellation Alert Banner -->
          <div v-if="issue.validationNote || issue.status === 'Dibatalkan'" class="alert-banner">
            <div class="alert-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="alert-text">
              <strong>Catatan Validasi Dokumen:</strong>
              <p>{{ issue.validationNote || 'Dokumen pengeluaran barang ini telah dibatalkan.' }}</p>
            </div>
          </div>

          <!-- Metadata Grid -->
          <div class="metadata-grid">
            <div class="meta-item">
              <span class="meta-label">Tanggal & Waktu</span>
              <span class="meta-value">{{ issue.dateDisplay }}, {{ issue.timeDisplay }}</span>
            </div>

            <div class="meta-item">
              <span class="meta-label">Gudang Asal</span>
              <span class="meta-value font-bold text-navy">{{ issue.warehouse }}</span>
              <span class="meta-sub">{{ issue.locationDetail || 'Rak Distribusi' }}</span>
            </div>

            <div class="meta-item">
              <span class="meta-label">Petugas Otorisasi</span>
              <span class="meta-value font-bold text-navy">{{ issue.officerName }}</span>
              <span class="meta-sub">{{ issue.officerRole }}</span>
            </div>

            <div class="meta-item">
              <span class="meta-label">Nomor Referensi / BAP</span>
              <span class="meta-value">{{ issue.referenceNumber || '-' }}</span>
            </div>

            <div class="meta-item">
              <span class="meta-label">Jenis Keperluan</span>
              <span class="meta-value">{{ issue.purpose }}</span>
            </div>

            <div class="meta-item">
              <span class="meta-label">Total Pengeluaran</span>
              <span class="meta-value font-bold text-navy">{{ issue.totalQuantity }} {{ issue.quantityUnit }}</span>
              <span class="meta-sub">{{ issue.itemCount }} Jenis Item</span>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="issue.notes" class="notes-box">
            <span class="notes-label">Catatan / Alasan:</span>
            <p class="notes-content">{{ issue.notes }}</p>
          </div>

          <!-- Items Table -->
          <div class="items-section">
            <div class="items-header">
              <h4 class="items-title">Daftar Item Obat yang Dikeluarkan</h4>
              <span class="items-subtitle">Rincian nama sediaan, nomor batch pabrik, masa kedaluwarsa, dan jumlah kuantitas</span>
            </div>

            <div class="table-wrap">
              <table v-if="issue.items && issue.items.length > 0" class="detail-items-table">
                <thead>
                  <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 35%;">Nama Obat / Sediaan</th>
                    <th style="width: 15%;">No. Batch</th>
                    <th style="width: 15%;">Tgl. Expired</th>
                    <th style="width: 15%;" class="text-center">Kuantitas Keluar</th>
                    <th style="width: 15%;">Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(it, idx) in issue.items" :key="it.id || idx">
                    <td class="text-center text-muted">{{ idx + 1 }}</td>
                    <td class="font-bold text-navy">{{ it.name }}</td>
                    <td><span class="batch-code">{{ it.batchNumber || '-' }}</span></td>
                    <td>{{ it.expDate || '-' }}</td>
                    <td class="text-center">
                      <span class="qty-pill font-bold">{{ it.quantity }} {{ it.unit }}</span>
                    </td>
                    <td class="text-muted">{{ it.notes || '-' }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" class="text-right font-bold text-navy">TOTAL PENGELUARAN:</td>
                    <td class="text-center font-bold text-primary">{{ issue.totalQuantity }} {{ issue.quantityUnit }}</td>
                    <td class="text-muted font-semibold">{{ issue.itemCount }} Jenis Item</td>
                  </tr>
                </tfoot>
              </table>

              <div v-else class="items-empty-state">
                <p>Belum ada rincian item individual yang tercatat pada dokumen ini.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button
            type="button"
            class="btn-cancel"
            @click="$emit('close')"
          >
            Tutup
          </button>

          <div class="footer-actions">
            <button
              v-if="issue.status === 'Draft'"
              type="button"
              class="btn-primary"
              @click="emit('finalize', issue)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              <span>Finalisasi Pengeluaran</span>
            </button>

            <button
              v-else-if="issue.status === 'Selesai'"
              type="button"
              class="btn-cancel text-danger"
              @click="emit('cancel', issue)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
              <span>Batalkan Transaksi</span>
            </button>

            <button
              type="button"
              class="btn-outline"
              @click="emit('print', issue)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
              </svg>
              <span>Cetak Dokumen Pengeluaran</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.modal-content {
  background: #ffffff;
  border-radius: 14px;
  width: 100%;
  max-width: 800px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: modalEnter 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  overflow: hidden;
}

@keyframes modalEnter {
  from {
    opacity: 0;
    transform: scale(0.97) translateY(8px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.header-badge-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 0.35rem;
  flex-wrap: wrap;
}

.issue-number {
  font-size: 1.05rem;
  color: var(--color-primary, #087f78);
}

.modal-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
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
  transition: all 0.15s ease;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #334155;
}

.btn-close svg {
  width: 18px;
  height: 18px;
}

.modal-body {
  padding: 1.25rem 1.5rem;
  overflow-y: auto;
  max-height: calc(90vh - 140px);
}

.alert-banner {
  display: flex;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  margin-bottom: 1.25rem;
}

.alert-icon {
  color: #dc2626;
  flex-shrink: 0;
  margin-top: 2px;
}

.alert-icon svg {
  width: 18px;
  height: 18px;
}

.alert-text strong {
  display: block;
  font-size: 0.85rem;
  color: #991b1b;
}

.alert-text p {
  margin: 0.2rem 0 0 0;
  font-size: 0.78rem;
  color: #b91c1c;
}

.metadata-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  margin-bottom: 1.25rem;
}

.meta-item {
  display: flex;
  flex-direction: column;
}

.meta-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-text-secondary, #64748b);
  text-transform: uppercase;
  margin-bottom: 0.2rem;
}

.meta-value {
  font-size: 0.85rem;
  color: var(--color-text-navy, #17243d);
}

.meta-sub {
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 0.15rem;
}

.notes-box {
  background: #f0fdfa;
  border: 1px solid #ccfbf1;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  margin-bottom: 1.25rem;
}

.notes-label {
  display: block;
  font-size: 0.74rem;
  font-weight: 700;
  color: #0f766e;
  margin-bottom: 0.2rem;
}

.notes-content {
  font-size: 0.82rem;
  color: #115e59;
  margin: 0;
}

.items-section {
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
  padding-top: 1.25rem;
}

.items-header {
  margin-bottom: 0.75rem;
}

.items-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.items-subtitle {
  font-size: 0.75rem;
  color: var(--color-text-secondary, #64748b);
}

.table-wrap {
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  overflow: hidden;
}

.detail-items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.detail-items-table th {
  background: #f8fafc;
  color: #475569;
  font-size: 0.74rem;
  font-weight: 700;
  padding: 0.65rem 0.75rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.detail-items-table td {
  padding: 0.65rem 0.75rem;
  border-bottom: 1px solid var(--color-border-soft, #f1f5f9);
}

.detail-items-table tfoot td {
  background: #f8fafc;
  padding: 0.75rem;
  border-top: 2px solid var(--color-border-soft, #cbd5e1);
}

.batch-code {
  font-family: monospace;
  font-size: 0.78rem;
  background: #f1f5f9;
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
  color: #334155;
}

.qty-pill {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  background: #eff6ff;
  color: #1d4ed8;
  border-radius: 4px;
}

.items-empty-state {
  padding: 1.5rem;
  text-align: center;
  color: #64748b;
  font-size: 0.82rem;
}

.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
  background: #f8fafc;
}

.footer-actions {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.btn-cancel,
.btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-cancel {
  background: transparent;
  border: 1px solid transparent;
  color: #64748b;
}

.btn-cancel:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-outline {
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  color: var(--color-text-navy, #17243d);
}

.btn-outline:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.btn-outline svg {
  width: 14px;
  height: 14px;
}

.text-center { text-align: center; }
.text-right { text-align: right; }
.text-primary { color: var(--color-primary, #087f78); }
.text-navy { color: var(--color-text-navy, #17243d); }
.text-muted { color: #64748b; }
.font-bold { font-weight: 700; }
.font-semibold { font-weight: 600; }

@media (max-width: 768px) {
  .metadata-grid {
    grid-template-columns: 1fr;
  }
}
</style>
