<script setup>
import { ref, onMounted } from 'vue';
import { useStockOpnames } from '@/composables/useStockOpnames';
import AppToast from '@/components/common/AppToast.vue';
import StockOpnameFilters from './components/StockOpnameFilters.vue';
import StockOpnameTable from './components/StockOpnameTable.vue';
import StockOpnameEditModal from './components/StockOpnameEditModal.vue';

const {
  stocks,
  warehouses,
  filters,
  hasActiveFilters,
  paginationInfo,
  loading,
  submitting,
  fetchWarehouses,
  fetchStockOpnames,
  applyFilters,
  resetFilters,
  setPage,
  setPerPage,
  updateStock
} = useStockOpnames();

// Edit Modal State
const isEditModalOpen = ref(false);
const selectedStockItem = ref(null);
const apiErrors = ref({});

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

// Lifecycle: Load initial data
onMounted(async () => {
  await Promise.allSettled([
    fetchWarehouses(),
    fetchStockOpnames()
  ]);
});

// Actions
const handleOpenEdit = (item) => {
  selectedStockItem.value = item;
  apiErrors.value = {};
  isEditModalOpen.value = true;
};

const handleCloseEdit = () => {
  isEditModalOpen.value = false;
  selectedStockItem.value = null;
  apiErrors.value = {};
};

const handleSaveEdit = async (payload) => {
  if (!selectedStockItem.value?.id) return;

  apiErrors.value = {};
  const res = await updateStock(selectedStockItem.value.id, payload);

  if (res.success) {
    handleCloseEdit();
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Stok opname berhasil diperbarui.'
    });
  } else {
    apiErrors.value = res.errors || {};
    showToast({
      type: 'error',
      title: 'Gagal Memperbarui',
      message: res.message || 'Terjadi kesalahan saat memperbarui stok.'
    });
  }
};

const handleRefresh = async () => {
  await fetchStockOpnames();
  showToast({
    type: 'success',
    title: 'Data Diperbarui',
    message: 'Saldo stok sediaan berhasil disegarkan dari server.'
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
          <span>Logistik</span>
          <span class="separator">></span>
          <span class="current">Stok Opname</span>
        </div>
        <div class="title-row">
          <h1 class="page-title">Data Stok Opname</h1>
          <span class="module-badge">
            <span class="bullet-dot">•</span> SIMRS-LOG
          </span>
        </div>
        <p class="page-subtitle">
          Kelola dan sesuaikan stok sediaan pada setiap gudang logistik.
        </p>
      </div>
    </div>

    <!-- 2. Section Filter & Pencarian -->
    <StockOpnameFilters
      :filters="filters"
      :warehouses="warehouses"
      :loading="loading"
      @apply="applyFilters"
      @reset="resetFilters"
    />

    <!-- 3. Tabel Saldo Stok Sediaan -->
    <StockOpnameTable
      :items="stocks"
      :pagination-info="paginationInfo"
      :loading="loading"
      :has-active-filters="hasActiveFilters"
      @edit="handleOpenEdit"
      @refresh="handleRefresh"
      @page-change="setPage"
      @per-page-change="setPerPage"
      @reset-filter="resetFilters"
    />

    <!-- 4. Modal Edit Stok Opname -->
    <StockOpnameEditModal
      :is-open="isEditModalOpen"
      :item="selectedStockItem"
      :is-submitting="submitting"
      :api-errors="apiErrors"
      @close="handleCloseEdit"
      @submit="handleSaveEdit"
    />
  </div>
</template>

<style scoped>
.stock-opname-page {
  padding: 1.5rem 2rem 3rem;
  background-color: #f8fafc;
  min-height: 100vh;
}

.page-header {
  margin-bottom: 1.75rem;
}

.breadcrumbs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.breadcrumbs .separator {
  color: #94a3b8;
  font-weight: 600;
}

.breadcrumbs .current {
  color: #0d9488;
  font-weight: 600;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.25rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0;
}

.module-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.2rem 0.625rem;
  font-size: 0.6875rem;
  font-weight: 700;
  color: #0d9488;
  background-color: #f0fdfa;
  border: 1px solid #ccfbf1;
  border-radius: 9999px;
  letter-spacing: 0.04em;
}

.bullet-dot {
  font-size: 0.875rem;
  line-height: 1;
}

.page-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}
</style>
