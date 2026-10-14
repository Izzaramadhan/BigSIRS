<script setup>
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue';
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue';

const props = defineProps({
  items: {
    type: Array,
    required: true,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  sortConfig: {
    type: Object,
    default: () => ({ column: 'created_at', direction: 'desc' })
  },
  pagination: {
    type: Object,
    default: () => ({ current_page: 1, per_page: 10 })
  },
  searchKeyword: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['sort', 'edit', 'delete', 'add']);

const handleSort = (column) => {
  let direction = 'asc';
  if (props.sortConfig.column === column) {
    direction = props.sortConfig.direction === 'asc' ? 'desc' : 'asc';
  }
  emit('sort', { column, direction });
};

const getRowNumber = (index) => {
  const currentPage = props.pagination?.current_page || 1;
  const perPage = props.pagination?.per_page || 10;
  return (currentPage - 1) * perPage + index + 1;
};
</script>

<template>
  <div class="mapping-table-wrapper">
    <div class="mapping-table-container">
      <table class="mapping-data-table">
        <thead>
          <tr>
            <th class="col-no text-center">No</th>

            <th @click="handleSort('name')" class="sortable col-name">
              <span class="th-content">
                <span>Nama Tindakan</span>
                <span class="sort-icon-box" :class="{ 'is-active': sortConfig.column === 'name' }">
                  <svg 
                    v-if="sortConfig.column === 'name' && sortConfig.direction === 'asc'" 
                    class="mapping-sort-icon" 
                    width="14" 
                    height="14" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="2" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                  >
                    <polyline points="18 15 12 9 6 15"></polyline>
                  </svg>
                  <svg 
                    v-else-if="sortConfig.column === 'name' && sortConfig.direction === 'desc'" 
                    class="mapping-sort-icon" 
                    width="14" 
                    height="14" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="2" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                  >
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                  <svg 
                    v-else 
                    class="mapping-sort-icon neutral" 
                    width="14" 
                    height="14" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="2" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                  >
                    <polyline points="7 15 12 20 17 15"></polyline>
                    <polyline points="7 9 12 4 17 9"></polyline>
                  </svg>
                </span>
              </span>
            </th>

            <th class="col-employees">Pegawai Terpetakan</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <!-- Loading skeleton -->
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="4" :rows="5" />
          </template>

          <!-- Empty state row -->
          <template v-else-if="items.length === 0">
            <tr>
              <td colspan="4" class="table-empty-cell">
                <div class="empty-state-content">
                  <svg 
                    v-if="searchKeyword"
                    class="empty-state-icon" 
                    width="44" 
                    height="44" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.5"
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                  >
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                  <svg 
                    v-else
                    class="empty-state-icon" 
                    width="44" 
                    height="44" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.5"
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                  >
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                  </svg>
                  <h4 class="empty-title">
                    {{ searchKeyword ? 'Mapping tindakan user tidak ditemukan.' : 'Belum ada mapping tindakan user.' }}
                  </h4>
                  <p class="empty-desc">
                    {{ searchKeyword 
                      ? `Tidak ada data mapping tindakan yang cocok dengan kata kunci "${searchKeyword}".` 
                      : 'Mulai tambahkan pemetaan tindakan dengan pegawai ke dalam sistem.' 
                    }}
                  </p>
                  <button 
                    type="button" 
                    class="empty-add-btn" 
                    @click="emit('add')"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                      <line x1="12" y1="5" x2="12" y2="19"></line>
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Tambah Mapping
                  </button>
                </div>
              </td>
            </tr>
          </template>

          <!-- Data rows -->
          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <!-- 1. No -->
              <td class="col-no text-center text-muted">
                {{ getRowNumber(index) }}
              </td>

              <!-- 2. Nama Tindakan -->
              <td class="col-name font-medium text-navy">
                {{ item.procedure?.name || '-' }}
              </td>

              <!-- 3. Pegawai Terpetakan -->
              <td class="col-employees">
                <div class="employee-tags" v-if="item.employees && item.employees.length">
                  <span 
                    v-for="emp in item.employees.slice(0, 3)" 
                    :key="emp.id"
                    class="badge badge-info"
                  >
                    {{ emp.name }} &mdash; {{ emp.profession || emp.code || 'Pegawai' }}
                  </span>
                  <span 
                    v-if="item.employees.length > 3" 
                    class="badge badge-secondary"
                    :title="item.employees.slice(3).map(e => `${e.name} (${e.profession || e.code || 'Pegawai'})`).join(', ')"
                  >
                    +{{ item.employees.length - 3 }} lainnya
                  </span>
                </div>
                <span v-else class="text-muted">Belum ada pegawai</span>
              </td>

              <!-- 4. Aksi -->
              <td class="col-actions text-center">
                <MasterDataActionButtons 
                  @edit="emit('edit', item)"
                  @delete="emit('delete', item)"
                />
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.mapping-table-wrapper {
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.mapping-table-container {
  overflow-x: auto;
}

.mapping-data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.mapping-data-table th {
  background: #f8fafc;
  padding: 0.85rem 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary, #64748b);
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  white-space: nowrap;
}

.mapping-data-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  vertical-align: middle;
}

.mapping-data-table tbody tr:last-child td {
  border-bottom: none;
}

.mapping-data-table tbody tr:hover {
  background: #f8fafc;
}

.col-no {
  width: 60px;
}

.col-name {
  width: 320px;
  min-width: 220px;
}

.col-employees {
  width: auto;
  min-width: 260px;
}

.col-actions {
  width: 100px;
  white-space: nowrap;
}

.sortable {
  cursor: pointer;
  user-select: none;
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

.mapping-sort-icon {
  width: 14px !important;
  height: 14px !important;
  min-width: 14px;
  max-width: 14px;
  min-height: 14px;
  max-height: 14px;
  color: var(--color-primary, #0284c7);
  flex-shrink: 0;
}

.mapping-sort-icon.neutral {
  color: #94a3b8;
  opacity: 0.6;
}

.employee-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  align-items: center;
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
  line-height: 1.2;
}

.badge-info {
  background-color: #e0f2fe;
  color: #0369a1;
  border: 1px solid #bae6fd;
}

.badge-secondary {
  background-color: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.text-muted {
  color: #94a3b8;
  font-size: 0.85rem;
}

.text-navy {
  color: var(--color-text-navy, #0f172a);
}

.font-medium {
  font-weight: 500;
}

/* Empty State inside Table */
.table-empty-cell {
  padding: 48px 16px !important;
  text-align: center;
  background: #ffffff;
}

.empty-state-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
}

.empty-state-icon {
  width: 44px !important;
  height: 44px !important;
  min-width: 44px;
  max-width: 44px;
  min-height: 44px;
  max-height: 44px;
  color: #94a3b8;
  flex-shrink: 0;
  margin-bottom: 0.25rem;
}

.empty-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-navy, #0f172a);
  margin: 0;
}

.empty-desc {
  font-size: 0.875rem;
  color: var(--color-text-secondary, #64748b);
  margin: 0;
}

.empty-add-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-top: 0.5rem;
  background: var(--color-primary, #0284c7);
  color: #ffffff;
  border: none;
  padding: 0.45rem 1rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.empty-add-btn:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}
</style>
