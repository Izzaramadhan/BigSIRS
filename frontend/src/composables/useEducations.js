import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { educationsService } from '@/services/master-data/educations.service'

/**
 * Composable modul Pendidikan berbasis useCrudResource.
 * Default sorting diselaraskan dengan EducationController (id ascending).
 */
export function useEducations() {
  const sort = reactive({
    column: 'id',
    direction: 'asc',
  })

  const crud = useCrudResource(educationsService, {
    label: 'pendidikan',
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
