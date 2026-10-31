<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useGoodsIssues } from '@/composables/useGoodsIssues';
import AppToast from '@/components/common/AppToast.vue';
import GoodsIssueStockSelect from './components/GoodsIssueStockSelect.vue';

const route = useRoute();
const router = useRouter();

const {
  warehouses,
  catalogMedicines,
  fetchFormOptions,
  fetchAvailableStocks,
  getGoodsIssue,
  addItem,
  updateItem,
  deleteItem,
  formatDateIndo,
  formatRupiah
} = useGoodsIssues();

const issue = ref(null);
const loading = ref(true);
const submitting = ref(false);

// Form Detail state
const formDetail = reactive({
  warehouseId: '',
  warehouseName: '',
  stockId: '',
  medicineId: '',
  quantity: 1,
  notes: ''
});

const selectedMedicine = ref(null);
const formErrors = ref({});

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
  return warehouses.value.map((w, index) => {
    if (typeof w === 'object' && w !== null) {
      return { id: w.id || index + 1, name: w.name };
    }
    return { id: index + 1, name: String(w) };
  });
});

// Load Issue Detail
const loadIssueDetail = async () => {
  loading.value = true;
  const res = await getGoodsIssue(route.params.id);
  if (res.success) {
    issue.value = res.data;
  } else {
    showToast({
      type: 'error',
      title: 'Gagal Memuat Data',
      message: res.message || 'Dokumen pengeluaran tidak ditemukan.'
    });
  }
  loading.value = false;
};

onMounted(async () => {
  await Promise.all([fetchFormOptions(), loadIssueDetail()]);
  if (normalizedWarehouses.value.length > 0 && !formDetail.warehouseId) {
    formDetail.warehouseId = normalizedWarehouses.value[0].id;
    formDetail.warehouseName = normalizedWarehouses.value[0].name;
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

// Watch medicine stock selection from searchable select
const handleStockSelected = (stock) => {
  selectedMedicine.value = stock;
  formDetail.stockId = stock.stock_id ?? stock.id;
  formDetail.medicineId = stock.item_id ?? stock.medicine_id ?? stock.medicineId;
  formDetail.quantity = 1;
  formErrors.value.medicineId = '';
};

const handleStockCleared = () => {
  selectedMedicine.value = null;
  formDetail.stockId = '';
  formDetail.medicineId = '';
};

// Subtotal preview for new item
const previewSubtotal = computed(() => {
  if (!selectedMedicine.value) return 0;
  const qty = Number(formDetail.quantity) || 0;
  const price = Number(selectedMedicine.value.selling_price ?? selectedMedicine.value.price ?? selectedMedicine.value.unitPrice ?? 0);
  return qty * price;
});

// Maximum stock of currently selected medicine batch
const maxAvailableStock = computed(() => {
  return Number(selectedMedicine.value?.available_stock ?? selectedMedicine.value?.availableStock ?? 0);
});

// Reactive check if quantity exceeds available stock
const isQuantityExceeded = computed(() => {
  if (!selectedMedicine.value) return false;
  const qty = Number(formDetail.quantity);
  return qty > maxAvailableStock.value;
});

// Reactive form validity: all conditions must be met for Simpan button
const isFormValid = computed(() => {
  if (!formDetail.warehouseId) return false;
  if (!formDetail.stockId || !selectedMedicine.value) return false;
  const qty = Number(formDetail.quantity);
  if (!qty || qty <= 0) return false;
  if (qty > maxAvailableStock.value) return false;
  if (submitting.value) return false;
  return true;
});

// Reset Detail Form
const resetDetailForm = () => {
  formDetail.stockId = '';
  formDetail.medicineId = '';
  formDetail.quantity = 1;
  formDetail.notes = '';
  selectedMedicine.value = null;
  formErrors.value = {};
};

// Validate Form Detail
const validateFormDetail = () => {
  const errs = {};
  if (!formDetail.warehouseId) {
    errs.warehouseId = 'Gudang wajib dipilih.';
  }
  if (!formDetail.stockId || !selectedMedicine.value) {
    errs.medicineId = 'Nama sediaan wajib dipilih dari daftar pencarian.';
  }
  const maxStock = maxAvailableStock.value;
  if (!formDetail.quantity || Number(formDetail.quantity) <= 0) {
    errs.quantity = 'Jumlah sediaan harus lebih besar dari 0.';
  } else if (selectedMedicine.value && Number(formDetail.quantity) > maxStock) {
    const unitName = selectedMedicine.value.unit_name || selectedMedicine.value.unit || 'Unit';
    errs.quantity = `Jumlah melebihi stok yang tersedia (${maxStock} ${unitName}).`;
  }
  formErrors.value = errs;
  return Object.keys(errs).length === 0;
};

// Submit Add Item
const handleAddItem = async () => {
  if (!validateFormDetail() || submitting.value) return;

  submitting.value = true;
  const payload = {
    medicine_id: Number(formDetail.medicineId),
    stock_id: Number(formDetail.stockId),
    warehouse_stock_id: Number(formDetail.stockId),
    warehouse_id: Number(formDetail.warehouseId),
    quantity: Number(formDetail.quantity),
    notes: formDetail.notes ? formDetail.notes.trim() : null
  };

  const res = await addItem(issue.value.id, payload);
  if (res.success) {
    showToast({
      type: 'success',
      title: 'Sediaan Ditambahkan',
      message: 'Detail sediaan berhasil disimpan ke dokumen pengeluaran.'
    });
    resetDetailForm();
    await loadIssueDetail();
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
  const res = await updateItem(issue.value.id, itemToEdit.value.id, {
    quantity: Number(editQuantity.value)
  });

  if (res.success) {
    isEditItemModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Perubahan Disimpan',
      message: 'Jumlah sediaan berhasil diperbarui.'
    });
    await Promise.all([loadIssueDetail(), fetchAvailableStocks(formDetail.warehouseName)]);
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
  const res = await deleteItem(issue.value.id, itemToDelete.value.id);

  if (res.success) {
    isDeleteItemDialogOpen.value = false;
    showToast({
      type: 'success',
      title: 'Item Dihapus',
      message: 'Sediaan berhasil dihapus dari daftar pengeluaran.'
    });
    itemToDelete.value = null;
    await Promise.all([loadIssueDetail(), fetchAvailableStocks(formDetail.warehouseName)]);
  } else {
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: res.message || 'Terjadi kesalahan saat menghapus sediaan.'
    });
  }
  submitting.value = false;
};

// Total calculation
const calculatedTotal = computed(() => {
  if (!issue.value?.items || issue.value.items.length === 0) return 0;
  return issue.value.items.reduce((sum, item) => sum + (Number(item.subtotal) || 0), 0);
});

const goBack = () => {
  router.push('/logistics/goods-issues');
};
</script>

<template>
  <div class="page-container goods-issue-detail-page">
    <!-- Toast Notification -->
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
          <router-link to="/logistics/goods-issues" class="crumb-link">Logistik</router-link>
          <span class="separator">/</span>
          <router-link to="/logistics/goods-issues" class="crumb-link">Pengeluaran Barang</router-link>
          <span class="separator">/</span>
          <span class="current">Detail Pengeluaran</span>
        </div>
        <div class="title-row">
          <h1 class="page-title">Data Detail Pengeluaran</h1>
          <span class="module-badge">
            <span class="bullet-dot">•</span> SIMRS-LOG
          </span>
        </div>
        <p class="page-subtitle">
          Informasi header dan pengelolaan daftar sediaan pengeluaran barang.
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
    <div v-if="loading" class="detail-loading-box">
      <div class="skeleton-card"></div>
      <div class="skeleton-card"></div>
    </div>

    <div v-else-if="issue" class="page-content">
      <!-- 1. Card Informasi Pengeluaran -->
      <div class="card info-card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </span>
            INFO PENGELUARAN
          </h3>
        </div>
        <div class="card-body">
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Tanggal</span>
              <span class="info-separator">:</span>
              <span class="info-value font-semibold text-navy">
                {{ issue.formattedDate || issue.dateDisplay || formatDateIndo(issue.issue_date || issue.issueDate) }}
              </span>
            </div>
            <div class="info-item">
              <span class="info-label">Petugas</span>
              <span class="info-separator">:</span>
              <span class="info-value font-semibold text-navy">
                {{ issue.officerName || issue.officer_name || '-' }}
              </span>
            </div>
            <div class="info-item">
              <span class="info-label">Keperluan</span>
              <span class="info-separator">:</span>
              <span class="info-value text-navy">
                {{ issue.notes || (issue.purpose !== 'Kebutuhan Internal' ? issue.purpose : '-') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Card Form Pengeluaran (Tambah Detail) -->
      <div class="card form-card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </span>
            FORM PENGELUARAN
          </h3>
        </div>
        <div class="card-body">
          <form @submit.prevent="handleAddItem">
            <div class="form-grid">
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

              <!-- Nama Sediaan (Searchable Select) -->
              <div class="form-col form-col-wide">
                <label for="detail-input-medicine" class="field-label">
                  Nama Sediaan <span class="text-danger">*</span>
                </label>
                <GoodsIssueStockSelect
                  id="detail-input-medicine"
                  v-model="formDetail.stockId"
                  :warehouse-id="formDetail.warehouseId"
                  :warehouse-name="formDetail.warehouseName"
                  :issue-date="issue?.issue_date || issue?.date"
                  :error="formErrors.medicineId"
                  @select="handleStockSelected"
                  @clear="handleStockCleared"
                />
                <span v-if="formErrors.medicineId" class="error-msg">{{ formErrors.medicineId }}</span>
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

            <!-- Form Buttons -->
            <div class="form-actions">
              <button
                type="submit"
                class="btn-primary"
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
                class="btn-outline"
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

      <!-- 3. Card Detail List Data Pengeluaran -->
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
            DETAIL LIST DATA PENGELUARAN
          </h3>
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
                <th class="col-actions text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!issue.items || issue.items.length === 0">
                <td colspan="7" class="empty-detail-cell text-muted text-center">
                  Belum ada detail sediaan yang ditambahkan pada dokumen pengeluaran ini.
                </td>
              </tr>
              <tr
                v-for="(item, idx) in issue.items"
                :key="item.id || idx"
                class="detail-row"
              >
                <!-- 1. No -->
                <td class="col-no text-center text-muted font-medium">
                  {{ idx + 1 }}
                </td>

                <!-- 2. Nama Sediaan -->
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
                  {{ item.formattedPrice || formatRupiah(item.unitPrice || item.unit_price) }}
                </td>

                <!-- 4. Jumlah -->
                <td class="col-qty text-center font-bold text-navy">
                  {{ Number(item.quantity) }}
                </td>

                <!-- 5. Sub Total -->
                <td class="col-subtotal text-right font-bold text-primary">
                  {{ item.formattedSubtotal || formatRupiah(item.subtotal) }}
                </td>

                <!-- 6. Gudang -->
                <td class="col-wh text-navy">
                  {{ item.warehouseName || item.warehouse_name || issue.warehouse || 'Gudang Utama' }}
                </td>

                <!-- 7. Aksi (Edit, Hapus) -->
                <td class="col-actions text-center">
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

        <!-- Total Bar -->
        <div class="total-summary-bar">
          <span class="total-label font-bold text-navy">Total :</span>
          <span class="total-amount font-bold text-primary">
            {{ formatRupiah(calculatedTotal) }}
          </span>
        </div>

        <!-- Card Footer -->
        <div class="card-footer-bar">
          <button type="button" class="btn-cancel" @click="goBack">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
            Tutup
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
              <div class="font-semibold text-navy">{{ formatRupiah(itemToEdit.unitPrice || itemToEdit.unit_price) }}</div>
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
              <strong class="text-primary">{{ formatRupiah(editQuantity * (itemToEdit.unitPrice || itemToEdit.unit_price || 0)) }}</strong>
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
          <div class="modal-header modal-header-danger">
            <h3 class="modal-title text-danger">Konfirmasi Hapus Sediaan</h3>
            <button type="button" class="btn-close" @click="isDeleteItemDialogOpen = false">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <p class="confirmation-message">
              Apakah Anda yakin ingin menghapus sediaan
              <strong>{{ itemToDelete.medicineName || itemToDelete.name }}</strong> (Jumlah: {{ itemToDelete.quantity }} {{ itemToDelete.unit }}) dari dokumen pengeluaran ini?
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-cancel" @click="isDeleteItemDialogOpen = false">Batal</button>
            <button type="button" class="btn-danger" :disabled="submitting" @click="handleConfirmDeleteItem">
              <span>{{ submitting ? 'Menghapus...' : 'Ya, Hapus' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.goods-issue-detail-page {
  animation: fadeIn 0.25s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.header-content {
  flex: 1 1 500px;
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

.crumb-link {
  color: #64748b;
  text-decoration: none;
}

.crumb-link:hover {
  color: var(--color-primary, #087f78);
}

.separator {
  color: #94a3b8;
  font-weight: 500;
}

.current {
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
}

.bullet-dot {
  font-size: 0.9rem;
}

.page-subtitle {
  margin: 0;
  font-size: 0.875rem;
  color: var(--color-text-secondary, #64748b);
}

.header-actions .btn-back {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  height: 40px;
  padding: 0 1.15rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  background: #ffffff;
  color: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  cursor: pointer;
  transition: all 0.15s ease;
}

.header-actions .btn-back:hover {
  background: var(--color-primary-light, #dff7f4);
}

.header-actions .btn-back svg {
  width: 16px;
  height: 16px;
}

/* Page Content Layout */
.page-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Cards */
.card {
  background: #ffffff;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

.form-card {
  overflow: visible !important;
  position: relative;
  z-index: 20;
}

.form-card .card-body {
  overflow: visible !important;
  position: relative;
}

.table-card {
  position: relative;
  z-index: 10;
}

.card-header {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #f1f5f9;
  background: #f8fafc;
}

.card-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  letter-spacing: 0.02em;
}

.card-body {
  padding: 1.25rem;
}

.header-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary, #087f78);
}

.header-icon svg {
  width: 16px;
  height: 16px;
}

/* 1. Info Grid */
.info-grid {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  font-size: 0.875rem;
}

.info-item {
  display: flex;
  align-items: baseline;
}

.info-label {
  width: 120px;
  font-weight: 600;
  color: #475569;
}

.info-separator {
  width: 20px;
  color: #94a3b8;
}

.info-value {
  flex: 1;
}

/* 2. Form Grid */
.form-grid {
  display: grid;
  grid-template-columns: minmax(240px, 280px) minmax(320px, 1fr) minmax(130px, 150px);
  gap: 1.25rem;
  align-items: flex-start;
}

.form-col {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.field-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
}

.field-select,
.field-input {
  width: 100%;
  height: 44px;
  min-height: 44px;
  padding: 0 0.85rem;
  font-size: 0.875rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background-color: #ffffff;
  color: #1e293b;
  box-sizing: border-box;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.field-select:focus,
.field-input:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 2px rgba(8, 127, 120, 0.15);
}

.field-input:disabled {
  background-color: #f8fafc;
  border-color: #e2e8f0;
  cursor: not-allowed;
  color: #94a3b8;
}

.qty-info-caption {
  font-size: 0.78rem;
  margin-top: 0.2rem;
  line-height: 1.25;
}

.stock-info-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1.25rem;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  font-size: 0.825rem;
}

.info-badge {
  color: #475569;
}

.info-badge-primary {
  color: var(--color-primary, #087f78);
  font-size: 0.875rem;
}

.form-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid #f1f5f9;
  justify-content: flex-start;
}

/* Buttons */
.btn-primary,
.btn-outline,
.btn-cancel,
.btn-danger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  height: 40px;
  padding: 0 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-primary {
  background: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: #076d67;
}

.btn-primary:disabled {
  background: #94a3b8;
  border-color: #94a3b8;
  cursor: not-allowed;
  opacity: 0.65;
}

.btn-outline {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
}

.btn-outline:hover:not(:disabled) {
  background: #f1f5f9;
}

.btn-cancel {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
}

.btn-cancel:hover {
  background: #f1f5f9;
}

.btn-danger {
  background: #ef4444;
  border: 1px solid #ef4444;
  color: #ffffff;
}

.btn-danger:hover {
  background: #dc2626;
}

.btn-primary svg,
.btn-outline svg,
.btn-cancel svg {
  width: 15px;
  height: 15px;
}

/* 3. Table */
.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.detail-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  text-align: left;
}

.detail-table th {
  background-color: #f8fafc;
  color: #475569;
  font-weight: 700;
  padding: 0.75rem 1rem;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}

.detail-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.col-no { width: 50px; }
.col-price { width: 140px; }
.col-qty { width: 90px; }
.col-subtotal { width: 150px; }
.col-wh { width: 160px; }
.col-actions { width: 100px; }

.empty-detail-cell {
  padding: 2rem 1rem;
}

.total-summary-bar {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: #fafafa;
  border-top: 2px solid #e2e8f0;
  font-size: 1.1rem;
}

.total-amount {
  font-size: 1.25rem;
}

.card-footer-bar {
  padding: 1rem 1.25rem;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: flex-start;
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1070;
  padding: 1rem;
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 460px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #f1f5f9;
  background: #f8fafc;
}

.modal-header-danger {
  background: #fef2f2;
  border-bottom-color: #fee2e2;
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
}

.modal-body {
  padding: 1.25rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-top: 1px solid #f1f5f9;
  background: #f8fafc;
}

.action-buttons-group {
  display: inline-flex;
  gap: 0.35rem;
}

.btn-action-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  cursor: pointer;
  color: #475569;
}

.btn-action-icon svg { width: 14px; height: 14px; }
.btn-edit:hover { background: #fefce8; border-color: #fde047; color: #ca8a04; }
.btn-delete:hover { background: #fef2f2; border-color: #fca5a5; color: #ef4444; }

.error-msg,
.invalid-feedback {
  font-size: 0.75rem;
  color: #ef4444;
}

.is-invalid {
  border-color: #ef4444 !important;
}

.preview-box {
  padding: 0.5rem 0.75rem;
  background: #f8fafc;
  border-radius: 6px;
  font-size: 0.85rem;
}

.text-center { text-align: center; }
.text-right { text-align: right; }
.text-primary { color: var(--color-primary, #087f78); }
.text-navy { color: var(--color-text-navy, #17243d); }
.text-muted { color: #64748b; }
.text-danger { color: #ef4444; }
.font-semibold { font-weight: 600; }
.font-bold { font-weight: 700; }
.font-medium { font-weight: 500; }

@media (max-width: 900px) {
  .form-grid {
    grid-template-columns: 1fr 1fr;
  }
  .form-col-wide {
    grid-column: 1 / -1;
  }
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .form-actions {
    flex-direction: column;
    width: 100%;
  }
  .form-actions button {
    width: 100%;
  }
}
</style>

