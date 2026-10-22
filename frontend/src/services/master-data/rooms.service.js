import axios from '@/utils/axios'

class RoomsService {
  async getRooms(params) {
    const response = await axios.get('/master-data/rooms', { params })
    return response.data
  }

  async getRoom(id) {
    const response = await axios.get(`/master-data/rooms/${id}`)
    return response.data
  }

  async createRoom(data) {
    const response = await axios.post('/master-data/rooms', data)
    return response.data
  }

  async updateRoom(id, data) {
    const response = await axios.put(`/master-data/rooms/${id}`, data)
    return response.data
  }

  async deleteRoom(id) {
    const response = await axios.delete(`/master-data/rooms/${id}`)
    return response.data
  }
}

export const roomsService = new RoomsService()
