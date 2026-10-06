<script setup>
import { ref, watch } from 'vue'
import { useServiceTypes } from '@/composables/useServiceTypes'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  serviceType: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'success'])

const { createServiceType, updateServiceType, loading } = useServiceTypes()

const form = ref({
  name: ''
})

const errors = ref({})
const submitError = ref(null)

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.serviceType) {
      form.value = { 
        name: props.serviceType.name
      }
    } else {
      form.value = {
        name: ''
      }
    }
    errors.value = {}
    submitError.value = null
  }
})

const validate = () => {
  const newErrors = {}
  
  if (!form.value.name?.trim()) {
    newErrors.name = 'Nama jenis layanan wajib diisi'
  }
  
  errors.value = newErrors
  return Object.keys(newErrors).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return
  
  submitError.value = null
  
  try {
    if (props.serviceType) {
      await updateServiceType(props.serviceType.id, {
        name: form.value.name,
        // Default to active if the status has been removed from UI
        is_active: true
      })
    } else {
      await createServiceType({
        name: form.value.name,
        is_active: true
      })
    }
    emit('success')
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors
    } else {
      submitError.value = err.response?.data?.message || 'Terjadi kesalahan sistem'
    }
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">
            {{ serviceType ? 'Edit Jenis Layanan' : 'Tambah Jenis Layanan' }}
          </h2>
          <button class="btn-close" @click="emit('close')" aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="modal-form">
          <div class="modal-body">
            <div v-if="submitError" class="alert-error">
              {{ submitError }}
            </div>

            <div class="form-group">
              <label for="name">Nama Jenis Layanan <span class="text-danger">*</span></label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                class="form-input"
                :class="{ 'has-error': errors.name }"
                placeholder="Masukkan nama jenis layanan"
              />
              <span v-if="errors.name" class="error-text">
                {{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}
              </span>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-cancel" @click="emit('close')" :disabled="loading">
              Batal
            </button>
            <button type="submit" class="btn-submit" :disabled="loading">
              <span v-if="loading" class="spinner"></span>
              {{ loading ? 'Menyimpan...' : (serviceType ? 'Simpan Perubahan' : 'Simpan') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 500;
  padding: 1rem;
  backdrop-filter: blur(2px);
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.btn-close {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-text-secondary);
  padding: 0.25rem;
  border-radius: 4px;
  display: flex;
  align-items: center;
  transition: all 0.2s;
}

.btn-close:hover {
  background: var(--color-page-bg);
  color: var(--color-text-navy);
}

.btn-close svg {
  width: 20px;
  height: 20px;
}

.modal-form {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--color-text-navy);
}

.text-danger {
  color: #ef4444;
}

.form-input {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.95rem;
  color: var(--color-text-navy);
  transition: all 0.2s;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.form-input.has-error {
  border-color: #ef4444;
}

.form-input.has-error:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.error-text {
  display: block;
  margin-top: 0.35rem;
  font-size: 0.8rem;
  color: #ef4444;
}

.alert-error {
  background: #fef2f2;
  border-left: 4px solid #ef4444;
  color: #991b1b;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  margin-bottom: 1.25rem;
  font-size: 0.9rem;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  background: #f8fafc;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}

.btn-cancel {
  background: #ffffff;
  color: var(--color-text-secondary);
  border: 1px solid var(--color-border-soft);
  padding: 0.6rem 1.25rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover:not(:disabled) {
  background: var(--color-page-bg);
}

.btn-submit {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  padding: 0.6rem 1.25rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-submit:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-submit:disabled, .btn-cancel:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #ffffff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
