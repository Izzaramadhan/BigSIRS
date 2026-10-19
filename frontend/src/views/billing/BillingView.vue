<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import BillingStatsCards from './components/BillingStatsCards.vue';
import BillingFiltersCard from './components/BillingFiltersCard.vue';
import BillingQuickFilters from './components/BillingQuickFilters.vue';
import BillingTable from './components/BillingTable.vue';
import BillingPagination from './components/BillingPagination.vue';
import BillingClosingShiftPanel from './components/BillingClosingShiftPanel.vue';
import BillingPaymentModal from './components/BillingPaymentModal.vue';
import BillingVerifyModal from './components/BillingVerifyModal.vue';
import BillingReceiptModal from './components/BillingReceiptModal.vue';
import BillingDetailModal from './components/BillingDetailModal.vue';
import BillingCancelModal from './components/BillingCancelModal.vue';
import BillingNotifPoliModal from './components/BillingNotifPoliModal.vue';
import BillingShiftHandoverModal from './components/BillingShiftHandoverModal.vue';
import BillingReconciliationModal from './components/BillingReconciliationModal.vue';
import BillingHistoryModal from './components/BillingHistoryModal.vue';
import { useBilling } from '@/composables/useBilling';

const {
  billingList,
  searchQuery,
  filters,
  activeQuickFilter,
  pagination,
  selectedRowIds,
  loading,
  error,
  cashierShift,
  statistics,
  quickFilterCounts,
  filteredRecords,
  paginatedRecords,
  pageSubtotal,
  paginationInfo,
  mockPolyclinics,
  mockDoctors,
  mockGuarantorOptions,
  mockPeriodOptions,
  mockStatusOptions,
  mockPaymentMethods,
  areAllVisibleSelected,
  toggleSelectAll,
  toggleSelectRow,
  applyFilters,
  resetFilters,
  setQuickFilter,
  setPage,
  setPerPage,
  refreshData,
  processPayment,
  verifyBill,
  settleRemainder,
  notifyClinic
} = useBilling();

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

// Export Dropdown State
const isExportDropdownOpen = ref(false);

const toggleExportDropdown = () => {
  isExportDropdownOpen.value = !isExportDropdownOpen.value;
};

const closeExportDropdown = (e) => {
  if (!e.target.closest('.export-dropdown-wrapper')) {
    isExportDropdownOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', closeExportDropdown);
});

onUnmounted(() => {
  document.removeEventListener('click', closeExportDropdown);
});

const handleExport = (type) => {
  isExportDropdownOpen.value = false;
  showToast({
    type: 'success',
    title: `Unduh Laporan ${type}`,
    message: `Laporan Billing Rawat Jalan (${type}) sebanyak ${paginationInfo.value.total} tagihan berhasil di-generate secara lokal.`
  });
};

// Header Action: Refresh
const handleRefresh = () => {
  refreshData();
  showToast({
    type: 'info',
    title: 'Data Tagihan Diperbarui',
    message: 'Data tagihan kasir rawat jalan berhasil dimuat ulang secara lokal.'
  });
};

// Modal States
const activeBill = ref(null);
const isPaymentModalOpen = ref(false);
const isSettleMode = ref(false);
const isVerifyModalOpen = ref(false);
const isReceiptModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const isCancelModalOpen = ref(false);
const isNotifPoliModalOpen = ref(false);
const isHandoverModalOpen = ref(false);
const isReconcileModalOpen = ref(false);
const isHistoryModalOpen = ref(false);

// Cashier Table Actions
const handleOpenPay = (bill) => {
  activeBill.value = bill;
  isSettleMode.value = false;
  isPaymentModalOpen.value = true;
};

const handleOpenSettle = (bill) => {
  activeBill.value = bill;
  isSettleMode.value = true;
  isPaymentModalOpen.value = true;
};

const handleConfirmPayment = ({ billId, amount, method, cashReceived, changeAmount }) => {
  const res = processPayment(billId, { amount, method });
  isPaymentModalOpen.value = false;
  if (res.success) {
    showToast({
      type: 'success',
      title: 'Pembayaran Berhasil Diproses',
      message: `Tagihan ${res.bill.invoice_number} a.n ${res.bill.patient_name} status kini '${res.bill.status}'. Kuitansi ${res.receiptNumber} diterbitkan.`
    });
  }
};

const handleOpenVerify = (bill) => {
  activeBill.value = bill;
  isVerifyModalOpen.value = true;
};

const handleConfirmVerify = (billId) => {
  const res = verifyBill(billId);
  isVerifyModalOpen.value = false;
  if (res.success) {
    showToast({
      type: 'success',
      title: 'Dokumen Klaim Terverifikasi',
      message: `Tagihan ${res.bill.invoice_number} telah diverifikasi petugas kasir dan siap diproses.`
    });
  }
};

const handleOpenReceipt = (bill) => {
  activeBill.value = bill;
  isReceiptModalOpen.value = true;
};

const handlePrintReceipt = (bill) => {
  isReceiptModalOpen.value = false;
  showToast({
    type: 'success',
    title: 'Cetak Kuitansi Kasir',
    message: `Kuitansi resmi ${bill.invoice_number} untuk ${bill.patient_name} berhasil dikirim ke antrean cetak virtual.`
  });
};

const handleOpenDetail = (bill) => {
  activeBill.value = bill;
  isDetailModalOpen.value = true;
};

const handleOpenCancelNote = (bill) => {
  activeBill.value = bill;
  isCancelModalOpen.value = true;
};

const handleOpenNotifPoli = (bill) => {
  activeBill.value = bill;
  isNotifPoliModalOpen.value = true;
};

const handleConfirmNotifPoli = ({ billId, notes }) => {
  const res = notifyClinic(billId, notes);
  isNotifPoliModalOpen.value = false;
  if (res.success) {
    showToast({
      type: 'info',
      title: 'Notifikasi Terkirim',
      message: res.message
    });
  }
};

// Shift Actions
const handleOpenReconcile = () => {
  isReconcileModalOpen.value = true;
};

const handleConfirmSettleBatch = () => {
  isReconcileModalOpen.value = false;
  showToast({
    type: 'success',
    title: 'Settlement Batch EDC Selesai',
    message: 'Semua batch transaksi EDC perbankan (Mandiri, BCA, BNI) berhasil disettle secara lokal.'
  });
};

const handleOpenHandover = () => {
  isHandoverModalOpen.value = true;
};

const handleConfirmHandover = () => {
  isHandoverModalOpen.value = false;
  showToast({
    type: 'success',
    title: 'Serah Terima Shift Berhasil',
    message: `Closing Shift Kasir #1 selesai. Total penerimaan Rp 48.250.000 diserahkan ke Kasir Shift Berikutnya.`
  });
};
</script>

<template>
  <div class="page-container billing-page">
    <!-- Toast Notification System -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Header Section (Breadcrumb: Rawat Jalan → Billing) -->
    <MasterDataPageHeader
      title="Billing Rawat Jalan"
      badge="Kasir Aktif (Poli Rawat Jalan)"
      subtitle="Kelola tagihan, verifikasi klaim penjamin, dan validasi pelunasan pasien Rawat Jalan terintegrasi."
      :breadcrumbs="[
        { label: 'Rawat Jalan', active: false },
        { label: 'Billing', active: true }
      ]"
    >
      <template #actions>
        <div class="header-action-buttons">
          <!-- A. Tombol Perbarui Data -->
          <button
            type="button"
            class="btn-secondary btn-header-action"
            @click="handleRefresh"
            :disabled="loading"
            aria-label="Perbarui Data Tagihan"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="{ 'spin-anim': loading }">
              <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
            </svg>
            <span>Perbarui Data</span>
          </button>

          <!-- B. Tombol Riwayat Transaksi -->
          <button
            type="button"
            class="btn-secondary btn-header-action"
            @click="isHistoryModalOpen = true"
            aria-label="Buka Riwayat Transaksi"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 8v4l3 3"></path>
              <circle cx="12" cy="12" r="9"></circle>
              <path d="M3.05 11a9 9 0 0 1 .5-2m14-5.05A9 9 0 0 1 21 12"></path>
            </svg>
            <span>Riwayat Transaksi</span>
          </button>

          <!-- C. Tombol Unduh Laporan (Dropdown) -->
          <div class="export-dropdown-wrapper">
            <button
              type="button"
              class="btn-primary btn-header-action btn-export"
              @click="toggleExportDropdown"
              aria-label="Menu Unduh Laporan"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
              </svg>
              <span>Unduh Laporan</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="chevron-icon">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>

            <!-- Export Dropdown Menu -->
            <div v-if="isExportDropdownOpen" class="export-menu">
              <button type="button" class="export-menu-item" @click="handleExport('Excel')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <span>Unduh Excel (.xlsx)</span>
              </button>
              <button type="button" class="export-menu-item" @click="handleExport('PDF')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                  <line x1="16" y1="13" x2="8" y2="13"></line>
                </svg>
                <span>Unduh PDF (.pdf)</span>
              </button>
              <button type="button" class="export-menu-item" @click="handleExport('Cetak')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 6 2 18 2 18 9"></polyline>
                  <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                  <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Cetak Rekap Kasir</span>
              </button>
            </div>
          </div>
        </div>
      </template>
    </MasterDataPageHeader>

    <!-- Page Content Flow -->
    <div class="page-content">
      <!-- 1. Lima Card Statistik Billing Rawat Jalan -->
      <BillingStatsCards :stats="statistics" />

      <!-- 2. Form Pencarian dan Filter Billing -->
      <BillingFiltersCard
        v-model:searchQuery="searchQuery"
        :filters="filters"
        :polyclinics="mockPolyclinics"
        :doctors="mockDoctors"
        :guarantor-options="mockGuarantorOptions"
        :period-options="mockPeriodOptions"
        :status-options="mockStatusOptions"
        :payment-methods="mockPaymentMethods"
        :loading="loading"
        @apply="applyFilters"
        @reset="resetFilters"
      />

      <!-- 3. Bagian Filter Cepat (Segmented Chips) -->
      <BillingQuickFilters
        :active-filter="activeQuickFilter"
        :counts="quickFilterCounts"
        @select="setQuickFilter"
      />

      <!-- 4. Tabel Billing Rawat Jalan (10 Kolom Urutan Tetap) -->
      <BillingTable
        :records="paginatedRecords"
        :total-count="paginationInfo.total"
        :page-subtotal="pageSubtotal"
        :current-page="pagination.currentPage"
        :per-page="pagination.perPage"
        :loading="loading"
        :error="error"
        :selected-ids="selectedRowIds"
        :are-all-visible-selected="areAllVisibleSelected"
        @toggle-all="toggleSelectAll"
        @toggle-row="toggleSelectRow"
        @pay="handleOpenPay"
        @settle="handleOpenSettle"
        @verify="handleOpenVerify"
        @receipt="handleOpenReceipt"
        @notify-clinic="handleOpenNotifPoli"
        @cancel-note="handleOpenCancelNote"
        @view-detail="handleOpenDetail"
        @reset-filter="resetFilters"
        @refresh="handleRefresh"
      />

      <!-- 5. Paginasi Tabel -->
      <BillingPagination
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

      <!-- 6. Panel Closing Shift Kasir #1 -->
      <BillingClosingShiftPanel
        :shift="cashierShift"
        @reconcile="handleOpenReconcile"
        @handover="handleOpenHandover"
      />
    </div>

    <!-- Modals & Dialogs -->
    <!-- Modal 1: Bayar & Lunasi Tagihan -->
    <BillingPaymentModal
      :is-open="isPaymentModalOpen"
      :bill="activeBill"
      :is-settle-mode="isSettleMode"
      @close="isPaymentModalOpen = false"
      @confirm="handleConfirmPayment"
    />

    <!-- Modal 2: Verifikasi Klaim & Dokumen -->
    <BillingVerifyModal
      :is-open="isVerifyModalOpen"
      :bill="activeBill"
      @close="isVerifyModalOpen = false"
      @confirm="handleConfirmVerify"
    />

    <!-- Modal 3: Pratinjau Kuitansi Pembayaran -->
    <BillingReceiptModal
      :is-open="isReceiptModalOpen"
      :bill="activeBill"
      @close="isReceiptModalOpen = false"
      @print="handlePrintReceipt"
    />

    <!-- Modal 4: Rincian Lengkap Tagihan (Eye Icon) -->
    <BillingDetailModal
      :is-open="isDetailModalOpen"
      :bill="activeBill"
      @close="isDetailModalOpen = false"
    />

    <!-- Modal 5: Catatan Pembatalan Tagihan -->
    <BillingCancelModal
      :is-open="isCancelModalOpen"
      :bill="activeBill"
      @close="isCancelModalOpen = false"
    />

    <!-- Modal 6: Notifikasi Kelengkapan ke Poliklinik -->
    <BillingNotifPoliModal
      :is-open="isNotifPoliModalOpen"
      :bill="activeBill"
      @close="isNotifPoliModalOpen = false"
      @confirm="handleConfirmNotifPoli"
    />

    <!-- Modal 7: Riwayat Transaksi Kasir -->
    <BillingHistoryModal
      :is-open="isHistoryModalOpen"
      :records="billingList"
      @close="isHistoryModalOpen = false"
    />

    <!-- Modal 8: Serah Terima Kasir -->
    <BillingShiftHandoverModal
      :is-open="isHandoverModalOpen"
      :shift="cashierShift"
      @close="isHandoverModalOpen = false"
      @confirm="handleConfirmHandover"
    />

    <!-- Modal 9: Rekonsiliasi EDC / Bank -->
    <BillingReconciliationModal
      :is-open="isReconcileModalOpen"
      @close="isReconcileModalOpen = false"
      @settle-batch="handleConfirmSettleBatch"
    />
  </div>
</template>

<style scoped>
.billing-page {
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

.btn-export {
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: none;
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.2);
}

.btn-export:hover:not(:disabled) {
  background: var(--color-primary-dark, #06635e);
  box-shadow: 0 4px 12px rgba(8, 127, 120, 0.3);
}

.chevron-icon {
  width: 14px;
  height: 14px;
  margin-left: -0.15rem;
}

/* Export Dropdown */
.export-dropdown-wrapper {
  position: relative;
}

.export-menu {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 6px;
  width: 200px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  padding: 0.35rem 0;
  z-index: 100;
  animation: dropFade 0.15s ease-out;
}

@keyframes dropFade {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.export-menu-item {
  width: 100%;
  padding: 0.55rem 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-size: 0.8rem;
  color: #334155;
  background: transparent;
  border: none;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s ease;
}

.export-menu-item:hover {
  background: #f1f5f9;
  color: var(--color-primary, #087f78);
}

.export-menu-item svg {
  width: 15px;
  height: 15px;
  color: #64748b;
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
  .btn-header-action {
    justify-content: center;
  }
  .export-dropdown-wrapper {
    width: 100%;
  }
  .btn-export {
    width: 100%;
    justify-content: center;
  }
}
</style>
