import http from '@/utils/axios'
import { createResourceService } from '@/services/core/createResourceService'

const resource = createResourceService('/master-data/room-classes')

export const roomClassesService = {
  ...resource,
  // Endpoint dropdown/options (dipakai useRoomClassOptions dan RoomFormModal).
  getRoomClassOptions(params = {}) {
    return http.get('/master-data/room-classes/options', { params }).then((res) => res.data)
  },
  // Alias kompatibilitas untuk caller dan spec sebelumnya (RoomFormModal.vue, RoomView.spec.js).
  getRoomClasses: (params) => resource.list(params),
  getRoomClass: (id) => resource.get(id),
  createRoomClass: (data) => resource.create(data),
  updateRoomClass: (id, data) => resource.update(id, data),
  deleteRoomClass: (id) => resource.remove(id),
}

export default roomClassesService
