<script setup>
import { reactive, ref, computed, watch } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';
import { medicinesService } from '@/services/master-data/medicines.service';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  packageData: {
    type: Object,
    default: null
  },
  item: {
    type: Object,
    default: null
  },
  isSubmitting: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'submit']);

const currentPackage = () => props.packageData || props.item || null;

const form = reactive({
  name: '',
  price: 0,
  quantity: 0,
  description: '',
  items: []
});

const clientErrors = reactive({});

// Medicine search state
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const searchErrorMessage = ref(null);
let searchDebounceTimer = null;

const calculatedTotal = computed(() => {
  const sum = form.items.reduce((total, item) => total + (Number(item.subtotal) || 0), 0);
  return Number(sum.toFixed(2));
});

const formatRupiah = (val) => {
  if (val === null || val === undefined || val === '') return 'Rp0';
  const num = Number(val);
  if (isNaN(num)) return 'Rp0';
  return 'Rp' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(num);
};

const resetForm = () => {
  form.name = '';
  form.price = 0;
  form.quantity = 0;
  form.description = '';
  form.items = [];
  searchQuery.value = '';
  searchResults.value = [];
  isSearching.value = false;
  searchErrorMessage.value = null;
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

const populateForm = (data) => {
  form.name = data.name || '';
  form.price = Number(data.price) || 0;
  form.quantity = data.quantity !== null && data.quantity !== undefined ? Number(data.quantity) : 0;
  form.description = data.description || '';

  if (Array.isArray(data.items)) {
    form.items = data.items.map((item) => {
      const qty = Number(item.quantity) || 1;
      // In edit mode: snapshot price saved on package item detail takes precedence
      const rawPrice = item.unit_price !== undefined && item.unit_price !== null
        ? item.unit_price
        : (item.medicine?.selling_price_per_unit ?? item.medicine?.unit_price ?? item.medicine?.price);
      const hasValidPrice = rawPrice !== null && rawPrice !== undefined && rawPrice !== '' && !isNaN(Number(rawPrice));
      const unitPrice = hasValidPrice ? Number(rawPrice) : 0;
      const subtotal = item.subtotal !== null && item.subtotal !== undefined
        ? Number(item.subtotal)
        : Number((qty * unitPrice).toFixed(2));

      return {
        medicine_id: item.medicine_id,
        medicine_name: item.medicine?.name || item.medicine_name || `Obat #${item.medicine_id}`,
        medicine_unit: item.medicine?.unit?.name || item.medicine_unit || '',
        quantity: qty,
        unit_price: unitPrice,
        subtotal: subtotal,
        has_no_price: !hasValidPrice && unitPrice === 0
      };
    });
  } else {
    form.items = [];
  }

  searchQuery.value = '';
  searchResults.value = [];
  isSearching.value = false;
  searchErrorMessage.value = null;
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      const p = currentPackage();
      if (p) {
        populateForm(p);
      } else {
        resetForm();
      }
    } else {
      resetForm();
    }
  },
  { immediate: true }
);

watch(
  () => [props.packageData, props.item],
  () => {
    if (props.isOpen) {
      const p = currentPackage();
      if (p) {
        populateForm(p);
      }
    }
  }
);

const onSearchInput = () => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer);
  }

  const query = searchQuery.value ? searchQuery.value.trim() : '';
  if (!query || query.length < 2) {
    searchResults.value = [];
    isSearching.value = false;
    searchErrorMessage.value = null;
    return;
  }

  isSearching.value = true;
  searchErrorMessage.value = null;

  searchDebounceTimer = setTimeout(async () => {
    try {
      const res = await medicinesService.getMedicines({ search: query, per_page: 8 });
      const list = res.data || [];
      searchResults.value = Array.isArray(list) ? list : (list.data || []);
    } catch (err) {
      console.error('Failed to search medicines:', err);
      searchErrorMessage.value = 'Gagal mencari obat. Silakan coba lagi.';
      searchResults.value = [];
    } finally {
      isSearching.value = false;
    }
  }, 350);
};

const addMedicine = (medicine) => {
  if (!medicine || !medicine.id) return;

  // Prevent duplicate medicine in package
  const exists = form.items.some((item) => item.medicine_id === medicine.id);
  if (exists) {
    clientErrors.search = `Obat "${medicine.name}" sudah ada dalam daftar.`;
    return;
  }

  delete clientErrors.search;
  delete clientErrors.items;

  // Resolve price from medicine response (selling_price_per_unit / unit_price / price)
  const rawPrice = medicine.selling_price_per_unit ?? medicine.unit_price ?? medicine.price;
  const hasValidPrice = rawPrice !== null && rawPrice !== undefined && rawPrice !== '' && !isNaN(Number(rawPrice));
  const unitPrice = hasValidPrice ? Number(rawPrice) : null;
  const qty = 1;
  const subtotal = unitPrice !== null ? Number((qty * unitPrice).toFixed(2)) : 0;

  form.items.push({
    medicine_id: medicine.id,
    medicine_name: medicine.name,
    medicine_unit: medicine.unit?.name || '',
    quantity: qty,
    unit_price: unitPrice,
    subtotal: subtotal,
    has_no_price: !hasValidPrice
  });

  // Automatically update package total price
  form.price = calculatedTotal.value;

  searchQuery.value = '';
  searchResults.value = [];
};

const updateItemSubtotal = (item) => {
  const qty = Math.max(1, Math.floor(Number(item.quantity) || 1));
  item.quantity = qty;
  if (item.unit_price !== null && item.unit_price !== '' && !isNaN(Number(item.unit_price))) {
    const price = Math.max(0, Number(item.unit_price));
    item.unit_price = price;
    item.subtotal = Number((qty * price).toFixed(2));
    item.has_no_price = false;
  } else {
    item.subtotal = 0;
  }

  form.price = calculatedTotal.value;
};

const removeItem = (index) => {
  form.items.splice(index, 1);
  form.price = calculatedTotal.value;
};

const clearFieldError = (field) => {
  if (clientErrors[field]) {
    delete clientErrors[field];
  }
};

const fieldError = (field) => {
  if (clientErrors[field]) {
    return clientErrors[field];
  }
  if (!props.errors || !props.errors[field]) return null;
  return Array.isArray(props.errors[field]) ? props.errors[field][0] : props.errors[field];
};

const validateClient = () => {
  Object.keys(clientErrors).forEach((k) => delete clientErrors[k]);
  let isValid = true;

  if (!form.name || !form.name.trim()) {
    clientErrors.name = 'Nama paket obat wajib diisi.';
    isValid = false;
  }

  if (form.items.length === 0) {
    clientErrors.items = 'Paket obat harus memiliki minimal satu item obat.';
    isValid = false;
  }

  for (let i = 0; i < form.items.length; i++) {
    const item = form.items[i];
    if (!item.quantity || item.quantity <= 0) {
      clientErrors[`items.${i}.quantity`] = 'Jumlah obat harus lebih dari 0.';
      isValid = false;
    }
    if (item.unit_price === null || item.unit_price === undefined || item.unit_price === '' || isNaN(Number(item.unit_price))) {
      clientErrors[`items.${i}.unit_price`] = 'Harga satuan obat belum tersedia atau wajib diisi.';
      isValid = false;
    } else if (Number(item.unit_price) < 0) {
      clientErrors[`items.${i}.unit_price`] = 'Harga satuan tidak boleh negatif.';
      isValid = false;
    }
  }

  if (form.price === null || form.price === undefined || form.price < 0) {
    clientErrors.price = 'Harga paket tidak boleh negatif.';
    isValid = false;
  }

  return isValid;
};

const handleSubmit = () => {
  if (props.isSubmitting) return;
  if (!validateClient()) return;

  const payload = {
    name: form.name.trim(),
    price: Number(form.price) || 0,
    quantity: form.quantity !== null && form.quantity !== undefined ? Number(form.quantity) : 0,
    description: form.description && form.description.trim() ? form.description.trim() : null,
    items: form.items.map((item) => ({
      medicine_id: item.medicine_id,
      quantity: Number(item.quantity) || 1,
      unit_price: Number(item.unit_price) || 0,
      subtotal: Number(item.subtotal) || 0
    }))
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="currentPackage() ? 'Edit Paket Obat' : 'Tambah Paket Obat'"
    :submit-text="currentPackage() ? 'Perbarui' : 'Simpan'"
    :is-submitting="isSubmitting"
    size="xl"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form-container">
      <!-- General Error Alert -->
      <div v-if="errors?.general" class="alert-error" role="alert">
        {{ errors.general }}
      </div>

      <div class="form-grid">
        <!-- 1. Nama Paket -->
        <div class="form-group form-group--full">
          <label for="name" class="form-label required">Nama Paket</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Masukkan nama paket obat"
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('name')"
            required
            autofocus
          />
          <div v-if="fieldError('name')" class="invalid-feedback">
            {{ fieldError('name') }}
          </div>
        </div>

        <!-- 2. Pencarian Obat -->
        <div class="form-group form-group--full search-section">
          <label for="medicine_search" class="form-label">Pencarian Obat</label>
          <div class="search-input-wrapper">
            <svg
              class="search-icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              id="medicine_search"
              v-model="searchQuery"
              type="text"
              class="form-control search-input"
              :class="{ 'is-invalid': fieldError('search') }"
              placeholder="Ketik minimal 2 huruf untuk mencari obat..."
              autocomplete="off"
              :disabled="isSubmitting"
              @input="onSearchInput"
            />
            <span v-if="isSearching" class="search-spinner" aria-hidden="true"></span>
          </div>

          <div v-if="fieldError('search')" class="invalid-feedback">
            {{ fieldError('search') }}
          </div>
          <div v-if="searchErrorMessage" class="invalid-feedback">
            {{ searchErrorMessage }}
          </div>

          <!-- Autocomplete Dropdown List -->
          <div v-if="searchResults.length > 0" class="autocomplete-dropdown" role="listbox">
            <button
              v-for="med in searchResults"
              :key="med.id"
              type="button"
              class="autocomplete-item"
              @click="addMedicine(med)"
            >
              <div class="autocomplete-item-content">
                <div class="autocomplete-title">{{ med.name }}</div>
                <div class="autocomplete-subtitle">
                  <span v-if="med.code" class="code-badge">{{ med.code }}</span>
                  <span v-if="med.unit?.name">{{ med.unit.name }}</span>
                  <span v-if="med.category?.name">• {{ med.category.name }}</span>
                </div>
              </div>
              <div class="autocomplete-price">
                <span v-if="med.selling_price_per_unit !== null && med.selling_price_per_unit !== undefined" class="price-tag">
                  {{ formatRupiah(med.selling_price_per_unit) }}
                </span>
                <span v-else class="no-price-tag">
                  Harga belum tersedia
                </span>
              </div>
            </button>
          </div>
          <div
            v-else-if="!isSearching && searchQuery.trim().length >= 2 && searchResults.length === 0 && !searchErrorMessage"
            class="autocomplete-dropdown empty-dropdown"
          >
            <span class="text-muted">Obat tidak ditemukan.</span>
          </div>
        </div>

        <!-- 3. Daftar Obat Terpilih -->
        <div class="form-group form-group--full">
          <div class="items-header">
            <span class="items-title required">Daftar Obat Terpilih</span>
            <span class="items-count">{{ form.items.length }} obat</span>
          </div>

          <div v-if="fieldError('items')" class="invalid-feedback mb-2">
            {{ fieldError('items') }}
          </div>

          <div class="items-table-wrapper">
            <table class="items-table">
              <thead>
                <tr>
                  <th class="col-item-name">Nama Obat</th>
                  <th class="col-item-qty text-center">Jumlah</th>
                  <th class="col-item-price text-right">Harga Satuan</th>
                  <th class="col-item-subtotal text-right">Subtotal</th>
                  <th class="col-item-action text-center">Hapus</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="form.items.length === 0">
                  <td colspan="5" class="empty-items-cell">
                    Belum ada obat yang dipilih. Gunakan pencarian obat di atas untuk menambahkan item.
                  </td>
                </tr>
                <tr v-for="(item, index) in form.items" :key="item.medicine_id">
                  <!-- 1. Nama Obat -->
                  <td class="col-item-name font-medium text-navy">
                    <div>{{ item.medicine_name }}</div>
                    <div v-if="item.medicine_unit" class="text-muted text-xs">
                      Satuan: {{ item.medicine_unit }}
                    </div>
                  </td>

                  <!-- 2. Jumlah -->
                  <td class="col-item-qty text-center">
                    <input
                      v-model.number="item.quantity"
                      type="number"
                      min="1"
                      step="1"
                      class="form-control form-control-sm text-center"
                      :disabled="isSubmitting"
                      @input="updateItemSubtotal(item)"
                      required
                    />
                    <div v-if="fieldError(`items.${index}.quantity`)" class="invalid-feedback text-xs">
                      {{ fieldError(`items.${index}.quantity`) }}
                    </div>
                  </td>

                  <!-- 3. Harga Satuan -->
                  <td class="col-item-price text-right">
                    <div class="price-cell-wrapper">
                      <input
                        v-model.number="item.unit_price"
                        type="number"
                        min="0"
                        step="any"
                        class="form-control form-control-sm text-right"
                        :class="{ 'is-invalid': fieldError(`items.${index}.unit_price`) || item.unit_price === null }"
                        :disabled="isSubmitting"
                        placeholder="0"
                        @input="updateItemSubtotal(item)"
                        required
                      />
                      <div v-if="item.unit_price !== null && item.unit_price !== undefined" class="formatted-hint text-xs text-muted mt-1">
                        {{ formatRupiah(item.unit_price) }}
                      </div>
                      <div v-else class="text-xs text-warning-badge mt-1">
                        Harga belum tersedia
                      </div>
                    </div>
                    <div v-if="fieldError(`items.${index}.unit_price`)" class="invalid-feedback text-xs">
                      {{ fieldError(`items.${index}.unit_price`) }}
                    </div>
                  </td>

                  <!-- 4. Subtotal -->
                  <td class="col-item-subtotal text-right font-medium text-navy">
                    <div>{{ formatRupiah(item.subtotal) }}</div>
                    <div v-if="item.quantity > 1 && item.unit_price" class="text-xs text-muted">
                      {{ item.quantity }} × {{ formatRupiah(item.unit_price) }}
                    </div>
                  </td>

                  <!-- 5. Hapus -->
                  <td class="col-item-action text-center">
                    <button
                      type="button"
                      class="btn-remove-item"
                      title="Hapus obat dari paket"
                      aria-label="Hapus obat"
                      :disabled="isSubmitting"
                      @click="removeItem(index)"
                    >
                      <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      >
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

        <!-- 4 & 5. Ringkasan & Total -->
        <div class="form-row-split">
          <!-- 6. Deskripsi -->
          <div class="form-group flex-1">
            <label for="description" class="form-label">Deskripsi</label>
            <textarea
              id="description"
              v-model="form.description"
              rows="4"
              class="form-control"
              placeholder="Masukkan deskripsi paket obat"
              :disabled="isSubmitting"
            ></textarea>
          </div>

          <!-- 4. Harga Paket & 5. Jumlah -->
          <div class="form-group flex-1 summary-card">
            <div class="summary-field">
              <label for="price" class="form-label required">Harga Paket</label>
              <input
                id="price"
                v-model.number="form.price"
                type="number"
                min="0"
                step="any"
                class="form-control text-right font-medium"
                :class="{ 'is-invalid': fieldError('price') }"
                placeholder="0"
                :disabled="isSubmitting"
                required
              />
              <div class="helper-hint">
                Total Komposisi Obat: <strong>{{ formatRupiah(calculatedTotal) }}</strong>
              </div>
              <div v-if="fieldError('price')" class="invalid-feedback">
                {{ fieldError('price') }}
              </div>
            </div>

            <div class="summary-field mt-3">
              <label for="quantity" class="form-label">Jumlah</label>
              <input
                id="quantity"
                v-model.number="form.quantity"
                type="number"
                min="0"
                step="1"
                class="form-control text-right"
                placeholder="Masukkan jumlah paket"
                :disabled="isSubmitting"
              />
              <div class="helper-hint">
                Kapasitas / kuantitas default paket obat.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.modal-form-container {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.alert-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

.form-grid {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-group--full {
  width: 100%;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #0f172a);
}

.form-label.required::after,
.items-title.required::after {
  content: ' *';
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  font-size: 0.875rem;
  color: var(--color-text-navy, #0f172a);
  background-color: #ffffff;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.form-control-sm {
  padding: 0.375rem 0.5rem;
  font-size: 0.85rem;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary, #0f766e);
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
}

.form-control.is-invalid {
  border-color: #ef4444;
}

.form-control.is-invalid:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.form-control:disabled {
  background-color: #f1f5f9;
  cursor: not-allowed;
  opacity: 0.7;
}

.invalid-feedback {
  font-size: 0.8rem;
  color: #ef4444;
  margin-top: 0.25rem;
}

/* Search section */
.search-section {
  position: relative;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  width: 16px;
  height: 16px;
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  padding-left: 2.25rem;
  padding-right: 2rem;
}

.search-spinner {
  position: absolute;
  right: 0.75rem;
  width: 14px;
  height: 14px;
  border: 2px solid #cbd5e1;
  border-top-color: var(--color-primary, #0f766e);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.autocomplete-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 50;
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  margin-top: 4px;
  max-height: 240px;
  overflow-y: auto;
}

.empty-dropdown {
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
}

.autocomplete-item {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.625rem 1rem;
  border: none;
  background: transparent;
  text-align: left;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.15s;
  gap: 0.75rem;
}

.autocomplete-item-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1;
}

.autocomplete-price {
  flex-shrink: 0;
}

.price-tag {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-primary, #0f766e);
  background: #f0fdfa;
  border: 1px solid #ccfbf1;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  white-space: nowrap;
}

.no-price-tag {
  font-size: 0.75rem;
  color: #b45309;
  background: #fef3c7;
  border: 1px solid #fde68a;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  white-space: nowrap;
}

.price-cell-wrapper {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.text-warning-badge {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 500;
  color: #b45309;
  background: #fef3c7;
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
}

.autocomplete-item:last-child {
  border-bottom: none;
}

.autocomplete-item:hover {
  background-color: var(--color-bg-hover, #f8fafc);
}

.autocomplete-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #0f172a);
}

.autocomplete-subtitle {
  font-size: 0.775rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  margin-top: 2px;
}

.code-badge {
  background: #f1f5f9;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
  font-family: monospace;
}

/* Items Table */
.items-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.items-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-navy, #0f172a);
}

.items-count {
  font-size: 0.75rem;
  color: #64748b;
  background: #f1f5f9;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
}

.items-table-wrapper {
  width: 100%;
  overflow-x: auto;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  background: #ffffff;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.items-table th {
  background: #f8fafc;
  padding: 0.625rem 0.875rem;
  font-weight: 600;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.items-table td {
  padding: 0.625rem 0.875rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.items-table tbody tr:last-child td {
  border-bottom: none;
}

.col-item-name {
  min-width: 200px;
}

.col-item-qty {
  width: 100px;
}

.col-item-price {
  width: 150px;
}

.col-item-subtotal {
  width: 150px;
}

.col-item-action {
  width: 60px;
}

.empty-items-cell {
  text-align: center;
  padding: 2rem 1rem !important;
  color: #94a3b8;
  font-style: italic;
}

.btn-remove-item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  background: transparent;
  border: none;
  border-radius: 4px;
  color: #ef4444;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-remove-item:hover:not(:disabled) {
  background: #fef2f2;
}

.btn-remove-item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-remove-item svg {
  width: 15px;
  height: 15px;
}

/* Split row */
.form-row-split {
  display: flex;
  gap: 1.25rem;
  flex-wrap: wrap;
}

.flex-1 {
  flex: 1;
  min-width: 280px;
}

.summary-card {
  background: #f8fafc;
  padding: 1rem 1.25rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.summary-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.helper-hint {
  font-size: 0.775rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.text-center { text-align: center; }
.text-right { text-align: right; }
.text-navy { color: var(--color-text-navy, #0f172a); }
.text-muted { color: #64748b; }
.font-medium { font-weight: 500; }
.text-xs { font-size: 0.75rem; }
.mt-3 { margin-top: 0.75rem; }
.mb-2 { margin-bottom: 0.5rem; }

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
