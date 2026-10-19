<script setup>
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import PosSaleStatusBadge from './PosSaleStatusBadge.vue';

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
  },
  tableState: {
    type: String,
    default: 'normal' // 'normal' | 'skeleton' | 'empty' | 'error'
  },
  errorMessage: {
    type: String,
    default: ''
  }
});

const emit = defineEmits([
  'view',
  'edit',
  'void',
  'delete',
  'print',
  'info-void',
  'refresh',
  'page-change',
  'per-page-change',
  'set-table-state',
  'retry',
  'reset-filter'
]);

const getRowNumber = (index) => {
  return (props.paginationInfo.from || 1) + index;
};

const formatRupiah = (val) => {
  if (val == null) return 'Rp 0';
  return 'Rp ' + Number(val).toLocaleString('id-ID');
};

const handlePerPageChange = (event) => {
  emit('per-page-change', Number(event.target.value));
};
</script>

<template>
  <div class="pos-table-container">
    <!-- Live State Switcher Bar (Frontend Simulation Aid) -->
    <div class="live-state-bar">
      <div class="state-bar-left">
        <svg class="state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="2" y1="12" x2="22" y2="12"></line>
          <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
        </svg>
        <span class="state-label">Simulasi Tampilan Tabel (Live State Switcher):</span>
      </div>
      <div class="state-buttons">
        <button
          type="button"
          class="state-btn"
          :class="{ active: tableState === 'normal' }"
          @click="emit('set-table-state', 'normal')"
        >
          Normal (Default)
        </button>
        <button
          type="button"
          class="state-btn"
          :class="{ active: tableState === 'skeleton' }"
          @click="emit('set-table-state', 'skeleton')"
        >
          Skeleton Loading
        </button>
        <button
          type="button"
          class="state-btn"
          :class="{ active: tableState === 'empty' }"
          @click="emit('set-table-state', 'empty')"
        >
          Empty State
        </button>
        <button
          type="button"
          class="state-btn"
          :class="{ active: tableState === 'error' }"
          @click="emit('set-table-state', 'error')"
        >
          Error State
        </button>
      </div>
    </div>

    <!-- Main Table Card -->
    <div class="pos-table-card card">
      <!-- Toolbar Header -->
      <div class="table-toolbar">
        <div class="toolbar-left">
          <h3 class="table-title">Daftar Transaksi Kasir OTC</h3>
          <span class="document-badge">{{ paginationInfo.total || 0 }} Transaksi Ditemukan</span>
        </div>

        <div class="toolbar-right">
          <div class="per-page-selector">
            <label class="per-page-label" for="pos-per-page">Baris per halaman:</label>
            <select
              id="pos-per-page"
              :value="paginationInfo.per_page"
              class="form-select per-page-select"
              @change="handlePerPageChange"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>

          <button
            type="button"
            class="btn-refresh"
            :disabled="loading"
            title="Segarkan data transaksi"
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
          </button>
        </div>
      </div>

      <!-- Table Content Area -->
      <!-- Case 1: Error State -->
      <div v-if="tableState === 'error'" class="error-container">
        <div class="error-icon-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <h4 class="error-title">Gagal Memuat Data Transaksi</h4>
        <p class="error-desc">{{ errorMessage || 'Terjadi gangguan koneksi pada server kasir farmasi OTC.' }}</p>
        <button type="button" class="btn-retry" @click="emit('retry')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10"></polyline>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
          </svg>
          Coba Lagi
        </button>
      </div>

      <!-- Case 2: Normal / Skeleton / Empty Table Wrapper -->
      <div v-else class="table-responsive">
        <table class="pos-table">
          <thead>
            <tr>
              <th class="col-no text-center">NO.</th>
              <th class="col-trans-no">
                <div class="th-content">
                  <span>NO. TRANSAKSI</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M7 15l5 5 5-5M7 9l5-5 5 5"></path>
                  </svg>
                </div>
              </th>
              <th class="col-date">
                <div class="th-content">
                  <span>TANGGAL & WAKTU</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 9l-7 7-7-7"></path>
                  </svg>
                </div>
              </th>
              <th class="col-customer">NAMA PEMBELI</th>
              <th class="col-item-count text-center">JML ITEM</th>
              <th class="col-qty">TOTAL QTY</th>
              <th class="col-amount text-right">
                <div class="th-content justify-end">
                  <span>TOTAL BAYAR</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M7 15l5 5 5-5M7 9l5-5 5 5"></path>
                  </svg>
                </div>
              </th>
              <th class="col-payment text-center">METODE BAYAR</th>
              <th class="col-status text-center">STATUS</th>
              <th class="col-cashier">PETUGAS KASIR</th>
              <th class="col-actions text-center">AKSI</th>
            </tr>
          </thead>

          <tbody>
            <!-- SKELETON LOADING STATE -->
            <template v-if="loading || tableState === 'skeleton'">
              <tr v-for="n in 6" :key="'skel-' + n" class="skeleton-row">
                <td class="text-center"><div class="skeleton-pill skel-no"></div></td>
                <td><div class="skeleton-line skel-wide"></div></td>
                <td><div class="skeleton-line skel-mid"></div><div class="skeleton-line skel-sub"></div></td>
                <td><div class="skeleton-line skel-wide"></div></td>
                <td class="text-center"><div class="skeleton-pill skel-mid"></div></td>
                <td><div class="skeleton-line skel-mid"></div></td>
                <td class="text-right"><div class="skeleton-line skel-mid ml-auto"></div></td>
                <td class="text-center"><div class="skeleton-pill skel-mid"></div></td>
                <td class="text-center"><div class="skeleton-pill skel-mid"></div></td>
                <td><div class="skeleton-line skel-wide"></div></td>
                <td class="text-center"><div class="skeleton-pill skel-action"></div></td>
              </tr>
            </template>

            <!-- EMPTY STATE -->
            <template v-else-if="tableState === 'empty' || items.length === 0">
              <tr>
                <td colspan="11" class="empty-table-cell">
                  <MasterDataEmptyState
                    title="Data Transaksi Tidak Ditemukan"
                    message="Tidak ada data transaksi kasir OTC yang sesuai dengan kriteria filter saat ini."
                    action-label="Reset Filter"
                    @action="emit('reset-filter')"
                  />
                </td>
              </tr>
            </template>

            <!-- DATA ROWS -->
            <template v-else>
              <tr
                v-for="(item, idx) in items"
                :key="item.id"
                class="data-row"
                :class="{ 'row-cancelled': item.status === 'Dibatalkan' }"
              >
                <!-- 1. No -->
                <td class="col-no text-center font-mono">
                  {{ getRowNumber(idx) }}
                </td>

                <!-- 2. No Transaksi -->
                <td class="col-trans-no font-mono">
                  <span
                    class="trans-link"
                    :class="{ 'text-cancelled line-through': item.status === 'Dibatalkan' }"
                    @click="emit('view', item)"
                  >
                    {{ item.transactionNumber }}
                  </span>
                </td>

                <!-- 3. Tanggal & Waktu -->
                <td class="col-date">
                  <div class="date-cell">
                    <span class="primary-date">{{ item.dateDisplay }}</span>
                    <span class="sub-time">{{ item.timeDisplay }}</span>
                  </div>
                </td>

                <!-- 4. Nama Pembeli -->
                <td class="col-customer">
                  <div class="customer-cell">
                    <span class="customer-name font-medium">{{ item.customerName }}</span>
                    <PosSaleStatusBadge
                      v-if="item.customerType"
                      type="customer"
                      :value="item.customerType"
                    />
                  </div>
                </td>

                <!-- 5. Jml Item -->
                <td class="col-item-count text-center">
                  <span class="item-count-badge">{{ item.itemCountText || (item.itemCount + ' Jenis') }}</span>
                </td>

                <!-- 6. Total Qty -->
                <td class="col-qty">
                  <span class="qty-text">{{ item.totalQtyText }}</span>
                </td>

                <!-- 7. Total Bayar -->
                <td class="col-amount text-right">
                  <span
                    class="amount-text font-bold"
                    :class="{ 'text-cancelled line-through': item.status === 'Dibatalkan' }"
                  >
                    {{ formatRupiah(item.totalAmount) }}
                  </span>
                </td>

                <!-- 8. Metode Bayar -->
                <td class="col-payment text-center">
                  <PosSaleStatusBadge
                    type="payment"
                    :value="item.paymentMethodDisplay || item.paymentMethod"
                  />
                </td>

                <!-- 9. Status -->
                <td class="col-status text-center">
                  <PosSaleStatusBadge
                    type="status"
                    :value="item.status"
                  />
                </td>

                <!-- 10. Petugas Kasir -->
                <td class="col-cashier">
                  <span class="cashier-name">{{ item.cashier }}</span>
                </td>

                <!-- 11. Aksi -->
                <td class="col-actions text-center">
                  <div class="actions-wrapper">
                    <!-- Status Lunas: View, Print, Void -->
                    <template v-if="item.status === 'Lunas'">
                      <button
                        type="button"
                        class="btn-action-icon btn-action-view"
                        title="Lihat Detail Transaksi"
                        aria-label="Lihat Detail Transaksi"
                        @click="emit('view', item)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                          <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                      </button>

                      <button
                        type="button"
                        class="btn-action-icon btn-action-print"
                        title="Cetak Struk Kasir"
                        aria-label="Cetak Struk Kasir"
                        @click="emit('print', item)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <polyline points="6 9 6 2 18 2 18 9"></polyline>
                          <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                          <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                      </button>

                      <button
                        type="button"
                        class="btn-action-icon btn-action-void"
                        title="Batalkan Transaksi (Void)"
                        aria-label="Batalkan Transaksi (Void)"
                        @click="emit('void', item)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <circle cx="12" cy="12" r="10"></circle>
                          <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                        </svg>
                      </button>
                    </template>

                    <!-- Status Draft: Edit, Delete -->
                    <template v-else-if="item.status === 'Draft'">
                      <button
                        type="button"
                        class="btn-action-icon btn-action-edit"
                        title="Edit Draft Transaksi"
                        aria-label="Edit Draft Transaksi"
                        @click="emit('edit', item)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                      </button>

                      <button
                        type="button"
                        class="btn-action-icon btn-action-delete"
                        title="Hapus Draft Transaksi"
                        aria-label="Hapus Draft Transaksi"
                        @click="emit('delete', item)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <polyline points="3 6 5 6 21 6"></polyline>
                          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                      </button>
                    </template>

                    <!-- Status Dibatalkan: View, Info Void -->
                    <template v-else-if="item.status === 'Dibatalkan'">
                      <button
                        type="button"
                        class="btn-action-icon btn-action-view"
                        title="Lihat Detail Transaksi"
                        aria-label="Lihat Detail Transaksi"
                        @click="emit('view', item)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                          <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                      </button>

                      <button
                        type="button"
                        class="btn-action-icon btn-action-info"
                        title="Informasi Alasan Void"
                        aria-label="Informasi Alasan Void"
                        @click="emit('info-void', item)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <circle cx="12" cy="12" r="10"></circle>
                          <line x1="12" y1="16" x2="12" y2="12"></line>
                          <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                      </button>
                    </template>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div v-if="tableState !== 'error' && tableState !== 'empty' && items.length > 0" class="table-pagination-footer">
        <div class="pagination-info">
          Menampilkan <span class="font-semibold">{{ paginationInfo.from }}–{{ paginationInfo.to }}</span> dari
          <span class="font-semibold">{{ paginationInfo.total }}</span> transaksi penjualan bebas
        </div>

        <MasterDataPagination
          :pagination="{
            current_page: paginationInfo.current_page,
            last_page: paginationInfo.last_page,
            total: paginationInfo.total,
            from: paginationInfo.from,
            to: paginationInfo.to
          }"
          @page-change="(p) => emit('page-change', p)"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.pos-table-container {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

/* Live State Switcher Bar */
.live-state-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
  border: 1px dashed var(--color-border-soft, #cbd5e1);
  border-radius: 8px;
  padding: 0.55rem 0.95rem;
  font-size: 0.78rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.state-bar-left {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  color: var(--color-text-secondary, #64748b);
  font-weight: 600;
}

.state-icon {
  width: 15px;
  height: 15px;
  color: var(--color-primary, #087f78);
}

.state-buttons {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.state-btn {
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  font-size: 0.74rem;
  font-weight: 600;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  background: #ffffff;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s ease;
}

.state-btn:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.state-btn.active {
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 1px 2px rgba(8, 127, 120, 0.2);
}

/* Main Table Card */
.pos-table-card {
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
  overflow: hidden;
}

.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.15rem 1.4rem;
  border-bottom: 1px solid var(--color-border-soft, #f1f5f9);
  flex-wrap: wrap;
  gap: 0.85rem;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.table-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  letter-spacing: -0.01em;
  margin: 0;
}

.document-badge {
  font-size: 0.74rem;
  font-weight: 600;
  color: var(--color-primary, #087f78);
  background: var(--color-primary-light, #dff7f4);
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
}

.toolbar-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.per-page-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.per-page-label {
  font-size: 0.78rem;
  color: var(--color-text-secondary, #64748b);
  white-space: nowrap;
}

.per-page-select {
  height: 32px;
  padding: 0 1.6rem 0 0.6rem;
  font-size: 0.78rem;
  border-radius: 6px;
  background-position: right 0.4rem center;
}

.btn-refresh {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  background: #ffffff;
  color: var(--color-text-secondary, #64748b);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-refresh:hover:not(:disabled) {
  background: #f8fafc;
  color: var(--color-primary, #087f78);
  border-color: var(--color-primary, #087f78);
}

.btn-refresh svg {
  width: 16px;
  height: 16px;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.pos-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
  text-align: left;
}

.pos-table thead th {
  background: #f8fafc;
  color: var(--color-text-secondary, #64748b);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  white-space: nowrap;
}

.th-content {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.sort-icon {
  width: 13px;
  height: 13px;
  color: #94a3b8;
}

.pos-table tbody td {
  padding: 0.95rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #f1f5f9);
  color: var(--color-text-navy, #17243d);
  vertical-align: middle;
}

.data-row:hover td {
  background-color: #f8fafc;
}

/* Cancelled Row Styling */
.row-cancelled td {
  background-color: rgba(239, 68, 68, 0.025);
}

.row-cancelled:hover td {
  background-color: rgba(239, 68, 68, 0.05);
}

.text-cancelled {
  color: #dc2626 !important;
}

.line-through {
  text-decoration: line-through;
}

.trans-link {
  color: var(--color-primary, #087f78);
  font-weight: 700;
  cursor: pointer;
  transition: color 0.15s ease;
}

.trans-link:hover {
  text-decoration: underline;
}

.date-cell {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.primary-date {
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.sub-time {
  font-size: 0.74rem;
  color: #94a3b8;
}

.customer-cell {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex-wrap: wrap;
}

.customer-name {
  font-weight: 600;
}

.item-count-badge {
  font-size: 0.76rem;
  font-weight: 600;
  color: #475569;
  background: #f1f5f9;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  display: inline-block;
}

.qty-text {
  font-size: 0.78rem;
  color: #334155;
  font-weight: 500;
}

.amount-text {
  font-size: 0.84rem;
  color: var(--color-text-navy, #17243d);
}

.cashier-name {
  font-size: 0.78rem;
  color: #475569;
}

/* Action Buttons */
.actions-wrapper {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-action-icon {
  width: 30px;
  height: 30px;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  background: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-action-icon svg {
  width: 14px;
  height: 14px;
}

.btn-action-view {
  color: var(--color-primary, #087f78);
}

.btn-action-view:hover {
  background: var(--color-primary-light, #dff7f4);
  border-color: var(--color-primary, #087f78);
}

.btn-action-print {
  color: #2563eb;
}

.btn-action-print:hover {
  background: #eff6ff;
  border-color: #93c5fd;
}

.btn-action-void {
  color: #dc2626;
}

.btn-action-void:hover {
  background: #fef2f2;
  border-color: #fca5a5;
}

.btn-action-edit {
  color: #d97706;
}

.btn-action-edit:hover {
  background: #fef3c7;
  border-color: #fcd34d;
}

.btn-action-delete {
  color: #ef4444;
}

.btn-action-delete:hover {
  background: #fee2e2;
  border-color: #fca5a5;
}

.btn-action-info {
  color: #0284c7;
}

.btn-action-info:hover {
  background: #e0f2fe;
  border-color: #7dd3fc;
}

/* Skeleton Loading styles */
.skeleton-row td {
  padding: 1rem;
}

.skeleton-line {
  height: 12px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  border-radius: 4px;
  animation: pulse-wave 1.5s infinite;
}

.skeleton-pill {
  height: 22px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  border-radius: 9999px;
  animation: pulse-wave 1.5s infinite;
}

.skel-no {
  width: 24px;
  margin: 0 auto;
}

.skel-wide {
  width: 120px;
}

.skel-mid {
  width: 75px;
}

.skel-sub {
  width: 60px;
  height: 9px;
  margin-top: 4px;
}

.skel-action {
  width: 64px;
  margin: 0 auto;
}

.ml-auto {
  margin-left: auto;
}

@keyframes pulse-wave {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Empty & Error States */
.empty-table-cell {
  padding: 3rem 1.5rem !important;
  text-align: center;
}

.error-container {
  padding: 3.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.error-icon-box {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #fef2f2;
  color: #dc2626;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}

.error-icon-box svg {
  width: 24px;
  height: 24px;
}

.error-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin-bottom: 0.35rem;
}

.error-desc {
  font-size: 0.82rem;
  color: #64748b;
  max-width: 420px;
  margin-bottom: 1.25rem;
}

.btn-retry {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.55rem 1.25rem;
  font-size: 0.82rem;
  font-weight: 600;
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: 1px solid var(--color-primary, #087f78);
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-retry:hover {
  background: #076d67;
}

.btn-retry svg {
  width: 15px;
  height: 15px;
}

/* Pagination Footer */
.table-pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.4rem;
  border-top: 1px solid var(--color-border-soft, #f1f5f9);
  flex-wrap: wrap;
  gap: 0.75rem;
}

.pagination-info {
  font-size: 0.78rem;
  color: var(--color-text-secondary, #64748b);
}

.spin-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Utility alignments */
.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.justify-end {
  justify-content: flex-end;
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
</style>
