import { ref } from 'vue'
import {
  formatWardOption,
  getSafeRoomErrorMessage,
} from '@/components/master-data/rooms/room.helpers'
import { wardsService } from '@/services/master-data/wards.service'
import { normalizeApiError } from '@/utils/http-error'

/**
 * Daftar lookup bangsal non-paginated (per_page: -1) untuk dropdown form Kamar.
 * Mengelola format { id, name } ("Nama Bangsal - Nama Lantai - Nama Gedung") tanpa useCrudResource.
 */
export function useWardOptions() {
  const options = ref([])
  const loading = ref(false)
  const error = ref('')

  async function load() {
    loading.value = true
    error.value = ''
    try {
      const response = await wardsService.getWards({
        per_page: -1,
        sort: 'name',
        direction: 'asc',
      })
      const wards = Array.isArray(response?.data) ? response.data : []
      options.value = wards.map((ward) => ({
        id: ward.id,
        name: formatWardOption(ward),
      }))
    } catch (err) {
      options.value = []
      const normalized = normalizeApiError(err, 'Gagal memuat data bangsal.')
      error.value = getSafeRoomErrorMessage(normalized, 'Gagal memuat data bangsal.')
    } finally {
      loading.value = false
    }
  }

  return { options, loading, error, load }
}
