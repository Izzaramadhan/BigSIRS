import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { buildingsService } from '@/services/master-data/buildings.service'

/**
 * Composable modul Gedung berbasis useCrudResource.
 * Mempertahankan state & aksi sorting agar dukungan pengurutan tetap tersedia.
 */
export function useBuildings() {
  const sort = reactive({
    column: 'created_at',
    direction: 'desc',
  })

  const crud = useCrudResource(buildingsService, {
    label: 'gedung',
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
