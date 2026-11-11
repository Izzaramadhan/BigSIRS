<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';
import RegistrationPatientSearchCard from './components/RegistrationPatientSearchCard.vue';
import RegistrationQueueCard from './components/RegistrationQueueCard.vue';
import RegistrationPatientListCard from './components/RegistrationPatientListCard.vue';
import SatuSehatKycModal from './components/SatuSehatKycModal.vue';
import PatientDetailModal from './components/PatientDetailModal.vue';
import AdmissionNewModal from '@/views/admission/components/AdmissionNewModal.vue';
import AdmissionEditModal from '@/views/admission/components/AdmissionEditModal.vue';
import AdmissionPrintModal from '@/views/admission/components/AdmissionPrintModal.vue';
import { useAdmissions } from '@/composables/useAdmissions';
import { mockPatients } from '@/mocks/patients';

const router = useRouter();

const {
  paginatedAdmissions,
  paginationInfo,
  pagination,
  searchQuery,
  filters,
  sort,
  nextQueueNumber,
  loading,
  mockPolyclinics,
  mockDoctors,
  setSort,
  setPage,
  setPerPage,
  updateAdmission,
  deleteAdmission,
  addAdmission
} = useAdmissions();

// Search Form State (FORM PENCARIAN PASIEN)
const patientKeyword = ref('');
const patientBirthDate = ref('');
const patientResults = ref([]);
const hasSearchedPatient = ref(false);
const searchValidationError = ref('');

// Antrean Pendaftaran Counter State (P001, P002, ...)
const queueNumberIndex = ref(1);
const formattedQueueNumber = computed(() => {
  return `P${String(queueNumberIndex.value).padStart(3, '0')}`;
});

// Modals & Active Item State
const isKycModalOpen = ref(false);
const isPatientDetailOpen = ref(false);
const isNewAdmissionOpen = ref(false);
const isEditAdmissionOpen = ref(false);
const isPrintModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);

const patientInDetail = ref(null);
const selectedPatientForAdmission = ref(null);
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

// Search Handler (FORM PENCARIAN PASIEN)
const handleSearchPatient = () => {
  const q = patientKeyword.value.trim().toLowerCase();
  const bd = patientBirthDate.value.trim();

  if (!q && !bd) {
    searchValidationError.value =
      'Masukkan minimal No. KTP/NIK, No. RM, Nama Pasien, atau Tanggal Lahir.';
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
    showToast({
      type: 'warning',
      title: 'Kata Kunci Terlalu Pendek',
      message: searchValidationError.value
    });
    return;
  }

  searchValidationError.value = '';

  const matches = mockPatients.filter((p) => {
    const matchKeyword =
      !q ||
      (p.name && p.name.toLowerCase().includes(q)) ||
      (p.nik && p.nik.toLowerCase().includes(q)) ||
      (p.medical_record_number && p.medical_record_number.toLowerCase().includes(q));

    const matchDate = !bd || p.birth_date === bd;
    return matchKeyword && matchDate;
  });

  patientResults.value = matches;
  hasSearchedPatient.value = true;

  if (matches.length === 0) {
    showToast({
      type: 'info',
      title: 'Data Tidak Ditemukan',
      message: 'Tidak ada data pasien yang cocok. Anda dapat mendaftarkan pasien baru.'
    });
  } else {
    showToast({
      type: 'success',
      title: 'Pencarian Berhasil',
      message: `Ditemukan ${matches.length} data pasien terdaftar.`
    });
  }
};

const handleResetSearch = () => {
  patientKeyword.value = '';
  patientBirthDate.value = '';
  patientResults.value = [];
  hasSearchedPatient.value = false;
  searchValidationError.value = '';
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

const handleViewPatientDetail = (patient) => {
  patientInDetail.value = patient;
  isPatientDetailOpen.value = true;
};

const handleAdmitFromSearch = (patient) => {
  isPatientDetailOpen.value = false;
  selectedPatientForAdmission.value = patient;
  isNewAdmissionOpen.value = true;
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

// Table Filter & Search Handlers (DAFTAR PASIEN)
const handleGuarantorChange = (val) => {
  filters.guarantor = val;
  pagination.currentPage = 1;
};

const handleTableSearchChange = (val) => {
  searchQuery.value = val;
  pagination.currentPage = 1;
};

const handleResetListFilter = () => {
  filters.guarantor = 'all';
  searchQuery.value = '';
  pagination.currentPage = 1;
};

// Tools Dropdown Action Handlers
const handleOpenEditAdmission = (item) => {
  activeAdmission.value = item;
  isEditAdmissionOpen.value = true;
};

const handleSaveEditAdmission = ({ id, payload }) => {
  const res = updateAdmission(id, payload);
  if (res.success) {
    isEditAdmissionOpen.value = false;
    showToast({
      type: 'success',
      title: 'Admisi Diperbarui',
      message: `Data admisi ${res.item.patient_name} berhasil diperbarui.`
    });
  }
};

const handleOpenDeleteAdmission = (item) => {
  activeAdmission.value = item;
  isDeleteDialogOpen.value = true;
};

const handleConfirmDeleteAdmission = () => {
  if (!activeAdmission.value) return;
  const res = deleteAdmission(activeAdmission.value.id);
  isDeleteDialogOpen.value = false;
  if (res.success) {
    if (paginatedAdmissions.value.length === 0 && pagination.currentPage > 1) {
      pagination.currentPage -= 1;
    }
    showToast({
      type: 'warning',
      title: 'Admisi Dihapus',
      message: `Data kunjungan admisi ${res.item.patient_name} (${res.item.queue_number}) telah dihapus dari daftar.`
    });
  }
  activeAdmission.value = null;
};

const handleOpenPrintDocument = ({ item, documentType }) => {
  activeAdmission.value = item;
  printDocType.value = documentType || 'No. Antrean';
  isPrintModalOpen.value = true;
};

const handleConfirmPrint = ({ documentType, admission }) => {
  isPrintModalOpen.value = false;
  showToast({
    type: 'success',
    title: 'Cetak Dokumen Diproses',
    message: `Dokumen ${documentType} untuk ${admission.patient_name} (${admission.medical_record_number}) berhasil dicetak (Simulasi).`
  });
};

const handleCloseNewAdmission = () => {
  isNewAdmissionOpen.value = false;
  selectedPatientForAdmission.value = null;
};

const handleSubmitNewAdmission = (newRecordData) => {
  const res = addAdmission(newRecordData);
  if (res.success) {
    isNewAdmissionOpen.value = false;
    selectedPatientForAdmission.value = null;
    if (filters.guarantor !== 'all' && filters.guarantor !== res.record.guarantor_type) {
      filters.guarantor = 'all';
    }
    searchQuery.value = '';
    pagination.currentPage = 1;
    showToast({
      type: 'success',
      title: 'Admisi Berhasil Didaftarkan',
      message: `${res.record.patient_name} berhasil ditambahkan ke daftar pasien (${res.record.polyclinic}).`
    });
  }
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
          :validation-error="searchValidationError"
          @search="handleSearchPatient"
          @reset="handleResetSearch"
          @register-new="handleNavigateRegisterNew"
          @open-kyc="handleOpenKyc"
          @view-detail="handleViewPatientDetail"
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

      <!-- Bottom Card: DAFTAR PASIEN -->
      <RegistrationPatientListCard
        :admissions="paginatedAdmissions"
        :total-count="paginationInfo.total"
        :current-page="paginationInfo.current_page"
        :last-page="paginationInfo.last_page"
        :per-page="pagination.perPage"
        :from="paginationInfo.from"
        :to="paginationInfo.to"
        :guarantor-filter="filters.guarantor"
        :table-search="searchQuery"
        :sort-key="sort.key"
        :sort-direction="sort.direction"
        :loading="loading"
        @update:guarantorFilter="handleGuarantorChange"
        @update:perPage="setPerPage"
        @update:tableSearch="handleTableSearchChange"
        @page-change="setPage"
        @sort="setSort"
        @edit-admission="handleOpenEditAdmission"
        @delete-admission="handleOpenDeleteAdmission"
        @print-document="handleOpenPrintDocument"
        @reset-filter="handleResetListFilter"
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

    <AdmissionNewModal
      :is-open="isNewAdmissionOpen"
      :polyclinics="mockPolyclinics"
      :doctors="mockDoctors"
      :next-queue-number="nextQueueNumber"
      :patient="selectedPatientForAdmission"
      @close="handleCloseNewAdmission"
      @submit="handleSubmitNewAdmission"
    />

    <AdmissionEditModal
      :is-open="isEditAdmissionOpen"
      :admission="activeAdmission"
      :polyclinics="mockPolyclinics"
      :doctors="mockDoctors"
      @close="isEditAdmissionOpen = false"
      @save="handleSaveEditAdmission"
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
  align-items: stretch;
}

@media (max-width: 1024px) {
  .top-cards-grid {
    grid-template-columns: 1fr;
  }
}
</style>
