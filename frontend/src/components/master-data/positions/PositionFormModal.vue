<script setup>
import { reactive, watch } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  position: {
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
  description: ''
});

const populateForm = (data) => {
  form.name = data.name || '';
  form.description = data.description || '';
};

const resetForm = () => {
  form.name = '';
  form.description = '';
};

watch(() => props.isOpen, (isOpen) => {
  if (!isOpen) return;

  if (props.position) {
    populateForm(props.position);
  } else {
    resetForm();
  }
}, { immediate: true });

const handleSubmit = () => {
  const payload = {
    name: form.name.trim(),
    description: form.description ? form.description.trim() : null
  };
  emit('submit', payload);
};

const fieldError = (field) => props.errors?.[field]?.[0] || null;
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="position ? 'Edit Jabatan' : 'Tambah Jabatan'"
    :is-submitting="isSubmitting"
    size="md"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
      <!-- General error -->
      <div v-if="errors.general" class="alert-error">
        {{ errors.general }}
      </div>

      <div class="form-group">
        <label for="name" class="form-label required">Nama Jabatan</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': fieldError('name') }"
          placeholder="Misal: Administrator"
          required
        >
        <div v-if="fieldError('name')" class="invalid-feedback">{{ fieldError('name') }}</div>
      </div>

      <div class="form-group">
        <label for="description" class="form-label">Deskripsi</label>
        <textarea
          id="description"
          v-model="form.description"
          class="form-control form-textarea"
          :class="{ 'is-invalid': fieldError('description') }"
          placeholder="Misal: Deskripsi lengkap tentang jabatan ini..."
          rows="3"
        ></textarea>
        <div v-if="fieldError('description')" class="invalid-feedback">{{ fieldError('description') }}</div>
      </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.alert-error {
  background-color: #fee2e2;
  border-left: 4px solid #ef4444;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  font-size: 0.9rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 500;
  font-size: 0.9rem;
  color: var(--color-text-navy);
}

.required::after {
  content: '*';
  color: #ef4444;
  margin-left: 0.25rem;
}

.form-control {
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.95rem;
  transition: all 0.2s;
  background-color: #fff;
  width: 100%;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.is-invalid {
  border-color: #ef4444;
}

.is-invalid:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.invalid-feedback {
  font-size: 0.8rem;
  color: #ef4444;
  margin-top: 0.25rem;
}
</style>
