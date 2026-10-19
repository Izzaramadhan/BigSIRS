import { ref, reactive, computed } from 'vue';
import {
  initialPosSales,
  mockPosLocations,
  mockPaymentMethods,
  mockTransactionStatuses,
  mockOtcMedicines
} from '@/mocks/posSales';

export function usePosSales() {
  const sales = ref(JSON.parse(JSON.stringify(initialPosSales)));
  const locations = ref(mockPosLocations);
  const paymentMethods = ref(mockPaymentMethods);
  const transactionStatuses = ref(mockTransactionStatuses);
  const otcMedicines = ref(mockOtcMedicines);

  const loading = ref(false);
  const submitting = ref(false);
  const tableState = ref('normal'); // 'normal' | 'skeleton' | 'empty' | 'error'
  const errorMessage = ref('');

  const defaultFilters = () => ({
    transactionNumber: '',
    customerName: '',
    startDate: '',
    endDate: '',
    location: 'Semua Lokasi',
    paymentMethod: 'Semua Metode',
    status: 'Semua Status'
  });

  const filters = reactive(defaultFilters());
  const appliedFilters = reactive(defaultFilters());

  const pagination = reactive({
    current_page: 1,
    per_page: 10
  });

  // Summary Cards computation
  const summary = computed(() => {
    return {
      todayTransactions: '42',
      todayUnit: 'Nota',
      todayChange: '+12% vs kemarin',
      todayCashier: 'Shift 1 & 2 Aktif',
      productsSold: '158',
      productsUnit: 'Unit Sediaan',
      productsSubtitle: 'Obat OTC, Vitamin, BMHP',
      totalSalesCurrency: 'Rp',
      totalSalesValue: '4.875.000',
      totalSalesNet: '100% Kas Masuk Bersih',
      totalSalesAverage: 'Rp 116.071/nota',
      cancelledTransactions: '2',
      cancelledUnit: 'Void Kasir',
      cancelledTotal: 'Rp 142.000',
      cancelledNote: 'Stok dikembalikan'
    };
  });

  // Filtered sales
  const filteredSales = computed(() => {
    let result = [...sales.value];

    // Filter by Transaction Number
    if (appliedFilters.transactionNumber && appliedFilters.transactionNumber.trim() !== '') {
      const q = appliedFilters.transactionNumber.toLowerCase().trim();
      result = result.filter((s) => s.transactionNumber.toLowerCase().includes(q));
    }

    // Filter by Customer Name
    if (appliedFilters.customerName && appliedFilters.customerName.trim() !== '') {
      const q = appliedFilters.customerName.toLowerCase().trim();
      result = result.filter(
        (s) =>
          s.customerName.toLowerCase().includes(q) ||
          (s.customerType && s.customerType.toLowerCase().includes(q))
      );
    }

    // Filter by Start Date
    if (appliedFilters.startDate) {
      result = result.filter((s) => {
        const saleDate = s.dateTime.split(' ')[0];
        return saleDate >= appliedFilters.startDate;
      });
    }

    // Filter by End Date
    if (appliedFilters.endDate) {
      result = result.filter((s) => {
        const saleDate = s.dateTime.split(' ')[0];
        return saleDate <= appliedFilters.endDate;
      });
    }

    // Filter by Location
    if (appliedFilters.location && appliedFilters.location !== 'Semua Lokasi') {
      result = result.filter((s) => s.location.includes(appliedFilters.location));
    }

    // Filter by Payment Method
    if (appliedFilters.paymentMethod && appliedFilters.paymentMethod !== 'Semua Metode') {
      result = result.filter((s) => s.paymentMethod.toLowerCase() === appliedFilters.paymentMethod.toLowerCase());
    }

    // Filter by Status
    if (appliedFilters.status && appliedFilters.status !== 'Semua Status') {
      result = result.filter((s) => s.status === appliedFilters.status);
    }

    return result;
  });

  // Paginated sales
  const paginatedSales = computed(() => {
    if (tableState.value === 'empty') return [];
    const start = (pagination.current_page - 1) * pagination.per_page;
    return filteredSales.value.slice(start, start + pagination.per_page);
  });

  // Pagination info
  const paginationInfo = computed(() => {
    const total = tableState.value === 'empty' ? 0 : filteredSales.value.length;
    const lastPage = Math.max(1, Math.ceil(total / pagination.per_page));
    const from = total === 0 ? 0 : (pagination.current_page - 1) * pagination.per_page + 1;
    const to = Math.min(pagination.current_page * pagination.per_page, total);

    return {
      current_page: pagination.current_page,
      per_page: pagination.per_page,
      last_page: lastPage,
      total,
      from,
      to
    };
  });

  // Actions
  const applyFilters = (newFilters) => {
    if (newFilters) {
      Object.assign(appliedFilters, newFilters);
    } else {
      Object.assign(appliedFilters, filters);
    }
    pagination.current_page = 1;
  };

  const resetFilters = () => {
    Object.assign(filters, defaultFilters());
    Object.assign(appliedFilters, defaultFilters());
    pagination.current_page = 1;
  };

  const setPage = (page) => {
    if (page >= 1 && page <= paginationInfo.value.last_page) {
      pagination.current_page = page;
    }
  };

  const setPerPage = (count) => {
    pagination.per_page = Number(count);
    pagination.current_page = 1;
  };

  const setTableState = (state) => {
    tableState.value = state;
    if (state === 'error') {
      errorMessage.value = 'Gagal memuat data transaksi kasir OTC. Silakan periksa koneksi atau coba lagi.';
    } else {
      errorMessage.value = '';
    }
  };

  const generateNextTransactionNumber = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const seq = String(sales.value.length + 43).padStart(4, '0');
    return `TR-OTC-${year}${month}${day}-${seq}`;
  };

  // Create Sale
  const createSale = async (payload, isDraft = false) => {
    submitting.value = true;
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));

      const now = new Date();
      const day = String(now.getDate()).padStart(2, '0');
      const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
      const monthStr = monthNames[now.getMonth()];
      const year = now.getFullYear();
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');

      const dateDisplay = `${day} ${monthStr} ${year}`;
      const timeDisplay = `${hours}:${minutes} WIB`;
      const dateTime = `${year}-${String(now.getMonth() + 1).padStart(2, '0')}-${day} ${hours}:${minutes}`;

      const itemsList = payload.items || [];
      const totalAmount = itemsList.reduce((sum, it) => sum + (Number(it.subtotal) || (Number(it.price) * Number(it.qty)) || 0), 0);
      const itemCount = itemsList.length;

      const newSale = {
        id: Date.now(),
        transactionNumber: payload.transactionNumber || generateNextTransactionNumber(),
        dateDisplay,
        timeDisplay,
        dateTime,
        customerName: payload.customerName || 'Walk-in Customer',
        customerType: payload.customerType || 'Umum',
        itemCount,
        itemCountText: `${itemCount} Jenis`,
        totalQtyText: `${itemsList.reduce((sum, it) => sum + Number(it.qty || 1), 0)} Unit`,
        totalAmount,
        paymentMethod: isDraft ? 'Belum Dipilih' : (payload.paymentMethod || 'Tunai'),
        paymentMethodDisplay: isDraft ? 'Belum Dipilih' : (payload.paymentMethod || 'Tunai'),
        status: isDraft ? 'Draft' : 'Lunas',
        cashier: payload.cashier || 'Rina Marlina (Kasir 1)',
        location: payload.location || 'Farmasi Pusat (Kasir 1)',
        paidAmount: isDraft ? 0 : totalAmount,
        changeAmount: 0,
        voidReason: '',
        items: itemsList
      };

      sales.value.unshift(newSale);
      return { success: true, data: newSale };
    } finally {
      submitting.value = false;
    }
  };

  // Void Sale
  const voidSale = async (id, reason) => {
    const item = sales.value.find((s) => s.id === id);
    if (item) {
      item.status = 'Dibatalkan';
      item.voidReason = reason || 'Transaksi dibatalkan / void oleh kasir.';
      return { success: true };
    }
    return { success: false, message: 'Data tidak ditemukan' };
  };

  // Delete Draft Sale
  const deleteSale = async (id) => {
    const idx = sales.value.findIndex((s) => s.id === id);
    if (idx !== -1) {
      sales.value.splice(idx, 1);
      return { success: true };
    }
    return { success: false, message: 'Data tidak ditemukan' };
  };

  // Export CSV
  const exportSalesCsv = () => {
    const headers = [
      'No',
      'No. Transaksi',
      'Tanggal Waktu',
      'Nama Pembeli',
      'Tipe Pembeli',
      'Jml Item',
      'Total Bayar',
      'Metode Bayar',
      'Status',
      'Petugas Kasir',
      'Lokasi'
    ];

    const rows = filteredSales.value.map((s, idx) => [
      idx + 1,
      `"${s.transactionNumber}"`,
      `"${s.dateDisplay} ${s.timeDisplay}"`,
      `"${s.customerName}"`,
      `"${s.customerType || 'Umum'}"`,
      s.itemCount,
      s.totalAmount,
      `"${s.paymentMethod}"`,
      `"${s.status}"`,
      `"${s.cashier}"`,
      `"${s.location}"`
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((r) => r.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `Daftar_Penjualan_OTC_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return {
    sales,
    locations,
    paymentMethods,
    transactionStatuses,
    otcMedicines,
    loading,
    submitting,
    tableState,
    errorMessage,
    filters,
    appliedFilters,
    pagination,
    summary,
    filteredSales,
    paginatedSales,
    paginationInfo,
    applyFilters,
    resetFilters,
    setPage,
    setPerPage,
    setTableState,
    generateNextTransactionNumber,
    createSale,
    voidSale,
    deleteSale,
    exportSalesCsv
  };
}
