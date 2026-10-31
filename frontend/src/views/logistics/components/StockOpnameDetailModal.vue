<script setup>
import { computed } from 'vue';
import StockOpnameStatusBadge from './StockOpnameStatusBadge.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  opname: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'approve', 'print', 'submit-review', 'update-item']);

const isCountingSession = computed(() => {
  return props.opname?.status === 'Sedang Dihitung' || props.opname?.rawStatus === 'counting';
});

const canSubmitReview = computed(() => {
  if (!props.opname?.items || props.opname.items.length === 0) return false;
  return props.opname.items.every(
    (it) => it.physicalStock !== null && it.physicalStock !== undefined
  );
});

const handlePhysicalStockChange = (it, val) => {
  const num = val === '' ? null : Math.max(0, Number(val));
  it.physicalStock = num;
  if (num !== null) {
    it.difference = num - it.systemStock;
    it.differenceValue = it.difference * (it.unitPrice || 0);
  } else {
    it.difference = null;
    it.differenceValue = null;
  }
  emit('update-item', { opname: props.opname, item: it });
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen && opname" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-lg">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <div class="header-badge-row">
              <span class="opname-number font-bold text-primary">{{ opname.opnameNumber }}</span>
              <StockOpnameStatusBadge type="session" :status="opname.status" />
              <StockOpnameStatusBadge
                v-if="opname.differenceStatus && opname.differenceStatus !== '-'"
                type="difference"
                :status="opname.differenceStatus"
                :variant="opname.differenceType"
              />
            </div>
            <h3 class="modal-title">{{ opname.title }}</h3>
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
          <!-- Cancellation Banner if Cancelled -->
          <div v-if="opname.status === 'Dibatalkan'" class="cancel-banner">
            <div class="cancel-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
            </div>
            <div class="cancel-text">
              <strong>Sesi Audit Telah Dibatalkan</strong>
              <p>{{ opname.cancelReason || opname.notes || 'Sesi dibatalkan oleh petugas logistik.' }}</p>
            </div>
          </div>

          <!-- Session Metadata Grid -->
          <div class="metadata-grid">
            <div class="meta-item">
              <span class="meta-label">Waktu Mulai</span>
              <span class="meta-value">{{ opname.dateDisplay }}, {{ opname.timeDisplay }}</span>
            </div>

            <div class="meta-item">
              <span class="meta-label">Gudang / Lokasi</span>
              <span class="meta-value font-bold text-navy">{{ typeof opname.warehouse === 'object' && opname.warehouse !== null ? opname.warehouse.name : opname.warehouse }}</span>
              <span class="meta-sub">{{ opname.locationDetail }}</span>
            </div>

            <div class="meta-item">
              <span class="meta-label">Jenis Audit</span>
              <span class="meta-value">{{ opname.type }}</span>
            </div>

            <div class="meta-item">
              <span class="meta-label">Petugas / Penanggung Jawab</span>
              <span class="meta-value">{{ opname.officerName }}</span>
              <span class="meta-sub">{{ opname.officerRole }}</span>
            </div>

            <div class="meta-item">
              <span class="meta-label">Cakupan Item</span>
              <span class="meta-value font-bold text-navy">{{ opname.totalItems }} Item</span>
              <span class="meta-sub">Sudah Dihitung: {{ opname.countedItems !== null ? opname.countedItems : '-' }}</span>
            </div>

            <div class="meta-item">
              <span class="meta-label">Progress Audit</span>
              <span class="meta-value font-semibold">{{ opname.progressText }}</span>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="opname.notes" class="notes-box">
            <span class="notes-label">Catatan Audit:</span>
            <p class="notes-content">{{ opname.notes }}</p>
          </div>

          <!-- Discrepancy Items List -->
          <div class="items-section">
            <div class="items-header">
              <h4 class="items-title">Rincian Fisik & Selisih Obat</h4>
              <span class="items-subtitle">Daftar item sediaan farmasi yang telah diperiksa</span>
            </div>

            <div class="table-wrap">
              <table v-if="opname.items && opname.items.length > 0" class="items-table">
                <thead>
                  <tr>
                    <th>No.</th>
                    <th>Nama Obat / Sediaan</th>
                    <th class="text-center">Satuan</th>
                    <th class="text-center">Stok Sistem</th>
                    <th class="text-center">Hitung Fisik</th>
                    <th class="text-center">Selisih</th>
                    <th class="text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(it, idx) in opname.items" :key="it.id || idx">
                    <td class="text-center text-muted">{{ idx + 1 }}</td>
                    <td class="font-bold text-navy">
                      <div>{{ it.name || it.medicineName || it.medicine_name }}</div>
                      <span v-if="it.batchNumber || it.expiredDate" class="item-batch-sub text-muted font-normal block">
                        Batch: {{ it.batchNumber || '-' }} • Exp: {{ it.expiredDate || '-' }}
                      </span>
                    </td>
                    <td class="text-center">{{ it.unit }}</td>
                    <td class="text-center font-semibold">{{ it.systemStock }}</td>
                    <td class="text-center">
                      <div v-if="isCountingSession" class="physical-input-wrap">
                        <input
                          type="number"
                          min="0"
                          :value="it.physicalStock"
                          class="form-input physical-input font-bold text-primary text-center"
                          placeholder="0"
                          @change="(e) => handlePhysicalStockChange(it, e.target.value)"
                        />
                      </div>
                      <span v-else class="font-bold text-primary">
                        {{ it.physicalStock !== null && it.physicalStock !== undefined ? it.physicalStock : '-' }}
                      </span>
                    </td>
                    <td class="text-center">
                      <span
                        v-if="it.difference !== null && it.difference !== undefined"
                        class="diff-val font-bold"
                        :class="{
                          'text-danger': it.difference < 0,
                          'text-warning': it.difference > 0,
                          'text-success': it.difference === 0
                        }"
                      >
                        {{ it.difference > 0 ? `+${it.difference}` : it.difference }}
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td class="text-center">
                      <span
                        v-if="it.difference === 0"
                        class="badge-item-match"
                      >
                        Match
                      </span>
                      <span
                        v-else-if="it.difference !== null && it.difference !== undefined"
                        class="badge-item-diff"
                        :class="it.difference < 0 ? 'badge-minus' : 'badge-plus'"
                      >
                        {{ it.difference < 0 ? 'Kurang' : 'Lebih' }}
                      </span>
                      <span v-else class="text-muted text-xs">-</span>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-else class="items-empty-state">
                <p>Belum ada rincian item individual yang dimasukkan pada sesi ini.</p>
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
            <!-- Action: Ajukan Peninjauan when counting -->
            <button
              v-if="opname.status === 'Sedang Dihitung' || opname.rawStatus === 'counting'"
              type="button"
              class="btn-primary"
              :disabled="!canSubmitReview"
              :title="!canSubmitReview ? 'Seluruh item harus dihitung sebelum peninjauan' : 'Ajukan Peninjauan'"
              @click="emit('submit-review', opname)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 11 12 14 22 4"></polyline>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
              </svg>
              <span>Ajukan Peninjauan</span>
            </button>

            <!-- Action: Setujui when in review -->
            <button
              v-if="opname.status === 'Menunggu Peninjauan' || opname.rawStatus === 'review'"
              type="button"
              class="btn-primary"
              @click="emit('approve', opname)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              <span>Setujui Penyesuaian Saldo</span>
            </button>

            <button
              type="button"
              class="btn-outline"
              @click="emit('print', opname)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
              </svg>
              <span>Cetak Berita Acara</span>
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

.opname-number {
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

.cancel-banner {
  display: flex;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  margin-bottom: 1.25rem;
}

.cancel-icon {
  color: #dc2626;
  flex-shrink: 0;
  margin-top: 2px;
}

.cancel-icon svg {
  width: 18px;
  height: 18px;
}

.cancel-text strong {
  display: block;
  font-size: 0.85rem;
  color: #991b1b;
}

.cancel-text p {
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

.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.items-table th {
  background: #f8fafc;
  color: #475569;
  font-size: 0.74rem;
  font-weight: 700;
  padding: 0.65rem 0.75rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.items-table td {
  padding: 0.65rem 0.75rem;
  border-bottom: 1px solid var(--color-border-soft, #f1f5f9);
}

.text-center { text-align: center; }
.text-primary { color: var(--color-primary, #087f78); }
.text-navy { color: var(--color-text-navy, #17243d); }
.text-muted { color: #64748b; }
.text-danger { color: #dc2626; }
.text-warning { color: #d97706; }
.text-success { color: #16a34a; }
.font-bold { font-weight: 700; }
.font-semibold { font-weight: 600; }

.badge-item-match {
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  background: #dcfce7;
  color: #15803d;
  font-size: 0.7rem;
  font-weight: 700;
}

.badge-item-diff {
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  font-weight: 700;
}

.badge-minus {
  background: #fee2e2;
  color: #b91c1c;
}

.badge-plus {
  background: #fef3c7;
  color: #b45309;
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
.btn-outline,
.btn-primary {
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

.btn-primary {
  background: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  color: #ffffff;
}

.btn-primary:hover {
  background: #066761;
}

.btn-primary svg,
.btn-outline svg {
  width: 14px;
  height: 14px;
}

.physical-input {
  width: 90px;
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  font-weight: 700;
  text-align: center;
  border: 1.5px solid var(--color-primary, #087f78);
  border-radius: 6px;
  background: #f0fdf4;
}

.item-batch-sub {
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 0.15rem;
}

@media (max-width: 768px) {
  .metadata-grid {
    grid-template-columns: 1fr;
  }
}
</style>
