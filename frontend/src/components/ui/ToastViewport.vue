<script setup>
import { CircleAlert, CircleCheck, X } from 'lucide-vue-next'
import { useToast } from '@/composables/core/useToast'

const { toasts, dismiss } = useToast()

const TONES = {
  success: { icon: CircleCheck, class: 'text-success' },
  danger: { icon: CircleAlert, class: 'text-danger' },
}
</script>

<template>
  <!-- Dipasang SEKALI di App.vue. Halaman cukup memanggil useToast(). -->
  <div class="pointer-events-none fixed top-4 right-4 z-60 flex w-full max-w-sm flex-col gap-2">
    <TransitionGroup
      enter-active-class="transition duration-200"
      leave-active-class="transition duration-150"
      enter-from-class="translate-x-4 opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :role="toast.tone === 'danger' ? 'alert' : 'status'"
        class="pointer-events-auto flex items-start gap-3 rounded-xl border border-line bg-surface p-4 shadow-lg"
      >
        <component
          :is="TONES[toast.tone].icon"
          class="mt-0.5 size-5 shrink-0"
          :class="TONES[toast.tone].class"
          aria-hidden="true"
        />
        <div class="flex-1">
          <p class="text-sm font-semibold text-fg">{{ toast.title }}</p>
          <p class="mt-0.5 text-sm text-fg-muted">{{ toast.message }}</p>
        </div>
        <button
          type="button"
          class="cursor-pointer rounded border-0 bg-transparent p-0 text-fg-muted hover:text-fg"
          aria-label="Tutup notifikasi"
          @click="dismiss(toast.id)"
        >
          <X class="size-4" aria-hidden="true" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
