<script setup>
import { ref, watch } from 'vue'
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  insuranceType: {
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
})

const emit = defineEmits(['close', 'submit'])

const form = ref({
  name: '',
  is_government: false,
  inacbg_id: ''
})

const fieldError = (field) => {
  if (!props.errors || !props.errors[field]) return null
  return Array.isArray(props.errors[field]) ? props.errors[field][0] : props.errors[field]
}

const populateForm = () => {
  if (props.insuranceType) {
    form.value = {
      name: props.insuranceType.name || '',
      is_government: Boolean(props.insuranceType.is_government),
      inacbg_id: props.insuranceType.inacbg_id !== null && props.insuranceType.inacbg_id !== undefined
        ? String(props.insuranceType.inacbg_id)
        : ''
    }
  } else {
    form.value = {
      name: '',
      is_government: false,
      inacbg_id: ''
    }
  }
}

watch(
  [() => props.isOpen, () => props.insuranceType],
  ([newOpen]) => {
    if (newOpen) {
      populateForm()
    }
  },
  { immediate: true }
)

const handleSubmit = () => {
  const payload = {
    name: form.value.name.trim(),
    is_government: Boolean(form.value.is_government),
    inacbg_id: form.value.inacbg_id === '' ? null : form.value.inacbg_id.trim()
  }

  if (props.insuranceType) {
    if (props.insuranceType.code) payload.code = props.insuranceType.code
    if (props.insuranceType.type) payload.type = props.insuranceType.type
  }

  emit('submit', payload)
}
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="insuranceType ? 'Edit Jenis Asuransi' : 'Tambah Jenis Asuransi'"
    :is-submitting="isSubmitting"
    :submit-text="insuranceType ? 'Simpan Perubahan' : 'Simpan'"
    size="md"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
      <div v-if="errors && errors.general" class="alert-error">
        {{ errors.general }}
      </div>

      <div class="form-group">
        <label for="name" class="form-label required">Jenis Asuransi</label>
        <input autocomplete="off"
          id="name"
          v-model="form.name"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': fieldError('name') }"
          placeholder="Misal: BPJS KESEHATAN"
          required
        />
        <div v-if="fieldError('name')" class="invalid-feedback">{{ fieldError('name') }}</div>
      </div>

      <div class="form-group">
        <label class="form-label required">Asuransi Pemerintah</label>
        <div class="radio-group">
          <label class="radio-label">
            <input type="radio" v-model="form.is_government" :value="false" /> Tidak
          </label>
          <label class="radio-label">
            <input type="radio" v-model="form.is_government" :value="true" /> Ya
          </label>
        </div>
        <div v-if="fieldError('is_government')" class="invalid-feedback">{{ fieldError('is_government') }}</div>
      </div>

      <div class="form-group">
        <label for="inacbg_id" class="form-label">ID INACBG</label>
        <input autocomplete="off"
          id="inacbg_id"
          v-model="form.inacbg_id"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': fieldError('inacbg_id') }"
          placeholder="Misal: 3"
        />
        <div v-if="fieldError('inacbg_id')" class="invalid-feedback">{{ fieldError('inacbg_id') }}</div>
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
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243D);
}

.form-label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 6px;
  font-size: 0.9rem;
  color: var(--color-text-navy, #17243D);
  transition: all 0.2s;
  width: 100%;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px var(--color-primary-light, rgba(8, 127, 120, 0.1));
}

.form-control.is-invalid {
  border-color: #ef4444;
}

.invalid-feedback {
  font-size: 0.8rem;
  color: #ef4444;
}

.radio-group {
  display: flex;
  gap: 1.5rem;
  align-items: center;
  padding: 0.25rem 0;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: var(--color-text-navy, #17243D);
  cursor: pointer;
}

.radio-label input[type="radio"] {
  accent-color: var(--color-primary, #087f78);
  width: 16px;
  height: 16px;
  cursor: pointer;
}
</style>
