<script setup>
import { ref, watch } from 'vue'

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
  <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">{{ title }}</h3>
        <button type="button" class="btn-close" @click="$emit('close')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      
      <div class="modal-body">
        <form @submit.prevent="handleSubmit" id="handlingForm">
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
        </form>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="$emit('close')" :disabled="loading">Batal</button>
        <button type="submit" form="handlingForm" class="btn btn-primary" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span v-else>Simpan</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.modal-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.btn-close {
  background: transparent;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.btn-close svg {
  width: 20px;
  height: 20px;
}

.btn-close:hover {
  background: var(--color-page-bg);
  color: var(--color-text-navy);
}

.modal-body {
  padding: 1.5rem;
}

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

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  background-color: #f8fafc;
  border-bottom-left-radius: 8px;
  border-bottom-right-radius: 8px;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  border: 1px solid transparent;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: white;
  border-color: var(--color-border-soft);
  color: var(--color-text-navy);
}

.btn-secondary:hover:not(:disabled) {
  background-color: #f1f5f9;
}

.btn-primary {
  background-color: var(--color-primary);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
