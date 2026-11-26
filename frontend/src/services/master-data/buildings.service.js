import { createResourceService } from '@/services/core/createResourceService'

const resource = createResourceService('/master-data/buildings')

export const buildingsService = {
  ...resource,
  // Dipertahankan sementara sebagai lookup untuk FloorFormModal.vue yang belum dimigrasi.
  getBuildings: (params) => resource.list(params),
}
