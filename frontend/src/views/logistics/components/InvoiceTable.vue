<script setup>
import InvoiceStatusBadge from './InvoiceStatusBadge.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';

const props = defineProps({
  items: {
    type: Array,
    required: true,
    default: () => []
  },
  paginationInfo: {
    type: Object,
    required: true
  },
  sort: {
    type: Object,
    default: () => ({ column: 'invoiceDate', direction: 'desc' })
  },
  searchQuery: {
    type: String,
    default: ''
  },
  loading: {
    type: Boolean,
    default: false
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

const emit = defineEmits([
  'view',
  'edit',
  'delete',
  'sort',
  'page-change',
  'per-page-change',
  'search-change',
  'export',
  'add'
]);

const handleSort = (column) => {
  emit('sort', column);
};

const handlePerPageChange = (event) => {
  emit('per-page-change', Number(event.target.value));
};

const handleSearchInput = (event) => {
  emit('search-change', event.target.value);
};

const getRowNumber = (index) => {
  const currentPage = props.paginationInfo?.current_page || 1;
  const perPage = props.paginationInfo?.per_page || 10;
  return (currentPage - 1) * perPage + index + 1;
};
</script>

<template>
  <div class="invoice-table-card card">
    <!-- Top toolbar: Rows per page, summary count, search in results, and export -->
    <div class="table-toolbar">
      <div class="toolbar-left">
        <div class="per-page-control">
          <label for="per-page-select" class="per-page-label">Baris per halaman:</label>
          <select
            id="per-page-select"
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

        <span class="toolbar-info">
          Menampilkan {{ paginationInfo.from }}–{{ paginationInfo.to }} dari {{ paginationInfo.total }} faktur
        </span>
      </div>

      <div class="toolbar-right">
        <!-- Search within results -->
        <div class="search-input-wrapper">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input autocomplete="off"
            type="text"
            class="form-input search-input"
            placeholder="Cari dalam hasil..."
            :value="searchQuery"
            @input="handleSearchInput"
          />
        </div>

        <!-- Export button -->
        <button
          type="button"
          class="btn-outline btn-export"
          @click="emit('export')"
          title="Ekspor Data Faktur"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          <span>Ekspor</span>
        </button>
      </div>
    </div>

    <!-- Table Container -->
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <!-- 1. No -->
            <th class="col-no text-center">NO.</th>

            <!-- 2. Tanggal Faktur -->
            <th class="sortable col-date" @click="handleSort('invoiceDate')">
              <div class="th-content">
                <span>TANGGAL FAKTUR</span>
                <span class="sort-icon-box" v-if="sort.column === 'invoiceDate'">
                  <svg v-if="sort.direction === 'asc'" class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="18 15 12 9 6 15"></polyline>
                  </svg>
                  <svg v-else class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </span>
                <span class="sort-icon-box neutral" v-else>
                  <svg class="sort-icon neutral" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="7 15 12 20 17 15"></polyline>
                    <polyline points="7 9 12 4 17 9"></polyline>
                  </svg>
                </span>
              </div>
            </th>

            <!-- 3. Nomor Faktur -->
            <th class="sortable col-invoice-num" @click="handleSort('invoiceNumber')">
              <div class="th-content">
                <span>NOMOR FAKTUR</span>
                <span class="sort-icon-box" v-if="sort.column === 'invoiceNumber'">
                  <svg v-if="sort.direction === 'asc'" class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="18 15 12 9 6 15"></polyline>
                  </svg>
                  <svg v-else class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </span>
                <span class="sort-icon-box neutral" v-else>
                  <svg class="sort-icon neutral" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="7 15 12 20 17 15"></polyline>
                    <polyline points="7 9 12 4 17 9"></polyline>
                  </svg>
                </span>
              </div>
            </th>

            <!-- 4. Supplier -->
            <th class="col-supplier">
              <span>SUPPLIER</span>
            </th>

            <!-- 5. Total (IDR) -->
            <th class="sortable col-total" @click="handleSort('totalAmount')">
              <div class="th-content">
                <span>TOTAL (IDR)</span>
                <span class="sort-icon-box" v-if="sort.column === 'totalAmount'">
                  <svg v-if="sort.direction === 'asc'" class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="18 15 12 9 6 15"></polyline>
                  </svg>
                  <svg v-else class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </span>
                <span class="sort-icon-box neutral" v-else>
                  <svg class="sort-icon neutral" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="7 15 12 20 17 15"></polyline>
                    <polyline points="7 9 12 4 17 9"></polyline>
                  </svg>
                </span>
              </div>
            </th>

            <!-- 6. Status -->
            <th class="col-status">
              <span>STATUS</span>
            </th>

            <!-- 7. Jatuh Tempo -->
            <th class="sortable col-due-date" @click="handleSort('dueDate')">
              <div class="th-content">
                <span>JATUH TEMPO</span>
                <span class="sort-icon-box" v-if="sort.column === 'dueDate'">
                  <svg v-if="sort.direction === 'asc'" class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="18 15 12 9 6 15"></polyline>
                  </svg>
                  <svg v-else class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </span>
                <span class="sort-icon-box neutral" v-else>
                  <svg class="sort-icon neutral" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="7 15 12 20 17 15"></polyline>
                    <polyline points="7 9 12 4 17 9"></polyline>
                  </svg>
                </span>
              </div>
            </th>

            <!-- 8. Aksi -->
            <th class="col-actions text-center">AKSI</th>
          </tr>
        </thead>

        <tbody>
          <!-- Empty State Row -->
          <tr v-if="items.length === 0">
            <td colspan="8" class="empty-cell">
              <MasterDataEmptyState
                :is-search="true"
                title="Data Faktur Tidak Ditemukan"
                search-message="Tidak ada data faktur yang sesuai dengan kriteria filter atau pencarian."
              >
                <template #action>
                  <button type="button" class="btn-primary" @click="emit('add')">
                    + Tambah Faktur Baru
                  </button>
                </template>
              </MasterDataEmptyState>
            </td>
          </tr>

          <!-- Data Rows -->
          <tr v-for="(invoice, index) in items" :key="invoice.id">
            <!-- 1. No -->
            <td class="col-no text-center text-muted font-medium">
              {{ getRowNumber(index) }}
            </td>

            <!-- 2. Tanggal Faktur -->
            <td class="col-date font-medium text-navy">
              {{ formatDateIndo(invoice.invoiceDate) }}
            </td>

            <!-- 3. Nomor Faktur -->
            <td class="col-invoice-num">
              <div class="invoice-num-highlight">{{ invoice.invoiceNumber }}</div>
              <div class="invoice-ref-text">Ref: {{ invoice.referenceNumber || '-' }}</div>
            </td>

            <!-- 4. Supplier -->
            <td class="col-supplier">
              <div class="supplier-main-name">{{ invoice.supplierName }}</div>
              <div class="supplier-subtext">
                <span v-if="invoice.supplierCity">{{ invoice.supplierCity }}</span>
                <span v-if="invoice.supplierCity && invoice.supplierNpwp" class="bullet-dot">•</span>
                <span v-if="invoice.supplierNpwp">NPWP: {{ invoice.supplierNpwp }}</span>
              </div>
            </td>

            <!-- 5. Total (IDR) -->
            <td class="col-total font-bold text-navy">
              {{ formatRupiah(invoice.totalAmount) }}
            </td>

            <!-- 6. Status -->
            <td class="col-status">
              <InvoiceStatusBadge
                :status="invoice.status"
                :is-overdue="isOverdue(invoice.dueDate, invoice.status)"
              />
            </td>

            <!-- 7. Jatuh Tempo -->
            <td class="col-due-date">
              <div
                class="due-date-main"
                :class="{ 'text-danger-due': isOverdue(invoice.dueDate, invoice.status) }"
              >
                {{ formatDateIndo(invoice.dueDate) }}
              </div>
              <div
                class="due-date-relative"
                :class="{
                  'due-overdue': isOverdue(invoice.dueDate, invoice.status),
                  'due-paid': invoice.status === 'Lunas',
                  'due-pending': invoice.status !== 'Lunas' && !isOverdue(invoice.dueDate, invoice.status)
                }"
              >
                {{ getDueStatusText(invoice) }}
              </div>
            </td>

            <!-- 8. Aksi -->
            <td class="col-actions text-center">
              <div class="action-buttons">
                <!-- Lihat detail -->
                <button
                  type="button"
                  class="btn-action view"
                  title="Lihat Detail Faktur"
                  aria-label="Lihat Detail"
                  @click="emit('view', invoice)"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>

                <!-- Edit -->
                <button
                  type="button"
                  class="btn-action edit"
                  title="Edit Faktur"
                  aria-label="Edit"
                  @click="emit('edit', invoice)"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>

                <!-- Hapus -->
                <button
                  type="button"
                  class="btn-action delete"
                  title="Hapus Faktur"
                  aria-label="Hapus"
                  @click="emit('delete', invoice)"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Bottom Pagination -->
    <div class="table-pagination-wrapper">
      <MasterDataPagination
        :pagination="paginationInfo"
        :loading="loading"
        :item-count="items.length"
        @page-change="emit('page-change', $event)"
      />
    </div>
  </div>
</template>

<style scoped>
.invoice-table-card {
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
  gap: 1.25rem;
  flex-wrap: wrap;
}

.per-page-control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.per-page-label {
  font-size: 0.82rem;
  color: var(--color-text-secondary, #64748b);
  white-space: nowrap;
}

.per-page-select {
  width: auto;
  padding: 0.35rem 1.75rem 0.35rem 0.65rem;
  font-size: 0.82rem;
  height: 34px;
}

.toolbar-info {
  font-size: 0.82rem;
  color: var(--color-text-secondary, #64748b);
}

.toolbar-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.65rem;
  width: 15px;
  height: 15px;
  color: var(--color-text-secondary, #94a3b8);
  pointer-events: none;
}

.search-input {
  padding-left: 2rem;
  height: 36px;
  width: 220px;
  font-size: 0.82rem;
}

.btn-export {
  padding: 0.45rem 0.85rem;
  font-size: 0.82rem;
  height: 36px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-export svg {
  width: 15px;
  height: 15px;
}

.table-container {
  width: 100%;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  min-width: 950px;
}

.data-table th {
  background: #f8fafc;
  padding: 0.85rem 1rem;
  text-align: left;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: var(--color-text-secondary, #64748b);
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  white-space: nowrap;
}

.data-table td {
  padding: 0.95rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #e8edf3);
  color: var(--color-text-navy, #17243d);
  vertical-align: middle;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: #f9fbfa;
}

/* Column Specifics */
.col-no { width: 50px; }
.col-date { width: 110px; }
.col-invoice-num { min-width: 160px; }
.col-supplier { min-width: 240px; }
.col-total { min-width: 130px; }
.col-status { width: 130px; }
.col-due-date { min-width: 130px; }
.col-actions { width: 110px; text-align: center; }

.invoice-num-highlight {
  font-weight: 700;
  color: var(--color-primary, #087f78);
  font-size: 0.88rem;
}

.invoice-ref-text {
  font-size: 0.72rem;
  color: var(--color-text-secondary, #64748b);
  margin-top: 2px;
}

.supplier-main-name {
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.supplier-subtext {
  font-size: 0.72rem;
  color: var(--color-text-secondary, #64748b);
  margin-top: 2px;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.bullet-dot {
  color: #94a3b8;
  font-size: 0.7rem;
}

.font-bold {
  font-weight: 700;
}

.due-date-main {
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.due-date-main.text-danger-due {
  color: var(--color-danger, #d64545);
}

.due-date-relative {
  font-size: 0.72rem;
  margin-top: 2px;
  font-weight: 500;
}

.due-overdue {
  color: var(--color-danger, #d64545);
  font-weight: 600;
}

.due-paid {
  color: #059669;
}

.due-pending {
  color: var(--color-text-secondary, #64748b);
}

.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
}

.sortable:hover {
  background-color: #f1f5f9;
}

.th-content {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.sort-icon-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.sort-icon {
  width: 14px;
  height: 14px;
  color: var(--color-primary, #087f78);
}

.sort-icon.neutral {
  color: #94a3b8;
  opacity: 0.5;
}

.action-buttons {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
}

.btn-action {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  border-radius: 6px;
  color: var(--color-text-secondary, #64748b);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action svg {
  width: 16px;
  height: 16px;
}

.btn-action.view:hover {
  background: #f0fdf4;
  color: #166534;
}

.btn-action.edit:hover {
  background: #e0f2fe;
  color: #0284c7;
}

.btn-action.delete:hover {
  background: #fef2f2;
  color: #ef4444;
}

.empty-cell {
  padding: 2rem !important;
  background: #ffffff;
}

.table-pagination-wrapper {
  padding: 0.75rem 1.25rem 1.25rem;
  border-top: 1px solid var(--color-border-soft, #e8edf3);
}

@media (max-width: 768px) {
  .table-toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .toolbar-left,
  .toolbar-right {
    width: 100%;
    justify-content: space-between;
  }
  
  .search-input {
    width: 100%;
  }
}
</style>
