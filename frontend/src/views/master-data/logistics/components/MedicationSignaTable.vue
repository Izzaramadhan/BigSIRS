<script setup>
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue';
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue';

defineProps({
  items: {
    type: Array,
    required: true,
    default: () => []
  },
  pagination: {
    type: Object,
    required: true
  },
  sort: {
    type: Object,
    default: () => ({ column: 'name', direction: 'asc' })
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['edit', 'delete', 'sort']);

const getRowNumber = (index, pagination) => {
  const currentPage = pagination?.current_page || 1;
  const perPage = pagination?.per_page || 10;
  return (currentPage - 1) * perPage + index + 1;
};

const handleSort = (column) => {
  emit('sort', column);
};
</script>

<template>
  <div class="table-wrapper">
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <!-- 1. No -->
            <th class="col-no text-center">No</th>

            <!-- 2. Nama -->
            <th class="sortable col-name" @click="handleSort('name')">
              <div class="th-content">
                <span>Nama</span>
                <span class="sort-icon-box" v-if="sort && sort.column === 'name'">
                  <svg 
                    v-if="sort.direction === 'asc'" 
                    class="sort-icon" 
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
                    v-else 
                    class="sort-icon" 
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
                </span>
                <span class="sort-icon-box neutral" v-else>
                  <svg 
                    class="sort-icon neutral" 
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
              </div>
            </th>

            <!-- 3. Aksi -->
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading skeleton -->
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="3" :rows="5" />
          </template>

          <!-- Empty fallback -->
          <template v-else-if="items.length === 0">
            <tr>
              <td colspan="3" class="text-center py-8 text-muted">
                Tidak ada data signa obat.
              </td>
            </tr>
          </template>

          <!-- Data rows -->
          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <!-- 1. No -->
              <td class="col-no text-center text-muted">
                {{ getRowNumber(index, pagination) }}
              </td>

              <!-- 2. Nama -->
              <td class="col-name font-medium text-navy">
                {{ item.name || '-' }}
              </td>

              <!-- 3. Aksi -->
              <td class="col-actions text-center actions-cell">
                <MasterDataActionButtons 
                  :show-toggle="false"
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
  width: 100%;
}

.table-container {
  width: 100%;
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  background: var(--color-card-bg, #ffffff);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.875rem;
}

.data-table thead th {
  padding: 0.75rem 1rem;
  background-color: var(--color-bg-table-header, #f8fafc);
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  color: var(--color-text-secondary, #64748b);
  font-weight: 600;
  white-space: nowrap;
}

.data-table thead th.sortable {
  cursor: pointer;
  user-select: none;
}

.data-table thead th.sortable:hover {
  background-color: var(--color-bg-hover, #f1f5f9);
}

.th-content {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.sort-icon-box {
  display: inline-flex;
  align-items: center;
  color: var(--color-primary, #0f766e);
}

.sort-icon-box.neutral {
  color: #cbd5e1;
}

.sort-icon.neutral {
  opacity: 0.5;
}

.data-table tbody tr {
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  transition: background-color 0.15s;
}

.data-table tbody tr:last-child {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background-color: var(--color-bg-hover, #f8fafc);
}

.data-table td {
  padding: 0.875rem 1rem;
  vertical-align: middle;
}

.col-no {
  width: 60px;
}

.col-name {
  min-width: 250px;
}

.col-actions {
  width: 120px;
}

.actions-cell {
  white-space: nowrap;
}

.text-center {
  text-align: center;
}

.text-muted {
  color: var(--color-text-secondary, #64748b);
}

.text-navy {
  color: var(--color-text-navy, #0f172a);
}

.font-medium {
  font-weight: 500;
}

.py-8 {
  padding-top: 2rem;
  padding-bottom: 2rem;
}
</style>
