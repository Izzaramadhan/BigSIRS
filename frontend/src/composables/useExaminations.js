import { ref, computed, reactive } from 'vue';
import {
  generateMockExaminations,
  mockPolyclinics,
  mockDoctors,
  mockGuarantorOptions,
  mockPeriodOptions,
  mockStatusOptions,
  calculateAge
} from '@/mocks/examinations';

export function useExaminations() {
  // All examination records in local state
  const examinations = ref(generateMockExaminations());

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

    return result;
  });

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
    filters.customStartDate = '';
    filters.customEndDate = '';
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

  return {
    examinations,
    searchQuery,
    filters,
    pagination,
    loading,
    error,
    lastCalledPatient,
    statistics,
    filteredExaminations,
    paginatedExaminations,
    paginationInfo,
    mockPolyclinics,
    mockDoctors,
    mockGuarantorOptions,
    mockPeriodOptions,
    mockStatusOptions,
    calculateAge,
    applyFilters,
    resetFilters,
    setPage,
    setPerPage,
    refreshData,
    startExamination,
    enterClinic,
    callNextQueue,
    replayQueueAudio
  };
}
