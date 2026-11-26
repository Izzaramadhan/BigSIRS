import { ref } from 'vue'
import {
  formatFloorOption,
  getSafeWardErrorMessage,
} from '@/components/master-data/wards/ward.helpers'
import { floorsService } from '@/services/master-data/floors.service'
import { normalizeApiError } from '@/utils/http-error'

/**
 * Daftar lookup lantai non-paginated (per_page: -1) untuk dropdown form Bangsal.
 * Mengelola format { id, name } ("Lantai - Gedung") untuk SearchableSelect tanpa useCrudResource.
 */
export function useFloorOptions() {
  const options = ref([])
  const loading = ref(false)
  const error = ref('')

  async function load() {
    loading.value = true
    error.value = ''
    try {
      const response = await floorsService.getFloors({
        per_page: -1,
        sort: 'name',
        direction: 'asc',
      })
      const rawData = response?.data
      const floors = Array.isArray(rawData) ? rawData : (rawData?.data ?? [])
      options.value = floors.map((floor) => ({
        id: floor.id,
        name: formatFloorOption(floor),
      }))
    } catch (err) {
      options.value = []
      const normalized = normalizeApiError(err, 'Gagal memuat data lantai.')
      error.value = getSafeWardErrorMessage(normalized, 'Gagal memuat data lantai.')
    } finally {
      loading.value = false
    }
  }

  return { options, loading, error, load }
}
