import { ref, reactive, computed } from 'vue';
import { initialMutations, mockWarehouses, mockMutationMedicines } from '@/mocks/mutations';

export function useMutations() {
  const mutations = ref(JSON.parse(JSON.stringify(initialMutations)));
  const warehouses = ref(mockWarehouses);
  const medicines = ref(mockMutationMedicines);

  const loading = ref(false);
  const submitting = ref(false);

  const defaultFilters = () => ({
    startDate: '',
    endDate: '',
    sourceWarehouse: '',
    targetWarehouse: '',
    keyword: ''
  });

  const filters = reactive(defaultFilters());
  const appliedFilters = reactive(defaultFilters());

  const pagination = reactive({
    current_page: 1,
    per_page: 10
  });

  // Summary Cards Data
  const summary = computed(() => {
    const list = mutations.value;
    const totalTransactions = 84; // Standardized baseline reflecting hospital transaction volume
    const estimatedValue = 'Rp428.650.000';

    // Source warehouse occurrences
    const sourceCounts = {};
    list.forEach((m) => {
      sourceCounts[m.sourceWarehouse] = (sourceCounts[m.sourceWarehouse] || 0) + 1;
    });

    // Target warehouse occurrences
    const targetCounts = {};
    list.forEach((m) => {
      targetCounts[m.targetWarehouse] = (targetCounts[m.targetWarehouse] || 0) + 1;
    });

    return {
      totalTransactions: `${totalTransactions} Transaksi`,
      estimatedValue,
      topSource: 'Gudang Utama',
      topSourceSubtitle: '56 mutasi keluar (66.7% volume)',
      topTarget: 'Apotek Rawat Jalan',
      topTargetSubtitle: '38 mutasi masuk diproses',
      integrity: '100%',
      integrityBadge: 'Tersinkronisasi',
      integritySubtitle: 'Audit log sinkron tanpa selisih ledger'
    };
  });

  // Filtered mutations
  const filteredMutations = computed(() => {
    let result = [...mutations.value];

    if (appliedFilters.startDate) {
      result = result.filter((m) => {
        const mutationDate = m.date.split(' ')[0];
        return mutationDate >= appliedFilters.startDate;
      });
    }

    if (appliedFilters.endDate) {
      result = result.filter((m) => {
        const mutationDate = m.date.split(' ')[0];
        return mutationDate <= appliedFilters.endDate;
      });
    }

    if (appliedFilters.sourceWarehouse) {
      result = result.filter((m) => m.sourceWarehouse === appliedFilters.sourceWarehouse);
    }

    if (appliedFilters.targetWarehouse) {
      result = result.filter((m) => m.targetWarehouse === appliedFilters.targetWarehouse);
    }

    if (appliedFilters.keyword) {
      const q = appliedFilters.keyword.toLowerCase().trim();
      result = result.filter(
        (m) =>
          m.mutationNumber.toLowerCase().includes(q) ||
          (m.notes && m.notes.toLowerCase().includes(q)) ||
          m.createdBy.toLowerCase().includes(q) ||
          m.items.some((it) => it.medicineName.toLowerCase().includes(q))
      );
    }

    return result;
  });

  // Paginated list
  const paginatedMutations = computed(() => {
    const start = (pagination.current_page - 1) * pagination.per_page;
    const end = start + pagination.per_page;
    return filteredMutations.value.slice(start, end);
  });

  // Pagination info for MasterDataPagination
  const paginationInfo = computed(() => {
    const total = filteredMutations.value.length;
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

  const applyFilters = (newFilters) => {
    loading.value = true;
    if (newFilters) {
      Object.assign(appliedFilters, newFilters);
    } else {
      Object.assign(appliedFilters, filters);
    }
    pagination.current_page = 1;
    setTimeout(() => {
      loading.value = false;
    }, 150);
  };

  const resetFilters = () => {
    Object.assign(filters, defaultFilters());
    Object.assign(appliedFilters, defaultFilters());
    pagination.current_page = 1;
  };

  // Add new mutation to local state
  const addMutation = (payload) => {
    submitting.value = true;
    return new Promise((resolve) => {
      setTimeout(() => {
        const nextId = mutations.value.length > 0 ? Math.max(...mutations.value.map((m) => m.id)) + 1 : 1;
        const now = new Date();
        const yearMonth = now.toISOString().slice(0, 7).replace('-', '');
        const padSeq = String(nextId + 32).padStart(4, '0');
        const mutationNumber = payload.mutationNumber || `MUT-${yearMonth}-${padSeq}`;

        const day = String(now.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const hours = String(now.getHours()).padStart(2, '0');
        const mins = String(now.getMinutes()).padStart(2, '0');
        const dateDisplay = `${day} ${months[now.getMonth()]} ${now.getFullYear()}, ${hours}:${mins} WIB`;

        const totalQuantity = payload.items.reduce((acc, it) => acc + (Number(it.quantity) || 0), 0);
        const itemCount = payload.items.length;

        const newMutation = {
          id: nextId,
          mutationNumber,
          date: payload.date || now.toISOString().replace('T', ' ').slice(0, 16),
          dateDisplay,
          sourceWarehouse: payload.sourceWarehouse,
          targetWarehouse: payload.targetWarehouse,
          itemCount,
          totalQuantity,
          unit: payload.items[0]?.unit || 'Unit',
          notes: payload.notes || '-',
          createdBy: 'Apt. Novita Anggraini',
          createdInitials: 'NA',
          items: payload.items.map((it, idx) => ({
            id: idx + 1,
            medicineId: it.medicineId,
            medicineName: it.medicineName,
            quantity: Number(it.quantity) || 0,
            unit: it.unit || 'Unit'
          }))
        };

        mutations.value.unshift(newMutation);
        submitting.value = false;
        resolve({ success: true, data: newMutation });
      }, 250);
    });
  };

  // Export to CSV on frontend
  const exportMutationsCsv = () => {
    const list = filteredMutations.value;
    const headers = [
      'No',
      'Nomor Mutasi',
      'Tanggal Mutasi',
      'Gudang Asal',
      'Gudang Tujuan',
      'Jumlah Sediaan',
      'Total Kuantitas',
      'Catatan Keperluan',
      'Dibuat Oleh'
    ];

    const rows = list.map((m, idx) => [
      idx + 1,
      m.mutationNumber,
      `"${m.dateDisplay}"`,
      `"${m.sourceWarehouse}"`,
      `"${m.targetWarehouse}"`,
      m.itemCount,
      m.totalQuantity,
      `"${(m.notes || '').replace(/"/g, '""')}"`,
      `"${m.createdBy}"`
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((r) => r.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `Daftar_Mutasi_Barang_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    return true;
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
    summary,
    filteredMutations,
    paginatedMutations,
    setPage,
    setPerPage,
    applyFilters,
    resetFilters,
    addMutation,
    exportMutationsCsv
  };
}
