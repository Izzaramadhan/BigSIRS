import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { bedsService } from '@/services/master-data/beds.service'

/**
 * Composable modul Bed berbasis useCrudResource.
 * Mempertahankan dukungan filter room_id dan state/aksi sorting.
 */
export function useBeds() {
  const sort = reactive({
    column: 'created_at',
    direction: 'desc',
  })

  const crud = useCrudResource(bedsService, {
    label: 'bed',
    sort,
    filters: {
      room_id: null,
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
