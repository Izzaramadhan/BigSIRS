<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import BillingDailyListCard from './components/BillingDailyListCard.vue';
import BillingPaymentModal from './components/BillingPaymentModal.vue';
import BillingVerifyModal from './components/BillingVerifyModal.vue';
import BillingReceiptModal from './components/BillingReceiptModal.vue';
import BillingDetailModal from './components/BillingDetailModal.vue';
import { useBilling } from '@/composables/useBilling';

const router = useRouter();

const {
  searchQuery,
  filters,
  pagination,
  sort,
  loading,
  paginatedRecords,
  paginationInfo,
  mockPolyclinics,
  applyFilters,
  resetFilters,
  setSort,
  setPage,
  setPerPage,
  processPayment,
  verifyBill,
  settleRemainder
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

// Modal States
const activeBill = ref(null);
const isPaymentModalOpen = ref(false);
const isSettleMode = ref(false);
const isVerifyModalOpen = ref(false);
const isReceiptModalOpen = ref(false);
const isDetailModalOpen = ref(false);

// Filter actions
const handleApplySearch = () => {
  applyFilters();
  showToast({
    type: 'success',
    title: 'Data Tagihan Ditampilkan',
    message: `Ditemukan ${paginationInfo.value.total} data billing pasien sesuai filter.`
  });
};

const handleResetFilter = () => {
  resetFilters();
  showToast({
    type: 'info',
    title: 'Filter Direset',
    message: 'Kriteria pencarian, periode tanggal, status bayar, klinik, dan jaminan telah direset.'
  });
};

// Table Row Action 1 (+): Buka Halaman Billing Order Pasien
const handleOpenBill = (bill) => {
  if (router) {
    router.push({
      name: 'clinical.billing-order',
      params: { id: bill.id }
    });
    return;
  }

  activeBill.value = bill;
  if (bill.status === 'Dibayar Sebagian' && bill.remaining_balance > 0) {
    isSettleMode.value = true;
    isPaymentModalOpen.value = true;
  } else if (bill.remaining_balance > 0) {
    isSettleMode.value = false;
    isPaymentModalOpen.value = true;
  } else if (bill.status === 'Menunggu Verifikasi') {
    isVerifyModalOpen.value = true;
  } else {
    isDetailModalOpen.value = true;
  }
};

// Table Row Action 2 (Speaker): Panggil Antrean Kasir
const handleCallPatient = (bill) => {
  showToast({
    type: 'success',
    title: 'Memanggil Pasien ke Kasir',
    message: `Nomor antrean ${bill.queue_number || bill.visit_number} (${bill.patient_name}) dipanggil ke Loket Billing / Kasir.`
  });
};

const handleConfirmPayment = ({ billId, amount, method }) => {
  const res = isSettleMode.value
    ? settleRemainder(billId, method)
    : processPayment(billId, { amount, method });
  isPaymentModalOpen.value = false;

  if (res.success) {
    showToast({
      type: 'success',
      title: 'Pembayaran Berhasil Diproses',
      message: `Tagihan ${res.bill.invoice_number} a.n ${res.bill.patient_name} berhasil diperbarui (${res.bill.status}).`
    });
  }
};

const handleConfirmVerify = (billId) => {
  const res = verifyBill(billId);
  isVerifyModalOpen.value = false;
  if (res.success) {
    showToast({
      type: 'success',
      title: 'Dokumen Klaim Terverifikasi',
      message: `Tagihan ${res.bill.invoice_number} telah diverifikasi dan siap diproses.`
    });
  }
};

const handlePrintReceipt = (bill) => {
  isReceiptModalOpen.value = false;
  showToast({
    type: 'success',
    title: 'Cetak Kuitansi Kasir',
    message: `Kuitansi ${bill.invoice_number} untuk ${bill.patient_name} berhasil diproses (Simulasi).`
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

    <!-- Header Section -->
    <MasterDataPageHeader
      title="Billing Pasien"
      subtitle="Daftar tagihan kunjungan pasien beserta filter periode, status bayar, poliklinik, dan jenis jaminan."
      :breadcrumbs="[
        { label: 'Home', active: false },
        { label: 'Billing Pasien', active: true }
      ]"
      badge="Billing Pasien"
    />

    <!-- Page Content -->
    <div class="page-content">
      <BillingDailyListCard
        v-model:searchQuery="searchQuery"
        :records="paginatedRecords"
        :total-count="paginationInfo.total"
        :current-page="pagination.currentPage"
        :last-page="paginationInfo.last_page"
        :per-page="pagination.perPage"
        :from="paginationInfo.from"
        :to="paginationInfo.to"
        :filters="filters"
        :polyclinics="mockPolyclinics"
        :sort-key="sort.key"
        :sort-direction="sort.direction"
        :loading="loading"
        @update:perPage="setPerPage"
        @page-change="setPage"
        @sort="setSort"
        @search="handleApplySearch"
        @reset="handleResetFilter"
        @open-bill="handleOpenBill"
        @call-patient="handleCallPatient"
      />
    </div>

    <!-- Modals -->
    <BillingPaymentModal
      :is-open="isPaymentModalOpen"
      :bill="activeBill"
      :is-settle-mode="isSettleMode"
      @close="isPaymentModalOpen = false"
      @confirm="handleConfirmPayment"
    />

    <BillingVerifyModal
      :is-open="isVerifyModalOpen"
      :bill="activeBill"
      @close="isVerifyModalOpen = false"
      @confirm="handleConfirmVerify"
    />

    <BillingReceiptModal
      :is-open="isReceiptModalOpen"
      :bill="activeBill"
      @close="isReceiptModalOpen = false"
      @print="handlePrintReceipt"
    />

    <BillingDetailModal
      :is-open="isDetailModalOpen"
      :bill="activeBill"
      @close="isDetailModalOpen = false"
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
</style>
