<script setup>
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue';
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue';

defineProps({
  items: {
    type: Array,
    required: true
  },
  pagination: {
    type: Object,
    required: true
  },
  sort: {
    type: Object,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['edit', 'delete', 'sort']);

const getRowNumber = (index, pagination) => {
  const page = pagination?.current_page || pagination?.currentPage || 1;
  const perPage = pagination?.per_page || pagination?.perPage || 10;
  return (page - 1) * perPage + index + 1;
};

const handleSort = (column) => {
  emit('sort', column);
};

const formatTariff = (value) => {
  if (value === null || value === undefined || value === '') return '-';
  const num = Number(value);
  if (isNaN(num)) return '-';
  return `Rp${num.toLocaleString('id-ID')}`;
};
</script>

<template>
  <div class="table-wrapper">
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th class="sortable col-code" @click="handleSort('code')">
              <div class="th-content">
                Kode
                <span class="sort-icon" v-if="sort.column === 'code'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="sortable col-name" @click="handleSort('name')">
              <div class="th-content">
                Nama Diagnosa
                <span class="sort-icon" v-if="sort.column === 'name'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-desc">Deskripsi</th>
            <th class="sortable col-inacbg" @click="handleSort('inacbg_code')">
              <div class="th-content">
                INACBG
                <span class="sort-icon" v-if="sort.column === 'inacbg_code'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-tariff">Tarif Kelas</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="7" :rows="5" />
          </template>

          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <td class="col-no text-muted">{{ getRowNumber(index, pagination) }}</td>
              <td class="col-code">
                <span class="code-badge">{{ item.code }}</span>
              </td>
              <td class="col-name">
                <div class="font-medium text-navy">{{ item.name }}</div>
                <div v-if="item.english_name" class="text-xs text-secondary italic mt-0.5">
                  {{ item.english_name }}
                </div>
              </td>
              <td class="col-desc">
                <span v-if="item.description" class="desc-text" :title="item.description">
                  {{ item.description }}
                </span>
                <span v-else class="text-muted">-</span>
              </td>
              <td class="col-inacbg">
                <div v-if="item.inacbg_code || item.inacbg_name" class="inacbg-box">
                  <span v-if="item.inacbg_code" class="badge-inacbg">{{ item.inacbg_code }}</span>
                  <div v-if="item.inacbg_name" class="inacbg-name" :title="item.inacbg_name">
                    {{ item.inacbg_name }}
                  </div>
                </div>
                <span v-else class="text-muted">-</span>
              </td>
              <td class="col-tariff">
                <div class="tariff-stack">
                  <div class="tariff-row">
                    <span class="tariff-label">Kelas 1: </span>
                    <span class="tariff-val">{{ formatTariff(item.class_1_tariff) }}</span>
                  </div>
                  <div class="tariff-row">
                    <span class="tariff-label">Kelas 2: </span>
                    <span class="tariff-val">{{ formatTariff(item.class_2_tariff) }}</span>
                  </div>
                  <div class="tariff-row">
                    <span class="tariff-label">Kelas 3: </span>
                    <span class="tariff-val">{{ formatTariff(item.class_3_tariff) }}</span>
                  </div>
                </div>
              </td>
              <td class="actions-cell">
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
  background: var(--color-card-bg, #ffffff);
  border-radius: 12px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.table-container {
  overflow-x: auto;
  width: 100%;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  min-width: 800px;
}

.data-table th,
.data-table td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.data-table th {
  font-weight: 600;
  font-size: 0.8rem;
  color: var(--color-text-secondary, #64748b);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: var(--color-page-bg, #f8fafc);
  white-space: nowrap;
}

.data-table td {
  font-size: 0.875rem;
  color: var(--color-text-navy, #17243D);
  vertical-align: middle;
}

.data-table tbody tr:hover {
  background-color: var(--color-page-bg, #f8fafc);
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background-color: #f1f5f9;
  color: var(--color-text-navy, #17243D);
}

.th-content {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.sort-icon svg {
  width: 14px;
  height: 14px;
}

.col-no {
  width: 50px;
  text-align: center;
}

.col-code {
  width: 110px;
}

.col-name {
  min-width: 180px;
  max-width: 260px;
}

.col-desc {
  min-width: 160px;
  max-width: 240px;
}

.col-inacbg {
  min-width: 150px;
  max-width: 200px;
}

.col-tariff {
  min-width: 160px;
}

.col-actions {
  width: 90px;
}

.code-badge {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  font-family: monospace;
  font-weight: 600;
  font-size: 0.8125rem;
  color: var(--color-primary, #087f78);
  background-color: #e6f5f4;
  border-radius: 4px;
}

.badge-inacbg {
  display: inline-block;
  padding: 0.15rem 0.4rem;
  font-size: 0.72rem;
  font-weight: 600;
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
}

.inacbg-box {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.inacbg-name {
  font-size: 0.75rem;
  color: var(--color-text-secondary, #64748b);
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.desc-text {
  font-size: 0.8125rem;
  color: var(--color-text-secondary, #64748b);
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.tariff-stack {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.775rem;
}

.tariff-row {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.tariff-label {
  color: var(--color-text-secondary, #64748b);
  font-weight: 500;
  min-width: 48px;
}

.tariff-val {
  color: var(--color-text-navy, #17243D);
  font-weight: 600;
}

.text-muted {
  color: #94a3b8;
}

.text-navy {
  color: var(--color-text-navy, #17243D);
}

.text-secondary {
  color: var(--color-text-secondary, #64748b);
}

.font-medium {
  font-weight: 500;
}

.actions-cell {
  text-align: center;
}
</style>
