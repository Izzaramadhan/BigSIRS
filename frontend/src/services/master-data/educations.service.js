import { createResourceService } from '@/services/core/createResourceService'

const resource = createResourceService('/master-data/educations')

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

export const educationsService = {
  ...resource,
  // Backend EducationController::index membaca sort_by & sort_desc.
  list: (params) => resource.list(mapListParams(params)),
  // Alias kompatibilitas untuk caller atau pengujian lama.
  getEducations: (params) => resource.list(mapListParams(params)),
  getEducation: (id) => resource.get(id),
  createEducation: (data) => resource.create(data),
  updateEducation: (id, data) => resource.update(id, data),
  deleteEducation: (id) => resource.remove(id),
}

export default educationsService
