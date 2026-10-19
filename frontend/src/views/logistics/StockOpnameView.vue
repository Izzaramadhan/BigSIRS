<script setup>
import { ref } from 'vue';
import { useStockOpnames } from '@/composables/useStockOpnames';
import AppToast from '@/components/common/AppToast.vue';
import StockOpnameSummaryCards from './components/StockOpnameSummaryCards.vue';
import StockOpnameFilters from './components/StockOpnameFilters.vue';
import StockOpnameTable from './components/StockOpnameTable.vue';
import StockOpnameFormModal from './components/StockOpnameFormModal.vue';
import StockOpnameDetailModal from './components/StockOpnameDetailModal.vue';

const {
  warehouses,
  opnameTypes,
  opnameStatuses,
  filters,
  paginationInfo,
  loading,
  submitting,
  summary,
  paginatedOpnames,
  applyFilters,
  resetFilters,
  setPage,
  setPerPage,
  createStockOpname,
  cancelStockOpname,
  approveStockOpname,
  exportStockOpnamesCsv
} = useStockOpnames();

// Modal States
const isFormModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const selectedOpname = ref(null);

// Toast Notification State
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

// Actions & Handlers
const openAddModal = () => {
  isFormModalOpen.value = true;
};

const handleOpenDetail = (opname) => {
  selectedOpname.value = opname;
  isDetailModalOpen.value = true;
};

const handleOpenReview = (opname) => {
  selectedOpname.value = opname;
  isDetailModalOpen.value = true;
};

const handleOpenReason = (opname) => {
  selectedOpname.value = opname;
  isDetailModalOpen.value = true;
};

const handleContinue = (opname) => {
  selectedOpname.value = opname;
  isDetailModalOpen.value = true;
  showToast({
    type: 'info',
    title: 'Lanjutkan Sesi',
    message: `Membuka sesi penghitungan fisik untuk ${opname.opnameNumber}.`
  });
};

const handleStartDraft = async (opname) => {
  opname.status = 'Sedang Dihitung';
  opname.progressPercent = 10;
  opname.progressText = '10% – Sedang Dihitung';
  opname.differenceStatus = 'Belum Dihitung';
  showToast({
    type: 'success',
    title: 'Sesi Dimulai',
    message: `Sesi stok opname ${opname.opnameNumber} berhasil dimulai.`
  });
};

const handleCancelSession = async (opname) => {
  await cancelStockOpname(opname.id, 'Dibatalkan melalui aksi tabel.');
  if (selectedOpname.value?.id === opname.id) {
    isDetailModalOpen.value = false;
  }
  showToast({
    type: 'success',
    title: 'Sesi Dibatalkan',
    message: `Sesi stok opname ${opname.opnameNumber} berhasil dibatalkan.`
  });
};

const handleDeleteDraft = (opname) => {
  cancelStockOpname(opname.id, 'Draft dihapus.');
  showToast({
    type: 'success',
    title: 'Draft Dihapus',
    message: `Draft sesi stok opname ${opname.opnameNumber} telah dihapus.`
  });
};

const handleApproveDiscrepancy = async (opname) => {
  await approveStockOpname(opname.id);
  isDetailModalOpen.value = false;
  showToast({
    type: 'success',
    title: 'Penyesuaian Disetujui',
    message: `Penyesuaian saldo stok opname ${opname.opnameNumber} berhasil disetujui.`
  });
};

const handleFormSubmit = async ({ isDraft, ...payload }) => {
  const res = await createStockOpname(payload, isDraft);
  if (res.success) {
    isFormModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isDraft
        ? 'Draft stok opname berhasil disimpan.'
        : 'Sesi stok opname berhasil dimulai.'
    });
  }
};

const handlePrint = (opname) => {
  showToast({
    type: 'info',
    title: 'Cetak Dokumen',
    message: opname
      ? `Mencetak Berita Acara Stok Opname ${opname.opnameNumber}...`
      : 'Mencetak dokumen rekapitulasi stok opname...'
  });
  window.print();
};

const handleExport = () => {
  exportStockOpnamesCsv();
  showToast({
    type: 'success',
    title: 'Ekspor Berhasil',
    message: 'Data sesi stok opname berhasil diekspor ke file Excel/CSV.'
  });
};

const handleRefresh = async () => {
  loading.value = true;
  await new Promise((resolve) => setTimeout(resolve, 400));
  loading.value = false;
  showToast({
    type: 'success',
    title: 'Data Diperbarui',
    message: 'Data sesi audit stok opname berhasil disegarkan.'
  });
};
</script>

<template>
  <div class="page-container stock-opname-page">
    <!-- Notification Toast -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- 1. Header Halaman -->
    <div class="page-header">
      <div class="header-content">
        <div class="breadcrumbs">
          <span>Logistik Farmasi</span>
          <span class="separator">/</span>
          <span class="current">Stok Opname</span>
        </div>
        <div class="title-row">
          <h1 class="page-title">Stok Opname Farmasi</h1>
          <span class="module-badge">
            <span class="bullet-dot">•</span> SIMRS-LOG
          </span>
        </div>
        <p class="page-subtitle">
          Bandingkan saldo stok sistem dengan hasil penghitungan fisik pada setiap gudang atau lokasi farmasi.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="btn-outline"
          @click="handleExport"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="8" y1="13" x2="16" y2="13"></line>
            <line x1="8" y1="17" x2="16" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
          </svg>
          Export Excel
        </button>

        <button
          type="button"
          class="btn-outline"
          @click="handlePrint(null)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
          Cetak Rekap
        </button>

        <button
          type="button"
          class="btn-primary"
          @click="openAddModal"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Mulai Stok Opname
        </button>
      </div>
    </div>

    <!-- Page Content Flow -->
    <div class="page-content">
      <!-- 2. Kartu Ringkasan -->
      <StockOpnameSummaryCards :summary="summary" />

      <!-- 3. Bagian Pencarian & Filter -->
      <StockOpnameFilters
        :filters="filters"
        :warehouses="warehouses"
        :statuses="opnameStatuses"
        :loading="loading"
        @apply="applyFilters"
        @reset="resetFilters"
      />

      <!-- 4. Tabel Daftar Sesi Audit Stok -->
      <StockOpnameTable
        :items="paginatedOpnames"
        :pagination-info="paginationInfo"
        :loading="loading"
        @view="handleOpenDetail"
        @continue="handleContinue"
        @review="handleOpenReview"
        @start="handleStartDraft"
        @view-reason="handleOpenReason"
        @cancel="handleCancelSession"
        @delete="handleDeleteDraft"
        @print="handlePrint"
        @refresh="handleRefresh"
        @page-change="setPage"
        @per-page-change="setPerPage"
        @reset-filter="resetFilters"
      />
    </div>

    <!-- 5. Modal Mulai Stok Opname -->
    <StockOpnameFormModal
      :is-open="isFormModalOpen"
      :warehouses="warehouses"
      :opname-types="opnameTypes"
      :is-submitting="submitting"
      @close="isFormModalOpen = false"
      @submit="handleFormSubmit"
    />

    <!-- 6. Modal Detail & Tinjau Selisih -->
    <StockOpnameDetailModal
      :is-open="isDetailModalOpen"
      :opname="selectedOpname"
      @close="isDetailModalOpen = false"
      @approve="handleApproveDiscrepancy"
      @print="handlePrint"
    />
  </div>
</template>

<style scoped>
.stock-opname-page {
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

.title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.35rem;
  flex-wrap: wrap;
}

.page-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
}

.module-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
  letter-spacing: 0.02em;
}

.bullet-dot {
  font-size: 0.9rem;
  line-height: 1;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

@media print {
  .header-actions,
  .filter-card,
  .table-toolbar,
  .table-pagination-wrapper {
    display: none !important;
  }
}
</style>
