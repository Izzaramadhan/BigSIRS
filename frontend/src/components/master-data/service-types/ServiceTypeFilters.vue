<script setup>
import { ref, watch } from 'vue'
import { RefreshCw } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseSearchInput from '@/components/ui/BaseSearchInput.vue'
import { useDebouncedRef } from '@/composables/core/useDebouncedRef'

const props = defineProps({
  search: {
    type: String,
    default: '',
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:search', 'refresh'])

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
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <BaseSearchInput
      v-model="term"
      label="Cari jenis layanan"
      placeholder="Cari jenis layanan..."
    />
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
</template>
