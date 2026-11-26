import { ref } from 'vue'
import { formatRoomOption, getSafeBedErrorMessage } from '@/components/master-data/beds/bed.helpers'
import { roomsService } from '@/services/master-data/rooms.service'
import { normalizeApiError } from '@/utils/http-error'

/**
 * Daftar lookup kamar non-paginated (per_page: -1) untuk dropdown form Bed.
 * Mengelola format { id, name } untuk SearchableSelect tanpa memaksakan useCrudResource.
 */
export function useRoomOptions() {
  const options = ref([])
  const loading = ref(false)
  const error = ref('')

  async function load() {
    loading.value = true
    error.value = ''
    try {
      const response = await roomsService.getRooms({
        per_page: -1,
        sort: 'name',
        direction: 'asc',
      })
      const rawData = response?.data
      const rooms = Array.isArray(rawData) ? rawData : (rawData?.data ?? [])
      options.value = rooms.map((room) => ({
        id: room.id,
        name: formatRoomOption(room),
      }))
    } catch (err) {
      options.value = []
      const normalized = normalizeApiError(err, 'Gagal memuat data kamar.')
      error.value = getSafeBedErrorMessage(normalized, 'Gagal memuat data kamar.')
    } finally {
      loading.value = false
    }
  }

  return { options, loading, error, load }
}
