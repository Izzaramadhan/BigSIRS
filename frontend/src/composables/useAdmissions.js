import { ref, reactive, computed } from 'vue';
import { 
  mockAdmissions, 
  mockPolyclinics, 
  mockDoctors, 
  mockGuarantors, 
  mockLoketInfo 
} from '@/mocks/admissions';

export function useAdmissions() {
  // Master admissions reactive state (local in-memory store)
  const admissionsList = ref([...mockAdmissions]);

  // Search keyword
  const searchQuery = ref('');

  // Filter criteria
  const filters = reactive({
    period: 'today', // 'today' | 'yesterday' | '7days' | '30days' | 'all' | 'custom'
    customStartDate: '',
    customEndDate: '',
    polyclinic: 'all',
    doctor: 'all',
    guarantor: 'all',
    status: 'all' // 'all' | 'Menunggu' | 'Sedang Diperiksa' | 'Selesai' | 'Dibatalkan'
  });

  // Pagination state
  const pagination = reactive({
    currentPage: 1,
    perPage: 10
  });

  // Loket and Queue management state
  const loket = reactive({ ...mockLoketInfo });
  const currentQueueNumber = ref('A-024');
  const nextQueueNumber = ref('A-025');

  // Loading indicator for simulated visual transitions
  const loading = ref(false);

  // Selected admission for detail / edit / cancel / print
  const selectedAdmission = ref(null);

  /**
   * Filtered admissions based on search query and active filters
   */
  const filteredAdmissions = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    const today = new Date();
    const formatIso = (d) => d.toISOString().split('T')[0];
    const todayStr = formatIso(today);

    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1);
    const yesterdayStr = formatIso(yesterday);

    const sevenDaysAgo = new Date(today);
    sevenDaysAgo.setDate(today.getDate() - 7);
    const sevenDaysAgoStr = formatIso(sevenDaysAgo);

    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    const thirtyDaysAgoStr = formatIso(thirtyDaysAgo);

    return admissionsList.value.filter((adm) => {
      // 1. Text Search across No. RM, NIK, Nama Pasien, Dokter
      if (q) {
        const matchRm = adm.medical_record_number.toLowerCase().includes(q);
        const matchNik = adm.nik.toLowerCase().includes(q);
        const matchName = adm.patient_name.toLowerCase().includes(q);
        const matchDoctor = adm.doctor.toLowerCase().includes(q);
        const matchQueue = adm.queue_number.toLowerCase().includes(q);

        if (!matchRm && !matchNik && !matchName && !matchDoctor && !matchQueue) {
          return false;
        }
      }

      // 2. Period Filter
      if (filters.period === 'today') {
        if (adm.admission_date !== todayStr) return false;
      } else if (filters.period === 'yesterday') {
        if (adm.admission_date !== yesterdayStr) return false;
      } else if (filters.period === '7days') {
        if (adm.admission_date < sevenDaysAgoStr || adm.admission_date > todayStr) return false;
      } else if (filters.period === '30days') {
        if (adm.admission_date < thirtyDaysAgoStr || adm.admission_date > todayStr) return false;
      } else if (filters.period === 'custom') {
        if (filters.customStartDate && adm.admission_date < filters.customStartDate) return false;
        if (filters.customEndDate && adm.admission_date > filters.customEndDate) return false;
      }

      // 3. Polyclinic Filter
      if (filters.polyclinic !== 'all') {
        if (adm.polyclinic !== filters.polyclinic) return false;
      }

      // 4. Doctor Filter
      if (filters.doctor !== 'all') {
        if (adm.doctor !== filters.doctor) return false;
      }

      // 5. Guarantor Filter
      if (filters.guarantor !== 'all') {
        if (adm.guarantor_type !== filters.guarantor) return false;
      }

      // 6. Service Status Filter
      if (filters.status !== 'all') {
        if (adm.status !== filters.status) return false;
      }

      return true;
    });
  });

  /**
   * Pagination computation
   */
  const paginationInfo = computed(() => {
    const total = filteredAdmissions.value.length;
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

  /**
   * Sliced admissions for active page
   */
  const paginatedAdmissions = computed(() => {
    const start = (pagination.currentPage - 1) * pagination.perPage;
    return filteredAdmissions.value.slice(start, start + pagination.perPage);
  });

  /**
   * Real-time statistics summary
   */
  const statistics = computed(() => {
    const list = admissionsList.value;
    const totalToday = list.length;
    const waiting = list.filter((a) => a.status === 'Menunggu').length;
    const inService = list.filter((a) => a.status === 'Sedang Diperiksa').length;
    const completed = list.filter((a) => a.status === 'Selesai').length;
    const cancelled = list.filter((a) => a.status === 'Dibatalkan').length;

    const targetQuota = 248;
    const percentage = ((completed / targetQuota) * 100).toFixed(1).replace('.', ',');

    return {
      total: totalToday,
      waiting,
      inService,
      completed,
      cancelled,
      percentage: `${percentage}%`
    };
  });

  /**
   * Reset all search criteria
   */
  const resetFilters = () => {
    searchQuery.value = '';
    filters.period = 'today';
    filters.customStartDate = '';
    filters.customEndDate = '';
    filters.polyclinic = 'all';
    filters.doctor = 'all';
    filters.guarantor = 'all';
    filters.status = 'all';

    pagination.currentPage = 1;
  };

  /**
   * Trigger local search with visual loading
   */
  const applyFilters = () => {
    loading.value = true;
    pagination.currentPage = 1;

    setTimeout(() => {
      loading.value = false;
    }, 200);
  };

  /**
   * Pagination navigation
   */
  const setPage = (page) => {
    const last = paginationInfo.value.last_page;
    if (page >= 1 && page <= last) {
      pagination.currentPage = page;
      window.scrollTo({ top: 450, behavior: 'smooth' });
    }
  };

  const setPerPage = (val) => {
    pagination.perPage = Number(val);
    pagination.currentPage = 1;
  };

  /**
   * Queue Simulation Methods
   */
  const callCurrentQueue = () => {
    const current = currentQueueNumber.value;
    // Find matching admission if exists in today's list
    const adm = admissionsList.value.find((a) => a.queue_number === current);
    if (adm && adm.status === 'Menunggu') {
      adm.status = 'Sedang Diperiksa';
    }

    return {
      success: true,
      queueNumber: current,
      patientName: adm ? adm.patient_name : 'Pasien'
    };
  };

  const callNextQueue = () => {
    const called = nextQueueNumber.value;
    currentQueueNumber.value = called;

    // Advance next queue number (e.g. A-025 -> A-026)
    const match = called.match(/^([A-Z])-(\d+)$/);
    if (match) {
      const letter = match[1];
      const nextNum = Number(match[2]) + 1;
      nextQueueNumber.value = `${letter}-${String(nextNum).padStart(3, '0')}`;
    }

    const adm = admissionsList.value.find((a) => a.queue_number === called);
    if (adm && adm.status === 'Menunggu') {
      adm.status = 'Sedang Diperiksa';
    }

    return {
      success: true,
      queueNumber: called,
      patientName: adm ? adm.patient_name : 'Pasien Berikutnya'
    };
  };

  const prevQueue = () => {
    const match = currentQueueNumber.value.match(/^([A-Z])-(\d+)$/);
    if (match) {
      const letter = match[1];
      const currentNum = Number(match[2]);
      if (currentNum > 1) {
        currentQueueNumber.value = `${letter}-${String(currentNum - 1).padStart(3, '0')}`;
      }
    }
  };

  const nextQueue = () => {
    const match = currentQueueNumber.value.match(/^([A-Z])-(\d+)$/);
    if (match) {
      const letter = match[1];
      const currentNum = Number(match[2]);
      currentQueueNumber.value = `${letter}-${String(currentNum + 1).padStart(3, '0')}`;
    }
  };

  /**
   * Admission Management Methods
   */
  const cancelAdmission = (id, reason = 'Permintaan Pasien / Keluarga') => {
    const index = admissionsList.value.findIndex((a) => a.id === id);
    if (index !== -1) {
      admissionsList.value[index] = {
        ...admissionsList.value[index],
        status: 'Dibatalkan',
        cancellation_reason: reason,
        cancelled_at: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB'
      };
      return { success: true, item: admissionsList.value[index] };
    }
    return { success: false };
  };

  const updateAdmission = (id, payload) => {
    const index = admissionsList.value.findIndex((a) => a.id === id);
    if (index !== -1) {
      admissionsList.value[index] = {
        ...admissionsList.value[index],
        ...payload
      };
      return { success: true, item: admissionsList.value[index] };
    }
    return { success: false };
  };

  const addAdmission = (newAdmissionData) => {
    const newId = admissionsList.value.length + 1;
    const now = new Date();
    const todayStr = now.toISOString().split('T')[0];
    const timeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')} WIB`;

    const newRecord = {
      id: newId,
      admission_date: todayStr,
      queue_number: nextQueueNumber.value,
      admission_time: timeStr,
      medical_record_number: newAdmissionData.medical_record_number || `RM-${String(240 + newId).padStart(6, '0')}`,
      nik: newAdmissionData.nik || '3471000000000000',
      patient_name: newAdmissionData.patient_name || 'Pasien Baru',
      gender: newAdmissionData.gender || 'L',
      birth_date: newAdmissionData.birth_date || '1990-01-01',
      age: newAdmissionData.age || 35,
      phone: newAdmissionData.phone || '0812-0000-0000',
      polyclinic: newAdmissionData.polyclinic || 'Poli Umum',
      doctor: newAdmissionData.doctor || 'dr. Budi Santoso, Sp.PD',
      guarantor_type: newAdmissionData.guarantor_type || 'BPJS Kesehatan',
      guarantor_detail: newAdmissionData.guarantor_detail || 'JKN-Non PBI',
      general_consent: 'Sudah TTD',
      status: 'Menunggu',
      notes: newAdmissionData.notes || 'Pendaftaran admisi baru'
    };

    // Prepend to top so it's immediately visible
    admissionsList.value.unshift(newRecord);

    // Increment next queue
    const match = nextQueueNumber.value.match(/^([A-Z])-(\d+)$/);
    if (match) {
      nextQueueNumber.value = `${match[1]}-${String(Number(match[2]) + 1).padStart(3, '0')}`;
    }

    return { success: true, record: newRecord };
  };

  return {
    admissionsList,
    filteredAdmissions,
    paginatedAdmissions,
    paginationInfo,
    pagination,
    searchQuery,
    filters,
    statistics,
    loket,
    currentQueueNumber,
    nextQueueNumber,
    loading,
    selectedAdmission,
    mockPolyclinics,
    mockDoctors,
    mockGuarantors,
    applyFilters,
    resetFilters,
    setPage,
    setPerPage,
    callCurrentQueue,
    callNextQueue,
    prevQueue,
    nextQueue,
    cancelAdmission,
    updateAdmission,
    addAdmission
  };
}
