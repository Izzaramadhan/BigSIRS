import { ref } from 'vue';
import employeeService from '../services/employee.service';

export function useEmployees() {
  const employees = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const meta = ref(null);

  const fetchEmployees = async (params = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await employeeService.getEmployees(params);
      // Laravel paginate returns items in .data and pagination info in root (if not wrapped in API Resource)
      employees.value = response.data || [];
      meta.value = response.meta || response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data pegawai';
    } finally {
      loading.value = false;
    }
  };

  const createEmployee = async (data) => {
    loading.value = true;
    error.value = null;
    try {
      await employeeService.createEmployee(data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menambahkan pegawai';
      if (err.response?.data?.errors) {
        throw err.response.data.errors;
      }
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateEmployee = async (id, data) => {
    loading.value = true;
    error.value = null;
    try {
      await employeeService.updateEmployee(id, data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui pegawai';
      if (err.response?.data?.errors) {
        throw err.response.data.errors;
      }
      return false;
    } finally {
      loading.value = false;
    }
  };

  const deleteEmployee = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      await employeeService.deleteEmployee(id);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus pegawai';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateStatus = async (id, isActive) => {
    try {
      await employeeService.updateStatus(id, isActive);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui status pegawai';
      return false;
    }
  };

  return {
    employees,
    loading,
    error,
    meta,
    fetchEmployees,
    createEmployee,
    updateEmployee,
    deleteEmployee,
    updateStatus,
  };
}
