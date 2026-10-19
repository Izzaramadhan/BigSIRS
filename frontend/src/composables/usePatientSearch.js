import { ref, reactive, computed } from 'vue';
import { mockPatients } from '@/mocks/patients';

export function usePatientSearch() {
  // Source data (simulated local mock dataset)
  const allPatients = ref([...mockPatients]);
  const filteredPatients = ref([...mockPatients]);

  // Search keyword and validation
  const searchQuery = ref('');
  const validationError = ref('');

  // Advanced Filters
  const filters = reactive({
    gender: 'all', // 'all' | 'L' | 'P'
    status: 'all', // 'all' | 'active' | 'inactive'
    birthDate: '',
    medicalRecordNumber: ''
  });

  const isAdvancedFilterOpen = ref(false);

  // Pagination state
  const pagination = reactive({
    currentPage: 1,
    perPage: 10,
    total: mockPatients.length
  });

  // Loading state (brief local UI feedback)
  const loading = ref(false);

  // Selected patient state
  const selectedPatient = ref(null);

  // Computed: Has active filter applied
  const hasActiveFilters = computed(() => {
    return Boolean(
      searchQuery.value.trim() ||
      filters.gender !== 'all' ||
      filters.status !== 'all' ||
      filters.birthDate ||
      filters.medicalRecordNumber.trim()
    );
  });

  // Computed: Pagination metadata
  const paginationInfo = computed(() => {
    const total = filteredPatients.value.length;
    const perPage = pagination.perPage;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const currentPage = Math.min(pagination.currentPage, lastPage);
    const from = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
    const to = total === 0 ? 0 : Math.min(currentPage * perPage, total);

    return {
      current_page: currentPage,
      last_page: lastPage,
      per_page: perPage,
      total,
      from,
      to
    };
  });

  // Computed: Paginated list
  const paginatedPatients = computed(() => {
    const start = (pagination.currentPage - 1) * pagination.perPage;
    return filteredPatients.value.slice(start, start + pagination.perPage);
  });

  /**
   * Validate search input
   * Minimal 3 karakter untuk nama jika berupa teks
   */
  const validateQuery = () => {
    validationError.value = '';
    const query = searchQuery.value.trim();

    // If advanced filters are used, general search query can be empty
    const hasOtherFilters = 
      filters.gender !== 'all' || 
      filters.status !== 'all' || 
      Boolean(filters.birthDate) || 
      Boolean(filters.medicalRecordNumber.trim());

    if (!query && !hasOtherFilters) {
      validationError.value = 'Silakan masukkan kata kunci pencarian (Nama Pasien min. 3 karakter, No. RM, atau NIK).';
      return false;
    }

    if (query) {
      const isDigitsOnly = /^\d+$/.test(query);
      const isRmFormat = /^RM-?\d+/i.test(query);

      // If it's a name search (not purely digits and not RM format), require at least 3 characters
      if (!isDigitsOnly && !isRmFormat && query.length < 3) {
        validationError.value = 'Pencarian berdasarkan nama pasien minimal 3 karakter.';
        return false;
      }
    }

    return true;
  };

  /**
   * Execute local search on dummy data
   */
  const search = (force = false) => {
    if (!force && !validateQuery()) {
      return { success: false, reason: 'validation_error', message: validationError.value };
    }

    validationError.value = '';
    loading.value = true;

    const query = searchQuery.value.trim().toLowerCase();

    // Perform local filtering
    const results = allPatients.value.filter((patient) => {
      // 1. Text Search across Nama, No. RM, NIK
      if (query) {
        const matchName = patient.name.toLowerCase().includes(query);
        const matchRm = patient.medical_record_number.toLowerCase().includes(query);
        const matchNik = patient.nik.toLowerCase().includes(query);

        if (!matchName && !matchRm && !matchNik) {
          return false;
        }
      }

      // 2. Filter Jenis Kelamin
      if (filters.gender !== 'all') {
        if (patient.gender !== filters.gender) {
          return false;
        }
      }

      // 3. Filter Status Pasien
      if (filters.status !== 'all') {
        const isActiveExpected = filters.status === 'active';
        if (patient.is_active !== isActiveExpected) {
          return false;
        }
      }

      // 4. Filter Tanggal Lahir
      if (filters.birthDate) {
        if (patient.birth_date !== filters.birthDate) {
          return false;
        }
      }

      // 5. Filter Spesifik No. Rekam Medis
      if (filters.medicalRecordNumber.trim()) {
        const rmFilter = filters.medicalRecordNumber.trim().toLowerCase();
        if (!patient.medical_record_number.toLowerCase().includes(rmFilter)) {
          return false;
        }
      }

      return true;
    });

    filteredPatients.value = results;
    pagination.currentPage = 1;
    pagination.total = results.length;

    // Simulate brief visual loading for modern UI feel (200ms)
    setTimeout(() => {
      loading.value = false;
    }, 200);

    return {
      success: true,
      count: results.length
    };
  };

  /**
   * Reset all filters and search input back to initial state
   */
  const resetFilters = () => {
    searchQuery.value = '';
    validationError.value = '';
    filters.gender = 'all';
    filters.status = 'all';
    filters.birthDate = '';
    filters.medicalRecordNumber = '';

    filteredPatients.value = [...allPatients.value];
    pagination.currentPage = 1;
    pagination.total = allPatients.value.length;
  };

  /**
   * Set pagination page
   */
  const setPage = (page) => {
    const lastPage = paginationInfo.value.last_page;
    if (page >= 1 && page <= lastPage) {
      pagination.currentPage = page;
      window.scrollTo({ top: 300, behavior: 'smooth' });
    }
  };

  /**
   * Set per-page limit (10, 25, 50)
   */
  const setPerPage = (perPage) => {
    pagination.perPage = Number(perPage);
    pagination.currentPage = 1;
  };

  /**
   * Select a patient locally (simulation for admission)
   */
  const selectPatient = (patient) => {
    selectedPatient.value = patient;
    return {
      success: true,
      patient
    };
  };

  /**
   * Toggle advanced filter visibility
   */
  const toggleAdvancedFilter = () => {
    isAdvancedFilterOpen.value = !isAdvancedFilterOpen.value;
  };

  return {
    allPatients,
    filteredPatients,
    paginatedPatients,
    paginationInfo,
    pagination,
    searchQuery,
    validationError,
    filters,
    isAdvancedFilterOpen,
    loading,
    selectedPatient,
    hasActiveFilters,
    search,
    resetFilters,
    setPage,
    setPerPage,
    selectPatient,
    toggleAdvancedFilter
  };
}
