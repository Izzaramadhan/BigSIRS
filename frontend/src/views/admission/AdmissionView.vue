<script setup>
import { ref } from 'vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import AdmissionStatsCards from './components/AdmissionStatsCards.vue';
import AdmissionQueueCard from './components/AdmissionQueueCard.vue';
import AdmissionFiltersCard from './components/AdmissionFiltersCard.vue';
import AdmissionTable from './components/AdmissionTable.vue';
import AdmissionPagination from './components/AdmissionPagination.vue';
import AdmissionDetailModal from './components/AdmissionDetailModal.vue';
import AdmissionEditModal from './components/AdmissionEditModal.vue';
import AdmissionPrintModal from './components/AdmissionPrintModal.vue';
import AdmissionCancelDialog from './components/AdmissionCancelDialog.vue';
import AdmissionNewModal from './components/AdmissionNewModal.vue';
import { useAdmissions } from '@/composables/useAdmissions';

const {
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
} = useAdmissions();

// Modal States
const isDetailOpen = ref(false);
const isEditOpen = ref(false);
const isPrintOpen = ref(false);
const isCancelOpen = ref(false);
const isNewOpen = ref(false);

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

// Queue Actions
const handleCallCurrent = () => {
  const res = callCurrentQueue();
  showToast({
    type: 'success',
    title: 'Memanggil Antrean',
    message: `Nomor antrean ${res.queueNumber} (${res.patientName}) dipanggil ke Loket Admisi 02.`
  });
};

const handleCallNext = () => {
  const res = callNextQueue();
  showToast({
    type: 'success',
    title: 'Memanggil Antrean Berikutnya',
    message: `Nomor antrean ${res.queueNumber} (${res.patientName}) dipanggil ke Loket Admisi 02.`
  });
};

const handlePrevQueue = () => {
  prevQueue();
  showToast({
    type: 'info',
    title: 'Nomor Antrean Mundur',
    message: `Nomor antrean aktif diubah ke ${currentQueueNumber.value}.`
  });
};

const handleNextQueue = () => {
  nextQueue();
  showToast({
    type: 'info',
    title: 'Nomor Antrean Maju',
    message: `Nomor antrean aktif diubah ke ${currentQueueNumber.value}.`
  });
};

// Filter Actions
const handleApplyFilter = () => {
  applyFilters();
  showToast({
    type: 'success',
    title: 'Filter Diterapkan',
    message: `Menampilkan ${paginationInfo.value.total} data admisi yang sesuai.`
  });
};

const handleResetFilter = () => {
  resetFilters();
  showToast({
    type: 'info',
    title: 'Filter Direset',
    message: 'Semua filter pencarian dan periode telah dikembalikan ke kondisi awal.'
  });
};

// Table Dropdown Action Handlers
const handleViewDetail = (item) => {
  activeAdmission.value = item;
  isDetailOpen.value = true;
};

const handleOpenEdit = (item) => {
  activeAdmission.value = item;
  isEditOpen.value = true;
};

const handleSaveEdit = ({ id, payload }) => {
  const res = updateAdmission(id, payload);
  if (res.success) {
    isEditOpen.value = false;
    showToast({
      type: 'success',
      title: 'Data Admisi Diperbarui',
      message: `Perubahan data admisi ${res.item.patient_name} berhasil disimpan.`
    });
  }
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

const handleOpenCancel = (item) => {
  activeAdmission.value = item;
  isCancelOpen.value = true;
};

const handleConfirmCancel = ({ id, reason }) => {
  const res = cancelAdmission(id, reason);
  if (res.success) {
    isCancelOpen.value = false;
    showToast({
      type: 'warning',
      title: 'Admisi Dibatalkan',
      message: `Pendaftaran admisi ${res.item.patient_name} (${res.item.queue_number}) telah dibatalkan.`
    });
  }
};

// New Admission Handlers
const handleOpenNew = () => {
  isNewOpen.value = true;
};

const handleSubmitNew = (newRecordData) => {
  const res = addAdmission(newRecordData);
  if (res.success) {
    isNewOpen.value = false;
    showToast({
      type: 'success',
      title: 'Admisi Baru Berhasil Didaftarkan',
      message: `Pasien ${res.record.patient_name} berhasil didaftarkan dengan No. Antrean ${res.record.queue_number}.`
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

    <!-- Header Section (Breadcrumb: Rawat Jalan → Admisi) -->
    <MasterDataPageHeader
      title="Admisi Rawat Jalan"
      subtitle="Kelola antrean dan pendaftaran pasien yang akan menerima pelayanan medis di seluruh poliklinik."
      :breadcrumbs="[
        { label: 'Rawat Jalan', active: false },
        { label: 'Admisi', active: true }
      ]"
    >
      <template #actions>
        <button 
          type="button" 
          class="btn-primary btn-add-admission" 
          @click="handleOpenNew"
          aria-label="Tambah Admisi Pasien Baru"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          <span>+ Tambah Admisi</span>
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Page Content Flow -->
    <div class="page-content">
      <!-- 1. Empat Kartu Statistik Admisi -->
      <AdmissionStatsCards :stats="statistics" />

      <!-- 2. Informasi Loket Admisi dan Antrean -->
      <AdmissionQueueCard
        :loket="loket"
        :current-queue="currentQueueNumber"
        :next-queue="nextQueueNumber"
        @call-current="handleCallCurrent"
        @call-next="handleCallNext"
        @prev-queue="handlePrevQueue"
        @next-queue="handleNextQueue"
      />

      <!-- 3. Bagian Pencarian dan Filter Admisi -->
      <AdmissionFiltersCard
        v-model:searchQuery="searchQuery"
        :filters="filters"
        :polyclinics="mockPolyclinics"
        :doctors="mockDoctors"
        :guarantors="mockGuarantors"
        :loading="loading"
        @apply="handleApplyFilter"
        @reset="handleResetFilter"
      />

      <!-- 4. Tabel Daftar Admisi Rawat Jalan (11 Kolom Urutan Tetap) -->
      <AdmissionTable
        :admissions="paginatedAdmissions"
        :total-count="paginationInfo.total"
        :current-page="pagination.currentPage"
        :per-page="pagination.perPage"
        :loading="loading"
        @view-detail="handleViewDetail"
        @edit-admission="handleOpenEdit"
        @print-document="handleOpenPrint"
        @cancel-admission="handleOpenCancel"
        @reset-filter="handleResetFilter"
      />

      <!-- 5. Bagian Paginasi -->
      <AdmissionPagination
        :current-page="pagination.currentPage"
        :last-page="paginationInfo.last_page"
        :per-page="pagination.perPage"
        :total="paginationInfo.total"
        :from="paginationInfo.from"
        :to="paginationInfo.to"
        :loading="loading"
        @page-change="setPage"
        @update:perPage="setPerPage"
      />
    </div>

    <!-- Modals & Dialogs -->
    <!-- Modal 1: Detail Admisi -->
    <AdmissionDetailModal
      :is-open="isDetailOpen"
      :admission="activeAdmission"
      @close="isDetailOpen = false"
      @edit="handleOpenEdit"
      @print="handleOpenPrint"
    />

    <!-- Modal 2: Edit Data Admisi -->
    <AdmissionEditModal
      :is-open="isEditOpen"
      :admission="activeAdmission"
      :polyclinics="mockPolyclinics"
      :doctors="mockDoctors"
      @close="isEditOpen = false"
      @save="handleSaveEdit"
    />

    <!-- Modal 3: Pratinjau / Simulasi Cetak Dokumen -->
    <AdmissionPrintModal
      :is-open="isPrintOpen"
      :admission="activeAdmission"
      :document-type="printDocType"
      @close="isPrintOpen = false"
      @confirm-print="handleConfirmPrint"
    />

    <!-- Modal 4: Konfirmasi Pembatalan Admisi -->
    <AdmissionCancelDialog
      :is-open="isCancelOpen"
      :admission="activeAdmission"
      @close="isCancelOpen = false"
      @confirm="handleConfirmCancel"
    />

    <!-- Modal 5: Tambah Admisi Baru -->
    <AdmissionNewModal
      :is-open="isNewOpen"
      :polyclinics="mockPolyclinics"
      :doctors="mockDoctors"
      :next-queue-number="nextQueueNumber"
      @close="isNewOpen = false"
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

.btn-add-admission {
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.2);
}

.btn-add-admission:hover {
  box-shadow: 0 4px 12px rgba(8, 127, 120, 0.3);
}
</style>
