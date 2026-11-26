<script setup>
import { ExternalLink, Pencil, Trash2 } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseDataTable from '@/components/ui/BaseDataTable.vue'
import {
  canPreviewLegacyResource,
  EMPTY,
  formatLetterTypeDescription,
  formatLetterTypeName,
  formatLetterTypeResource,
  formatLetterTypeSummary,
} from './letter-type.helpers'

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

const emit = defineEmits(['preview-legacy', 'edit', 'delete'])

const columns = [
  { key: 'no', label: 'No', align: 'center', class: 'w-16' },
  { key: 'name', label: 'Nama', class: 'min-w-48' },
  { key: 'description', label: 'Deskripsi', class: 'min-w-56' },
  { key: 'legacy_resource', label: 'Resource', class: 'min-w-44' },
  { key: 'actions', label: 'Aksi', align: 'center', class: 'w-36' },
]
</script>

<template>
  <BaseDataTable :columns="columns" :rows="items" :loading="loading" caption="Daftar surat-surat">
    <template #cell-no="{ index }">
      <span class="text-fg-muted">{{ offset + index + 1 }}</span>
    </template>

    <template #cell-name="{ row }">
      <span class="font-medium text-fg">{{ formatLetterTypeName(row) }}</span>
    </template>

    <template #cell-description="{ row }">
      <span class="text-fg-muted">{{ formatLetterTypeDescription(row) }}</span>
    </template>

    <template #cell-legacy_resource="{ row }">
      <code
        v-if="formatLetterTypeResource(row)"
        class="rounded bg-canvas px-1.5 py-0.5 font-mono text-xs text-fg"
      >
        {{ formatLetterTypeResource(row) }}
      </code>
      <span v-else class="text-fg-muted">{{ EMPTY }}</span>
    </template>

    <template #cell-actions="{ row }">
      <div class="flex justify-center gap-1">
        <BaseButton
          v-if="canPreviewLegacyResource(row)"
          variant="ghost"
          size="icon"
          :aria-label="`Lihat Format Lama ${formatLetterTypeSummary(row)}`"
          title="Lihat Format Lama"
          @click="emit('preview-legacy', row)"
        >
          <ExternalLink class="size-4" aria-hidden="true" />
        </BaseButton>
        <BaseButton
          variant="ghost"
          size="icon"
          :aria-label="`Edit ${formatLetterTypeSummary(row)}`"
          title="Edit"
          @click="emit('edit', row)"
        >
          <Pencil class="size-4" aria-hidden="true" />
        </BaseButton>
        <BaseButton
          variant="ghost-danger"
          size="icon"
          :aria-label="`Hapus ${formatLetterTypeSummary(row)}`"
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
