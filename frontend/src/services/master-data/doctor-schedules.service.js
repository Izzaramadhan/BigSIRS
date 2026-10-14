import api from '@/utils/axios';

const basePath = '/master-data/doctor-schedules';

export default {
  getDoctorSchedules(params) {
    return api.get(basePath, { params }).then(res => res.data);
  },
  getRecap() {
    return api.get(`${basePath}/recap`).then(res => res.data);
  },
  getDoctorSchedule(id) {
    return api.get(`${basePath}/${id}`).then(res => res.data);
  },
  createDoctorSchedule(data) {
    return api.post(basePath, data).then(res => res.data);
  },
  updateDoctorSchedule(id, data) {
    return api.put(`${basePath}/${id}`, data).then(res => res.data);
  },
  deleteDoctorSchedule(id) {
    return api.delete(`${basePath}/${id}`).then(res => res.data);
  }
};
