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

            <!-- 2. Kode -->
            <th class="sortable col-code" @click="handleSort('code')">
              <div class="th-content">
                <span>Kode</span>
                <span class="sort-icon-box" v-if="sort && sort.column === 'code'">
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

            <!-- 3. Nama -->
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

            <!-- 4. Satuan -->
            <th class="col-unit">Satuan</th>

            <!-- 5. Kategori -->
            <th class="col-category">Kategori</th>

            <!-- 6. Golongan -->
            <th class="col-classification">Golongan</th>

            <!-- 7. Rute -->
            <th class="col-route">Rute</th>

            <!-- 8. Generik -->
            <th class="col-generic">Generik</th>

            <!-- 9. Kode KFA -->
            <th class="col-kfa">Kode KFA</th>

            <!-- 10. Deskripsi -->
            <th class="col-desc">Deskripsi</th>

            <!-- 11. Aksi -->
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading skeleton -->
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="11" :rows="5" />
          </template>

          <!-- Empty fallback -->
          <template v-else-if="items.length === 0">
            <tr>
              <td colspan="11" class="text-center py-8 text-muted">
                Tidak ada data obat.
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

              <!-- 2. Kode -->
              <td class="col-code font-mono text-muted">
                {{ item.code || '-' }}
              </td>

              <!-- 3. Nama -->
              <td class="col-name font-medium text-navy">
                {{ item.name || '-' }}
              </td>

              <!-- 4. Satuan -->
              <td class="col-unit text-muted">
                {{ item.unit?.name || '-' }}
              </td>

              <!-- 5. Kategori -->
              <td class="col-category text-muted">
                {{ item.category?.name || '-' }}
              </td>

              <!-- 6. Golongan -->
              <td class="col-classification text-muted">
                {{ item.classification?.name || '-' }}
              </td>

              <!-- 7. Rute -->
              <td class="col-route text-muted">
                {{ item.route?.name || '-' }}
              </td>

              <!-- 8. Generik -->
              <td class="col-generic text-muted">
                {{ item.generic?.name || '-' }}
              </td>

              <!-- 9. Kode KFA -->
              <td class="col-kfa font-mono text-muted">
                {{ item.kfa_code || '-' }}
              </td>

              <!-- 10. Deskripsi -->
              <td class="col-desc text-muted truncate-cell" :title="item.description || ''">
                {{ item.description || '-' }}
              </td>

              <!-- 11. Aksi -->
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
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
  min-width: 1200px;
}

.data-table th {
  background: #f8fafc;
  padding: 0.85rem 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary, #64748b);
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  white-space: nowrap;
}

.data-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  color: var(--color-text-navy, #0f172a);
  vertical-align: middle;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.col-no {
  width: 50px;
}

.col-code {
  width: 110px;
  min-width: 100px;
}

.col-name {
  min-width: 180px;
  max-width: 260px;
}

.col-unit {
  min-width: 100px;
}

.col-category {
  min-width: 120px;
}

.col-classification {
  min-width: 120px;
}

.col-route {
  min-width: 100px;
}

.col-generic {
  min-width: 120px;
}

.col-kfa {
  min-width: 110px;
}

.col-desc {
  min-width: 160px;
  max-width: 250px;
}

.truncate-cell {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.col-actions {
  width: 90px;
  text-align: center;
}

.actions-cell {
  text-align: center;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.825rem;
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
  width: 14px !important;
  height: 14px !important;
  color: var(--color-primary, #0f766e);
  flex-shrink: 0;
}

.sort-icon.neutral {
  color: #94a3b8;
  opacity: 0.6;
}

.text-center {
  text-align: center !important;
}

.text-muted {
  color: var(--color-text-secondary, #64748b);
  font-size: 0.85rem;
}

.text-navy {
  color: var(--color-text-navy, #0f172a);
}

.font-medium {
  font-weight: 500;
}

.py-8 {
  padding-top: 2rem !important;
  padding-bottom: 2rem !important;
}
</style>
