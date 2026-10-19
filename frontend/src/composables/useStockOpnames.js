import { ref, reactive, computed } from 'vue';
import {
  initialStockOpnames,
  mockOpnameWarehouses,
  mockOpnameTypes,
  mockOpnameStatuses
} from '@/mocks/stockOpnames';

export function useStockOpnames() {
  const opnames = ref(JSON.parse(JSON.stringify(initialStockOpnames)));
  const warehouses = ref(mockOpnameWarehouses);
  const opnameTypes = ref(mockOpnameTypes);
  const opnameStatuses = ref(mockOpnameStatuses);

  const loading = ref(false);
  const submitting = ref(false);

  const defaultFilters = () => ({
    opnameNumber: '',
    startDate: '',
    endDate: '',
    warehouse: 'Semua Gudang',
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
      inProgressSessions: '3 Sesi',
      inProgressNote: '1.420 item dalam proses hitung fisik',
      pendingReviewWarehouses: '2 Gudang',
      pendingReviewNote: 'Perlu persetujuan Apoteker & PJ Logistik',
      completedThisMonthSessions: '18 Sesi',
      completedThisMonthNote: 'Akurasi stok rata-rata 99,4%',
      discrepancyItems: '14 Item',
      discrepancyValue: 'Rp1.840.000',
      discrepancyRatio: '8+ / 6-'
    };
  });

  // Filtered opname sessions
  const filteredOpnames = computed(() => {
    let result = [...opnames.value];

    // Filter by Opname Number
    if (appliedFilters.opnameNumber && appliedFilters.opnameNumber.trim() !== '') {
      const q = appliedFilters.opnameNumber.toLowerCase().trim();
      result = result.filter(
        (o) =>
          o.opnameNumber.toLowerCase().includes(q) ||
          (o.title && o.title.toLowerCase().includes(q))
      );
    }

    // Filter by Start Date
    if (appliedFilters.startDate) {
      result = result.filter((o) => {
        const itemDate = o.dateTime.split(' ')[0];
        return itemDate >= appliedFilters.startDate;
      });
    }

    // Filter by End Date
    if (appliedFilters.endDate) {
      result = result.filter((o) => {
        const itemDate = o.dateTime.split(' ')[0];
        return itemDate <= appliedFilters.endDate;
      });
    }

    // Filter by Warehouse
    if (appliedFilters.warehouse && appliedFilters.warehouse !== 'Semua Gudang') {
      result = result.filter((o) => o.warehouse === appliedFilters.warehouse);
    }

    // Filter by Status
    if (appliedFilters.status && appliedFilters.status !== 'Semua Status') {
      result = result.filter((o) => o.status === appliedFilters.status);
    }

    return result;
  });

  // Paginated opname sessions
  const paginatedOpnames = computed(() => {
    const start = (pagination.current_page - 1) * pagination.per_page;
    return filteredOpnames.value.slice(start, start + pagination.per_page);
  });

  // Pagination info for MasterDataPagination
  const paginationInfo = computed(() => {
    const total = filteredOpnames.value.length;
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

  // Handlers
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

  // Generate next SO number
  const generateOpnameNumber = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const seq = String(opnames.value.length + 46).padStart(4, '0');
    return `SO-${year}${month}-${seq}`;
  };

  // Add stock opname (Draft or Active)
  const createStockOpname = async (payload, isDraft = false) => {
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

      const newOpname = {
        id: Date.now(),
        opnameNumber: payload.opnameNumber || generateOpnameNumber(),
        title: payload.title || (isDraft ? 'Draft Sesi Audit' : 'Audit Fisik Persediaan'),
        type: payload.type || 'Audit Rutin',
        dateDisplay,
        timeDisplay,
        dateTime,
        warehouse: payload.warehouse || 'Gudang Utama Farmasi',
        locationDetail: payload.locationDetail || 'Semua Rak Unit',
        totalItems: payload.totalItems || 150,
        countedItems: isDraft ? 0 : 0,
        differenceCount: isDraft ? 0 : 0,
        differenceStatus: isDraft ? '- Belum Dihitung -' : 'Belum Dihitung',
        differenceType: 'muted',
        progressPercent: 0,
        progressText: isDraft ? '0% – Menunggu Mulai' : '0% – Mulai Hitung',
        officerName: payload.officerName || 'Apt. Budi Santoso, S.Farm',
        officerRole: 'Staf Farmasi',
        status: isDraft ? 'Draft Sesi' : 'Sedang Dihitung',
        notes: payload.notes || '',
        items: []
      };

      opnames.value.unshift(newOpname);
      return { success: true, data: newOpname };
    } finally {
      submitting.value = false;
    }
  };

  // Cancel stock opname
  const cancelStockOpname = async (id, reason) => {
    const item = opnames.value.find((o) => o.id === id);
    if (item) {
      item.status = 'Dibatalkan';
      item.progressPercent = 0;
      item.progressText = 'Dibatalkan';
      item.cancelReason = reason || 'Dibatalkan oleh petugas logistik.';
      return { success: true };
    }
    return { success: false, message: 'Data tidak ditemukan' };
  };

  // Review / Approve discrepancy
  const approveStockOpname = async (id) => {
    const item = opnames.value.find((o) => o.id === id);
    if (item) {
      item.status = 'Selesai';
      item.differenceStatus = `${item.differenceCount} Item (Adjusted)`;
      item.differenceType = 'warning';
      item.progressPercent = 100;
      item.progressText = '100% – Telah Disesuaikan';
      return { success: true };
    }
    return { success: false, message: 'Data tidak ditemukan' };
  };

  // Export CSV
  const exportStockOpnamesCsv = () => {
    const headers = [
      'No',
      'Nomor Opname',
      'Judul',
      'Tanggal Mulai',
      'Gudang',
      'Total Item',
      'Sudah Dihitung',
      'Status Selisih',
      'Petugas',
      'Status'
    ];

    const rows = filteredOpnames.value.map((o, idx) => [
      idx + 1,
      `"${o.opnameNumber}"`,
      `"${o.title || ''}"`,
      `"${o.dateDisplay} ${o.timeDisplay}"`,
      `"${o.warehouse}"`,
      o.totalItems,
      o.countedItems !== null ? o.countedItems : '-',
      `"${o.differenceStatus}"`,
      `"${o.officerName}"`,
      `"${o.status}"`
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((r) => r.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `Daftar_Stok_Opname_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return {
    opnames,
    warehouses,
    opnameTypes,
    opnameStatuses,
    loading,
    submitting,
    filters,
    appliedFilters,
    pagination,
    summary,
    filteredOpnames,
    paginatedOpnames,
    paginationInfo,
    applyFilters,
    resetFilters,
    setPage,
    setPerPage,
    generateOpnameNumber,
    createStockOpname,
    cancelStockOpname,
    approveStockOpname,
    exportStockOpnamesCsv
  };
}
