<script setup>
import { ref } from 'vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import ExaminationStatsCards from './components/ExaminationStatsCards.vue';
import ExaminationFiltersCard from './components/ExaminationFiltersCard.vue';
import ExaminationTable from './components/ExaminationTable.vue';
import ExaminationPagination from './components/ExaminationPagination.vue';
import ExaminationAudioDisplay from './components/ExaminationAudioDisplay.vue';
import ExaminationStartModal from './components/ExaminationStartModal.vue';
import ExaminationRmeModal from './components/ExaminationRmeModal.vue';
import ExaminationResumeModal from './components/ExaminationResumeModal.vue';
import ExaminationCancelModal from './components/ExaminationCancelModal.vue';
import ExaminationDetailModal from './components/ExaminationDetailModal.vue';
import { useExaminations } from '@/composables/useExaminations';

const {
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
  applyFilters,
  resetFilters,
  setPage,
  setPerPage,
  refreshData,
  startExamination,
  enterClinic,
  callNextQueue,
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

// Modal states
const activePatient = ref(null);
const isStartModalOpen = ref(false);
const startModalMode = ref('start'); // 'start' or 'enter'
const isRmeModalOpen = ref(false);
const isResumeModalOpen = ref(false);
const isCancelModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const detailInitialTab = ref('patient');

// Header action 1: Refresh data
const handleRefresh = () => {
  refreshData();
  showToast({
    type: 'info',
    title: 'Data Diperbarui',
    message: 'Tampilan data antrean pemeriksaan poliklinik berhasil dimuat ulang secara lokal.'
  });
};

// Header action 2: Call next queue
const handleCallNextQueue = () => {
  const res = callNextQueue();
  if (res.success) {
    showToast({
      type: 'success',
      title: 'Memanggil Antrean Berikutnya',
      message: `Nomor antrean ${res.queueNumber} (${res.patientName}) dipanggil ke ${res.polyclinic} &bull; ${res.room} (Mode Simulasi).`
    });
  }
};

// Filter actions
const handleApplyFilter = () => {
  applyFilters();
  showToast({
    type: 'success',
    title: 'Filter Diterapkan',
    message: `Menampilkan ${paginationInfo.value.total} pasien sesuai kriteria filter.`
  });
};

const handleResetFilter = () => {
  resetFilters();
  showToast({
    type: 'info',
    title: 'Filter Direset',
    message: 'Kriteria pencarian dan filter poliklinik telah dikembalikan ke kondisi awal.'
  });
};

// Clinical Actions from Table
const handleStartExam = (patient) => {
  activePatient.value = patient;
  startModalMode.value = 'start';
  isStartModalOpen.value = true;
};

const handleEnterClinic = (patient) => {
  activePatient.value = patient;
  startModalMode.value = 'enter';
  isStartModalOpen.value = true;
};

const handleConfirmStartExam = (patient) => {
  const res = startExamination(patient.id);
  isStartModalOpen.value = false;
  if (res.success) {
    showToast({
      type: 'success',
      title: 'Pemeriksaan Dimulai',
      message: `Pasien ${res.patient.patient_name} (${res.patient.queue_number}) kini berstatus Sedang Diperiksa di ${res.patient.polyclinic}.`
    });
  }
};

const handleOpenRme = (patient) => {
  activePatient.value = patient;
  isRmeModalOpen.value = true;
};

const handleViewResume = (patient) => {
  activePatient.value = patient;
  isResumeModalOpen.value = true;
};

const handlePrintResume = (patient) => {
  isResumeModalOpen.value = false;
  showToast({
    type: 'success',
    title: 'Simulasi Cetak Resume Medis',
    message: `Dokumen Resume Medis ${patient.patient_name} (${patient.medical_record_number}) berhasil dikirim ke antrean cetak virtual.`
  });
};

const handleViewCancelDetail = (patient) => {
  activePatient.value = patient;
  isCancelModalOpen.value = true;
};

// Dropdown Actions from Table
const handleViewDetail = (patient) => {
  activePatient.value = patient;
  detailInitialTab.value = 'patient';
  isDetailModalOpen.value = true;
};

const handleViewQueueInfo = (patient) => {
  activePatient.value = patient;
  detailInitialTab.value = 'queue';
  isDetailModalOpen.value = true;
};

const handleViewHistory = (patient) => {
  activePatient.value = patient;
  detailInitialTab.value = 'history';
  isDetailModalOpen.value = true;
};

// Bottom Audio Display Action
const handleReplayAudio = () => {
  const res = replayQueueAudio();
  if (res.success && res.patient) {
    showToast({
      type: 'info',
      title: 'Panggilan Dibunyikan Ulang',
      message: `Panggilan suara antrean ${res.queueNumber} (${res.patientName}) telah disimulasikan ke speaker ${res.polyclinic}.`
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

    <!-- Header Section (Breadcrumb: Pelayanan Klinis → Rawat Jalan → Pemeriksaan Poliklinik) -->
    <MasterDataPageHeader
      title="Pemeriksaan Rawat Jalan"
      badge="Poliklinik Terpadu"
      subtitle="Kelola antrean dan pemeriksaan pasien yang telah menyelesaikan admisi poliklinik secara realtime dengan bridging SatuSehat & BPJS."
      :breadcrumbs="[
        { label: 'Pelayanan Klinis', active: false },
        { label: 'Rawat Jalan', active: false },
        { label: 'Pemeriksaan Poliklinik', active: true }
      ]"
    >
      <template #actions>
        <div class="header-action-buttons">
          <!-- A. Indikator Sinkronisasi (Mode Demo) -->
          <div class="sync-indicator-header" title="Bridging SatuSehat & BPJS dalam mode simulasi">
            <span class="sync-dot-anim"></span>
            <div class="sync-text-group">
              <span class="sync-main-label">Auto-Sync Realtime</span>
              <span class="sync-sub-label">Mode Demo — Tidak Terhubung</span>
            </div>
          </div>

          <!-- B. Tombol Perbarui Data -->
          <button
            type="button"
            class="btn-secondary btn-header-action"
            @click="handleRefresh"
            :disabled="loading"
            aria-label="Perbarui Data Antrean"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="{ 'spin-anim': loading }">
              <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
            </svg>
            <span>Perbarui Data</span>
          </button>

          <!-- C. Tombol Panggil Antrean Berikut -->
          <button
            type="button"
            class="btn-primary btn-header-action btn-call-queue"
            @click="handleCallNextQueue"
            :disabled="loading"
            aria-label="Panggil Antrean Berikut"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
              <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
            </svg>
            <span>Panggil Antrean Berikut</span>
          </button>
        </div>
      </template>
    </MasterDataPageHeader>

    <!-- Page Content Flow -->
    <div class="page-content">
      <!-- 1. Empat Card Statistik Pemeriksaan Rawat Jalan -->
      <ExaminationStatsCards :stats="statistics" />

      <!-- 2. Bagian Pencarian dan Multi-Filter Pemeriksaan -->
      <ExaminationFiltersCard
        v-model:searchQuery="searchQuery"
        :filters="filters"
        :polyclinics="mockPolyclinics"
        :doctors="mockDoctors"
        :period-options="mockPeriodOptions"
        :status-options="mockStatusOptions"
        :guarantor-options="mockGuarantorOptions"
        :loading="loading"
        @apply="handleApplyFilter"
        @reset="handleResetFilter"
      />

      <!-- 3. Tabel Pemeriksaan Rawat Jalan (11 Kolom Urutan Tetap) -->
      <ExaminationTable
        :examinations="paginatedExaminations"
        :total-count="paginationInfo.total"
        :active-queue-count="paginationInfo.activeQueueCount"
        :current-page="pagination.currentPage"
        :per-page="pagination.perPage"
        :loading="loading"
        :error="error"
        @start-exam="handleStartExam"
        @enter-clinic="handleEnterClinic"
        @open-rme="handleOpenRme"
        @view-resume="handleViewResume"
        @view-cancel-detail="handleViewCancelDetail"
        @view-detail="handleViewDetail"
        @view-queue-info="handleViewQueueInfo"
        @view-history="handleViewHistory"
        @reset-filter="handleResetFilter"
        @refresh="handleRefresh"
      />

      <!-- 4. Paginasi Tabel -->
      <ExaminationPagination
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

      <!-- 5. Display Pemanggil Suara Poliklinik (Speaker Ruang Antrean) -->
      <ExaminationAudioDisplay
        :last-called-patient="lastCalledPatient"
        @replay-audio="handleReplayAudio"
      />
    </div>

    <!-- Modals & Dialogs -->
    <!-- Modal 1: Konfirmasi Mulai Pemeriksaan / Masuk Poli -->
    <ExaminationStartModal
      :is-open="isStartModalOpen"
      :patient="activePatient"
      :mode="startModalMode"
      @close="isStartModalOpen = false"
      @confirm="handleConfirmStartExam"
    />

    <!-- Modal 2: Placeholder RME (Rekam Medis Elektronik) -->
    <ExaminationRmeModal
      :is-open="isRmeModalOpen"
      :patient="activePatient"
      @close="isRmeModalOpen = false"
    />

    <!-- Modal 3: Pratinjau Resume Medis -->
    <ExaminationResumeModal
      :is-open="isResumeModalOpen"
      :patient="activePatient"
      @close="isResumeModalOpen = false"
      @print-simulated="handlePrintResume"
    />

    <!-- Modal 4: Detail Pembatalan Pasien -->
    <ExaminationCancelModal
      :is-open="isCancelModalOpen"
      :patient="activePatient"
      @close="isCancelModalOpen = false"
    />

    <!-- Modal 5: Detail Pasien & Informasi Antrean -->
    <ExaminationDetailModal
      :is-open="isDetailModalOpen"
      :patient="activePatient"
      :initial-tab="detailInitialTab"
      @close="isDetailModalOpen = false"
    />
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

.header-action-buttons {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.sync-indicator-header {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.35rem 0.75rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.sync-dot-anim {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #94a3b8;
  position: relative;
}

.sync-text-group {
  display: flex;
  flex-direction: column;
}

.sync-main-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: #334155;
  line-height: 1.1;
}

.sync-sub-label {
  font-size: 0.65rem;
  color: #94a3b8;
  line-height: 1.1;
}

.btn-header-action {
  height: 40px;
  padding: 0 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.btn-header-action svg {
  width: 16px;
  height: 16px;
}

.btn-secondary {
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
  color: #0f172a;
  border-color: #94a3b8;
}

.btn-call-queue {
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: none;
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.2);
}

.btn-call-queue:hover:not(:disabled) {
  background: var(--color-primary-dark, #06635e);
  box-shadow: 0 4px 12px rgba(8, 127, 120, 0.3);
}

.spin-anim {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 768px) {
  .header-action-buttons {
    width: 100%;
    flex-direction: column;
    align-items: stretch;
  }
  .sync-indicator-header {
    justify-content: center;
  }
  .btn-header-action {
    justify-content: center;
  }
}
</style>
