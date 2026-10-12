<script setup>
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue';
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue';
import MasterDataStatusBadge from '@/components/master-data/shared/MasterDataStatusBadge.vue';

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

const emit = defineEmits(['sort', 'edit', 'delete', 'toggle-status']);

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
            <th class="col-category">KATEGORI</th>
            <th @click="handleSort('name')" class="sortable col-code">
              <div class="th-content">
                KODE
                <span class="sort-icon" v-if="sortConfig.column === 'name'">
                  <svg v-if="sortConfig.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-icd9">ICD9CM</th>
            <th @click="handleSort('code')" class="sortable col-name">
              <div class="th-content">
                TINDAKAN
                <span class="sort-icon" v-if="sortConfig.column === 'code'">
                  <svg v-if="sortConfig.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-tariff-type">JENIS TARIF</th>
            <th class="col-tariff-price">HARGA</th>
            <th class="col-polyclinic">POLIKLINIK</th>
            <th @click="handleSort('is_visible')" class="sortable col-status text-center">
              <div class="th-content justify-center">
                STATUS
                <span class="sort-icon" v-if="sortConfig.column === 'is_visible'">
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
            <MasterDataSkeletonRow :columns="10" :rows="5" />
          </template>
          
          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <!-- NO -->
              <td class="col-no text-center text-muted">{{ getRowNumber(index) }}</td>
              
              <!-- KATEGORI -->
              <td class="col-category">
                <div v-if="item.category" class="text-sm font-medium text-navy category-text" :title="item.category.name">{{ item.category.name }}</div>
                <div v-else class="text-muted">-</div>
              </td>

              <!-- KODE (Mapped to item.name based on user requirement) -->
              <td class="col-code font-medium">
                <span v-if="item.name" class="code-badge">{{ item.name }}</span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- ICD9CM -->
              <td class="col-icd9">
                <span v-if="item.icd9_cm" class="code-badge bg-gray" :title="item.icd9_cm.name">{{ item.icd9_cm.code }}</span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- TINDAKAN (Mapped to item.code based on user requirement) -->
              <td class="col-name font-medium text-navy">
                <div class="name-text" :title="item.code">{{ item.code }}</div>
              </td>

              <!-- JENIS TARIF -->
              <td class="col-tariff-type p-0">
                <div v-if="!item.tariffs || item.tariffs.length === 0" class="p-3 text-muted">-</div>
                <div v-else class="tariff-inner-list">
                  <div v-for="tariff in item.tariffs" :key="tariff.id" class="tariff-inner-item">
                    {{ tariff.tariff_type?.name || '-' }}
                  </div>
                </div>
              </td>

              <!-- HARGA -->
              <td class="col-tariff-price p-0">
                <div v-if="!item.tariffs || item.tariffs.length === 0" class="p-3 text-muted text-right">-</div>
                <div v-else class="tariff-inner-list">
                  <div v-for="tariff in item.tariffs" :key="tariff.id" class="tariff-inner-item text-right font-medium text-navy">
                    {{ formatCurrency(tariff.total_amount) }}
                  </div>
                </div>
              </td>

              <!-- POLIKLINIK -->
              <td class="col-polyclinic">
                <div v-if="!item.polyclinics || item.polyclinics.length === 0" class="text-muted">-</div>
                <div v-else class="polyclinic-list">
                  <span v-for="(poly, pIdx) in item.polyclinics" :key="pIdx" class="poly-chip" :title="poly.name">
                    {{ poly.name }}
                  </span>
                </div>
              </td>

              <!-- STATUS -->
              <td class="col-status text-center">
                <MasterDataStatusBadge :is-active="item.is_visible" />
              </td>

              <!-- AKSI -->
              <td class="col-actions">
                <MasterDataActionButtons 
                  :has-status="true"
                  :is-active="item.is_visible"
                  @edit="$emit('edit', item)"
                  @delete="$emit('delete', item)"
                  @toggle-status="$emit('toggle-status', item)"
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
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.table-container {
  width: 100%;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  white-space: nowrap;
  min-width: 1200px;
}

.data-table th,
.data-table td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid var(--color-border-soft);
  vertical-align: middle;
}

.data-table td.p-0 {
  padding: 0 !important;
  vertical-align: top;
}

.p-3 {
  padding: 0.875rem 1rem;
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

.justify-center {
  justify-content: center;
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

.text-center { text-align: center; }
.text-right { text-align: right; }
.text-muted { color: var(--color-text-secondary); }
.text-navy { color: var(--color-text-navy); }
.font-medium { font-weight: 500; }
.italic { font-style: italic; }
.text-sm { font-size: 0.875rem; }
.text-xs { font-size: 0.75rem; }

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

.code-badge.bg-gray {
  background-color: #f8fafc;
  border-color: #cbd5e1;
}

.name-text, .category-text {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: normal;
  line-height: 1.4;
}

.tariff-inner-list {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.tariff-inner-item {
  padding: 0.6rem 1rem;
  border-bottom: 1px dashed #e2e8f0;
  white-space: nowrap;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  min-height: 38px;
}

.tariff-inner-list .tariff-inner-item:last-child {
  border-bottom: none;
}

.polyclinic-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  max-width: 200px;
}

.poly-chip {
  display: inline-flex;
  padding: 0.15rem 0.5rem;
  background-color: #e0e7ff;
  color: #3730a3;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
}

.col-no { width: 60px; }
.col-category { width: 140px; }
.col-code { width: 100px; }
.col-icd9 { width: 100px; }
.col-name { width: 180px; }
.col-tariff-type { width: 140px; }
.col-tariff-price { width: 140px; }
.col-polyclinic { width: 160px; }
.col-status { width: 100px; }
.col-actions { width: 100px; padding-right: 1.5rem !important; }
</style>
