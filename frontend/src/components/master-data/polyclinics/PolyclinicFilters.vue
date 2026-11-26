<script setup>
import { computed, ref, watch } from 'vue'
import { RefreshCw } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseSearchInput from '@/components/ui/BaseSearchInput.vue'
import { fieldControlClass } from '@/components/ui/fieldControlClass'
import { useDebouncedRef } from '@/composables/core/useDebouncedRef'

const props = defineProps({
  search: {
    type: String,
    default: '',
  },
  isActive: {
    type: Boolean,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:search', 'update:isActive', 'refresh'])

const term = ref(props.search)
const debouncedTerm = useDebouncedRef(term, 400)

watch(debouncedTerm, (value) => {
  const normalized = value.trim()
  if (normalized !== props.search) emit('update:search', normalized)
})

watch(
  () => props.search,
  (value) => {
    if (term.value.trim() !== value) term.value = value
  },
)

const statusValue = computed({
  get: () => {
    if (props.isActive === null || props.isActive === undefined) return ''
    return String(props.isActive)
  },
  set: (value) => {
    emit('update:isActive', value === '' ? null : value === 'true')
  },
})
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <BaseSearchInput
      v-model="term"
      label="Cari kode atau nama poliklinik"
      placeholder="Cari kode atau nama poliklinik..."
    />

    <div class="flex flex-wrap items-center gap-2">
      <select
        v-model="statusValue"
        aria-label="Filter status aktif"
        :disabled="loading"
        :class="[fieldControlClass(), 'w-44']"
      >
        <option value="">Semua Status</option>
        <option value="true">Aktif</option>
        <option value="false">Nonaktif</option>
      </select>

      <BaseButton
        variant="secondary"
        size="icon"
        aria-label="Muat ulang data"
        title="Muat ulang data"
        :disabled="loading"
        @click="emit('refresh')"
      >
        <RefreshCw class="size-4" :class="{ 'animate-spin': loading }" aria-hidden="true" />
      </BaseButton>
    </div>
  </div>
</template>
