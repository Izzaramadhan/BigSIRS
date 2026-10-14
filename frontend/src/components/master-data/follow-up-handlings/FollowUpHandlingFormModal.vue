<script setup>
import { reactive, watch, computed } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  item: {
    type: Object,
    default: null
  },
  handling: {
    type: Object,
    default: null
  },
  initialData: {
    type: Object,
    default: null
  },
  isSubmitting: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'submit']);

const activeItem = computed(() => props.item || props.handling || props.initialData);
const isEditing = computed(() => Boolean(activeItem.value?.id || activeItem.value?.name));
const isBusy = computed(() => props.isSubmitting || props.loading);

const form = reactive({
  name: ''
});

const clientErrors = reactive({
  name: ''
});

const populateForm = (data) => {
  form.name = data?.name || '';
  clientErrors.name = '';
};

const resetForm = () => {
  form.name = '';
  clientErrors.name = '';
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (!isOpen) {
      resetForm();
      return;
    }

    if (activeItem.value) {
      populateForm(activeItem.value);
    } else {
      resetForm();
    }
  },
  { immediate: true }
);

const fieldError = (field) => {
  return clientErrors[field] || props.errors?.[field]?.[0] || null;
};

const handleSubmit = () => {
  clientErrors.name = '';

  const trimmedName = form.name.trim();
  if (!trimmedName) {
    clientErrors.name = 'Nama penanganan lanjutan wajib diisi.';
    return;
  }

  const payload = {
    name: trimmedName
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="isEditing ? 'Edit Penanganan Lanjutan' : 'Tambah Penanganan Lanjutan'"
    :submit-text="isEditing ? 'Simpan Perubahan' : 'Simpan'"
    :is-submitting="isBusy"
    size="md"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
      <!-- General error banner -->
      <div v-if="errors?.general" class="alert-error">
        {{ errors.general }}
      </div>

      <!-- Field Nama -->
      <div class="form-group">
        <label for="name" class="form-label required">Nama</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': fieldError('name') }"
          placeholder="Masukkan nama penanganan lanjutan"
          autocomplete="off"
          required
        />
        <div v-if="fieldError('name')" class="invalid-feedback">
          {{ fieldError('name') }}
        </div>
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
  padding: 0.625rem 0.875rem;
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
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
