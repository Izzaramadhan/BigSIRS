import { ref } from 'vue'
import {
  formatBuildingOption,
  getSafeFloorErrorMessage,
} from '@/components/master-data/floors/floor.helpers'
import { buildingsService } from '@/services/master-data/buildings.service'
import { normalizeApiError } from '@/utils/http-error'

/**
 * Daftar lookup gedung non-paginated (per_page: -1) untuk dropdown form Lantai.
 * Mengelola format { id, name } untuk SearchableSelect tanpa memaksakan useCrudResource.
 */
export function useBuildingOptions() {
  const options = ref([])
  const loading = ref(false)
  const error = ref('')

  async function load() {
    loading.value = true
    error.value = ''
    try {
      const response = await buildingsService.getBuildings({
        per_page: -1,
        sort: 'name',
        direction: 'asc',
      })
      const rawData = response?.data
      const buildings = Array.isArray(rawData) ? rawData : (rawData?.data ?? [])
      options.value = buildings.map((building) => ({
        id: building.id,
        name: formatBuildingOption(building),
      }))
    } catch (err) {
      options.value = []
      const normalized = normalizeApiError(err, 'Gagal memuat data gedung.')
      error.value = getSafeFloorErrorMessage(normalized, 'Gagal memuat data gedung.')
    } finally {
      loading.value = false
    }
  }

  return { options, loading, error, load }
}
