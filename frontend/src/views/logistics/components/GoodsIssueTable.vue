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

const formatDateIndo = (val) => {
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

const getPurposeDisplay = (item) => {
  if (item.notes && item.notes.trim() !== '') return item.notes;
  if (item.purpose && item.purpose.trim() !== '' && item.purpose !== 'Kebutuhan Internal' && item.purpose !== '-') {
    return item.purpose;
  }
  return '-';
};
</script>

<template>
  <div class="issue-table-card card">
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
      <table class="issue-table">
        <thead>
          <tr>
            <th class="col-no text-center">No.</th>
            <th class="col-date">Tanggal</th>
            <th class="col-officer">Petugas</th>
            <th class="col-purpose">Keperluan</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading Skeleton -->
          <template v-if="loading">
            <tr v-for="n in 6" :key="'skel-' + n" class="skeleton-row">
              <td class="text-center"><div class="skeleton-pill skel-no"></div></td>
              <td><div class="skeleton-line skel-date"></div></td>
              <td><div class="skeleton-line skel-officer"></div></td>
              <td><div class="skeleton-line skel-purpose"></div></td>
              <td class="text-center"><div class="skeleton-pill skel-actions"></div></td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else-if="items.length === 0">
            <td colspan="5" class="empty-cell">
              <MasterDataEmptyState
                :is-search="Boolean(search)"
                title="Data pengeluaran barang tidak ditemukan"
                search-message="Tidak ada data pengeluaran yang cocok dengan kriteria pencarian Anda."
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
            class="data-row"
          >
            <!-- 1. No -->
            <td class="col-no text-center font-medium">
              {{ getRowNumber(index) }}
            </td>

            <!-- 2. Tanggal -->
            <td class="col-date">
              <span class="date-text font-medium text-navy">
                {{ item.formattedDate || item.formatted_date || formatDateIndo(item.issue_date || item.issueDate) }}
              </span>
            </td>

            <!-- 3. Petugas -->
            <td class="col-officer">
              <span class="officer-name font-semibold text-navy">
                {{ item.officerName || item.officer_name || '-' }}
              </span>
            </td>

            <!-- 4. Keperluan -->
            <td class="col-purpose">
              <span class="purpose-text text-secondary">
                {{ getPurposeDisplay(item) }}
              </span>
            </td>

            <!-- 5. Aksi (Detail, Edit, Hapus) -->
            <td class="col-actions text-center">
              <div class="action-buttons-group">
                <button
                  type="button"
                  class="btn-action-icon btn-detail"
                  title="Lihat Detail Pengeluaran"
                  @click="emit('view', item)"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>

                <button
                  type="button"
                  class="btn-action-icon btn-edit"
                  title="Edit Pengeluaran"
                  @click="emit('edit', item)"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>

                <button
                  type="button"
                  class="btn-action-icon btn-delete"
                  title="Hapus Pengeluaran"
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
.issue-table-card {
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
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background-color: #ffffff;
  color: #1e293b;
  cursor: pointer;
}

.records-select:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
}

.search-input-wrapper {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.search-label {
  font-size: 0.85rem;
  color: #475569;
  font-weight: 500;
}

.search-input {
  padding: 0.4rem 0.75rem;
  font-size: 0.85rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  width: 220px;
  color: #1e293b;
  transition: border-color 0.15s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 2px rgba(8, 127, 120, 0.1);
}

/* Table */
.table-responsive {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.issue-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  text-align: left;
}

.issue-table th {
  background-color: #f8fafc;
  color: #475569;
  font-weight: 700;
  padding: 0.85rem 1rem;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}

.issue-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.data-row:hover {
  background-color: #f8fafc;
}

.col-no {
  width: 60px;
}

.col-date {
  width: 140px;
  white-space: nowrap;
}

.col-officer {
  width: 220px;
}

.col-purpose {
  min-width: 200px;
}

.col-actions {
  width: 130px;
  white-space: nowrap;
}

.text-center {
  text-align: center;
}

.font-medium {
  font-weight: 500;
}

.font-semibold {
  font-weight: 600;
}

.text-navy {
  color: var(--color-text-navy, #17243d);
}

.text-secondary {
  color: #475569;
}

.text-muted {
  color: #64748b;
}

/* Action Buttons Group */
.action-buttons-group {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
}

.btn-action-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-action-icon svg {
  width: 15px;
  height: 15px;
}

.btn-detail:hover {
  background: #f0fdfa;
  border-color: #5eead4;
  color: var(--color-primary, #087f78);
}

.btn-edit:hover {
  background: #fefce8;
  border-color: #fde047;
  color: #ca8a04;
}

.btn-delete:hover {
  background: #fef2f2;
  border-color: #fca5a5;
  color: #ef4444;
}

/* Table Footer */
.table-pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  background: #ffffff;
  border-top: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  font-size: 0.85rem;
  color: #64748b;
}

/* Skeletons */
.skeleton-row td {
  padding: 1rem;
}

.skeleton-pill,
.skeleton-line {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

.skel-no {
  width: 24px;
  height: 16px;
  margin: 0 auto;
}

.skel-date {
  width: 90px;
  height: 16px;
}

.skel-officer {
  width: 140px;
  height: 16px;
}

.skel-purpose {
  width: 180px;
  height: 16px;
}

.skel-actions {
  width: 90px;
  height: 28px;
  margin: 0 auto;
  border-radius: 6px;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

.empty-cell {
  padding: 2.5rem 1rem;
  text-align: center;
}

@media (max-width: 640px) {
  .table-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .search-input-wrapper {
    width: 100%;
  }

  .search-input {
    width: 100%;
  }

  .table-pagination-wrapper {
    flex-direction: column;
    align-items: center;
  }
}
</style>
