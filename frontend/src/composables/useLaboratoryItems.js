import { ref } from 'vue'
import { laboratoryItemsService } from '@/services/master-data/laboratoryItems.service'

export function useLaboratoryItems() {
  const items = ref([])
  const meta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
  const loading = ref(false)
  const error = ref(null)

  const fetchItems = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await laboratoryItemsService.list(params)
      items.value = response.data ?? []
      meta.value = response.meta ?? meta.value
      return response
    } catch (requestError) {
      error.value = requestError.response?.data?.message || 'Gagal memuat data item lab.'
      throw requestError
    } finally {
      loading.value = false
    }
  }

  return { items, meta, loading, error, fetchItems }
}
