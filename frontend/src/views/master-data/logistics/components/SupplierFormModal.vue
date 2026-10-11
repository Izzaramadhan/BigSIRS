<script setup>
import { reactive, watch } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  supplier: {
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

const currentSupplier = () => props.supplier || props.item || null;

const form = reactive({
  name: '',
  phone: '',
  address: ''
});

const clientErrors = reactive({});

const resetForm = () => {
  form.name = '';
  form.phone = '';
  form.address = '';
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

const populateForm = (data) => {
  form.name = data.name || '';
  form.phone = data.phone || '';
  form.address = data.address || '';
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      const sup = currentSupplier();
      if (sup) {
        populateForm(sup);
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
  () => [props.supplier, props.item],
  () => {
    if (props.isOpen) {
      const sup = currentSupplier();
      if (sup) {
        populateForm(sup);
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
    clientErrors.name = 'Nama supplier wajib diisi.';
    isValid = false;
  }

  return isValid;
};

const handleSubmit = () => {
  if (props.isSubmitting) return;
  if (!validateClient()) return;

  const payload = {
    name: form.name.trim(),
    phone: form.phone && form.phone.trim() ? form.phone.trim() : null,
    address: form.address && form.address.trim() ? form.address.trim() : null
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="currentSupplier() ? 'Edit Supplier' : 'Tambah Supplier'"
    :submit-text="currentSupplier() ? 'Perbarui' : 'Simpan'"
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
            placeholder="Masukkan nama supplier"
            maxlength="150"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('name')"
            required
          />
          <div v-if="fieldError('name')" class="invalid-feedback">
            {{ fieldError('name') }}
          </div>
        </div>

        <!-- 2. Telp -->
        <div class="form-group form-group--full">
          <label for="phone" class="form-label">Telp</label>
          <input
            id="phone"
            v-model="form.phone"
            type="tel"
            class="form-control"
            :class="{ 'is-invalid': fieldError('phone') }"
            placeholder="Masukkan nomor telepon supplier"
            maxlength="50"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('phone')"
          />
          <div v-if="fieldError('phone')" class="invalid-feedback">
            {{ fieldError('phone') }}
          </div>
        </div>

        <!-- 3. Alamat -->
        <div class="form-group form-group--full">
          <label for="address" class="form-label">Alamat</label>
          <textarea
            id="address"
            v-model="form.address"
            class="form-control form-textarea"
            :class="{ 'is-invalid': fieldError('address') }"
            placeholder="Masukkan alamat supplier"
            rows="3"
            maxlength="500"
            :disabled="isSubmitting"
            @input="clearFieldError('address')"
          ></textarea>
          <div v-if="fieldError('address')" class="invalid-feedback">
            {{ fieldError('address') }}
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
