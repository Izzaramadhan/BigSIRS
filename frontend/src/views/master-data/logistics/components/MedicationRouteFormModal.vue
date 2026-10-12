<script setup>
import { reactive, watch } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  route: {
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

const currentRoute = () => props.route || props.item || null;

const form = reactive({
  name: '',
  object_code: ''
});

const clientErrors = reactive({});

const resetForm = () => {
  form.name = '';
  form.object_code = '';
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

const populateForm = (data) => {
  form.name = data.name || '';
  form.object_code = data.object_code && data.object_code !== '-' && data.object_code !== '\\N' ? String(data.object_code) : '';
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      const r = currentRoute();
      if (r) {
        populateForm(r);
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
  () => [props.route, props.item],
  () => {
    if (props.isOpen) {
      const r = currentRoute();
      if (r) {
        populateForm(r);
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
    clientErrors.name = 'Nama jalur masuk obat wajib diisi.';
    isValid = false;
  }

  return isValid;
};

const handleSubmit = () => {
  if (props.isSubmitting) return;
  if (!validateClient()) return;

  const payload = {
    name: form.name.trim(),
    object_code: form.object_code && form.object_code.trim() ? form.object_code.trim() : null
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="currentRoute() ? 'Edit Jalur Masuk Obat' : 'Tambah Jalur Masuk Obat'"
    :submit-text="currentRoute() ? 'Perbarui' : 'Simpan'"
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
        <!-- 1. Nama Jalur Masuk Obat (required) -->
        <div class="form-group form-group--full">
          <label for="name" class="form-label required">Nama Jalur Masuk Obat</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Masukkan nama jalur masuk obat"
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

        <!-- 2. Objek -->
        <div class="form-group form-group--full">
          <label for="object_code" class="form-label">Objek</label>
          <input
            id="object_code"
            v-model="form.object_code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('object_code') }"
            placeholder="Masukkan objek jalur masuk obat"
            maxlength="50"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('object_code')"
          />
          <div v-if="fieldError('object_code')" class="invalid-feedback">
            {{ fieldError('object_code') }}
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

.invalid-feedback {
  font-size: 0.8rem;
  color: #ef4444;
  margin-top: 0.25rem;
}
</style>
