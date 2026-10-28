<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  invoice: {
    type: Object,
    default: null
  },
  suppliers: {
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
  formatRupiah: {
    type: Function,
    required: true
  }
});

const emit = defineEmits(['close', 'submit']);

// Standard Margins according to legacy system & master data
const standardMargins = [
  { value: 0.28, label: '0.28 (28%) - Standar' },
  { value: 0.26, label: '0.26 (26%)' },
  { value: 0.21, label: '0.21 (21%)' },
  { value: 0.16, label: '0.16 (16%)' },
  { value: 0.09, label: '0.09 (9%)' },
  { value: 0.07, label: '0.07 (7%)' }
];

const getTodayString = () => new Date().toISOString().split('T')[0];

const errors = ref({});
const itemNotice = ref('');
const isConfirmCloseOpen = ref(false);

// Search state for Medicines
const medicineSearchQuery = ref('');
const showMedicineDropdown = ref(false);
const medicineSearchInputRef = ref(null);

// Search state for Suppliers
const supplierSearchQuery = ref('');
const showSupplierDropdown = ref(false);

const createDefaultItem = () => ({
  id: Date.now() + Math.random(),
  medicineId: '',
  medicineName: '',
  medicineCode: '',
  quantity: 1,
  unit: 'Box',
  batchNumber: '',
  expiredDate: '',
  isRepack: false,
  price: 0,
  discountType: 'percentage',
  discount: 0,
  margin: 0.28,
  subtotal: 0
});

const formData = reactive({
  invoiceNumber: '',
  invoiceDate: getTodayString(),
  dueDate: '',
  supplierId: '',
  status: 'Belum Lunas',
  taxType: 'non_tax',
  taxRate: 0,
  notes: '',
  items: []
});

let initialFormSnapshot = '';

const takeSnapshot = () => {
  return JSON.stringify({
    invoiceNumber: formData.invoiceNumber,
    invoiceDate: formData.invoiceDate,
    dueDate: formData.status === 'Lunas' ? null : formData.dueDate,
    supplierId: formData.supplierId,
    status: formData.status,
    taxType: formData.taxType,
    notes: formData.notes,
    items: formData.items.map((it) => ({
      medicineId: it.medicineId,
      quantity: it.quantity,
      batchNumber: it.batchNumber,
      expiredDate: it.expiredDate,
      isRepack: it.isRepack,
      price: it.price,
      discount: it.discount,
      margin: it.margin
    }))
  });
};

const isDirty = computed(() => {
  if (!initialFormSnapshot) return false;
  return takeSnapshot() !== initialFormSnapshot;
});

// Selected supplier object
const selectedSupplier = computed(() => {
  if (!formData.supplierId) return null;
  return props.suppliers.find((s) => Number(s.id) === Number(formData.supplierId)) || null;
});

// Filtered suppliers based on search query
const filteredSuppliers = computed(() => {
  const q = (supplierSearchQuery.value || '').toLowerCase().trim();
  if (!q) return props.suppliers.slice(0, 50);
  return props.suppliers.filter((s) => {
    return (
      (s.name && s.name.toLowerCase().includes(q)) ||
      (s.city && s.city.toLowerCase().includes(q)) ||
      (s.phone && s.phone.toLowerCase().includes(q))
    );
  }).slice(0, 30);
});

// Filtered medicines based on search query
const filteredMedicines = computed(() => {
  const q = (medicineSearchQuery.value || '').toLowerCase().trim();
  if (!q) return [];
  return props.medicines.filter((m) => {
    return (
      (m.name && m.name.toLowerCase().includes(q)) ||
      (m.code && m.code.toLowerCase().includes(q)) ||
      (m.generic_name && m.generic_name.toLowerCase().includes(q)) ||
      (m.kfa_code && m.kfa_code.toLowerCase().includes(q))
    );
  }).slice(0, 20);
});

// Watch status change:
// When status becomes Lunas: clear dueDate and clear errors.
// When status switches back from Lunas to Belum Lunas: field reappears empty.
watch(
  () => formData.status,
  (newStatus, oldStatus) => {
    if (newStatus === 'Lunas') {
      formData.dueDate = '';
      if (errors.value.dueDate) delete errors.value.dueDate;
      if (errors.value.due_date) delete errors.value.due_date;
    } else if (newStatus === 'Belum Lunas' && oldStatus === 'Lunas') {
      formData.dueDate = '';
    }
  }
);

// Calculate subtotal for single item
const calculateItemSubtotal = (item) => {
  const qty = Number(item.quantity) || 0;
  const price = Number(item.price) || 0;
  const gross = qty * price;
  let discAmount = 0;
  const rawDisc = Number(item.discount) || 0;

  if (item.discountType === 'nominal') {
    discAmount = Math.min(rawDisc, gross);
  } else {
    if (rawDisc > 0 && rawDisc < 1) {
      discAmount = gross * rawDisc;
    } else {
      discAmount = gross * (Math.min(rawDisc, 100) / 100);
    }
  }

  return Math.max(0, Math.round(gross - discAmount));
};

// Format nominal number with Indonesian thousand separators (e.g. 16.000)
const formatNumber = (value) => {
  if (value === undefined || value === null || isNaN(Number(value))) return '0';
  return Math.round(Number(value)).toLocaleString('id-ID');
};

// Handle value changes in a row
const onItemValueChange = (item) => {
  item.subtotal = calculateItemSubtotal(item);
};

// Add medicine from search dropdown
const selectMedicineFromSearch = (med) => {
  itemNotice.value = '';

  // Check if already in items list
  const existingIndex = formData.items.findIndex(
    (it) => Number(it.medicineId) === Number(med.id)
  );

  if (existingIndex !== -1) {
    // If already added, increment quantity and show gentle notification
    formData.items[existingIndex].quantity = (Number(formData.items[existingIndex].quantity) || 1) + 1;
    formData.items[existingIndex].subtotal = calculateItemSubtotal(formData.items[existingIndex]);
    itemNotice.value = `"${med.name}" sudah ada di daftar. Kuantitas ditambahkan menjadi ${formData.items[existingIndex].quantity}.`;
  } else {
    const newItem = {
      id: Date.now() + Math.random(),
      medicineId: med.id,
      medicineName: med.name,
      medicineCode: med.code || '',
      quantity: 1,
      unit: med.unit || 'Box',
      batchNumber: '',
      expiredDate: '',
      isRepack: false,
      price: Number(med.price) || 0,
      discountType: 'percentage',
      discount: 0,
      margin: 0.28,
      subtotal: Number(med.price) || 0
    };
    newItem.subtotal = calculateItemSubtotal(newItem);
    formData.items.push(newItem);
    itemNotice.value = `"${med.name}" berhasil ditambahkan ke daftar.`;
  }

  medicineSearchQuery.value = '';
  showMedicineDropdown.value = false;

  setTimeout(() => {
    itemNotice.value = '';
  }, 4000);
};

// Select supplier
const selectSupplier = (sup) => {
  formData.supplierId = sup.id;
  supplierSearchQuery.value = sup.name;
  showSupplierDropdown.value = false;
  if (errors.value.supplierId) {
    delete errors.value.supplierId;
  }
};

// Clear chosen supplier
const clearSupplier = () => {
  formData.supplierId = '';
  supplierSearchQuery.value = '';
  showSupplierDropdown.value = false;
};

// Remove an item row
const removeItemRow = (index) => {
  formData.items.splice(index, 1);
};

// Totals calculations
const summaryTotals = computed(() => {
  const items = formData.items;
  let subtotalGross = 0;
  let totalDiscount = 0;

  items.forEach((it) => {
    const qty = Number(it.quantity) || 0;
    const price = Number(it.price) || 0;
    const gross = qty * price;
    subtotalGross += gross;

    const rawDisc = Number(it.discount) || 0;
    let discVal = 0;
    if (it.discountType === 'nominal') {
      discVal = Math.min(rawDisc, gross);
    } else {
      if (rawDisc > 0 && rawDisc < 1) {
        discVal = gross * rawDisc;
      } else {
        discVal = gross * (Math.min(rawDisc, 100) / 100);
      }
    }
    totalDiscount += Math.min(discVal, gross);
  });

  const dpp = Math.max(0, subtotalGross - totalDiscount);
  const isPpn = formData.taxType === 'ppn_11';
  const ppnAmount = isPpn ? Math.round(dpp * 0.11) : 0;
  const totalAmount = Math.round(dpp + ppnAmount);
  const remainingBill = formData.status === 'Lunas' ? 0 : totalAmount;

  return {
    subtotalGross: Math.round(subtotalGross),
    totalDiscount: Math.round(totalDiscount),
    dpp: Math.round(dpp),
    ppnAmount,
    totalAmount,
    remainingBill
  };
});

// Watch modal state to initialize or reset
watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      errors.value = {};
      itemNotice.value = '';
      medicineSearchQuery.value = '';
      showMedicineDropdown.value = false;
      showSupplierDropdown.value = false;

      if (props.invoice) {
        // Edit mode
        formData.invoiceNumber = props.invoice.invoiceNumber || props.invoice.invoice_number || '';
        formData.invoiceDate = props.invoice.invoiceDate || props.invoice.invoice_date || getTodayString();
        formData.status = props.invoice.status || 'Belum Lunas';
        if (formData.status === 'Lunas') {
          formData.dueDate = '';
        } else {
          formData.dueDate = props.invoice.dueDate || props.invoice.due_date || '';
        }
        formData.supplierId = props.invoice.supplierId || props.invoice.supplier_id || '';
        formData.taxType = props.invoice.taxType || props.invoice.tax_type || 'non_tax';
        formData.taxRate = Number(props.invoice.taxRate ?? props.invoice.tax_rate ?? 0);
        formData.notes = props.invoice.notes || '';

        const sup = props.suppliers.find((s) => Number(s.id) === Number(formData.supplierId));
        supplierSearchQuery.value = sup ? sup.name : (props.invoice.supplierName || '');

        formData.items = (props.invoice.items || []).map((it) => {
          const itemObj = {
            id: it.id || Date.now() + Math.random(),
            medicineId: it.medicineId || it.medicine_id,
            medicineName: it.medicineName || it.medicine_name || '',
            medicineCode: it.medicineCode || it.medicine_code || '',
            quantity: Number(it.quantity) || 1,
            unit: it.unit || 'Box',
            batchNumber: it.batchNumber || it.batch_number || '',
            expiredDate: it.expiredDate || it.expired_date || '',
            isRepack: Boolean(it.isRepack || it.is_repack),
            price: Number(it.price || it.unitPrice || it.unit_price) || 0,
            discountType: it.discountType || it.discount_type || 'percentage',
            discount: Number(it.discount) || 0,
            margin: it.margin != null ? Number(it.margin) : 0.28,
            subtotal: Number(it.subtotal) || 0
          };
          itemObj.subtotal = calculateItemSubtotal(itemObj);
          return itemObj;
        });

        if (formData.items.length === 0) {
          formData.items = [createDefaultItem()];
        }
      } else {
        // Add mode
        formData.invoiceNumber = '';
        formData.invoiceDate = getTodayString();
        formData.dueDate = '';
        formData.supplierId = '';
        formData.status = 'Belum Lunas';
        formData.taxType = 'non_tax';
        formData.taxRate = 0;
        formData.notes = '';
        supplierSearchQuery.value = '';
        formData.items = [];
      }

      nextTick(() => {
        initialFormSnapshot = takeSnapshot();
      });
    }
  },
  { immediate: true }
);

// Close handler with dirty check
const handleRequestClose = () => {
  if (isDirty.value) {
    isConfirmCloseOpen.value = true;
  } else {
    emit('close');
  }
};

const handleConfirmDiscard = () => {
  isConfirmCloseOpen.value = false;
  emit('close');
};

// Form validation
const validate = () => {
  const errs = {};

  if (!formData.invoiceDate) {
    errs.invoiceDate = 'Tanggal faktur wajib diisi.';
  }

  if (!formData.invoiceNumber || !formData.invoiceNumber.trim()) {
    errs.invoiceNumber = 'Nomor faktur wajib diisi.';
  }

  if (!formData.supplierId) {
    errs.supplierId = 'Supplier wajib dipilih.';
  }

  // Jatuh Tempo is ONLY required when Belum Lunas
  if (formData.status === 'Belum Lunas') {
    if (!formData.dueDate) {
      errs.dueDate = 'Tanggal jatuh tempo wajib diisi.';
    } else if (formData.invoiceDate && formData.dueDate < formData.invoiceDate) {
      errs.dueDate = 'Tanggal jatuh tempo tidak boleh lebih awal dari tanggal faktur.';
    }
  }

  if (!formData.items || formData.items.length === 0) {
    errs.items = 'Minimal harus menambahkan satu obat/sediaan.';
  } else {
    formData.items.forEach((it, idx) => {
      if (!it.medicineId && !it.medicineName) {
        errs[`items.${idx}.medicineId`] = 'Obat wajib dipilih.';
        errs.items = 'Pastikan semua obat telah dipilih dengan benar.';
      }
      if (Number(it.quantity) <= 0) {
        errs[`items.${idx}.quantity`] = 'Kuantitas harus lebih besar dari 0.';
        errs.items = 'Pastikan kuantitas obat valid (minimal 1).';
      }
      if (Number(it.price) < 0) {
        errs[`items.${idx}.price`] = 'Harga beli tidak boleh negatif.';
      }
      if (Number(it.discount) < 0) {
        errs[`items.${idx}.discount`] = 'Diskon tidak boleh negatif.';
      }
      if (it.expiredDate && formData.invoiceDate && it.expiredDate < formData.invoiceDate) {
        errs[`items.${idx}.expiredDate`] = 'Kedaluwarsa tidak boleh sebelum tanggal faktur.';
      }
    });
  }

  errors.value = errs;
  return Object.keys(errs).length === 0;
};

// Handle submit
const handleSubmit = () => {
  if (!validate()) return;

  const isPaid = formData.status === 'Lunas';
  const payload = {
    invoiceNumber: formData.invoiceNumber.trim(),
    invoiceDate: formData.invoiceDate,
    status: formData.status,
    dueDate: isPaid ? null : (formData.dueDate || null),
    supplierId: Number(formData.supplierId),
    taxType: formData.taxType,
    taxRate: formData.taxType === 'ppn_11' ? 11 : 0,
    notes: formData.notes ? formData.notes.trim() : '',
    totalAmount: summaryTotals.value.totalAmount,
    items: formData.items.map((it) => ({
      medicineId: it.medicineId,
      medicineName: it.medicineName,
      batchNumber: it.batchNumber ? it.batchNumber.trim() : '',
      expiredDate: it.expiredDate || null,
      quantity: Number(it.quantity),
      unit: it.unit || 'Box',
      isRepack: Boolean(it.isRepack),
      price: Number(it.price),
      discountType: it.discountType || 'percentage',
      discount: Number(it.discount) || 0,
      margin: it.margin != null ? Number(it.margin) : 0.28,
      subtotal: calculateItemSubtotal(it)
    }))
  };

  emit('submit', {
    payload,
    onError: (res) => {
      if (res && res.errors) {
        errors.value = res.errors;
      }
    }
  });
};

// Helper to get nested item error
const getItemError = (index, field) => {
  const snakeField = field.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
  return errors.value[`items.${index}.${field}`] || errors.value[`items.${index}.${snakeField}`] || '';
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="handleRequestClose">
      <div class="modal-content modal-xl invoice-modal-container">
        <!-- Modal Top Header -->
        <div class="modal-header">
          <div class="header-content">
            <div class="header-icon-box" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
            </div>
            <div>
              <h3 class="modal-title">{{ invoice ? 'Edit Faktur' : 'Tambah Faktur' }}</h3>
              <p class="modal-subtitle">
                {{ invoice ? 'Perbarui informasi faktur pembelian farmasi.' : 'Lengkapi formulir untuk menambahkan faktur pembelian baru.' }}
              </p>
            </div>
          </div>
          <button
            type="button"
            class="btn-close"
            @click="handleRequestClose"
            :disabled="isSubmitting"
            aria-label="Tutup Formulir"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Modal Body with 2 Main Cards -->
        <div class="modal-body">
          <form autocomplete="off" id="invoice-form" @submit.prevent="handleSubmit">
            <!-- ========================================== -->
            <!-- CARD 1: FORM FAKTUR (Header Faktur)        -->
            <!-- ========================================== -->
            <div class="section-card">
              <div class="section-card-header">
                <div class="section-title-wrap">
                  <span class="section-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="3" width="7" height="7"></rect>
                      <rect x="14" y="3" width="7" height="7"></rect>
                      <rect x="14" y="14" width="7" height="7"></rect>
                      <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                  </span>
                  <div>
                    <h4 class="section-title">FORM FAKTUR</h4>
                    <span class="section-desc">Informasi utama nomor faktur, tanggal, status pembayaran, dan supplier</span>
                  </div>
                </div>
              </div>

              <div class="section-card-body">
                <div class="two-column-grid">
                  <!-- Left Column: Tanggal Faktur, Nomor Faktur, Status Lunas, Jatuh Tempo (kondisional) -->
                  <div class="col-stack">
                    <!-- 1. Tanggal Faktur -->
                    <div class="form-row-field">
                      <label class="form-label field-label" for="form-invoice-date">
                        Tanggal Faktur <span class="required-mark">*</span>
                      </label>
                      <div class="field-control">
                        <input
                          id="form-invoice-date"
                          v-model="formData.invoiceDate"
                          type="date"
                          class="form-input"
                          :class="{ 'has-error': errors.invoiceDate || errors.invoice_date }"
                          required
                        />
                        <span v-if="errors.invoiceDate || errors.invoice_date" class="error-message">
                          {{ errors.invoiceDate || errors.invoice_date }}
                        </span>
                      </div>
                    </div>

                    <!-- 2. Nomor Faktur -->
                    <div class="form-row-field">
                      <label class="form-label field-label" for="form-invoice-number">
                        Nomor <span class="required-mark">*</span>
                      </label>
                      <div class="field-control">
                        <input
                          id="form-invoice-number"
                          v-model="formData.invoiceNumber"
                          type="text"
                          class="form-input"
                          :class="{ 'has-error': errors.invoiceNumber || errors.invoice_number }"
                          placeholder="Nomor faktur dari supplier..."
                          required
                        />
                        <span v-if="errors.invoiceNumber || errors.invoice_number" class="error-message">
                          {{ errors.invoiceNumber || errors.invoice_number }}
                        </span>
                      </div>
                    </div>

                    <!-- 3. Status Lunas (Radio Buttons) -->
                    <div class="form-row-field">
                      <label class="form-label field-label">
                        Status Lunas <span class="required-mark">*</span>
                      </label>
                      <div class="field-control radio-group-box">
                        <label class="radio-label">
                          <input
                            type="radio"
                            name="payment_status"
                            value="Belum Lunas"
                            v-model="formData.status"
                          />
                          <span class="radio-text">Belum Lunas</span>
                        </label>
                        <label class="radio-label">
                          <input
                            type="radio"
                            name="payment_status"
                            value="Lunas"
                            v-model="formData.status"
                          />
                          <span class="radio-text">Lunas</span>
                        </label>
                      </div>
                    </div>

                    <!-- 4. Tanggal Jatuh Tempo (HANYA DITAMPILKAN JIKA BELUM LUNAS) -->
                    <div v-if="formData.status === 'Belum Lunas'" class="form-row-field">
                      <label class="form-label field-label" for="form-due-date">
                        Jatuh Tempo <span class="required-mark">*</span>
                      </label>
                      <div class="field-control">
                        <input
                          id="form-due-date"
                          v-model="formData.dueDate"
                          type="date"
                          class="form-input"
                          :class="{ 'has-error': errors.dueDate || errors.due_date }"
                          required
                        />
                        <span v-if="errors.dueDate || errors.due_date" class="error-message">
                          {{ errors.dueDate || errors.due_date }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Right Column: Supplier, Telp, Alamat -->
                  <div class="col-stack">
                    <!-- 5. Supplier (Searchable Select with Clear) -->
                    <div class="form-row-field">
                      <label class="form-label field-label" for="supplier-search-input">
                        Supplier <span class="required-mark">*</span>
                      </label>
                      <div class="field-control relative-position">
                        <div class="searchable-input-wrapper">
                          <input
                            id="supplier-search-input"
                            v-model="supplierSearchQuery"
                            type="text"
                            class="form-input"
                            :class="{ 'has-error': errors.supplierId || errors.supplier_id }"
                            placeholder="Cari atau pilih Supplier..."
                            @focus="showSupplierDropdown = true"
                          />
                          <button
                            v-if="formData.supplierId || supplierSearchQuery"
                            type="button"
                            class="btn-clear-supplier"
                            @click="clearSupplier"
                            title="Hapus Pilihan Supplier"
                            aria-label="Hapus Pilihan Supplier"
                          >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                              <line x1="18" y1="6" x2="6" y2="18"></line>
                              <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                          </button>
                        </div>

                        <!-- Dropdown Options -->
                        <div
                          v-if="showSupplierDropdown"
                          class="autocomplete-dropdown"
                          @mousedown.prevent
                        >
                          <div
                            v-for="sup in filteredSuppliers"
                            :key="sup.id"
                            class="dropdown-item"
                            :class="{ selected: Number(sup.id) === Number(formData.supplierId) }"
                            @click="selectSupplier(sup)"
                          >
                            <span class="dropdown-item-title">{{ sup.name }}</span>
                            <span class="dropdown-item-meta">{{ sup.city || 'Alamat tidak tersedia' }}</span>
                          </div>
                          <div v-if="filteredSuppliers.length === 0" class="dropdown-empty">
                            Supplier tidak ditemukan
                          </div>
                        </div>

                        <span v-if="errors.supplierId || errors.supplier_id" class="error-message">
                          {{ errors.supplierId || errors.supplier_id }}
                        </span>
                      </div>
                    </div>

                    <!-- 6. Telp Supplier (Readonly) -->
                    <div class="form-row-field">
                      <label class="form-label field-label" for="form-supplier-phone">Telp</label>
                      <div class="field-control">
                        <input
                          id="form-supplier-phone"
                          type="text"
                          class="form-input input-readonly"
                          :value="selectedSupplier?.phone || '-'"
                          placeholder="Telp"
                          readonly
                        />
                      </div>
                    </div>

                    <!-- 7. Alamat Supplier (Readonly) -->
                    <div class="form-row-field">
                      <label class="form-label field-label" for="form-supplier-address">Alamat</label>
                      <div class="field-control">
                        <textarea
                          id="form-supplier-address"
                          class="form-textarea input-readonly"
                          rows="3"
                          :value="selectedSupplier?.city || selectedSupplier?.address || '-'"
                          placeholder="Alamat Supplier"
                          readonly
                        ></textarea>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ======================================================== -->
            <!-- CARD 2: FORM DETAIL DATA FAKTUR (OBAT)                  -->
            <!-- ======================================================== -->
            <div class="section-card mt-4">
              <div class="section-card-header">
                <div class="section-title-wrap">
                  <span class="section-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="3" width="7" height="7"></rect>
                      <rect x="14" y="3" width="7" height="7"></rect>
                      <rect x="14" y="14" width="7" height="7"></rect>
                      <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                  </span>
                  <div>
                    <h4 class="section-title">FORM DETAIL DATA FAKTUR (OBAT)</h4>
                    <span class="section-desc">Cari obat dan lengkapi kuantitas, no batch, expired date, harga, diskon, dan margin</span>
                  </div>
                </div>
              </div>

              <div class="section-card-body">
                <!-- Top Medicine Search Bar (Matches Legacy UI Structure) -->
                <div class="medicine-search-wrapper relative-position">
                  <div class="search-input-box">
                    <input
                      ref="medicineSearchInputRef"
                      v-model="medicineSearchQuery"
                      type="text"
                      class="form-input search-medicine-input"
                      placeholder="Masukkan Nama atau Kode Obat..."
                      @focus="showMedicineDropdown = true"
                    />
                    <span class="search-icon-right" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                      </svg>
                    </span>
                  </div>

                  <!-- Autocomplete Suggestions Dropdown -->
                  <div
                    v-if="showMedicineDropdown && medicineSearchQuery.trim()"
                    class="autocomplete-dropdown medicine-results-dropdown"
                    @mousedown.prevent
                  >
                    <div
                      v-for="med in filteredMedicines"
                      :key="med.id"
                      class="medicine-dropdown-item"
                      @click="selectMedicineFromSearch(med)"
                    >
                      <div class="med-item-primary">
                        <span class="med-item-name">{{ med.name }}</span>
                        <span v-if="med.code" class="med-item-code">{{ med.code }}</span>
                      </div>
                      <div class="med-item-secondary">
                        <span v-if="med.generic_name" class="med-item-generic">({{ med.generic_name }})</span>
                        <span class="med-item-satuan">Satuan: <strong>{{ med.unit || 'Box' }}</strong></span>
                        <span class="med-item-price">{{ formatRupiah(med.price) }}</span>
                      </div>
                    </div>
                    <div v-if="filteredMedicines.length === 0" class="dropdown-empty">
                      Obat tidak ditemukan. Periksa kata kunci pencarian.
                    </div>
                  </div>
                </div>

                <!-- Notification Alert if duplicate added -->
                <div v-if="itemNotice" class="alert-info-toast mt-2">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                  </svg>
                  <span>{{ itemNotice }}</span>
                </div>

                <span v-if="errors.items" class="error-message mt-2 mb-2 font-medium">
                  {{ errors.items }}
                </span>

                <!-- Items Table / Grid List -->
                <div class="detail-items-container mt-3">
                  <div class="detail-table-header">
                    <div class="col-header-nama">Nama Obat / Sediaan</div>
                    <div class="col-header-keterangan">Keterangan & Rincian Transaksi</div>
                    <div class="col-header-action text-center">Aksi</div>
                  </div>

                  <!-- Row Items List -->
                  <div v-if="formData.items.length > 0" class="detail-rows-list">
                    <div
                      v-for="(item, idx) in formData.items"
                      :key="item.id"
                      class="detail-row-card"
                    >
                      <!-- Col 1: Medicine Name & Satuan -->
                      <div class="cell-nama">
                        <h5 class="row-med-name">{{ item.medicineName || 'Pilih Obat' }}</h5>
                        <div class="row-med-meta">
                          <span class="row-unit-badge">{{ item.unit || 'Box' }}</span>
                          <span v-if="item.medicineCode" class="row-code-badge">{{ item.medicineCode }}</span>
                        </div>
                        <span v-if="getItemError(idx, 'medicineId')" class="error-message">
                          {{ getItemError(idx, 'medicineId') }}
                        </span>
                      </div>

                      <!-- Col 2: Keterangan (2 Sub-columns: Batch/Exp/Qty/Repack + Harga/Diskon/Subtotal/Margin) -->
                      <div class="cell-keterangan">
                        <div class="keterangan-grid">
                          <!-- Subcol A: Batch, Expired Date, QTY, Repack -->
                          <div class="subcol-stack">
                            <!-- No Batch -->
                            <div class="sub-field-row">
                              <label class="sub-label">No Batch</label>
                              <div class="sub-control">
                                <input
                                  v-model="item.batchNumber"
                                  type="text"
                                  class="form-input item-input"
                                  :class="{ 'has-error': getItemError(idx, 'batchNumber') }"
                                  placeholder="No Batch"
                                  aria-label="Nomor Batch"
                                />
                                <span v-if="getItemError(idx, 'batchNumber')" class="error-message">
                                  {{ getItemError(idx, 'batchNumber') }}
                                </span>
                              </div>
                            </div>

                            <!-- Expired Date -->
                            <div class="sub-field-row">
                              <label class="sub-label">Expired Date</label>
                              <div class="sub-control">
                                <input
                                  v-model="item.expiredDate"
                                  type="date"
                                  class="form-input item-input"
                                  :class="{ 'has-error': getItemError(idx, 'expiredDate') }"
                                  aria-label="Tanggal Kedaluwarsa"
                                />
                                <span v-if="getItemError(idx, 'expiredDate')" class="error-message">
                                  {{ getItemError(idx, 'expiredDate') }}
                                </span>
                              </div>
                            </div>

                            <!-- QTY -->
                            <div class="sub-field-row">
                              <label class="sub-label">QTY</label>
                              <div class="sub-control">
                                <input
                                  v-model.number="item.quantity"
                                  type="number"
                                  min="1"
                                  step="any"
                                  class="form-input item-input"
                                  :class="{ 'has-error': getItemError(idx, 'quantity') }"
                                  placeholder="0"
                                  @input="onItemValueChange(item)"
                                  required
                                  aria-label="Jumlah Kuantitas"
                                />
                                <span v-if="getItemError(idx, 'quantity')" class="error-message">
                                  {{ getItemError(idx, 'quantity') }}
                                </span>
                              </div>
                            </div>

                            <!-- Repack (Tidak / Ya) -->
                            <div class="sub-field-row">
                              <label class="sub-label">Repack</label>
                              <div class="sub-control">
                                <select
                                  v-model="item.isRepack"
                                  class="form-select item-select"
                                  aria-label="Pilihan Repack"
                                >
                                  <option :value="false">Tidak</option>
                                  <option :value="true">Ya</option>
                                </select>
                              </div>
                            </div>
                          </div>

                          <!-- Subcol B: Harga Beli/@, Diskon, Sub Total, Margin -->
                          <div class="subcol-stack">
                            <!-- Harga Beli/@ -->
                            <div class="sub-field-row">
                              <label class="sub-label">Harga Beli/@</label>
                              <div class="sub-control">
                                <div
                                  class="currency-input-group"
                                  :class="{ 'has-error': getItemError(idx, 'price') }"
                                >
                                  <span class="currency-prefix">Rp.</span>
                                  <input
                                    v-model.number="item.price"
                                    type="number"
                                    min="0"
                                    step="any"
                                    class="currency-input"
                                    placeholder="0"
                                    @input="onItemValueChange(item)"
                                    aria-label="Harga Beli Satuan"
                                  />
                                </div>
                                <span v-if="getItemError(idx, 'price')" class="error-message">
                                  {{ getItemError(idx, 'price') }}
                                </span>
                              </div>
                            </div>

                            <!-- Diskon -->
                            <div class="sub-field-row">
                              <label class="sub-label">Diskon</label>
                              <div class="sub-control">
                                <div class="discount-input-wrap">
                                  <input
                                    v-model.number="item.discount"
                                    type="number"
                                    min="0"
                                    step="any"
                                    class="form-input item-input discount-value-input"
                                    :class="{ 'has-error': getItemError(idx, 'discount') }"
                                    placeholder="0"
                                    @input="onItemValueChange(item)"
                                    aria-label="Diskon Item"
                                  />
                                  <select
                                    v-model="item.discountType"
                                    class="discount-type-select"
                                    @change="onItemValueChange(item)"
                                    aria-label="Tipe Diskon"
                                  >
                                    <option value="percentage">%</option>
                                    <option value="nominal">Rp</option>
                                  </select>
                                </div>
                                <span v-if="getItemError(idx, 'discount')" class="error-message">
                                  {{ getItemError(idx, 'discount') }}
                                </span>
                              </div>
                            </div>

                            <!-- Sub Total (Readonly) -->
                            <div class="sub-field-row">
                              <label class="sub-label font-bold text-navy">Sub Total</label>
                              <div class="sub-control">
                                <div class="currency-input-group is-readonly">
                                  <span class="currency-prefix">Rp.</span>
                                  <input
                                    type="text"
                                    class="currency-input currency-input-readonly font-bold text-navy"
                                    :value="formatNumber(calculateItemSubtotal(item))"
                                    readonly
                                    aria-label="Subtotal Item"
                                  />
                                </div>
                              </div>
                            </div>

                            <!-- Margin -->
                            <div class="sub-field-row">
                              <label class="sub-label">Margin</label>
                              <div class="sub-control">
                                <select
                                  v-model.number="item.margin"
                                  class="form-select item-select"
                                  aria-label="Margin Keuntungan"
                                >
                                  <option
                                    v-for="m in standardMargins"
                                    :key="m.value"
                                    :value="m.value"
                                  >
                                    {{ m.label }}
                                  </option>
                                </select>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Col 3: Delete Action -->
                      <div class="cell-action text-center">
                        <button
                          type="button"
                          class="btn-delete-item"
                          @click="removeItemRow(idx)"
                          title="Hapus Obat Ini"
                          aria-label="Hapus Obat"
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Empty state if no medicines yet -->
                  <div v-else class="empty-medicines-box">
                    <div class="empty-icon" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                      </svg>
                    </div>
                    <p class="empty-text">Belum ada obat yang ditambahkan ke detail faktur.</p>
                    <p class="empty-subtext">Gunakan kolom pencarian di atas untuk menambahkan obat.</p>
                  </div>
                </div>

                <!-- ======================================================== -->
                <!-- BOTTOM INFO BOX & TAX / TOTALS SUMMARY                  -->
                <!-- ======================================================== -->
                <div class="detail-summary-grid mt-4">
                  <!-- Left: Alert Info Standar BigSIRS -->
                  <div class="info-alert-card">
                    <div class="info-alert-header">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-info-icon">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                      </svg>
                      <h5 class="info-alert-title">Petunjuk Pengisian Faktur</h5>
                    </div>
                    <div class="info-alert-body">
                      <ul>
                        <li>Pastikan seluruh data sudah sesuai dengan lembar faktur fisik dari Supplier.</li>
                        <li>Pastikan nomor batch, tanggal kedaluwarsa, jumlah, harga beli, diskon, dan pajak sudah benar.</li>
                        <li>Jangan memuat ulang halaman sebelum proses penyimpanan berhasil.</li>
                        <li>Diskon dihitung otomatis sesuai tipe persentase (%) atau nominal rupiah (Rp).</li>
                        <li class="info-warehouse-highlight">
                          Detail item faktur akan dicatat sesuai faktur pembelian obat.
                        </li>
                      </ul>
                    </div>
                  </div>

                  <!-- Right: Pajak PPN & Ringkasan Nilai -->
                  <div class="totals-calculation-card">
                    <!-- Tax Selection (NON PPN / PPN 11%) -->
                    <div class="tax-radio-row">
                      <label class="tax-radio-label">
                        <input
                          type="radio"
                          name="tax_type"
                          value="non_tax"
                          v-model="formData.taxType"
                        />
                        <span>NON PPN</span>
                      </label>
                      <label class="tax-radio-label">
                        <input
                          type="radio"
                          name="tax_type"
                          value="ppn_11"
                          v-model="formData.taxType"
                        />
                        <span>PPN (11%)</span>
                      </label>
                    </div>

                    <!-- Summary Lines -->
                    <div class="summary-key-values">
                      <!-- Sub Total -->
                      <div class="summary-row">
                        <span class="summary-label">SUB TOTAL :</span>
                        <div class="summary-value-box">
                          <span class="prefix">Rp.</span>
                          <span class="value">{{ summaryTotals.subtotalGross.toLocaleString('id-ID') }}</span>
                        </div>
                      </div>

                      <!-- Diskon -->
                      <div class="summary-row">
                        <span class="summary-label">DISKON :</span>
                        <div class="summary-value-box">
                          <span class="prefix">Rp.</span>
                          <span class="value">{{ summaryTotals.totalDiscount.toLocaleString('id-ID') }}</span>
                        </div>
                      </div>

                      <!-- PPN 11% -->
                      <div class="summary-row">
                        <span class="summary-label">PPN 11% :</span>
                        <div class="summary-value-box">
                          <span class="prefix">Rp.</span>
                          <span class="value">{{ summaryTotals.ppnAmount.toLocaleString('id-ID') }}</span>
                        </div>
                      </div>

                      <!-- Total -->
                      <div class="summary-row highlight-total">
                        <span class="summary-label">TOTAL :</span>
                        <div class="summary-value-box">
                          <span class="prefix">Rp.</span>
                          <span class="value text-primary">{{ summaryTotals.totalAmount.toLocaleString('id-ID') }}</span>
                        </div>
                      </div>

                      <!-- Sisa Tagihan (Informasi pelunasan) -->
                      <div class="summary-row sisa-tagihan-row">
                        <span class="summary-label">SISA TAGIHAN :</span>
                        <div class="summary-value-box">
                          <span class="prefix">Rp.</span>
                          <span class="value" :class="summaryTotals.remainingBill === 0 ? 'text-success' : 'text-danger'">
                            {{ summaryTotals.remainingBill.toLocaleString('id-ID') }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- Sticky Modal Footer -->
        <div class="modal-footer">
          <div class="footer-left">
            <button
              type="button"
              class="btn-cancel"
              @click="handleRequestClose"
              :disabled="isSubmitting"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
              <span>Tutup</span>
            </button>
          </div>

          <div class="footer-right">
            <button
              type="submit"
              form="invoice-form"
              class="btn-primary"
              :disabled="isSubmitting"
            >
              <svg v-if="isSubmitting" class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                <path d="M12 2a10 10 0 0 1 10 10"></path>
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              <span>{{ isSubmitting ? 'Menyimpan...' : (invoice ? 'Simpan Perubahan' : 'Simpan Faktur') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Discard Unsaved Changes Dialog -->
    <div v-if="isConfirmCloseOpen" class="modal-overlay sub-modal-overlay">
      <div class="confirm-dialog-card">
        <div class="confirm-header">
          <div class="confirm-warning-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </div>
          <h4>Perubahan Belum Disimpan</h4>
        </div>
        <p class="confirm-text">
          Ada perubahan formulir faktur yang belum disimpan. Apakah Anda yakin ingin keluar dan membuang perubahan ini?
        </p>
        <div class="confirm-actions">
          <button type="button" class="btn-cancel" @click="isConfirmCloseOpen = false">
            Lanjutkan Mengisi
          </button>
          <button type="button" class="btn-danger-confirm" @click="handleConfirmDiscard">
            Tutup Tanpa Menyimpan
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.invoice-modal-container {
  max-width: 1200px;
  width: 95vw;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.15rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  background: #ffffff;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.header-icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: var(--color-primary-light, #e0f2fe);
  color: var(--color-primary, #0284c7);
}

.header-icon-box svg {
  width: 22px;
  height: 22px;
}

.modal-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
}

.modal-subtitle {
  margin: 0.2rem 0 0;
  font-size: 0.82rem;
  color: var(--color-text-secondary, #64748b);
}

.modal-body {
  padding: 1.25rem 1.5rem;
  overflow-y: auto;
  background: #f8fafc;
}

/* Section Cards */
.section-card {
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
  overflow: hidden;
}

.section-card-header {
  padding: 0.85rem 1.25rem;
  background: #f1f5f9;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.section-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.section-icon {
  display: flex;
  align-items: center;
  color: var(--color-primary, #0284c7);
}

.section-icon svg {
  width: 18px;
  height: 18px;
}

.section-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 800;
  letter-spacing: 0.02em;
  color: var(--color-text-navy, #17243d);
}

.section-desc {
  font-size: 0.75rem;
  color: var(--color-text-secondary, #64748b);
}

.section-card-body {
  padding: 1.25rem;
}

/* Two Column Grid for Section 1 */
.two-column-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.col-stack {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.form-row-field {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.field-label {
  width: 130px;
  flex-shrink: 0;
  padding-top: 0.45rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.field-control {
  flex: 1;
}

.radio-group-box {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding-top: 0.45rem;
}

.radio-label {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.85rem;
  cursor: pointer;
  color: var(--color-text-navy, #17243d);
}

.radio-label input[type="radio"] {
  accent-color: var(--color-primary, #0284c7);
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.relative-position {
  position: relative;
}

.searchable-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.btn-clear-supplier {
  position: absolute;
  right: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 4px;
  background: #fee2e2;
  color: #dc2626;
  border: none;
  cursor: pointer;
}

.btn-clear-supplier svg {
  width: 14px;
  height: 14px;
}

.input-readonly {
  background-color: #f8fafc !important;
  color: #475569 !important;
  cursor: default;
}

/* Medicine Search Bar */
.medicine-search-wrapper {
  margin-bottom: 0.85rem;
}

.search-input-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-medicine-input {
  padding-right: 2.75rem;
  font-size: 0.88rem;
  height: 42px;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft, #cbd5e1);
}

.search-icon-right {
  position: absolute;
  right: 12px;
  color: #64748b;
  display: flex;
  align-items: center;
  pointer-events: none;
}

.search-icon-right svg {
  width: 18px;
  height: 18px;
}

/* Autocomplete Dropdown */
.autocomplete-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 50;
  max-height: 250px;
  overflow-y: auto;
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  margin-top: 4px;
}

.dropdown-item {
  padding: 0.65rem 0.85rem;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
}

.dropdown-item:hover,
.dropdown-item.selected {
  background: #f0f9ff;
}

.dropdown-item-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.dropdown-item-meta {
  font-size: 0.75rem;
  color: #64748b;
}

.medicine-dropdown-item {
  padding: 0.65rem 0.85rem;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: background 0.15s;
}

.medicine-dropdown-item:hover {
  background: #f0fdf4;
}

.med-item-primary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.med-item-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.med-item-code {
  font-size: 0.72rem;
  background: #e2e8f0;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
  color: #475569;
}

.med-item-secondary {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.78rem;
  color: #64748b;
}

.med-item-price {
  font-weight: 700;
  color: var(--color-primary, #0284c7);
}

.dropdown-empty {
  padding: 1rem;
  text-align: center;
  font-size: 0.82rem;
  color: #94a3b8;
}

.alert-info-toast {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  padding: 0.55rem 0.85rem;
  border-radius: 6px;
  font-size: 0.82rem;
}

.alert-info-toast svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* Detail Items Table Header */
.detail-table-header {
  display: grid;
  grid-template-columns: 240px 1fr 48px;
  gap: 1rem;
  padding: 0.65rem 0.85rem;
  background: #f8fafc;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px 8px 0 0;
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-text-secondary, #64748b);
}

.detail-rows-list {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-top: none;
  border-radius: 0 0 8px 8px;
  background: #ffffff;
}

.detail-row-card {
  display: grid;
  grid-template-columns: 240px 1fr 44px;
  gap: 1.25rem;
  padding: 0.9rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #f1f5f9);
  align-items: center;
}

.detail-row-card:last-child {
  border-bottom: none;
}

/* Col 1: Nama */
.cell-nama {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.row-med-name {
  margin: 0;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  line-height: 1.3;
}

.row-med-meta {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.row-unit-badge {
  display: inline-block;
  padding: 0.15rem 0.45rem;
  font-size: 0.72rem;
  font-weight: 600;
  background: #f1f5f9;
  color: #475569;
  border-radius: 4px;
}

.row-code-badge {
  font-size: 0.72rem;
  color: #94a3b8;
}

/* Col 2: Keterangan (2 Subcols) */
.cell-keterangan {
  width: 100%;
}

.keterangan-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.subcol-stack {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.sub-field-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sub-label {
  width: 90px;
  flex-shrink: 0;
  font-size: 0.78rem;
  font-weight: 600;
  color: #475569;
}

.sub-control {
  flex: 1;
  min-width: 0;
}

.item-input,
.item-select {
  height: 32px;
  font-size: 0.82rem;
  padding: 0.25rem 0.5rem;
}

/* Currency Input Group */
.currency-input-group {
  display: flex;
  align-items: stretch;
  width: 100%;
  height: 32px;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  background: #ffffff;
  overflow: hidden;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.currency-input-group:focus-within {
  border-color: var(--color-primary, #0284c7);
  box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.12);
}

.currency-input-group.has-error {
  border-color: #ef4444;
}

.currency-input-group.is-readonly {
  background: #f8fafc;
  border-color: #e2e8f0;
}

.currency-prefix {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.55rem;
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
  background: #f1f5f9;
  border-right: 1px solid var(--color-border-soft, #cbd5e1);
  flex-shrink: 0;
  user-select: none;
  line-height: 1;
}

.currency-input-group.is-readonly .currency-prefix {
  background: #edf2f7;
  border-right-color: #e2e8f0;
  color: #475569;
}

.currency-input {
  flex: 1;
  min-width: 0;
  height: 100%;
  border: none !important;
  background: transparent !important;
  padding: 0 0.55rem;
  font-size: 0.82rem;
  color: #1e293b;
  outline: none !important;
  box-shadow: none !important;
}

.currency-input::placeholder {
  color: #94a3b8;
}

.currency-input-readonly {
  cursor: default;
  color: var(--color-text-navy, #17243d);
  font-weight: 700;
  background: transparent !important;
}

.discount-input-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}

.discount-value-input {
  flex: 1;
  min-width: 0;
  height: 32px;
}

.discount-type-select {
  height: 32px;
  width: 56px;
  flex-shrink: 0;
  padding: 0 0.4rem;
  font-size: 0.8rem;
  font-weight: 600;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  background: #f8fafc;
  color: #334155;
  cursor: pointer;
  transition: border-color 0.15s ease-in-out;
}

.discount-type-select:focus {
  border-color: var(--color-primary, #0284c7);
  outline: none;
}

/* Col 3: Action */
.cell-action {
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-delete-item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: transparent;
  color: #ef4444;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-delete-item:hover {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #dc2626;
}

.btn-delete-item svg {
  width: 16px;
  height: 16px;
}

.empty-medicines-box {
  padding: 2.5rem 1rem;
  text-align: center;
  border: 1px dashed var(--color-border-soft, #cbd5e1);
  border-top: none;
  border-radius: 0 0 8px 8px;
  background: #ffffff;
}

.empty-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #94a3b8;
  margin-bottom: 0.5rem;
}

.empty-icon svg {
  width: 24px;
  height: 24px;
}

.empty-text {
  margin: 0;
  font-size: 0.88rem;
  font-weight: 600;
  color: #475569;
}

.empty-subtext {
  margin: 0.25rem 0 0;
  font-size: 0.78rem;
  color: #94a3b8;
}

/* Bottom Detail Summary Grid */
.detail-summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

/* Left Info Alert */
.info-alert-card {
  padding: 1rem 1.25rem;
  background: #f0f7ff;
  border: 1px solid #bfdbfe;
  border-radius: 10px;
}

.info-alert-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.65rem;
}

.alert-info-icon {
  width: 18px;
  height: 18px;
  color: #2563eb;
}

.info-alert-title {
  margin: 0;
  font-size: 0.88rem;
  font-weight: 700;
  color: #1e40af;
}

.info-alert-body ul {
  margin: 0;
  padding-left: 1.15rem;
  font-size: 0.8rem;
  color: #1e3a8a;
  line-height: 1.6;
}

.info-warehouse-highlight {
  margin-top: 0.35rem;
  color: #1d4ed8;
  font-weight: 500;
}

/* Right Calculation Card */
.totals-calculation-card {
  padding: 1rem 1.25rem;
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 10px;
}

.tax-radio-row {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding-bottom: 0.85rem;
  margin-bottom: 0.85rem;
  border-bottom: 1px solid #f1f5f9;
}

.tax-radio-label {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
  cursor: pointer;
}

.tax-radio-label input[type="radio"] {
  accent-color: var(--color-primary, #0284c7);
  width: 16px;
  height: 16px;
}

.summary-key-values {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-label {
  font-size: 0.82rem;
  font-weight: 700;
  color: #475569;
  letter-spacing: 0.02em;
}

.summary-value-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 190px;
  padding: 0.35rem 0.75rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
}

.summary-value-box .prefix {
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
  flex-shrink: 0;
}

.summary-value-box .value {
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  text-align: right;
  flex: 1;
}

.highlight-total .summary-label {
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--color-text-navy, #17243d);
}

.highlight-total .summary-value-box {
  background: #f0f9ff;
  border-color: #bae6fd;
}

.highlight-total .summary-value-box .value {
  font-size: 1.05rem;
  font-weight: 800;
}

.sisa-tagihan-row .summary-label {
  font-size: 0.8rem;
  color: #64748b;
}

.text-success {
  color: #16a34a !important;
}

.text-danger {
  color: #dc2626 !important;
}

/* Modal Footer */
.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: #ffffff;
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
}

.btn-cancel {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.55rem 1.15rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  background: #ffffff;
  color: #475569;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover:not(:disabled) {
  background: #f1f5f9;
  color: #1e293b;
}

.btn-cancel svg {
  width: 16px;
  height: 16px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.55rem 1.35rem;
  border: none;
  background: var(--color-primary, #0284c7);
  color: #ffffff;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  opacity: 0.92;
  transform: translateY(-1px);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary svg {
  width: 16px;
  height: 16px;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Confirm Dialog */
.sub-modal-overlay {
  z-index: 1000;
  background: rgba(15, 23, 42, 0.4);
}

.confirm-dialog-card {
  width: 440px;
  background: #ffffff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
  animation: scaleIn 0.2s ease-out;
}

@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.confirm-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.confirm-warning-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: #fef3c7;
  color: #d97706;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.confirm-warning-icon svg {
  width: 20px;
  height: 20px;
}

.confirm-header h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
}

.confirm-text {
  font-size: 0.85rem;
  color: #64748b;
  margin: 0 0 1.25rem 0;
  line-height: 1.5;
}

.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-danger-confirm {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  border: none;
  background: #dc2626;
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-danger-confirm:hover {
  background: #b91c1c;
}

/* Responsive adjustments */
@media (max-width: 900px) {
  .two-column-grid,
  .detail-summary-grid {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }

  .detail-table-header {
    display: none;
  }

  .detail-row-card {
    grid-template-columns: 1fr;
    gap: 0.75rem;
    position: relative;
    padding-top: 2rem;
  }

  .cell-action {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
  }

  .keterangan-grid {
    grid-template-columns: 1fr;
  }
}
</style>
