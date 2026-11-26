import http from '@/utils/axios'
import { createResourceService } from '@/services/core/createResourceService'

const API_URL = '/master-data/service-types'
const resource = createResourceService(API_URL)

function mapListParams(params = {}) {
  if (!params || !('direction' in params)) return params
  const { direction, ...rest } = params
  return {
    ...rest,
    ...(direction && rest.order === undefined ? { order: direction } : {}),
  }
}

export const serviceTypeService = {
  ...resource,
  // Backend ServiceTypeController::index membaca `sort` & `order` (bukan `direction`).
  list: (params) => resource.list(mapListParams(params)),
  // Alias kompatibilitas untuk caller atau pengujian lama.
  getAll: (params) => resource.list(mapListParams(params)),
  getById: (id) => resource.get(id),
  create: (data) => resource.create(data),
  update: (id, data) => resource.update(id, data),
  updateStatus: (id, isActive) =>
    http.patch(`${API_URL}/${id}/status`, { is_active: isActive }).then((res) => res.data),
  delete: (id) => resource.remove(id),
}

export default serviceTypeService
