import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { activityTypeService } from '@/services/activityType.service'

/**
 * Composable modul Jenis Kegiatan berbasis useCrudResource.
 * Default sorting diselaraskan dengan ActivityTypeService::getPaginated (`name` ascending).
 */
export function useActivityTypes() {
  const sort = reactive({
    column: 'name',
    direction: 'asc',
  })

  const crud = useCrudResource(activityTypeService, {
    label: 'jenis kegiatan',
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

  async function lookupActivityTypes(excludeId = null) {
    const params = excludeId ? { exclude_id: excludeId } : {}
    const response = await activityTypeService.lookup(params)
    return Array.isArray(response?.data) ? response.data : []
  }

  return {
    ...crud,
    sort,
    setSort,
    lookupActivityTypes,
  }
}
