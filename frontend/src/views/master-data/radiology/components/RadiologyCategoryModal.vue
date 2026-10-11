<script setup>
import { reactive, watch } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  category: {
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

const form = reactive({
  name: '',
  description: '',
  is_active: true,
  loinc_code: '',
  loinc_url: '',
  snomed_code: '',
  snomed_url: ''
});

const clientErrors = reactive({});

const resetForm = () => {
  form.name = '';
  form.description = '';
  form.is_active = true;
  form.loinc_code = '';
  form.loinc_url = '';
  form.snomed_code = '';
  form.snomed_url = '';
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

const populateForm = (data) => {
  form.name = data.name || '';
  form.description = data.description || '';
  form.is_active = data.is_active !== undefined ? Boolean(data.is_active) : true;
  form.loinc_code = data.loinc_code || '';
  form.loinc_url = data.loinc_url || '';
  form.snomed_code = data.snomed_code || '';
  form.snomed_url = data.snomed_url || '';
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      if (props.category) {
        populateForm(props.category);
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
  () => props.category,
  (newCategory) => {
    if (props.isOpen && newCategory) {
      populateForm(newCategory);
    }
  }
);

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

const isValidHttpUrl = (string) => {
  if (!string || typeof string !== 'string') return false;
  try {
    const url = new URL(string.trim());
    return url.protocol === 'http:' || url.protocol === 'https:';
  } catch {
    return false;
  }
};

const validateClient = () => {
  Object.keys(clientErrors).forEach((k) => delete clientErrors[k]);
  let isValid = true;

  if (!form.name || !form.name.trim()) {
    clientErrors.name = 'Kategori radiologi wajib diisi.';
    isValid = false;
  }

  if (form.loinc_url && form.loinc_url.trim()) {
    if (!isValidHttpUrl(form.loinc_url)) {
      clientErrors.loinc_url = 'Format URL LOINC tidak valid (harus dimulai dengan http:// atau https://).';
      isValid = false;
    }
  }

  if (form.snomed_url && form.snomed_url.trim()) {
    if (!isValidHttpUrl(form.snomed_url)) {
      clientErrors.snomed_url = 'Format URL SNOMED tidak valid (harus dimulai dengan http:// atau https://).';
      isValid = false;
    }
  }

  return isValid;
};

const handleSubmit = () => {
  if (!validateClient()) return;

  const payload = {
    name: form.name.trim(),
    description: form.description && form.description.trim() ? form.description.trim() : null,
    is_active: Boolean(form.is_active),
    loinc_code: form.loinc_code && form.loinc_code.trim() ? form.loinc_code.trim() : null,
    loinc_url: form.loinc_url && form.loinc_url.trim() ? form.loinc_url.trim() : null,
    snomed_code: form.snomed_code && form.snomed_code.trim() ? form.snomed_code.trim() : null,
    snomed_url: form.snomed_url && form.snomed_url.trim() ? form.snomed_url.trim() : null
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="category ? 'Edit Kategori Radiologi' : 'Tambah Kategori Radiologi'"
    :submit-text="category ? 'Simpan Perubahan' : 'Simpan'"
    :is-submitting="isSubmitting"
    size="lg"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form-container">
      <!-- General Error Alert -->
      <div v-if="errors?.general" class="alert-error" role="alert">
        {{ errors.general }}
      </div>

      <div class="form-grid">
        <!-- 1. Kategori (name) - Full width -->
        <div class="form-group form-group--full">
          <label for="name" class="form-label required">Kategori</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Contoh: Radiografi Konvensional"
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

        <!-- 2. Deskripsi - Full width -->
        <div class="form-group form-group--full">
          <label for="description" class="form-label">Deskripsi</label>
          <textarea
            id="description"
            v-model="form.description"
            class="form-control form-textarea"
            :class="{ 'is-invalid': fieldError('description') }"
            placeholder="Deskripsi kategori radiologi (opsional)"
            rows="3"
            maxlength="1000"
            :disabled="isSubmitting"
            @input="clearFieldError('description')"
          ></textarea>
          <div v-if="fieldError('description')" class="invalid-feedback">
            {{ fieldError('description') }}
          </div>
        </div>

        <!-- 3. Status - Full width -->
        <div class="form-group form-group--full">
          <label class="form-label required">Status</label>
          <div class="status-radio-group">
            <label class="status-radio-item" :class="{ 'is-selected': form.is_active === true }">
              <input
                id="status_active"
                type="radio"
                v-model="form.is_active"
                :value="true"
                name="radiology_category_status"
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
                name="radiology_category_status"
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

        <!-- 4. Loinc Code -->
        <div class="form-group">
          <label for="loinc_code" class="form-label">Loinc Code</label>
          <input
            id="loinc_code"
            v-model="form.loinc_code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('loinc_code') }"
            placeholder="Kode LOINC (opsional)"
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('loinc_code')"
          />
          <div v-if="fieldError('loinc_code')" class="invalid-feedback">
            {{ fieldError('loinc_code') }}
          </div>
        </div>

        <!-- 5. Loinc Url -->
        <div class="form-group">
          <label for="loinc_url" class="form-label">Loinc Url</label>
          <input
            id="loinc_url"
            v-model="form.loinc_url"
            type="url"
            class="form-control"
            :class="{ 'is-invalid': fieldError('loinc_url') }"
            placeholder="https://loinc.org/... (opsional)"
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('loinc_url')"
          />
          <div v-if="fieldError('loinc_url')" class="invalid-feedback">
            {{ fieldError('loinc_url') }}
          </div>
        </div>

        <!-- 6. Snomed Code -->
        <div class="form-group">
          <label for="snomed_code" class="form-label">Snomed Code</label>
          <input
            id="snomed_code"
            v-model="form.snomed_code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('snomed_code') }"
            placeholder="Kode SNOMED (opsional)"
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('snomed_code')"
          />
          <div v-if="fieldError('snomed_code')" class="invalid-feedback">
            {{ fieldError('snomed_code') }}
          </div>
        </div>

        <!-- 7. Snomed Url -->
        <div class="form-group">
          <label for="snomed_url" class="form-label">Snomed Url</label>
          <input
            id="snomed_url"
            v-model="form.snomed_url"
            type="url"
            class="form-control"
            :class="{ 'is-invalid': fieldError('snomed_url') }"
            placeholder="https://snomed.info/... (opsional)"
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
}

.alert-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  margin-bottom: 1.25rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group--full {
  grid-column: 1 / -1;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #0f172a);
  margin-bottom: 0.375rem;
}

.form-label.required::after {
  content: " *";
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: var(--color-text-navy, #0f172a);
  background-color: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
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
  background-color: #f8fafc;
  cursor: not-allowed;
  opacity: 0.75;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.invalid-feedback {
  display: block;
  font-size: 0.8125rem;
  color: #ef4444;
  margin-top: 0.375rem;
}

.status-radio-group {
  display: flex;
  gap: 1rem;
  margin-top: 0.25rem;
}

.status-radio-item {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  cursor: pointer;
  background-color: #ffffff;
  transition: all 0.2s;
  user-select: none;
}

.status-radio-item:hover {
  border-color: var(--color-primary, #0f766e);
  background-color: #f0fdfa;
}

.status-radio-item.is-selected {
  border-color: var(--color-primary, #0f766e);
  background-color: #f0fdfa;
}

.status-radio-item input[type="radio"] {
  cursor: pointer;
  accent-color: var(--color-primary, #0f766e);
}

.status-radio-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #0f172a);
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
