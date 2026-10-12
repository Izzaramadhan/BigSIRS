<script setup>
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue';
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue';

const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  sortConfig: {
    type: Object,
    required: true
  },
  pagination: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['sort', 'edit', 'delete']);

const handleSort = (column) => {
  let direction = 'asc';
  if (props.sortConfig.column === column) {
    direction = props.sortConfig.direction === 'asc' ? 'desc' : 'asc';
  }
  emit('sort', { column, direction });
};

const getRowNumber = (index) => {
  return (props.pagination.current_page - 1) * props.pagination.per_page + index + 1;
};

const getTotalPercentage = (components) => {
  if (!components || components.length === 0) return 0;
  return components.reduce((sum, comp) => sum + Number(comp.percentage || 0), 0);
};

const getPercentageStatusClass = (total) => {
  if (total === 100) return 'text-success';
  if (total < 100) return 'text-warning';
  return 'text-danger';
};
</script>

<template>
  <div class="table-wrapper">
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no text-center">No</th>
            <th @click="handleSort('code')" class="sortable col-code">
              <div class="th-content">
                Kode
                <span class="sort-icon" v-if="sortConfig.column === 'code'">
                  <svg v-if="sortConfig.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th @click="handleSort('name')" class="sortable col-name">
              <div class="th-content">
                Jenis Tarif
                <span class="sort-icon" v-if="sortConfig.column === 'name'">
                  <svg v-if="sortConfig.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-desc">Deskripsi</th>
            <th class="col-components">Komponen Pembentuk (%)</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="5" :rows="5" />
          </template>
          
          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <td class="col-no text-center text-muted">{{ getRowNumber(index) }}</td>
              <td class="col-code font-medium">
                <span class="code-badge">{{ item.code || '-' }}</span>
              </td>
              <td class="col-name font-medium text-navy">
                {{ item.name }}
              </td>
              <td class="col-desc">
                <div v-if="item.description" class="desc-text" :title="item.description">{{ item.description }}</div>
                <div v-else class="text-muted">-</div>
              </td>
              <td class="col-components">
                <div class="components-list" v-if="item.components && item.components.length > 0">
                  <div class="component-item" v-for="comp in item.components" :key="comp.id">
                    <span class="comp-name">{{ comp.component?.name || 'Unknown' }}</span>
                    <span class="comp-val">{{ Number(comp.percentage) }}%</span>
                  </div>
                  <div class="component-total">
                    <span class="comp-name font-medium">Total:</span>
                    <span class="comp-val font-medium" :class="getPercentageStatusClass(getTotalPercentage(item.components))">
                      {{ getTotalPercentage(item.components) }}%
                    </span>
                  </div>
                </div>
                <div v-else class="text-muted text-sm italic">
                  Belum ada komponen terhubung
                </div>
              </td>
              <td class="actions-cell text-center">
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
.table-wrapper {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  overflow: hidden;
}

.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  white-space: nowrap;
}

.data-table th,
.data-table td {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--color-border-soft);
  vertical-align: middle;
}

.data-table td.col-components {
  vertical-align: top;
}

.data-table th {
  background-color: var(--color-page-bg);
  color: var(--color-text-secondary);
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  text-align: left;
}

.data-table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
}

.data-table th.sortable:hover {
  background-color: var(--color-border-soft);
}

.th-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sort-icon {
  display: flex;
  color: var(--color-primary);
}

.sort-icon svg {
  width: 14px;
  height: 14px;
}

.data-table tbody tr {
  transition: background-color 0.2s;
}

.data-table tbody tr:hover {
  background-color: var(--color-page-bg);
}

.text-center {
  text-align: center;
}

.text-muted {
  color: var(--color-text-secondary);
}

.text-navy {
  color: var(--color-text-navy);
}

.font-medium {
  font-weight: 500;
}

.code-badge {
  display: inline-block;
  padding: 0.25rem 0.6rem;
  background-color: #f1f5f9;
  color: #475569;
  border-radius: 4px;
  font-family: monospace;
  font-size: 0.85rem;
  border: 1px solid #e2e8f0;
}

.sub-text {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  margin-top: 0.25rem;
  font-weight: 400;
  white-space: normal;
}

.components-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 220px;
}

.component-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  padding-bottom: 0.25rem;
  border-bottom: 1px dashed #e2e8f0;
}

.component-item:last-of-type {
  border-bottom: none;
  margin-bottom: 0.25rem;
}

.component-total {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  padding-top: 0.25rem;
  border-top: 1px solid #cbd5e1;
}

.comp-name {
  color: var(--color-text-navy);
}

.comp-val {
  color: #475569;
  min-width: 40px;
  text-align: right;
}

.text-success { color: #16a34a; }
.text-warning { color: #d97706; }
.text-danger { color: #dc2626; }
.text-sm { font-size: 0.875rem; }
.italic { font-style: italic; }

.col-no { width: 60px; }
.col-code { width: 140px; }
.col-name { width: 220px; }
.col-desc { width: 220px; }
.col-actions { width: 120px; }

.desc-text {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: normal;
  line-height: 1.4;
}
</style>
