import { ref, reactive } from 'vue';
import { laboratoryGroupsService } from '@/services/master-data/laboratoryGroups.service';

export function useLaboratoryGroups() {
  const items = ref([]);
  const pagination = reactive({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    from: 0,
    to: 0
  });

  const filters = reactive({
    search: '',
    is_active: null
  });

  const sort = reactive({
    column: 'name',
    direction: 'asc'
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchLaboratoryGroups = async (customParams = {}) => {
    loading.value = true;
    error.value = null;

    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        sort_by: sort.column,
        sort_desc: sort.direction === 'desc',
        ...customParams
      };

      if (filters.search && filters.search.trim()) {
        params.search = filters.search.trim();
      }

      if (filters.is_active !== null && filters.is_active !== '') {
        params.is_active = filters.is_active;
      }

      const response = await laboratoryGroupsService.list(params);
      items.value = response.data || [];

      if (response.meta) {
        pagination.current_page = response.meta.current_page || 1;
        pagination.per_page = response.meta.per_page || 10;
        pagination.total = response.meta.total || 0;
        pagination.last_page = response.meta.last_page || 1;
        pagination.from = response.meta.from ?? ((pagination.current_page - 1) * pagination.per_page + 1);
        pagination.to = response.meta.to ?? Math.min(pagination.current_page * pagination.per_page, pagination.total);
      }

      return { success: true, data: response };
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data grup lab.';
      return { success: false, error: err };
    } finally {
      loading.value = false;
    }
  };

  const getLaboratoryGroup = async (id) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await laboratoryGroupsService.detail(id);
      return { success: true, data: response.data || response };
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat detail grup lab.';
      return { success: false, error: err };
    } finally {
      loading.value = false;
    }
  };

  const createLaboratoryGroup = async (payload) => {
    submitting.value = true;
    error.value = null;

    try {
      const response = await laboratoryGroupsService.create(payload);
      return { success: true, data: response.data || response };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const updateLaboratoryGroup = async (id, payload) => {
    submitting.value = true;
    error.value = null;

    try {
      const response = await laboratoryGroupsService.update(id, payload);
      return { success: true, data: response.data || response };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const deleteLaboratoryGroup = async (id) => {
    submitting.value = true;
    error.value = null;

    try {
      await laboratoryGroupsService.remove(id);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const setPage = (page) => {
    pagination.current_page = page;
    fetchLaboratoryGroups();
  };

  const setSort = ({ column, direction }) => {
    sort.column = column;
    sort.direction = direction;
    pagination.current_page = 1;
    fetchLaboratoryGroups();
  };

  return {
    items,
    laboratoryGroups: items,
    pagination,
    meta: pagination,
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchLaboratoryGroups,
    getLaboratoryGroup,
    createLaboratoryGroup,
    updateLaboratoryGroup,
    deleteLaboratoryGroup,
    setPage,
    setSort
  };
}
