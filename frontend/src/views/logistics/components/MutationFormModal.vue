<script setup>
import { ref, reactive, computed, watch } from 'vue';
import mutationsService from '@/services/logistics/mutations.service';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  warehouses: {
    type: Array,
    default: () => []
  },
  medicines: {
    type: Array,
    default: () => []
  },
  isSubmitting: {
    type: Boolean,
    default: false
  },
  apiErrors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'submit']);

// Form State
const getNowLocalDateTime = () => {
  const now = new Date();
  const offset = now.getTimezoneOffset();
  const local = new Date(now.getTime() - offset * 60000);
  return local.toISOString().slice(0, 16);
};

const formData = reactive({
  date: getNowLocalDateTime(),
  sourceWarehouseId: '',
  targetWarehouseId: '',
  notes: '',
  items: []
});

const errors = ref({});
const warehouseStocks = ref([]);
const loadingStocks = ref(false);
const stockLoadError = ref(null);

// Search State
const searchQuery = ref('');
const isSearchFocused = ref(false);
const itemWarningMessage = ref('');
let warningTimeout = null;

// Confirmation Dialog for Source Warehouse Change
const showWarehouseChangeConfirm = ref(false);
const pendingWarehouseId = ref(null);

// Normalized Warehouses
const normalizedWarehouses = computed(() => {
  return props.warehouses.map((wh) => {
    if (typeof wh === 'object' && wh !== null) {
      return {
        id: Number(wh.id),
        code: wh.code || '',
        name: wh.name || ''
      };
    }
    return { id: Number(wh), code: '', name: String(wh) };
  });
});

// Normalized Stocks available from selected source warehouse
const availableStocks = computed(() => {
  if (warehouseStocks.value.length > 0) {
    return warehouseStocks.value
      .filter((s) => Number(s.availableStock) > 0)
      .map((s) => ({
        id: s.id,
        medicineId: Number(s.medicineId || s.id),
        medicineCode: s.medicineCode || s.code || '',
        medicineName: s.medicineName || s.name || '',
        unit: s.unit || 'Unit',
        availableStock: Number(s.availableStock),
        unitPrice: Number(s.unitPrice ?? s.price ?? 0),
        batchNumber: s.batchNumber || null,
        expiredDate: s.expiredDate || null
      }));
  }

  // Fallback to props.medicines (e.g. for testing / preloaded datasets)
  if (props.medicines && props.medicines.length > 0) {
    return props.medicines
      .filter((m) => Number(m.availableStock ?? 1) > 0)
      .map((m) => ({
        id: m.id,
        medicineId: Number(m.id),
        medicineCode: m.code || '',
        medicineName: m.name || '',
        unit: m.unit?.name || m.unit || 'Unit',
        availableStock: Number(m.availableStock ?? 100),
        unitPrice: Number(m.selling_price_per_unit ?? m.unitPrice ?? m.price ?? 0),
        batchNumber: null,
        expiredDate: null
      }));
  }

  return [];
});

// Filtered options based on search query
const searchResults = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return availableStocks.value;

  return availableStocks.value.filter((s) => {
    const nameMatch = s.medicineName.toLowerCase().includes(query);
    const codeMatch = s.medicineCode.toLowerCase().includes(query);
    return nameMatch || codeMatch;
  });
});

// Load stocks for warehouse
const loadWarehouseStocks = async (warehouseId) => {
  if (!warehouseId) {
    warehouseStocks.value = [];
    return;
  }

  loadingStocks.value = true;
  stockLoadError.value = null;
  try {
    const res = await mutationsService.getWarehouseStocks({
      warehouse_id: Number(warehouseId),
      all: 1
    });
    warehouseStocks.value = res.data || [];
  } catch (err) {
    warehouseStocks.value = [];
    stockLoadError.value = err.message || 'Gagal memuat stok sediaan dari gudang asal.';
  } finally {
    loadingStocks.value = false;
  }
};

// Handle Source Warehouse Change
const handleSourceWarehouseChange = (event) => {
  const newId = event.target.value ? Number(event.target.value) : '';

  if (newId === Number(formData.sourceWarehouseId)) {
    return;
  }

  // If user already added items, show confirmation before wiping
  if (formData.items.length > 0) {
    pendingWarehouseId.value = newId;
    showWarehouseChangeConfirm.value = true;
    // Revert visual selection in select element until confirmed
    event.target.value = formData.sourceWarehouseId || '';
  } else {
    applySourceWarehouseChange(newId);
  }
};

const applySourceWarehouseChange = async (newId) => {
  formData.sourceWarehouseId = newId;
  formData.items = [];
  searchQuery.value = '';
  itemWarningMessage.value = '';
  delete errors.value.sourceWarehouse;
  delete errors.value.sourceWarehouseId;

  if (Number(formData.targetWarehouseId) === newId) {
    formData.targetWarehouseId = '';
  }

  if (newId) {
    await loadWarehouseStocks(newId);
  } else {
    warehouseStocks.value = [];
  }
};

const confirmWarehouseChange = () => {
  showWarehouseChangeConfirm.value = false;
  applySourceWarehouseChange(pendingWarehouseId.value);
  pendingWarehouseId.value = null;
};

const cancelWarehouseChange = () => {
  showWarehouseChangeConfirm.value = false;
  pendingWarehouseId.value = null;
};

// Add medicine item from search
const selectStockItem = (item) => {
  // Check if medicine already exists in List Sediaan
  const alreadyExists = formData.items.some(
    (it) => it.medicineId === item.medicineId
  );

  if (alreadyExists) {
    itemWarningMessage.value = 'Sediaan tersebut sudah ditambahkan.';
    if (warningTimeout) clearTimeout(warningTimeout);
    warningTimeout = setTimeout(() => {
      itemWarningMessage.value = '';
    }, 4000);
    return;
  }

  formData.items.push({
    id: Date.now() + Math.random(),
    warehouseStockId: item.id,
    medicineId: item.medicineId,
    medicineCode: item.medicineCode,
    medicineName: item.medicineName,
    unit: item.unit,
    availableStock: item.availableStock,
    quantity: 1,
    unitPrice: item.unitPrice
  });

  searchQuery.value = '';
  isSearchFocused.value = false;
  itemWarningMessage.value = '';
  delete errors.value.items;
};

// Remove item from List Sediaan
const removeItem = (index) => {
  formData.items.splice(index, 1);
};

// Item subtotal and grand total
const calculateItemSubtotal = (item) => {
  return (Number(item.quantity) || 0) * (Number(item.unitPrice) || 0);
};

const totalSediaanValue = computed(() => {
  return formData.items.reduce((sum, it) => sum + calculateItemSubtotal(it), 0);
});

const formatRupiah = (val) => {
  return 'Rp' + Number(val || 0).toLocaleString('id-ID');
};

// Validations
const isItemQtyExceeded = (item) => {
  return Number(item.quantity) > Number(item.availableStock);
};

const isItemQtyInvalid = (item) => {
  const qty = Number(item.quantity);
  return isNaN(qty) || qty <= 0 || qty > Number(item.availableStock);
};

const hasItemErrors = computed(() => {
  if (formData.items.length === 0) return true;
  return formData.items.some((it) => isItemQtyInvalid(it));
});

const validateForm = () => {
  const errs = {};

  if (!formData.date) {
    errs.date = 'Tanggal mutasi wajib diisi.';
  }

  if (!formData.sourceWarehouseId) {
    errs.sourceWarehouse = 'Gudang asal wajib dipilih.';
    errs.sourceWarehouseId = 'Gudang asal wajib dipilih.';
  }

  if (!formData.targetWarehouseId) {
    errs.targetWarehouse = 'Gudang tujuan wajib dipilih.';
    errs.targetWarehouseId = 'Gudang tujuan wajib dipilih.';
  }

  if (
    formData.sourceWarehouseId &&
    formData.targetWarehouseId &&
    Number(formData.sourceWarehouseId) === Number(formData.targetWarehouseId)
  ) {
    errs.targetWarehouse = 'Gudang tujuan tidak boleh sama dengan gudang asal.';
    errs.targetWarehouseId = 'Gudang tujuan tidak boleh sama dengan gudang asal.';
  }

  if (formData.items.length === 0) {
    errs.items = 'Minimal satu sediaan harus ditambahkan ke List Sediaan.';
  } else {
    for (const it of formData.items) {
      if (isItemQtyExceeded(it)) {
        errs.items = `Jumlah untuk "${it.medicineName}" melebihi stok yang tersedia (${it.availableStock} ${it.unit}).`;
        break;
      }
      if (Number(it.quantity) <= 0) {
        errs.items = 'Jumlah sediaan harus lebih besar dari 0.';
        break;
      }
    }
  }

  errors.value = errs;
  return Object.keys(errs).length === 0;
};

const handleSubmit = () => {
  if (!validateForm()) return;

  const payload = {
    date: formData.date,
    mutationDate: formData.date,
    sourceWarehouseId: Number(formData.sourceWarehouseId),
    targetWarehouseId: Number(formData.targetWarehouseId),
    notes: formData.notes ? formData.notes.trim() : null,
    items: formData.items.map((it) => ({
      warehouseStockId: it.warehouseStockId,
      medicineId: Number(it.medicineId),
      quantity: Number(it.quantity)
    }))
  };

  emit('submit', payload);
};

// Reset on open/close
watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      errors.value = {};
      formData.date = getNowLocalDateTime();
      formData.sourceWarehouseId = '';
      formData.targetWarehouseId = '';
      formData.notes = '';
      formData.items = [];
      warehouseStocks.value = [];
      searchQuery.value = '';
      itemWarningMessage.value = '';
      showWarehouseChangeConfirm.value = false;
    }
  }
);

watch(
  () => props.apiErrors,
  (newErrors) => {
    if (newErrors && Object.keys(newErrors).length > 0) {
      errors.value = { ...errors.value, ...newErrors };
    }
  },
  { deep: true }
);
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content mutation-form-modal">
        <!-- Header -->
        <div class="modal-header">
          <div class="header-titles">
            <h3 class="modal-title">Tambah Mutasi Barang</h3>
            <p class="modal-subtitle">Formulir pemindahan stok sediaan obat antarunit farmasi.</p>
          </div>
          <button
            type="button"
            class="btn-close"
            :disabled="isSubmitting"
            aria-label="Tutup"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <!-- Information Banner (Perhatian) -->
          <div class="info-banner">
            <div class="info-banner-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="info-banner-content">
              <strong class="info-banner-title">Perhatian</strong>
              <p class="info-banner-text">
                Pastikan data sediaan tersedia di gudang atau unit asal. Jangan menutup atau memuat ulang halaman sebelum proses penyimpanan selesai.
              </p>
            </div>
          </div>

          <form autocomplete="off" id="mutation-form" @submit.prevent="handleSubmit">
            <!-- 1. Bagian DATA MUTASI -->
            <div class="form-section-card card">
              <div class="section-card-header">
                <div class="section-title-wrap">
                  <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                  </svg>
                  <h4 class="section-title">Data Mutasi</h4>
                </div>
              </div>

              <div class="section-card-body">
                <div class="form-grid-2col">
                  <!-- Kolom Kiri: Tanggal Mutasi & Catatan -->
                  <div class="form-col">
                    <!-- 1. Tanggal Mutasi -->
                    <div class="form-group">
                      <label class="form-label" for="form-mutation-date">
                        Tanggal Mutasi <span class="required-mark">*</span>
                      </label>
                      <input
                        id="form-mutation-date"
                        v-model="formData.date"
                        type="datetime-local"
                        class="form-control"
                        :class="{ 'is-invalid': errors.date || errors.mutationDate }"
                        required
                      />
                      <span v-if="errors.date || errors.mutationDate" class="invalid-feedback">
                        {{ errors.date || errors.mutationDate }}
                      </span>
                    </div>

                    <!-- 2. Catatan -->
                    <div class="form-group">
                      <label class="form-label" for="form-notes">Catatan</label>
                      <textarea
                        id="form-notes"
                        v-model="formData.notes"
                        rows="3"
                        class="form-control notes-textarea"
                        placeholder="Masukkan catatan keperluan mutasi"
                      ></textarea>
                    </div>
                  </div>

                  <!-- Kolom Kanan: Gudang Asal & Gudang Tujuan -->
                  <div class="form-col">
                    <!-- 3. Gudang Asal -->
                    <div class="form-group">
                      <label class="form-label" for="form-source-wh">
                        Gudang Asal <span class="required-mark">*</span>
                      </label>
                      <select
                        id="form-source-wh"
                        :value="formData.sourceWarehouseId"
                        class="form-select"
                        :class="{ 'is-invalid': errors.sourceWarehouse || errors.sourceWarehouseId }"
                        required
                        @change="handleSourceWarehouseChange"
                      >
                        <option value="" disabled>Pilih Gudang Asal</option>
                        <option
                          v-for="wh in normalizedWarehouses"
                          :key="wh.id"
                          :value="wh.id"
                        >
                          {{ wh.name }}
                        </option>
                      </select>
                      <span v-if="errors.sourceWarehouse || errors.sourceWarehouseId" class="invalid-feedback">
                        {{ errors.sourceWarehouse || errors.sourceWarehouseId }}
                      </span>
                    </div>

                    <!-- 4. Gudang Tujuan -->
                    <div class="form-group">
                      <label class="form-label" for="form-target-wh">
                        Gudang Tujuan <span class="required-mark">*</span>
                      </label>
                      <select
                        id="form-target-wh"
                        v-model="formData.targetWarehouseId"
                        class="form-select"
                        :class="{ 'is-invalid': errors.targetWarehouse || errors.targetWarehouseId }"
                        required
                      >
                        <option value="" disabled>Pilih Gudang Tujuan</option>
                        <option
                          v-for="wh in normalizedWarehouses"
                          :key="wh.id"
                          :value="wh.id"
                          :disabled="wh.id === Number(formData.sourceWarehouseId)"
                        >
                          {{ wh.name }} {{ wh.id === Number(formData.sourceWarehouseId) ? '(Gudang Asal)' : '' }}
                        </option>
                      </select>
                      <span v-if="errors.targetWarehouse || errors.targetWarehouseId" class="invalid-feedback">
                        {{ errors.targetWarehouse || errors.targetWarehouseId }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. Bagian LIST SEDIAAN -->
            <div class="form-section-card card">
              <div class="section-card-header">
                <div class="section-title-wrap">
                  <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                  </svg>
                  <h4 class="section-title">List Sediaan</h4>
                </div>
              </div>

              <div class="section-card-body">
                <!-- Pencarian Sediaan -->
                <div class="search-sediaan-wrapper">
                  <div class="search-input-box">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="11" cy="11" r="8"></circle>
                      <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input
                      id="search-sediaan"
                      v-model="searchQuery"
                      type="text"
                      class="form-control search-input"
                      placeholder="Masukkan Data Sediaan"
                      :disabled="!formData.sourceWarehouseId || loadingStocks"
                      @focus="isSearchFocused = true"
                    />
                    <span v-if="loadingStocks" class="spinner-inline"></span>
                  </div>

                  <!-- Error loading stock with Coba Lagi button -->
                  <div v-if="stockLoadError" class="alert-inline-error">
                    <span>{{ stockLoadError }}</span>
                    <button
                      type="button"
                      class="btn-retry"
                      @click="loadWarehouseStocks(formData.sourceWarehouseId)"
                    >
                      Coba Lagi
                    </button>
                  </div>

                  <!-- Inline duplicate warning message -->
                  <div v-if="itemWarningMessage" class="alert-inline-warning">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="12" y1="8" x2="12" y2="12"></line>
                      <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>{{ itemWarningMessage }}</span>
                  </div>

                  <!-- Dropdown Search Results -->
                  <div
                    v-if="formData.sourceWarehouseId && (isSearchFocused || searchQuery) && searchQuery.trim()"
                    class="search-results-dropdown"
                  >
                    <div v-if="loadingStocks" class="dropdown-state-text">
                      Memuat data stok sediaan...
                    </div>
                    <div v-else-if="searchResults.length === 0" class="dropdown-state-text">
                      Sediaan "{{ searchQuery }}" tidak ditemukan atau stok kosong di gudang ini.
                    </div>
                    <div
                      v-else
                      v-for="stk in searchResults"
                      :key="stk.id || stk.medicineId"
                      class="dropdown-result-item"
                      @click="selectStockItem(stk)"
                    >
                      <div class="result-item-main">
                        <strong class="item-name text-navy">{{ stk.medicineName }}</strong>
                        <span class="item-code text-muted">
                          {{ stk.medicineCode || '-' }} • Satuan: {{ stk.unit }}
                        </span>
                      </div>
                      <div class="result-item-meta">
                        <span class="item-price font-bold text-navy">
                          {{ formatRupiah(stk.unitPrice) }}
                        </span>
                        <span class="wh-badge source-badge">
                          Stok: {{ stk.availableStock }} {{ stk.unit }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Global items validation error -->
                <div v-if="errors.items" class="alert-inline-error mb-3">
                  <span>{{ errors.items }}</span>
                </div>

                <!-- Tabel List Sediaan: Nama | Satuan | Jumlah | Harga | Aksi -->
                <div class="sediaan-table-container">
                  <table class="sediaan-table">
                    <thead>
                      <tr>
                        <th class="col-name">Nama</th>
                        <th class="col-unit">Satuan</th>
                        <th class="col-qty">Jumlah</th>
                        <th class="col-price">Harga</th>
                        <th class="col-action text-center">Aksi</th>
                      </tr>
                    </thead>

                    <tbody>
                      <!-- Empty State -->
                      <tr v-if="formData.items.length === 0">
                        <td colspan="5" class="empty-sediaan-cell">
                          <div class="empty-sediaan-box">
                            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                              <circle cx="12" cy="12" r="10"></circle>
                              <line x1="8" y1="12" x2="16" y2="12"></line>
                            </svg>
                            <p class="empty-main-text">Belum ada sediaan yang ditambahkan.</p>
                            <p class="empty-sub-text">Pilih Gudang Asal, lalu cari dan tambahkan sediaan.</p>
                          </div>
                        </td>
                      </tr>

                      <!-- Items Rows -->
                      <tr v-else v-for="(it, idx) in formData.items" :key="it.id">
                        <!-- 1. Nama -->
                        <td class="col-name">
                          <strong class="text-navy d-block">{{ it.medicineName }}</strong>
                          <span class="item-code-helper text-muted">
                            {{ it.medicineCode ? it.medicineCode + ' • ' : '' }}Stok: {{ it.availableStock }} {{ it.unit }}
                          </span>
                        </td>

                        <!-- 2. Satuan -->
                        <td class="col-unit text-muted font-medium">
                          {{ it.unit }}
                        </td>

                        <!-- 3. Jumlah -->
                        <td class="col-qty">
                          <div class="qty-input-wrap">
                            <input
                              v-model.number="it.quantity"
                              type="number"
                              min="1"
                              :max="it.availableStock"
                              class="form-control qty-input"
                              :class="{ 'is-invalid': isItemQtyInvalid(it) || errors[`items.${idx}.quantity`] }"
                              required
                            />
                            <span v-if="isItemQtyExceeded(it)" class="qty-error-text">
                              Jumlah melebihi stok yang tersedia. Stok saat ini: {{ it.availableStock }} {{ it.unit }}.
                            </span>
                            <span v-else-if="Number(it.quantity) <= 0" class="qty-error-text">
                              Jumlah harus lebih besar dari 0.
                            </span>
                            <span v-else-if="errors[`items.${idx}.quantity`]" class="qty-error-text">
                              {{ errors[`items.${idx}.quantity`][0] || errors[`items.${idx}.quantity`] }}
                            </span>
                          </div>
                        </td>

                        <!-- 4. Harga (Subtotal = Jumlah x Harga Satuan) -->
                        <td class="col-price font-bold text-navy">
                          {{ formatRupiah(calculateItemSubtotal(it)) }}
                        </td>

                        <!-- 5. Aksi: Hapus Item -->
                        <td class="col-action text-center">
                          <button
                            type="button"
                            class="btn-delete-row"
                            title="Hapus Sediaan"
                            aria-label="Hapus Sediaan"
                            @click="removeItem(idx)"
                          >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                              <polyline points="3 6 5 6 21 6"></polyline>
                              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                              <line x1="10" y1="11" x2="10" y2="17"></line>
                              <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                          </button>
                        </td>
                      </tr>
                    </tbody>

                    <!-- Total Nilai Sediaan Row -->
                    <tfoot v-if="formData.items.length > 0">
                      <tr class="total-summary-row">
                        <td colspan="3" class="total-label text-right font-bold text-navy">
                          TOTAL NILAI SEDIAAN
                        </td>
                        <td colspan="2" class="total-value font-bold text-primary">
                          {{ formatRupiah(totalSediaanValue) }}
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button
            type="button"
            class="btn-secondary"
            :disabled="isSubmitting"
            @click="$emit('close')"
          >
            Batal
          </button>
          <button
            type="submit"
            form="mutation-form"
            class="btn-primary"
            :disabled="isSubmitting || hasItemErrors"
          >
            <span v-if="isSubmitting" class="spinner-small mr-2"></span>
            {{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>

        <!-- Confirmation Dialog on Changing Source Warehouse -->
        <div v-if="showWarehouseChangeConfirm" class="confirm-dialog-overlay">
          <div class="confirm-dialog-card">
            <div class="confirm-dialog-header">
              <div class="confirm-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                  <line x1="12" y1="9" x2="12" y2="13"></line>
                  <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
              </div>
              <h4 class="confirm-title">Ganti Gudang Asal?</h4>
            </div>
            <p class="confirm-body">
              Mengganti Gudang Asal akan mengosongkan seluruh daftar sediaan yang telah ditambahkan. Apakah Anda yakin ingin melanjutkan?
            </p>
            <div class="confirm-actions">
              <button type="button" class="btn-secondary" @click="cancelWarehouseChange">
                Batal
              </button>
              <button type="button" class="btn-danger-action" @click="confirmWarehouseChange">
                Ya, Ganti & Kosongkan
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.mutation-form-modal {
  background: #ffffff;
  border-radius: 14px;
  width: 100%;
  max-width: 960px;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  position: relative;
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}

.modal-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.modal-subtitle {
  font-size: 0.82rem;
  color: var(--color-text-secondary, #64748b);
  margin: 0.2rem 0 0 0;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  flex-shrink: 0;
  background: #f8fafc;
}

/* Information Banner */
.info-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  padding: 0.85rem 1.15rem;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-left: 4px solid #0284c7;
  border-radius: 8px;
}

.info-banner-icon svg {
  width: 20px;
  height: 20px;
  color: #0284c7;
  flex-shrink: 0;
  margin-top: 2px;
}

.info-banner-title {
  display: block;
  font-size: 0.88rem;
  font-weight: 700;
  color: #0369a1;
  margin-bottom: 0.15rem;
}

.info-banner-text {
  font-size: 0.82rem;
  color: #0c4a6e;
  margin: 0;
  line-height: 1.45;
}

/* Form Section Cards */
.form-section-card {
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 10px;
  overflow: hidden;
}

.section-card-header {
  padding: 0.85rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft, #e8edf3);
}

.section-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-icon {
  width: 17px;
  height: 17px;
  color: var(--color-primary, #087f78);
}

.section-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
  letter-spacing: 0.02em;
}

.section-card-body {
  padding: 1.25rem;
}

/* 2 Column Form Grid */
.form-grid-2col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem 1.75rem;
}

.form-col {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
  margin-bottom: 0.35rem;
}

.required-mark {
  color: #ef4444;
}

.notes-textarea {
  resize: vertical;
  min-height: 80px;
}

/* Search Sediaan */
.search-sediaan-wrapper {
  position: relative;
  margin-bottom: 1.25rem;
}

.search-input-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.85rem;
  width: 18px;
  height: 18px;
  color: var(--color-text-secondary, #94a3b8);
  pointer-events: none;
}

.search-input {
  padding-left: 2.5rem;
  height: 42px;
  font-size: 0.85rem;
}

.search-results-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 50;
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  margin-top: 4px;
  max-height: 240px;
  overflow-y: auto;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.dropdown-state-text {
  padding: 1rem;
  font-size: 0.82rem;
  color: var(--color-text-secondary, #64748b);
  text-align: center;
}

.dropdown-result-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background 0.15s;
}

.dropdown-result-item:last-child {
  border-bottom: none;
}

.dropdown-result-item:hover {
  background: var(--color-primary-light, #f0fdfa);
}

.item-name {
  display: block;
  font-size: 0.85rem;
}

.item-code {
  display: block;
  font-size: 0.75rem;
  margin-top: 0.1rem;
}

.result-item-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.item-price {
  font-size: 0.82rem;
}

/* Alerts */
.alert-inline-warning {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: #fffbeb;
  border: 1px solid #fef3c7;
  border-radius: 6px;
  font-size: 0.8rem;
  color: #b45309;
  margin-top: 0.5rem;
}

.alert-inline-warning svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.alert-inline-error {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0.75rem;
  background: #fef2f2;
  border: 1px solid #fee2e2;
  border-radius: 6px;
  font-size: 0.8rem;
  color: #b91c1c;
  margin-top: 0.5rem;
}

.btn-retry {
  padding: 0.2rem 0.6rem;
  font-size: 0.75rem;
  font-weight: 600;
  background: #ffffff;
  border: 1px solid #fca5a5;
  color: #b91c1c;
  border-radius: 4px;
  cursor: pointer;
}

/* Sediaan Table */
.sediaan-table-container {
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  overflow-x: auto;
}

.sediaan-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.84rem;
  min-width: 600px;
}

.sediaan-table th {
  background: #f8fafc;
  padding: 0.75rem 0.9rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--color-text-secondary, #64748b);
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  text-align: left;
}

.sediaan-table td {
  padding: 0.75rem 0.9rem;
  border-bottom: 1px solid var(--color-border-soft, #e8edf3);
  vertical-align: middle;
}

.col-name { min-width: 220px; }
.col-unit { width: 90px; }
.col-qty { width: 150px; }
.col-price { width: 140px; }
.col-action { width: 60px; }

.item-code-helper {
  display: block;
  font-size: 0.72rem;
  margin-top: 0.15rem;
}

.qty-input {
  width: 90px;
  height: 34px;
  font-size: 0.85rem;
  font-weight: 600;
}

.qty-error-text {
  display: block;
  font-size: 0.72rem;
  color: #ef4444;
  margin-top: 0.25rem;
  line-height: 1.25;
}

.btn-delete-row {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid transparent;
  background: transparent;
  color: #ef4444;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.btn-delete-row:hover {
  background: #fee2e2;
  border-color: #fca5a5;
}

.btn-delete-row svg {
  width: 16px;
  height: 16px;
}

.empty-sediaan-cell {
  padding: 2.5rem 1rem;
  text-align: center;
  background: #fafafa;
}

.empty-sediaan-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
}

.empty-icon {
  width: 36px;
  height: 36px;
  color: #cbd5e1;
  margin-bottom: 0.25rem;
}

.empty-main-text {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--color-text-navy, #334155);
  margin: 0;
}

.empty-sub-text {
  font-size: 0.78rem;
  color: var(--color-text-secondary, #64748b);
  margin: 0;
}

.total-summary-row td {
  background: #f8fafc;
  padding: 0.85rem 1rem;
  border-top: 2px solid var(--color-border-soft, #e2e8f0);
}

.total-label {
  font-size: 0.82rem;
  letter-spacing: 0.03em;
}

.total-value {
  font-size: 0.95rem;
}

/* Warehouse Change Confirm Dialog */
.confirm-dialog-overlay {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 60;
  padding: 1.5rem;
}

.confirm-dialog-card {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 440px;
  padding: 1.5rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
}

.confirm-dialog-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.confirm-icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #fef3c7;
  color: #d97706;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.confirm-icon-wrap svg {
  width: 20px;
  height: 20px;
}

.confirm-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.confirm-body {
  font-size: 0.84rem;
  color: #475569;
  line-height: 1.5;
  margin: 0 0 1.25rem 0;
}

.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.65rem;
}

.btn-danger-action {
  padding: 0.45rem 1rem;
  background: #dc2626;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-danger-action:hover {
  background: #b91c1c;
}

/* Helpers */
.wh-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 600;
}

.source-badge {
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.spinner-inline {
  position: absolute;
  right: 0.85rem;
  width: 16px;
  height: 16px;
  border: 2px solid #cbd5e1;
  border-top-color: var(--color-primary, #087f78);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .form-grid-2col {
    grid-template-columns: 1fr;
  }
}
</style>
