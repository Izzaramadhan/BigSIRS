<script setup>
import { Pencil, Trash2 } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseDataTable from '@/components/ui/BaseDataTable.vue'
import {
  formatPolyclinicCode,
  formatPolyclinicName,
  formatPolyclinicQuota,
  formatPolyclinicServiceType,
  formatPolyclinicSummary,
  formatPolyclinicWarehouse,
} from './polyclinic.helpers'

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
  { key: 'code', label: 'Kode', class: 'w-32 whitespace-nowrap' },
  { key: 'name', label: 'Nama Poliklinik', class: 'min-w-44' },
  { key: 'service_type', label: 'Jenis Layanan', class: 'w-40 whitespace-nowrap' },
  { key: 'warehouse', label: 'Gudang Default', class: 'min-w-44' },
  { key: 'is_active', label: 'Status', align: 'center', class: 'w-28' },
  { key: 'quota', label: 'Kuota', align: 'center', class: 'w-24 whitespace-nowrap' },
  { key: 'jkn_quota', label: 'Kuota JKN', align: 'center', class: 'w-28 whitespace-nowrap' },
  { key: 'actions', label: 'Aksi', align: 'center', class: 'w-28' },
]
</script>

<template>
  <BaseDataTable :columns="columns" :rows="items" :loading="loading" caption="Daftar poliklinik">
    <template #cell-no="{ index }">
      <span class="text-fg-muted">{{ offset + index + 1 }}</span>
    </template>

    <template #cell-code="{ row }">
      <span class="font-mono text-xs font-medium text-fg">{{ formatPolyclinicCode(row) }}</span>
    </template>

    <template #cell-name="{ row }">
      <span class="font-medium text-fg">{{ formatPolyclinicName(row) }}</span>
    </template>

    <template #cell-service_type="{ row }">
      <span class="text-fg">{{ formatPolyclinicServiceType(row) }}</span>
    </template>

    <template #cell-warehouse="{ row }">
      <span class="text-fg-muted">{{ formatPolyclinicWarehouse(row) }}</span>
    </template>

    <template #cell-is_active="{ row }">
      <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="
          row.is_active
            ? 'bg-success-subtle text-success-strong'
            : 'bg-danger-subtle text-danger-strong'
        "
      >
        {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
      </span>
    </template>

    <template #cell-quota="{ row }">
      <span class="text-fg tabular-nums">{{ formatPolyclinicQuota(row.quota) }}</span>
    </template>

    <template #cell-jkn_quota="{ row }">
      <span class="text-fg tabular-nums">{{ formatPolyclinicQuota(row.jkn_quota) }}</span>
    </template>

    <template #cell-actions="{ row }">
      <div class="flex justify-center gap-1">
        <BaseButton
          variant="ghost"
          size="icon"
          :aria-label="`Edit ${formatPolyclinicSummary(row)}`"
          title="Edit"
          @click="emit('edit', row)"
        >
          <Pencil class="size-4" aria-hidden="true" />
        </BaseButton>
        <BaseButton
          variant="ghost-danger"
          size="icon"
          :aria-label="`Arsipkan ${formatPolyclinicSummary(row)}`"
          title="Arsipkan"
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
