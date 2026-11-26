import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { wardsService } from '@/services/master-data/wards.service'

/**
 * Composable modul Bangsal berbasis useCrudResource.
 * Mempertahankan dukungan filter floor_id dan state/aksi sorting.
 */
export function useWards() {
  const sort = reactive({
    column: 'created_at',
    direction: 'desc',
  })

  const crud = useCrudResource(wardsService, {
    label: 'bangsal',
    sort,
    filters: {
      floor_id: null,
    },
  })

  function setSort(column) {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc'
    } else {
      sort.column = column
      sort.direction = 'asc'
    }
    crud.meta.page = 1
    return crud.fetchItems()
  }

  return {
    ...crud,
    sort,
    setSort,
  }
}
