<script setup>
import { ref, onMounted } from 'vue';
import { useMutations } from '@/composables/useMutations';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import MutationSummaryCards from './components/MutationSummaryCards.vue';
import MutationFilters from './components/MutationFilters.vue';
import MutationAuditBanner from './components/MutationAuditBanner.vue';
import MutationTable from './components/MutationTable.vue';
import MutationFormModal from './components/MutationFormModal.vue';
import MutationDetailModal from './components/MutationDetailModal.vue';

const {
  warehouses,
  medicines,
  filters,
  paginationInfo,
  loading,
  submitting,
  summary,
  paginatedMutations,
  fetchInitialData,
  setPage,
  setPerPage,
  applyFilters,
  resetFilters,
  addMutation,
  getMutationDetail,
  exportMutationsCsv
} = useMutations();

onMounted(async () => {
  await fetchInitialData();
});

// Modals
const isFormModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const selectedMutation = ref(null);
const formErrors = ref({});

// Toast
const toast = ref({
  show: false,
  type: 'success',
  title: '',
  message: ''
});

const showToast = ({ type = 'success', title, message }) => {
  toast.value = { show: true, type, title, message };
};

const closeToast = () => {
  toast.value.show = false;
};

// Handlers
const openAddModal = () => {
  formErrors.value = {};
  isFormModalOpen.value = true;
};

const handleOpenDetail = async (mutation) => {
  selectedMutation.value = mutation;
  isDetailModalOpen.value = true;
  try {
    const detail = await getMutationDetail(mutation.id);
    if (detail) {
      selectedMutation.value = detail;
    }
  } catch (err) {
    // Keep local table item data if detail API fails
  }
};

const handleApplyFilters = (payload) => {
  if (payload) {
    Object.assign(filters, payload);
  }
  applyFilters();
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};
  const res = await addMutation(payload);
  if (res.success) {
    isFormModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Mutasi barang berhasil disimpan dan stok telah diperbarui.'
    });
  } else {
    if (res.errors) {
      formErrors.value = res.errors;
    }
    showToast({
      type: 'error',
      title: 'Gagal',
      message: res.message || 'Gagal menyimpan mutasi barang.'
    });
  }
};

const handlePrint = () => {
  window.print();
};

const handleExport = async () => {
  try {
    await exportMutationsCsv();
    showToast({
      type: 'success',
      title: 'Ekspor Berhasil',
      message: 'Data mutasi barang berhasil diekspor ke file CSV.'
    });
  } catch (err) {
    showToast({
      type: 'error',
      title: 'Ekspor Gagal',
      message: 'Gagal mengekspor data mutasi barang.'
    });
  }
};
</script>

<template>
  <div class="page-container mutation-page">
    <!-- Notification Toast -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- 1. Header Halaman -->
    <MasterDataPageHeader
      title="Mutasi Barang"
      subtitle="Kelola perpindahan obat dan sediaan antarunit penyimpanan."
      :breadcrumbs="[
        { label: 'Logistik Farmasi', active: false },
        { label: 'Mutasi Barang', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Mutasi
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Page Content Flow -->
    <div class="page-content">
      <!-- 2. Kartu Ringkasan -->
      <MutationSummaryCards :summary="summary" />

      <!-- 3. Bagian Pencarian & Filter -->
      <MutationFilters
        :filters="filters"
        :warehouses="warehouses"
        :loading="loading"
        @apply="handleApplyFilters"
        @reset="resetFilters"
      />

      <!-- 4. Informasi Audit Trail -->
      <MutationAuditBanner />

      <!-- 5. Tabel Daftar Mutasi -->
      <MutationTable
        :items="paginatedMutations"
        :pagination-info="paginationInfo"
        :loading="loading"
        @view="handleOpenDetail"
        @page-change="setPage"
        @per-page-change="setPerPage"
        @print="handlePrint"
        @export="handleExport"
        @reset-filter="resetFilters"
      />
    </div>

    <!-- 6. Modal Tambah Mutasi -->
    <MutationFormModal
      :is-open="isFormModalOpen"
      :warehouses="warehouses"
      :medicines="medicines"
      :api-errors="formErrors"
      :is-submitting="submitting"
      @close="isFormModalOpen = false"
      @submit="handleFormSubmit"
    />

    <!-- 7. Modal Detail Mutasi -->
    <MutationDetailModal
      :is-open="isDetailModalOpen"
      :mutation="selectedMutation"
      @close="isDetailModalOpen = false"
    />
  </div>
</template>

<style scoped>
.mutation-page {
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

@media print {
  :deep(.app-header),
  :deep(.app-sidebar),
  :deep(.page-header),
  .mutation-summary-cards,
  .mutation-filters,
  .mutation-audit-banner {
    display: none !important;
  }

  .page-container {
    padding: 0 !important;
    background: transparent !important;
  }
}
</style>
