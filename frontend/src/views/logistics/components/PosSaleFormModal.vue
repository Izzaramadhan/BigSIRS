<script setup>
import { ref, reactive, computed, watch } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  saleToEdit: {
    type: Object,
    default: null
  },
  otcMedicines: {
    type: Array,
    default: () => []
  },
  locations: {
    type: Array,
    default: () => []
  },
  paymentMethods: {
    type: Array,
    default: () => []
  },
  submitting: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'submit']);

// Form State
const formData = reactive({
  customerName: 'Walk-in Customer',
  customerType: 'Umum',
  location: 'Farmasi Pusat (Kasir 1)',
  paymentMethod: 'Tunai',
  paidAmount: 0,
  items: []
});

// Selected medicine in picker
const selectedMedicineId = ref('');
const inputQty = ref(1);
const formErrors = reactive({
  customerName: '',
  items: '',
  paidAmount: ''
});

// Available stock of currently selected medicine
const selectedMedicine = computed(() => {
  return props.otcMedicines.find((m) => m.id === Number(selectedMedicineId.value)) || null;
});

// Watch edit mode
watch(
  () => props.isOpen,
  (val) => {
    if (val) {
      if (props.saleToEdit) {
        formData.customerName = props.saleToEdit.customerName || 'Walk-in Customer';
        formData.customerType = props.saleToEdit.customerType || 'Umum';
        formData.location = props.saleToEdit.location || 'Farmasi Pusat (Kasir 1)';
        formData.paymentMethod = props.saleToEdit.paymentMethod || 'Tunai';
        formData.paidAmount = props.saleToEdit.paidAmount || props.saleToEdit.totalAmount || 0;
        formData.items = JSON.parse(JSON.stringify(props.saleToEdit.items || []));
      } else {
        formData.customerName = 'Walk-in Customer';
        formData.customerType = 'Umum';
        formData.location = 'Farmasi Pusat (Kasir 1)';
        formData.paymentMethod = 'Tunai';
        formData.paidAmount = 0;
        formData.items = [
          {
            id: 1,
            name: 'Paracetamol 500 mg Tablet',
            category: 'Obat Bebas',
            price: 15000,
            qty: 2,
            unit: 'Strip',
            subtotal: 30000
          },
          {
            id: 2,
            name: 'Vitamin C 500 mg IPI',
            category: 'Suplemen',
            price: 10000,
            qty: 2,
            unit: 'Strip',
            subtotal: 20000
          }
        ];
      }
      selectedMedicineId.value = '';
      inputQty.value = 1;
      formErrors.customerName = '';
      formErrors.items = '';
      formErrors.paidAmount = '';
    }
  }
);

// Totals
const totalAmount = computed(() => {
  return formData.items.reduce((sum, item) => sum + (Number(item.subtotal) || 0), 0);
});

const changeAmount = computed(() => {
  if (formData.paymentMethod !== 'Tunai') return 0;
  const paid = Number(formData.paidAmount) || 0;
  return Math.max(0, paid - totalAmount.value);
});

// Add Item
const handleAddItem = () => {
  if (!selectedMedicine.value) return;
  const qty = Number(inputQty.value) || 1;
  if (qty <= 0) return;

  const existingIndex = formData.items.findIndex((it) => it.name === selectedMedicine.value.name);
  if (existingIndex !== -1) {
    formData.items[existingIndex].qty += qty;
    formData.items[existingIndex].subtotal =
      formData.items[existingIndex].qty * formData.items[existingIndex].price;
  } else {
    formData.items.push({
      id: selectedMedicine.value.id,
      name: selectedMedicine.value.name,
      category: selectedMedicine.value.category,
      price: selectedMedicine.value.price,
      qty,
      unit: selectedMedicine.value.unit,
      subtotal: selectedMedicine.value.price * qty
    });
  }

  // Auto set paidAmount if greater than 0
  if (formData.paidAmount < totalAmount.value) {
    formData.paidAmount = totalAmount.value;
  }

  // Reset item inputs
  selectedMedicineId.value = '';
  inputQty.value = 1;
  formErrors.items = '';
};

// Remove Item
const handleRemoveItem = (index) => {
  formData.items.splice(index, 1);
  if (formData.paidAmount < totalAmount.value) {
    formData.paidAmount = totalAmount.value;
  }
};

// Update item Qty
const handleUpdateQty = (index, newQty) => {
  const qty = Number(newQty);
  if (qty <= 0) {
    handleRemoveItem(index);
    return;
  }
  formData.items[index].qty = qty;
  formData.items[index].subtotal = qty * formData.items[index].price;
};

// Validate
const validate = () => {
  let isValid = true;
  if (!formData.customerName.trim()) {
    formErrors.customerName = 'Nama pembeli harus diisi.';
    isValid = false;
  } else {
    formErrors.customerName = '';
  }

  if (formData.items.length === 0) {
    formErrors.items = 'Minimal harus menambahkan 1 item obat/BMHP.';
    isValid = false;
  } else {
    formErrors.items = '';
  }

  return isValid;
};

// Submit handler
const handleSubmit = (isDraft = false) => {
  if (!isDraft && !validate()) return;

  emit('submit', {
    isDraft,
    customerName: formData.customerName,
    customerType: formData.customerType,
    location: formData.location,
    paymentMethod: formData.paymentMethod,
    paidAmount: isDraft ? 0 : Number(formData.paidAmount) || totalAmount.value,
    items: formData.items
  });
};

const formatRupiah = (val) => {
  if (val == null) return 'Rp 0';
  return 'Rp ' + Number(val).toLocaleString('id-ID');
};
</script>

<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-dialog">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="header-icon-box" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
              <line x1="8" y1="21" x2="16" y2="21"></line>
              <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
          </div>
          <div>
            <h3 class="modal-title">
              {{ saleToEdit ? 'Edit Draft Transaksi Kasir' : 'Transaksi Kasir OTC Baru' }}
            </h3>
            <p class="modal-subtitle">
              Kasir Penjualan Obat Bebas, Vitamin, Suplemen & BMHP Non-Resep
            </p>
          </div>
        </div>

        <button type="button" class="btn-close" aria-label="Tutup Modal" @click="emit('close')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <!-- Section 1: Customer & Cashier Info -->
        <div class="form-section">
          <h4 class="form-section-title">Informasi Pembeli & Kasir</h4>
          <div class="form-grid">
            <!-- Customer Name -->
            <div class="form-group">
              <label class="form-label" for="pos-cust-name">
                Nama Pembeli <span class="required">*</span>
              </label>
              <input
                id="pos-cust-name"
                v-model="formData.customerName"
                type="text"
                class="form-input"
                placeholder="Walk-in Customer / Nama Pembeli..."
                :class="{ 'has-error': formErrors.customerName }"
              />
              <span v-if="formErrors.customerName" class="error-msg">{{ formErrors.customerName }}</span>
            </div>

            <!-- Customer Type -->
            <div class="form-group">
              <label class="form-label" for="pos-cust-type">Tipe Pembeli</label>
              <select id="pos-cust-type" v-model="formData.customerType" class="form-select">
                <option value="Umum">Umum (Walk-in Customer)</option>
                <option value="Staf RS">Staf / Dokter Rumah Sakit</option>
                <option value="Pasien">Pasien Poliklinik</option>
              </select>
            </div>

            <!-- Cashier Location -->
            <div class="form-group">
              <label class="form-label" for="pos-location">Lokasi Kasir</label>
              <select id="pos-location" v-model="formData.location" class="form-select">
                <option v-for="loc in locations.filter(l => l !== 'Semua Lokasi')" :key="loc" :value="loc">
                  {{ loc }}
                </option>
              </select>
            </div>

            <!-- Payment Method -->
            <div class="form-group">
              <label class="form-label" for="pos-payment">Metode Pembayaran</label>
              <select id="pos-payment" v-model="formData.paymentMethod" class="form-select">
                <option v-for="pm in paymentMethods.filter(p => p !== 'Semua Metode')" :key="pm" :value="pm">
                  {{ pm }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Section 2: Product Item Picker -->
        <div class="form-section">
          <h4 class="form-section-title">Tambah Obat Bebas & BMHP</h4>
          <div class="item-picker-box">
            <div class="picker-medicine-select">
              <label class="form-label" for="pos-med-picker">Pilih Produk / Obat OTC</label>
              <select id="pos-med-picker" v-model="selectedMedicineId" class="form-select">
                <option value="" disabled>-- Pilih Obat OTC / BMHP --</option>
                <option v-for="med in otcMedicines" :key="med.id" :value="med.id">
                  {{ med.name }} - {{ formatRupiah(med.price) }}/{{ med.unit }} (Stok: {{ med.availableStock }})
                </option>
              </select>
            </div>

            <div class="picker-qty-input">
              <label class="form-label" for="pos-med-qty">Jumlah</label>
              <input
                id="pos-med-qty"
                v-model.number="inputQty"
                type="number"
                min="1"
                class="form-input text-center"
              />
            </div>

            <div class="picker-add-action">
              <label class="form-label">&nbsp;</label>
              <button
                type="button"
                class="btn-add-item"
                :disabled="!selectedMedicine"
                @click="handleAddItem"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Tambah
              </button>
            </div>
          </div>
          <span v-if="formErrors.items" class="error-msg mt-2">{{ formErrors.items }}</span>
        </div>

        <!-- Section 3: Selected Items Table -->
        <div class="form-section">
          <h4 class="form-section-title">Daftar Belanja ({{ formData.items.length }} Item)</h4>
          <div class="table-responsive">
            <table class="items-edit-table">
              <thead>
                <tr>
                  <th class="col-sm text-center">NO</th>
                  <th>NAMA OBAT / BMHP</th>
                  <th class="text-right">HARGA</th>
                  <th class="col-qty-edit text-center">QTY</th>
                  <th class="text-right">SUBTOTAL</th>
                  <th class="col-sm text-center">AKSI</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="formData.items.length === 0">
                  <td colspan="6" class="text-center text-muted py-4">
                    Belum ada item ditambahkan. Pilih obat di atas untuk menambahkan.
                  </td>
                </tr>
                <tr v-for="(it, idx) in formData.items" :key="idx">
                  <td class="text-center font-mono">{{ idx + 1 }}</td>
                  <td>
                    <span class="font-medium text-navy">{{ it.name }}</span>
                    <span v-if="it.category" class="item-cat-sub">({{ it.category }})</span>
                  </td>
                  <td class="text-right font-mono">{{ formatRupiah(it.price) }}</td>
                  <td class="text-center">
                    <div class="qty-control">
                      <button type="button" class="btn-qty" @click="handleUpdateQty(idx, it.qty - 1)">-</button>
                      <span class="qty-num font-mono">{{ it.qty }}</span>
                      <button type="button" class="btn-qty" @click="handleUpdateQty(idx, it.qty + 1)">+</button>
                    </div>
                  </td>
                  <td class="text-right font-mono font-semibold">{{ formatRupiah(it.subtotal) }}</td>
                  <td class="text-center">
                    <button
                      type="button"
                      class="btn-delete-row"
                      title="Hapus Item"
                      @click="handleRemoveItem(idx)"
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
        </div>

        <!-- Section 4: Payment Summary Box -->
        <div class="payment-calc-wrapper">
          <div class="calc-card">
            <div class="calc-row">
              <span class="calc-label">Total Belanja:</span>
              <span class="calc-val font-mono font-bold text-teal text-xl">{{ formatRupiah(totalAmount) }}</span>
            </div>

            <div v-if="formData.paymentMethod === 'Tunai'" class="calc-row-input">
              <label class="calc-label" for="pos-paid-amount">Uang Diterima (Rp):</label>
              <input
                id="pos-paid-amount"
                v-model.number="formData.paidAmount"
                type="number"
                class="form-input text-right font-mono font-bold"
                placeholder="0"
              />
            </div>

            <div v-if="formData.paymentMethod === 'Tunai'" class="calc-row">
              <span class="calc-label">Kembalian:</span>
              <span class="calc-val font-mono font-bold text-success">{{ formatRupiah(changeAmount) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button
          type="button"
          class="btn-cancel"
          :disabled="submitting"
          @click="emit('close')"
        >
          Batal
        </button>

        <button
          type="button"
          class="btn-draft"
          :disabled="submitting"
          @click="handleSubmit(true)"
        >
          Simpan sebagai Draft
        </button>

        <button
          type="button"
          class="btn-primary"
          :disabled="submitting"
          @click="handleSubmit(false)"
        >
          <svg v-if="submitting" class="spin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          Proses Pembayaran (Selesai)
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.modal-dialog {
  background: #ffffff;
  border-radius: 14px;
  width: 100%;
  max-width: 780px;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: #f8fafc;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.header-icon-box {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon-box svg {
  width: 22px;
  height: 22px;
}

.modal-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.modal-subtitle {
  font-size: 0.78rem;
  color: var(--color-text-secondary, #64748b);
  margin: 0.2rem 0 0 0;
}

.btn-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.4rem;
  border-radius: 6px;
  transition: all 0.15s ease;
}

.btn-close:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-close svg {
  width: 18px;
  height: 18px;
}

.modal-body {
  padding: 1.4rem 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.4rem;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.form-section-title {
  font-size: 0.86rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  letter-spacing: -0.01em;
  margin: 0;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
}

.required {
  color: #dc2626;
}

.form-input,
.form-select {
  height: 38px;
  padding: 0 0.85rem;
  font-size: 0.82rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 7px;
  background-color: #ffffff;
  color: var(--color-text-navy, #17243d);
  width: 100%;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.12);
}

.has-error {
  border-color: #dc2626 !important;
}

.error-msg {
  font-size: 0.74rem;
  color: #dc2626;
}

/* Item Picker */
.item-picker-box {
  display: flex;
  align-items: flex-end;
  gap: 0.85rem;
  background: #f8fafc;
  padding: 0.95rem 1.15rem;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.picker-medicine-select {
  flex: 3;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.picker-qty-input {
  flex: 1;
  max-width: 90px;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.picker-add-action {
  display: flex;
  flex-direction: column;
}

.btn-add-item {
  height: 38px;
  padding: 0 1.2rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #ffffff;
  background: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  border-radius: 7px;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-add-item:hover:not(:disabled) {
  background: #076d67;
}

.btn-add-item:disabled {
  background: #cbd5e1;
  border-color: #cbd5e1;
  cursor: not-allowed;
}

.btn-add-item svg {
  width: 15px;
  height: 15px;
}

/* Items Edit Table */
.items-edit-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}

.items-edit-table thead th {
  background: #f1f5f9;
  color: #475569;
  font-weight: 700;
  font-size: 0.72rem;
  padding: 0.65rem 0.85rem;
  border-bottom: 1px solid #cbd5e1;
}

.items-edit-table tbody td {
  padding: 0.75rem 0.85rem;
  border-bottom: 1px solid #f1f5f9;
}

.item-cat-sub {
  font-size: 0.72rem;
  color: #94a3b8;
  margin-left: 0.35rem;
}

.qty-control {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  padding: 0.15rem 0.35rem;
}

.btn-qty {
  width: 20px;
  height: 20px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  font-weight: 700;
  font-size: 0.8rem;
  color: #334155;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-qty:hover {
  background: #e2e8f0;
}

.qty-num {
  font-size: 0.8rem;
  font-weight: 600;
  color: #17243d;
  min-width: 20px;
  text-align: center;
}

.btn-delete-row {
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  color: #ef4444;
  cursor: pointer;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.btn-delete-row:hover {
  background: #fee2e2;
}

.btn-delete-row svg {
  width: 15px;
  height: 15px;
}

/* Payment Calc Card */
.payment-calc-wrapper {
  display: flex;
  justify-content: flex-end;
}

.calc-card {
  width: 100%;
  max-width: 360px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.calc-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.calc-row-input {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.calc-row-input input {
  width: 150px;
}

.calc-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
}

.text-teal {
  color: var(--color-primary, #087f78) !important;
}

.text-success {
  color: #059669 !important;
}

.text-xl {
  font-size: 1.2rem;
}

/* Modal Footer */
.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
  background: #f8fafc;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn-cancel {
  height: 38px;
  padding: 0 1.2rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  cursor: pointer;
}

.btn-draft {
  height: 38px;
  padding: 0 1.25rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #0369a1;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-draft:hover {
  background: #e0f2fe;
}

.btn-primary {
  height: 38px;
  padding: 0 1.35rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #ffffff;
  background: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  border-radius: 7px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  transition: all 0.15s ease;
}

.btn-primary:hover {
  background: #076d67;
}

.btn-primary svg {
  width: 15px;
  height: 15px;
}

.spin-icon {
  animation: spin 1s linear infinite;
  width: 15px;
  height: 15px;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
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

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.col-sm {
  width: 50px;
}

.col-qty-edit {
  width: 110px;
}
</style>
