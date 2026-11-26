import http from '@/utils/axios'

/**
 * Service CRUD standar untuk endpoint REST Laravel (apiResource).
 * Menggantikan 37 class service yang isinya sama persis.
 *
 * Tambahkan method khusus dengan spread:
 *   export const roomsService = {
 *     ...createResourceService('/master-data/rooms'),
 *     options: () => http.get('/master-data/rooms/options').then((r) => r.data),
 *   }
 *
 * @param {string} endpoint mis. '/master-data/beds'
 */
export function createResourceService(endpoint) {
  return {
    list: (params) => http.get(endpoint, { params }).then((res) => res.data),
    get: (id) => http.get(`${endpoint}/${id}`).then((res) => res.data),
    create: (payload) => http.post(endpoint, payload).then((res) => res.data),
    update: (id, payload) => http.put(`${endpoint}/${id}`, payload).then((res) => res.data),
    remove: (id) => http.delete(`${endpoint}/${id}`).then((res) => res.data),
  }
}
