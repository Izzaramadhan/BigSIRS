<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import BillingOrdersTab from './components/BillingOrdersTab.vue';
import BillingOrderCategoryTab from './components/BillingOrderCategoryTab.vue';
import BillingReceiptModal from './components/BillingReceiptModal.vue';
import { useBilling } from '@/composables/useBilling';

const READ_ONLY_ACTION_TITLE = 'Mode Baca Saja (Fase 2B.19A)';
const READ_ONLY_ACTION_MESSAGE = 'Perubahan item, diskon, dan transaksi pembayaran dinonaktifkan sampai aturan bisnis Fase 2B.19B ditetapkan.';

const route = useRoute();
const router = useRouter();

const {
  polyclinics,
  mockBillingPaymentMethods,
  mockOrderDiscountOptions,
  mockBillingOrderCatalogs,
  loadPolyclinics,
  fetchBillingDetail
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
const isLoading = ref(false);
const loadError = ref(null);
const isReceiptModalOpen = ref(false);
let latestLoadSeq = 0;

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

const loadBillData = async () => {
  const visitId = route?.params?.id;
  const seq = ++latestLoadSeq;
  loadError.value = null;

  if (!visitId) {
    activeBill.value = null;
    orderList.value = [];
    loadError.value = 'Identifier kunjungan (visit_id) tidak tersedia.';
    return;
  }

  isLoading.value = true;
  const res = await fetchBillingDetail(visitId);
  if (seq !== latestLoadSeq) return;
  isLoading.value = false;

  if (!res.success || !res.data) {
    activeBill.value = null;
    orderList.value = [];
    loadError.value = res.message || 'Gagal memuat rincian tagihan kunjungan.';
    return;
  }

  activeBill.value = { ...res.data };
  orderList.value = Array.isArray(res.data.items)
    ? res.data.items.map((item) => ({ ...item }))
    : [];
};

onMounted(async () => {
  await Promise.all([loadPolyclinics(), loadBillData()]);
});

watch(() => route?.params?.id, () => {
  loadBillData();
});

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
    if (item.price === null || item.price === undefined || item.pricing_status === 'unpriced') {
      return acc;
    }
    const gross = Number(item.price) * (Math.max(1, Number(item.qty) || 1));
    const discPct = Math.min(100, Math.max(0, Number(item.discount_percent) || 0));
    return acc + Math.max(0, Math.round(gross * (1 - discPct / 100)));
  }, 0);

  return {
    ...activeBill.value,
    read_only_mode: true,
    total_amount: activeBill.value.verified_subtotal ?? totalSum,
    items: orderList.value.map((ord) => {
      const hasPrice = ord.price !== null && ord.price !== undefined && ord.pricing_status !== 'unpriced';
      const qty = Math.max(1, Number(ord.qty) || 1);
      const discPct = Math.min(100, Math.max(0, Number(ord.discount_percent) || 0));
      const unitPrice = hasPrice ? Number(ord.price) : null;
      return {
        name: ord.name,
        category: ord.jenis,
        qty,
        unit_price: unitPrice,
        total: hasPrice ? Math.max(0, Math.round(unitPrice * qty * (1 - discPct / 100))) : null
      };
    })
  };
});

const notifyReadOnlyBlocked = () => {
  showToast({
    type: 'warning',
    title: READ_ONLY_ACTION_TITLE,
    message: READ_ONLY_ACTION_MESSAGE
  });
};

const handleUpdateDiscount = () => {
  notifyReadOnlyBlocked();
};

const handleRemoveOrder = () => {
  notifyReadOnlyBlocked();
};

const handleAddCategoryOrder = () => {
  notifyReadOnlyBlocked();
};

const handleSubmitPayment = () => {
  notifyReadOnlyBlocked();
};

const handleCancelPayment = () => {
  notifyReadOnlyBlocked();
};

const handlePrintOrder = () => {
  if (!activeBill.value) return;
  isReceiptModalOpen.value = true;
};

const handlePrintReceipt = (bill) => {
  isReceiptModalOpen.value = false;
  showToast({
    type: 'info',
    title: 'Pratinjau Kuitansi Billing',
    message: `Pratinjau tagihan ${bill?.invoice_number || bill?.visit_number || ''} untuk ${bill?.patient_name || ''} dibuka dalam mode baca saja.`
  });
};

const handleCallPatient = () => {
  if (!activeBill.value) return;
  showToast({
    type: 'info',
    title: 'Informasi Kunjungan Pasien',
    message: `Antrean ${activeBill.value.queue_number || activeBill.value.visit_number || '-'} (${activeBill.value.patient_name}) berada pada tampilan rincian tagihan read-only.`
  });
};

const handleRefreshVisit = async () => {
  await loadBillData();
  if (!loadError.value) {
    showToast({
      type: 'info',
      title: 'Data Diperbarui',
      message: 'Rincian tagihan kunjungan dimuat ulang dari backend.'
    });
  }
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

    <div v-if="isLoading && !activeBill" class="card billing-state-card">
      <p class="billing-state-text">Memuat rincian tagihan kunjungan dari backend...</p>
    </div>

    <div v-else-if="loadError" class="card billing-state-card billing-state-card--error" role="alert">
      <p class="billing-state-title">Gagal Memuat Rincian Tagihan</p>
      <p class="billing-state-text">{{ loadError }}</p>
      <div class="billing-state-actions">
        <button type="button" class="btn-retry-bill" @click="loadBillData">
          Muat Ulang
        </button>
        <button type="button" class="btn-header-back" @click="goBack">
          Kembali ke Billing
        </button>
      </div>
    </div>

    <div v-else-if="activeBill" class="page-content">
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
          :polyclinics="polyclinics"
          :payment-methods="mockBillingPaymentMethods"
          :discount-options="mockOrderDiscountOptions"
          :default-payment-method="normalizedDefaultPayMethod"
          :default-polyclinic="activeBill.polyclinic"
          :read-only-mode="true"
          :unpriced-count="activeBill.unpriced_item_count || 0"
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
          :read-only-mode="true"
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

.billing-state-card {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.billing-state-card--error {
  border-color: #fecaca;
  background: #fef2f2;
  color: #991b1b;
}

.billing-state-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
}

.billing-state-text {
  margin: 0;
  font-size: 0.875rem;
}

.billing-state-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn-retry-bill {
  height: 40px;
  padding: 0 1rem;
  border-radius: 8px;
  border: 1px solid #dc2626;
  background: #dc2626;
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
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

