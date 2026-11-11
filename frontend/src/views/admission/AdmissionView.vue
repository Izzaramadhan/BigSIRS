<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';
import AdmissionOldPatientSearchCard from './components/AdmissionOldPatientSearchCard.vue';
import AdmissionPatientListCard from './components/AdmissionPatientListCard.vue';
import AdmissionPrintModal from './components/AdmissionPrintModal.vue';
import AdmissionNewModal from './components/AdmissionNewModal.vue';
import { useAdmissions } from '@/composables/useAdmissions';
import { mockPatients } from '@/mocks/patients';

const router = useRouter();

const {
  paginatedAdmissions,
  paginationInfo,
  pagination,
  filters,
  sort,
  nextQueueNumber,
  loading,
  mockPolyclinics,
  mockDoctors,
  setSort,
  setPage,
  setPerPage,
  deleteAdmission,
  addAdmission
} = useAdmissions();

// Form Pencarian Pasien Lama state (Frontend local simulation)
const oldPatientQuery = ref('');
const oldPatientBirthDate = ref('');
const oldPatientResults = ref([]);
const hasSearchedOldPatient = ref(false);
const searchValidationError = ref('');

// Modal States
const isPrintOpen = ref(false);
const isDeleteOpen = ref(false);
const isNewOpen = ref(false);

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

// Pencarian Pasien Lama Handlers
const handleSearchOldPatient = () => {
  const q = oldPatientQuery.value.trim().toLowerCase();
  const bd = oldPatientBirthDate.value.trim();

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

  const matches = mockPatients.filter((p) => {
    const matchesKeyword = !q
      || (p.name && p.name.toLowerCase().includes(q))
      || (p.nik && p.nik.toLowerCase().includes(q))
      || (p.medical_record_number && p.medical_record_number.toLowerCase().includes(q));

    const matchesDate = !bd || p.birth_date === bd;

    return matchesKeyword && matchesDate;
  });

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
};

const handleResetOldPatientSearch = () => {
  oldPatientQuery.value = '';
  oldPatientBirthDate.value = '';
  oldPatientResults.value = [];
  hasSearchedOldPatient.value = false;
  searchValidationError.value = '';
};

const handleSelectOldPatient = (patient) => {
  selectedOldPatient.value = patient;
  isNewOpen.value = true;
};

const handleRegisterNewPatient = () => {
  router.push('/pendaftaran/baru');
};

// Filter Asuransi di Daftar Pasien
const handleGuarantorChange = (val) => {
  filters.guarantor = val;
  pagination.currentPage = 1;
};

const handleResetGuarantor = () => {
  filters.guarantor = 'all';
  pagination.currentPage = 1;
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

const handleSubmitNew = (newRecordData) => {
  const res = addAdmission(newRecordData);
  if (res.success) {
    isNewOpen.value = false;
    selectedOldPatient.value = null;
    if (filters.guarantor !== 'all' && filters.guarantor !== res.record.guarantor_type) {
      filters.guarantor = 'all';
    }
    pagination.currentPage = 1;
    showToast({
      type: 'success',
      title: 'Admisi Berhasil Didaftarkan',
      message: `Pasien ${res.record.patient_name} berhasil didaftarkan ke ${res.record.polyclinic}.`
    });
  }
};
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
        :validation-error="searchValidationError"
        @search="handleSearchOldPatient"
        @reset="handleResetOldPatientSearch"
        @select-patient="handleSelectOldPatient"
        @register-new="handleRegisterNewPatient"
      />

      <!-- Card 2: DAFTAR PASIEN -->
      <AdmissionPatientListCard
        :admissions="paginatedAdmissions"
        :total-count="paginationInfo.total"
        :current-page="paginationInfo.current_page"
        :last-page="paginationInfo.last_page"
        :per-page="pagination.perPage"
        :from="paginationInfo.from"
        :to="paginationInfo.to"
        :guarantor-filter="filters.guarantor"
        :sort-key="sort.key"
        :sort-direction="sort.direction"
        :loading="loading"
        @update:guarantorFilter="handleGuarantorChange"
        @update:perPage="setPerPage"
        @page-change="setPage"
        @sort="setSort"
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
      :polyclinics="mockPolyclinics"
      :doctors="mockDoctors"
      :next-queue-number="nextQueueNumber"
      :patient="selectedOldPatient"
      @close="handleCloseNewModal"
      @submit="handleSubmitNew"
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
