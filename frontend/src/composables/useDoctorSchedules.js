import { ref, reactive } from 'vue';
import doctorScheduleService from '../services/master-data/doctor-schedules.service';

export function useDoctorSchedules() {
  const items = ref([]);
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });
  const filters = reactive({
    search: '',
    doctor_id: null,
    polyclinic_id: null,
    day_of_week: null,
    is_holiday: null,
  });
  const sort = reactive({
    column: 'id',
    direction: 'desc'
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchSchedules = async () => {
    loading.value = true;
    error.value = null;

    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        sort_by: sort.column,
        sort_dir: sort.direction
      };

      if (filters.search) params.search = filters.search;
      if (filters.doctor_id) params.doctor_id = filters.doctor_id;
      if (filters.polyclinic_id) params.polyclinic_id = filters.polyclinic_id;
      if (filters.day_of_week) params.day_of_week = filters.day_of_week;
      if (filters.is_holiday !== null && filters.is_holiday !== '') params.is_holiday = filters.is_holiday;

      const response = await doctorScheduleService.getDoctorSchedules(params);

      items.value = response.data;

      if (response.meta) {
        pagination.current_page = response.meta.current_page;
        pagination.last_page = response.meta.last_page;
        pagination.per_page = response.meta.per_page;
        pagination.total = response.meta.total;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Data Jadwal Dokter gagal dimuat.';
    } finally {
      loading.value = false;
    }
  };

  const createSchedule = async (payload) => {
    submitting.value = true;
    try {
      await doctorScheduleService.createDoctorSchedule(payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const updateSchedule = async (id, payload) => {
    submitting.value = true;
    try {
      await doctorScheduleService.updateDoctorSchedule(id, payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const deleteSchedule = async (id) => {
    try {
      await doctorScheduleService.deleteDoctorSchedule(id);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    }
  };

  const resetFilters = () => {
    filters.search = '';
    filters.doctor_id = null;
    filters.polyclinic_id = null;
    filters.day_of_week = null;
    filters.is_holiday = null;
    pagination.current_page = 1;
    fetchSchedules();
  };

  return {
    items,
    pagination,
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchSchedules,
    createSchedule,
    updateSchedule,
    deleteSchedule,
    resetFilters
  };
}
