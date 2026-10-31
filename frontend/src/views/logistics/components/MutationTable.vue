<script setup>
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue';

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
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'view',
  'page-change',
  'per-page-change',
  'print',
  'export',
  'reset-filter'
]);

const handlePerPageChange = (event) => {
  emit('per-page-change', Number(event.target.value));
};

const getRowNumber = (index) => {
  const currentPage = props.paginationInfo?.current_page || 1;
  const perPage = props.paginationInfo?.per_page || 10;
  return (currentPage - 1) * perPage + index + 1;
};

const formatMutationDate = (mut) => {
  if (mut.dateDisplay) return mut.dateDisplay;
  if (!mut.mutationDate) return '-';
  try {
    const d = new Date(mut.mutationDate);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const day = String(d.getDate()).padStart(2, '0');
    const month = months[d.getMonth()];
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const mins = String(d.getMinutes()).padStart(2, '0');
    return `${day} ${month} ${year}, ${hours}:${mins} WIB`;
  } catch {
    return mut.mutationDate;
  }
};
</script>

<template>
  <div class="mutation-table-card card">
    <!-- Top toolbar: per-page, count info, print and export buttons -->
    <div class="table-toolbar">
      <div class="toolbar-left">
        <div class="per-page-control">
          <label for="mutation-per-page" class="per-page-label">Tampilkan:</label>
          <select
            id="mutation-per-page"
            :value="paginationInfo.per_page"
            class="form-select per-page-select"
            @change="handlePerPageChange"
          >
            <option :value="10">10 data</option>
            <option :value="25">25 data</option>
            <option :value="50">50 data</option>
            <option :value="100">100 data</option>
          </select>
        </div>

        <span class="toolbar-info">
          Menampilkan {{ paginationInfo.from }}–{{ paginationInfo.to }} dari {{ paginationInfo.total }} transaksi
        </span>
      </div>

      <div class="toolbar-right">
        <!-- Cetak button -->
        <button
          type="button"
          class="btn-outline btn-tool"
          @click="emit('print')"
          title="Cetak Daftar Mutasi"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
          <span>Cetak</span>
        </button>

        <!-- Ekspor Excel button -->
        <button
          type="button"
          class="btn-outline btn-tool"
          @click="emit('export')"
          title="Ekspor Data ke Excel/CSV"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          <span>Ekspor Excel</span>
        </button>
      </div>
    </div>

    <!-- Table Container -->
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no text-center">No</th>
            <th class="col-date">Tanggal</th>
            <th class="col-source">Gudang Asal</th>
            <th class="col-target">Gudang Tujuan</th>
            <th class="col-notes">Catatan</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading State Skeleton -->
          <MasterDataSkeletonRow v-if="loading" :columns="6" :rows="5" />

          <!-- Empty State Row -->
          <tr v-else-if="items.length === 0">
            <td colspan="6" class="empty-cell">
              <MasterDataEmptyState
                :is-search="true"
                title="Data mutasi tidak ditemukan"
                search-message="Tidak ada riwayat mutasi barang yang cocok dengan kriteria filter."
              >
                <template #action>
                  <button type="button" class="btn-primary" @click="emit('reset-filter')">
                    Reset Filter
                  </button>
                </template>
              </MasterDataEmptyState>
            </td>
          </tr>

          <!-- Data Rows: 6 Columns -->
          <tr v-else v-for="(mut, index) in items" :key="mut.id">
            <!-- 1. No -->
            <td class="col-no text-center text-muted font-medium">
              {{ getRowNumber(index) }}
            </td>

            <!-- 2. Tanggal -->
            <td class="col-date text-muted">
              {{ formatMutationDate(mut) }}
            </td>

            <!-- 3. Gudang Asal -->
            <td class="col-source">
              <span class="wh-badge source-badge">
                {{ mut.sourceWarehouse }}
              </span>
            </td>

            <!-- 4. Gudang Tujuan -->
            <td class="col-target">
              <span class="wh-badge target-badge">
                {{ mut.targetWarehouse }}
              </span>
            </td>

            <!-- 5. Catatan -->
            <td class="col-notes text-muted truncate-cell" :title="mut.notes || '-'">
              {{ mut.notes || '-' }}
            </td>

            <!-- 6. Aksi: Lihat Detail -->
            <td class="col-actions text-center">
              <button
                type="button"
                class="btn-detail"
                title="Lihat Detail Mutasi"
                aria-label="Lihat Detail"
                @click="emit('view', mut)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <span>Lihat Detail</span>
              </button>
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
.mutation-table-card {
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

.btn-tool {
  padding: 0.45rem 0.85rem;
  font-size: 0.82rem;
  height: 36px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-tool svg {
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
  min-width: 820px;
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

/* Columns: No, Tanggal, Gudang Asal, Gudang Tujuan, Catatan, Aksi */
.col-no {
  width: 60px;
  min-width: 50px;
}

.col-date {
  width: 190px;
  min-width: 170px;
}

.col-source {
  width: 180px;
  min-width: 150px;
}

.col-target {
  width: 180px;
  min-width: 150px;
}

.col-notes {
  min-width: 250px;
}

.col-actions {
  width: 130px;
  min-width: 120px;
}

.wh-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  font-size: 0.76rem;
  font-weight: 600;
  white-space: nowrap;
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

.truncate-cell {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 420px;
}

.btn-detail {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  background: #ffffff;
  color: var(--color-primary, #087f78);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-detail:hover {
  background: var(--color-primary-light, #dff7f4);
  border-color: var(--color-primary, #087f78);
}

.btn-detail svg {
  width: 14px;
  height: 14px;
}

.empty-cell {
  padding: 2.5rem !important;
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
}

@media print {
  .table-toolbar,
  .table-pagination-wrapper,
  .col-actions {
    display: none !important;
  }

  .mutation-table-card {
    border: none !important;
    box-shadow: none !important;
  }

  .data-table {
    min-width: 100% !important;
  }
}
</style>
