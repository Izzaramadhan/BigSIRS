<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import ExaminationDailyListCard from './components/ExaminationDailyListCard.vue';
import { useExaminations } from '@/composables/useExaminations';

const router = useRouter();

const {
  searchQuery,
  filters,
  pagination,
  sort,
  loading,
  paginatedExaminations,
  paginationInfo,
  mockPolyclinics,
  applyFilters,
  resetFilters,
  setSort,
  setPage,
  setPerPage,
  startExamination,
  replayQueueAudio
} = useExaminations();

// Toast notification state
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

// Filter actions
const handleApplySearch = () => {
  applyFilters();
  showToast({
    type: 'success',
    title: 'Data Kunjungan Ditampilkan',
    message: `Ditemukan ${paginationInfo.value.total} kunjungan pasien sesuai kriteria filter.`
  });
};

const handleResetFilter = () => {
  resetFilters();
  showToast({
    type: 'info',
    title: 'Filter Direset',
    message: 'Kriteria pencarian, periode tanggal admisi, dan poliklinik telah dikembalikan ke kondisi awal.'
  });
};

// Table Row Action 1 (+): Mulai Pemeriksaan & Buka Halaman Pemeriksaan Pasien
const handleStartRme = (patient) => {
  if (patient.status === 'Menunggu' || patient.status === 'Dipanggil') {
    startExamination(patient.id);
  }
  router.push({
    name: 'clinical.examination-patient',
    params: { id: patient.id }
  });
};

// Table Row Action 2 (Speaker): Panggil Antrean Pasien
const handleCallPatient = (patient) => {
  if (patient.status === 'Menunggu') {
    patient.status = 'Dipanggil';
  }
  const res = replayQueueAudio(patient);
  if (res.success && res.patient) {
    showToast({
      type: 'success',
      title: 'Memanggil Pasien',
      message: `Nomor antrean ${res.queueNumber} (${res.patientName}) dipanggil ke ${res.polyclinic} (${res.room}).`
    });
  }
};
</script>

<template>
  <div class="page-container examination-page">
    <!-- Toast Notification System -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Header Section -->
    <MasterDataPageHeader
      title="Pemeriksaan Pasien"
      subtitle="Daftar kunjungan pasien hari ini beserta filter periode tanggal admisi, klinik, dan aksi pemeriksaan klinis."
      :breadcrumbs="[
        { label: 'Home', active: false },
        { label: 'Pemeriksaan Pasien', active: true }
      ]"
    >
      <template #badge>
        <span class="header-visit-count-badge" title="Total kunjungan pasien sesuai filter">
          {{ paginationInfo.total }}
        </span>
      </template>
    </MasterDataPageHeader>

    <!-- Main Page Content -->
    <div class="page-content">
      <ExaminationDailyListCard
        v-model:searchQuery="searchQuery"
        :examinations="paginatedExaminations"
        :total-count="paginationInfo.total"
        :current-page="pagination.currentPage"
        :last-page="paginationInfo.last_page"
        :per-page="pagination.perPage"
        :from="paginationInfo.from"
        :to="paginationInfo.to"
        :filters="filters"
        :polyclinics="mockPolyclinics"
        :sort-key="sort.key"
        :sort-direction="sort.direction"
        :loading="loading"
        @update:perPage="setPerPage"
        @page-change="setPage"
        @sort="setSort"
        @search="handleApplySearch"
        @reset="handleResetFilter"
        @start-rme="handleStartRme"
        @call-patient="handleCallPatient"
      />
    </div>
  </div>
</template>

<style scoped>
.examination-page {
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

.header-visit-count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 0.55rem;
  border-radius: 9999px;
  background: #e11d48;
  color: #ffffff;
  font-size: 0.8rem;
  font-weight: 700;
  box-shadow: 0 2px 6px rgba(225, 29, 72, 0.25);
}
</style>
