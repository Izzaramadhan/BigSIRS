<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useGoodsIssues } from '@/composables/useGoodsIssues';
import AppToast from '@/components/common/AppToast.vue';
import GoodsIssueTable from './components/GoodsIssueTable.vue';
import GoodsIssueFormModal from './components/GoodsIssueFormModal.vue';
import GoodsIssueDeleteDialog from './components/GoodsIssueDeleteDialog.vue';

const router = useRouter();

const {
  officers,
  filters,
  paginationInfo,
  loading,
  submitting,
  paginatedIssues,
  fetchIssues,
  setPage,
  setPerPage,
  createIssue,
  updateIssue,
  deleteIssue
} = useGoodsIssues();

// Modal States
const isFormModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);

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

// Handlers
const openAddModal = () => {
  issueToEdit.value = null;
  isFormModalOpen.value = true;
};

const handleOpenEdit = (issue) => {
  issueToEdit.value = issue;
  isFormModalOpen.value = true;
};

const handleViewDetail = (issue) => {
  if (router) {
    router.push({ name: 'logistics.goods-issues.detail', params: { id: issue.id } });
  }
};

const handleOpenDelete = (issue) => {
  issueToDelete.value = issue;
  isDeleteDialogOpen.value = true;
};

const handleSearch = (query) => {
  filters.search = query;
  setPage(1);
  fetchIssues({ search: query });
};

const handleResetFilter = () => {
  filters.search = '';
  setPage(1);
  fetchIssues({ search: '' });
};

const handleRefresh = async () => {
  await fetchIssues();
  showToast({
    type: 'success',
    title: 'Data Diperbarui',
    message: 'Data pengeluaran barang berhasil disegarkan.'
  });
};

const handleFormSubmit = async (payload) => {
  if (issueToEdit.value) {
    const res = await updateIssue(issueToEdit.value.id, payload);
    if (res.success) {
      isFormModalOpen.value = false;
      issueToEdit.value = null;
      showToast({
        type: 'success',
        title: 'Berhasil',
        message: 'Data pengeluaran barang berhasil diperbarui.'
      });
    } else {
      showToast({
        type: 'error',
        title: 'Gagal Memperbarui',
        message: res.message || 'Terjadi kesalahan saat memperbarui data pengeluaran.'
      });
    }
  } else {
    const res = await createIssue(payload);
    if (res.success) {
      isFormModalOpen.value = false;
      showToast({
        type: 'success',
        title: 'Berhasil Disimpan',
        message: 'Data pengeluaran barang berhasil ditambahkan.'
      });
    } else {
      showToast({
        type: 'error',
        title: 'Gagal Menyimpan',
        message: res.message || 'Terjadi kesalahan saat menyimpan pengeluaran.'
      });
    }
  }
};

const handleDeleteConfirm = async () => {
  if (!issueToDelete.value) return;
  const res = await deleteIssue(issueToDelete.value.id);
  if (res.success) {
    isDeleteDialogOpen.value = false;
    issueToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Data Dihapus',
      message: 'Data pengeluaran barang telah berhasil dihapus.'
    });
  } else {
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: res.message || 'Terjadi kesalahan saat menghapus data pengeluaran.'
    });
  }
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

    <!-- Header Halaman -->
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
          class="btn-primary"
          @click="openAddModal"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Pengeluaran
        </button>
      </div>
    </div>

    <!-- Page Content Flow -->
    <div class="page-content">
      <!-- Tabel Pengeluaran Barang -->
      <GoodsIssueTable
        :items="paginatedIssues"
        :pagination-info="paginationInfo"
        :loading="loading"
        :search="filters.search"
        @view="handleViewDetail"
        @edit="handleOpenEdit"
        @delete="handleOpenDelete"
        @search="handleSearch"
        @refresh="handleRefresh"
        @page-change="setPage"
        @per-page-change="setPerPage"
        @reset-filter="handleResetFilter"
      />
    </div>

    <!-- Modal FORM INPUT (Tambah / Edit Pengeluaran) -->
    <GoodsIssueFormModal
      :is-open="isFormModalOpen"
      :issue-to-edit="issueToEdit"
      :officers="officers"
      :is-submitting="submitting"
      @close="isFormModalOpen = false"
      @submit="handleFormSubmit"
    />

    <!-- Dialog Konfirmasi Hapus Data -->
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

/* 1. Header Layout */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.header-content {
  flex: 1 1 520px;
  min-width: 0;
}

.breadcrumbs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: #64748b;
  margin-bottom: 0.35rem;
}

.breadcrumbs .separator {
  color: #94a3b8;
  font-weight: 500;
}

.breadcrumbs .current {
  color: var(--color-primary, #087f78);
  font-weight: 600;
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
  letter-spacing: -0.02em;
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

.page-subtitle {
  margin: 0;
  font-size: 0.875rem;
  color: var(--color-text-secondary, #64748b);
  line-height: 1.45;
  max-width: 720px;
}

/* 2. Header Action Button */
.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-top: 0.25rem;
}

.header-actions .btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  height: 40px;
  min-height: 40px;
  padding: 0 1.15rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  box-sizing: border-box;
  transition: all 0.15s ease;
  line-height: 1;
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: 1px solid var(--color-primary, #087f78);
  box-shadow: 0 2px 4px rgba(8, 127, 120, 0.15);
}

.header-actions .btn-primary:hover {
  background: #076d67;
  border-color: #076d67;
}

.header-actions .btn-primary svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* 3. Responsive Styles */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .header-actions {
    width: 100%;
    margin-top: 0.25rem;
  }

  .header-actions .btn-primary {
    width: 100%;
    justify-content: center;
  }
}

@media print {
  .header-actions {
    display: none !important;
  }
}
</style>
