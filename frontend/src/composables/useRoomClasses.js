import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { roomClassesService } from '@/services/master-data/roomClasses.service'

/**
 * Composable modul Kelas (Room Class) berbasis useCrudResource.
 * Mempertahankan default sort berdasarkan nama kelas secara ascending dan aksi setSort.
 */
export function useRoomClasses() {
  const sort = reactive({
    column: 'name',
    direction: 'asc',
  })

  const crud = useCrudResource(roomClassesService, {
    label: 'kelas',
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
