import http from '@/utils/axios'
import { createResourceService } from '@/services/core/createResourceService'

const API_URL = '/master-data/activity-types'
const resource = createResourceService(API_URL)

export const activityTypeService = {
  ...resource,
  // Alias kompatibilitas untuk caller atau pengujian lama.
  getAll: (params) => resource.list(params),
  getById: (id) => resource.get(id),
  create: (data) => resource.create(data),
  update: (id, data) => resource.update(id, data),
  delete: (id) => resource.remove(id),
  lookup: (params = {}) => http.get(`${API_URL}/lookup`, { params }).then((res) => res.data),
}

export default activityTypeService
