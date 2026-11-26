import http from '@/utils/axios'
import { createResourceService } from '@/services/core/createResourceService'

const API_URL = '/master-data/letter-types'
const resource = createResourceService(API_URL)

/**
 * Adapter parameter list untuk backend LetterTypeService::getPaginated:
 * - Frontend (`useCrudResource`) mengirim `sort` dan `direction`
 * - Backend (`LetterTypeController::index`) mengharapkan `sort_by` dan `sort_dir`
 */
export function adaptLetterTypeListParams(params = {}) {
  const { sort, direction, sort_by: explicitSortBy, sort_dir: explicitSortDir, ...rest } =
    params || {}

  const resolvedSortBy = explicitSortBy || sort
  const resolvedSortDir = explicitSortDir || direction

  return {
    ...rest,
    ...(resolvedSortBy ? { sort_by: resolvedSortBy } : {}),
    ...(resolvedSortDir ? { sort_dir: resolvedSortDir } : {}),
  }
}

export const letterTypeService = {
  ...resource,
  list: (params) => resource.list(adaptLetterTypeListParams(params)),
  // Alias kompatibilitas untuk caller atau pengujian lama.
  getLetterTypes: (params) => resource.list(adaptLetterTypeListParams(params)),
  getLetterType: (id) => resource.get(id),
  createLetterType: (data) => resource.create(data),
  updateLetterType: (id, data) => resource.update(id, data),
  deleteLetterType: (id) => resource.remove(id),
  updateStatus: (id, isActive) =>
    http.patch(`${API_URL}/${id}/status`, { is_active: isActive }).then((res) => res.data),
}

export default letterTypeService
