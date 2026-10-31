import { ref, reactive, computed, onMounted, getCurrentInstance } from 'vue';
import goodsIssuesService from '@/services/logistics/goodsIssues.service';

export function useGoodsIssues() {
  const issues = ref([]);
  const warehouses = ref([]);
  const officers = ref([]);
  const purposes = ref([]);
  const catalogMedicines = ref([]);

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const defaultFilters = () => ({
    search: '',
    issueNumber: '',
    startDate: '',
    endDate: '',
    warehouse: 'Semua Gudang Asal',
    officer: 'Semua Petugas',
    purpose: 'Semua Keperluan',
    notes: ''
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

  // Summary Metrics
  const summary = ref({
    todayIssues: '0',
    todayUnit: 'Dokumen',
    todayNote: 'Disetujui hari ini (Shift 1 & 2)',
    totalItemOut: '0',
    totalItemUnit: 'Unit',
    totalItemNote: 'Akumulasi fisik bulan berjalan',
    damagedItem: '0',
    damagedUnit: 'Unit',
    damagedNote: 'Kerusakan kemasan & pecah',
    expiredItem: '0',
    expiredUnit: 'Unit',
    expiredNote: 'Karantina & pemusnahan BPOM'
  });

  // Paginated List (server-side pagination, so returns all current page items)
  const paginatedIssues = computed(() => issues.value);
  const filteredIssues = computed(() => issues.value);

  // Pagination Info for MasterDataPagination
  const paginationInfo = computed(() => ({
    current_page: pagination.current_page,
    per_page: pagination.per_page,
    last_page: pagination.last_page,
    total: pagination.total,
    from: pagination.from,
    to: pagination.to
  }));

  // Fetch summary
  const fetchSummary = async (params = {}) => {
    try {
      const res = await goodsIssuesService.getSummary(params);
      const data = res.data || res;
      summary.value = {
        todayIssues: data.todayIssues ?? '0',
        todayUnit: data.todayUnit ?? 'Dokumen',
        todayNote: data.todayNote ?? 'Disetujui hari ini (Shift 1 & 2)',
        totalItemOut: data.totalItemOut ?? '0',
        totalItemUnit: data.totalItemUnit ?? 'Unit',
        totalItemNote: data.totalItemNote ?? 'Akumulasi fisik bulan berjalan',
        damagedItem: data.damagedItem ?? '0',
        damagedUnit: data.damagedUnit ?? 'Unit',
        damagedNote: data.damagedNote ?? 'Kerusakan kemasan & pecah',
        expiredItem: data.expiredItem ?? '0',
        expiredUnit: data.expiredUnit ?? 'Unit',
        expiredNote: data.expiredNote ?? 'Karantina & pemusnahan BPOM'
      };
    } catch {
      // Retain existing summary if error
    }
  };

  // Fetch form reference options
  const fetchFormOptions = async () => {
    try {
      const res = await goodsIssuesService.getFormOptions();
      const data = res.data || res;
      if (data.warehouses && data.warehouses.length > 0) {
        warehouses.value = data.warehouses;
      }
      if (data.officers && data.officers.length > 0) {
        officers.value = data.officers;
      }
      if (data.purposes && data.purposes.length > 0) {
        purposes.value = data.purposes;
      }
    } catch {
      // Sensible defaults
      if (warehouses.value.length === 0) {
        warehouses.value = ['Gudang Utama Farmasi', 'Apotek Rawat Jalan', 'Apotek Rawat Inap'];
      }
      if (officers.value.length === 0) {
        officers.value = ['Apt. Budi Santoso, S.Farm', 'Siti Rahmawati, A.Md.Farm'];
      }
      if (purposes.value.length === 0) {
        purposes.value = [
          'Kebutuhan Internal',
          'Barang Rusak',
          'Kegiatan Sosial',
          'Kedaluwarsa/Pemusnahan',
          'Buffer Ruangan',
          'Penelitian/Uji Mutu',
          'Lainnya'
        ];
      }
    }
  };

  // Fetch available warehouse stocks for item dropdown
  const fetchAvailableStocks = async (warehouseNameOrId, customParams = {}) => {
    try {
      let resolvedWarehouseId = null;
      if (typeof warehouseNameOrId === 'number' || (!isNaN(Number(warehouseNameOrId)) && warehouseNameOrId !== '')) {
        resolvedWarehouseId = Number(warehouseNameOrId);
      } else if (typeof warehouseNameOrId === 'string' && warehouseNameOrId !== 'Semua Gudang Asal') {
        const found = warehouses.value.find((w) => (w.name || w) === warehouseNameOrId);
        if (found && found.id) {
          resolvedWarehouseId = found.id;
        }
      }

      let res;
      if (resolvedWarehouseId) {
        try {
          res = await goodsIssuesService.getAvailableStocks(resolvedWarehouseId, customParams);
        } catch {
          res = await goodsIssuesService.getWarehouseStocks({ warehouse_id: resolvedWarehouseId, ...customParams });
        }
      } else {
        const params = {};
        if (warehouseNameOrId && warehouseNameOrId !== 'Semua Gudang Asal') {
          params.warehouse = warehouseNameOrId;
        }
        res = await goodsIssuesService.getWarehouseStocks({ ...params, ...customParams });
      }

      const rawItems = Array.isArray(res?.data)
        ? res.data
        : (Array.isArray(res) ? res : (res?.data?.data || []));

      catalogMedicines.value = rawItems.map((it) => {
        const stockId = it.stock_id ?? it.id;
        const medicineId = it.item_id ?? it.medicine_id ?? it.medicineId;
        const medicineName = it.item_name ?? it.medicine_name ?? it.medicineName ?? it.name;
        const medicineCode = it.item_code ?? it.medicine_code ?? it.medicineCode ?? it.code;
        const unitName = it.unit_name ?? it.unit ?? 'Unit';
        const batchNumber = it.batch_number ?? it.batchNumber ?? 'REG-BATCH';
        const expiredDate = it.expiry_date ?? it.expired_date ?? it.expiredDate ?? '2027-12-31';
        const availableStock = Number(it.available_stock ?? it.availableStock ?? 0);
        const price = Number(it.selling_price ?? it.price ?? it.unit_price ?? it.unitPrice ?? 0);

        return {
          id: stockId,
          stockId,
          stock_id: stockId,
          medicineId,
          medicine_id: medicineId,
          warehouseStockId: stockId,
          name: medicineName,
          medicineName,
          code: medicineCode,
          medicineCode,
          unit: unitName,
          unitName,
          defaultBatch: batchNumber,
          batchNumber,
          batch_number: batchNumber,
          expDate: expiredDate,
          expiry_date: expiredDate,
          expiredDate,
          availableStock,
          available_stock: availableStock,
          price,
          selling_price: price,
          unitPrice: price
        };
      });
    } catch {
      catalogMedicines.value = [];
    }
  };

  // Fetch goods issues list
  const fetchIssues = async (customParams = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const searchVal = customParams.search !== undefined
        ? customParams.search
        : (appliedFilters.search || appliedFilters.issueNumber || undefined);

      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        search: searchVal || undefined,
        startDate: appliedFilters.startDate || undefined,
        endDate: appliedFilters.endDate || undefined,
        warehouse: appliedFilters.warehouse !== 'Semua Gudang Asal' ? appliedFilters.warehouse : undefined,
        officer: appliedFilters.officer !== 'Semua Petugas' ? appliedFilters.officer : undefined,
        purpose: appliedFilters.purpose !== 'Semua Keperluan' ? appliedFilters.purpose : undefined,
        notes: appliedFilters.notes || undefined,
        ...customParams
      };

      const res = await goodsIssuesService.getGoodsIssues(params);
      issues.value = res.data || [];

      if (res.meta) {
        pagination.current_page = res.meta.current_page;
        pagination.per_page = res.meta.per_page;
        pagination.last_page = res.meta.last_page;
        pagination.total = res.meta.total;
        pagination.from = res.meta.from ?? 0;
        pagination.to = res.meta.to ?? 0;
      } else {
        pagination.total = issues.value.length;
        pagination.from = issues.value.length > 0 ? 1 : 0;
        pagination.to = issues.value.length;
        pagination.last_page = 1;
      }
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal memuat daftar pengeluaran barang';
      issues.value = [];
    } finally {
      loading.value = false;
    }
  };

  // Handlers
  const applyFilters = (newFilters) => {
    if (newFilters) {
      Object.assign(filters, newFilters);
      Object.assign(appliedFilters, newFilters);
    } else {
      Object.assign(appliedFilters, filters);
    }
    pagination.current_page = 1;
    fetchIssues();
    fetchSummary();
  };

  const resetFilters = () => {
    Object.assign(filters, defaultFilters());
    Object.assign(appliedFilters, defaultFilters());
    pagination.current_page = 1;
    fetchIssues();
    fetchSummary();
  };

  const setPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      pagination.current_page = page;
      fetchIssues();
    }
  };

  const setPerPage = (count) => {
    pagination.per_page = Number(count);
    pagination.current_page = 1;
    fetchIssues();
  };

  // Helper to generate next issue number preview
  const generateNextIssueNumber = () => {
    const now = new Date();
    const yearMonth = now.toISOString().slice(0, 7).replace('-', '');
    const seq = String(pagination.total + 1).padStart(4, '0');
    return `OUT-${yearMonth}-${seq}`;
  };

  // Create Issue
  const createIssue = async (payload, isDraft = false) => {
    submitting.value = true;
    try {
      const body = {
        ...payload,
        is_draft: isDraft,
        isDraft: isDraft
      };
      const res = await goodsIssuesService.createGoodsIssue(body);
      await Promise.all([fetchIssues(), fetchSummary()]);
      return { success: true, data: res.data || res };
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal menyimpan pengeluaran barang';
      const errors = err.response?.data?.errors || {};
      return { success: false, message: msg, errors };
    } finally {
      submitting.value = false;
    }
  };

  // Update Draft Issue
  const updateIssue = async (id, payload, isDraft = false) => {
    submitting.value = true;
    try {
      const body = {
        ...payload,
        is_draft: isDraft,
        isDraft: isDraft
      };
      const res = await goodsIssuesService.updateGoodsIssue(id, body);
      await Promise.all([fetchIssues(), fetchSummary()]);
      return { success: true, data: res.data || res };
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal memperbarui draft pengeluaran barang';
      const errors = err.response?.data?.errors || {};
      return { success: false, message: msg, errors };
    } finally {
      submitting.value = false;
    }
  };

  // Finalize Draft Issue
  const finalizeIssue = async (id) => {
    submitting.value = true;
    try {
      const res = await goodsIssuesService.finalizeGoodsIssue(id);
      await Promise.all([fetchIssues(), fetchSummary()]);
      return { success: true, data: res.data || res };
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Gagal memfinalisasi pengeluaran barang'
      };
    } finally {
      submitting.value = false;
    }
  };

  // Delete Draft Issue
  const deleteIssue = async (id) => {
    submitting.value = true;
    try {
      await goodsIssuesService.deleteGoodsIssue(id);
      await Promise.all([fetchIssues(), fetchSummary()]);
      return { success: true };
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Gagal menghapus draft pengeluaran barang'
      };
    } finally {
      submitting.value = false;
    }
  };

  // Get Single Goods Issue Detail
  const getGoodsIssue = async (id) => {
    loading.value = true;
    try {
      const res = await goodsIssuesService.getGoodsIssue(id);
      return { success: true, data: res.data || res };
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Gagal memuat detail pengeluaran barang'
      };
    } finally {
      loading.value = false;
    }
  };

  // Add Item to Goods Issue
  const addItem = async (goodsIssueId, payload) => {
    submitting.value = true;
    try {
      const res = await goodsIssuesService.addItem(goodsIssueId, payload);
      return { success: true, data: res.data || res };
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal menambahkan sediaan pengeluaran';
      const errors = err.response?.data?.errors || {};
      return { success: false, message: msg, errors };
    } finally {
      submitting.value = false;
    }
  };

  // Update Item in Goods Issue
  const updateItem = async (goodsIssueId, itemId, payload) => {
    submitting.value = true;
    try {
      const res = await goodsIssuesService.updateItem(goodsIssueId, itemId, payload);
      return { success: true, data: res.data || res };
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal memperbarui sediaan pengeluaran';
      const errors = err.response?.data?.errors || {};
      return { success: false, message: msg, errors };
    } finally {
      submitting.value = false;
    }
  };

  // Delete Item from Goods Issue
  const deleteItem = async (goodsIssueId, itemId) => {
    submitting.value = true;
    try {
      const res = await goodsIssuesService.deleteItem(goodsIssueId, itemId);
      return { success: true, data: res.data || res };
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal menghapus sediaan pengeluaran';
      return { success: false, message: msg };
    } finally {
      submitting.value = false;
    }
  };

  // Format Date to DD-MM-YYYY
  const formatDateIndo = (dateStr) => {
    if (!dateStr) return '-';
    try {
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      return `${day}-${month}-${year}`;
    } catch {
      return dateStr;
    }
  };

  // Format Currency to Rupiah
  const formatRupiah = (val) => {
    const num = Number(val) || 0;
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
  };

  // Cancel Issue
  const cancelIssue = async (id, reason) => {
    submitting.value = true;
    try {
      const res = await goodsIssuesService.cancelGoodsIssue(id, reason);
      await Promise.all([fetchIssues(), fetchSummary()]);
      return { success: true, data: res.data || res };
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Gagal membatalkan transaksi pengeluaran barang'
      };
    } finally {
      submitting.value = false;
    }
  };

  // Export CSV
  const exportIssuesCsv = async () => {
    try {
      const params = {
        search: appliedFilters.issueNumber || undefined,
        start_date: appliedFilters.startDate || undefined,
        end_date: appliedFilters.endDate || undefined,
        warehouse: appliedFilters.warehouse !== 'Semua Gudang Asal' ? appliedFilters.warehouse : undefined,
        officer: appliedFilters.officer !== 'Semua Petugas' ? appliedFilters.officer : undefined,
        purpose: appliedFilters.purpose !== 'Semua Keperluan' ? appliedFilters.purpose : undefined
      };

      const blob = await goodsIssuesService.exportCsv(params);
      const url = window.URL.createObjectURL(new Blob([blob], { type: 'text/csv;charset=utf-8;' }));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `Daftar_Pengeluaran_Barang_${new Date().toISOString().slice(0, 10)}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      return { success: true };
    } catch {
      return { success: false, message: 'Gagal mengekspor data ke file CSV' };
    }
  };

  // Initialize on mount if called inside component setup
  if (getCurrentInstance()) {
    onMounted(async () => {
      await Promise.all([
        fetchFormOptions(),
        fetchSummary(),
        fetchIssues()
      ]);
      if (warehouses.value.length > 0) {
        fetchAvailableStocks(warehouses.value[0]);
      }
    });
  }

  return {
    issues,
    warehouses,
    officers,
    purposes,
    catalogMedicines,
    loading,
    submitting,
    error,
    filters,
    appliedFilters,
    pagination,
    summary,
    filteredIssues,
    paginatedIssues,
    paginationInfo,
    fetchIssues,
    fetchSummary,
    fetchFormOptions,
    fetchAvailableStocks,
    applyFilters,
    resetFilters,
    setPage,
    setPerPage,
    generateNextIssueNumber,
    createIssue,
    updateIssue,
    finalizeIssue,
    deleteIssue,
    cancelIssue,
    exportIssuesCsv,
    getGoodsIssue,
    addItem,
    updateItem,
    deleteItem,
    formatDateIndo,
    formatRupiah
  };
}
