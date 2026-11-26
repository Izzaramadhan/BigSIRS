<script setup>
import { Pencil, Trash2 } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseDataTable from '@/components/ui/BaseDataTable.vue'
import {
  formatInacbgId,
  formatInsuranceGovernment,
  formatInsuranceName,
  formatInsuranceSummary,
} from './insurance-type.helpers'

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
  { key: 'name', label: 'Jenis Asuransi', class: 'min-w-48' },
  {
    key: 'is_government',
    label: 'Asuransi Pemerintah',
    align: 'center',
    class: 'w-44 whitespace-nowrap',
  },
  { key: 'inacbg_id', label: 'ID INACBG', align: 'center', class: 'w-32 whitespace-nowrap' },
  { key: 'actions', label: 'Aksi', align: 'center', class: 'w-28' },
]
</script>

<template>
  <BaseDataTable :columns="columns" :rows="items" :loading="loading" caption="Daftar jenis asuransi">
    <template #cell-no="{ index }">
      <span class="text-fg-muted">{{ offset + index + 1 }}</span>
    </template>

    <template #cell-name="{ row }">
      <span class="font-medium text-fg">{{ formatInsuranceName(row) }}</span>
    </template>

    <template #cell-is_government="{ row }">
      <span class="text-fg">{{ formatInsuranceGovernment(row) }}</span>
    </template>

    <template #cell-inacbg_id="{ row }">
      <span
        class="text-fg-muted"
        :class="{ 'font-mono text-xs text-fg': Boolean(row.inacbg_id) }"
      >
        {{ formatInacbgId(row) }}
      </span>
    </template>

    <template #cell-actions="{ row }">
      <div class="flex justify-center gap-1">
        <BaseButton
          variant="ghost"
          size="icon"
          :aria-label="`Edit ${formatInsuranceSummary(row)}`"
          title="Edit"
          @click="emit('edit', row)"
        >
          <Pencil class="size-4" aria-hidden="true" />
        </BaseButton>
        <BaseButton
          variant="ghost-danger"
          size="icon"
          :aria-label="`Hapus ${formatInsuranceSummary(row)}`"
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
