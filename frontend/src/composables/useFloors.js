import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { floorsService } from '@/services/master-data/floors.service'

/**
 * Composable modul Lantai berbasis useCrudResource.
 * Mempertahankan dukungan filter building_id dan state/aksi sorting.
 */
export function useFloors() {
  const sort = reactive({
    column: 'created_at',
    direction: 'desc',
  })

  const crud = useCrudResource(floorsService, {
    label: 'lantai',
    sort,
    filters: {
      building_id: null,
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
