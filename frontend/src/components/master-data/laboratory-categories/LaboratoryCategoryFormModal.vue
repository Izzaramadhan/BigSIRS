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
  type: '',
  loinc_code: '',
  loinc_url: '',
  snomed_code: '',
  snomed_url: '',
  is_active: true
});

const resetForm = () => {
  form.name = '';
  form.description = '';
  form.type = '';
  form.loinc_code = '';
  form.loinc_url = '';
  form.snomed_code = '';
  form.snomed_url = '';
  form.is_active = true;
};

const populateForm = (data) => {
  form.name = data.name || '';
  form.description = data.description || '';
  form.type = data.type || '';
  form.loinc_code = data.loinc_code || '';
  form.loinc_url = data.loinc_url || '';
  form.snomed_code = data.snomed_code || '';
  form.snomed_url = data.snomed_url || '';
  form.is_active = data.is_active !== undefined ? Boolean(data.is_active) : true;
};

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
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
  (cat) => {
    if (props.isOpen && cat) {
      populateForm(cat);
    }
  }
);

const fieldError = (field) => {
  if (!props.errors || !props.errors[field]) return null;
  return Array.isArray(props.errors[field]) ? props.errors[field][0] : props.errors[field];
};

const handleSubmit = () => {
  const payload = {
    name: form.name.trim(),
    description: form.description && form.description.trim() ? form.description.trim() : null,
    type: form.type || null,
    loinc_code: form.loinc_code && form.loinc_code.trim() ? form.loinc_code.trim() : null,
    loinc_url: form.loinc_url && form.loinc_url.trim() ? form.loinc_url.trim() : null,
    snomed_code: form.snomed_code && form.snomed_code.trim() ? form.snomed_code.trim() : null,
    snomed_url: form.snomed_url && form.snomed_url.trim() ? form.snomed_url.trim() : null,
    is_active: form.is_active
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="category ? 'Edit Kategori Lab' : 'Tambah Kategori Lab'"
    :is-submitting="isSubmitting"
    :submit-text="category ? 'Simpan Perubahan' : 'Simpan'"
    size="lg"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
      <div v-if="errors?.general" class="alert-error" role="alert">
        {{ errors.general }}
      </div>

      <div class="form-grid">
        <!-- 1. Kategori (name) -->
        <div class="form-group">
          <label for="name" class="form-label required">Kategori</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Contoh: Hematologi Rutin"
            maxlength="255"
            required
            :disabled="isSubmitting"
          />
          <div v-if="fieldError('name')" class="invalid-feedback">
            {{ fieldError('name') }}
          </div>
        </div>

        <!-- 2. Tipe -->
        <div class="form-group">
          <label for="type" class="form-label">Tipe</label>
          <select
            id="type"
            v-model="form.type"
            class="form-control form-select"
            :class="{ 'is-invalid': fieldError('type') }"
            :disabled="isSubmitting"
          >
            <option value="">-- Pilih Tipe Lab --</option>
            <option value="lab klinik">Lab Klinik</option>
            <option value="lab gigi">Lab Gigi</option>
            <option value="lab mikrobakteri">Lab Mikrobakteri</option>
          </select>
          <div v-if="fieldError('type')" class="invalid-feedback">
            {{ fieldError('type') }}
          </div>
        </div>

        <!-- 3. LOINC Code -->
        <div class="form-group">
          <label for="loinc_code" class="form-label">LOINC Code</label>
          <input
            id="loinc_code"
            v-model="form.loinc_code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('loinc_code') }"
            placeholder="Kode LOINC (opsional)"
            maxlength="255"
            :disabled="isSubmitting"
          />
          <div v-if="fieldError('loinc_code')" class="invalid-feedback">
            {{ fieldError('loinc_code') }}
          </div>
        </div>

        <!-- 4. LOINC URL -->
        <div class="form-group">
          <label for="loinc_url" class="form-label">LOINC URL</label>
          <input
            id="loinc_url"
            v-model="form.loinc_url"
            type="url"
            class="form-control"
            :class="{ 'is-invalid': fieldError('loinc_url') }"
            placeholder="https://loinc.org/... (opsional)"
            maxlength="255"
            :disabled="isSubmitting"
          />
          <div v-if="fieldError('loinc_url')" class="invalid-feedback">
            {{ fieldError('loinc_url') }}
          </div>
        </div>

        <!-- 5. SNOMED Code -->
        <div class="form-group">
          <label for="snomed_code" class="form-label">SNOMED Code</label>
          <input
            id="snomed_code"
            v-model="form.snomed_code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('snomed_code') }"
            placeholder="Kode SNOMED (opsional)"
            maxlength="255"
            :disabled="isSubmitting"
          />
          <div v-if="fieldError('snomed_code')" class="invalid-feedback">
            {{ fieldError('snomed_code') }}
          </div>
        </div>

        <!-- 6. Status -->
        <div class="form-group">
          <span class="form-label">Status</span>
          <div class="toggle-group">
            <label class="toggle-row">
              <input
                id="is_active"
                type="checkbox"
                v-model="form.is_active"
                class="toggle-input"
                :disabled="isSubmitting"
              />
              <span class="toggle-track"></span>
              <span class="toggle-label-text">
                {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </label>
          </div>
        </div>

        <!-- 7. Deskripsi -->
        <div class="form-group form-group--full">
          <label for="description" class="form-label">Deskripsi</label>
          <textarea
            id="description"
            v-model="form.description"
            class="form-control form-textarea"
            :class="{ 'is-invalid': fieldError('description') }"
            placeholder="Deskripsi kategori laboratorium (opsional)"
            rows="3"
            maxlength="1000"
            :disabled="isSubmitting"
          ></textarea>
          <div v-if="fieldError('description')" class="invalid-feedback">
            {{ fieldError('description') }}
          </div>
        </div>
      </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.modal-form {
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
  margin-bottom: 1rem;
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
  margin-bottom: 0.5rem;
  font-weight: 500;
  font-size: 0.9rem;
  color: var(--color-text-navy, #0f172a);
}

.form-label.required::after {
  content: ' *';
  color: var(--color-danger, #ef4444);
}

.form-control {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.95rem;
  color: var(--color-text-navy, #0f172a);
  background-color: #ffffff;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary, #0f766e);
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
}

.form-control.is-invalid {
  border-color: #dc2626;
}

.form-control.is-invalid:focus {
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
}

.form-control:disabled {
  background-color: #f8fafc;
  cursor: not-allowed;
  opacity: 0.75;
}

.form-select {
  cursor: pointer;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.invalid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.35rem;
  font-size: 0.8rem;
  color: #dc2626;
}

/* Toggle styles */
.toggle-group {
  display: flex;
  align-items: center;
  padding: 0.4rem 0;
}

.toggle-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  cursor: pointer;
}

.toggle-input {
  display: none;
}

.toggle-track {
  width: 38px;
  height: 22px;
  background: #cbd5e1;
  border-radius: 999px;
  position: relative;
  transition: background 0.2s;
  flex-shrink: 0;
}

.toggle-track::after {
  content: '';
  position: absolute;
  width: 16px;
  height: 16px;
  background: #ffffff;
  border-radius: 50%;
  top: 3px;
  left: 3px;
  transition: transform 0.2s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.toggle-input:checked + .toggle-track {
  background: var(--color-primary, #0f766e);
}

.toggle-input:checked + .toggle-track::after {
  transform: translateX(16px);
}

.toggle-label-text {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #0f172a);
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .form-group--full {
    grid-column: auto;
  }
}
</style>
