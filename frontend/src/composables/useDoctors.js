import { reactive } from 'vue'
import { useCrudResource } from '@/composables/core/useCrudResource'
import { doctorService } from '@/services/master-data/doctors.service'

/**
 * Composable modul Dokter berbasis useCrudResource.
 * Mempertahankan default `perPage: 15` dan sorting backend `created_at` desc.
 */
export function useDoctors() {
  const sort = reactive({
    column: 'created_at',
    direction: 'desc',
  })

  const crud = useCrudResource(doctorService, {
    label: 'dokter',
    perPage: 15,
    sort,
    filters: {
      specialization_id: null,
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
