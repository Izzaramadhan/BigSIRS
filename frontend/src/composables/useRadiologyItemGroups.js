import { ref, reactive, computed } from 'vue';
import { radiologyItemGroupsService } from '@/services/master-data/radiologyItemGroups.service';

export function useRadiologyItemGroups() {
  const groups = ref([]);
  const pagination = reactive({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    from: 0,
    to: 0
  });
  const meta = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
  });

  const filters = reactive({
    search: ''
  });

  const sort = reactive({
    column: 'name',
    direction: 'asc'
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchGroups = async (customParams = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        sortBy: sort.column,
        sortDesc: sort.direction === 'desc',
        ...customParams
      };

      if (filters.search && filters.search.trim()) {
        params.search = filters.search.trim();
      }

      const response = await radiologyItemGroupsService.getGroups(params);
      const resData = response.data ? response : { data: response };
      groups.value = resData.data || [];

      if (resData.meta) {
        pagination.current_page = resData.meta.current_page || 1;
        pagination.per_page = resData.meta.per_page || 10;
        pagination.total = resData.meta.total || 0;
        pagination.last_page = resData.meta.last_page || 1;
        pagination.from = resData.meta.from ?? ((pagination.current_page - 1) * pagination.per_page + 1);
        pagination.to = resData.meta.to ?? Math.min(pagination.current_page * pagination.per_page, pagination.total);

        meta.value = { ...pagination };
      }

      return { success: true, data: resData };
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal memuat data kelompok item radiologi.';
      error.value = msg;
      return { success: false, error: err };
    } finally {
      loading.value = false;
    }
  };

  const createGroup = async (data) => {
    submitting.value = true;
    error.value = null;
    try {
      const response = await radiologyItemGroupsService.createGroup(data);
      return { success: true, data: response.data || response };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menyimpan kelompok item radiologi.';
      error.value = errorMessage;
      return { success: false, error: errorMessage, errors: err.response?.data?.errors };
    } finally {
      submitting.value = false;
    }
  };

  const updateGroup = async (id, data) => {
    submitting.value = true;
    error.value = null;
    try {
      const response = await radiologyItemGroupsService.updateGroup(id, data);
      return { success: true, data: response.data || response };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal memperbarui kelompok item radiologi.';
      error.value = errorMessage;
      return { success: false, error: errorMessage, errors: err.response?.data?.errors };
    } finally {
      submitting.value = false;
    }
  };

  const deleteGroup = async (id) => {
    submitting.value = true;
    error.value = null;
    try {
      await radiologyItemGroupsService.deleteGroup(id);
      return { success: true };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menghapus kelompok item radiologi.';
      error.value = errorMessage;
      return { success: false, error: errorMessage };
    } finally {
      submitting.value = false;
    }
  };

  const setPage = (page) => {
    pagination.current_page = page;
    return fetchGroups();
  };

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      sort.column = column;
      sort.direction = 'asc';
    }
    pagination.current_page = 1;
    return fetchGroups();
  };

  const handlePageChange = (page) => {
    pagination.current_page = page;
    fetchGroups();
  };

  const handleLimitChange = (limit) => {
    pagination.current_page = 1;
    pagination.per_page = limit;
    fetchGroups();
  };

  return {
    items: groups,
    groups,
    pagination,
    meta,
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchGroups,
    createGroup,
    updateGroup,
    deleteGroup,
    setPage,
    setSort,
    handlePageChange,
    handleLimitChange,
    isEmpty: computed(() => !loading.value && groups.value.length === 0)
  };
}
