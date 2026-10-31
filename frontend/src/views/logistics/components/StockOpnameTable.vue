<script setup>
import { computed } from 'vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';

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
  hasActiveFilters: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'edit',
  'refresh',
  'page-change',
  'per-page-change',
  'reset-filter'
]);

const getRowNumber = (index) => {
  const page = props.paginationInfo.current_page || 1;
  const perPage = props.paginationInfo.per_page || 10;
  return (page - 1) * perPage + index + 1;
};

const handlePerPageChange = (event) => {
  emit('per-page-change', Number(event.target.value));
};

const formatRupiah = (val) => {
  const num = Number(val || 0);
  return 'Rp' + num.toLocaleString('id-ID');
};

const formatStock = (val) => {
  const num = Number(val ?? 0);
  if (Number.isInteger(num)) {
    return num.toLocaleString('id-ID');
  }
  return num.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
};

const getExpirationClass = (item) => {
  if (item.is_expired || item.isExpired) {
    return 'badge-expired';
  }
  if (item.is_expiring_soon || item.isExpiringSoon) {
    return 'badge-expiring';
  }
  return 'badge-normal';
};
</script>

<template>
  <div class="stock-table-card card">
    <!-- Toolbar Header -->
    <div class="table-toolbar">
      <div class="toolbar-left">
        <div class="title-with-badge">
          <h3 class="table-title">LIST DATA STOK SEDIAAN</h3>
          <span class="document-badge">{{ paginationInfo.total || 0 }} Sediaan</span>
        </div>
      </div>

      <div class="toolbar-right">
        <!-- Records Per Page Dropdown -->
        <div class="per-page-wrapper">
          <label for="records-per-page" class="per-page-label">Tampilkan:</label>
          <select
            id="records-per-page"
            :value="paginationInfo.per_page || 10"
            class="per-page-select"
            @change="handlePerPageChange"
          >
            <option :value="10">10 data</option>
            <option :value="25">25 data</option>
            <option :value="50">50 data</option>
            <option :value="100">100 data</option>
          </select>
        </div>

        <button
          type="button"
          class="btn-refresh"
          :disabled="loading"
          title="Segarkan data stok"
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
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Table Responsive Wrapper -->
    <div class="table-responsive">
      <table class="stock-table">
        <thead>
          <tr>
            <th class="col-no text-center">No</th>
            <th class="col-warehouse">Gudang</th>
            <th class="col-medicine">Nama Sediaan</th>
            <th class="col-unit">Satuan</th>
            <th class="col-batch">No. Batch</th>
            <th class="col-expiration">Tgl. Kadaluarsa</th>
            <th class="col-price text-right">Harga Jual</th>
            <th class="col-stock text-right">Stok</th>
            <th class="col-action text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading State -->
          <tr v-if="loading">
            <td colspan="9" class="loading-cell text-center py-8">
              <div class="loading-state">
                <span class="spinner-icon"></span>
                <p class="loading-text">Memuat saldo stok sediaan...</p>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-else-if="items.length === 0">
            <td colspan="9" class="empty-cell text-center py-10">
              <div v-if="hasActiveFilters" class="empty-state">
                <div class="empty-icon-box">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    <line x1="8" y1="11" x2="14" y2="11"></line>
                  </svg>
                </div>
                <h4 class="empty-title">Data Tidak Ditemukan</h4>
                <p class="empty-description">Tidak ada stok sediaan yang sesuai dengan pencarian atau filter.</p>
                <button
                  type="button"
                  class="btn-reset-empty"
                  @click="emit('reset-filter')"
                >
                  Reset Filter
                </button>
              </div>

              <div v-else class="empty-state">
                <div class="empty-icon-box">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                  </svg>
                </div>
                <h4 class="empty-title">Belum Ada Data Stok</h4>
                <p class="empty-description">Belum terdapat stok sediaan pada gudang Logistik.</p>
              </div>
            </td>
          </tr>

          <!-- Data Rows -->
          <tr v-else v-for="(item, index) in items" :key="item.id" class="stock-row">
            <!-- 1. No -->
            <td class="col-no text-center font-medium text-slate-600">
              {{ getRowNumber(index) }}
            </td>

            <!-- 2. Gudang -->
            <td class="col-warehouse">
              <span class="warehouse-name font-medium text-slate-800">
                {{ item.warehouse?.name || item.warehouse_name || '-' }}
              </span>
            </td>

            <!-- 3. Nama Sediaan -->
            <td class="col-medicine">
              <div class="medicine-cell">
                <span class="medicine-name font-semibold text-slate-900">
                  {{ item.medicine?.name || item.medicine_name || '-' }}
                </span>
                <span
                  v-if="item.medicine?.code || item.medicine_code"
                  class="medicine-code text-xs text-slate-400"
                >
                  {{ item.medicine?.code || item.medicine_code }}
                </span>
              </div>
            </td>

            <!-- 4. Satuan -->
            <td class="col-unit">
              <span class="unit-badge">
                {{ item.unit?.name || item.unit_name || 'Unit' }}
              </span>
            </td>

            <!-- 5. No. Batch -->
            <td class="col-batch">
              <span class="batch-code font-mono text-slate-700">
                {{ item.batch_number || item.batchNumber || '-' }}
              </span>
            </td>

            <!-- 6. Tgl. Kadaluarsa -->
            <td class="col-expiration">
              <span
                class="expiration-pill"
                :class="getExpirationClass(item)"
              >
                {{ item.expiration_date_display || item.expirationDateDisplay || '-' }}
              </span>
            </td>

            <!-- 7. Harga Jual -->
            <td class="col-price text-right font-medium text-slate-800">
              {{ formatRupiah(item.selling_price ?? item.sellingPrice) }}
            </td>

            <!-- 8. Stok -->
            <td class="col-stock text-right">
              <span class="stock-value font-bold text-teal-700">
                {{ formatStock(item.stock ?? item.quantity) }}
              </span>
            </td>

            <!-- 9. Aksi: Edit Stok Opname -->
            <td class="col-action text-center">
              <button
                type="button"
                class="btn-edit-action"
                title="Edit Stok Opname"
                aria-label="Edit Stok Opname"
                @click="emit('edit', item)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Table Footer with Pagination -->
    <div v-if="paginationInfo.total > 0" class="table-footer">
      <div class="footer-info text-sm text-slate-600">
        Menampilkan
        <span class="font-medium text-slate-800">{{ paginationInfo.from || 1 }}</span>
        sampai
        <span class="font-medium text-slate-800">{{ paginationInfo.to || items.length }}</span>
        dari
        <span class="font-medium text-slate-800">{{ paginationInfo.total }}</span>
        data
      </div>

      <div class="footer-pagination">
        <MasterDataPagination
          :pagination="{
            current_page: paginationInfo.current_page || 1,
            last_page: paginationInfo.last_page || 1,
            per_page: paginationInfo.per_page || 10,
            total: paginationInfo.total || 0,
            from: paginationInfo.from || 1,
            to: paginationInfo.to || items.length
          }"
          @page-change="emit('page-change', $event)"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.stock-table-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.table-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.125rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 1rem;
}

.title-with-badge {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.table-title {
  font-size: 0.9375rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: 0.02em;
  margin: 0;
}

.document-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.625rem;
  font-size: 0.75rem;
  font-weight: 600;
  background-color: #f1f5f9;
  color: #334155;
  border-radius: 9999px;
}

.toolbar-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.per-page-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.per-page-label {
  font-size: 0.8125rem;
  color: #64748b;
  font-weight: 500;
}

.per-page-select {
  height: 34px;
  padding: 0 0.625rem;
  font-size: 0.8125rem;
  color: #1e293b;
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  outline: none;
  cursor: pointer;
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  height: 34px;
  padding: 0 0.875rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #0d9488;
  background: #f0fdfa;
  border: 1px solid #ccfbf1;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-refresh:hover:not(:disabled) {
  background: #e6fffa;
  border-color: #99f6e4;
}

.btn-refresh svg {
  width: 14px;
  height: 14px;
}

.spin-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.stock-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
  text-align: left;
}

.stock-table th {
  background-color: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 0.75rem;
  letter-spacing: 0.04em;
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.stock-table td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.stock-row:hover {
  background-color: #f8fafc;
}

.col-no {
  width: 50px;
}

.col-warehouse {
  width: 140px;
}

.col-medicine {
  min-width: 220px;
}

.medicine-cell {
  display: flex;
  flex-direction: column;
}

.medicine-code {
  margin-top: 2px;
}

.col-unit {
  width: 90px;
}

.unit-badge {
  display: inline-block;
  padding: 0.125rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  background-color: #f1f5f9;
  color: #475569;
  border-radius: 4px;
}

.col-batch {
  width: 130px;
}

.batch-code {
  font-size: 0.8125rem;
}

.col-expiration {
  width: 130px;
}

.expiration-pill {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 6px;
}

.badge-normal {
  background-color: #f1f5f9;
  color: #334155;
}

.badge-expiring {
  background-color: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
}

.badge-expired {
  background-color: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}

.col-price {
  width: 120px;
  text-align: right;
}

.col-stock {
  width: 90px;
  text-align: right;
}

.stock-value {
  font-size: 0.9375rem;
}

.col-action {
  width: 70px;
  text-align: center;
}

.btn-edit-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid #ccfbf1;
  background: #f0fdfa;
  color: #0d9488;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-edit-action:hover {
  background: #0d9488;
  color: #ffffff;
  border-color: #0d9488;
}

.btn-edit-action svg {
  width: 15px;
  height: 15px;
}

.table-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  border-top: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 1rem;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.empty-icon-box {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: #f8fafc;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.25rem;
}

.empty-icon-box svg {
  width: 24px;
  height: 24px;
}

.empty-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.empty-description {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.btn-reset-empty {
  margin-top: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #0d9488;
  background: #f0fdfa;
  border: 1px solid #ccfbf1;
  border-radius: 6px;
  cursor: pointer;
}

.btn-reset-empty:hover {
  background: #e6fffa;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.spinner-icon {
  width: 28px;
  height: 28px;
  border: 3px solid #e2e8f0;
  border-top-color: #0d9488;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.loading-text {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}
</style>
