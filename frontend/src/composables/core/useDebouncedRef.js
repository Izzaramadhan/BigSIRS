import { onScopeDispose, ref, toValue, watch } from 'vue'

/**
 * Ref yang mengikuti `source` setelah `delay` ms tanpa perubahan.
 * Dipakai untuk input pencarian agar tidak memanggil API di setiap ketikan.
 *
 * @template T
 * @param {import('vue').MaybeRefOrGetter<T>} source
 * @param {number} [delay]
 */
export function useDebouncedRef(source, delay = 400) {
  const debounced = ref(toValue(source))
  let timer

  watch(
    () => toValue(source),
    (value) => {
      clearTimeout(timer)
      timer = setTimeout(() => {
        debounced.value = value
      }, delay)
    },
  )

  onScopeDispose(() => clearTimeout(timer))

  return debounced
}
