import { createResourceService } from '@/services/core/createResourceService'

const resource = createResourceService('/master-data/floors')

export const floorsService = {
  ...resource,
  // Dipertahankan sementara untuk pemanggil lookup lama (WardFormModal.vue, WardView.spec.js).
  getFloors: (params) => resource.list(params),
}
