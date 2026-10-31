<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { usePosSales } from '@/composables/usePosSales';
import AppToast from '@/components/common/AppToast.vue';
import PosSaleTable from './components/PosSaleTable.vue';
import PosSaleFormModal from './components/PosSaleFormModal.vue';
import PosSaleDeleteDialog from './components/PosSaleDeleteDialog.vue';

const router = useRouter();

const {
  sales,
  customers,
  loading,
  submitting,
  filters,
  paginationInfo,
  fetchSales,
  fetchCustomers,
  setPage,
  setPerPage,
  createSale,
  updateSale,
  deleteSale
} = usePosSales();

// Modal States
const isFormModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);

const saleToEdit = ref(null);
const saleToDelete = ref(null);

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

onMounted(async () => {
  await Promise.allSettled([
    fetchSales(),
    fetchCustomers()
  ]);
});

// Handlers
const openAddModal = () => {
  saleToEdit.value = null;
  isFormModalOpen.value = true;
};

const handleOpenEdit = (sale) => {
  saleToEdit.value = sale;
  isFormModalOpen.value = true;
};

const handleViewDetail = (sale) => {
  if (router) {
    router.push({ name: 'logistics.pos.detail', params: { id: sale.id } });
  }
};

const handleOpenDelete = (sale) => {
  saleToDelete.value = sale;
  isDeleteDialogOpen.value = true;
};

const handleSearch = (query) => {
  filters.search = query;
  setPage(1);
  fetchSales({ search: query });
};

const handleResetFilter = () => {
  filters.search = '';
  setPage(1);
  fetchSales({ search: '' });
};

const handleFormSubmit = async (payload) => {
  if (saleToEdit.value) {
    const res = await updateSale(saleToEdit.value.id, payload);
    if (res.success) {
      isFormModalOpen.value = false;
      saleToEdit.value = null;
      showToast({
        type: 'success',
        title: 'Berhasil',
        message: 'Data penjualan bebas berhasil diperbarui.'
      });
    } else {
      showToast({
        type: 'error',
        title: 'Gagal Memperbarui',
        message: res.message || 'Terjadi kesalahan saat memperbarui data penjualan.'
      });
    }
  } else {
    const res = await createSale(payload);
    if (res.success) {
      isFormModalOpen.value = false;
      showToast({
        type: 'success',
        title: 'Berhasil Disimpan',
        message: 'Data penjualan bebas berhasil ditambahkan.'
      });
      if (res.data?.id) {
        router.push({ name: 'logistics.pos.detail', params: { id: res.data.id } });
      }
    } else {
      showToast({
        type: 'error',
        title: 'Gagal Menyimpan',
        message: res.message || 'Terjadi kesalahan saat menyimpan data penjualan.'
      });
    }
  }
};

const handleDeleteConfirm = async () => {
  if (!saleToDelete.value) return;
  const res = await deleteSale(saleToDelete.value.id);
  if (res.success) {
    isDeleteDialogOpen.value = false;
    saleToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Data Dihapus',
      message: 'Data penjualan bebas telah berhasil dihapus.'
    });
    await fetchSales();
  } else {
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: res.message || 'Terjadi kesalahan saat menghapus data penjualan.'
    });
  }
};
</script>

<template>
  <div class="page-container pos-sales-page">
    <!-- Notification Toast -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Header Halaman Standar BigSIRS -->
    <div class="page-header">
      <div class="header-content">
        <div class="breadcrumbs">
          <span>Logistik</span>
          <span class="separator">/</span>
          <span class="current">Penjualan Bebas</span>
        </div>
        <div class="title-row">
          <h1 class="page-title">Penjualan Bebas</h1>
          <span class="module-badge">
            <span class="bullet-dot">•</span> SIMRS-LOG
          </span>
        </div>
        <p class="page-subtitle">
          Kelola data penjualan sediaan secara langsung kepada pembeli.
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
          Tambah Penjualan Bebas
        </button>
      </div>
    </div>

    <!-- Page Content Flow -->
    <div class="page-content">
      <!-- Tabel Penjualan Bebas (4 Kolom Sederhana) -->
      <PosSaleTable
        :items="sales"
        :pagination-info="paginationInfo"
        :loading="loading"
        :search="filters.search"
        @view="handleViewDetail"
        @edit="handleOpenEdit"
        @delete="handleOpenDelete"
        @search="handleSearch"
        @reset-filter="handleResetFilter"
        @page-change="setPage"
        @per-page-change="setPerPage"
      />
    </div>

    <!-- Interactive Modals & Dialogs -->
    <PosSaleFormModal
      :is-open="isFormModalOpen"
      :sale-to-edit="saleToEdit"
      :customers="customers"
      :is-submitting="submitting"
      @close="isFormModalOpen = false"
      @submit="handleFormSubmit"
    />

    <PosSaleDeleteDialog
      :is-open="isDeleteDialogOpen"
      :sale="saleToDelete"
      :submitting="submitting"
      @close="isDeleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>
.page-container {
  padding: 1.5rem 2rem 2.5rem 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1.25rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  padding-bottom: 1.25rem;
}

.breadcrumbs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.825rem;
  color: var(--color-text-muted, #64748b);
  margin-bottom: 0.4rem;
}

.breadcrumbs .separator {
  color: #cbd5e1;
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
}

.page-title {
  font-size: 1.65rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
  letter-spacing: -0.02em;
}

.module-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background-color: var(--color-primary-light, #e0f2f1);
  color: var(--color-primary, #008779);
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.bullet-dot {
  font-size: 0.9rem;
  line-height: 1;
}

.page-subtitle {
  color: var(--color-text-muted, #64748b);
  font-size: 0.875rem;
  margin: 0;
  max-width: 680px;
  line-height: 1.45;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background-color: var(--color-primary, #008779);
  color: #ffffff;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn-primary:hover {
  background-color: #007366;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.btn-primary svg {
  width: 16px;
  height: 16px;
}

.page-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
</style>
