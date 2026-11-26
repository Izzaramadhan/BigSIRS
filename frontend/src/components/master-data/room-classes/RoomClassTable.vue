<script setup>
import { Pencil, Trash2 } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseDataTable from '@/components/ui/BaseDataTable.vue'
import {
  formatRoomClassDescription,
  formatRoomClassExternalCode,
  formatRoomClassPrice,
} from './room-class.helpers'

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
  { key: 'name', label: 'Nama Kelas', class: 'min-w-44' },
  { key: 'price', label: 'Harga Kelas', class: 'w-36 whitespace-nowrap' },
  { key: 'description', label: 'Deskripsi', class: 'min-w-48' },
  { key: 'aplicare_code', label: 'Kode Kelas BPJS', class: 'w-36 whitespace-nowrap' },
  { key: 'sirs_code', label: 'Kode Kelas SIRS', class: 'w-36 whitespace-nowrap' },
  { key: 'actions', label: 'Aksi', align: 'center', class: 'w-28' },
]
</script>

<template>
  <BaseDataTable :columns="columns" :rows="items" :loading="loading" caption="Daftar kelas">
    <template #cell-no="{ index }">
      <span class="text-fg-muted">{{ offset + index + 1 }}</span>
    </template>

    <template #cell-name="{ row }">
      <span class="font-medium text-fg">{{ row.name }}</span>
    </template>

    <template #cell-price="{ row }">
      <span class="font-medium text-fg tabular-nums">{{ formatRoomClassPrice(row) }}</span>
    </template>

    <template #cell-description="{ row }">
      <span class="text-fg-muted">{{ formatRoomClassDescription(row) }}</span>
    </template>

    <template #cell-aplicare_code="{ row }">
      <span class="text-fg-muted" :class="{ 'font-mono text-xs': Boolean(row.aplicare_code) }">
        {{ formatRoomClassExternalCode(row.aplicare_code) }}
      </span>
    </template>

    <template #cell-sirs_code="{ row }">
      <span class="text-fg-muted" :class="{ 'font-mono text-xs': Boolean(row.sirs_code) }">
        {{ formatRoomClassExternalCode(row.sirs_code) }}
      </span>
    </template>

    <template #cell-actions="{ row }">
      <div class="flex justify-center gap-1">
        <BaseButton
          variant="ghost"
          size="icon"
          :aria-label="`Edit ${row.name}`"
          title="Edit"
          @click="emit('edit', row)"
        >
          <Pencil class="size-4" aria-hidden="true" />
        </BaseButton>
        <BaseButton
          variant="ghost-danger"
          size="icon"
          :aria-label="`Hapus ${row.name}`"
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
