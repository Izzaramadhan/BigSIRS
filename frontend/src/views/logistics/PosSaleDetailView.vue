<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { usePosSales } from '@/composables/usePosSales';
import AppToast from '@/components/common/AppToast.vue';
import PosSaleStockSelect from './components/PosSaleStockSelect.vue';

const route = useRoute();
const router = useRouter();

const {
  warehouses,
  fetchFormOptions,
  fetchAvailableStocks,
  getPosSale,
  addItem,
  updateItem,
  deleteItem,
  formatDateIndo,
  formatRupiah
} = usePosSales();

const sale = ref(null);
const loading = ref(true);
const submitting = ref(false);
const isFormCollapsed = ref(false);

// Form Detail state
const formDetail = reactive({
  warehouseId: 1,
  warehouseName: 'Gudang Utama',
  stockId: '',
  medicineId: '',
  quantity: 1,
  notes: ''
});

const selectedMedicine = ref(null);
const formErrors = ref({});

// Table controls
const recordsPerPage = ref(10);
const tableSearch = ref('');
const currentPage = ref(1);

// Edit Item Modal state
const isEditItemModalOpen = ref(false);
const itemToEdit = ref(null);
const editQuantity = ref(1);
const editErrors = ref('');

// Delete Item Dialog state
const isDeleteItemDialogOpen = ref(false);
const itemToDelete = ref(null);

// Toast state
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

// Normalized warehouses
const normalizedWarehouses = computed(() => {
  if (!warehouses.value || warehouses.value.length === 0) {
    return [
      { id: 1, name: 'Gudang Utama' },
      { id: 2, name: 'Apotek Rawat Jalan' },
      { id: 3, name: 'Apotek IGD' }
    ];
  }
  return warehouses.value.map((w, index) => {
    if (typeof w === 'object' && w !== null) {
      return { id: w.id || index + 1, name: w.name };
    }
    return { id: index + 1, name: String(w) };
  });
});

// Load Sale Detail
const loadSaleDetail = async () => {
  loading.value = true;
  const res = await getPosSale(route.params.id);
  if (res.success) {
    sale.value = res.data;
  } else {
    showToast({
      type: 'error',
      title: 'Gagal Memuat Data',
      message: res.message || 'Dokumen penjualan bebas tidak ditemukan.'
    });
  }
  loading.value = false;
};

onMounted(async () => {
  await Promise.all([fetchFormOptions(), loadSaleDetail()]);
  if (normalizedWarehouses.value.length > 0 && !formDetail.warehouseId) {
    formDetail.warehouseId = normalizedWarehouses.value[0].id;
    formDetail.warehouseName = normalizedWarehouses.value[0].name;
  }
  if (formDetail.warehouseId) {
    await fetchAvailableStocks(formDetail.warehouseId);
  }
});

// Watch warehouse selection in form
watch(
  () => formDetail.warehouseId,
  async (newId) => {
    const match = normalizedWarehouses.value.find((w) => String(w.id) === String(newId));
    if (match) {
      formDetail.warehouseName = match.name;
    }
    // When warehouse changes, clear previous medicine selection
    selectedMedicine.value = null;
    formDetail.stockId = '';
    formDetail.medicineId = '';
    formDetail.quantity = 1;
    if (newId) {
      await fetchAvailableStocks(newId);
    }
  }
);

// Stock Selected Handler
const handleStockSelected = (stockItem) => {
  selectedMedicine.value = stockItem;
  formDetail.stockId = stockItem.stock_id || stockItem.id;
  formDetail.medicineId = stockItem.item_id || stockItem.medicine_id || stockItem.medicineId;
  formDetail.quantity = 1;
  formErrors.value = {};
};

// Stock Cleared Handler
const handleStockCleared = () => {
  selectedMedicine.value = null;
  formDetail.stockId = '';
  formDetail.medicineId = '';
  formDetail.quantity = 1;
};

// Max available stock
const maxAvailableStock = computed(() => {
  if (!selectedMedicine.value) return 0;
  return Number(selectedMedicine.value.available_stock ?? selectedMedicine.value.availableStock ?? 0);
});

// Quantity exceeds validation
const isQuantityExceeded = computed(() => {
  if (!selectedMedicine.value) return false;
  return Number(formDetail.quantity) > maxAvailableStock.value;
});

// Preview Subtotal in form
const previewSubtotal = computed(() => {
  if (!selectedMedicine.value) return 0;
  const price = Number(
    selectedMedicine.value.selling_price ??
    selectedMedicine.value.price ??
    selectedMedicine.value.unitPrice ??
    0
  );
  return price * (Number(formDetail.quantity) || 0);
});

// Is Form Valid
const isFormValid = computed(() => {
  return (
    formDetail.warehouseId &&
    formDetail.medicineId &&
    formDetail.quantity > 0 &&
    !isQuantityExceeded.value
  );
});

// Reset Detail Form
const resetDetailForm = () => {
  selectedMedicine.value = null;
  formDetail.stockId = '';
  formDetail.medicineId = '';
  formDetail.quantity = 1;
  formDetail.notes = '';
  formErrors.value = {};
};

// Add Detail Item
const handleAddItem = async () => {
  formErrors.value = {};
  if (!formDetail.warehouseId) {
    formErrors.value.warehouseId = 'Gudang wajib dipilih.';
    return;
  }
  if (!formDetail.medicineId) {
    formErrors.value.medicineId = 'Nama sediaan obat wajib dipilih.';
    return;
  }
  if (!formDetail.quantity || formDetail.quantity <= 0) {
    formErrors.value.quantity = 'Jumlah sediaan harus lebih besar dari 0.';
    return;
  }
  if (isQuantityExceeded.value) {
    formErrors.value.quantity = `Jumlah melebihi stok yang tersedia (Maks. ${maxAvailableStock.value}).`;
    return;
  }

  submitting.value = true;
  const unitPrice = Number(
    selectedMedicine.value?.selling_price ??
    selectedMedicine.value?.price ??
    selectedMedicine.value?.unitPrice ??
    0
  );

  const payload = {
    warehouse_id: formDetail.warehouseId,
    warehouse_stock_id: formDetail.stockId,
    medicine_id: formDetail.medicineId,
    quantity: Number(formDetail.quantity),
    price: unitPrice,
    unit_price: unitPrice,
    unit: selectedMedicine.value?.unit_name || selectedMedicine.value?.unit || 'Unit',
    batch_number: selectedMedicine.value?.batch_number || null,
    expired_date: selectedMedicine.value?.expiry_date || selectedMedicine.value?.expired_date || null,
    notes: formDetail.notes
  };

  const res = await addItem(sale.value.id, payload);
  if (res.success) {
    showToast({
      type: 'success',
      title: 'Sediaan Ditambahkan',
      message: 'Detail sediaan berhasil ditambahkan ke transaksi penjualan bebas.'
    });
    resetDetailForm();
    await Promise.all([loadSaleDetail(), fetchAvailableStocks(formDetail.warehouseId)]);
  } else {
    showToast({
      type: 'error',
      title: 'Gagal Menyimpan',
      message: res.message || 'Terjadi kesalahan saat menambahkan detail sediaan.'
    });
  }
  submitting.value = false;
};

// Open Edit Modal
const openEditItemModal = (item) => {
  itemToEdit.value = item;
  editQuantity.value = Number(item.quantity) || 1;
  editErrors.value = '';
  isEditItemModalOpen.value = true;
};

// Save Edit Item
const handleSaveEditItem = async () => {
  if (!editQuantity.value || Number(editQuantity.value) <= 0) {
    editErrors.value = 'Jumlah sediaan harus lebih besar dari 0.';
    return;
  }

  submitting.value = true;
  const res = await updateItem(sale.value.id, itemToEdit.value.id, {
    quantity: Number(editQuantity.value)
  });

  if (res.success) {
    isEditItemModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Perubahan Disimpan',
      message: 'Jumlah sediaan berhasil diperbarui.'
    });
    await Promise.all([loadSaleDetail(), fetchAvailableStocks(formDetail.warehouseId)]);
  } else {
    editErrors.value = res.message || 'Gagal memperbarui jumlah sediaan.';
  }
  submitting.value = false;
};

// Open Delete Dialog
const openDeleteItemDialog = (item) => {
  itemToDelete.value = item;
  isDeleteItemDialogOpen.value = true;
};

// Confirm Delete Item
const handleConfirmDeleteItem = async () => {
  if (!itemToDelete.value) return;

  submitting.value = true;
  const res = await deleteItem(sale.value.id, itemToDelete.value.id);

  if (res.success) {
    isDeleteItemDialogOpen.value = false;
    showToast({
      type: 'success',
      title: 'Item Dihapus',
      message: 'Sediaan berhasil dihapus dan stok telah dikembalikan ke gudang.'
    });
    itemToDelete.value = null;
    await Promise.all([loadSaleDetail(), fetchAvailableStocks(formDetail.warehouseId)]);
  } else {
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: res.message || 'Terjadi kesalahan saat menghapus sediaan.'
    });
  }
  submitting.value = false;
};

// Filtered and paginated detail items
const filteredDetailItems = computed(() => {
  if (!sale.value?.items) return [];
  const q = tableSearch.value.trim().toLowerCase();
  if (!q) return sale.value.items;
  return sale.value.items.filter((it) => {
    const name = (it.medicineName || it.medicine_name || it.name || '').toLowerCase();
    const wh = (it.warehouseName || it.warehouse_name || '').toLowerCase();
    return name.includes(q) || wh.includes(q);
  });
});

const paginatedDetailItems = computed(() => {
  const start = (currentPage.value - 1) * recordsPerPage.value;
  return filteredDetailItems.value.slice(start, start + recordsPerPage.value);
});

const totalDetailPages = computed(() => {
  return Math.ceil(filteredDetailItems.value.length / recordsPerPage.value) || 1;
});

// Total calculation
const calculatedTotal = computed(() => {
  if (!sale.value?.items || sale.value.items.length === 0) return 0;
  return sale.value.items.reduce((sum, item) => sum + (Number(item.subtotal) || 0), 0);
});

const goBack = () => {
  router.push('/logistics/pos');
};

const handlePrintNota = () => {
  showToast({
    type: 'info',
    title: 'Cetak Nota',
    message: 'Mempersiapkan cetak nota penjualan bebas...'
  });
  window.print();
};
</script>

<template>
  <div class="page-container pos-sale-detail-page">
    <!-- Toast Notification -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Header Halaman Standar BigSIRS -->
    <div class="page-header no-print">
      <div class="header-content">
        <div class="breadcrumbs">
          <router-link to="/logistics/pos" class="crumb-link">Logistik</router-link>
          <span class="separator">/</span>
          <router-link to="/logistics/pos" class="crumb-link">Penjualan Bebas</router-link>
          <span class="separator">/</span>
          <span class="current">Detail Penjualan</span>
        </div>
        <div class="title-row">
          <h1 class="page-title">Data Detail Penjualan Bebas</h1>
          <span class="module-badge">
            <span class="bullet-dot">•</span> SIMRS-LOG
          </span>
        </div>
        <p class="page-subtitle">
          Informasi header dan pengelolaan daftar sediaan penjualan bebas.
        </p>
      </div>

      <div class="header-actions">
        <button type="button" class="btn-outline btn-back" @click="goBack">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          Kembali ke Daftar
        </button>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="detail-loading-box no-print">
      <div class="skeleton-card"></div>
      <div class="skeleton-card"></div>
    </div>

    <div v-else-if="sale" class="page-content">
      <!-- 1. Card INFO (Header Data) -->
      <div class="card info-card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
              </svg>
            </span>
            INFO
          </h3>
        </div>
        <div class="card-body">
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Tanggal</span>
              <span class="info-separator">:</span>
              <span class="info-value font-semibold text-navy">
                {{ sale.formattedDate || formatDateIndo(sale.date || sale.transaction_date) }}
              </span>
            </div>
            <div class="info-item">
              <span class="info-label">Pembeli</span>
              <span class="info-separator">:</span>
              <span class="info-value font-semibold text-navy">
                {{ sale.pembeli || sale.customerName || sale.customer_name || '-' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Card FORM DETAIL (Tambah Item) -->
      <div class="card form-card no-print">
        <div class="card-header form-card-header">
          <div class="card-header-left">
            <span class="header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </span>
            <h3 class="card-title">FORM DETAIL</h3>
          </div>
          <button
            type="button"
            class="btn-collapse"
            :aria-expanded="!isFormCollapsed"
            :title="isFormCollapsed ? 'Buka Form Detail' : 'Tutup Form Detail'"
            @click="isFormCollapsed = !isFormCollapsed"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              class="collapse-icon"
              :class="{ 'rotate-180': isFormCollapsed }"
            >
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
        </div>
        <div v-show="!isFormCollapsed" class="card-body">
          <form @submit.prevent="handleAddItem">
            <div class="form-grid">
              <!-- Nama Sediaan (Searchable Select) -->
              <div class="form-col form-col-wide">
                <label for="detail-input-medicine" class="field-label">
                  Nama Sediaan <span class="text-danger">*</span>
                </label>
                <PosSaleStockSelect
                  id="detail-input-medicine"
                  v-model="formDetail.stockId"
                  :warehouse-id="formDetail.warehouseId"
                  :warehouse-name="formDetail.warehouseName"
                  :sale-date="sale?.date || sale?.transaction_date"
                  :error="formErrors.medicineId"
                  @select="handleStockSelected"
                  @clear="handleStockCleared"
                />
                <span v-if="formErrors.medicineId" class="error-msg">{{ formErrors.medicineId }}</span>
              </div>

              <!-- Gudang -->
              <div class="form-col">
                <label for="detail-input-warehouse" class="field-label">
                  Gudang <span class="text-danger">*</span>
                </label>
                <select
                  id="detail-input-warehouse"
                  v-model="formDetail.warehouseId"
                  class="field-select"
                  :class="{ 'is-invalid': formErrors.warehouseId }"
                  required
                >
                  <option
                    v-for="wh in normalizedWarehouses"
                    :key="wh.id"
                    :value="wh.id"
                  >
                    {{ wh.name }}
                  </option>
                </select>
                <span v-if="formErrors.warehouseId" class="error-msg">{{ formErrors.warehouseId }}</span>
              </div>

              <!-- Jumlah -->
              <div class="form-col form-col-sm">
                <label for="detail-input-qty" class="field-label">
                  Jumlah <span class="text-danger">*</span>
                </label>
                <input
                  id="detail-input-qty"
                  v-model.number="formDetail.quantity"
                  type="number"
                  min="1"
                  :max="selectedMedicine ? maxAvailableStock : undefined"
                  class="field-input"
                  :class="{ 'is-invalid': formErrors.quantity || isQuantityExceeded }"
                  :disabled="!selectedMedicine"
                  placeholder="Jumlah"
                  required
                />
                <div v-if="selectedMedicine" class="qty-info-caption">
                  <span v-if="isQuantityExceeded" class="error-msg font-medium">
                    Jumlah melebihi stok (Maks. {{ maxAvailableStock }} {{ selectedMedicine.unit_name || selectedMedicine.unit }}).
                  </span>
                  <span v-else class="text-muted text-xs">
                    Stok tersedia: <strong>{{ maxAvailableStock }} {{ selectedMedicine.unit_name || selectedMedicine.unit }}</strong>
                  </span>
                </div>
                <span v-else-if="formErrors.quantity" class="error-msg">{{ formErrors.quantity }}</span>
              </div>
            </div>

            <!-- Readonly info badges -->
            <div v-if="selectedMedicine" class="stock-info-bar">
              <span class="info-badge">
                <strong>Satuan:</strong> {{ selectedMedicine.unit_name || selectedMedicine.unit }}
              </span>
              <span v-if="selectedMedicine.batch_number" class="info-badge">
                <strong>No. Batch:</strong> {{ selectedMedicine.batch_number }}
              </span>
              <span v-if="selectedMedicine.expiry_date || selectedMedicine.expired_date" class="info-badge">
                <strong>Tgl. Kadaluarsa:</strong> {{ selectedMedicine.expiry_date || selectedMedicine.expired_date }}
              </span>
              <span class="info-badge">
                <strong>Harga Jual:</strong> {{ formatRupiah(selectedMedicine.selling_price ?? selectedMedicine.price ?? selectedMedicine.unitPrice) }}
              </span>
              <span class="info-badge">
                <strong>Stok Tersedia:</strong> {{ selectedMedicine.available_stock ?? selectedMedicine.availableStock }} {{ selectedMedicine.unit_name || selectedMedicine.unit }}
              </span>
              <span class="info-badge info-badge-primary">
                <strong>Sub Total:</strong> {{ formatRupiah(previewSubtotal) }}
              </span>
            </div>

            <!-- Form Action Buttons -->
            <div class="form-actions-divider"></div>
            <div class="form-actions-row">
              <button
                type="submit"
                class="btn-save-blue"
                :disabled="!isFormValid || submitting"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                  <polyline points="17 21 17 13 7 13 7 21"></polyline>
                  <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>{{ submitting ? 'Menyimpan...' : 'Simpan' }}</span>
              </button>

              <button
                type="button"
                class="btn-reset-white"
                :disabled="submitting"
                @click="resetDetailForm"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                <span>Reset</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- 3. Card DETAIL LIST DATA -->
      <div class="card table-card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
              </svg>
            </span>
            DETAIL LIST DATA
          </h3>
        </div>

        <!-- Table Toolbar (records & search) -->
        <div class="detail-table-toolbar no-print">
          <div class="toolbar-left">
            <label for="detail-records-select" class="per-page-label">
              <select
                id="detail-records-select"
                v-model.number="recordsPerPage"
                class="records-select"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
              <span class="records-text">records</span>
            </label>
          </div>

          <div class="toolbar-right">
            <div class="search-input-wrapper">
              <label for="detail-search-input" class="search-label">Search:</label>
              <input
                id="detail-search-input"
                v-model="tableSearch"
                type="text"
                class="search-input"
                placeholder="Cari sediaan..."
              />
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="detail-table">
            <thead>
              <tr>
                <th class="col-no text-center">No.</th>
                <th class="col-med">Nama Sediaan</th>
                <th class="col-price text-right">Harga Jual</th>
                <th class="col-qty text-center">Jumlah</th>
                <th class="col-subtotal text-right">Sub Total</th>
                <th class="col-wh">Gudang</th>
                <th class="col-actions text-center no-print">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="paginatedDetailItems.length === 0">
                <td colspan="7" class="empty-detail-cell text-muted text-center">
                  Belum ada detail sediaan yang ditambahkan pada transaksi ini.
                </td>
              </tr>
              <tr
                v-for="(item, idx) in paginatedDetailItems"
                :key="item.id || idx"
                class="detail-row"
              >
                <!-- 1. No -->
                <td class="col-no text-center text-muted font-medium">
                  {{ (currentPage - 1) * recordsPerPage + idx + 1 }}
                </td>

                <!-- 2. Nama Sediaan + Satuan underneath -->
                <td class="col-med">
                  <div class="med-name font-semibold text-navy">
                    {{ item.medicineName || item.medicine_name || item.name }}
                  </div>
                  <div class="med-unit text-muted">
                    {{ item.unit || 'Unit' }}
                  </div>
                </td>

                <!-- 3. Harga Jual -->
                <td class="col-price text-right font-medium text-navy">
                  {{ item.formattedPrice || formatRupiah(item.unitPrice || item.price || item.unit_price) }}
                </td>

                <!-- 4. Jumlah -->
                <td class="col-qty text-center font-bold text-navy">
                  {{ Number(item.quantity) }}
                </td>

                <!-- 5. Sub Total -->
                <td class="col-subtotal text-right font-bold text-navy">
                  {{ item.formattedSubtotal || formatRupiah(item.subtotal) }}
                </td>

                <!-- 6. Gudang -->
                <td class="col-wh text-navy">
                  {{ item.warehouseName || item.warehouse_name || 'Gudang Utama' }}
                </td>

                <!-- 7. Aksi (Edit, Hapus) -->
                <td class="col-actions text-center no-print">
                  <div class="action-buttons-group">
                    <button
                      type="button"
                      class="btn-action-icon btn-edit"
                      title="Edit Jumlah"
                      @click="openEditItemModal(item)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                    </button>
                    <button
                      type="button"
                      class="btn-action-icon btn-delete"
                      title="Hapus Sediaan"
                      @click="openDeleteItemDialog(item)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Total Summary Bar -->
        <div class="total-summary-bar">
          <span class="total-label font-bold text-navy">Total :</span>
          <span class="total-amount font-bold text-navy">
            {{ formatRupiah(calculatedTotal) }}
          </span>
        </div>

        <!-- Table Pagination Info -->
        <div class="detail-pagination-bar no-print">
          <div class="pagination-info text-muted">
            Showing {{ filteredDetailItems.length > 0 ? (currentPage - 1) * recordsPerPage + 1 : 0 }} to
            {{ Math.min(currentPage * recordsPerPage, filteredDetailItems.length) }} of
            {{ filteredDetailItems.length }} entries
          </div>
          <div v-if="totalDetailPages > 1" class="page-buttons">
            <button
              type="button"
              class="page-btn"
              :disabled="currentPage === 1"
              @click="currentPage--"
            >
              &lt;
            </button>
            <button
              v-for="p in totalDetailPages"
              :key="p"
              type="button"
              class="page-btn"
              :class="{ active: p === currentPage }"
              @click="currentPage = p"
            >
              {{ p }}
            </button>
            <button
              type="button"
              class="page-btn"
              :disabled="currentPage === totalDetailPages"
              @click="currentPage++"
            >
              &gt;
            </button>
          </div>
        </div>

        <!-- Card Footer with Tutup and Print Nota -->
        <div class="card-footer-split no-print">
          <button type="button" class="btn-cancel-footer" @click="goBack">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
            Tutup
          </button>

          <button type="button" class="btn-print-nota" @click="handlePrintNota">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 6 2 18 2 18 9"></polyline>
              <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
              <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Print Nota
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Edit Quantity Item -->
    <Teleport to="body">
      <div v-if="isEditItemModalOpen && itemToEdit" class="modal-overlay" @click.self="isEditItemModalOpen = false">
        <div class="modal-content modal-sm">
          <div class="modal-header">
            <h3 class="modal-title font-bold text-navy">Edit Jumlah Sediaan</h3>
            <button type="button" class="btn-close" @click="isEditItemModalOpen = false">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group mb-2">
              <label class="form-label text-muted">Nama Sediaan</label>
              <div class="font-semibold text-navy">{{ itemToEdit.medicineName || itemToEdit.name }}</div>
            </div>
            <div class="form-group mb-2">
              <label class="form-label text-muted">Harga Jual</label>
              <div class="font-semibold text-navy">{{ formatRupiah(itemToEdit.unitPrice || itemToEdit.price || itemToEdit.unit_price) }}</div>
            </div>
            <div class="form-group">
              <label for="edit-item-quantity" class="form-label">Jumlah <span class="text-danger">*</span></label>
              <input
                id="edit-item-quantity"
                v-model.number="editQuantity"
                type="number"
                min="1"
                class="form-control"
                required
              />
              <span v-if="editErrors" class="invalid-feedback block">{{ editErrors }}</span>
            </div>
            <div class="preview-box mt-2">
              <span class="text-muted">Sub Total Baru: </span>
              <strong class="text-navy">{{ formatRupiah(editQuantity * (itemToEdit.unitPrice || itemToEdit.price || itemToEdit.unit_price || 0)) }}</strong>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-cancel" @click="isEditItemModalOpen = false">Batal</button>
            <button type="button" class="btn-primary" :disabled="submitting" @click="handleSaveEditItem">
              <span>{{ submitting ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Dialog Konfirmasi Hapus Item -->
    <Teleport to="body">
      <div v-if="isDeleteItemDialogOpen && itemToDelete" class="modal-overlay" @click.self="isDeleteItemDialogOpen = false">
        <div class="modal-content modal-sm">
          <div class="modal-header">
            <div class="delete-icon-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </div>
            <div>
              <h3 class="modal-title font-bold text-navy">Hapus Detail Sediaan</h3>
              <p class="modal-subtitle text-xs text-muted">Stok sediaan akan dikembalikan ke gudang.</p>
            </div>
          </div>
          <div class="modal-body">
            <p class="text-sm text-navy">
              Apakah Anda yakin ingin menghapus <strong>{{ itemToDelete.medicineName || itemToDelete.name }}</strong>
              sejumlah <strong>{{ itemToDelete.quantity }} {{ itemToDelete.unit || 'Unit' }}</strong> dari transaksi ini?
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-cancel" :disabled="submitting" @click="isDeleteItemDialogOpen = false">
              Batal
            </button>
            <button type="button" class="btn-danger-confirm" :disabled="submitting" @click="handleConfirmDeleteItem">
              <span>{{ submitting ? 'Menghapus...' : 'Ya, Hapus' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
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

.crumb-link {
  color: var(--color-text-muted, #64748b);
  text-decoration: none;
  transition: color 0.15s ease;
}

.crumb-link:hover {
  color: var(--color-primary, #008779);
}

.breadcrumbs .separator {
  color: #cbd5e1;
}

.breadcrumbs .current {
  color: var(--color-primary, #008779);
  font-weight: 600;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.35rem;
}

.page-title {
  font-size: 1.55rem;
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
  line-height: 1.45;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background-color: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
  padding: 0.55rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-outline:hover {
  background-color: #f8fafc;
  border-color: #94a3b8;
  color: #1e293b;
}

.btn-outline svg {
  width: 16px;
  height: 16px;
}

/* Page Content */
.page-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Card Styling */
.card {
  background: #ffffff;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
  overflow: hidden;
}

.card.form-card {
  overflow: visible;
}

.card-header {
  padding: 0.85rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid #edf2f7;
}

.form-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.card-title {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #334155;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-collapse {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 1px solid #cbd5e1;
  background-color: #ffffff;
  color: #64748b;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 0;
}

.btn-collapse:hover {
  background-color: #f1f5f9;
  color: #1e293b;
  border-color: #94a3b8;
}

.collapse-icon {
  width: 16px;
  height: 16px;
  transition: transform 0.2s ease;
}

.collapse-icon.rotate-180 {
  transform: rotate(180deg);
}

.header-icon {
  display: inline-flex;
  align-items: center;
  color: #0d9488;
}

.header-icon svg {
  width: 16px;
  height: 16px;
}

.card-body {
  padding: 1.25rem 1.5rem;
}

/* Info Grid */
.info-grid {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.info-item {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
}

.info-label {
  width: 140px;
  color: #334155;
  font-weight: 500;
}

.info-separator {
  width: 20px;
  color: #64748b;
}

.info-value {
  color: #0f172a;
}

/* Form Grid */
.form-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 1.25rem;
  align-items: flex-start;
}

@media (max-width: 900px) {
  .form-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}

.form-col {
  display: flex;
  flex-direction: column;
}

.field-label {
  display: block;
  font-size: 0.825rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.4rem;
  min-height: 1.25rem;
  line-height: 1.25rem;
}

.field-select,
.field-input {
  width: 100%;
  padding: 0.6rem 0.85rem;
  font-size: 0.875rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background-color: #ffffff;
  color: #1e293b;
  min-height: 42px;
  height: 42px;
  box-sizing: border-box;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.field-select:focus,
.field-input:focus {
  outline: none;
  border-color: #008779;
  box-shadow: 0 0 0 3px rgba(0, 135, 121, 0.15);
}

.is-invalid {
  border-color: #ef4444 !important;
}

.error-msg {
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.25rem;
  display: block;
}

.qty-info-caption {
  margin-top: 0.25rem;
}

/* Badges Bar */
.stock-info-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 1.25rem;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px dashed #cbd5e1;
}

.info-badge {
  font-size: 0.8rem;
  color: #334155;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 0.25rem 0.6rem;
  border-radius: 6px;
}

.info-badge-primary {
  background: #e0f2f1;
  border-color: #80cbc4;
  color: #008779;
}

/* Form Action Buttons */
.form-actions-divider {
  height: 1px;
  background-color: #edf2f7;
  margin: 1.75rem 0 1.25rem 0;
}

.form-actions-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.btn-save-blue {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  background-color: #3b82f6;
  color: #ffffff;
  min-height: 40px;
  height: 40px;
  padding: 0 1.5rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
  border: 1px solid transparent;
  cursor: pointer;
  box-sizing: border-box;
  transition: all 0.15s ease;
}

.btn-save-blue:hover:not(:disabled) {
  background-color: #2563eb;
}

.btn-save-blue:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-save-blue svg {
  width: 15px;
  height: 15px;
}

.btn-reset-white {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  background-color: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
  min-height: 40px;
  height: 40px;
  padding: 0 1.5rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  box-sizing: border-box;
  transition: all 0.15s ease;
}

.btn-reset-white:hover:not(:disabled) {
  background-color: #f1f5f9;
  color: #1e293b;
}

.btn-reset-white svg {
  width: 15px;
  height: 15px;
}

/* Detail Table Toolbar */
.detail-table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 1.25rem;
  border-bottom: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.per-page-label {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #475569;
}

.records-select {
  padding: 0.3rem 0.6rem;
  font-size: 0.85rem;
  color: #1e293b;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background-color: #fff;
  cursor: pointer;
}

.records-text {
  font-weight: 500;
}

.search-input-wrapper {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.search-label {
  font-size: 0.85rem;
  font-weight: 500;
  color: #475569;
}

.search-input {
  padding: 0.35rem 0.75rem;
  font-size: 0.85rem;
  color: #1e293b;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  width: 180px;
}

.search-input:focus {
  outline: none;
  border-color: #008779;
}

/* Detail Table */
.detail-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.detail-table thead th {
  background-color: #f8fafc;
  color: #475569;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.85rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}

.detail-table tbody td {
  padding: 0.9rem 1.25rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  vertical-align: middle;
}

.detail-row:hover td {
  background-color: #f8fbfb;
}

.med-name {
  font-size: 0.875rem;
  color: #0f172a;
}

.med-unit {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.15rem;
}

.text-right {
  text-align: right;
}

.text-center {
  text-align: center;
}

.empty-detail-cell {
  padding: 2.5rem 1rem !important;
}

/* Total Summary Bar */
.total-summary-bar {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  background-color: #ffffff;
  border-top: 1px solid #f1f5f9;
  font-size: 1.15rem;
}

.total-label {
  color: #0f172a;
}

.total-amount {
  color: #0f172a;
}

/* Detail Pagination Bar */
.detail-pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 1.25rem;
  border-top: 1px solid #f1f5f9;
  font-size: 0.85rem;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.page-buttons {
  display: inline-flex;
  gap: 0.25rem;
}

.page-btn {
  padding: 0.25rem 0.65rem;
  font-size: 0.85rem;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  border-radius: 4px;
  cursor: pointer;
}

.page-btn.active {
  background-color: #0284c7;
  color: #ffffff;
  border-color: #0284c7;
}

/* Card Footer with Split Buttons */
.card-footer-split {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-top: 1px solid #f1f5f9;
  background: #f8fafc;
}

.btn-cancel-footer {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1.15rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 6px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-cancel-footer:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.btn-cancel-footer svg {
  width: 15px;
  height: 15px;
}

.btn-print-nota {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 1.35rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 6px;
  background-color: #06b6d4;
  color: #ffffff;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.15s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn-print-nota:hover {
  background-color: #0891b2;
}

.btn-print-nota svg {
  width: 16px;
  height: 16px;
}

/* Action Buttons */
.action-buttons-group {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-action-icon {
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-action-icon svg {
  width: 15px;
  height: 15px;
}

.btn-action-icon.btn-edit:hover {
  background-color: #f0fdf4;
  border-color: #86efac;
  color: #16a34a;
}

.btn-action-icon.btn-delete:hover {
  background-color: #fef2f2;
  border-color: #fca5a5;
  color: #dc2626;
}

/* Modal and Dialogs */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1.25rem;
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.15rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  background: #f8fafc;
}

.modal-title {
  margin: 0;
  font-size: 1.1rem;
}

.btn-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.35rem;
  display: flex;
}

.btn-close:hover {
  color: #1e293b;
}

.btn-close svg {
  width: 18px;
  height: 18px;
}

.modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #f1f5f9;
  background: #f8fafc;
}

.btn-cancel {
  padding: 0.55rem 1.15rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 6px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
  cursor: pointer;
}

.btn-cancel:hover {
  background: #f1f5f9;
}

.btn-primary {
  padding: 0.55rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 6px;
  background-color: #008779;
  color: #ffffff;
  border: none;
  cursor: pointer;
}

.btn-primary:hover:not(:disabled) {
  background-color: #007366;
}

.btn-danger-confirm {
  padding: 0.55rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 6px;
  background-color: #ef4444;
  color: #ffffff;
  border: none;
  cursor: pointer;
}

.btn-danger-confirm:hover:not(:disabled) {
  background-color: #dc2626;
}

.delete-icon-box {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background-color: #fee2e2;
  color: #dc2626;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 0.75rem;
}

.delete-icon-box svg {
  width: 20px;
  height: 20px;
}

.text-navy {
  color: #0f172a;
}

.text-muted {
  color: #64748b;
}

.font-medium {
  font-weight: 500;
}

.font-semibold {
  font-weight: 600;
}

.font-bold {
  font-weight: 700;
}

.text-danger {
  color: #ef4444;
}

/* Skeletons */
.detail-loading-box {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.skeleton-card {
  height: 200px;
  border-radius: 8px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Print styling */
@media print {
  .no-print {
    display: none !important;
  }

  .page-container {
    padding: 0 !important;
  }

  .card {
    border: none !important;
    box-shadow: none !important;
  }

  .card-header {
    background: transparent !important;
    border-bottom: 2px solid #000 !important;
  }

  .detail-table thead th {
    border-bottom: 2px solid #000 !important;
    color: #000 !important;
  }

  .detail-table tbody td {
    border-bottom: 1px solid #ddd !important;
    color: #000 !important;
  }

  .total-summary-bar {
    font-size: 1.25rem !important;
    border-top: 2px solid #000 !important;
  }
}
</style>

