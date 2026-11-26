<script setup>
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { buildPageRange, ELLIPSIS } from '@/utils/pagination'

const props = defineProps({
  page: {
    type: Number,
    required: true,
  },
  lastPage: {
    type: Number,
    required: true,
  },
  total: {
    type: Number,
    required: true,
  },
  from: {
    type: Number,
    default: 0,
  },
  to: {
    type: Number,
    default: 0,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['change'])

const pages = computed(() => buildPageRange(props.page, props.lastPage))

const PAGE_BUTTON =
  'inline-flex size-9 cursor-pointer items-center justify-center rounded-lg border-0 p-0 font-sans text-sm transition-colors disabled:pointer-events-none disabled:opacity-50'
</script>

<template>
  <nav
    v-if="total > 0"
    aria-label="Paginasi"
    class="flex flex-wrap items-center justify-between gap-3 text-sm text-fg-muted"
  >
    <p>Menampilkan {{ from }}–{{ to }} dari {{ total }} data</p>

    <div class="flex items-center gap-1">
      <button
        type="button"
        :class="[PAGE_BUTTON, 'bg-transparent text-fg hover:bg-canvas']"
        :disabled="disabled || page <= 1"
        aria-label="Halaman sebelumnya"
        @click="emit('change', page - 1)"
      >
        <ChevronLeft class="size-4" aria-hidden="true" />
      </button>

      <template v-for="(item, index) in pages" :key="`${item}-${index}`">
        <span v-if="item === ELLIPSIS" class="px-1" aria-hidden="true">{{ ELLIPSIS }}</span>
        <button
          v-else
          type="button"
          :class="[
            PAGE_BUTTON,
            item === page
              ? 'bg-primary font-semibold text-white'
              : 'bg-transparent text-fg hover:bg-canvas',
          ]"
          :disabled="disabled"
          :aria-current="item === page ? 'page' : undefined"
          @click="item !== page && emit('change', item)"
        >
          {{ item }}
        </button>
      </template>

      <button
        type="button"
        :class="[PAGE_BUTTON, 'bg-transparent text-fg hover:bg-canvas']"
        :disabled="disabled || page >= lastPage"
        aria-label="Halaman berikutnya"
        @click="emit('change', page + 1)"
      >
        <ChevronRight class="size-4" aria-hidden="true" />
      </button>
    </div>
  </nav>
</template>
