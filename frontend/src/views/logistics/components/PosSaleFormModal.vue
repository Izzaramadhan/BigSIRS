<script setup>
import { ref, reactive, watch, computed } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  saleToEdit: {
    type: Object,
    default: null
  },
  customers: {
    type: Array,
    default: () => []
  },
  isSubmitting: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'submit']);

const errors = ref({});

const getTodayDate = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const formData = reactive({
  date: getTodayDate(),
  customerId: '',
  customerName: ''
});

// Normalized customer list to { id, name, code }
const normalizedCustomers = computed(() => {
  return props.customers.map((item, index) => {
    if (typeof item === 'object' && item !== null) {
      return {
        id: item.id || index + 1,
        name: item.name || item.customerName || item.pembeli || 'Pembeli ' + (index + 1),
        code: item.code || ''
      };
    }
    return {
      id: index + 1,
      name: String(item),
      code: ''
    };
  });
});

const customerSearch = ref('');
const isCustomerDropdownOpen = ref(false);

const filteredCustomers = computed(() => {
  const q = customerSearch.value.trim().toLowerCase();
  if (!q) return normalizedCustomers.value;
  return normalizedCustomers.value.filter(
    (c) => c.name.toLowerCase().includes(q) || (c.code && c.code.toLowerCase().includes(q))
  );
});

const selectCustomer = (c) => {
  formData.customerId = c.id;
  formData.customerName = c.name;
  customerSearch.value = c.name;
  isCustomerDropdownOpen.value = false;
  if (errors.value.customerName) {
    errors.value.customerName = '';
  }
};

const handleCustomerInput = (e) => {
  const val = e.target.value;
  customerSearch.value = val;
  formData.customerName = val;
  // If exact match
  const match = normalizedCustomers.value.find((c) => c.name.toLowerCase() === val.trim().toLowerCase());
  if (match) {
    formData.customerId = match.id;
  } else {
    formData.customerId = '';
  }
  isCustomerDropdownOpen.value = true;
};

const initForm = () => {
  errors.value = {};
  if (props.saleToEdit) {
    let rawDate = props.saleToEdit.date || props.saleToEdit.transaction_date || props.saleToEdit.transactionDate || '';
    if (rawDate && rawDate.includes('T')) {
      rawDate = rawDate.split('T')[0];
    } else if (rawDate && rawDate.includes(' ')) {
      rawDate = rawDate.split(' ')[0];
    }
    formData.date = rawDate || getTodayDate();
    formData.customerId = props.saleToEdit.customerId || props.saleToEdit.customer_id || '';
    formData.customerName = props.saleToEdit.pembeli || props.saleToEdit.customerName || props.saleToEdit.customer_name || '';
    customerSearch.value = formData.customerName;

    // If customerId is not set, try to match by name
    if (!formData.customerId && formData.customerName) {
      const match = normalizedCustomers.value.find((c) => c.name === formData.customerName);
      if (match) formData.customerId = match.id;
    }
  } else {
    formData.date = getTodayDate();
    const defaultCust = normalizedCustomers.value[0];
    if (defaultCust) {
      formData.customerId = defaultCust.id;
      formData.customerName = defaultCust.name;
      customerSearch.value = defaultCust.name;
    } else {
      formData.customerId = '';
      formData.customerName = '';
      customerSearch.value = '';
    }
  }
};

watch(
  () => props.isOpen,
  (open) => {
    if (open) initForm();
  },
  { immediate: true }
);

watch(
  () => props.customers,
  () => {
    if (props.isOpen && !formData.customerId && normalizedCustomers.value.length > 0) {
      formData.customerId = normalizedCustomers.value[0].id;
      formData.customerName = normalizedCustomers.value[0].name;
      customerSearch.value = normalizedCustomers.value[0].name;
    }
  }
);

const validate = () => {
  const newErrors = {};
  if (!formData.date) {
    newErrors.date = 'Tanggal transaksi wajib diisi.';
  }
  if (!formData.customerName || formData.customerName.trim() === '') {
    newErrors.customerName = 'Nama pembeli wajib dipilih atau diisi.';
  }
  errors.value = newErrors;
  return Object.keys(newErrors).length === 0;
};

const handleSubmit = () => {
  if (!validate()) return;

  emit('submit', {
    transaction_date: formData.date,
    date: formData.date,
    customer_id: formData.customerId || null,
    customerId: formData.customerId || null,
    customer_name: formData.customerName,
    customerName: formData.customerName,
    pembeli: formData.customerName
  });
};

const handleClose = () => {
  isCustomerDropdownOpen.value = false;
  emit('close');
};
</script>

<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="modal-overlay"
      @click.self="handleClose"
    >
      <div class="modal-card">
        <!-- Modal Header -->
        <div class="modal-header">
          <div class="header-title-box">
            <span class="header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
              </svg>
            </span>
            <h3 class="modal-title">
              {{ saleToEdit ? 'Edit Penjualan Bebas' : 'Tambah Penjualan Bebas' }}
            </h3>
          </div>
          <button
            type="button"
            class="btn-close"
            aria-label="Tutup modal"
            @click="handleClose"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Modal Form Body -->
        <form @submit.prevent="handleSubmit">
          <div class="modal-body">
            <!-- 1. Tanggal -->
            <div class="form-group">
              <label for="input-pos-sale-date" class="form-label">
                Tanggal <span class="text-danger">*</span>
              </label>
              <input
                id="input-pos-sale-date"
                v-model="formData.date"
                type="date"
                class="form-control"
                :class="{ 'is-invalid': errors.date }"
                required
              />
              <span v-if="errors.date" class="invalid-feedback">
                {{ errors.date }}
              </span>
            </div>

            <!-- 2. Pembeli (Searchable Select) -->
            <div class="form-group relative">
              <label for="input-pos-sale-customer" class="form-label">
                Pembeli <span class="text-danger">*</span>
              </label>
              <div class="searchable-customer-wrapper">
                <input
                  id="input-pos-sale-customer"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors.customerName }"
                  :value="customerSearch"
                  placeholder="Ketik atau pilih nama pembeli..."
                  autocomplete="off"
                  @input="handleCustomerInput"
                  @focus="isCustomerDropdownOpen = true"
                />
                <button
                  type="button"
                  class="dropdown-toggle-btn"
                  @click="isCustomerDropdownOpen = !isCustomerDropdownOpen"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>
              </div>

              <!-- Customer Dropdown List -->
              <div
                v-if="isCustomerDropdownOpen"
                class="customer-dropdown-menu"
              >
                <div
                  v-if="filteredCustomers.length === 0"
                  class="dropdown-empty-item text-muted"
                >
                  Gunakan "{{ customerSearch }}" sebagai pembeli umum
                </div>
                <div
                  v-for="c in filteredCustomers"
                  :key="c.id"
                  class="dropdown-item"
                  :class="{ selected: c.id === formData.customerId }"
                  @click="selectCustomer(c)"
                >
                  <span class="cust-name font-semibold text-navy">{{ c.name }}</span>
                  <span v-if="c.code" class="cust-code text-muted text-xs">({{ c.code }})</span>
                </div>
              </div>
              <span v-if="errors.customerName" class="invalid-feedback">
                {{ errors.customerName }}
              </span>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer">
            <button
              type="button"
              class="btn-cancel"
              :disabled="isSubmitting"
              @click="handleClose"
            >
              Batal
            </button>
            <button
              type="submit"
              class="btn-primary"
              :disabled="isSubmitting"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1.25rem;
}

.modal-card {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  overflow: visible;
  animation: modalScale 0.2s ease-out;
}

@keyframes modalScale {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.15rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  background: #f8fafc;
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
}

.header-title-box {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.header-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
}

.header-icon svg {
  width: 18px;
  height: 18px;
}

.modal-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
}

.btn-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.35rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
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
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  position: relative;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  position: relative;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
}

.searchable-customer-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.dropdown-toggle-btn {
  position: absolute;
  right: 0.5rem;
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dropdown-toggle-btn svg {
  width: 16px;
  height: 16px;
}

.customer-dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  max-height: 200px;
  overflow-y: auto;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  z-index: 1050;
}

.dropdown-item {
  padding: 0.65rem 0.85rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  transition: background 0.15s ease;
}

.dropdown-item:hover {
  background-color: #f1f5f9;
}

.dropdown-item.selected {
  background-color: #f0fdf4;
  color: #087f78;
}

.dropdown-empty-item {
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
}

.form-control {
  width: 100%;
  padding: 0.6rem 0.85rem;
  font-size: 0.875rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background-color: #ffffff;
  color: #1e293b;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.15);
}

.is-invalid {
  border-color: #ef4444 !important;
}

.invalid-feedback {
  font-size: 0.78rem;
  color: #ef4444;
}

.text-danger {
  color: #ef4444;
}

.text-navy {
  color: #0f172a;
}

.text-muted {
  color: #64748b;
}

.text-xs {
  font-size: 0.75rem;
}

.font-semibold {
  font-weight: 600;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.15rem 1.5rem;
  border-top: 1px solid #f1f5f9;
  background: #f8fafc;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
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
  transition: all 0.15s ease;
}

.btn-cancel:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 6px;
  background-color: var(--color-primary, #087f78);
  border: 1px solid transparent;
  color: #ffffff;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-primary:hover:not(:disabled) {
  background-color: #066660;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary svg {
  width: 16px;
  height: 16px;
}
</style>
