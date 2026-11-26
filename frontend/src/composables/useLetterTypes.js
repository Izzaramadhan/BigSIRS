import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { letterTypeService } from '@/services/letterType.service'

/**
 * Composable modul Surat-Surat berbasis useCrudResource.
 * Default sorting diselaraskan dengan LetterTypeService::getPaginated (`name` ascending).
 */
export function useLetterTypes() {
  const sort = reactive({
    column: 'name',
    direction: 'asc',
  })

  const crud = useCrudResource(letterTypeService, {
    label: 'surat-surat',
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
