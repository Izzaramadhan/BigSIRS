import { ref, computed, reactive } from 'vue';
import {
  generateMockExaminations,
  mockPolyclinics,
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

// Frontend-only session clinical state for patient examination views
const sessionExaminationData = reactive({});

export function useExaminations() {
  // All examination records in local state
  const examinations = ref(generateMockExaminations());

  // Search input query
  const searchQuery = ref('');

  const todayStr = new Date().toISOString().split('T')[0];

  // Current applied filters
  const filters = reactive({
    period: 'today',
    customStartDate: todayStr,
    customEndDate: todayStr,
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

  // Pagination state
  const pagination = reactive({
    currentPage: 1,
    perPage: 10
  });

  // UI state
  const loading = ref(false);
  const error = ref(null);

  // Track currently / last called patient
  const lastCalledPatient = ref(null);

  // Statistics dynamically calculated from reactive records
  const statistics = computed(() => {
    const list = examinations.value;
    const waitingList = list.filter((item) => item.status === 'Menunggu');
    const waitingCount = waitingList.length;

    // Average wait time for waiting patients
    const totalWait = waitingList.reduce((acc, curr) => acc + (curr.wait_time_minutes || 0), 0);
    const avgWaitTime = waitingCount > 0 ? Math.round(totalWait / waitingCount) : 24;

    const inServiceList = list.filter((item) => item.status === 'Sedang Diperiksa' || item.status === 'Dipanggil');
    const inServiceCount = inServiceList.length;

    // Count distinct active polyclinics
    const activePolys = new Set(inServiceList.map((item) => item.polyclinic));
    const activePolyCount = activePolys.size > 0 ? activePolys.size : 8;

    const completedCount = list.filter((item) => item.status === 'Selesai').length;
    const cancelledCount = list.filter((item) => item.status === 'Dibatalkan').length;

    // Completion percentage of total processed today
    const totalProcessed = waitingCount + inServiceCount + completedCount;
    const completionPercentage = totalProcessed > 0
      ? ((completedCount / totalProcessed) * 100).toFixed(1).replace('.', ',')
      : '77,8';

    return {
      waiting: waitingCount,
      avgWaitTime: `~${avgWaitTime} Menit`,
      inService: inServiceCount,
      activePolyCount,
      activeDoctorsCount: 14,
      completed: completedCount,
      completedPercentage: `${completionPercentage}%`,
      cancelled: cancelledCount
    };
  });

  // Filtered examinations list
  const filteredExaminations = computed(() => {
    let result = [...examinations.value];

    // 1. Text Search (NIK, No. RM, No. Antrean, Patient Name)
    if (searchQuery.value && searchQuery.value.trim() !== '') {
      const q = searchQuery.value.toLowerCase().trim();
      result = result.filter((item) => {
        const matchName = item.patient_name.toLowerCase().includes(q);
        const matchRM = item.medical_record_number.toLowerCase().includes(q);
        const matchQueue = item.queue_number.toLowerCase().includes(q);
        const matchNik = item.nik.toLowerCase().includes(q);
        return matchName || matchRM || matchQueue || matchNik;
      });
    }

    // 1.5. Periode Tanggal Admisi (Start - End)
    if (filters.customStartDate) {
      result = result.filter((item) => !item.admission_date || item.admission_date >= filters.customStartDate);
    }
    if (filters.customEndDate) {
      result = result.filter((item) => !item.admission_date || item.admission_date <= filters.customEndDate);
    }

    // 2. Polyclinic Filter
    if (filters.polyclinic && filters.polyclinic !== 'Semua Poliklinik') {
      result = result.filter((item) => item.polyclinic === filters.polyclinic);
    }

    // 3. Doctor Filter
    if (filters.doctor && filters.doctor !== 'Semua Dokter Pemeriksa') {
      result = result.filter((item) => item.doctor === filters.doctor);
    }

    // 4. Status Filter
    if (filters.status && filters.status !== 'Semua Status') {
      result = result.filter((item) => item.status === filters.status);
    }

    // 5. Guarantor Filter
    if (filters.guarantor && filters.guarantor !== 'Semua Penjamin') {
      result = result.filter((item) => {
        if (filters.guarantor === 'BPJS Kesehatan') {
          return item.guarantor_type.includes('BPJS');
        } else if (filters.guarantor === 'Pasien Umum / Tunai') {
          return item.guarantor_type.includes('Umum') || item.guarantor_type.includes('Tunai');
        } else if (filters.guarantor === 'Asuransi') {
          return item.guarantor_type.includes('Asuransi');
        }
        return item.guarantor_type === filters.guarantor;
      });
    }

    if (sort.key) {
      const dir = sort.direction === 'asc' ? 1 : -1;
      result.sort((a, b) => {
        let valA = '';
        let valB = '';
        if (sort.key === 'antrian') {
          valA = (a.queue_number || '').toLowerCase();
          valB = (b.queue_number || '').toLowerCase();
        } else if (sort.key === 'waktu') {
          valA = `${a.admission_date || ''} ${a.admission_time || ''}`;
          valB = `${b.admission_date || ''} ${b.admission_time || ''}`;
        } else if (sort.key === 'nama') {
          valA = (a.patient_name || '').toLowerCase();
          valB = (b.patient_name || '').toLowerCase();
        } else if (sort.key === 'klinik') {
          valA = (a.polyclinic || '').toLowerCase();
          valB = (b.polyclinic || '').toLowerCase();
        } else if (sort.key === 'dokter') {
          valA = (a.doctor || '').toLowerCase();
          valB = (b.doctor || '').toLowerCase();
        }
        if (valA < valB) return -1 * dir;
        if (valA > valB) return 1 * dir;
        return 0;
      });
    }

    return result;
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
  };

  // Paginated records
  const paginatedExaminations = computed(() => {
    const start = (pagination.currentPage - 1) * pagination.perPage;
    return filteredExaminations.value.slice(start, start + pagination.perPage);
  });

  // Pagination details and count info
  const paginationInfo = computed(() => {
    const total = filteredExaminations.value.length;
    const lastPage = Math.ceil(total / pagination.perPage) || 1;
    const from = total === 0 ? 0 : (pagination.currentPage - 1) * pagination.perPage + 1;
    const to = Math.min(pagination.currentPage * pagination.perPage, total);

    // Active queue count (Menunggu + Dipanggil + Sedang Diperiksa)
    const activeQueueCount = examinations.value.filter(
      (item) => item.status === 'Menunggu' || item.status === 'Dipanggil' || item.status === 'Sedang Diperiksa'
    ).length;

    return {
      total,
      last_page: lastPage,
      from,
      to,
      activeQueueCount
    };
  });

  // Apply filters action
  const applyFilters = () => {
    pagination.currentPage = 1;
    loading.value = true;
    setTimeout(() => {
      loading.value = false;
    }, 200);
  };

  // Reset filters action
  const resetFilters = () => {
    searchQuery.value = '';
    filters.period = 'today';
    filters.customStartDate = todayStr;
    filters.customEndDate = todayStr;
    filters.polyclinic = 'Semua Poliklinik';
    filters.doctor = 'Semua Dokter Pemeriksa';
    filters.status = 'Semua Status';
    filters.guarantor = 'Semua Penjamin';
    pagination.currentPage = 1;
    loading.value = true;
    setTimeout(() => {
      loading.value = false;
    }, 200);
  };

  // Set page
  const setPage = (page) => {
    if (page >= 1 && page <= paginationInfo.value.last_page) {
      pagination.currentPage = page;
    }
  };

  // Set items per page
  const setPerPage = (num) => {
    pagination.perPage = Number(num);
    pagination.currentPage = 1;
  };

  // Refresh data simulation (local reload)
  const refreshData = () => {
    loading.value = true;
    error.value = null;
    setTimeout(() => {
      examinations.value = generateMockExaminations();
      pagination.currentPage = 1;
      loading.value = false;
    }, 350);
  };

  // Clinical action 1: Start examination (Mulai Periksa)
  const startExamination = (patientId) => {
    const item = examinations.value.find((p) => p.id === patientId);
    if (item) {
      item.status = 'Sedang Diperiksa';
      lastCalledPatient.value = item;
      return { success: true, patient: item };
    }
    return { success: false };
  };

  // Clinical action 2: Enter Clinic (Masuk Poli)
  const enterClinic = (patientId) => {
    const item = examinations.value.find((p) => p.id === patientId);
    if (item) {
      item.status = 'Sedang Diperiksa';
      lastCalledPatient.value = item;
      return { success: true, patient: item };
    }
    return { success: false };
  };

  // Queue calling action: Call next queue (Panggil Antrean Berikut)
  const callNextQueue = () => {
    // Find next waiting patient
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
        room: nextPatient.room
      };
    }
    // Fallback if none waiting
    const firstActive = examinations.value.find((p) => p.status === 'Dipanggil' || p.status === 'Sedang Diperiksa') || examinations.value[0];
    lastCalledPatient.value = firstActive;
    return {
      success: true,
      patient: firstActive,
      queueNumber: firstActive.queue_number,
      patientName: firstActive.patient_name,
      polyclinic: firstActive.polyclinic,
      room: firstActive.room
    };
  };

  // Audio replay action (Bunyikan Ulang Panggilan)
  const replayQueueAudio = (patient = null) => {
    const target = patient || lastCalledPatient.value || examinations.value[0];
    return {
      success: true,
      patient: target,
      queueNumber: target.queue_number,
      patientName: target.patient_name,
      polyclinic: target.polyclinic,
      room: target.room
    };
  };

  // Get single examination record merged with frontend session state
  const getExaminationById = (patientId) => {
    const base = examinations.value.find((p) => String(p.id) === String(patientId)) || examinations.value[0];
    if (!base) return null;
    const override = sessionExaminationData[base.id];
    return override ? { ...base, ...override } : base;
  };

  // Save clinical examination workspace data in local session state (FE-only)
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

  // Mark examination as Selesai in local session state
  const completeExamination = (patientId) => {
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
    mockPolyclinics,
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
