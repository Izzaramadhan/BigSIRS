<script setup>
import { ref } from 'vue';
import { useGoodsIssues } from '@/composables/useGoodsIssues';
import AppToast from '@/components/common/AppToast.vue';
import GoodsIssueSummaryCards from './components/GoodsIssueSummaryCards.vue';
import GoodsIssueFilters from './components/GoodsIssueFilters.vue';
import GoodsIssueTable from './components/GoodsIssueTable.vue';
import GoodsIssueFormModal from './components/GoodsIssueFormModal.vue';
import GoodsIssueDetailModal from './components/GoodsIssueDetailModal.vue';
import GoodsIssueDeleteDialog from './components/GoodsIssueDeleteDialog.vue';

const {
  warehouses,
  officers,
  purposes,
  catalogMedicines,
  filters,
  paginationInfo,
  loading,
  submitting,
  summary,
  paginatedIssues,
  applyFilters,
  resetFilters,
  setPage,
  setPerPage,
  createIssue,
  updateIssue,
  deleteIssue,
  exportIssuesCsv
} = useGoodsIssues();

// Modal States
const isFormModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);

const selectedIssue = ref(null);
const issueToEdit = ref(null);
const issueToDelete = ref(null);

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
  issueToEdit.value = null;
  isFormModalOpen.value = true;
};

const handleOpenEdit = (issue) => {
  issueToEdit.value = issue;
  isFormModalOpen.value = true;
};

const handleOpenDetail = (issue) => {
  selectedIssue.value = issue;
  isDetailModalOpen.value = true;
};

const handleOpenDelete = (issue) => {
  issueToDelete.value = issue;
  isDeleteDialogOpen.value = true;
};

const handleFormSubmit = async ({ isDraft, ...payload }) => {
  if (issueToEdit.value) {
    const res = await updateIssue(issueToEdit.value.id, payload, isDraft);
    if (res.success) {
      isFormModalOpen.value = false;
      issueToEdit.value = null;
      showToast({
        type: 'success',
        title: 'Berhasil',
        message: isDraft
          ? 'Draft pengeluaran barang berhasil diperbarui.'
          : 'Data pengeluaran barang berhasil diperbarui.'
      });
    }
  } else {
    const res = await createIssue(payload, isDraft);
    if (res.success) {
      isFormModalOpen.value = false;
      showToast({
        type: 'success',
        title: 'Berhasil',
        message: isDraft
          ? 'Draft pengeluaran barang berhasil disimpan.'
          : 'Dokumen pengeluaran barang berhasil diterbitkan.'
      });
    }
  }
};

const handleDeleteConfirm = async () => {
  if (!issueToDelete.value) return;
  const num = issueToDelete.value.issueNumber;
  const res = await deleteIssue(issueToDelete.value.id);
  if (res.success) {
    isDeleteDialogOpen.value = false;
    issueToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Draft Dihapus',
      message: `Draft pengeluaran barang ${num} telah dihapus.`
    });
  }
};

const handlePrint = (issue) => {
  showToast({
    type: 'info',
    title: 'Cetak Dokumen',
    message: issue
      ? `Mencetak dokumen pengeluaran ${issue.issueNumber}...`
      : 'Mencetak dokumen rekapitulasi bulanan pengeluaran barang...'
  });
  window.print();
};

const handleExport = () => {
  exportIssuesCsv();
  showToast({
    type: 'success',
    title: 'Ekspor Berhasil',
    message: 'Data pengeluaran barang berhasil diekspor ke file Excel/CSV.'
  });
};

const handleRefresh = async () => {
  loading.value = true;
  await new Promise((resolve) => setTimeout(resolve, 400));
  loading.value = false;
  showToast({
    type: 'success',
    title: 'Data Diperbarui',
    message: 'Data pengeluaran barang berhasil disegarkan.'
  });
};
</script>

<template>
  <div class="page-container goods-issue-page">
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
          <span>Logistik</span>
          <span class="separator">/</span>
          <span class="current">Pengeluaran Barang</span>
        </div>
        <div class="title-row">
          <h1 class="page-title">Pengeluaran Barang</h1>
          <span class="module-badge">
            <span class="bullet-dot">•</span> SIMRS-LOG
          </span>
        </div>
        <p class="page-subtitle">
          Kelola pengeluaran obat dan sediaan untuk kebutuhan nonpenjualan serta mutasi pengurangan kartu inventori.
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
          Cetak Rekap Bulanan
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
          + Tambah Pengeluaran
        </button>
      </div>
    </div>

    <!-- Page Content Flow -->
    <div class="page-content">
      <!-- 2. Kartu Ringkasan -->
      <GoodsIssueSummaryCards :summary="summary" />

      <!-- 3. Bagian Pencarian & Filter -->
      <GoodsIssueFilters
        :filters="filters"
        :warehouses="warehouses"
        :officers="officers"
        :purposes="purposes"
        :loading="loading"
        @apply="applyFilters"
        @reset="resetFilters"
      />

      <!-- 4. Tabel Pengeluaran Barang -->
      <GoodsIssueTable
        :items="paginatedIssues"
        :pagination-info="paginationInfo"
        :loading="loading"
        @view="handleOpenDetail"
        @edit="handleOpenEdit"
        @delete="handleOpenDelete"
        @print="handlePrint"
        @refresh="handleRefresh"
        @page-change="setPage"
        @per-page-change="setPerPage"
        @reset-filter="resetFilters"
      />
    </div>

    <!-- 5. Modal Tambah / Edit Pengeluaran -->
    <GoodsIssueFormModal
      :is-open="isFormModalOpen"
      :issue-to-edit="issueToEdit"
      :warehouses="warehouses"
      :officers="officers"
      :purposes="purposes"
      :medicines="catalogMedicines"
      :is-submitting="submitting"
      @close="isFormModalOpen = false"
      @submit="handleFormSubmit"
    />

    <!-- 6. Modal Detail Pengeluaran -->
    <GoodsIssueDetailModal
      :is-open="isDetailModalOpen"
      :issue="selectedIssue"
      @close="isDetailModalOpen = false"
      @print="handlePrint"
    />

    <!-- 7. Dialog Konfirmasi Hapus Draft -->
    <GoodsIssueDeleteDialog
      :is-open="isDeleteDialogOpen"
      :issue="issueToDelete"
      :is-submitting="submitting"
      @close="isDeleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>
.goods-issue-page {
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
