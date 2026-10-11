<script setup>
import MasterDataStatusBadge from '@/components/master-data/shared/MasterDataStatusBadge.vue';
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

const formatCurrency = (val) => {
  if (val === null || val === undefined || val === '') return '-';
  const num = Number(val);
  if (isNaN(num)) return '-';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(num);
};

const isValidUrl = (url) => {
  if (!url || typeof url !== 'string' || !url.trim()) return false;
  try {
    const parsed = new URL(url.trim());
    return parsed.protocol === 'http:' || parsed.protocol === 'https:';
  } catch {
    return false;
  }
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

            <!-- 2. Grup -->
            <th class="sortable col-name" @click="handleSort('name')">
              <div class="th-content">
                <span>Grup</span>
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

            <!-- 3. Tipe -->
            <th class="col-type">Tipe</th>

            <!-- 4. Kategori -->
            <th class="col-category">Kategori</th>

            <!-- 5. Item Pemeriksaan -->
            <th class="col-items">Item Pemeriksaan</th>

            <!-- 6. Harga -->
            <th class="col-price">Harga</th>

            <!-- 7. Harga Interpretasi -->
            <th class="col-price">Harga Interpretasi</th>

            <!-- 8. Status -->
            <th class="col-status text-center">Status</th>

            <!-- 9. Loinc Code -->
            <th class="col-loinc">Loinc Code</th>

            <!-- 10. Loinc Url -->
            <th class="col-url">Loinc Url</th>

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
                Tidak ada data grup radiologi.
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

              <!-- 2. Grup -->
              <td class="col-name font-medium text-navy">
                {{ item.name || '-' }}
              </td>

              <!-- 3. Tipe -->
              <td class="col-type text-muted">
                {{ item.type?.name || '-' }}
              </td>

              <!-- 4. Kategori -->
              <td class="col-category text-muted">
                {{ item.category?.name || '-' }}
              </td>

              <!-- 5. Item Pemeriksaan -->
              <td class="col-items">
                <div v-if="item.item_groups && item.item_groups.length > 0" class="badge-group">
                  <span 
                    v-for="ig in item.item_groups" 
                    :key="ig.id" 
                    class="item-badge"
                  >
                    {{ ig.name }}
                  </span>
                </div>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- 6. Harga -->
              <td class="col-price font-medium text-navy">
                {{ formatCurrency(item.price) }}
              </td>

              <!-- 7. Harga Interpretasi -->
              <td class="col-price font-medium text-navy">
                {{ formatCurrency(item.interpretation_price) }}
              </td>

              <!-- 8. Status -->
              <td class="col-status text-center">
                <MasterDataStatusBadge :is-active="item.is_active" />
              </td>

              <!-- 9. Loinc Code -->
              <td class="col-loinc">
                <span class="code-badge" v-if="item.loinc_code">{{ item.loinc_code }}</span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- 10. Loinc Url -->
              <td class="col-url">
                <a 
                  v-if="isValidUrl(item.loinc_url)" 
                  :href="item.loinc_url" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  class="table-link"
                  :title="item.loinc_url"
                >
                  <span class="url-text">Buka tautan</span>
                  <svg 
                    class="external-icon" 
                    width="12" 
                    height="12" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="2" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                  >
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                  </svg>
                </a>
                <span v-else-if="item.loinc_url" class="text-muted url-text" :title="item.loinc_url">
                  {{ item.loinc_url }}
                </span>
                <span v-else class="text-muted">-</span>
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
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.table-container {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  min-width: 1100px;
  border-collapse: collapse;
  font-size: 0.875rem;
  text-align: left;
}

.data-table th {
  background-color: #f8fafc;
  color: var(--color-text-navy, #0f172a);
  font-weight: 600;
  padding: 0.875rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #cbd5e1);
  white-space: nowrap;
  user-select: none;
}

.data-table th.sortable {
  cursor: pointer;
}

.data-table th.sortable:hover {
  background-color: #f1f5f9;
}

.th-content {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
}

.sort-icon-box {
  display: inline-flex;
  align-items: center;
  color: var(--color-primary, #0f766e);
}

.sort-icon-box.neutral {
  color: #94a3b8;
}

.data-table td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  color: var(--color-text-navy, #0f172a);
  vertical-align: middle;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background-color: #f8fafc;
}

.col-no {
  width: 50px;
  min-width: 50px;
}

.col-name {
  min-width: 160px;
}

.col-type {
  min-width: 120px;
}

.col-category {
  min-width: 130px;
}

.col-items {
  min-width: 180px;
  max-width: 250px;
}

.badge-group {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.item-badge {
  display: inline-block;
  background-color: #f1f5f9;
  color: #334155;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid #e2e8f0;
}

.col-price {
  min-width: 120px;
  white-space: nowrap;
}

.col-status {
  width: 100px;
  min-width: 100px;
}

.col-loinc {
  min-width: 100px;
}

.col-url {
  min-width: 120px;
}

.col-actions {
  width: 90px;
  min-width: 90px;
}

.text-center {
  text-align: center;
}

.text-muted {
  color: var(--color-text-secondary, #64748b);
}

.font-medium {
  font-weight: 500;
}

.text-navy {
  color: var(--color-text-navy, #0f172a);
}

.code-badge {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.8125rem;
  background: #f1f5f9;
  color: #334155;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}

.table-link {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  color: var(--color-primary, #0f766e);
  text-decoration: none;
  font-size: 0.8125rem;
  font-weight: 500;
}

.table-link:hover {
  text-decoration: underline;
  color: var(--color-primary-dark, #0d5f58);
}

.url-text {
  max-width: 120px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: inline-block;
}

.external-icon {
  flex-shrink: 0;
}

.actions-cell {
  white-space: nowrap;
}
</style>
