import http from '@/utils/axios'
import { createResourceService } from '@/services/core/createResourceService'

const API_URL = '/master-data/guarantors'
const resource = createResourceService(API_URL)

export const guarantorsService = {
  ...resource,
  // Alias kompatibilitas untuk caller atau pengujian lama.
  getGuarantors: (params) => resource.list(params),
  getGuarantor: (id) => resource.get(id),
  createGuarantor: (data) => resource.create(data),
  updateGuarantor: (id, data) => resource.update(id, data),
  updateStatus: (id, isActive) =>
    http.patch(`${API_URL}/${id}/status`, { is_active: isActive }).then((res) => res.data),
  deleteGuarantor: (id) => resource.remove(id),
}

export default guarantorsService
