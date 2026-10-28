<script setup>
import { ref, onMounted } from 'vue';
import { useInvoices } from '@/composables/useInvoices';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import InvoiceSummaryCards from './components/InvoiceSummaryCards.vue';
import InvoiceFilters from './components/InvoiceFilters.vue';
import InvoiceTable from './components/InvoiceTable.vue';
import InvoiceFormModal from './components/InvoiceFormModal.vue';
import InvoiceDetailModal from './components/InvoiceDetailModal.vue';
import InvoiceDeleteDialog from './components/InvoiceDeleteDialog.vue';

const {
  suppliers,
  medicines,
  warehouses,
  filters,
  sort,
  paginationInfo,
  loading,
  submitting,
  summary,
  paginatedInvoices,
  isOverdue,
  getDueStatusText,
  setPage,
  setPerPage,
  setSort,
  applyFilters,
  resetFilters,
  addInvoice,
  updateInvoice,
  deleteInvoice,
  formatRupiah,
  formatDateIndo,
  exportInvoicesCsv,
  loadInitialData
} = useInvoices();

onMounted(async () => {
  await loadInitialData();
});

// Modals state
const isFormModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);

const selectedInvoice = ref(null);
const invoiceToDelete = ref(null);

// Single Toast state to prevent duplicates
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
  selectedInvoice.value = null;
  isFormModalOpen.value = true;
};

const handleOpenEdit = (invoice) => {
  selectedInvoice.value = { ...invoice };
  isFormModalOpen.value = true;
};

const handleOpenDetail = (invoice) => {
  selectedInvoice.value = invoice;
  isDetailModalOpen.value = true;
};

const handleOpenDelete = (invoice) => {
  invoiceToDelete.value = invoice;
  isDeleteDialogOpen.value = true;
};

const handleFormSubmit = async (payloadOrEvent) => {
  let payload = payloadOrEvent;
  let onSuccess = null;
  let onError = null;

  if (payloadOrEvent && typeof payloadOrEvent === 'object' && payloadOrEvent.payload) {
    payload = payloadOrEvent.payload;
    onSuccess = payloadOrEvent.onSuccess;
    onError = payloadOrEvent.onError;
  }

  const isEditing = Boolean(selectedInvoice.value);

  if (isEditing) {
    const res = await updateInvoice(selectedInvoice.value.id, payload);
    if (res.success) {
      isFormModalOpen.value = false;
      selectedInvoice.value = null;
      showToast({
        type: 'success',
        title: 'Berhasil',
        message: 'Data Faktur berhasil diperbarui.'
      });
      if (typeof onSuccess === 'function') onSuccess(res.data);
    } else {
      if (typeof onError === 'function') onError(res);
      showToast({
        type: 'error',
        title: 'Gagal Memperbarui',
        message: res.message || 'Silakan periksa kembali isian formulir faktur.'
      });
    }
  } else {
    const res = await addInvoice(payload);
    if (res.success) {
      isFormModalOpen.value = false;
      selectedInvoice.value = null;
      showToast({
        type: 'success',
        title: 'Berhasil',
        message: 'Data Faktur berhasil ditambahkan.'
      });
      if (typeof onSuccess === 'function') onSuccess(res.data);
    } else {
      if (typeof onError === 'function') onError(res);
      showToast({
        type: 'error',
        title: 'Gagal Menyimpan',
        message: res.message || 'Silakan periksa kembali isian formulir faktur.'
      });
    }
  }
};

const handleDeleteConfirm = async () => {
  if (!invoiceToDelete.value) return;

  const res = await deleteInvoice(invoiceToDelete.value.id);
  if (res.success) {
    isDeleteDialogOpen.value = false;
    invoiceToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Data Faktur berhasil dihapus.'
    });
  }
};

const handleApplyFilters = (payload) => {
  if (payload) {
    Object.assign(filters, payload);
  }
  applyFilters();
};

const handleSearchChange = (query) => {
  filters.search = query;
};

const handleExport = () => {
  exportInvoicesCsv();
  showToast({
    type: 'success',
    title: 'Ekspor Berhasil',
    message: 'Data faktur berhasil diekspor ke file CSV.'
  });
};
</script>

<template>
  <div class="page-container invoice-page">
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
      title="Data Faktur"
      subtitle="Kelola faktur pembelian obat dan sediaan dari supplier."
      :breadcrumbs="[
        { label: 'Logistik', active: false },
        { label: 'Faktur', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Faktur
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Page Content Flow -->
    <div class="page-content">
      <!-- 2. Kartu Ringkasan Statistik -->
      <InvoiceSummaryCards
        :summary="summary"
        :format-rupiah="formatRupiah"
      />

      <!-- 3. Bagian Pencarian & Filter -->
      <InvoiceFilters
        :filters="filters"
        :suppliers="suppliers"
        :loading="loading"
        @apply="handleApplyFilters"
        @reset="resetFilters"
      />

      <!-- 4. Tabel Data Faktur -->
      <InvoiceTable
        :items="paginatedInvoices"
        :pagination-info="paginationInfo"
        :sort="sort"
        :search-query="filters.search"
        :loading="loading"
        :format-rupiah="formatRupiah"
        :format-date-indo="formatDateIndo"
        :is-overdue="isOverdue"
        :get-due-status-text="getDueStatusText"
        @view="handleOpenDetail"
        @edit="handleOpenEdit"
        @delete="handleOpenDelete"
        @sort="setSort"
        @page-change="setPage"
        @per-page-change="setPerPage"
        @search-change="handleSearchChange"
        @export="handleExport"
        @add="openAddModal"
      />
    </div>

    <!-- 5. Modal Tambah & Edit Faktur -->
    <InvoiceFormModal
      :is-open="isFormModalOpen"
      :invoice="selectedInvoice"
      :suppliers="suppliers"
      :medicines="medicines"
      :is-submitting="submitting"
      :format-rupiah="formatRupiah"
      @close="isFormModalOpen = false"
      @submit="handleFormSubmit"
    />

    <!-- 6. Modal Detail Faktur -->
    <InvoiceDetailModal
      :is-open="isDetailModalOpen"
      :invoice="selectedInvoice"
      :format-rupiah="formatRupiah"
      :format-date-indo="formatDateIndo"
      :is-overdue="isOverdue"
      :get-due-status-text="getDueStatusText"
      @close="isDetailModalOpen = false"
    />

    <!-- 7. Dialog Konfirmasi Hapus Faktur -->
    <InvoiceDeleteDialog
      :is-open="isDeleteDialogOpen"
      :invoice="invoiceToDelete"
      :is-submitting="submitting"
      @close="isDeleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>
.invoice-page {
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
</style>
