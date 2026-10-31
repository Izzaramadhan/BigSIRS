import { ref, reactive, computed } from 'vue';
import mutationsService from '@/services/logistics/mutations.service';

export function useMutations() {
  const mutations = ref([]);
  const warehouses = ref([]);
  const medicines = ref([]);

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const defaultFilters = () => ({
    startDate: '',
    endDate: '',
    sourceWarehouseId: '',
    targetWarehouseId: '',
    keyword: ''
  });

  const filters = reactive(defaultFilters());
  const appliedFilters = reactive(defaultFilters());

  const pagination = reactive({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    from: 0,
    to: 0
  });

  const summary = ref({
    totalTransactions: '0 Transaksi',
    totalCount: 0,
    estimatedValue: 'Rp0',
    topSource: '-',
    topSourceCount: 0,
    topSourceSubtitle: 'Belum ada mutasi keluar',
    topTarget: '-',
    topTargetCount: 0,
    topTargetSubtitle: 'Belum ada mutasi masuk',
    integrity: '100%',
    integrityBadge: 'Tersinkronisasi',
    integritySubtitle: 'Audit log sinkron tanpa selisih ledger'
  });

  const paginationInfo = computed(() => ({
    current_page: pagination.current_page,
    per_page: pagination.per_page,
    last_page: pagination.last_page,
    total: pagination.total,
    from: pagination.from,
    to: pagination.to
  }));

  const paginatedMutations = computed(() => mutations.value);
  const filteredMutations = computed(() => mutations.value);

  // Fetch warehouses lookup
  const fetchWarehouses = async () => {
    try {
      const res = await mutationsService.getWarehousesLookup();
      warehouses.value = res.data || res || [];
    } catch {
      warehouses.value = [];
    }
  };

  // Fetch summary
  const fetchSummary = async (params = {}) => {
    try {
      const res = await mutationsService.getSummary(params);
      summary.value = {
        totalTransactions: res.totalTransactions || `${res.totalCount || 0} Transaksi`,
        totalCount: res.totalCount || 0,
        estimatedValue: res.estimatedValue || 'Rp0',
        topSource: res.topSource || '-',
        topSourceCount: res.topSourceCount || 0,
        topSourceSubtitle: res.topSourceSubtitle || 'Belum ada mutasi keluar',
        topTarget: res.topTarget || '-',
        topTargetCount: res.topTargetCount || 0,
        topTargetSubtitle: res.topTargetSubtitle || 'Belum ada mutasi masuk',
        integrity: res.integrity || '100%',
        integrityBadge: res.integrityBadge || 'Tersinkronisasi',
        integritySubtitle: res.integritySubtitle || 'Audit log sinkron tanpa selisih ledger'
      };
    } catch (err) {
      error.value = err.message;
    }
  };

  // Fetch mutations list
  const fetchMutations = async () => {
    loading.value = true;
    error.value = null;
    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        search: appliedFilters.keyword || undefined,
        start_date: appliedFilters.startDate || undefined,
        end_date: appliedFilters.endDate || undefined,
        source_warehouse_id: appliedFilters.sourceWarehouseId || undefined,
        target_warehouse_id: appliedFilters.targetWarehouseId || undefined
      };

      const res = await mutationsService.getMutations(params);
      mutations.value = res.data || [];

      if (res.meta) {
        pagination.current_page = res.meta.current_page;
        pagination.per_page = res.meta.per_page;
        pagination.last_page = res.meta.last_page;
        pagination.total = res.meta.total;
        pagination.from = res.meta.from ?? 0;
        pagination.to = res.meta.to ?? 0;
      } else {
        pagination.total = mutations.value.length;
        pagination.from = mutations.value.length > 0 ? 1 : 0;
        pagination.to = mutations.value.length;
        pagination.last_page = 1;
      }
    } catch (err) {
      error.value = err.message || 'Gagal memuat data mutasi';
      mutations.value = [];
    } finally {
      loading.value = false;
    }
  };

  const setPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      pagination.current_page = page;
      fetchMutations();
    }
  };

  const setPerPage = (perPage) => {
    pagination.per_page = Number(perPage);
    pagination.current_page = 1;
    fetchMutations();
  };

  const applyFilters = (newFilters) => {
    if (newFilters) {
      Object.assign(filters, newFilters);
      Object.assign(appliedFilters, newFilters);
    } else {
      Object.assign(appliedFilters, filters);
    }
    pagination.current_page = 1;
    fetchMutations();
    fetchSummary();
  };

  const resetFilters = () => {
    Object.assign(filters, defaultFilters());
    Object.assign(appliedFilters, defaultFilters());
    pagination.current_page = 1;
    fetchMutations();
    fetchSummary();
  };

  const addMutation = async (payload) => {
    submitting.value = true;
    try {
      const formattedPayload = {
        mutationDate: payload.date || payload.mutationDate,
        sourceWarehouseId: payload.sourceWarehouseId,
        targetWarehouseId: payload.targetWarehouseId,
        notes: payload.notes || null,
        items: (payload.items || []).map((it) => ({
          medicineId: it.medicineId,
          quantity: Number(it.quantity)
        }))
      };

      const res = await mutationsService.createMutation(formattedPayload);

      // Refresh data
      await Promise.all([fetchMutations(), fetchSummary()]);

      return { success: true, data: res.data || res };
    } catch (err) {
      const validationErrors = err.response?.data?.errors || null;
      const message = err.response?.data?.message || err.message || 'Gagal menyimpan mutasi barang.';
      return { success: false, error: err, errors: validationErrors, message };
    } finally {
      submitting.value = false;
    }
  };

  const getMutationDetail = async (id) => {
    const res = await mutationsService.getMutation(id);
    return res.data || res;
  };

  const exportMutationsCsv = async () => {
    const params = {
      search: appliedFilters.keyword || undefined,
      start_date: appliedFilters.startDate || undefined,
      end_date: appliedFilters.endDate || undefined,
      source_warehouse_id: appliedFilters.sourceWarehouseId || undefined,
      target_warehouse_id: appliedFilters.targetWarehouseId || undefined
    };

    const blob = await mutationsService.exportCsv(params);
    const url = window.URL.createObjectURL(new Blob([blob], { type: 'text/csv' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Daftar_Mutasi_Barang_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    return true;
  };

  const fetchInitialData = async () => {
    loading.value = true;
    try {
      await Promise.all([
        fetchWarehouses(),
        fetchSummary(),
        fetchMutations()
      ]);
    } finally {
      loading.value = false;
    }
  };

  return {
    mutations,
    warehouses,
    medicines,
    filters,
    appliedFilters,
    pagination,
    paginationInfo,
    loading,
    submitting,
    error,
    summary,
    filteredMutations,
    paginatedMutations,
    fetchWarehouses,
    fetchSummary,
    fetchMutations,
    fetchInitialData,
    setPage,
    setPerPage,
    applyFilters,
    resetFilters,
    addMutation,
    getMutationDetail,
    exportMutationsCsv
  };
}

export default useMutations;
