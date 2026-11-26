<script setup>
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseDataTable from '@/components/ui/BaseDataTable.vue'
import { formatBedCount, formatSirsLastUpdate, formatTextCell } from './sirs-bed-availability.helpers'

const props = defineProps({
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

const columns = [
  { key: 'no', label: 'No', align: 'center', class: 'w-16' },
  { key: 'className', label: 'Kelas', class: 'min-w-36' },
  { key: 'roomName', label: 'Ruang', class: 'min-w-48' },
  { key: 'capacity', label: 'Kapasitas', align: 'center', class: 'w-28' },
  { key: 'available', label: 'Tersedia', align: 'center', class: 'w-28' },
  { key: 'lastUpdatedAt', label: 'Last Update', class: 'min-w-44 whitespace-nowrap' },
  { key: 'actions', label: 'Aksi', align: 'center', class: 'w-40' },
]

function rowNumber(index) {
  const page = props.pagination?.current_page || 1
  const perPage = props.pagination?.per_page || 10
  return (page - 1) * perPage + index + 1
}
</script>

<template>
  <BaseDataTable
    :columns="columns"
    :rows="items"
    :loading="loading"
    caption="Daftar ketersediaan bed SIRS"
  >
    <template #cell-no="{ index }">
      <span class="text-fg-muted">{{ rowNumber(index) }}</span>
    </template>

    <template #cell-className="{ row }">
      <span class="font-medium text-fg">{{ formatTextCell(row.className) }}</span>
    </template>

    <template #cell-roomName="{ row }">
      <span class="text-fg">{{ formatTextCell(row.roomName) }}</span>
    </template>

    <template #cell-capacity="{ row }">
      <span class="font-semibold text-fg tabular-nums">{{ formatBedCount(row.capacity) }}</span>
    </template>

    <template #cell-available="{ row }">
      <span class="font-semibold text-success tabular-nums">{{
        formatBedCount(row.available)
      }}</span>
    </template>

    <template #cell-lastUpdatedAt="{ row }">
      <span class="whitespace-nowrap text-fg-muted">{{
        formatSirsLastUpdate(row.lastUpdatedAt)
      }}</span>
    </template>

    <template #cell-actions>
      <div class="flex justify-center">
        <BaseButton
          variant="secondary"
          size="sm"
          disabled
          title="Alur tindakan belum ditentukan."
          aria-label="Alur tindakan belum ditentukan"
        >
          Belum tersedia
        </BaseButton>
      </div>
    </template>

    <template #empty>
      <slot name="empty" />
    </template>
  </BaseDataTable>
</template>
