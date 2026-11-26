import { readonly, ref } from 'vue'

const DEFAULT_DURATION = 3500

// State modul (singleton): satu antrean toast untuk seluruh aplikasi.
// <ToastViewport /> dipasang sekali di App.vue dan membaca antrean ini.
const toasts = ref([])
let nextId = 0

function dismiss(id) {
  toasts.value = toasts.value.filter((toast) => toast.id !== id)
}

function push({ tone, title, message, duration = DEFAULT_DURATION }) {
  const id = ++nextId
  toasts.value = [...toasts.value, { id, tone, title, message }]
  if (duration > 0) setTimeout(() => dismiss(id), duration)
  return id
}

/**
 * Notifikasi global. Menggantikan state `toast` + `showToast()` + `<AppToast>`
 * yang tersalin di 61 view.
 *
 *   const toast = useToast()
 *   toast.success('Data bed berhasil ditambahkan.')
 *   toast.error(error.message)
 */
export function useToast() {
  return {
    toasts: readonly(toasts),
    success: (message, title = 'Berhasil') => push({ tone: 'success', title, message }),
    error: (message, title = 'Gagal') => push({ tone: 'danger', title, message }),
    dismiss,
    /** Untuk test: kosongkan antrean. */
    clear: () => {
      toasts.value = []
    },
  }
}
