import axios from '@/utils/axios'

class RoomClassesService {
  async getRoomClasses(params) {
    const response = await axios.get('/master-data/room-classes', { params })
    return response.data
  }

  async getRoomClassOptions(params) {
    const response = await axios.get('/master-data/room-classes/options', { params })
    return response.data
  }

  async createRoomClass(data) {
    const response = await axios.post('/master-data/room-classes', data)
    return response.data
  }

  async getRoomClass(id) {
    const response = await axios.get(`/master-data/room-classes/${id}`)
    return response.data
  }

  async deleteRoomClass(id) {
    const response = await axios.delete(`/master-data/room-classes/${id}`)
    return response.data
  }
}

export const roomClassesService = new RoomClassesService()
export default roomClassesService
