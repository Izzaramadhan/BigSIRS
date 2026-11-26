import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { guarantorsService } from '@/services/master-data/guarantors.service'

/**
 * Composable modul Jenis Asuransi / Penjamin berbasis useCrudResource.
 * Default sorting diselaraskan dengan GuarantorController::index (`name` ascending).
 */
export function useGuarantors() {
  const sort = reactive({
    column: 'name',
    direction: 'asc',
  })

  const crud = useCrudResource(guarantorsService, {
    label: 'jenis asuransi',
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
