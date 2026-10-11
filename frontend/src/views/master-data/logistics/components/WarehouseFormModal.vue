<script setup>
import { reactive, watch } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  warehouse: {
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

const currentWarehouse = () => props.warehouse || props.item || null;

const form = reactive({
  name: '',
  code: '',
  description: ''
});

const clientErrors = reactive({});

const resetForm = () => {
  form.name = '';
  form.code = '';
  form.description = '';
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

const populateForm = (data) => {
  form.name = data.name || '';
  form.code = data.code && data.code !== '-' ? String(data.code) : '';
  form.description = data.description && data.description !== '-' ? data.description : '';
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      const wh = currentWarehouse();
      if (wh) {
        populateForm(wh);
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
  () => [props.warehouse, props.item],
  () => {
    if (props.isOpen) {
      const wh = currentWarehouse();
      if (wh) {
        populateForm(wh);
      }
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

const validateClient = () => {
  Object.keys(clientErrors).forEach((k) => delete clientErrors[k]);
  let isValid = true;

  if (!form.name || !form.name.trim()) {
    clientErrors.name = 'Nama gudang wajib diisi.';
    isValid = false;
  }

  return isValid;
};

const handleSubmit = () => {
  if (props.isSubmitting) return;
  if (!validateClient()) return;

  const payload = {
    name: form.name.trim(),
    code: form.code && form.code.trim() ? form.code.trim() : null,
    description: form.description && form.description.trim() ? form.description.trim() : null
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="currentWarehouse() ? 'Edit Gudang' : 'Tambah Gudang'"
    :submit-text="currentWarehouse() ? 'Perbarui' : 'Simpan'"
    :is-submitting="isSubmitting"
    size="md"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form-container">
      <!-- General Error Alert -->
      <div v-if="errors?.general" class="alert-error" role="alert">
        {{ errors.general }}
      </div>

      <div class="form-grid">
        <!-- 1. Nama (required) -->
        <div class="form-group form-group--full">
          <label for="name" class="form-label required">Nama</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Masukkan nama gudang"
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

        <!-- 2. Kode -->
        <div class="form-group form-group--full">
          <label for="code" class="form-label">Kode</label>
          <input
            id="code"
            v-model="form.code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('code') }"
            placeholder="Masukkan kode gudang"
            maxlength="50"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('code')"
          />
          <div v-if="fieldError('code')" class="invalid-feedback">
            {{ fieldError('code') }}
          </div>
        </div>

        <!-- 3. Deskripsi -->
        <div class="form-group form-group--full">
          <label for="description" class="form-label">Deskripsi</label>
          <textarea
            id="description"
            v-model="form.description"
            class="form-control form-textarea"
            :class="{ 'is-invalid': fieldError('description') }"
            placeholder="Masukkan deskripsi gudang"
            rows="3"
            :disabled="isSubmitting"
            @input="clearFieldError('description')"
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

.form-label.required::after {
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

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.invalid-feedback {
  font-size: 0.8rem;
  color: #ef4444;
  margin-top: 0.25rem;
}
</style>
