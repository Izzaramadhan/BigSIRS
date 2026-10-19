<script setup>
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import StockOpnameStatusBadge from './StockOpnameStatusBadge.vue';

const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  paginationInfo: {
    type: Object,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'view',
  'continue',
  'review',
  'start',
  'view-reason',
  'cancel',
  'delete',
  'print',
  'refresh',
  'page-change',
  'per-page-change',
  'reset-filter'
]);

const getRowNumber = (index) => {
  return (props.paginationInfo.from || 1) + index;
};

const handlePerPageChange = (event) => {
  emit('per-page-change', Number(event.target.value));
};
</script>

<template>
  <div class="opname-table-card card">
    <!-- Toolbar Header -->
    <div class="table-toolbar">
      <div class="toolbar-left">
        <h3 class="table-title">DAFTAR SESI AUDIT STOK</h3>
        <span class="document-badge">{{ paginationInfo.total || 0 }} Dokumen</span>
      </div>

      <div class="toolbar-right">
        <button
          type="button"
          class="btn-refresh"
          :disabled="loading"
          @click="emit('refresh')"
        >
          <svg
            :class="{ 'spin-icon': loading }"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <polyline points="23 4 23 10 17 10"></polyline>
            <polyline points="1 20 1 14 7 14"></polyline>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
          </svg>
          <span>Refresh Live</span>
        </button>
      </div>
    </div>

    <!-- Table Responsive Wrapper -->
    <div class="table-responsive">
      <table class="opname-table">
        <thead>
          <tr>
            <th class="col-no text-center">NO.</th>
            <th class="col-opname-no">NOMOR OPNAME</th>
            <th class="col-date">WAKTU MULAI</th>
            <th class="col-warehouse">GUDANG / LOKASI</th>
            <th class="col-total text-center">TOTAL ITEM</th>
            <th class="col-counted text-center">SUDAH DIHITUNG</th>
            <th class="col-difference text-center">STATUS SELISIH</th>
            <th class="col-progress">PROGRESS</th>
            <th class="col-officer">PETUGAS / PJ</th>
            <th class="col-status text-center">STATUS</th>
            <th class="col-actions text-center">AKSI</th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading Skeleton -->
          <template v-if="loading">
            <tr v-for="n in 6" :key="'skel-' + n" class="skeleton-row">
              <td class="text-center"><div class="skeleton-pill skel-no"></div></td>
              <td><div class="skeleton-line skel-wide"></div><div class="skeleton-line skel-sub"></div></td>
              <td><div class="skeleton-line skel-mid"></div></td>
              <td><div class="skeleton-line skel-wide"></div></td>
              <td class="text-center"><div class="skeleton-pill skel-num"></div></td>
              <td class="text-center"><div class="skeleton-pill skel-num"></div></td>
              <td class="text-center"><div class="skeleton-pill skel-badge"></div></td>
              <td><div class="skeleton-bar"></div></td>
              <td><div class="skeleton-line skel-mid"></div></td>
              <td class="text-center"><div class="skeleton-pill skel-badge"></div></td>
              <td class="text-center"><div class="skeleton-pill skel-btn"></div></td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else-if="items.length === 0">
            <td colspan="11" class="empty-cell">
              <MasterDataEmptyState
                :is-search="true"
                title="Belum ada sesi stok opname."
                search-message="Belum ada sesi stok opname."
              >
                <template #action>
                  <button type="button" class="btn-primary" @click="emit('reset-filter')">
                    Reset Filter
                  </button>
                </template>
              </MasterDataEmptyState>
            </td>
          </tr>

          <!-- Data Rows -->
          <tr
            v-for="(item, index) in items"
            v-else
            :key="item.id"
            :class="{ 'row-cancelled': item.status === 'Dibatalkan' }"
          >
            <!-- 1. No -->
            <td class="col-no text-center text-muted font-medium">
              {{ getRowNumber(index) }}
            </td>

            <!-- 2. Nomor Opname -->
            <td class="col-opname-no">
              <button
                type="button"
                class="opname-number-link font-bold text-primary"
                @click="emit('view', item)"
              >
                {{ item.opnameNumber }}
              </button>
              <span class="sub-text block text-muted">{{ item.title }}</span>
            </td>

            <!-- 3. Waktu Mulai -->
            <td class="col-date">
              <span class="date-bold">{{ item.dateDisplay }}</span>
              <span class="sub-text block text-muted">{{ item.timeDisplay }}</span>
            </td>

            <!-- 4. Gudang / Lokasi -->
            <td class="col-warehouse">
              <span class="warehouse-name font-bold text-navy">{{ item.warehouse }}</span>
              <span class="sub-text block text-muted">{{ item.locationDetail }}</span>
            </td>

            <!-- 5. Total Item -->
            <td class="col-total text-center font-bold text-navy">
              {{ item.totalItems }}
            </td>

            <!-- 6. Sudah Dihitung -->
            <td class="col-counted text-center">
              <div v-if="item.countedItems !== null" class="counted-container">
                <span class="counted-pill">{{ item.countedItems }}</span>
                <span class="counted-divider">/</span>
                <span class="counted-total">{{ item.totalItems }}</span>
              </div>
              <span v-else class="text-muted">-</span>
            </td>

            <!-- 7. Status Selisih -->
            <td class="col-difference text-center">
              <StockOpnameStatusBadge
                v-if="item.differenceStatus && item.differenceStatus !== '-'"
                type="difference"
                :status="item.differenceStatus"
                :variant="item.differenceType"
              />
              <span v-else class="text-muted">-</span>
            </td>

            <!-- 8. Progress -->
            <td class="col-progress">
              <div v-if="item.status === 'Dibatalkan'" class="text-cancelled font-semibold">
                Dibatalkan
              </div>
              <div v-else class="progress-wrapper">
                <div class="progress-label-row">
                  <span class="progress-label">{{ item.progressText }}</span>
                </div>
                <div class="progress-track">
                  <div
                    class="progress-fill"
                    :class="{
                      'fill-teal': item.progressPercent > 0 && item.progressPercent < 100,
                      'fill-green': item.progressPercent === 100 && item.status === 'Selesai',
                      'fill-amber': item.progressPercent === 100 && item.differenceStatus.includes('Adjusted')
                    }"
                    :style="{ width: item.progressPercent + '%' }"
                  ></div>
                </div>
              </div>
            </td>

            <!-- 9. Petugas / PJ -->
            <td class="col-officer">
              <span class="officer-name font-semibold text-navy">{{ item.officerName }}</span>
              <span class="sub-text block text-muted">{{ item.officerRole }}</span>
            </td>

            <!-- 10. Status -->
            <td class="col-status text-center">
              <StockOpnameStatusBadge
                type="session"
                :status="item.status"
              />
            </td>

            <!-- 11. Aksi -->
            <td class="col-actions text-center">
              <div class="action-buttons-group">
                <!-- Status: Sedang Dihitung -->
                <template v-if="item.status === 'Sedang Dihitung'">
                  <button
                    type="button"
                    class="btn-row-action btn-continue"
                    title="Lanjutkan Penghitungan Fisik"
                    @click="emit('continue', item)"
                  >
                    <span>Lanjutkan</span>
                  </button>
                  <button
                    type="button"
                    class="btn-icon-subtle"
                    title="Batalkan Sesi"
                    aria-label="Batalkan Sesi"
                    @click="emit('cancel', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="18" y1="6" x2="6" y2="18"></line>
                      <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                  </button>
                </template>

                <!-- Status: Menunggu Peninjauan -->
                <template v-else-if="item.status === 'Menunggu Peninjauan'">
                  <button
                    type="button"
                    class="btn-row-action btn-review"
                    title="Tinjau Selisih Stok"
                    @click="emit('review', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    <span>Tinjau Selisih</span>
                  </button>
                  <button
                    type="button"
                    class="btn-icon-subtle"
                    title="Batalkan Sesi"
                    aria-label="Batalkan Sesi"
                    @click="emit('cancel', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="18" y1="6" x2="6" y2="18"></line>
                      <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                  </button>
                </template>

                <!-- Status: Selesai -->
                <template v-else-if="item.status === 'Selesai'">
                  <button
                    type="button"
                    class="btn-row-action btn-detail"
                    title="Lihat Detail Hasil Audit"
                    @click="emit('view', item)"
                  >
                    <span>Lihat Detail</span>
                  </button>
                  <button
                    type="button"
                    class="btn-icon-subtle text-primary"
                    title="Cetak Berita Acara"
                    aria-label="Cetak Berita Acara"
                    @click="emit('print', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="6 9 6 2 18 2 18 9"></polyline>
                      <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                      <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                  </button>
                </template>

                <!-- Status: Draft Sesi -->
                <template v-else-if="item.status === 'Draft Sesi'">
                  <button
                    type="button"
                    class="btn-row-action btn-start"
                    title="Mulai Penghitungan"
                    @click="emit('start', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <span>Mulai Hitung</span>
                  </button>
                  <button
                    type="button"
                    class="btn-icon-subtle text-muted"
                    title="Hapus Draft"
                    aria-label="Hapus Draft"
                    @click="emit('delete', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                  </button>
                </template>

                <!-- Status: Dibatalkan -->
                <template v-else-if="item.status === 'Dibatalkan'">
                  <button
                    type="button"
                    class="btn-row-action btn-reason"
                    title="Lihat Alasan Pembatalan"
                    @click="emit('view-reason', item)"
                  >
                    <span>Detail Alasan</span>
                  </button>
                </template>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Table Footer / Pagination -->
    <div class="table-pagination-wrapper">
      <div class="pagination-info-summary">
        Menampilkan {{ paginationInfo.from || 0 }}–{{ paginationInfo.to || 0 }} dari {{ paginationInfo.total || 0 }} sesi audit
      </div>

      <div class="pagination-right-controls">
        <div class="per-page-selector">
          <label for="per-page-select">Per Halaman:</label>
          <select
            id="per-page-select"
            :value="paginationInfo.per_page"
            class="per-page-input"
            @change="handlePerPageChange"
          >
            <option :value="10">10 data</option>
            <option :value="25">25 data</option>
            <option :value="50">50 data</option>
          </select>
        </div>

        <MasterDataPagination
          :pagination="paginationInfo"
          :loading="loading"
          :item-count="items.length"
          @page-change="emit('page-change', $event)"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.opname-table-card {
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
  overflow: hidden;
}

.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--color-border-soft, #e8edf3);
  flex-wrap: wrap;
  gap: 1rem;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.table-title {
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.document-badge {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.85rem;
  font-size: 0.78rem;
  font-weight: 600;
  border-radius: 6px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  color: var(--color-text-navy, #17243d);
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-refresh:hover:not(:disabled) {
  background: #f8fafc;
  border-color: var(--color-primary, #087f78);
  color: var(--color-primary, #087f78);
}

.btn-refresh svg {
  width: 14px;
  height: 14px;
}

.spin-icon {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.opname-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
  text-align: left;
  white-space: nowrap;
}

.opname-table th {
  background: #f8fafc;
  color: #475569;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  padding: 0.85rem 0.9rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.opname-table td {
  padding: 0.85rem 0.9rem;
  border-bottom: 1px solid var(--color-border-soft, #f1f5f9);
  vertical-align: middle;
}

.opname-table tbody tr:hover {
  background: #f8fafc;
}

.row-cancelled {
  opacity: 0.6;
  background: #fafafa;
}

.opname-number-link {
  background: none;
  border: none;
  padding: 0;
  font-size: 0.82rem;
  color: var(--color-primary, #087f78);
  text-decoration: none;
  cursor: pointer;
  text-align: left;
}

.opname-number-link:hover {
  text-decoration: underline;
}

.sub-text {
  font-size: 0.72rem;
  margin-top: 0.15rem;
}

.block {
  display: block;
}

.date-bold {
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.warehouse-name {
  font-size: 0.82rem;
}

.counted-container {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.counted-pill {
  display: inline-block;
  padding: 0.15rem 0.45rem;
  background: #e0f2fe;
  color: #0369a1;
  font-weight: 700;
  border-radius: 4px;
}

.counted-divider {
  color: #94a3b8;
}

.counted-total {
  color: #64748b;
  font-weight: 500;
}

.progress-wrapper {
  min-width: 120px;
}

.progress-label-row {
  margin-bottom: 0.25rem;
}

.progress-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: #475569;
}

.progress-track {
  width: 100%;
  height: 6px;
  background: #e2e8f0;
  border-radius: 9999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.3s ease;
}

.fill-teal {
  background: var(--color-primary, #087f78);
}

.fill-green {
  background: #10b981;
}

.fill-amber {
  background: #f59e0b;
}

.text-cancelled {
  color: #dc2626;
  font-size: 0.78rem;
}

.officer-name {
  font-size: 0.8rem;
}

.action-buttons-group {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-row-action {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.35rem 0.7rem;
  font-size: 0.74rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.btn-row-action svg {
  width: 13px;
  height: 13px;
}

.btn-continue {
  background: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  color: #ffffff;
}

.btn-continue:hover {
  background: #066761;
}

.btn-review {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
}

.btn-review:hover {
  background: #dbeafe;
}

.btn-detail {
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  color: var(--color-text-navy, #17243d);
}

.btn-detail:hover {
  background: #f8fafc;
  border-color: var(--color-primary, #087f78);
  color: var(--color-primary, #087f78);
}

.btn-start {
  background: #ffffff;
  border: 1px solid var(--color-primary, #087f78);
  color: var(--color-primary, #087f78);
}

.btn-start:hover {
  background: var(--color-primary-light, #dff7f4);
}

.btn-reason {
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  color: #64748b;
}

.btn-reason:hover {
  background: #f1f5f9;
  color: #334155;
}

.btn-icon-subtle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: none;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-icon-subtle:hover {
  background: #f1f5f9;
  color: #334155;
}

.btn-icon-subtle svg {
  width: 14px;
  height: 14px;
}

.empty-cell {
  padding: 3rem 1rem;
  text-align: center;
}

/* Skeleton Loading */
.skeleton-row td {
  padding: 1rem 0.9rem;
}

.skeleton-pill,
.skeleton-line,
.skeleton-bar {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: skeletonPulse 1.5s infinite;
  border-radius: 4px;
}

@keyframes skeletonPulse {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.skel-no { width: 24px; height: 16px; margin: 0 auto; }
.skel-wide { width: 140px; height: 14px; margin-bottom: 4px; }
.skel-mid { width: 100px; height: 14px; }
.skel-sub { width: 80px; height: 10px; }
.skel-num { width: 36px; height: 16px; margin: 0 auto; }
.skel-badge { width: 80px; height: 22px; border-radius: 9999px; margin: 0 auto; }
.skel-bar { width: 100px; height: 8px; border-radius: 9999px; }
.skel-btn { width: 70px; height: 26px; border-radius: 6px; margin: 0 auto; }

/* Table Pagination Wrapper */
.table-pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 1.25rem;
  border-top: 1px solid var(--color-border-soft, #e8edf3);
  flex-wrap: wrap;
  gap: 1rem;
  background: #ffffff;
}

.pagination-info-summary {
  font-size: 0.82rem;
  color: var(--color-text-secondary, #64748b);
}

.pagination-right-controls {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  flex-wrap: wrap;
}

.per-page-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.82rem;
  color: var(--color-text-secondary, #64748b);
}

.per-page-input {
  padding: 0.25rem 0.5rem;
  font-size: 0.8rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  background: #ffffff;
  color: var(--color-text-navy, #17243d);
}

/* Utilities */
.text-center { text-align: center; }
.text-primary { color: var(--color-primary, #087f78); }
.text-navy { color: var(--color-text-navy, #17243d); }
.text-muted { color: var(--color-text-secondary, #64748b); }
.font-bold { font-weight: 700; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
</style>
