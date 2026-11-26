import { createResourceService } from '@/services/core/createResourceService'

const resource = createResourceService('/master-data/wards')

export const wardsService = {
  ...resource,
  // Dipertahankan sementara untuk lookup di RoomFormModal.vue dan RoomView.spec.js.
  getWards: (params) => resource.list(params),
}
