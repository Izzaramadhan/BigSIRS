<script setup>
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import GoodsIssueStatusBadge from './GoodsIssueStatusBadge.vue';

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
  'edit',
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
  <div class="issue-table-card card">
    <!-- Toolbar Header -->
    <div class="table-toolbar">
      <div class="toolbar-left">
        <h3 class="table-title">DAFTAR PENGELUARAN NONPENJUALAN</h3>
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
      <table class="issue-table">
        <thead>
          <tr>
            <th class="col-no text-center">NO</th>
            <th class="col-issue-no">NO. PENGELUARAN</th>
            <th class="col-date">TANGGAL & WAKTU</th>
            <th class="col-warehouse">GUDANG ASAL</th>
            <th class="col-officer">PETUGAS OTORISASI</th>
            <th class="col-purpose">KEPERLUAN</th>
            <th class="col-item-count text-center">JENIS ITEM</th>
            <th class="col-qty">TOTAL KUANTITAS</th>
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
              <td><div class="skeleton-line skel-mid"></div><div class="skeleton-line skel-sub"></div></td>
              <td><div class="skeleton-line skel-wide"></div><div class="skeleton-line skel-sub"></div></td>
              <td><div class="skeleton-line skel-mid"></div><div class="skeleton-line skel-sub"></div></td>
              <td><div class="skeleton-pill skel-badge"></div></td>
              <td class="text-center"><div class="skeleton-pill skel-num"></div></td>
              <td><div class="skeleton-line skel-mid"></div></td>
              <td class="text-center"><div class="skeleton-pill skel-badge"></div></td>
              <td class="text-center"><div class="skeleton-pill skel-btn"></div></td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else-if="items.length === 0">
            <td colspan="10" class="empty-cell">
              <MasterDataEmptyState
                :is-search="true"
                title="Data pengeluaran barang tidak ditemukan"
                search-message="Data pengeluaran barang tidak ditemukan. Silakan sesuaikan kriteria filter atau reset filter."
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

            <!-- 2. No. Pengeluaran -->
            <td class="col-issue-no">
              <button
                type="button"
                class="issue-number-link font-bold text-primary"
                @click="emit('view', item)"
              >
                {{ item.issueNumber }}
              </button>
              <span
                v-if="item.validationNote"
                class="sub-text block text-danger font-semibold"
              >
                {{ item.validationNote }}
              </span>
              <span v-else-if="item.referenceNumber" class="sub-text block text-muted">
                {{ item.referenceNumber }}
              </span>
            </td>

            <!-- 3. Tanggal & Waktu -->
            <td class="col-date">
              <span class="date-bold">{{ item.dateDisplay }}</span>
              <span class="sub-text block text-muted">{{ item.timeDisplay }}</span>
            </td>

            <!-- 4. Gudang Asal -->
            <td class="col-warehouse">
              <span class="warehouse-name font-bold text-navy">{{ item.warehouse }}</span>
              <span class="sub-text block text-muted">{{ item.locationDetail }}</span>
            </td>

            <!-- 5. Petugas Otorisasi -->
            <td class="col-officer">
              <span class="officer-name font-semibold text-navy">{{ item.officerName }}</span>
              <span class="sub-text block text-muted">{{ item.officerRole }}</span>
            </td>

            <!-- 6. Keperluan -->
            <td class="col-purpose">
              <GoodsIssueStatusBadge
                type="purpose"
                :value="item.purpose"
              />
            </td>

            <!-- 7. Jenis Item -->
            <td class="col-item-count text-center">
              <div class="item-count-box">
                <span class="item-type-pill">{{ item.itemCount }}</span>
                <span class="item-type-text">Jenis</span>
              </div>
            </td>

            <!-- 8. Total Kuantitas -->
            <td class="col-qty">
              <span class="qty-bold font-bold text-navy">
                {{ item.totalQuantity }} {{ item.quantityUnit }}
              </span>
              <span v-if="item.quantitySubtitle" class="sub-text block text-muted">
                {{ item.quantitySubtitle }}
              </span>
            </td>

            <!-- 9. Status -->
            <td class="col-status text-center">
              <GoodsIssueStatusBadge
                type="status"
                :value="item.status"
              />
            </td>

            <!-- 10. Aksi -->
            <td class="col-actions text-center">
              <div class="action-buttons-group">
                <!-- Status: Selesai -->
                <template v-if="item.status === 'Selesai'">
                  <button
                    type="button"
                    class="btn-icon-action"
                    title="Lihat Detail Pengeluaran"
                    aria-label="Lihat Detail"
                    @click="emit('view', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="btn-icon-action"
                    title="Cetak Dokumen Pengeluaran"
                    aria-label="Cetak"
                    @click="emit('print', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="6 9 6 2 18 2 18 9"></polyline>
                      <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                      <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                  </button>
                </template>

                <!-- Status: Draft -->
                <template v-else-if="item.status === 'Draft'">
                  <button
                    type="button"
                    class="btn-icon-action"
                    title="Lihat Detail Pengeluaran"
                    aria-label="Lihat Detail"
                    @click="emit('view', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="btn-icon-action text-primary"
                    title="Edit Draft Pengeluaran"
                    aria-label="Edit Draft"
                    @click="emit('edit', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="btn-icon-action text-danger"
                    title="Hapus / Batalkan Draft"
                    aria-label="Hapus Draft"
                    @click="emit('delete', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      <line x1="10" y1="11" x2="10" y2="17"></line>
                      <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                  </button>
                </template>

                <!-- Status: Dibatalkan -->
                <template v-else-if="item.status === 'Dibatalkan'">
                  <button
                    type="button"
                    class="btn-icon-action"
                    title="Lihat Detail Pembatalan"
                    aria-label="Lihat Detail"
                    @click="emit('view', item)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
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
        Menampilkan {{ paginationInfo.from || 0 }}–{{ paginationInfo.to || 0 }} dari {{ paginationInfo.total || 0 }} dokumen pengeluaran
      </div>

      <div class="pagination-right-controls">
        <div class="per-page-selector">
          <label for="issue-per-page-select">Tampilkan</label>
          <select
            id="issue-per-page-select"
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
.issue-table-card {
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

.issue-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
  text-align: left;
  white-space: nowrap;
}

.issue-table th {
  background: #f8fafc;
  color: #475569;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  padding: 0.85rem 0.9rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.issue-table td {
  padding: 0.85rem 0.9rem;
  border-bottom: 1px solid var(--color-border-soft, #f1f5f9);
  vertical-align: middle;
}

.issue-table tbody tr:hover {
  background: #f8fafc;
}

.row-cancelled {
  opacity: 0.6;
  background: #fafafa;
}

.issue-number-link {
  background: none;
  border: none;
  padding: 0;
  font-size: 0.82rem;
  color: var(--color-primary, #087f78);
  text-decoration: none;
  cursor: pointer;
  text-align: left;
}

.issue-number-link:hover {
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

.officer-name {
  font-size: 0.8rem;
}

.item-count-box {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
}

.item-type-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.15rem 0.45rem;
  background: #eff6ff;
  color: #2563eb;
  font-weight: 700;
  border-radius: 4px;
  font-size: 0.75rem;
}

.item-type-text {
  font-size: 0.74rem;
  color: #64748b;
}

.qty-bold {
  font-size: 0.82rem;
}

.action-buttons-group {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.btn-icon-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  background: #ffffff;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-icon-action:hover {
  background: #f8fafc;
  color: var(--color-primary, #087f78);
  border-color: var(--color-primary, #087f78);
}

.btn-icon-action.text-primary:hover {
  color: #066761;
}

.btn-icon-action.text-danger:hover {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #dc2626;
}

.btn-icon-action svg {
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
.skeleton-line {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: skeletonPulse 1.5s infinite;
  border-radius: 4px;
}

@keyframes skeletonPulse {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.skel-no { width: 20px; height: 16px; margin: 0 auto; }
.skel-wide { width: 140px; height: 14px; margin-bottom: 4px; }
.skel-mid { width: 100px; height: 14px; margin-bottom: 4px; }
.skel-sub { width: 80px; height: 10px; }
.skel-num { width: 32px; height: 16px; margin: 0 auto; }
.skel-badge { width: 90px; height: 22px; border-radius: 9999px; margin: 0 auto; }
.skel-btn { width: 50px; height: 26px; border-radius: 6px; margin: 0 auto; }

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
.text-danger { color: #dc2626; }
.font-bold { font-weight: 700; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
</style>
