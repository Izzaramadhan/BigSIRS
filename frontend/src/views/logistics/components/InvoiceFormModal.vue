<script setup>
import { ref, reactive, computed, watch } from 'vue';

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

const errors = ref({});

const createDefaultItem = () => ({
  id: Date.now() + Math.random(),
  medicineId: '',
  medicineName: '',
  quantity: 1,
  unit: 'Box',
  price: 0,
  discount: 0,
  subtotal: 0
});

const getTodayString = () => new Date().toISOString().split('T')[0];

const getFutureDateString = (daysAhead = 30) => {
  const d = new Date();
  d.setDate(d.getDate() + daysAhead);
  return d.toISOString().split('T')[0];
};

const formData = reactive({
  invoiceNumber: '',
  referenceNumber: '',
  invoiceDate: getTodayString(),
  dueDate: getFutureDateString(30),
  supplierId: '',
  status: 'Belum Lunas',
  notes: '',
  items: [createDefaultItem()]
});

// Calculate subtotal for a single item
const calculateItemSubtotal = (item) => {
  const qty = Number(item.quantity) || 0;
  const price = Number(item.price) || 0;
  const disc = Number(item.discount) || 0;
  const discMultiplier = Math.max(0, 1 - disc / 100);
  return Math.round(qty * price * discMultiplier);
};

// Handle medicine selection in a row
const onMedicineChange = (item) => {
  const med = props.medicines.find((m) => m.id === Number(item.medicineId));
  if (med) {
    item.medicineName = med.name;
    item.unit = med.unit;
    item.price = med.price;
  } else {
    item.medicineName = '';
  }
  item.subtotal = calculateItemSubtotal(item);
};

// Handle qty / price / discount changes
const onItemValueChange = (item) => {
  item.subtotal = calculateItemSubtotal(item);
};

// Add new row to items
const addItemRow = () => {
  formData.items.push(createDefaultItem());
};

// Remove a row
const removeItemRow = (index) => {
  if (formData.items.length > 1) {
    formData.items.splice(index, 1);
  } else {
    // If only 1 row, just reset it
    formData.items[0] = createDefaultItem();
  }
};

// Total invoice amount computed from all items
const totalInvoiceAmount = computed(() => {
  return formData.items.reduce((sum, item) => sum + (Number(item.subtotal) || 0), 0);
});

// Watch modal state to initialize or reset
watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      errors.value = {};
      if (props.invoice) {
        // Edit mode
        formData.invoiceNumber = props.invoice.invoiceNumber;
        formData.referenceNumber = props.invoice.referenceNumber || '';
        formData.invoiceDate = props.invoice.invoiceDate;
        formData.dueDate = props.invoice.dueDate;
        formData.supplierId = props.invoice.supplierId;
        formData.status = props.invoice.status;
        formData.notes = props.invoice.notes || '';
        formData.items = (props.invoice.items || []).map((it) => ({
          ...it,
          id: it.id || Date.now() + Math.random()
        }));
        if (formData.items.length === 0) {
          formData.items = [createDefaultItem()];
        }
      } else {
        // Add mode
        formData.invoiceNumber = '';
        formData.referenceNumber = '';
        formData.invoiceDate = getTodayString();
        formData.dueDate = getFutureDateString(30);
        formData.supplierId = '';
        formData.status = 'Belum Lunas';
        formData.notes = '';
        formData.items = [createDefaultItem()];
      }
    }
  },
  { immediate: true }
);

const validate = () => {
  const errs = {};

  if (!formData.invoiceNumber || !formData.invoiceNumber.trim()) {
    errs.invoiceNumber = 'Nomor faktur wajib diisi.';
  }

  if (!formData.invoiceDate) {
    errs.invoiceDate = 'Tanggal faktur wajib diisi.';
  }

  if (!formData.dueDate) {
    errs.dueDate = 'Tanggal jatuh tempo wajib diisi.';
  }

  if (!formData.supplierId) {
    errs.supplierId = 'Supplier wajib dipilih.';
  }

  // Validate items
  if (!formData.items || formData.items.length === 0) {
    errs.items = 'Minimal harus menambahkan satu obat/sediaan.';
  } else {
    const invalidItems = formData.items.some(
      (it) => !it.medicineId || Number(it.quantity) <= 0 || Number(it.price) < 0
    );
    if (invalidItems) {
      errs.items = 'Pastikan semua obat telah dipilih dengan kuantitas minimal 1.';
    }
  }

  errors.value = errs;
  return Object.keys(errs).length === 0;
};

const handleSubmit = () => {
  if (!validate()) return;

  emit('submit', {
    ...formData,
    totalAmount: totalInvoiceAmount.value
  });
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-xl">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <h3>{{ invoice ? 'Edit Faktur' : 'Tambah Faktur' }}</h3>
            <p>{{ invoice ? 'Perbarui informasi faktur pembelian obat.' : 'Lengkapi formulir untuk menambahkan faktur pembelian baru.' }}</p>
          </div>
          <button type="button" class="btn-close" @click="$emit('close')" :disabled="isSubmitting" aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <form id="invoice-form" @submit.prevent="handleSubmit">
            <!-- Row 1: Invoice Number & Reference/PO -->
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="form-invoice-number">
                  Nomor Faktur <span class="required-mark">*</span>
                </label>
                <input
                  id="form-invoice-number"
                  v-model="formData.invoiceNumber"
                  type="text"
                  class="form-input"
                  :class="{ 'has-error': errors.invoiceNumber }"
                  placeholder="Contoh: FKT-KF-202609-0892"
                />
                <span v-if="errors.invoiceNumber" class="error-message">{{ errors.invoiceNumber }}</span>
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-reference-number">Nomor Referensi / PO</label>
                <input
                  id="form-reference-number"
                  v-model="formData.referenceNumber"
                  type="text"
                  class="form-input"
                  placeholder="Contoh: PO-2026-0814"
                />
              </div>
            </div>

            <!-- Row 2: Invoice Date & Supplier -->
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="form-invoice-date">
                  Tanggal Faktur <span class="required-mark">*</span>
                </label>
                <input
                  id="form-invoice-date"
                  v-model="formData.invoiceDate"
                  type="date"
                  class="form-input"
                  :class="{ 'has-error': errors.invoiceDate }"
                />
                <span v-if="errors.invoiceDate" class="error-message">{{ errors.invoiceDate }}</span>
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-supplier">
                  Supplier <span class="required-mark">*</span>
                </label>
                <select
                  id="form-supplier"
                  v-model="formData.supplierId"
                  class="form-select"
                  :class="{ 'has-error': errors.supplierId }"
                >
                  <option value="" disabled>Pilih Supplier</option>
                  <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">
                    {{ sup.name }} ({{ sup.city }})
                  </option>
                </select>
                <span v-if="errors.supplierId" class="error-message">{{ errors.supplierId }}</span>
              </div>
            </div>

            <!-- Row 3: Due Date & Status -->
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="form-due-date">
                  Tanggal Jatuh Tempo <span class="required-mark">*</span>
                </label>
                <input
                  id="form-due-date"
                  v-model="formData.dueDate"
                  type="date"
                  class="form-input"
                  :class="{ 'has-error': errors.dueDate }"
                />
                <span v-if="errors.dueDate" class="error-message">{{ errors.dueDate }}</span>
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-status">Status Faktur</label>
                <select id="form-status" v-model="formData.status" class="form-select">
                  <option value="Belum Lunas">Belum Lunas</option>
                  <option value="Lunas">Lunas</option>
                </select>
              </div>
            </div>

            <!-- Row 4: Notes -->
            <div class="form-group">
              <label class="form-label" for="form-notes">Catatan Faktur</label>
              <textarea
                id="form-notes"
                v-model="formData.notes"
                class="form-textarea"
                rows="2"
                placeholder="Catatan tambahan terkait faktur atau pengiriman..."
              ></textarea>
            </div>

            <!-- Items Table Section -->
            <div class="items-section">
              <div class="items-header">
                <div>
                  <h4 class="items-title">Daftar Obat / Sediaan</h4>
                  <p class="items-subtitle">Rincian item obat yang diterima sesuai faktur</p>
                </div>
                <button
                  type="button"
                  class="btn-outline btn-sm"
                  @click="addItemRow"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                  </svg>
                  Tambah Item
                </button>
              </div>

              <span v-if="errors.items" class="error-message mb-2">{{ errors.items }}</span>

              <div class="items-table-wrapper">
                <table class="items-table">
                  <thead>
                    <tr>
                      <th style="width: 32%;">Obat / Sediaan <span class="required-mark">*</span></th>
                      <th style="width: 12%;">Jumlah <span class="required-mark">*</span></th>
                      <th style="width: 16%;">Satuan</th>
                      <th style="width: 16%;">Harga Satuan (IDR)</th>
                      <th style="width: 10%;">Diskon (%)</th>
                      <th style="width: 14%;">Subtotal (IDR)</th>
                      <th style="width: 48px; text-align: center;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, idx) in formData.items" :key="item.id">
                      <!-- Obat select -->
                      <td>
                        <select
                          v-model="item.medicineId"
                          class="form-select item-select"
                          @change="onMedicineChange(item)"
                          required
                          aria-label="Pilih Obat / Sediaan"
                        >
                          <option value="" disabled>Pilih Obat...</option>
                          <option v-for="med in medicines" :key="med.id" :value="med.id">
                            {{ med.name }}
                          </option>
                        </select>
                      </td>

                      <!-- Kuantitas -->
                      <td>
                        <input
                          v-model.number="item.quantity"
                          type="number"
                          min="1"
                          class="form-input item-input"
                          @input="onItemValueChange(item)"
                          required
                          aria-label="Jumlah Kuantitas"
                        />
                      </td>

                      <!-- Satuan -->
                      <td>
                        <input
                          v-model="item.unit"
                          type="text"
                          class="form-input item-input"
                          placeholder="Box / Strip"
                          aria-label="Satuan Obat"
                        />
                      </td>

                      <!-- Harga Satuan -->
                      <td>
                        <input
                          v-model.number="item.price"
                          type="number"
                          min="0"
                          step="500"
                          class="form-input item-input"
                          @input="onItemValueChange(item)"
                          aria-label="Harga Satuan"
                        />
                      </td>

                      <!-- Diskon % -->
                      <td>
                        <input
                          v-model.number="item.discount"
                          type="number"
                          min="0"
                          max="100"
                          class="form-input item-input"
                          @input="onItemValueChange(item)"
                          aria-label="Diskon Persen"
                        />
                      </td>

                      <!-- Subtotal -->
                      <td class="font-semibold text-navy text-right">
                        {{ formatRupiah(item.subtotal) }}
                      </td>

                      <!-- Hapus row -->
                      <td class="text-center">
                        <button
                          type="button"
                          class="btn-delete-row"
                          title="Hapus Baris"
                          aria-label="Hapus Baris"
                          @click="removeItemRow(idx)"
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                          </svg>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Total Summary Box -->
              <div class="total-summary-box">
                <div class="summary-line">
                  <span class="summary-label">Total Item:</span>
                  <span class="summary-value">{{ formData.items.length }} Obat</span>
                </div>
                <div class="summary-line total-highlight">
                  <span class="summary-label">Total Faktur:</span>
                  <span class="summary-total-amount text-primary">{{ formatRupiah(totalInvoiceAmount) }}</span>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="button" class="btn-cancel" @click="$emit('close')" :disabled="isSubmitting">
            Batal
          </button>
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
            <span>{{ isSubmitting ? 'Menyimpan...' : (invoice ? 'Simpan Perubahan' : 'Simpan Faktur') }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.form-row {
  display: flex;
  gap: 1rem;
}

.flex-1 {
  flex: 1;
}

.items-section {
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--color-border-soft, #e8edf3);
}

.items-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.85rem;
}

.items-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
}

.items-subtitle {
  margin: 0.15rem 0 0;
  font-size: 0.78rem;
  color: var(--color-text-secondary, #64748b);
}

.btn-sm {
  padding: 0.4rem 0.75rem;
  font-size: 0.8rem;
  border-radius: 6px;
}

.items-table-wrapper {
  overflow-x: auto;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  background: #ffffff;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
  min-width: 750px;
}

.items-table th {
  background: #f8fafc;
  padding: 0.65rem 0.75rem;
  text-align: left;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-text-secondary, #64748b);
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  white-space: nowrap;
}

.items-table td {
  padding: 0.6rem 0.75rem;
  border-bottom: 1px solid var(--color-border-soft, #f1f5f9);
  vertical-align: middle;
}

.items-table tbody tr:last-child td {
  border-bottom: none;
}

.item-select,
.item-input {
  padding: 0.4rem 0.5rem;
  font-size: 0.82rem;
  height: 34px;
}

.btn-delete-row {
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 6px;
  color: var(--color-danger, #d64545);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-delete-row:hover {
  background: var(--color-danger-bg, #fff0f0);
}

.btn-delete-row svg {
  width: 16px;
  height: 16px;
}

.total-summary-box {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.35rem;
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-radius: 8px;
}

.summary-line {
  display: flex;
  gap: 1.5rem;
  font-size: 0.85rem;
  color: var(--color-text-secondary, #64748b);
}

.summary-label {
  font-weight: 500;
}

.summary-value {
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.total-highlight {
  font-size: 1.05rem;
  font-weight: 700;
}

.summary-total-amount {
  font-size: 1.15rem;
  font-weight: 800;
}

.font-semibold {
  font-weight: 600;
}

.text-navy {
  color: var(--color-text-navy, #17243d);
}

.text-right {
  text-align: right;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

@media (max-width: 640px) {
  .form-row {
    flex-direction: column;
    gap: 0;
  }
}
</style>
