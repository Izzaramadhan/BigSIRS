<script setup>
import { Eye, Pencil, Trash2 } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseDataTable from '@/components/ui/BaseDataTable.vue'
import { formatDoctorName, formatDoctorSpecialization } from './doctor.helpers'

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

const emit = defineEmits(['view', 'edit', 'delete'])

const columns = [
  { key: 'no', label: 'No', align: 'center', class: 'w-16' },
  { key: 'name', label: 'Nama Dokter', class: 'min-w-48' },
  { key: 'specialization', label: 'Spesialisasi', class: 'min-w-44' },
  { key: 'actions', label: 'Aksi', align: 'center', class: 'w-36' },
]
</script>

<template>
  <BaseDataTable :columns="columns" :rows="items" :loading="loading" caption="Daftar dokter">
    <template #cell-no="{ index }">
      <span class="text-fg-muted">{{ offset + index + 1 }}</span>
    </template>

    <template #cell-name="{ row }">
      <span class="font-medium text-fg">{{ formatDoctorName(row) }}</span>
    </template>

    <template #cell-specialization="{ row }">
      <span class="text-fg-muted">{{ formatDoctorSpecialization(row) }}</span>
    </template>

    <template #cell-actions="{ row }">
      <div class="flex justify-center gap-1">
        <BaseButton
          variant="ghost"
          size="icon"
          :aria-label="`Lihat detail ${formatDoctorName(row)}`"
          title="Lihat Detail"
          @click="emit('view', row)"
        >
          <Eye class="size-4" aria-hidden="true" />
        </BaseButton>
        <BaseButton
          variant="ghost"
          size="icon"
          :aria-label="`Edit ${formatDoctorName(row)}`"
          title="Edit"
          @click="emit('edit', row)"
        >
          <Pencil class="size-4" aria-hidden="true" />
        </BaseButton>
        <BaseButton
          variant="ghost-danger"
          size="icon"
          :aria-label="`Hapus ${formatDoctorName(row)}`"
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
