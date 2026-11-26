import { ref } from 'vue'
import {
  getSafeRoomErrorMessage,
  toRoomClassOption,
} from '@/components/master-data/rooms/room.helpers'
import { roomClassesService } from '@/services/master-data/roomClasses.service'
import { normalizeApiError } from '@/utils/http-error'

/**
 * Daftar lookup kelas perawatan non-paginated ({ data }) dari /master-data/room-classes/options.
 * Menjaga opsi kelas tersimpan saat edit agar tetap tampil meski hasil pencarian berubah.
 */
export function useRoomClassOptions() {
  const options = ref([])
  const loading = ref(false)
  const error = ref('')

  function ensureOption(roomClass) {
    if (!roomClass?.id) return
    const exists = options.value.some((item) => String(item.id) === String(roomClass.id))
    if (!exists) {
      options.value = [toRoomClassOption(roomClass), ...options.value]
    }
  }

  async function load(search = '', preservedClass = null) {
    loading.value = true
    error.value = ''
    try {
      const response = await roomClassesService.getRoomClassOptions({ search })
      const classes = Array.isArray(response?.data) ? response.data : []
      options.value = classes.map(toRoomClassOption)
      ensureOption(preservedClass)
    } catch (err) {
      options.value = []
      const normalized = normalizeApiError(err, 'Gagal memuat data kelas.')
      error.value = getSafeRoomErrorMessage(normalized, 'Gagal memuat data kelas.')
    } finally {
      loading.value = false
    }
  }

  return { options, loading, error, load, ensureOption }
}
