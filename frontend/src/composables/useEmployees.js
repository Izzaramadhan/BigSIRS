import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { employeeService } from '@/services/employee.service'

/**
 * Composable modul Pegawai berbasis useCrudResource.
 * Mempertahankan default `perPage: 15` sesuai EmployeeController::index.
 */
export function useEmployees() {
  const sort = reactive({
    column: 'created_at',
    direction: 'desc',
  })

  const crud = useCrudResource(employeeService, {
    label: 'pegawai',
    perPage: 15,
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
