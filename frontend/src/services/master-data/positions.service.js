import { createResourceService } from '@/services/core/createResourceService'

const resource = createResourceService('/master-data/positions')

export const positionsService = {
  ...resource,
  // Alias kompatibilitas untuk caller atau pengujian lama.
  getPositions: (params) => resource.list(params),
  getPosition: (id) => resource.get(id),
  createPosition: (data) => resource.create(data),
  updatePosition: (id, data) => resource.update(id, data),
  deletePosition: (id) => resource.remove(id),
}

export default positionsService
