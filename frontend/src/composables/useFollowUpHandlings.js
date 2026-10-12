import { ref, reactive } from 'vue';
import { followUpHandlingsService } from '@/services/master-data/followUpHandlings.service';

export function useFollowUpHandlings() {
  const items = ref([]);
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    // Backward compatibility aliases
    get currentPage() {
      return this.current_page;
    },
    set currentPage(val) {
      this.current_page = val;
    },
    get lastPage() {
      return this.last_page;
    },
    set lastPage(val) {
      this.last_page = val;
    },
    get perPage() {
      return this.per_page;
    },
    set perPage(val) {
      this.per_page = val;
    }
  });

  const filters = reactive({
    search: ''
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchHandlings = async (pageOrParams = 1) => {
    loading.value = true;
    error.value = null;

    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page
      };

      if (typeof pageOrParams === 'number') {
        params.page = pageOrParams;
        pagination.current_page = pageOrParams;
      } else if (typeof pageOrParams === 'object' && pageOrParams !== null) {
        if (pageOrParams.page !== undefined) {
          params.page = pageOrParams.page;
          pagination.current_page = pageOrParams.page;
        }
        if (pageOrParams.per_page !== undefined) {
          params.per_page = pageOrParams.per_page;
          pagination.per_page = pageOrParams.per_page;
        }
        if (pageOrParams.search !== undefined) {
          filters.search = pageOrParams.search;
        }
      }

      if (filters.search) {
        params.search = filters.search;
      }

      const response = await followUpHandlingsService.getFollowUpHandlings(params);
      const data = response?.data || [];
      const meta = response?.meta || {};

      items.value = data;

      if (meta.current_page !== undefined) {
        pagination.current_page = meta.current_page;
        pagination.last_page = meta.last_page;
        pagination.per_page = meta.per_page;
        pagination.total = meta.total;
        pagination.from = meta.from || 0;
        pagination.to = meta.to || 0;
      } else {
        pagination.total = data.length;
        pagination.last_page = 1;
        pagination.from = data.length > 0 ? 1 : 0;
        pagination.to = data.length;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Data Penanganan Lanjutan gagal dimuat.';
      console.error('Failed to fetch follow-up handlings:', err);
    } finally {
      loading.value = false;
    }
  };

  const createHandling = async (payload) => {
    submitting.value = true;
    error.value = null;

    try {
      const response = await followUpHandlingsService.createFollowUpHandling(payload);
      return { success: true, data: response?.data || response };
    } catch (err) {
      return {
        success: false,
        error: err,
        errors: err.response?.data?.errors || {}
      };
    } finally {
      submitting.value = false;
    }
  };

  const updateHandling = async (id, payload) => {
    submitting.value = true;
    error.value = null;

    try {
      const response = await followUpHandlingsService.updateFollowUpHandling(id, payload);
      return { success: true, data: response?.data || response };
    } catch (err) {
      return {
        success: false,
        error: err,
        errors: err.response?.data?.errors || {}
      };
    } finally {
      submitting.value = false;
    }
  };

  const deleteHandling = async (id) => {
    submitting.value = true;
    error.value = null;

    try {
      const response = await followUpHandlingsService.deleteFollowUpHandling(id);
      return { success: true, data: response };
    } catch (err) {
      return {
        success: false,
        error: err
      };
    } finally {
      submitting.value = false;
    }
  };

  const setPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      pagination.current_page = page;
      fetchHandlings(page);
    }
  };

  return {
    items,
    handlings: items, // alias
    pagination,
    filters,
    loading,
    submitting,
    error,
    fetchHandlings,
    fetchFollowUpHandlings: fetchHandlings,
    createHandling,
    createFollowUpHandling: createHandling,
    updateHandling,
    updateFollowUpHandling: updateHandling,
    deleteHandling,
    deleteFollowUpHandling: deleteHandling,
    setPage
  };
}
