<script setup>
import { ref } from 'vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import PatientSearchFilters from './components/PatientSearchFilters.vue';
import PatientSearchTable from './components/PatientSearchTable.vue';
import PatientPagination from './components/PatientPagination.vue';
import PatientDetailModal from './components/PatientDetailModal.vue';
import NewPatientModal from './components/NewPatientModal.vue';
import { usePatientSearch } from '@/composables/usePatientSearch';

const {
  paginatedPatients,
  paginationInfo,
  pagination,
  searchQuery,
  validationError,
  filters,
  isAdvancedFilterOpen,
  loading,
  selectedPatient,
  search,
  resetFilters,
  setPage,
  setPerPage,
  selectPatient,
  toggleAdvancedFilter
} = usePatientSearch();

// Modals State
const isDetailModalOpen = ref(false);
const isRegisterModalOpen = ref(false);
const patientInDetail = ref(null);

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

// Search Handler
const handleSearch = () => {
  const result = search();

  if (!result.success && result.reason === 'validation_error') {
    showToast({
      type: 'warning',
      title: 'Pencarian Belum Sesuai',
      message: result.message
    });
    return;
  }

  if (result.count === 0) {
    showToast({
      type: 'info',
      title: 'Data Tidak Ditemukan',
      message: 'Tidak ada data pasien yang sesuai dengan kata kunci atau filter yang Anda masukkan.'
    });
  } else {
    showToast({
      type: 'success',
      title: 'Pencarian Berhasil',
      message: `Ditemukan ${result.count} data pasien yang cocok.`
    });
  }
};

// Reset Search & Filters Handler
const handleReset = () => {
  resetFilters();
  showToast({
    type: 'info',
    title: 'Filter Direset',
    message: 'Form pencarian dan filter lanjutan telah dikembalikan ke kondisi awal.'
  });
};

// Open Detail Modal Handler
const handleViewDetail = (patient) => {
  patientInDetail.value = patient;
  isDetailModalOpen.value = true;
};

// Close Detail Modal Handler
const handleCloseDetail = () => {
  isDetailModalOpen.value = false;
  patientInDetail.value = null;
};

// Select Patient Handler (Simulation for admission/clinical service)
const handleSelectPatient = (patient) => {
  selectPatient(patient);
  
  // If detail modal was open, close it
  if (isDetailModalOpen.value) {
    isDetailModalOpen.value = false;
  }

  showToast({
    type: 'success',
    title: 'Pasien Berhasil Dipilih',
    message: `${patient.name} (${patient.medical_record_number}) berhasil dipilih untuk simulasi admisi/pelayanan.`
  });
};

// Clear currently selected patient
const handleClearSelectedPatient = () => {
  selectedPatient.value = null;
  showToast({
    type: 'info',
    title: 'Pilihan Dibatalkan',
    message: 'Pilihan pasien aktif telah dibatalkan.'
  });
};

// Open New Patient Registration Modal
const handleOpenRegister = () => {
  isRegisterModalOpen.value = true;
};

const handleCloseRegister = () => {
  isRegisterModalOpen.value = false;
};
</script>

<template>
  <div class="page-container patient-search-page">
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
      title="Pendaftaran Pasien"
      subtitle="Cari pasien yang sudah terdaftar atau tambahkan pasien baru ke dalam sistem."
      :breadcrumbs="[
        { label: 'Rawat Jalan', active: false },
        { label: 'Pendaftaran', active: false },
        { label: 'Pencarian Pasien', active: true }
      ]"
    >
      <template #actions>
        <button 
          type="button" 
          class="btn-primary btn-add-patient" 
          @click="handleOpenRegister"
          aria-label="Registrasi Pasien Baru"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <line x1="19" y1="8" x2="19" y2="14"></line>
            <line x1="22" y1="11" x2="16" y2="11"></line>
          </svg>
          <span>+ Registrasi Pasien Baru</span>
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Selected Patient Simulation Banner (if a patient is currently picked) -->
    <Transition name="fade">
      <div v-if="selectedPatient" class="selected-patient-banner" role="status">
        <div class="banner-left">
          <div class="banner-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
          <div class="banner-text">
            <span class="banner-title">Pasien Aktif Terpilih (Simulasi Admisi):</span>
            <span class="banner-patient-name">{{ selectedPatient.name }}</span>
            <span class="banner-rm-badge">{{ selectedPatient.medical_record_number }}</span>
            <span class="banner-nik">NIK: {{ selectedPatient.nik }}</span>
          </div>
        </div>

        <div class="banner-actions">
          <button 
            type="button" 
            class="btn-banner-detail" 
            @click="handleViewDetail(selectedPatient)"
          >
            Lihat Detail
          </button>
          <button 
            type="button" 
            class="btn-banner-clear" 
            @click="handleClearSelectedPatient"
            title="Batalkan Pilihan"
          >
            Batal Pilih
          </button>
        </div>
      </div>
    </Transition>

    <!-- Page Content -->
    <div class="page-content">
      <!-- 1. Card Pencarian Data Pasien -->
      <PatientSearchFilters
        v-model:searchQuery="searchQuery"
        :filters="filters"
        :is-open="isAdvancedFilterOpen"
        :validation-error="validationError"
        :loading="loading"
        @search="handleSearch"
        @toggle-advanced="toggleAdvancedFilter"
        @reset="handleReset"
      />

      <!-- 2. Card Tabel Hasil Pencarian Pasien -->
      <PatientSearchTable
        :patients="paginatedPatients"
        :total-count="paginationInfo.total"
        :current-page="pagination.currentPage"
        :per-page="pagination.perPage"
        :loading="loading"
        :selected-patient-id="selectedPatient?.id"
        @view-detail="handleViewDetail"
        @select-patient="handleSelectPatient"
        @open-register="handleOpenRegister"
        @reset-search="handleReset"
      />

      <!-- 3. Pagination Section -->
      <PatientPagination
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

    <!-- Modals -->
    <!-- Modal Detail Pasien -->
    <PatientDetailModal
      :is-open="isDetailModalOpen"
      :patient="patientInDetail"
      @close="handleCloseDetail"
      @select="handleSelectPatient"
    />

    <!-- Modal Registrasi Pasien Baru (Placeholder) -->
    <NewPatientModal
      :is-open="isRegisterModalOpen"
      @close="handleCloseRegister"
    />
  </div>
</template>

<style scoped>
.patient-search-page {
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

.btn-add-patient {
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.2);
}

.btn-add-patient:hover {
  box-shadow: 0 4px 12px rgba(8, 127, 120, 0.3);
}

/* Selected Patient Banner */
.selected-patient-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  background: linear-gradient(135deg, #dff7f4 0%, #edfcf9 100%);
  border: 1px solid #a7eedf;
  border-radius: 10px;
  padding: 0.85rem 1.25rem;
  margin-bottom: 0.5rem;
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.05);
}

.banner-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.banner-icon {
  width: 28px;
  height: 28px;
  background: var(--color-primary);
  color: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.banner-icon svg {
  width: 16px;
  height: 16px;
}

.banner-text {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.875rem;
  flex-wrap: wrap;
}

.banner-title {
  color: var(--color-primary-dark);
  font-weight: 600;
}

.banner-patient-name {
  color: var(--color-text-navy);
  font-weight: 700;
}

.banner-rm-badge {
  background: var(--color-primary);
  color: #ffffff;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.banner-nik {
  color: var(--color-text-secondary);
  font-size: 0.8rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.banner-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-banner-detail {
  background: #ffffff;
  border: 1px solid var(--color-primary);
  color: var(--color-primary);
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-banner-detail:hover {
  background: var(--color-primary-light);
}

.btn-banner-clear {
  background: transparent;
  border: 1px solid #cbd5e1;
  color: var(--color-text-secondary);
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-banner-clear:hover {
  background: #f1f5f9;
  color: var(--color-text-navy);
}

/* Transition for Banner */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

@media (max-width: 640px) {
  .selected-patient-banner {
    flex-direction: column;
    align-items: stretch;
  }

  .banner-actions {
    justify-content: flex-end;
  }
}
</style>
