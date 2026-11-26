import { createResourceService } from '@/services/core/createResourceService'

const resource = createResourceService('/master-data/rooms')

export const roomsService = {
  ...resource,
  // Dipertahankan untuk lookup kamar pada modul Bed (useRoomOptions.js & BedView.spec.js).
  getRooms: (params) => resource.list(params),
}
