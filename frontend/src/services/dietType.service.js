import http from '@/utils/axios'
import { createResourceService } from '@/services/core/createResourceService'

const API_URL = '/master-data/diet-types'
const resource = createResourceService(API_URL)

export const dietTypeService = {
  ...resource,
  // Alias kompatibilitas untuk caller atau pengujian lama.
  getAll: (params) => resource.list(params),
  getById: (id) => resource.get(id),
  create: (data) => resource.create(data),
  update: (id, data) => resource.update(id, data),
  updateStatus: (id, isActive) =>
    http.patch(`${API_URL}/${id}/status`, { is_active: isActive }).then((res) => res.data),
  delete: (id) => resource.remove(id),
}

export default dietTypeService
