<script setup>
import { ref, watch } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Tambah Data'
  },
  initialData: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'submit'])

const formData = ref({
  name: '',
  code: '',
  is_active: true
})

const errors = ref({})

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    formData.value = {
      name: props.initialData.name || '',
      code: props.initialData.code || '',
      is_active: props.initialData.is_active !== undefined ? props.initialData.is_active : true
    }
    errors.value = {}
  }
})

const validate = () => {
  const newErrors = {}
  
  if (!formData.value.name.trim()) {
    newErrors.name = 'Nama penanganan tidak boleh kosong'
  }
  
  errors.value = newErrors
  return Object.keys(newErrors).length === 0
}

const handleSubmit = () => {
  if (validate()) {
    emit('submit', { ...formData.value })
  }
}
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="title"
    :is-submitting="loading"
    size="sm"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
          <div class="form-group">
            <label for="name" class="form-label">Nama Penanganan <span class="text-danger">*</span></label>
            <input 
              type="text" 
              id="name" 
              v-model="formData.name"
              class="form-control"
              :class="{ 'is-invalid': errors.name }"
              placeholder="Masukkan nama penanganan"
            >
            <span v-if="errors.name" class="invalid-feedback">{{ errors.name }}</span>
          </div>
          
          <div class="form-group">
            <label for="code" class="form-label">Kode <span class="text-muted">(Opsional)</span></label>
            <input 
              type="text" 
              id="code" 
              v-model="formData.code"
              class="form-control"
              placeholder="Kode penanganan"
            >
          </div>
          
          <div class="form-group">
            <label class="form-label d-flex align-items-center gap-2 cursor-pointer">
              <input 
                type="checkbox" 
                v-model="formData.is_active"
                class="form-checkbox"
              >
              <span>Status Aktif</span>
            </label>
          </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.form-group {
  margin-bottom: 1.25rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy);
}

.form-control {
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  background-color: #fff;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-control.is-invalid {
  border-color: #dc2626;
}

.form-control.is-invalid:focus {
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.invalid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #dc2626;
}

.form-checkbox {
  width: 1rem;
  height: 1rem;
  border-radius: 0.25rem;
  border: 1px solid var(--color-border-soft);
  cursor: pointer;
}

.cursor-pointer {
  cursor: pointer;
}

.text-danger {
  color: #dc2626;
}

.text-muted {
  color: #64748b;
  font-weight: normal;
}
</style>
