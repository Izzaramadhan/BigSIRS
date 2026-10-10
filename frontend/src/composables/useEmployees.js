import { ref, reactive } from 'vue';
import employeeService from '../services/employee.service';

export function useEmployees() {
  const items = ref([]);
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  });
  const filters = reactive({
    search: '',
  });
  const sort = reactive({
    column: 'created_at',
    direction: 'desc'
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchEmployees = async () => {
    loading.value = true;
    error.value = null;

    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        sort_by: sort.column,
        sort_desc: sort.direction === 'desc' ? 1 : 0
      };

      if (filters.search) params.search = filters.search;

      const response = await employeeService.getEmployees(params);

      // The Employee API returns a raw Laravel paginator:
      // { current_page, data: [...], last_page, per_page, total, ... }
      // NOT wrapped in Resource format { data: [...], meta: {...} }
      items.value = response.data || [];

      // Read pagination from top-level keys (raw paginator)
      // with fallback to meta (Resource format) for forward-compatibility
      const meta = response.meta || response;
      if (meta) {
        pagination.current_page = meta.current_page ?? pagination.current_page;
        pagination.last_page = meta.last_page ?? pagination.last_page;
        pagination.per_page = meta.per_page ?? pagination.per_page;
        pagination.total = meta.total ?? pagination.total;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Data Pegawai gagal dimuat.';
    } finally {
      loading.value = false;
    }
  };


  const createEmployee = async (payload) => {
    submitting.value = true;
    try {
      await employeeService.createEmployee(payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const updateEmployee = async (id, payload) => {
    submitting.value = true;
    try {
      await employeeService.updateEmployee(id, payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const deleteEmployee = async (id) => {
    try {
      await employeeService.deleteEmployee(id);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    }
  };

  const setPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      pagination.current_page = page;
      fetchEmployees();
    }
  };

  const setSort = ({ column, direction }) => {
    sort.column = column;
    sort.direction = direction;
    fetchEmployees();
  };

  return {
    items,
    pagination,
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchEmployees,
    createEmployee,
    updateEmployee,
    deleteEmployee,
    setPage,
    setSort
  };
}
