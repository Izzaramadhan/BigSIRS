<script setup>
import { ref, watch } from 'vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';

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
  search: {
    type: String,
    default: ''
  }
});

const emit = defineEmits([
  'view',
  'edit',
  'delete',
  'search',
  'refresh',
  'page-change',
  'per-page-change',
  'reset-filter'
]);

const localSearch = ref(props.search);
let searchTimeout = null;

watch(
  () => props.search,
  (newVal) => {
    localSearch.value = newVal;
  }
);

const handleSearchInput = (e) => {
  const val = e.target.value;
  localSearch.value = val;
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    emit('search', val);
  }, 300);
};

const handlePerPageChange = (event) => {
  emit('per-page-change', Number(event.target.value));
};

const getRowNumber = (index) => {
  return (props.paginationInfo.from || 1) + index;
};

const formatDate = (item) => {
  if (item.formattedDate && item.formattedDate !== '-') {
    return item.formattedDate;
  }
  const val = item.date || item.transaction_date || item.transactionDate;
  if (!val) return '-';
  if (/^\d{2}-\d{2}-\d{4}$/.test(val)) return val;
  try {
    const d = new Date(val);
    if (isNaN(d.getTime())) return val;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}-${month}-${year}`;
  } catch {
    return val;
  }
};
</script>

<template>
  <div class="pos-table-card card">
    <!-- Toolbar Controls (Per-page & Search) -->
    <div class="table-toolbar">
      <div class="toolbar-left">
        <label for="records-select" class="per-page-label">
          <select
            id="records-select"
            class="records-select"
            :value="paginationInfo.per_page || 10"
            @change="handlePerPageChange"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
          <span class="records-text">records</span>
        </label>
      </div>

      <div class="toolbar-right">
        <div class="search-input-wrapper">
          <label for="table-search-input" class="search-label">Search:</label>
          <input
            id="table-search-input"
            type="text"
            class="search-input"
            :value="localSearch"
            placeholder="Cari data..."
            @input="handleSearchInput"
          />
        </div>
      </div>
    </div>

    <!-- Table Responsive Wrapper -->
    <div class="table-responsive">
      <table class="pos-table">
        <thead>
          <tr>
            <th class="col-no text-center">No.</th>
            <th class="col-date">Tanggal</th>
            <th class="col-customer">Pembeli</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading Skeleton -->
          <template v-if="loading">
            <tr v-for="n in 6" :key="'skel-' + n" class="skeleton-row">
              <td class="text-center"><div class="skeleton-pill skel-no"></div></td>
              <td><div class="skeleton-line skel-date"></div></td>
              <td><div class="skeleton-line skel-customer"></div></td>
              <td class="text-center"><div class="skeleton-pill skel-actions"></div></td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else-if="items.length === 0">
            <td colspan="4" class="empty-cell">
              <MasterDataEmptyState
                title="Data Penjualan Bebas Tidak Ditemukan"
                description="Belum ada transaksi penjualan bebas yang tersimpan atau cocok dengan pencarian Anda."
                action-label="Reset Filter"
                @action="emit('reset-filter')"
              />
            </td>
          </tr>

          <!-- Normal Table Rows -->
          <tr
            v-for="(item, idx) in items"
            v-else
            :key="item.id || idx"
            class="pos-row"
          >
            <!-- 1. No -->
            <td class="col-no text-center font-medium text-muted">
              {{ getRowNumber(idx) }}
            </td>

            <!-- 2. Tanggal (DD-MM-YYYY) -->
            <td class="col-date">
              <span class="date-text font-semibold text-navy">
                {{ formatDate(item) }}
              </span>
            </td>

            <!-- 3. Pembeli -->
            <td class="col-customer">
              <span class="customer-name font-semibold text-navy">
                {{ item.pembeli || item.customerName || item.customer_name || '-' }}
              </span>
            </td>

            <!-- 4. Aksi (Edit, Detail, Hapus) -->
            <td class="col-actions text-center">
              <div class="action-buttons-group">
                <button
                  type="button"
                  class="btn-action-icon btn-edit"
                  title="Edit Penjualan Bebas"
                  @click="emit('edit', item)"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>

                <button
                  type="button"
                  class="btn-action-icon btn-detail"
                  title="Lihat Detail Penjualan"
                  @click="emit('view', item)"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>

                <button
                  type="button"
                  class="btn-action-icon btn-delete"
                  title="Hapus Penjualan Bebas"
                  @click="emit('delete', item)"
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

    <!-- Table Pagination Footer -->
    <div class="table-pagination-wrapper">
      <div class="pagination-info text-muted">
        Showing {{ paginationInfo.from || 0 }} to {{ paginationInfo.to || 0 }} of {{ paginationInfo.total || 0 }} entries
      </div>
      <MasterDataPagination
        :pagination="paginationInfo"
        :loading="loading"
        @page-change="(p) => emit('page-change', p)"
      />
    </div>
  </div>
</template>

<style scoped>
.pos-table-card {
  background: #ffffff;
  border-radius: 10px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  margin-bottom: 2rem;
}

/* Toolbar Controls */
.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 1rem;
}

.per-page-label {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #475569;
}

.records-select {
  padding: 0.35rem 0.65rem;
  font-size: 0.85rem;
  color: #1e293b;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background-color: #fff;
  cursor: pointer;
}

.records-select:focus {
  border-color: #008779;
  outline: none;
  box-shadow: 0 0 0 2px rgba(0, 135, 121, 0.15);
}

.records-text {
  font-weight: 500;
}

.search-input-wrapper {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.search-label {
  font-size: 0.85rem;
  font-weight: 500;
  color: #475569;
}

.search-input {
  padding: 0.35rem 0.75rem;
  font-size: 0.85rem;
  color: #1e293b;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  width: 200px;
  transition: all 0.2s ease;
}

.search-input:focus {
  width: 240px;
  border-color: #008779;
  outline: none;
  box-shadow: 0 0 0 2px rgba(0, 135, 121, 0.15);
}

/* Table */
.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.pos-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.875rem;
}

.pos-table thead th {
  background-color: #f8fafc;
  color: #475569;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.85rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.pos-table tbody td {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  vertical-align: middle;
}

.pos-row:hover td {
  background-color: #f8fbfb;
}

/* Column Widths */
.col-no {
  width: 70px;
}
.col-date {
  width: 180px;
}
.col-customer {
  min-width: 240px;
}
.col-actions {
  width: 140px;
}

/* Typography & Helpers */
.text-navy {
  color: #0f172a;
}
.text-muted {
  color: #64748b;
}
.font-medium {
  font-weight: 500;
}
.font-semibold {
  font-weight: 600;
}
.text-center {
  text-align: center;
}

/* Action Buttons */
.action-buttons-group {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-action-icon {
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-action-icon svg {
  width: 16px;
  height: 16px;
}

.btn-action-icon.btn-edit:hover {
  background-color: #f0fdf4;
  border-color: #86efac;
  color: #16a34a;
}

.btn-action-icon.btn-detail:hover {
  background-color: #f0fdfa;
  border-color: #99f6e4;
  color: #0d9488;
}

.btn-action-icon.btn-delete:hover {
  background-color: #fef2f2;
  border-color: #fca5a5;
  color: #dc2626;
}

/* Empty Cell */
.empty-cell {
  padding: 3rem 1.5rem !important;
  text-align: center;
}

/* Skeletons */
.skeleton-row td {
  padding: 1.15rem 1.25rem;
}

.skeleton-line {
  height: 14px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

.skel-date {
  width: 110px;
}
.skel-customer {
  width: 160px;
}

.skeleton-pill {
  height: 28px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 6px;
  margin: 0 auto;
}

.skel-no {
  width: 24px;
}
.skel-actions {
  width: 90px;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Footer Pagination */
.table-pagination-wrapper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-top: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  font-size: 0.85rem;
}
</style>
