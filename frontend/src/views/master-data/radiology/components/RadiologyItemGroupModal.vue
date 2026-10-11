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
  groupData: {
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

const emit = defineEmits(['close', 'submit', 'saved']);

const activeGroup = () => props.group || props.groupData || null;

const form = reactive({
  name: '',
  description: '',
  is_active: true
});

const clientErrors = reactive({});

const resetForm = () => {
  form.name = '';
  form.description = '';
  form.is_active = true;
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

const populateForm = (data) => {
  form.name = data.name || '';
  form.description = (data.description !== null && data.description !== undefined) ? String(data.description) : '';
  form.is_active = (data.is_active !== undefined && data.is_active !== null) ? Boolean(data.is_active) : true;
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      const current = activeGroup();
      if (current) {
        populateForm(current);
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
  () => [props.group, props.groupData],
  () => {
    if (props.isOpen) {
      const current = activeGroup();
      if (current) {
        populateForm(current);
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
    clientErrors.name = 'Kelompok item radiologi wajib diisi.';
    isValid = false;
  }

  return isValid;
};

const handleSubmit = () => {
  if (!validateClient()) return;

  const payload = {
    name: form.name.trim(),
    description: form.description && form.description.trim() ? form.description.trim() : null,
    is_active: Boolean(form.is_active)
  };

  emit('submit', payload);
  emit('saved', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="activeGroup() ? 'Edit Kelompok Item Radiologi' : 'Tambah Kelompok Item Radiologi'"
    :submit-text="activeGroup() ? 'Simpan Perubahan' : 'Simpan'"
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
        <!-- 1. Kelompok Item (name) -->
        <div class="form-group form-group--full">
          <label for="name" class="form-label required">Kelompok Item</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Masukkan nama kelompok item"
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

        <!-- 2. Deskripsi -->
        <div class="form-group form-group--full">
          <label for="description" class="form-label">Deskripsi</label>
          <textarea
            id="description"
            v-model="form.description"
            class="form-control form-textarea"
            :class="{ 'is-invalid': fieldError('description') }"
            placeholder="Masukkan deskripsi kelompok item"
            rows="4"
            maxlength="1000"
            :disabled="isSubmitting"
            @input="clearFieldError('description')"
          ></textarea>
          <div v-if="fieldError('description')" class="invalid-feedback">
            {{ fieldError('description') }}
          </div>
        </div>

        <!-- 3. Status Tampil -->
        <div class="form-group form-group--full">
          <label class="form-label required">Status Tampil</label>
          <div class="status-radio-group">
            <label class="status-radio-item" :class="{ 'is-selected': form.is_active === true }">
              <input
                id="status_active"
                type="radio"
                v-model="form.is_active"
                :value="true"
                name="radiology_item_group_status"
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
                name="radiology_item_group_status"
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
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group--full {
  width: 100%;
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
  min-height: 90px;
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
</style>
