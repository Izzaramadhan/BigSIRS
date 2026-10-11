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

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};
</script>

<template>
  <div class="table-wrapper">
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no text-center">NO</th>
            <th @click="handleSort('name')" class="sortable col-name">
              <div class="th-content">
                NAMA PAKET
                <span class="sort-icon" v-if="sortConfig.column === 'name'">
                  <svg v-if="sortConfig.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-items">TINDAKAN</th>
            <th @click="handleSort('total_amount')" class="sortable col-price">
              <div class="th-content">
                HARGA PAKET
                <span class="sort-icon" v-if="sortConfig.column === 'total_amount'">
                  <svg v-if="sortConfig.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-actions text-center">AKSI</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="5" :rows="5" />
          </template>
          
          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <!-- NO -->
              <td class="col-no text-center text-muted">{{ getRowNumber(index) }}</td>
              
              <!-- NAMA PAKET -->
              <td class="col-name">
                <div class="font-medium text-navy">{{ item.name }}</div>
              </td>

              <!-- TINDAKAN -->
              <td class="col-items">
                <div class="text-sm text-gray-500">
                  <span v-if="item.items_summary && item.items_summary.length" :title="item.items_summary.map(i => i.procedure_name).join(', ')">
                    <span class="font-medium">{{ item.items_count || item.items_summary.length }} item: </span>
                    <span v-for="(i, idx) in item.items_summary.slice(0, 2)" :key="i.id">
                      {{ i.procedure_name }}{{ idx < Math.min(item.items_summary.length, 2) - 1 ? ', ' : '' }}
                    </span>
                    <span v-if="item.items_summary.length > 2">...</span>
                  </span>
                  <span v-else class="text-muted">-</span>
                </div>
              </td>
              
              <!-- HARGA PAKET -->
              <td class="col-price">
                <div class="font-medium text-navy">{{ formatCurrency(item.total_amount) }}</div>
              </td>
              
              <!-- AKSI -->
              <td class="col-actions">
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
  background: #fff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.table-container {
  overflow-x: auto;
  min-height: 200px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.data-table th,
.data-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  text-align: left;
  vertical-align: middle;
}

.data-table th {
  background: #f8fafc;
  color: var(--color-text-navy, #1e293b);
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
}

.sortable:hover {
  background: #f1f5f9;
}

.th-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.th-content.justify-center {
  justify-content: center;
}

.sort-icon {
  display: flex;
  color: var(--color-primary, #0b57d0);
}

.sort-icon svg {
  width: 16px;
  height: 16px;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.text-center { text-align: center !important; }
.text-muted { color: #94a3b8; }
.text-navy { color: var(--color-text-navy, #1e293b); }
.text-gray-500 { color: #64748b; }
.font-medium { font-weight: 500; }
.text-sm { font-size: 0.875rem; }

/* Column Widths */
.col-no { width: 5%; }
.col-name { width: 30%; }
.col-items { width: 35%; }
.col-price { width: 15%; }
.col-actions { width: 15%; }
</style>
