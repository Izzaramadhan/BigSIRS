import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { occupationsService } from '@/services/master-data/occupations.service'

/**
 * Composable modul Pekerjaan berbasis useCrudResource.
 * Default sorting diselaraskan dengan OccupationController (id ascending).
 */
export function useOccupations() {
  const sort = reactive({
    column: 'id',
    direction: 'asc',
  })

  const crud = useCrudResource(occupationsService, {
    label: 'pekerjaan',
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
