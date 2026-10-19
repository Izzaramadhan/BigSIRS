import { ref, reactive, computed } from 'vue';
import {
  initialGoodsIssues,
  mockIssueWarehouses,
  mockIssueOfficers,
  mockIssuePurposes,
  mockIssueMedicinesCatalog
} from '@/mocks/goodsIssues';

export function useGoodsIssues() {
  const issues = ref(JSON.parse(JSON.stringify(initialGoodsIssues)));
  const warehouses = ref(mockIssueWarehouses);
  const officers = ref(mockIssueOfficers);
  const purposes = ref(mockIssuePurposes);
  const catalogMedicines = ref(mockIssueMedicinesCatalog);

  const loading = ref(false);
  const submitting = ref(false);

  const defaultFilters = () => ({
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
    per_page: 10
  });

  // Summary Metrics (exact values from specification and reference design)
  const summary = computed(() => {
    return {
      todayIssues: '8',
      todayUnit: 'Dokumen',
      todayNote: 'Disetujui hari ini (Shift 1 & 2)',
      totalItemOut: '1.420',
      totalItemUnit: 'Unit',
      totalItemNote: 'Akumulasi fisik bulan berjalan',
      damagedItem: '42',
      damagedUnit: 'Unit',
      damagedNote: 'Kerusakan kemasan & pecah',
      expiredItem: '65',
      expiredUnit: 'Unit',
      expiredNote: 'Karantina & pemusnahan BPOM'
    };
  });

  // Filtered List
  const filteredIssues = computed(() => {
    let result = [...issues.value];

    // Filter by Issue Number
    if (appliedFilters.issueNumber && appliedFilters.issueNumber.trim() !== '') {
      const q = appliedFilters.issueNumber.toLowerCase().trim();
      result = result.filter(
        (it) =>
          it.issueNumber.toLowerCase().includes(q) ||
          (it.referenceNumber && it.referenceNumber.toLowerCase().includes(q))
      );
    }

    // Filter by Start Date
    if (appliedFilters.startDate) {
      result = result.filter((it) => {
        const itemDate = it.dateTime.split(' ')[0];
        return itemDate >= appliedFilters.startDate;
      });
    }

    // Filter by End Date
    if (appliedFilters.endDate) {
      result = result.filter((it) => {
        const itemDate = it.dateTime.split(' ')[0];
        return itemDate <= appliedFilters.endDate;
      });
    }

    // Filter by Warehouse
    if (appliedFilters.warehouse && appliedFilters.warehouse !== 'Semua Gudang Asal') {
      result = result.filter((it) => it.warehouse.toLowerCase().includes(appliedFilters.warehouse.toLowerCase()));
    }

    // Filter by Officer
    if (appliedFilters.officer && appliedFilters.officer !== 'Semua Petugas') {
      result = result.filter((it) => it.officerName === appliedFilters.officer);
    }

    // Filter by Purpose
    if (appliedFilters.purpose && appliedFilters.purpose !== 'Semua Keperluan') {
      result = result.filter(
        (it) =>
          it.purpose.toLowerCase().includes(appliedFilters.purpose.toLowerCase()) ||
          (it.purposeKey && it.purposeKey.toLowerCase().includes(appliedFilters.purpose.toLowerCase()))
      );
    }

    // Filter by Notes / Disposition
    if (appliedFilters.notes && appliedFilters.notes.trim() !== '') {
      const q = appliedFilters.notes.toLowerCase().trim();
      result = result.filter(
        (it) =>
          (it.notes && it.notes.toLowerCase().includes(q)) ||
          (it.referenceNumber && it.referenceNumber.toLowerCase().includes(q)) ||
          (it.validationNote && it.validationNote.toLowerCase().includes(q))
      );
    }

    return result;
  });

  // Paginated List
  const paginatedIssues = computed(() => {
    const start = (pagination.current_page - 1) * pagination.per_page;
    return filteredIssues.value.slice(start, start + pagination.per_page);
  });

  // Pagination Info for MasterDataPagination
  const paginationInfo = computed(() => {
    const total = filteredIssues.value.length;
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

  // Helper to generate next issue number
  const generateNextIssueNumber = () => {
    const now = new Date();
    const yearMonth = now.toISOString().slice(0, 7).replace('-', '');
    const seq = String(issues.value.length + 29).padStart(4, '0');
    return `OUT-${yearMonth}-${seq}`;
  };

  // Create Issue
  const createIssue = async (payload, isDraft = false) => {
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
      const totalQty = itemsList.reduce((sum, it) => sum + (Number(it.quantity) || 0), 0);
      const itemCount = itemsList.length;

      const newIssue = {
        id: Date.now(),
        issueNumber: payload.issueNumber || generateNextIssueNumber(),
        referenceNumber: payload.referenceNumber || (isDraft ? 'Draft Permintaan Baru' : 'Ref: REQ/INT/FAR'),
        dateDisplay,
        timeDisplay,
        dateTime,
        warehouse: payload.warehouse || 'Gudang Utama Farmasi (GOF-01)',
        locationDetail: payload.locationDetail || 'Rak Distribusi',
        officerName: payload.officerName || 'Apt. Budi Santoso, S.Farm',
        officerRole: 'SIP: 19820412/SIPA/2021',
        purpose: payload.purpose || 'Kebutuhan Internal',
        purposeKey: payload.purpose || 'Kebutuhan Internal',
        itemCount,
        totalQuantity: totalQty,
        quantityUnit: itemsList[0]?.unit || 'Unit',
        quantitySubtitle: `${itemCount} Jenis Barang`,
        status: isDraft ? 'Draft' : 'Selesai',
        validationNote: '',
        notes: payload.notes || '',
        items: itemsList
      };

      issues.value.unshift(newIssue);
      return { success: true, data: newIssue };
    } finally {
      submitting.value = false;
    }
  };

  // Update Draft Issue
  const updateIssue = async (id, payload, isDraft = false) => {
    submitting.value = true;
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));
      const idx = issues.value.findIndex((it) => it.id === id);
      if (idx !== -1) {
        const itemsList = payload.items || issues.value[idx].items || [];
        const totalQty = itemsList.reduce((sum, it) => sum + (Number(it.quantity) || 0), 0);
        const itemCount = itemsList.length;

        issues.value[idx] = {
          ...issues.value[idx],
          ...payload,
          itemCount,
          totalQuantity: totalQty,
          status: isDraft ? 'Draft' : 'Selesai',
          items: itemsList
        };
        return { success: true, data: issues.value[idx] };
      }
      return { success: false, message: 'Data tidak ditemukan' };
    } finally {
      submitting.value = false;
    }
  };

  // Delete / Cancel Issue
  const deleteIssue = async (id) => {
    const idx = issues.value.findIndex((it) => it.id === id);
    if (idx !== -1) {
      issues.value.splice(idx, 1);
      return { success: true };
    }
    return { success: false, message: 'Data tidak ditemukan' };
  };

  const cancelIssue = async (id, reason) => {
    const item = issues.value.find((it) => it.id === id);
    if (item) {
      item.status = 'Dibatalkan';
      item.validationNote = reason || 'Dibatalkan oleh petugas logistik';
      return { success: true };
    }
    return { success: false, message: 'Data tidak ditemukan' };
  };

  // Export CSV
  const exportIssuesCsv = () => {
    const headers = [
      'No',
      'Nomor Pengeluaran',
      'Referensi/BAP',
      'Tanggal Waktu',
      'Gudang Asal',
      'Petugas Otorisasi',
      'Keperluan',
      'Total Jenis',
      'Total Kuantitas',
      'Status',
      'Catatan'
    ];

    const rows = filteredIssues.value.map((it, idx) => [
      idx + 1,
      `"${it.issueNumber}"`,
      `"${it.referenceNumber || ''}"`,
      `"${it.dateDisplay} ${it.timeDisplay}"`,
      `"${it.warehouse}"`,
      `"${it.officerName}"`,
      `"${it.purpose}"`,
      it.itemCount,
      `${it.totalQuantity} ${it.quantityUnit}`,
      `"${it.status}"`,
      `"${it.notes || ''}"`
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((r) => r.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `Daftar_Pengeluaran_Barang_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return {
    issues,
    warehouses,
    officers,
    purposes,
    catalogMedicines,
    loading,
    submitting,
    filters,
    appliedFilters,
    pagination,
    summary,
    filteredIssues,
    paginatedIssues,
    paginationInfo,
    applyFilters,
    resetFilters,
    setPage,
    setPerPage,
    generateNextIssueNumber,
    createIssue,
    updateIssue,
    deleteIssue,
    cancelIssue,
    exportIssuesCsv
  };
}
