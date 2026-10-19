<script setup>
import { ref } from 'vue';
import { usePosSales } from '@/composables/usePosSales';
import AppToast from '@/components/common/AppToast.vue';
import PosSaleSummaryCards from './components/PosSaleSummaryCards.vue';
import PosSaleFilters from './components/PosSaleFilters.vue';
import PosSaleTable from './components/PosSaleTable.vue';
import PosSaleDetailModal from './components/PosSaleDetailModal.vue';
import PosSaleVoidDialog from './components/PosSaleVoidDialog.vue';
import PosSaleFormModal from './components/PosSaleFormModal.vue';
import PosSaleDeleteDialog from './components/PosSaleDeleteDialog.vue';

const {
  locations,
  paymentMethods,
  transactionStatuses,
  otcMedicines,
  loading,
  submitting,
  tableState,
  errorMessage,
  filters,
  summary,
  paginatedSales,
  paginationInfo,
  applyFilters,
  resetFilters,
  setPage,
  setPerPage,
  setTableState,
  createSale,
  voidSale,
  deleteSale,
  exportSalesCsv
} = usePosSales();

// Modal States
const isDetailModalOpen = ref(false);
const isVoidDialogOpen = ref(false);
const isFormModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);

const selectedSale = ref(null);
const saleToVoid = ref(null);
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

// Handlers
const openAddModal = () => {
  saleToEdit.value = null;
  isFormModalOpen.value = true;
};

const handleOpenEdit = (sale) => {
  saleToEdit.value = sale;
  isFormModalOpen.value = true;
};

const handleOpenDetail = (sale) => {
  selectedSale.value = sale;
  isDetailModalOpen.value = true;
};

const handleOpenVoid = (sale) => {
  saleToVoid.value = sale;
  isVoidDialogOpen.value = true;
};

const handleOpenDelete = (sale) => {
  saleToDelete.value = sale;
  isDeleteDialogOpen.value = true;
};

const handleFormSubmit = async ({ isDraft, ...payload }) => {
  const res = await createSale(payload, isDraft);
  if (res.success) {
    isFormModalOpen.value = false;
    showToast({
      type: 'success',
      title: isDraft ? 'Draft Disimpan' : 'Transaksi Berhasil',
      message: isDraft
        ? `Draft transaksi ${res.data.transactionNumber} berhasil disimpan.`
        : `Pembayaran ${res.data.transactionNumber} sukses dicatat. Struk siap dicetak.`
    });
  }
};

const handleVoidConfirm = async ({ id, reason }) => {
  const num = saleToVoid.value?.transactionNumber;
  const res = await voidSale(id, reason);
  if (res.success) {
    isVoidDialogOpen.value = false;
    saleToVoid.value = null;
    showToast({
      type: 'success',
      title: 'Transaksi Dibatalkan (Void)',
      message: `Transaksi ${num} berhasil dibatalkan. Stok sediaan telah dikembalikan ke inventori.`
    });
  }
};

const handleDeleteConfirm = async () => {
  if (!saleToDelete.value) return;
  const num = saleToDelete.value.transactionNumber;
  const res = await deleteSale(saleToDelete.value.id);
  if (res.success) {
    isDeleteDialogOpen.value = false;
    saleToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Draft Dihapus',
      message: `Draft transaksi ${num} telah dihapus dari antrean kasir.`
    });
  }
};

const handlePrint = (sale) => {
  showToast({
    type: 'info',
    title: 'Cetak Struk Kasir',
    message: sale
      ? `Mencetak struk kasir untuk transaksi ${sale.transactionNumber}...`
      : 'Mencetak struk transaksi penjualan bebas...'
  });
  window.print();
};

const handlePrintDailyReport = () => {
  showToast({
    type: 'info',
    title: 'Rekap Kasir Harian',
    message: 'Mencetak laporan Rekapitulasi Kasir Harian Shift 1 & 2...'
  });
  window.print();
};

const handleExport = () => {
  exportSalesCsv();
  showToast({
    type: 'success',
    title: 'Ekspor Berhasil',
    message: 'Data riwayat transaksi kasir OTC berhasil diekspor ke file Excel/CSV.'
  });
};

const handleRefresh = async () => {
  loading.value = true;
  tableState.value = 'normal';
  await new Promise((resolve) => setTimeout(resolve, 400));
  loading.value = false;
  showToast({
    type: 'success',
    title: 'Data Diperbarui',
    message: 'Daftar riwayat transaksi kasir OTC berhasil disegarkan.'
  });
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

    <!-- 1. Header Halaman -->
    <div class="page-header">
      <div class="header-content">
        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
          <span>Logistik Farmasi</span>
          <span class="separator">&gt;</span>
          <span class="current">Penjualan Bebas (OTC)</span>
        </div>

        <!-- Page Title & Badges -->
        <div class="title-row">
          <h1 class="page-title">Penjualan Bebas</h1>
          <div class="badges-wrapper">
            <span class="otc-badge">OTC-FARMASI</span>
            <span class="version-badge">POS V2.6</span>
          </div>
        </div>

        <!-- Subtitle -->
        <p class="page-subtitle">
          Kelola transaksi kasir langsung, inventaris obat bebas (OTC), vitamin, suplemen, dan BMHP non-kunjungan pasien.
        </p>
      </div>

      <!-- Header Action Buttons -->
      <div class="header-actions">
        <button
          type="button"
          class="btn-outline"
          title="Ekspor seluruh data transaksi ke Excel"
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
          title="Cetak rekap kasir harian"
          @click="handlePrintDailyReport"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
          Rekap Kasir Harian
        </button>

        <button
          type="button"
          class="btn-primary"
          title="Buka kasir untuk transaksi baru"
          @click="openAddModal"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          + Transaksi Baru
        </button>
      </div>
    </div>

    <!-- Page Content Flow -->
    <div class="page-content">
      <!-- 2. Kartu Ringkasan -->
      <PosSaleSummaryCards :summary="summary" />

      <!-- 3. Bagian Pencarian & Filter -->
      <PosSaleFilters
        :filters="filters"
        :locations="locations"
        :payment-methods="paymentMethods"
        :statuses="transactionStatuses"
        :loading="loading"
        @apply="applyFilters"
        @reset="resetFilters"
      />

      <!-- 4. Tabel Transaksi Kasir OTC (dengan Live State Switcher) -->
      <PosSaleTable
        :items="paginatedSales"
        :pagination-info="paginationInfo"
        :loading="loading"
        :table-state="tableState"
        :error-message="errorMessage"
        @view="handleOpenDetail"
        @edit="handleOpenEdit"
        @void="handleOpenVoid"
        @delete="handleOpenDelete"
        @print="handlePrint"
        @info-void="handleOpenDetail"
        @refresh="handleRefresh"
        @page-change="setPage"
        @per-page-change="setPerPage"
        @set-table-state="setTableState"
        @retry="handleRefresh"
        @reset-filter="resetFilters"
      />
    </div>

    <!-- 5. Interactive Modals & Dialogs -->
    <PosSaleDetailModal
      :is-open="isDetailModalOpen"
      :sale="selectedSale"
      @close="isDetailModalOpen = false"
      @print="handlePrint"
    />

    <PosSaleVoidDialog
      :is-open="isVoidDialogOpen"
      :sale="saleToVoid"
      :submitting="submitting"
      @close="isVoidDialogOpen = false"
      @confirm="handleVoidConfirm"
    />

    <PosSaleFormModal
      :is-open="isFormModalOpen"
      :sale-to-edit="saleToEdit"
      :otc-medicines="otcMedicines"
      :locations="locations"
      :payment-methods="paymentMethods"
      :submitting="submitting"
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
  background-color: var(--color-bg-main, #f8fafc);
  min-height: calc(100vh - 64px);
}

/* 1. Header Styles */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.breadcrumbs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: var(--color-text-secondary, #64748b);
  font-weight: 500;
}

.breadcrumbs .separator {
  color: #94a3b8;
  font-size: 0.72rem;
}

.breadcrumbs .current {
  color: var(--color-primary, #087f78);
  font-weight: 600;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-text-navy, #17243d);
  letter-spacing: -0.02em;
  margin: 0;
}

.badges-wrapper {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.otc-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.version-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  background: #f1f5f9;
  color: #475569;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.page-subtitle {
  font-size: 0.84rem;
  color: var(--color-text-secondary, #64748b);
  margin: 0;
  max-width: 720px;
  line-height: 1.45;
}

/* Header Action Buttons */
.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  height: 40px;
  padding: 0 1.15rem;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  background: #ffffff;
  color: var(--color-text-navy, #17243d);
  border: 1px solid var(--color-border-soft, #cbd5e1);
  cursor: pointer;
  transition: all 0.15s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.btn-outline:hover {
  background: #f8fafc;
  border-color: var(--color-primary, #087f78);
  color: var(--color-primary, #087f78);
}

.btn-outline svg {
  width: 15px;
  height: 15px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  height: 40px;
  padding: 0 1.25rem;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: 1px solid var(--color-primary, #087f78);
  cursor: pointer;
  transition: all 0.15s ease;
  box-shadow: 0 2px 4px rgba(8, 127, 120, 0.15);
}

.btn-primary:hover {
  background: #076d67;
  border-color: #076d67;
}

.btn-primary svg {
  width: 16px;
  height: 16px;
}

/* 2. Content Layout */
.page-content {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

@media (max-width: 768px) {
  .page-container {
    padding: 1rem;
    gap: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    justify-content: stretch;
  }

  .header-actions .btn-outline,
  .header-actions .btn-primary {
    flex: 1;
    justify-content: center;
  }
}
</style>
