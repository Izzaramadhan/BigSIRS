<script setup>
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue'
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue'

defineProps({
  items: {
    type: Array,
    required: true,
  },
  pagination: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['edit', 'delete'])

const getRowNumber = (index, pagination) => {
  return (pagination.current_page - 1) * pagination.per_page + index + 1
}

const formatRupiah = (val) => {
  if (val === null || val === undefined || val === '') {
    return 'Rp0'
  }
  const num = Number(val)
  if (isNaN(num)) {
    return 'Rp0'
  }
  return 'Rp' + num.toLocaleString('id-ID')
}

const getDescription = (item) => {
  if (item.description && item.description.trim() !== '') {
    return item.description
  }
  return '—'
}

const getAplicareCode = (item) => {
  if (item.aplicare_code && String(item.aplicare_code).trim() !== '') {
    return String(item.aplicare_code)
  }
  return '—'
}

const getSirsCode = (item) => {
  if (item.sirs_code && String(item.sirs_code).trim() !== '') {
    return String(item.sirs_code)
  }
  return '—'
}
</script>

<template>
  <div class="table-wrapper">
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no text-center">No</th>
            <th class="col-name">Nama Kelas</th>
            <th class="col-price">Harga Kelas</th>
            <th class="col-description">Deskripsi</th>
            <th class="col-code text-center">Kode Kelas BPJS</th>
            <th class="col-code text-center">Kode Kelas SIRS</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="7" :rows="5" />
          </template>

          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <td class="col-no text-center text-muted">{{ getRowNumber(index, pagination) }}</td>
              <td class="col-name font-medium text-navy">{{ item.name }}</td>
              <td class="col-price font-medium text-default">{{ formatRupiah(item.price) }}</td>
              <td class="col-description text-muted">{{ getDescription(item) }}</td>
              <td class="col-code text-center text-muted">{{ getAplicareCode(item) }}</td>
              <td class="col-code text-center text-muted">{{ getSirsCode(item) }}</td>
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
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.05),
    0 2px 4px -1px rgba(0, 0, 0, 0.03);
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
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.data-table th {
  background-color: var(--color-page-bg, #f8fafc);
  color: var(--color-text-secondary, #64748b);
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  text-align: left;
}

.data-table tbody tr {
  transition: background-color 0.2s;
}

.data-table tbody tr:hover {
  background-color: var(--color-page-bg, #f8fafc);
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

.text-default {
  color: var(--color-text-primary, #1e293b);
}

.font-medium {
  font-weight: 500;
}

.col-no {
  width: 60px;
}

.col-name {
  width: 240px;
}

.col-price {
  width: 180px;
}

.col-description {
  width: auto;
}

.col-code {
  width: 150px;
}

.col-actions {
  width: 120px;
}
</style>
