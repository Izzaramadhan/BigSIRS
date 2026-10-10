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
</script>

<template>
  <div class="table-wrapper">
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no text-center">No</th>
            <th @click="handleSort('name')" class="sortable col-name">
              <div class="th-content">
                Komponen Tindakan
                <span class="sort-icon" v-if="sortConfig.column === 'name'">
                  <svg v-if="sortConfig.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th @click="handleSort('description')" class="sortable">
              <div class="th-content">
                Deskripsi
                <span class="sort-icon" v-if="sortConfig.column === 'description'">
                  <svg v-if="sortConfig.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="4" :rows="5" />
          </template>
          
          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <td class="col-no text-center text-muted">{{ getRowNumber(index) }}</td>
              <td class="col-name font-medium text-navy">{{ item.name }}</td>
              <td class="text-muted">{{ item.description || '-' }}</td>
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

.col-no {
  width: 60px;
}

.col-name {
  width: 300px;
}

.col-actions {
  width: 120px;
}
</style>
