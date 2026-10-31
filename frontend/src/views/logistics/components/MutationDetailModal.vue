<script setup>
import { computed } from 'vue';

const props = defineProps({
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

const formatRupiah = (val) => {
  return 'Rp' + Number(val || 0).toLocaleString('id-ID');
};

const totalValue = computed(() => {
  if (props.mutation?.totalValue !== undefined && props.mutation?.totalValue !== null) {
    return Number(props.mutation.totalValue);
  }
  if (!props.mutation?.items) return 0;
  return props.mutation.items.reduce((sum, it) => {
    const sub = it.subtotal !== undefined && it.subtotal !== null
      ? Number(it.subtotal)
      : (Number(it.quantity) || 0) * (Number(it.unitPrice) || 0);
    return sum + sub;
  }, 0);
});
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen && mutation" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content mutation-detail-modal">
        <!-- Header -->
        <div class="modal-header">
          <div class="header-left">
            <div class="title-row">
              <h3 class="modal-title">{{ mutation.mutationNumber }}</h3>
              <span class="wh-badge target-badge">Tervalidasi</span>
            </div>
            <p class="modal-subtitle">Rincian data dan sediaan mutasi barang antarunit farmasi</p>
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
          <!-- 1. Bagian DATA MUTASI -->
          <div class="detail-section-card card">
            <div class="section-card-header">
              <div class="section-title-wrap">
                <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="3" width="7" height="7"></rect>
                  <rect x="14" y="3" width="7" height="7"></rect>
                  <rect x="14" y="14" width="7" height="7"></rect>
                  <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <h4 class="section-title">Data Mutasi</h4>
              </div>
            </div>

            <div class="section-card-body">
              <div class="detail-grid-2col">
                <!-- Left Column: Tanggal Mutasi & Catatan -->
                <div class="detail-col">
                  <div class="detail-field">
                    <span class="field-label">Tanggal Mutasi</span>
                    <strong class="field-value text-navy">{{ mutation.dateDisplay || '-' }}</strong>
                  </div>

                  <div class="detail-field">
                    <span class="field-label">Catatan</span>
                    <p class="field-value text-muted notes-para">{{ mutation.notes || '-' }}</p>
                  </div>
                </div>

                <!-- Right Column: Gudang Asal & Gudang Tujuan -->
                <div class="detail-col">
                  <div class="detail-field">
                    <span class="field-label">Gudang Asal</span>
                    <div>
                      <span class="wh-badge source-badge font-semibold">
                        {{ mutation.sourceWarehouse || '-' }}
                      </span>
                    </div>
                  </div>

                  <div class="detail-field">
                    <span class="field-label">Gudang Tujuan</span>
                    <div>
                      <span class="wh-badge target-badge font-semibold">
                        {{ mutation.targetWarehouse || '-' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Bagian LIST SEDIAAN -->
          <div class="detail-section-card card">
            <div class="section-card-header">
              <div class="section-title-wrap">
                <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="8" y1="6" x2="21" y2="6"></line>
                  <line x1="8" y1="12" x2="21" y2="12"></line>
                  <line x1="8" y1="18" x2="21" y2="18"></line>
                  <line x1="3" y1="6" x2="3.01" y2="6"></line>
                  <line x1="3" y1="12" x2="3.01" y2="12"></line>
                  <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <h4 class="section-title">List Sediaan</h4>
              </div>
            </div>

            <div class="section-card-body p-0">
              <div class="detail-table-container">
                <table class="detail-table">
                  <thead>
                    <tr>
                      <th class="col-name">Nama</th>
                      <th class="col-unit">Satuan</th>
                      <th class="col-qty text-center">Jumlah</th>
                      <th class="col-price text-right">Harga</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!mutation.items || mutation.items.length === 0">
                      <td colspan="4" class="text-center py-4 text-muted">
                        Tidak ada item sediaan tercatat.
                      </td>
                    </tr>
                    <tr v-else v-for="(it, idx) in mutation.items" :key="it.id || idx">
                      <!-- 1. Nama -->
                      <td class="col-name">
                        <strong class="text-navy font-semibold">{{ it.medicineName }}</strong>
                        <span v-if="it.medicineCode" class="item-code-helper text-muted">
                          {{ it.medicineCode }}
                        </span>
                      </td>

                      <!-- 2. Satuan -->
                      <td class="col-unit text-muted font-medium">
                        {{ it.unit || 'Unit' }}
                      </td>

                      <!-- 3. Jumlah -->
                      <td class="col-qty text-center font-bold text-navy">
                        {{ (Number(it.quantity) || 0).toLocaleString('id-ID') }}
                      </td>

                      <!-- 4. Harga (Subtotal) -->
                      <td class="col-price text-right font-bold text-navy">
                        {{ formatRupiah(it.subtotal ?? ((Number(it.quantity) || 0) * (Number(it.unitPrice) || 0))) }}
                      </td>
                    </tr>
                  </tbody>
                  <!-- Total Nilai Sediaan Footer Row -->
                  <tfoot v-if="mutation.items && mutation.items.length > 0">
                    <tr class="total-row">
                      <td colspan="3" class="total-label text-right font-bold text-navy">
                        TOTAL NILAI SEDIAAN
                      </td>
                      <td class="total-value text-right font-bold text-primary">
                        {{ formatRupiah(totalValue) }}
                      </td>
                    </tr>
                  </tfoot>
                </table>
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
          <button type="button" class="btn-secondary" @click="$emit('close')">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.mutation-detail-modal {
  background: #ffffff;
  border-radius: 14px;
  width: 100%;
  max-width: 860px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.modal-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-primary, #087f78);
  margin: 0;
}

.modal-subtitle {
  font-size: 0.82rem;
  color: var(--color-text-secondary, #64748b);
  margin: 0.2rem 0 0 0;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  flex-shrink: 0;
  background: #f8fafc;
}

/* Detail Section Cards */
.detail-section-card {
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 10px;
  overflow: hidden;
}

.section-card-header {
  padding: 0.85rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft, #e8edf3);
}

.section-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-icon {
  width: 17px;
  height: 17px;
  color: var(--color-primary, #087f78);
}

.section-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.section-card-body {
  padding: 1.25rem;
}

.detail-grid-2col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem 2rem;
}

.detail-col {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.field-label {
  font-size: 0.76rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-secondary, #64748b);
}

.field-value {
  font-size: 0.88rem;
}

.notes-para {
  margin: 0;
  line-height: 1.45;
  white-space: pre-wrap;
}

/* Table */
.detail-table-container {
  width: 100%;
  overflow-x: auto;
}

.detail-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.84rem;
}

.detail-table th {
  background: #f8fafc;
  padding: 0.75rem 1rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--color-text-secondary, #64748b);
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  text-align: left;
}

.detail-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #e8edf3);
  vertical-align: middle;
}

.col-name { min-width: 200px; }
.col-unit { width: 100px; }
.col-qty { width: 100px; }
.col-price { width: 150px; }

.item-code-helper {
  display: block;
  font-size: 0.72rem;
  margin-top: 0.15rem;
}

.total-row td {
  background: #f8fafc;
  padding: 0.95rem 1rem;
  border-top: 2px solid var(--color-border-soft, #e2e8f0);
}

.total-label {
  font-size: 0.82rem;
  letter-spacing: 0.04em;
}

.total-value {
  font-size: 0.98rem;
}

/* Badges */
.wh-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  font-size: 0.78rem;
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

@media (max-width: 768px) {
  .detail-grid-2col {
    grid-template-columns: 1fr;
  }
}
</style>
