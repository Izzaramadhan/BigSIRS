import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { serviceTypeService } from '@/services/serviceType.service'

/**
 * Composable modul Jenis Layanan berbasis useCrudResource.
 * Default sorting diselaraskan dengan ServiceTypeController::index (`name` ascending).
 */
export function useServiceTypes() {
  const sort = reactive({
    column: 'name',
    direction: 'asc',
  })

  const crud = useCrudResource(serviceTypeService, {
    label: 'jenis layanan',
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
