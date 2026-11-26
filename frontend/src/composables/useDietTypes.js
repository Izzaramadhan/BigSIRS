import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { dietTypeService } from '@/services/dietType.service'

/**
 * Composable modul Asuhan Gizi berbasis useCrudResource.
 * Default sorting diselaraskan dengan DietTypeService::getPaginated (`name` ascending).
 */
export function useDietTypes() {
  const sort = reactive({
    column: 'name',
    direction: 'asc',
  })

  const crud = useCrudResource(dietTypeService, {
    label: 'asuhan gizi',
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
