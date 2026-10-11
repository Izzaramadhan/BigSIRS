<script setup>
import { reactive, watch } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  group: {
    type: Object,
    default: null
  },
  categories: {
    type: Array,
    default: () => []
  },
  availableItems: {
    type: Array,
    default: () => []
  },
  isLoadingCategories: {
    type: Boolean,
    default: false
  },
  isLoadingItems: {
    type: Boolean,
    default: false
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

const form = reactive({
  name: '',
  laboratory_category_id: '',
  laboratory_item_id: '',
  price: '',
  harga_jaspel: '',
  is_active: true,
  loinc_code: '',
  loinc_url: '',
  snomed_code: '',
  snomed_url: ''
});

const clientErrors = reactive({});

const isValidUrl = (string) => {
  if (!string) return true;
  try {
    const url = new URL(string);
    return url.protocol === 'http:' || url.protocol === 'https:';
  } catch {
    return false;
  }
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
  if (props.errors[field]) {
    const err = props.errors[field];
    return Array.isArray(err) ? err[0] : err;
  }
  if (field === 'laboratory_item_id' && props.errors.laboratory_item_ids) {
    const err = props.errors.laboratory_item_ids;
    return Array.isArray(err) ? err[0] : err;
  }
  return null;
};

const resetForm = () => {
  form.name = '';
  form.laboratory_category_id = '';
  form.laboratory_item_id = '';
  form.price = '';
  form.harga_jaspel = '';
  form.is_active = true;
  form.loinc_code = '';
  form.loinc_url = '';
  form.snomed_code = '';
  form.snomed_url = '';

  Object.keys(clientErrors).forEach((k) => delete clientErrors[k]);
};

const populateForm = (data) => {
  form.name = data.name || '';
  form.laboratory_category_id = data.laboratory_category_id ? String(data.laboratory_category_id) : '';

  // Resolve item ID from laboratory_item_id or items array
  if (data.laboratory_item_id) {
    form.laboratory_item_id = String(data.laboratory_item_id);
  } else if (Array.isArray(data.items) && data.items.length > 0) {
    form.laboratory_item_id = String(data.items[0].id);
  } else if (Array.isArray(data.laboratory_item_ids) && data.laboratory_item_ids.length > 0) {
    form.laboratory_item_id = String(data.laboratory_item_ids[0]);
  } else {
    form.laboratory_item_id = '';
  }

  form.price = data.price !== null && data.price !== undefined ? data.price : '';
  form.harga_jaspel = data.harga_jaspel !== null && data.harga_jaspel !== undefined ? data.harga_jaspel : '';
  form.is_active = data.is_active !== undefined ? Boolean(data.is_active) : true;
  form.loinc_code = data.loinc_code || '';
  form.loinc_url = data.loinc_url || '';
  form.snomed_code = data.snomed_code || '';
  form.snomed_url = data.snomed_url || '';

  Object.keys(clientErrors).forEach((k) => delete clientErrors[k]);
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      if (props.group) {
        populateForm(props.group);
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
  () => props.group,
  (newGroup) => {
    if (props.isOpen) {
      if (newGroup) {
        populateForm(newGroup);
      } else {
        resetForm();
      }
    }
  }
);

const handleCategoryChange = () => {
  clearFieldError('laboratory_category_id');
  // When category changes, keep item if valid or reset if no category selected
  if (!form.laboratory_category_id) {
    form.laboratory_item_id = '';
  }
};

const handleSubmit = () => {
  Object.keys(clientErrors).forEach((k) => delete clientErrors[k]);

  let hasError = false;

  // Validate Name
  if (!form.name || !form.name.trim()) {
    clientErrors.name = 'Nama grup lab wajib diisi.';
    hasError = true;
  }

  // Validate Category
  if (!form.laboratory_category_id) {
    clientErrors.laboratory_category_id = 'Kategori lab wajib dipilih.';
    hasError = true;
  }

  // Validate Item
  if (!form.laboratory_item_id) {
    clientErrors.laboratory_item_id = 'Item lab wajib dipilih.';
    hasError = true;
  }

  // Validate LOINC URL
  if (form.loinc_url && form.loinc_url.trim() && !isValidUrl(form.loinc_url.trim())) {
    clientErrors.loinc_url = 'Format URL LOINC tidak valid. Contoh: https://loinc.org/...';
    hasError = true;
  }

  // Validate SNOMED URL
  if (form.snomed_url && form.snomed_url.trim() && !isValidUrl(form.snomed_url.trim())) {
    clientErrors.snomed_url = 'Format URL SNOMED tidak valid. Contoh: https://...';
    hasError = true;
  }

  // Validate Non-negative Price
  if (form.price !== '' && form.price !== null && Number(form.price) < 0) {
    clientErrors.price = 'Harga tidak boleh bernilai negatif.';
    hasError = true;
  }

  // Validate Non-negative Harga Jaspel
  if (form.harga_jaspel !== '' && form.harga_jaspel !== null && Number(form.harga_jaspel) < 0) {
    clientErrors.harga_jaspel = 'Harga jaspel tidak boleh bernilai negatif.';
    hasError = true;
  }

  if (hasError) return;

  const itemId = Number(form.laboratory_item_id);

  const payload = {
    name: form.name.trim(),
    laboratory_category_id: Number(form.laboratory_category_id),
    laboratory_item_id: itemId,
    laboratory_item_ids: [itemId],
    price: form.price !== '' && form.price !== null ? Number(form.price) : null,
    harga_jaspel: form.harga_jaspel !== '' && form.harga_jaspel !== null ? Number(form.harga_jaspel) : null,
    is_active: Boolean(form.is_active),
    loinc_code: form.loinc_code ? form.loinc_code.trim() : null,
    loinc_url: form.loinc_url ? form.loinc_url.trim() : null,
    snomed_code: form.snomed_code ? form.snomed_code.trim() : null,
    snomed_url: form.snomed_url ? form.snomed_url.trim() : null
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="group ? 'Edit Grup Lab' : 'Tambah Grup Lab'"
    :submit-text="group ? 'Simpan Perubahan' : 'Simpan'"
    :is-submitting="isSubmitting"
    size="lg"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form-container">
      <!-- General Error Alert -->
      <div v-if="errors.general" class="alert-error">
        {{ errors.general }}
      </div>

      <div class="form-grid">
        <!-- 1. Nama Grup Lab (Baris 1: Full width) -->
        <div class="form-group form-group--full">
          <label for="name" class="form-label required">Nama Grup Lab</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Masukkan nama grup lab"
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('name')"
            required
          />
          <div v-if="fieldError('name')" class="invalid-feedback">
            {{ fieldError('name') }}
          </div>
        </div>

        <!-- 2. Kategori Lab (Baris 2, Kolom 1) -->
        <div class="form-group">
          <label for="laboratory_category_id" class="form-label required">Kategori Lab</label>
          <select
            id="laboratory_category_id"
            v-model="form.laboratory_category_id"
            class="form-control form-select"
            :class="{ 'is-invalid': fieldError('laboratory_category_id') }"
            :disabled="isSubmitting || isLoadingCategories"
            @change="handleCategoryChange"
            required
          >
            <option value="" disabled>-- Pilih kategori lab --</option>
            <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
              {{ cat.name }}
            </option>
          </select>
          <div v-if="isLoadingCategories" class="helper-text helper-loading">
            Memuat daftar kategori...
          </div>
          <div v-else-if="categories.length === 0" class="helper-text helper-empty">
            Belum ada kategori lab tersedia.
          </div>
          <div v-if="fieldError('laboratory_category_id')" class="invalid-feedback">
            {{ fieldError('laboratory_category_id') }}
          </div>
        </div>

        <!-- 3. Item Lab (Baris 2, Kolom 2) -->
        <div class="form-group">
          <label for="laboratory_item_id" class="form-label required">Item Lab</label>
          <select
            id="laboratory_item_id"
            v-model="form.laboratory_item_id"
            class="form-control form-select"
            :class="{ 'is-invalid': fieldError('laboratory_item_id') }"
            :disabled="isSubmitting || isLoadingItems || !form.laboratory_category_id"
            @change="clearFieldError('laboratory_item_id')"
            required
          >
            <option value="" disabled>
              {{ !form.laboratory_category_id ? 'Pilih kategori lab terlebih dahulu' : '-- Pilih item lab --' }}
            </option>
            <option v-for="item in availableItems" :key="item.id" :value="String(item.id)">
              {{ item.name }}{{ item.unit ? ` (${item.unit})` : '' }}
            </option>
          </select>
          <div v-if="isLoadingItems" class="helper-text helper-loading">
            Memuat daftar item lab...
          </div>
          <div v-else-if="!form.laboratory_category_id" class="helper-text">
            Pilih kategori lab untuk mengaktifkan pilihan item.
          </div>
          <div v-if="fieldError('laboratory_item_id')" class="invalid-feedback">
            {{ fieldError('laboratory_item_id') }}
          </div>
        </div>

        <!-- 4. Harga (Baris 3, Kolom 1) -->
        <div class="form-group">
          <label for="price" class="form-label">Harga</label>
          <div class="input-prefix-wrapper">
            <span class="prefix">Rp</span>
            <input
              id="price"
              v-model="form.price"
              type="number"
              min="0"
              step="any"
              class="form-control input-with-prefix font-mono"
              :class="{ 'is-invalid': fieldError('price') }"
              placeholder="Masukkan harga"
              :disabled="isSubmitting"
              @input="clearFieldError('price')"
            />
          </div>
          <div v-if="fieldError('price')" class="invalid-feedback">
            {{ fieldError('price') }}
          </div>
        </div>

        <!-- 5. Harga Jaspel (Baris 3, Kolom 2) -->
        <div class="form-group">
          <label for="harga_jaspel" class="form-label">Harga Jaspel</label>
          <div class="input-prefix-wrapper">
            <span class="prefix">Rp</span>
            <input
              id="harga_jaspel"
              v-model="form.harga_jaspel"
              type="number"
              min="0"
              step="any"
              class="form-control input-with-prefix font-mono"
              :class="{ 'is-invalid': fieldError('harga_jaspel') }"
              placeholder="Masukkan harga jaspel"
              :disabled="isSubmitting"
              @input="clearFieldError('harga_jaspel')"
            />
          </div>
          <div v-if="fieldError('harga_jaspel')" class="invalid-feedback">
            {{ fieldError('harga_jaspel') }}
          </div>
        </div>

        <!-- 6. Status (Baris 4: Full width) -->
        <div class="form-group form-group--full">
          <label class="form-label required">Status</label>
          <div class="status-radio-group">
            <label class="status-radio-item" :class="{ 'is-selected': form.is_active === true }">
              <input
                id="status_active"
                type="radio"
                v-model="form.is_active"
                :value="true"
                name="laboratory_group_status"
                :disabled="isSubmitting"
                @change="clearFieldError('is_active')"
              />
              <span class="status-radio-label">Aktif</span>
            </label>
            <label class="status-radio-item" :class="{ 'is-selected': form.is_active === false }">
              <input
                id="status_inactive"
                type="radio"
                v-model="form.is_active"
                :value="false"
                name="laboratory_group_status"
                :disabled="isSubmitting"
                @change="clearFieldError('is_active')"
              />
              <span class="status-radio-label">Nonaktif</span>
            </label>
          </div>
          <div v-if="fieldError('is_active')" class="invalid-feedback">
            {{ fieldError('is_active') }}
          </div>
        </div>

        <!-- 7. LOINC Code (Baris 5, Kolom 1) -->
        <div class="form-group">
          <label for="loinc_code" class="form-label">LOINC Code</label>
          <input
            id="loinc_code"
            v-model="form.loinc_code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('loinc_code') }"
            placeholder="Masukkan kode LOINC"
            maxlength="100"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('loinc_code')"
          />
          <div v-if="fieldError('loinc_code')" class="invalid-feedback">
            {{ fieldError('loinc_code') }}
          </div>
        </div>

        <!-- 8. LOINC URL (Baris 5, Kolom 2) -->
        <div class="form-group">
          <label for="loinc_url" class="form-label">LOINC URL</label>
          <input
            id="loinc_url"
            v-model="form.loinc_url"
            type="url"
            class="form-control"
            :class="{ 'is-invalid': fieldError('loinc_url') }"
            placeholder="https://loinc.org/..."
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('loinc_url')"
          />
          <div v-if="fieldError('loinc_url')" class="invalid-feedback">
            {{ fieldError('loinc_url') }}
          </div>
        </div>

        <!-- 9. SNOMED Code (Baris 6, Kolom 1) -->
        <div class="form-group">
          <label for="snomed_code" class="form-label">SNOMED Code</label>
          <input
            id="snomed_code"
            v-model="form.snomed_code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('snomed_code') }"
            placeholder="Masukkan kode SNOMED"
            maxlength="100"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('snomed_code')"
          />
          <div v-if="fieldError('snomed_code')" class="invalid-feedback">
            {{ fieldError('snomed_code') }}
          </div>
        </div>

        <!-- 10. SNOMED URL (Baris 6, Kolom 2) -->
        <div class="form-group">
          <label for="snomed_url" class="form-label">SNOMED URL</label>
          <input
            id="snomed_url"
            v-model="form.snomed_url"
            type="url"
            class="form-control"
            :class="{ 'is-invalid': fieldError('snomed_url') }"
            placeholder="https://..."
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('snomed_url')"
          />
          <div v-if="fieldError('snomed_url')" class="invalid-feedback">
            {{ fieldError('snomed_url') }}
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
  padding: 0.25rem 0;
}

.alert-error {
  padding: 0.75rem 1rem;
  background-color: var(--color-danger-bg, #fef2f2);
  border: 1px solid var(--color-danger-border, #fecaca);
  border-radius: 6px;
  color: var(--color-danger, #ef4444);
  font-size: 0.875rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-group--full {
  grid-column: 1 / -1;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #0f172a);
}

.form-label.required::after {
  content: ' *';
  color: var(--color-danger, #ef4444);
}

.form-control {
  width: 100%;
  padding: 0.55rem 0.75rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  font-size: 0.875rem;
  color: var(--color-text-navy, #0f172a);
  background-color: #ffffff;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  outline: none;
}

.form-control:focus {
  border-color: var(--color-primary, #0f766e);
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
}

.form-control:disabled {
  background-color: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
  border-color: #e2e8f0;
}

.form-control.is-invalid {
  border-color: var(--color-danger, #ef4444);
}

.form-control.is-invalid:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.form-select {
  padding-right: 2rem;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
}

.input-prefix-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.prefix {
  position: absolute;
  left: 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  pointer-events: none;
  z-index: 1;
}

.input-with-prefix {
  padding-left: 2.25rem;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.status-radio-group {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-top: 0.125rem;
}

.status-radio-item {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 1rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  background-color: #ffffff;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  user-select: none;
}

.status-radio-item input[type='radio'] {
  margin: 0;
  cursor: pointer;
  accent-color: var(--color-primary, #0f766e);
}

.status-radio-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #0f172a);
}

.status-radio-item.is-selected {
  border-color: var(--color-primary, #0f766e);
  background-color: rgba(15, 118, 110, 0.05);
}

.status-radio-item.is-selected .status-radio-label {
  color: var(--color-primary, #0f766e);
}

.helper-text {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.125rem;
}

.helper-loading {
  color: var(--color-primary, #0f766e);
}

.helper-empty {
  color: #b45309;
}

.invalid-feedback {
  font-size: 0.75rem;
  color: var(--color-danger, #ef4444);
  margin-top: 0.125rem;
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}
</style>
