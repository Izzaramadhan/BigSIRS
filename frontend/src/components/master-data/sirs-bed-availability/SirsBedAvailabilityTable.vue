<script setup>
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue'

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

const getRowNumber = (index, pagination) => {
  const currentPage = pagination?.current_page || 1
  const perPage = pagination?.per_page || 10
  return (currentPage - 1) * perPage + index + 1
}

/**
 * Format tanggal dan waktu ke format Indonesia:
 * Contoh: 22 Oktober 2026, 09.30
 */
const formatLastUpdate = (dateVal) => {
  if (!dateVal) return '—'
  const date = new Date(dateVal)
  if (isNaN(date.getTime())) return '—'

  try {
    const datePart = date.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')
    return `${datePart}, ${hours}.${minutes}`
  } catch {
    return '—'
  }
}

const formatNumber = (val) => {
  if (val === null || val === undefined || val === '') return '—'
  const num = Number(val)
  return isNaN(num) ? '—' : String(num)
}
</script>

<template>
  <div class="table-wrapper">
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no text-center">No</th>
            <th class="col-class">Kelas</th>
            <th class="col-room">Ruang</th>
            <th class="col-capacity text-center">Kapasitas</th>
            <th class="col-available text-center">Tersedia</th>
            <th class="col-updated">Last Update</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="7" :rows="5" />
          </template>

          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id || index">
              <td class="col-no text-center text-muted">
                {{ getRowNumber(index, pagination) }}
              </td>
              <td class="col-class font-medium text-navy">
                {{ item.className || '—' }}
              </td>
              <td class="col-room text-navy">
                {{ item.roomName || '—' }}
              </td>
              <td class="col-capacity text-center font-semibold text-navy">
                {{ formatNumber(item.capacity) }}
              </td>
              <td class="col-available text-center font-semibold text-emerald">
                {{ formatNumber(item.available) }}
              </td>
              <td class="col-updated text-muted">
                {{ formatLastUpdate(item.lastUpdatedAt) }}
              </td>
              <td class="col-actions text-center">
                <button
                  type="button"
                  class="btn-action-disabled"
                  disabled
                  title="Alur tindakan belum ditentukan."
                  aria-label="Alur tindakan belum ditentukan"
                >
                  Belum tersedia
                </button>
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
  border: 1px solid var(--color-border-soft, #e2e8f0);
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
  font-size: 0.825rem;
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
  font-size: 0.9rem;
}

.text-navy {
  color: var(--color-text-navy, #1e293b);
}

.text-emerald {
  color: #059669;
}

.font-medium {
  font-weight: 500;
}

.font-semibold {
  font-weight: 600;
}

.col-no {
  width: 60px;
}

.col-class {
  min-width: 140px;
}

.col-room {
  min-width: 180px;
}

.col-capacity {
  width: 110px;
}

.col-available {
  width: 110px;
}

.col-updated {
  min-width: 200px;
}

.col-actions {
  width: 130px;
}

/* Tombol aksi non-aktif sementara */
.btn-action-disabled {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.35rem 0.65rem;
  font-size: 0.775rem;
  font-weight: 500;
  color: var(--color-text-secondary, #94a3b8);
  background-color: #f1f5f9;
  border: 1px dashed #cbd5e1;
  border-radius: 6px;
  cursor: not-allowed;
  user-select: none;
}
</style>
