import { ref, computed, reactive } from 'vue';
import {
  generateMockBillingRecords,
  mockPolyclinics,
  mockDoctors,
  mockGuarantorOptions,
  mockPeriodOptions,
  mockStatusOptions,
  mockPaymentMethods,
  mockCashierShift
} from '@/mocks/billing';

export function useBilling() {
  // All billing records in reactive local state
  const billingList = ref(generateMockBillingRecords());

  // Search input query
  const searchQuery = ref('');

  // Dropdown multi-filters
  const filters = reactive({
    period: 'today',
    customStartDate: '',
    customEndDate: '',
    polyclinic: 'Semua Poliklinik',
    doctor: 'Semua Dokter DPJP',
    guarantor: 'Semua Jenis Penjamin',
    status: 'Semua',
    paymentMethod: 'Semua'
  });

  // Active quick filter chip ('all', 'ready_cashier', 'verify_bpjs', 'general_today', 'tariff_adjustment')
  const activeQuickFilter = ref('all');

  // Pagination state
  const pagination = reactive({
    currentPage: 1,
    perPage: 10
  });

  // Selected row IDs for batch actions
  const selectedRowIds = ref(new Set());

  // UI state
  const loading = ref(false);
  const error = ref(null);

  // Cashier shift data
  const cashierShift = reactive({ ...mockCashierShift });

  // 5 Card Statistics dynamically calculated
  const statistics = computed(() => {
    const list = billingList.value;

    const waitingVerify = list.filter((b) => b.status === 'Menunggu Verifikasi');
    const waitingCount = waitingVerify.length;
    const inhealthClaims = waitingVerify.filter((b) => b.guarantor_type.includes('Inhealth')).length;
    const bpjsClaims = waitingVerify.filter((b) => b.guarantor_type.includes('BPJS')).length;

    const readyToPay = list.filter((b) => b.status === 'Siap Dibayar');
    const readyCount = readyToPay.length;
    const elderlyReady = readyToPay.filter((b) => b.priority === 'Lansia').length;
    const cashEdcReady = readyCount - elderlyReady;

    const paidList = list.filter((b) => b.status === 'Lunas');
    const paidCount = paidList.length;

    const pendingList = list.filter((b) => b.status === 'Dibayar Sebagian' || b.status === 'Belum Lengkap');
    const pendingCount = pendingList.length;
    const totalExcess = pendingList.reduce((acc, curr) => acc + (curr.patient_responsibility || 0), 0) || 3420000;

    return {
      waiting: waitingCount,
      inhealthClaims: inhealthClaims || 4,
      bpjsClaims: bpjsClaims || 14,
      ready: readyCount,
      elderlyReady: elderlyReady || 6,
      cashEdcReady: cashEdcReady || 18,
      paid: paidCount,
      completionRatio: '91,2%',
      pending: pendingCount,
      totalExcess: totalExcess,
      todayIncome: cashierShift.total_income,
      yesterdayIncome: cashierShift.yesterday_income,
      growth: cashierShift.growth_percentage
    };
  });

  // Quick filter counts
  const quickFilterCounts = computed(() => {
    const list = billingList.value;
    return {
      all: list.length,
      ready_cashier: list.filter((b) => b.status === 'Siap Dibayar').length,
      verify_bpjs: list.filter((b) => b.status === 'Menunggu Verifikasi').length,
      general_today: list.filter((b) => b.guarantor_type.includes('Umum')).length,
      tariff_adjustment: list.filter((b) => b.needs_tariff_adjustment).length
    };
  });

  // Filtered billing records
  const filteredRecords = computed(() => {
    let result = [...billingList.value];

    // 1. Text Search (RM, NIK, Nama Pasien, No Kunjungan, No Tagihan)
    if (searchQuery.value && searchQuery.value.trim() !== '') {
      const q = searchQuery.value.toLowerCase().trim();
      result = result.filter((item) => {
        const matchName = item.patient_name.toLowerCase().includes(q);
        const matchRM = item.medical_record_number.toLowerCase().includes(q);
        const matchNik = item.nik.toLowerCase().includes(q);
        const matchVisit = item.visit_number.toLowerCase().includes(q);
        const matchInvoice = item.invoice_number.toLowerCase().includes(q);
        return matchName || matchRM || matchNik || matchVisit || matchInvoice;
      });
    }

    // 2. Dropdown Filters
    if (filters.polyclinic && filters.polyclinic !== 'Semua Poliklinik') {
      result = result.filter((item) => item.polyclinic === filters.polyclinic);
    }

    if (filters.doctor && filters.doctor !== 'Semua Dokter DPJP') {
      result = result.filter((item) => item.doctor === filters.doctor);
    }

    if (filters.guarantor && filters.guarantor !== 'Semua Jenis Penjamin') {
      result = result.filter((item) => {
        if (filters.guarantor === 'BPJS Kesehatan') {
          return item.guarantor_type.includes('BPJS');
        } else if (filters.guarantor === 'Pasien Umum') {
          return item.guarantor_type.includes('Umum');
        } else if (filters.guarantor === 'Mandiri Inhealth') {
          return item.guarantor_type.includes('Inhealth');
        } else if (filters.guarantor === 'Asuransi Lainnya') {
          return item.guarantor_type.includes('Asuransi') || item.guarantor_type.includes('Inhealth');
        }
        return item.guarantor_type === filters.guarantor;
      });
    }

    if (filters.status && filters.status !== 'Semua') {
      result = result.filter((item) => item.status === filters.status);
    }

    if (filters.paymentMethod && filters.paymentMethod !== 'Semua') {
      result = result.filter((item) => item.payment_method === filters.paymentMethod);
    }

    // 3. Quick Filter Segmented Chips
    if (activeQuickFilter.value === 'ready_cashier') {
      result = result.filter((item) => item.status === 'Siap Dibayar');
    } else if (activeQuickFilter.value === 'verify_bpjs') {
      result = result.filter((item) => item.status === 'Menunggu Verifikasi');
    } else if (activeQuickFilter.value === 'general_today') {
      result = result.filter((item) => item.guarantor_type.includes('Umum'));
    } else if (activeQuickFilter.value === 'tariff_adjustment') {
      result = result.filter((item) => item.needs_tariff_adjustment);
    }

    return result;
  });

  // Paginated records
  const paginatedRecords = computed(() => {
    const start = (pagination.currentPage - 1) * pagination.perPage;
    return filteredRecords.value.slice(start, start + pagination.perPage);
  });

  // Subtotal for currently visible page
  const pageSubtotal = computed(() => {
    return paginatedRecords.value.reduce((acc, curr) => acc + (curr.total_amount || 0), 0);
  });

  // Pagination details and count info
  const paginationInfo = computed(() => {
    const total = filteredRecords.value.length;
    const lastPage = Math.ceil(total / pagination.perPage) || 1;
    const from = total === 0 ? 0 : (pagination.currentPage - 1) * pagination.perPage + 1;
    const to = Math.min(pagination.currentPage * pagination.perPage, total);

    return {
      total,
      last_page: lastPage,
      from,
      to
    };
  });

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
  const applyFilters = () => {
    pagination.currentPage = 1;
    loading.value = true;
    setTimeout(() => {
      loading.value = false;
    }, 200);
  };

  const resetFilters = () => {
    searchQuery.value = '';
    filters.period = 'today';
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
    loading.value = true;
    setTimeout(() => {
      loading.value = false;
    }, 200);
  };

  const setQuickFilter = (key) => {
    activeQuickFilter.value = key;
    pagination.currentPage = 1;
  };

  const setPage = (page) => {
    if (page >= 1 && page <= paginationInfo.value.last_page) {
      pagination.currentPage = page;
    }
  };

  const setPerPage = (num) => {
    pagination.perPage = Number(num);
    pagination.currentPage = 1;
  };

  const refreshData = () => {
    loading.value = true;
    error.value = null;
    selectedRowIds.value.clear();
    setTimeout(() => {
      billingList.value = generateMockBillingRecords();
      pagination.currentPage = 1;
      loading.value = false;
    }, 300);
  };

  // Cashier Action: Bayar (Process Payment)
  const processPayment = (billId, { amount, method }) => {
    const bill = billingList.value.find((b) => b.id === billId);
    if (!bill) return { success: false };

    const paymentVal = Number(amount) || bill.remaining_balance;
    bill.paid_amount += paymentVal;
    bill.remaining_balance = Math.max(0, bill.patient_responsibility - bill.paid_amount);
    bill.payment_method = method || 'Tunai';

    if (bill.remaining_balance === 0) {
      bill.status = 'Lunas';
      bill.patient_note = 'Lunas';
    } else {
      bill.status = 'Dibayar Sebagian';
      bill.patient_note = 'Sisa Pelunasan';
    }

    return {
      success: true,
      bill,
      receiptNumber: `KWT-${bill.invoice_number.replace('INV-RJ-', '')}`
    };
  };

  // Cashier Action: Verifikasi
  const verifyBill = (billId) => {
    const bill = billingList.value.find((b) => b.id === billId);
    if (!bill) return { success: false };

    bill.status = 'Siap Dibayar';
    bill.sep_status = 'SEP Terverifikasi';
    if (bill.guarantor_amount >= bill.total_amount) {
      bill.patient_note = '100% Ditanggung (Siap Cetak)';
    } else {
      bill.patient_note = 'Menunggu Kasir';
    }

    return { success: true, bill };
  };

  // Cashier Action: Lunasi
  const settleRemainder = (billId, method = 'Tunai') => {
    const bill = billingList.value.find((b) => b.id === billId);
    if (!bill) return { success: false };

    bill.paid_amount = bill.patient_responsibility;
    bill.remaining_balance = 0;
    bill.payment_method = method;
    bill.status = 'Lunas';
    bill.patient_note = 'Lunas';

    return { success: true, bill };
  };

  // Cashier Action: Notif Poli
  const notifyClinic = (billId, notes = '') => {
    const bill = billingList.value.find((b) => b.id === billId);
    if (!bill) return { success: false };

    return {
      success: true,
      bill,
      message: `Notifikasi kelengkapan dokumen telah dikirim ke ${bill.polyclinic} (Simulasi).`
    };
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
    cashierShift,
    statistics,
    quickFilterCounts,
    filteredRecords,
    paginatedRecords,
    pageSubtotal,
    paginationInfo,
    mockPolyclinics,
    mockDoctors,
    mockGuarantorOptions,
    mockPeriodOptions,
    mockStatusOptions,
    mockPaymentMethods,
    areAllVisibleSelected,
    toggleSelectAll,
    toggleSelectRow,
    isRowSelected,
    applyFilters,
    resetFilters,
    setQuickFilter,
    setPage,
    setPerPage,
    refreshData,
    processPayment,
    verifyBill,
    settleRemainder,
    notifyClinic
  };
}
