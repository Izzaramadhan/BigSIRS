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

const getWardDisplayName = (item) => {
  if (item.ward_display_name) {
    return item.ward_display_name
  }
  if (item.ward) {
    const floor = item.ward.floor
    if (floor) {
      if (floor.building?.name) {
        return `${item.ward.name} (${floor.name} - ${floor.building.name})`
      }
      return `${item.ward.name} (${floor.name})`
    }
    return item.ward.name || '-'
  }
  return '-'
}

const getClassName = (item) => {
  if (item.class && item.class.name) {
    return item.class.name
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
            <th class="col-ward">Nama Bangsal</th>
            <th class="col-name">Nama Kamar</th>
            <th class="col-class text-center">Nama Kelas</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="5" :rows="5" />
          </template>

          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <td class="col-no text-center text-muted">{{ getRowNumber(index, pagination) }}</td>
              <td class="col-ward font-medium text-navy">{{ getWardDisplayName(item) }}</td>
              <td class="col-name text-default">{{ item.name }}</td>
              <td class="col-class text-center text-muted">{{ getClassName(item) }}</td>
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

.text-default {
  color: var(--color-text-primary, #1e293b);
}

.font-medium {
  font-weight: 500;
}

.col-no {
  width: 60px;
}

.col-ward {
  width: 380px;
}

.col-name {
  width: auto;
}

.col-class {
  width: 150px;
}

.col-actions {
  width: 120px;
}
</style>
