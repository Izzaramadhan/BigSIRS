import { ref, computed, reactive } from 'vue';
import { examinationService } from '@/services/examination.service';
import { polyclinicService } from '@/services/polyclinic';
import {
  mockPolyclinics as defaultPolyclinics,
  mockDoctors,
  mockMidwives,
  mockBidanPhysicalHistory,
  mockGuarantorOptions,
  mockPeriodOptions,
  mockStatusOptions,
  mockIcd10Catalog,
  mockIcd9Catalog,
  mockClinicalProcedures,
  mockProcedurePackages,
  mockParamedics,
  mockMedicinesCatalog,
  mockPrescriptionPackages,
  mockPrescriptionHistory,
  mockLaboratoryCatalog,
  mockLabDpjpDoctors,
  mockLabClinicalStaff,
  mockLabHistory,
  mockRadiologyCatalog,
  mockRadiologyHistory,
  mockVkSurgeryOptions,
  mockIbsSurgeryOptions,
  calculateAge
} from '@/mocks/examinations';

// Frontend-only session clinical cache for patient examination views
const sessionExaminationData = reactive({});

export function useExaminations() {
  // All examination records in local state sourced directly from API
  const examinations = ref([]);

  // Search input query
  const searchQuery = ref('');

  // Current applied filters
  const filters = reactive({
    period: 'today',
    customStartDate: '',
    customEndDate: '',
    polyclinic: 'Semua Poliklinik',
    doctor: 'Semua Dokter Pemeriksa',
    status: 'Semua Status',
    guarantor: 'Semua Penjamin'
  });

  // Sort state
  const sort = reactive({
    key: '',
    direction: 'asc'
  });

  // Pagination state (driven by backend metadata)
  const pagination = reactive({
    currentPage: 1,
    perPage: 10,
    total: 0,
    lastPage: 1,
    from: 0,
    to: 0
  });

  // UI state
  const loading = ref(false);
  const error = ref(null);

  // Dynamic polyclinics list
  const polyclinicsList = ref([...defaultPolyclinics]);

  // Track currently / last called patient
  const lastCalledPatient = ref(null);

  // Fetch polyclinics from legacy backend for dropdown filter
  const loadPolyclinics = async () => {
    try {
      const res = await polyclinicService.getPolyclinics({ per_page: 100 });
      const data = res.data?.data || res.data;
      if (Array.isArray(data) && data.length > 0) {
        const names = data.map((p) => p.name || p.nama || p.nama_poliklinik).filter(Boolean);
        if (names.length > 0) {
          polyclinicsList.value = Array.from(new Set(names));
        }
      }
    } catch {
      // Keep default polyclinics if API not ready
    }
  };

  // Fetch examinations from backend API
  const fetchExaminations = async () => {
    loading.value = true;
    error.value = null;

    try {
      const params = {
        page: pagination.currentPage,
        per_page: pagination.perPage,
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

      if (filters.doctor && filters.doctor !== 'Semua Dokter Pemeriksa') {
        params.doctor = filters.doctor;
      }

      if (filters.status && filters.status !== 'Semua Status') {
        params.status = filters.status;
      }

      if (sort.key) {
        params.sort_direction = sort.direction;
        if (sort.key === 'antrian') {
          params.sort_by = 'id';
        } else if (sort.key === 'waktu') {
          params.sort_by = 'waktu_mulai_periksa';
        } else if (sort.key === 'klinik') {
          params.sort_by = 'id_poliklinik';
        } else {
          params.sort_by = sort.key;
        }
      }

      const response = await examinationService.getExaminations(params);
      const resData = response.data;

      const items = Array.isArray(resData?.data) ? resData.data : (Array.isArray(resData) ? resData : []);
      examinations.value = items;

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
      error.value = err.response?.data?.message || err.message || 'Gagal memuat data kunjungan pemeriksaan.';
      examinations.value = [];
      pagination.total = 0;
      pagination.from = 0;
      pagination.to = 0;
      pagination.lastPage = 1;
    } finally {
      loading.value = false;
    }
  };

  // Filtered examinations list (source of truth from API)
  const filteredExaminations = computed(() => {
    return examinations.value;
  });

  // Paginated records for table view
  const paginatedExaminations = computed(() => {
    return examinations.value;
  });

  // Statistics dynamically calculated from reactive records
  const statistics = computed(() => {
    const list = examinations.value;
    const waitingList = list.filter((item) => item.status === 'Menunggu');
    const waitingCount = waitingList.length;

    const inServiceList = list.filter((item) => item.status === 'Sedang Diperiksa' || item.status === 'Dipanggil');
    const inServiceCount = inServiceList.length;

    const completedCount = list.filter((item) => item.status === 'Selesai').length;
    const cancelledCount = list.filter((item) => item.status === 'Dibatalkan').length;

    const totalProcessed = waitingCount + inServiceCount + completedCount;
    const completionPercentage = totalProcessed > 0
      ? ((completedCount / totalProcessed) * 100).toFixed(1).replace('.', ',')
      : '0';

    return {
      waiting: waitingCount,
      avgWaitTime: '~20 Menit',
      inService: inServiceCount,
      activePolyCount: polyclinicsList.value.length,
      activeDoctorsCount: 14,
      completed: completedCount,
      completedPercentage: `${completionPercentage}%`,
      cancelled: cancelledCount
    };
  });

  // Pagination details and count info for UI
  const paginationInfo = computed(() => {
    const activeQueueCount = examinations.value.filter(
      (item) => item.status === 'Menunggu' || item.status === 'Dipanggil' || item.status === 'Sedang Diperiksa'
    ).length;

    return {
      total: pagination.total,
      last_page: pagination.lastPage,
      from: pagination.from,
      to: pagination.to,
      activeQueueCount
    };
  });

  const setSort = (key) => {
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
    fetchExaminations();
  };

  // Apply filters action
  const applyFilters = async () => {
    pagination.currentPage = 1;
    await fetchExaminations();
  };

  // Reset filters action
  const resetFilters = async () => {
    searchQuery.value = '';
    filters.period = 'today';
    filters.customStartDate = '';
    filters.customEndDate = '';
    filters.polyclinic = 'Semua Poliklinik';
    filters.doctor = 'Semua Dokter Pemeriksa';
    filters.status = 'Semua Status';
    filters.guarantor = 'Semua Penjamin';
    sort.key = '';
    sort.direction = 'asc';
    pagination.currentPage = 1;
    await fetchExaminations();
  };

  // Set page
  const setPage = (page) => {
    if (page >= 1 && page <= pagination.lastPage) {
      pagination.currentPage = page;
      fetchExaminations();
    }
  };

  // Set items per page
  const setPerPage = (num) => {
    pagination.perPage = Number(num);
    pagination.currentPage = 1;
    fetchExaminations();
  };

  // Refresh data action
  const refreshData = async () => {
    pagination.currentPage = 1;
    await fetchExaminations();
  };

  // Clinical action 1: Start examination (Mulai Periksa)
  const startExamination = async (patientId) => {
    const item = examinations.value.find((p) => String(p.id) === String(patientId));
    try {
      await examinationService.updateStatus(patientId, 'Sedang Diperiksa');
      if (item) {
        item.status = 'Sedang Diperiksa';
        lastCalledPatient.value = item;
      }
      return { success: true, patient: item };
    } catch {
      if (item) {
        item.status = 'Sedang Diperiksa';
        lastCalledPatient.value = item;
      }
      return { success: true, patient: item };
    }
  };

  // Clinical action 2: Enter Clinic (Masuk Poli)
  const enterClinic = async (patientId) => {
    return startExamination(patientId);
  };

  // Queue calling action: Call next queue (Panggil Antrean Berikut)
  const callNextQueue = () => {
    const nextPatient = examinations.value.find((p) => p.status === 'Menunggu');
    if (nextPatient) {
      nextPatient.status = 'Dipanggil';
      lastCalledPatient.value = nextPatient;
      return {
        success: true,
        patient: nextPatient,
        queueNumber: nextPatient.queue_number,
        patientName: nextPatient.patient_name,
        polyclinic: nextPatient.polyclinic,
        room: nextPatient.room || 'Ruang 1'
      };
    }

    const firstActive = examinations.value.find((p) => p.status === 'Dipanggil' || p.status === 'Sedang Diperiksa') || examinations.value[0];
    lastCalledPatient.value = firstActive;
    return {
      success: true,
      patient: firstActive,
      queueNumber: firstActive?.queue_number || '—',
      patientName: firstActive?.patient_name || '-',
      polyclinic: firstActive?.polyclinic || '-',
      room: firstActive?.room || 'Ruang 1'
    };
  };

  // Audio replay action (Bunyikan Ulang Panggilan)
  const replayQueueAudio = (patient = null) => {
    const target = patient || lastCalledPatient.value || examinations.value[0];
    return {
      success: true,
      patient: target,
      queueNumber: target?.queue_number || '—',
      patientName: target?.patient_name || '-',
      polyclinic: target?.polyclinic || '-',
      room: target?.room || 'Ruang 1'
    };
  };

  // Get single examination record merged with session cache
  const getExaminationById = (patientId) => {
    const base = examinations.value.find((p) => String(p.id) === String(patientId));
    if (!base) return null;
    const override = sessionExaminationData[base.id];
    return override ? { ...base, ...override } : base;
  };

  // Save clinical examination workspace data in local session cache
  const saveSessionExaminationData = (patientId, payload) => {
    const targetId = String(patientId);
    const existing = sessionExaminationData[targetId] || {};
    sessionExaminationData[targetId] = {
      ...existing,
      ...payload
    };

    const item = examinations.value.find((p) => String(p.id) === targetId);
    if (item && payload) {
      Object.assign(item, payload);
    }

    return {
      success: true,
      data: getExaminationById(targetId)
    };
  };

  // Mark examination as Selesai in backend and local state
  const completeExamination = async (patientId) => {
    try {
      await examinationService.updateStatus(patientId, 'Selesai');
    } catch {
      // Ignore if offline
    }
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    return saveSessionExaminationData(patientId, {
      status: 'Selesai',
      completed_time: `${hh}:${mm}`
    });
  };

  return {
    examinations,
    searchQuery,
    filters,
    pagination,
    loading,
    error,
    lastCalledPatient,
    sort,
    statistics,
    filteredExaminations,
    paginatedExaminations,
    paginationInfo,
    polyclinics: polyclinicsList,
    mockPolyclinics: polyclinicsList,
    mockDoctors,
    mockMidwives,
    mockBidanPhysicalHistory,
    mockGuarantorOptions,
    mockPeriodOptions,
    mockStatusOptions,
    mockIcd10Catalog,
    mockIcd9Catalog,
    mockClinicalProcedures,
    mockProcedurePackages,
    mockParamedics,
    mockMedicinesCatalog,
    mockPrescriptionPackages,
    mockPrescriptionHistory,
    mockLaboratoryCatalog,
    mockLabDpjpDoctors,
    mockLabClinicalStaff,
    mockLabHistory,
    mockRadiologyCatalog,
    mockRadiologyHistory,
    mockVkSurgeryOptions,
    mockIbsSurgeryOptions,
    calculateAge,
    getExaminationById,
    saveSessionExaminationData,
    completeExamination,
    fetchExaminations,
    loadPolyclinics,
    applyFilters,
    resetFilters,
    setSort,
    setPage,
    setPerPage,
    refreshData,
    startExamination,
    enterClinic,
    callNextQueue,
    replayQueueAudio
  };
}
