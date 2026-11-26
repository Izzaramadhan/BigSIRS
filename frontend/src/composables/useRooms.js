import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { roomsService } from '@/services/master-data/rooms.service'

/**
 * Composable modul Kamar berbasis useCrudResource.
 * Mempertahankan dukungan filter ward_id/room_class_id dan state/aksi sorting.
 */
export function useRooms() {
  const sort = reactive({
    column: 'created_at',
    direction: 'desc',
  })

  const crud = useCrudResource(roomsService, {
    label: 'kamar',
    sort,
    filters: {
      ward_id: null,
      room_class_id: null,
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
