<script setup>
import { computed, useId } from 'vue'
import { RotateCcw } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseSearchInput from '@/components/ui/BaseSearchInput.vue'
import { PER_PAGE_OPTIONS } from './sirs-bed-availability.helpers'

const props = defineProps({
  search: {
    type: String,
    default: '',
  },
  perPage: {
    type: Number,
    default: 10,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:search', 'update:perPage', 'refresh'])

const selectId = useId()

const localSearch = computed({
  get: () => props.search,
  set: (val) => emit('update:search', val),
})

const localPerPage = computed({
  get: () => props.perPage,
  set: (val) => emit('update:perPage', Number(val)),
})
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2 text-sm text-fg-muted">
      <label :for="selectId" class="font-medium text-fg">Tampilkan</label>
      <select
        :id="selectId"
        v-model="localPerPage"
        aria-label="Pilihan jumlah data per halaman"
        :disabled="loading"
        class="h-10 rounded-lg border border-line-strong bg-surface px-3 font-sans text-sm text-fg transition-colors focus:border-primary focus:ring-3 focus:ring-primary/15 focus:outline-none disabled:cursor-not-allowed disabled:bg-canvas disabled:opacity-70"
      >
        <option v-for="opt in PER_PAGE_OPTIONS" :key="opt" :value="opt">
          {{ opt }}
        </option>
      </select>
      <span>entri</span>
    </div>

    <div class="flex w-full flex-1 flex-wrap items-center justify-end gap-2 sm:w-auto sm:flex-initial">
      <BaseSearchInput
        v-model="localSearch"
        label="Cari kelas atau ruang"
        placeholder="Cari kelas atau ruang"
        :disabled="loading"
      />

      <BaseButton
        variant="secondary"
        size="sm"
        aria-label="Bersihkan pencarian"
        title="Bersihkan filter pencarian dan kembali ke halaman 1"
        :disabled="loading"
        @click="emit('refresh')"
      >
        <template #icon><RotateCcw class="size-4" aria-hidden="true" /></template>
        Bersihkan Pencarian
      </BaseButton>
    </div>
  </div>
</template>
