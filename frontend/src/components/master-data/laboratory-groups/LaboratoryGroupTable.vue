<script setup>
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue';
import MasterDataStatusBadge from '@/components/master-data/shared/MasterDataStatusBadge.vue';
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
  sort: {
    type: Object,
    default: () => ({ column: 'name', direction: 'asc' })
  },
  pagination: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['sort', 'edit', 'delete']);

const handleSort = (column) => {
  let direction = 'asc';
  if (props.sort.column === column) {
    direction = props.sort.direction === 'asc' ? 'desc' : 'asc';
  }
  emit('sort', { column, direction });
};

const getRowNumber = (index) => {
  return (props.pagination.current_page - 1) * props.pagination.per_page + index + 1;
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

const getLoincUrl = (item) => {
  const url = item.loinc_url || item.category?.loinc_url;
  return isValidUrl(url) ? url.trim() : null;
};

const getSnomedUrl = (item) => {
  const url = item.snomed_url || item.category?.snomed_url;
  return isValidUrl(url) ? url.trim() : null;
};
</script>

<template>
  <div class="table-wrapper">
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no text-center">No</th>
            <th @click="handleSort('name')" class="sortable col-group">
              <div class="th-content">
                <span>Grup</span>
                <span class="sort-icon" v-if="sort.column === 'name'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-category">Kategori</th>
            <th class="col-items">Item</th>
            <th @click="handleSort('price')" class="sortable col-price text-right">
              <div class="th-content justify-end">
                <span>Harga</span>
                <span class="sort-icon" v-if="sort.column === 'price'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-jaspel text-right">Harga Jaspel</th>
            <th @click="handleSort('is_active')" class="sortable col-status text-center">
              <div class="th-content justify-center">
                <span>Status</span>
                <span class="sort-icon" v-if="sort.column === 'is_active'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-loinc-code">LOINC Code</th>
            <th class="col-loinc-url text-center">LOINC URL</th>
            <th class="col-snomed-code">SNOMED Code</th>
            <th class="col-snomed-url text-center">SNOMED URL</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <!-- Loading State -->
          <template v-if="loading">
            <MasterDataSkeletonRow :rows="5" :columns="12" />
          </template>

          <!-- Data Rows -->
          <template v-else-if="items.length > 0">
            <tr v-for="(item, index) in items" :key="item.id">
              <!-- 1. No -->
              <td class="col-no text-center text-muted">
                {{ getRowNumber(index) }}
              </td>

              <!-- 2. Grup -->
              <td class="col-group font-medium text-navy">
                {{ item.name || '-' }}
              </td>

              <!-- 3. Kategori -->
              <td class="col-category">
                {{ item.category?.name || '-' }}
              </td>

              <!-- 4. Item -->
              <td class="col-items">
                <div v-if="item.items && item.items.length > 0" class="items-list">
                  <span
                    v-for="labItem in item.items"
                    :key="labItem.id"
                    class="item-badge"
                  >
                    {{ labItem.name }}<span v-if="labItem.unit" class="item-unit"> ({{ labItem.unit }})</span>
                  </span>
                </div>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- 5. Harga -->
              <td class="col-price text-right font-mono">
                {{ formatCurrency(item.price) }}
              </td>

              <!-- 6. Harga Jaspel -->
              <td class="col-jaspel text-right font-mono">
                {{ item.harga_jaspel ? formatCurrency(item.harga_jaspel) : '-' }}
              </td>

              <!-- 7. Status -->
              <td class="col-status text-center">
                <MasterDataStatusBadge :is-active="Boolean(item.is_active)" />
              </td>

              <!-- 8. LOINC Code -->
              <td class="col-loinc-code">
                {{ item.loinc_code || item.category?.loinc_code || '-' }}
              </td>

              <!-- 9. LOINC URL -->
              <td class="col-loinc-url text-center">
                <a
                  v-if="getLoincUrl(item)"
                  :href="getLoincUrl(item)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="link-external"
                  title="Buka tautan LOINC"
                >
                  Buka tautan
                </a>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- 10. SNOMED Code -->
              <td class="col-snomed-code">
                {{ item.snomed_code || item.category?.snomed_code || '-' }}
              </td>

              <!-- 11. SNOMED URL -->
              <td class="col-snomed-url text-center">
                <a
                  v-if="getSnomedUrl(item)"
                  :href="getSnomedUrl(item)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="link-external"
                  title="Buka tautan SNOMED"
                >
                  Buka tautan
                </a>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- 12. Aksi -->
              <td class="col-actions text-center">
                <MasterDataActionButtons
                  :show-toggle="false"
                  @edit="$emit('edit', item)"
                  @delete="$emit('delete', item)"
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
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.table-container {
  overflow-x: auto;
  max-width: 100%;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.875rem;
  white-space: nowrap;
}

.data-table th,
.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.data-table th {
  background: var(--color-bg-table-header, #f8fafc);
  font-weight: 600;
  color: var(--color-text-navy, #0f172a);
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 2px solid var(--color-border-soft, #e2e8f0);
}

.data-table tbody tr:hover {
  background-color: var(--color-bg-hover, #f8fafc);
}

.th-content {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background-color: #f1f5f9;
}

.sort-icon svg {
  width: 14px;
  height: 14px;
}

.col-no {
  width: 50px;
}

.col-group {
  min-width: 180px;
}

.col-category {
  min-width: 150px;
}

.col-items {
  min-width: 220px;
  max-width: 320px;
  white-space: normal;
}

.col-price,
.col-jaspel {
  min-width: 110px;
}

.col-status {
  min-width: 90px;
}

.col-loinc-code,
.col-snomed-code {
  min-width: 110px;
}

.col-loinc-url,
.col-snomed-url {
  min-width: 110px;
}

.col-actions {
  min-width: 90px;
  width: 90px;
}

.items-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  align-items: center;
}

.item-badge {
  display: inline-block;
  background: #f1f5f9;
  color: #334155;
  border-radius: 4px;
  padding: 0.15rem 0.4rem;
  font-size: 0.75rem;
  line-height: 1.3;
}

.item-unit {
  color: #64748b;
  font-size: 0.7rem;
}

.link-external {
  color: var(--color-primary, #0f766e);
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.link-external:hover {
  text-decoration: underline;
  color: #0d9488;
}

.text-navy {
  color: var(--color-text-navy, #0f172a);
}

.text-muted {
  color: #94a3b8;
}

.font-medium {
  font-weight: 500;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.justify-center {
  justify-content: center;
}

.justify-end {
  justify-content: flex-end;
}
</style>
