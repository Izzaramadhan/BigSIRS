<script setup>
import { nextTick, onBeforeUnmount, useId, useTemplateRef, watch } from 'vue'
import { X } from 'lucide-vue-next'
import BaseButton from './BaseButton.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  /** Cegah modal ditutup (klik luar, Esc, tombol X), mis. saat request berjalan. */
  persistent: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close'])

const SIZES = { sm: 'max-w-md', md: 'max-w-lg', lg: 'max-w-3xl' }

const titleId = useId()
const descriptionId = useId()
const panel = useTemplateRef('panel')

function requestClose() {
  if (!props.persistent) emit('close')
}

function handleKeydown(event) {
  if (event.key === 'Escape') requestClose()
}

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) {
      document.removeEventListener('keydown', handleKeydown)
      return
    }
    document.addEventListener('keydown', handleKeydown)
    // Pindahkan fokus ke dialog agar pembaca layar & keyboard langsung berada di dalamnya.
    await nextTick()
    panel.value?.focus()
  },
  { immediate: true },
)

onBeforeUnmount(() => document.removeEventListener('keydown', handleKeydown))
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-150"
      leave-active-class="transition-opacity duration-150"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-fg/50 p-4"
        @click.self="requestClose"
      >
        <div
          ref="panel"
          role="dialog"
          aria-modal="true"
          tabindex="-1"
          :aria-labelledby="titleId"
          :aria-describedby="description ? descriptionId : undefined"
          class="flex max-h-[90vh] w-full flex-col rounded-xl bg-surface shadow-xl outline-none"
          :class="SIZES[size]"
        >
          <header class="flex items-start justify-between gap-4 border-b border-line px-6 py-4">
            <div>
              <h2 :id="titleId" class="text-lg font-semibold text-fg">{{ title }}</h2>
              <p v-if="description" :id="descriptionId" class="mt-1 text-sm text-fg-muted">
                {{ description }}
              </p>
            </div>
            <BaseButton
              variant="ghost"
              size="icon"
              aria-label="Tutup"
              :disabled="persistent"
              @click="requestClose"
            >
              <X class="size-5" aria-hidden="true" />
            </BaseButton>
          </header>

          <div class="overflow-y-auto px-6 py-5">
            <slot />
          </div>

          <footer
            v-if="$slots.footer"
            class="flex justify-end gap-3 border-t border-line px-6 py-4"
          >
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
