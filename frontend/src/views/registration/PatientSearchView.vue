<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';
import RegistrationPatientSearchCard from './components/RegistrationPatientSearchCard.vue';
import RegistrationQueueCard from './components/RegistrationQueueCard.vue';
import RegistrationPatientListCard from './components/RegistrationPatientListCard.vue';
import SatuSehatKycModal from './components/SatuSehatKycModal.vue';
import PatientDetailModal from './components/PatientDetailModal.vue';
import AdmissionPrintModal from '@/views/admission/components/AdmissionPrintModal.vue';
import { useGuarantorOptions } from '@/composables/useGuarantorOptions';
import patientService from '@/services/patient.service';

const router = useRouter();

const {
  guarantors: masterGuarantors,
  loading: guarantorsLoading,
  error: guarantorsError,
  fetchGuarantors
} = useGuarantorOptions();

// Search Form State (FORM PENCARIAN PASIEN)
const patientKeyword = ref('');
const patientBirthDate = ref('');
const patientResults = ref([]);
const hasSearchedPatient = ref(false);
const searchValidationError = ref('');
const searchError = ref('');
const isSearchingPatient = ref(false);
const searchPagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 20,
  total: 0
});

// Antrean Pendaftaran Counter State (P001, P002, ...)
const queueNumberIndex = ref(1);
const formattedQueueNumber = computed(() => {
  return `P${String(queueNumberIndex.value).padStart(3, '0')}`;
});

// Modals & Active Item State
const isKycModalOpen = ref(false);
const isPatientDetailOpen = ref(false);
const isDeletePatientOpen = ref(false);
const patientToDelete = ref(null);
const isDeletingPatient = ref(false);
const isPrintModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);

const patientInDetail = ref(null);
const activeAdmission = ref(null);
const printDocType = ref('No. Antrean');

// Toast Notification State
const toast = ref({
  show: false,
  type: 'success',
  title: '',
  message: ''
});

const showToast = ({ type = 'success', title, message }) => {
  toast.value = {
    show: true,
    type,
    title,
    message
  };
};

const closeToast = () => {
  toast.value.show = false;
};

const classifyPatientApiError = (err, fallbackMessage) => {
  const status = err?.response?.status;
  if (!err?.response) {
    return 'Tidak dapat terhubung ke server. Periksa koneksi jaringan Anda.';
  }
  if (status === 401 || status === 403) {
    return err.response?.data?.message || 'Sesi login berakhir atau Anda tidak memiliki akses ke data pasien.';
  }
  if (status === 422) {
    const errorsObj = err.response?.data?.errors;
    const firstFieldMsg = errorsObj ? Object.values(errorsObj).flat()[0] : null;
    return firstFieldMsg || err.response?.data?.message || 'Parameter permintaan tidak valid.';
  }
  return err.response?.data?.message || err.message || fallbackMessage;
};

// Search Handler (FORM PENCARIAN PASIEN)
const handleSearchPatient = async (targetPage = 1) => {
  const page = typeof targetPage === 'number' && targetPage >= 1 ? targetPage : 1;
  const q = patientKeyword.value.trim().replace(/\s+/g, ' ');
  const bd = patientBirthDate.value.trim();

  if (!q && !bd) {
    searchValidationError.value =
      'Masukkan minimal No. KTP/NIK, No. RM, Nama Pasien, atau Tanggal Lahir.';
    searchError.value = '';
    showToast({
      type: 'warning',
      title: 'Kata Kunci Kosong',
      message: searchValidationError.value
    });
    return;
  }

  if (q && q.length < 2 && !bd) {
    searchValidationError.value =
      'Masukkan minimal 2 karakter untuk mencari berdasarkan No. KTP/NIK, No. RM, atau Nama.';
    searchError.value = '';
    showToast({
      type: 'warning',
      title: 'Kata Kunci Terlalu Pendek',
      message: searchValidationError.value
    });
    return;
  }

  searchValidationError.value = '';
  searchError.value = '';
  isSearchingPatient.value = true;

  try {
    const params = {
      page,
      per_page: searchPagination.perPage
    };
    if (q) params.q = q;
    if (bd) params.birth_date = bd;

    const res = await patientService.searchPatients(params);
    const data = Array.isArray(res?.data?.data) ? res.data.data : [];
    const meta = res?.data?.meta || {};

    patientResults.value = data;
    searchPagination.currentPage = meta.current_page ?? page;
    searchPagination.lastPage = meta.last_page ?? 1;
    searchPagination.total = meta.total ?? data.length;
    hasSearchedPatient.value = true;

    if (searchPagination.total === 0) {
      showToast({
        type: 'info',
        title: 'Data Tidak Ditemukan',
        message: 'Tidak ada data pasien yang cocok. Anda dapat mendaftarkan pasien baru.'
      });
    } else {
      showToast({
        type: 'success',
        title: 'Pencarian Berhasil',
        message: `Ditemukan ${searchPagination.total} data pasien terdaftar.`
      });
    }
  } catch (err) {
    const msg = classifyPatientApiError(err, 'Terjadi kesalahan saat memanggil server.');
    patientResults.value = [];
    searchPagination.currentPage = 1;
    searchPagination.lastPage = 1;
    searchPagination.total = 0;
    if (err?.response?.status === 422) {
      searchValidationError.value = msg;
    }
    searchError.value = msg;
    hasSearchedPatient.value = true;
    showToast({
      type: 'danger',
      title: 'Gagal Mencari Data',
      message: msg
    });
  } finally {
    isSearchingPatient.value = false;
  }
};

const handleSearchPageChange = (page) => {
  handleSearchPatient(page);
};

const handleResetSearch = () => {
  patientKeyword.value = '';
  patientBirthDate.value = '';
  patientResults.value = [];
  hasSearchedPatient.value = false;
  searchValidationError.value = '';
  searchError.value = '';
  searchPagination.currentPage = 1;
  searchPagination.lastPage = 1;
  searchPagination.total = 0;
};

const handleNavigateRegisterNew = () => {
  router.push('/pendaftaran/baru');
};

const handleOpenKyc = () => {
  isKycModalOpen.value = true;
};

const handleKycVerified = (res) => {
  showToast({
    type: 'success',
    title: 'SatuSehat KYC Terverifikasi',
    message: `NIK ${res.nik} berhasil terverifikasi (No. IHS Simulasi: ${res.ihs_number}).`
  });
};

const handleViewPatientDetail = async (patient) => {
  if (!patient?.id) return;
  try {
    const res = await patientService.getPatient(patient.id);
    const detail = res?.data?.data || res?.data;
    if (!detail?.id) {
      throw new Error('Data detail pasien tidak valid.');
    }
    patientInDetail.value = detail;
    isPatientDetailOpen.value = true;
  } catch (err) {
    showToast({
      type: 'danger',
      title: 'Gagal Memuat Detail Pasien',
      message: classifyPatientApiError(err, 'Gagal mengambil detail pasien dari server.')
    });
  }
};

const handleAdmitFromSearch = (patient) => {
  isPatientDetailOpen.value = false;
  handleAdmitPatient(patient);
};

const handleAdmitPatient = async (item) => {
  const patientId = item.patient_id || item.id;
  if (!patientId) return;

  // Verifikasi status tunggakan billing pasien
  let hasUnpaid = item.has_unpaid_bills;
  let unpaidMsg = item.unpaid_billing_message;

  if (hasUnpaid === undefined) {
    try {
      const res = await patientService.getPatient(patientId);
      const patData = res?.data?.data || res?.data;
      if (!patData?.id) {
        throw new Error('Data pasien tidak ditemukan di server.');
      }
      hasUnpaid = Boolean(patData.has_unpaid_bills);
      unpaidMsg = patData.unpaid_billing_message;
    } catch (err) {
      showToast({
        type: 'danger',
        title: 'Gagal Memverifikasi Pasien',
        message: classifyPatientApiError(err, 'Gagal memverifikasi status pasien dari server.')
      });
      return;
    }
  }

  // Jika pasien memiliki tanggungan belum lunas: Tampilkan notifikasi di Pendaftaran & JANGAN berpindah halaman
  if (hasUnpaid) {
    showToast({
      type: 'danger',
      title: 'Notifications',
      message:
        unpaidMsg ||
        'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.'
    });
    return;
  }

  // Jika pasien belum pernah admisi / tagihan lunas: Langsung arahkan ke Form Tambah Admisi
  router.push({ path: '/admisi/baru', query: { patient_id: patientId } });
};

// Patient Tools Action Handlers (Edit & Delete Pasien)
const handleOpenEditPatient = (patient) => {
  const patientId = patient?.id;
  if (!patientId) return;
  router.push({
    name: 'registration.patient-edit',
    params: { id: patientId }
  });
};

const handleOpenDeletePatient = (patient) => {
  patientToDelete.value = patient;
  isDeletePatientOpen.value = true;
};

const handleConfirmDeletePatient = async () => {
  if (!patientToDelete.value) return;
  isDeletingPatient.value = true;
  try {
    const res = await patientService.deletePatient(patientToDelete.value.id);
    const pName = patientToDelete.value.name || 'Pasien';
    const pRm = patientToDelete.value.medical_record_number || '';
    patientResults.value = patientResults.value.filter((p) => p.id !== patientToDelete.value.id);
    loadPatientTable();
    isDeletePatientOpen.value = false;
    showToast({
      type: 'warning',
      title: 'Pasien Dinonaktifkan',
      message: res.data?.message || `Data pasien ${pName} (${pRm}) berhasil dinonaktifkan.`
    });
  } catch (err) {
    const msg = err.response?.data?.message || err.message || 'Gagal menonaktifkan data pasien.';
    showToast({
      type: 'danger',
      title: 'Penghapusan Dibatasi',
      message: msg
    });
    isDeletePatientOpen.value = false;
  } finally {
    isDeletingPatient.value = false;
    patientToDelete.value = null;
  }
};

// Queue Handlers (NO. ANTRIAN)
const handleCallQueue = () => {
  showToast({
    type: 'success',
    title: 'Memanggil Antrean Pendaftaran',
    message: `Nomor antrean ${formattedQueueNumber.value} sedang dipanggil ke Loket Pendaftaran (Simulasi).`
  });
};

const handlePrevQueue = () => {
  if (queueNumberIndex.value > 1) {
    queueNumberIndex.value -= 1;
    showToast({
      type: 'info',
      title: 'Antrean Sebelumnya',
      message: `Nomor antrean aktif diubah ke ${formattedQueueNumber.value}.`
    });
  }
};

const handleNextQueue = () => {
  queueNumberIndex.value += 1;
  showToast({
    type: 'info',
    title: 'Antrean Berikutnya',
    message: `Nomor antrean aktif diubah ke ${formattedQueueNumber.value}.`
  });
};

// DAFTAR PASIEN Table State (terhubung langsung ke database backend)
const patientTableList = ref([]);
const patientTableLoading = ref(false);
const patientTableError = ref('');
const patientTablePagination = reactive({
  currentPage: 1,
  perPage: 10,
  total: 0,
  lastPage: 1,
  from: 0,
  to: 0
});
const patientTableGuarantor = ref('all');

const getStoredPrintedConsents = () => {
  try {
    const raw = localStorage.getItem('bigsirs_printed_general_consents');
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
};

const saveStoredPrintedConsent = (id) => {
  try {
    if (!id) return;
    const list = getStoredPrintedConsents();
    const strId = String(id);
    if (!list.includes(strId)) {
      list.push(strId);
      localStorage.setItem('bigsirs_printed_general_consents', JSON.stringify(list));
    }
  } catch {
    // Abaikan kegagalan akses localStorage
  }
};

const patientTableSort = reactive({
  key: '',
  direction: 'asc'
});

const loadPatientTable = async () => {
  patientTableLoading.value = true;
  patientTableError.value = '';
  try {
    const params = {
      page: patientTablePagination.currentPage,
      per_page: patientTablePagination.perPage
    };
    if (patientTableGuarantor.value !== 'all') {
      params.guarantor = patientTableGuarantor.value;
      if (/^\d+$/.test(String(patientTableGuarantor.value))) {
        params.guarantor_id = Number(patientTableGuarantor.value);
      }
    }
    const res = await patientService.searchPatients(params);
    const rawData = Array.isArray(res?.data?.data) ? res.data.data : [];
    const meta = res?.data?.meta || {};

    patientTablePagination.total = meta.total ?? rawData.length;
    patientTablePagination.currentPage = meta.current_page ?? 1;
    patientTablePagination.lastPage = meta.last_page ?? 1;
    patientTablePagination.from = meta.from ?? (rawData.length > 0 ? (patientTablePagination.currentPage - 1) * patientTablePagination.perPage + 1 : 0);
    patientTablePagination.to = meta.to ?? (rawData.length > 0 ? patientTablePagination.from + rawData.length - 1 : 0);

    const printedConsents = getStoredPrintedConsents();
    patientTableList.value = rawData.map((p) => {
      const adm = p.admission || {};
      const isPrinted = printedConsents.includes(String(p.id)) ||
                        (adm.id && printedConsents.includes(String(adm.id))) ||
                        adm.general_consent === 'Sudah diprint';
      return {
        id: p.id,
        patient_id: p.id,
        admission_id: adm.id || null,
        visit_id: adm.visit_id || null,
        rawPatient: p,
        admission: adm,
        queue_number: '—',
        admission_date: adm.date || p.birth_date || '—',
        admission_time: adm.time || '—',
        patient_name: p.name,
        medical_record_number: p.medical_record_number,
        gender: p.gender,
        birth_date: p.birth_date,
        phone: p.phone,
        address: p.address,
        polyclinic: adm.polyclinic || '—',
        notes: adm.notes || '',
        guarantor_type: (p.guarantor && typeof p.guarantor === 'string')
          ? p.guarantor
          : (p.guarantor?.name || p.guarantor?.jenis_asuransi || '—'),
        general_consent: isPrinted ? 'Sudah diprint' : 'Belum diprint'
      };
    });
  } catch (err) {
    const msg = classifyPatientApiError(err, 'Gagal memuat daftar pasien dari database.');
    patientTableList.value = [];
    patientTablePagination.total = 0;
    patientTablePagination.lastPage = 1;
    patientTablePagination.from = 0;
    patientTablePagination.to = 0;
    patientTableError.value = msg;
    showToast({
      type: 'danger',
      title: 'Gagal Memuat Data',
      message: msg
    });
  } finally {
    patientTableLoading.value = false;
  }
};

const handlePatientGuarantorChange = (val) => {
  patientTableGuarantor.value = val;
  patientTablePagination.currentPage = 1;
  loadPatientTable();
};

const handlePatientResetFilter = () => {
  patientTableGuarantor.value = 'all';
  patientTablePagination.currentPage = 1;
  loadPatientTable();
};

const handlePatientPerPageChange = (val) => {
  patientTablePagination.perPage = val;
  patientTablePagination.currentPage = 1;
  loadPatientTable();
};

const handlePatientPageChange = (page) => {
  patientTablePagination.currentPage = page;
  loadPatientTable();
};

const handlePatientSort = (key) => {
  if (patientTableSort.key === key) {
    patientTableSort.direction = patientTableSort.direction === 'asc' ? 'desc' : 'asc';
  } else {
    patientTableSort.key = key;
    patientTableSort.direction = 'asc';
  }

  patientTableList.value.sort((a, b) => {
    let valA = a[key] || '';
    let valB = b[key] || '';
    if (key === 'nama') {
      valA = a.patient_name;
      valB = b.patient_name;
    } else if (key === 'asuransi') {
      valA = a.guarantor_type;
      valB = b.guarantor_type;
    }
    const cmp = String(valA).localeCompare(String(valB), 'id', { numeric: true });
    return patientTableSort.direction === 'asc' ? cmp : -cmp;
  });
};

onMounted(() => {
  fetchGuarantors();
  loadPatientTable();
});

// Tools Dropdown Action Handlers
const handleOpenEditAdmission = (item) => {
  const admissionId = item.admission_id || item.admission?.id;
  if (!admissionId) {
    showToast({
      type: 'warning',
      title: 'Admisi Belum Tersedia',
      message: `Pasien ${item.patient_name || ''} belum memiliki riwayat admisi untuk diedit.`
    });
    return;
  }
  const query = { from: 'pendaftaran' };
  const visitId = item.visit_id || item.admission?.visit_id;
  if (visitId) {
    query.visit_id = visitId;
  }
  router.push({
    name: 'registration.admission-edit',
    params: { id: admissionId },
    query
  });
};

const handleOpenDeleteAdmission = (item) => {
  activeAdmission.value = item;
  isDeleteDialogOpen.value = true;
};

const handleConfirmDeleteAdmission = () => {
  if (!activeAdmission.value) return;
  const deletedName = activeAdmission.value.patient_name;
  patientTableList.value = patientTableList.value.filter((p) => p.id !== activeAdmission.value.id);
  patientTablePagination.total = Math.max(0, patientTablePagination.total - 1);
  isDeleteDialogOpen.value = false;
  showToast({
    type: 'warning',
    title: 'Admisi Dihapus',
    message: `Data kunjungan admisi ${deletedName} telah dihapus dari daftar.`
  });
  activeAdmission.value = null;
};

const handleOpenPrintDocument = ({ item, documentType }) => {
  activeAdmission.value = item;
  printDocType.value = documentType || 'No. Antrean';
  isPrintModalOpen.value = true;

  const dt = String(documentType || '').toLowerCase();
  if (dt.includes('consent') || dt.includes('persetujuan')) {
    item.general_consent = 'Sudah diprint';
    const row = patientTableList.value.find((p) => p.id === item.id);
    if (row) {
      row.general_consent = 'Sudah diprint';
    }
    saveStoredPrintedConsent(item.id);
    if (item.admission_id) saveStoredPrintedConsent(item.admission_id);
  }
};

const handleConfirmPrint = ({ documentType, admission }) => {
  isPrintModalOpen.value = false;
  const dt = String(documentType || '').toLowerCase();
  if (dt.includes('consent') || dt.includes('persetujuan')) {
    if (admission) {
      admission.general_consent = 'Sudah diprint';
      const row = patientTableList.value.find((p) => p.id === admission.id);
      if (row) {
        row.general_consent = 'Sudah diprint';
      }
      saveStoredPrintedConsent(admission.id);
      if (admission.admission_id) saveStoredPrintedConsent(admission.admission_id);
    }
  }
  showToast({
    type: 'success',
    title: 'Cetak Dokumen Diproses',
    message: `Dokumen ${documentType} untuk ${admission?.patient_name || 'pasien'} (${admission?.medical_record_number || ''}) berhasil diproses cetak.`
  });
};
</script>

<template>
  <div class="page-container registration-home-page">
    <!-- Toast Notification -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Header Section -->
    <MasterDataPageHeader
      title="Pendaftaran Pasien"
      subtitle="Manajemen Data Pasien — Pencarian pasien, registrasi pasien baru, kontrol antrean, dan daftar pasien."
      :breadcrumbs="[
        { label: 'Home', active: false },
        { label: 'Data Pasien', active: true }
      ]"
      badge="Manajemen Data Pasien"
    />

    <!-- Main Page Content -->
    <div class="page-content">
      <!-- Top Row: Form Pencarian Pasien (Left) + No. Antrian (Right) -->
      <div class="top-cards-grid">
        <RegistrationPatientSearchCard
          v-model:keyword="patientKeyword"
          v-model:birthDate="patientBirthDate"
          :results="patientResults"
          :has-searched="hasSearchedPatient"
          :loading="isSearchingPatient"
          :validation-error="searchValidationError"
          :error="searchError"
          :total-count="searchPagination.total"
          :current-page="searchPagination.currentPage"
          :last-page="searchPagination.lastPage"
          @search="handleSearchPatient(1)"
          @retry="handleSearchPatient(searchPagination.currentPage)"
          @page-change="handleSearchPageChange"
          @reset="handleResetSearch"
          @register-new="handleNavigateRegisterNew"
          @open-kyc="handleOpenKyc"
          @view-detail="handleViewPatientDetail"
          @edit-patient="handleOpenEditPatient"
          @delete-patient="handleOpenDeletePatient"
          @admit-patient="handleAdmitFromSearch"
        />

        <RegistrationQueueCard
          :queue-number="formattedQueueNumber"
          :can-prev="queueNumberIndex > 1"
          @call="handleCallQueue"
          @prev="handlePrevQueue"
          @next="handleNextQueue"
        />
      </div>

      <!-- Bottom Card: DAFTAR PASIEN (Real Database Data) -->
      <RegistrationPatientListCard
        :admissions="patientTableList"
        :total-count="patientTablePagination.total"
        :current-page="patientTablePagination.currentPage"
        :last-page="patientTablePagination.lastPage"
        :per-page="patientTablePagination.perPage"
        :from="patientTablePagination.from"
        :to="patientTablePagination.to"
        :guarantor-filter="patientTableGuarantor"
        :guarantors="masterGuarantors"
        :guarantors-loading="guarantorsLoading"
        :guarantors-error="guarantorsError"
        :sort-key="patientTableSort.key"
        :sort-direction="patientTableSort.direction"
        :loading="patientTableLoading"
        :error="patientTableError"
        @update:guarantorFilter="handlePatientGuarantorChange"
        @update:perPage="handlePatientPerPageChange"
        @page-change="handlePatientPageChange"
        @sort="handlePatientSort"
        @admit-patient="handleAdmitPatient"
        @edit-admission="handleOpenEditAdmission"
        @delete-admission="handleOpenDeleteAdmission"
        @print-document="handleOpenPrintDocument"
        @reset-filter="handlePatientResetFilter"
        @retry="loadPatientTable"
        @retry-guarantors="fetchGuarantors"
      />
    </div>

    <!-- Modals -->
    <SatuSehatKycModal
      :is-open="isKycModalOpen"
      :initial-nik="patientKeyword"
      @close="isKycModalOpen = false"
      @verified="handleKycVerified"
    />

    <PatientDetailModal
      :is-open="isPatientDetailOpen"
      :patient="patientInDetail"
      @close="isPatientDetailOpen = false"
      @select="handleAdmitFromSearch"
    />

    <MasterDataDeleteDialog
      :is-open="isDeletePatientOpen"
      title="Konfirmasi Penonaktifan Pasien"
      :item-name="patientToDelete ? `${patientToDelete.name} (${patientToDelete.medical_record_number})` : ''"
      :is-submitting="isDeletingPatient"
      warning-message="Pasien yang dinonaktifkan tidak akan muncul lagi pada pencarian aktif. Pasien dengan riwayat admisi/kunjungan tidak dapat dihapus demi integritas rekam medis."
      confirm-text="Nonaktifkan Pasien"
      @close="isDeletePatientOpen = false"
      @confirm="handleConfirmDeletePatient"
    />

    <AdmissionPrintModal
      :is-open="isPrintModalOpen"
      :admission="activeAdmission"
      :document-type="printDocType"
      @close="isPrintModalOpen = false"
      @confirm-print="handleConfirmPrint"
    />

    <MasterDataDeleteDialog
      :is-open="isDeleteDialogOpen"
      title="Hapus Data Admisi?"
      :item-name="activeAdmission ? `${activeAdmission.patient_name} (${activeAdmission.queue_number})` : ''"
      warning-message="Data kunjungan admisi yang dihapus akan dikeluarkan dari daftar pasien hari ini."
      confirm-text="Ya, Hapus Admisi"
      @close="isDeleteDialogOpen = false"
      @confirm="handleConfirmDeleteAdmission"
    />
  </div>
</template>

<style scoped>
.registration-home-page {
  animation: fadeIn 0.25s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.top-cards-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 330px;
  gap: 1.25rem;
  align-items: start;
}

.top-cards-grid :deep(.registration-search-card) {
  min-width: 0;
  height: auto;
}

.top-cards-grid :deep(.registration-queue-card) {
  height: fit-content;
  align-self: start;
}

@media (max-width: 1024px) {
  .top-cards-grid {
    grid-template-columns: 1fr;
  }
}
</style>
