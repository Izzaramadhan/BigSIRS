<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import BillingOrdersTab from './components/BillingOrdersTab.vue';
import BillingOrderCategoryTab from './components/BillingOrderCategoryTab.vue';
import BillingReceiptModal from './components/BillingReceiptModal.vue';
import { useBilling } from '@/composables/useBilling';

const route = useRoute();
const router = useRouter();

const {
  billingList,
  mockPolyclinics,
  mockBillingPaymentMethods,
  mockOrderDiscountOptions,
  mockBillingOrderCatalogs,
  getBillById,
  getBillOrders,
  saveBillOrders
} = useBilling();

const activeTab = ref('orders');

const tabs = [
  { key: 'orders', label: 'Orders' },
  { key: 'tindakan', label: 'Tindakan', jenis: 'TND', catalogKey: 'tindakan' },
  { key: 'resep', label: 'Resep', jenis: 'RSP', catalogKey: 'resep' },
  { key: 'laboratorium', label: 'Laboratorium', jenis: 'LAB', catalogKey: 'laboratorium' },
  { key: 'radiologi', label: 'Radiologi', jenis: 'RAD', catalogKey: 'radiologi' },
  { key: 'ambulance', label: 'Ambulance', jenis: 'AMB', catalogKey: 'ambulance' }
];

const activeBill = ref(null);
const orderList = ref([]);
const isReceiptModalOpen = ref(false);

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

const loadBillData = () => {
  const id = route?.params?.id;
  const found = getBillById(id) || billingList.value[0];
  if (!found) return;
  activeBill.value = { ...found };
  orderList.value = getBillOrders(found.id);
};

loadBillData();

watch(() => route?.params?.id, loadBillData);

const normalizedDefaultPayMethod = computed(() => {
  const raw = String(activeBill.value?.guarantor_type || '').toLowerCase();
  if (raw.includes('bpjs')) return 'BPJS Kesehatan';
  if (raw.includes('inhealth')) return 'Mandiri Inhealth';
  if (raw.includes('asuransi')) return 'Asuransi Swasta';
  return 'UMUM';
});

const activeCategoryTabConfig = computed(() => {
  return tabs.find((t) => t.key === activeTab.value && t.jenis) || null;
});

const activeCategoryCatalog = computed(() => {
  if (!activeCategoryTabConfig.value) return [];
  return mockBillingOrderCatalogs[activeCategoryTabConfig.value.catalogKey] || [];
});

const receiptBillSnapshot = computed(() => {
  if (!activeBill.value) return null;
  const totalSum = orderList.value.reduce((acc, item) => {
    const gross = (Number(item.price) || 0) * (Math.max(1, Number(item.qty) || 1));
    const discPct = Math.min(100, Math.max(0, Number(item.discount_percent) || 0));
    return acc + Math.max(0, Math.round(gross * (1 - discPct / 100)));
  }, 0);

  return {
    ...activeBill.value,
    total_amount: totalSum,
    items: orderList.value.map((ord) => {
      const gross = (Number(ord.price) || 0) * (Math.max(1, Number(ord.qty) || 1));
      const discPct = Math.min(100, Math.max(0, Number(ord.discount_percent) || 0));
      return {
        name: ord.name,
        category: ord.jenis,
        qty: Number(ord.qty) || 1,
        unit_price: Number(ord.price) || 0,
        total: Math.max(0, Math.round(gross * (1 - discPct / 100)))
      };
    })
  };
});

const persistOrders = (nextOrders, meta = {}) => {
  if (!activeBill.value) return;
  const res = saveBillOrders(activeBill.value.id, nextOrders, meta);
  if (res.success) {
    orderList.value = res.orders;
    activeBill.value = { ...res.bill };
  }
};

const handleUpdateDiscount = ({ id, discount }) => {
  const next = orderList.value.map((item) =>
    item.id === id ? { ...item, discount_percent: discount } : item
  );
  persistOrders(next);
};

const handleRemoveOrder = (id) => {
  const target = orderList.value.find((item) => item.id === id);
  const next = orderList.value.filter((item) => item.id !== id);
  persistOrders(next);
  showToast({
    type: 'warning',
    title: 'Item Order Dihapus',
    message: target ? `${target.name} dihapus dari daftar order pasien.` : 'Item order dihapus.'
  });
};

const handleAddCategoryOrder = (newItem) => {
  const created = {
    id: `${activeBill.value?.id || 'BILL'}-ORD-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
    order_date: newItem.order_date,
    jenis: newItem.jenis,
    name: newItem.name,
    qty: newItem.qty,
    price: newItem.price,
    discount_percent: 0,
    status: 'Belum Bayar'
  };
  const next = [...orderList.value, created];
  persistOrders(next);
  showToast({
    type: 'success',
    title: 'Order Ditambahkan',
    message: `${created.name} (${created.jenis}) berhasil ditambahkan ke LIST ORDERS.`
  });
};

const handleSubmitPayment = (payload) => {
  if (!payload.selectedIds || payload.selectedIds.length === 0) {
    showToast({
      type: 'warning',
      title: 'Pilih Item Order',
      message: 'Centang minimal 1 item order terlebih dahulu sebelum menekan Submit.'
    });
    return;
  }

  const selectedSet = new Set(payload.selectedIds);
  const next = orderList.value.map((item) =>
    selectedSet.has(item.id) ? { ...item, status: 'Lunas' } : item
  );

  persistOrders(next, {
    payment_method: payload.paymentMethod,
    polyclinic: payload.polyclinic
  });

  showToast({
    type: 'success',
    title: 'Pembayaran Berhasil Diproses',
    message: `${payload.selectedIds.length} item order untuk ${activeBill.value?.patient_name} berhasil dibayar (${payload.paymentMethod}).`
  });
};

const handleCancelPayment = ({ selectedIds }) => {
  const selectedSet = new Set(selectedIds || []);
  const paidTargets = orderList.value.filter(
    (item) => selectedSet.has(item.id) && item.status === 'Lunas'
  );

  if (paidTargets.length === 0) {
    showToast({
      type: 'warning',
      title: 'Tidak Ada Item Terbayar Dipilih',
      message: 'Centang minimal 1 item order berstatus sudah bayar untuk membatalkan pembayaran.'
    });
    return;
  }

  const next = orderList.value.map((item) =>
    selectedSet.has(item.id) && item.status === 'Lunas'
      ? { ...item, status: 'Belum Bayar' }
      : item
  );
  persistOrders(next);

  showToast({
    type: 'info',
    title: 'Pembayaran Dibatalkan',
    message: `${paidTargets.length} item order dikembalikan ke status belum bayar.`
  });
};

const handlePrintOrder = () => {
  isReceiptModalOpen.value = true;
};

const handlePrintReceipt = (bill) => {
  isReceiptModalOpen.value = false;
  showToast({
    type: 'success',
    title: 'Cetak Kuitansi Billing',
    message: `Kuitansi ${bill?.invoice_number || ''} untuk ${bill?.patient_name || ''} siap dicetak (Simulasi).`
  });
};

const handleCallPatient = () => {
  if (!activeBill.value) return;
  showToast({
    type: 'success',
    title: 'Memanggil Pasien ke Kasir',
    message: `Nomor antrean ${activeBill.value.queue_number || activeBill.value.visit_number} (${activeBill.value.patient_name}) dipanggil ke Loket Billing.`
  });
};

const handleRefreshVisit = () => {
  loadBillData();
  showToast({
    type: 'info',
    title: 'Data Diperbarui',
    message: 'Data kunjungan dan daftar order billing pasien dimuat ulang.'
  });
};

const goBack = () => {
  if (router) {
    router.push({ name: 'clinical.billing' });
  }
};
</script>

<template>
  <div class="page-container billing-order-page">
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Standardized Header -->
    <MasterDataPageHeader
      title="Billing Order"
      subtitle="Billing order — kelola tagihan layanan, diskon item, cara bayar, dan cetak kuitansi pasien."
      :breadcrumbs="[
        { label: 'Home', active: false },
        { label: 'Billing', active: false },
        { label: 'Billing Order', active: true }
      ]"
      badge="Billing Order"
    >
      <template #actions>
        <button type="button" class="btn-header-back" @click="goBack">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
          <span>Kembali ke Billing</span>
        </button>
      </template>
    </MasterDataPageHeader>

    <div v-if="activeBill" class="page-content">
      <!-- Patient Profile Summary + Tabs Header Card -->
      <div class="card patient-banner-card">
        <div class="patient-banner-body">
          <!-- Left: Patient Portrait & Details -->
          <div class="patient-identity-block">
            <div class="patient-avatar-box" aria-hidden="true">
              <svg viewBox="0 0 64 64" fill="none">
                <rect width="64" height="64" rx="12" fill="#e2e8f0" />
                <circle cx="32" cy="24" r="11" fill="#94a3b8" />
                <path d="M12 56c0-10.5 9-18 20-18s20 7.5 20 18" fill="#64748b" />
              </svg>
            </div>

            <div class="patient-meta-content">
              <div class="patient-name-line">
                <h2 class="patient-hero-name">{{ activeBill.patient_name }}</h2>
                <span class="verified-check-badge" title="Identitas Pasien Terverifikasi">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l2.4 2.1 3.2-.3.9 3.1 2.9 1.3-.7 3.1 1.6 2.8-2.3 2.2.1 3.2-3.1.8-1.4 2.9-3.1-.7-2.8 1.6-2.2-2.3-3.2.1-.8-3.1-2.9-1.4.7-3.1-1.6-2.8 2.3-2.2-.1-3.2 3.1-.8L9.6 2z" opacity="0.16" />
                    <circle cx="12" cy="12" r="9.5" fill="#0284c7" />
                    <path d="M8.5 12.2l2.3 2.3 4.8-4.8" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                  </svg>
                </span>
              </div>

              <p class="patient-address-line">{{ activeBill.address || '-' }}</p>

              <div class="patient-icons-row">
                <span class="meta-chip" title="Tempat Lahir">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>
                  <span>{{ activeBill.birth_place || '-' }}</span>
                </span>

                <span class="meta-chip font-mono" title="Tanggal Lahir">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                  <span>{{ activeBill.birth_date || '-' }}</span>
                </span>

                <span class="meta-chip font-mono" title="No. Rekam Medis">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"></path>
                  </svg>
                  <span>{{ activeBill.medical_record_number }}</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Right: Kunjungan RS + Refresh + Panggil -->
          <div class="patient-visit-actions">
            <div class="visit-header-row">
              <div class="visit-label-stack">
                <span class="visit-type-title">Kunjungan RS</span>
                <span class="visit-poly-sub">{{ activeBill.polyclinic }} &bull; {{ activeBill.queue_number }}</span>
              </div>
              <button
                type="button"
                class="btn-refresh-visit"
                title="Muat ulang data kunjungan"
                aria-label="Muat ulang data kunjungan"
                @click="handleRefreshVisit"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="23 4 23 10 17 10"></polyline>
                  <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
              </button>
            </div>

            <button type="button" class="btn-call-patient" @click="handleCallPatient">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
              </svg>
              <span>Panggil</span>
            </button>
          </div>
        </div>

        <!-- 6-Tab Strip -->
        <div class="billing-tabs-bar" role="tablist" aria-label="Menu Billing Order">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            role="tab"
            class="billing-tab-btn"
            :class="{ 'is-active': activeTab === tab.key }"
            :aria-selected="activeTab === tab.key"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Tab Content Workspace Card -->
      <div class="card orders-workspace-card">
        <BillingOrdersTab
          v-if="activeTab === 'orders'"
          :orders="orderList"
          :polyclinics="mockPolyclinics"
          :payment-methods="mockBillingPaymentMethods"
          :discount-options="mockOrderDiscountOptions"
          :default-payment-method="normalizedDefaultPayMethod"
          :default-polyclinic="activeBill.polyclinic"
          @update-discount="handleUpdateDiscount"
          @remove-order="handleRemoveOrder"
          @submit-payment="handleSubmitPayment"
          @cancel-payment="handleCancelPayment"
          @print-order="handlePrintOrder"
          @cancel="goBack"
        />

        <BillingOrderCategoryTab
          v-else-if="activeCategoryTabConfig"
          :key="activeCategoryTabConfig.key"
          :title="activeCategoryTabConfig.label"
          :jenis="activeCategoryTabConfig.jenis"
          :catalog="activeCategoryCatalog"
          :orders="orderList"
          :service-date="activeBill.service_date"
          @add-order="handleAddCategoryOrder"
          @remove-order="handleRemoveOrder"
        />
      </div>
    </div>

    <!-- Print Receipt Modal -->
    <BillingReceiptModal
      :is-open="isReceiptModalOpen"
      :bill="receiptBillSnapshot"
      @close="isReceiptModalOpen = false"
      @print="handlePrintReceipt"
    />
  </div>
</template>

<style scoped>
.billing-order-page {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  font-size: 0.875rem;
  animation: fadeIn 0.25s ease-out;
}

.billing-order-page :deep(button),
.billing-order-page :deep(input),
.billing-order-page :deep(select),
.billing-order-page :deep(textarea) {
  font-family: inherit;
}

.billing-order-page :deep(strong),
.billing-order-page :deep(b) {
  font-weight: 700;
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

.page-content {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.btn-header-back {
  height: 40px;
  padding: 0 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: #ffffff;
  color: var(--color-text-navy);
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.btn-header-back:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-header-back svg {
  width: 16px;
  height: 16px;
}

/* Cards */
.patient-banner-card,
.orders-workspace-card {
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  overflow: hidden;
}

.patient-banner-body {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1.5rem;
  padding: 1.35rem 1.5rem 1.15rem;
  background: #ffffff;
  flex-wrap: wrap;
}

.patient-identity-block {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  min-width: 0;
}

.patient-avatar-box {
  width: 88px;
  height: 88px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--color-border-soft);
  flex-shrink: 0;
}

.patient-avatar-box svg {
  width: 100%;
  height: 100%;
  display: block;
}

.patient-meta-content {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.patient-name-line {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.patient-hero-name {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--color-primary);
  text-transform: uppercase;
  letter-spacing: 0.01em;
}

.verified-check-badge {
  width: 20px;
  height: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #0284c7;
}

.verified-check-badge svg {
  width: 18px;
  height: 18px;
}

.patient-address-line {
  margin: 0;
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.patient-icons-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  margin-top: 0.15rem;
}

.meta-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.82rem;
  font-weight: 500;
  color: var(--color-text-navy);
}

.meta-chip svg {
  width: 14px;
  height: 14px;
  color: var(--color-text-secondary);
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-variant-numeric: tabular-nums;
}

/* Right Visit Block */
.patient-visit-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.65rem;
  min-width: 190px;
}

.visit-header-row {
  width: 100%;
  display: flex;
  align-items: flex-start;
  justify-content: flex-end;
  gap: 0.75rem;
}

.visit-label-stack {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.12rem;
}

.visit-type-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #e11d48;
}

.visit-poly-sub {
  font-size: 0.78rem;
  color: var(--color-text-secondary);
  font-weight: 500;
}

.btn-refresh-visit {
  width: 30px;
  height: 30px;
  border-radius: 7px;
  border: 1px solid var(--color-border-soft);
  background: #ffffff;
  color: var(--color-text-secondary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-refresh-visit:hover {
  background: #f8fafc;
  color: var(--color-primary);
}

.btn-refresh-visit svg {
  width: 15px;
  height: 15px;
}

.btn-call-patient {
  height: 36px;
  padding: 0 1.1rem;
  border-radius: 7px;
  border: none;
  background: #0284c7;
  color: #ffffff;
  font-size: 0.84rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.btn-call-patient:hover {
  background: #0369a1;
}

.btn-call-patient svg {
  width: 15px;
  height: 15px;
}

/* Tabs Bar */
.billing-tabs-bar {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  background: #ffffff;
  overflow-x: auto;
}

.billing-tab-btn {
  position: relative;
  padding: 0.85rem 1.15rem;
  border: none;
  border-bottom: 2px solid transparent;
  background: transparent;
  color: var(--color-text-secondary);
  font-size: 0.86rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.billing-tab-btn:hover {
  color: var(--color-primary);
}

.billing-tab-btn.is-active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
  font-weight: 700;
}

@media (max-width: 768px) {
  .patient-banner-body {
    flex-direction: column;
    align-items: stretch;
  }

  .patient-visit-actions {
    align-items: flex-start;
  }

  .visit-header-row {
    justify-content: space-between;
  }

  .visit-label-stack {
    align-items: flex-start;
  }
}
</style>

