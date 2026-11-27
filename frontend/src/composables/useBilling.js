import { ref, computed, reactive } from 'vue';
import { billingService } from '@/services/billing.service';
import { polyclinicService } from '@/services/polyclinic';
import {
  mockDoctors,
  mockGuarantorOptions,
  mockPeriodOptions,
  mockStatusOptions,
  mockPaymentMethods,
  mockBillingPaymentMethods,
  mockOrderDiscountOptions,
  mockBillingOrderCatalogs
} from '@/mocks/billing';

export function useBilling() {
  // Billing records loaded strictly from backend API
  const billingList = ref([]);
  const polyclinicsList = ref([]);
  const polyclinicsError = ref(null);

  // Search input query
  const searchQuery = ref('');

  // Dropdown multi-filters
  const filters = reactive({
    period: 'all',
    customStartDate: '',
    customEndDate: '',
    polyclinic: 'Semua Poliklinik',
    doctor: 'Semua Dokter DPJP',
    guarantor: 'Semua Jenis Penjamin',
    status: 'Semua',
    paymentMethod: 'Semua'
  });

  // Sort state
  const sort = reactive({
    key: '',
    direction: 'asc'
  });

  // Active quick filter chip
  const activeQuickFilter = ref('all');

  // Pagination state (driven by backend metadata)
  const pagination = reactive({
    currentPage: 1,
    perPage: 10,
    total: 0,
    lastPage: 1,
    from: 0,
    to: 0
  });

  // Selected row IDs for display selection
  const selectedRowIds = ref(new Set());

  // UI state
  const loading = ref(false);
  const error = ref(null);
  let latestFetchSeq = 0;

  const cashierShift = reactive({
    shift_number: 1,
    cashier_name: '-',
    time_range: '-',
    total_income: 0,
    yesterday_income: 0,
    growth_percentage: '0%'
  });

  const extractBillingErrorMessage = (err, fallback) => {
    const status = err?.response?.status;
    if (status === 401 || status === 403) {
      return err.response?.data?.message || 'Sesi tidak sah atau akses data billing ditolak.';
    }
    if (status === 404) {
      return err.response?.data?.message || 'Data tagihan kunjungan tidak ditemukan di database.';
    }
    if (!err?.response) {
      return 'Gagal terhubung ke server backend saat memuat data billing.';
    }
    return err.response?.data?.message || err.message || fallback;
  };

  const loadPolyclinics = async () => {
    polyclinicsError.value = null;
    try {
      const res = await polyclinicService.getPolyclinics({ per_page: 100 });
      const data = res.data?.data || res.data;
      if (Array.isArray(data)) {
        const names = data.map((p) => p.name || p.nama || p.nama_poliklinik).filter(Boolean);
        polyclinicsList.value = Array.from(new Set(names));
      } else {
        polyclinicsList.value = [];
      }
    } catch (err) {
      polyclinicsError.value = extractBillingErrorMessage(err, 'Gagal memuat daftar poliklinik.');
      polyclinicsList.value = [];
    }
  };

  const fetchBillings = async () => {
    const seq = ++latestFetchSeq;
    loading.value = true;
    error.value = null;

    try {
      const params = {
        page: pagination.currentPage,
        per_page: pagination.perPage
      };

      if (searchQuery.value && searchQuery.value.trim() !== '') {
        params.q = searchQuery.value.trim();
      }
      if (filters.customStartDate) {
        params.date_from = filters.customStartDate;
      }
      if (filters.customEndDate) {
        params.date_to = filters.customEndDate;
      }
      if (filters.polyclinic && filters.polyclinic !== 'Semua Poliklinik') {
        params.polyclinic = filters.polyclinic;
      }
      if (filters.guarantor && filters.guarantor !== 'Semua Jenis Penjamin') {
        params.guarantor = filters.guarantor;
      }
      if (filters.status && filters.status !== 'Semua') {
        params.status = filters.status;
      }
      if (sort.key) {
        params.sort_by = sort.key;
        params.sort_direction = sort.direction;
      }

      const response = await billingService.getBillings(params);
      if (seq !== latestFetchSeq) return;

      const resData = response.data;
      const items = Array.isArray(resData?.data) ? resData.data : (Array.isArray(resData) ? resData : []);
      billingList.value = items;

      const meta = resData?.meta;
      if (meta) {
        pagination.total = meta.total ?? items.length;
        pagination.lastPage = meta.last_page ?? 1;
        pagination.from = meta.from ?? (items.length > 0 ? 1 : 0);
        pagination.to = meta.to ?? items.length;
        pagination.currentPage = meta.current_page ?? pagination.currentPage;
      } else {
        pagination.total = items.length;
        pagination.lastPage = 1;
        pagination.from = items.length > 0 ? 1 : 0;
        pagination.to = items.length;
      }
    } catch (err) {
      if (seq !== latestFetchSeq) return;
      error.value = extractBillingErrorMessage(err, 'Gagal memuat data billing pasien dari backend.');
      billingList.value = [];
      pagination.total = 0;
      pagination.lastPage = 1;
      pagination.from = 0;
      pagination.to = 0;
    } finally {
      if (seq === latestFetchSeq) {
        loading.value = false;
      }
    }
  };

  // Statistics derived honestly from loaded backend records (never hardcoded fallback numbers)
  const statistics = computed(() => {
    const list = billingList.value;
    const waitingVerify = list.filter((b) => b.status === 'Menunggu Verifikasi');
    const readyToPay = list.filter((b) => b.status === 'Siap Dibayar' || b.status === 'Belum Bayar');
    const paidList = list.filter((b) => b.status === 'Lunas');
    const pendingList = list.filter((b) => b.status === 'Dibayar Sebagian' || b.status === 'Belum Lengkap');
    const totalProcessed = paidList.length + readyToPay.length + pendingList.length + waitingVerify.length;
    const ratio = totalProcessed > 0 ? ((paidList.length / totalProcessed) * 100).toFixed(1).replace('.', ',') : '0';

    return {
      waiting: waitingVerify.length,
      inhealthClaims: waitingVerify.filter((b) => String(b.guarantor_type || '').includes('Inhealth')).length,
      bpjsClaims: waitingVerify.filter((b) => String(b.guarantor_type || '').includes('BPJS')).length,
      ready: readyToPay.length,
      elderlyReady: readyToPay.filter((b) => b.priority === 'Lansia').length,
      cashEdcReady: readyToPay.filter((b) => b.priority !== 'Lansia').length,
      paid: paidList.length,
      completionRatio: `${ratio}%`,
      pending: pendingList.length,
      totalExcess: pendingList.reduce((acc, curr) => acc + (Number(curr.remaining_balance) || 0), 0),
      todayIncome: paidList.reduce((acc, curr) => acc + (Number(curr.paid_amount) || 0), 0),
      yesterdayIncome: 0,
      growth: '0%'
    };
  });

  // Quick filter counts derived from loaded records
  const quickFilterCounts = computed(() => {
    const list = billingList.value;
    return {
      all: pagination.total || list.length,
      ready_cashier: list.filter((b) => b.status === 'Siap Dibayar' || b.status === 'Belum Bayar').length,
      verify_bpjs: list.filter((b) => b.status === 'Menunggu Verifikasi').length,
      general_today: list.filter((b) => String(b.guarantor_type || '').toUpperCase().includes('UMUM')).length,
      tariff_adjustment: list.filter((b) => Number(b.unpriced_item_count || 0) > 0).length
    };
  });

  const filteredRecords = computed(() => {
    let result = [...billingList.value];
    if (activeQuickFilter.value === 'ready_cashier') {
      result = result.filter((item) => item.status === 'Siap Dibayar' || item.status === 'Belum Bayar');
    } else if (activeQuickFilter.value === 'verify_bpjs') {
      result = result.filter((item) => item.status === 'Menunggu Verifikasi');
    } else if (activeQuickFilter.value === 'general_today') {
      result = result.filter((item) => String(item.guarantor_type || '').toUpperCase().includes('UMUM'));
    } else if (activeQuickFilter.value === 'tariff_adjustment') {
      result = result.filter((item) => Number(item.unpriced_item_count || 0) > 0);
    }
    return result;
  });

  const setSort = async (key) => {
    if (sort.key === key) {
      if (sort.direction === 'asc') {
        sort.direction = 'desc';
      } else {
        sort.key = '';
        sort.direction = 'asc';
      }
    } else {
      sort.key = key;
      sort.direction = 'asc';
    }
    pagination.currentPage = 1;
    await fetchBillings();
  };

  // Paginated records from API
  const paginatedRecords = computed(() => filteredRecords.value);

  const pageSubtotal = computed(() => {
    return paginatedRecords.value.reduce((acc, curr) => acc + (Number(curr.verified_subtotal ?? curr.total_amount) || 0), 0);
  });

  const paginationInfo = computed(() => ({
    total: pagination.total,
    last_page: pagination.lastPage,
    from: pagination.from,
    to: pagination.to
  }));

  // Selection Checkbox Logic
  const areAllVisibleSelected = computed(() => {
    const visible = paginatedRecords.value;
    if (visible.length === 0) return false;
    return visible.every((item) => selectedRowIds.value.has(item.id));
  });

  const toggleSelectAll = (checked) => {
    const newSet = new Set(selectedRowIds.value);
    if (checked) {
      paginatedRecords.value.forEach((item) => newSet.add(item.id));
    } else {
      paginatedRecords.value.forEach((item) => newSet.delete(item.id));
    }
    selectedRowIds.value = newSet;
  };

  const toggleSelectRow = (id) => {
    const newSet = new Set(selectedRowIds.value);
    if (newSet.has(id)) {
      newSet.delete(id);
    } else {
      newSet.add(id);
    }
    selectedRowIds.value = newSet;
  };

  const isRowSelected = (id) => {
    return selectedRowIds.value.has(id);
  };

  // Actions
  const applyFilters = async () => {
    pagination.currentPage = 1;
    await fetchBillings();
  };

  const resetFilters = async () => {
    searchQuery.value = '';
    filters.period = 'all';
    filters.customStartDate = '';
    filters.customEndDate = '';
    filters.polyclinic = 'Semua Poliklinik';
    filters.doctor = 'Semua Dokter DPJP';
    filters.guarantor = 'Semua Jenis Penjamin';
    filters.status = 'Semua';
    filters.paymentMethod = 'Semua';
    activeQuickFilter.value = 'all';
    pagination.currentPage = 1;
    selectedRowIds.value.clear();
    await fetchBillings();
  };

  const setQuickFilter = (key) => {
    activeQuickFilter.value = key;
    pagination.currentPage = 1;
  };

  const setPage = async (page) => {
    if (page >= 1 && page <= paginationInfo.value.last_page) {
      pagination.currentPage = page;
      await fetchBillings();
    }
  };

  const setPerPage = async (num) => {
    pagination.perPage = Number(num);
    pagination.currentPage = 1;
    await fetchBillings();
  };

  const refreshData = async () => {
    selectedRowIds.value.clear();
    pagination.currentPage = 1;
    await fetchBillings();
  };

  const READ_ONLY_MSG = 'Fitur pembayaran, verifikasi klaim, dan perubahan status lunas dinonaktifkan pada Fase 2B.19A (menunggu aturan bisnis Fase 2B.19B).';

  // Read-only stubs (never mutate billing or status into Lunas locally)
  const processPayment = () => ({ success: false, disabled: true, message: READ_ONLY_MSG });
  const verifyBill = () => ({ success: false, disabled: true, message: READ_ONLY_MSG });
  const settleRemainder = () => ({ success: false, disabled: true, message: READ_ONLY_MSG });
  const notifyClinic = () => ({ success: false, disabled: true, message: READ_ONLY_MSG });
  const saveBillOrders = () => ({ success: false, disabled: true, message: READ_ONLY_MSG });

  const fetchBillingDetail = async (visitId) => {
    if (!visitId) {
      return { success: false, status: 400, message: 'Identifier kunjungan (visit_id) tidak valid.' };
    }
    try {
      const res = await billingService.getBilling(visitId);
      const record = res.data?.data || res.data;
      if (!record || !record.visit_id) {
        return { success: false, status: 404, message: 'Data tagihan kunjungan tidak ditemukan di database.' };
      }
      return { success: true, data: record };
    } catch (err) {
      return {
        success: false,
        status: err?.response?.status ?? null,
        message: extractBillingErrorMessage(err, 'Gagal memuat rincian tagihan kunjungan.')
      };
    }
  };

  const getBillById = (visitId) => {
    return billingList.value.find((b) => String(b.visit_id || b.id) === String(visitId || '')) || null;
  };

  const getBillOrders = (visitId) => {
    const bill = getBillById(visitId);
    return Array.isArray(bill?.items) ? bill.items.map((item) => ({ ...item })) : [];
  };

  return {
    billingList,
    searchQuery,
    filters,
    activeQuickFilter,
    pagination,
    selectedRowIds,
    loading,
    error,
    polyclinicsError,
    cashierShift,
    sort,
    statistics,
    quickFilterCounts,
    filteredRecords,
    paginatedRecords,
    pageSubtotal,
    paginationInfo,
    polyclinics: polyclinicsList,
    mockPolyclinics: polyclinicsList,
    mockDoctors,
    mockGuarantorOptions,
    mockPeriodOptions,
    mockStatusOptions,
    mockPaymentMethods,
    mockBillingPaymentMethods,
    mockOrderDiscountOptions,
    mockBillingOrderCatalogs,
    areAllVisibleSelected,
    toggleSelectAll,
    toggleSelectRow,
    isRowSelected,
    loadPolyclinics,
    fetchBillings,
    fetchBillingDetail,
    applyFilters,
    resetFilters,
    setQuickFilter,
    setSort,
    setPage,
    setPerPage,
    refreshData,
    processPayment,
    verifyBill,
    settleRemainder,
    notifyClinic,
    getBillById,
    getBillOrders,
    saveBillOrders
  };
}
