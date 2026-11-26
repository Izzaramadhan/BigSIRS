import { createResourceService } from '@/services/core/createResourceService'

const resource = createResourceService('/master-data/occupations')

function mapListParams(params = {}) {
  if (!params || (!('sort' in params) && !('direction' in params))) {
    return params
  }
  const { sort, direction, ...rest } = params
  return {
    ...rest,
    ...(sort ? { sort_by: sort } : {}),
    ...(direction ? { sort_desc: direction === 'desc' } : {}),
  }
}

export const occupationsService = {
  ...resource,
  // Backend OccupationController::index membaca sort_by & sort_desc.
  list: (params) => resource.list(mapListParams(params)),
  // Alias kompatibilitas untuk caller atau pengujian lama.
  getOccupations: (params) => resource.list(mapListParams(params)),
  getOccupation: (id) => resource.get(id),
  createOccupation: (data) => resource.create(data),
  updateOccupation: (id, data) => resource.update(id, data),
  deleteOccupation: (id) => resource.remove(id),
}

export default occupationsService
