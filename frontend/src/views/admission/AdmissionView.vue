<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';
import AdmissionOldPatientSearchCard from './components/AdmissionOldPatientSearchCard.vue';
import AdmissionPatientListCard from './components/AdmissionPatientListCard.vue';
import AdmissionPrintModal from './components/AdmissionPrintModal.vue';
import AdmissionNewModal from './components/AdmissionNewModal.vue';
import PatientDetailModal from '@/views/registration/components/PatientDetailModal.vue';
import { useAdmissions } from '@/composables/useAdmissions';
import { mockPatients } from '@/mocks/patients';
import patientService from '@/services/patient.service';
import admissionService from '@/services/admission.service';
import lookupService from '@/services/lookup.service';
import { polyclinicService } from '@/services/polyclinic';

const router = useRouter();
const route = useRoute();

const {
  paginatedAdmissions,
  paginationInfo,
  pagination,
  filters,
  sort,
  nextQueueNumber,
  loading: mockLoading,
  mockPolyclinics,
  mockDoctors,
  setSort: mockSetSort,
  setPage: mockSetPage,
  setPerPage: mockSetPerPage,
  deleteAdmission,
  addAdmission
} = useAdmissions();

// Real API Admissions state
const realAdmissions = ref([]);
const realTotal = ref(0);
const realCurrentPage = ref(1);
const realLastPage = ref(1);
const realPerPage = ref(10);
const realFrom = ref(0);
const realTo = ref(0);
const realLoading = ref(false);
const polyclinicsList = ref([]);
const doctorsList = ref([]);

const loadAdmissions = async () => {
  realLoading.value = true;
  try {
    const params = {
      page: realCurrentPage.value,
      per_page: realPerPage.value
    };
    if (filters.guarantor && filters.guarantor !== 'all') {
      params.guarantor = filters.guarantor;
    }
    const res = await admissionService.getAdmissions(params);
    const data = res.data?.data || [];
    const meta = res.data?.meta || {};
    realAdmissions.value = data;
    realTotal.value = meta.total ?? data.length;
    realCurrentPage.value = meta.current_page ?? realCurrentPage.value;
    realLastPage.value = meta.last_page ?? 1;
    realFrom.value = meta.from ?? (data.length > 0 ? (realCurrentPage.value - 1) * realPerPage.value + 1 : 0);
    realTo.value = meta.to ?? (data.length > 0 ? realFrom.value + data.length - 1 : 0);
  } catch (err) {
    realAdmissions.value = paginatedAdmissions.value;
    realTotal.value = paginationInfo.value.total;
    realCurrentPage.value = paginationInfo.value.current_page;
    realLastPage.value = paginationInfo.value.last_page;
    realFrom.value = paginationInfo.value.from;
    realTo.value = paginationInfo.value.to;
  } finally {
    realLoading.value = false;
  }
};

// Form Pencarian Pasien Lama state
const oldPatientQuery = ref('');
const oldPatientBirthDate = ref('');
const oldPatientResults = ref([]);
const hasSearchedOldPatient = ref(false);
const isSearchingOldPatient = ref(false);
const searchValidationError = ref('');

// Modal States
const isPrintOpen = ref(false);
const isDeleteOpen = ref(false);
const isNewOpen = ref(false);
const isPatientDetailOpen = ref(false);
const detailPatient = ref(null);

const activeAdmission = ref(null);
const selectedOldPatient = ref(null);
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

// Pencarian Pasien Lama Handlers (terhubung langsung ke database nyata)
const handleSearchOldPatient = async (payload) => {
  if (payload?.keyword !== undefined) {
    oldPatientQuery.value = payload.keyword;
  }
  if (payload?.birthDate !== undefined) {
    oldPatientBirthDate.value = payload.birthDate;
  }

  const q = (oldPatientQuery.value || '').trim();
  const bd = (oldPatientBirthDate.value || '').trim();

  if (!q && !bd) {
    searchValidationError.value =
      'Masukkan minimal NIK, No. Rekam Medis, Nama Pasien, atau Tanggal Lahir.';
    showToast({
      type: 'warning',
      title: 'Kata Kunci Kosong',
      message: searchValidationError.value
    });
    return;
  }

  if (q && q.length < 2 && !bd) {
    searchValidationError.value =
      'Masukkan minimal 2 karakter untuk pencarian NIK, No. Rekam Medis, atau Nama Pasien.';
    showToast({
      type: 'warning',
      title: 'Kata Kunci Terlalu Pendek',
      message: searchValidationError.value
    });
    return;
  }

  searchValidationError.value = '';
  isSearchingOldPatient.value = true;

  try {
    const params = { per_page: 50 };
    if (q) params.q = q;
    if (bd) params.birth_date = bd;

    const res = await patientService.searchPatients(params);
    const matches = res.data?.data || [];
    oldPatientResults.value = matches;
    hasSearchedOldPatient.value = true;

    if (matches.length === 0) {
      showToast({
        type: 'info',
        title: 'Pasien Tidak Ditemukan',
        message: 'Tidak ada data pasien lama yang cocok dengan kriteria pencarian Anda.'
      });
    } else {
      showToast({
        type: 'success',
        title: 'Pasien Ditemukan',
        message: `Ditemukan ${matches.length} data pasien lama yang sesuai.`
      });
    }
  } catch (err) {
    oldPatientResults.value = [];
    hasSearchedOldPatient.value = true;
    showToast({
      type: 'danger',
      title: 'Gagal Mencari Data',
      message: err.response?.data?.message || err.message || 'Terjadi kesalahan saat memanggil database.'
    });
  } finally {
    isSearchingOldPatient.value = false;
  }
};

const handleResetOldPatientSearch = () => {
  oldPatientQuery.value = '';
  oldPatientBirthDate.value = '';
  oldPatientResults.value = [];
  hasSearchedOldPatient.value = false;
  searchValidationError.value = '';
};

const handleSelectOldPatient = (patient) => {
  if (patient?.has_unpaid_bills) {
    showToast({
      type: 'danger',
      title: 'Gagal Admisi',
      message:
        patient.unpaid_billing_message ||
        'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.'
    });
    return;
  }
  selectedOldPatient.value = patient;
  isNewOpen.value = true;
};

const handleViewPatientDetail = async (patient) => {
  try {
    const res = await patientService.getPatient(patient.id);
    detailPatient.value = res.data?.data || patient;
  } catch {
    detailPatient.value = patient;
  }
  isPatientDetailOpen.value = true;
};

const handleSelectFromDetail = (patient) => {
  isPatientDetailOpen.value = false;
  handleSelectOldPatient(patient);
};

const handleRegisterNewPatient = () => {
  router.push('/pendaftaran/baru');
};

// Filter Asuransi di Daftar Pasien
const handleGuarantorChange = (val) => {
  filters.guarantor = val;
  pagination.currentPage = 1;
  realCurrentPage.value = 1;
  loadAdmissions();
};

const handleResetGuarantor = () => {
  filters.guarantor = 'all';
  pagination.currentPage = 1;
  realCurrentPage.value = 1;
  loadAdmissions();
};

const handlePageChange = (page) => {
  realCurrentPage.value = page;
  mockSetPage(page);
  loadAdmissions();
};

const handlePerPageChange = (perPage) => {
  realPerPage.value = perPage;
  mockSetPerPage(perPage);
  realCurrentPage.value = 1;
  loadAdmissions();
};

const handleSort = (key) => {
  mockSetSort(key);
};

// Table Dropdown (Tools) Action Handlers
const handleOpenEdit = (item) => {
  router.push({ name: 'registration.admission-edit', params: { id: item.id } });
};

const handleOpenPrint = ({ item, documentType }) => {
  activeAdmission.value = item;
  printDocType.value = documentType || 'No. Antrean';
  isPrintOpen.value = true;
};

const handleConfirmPrint = ({ documentType, admission }) => {
  isPrintOpen.value = false;
  showToast({
    type: 'success',
    title: 'Pencetakan Berhasil',
    message: `Dokumen ${documentType} untuk ${admission.patient_name} (${admission.medical_record_number}) berhasil diproses.`
  });
};

const handleOpenDelete = (item) => {
  activeAdmission.value = item;
  isDeleteOpen.value = true;
};

const handleConfirmDelete = () => {
  if (!activeAdmission.value) return;
  const res = deleteAdmission(activeAdmission.value.id);
  isDeleteOpen.value = false;
  if (res.success) {
    if (paginatedAdmissions.value.length === 0 && pagination.currentPage > 1) {
      pagination.currentPage -= 1;
    }
    loadAdmissions();
    showToast({
      type: 'warning',
      title: 'Data Admisi Dihapus',
      message: `Data admisi ${res.item.patient_name} (${res.item.medical_record_number}) telah dihapus dari daftar.`
    });
  }
  activeAdmission.value = null;
};

const handleCloseNewModal = () => {
  isNewOpen.value = false;
  selectedOldPatient.value = null;
};

const handleSubmitNew = async (newRecordData) => {
  const patientId = selectedOldPatient.value?.id || newRecordData.patient_id;
  if (!patientId) {
    showToast({
      type: 'danger',
      title: 'Gagal Menyimpan Admisi',
      message: 'Identitas pasien tidak ditemukan. Pilih pasien terlebih dahulu.'
    });
    return;
  }

  if (selectedOldPatient.value?.has_unpaid_bills) {
    showToast({
      type: 'danger',
      title: 'Gagal Menyimpan Admisi',
      message:
        selectedOldPatient.value.unpaid_billing_message ||
        'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.'
    });
    isNewOpen.value = false;
    return;
  }

  try {
    const payload = {
      patient_id: patientId,
      polyclinic: newRecordData.polyclinic,
      doctor: newRecordData.doctor,
      notes: newRecordData.notes || '',
      admission_date: newRecordData.admission_date
    };
    await admissionService.createAdmission(payload);
    isNewOpen.value = false;
    selectedOldPatient.value = null;
    await loadAdmissions();
    showToast({
      type: 'success',
      title: 'Admisi Berhasil Didaftarkan',
      message: `Pasien ${newRecordData.patient_name} berhasil didaftarkan ke ${newRecordData.polyclinic}.`
    });
  } catch (err) {
    showToast({
      type: 'danger',
      title: 'Gagal Menyimpan Admisi',
      message: err.response?.data?.message || err.message || 'Terjadi kesalahan saat menyimpan data admisi ke database.'
    });
  }
};

onMounted(async () => {
  await loadAdmissions();

  // If patient_id passed in query string from Registration handoff
  const patientId = route.query.patient_id;
  if (patientId) {
    try {
      const res = await patientService.getPatient(patientId);
      const patientData = res.data?.data;
      if (patientData) {
        if (patientData.has_unpaid_bills) {
          showToast({
            type: 'danger',
            title: 'Gagal Admisi',
            message:
              patientData.unpaid_billing_message ||
              'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.'
          });
        } else {
          selectedOldPatient.value = patientData;
          isNewOpen.value = true;
          showToast({
            type: 'info',
            title: 'Pasien Siap Diadmisi',
            message: `Melanjutkan pendaftaran admisi untuk pasien ${patientData.name} (${patientData.medical_record_number}).`
          });
        }
      }
    } catch {
      // fallback
    }
  }

  // Master Polyclinics and Doctors
  try {
    const polyRes = await polyclinicService.getPolyclinics({ per_page: 50 });
    const polyData = polyRes.data?.data || [];
    if (polyData.length > 0) {
      polyclinicsList.value = polyData.map((p) => p.name || p.nama_poliklinik);
    }
  } catch {}

  try {
    const docRes = await lookupService.getDoctors();
    const docData = docRes.data?.data || docRes.data || [];
    if (docData.length > 0) {
      doctorsList.value = docData.map((d) => d.name || d.nama_lengkap || d.nama);
    }
  } catch {}
});
</script>

<template>
  <div class="page-container admission-page">
    <!-- Notification Toast System -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Header Section -->
    <MasterDataPageHeader
      title="Admisi Pasien"
      subtitle="Pencarian data pasien lama dan pengelolaan daftar kunjungan pasien admisi."
      :breadcrumbs="[
        { label: 'Home', active: false },
        { label: 'Data Admisi Pasien', active: true }
      ]"
      badge="Admisi Pasien"
    />

    <!-- Page Content Flow -->
    <div class="page-content">
      <!-- Card 1: FORM PENCARIAN PASIEN LAMA -->
      <AdmissionOldPatientSearchCard
        v-model:keyword="oldPatientQuery"
        v-model:birthDate="oldPatientBirthDate"
        :results="oldPatientResults"
        :has-searched="hasSearchedOldPatient"
        :loading="isSearchingOldPatient"
        :validation-error="searchValidationError"
        @search="handleSearchOldPatient"
        @reset="handleResetOldPatientSearch"
        @select-patient="handleSelectOldPatient"
        @view-detail="handleViewPatientDetail"
        @register-new="handleRegisterNewPatient"
      />

      <!-- Card 2: DAFTAR PASIEN -->
      <AdmissionPatientListCard
        :admissions="realAdmissions.length > 0 ? realAdmissions : paginatedAdmissions"
        :total-count="realAdmissions.length > 0 ? realTotal : paginationInfo.total"
        :current-page="realAdmissions.length > 0 ? realCurrentPage : paginationInfo.current_page"
        :last-page="realAdmissions.length > 0 ? realLastPage : paginationInfo.last_page"
        :per-page="realAdmissions.length > 0 ? realPerPage : pagination.perPage"
        :from="realAdmissions.length > 0 ? realFrom : paginationInfo.from"
        :to="realAdmissions.length > 0 ? realTo : paginationInfo.to"
        :guarantor-filter="filters.guarantor"
        :sort-key="sort.key"
        :sort-direction="sort.direction"
        :loading="realLoading || mockLoading"
        @update:guarantorFilter="handleGuarantorChange"
        @update:perPage="handlePerPageChange"
        @page-change="handlePageChange"
        @sort="handleSort"
        @edit-admission="handleOpenEdit"
        @delete-admission="handleOpenDelete"
        @print-document="handleOpenPrint"
        @reset-filter="handleResetGuarantor"
      />
    </div>

    <!-- Modals & Dialogs -->

    <AdmissionPrintModal
      :is-open="isPrintOpen"
      :admission="activeAdmission"
      :document-type="printDocType"
      @close="isPrintOpen = false"
      @confirm-print="handleConfirmPrint"
    />

    <MasterDataDeleteDialog
      :is-open="isDeleteOpen"
      title="Hapus Data Admisi?"
      :item-name="activeAdmission ? `${activeAdmission.patient_name} (${activeAdmission.medical_record_number})` : ''"
      warning-message="Data kunjungan admisi yang dihapus akan dikeluarkan dari daftar pasien."
      confirm-text="Ya, Hapus"
      @close="isDeleteOpen = false"
      @confirm="handleConfirmDelete"
    />

    <AdmissionNewModal
      :is-open="isNewOpen"
      :polyclinics="polyclinicsList.length > 0 ? polyclinicsList : mockPolyclinics"
      :doctors="doctorsList.length > 0 ? doctorsList : mockDoctors"
      :next-queue-number="nextQueueNumber"
      :patient="selectedOldPatient"
      @close="handleCloseNewModal"
      @submit="handleSubmitNew"
    />

    <PatientDetailModal
      :is-open="isPatientDetailOpen"
      :patient="detailPatient"
      @close="isPatientDetailOpen = false"
      @select="handleSelectFromDetail"
    />
  </div>
</template>

<style scoped>
.admission-page {
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
</style>
