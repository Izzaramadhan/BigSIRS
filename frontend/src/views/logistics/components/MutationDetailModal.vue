<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  mutation: {
    type: Object,
    default: null
  }
});

defineEmits(['close']);

const printMutation = () => {
  window.print();
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen && mutation" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-lg">
        <!-- Header -->
        <div class="modal-header">
          <div class="header-left">
            <div class="title-row">
              <h3>{{ mutation.mutationNumber }}</h3>
              <span class="wh-badge target-badge">Tervalidasi</span>
            </div>
            <p>Rincian perpindahan stok obat antarunit farmasi</p>
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
              <span class="info-label">Gudang Asal & Tujuan</span>
              <div class="transfer-flow">
                <span class="wh-badge source-badge">{{ mutation.sourceWarehouse }}</span>
                <svg class="flow-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                  <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
                <span class="wh-badge target-badge">{{ mutation.targetWarehouse }}</span>
              </div>
            </div>

            <div class="info-card">
              <span class="info-label">Tanggal & Waktu Mutasi</span>
              <strong class="info-value text-navy">{{ mutation.dateDisplay }}</strong>
            </div>

            <div class="info-card">
              <span class="info-label">Dibuat Oleh</span>
              <div class="user-pill-large">
                <span class="user-avatar-lg">{{ mutation.createdInitials || 'AP' }}</span>
                <strong class="info-value text-navy">{{ mutation.createdBy }}</strong>
              </div>
            </div>

            <div class="info-card">
              <span class="info-label">Total Volume Mutasi</span>
              <strong class="info-value text-primary">
                {{ mutation.itemCount }} Sediaan ({{ mutation.totalQuantity.toLocaleString('id-ID') }} {{ mutation.unit }})
              </strong>
            </div>
          </div>

          <div v-if="mutation.notes" class="notes-box">
            <span class="notes-label">Catatan Keperluan:</span>
            <p class="notes-text">{{ mutation.notes }}</p>
          </div>

          <!-- Items Table -->
          <div class="detail-items-section">
            <h4 class="section-title">Daftar Item Obat / Sediaan yang Dimutasi</h4>
            <div class="items-table-container">
              <table class="detail-table">
                <thead>
                  <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th>Nama Item Obat / Sediaan</th>
                    <th style="width: 140px; text-align: right;">Kuantitas</th>
                    <th style="width: 120px;">Satuan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(it, idx) in mutation.items" :key="it.id || idx">
                    <td style="text-align: center;" class="text-muted">{{ idx + 1 }}</td>
                    <td class="font-medium text-navy">{{ it.medicineName }}</td>
                    <td style="text-align: right;" class="font-bold text-primary">{{ it.quantity.toLocaleString('id-ID') }}</td>
                    <td class="text-muted">{{ it.unit }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Grand Total Box -->
            <div class="grand-total-container">
              <div class="total-row">
                <span class="total-label">Total Kuantitas:</span>
                <span class="total-amount text-primary">{{ mutation.totalQuantity.toLocaleString('id-ID') }} {{ mutation.unit }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="button" class="btn-outline" @click="printMutation">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
              <polyline points="6 9 6 2 18 2 18 9"></polyline>
              <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
              <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>Cetak Bukti Mutasi</span>
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
  margin-bottom: 0.35rem;
}

.info-value {
  font-size: 0.95rem;
}

.transfer-flow {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.flow-arrow {
  width: 16px;
  height: 16px;
  color: var(--color-text-secondary, #64748b);
}

.wh-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  font-size: 0.76rem;
  font-weight: 600;
}

.source-badge {
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.target-badge {
  background: #ecfdf5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.user-pill-large {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-avatar-lg {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #e0f2fe;
  color: #0369a1;
  font-size: 0.7rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
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
  min-width: 550px;
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
  font-size: 1.2rem;
  font-weight: 800;
}

.text-navy {
  color: var(--color-text-navy, #17243d);
}

.text-primary {
  color: var(--color-primary, #087f78);
}

.text-muted {
  color: var(--color-text-secondary, #64748b);
}

.font-medium {
  font-weight: 500;
}

.font-bold {
  font-weight: 700;
}

@media (max-width: 640px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
}
</style>
