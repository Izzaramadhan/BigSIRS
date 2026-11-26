<script setup>
import { Pencil, Trash2 } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseDataTable from '@/components/ui/BaseDataTable.vue'
import {
  EMPTY,
  formatDayName,
  formatScheduleSummary,
  formatScheduleTime,
} from './doctor-schedule.helpers'

defineProps({
  items: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  /** Nomor urut baris pertama dikurangi 1, untuk kolom "No" lintas halaman. */
  offset: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['edit', 'delete'])

const columns = [
  { key: 'no', label: 'No', align: 'center', class: 'w-16' },
  { key: 'doctor', label: 'Nama Dokter', class: 'min-w-44' },
  { key: 'polyclinic', label: 'Poliklinik', class: 'min-w-40' },
  { key: 'day_of_week', label: 'Hari Praktik', class: 'w-28' },
  { key: 'start_time', label: 'Jam Mulai', align: 'center', class: 'w-28' },
  { key: 'end_time', label: 'Jam Selesai', align: 'center', class: 'w-28' },
  { key: 'is_holiday', label: 'Status', align: 'center', class: 'w-28' },
  { key: 'actions', label: 'Aksi', align: 'center', class: 'w-28' },
]
</script>

<template>
  <BaseDataTable :columns="columns" :rows="items" :loading="loading" caption="Daftar jadwal dokter">
    <template #cell-no="{ index }">
      <span class="text-fg-muted">{{ offset + index + 1 }}</span>
    </template>

    <template #cell-doctor="{ row }">
      <span class="font-medium text-fg">{{ row.doctor?.name || EMPTY }}</span>
    </template>

    <template #cell-polyclinic="{ row }">
      <span class="font-medium text-fg">{{ row.polyclinic?.name || EMPTY }}</span>
    </template>

    <template #cell-day_of_week="{ row }">
      <span class="text-fg">{{ formatDayName(row.day_of_week) }}</span>
    </template>

    <template #cell-start_time="{ row }">
      <span class="font-mono text-xs text-fg">
        {{ formatScheduleTime(row.start_time) || EMPTY }}
      </span>
    </template>

    <template #cell-end_time="{ row }">
      <span class="font-mono text-xs text-fg">
        {{ formatScheduleTime(row.end_time) || EMPTY }}
      </span>
    </template>

    <template #cell-is_holiday="{ row }">
      <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="
          row.is_holiday
            ? 'bg-danger-subtle text-danger-strong'
            : 'bg-success-subtle text-success-strong'
        "
      >
        {{ row.is_holiday ? 'Libur' : 'Praktik' }}
      </span>
    </template>

    <template #cell-actions="{ row }">
      <div class="flex justify-center gap-1">
        <BaseButton
          variant="ghost"
          size="icon"
          :aria-label="`Edit ${formatScheduleSummary(row)}`"
          title="Edit"
          @click="emit('edit', row)"
        >
          <Pencil class="size-4" aria-hidden="true" />
        </BaseButton>
        <BaseButton
          variant="ghost-danger"
          size="icon"
          :aria-label="`Hapus ${formatScheduleSummary(row)}`"
          title="Hapus"
          @click="emit('delete', row)"
        >
          <Trash2 class="size-4" aria-hidden="true" />
        </BaseButton>
      </div>
    </template>

    <template #empty>
      <slot name="empty" />
    </template>
  </BaseDataTable>
</template>
