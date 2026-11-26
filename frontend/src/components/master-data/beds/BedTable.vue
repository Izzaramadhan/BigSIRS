<script setup>
import { Pencil, Trash2 } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseDataTable from '@/components/ui/BaseDataTable.vue'
import { formatBedClass, formatBedDescription, formatBedRoom } from './bed.helpers'

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
  { key: 'room', label: 'Nama Kamar', class: 'w-80 min-w-56' },
  { key: 'class', label: 'Nama Kelas', align: 'center', class: 'w-36' },
  { key: 'name', label: 'Nama Bed', class: 'w-52' },
  { key: 'description', label: 'Deskripsi', class: 'min-w-48' },
  { key: 'actions', label: 'Aksi', align: 'center', class: 'w-28' },
]
</script>

<template>
  <BaseDataTable :columns="columns" :rows="items" :loading="loading" caption="Daftar bed">
    <template #cell-no="{ index }">
      <span class="text-fg-muted">{{ offset + index + 1 }}</span>
    </template>

    <template #cell-room="{ row }">
      <span class="font-medium">{{ formatBedRoom(row) }}</span>
    </template>

    <template #cell-class="{ row }">
      <span class="text-fg-muted">{{ formatBedClass(row) }}</span>
    </template>

    <template #cell-description="{ row }">
      <span class="text-fg-muted">{{ formatBedDescription(row) }}</span>
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
