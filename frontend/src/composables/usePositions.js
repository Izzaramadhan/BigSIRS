import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { positionsService } from '@/services/master-data/positions.service'

/**
 * Composable modul Jabatan berbasis useCrudResource.
 * Default sorting diselaraskan dengan PositionController::index (name ascending).
 */
export function usePositions() {
  const sort = reactive({
    column: 'name',
    direction: 'asc',
  })

  const crud = useCrudResource(positionsService, {
    label: 'jabatan',
    sort,
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
