import { ref, reactive, computed } from 'vue';
import { initialInvoices, mockSuppliers, mockMedicines } from '@/mocks/invoices';

export function useInvoices() {
  const invoices = ref(JSON.parse(JSON.stringify(initialInvoices)));
  const suppliers = ref(mockSuppliers);
  const medicines = ref(mockMedicines);

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const defaultFilters = () => ({
    invoiceNumber: '',
    supplierId: '',
    status: '',
    invoiceDateStart: '',
    invoiceDateEnd: '',
    dueDateStart: '',
    dueDateEnd: '',
    search: ''
  });

  const filters = reactive(defaultFilters());
  const appliedFilters = reactive(defaultFilters());

  const sort = reactive({
    column: 'invoiceDate',
    direction: 'desc'
  });

  const pagination = reactive({
    current_page: 1,
    per_page: 10
  });

  // Check if a due date is overdue compared to reference date
  // In the demo/fixtures, reference date is mid-September 2026 or real current date
  const isOverdue = (dueDate, status) => {
    if (status === 'Lunas' || !dueDate) return false;
    const due = new Date(dueDate);
    const now = new Date();
    // Normalize both to start of day
    due.setHours(0, 0, 0, 0);
    now.setHours(0, 0, 0, 0);
    return due < now;
  };

  const getDueStatusText = (invoice) => {
    if (invoice.status === 'Lunas') {
      if (invoice.paidAt) {
        const d = new Date(invoice.paidAt);
        const day = d.getDate();
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return `(Lunas ${day} ${months[d.getMonth()]})`;
      }
      return '(Lunas)';
    }

    if (!invoice.dueDate) return '';

    const due = new Date(invoice.dueDate);
    const now = new Date();
    due.setHours(0, 0, 0, 0);
    now.setHours(0, 0, 0, 0);

    const diffTime = due.getTime() - now.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
      return `(Lewat ${Math.abs(diffDays)} hari)`;
    } else if (diffDays === 0) {
      return '(Jatuh tempo hari ini)';
    } else {
      return `(Sisa ${diffDays} hari)`;
    }
  };

  // Summary statistics computed automatically from invoices
  const summary = computed(() => {
    const all = invoices.value;
    const totalCount = all.length;
    const totalAmount = all.reduce((sum, inv) => sum + (Number(inv.totalAmount) || 0), 0);

    const paidList = all.filter((inv) => inv.status === 'Lunas');
    const paidCount = paidList.length;
    const paidAmount = paidList.reduce((sum, inv) => sum + (Number(inv.totalAmount) || 0), 0);
    const paidPercentage = totalCount > 0 ? ((paidCount / totalCount) * 100).toFixed(1) : '0';

    const unpaidList = all.filter((inv) => inv.status !== 'Lunas');
    const unpaidCount = unpaidList.length;
    const unpaidAmount = unpaidList.reduce((sum, inv) => sum + (Number(inv.totalAmount) || 0), 0);
    const unpaidPercentage = totalCount > 0 ? ((unpaidCount / totalCount) * 100).toFixed(1) : '0';

    const overdueCount = unpaidList.filter((inv) => isOverdue(inv.dueDate, inv.status)).length;

    return {
      totalCount,
      totalAmount,
      paidCount,
      paidAmount,
      paidPercentage,
      unpaidCount,
      unpaidAmount,
      unpaidPercentage,
      overdueCount
    };
  });

  // Filtered list
  const filteredInvoices = computed(() => {
    let result = [...invoices.value];

    // Applied filters
    if (appliedFilters.invoiceNumber) {
      const q = appliedFilters.invoiceNumber.toLowerCase().trim();
      result = result.filter(
        (inv) =>
          inv.invoiceNumber.toLowerCase().includes(q) ||
          (inv.referenceNumber && inv.referenceNumber.toLowerCase().includes(q))
      );
    }

    if (appliedFilters.supplierId) {
      result = result.filter(
        (inv) => String(inv.supplierId) === String(appliedFilters.supplierId)
      );
    }

    if (appliedFilters.status) {
      result = result.filter((inv) => inv.status === appliedFilters.status);
    }

    if (appliedFilters.invoiceDateStart) {
      result = result.filter((inv) => inv.invoiceDate >= appliedFilters.invoiceDateStart);
    }

    if (appliedFilters.invoiceDateEnd) {
      result = result.filter((inv) => inv.invoiceDate <= appliedFilters.invoiceDateEnd);
    }

    if (appliedFilters.dueDateStart) {
      result = result.filter((inv) => inv.dueDate >= appliedFilters.dueDateStart);
    }

    if (appliedFilters.dueDateEnd) {
      result = result.filter((inv) => inv.dueDate <= appliedFilters.dueDateEnd);
    }

    // Search within results (instant search)
    if (filters.search) {
      const sq = filters.search.toLowerCase().trim();
      result = result.filter((inv) => {
        return (
          inv.invoiceNumber.toLowerCase().includes(sq) ||
          (inv.referenceNumber && inv.referenceNumber.toLowerCase().includes(sq)) ||
          inv.supplierName.toLowerCase().includes(sq) ||
          (inv.notes && inv.notes.toLowerCase().includes(sq)) ||
          inv.items.some((item) => item.medicineName.toLowerCase().includes(sq))
        );
      });
    }

    return result;
  });

  // Sorted list
  const sortedInvoices = computed(() => {
    const list = [...filteredInvoices.value];
    const { column, direction } = sort;
    const modifier = direction === 'asc' ? 1 : -1;

    return list.sort((a, b) => {
      let valA = a[column];
      let valB = b[column];

      if (column === 'totalAmount') {
        return (Number(valA) - Number(valB)) * modifier;
      }

      if (typeof valA === 'string') {
        return valA.localeCompare(valB) * modifier;
      }

      if (valA < valB) return -1 * modifier;
      if (valA > valB) return 1 * modifier;
      return 0;
    });
  });

  // Paginated list
  const paginatedInvoices = computed(() => {
    const start = (pagination.current_page - 1) * pagination.per_page;
    const end = start + pagination.per_page;
    return sortedInvoices.value.slice(start, end);
  });

  // Pagination info
  const paginationInfo = computed(() => {
    const total = sortedInvoices.value.length;
    const perPage = pagination.per_page;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const currentPage = Math.min(pagination.current_page, lastPage);
    const from = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
    const to = total === 0 ? 0 : Math.min(currentPage * perPage, total);

    return {
      current_page: currentPage,
      per_page: perPage,
      last_page: lastPage,
      total,
      from,
      to
    };
  });

  const setPage = (page) => {
    if (page >= 1 && page <= paginationInfo.value.last_page) {
      pagination.current_page = page;
    }
  };

  const setPerPage = (perPage) => {
    pagination.per_page = Number(perPage);
    pagination.current_page = 1;
  };

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      sort.column = column;
      sort.direction = 'asc';
    }
    pagination.current_page = 1;
  };

  const applyFilters = () => {
    loading.value = true;
    Object.assign(appliedFilters, {
      invoiceNumber: filters.invoiceNumber,
      supplierId: filters.supplierId,
      status: filters.status,
      invoiceDateStart: filters.invoiceDateStart,
      invoiceDateEnd: filters.invoiceDateEnd,
      dueDateStart: filters.dueDateStart,
      dueDateEnd: filters.dueDateEnd
    });
    pagination.current_page = 1;
    setTimeout(() => {
      loading.value = false;
    }, 200);
  };

  const resetFilters = () => {
    Object.assign(filters, defaultFilters());
    Object.assign(appliedFilters, defaultFilters());
    pagination.current_page = 1;
  };

  // Add invoice to local state
  const addInvoice = (payload) => {
    submitting.value = true;
    return new Promise((resolve) => {
      setTimeout(() => {
        const nextId = invoices.value.length > 0 ? Math.max(...invoices.value.map((i) => i.id)) + 1 : 1;
        const supplier = suppliers.value.find((s) => s.id === Number(payload.supplierId));

        const newInvoice = {
          id: nextId,
          invoiceNumber: payload.invoiceNumber,
          referenceNumber: payload.referenceNumber || '',
          invoiceDate: payload.invoiceDate,
          dueDate: payload.dueDate,
          supplierId: Number(payload.supplierId),
          supplierName: supplier ? supplier.name : payload.supplierName || 'Supplier',
          supplierCity: supplier ? supplier.city : '',
          supplierNpwp: supplier ? supplier.npwp : '',
          status: payload.status || 'Belum Lunas',
          notes: payload.notes || '',
          paidAt: payload.status === 'Lunas' ? new Date().toISOString().split('T')[0] : null,
          items: payload.items.map((it, idx) => ({
            id: idx + 1,
            medicineId: it.medicineId,
            medicineName: it.medicineName,
            quantity: Number(it.quantity) || 1,
            unit: it.unit || 'Box',
            price: Number(it.price) || 0,
            discount: Number(it.discount) || 0,
            subtotal: Number(it.subtotal) || 0
          })),
          totalAmount: payload.items.reduce((acc, it) => acc + (Number(it.subtotal) || 0), 0)
        };

        invoices.value.unshift(newInvoice);
        submitting.value = false;
        resolve({ success: true, data: newInvoice });
      }, 300);
    });
  };

  // Update existing invoice in local state
  const updateInvoice = (id, payload) => {
    submitting.value = true;
    return new Promise((resolve) => {
      setTimeout(() => {
        const index = invoices.value.findIndex((i) => i.id === id);
        if (index === -1) {
          submitting.value = false;
          resolve({ success: false, error: 'Faktur tidak ditemukan.' });
          return;
        }

        const supplier = suppliers.value.find((s) => s.id === Number(payload.supplierId));
        const prev = invoices.value[index];

        const updated = {
          ...prev,
          invoiceNumber: payload.invoiceNumber,
          referenceNumber: payload.referenceNumber || '',
          invoiceDate: payload.invoiceDate,
          dueDate: payload.dueDate,
          supplierId: Number(payload.supplierId),
          supplierName: supplier ? supplier.name : prev.supplierName,
          supplierCity: supplier ? supplier.city : prev.supplierCity,
          supplierNpwp: supplier ? supplier.npwp : prev.supplierNpwp,
          status: payload.status,
          notes: payload.notes || '',
          paidAt: payload.status === 'Lunas' ? (prev.paidAt || new Date().toISOString().split('T')[0]) : null,
          items: payload.items.map((it, idx) => ({
            id: it.id || idx + 1,
            medicineId: it.medicineId,
            medicineName: it.medicineName,
            quantity: Number(it.quantity) || 1,
            unit: it.unit || 'Box',
            price: Number(it.price) || 0,
            discount: Number(it.discount) || 0,
            subtotal: Number(it.subtotal) || 0
          })),
          totalAmount: payload.items.reduce((acc, it) => acc + (Number(it.subtotal) || 0), 0)
        };

        invoices.value[index] = updated;
        submitting.value = false;
        resolve({ success: true, data: updated });
      }, 300);
    });
  };

  // Delete invoice from local state
  const deleteInvoice = (id) => {
    submitting.value = true;
    return new Promise((resolve) => {
      setTimeout(() => {
        invoices.value = invoices.value.filter((i) => i.id !== id);
        submitting.value = false;
        resolve({ success: true });
      }, 200);
    });
  };

  // Format currency helper
  const formatRupiah = (value) => {
    if (value === undefined || value === null) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(value);
  };

  // Format date helper (e.g. 12 Sep 2026)
  const formatDateIndo = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${day} ${months[d.getMonth()]} ${d.getFullYear()}`;
  };

  // Export to CSV on frontend
  const exportInvoicesCsv = () => {
    const list = sortedInvoices.value;
    const headers = ['No', 'Nomor Faktur', 'Nomor PO', 'Tanggal Faktur', 'Supplier', 'NPWP', 'Total (IDR)', 'Status', 'Jatuh Tempo'];
    const rows = list.map((inv, idx) => [
      idx + 1,
      inv.invoiceNumber,
      inv.referenceNumber || '-',
      inv.invoiceDate,
      `"${inv.supplierName}"`,
      inv.supplierNpwp || '-',
      inv.totalAmount,
      inv.status,
      inv.dueDate
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((r) => r.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `Data_Faktur_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    return true;
  };

  return {
    invoices,
    suppliers,
    medicines,
    filters,
    appliedFilters,
    sort,
    pagination,
    paginationInfo,
    loading,
    submitting,
    error,
    summary,
    filteredInvoices,
    sortedInvoices,
    paginatedInvoices,
    isOverdue,
    getDueStatusText,
    setPage,
    setPerPage,
    setSort,
    applyFilters,
    resetFilters,
    addInvoice,
    updateInvoice,
    deleteInvoice,
    formatRupiah,
    formatDateIndo,
    exportInvoicesCsv
  };
}
