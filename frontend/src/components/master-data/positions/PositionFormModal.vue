<script setup>
import { reactive, watch } from 'vue'
import { positionsService } from '@/services/master-data/positions.service'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  position: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'success'])

const form = reactive({
  name: '',
  description: ''
})

const loading = reactive({
  submit: false
})

const errors = reactive({
  general: null,
  name: null,
  description: null
})

const resetForm = () => {
  form.name = ''
  form.description = ''
  clearErrors()
}

const clearErrors = () => {
  errors.general = null
  errors.name = null
  errors.description = null
}

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.position) {
      form.name = props.position.name
      form.description = props.position.description || ''
    } else {
      resetForm()
    }
  }
})

const handleSubmit = async () => {
  clearErrors()
  loading.submit = true

  try {
    const payload = {
      name: form.name.trim(),
      description: form.description ? form.description.trim() : null
    }

    if (props.position) {
      await positionsService.updatePosition(props.position.id, payload)
    } else {
      await positionsService.createPosition(payload)
    }
    
    emit('success')
  } catch (err) {
    if (err.response?.status === 422) {
      const validationErrors = err.response.data.errors
      if (validationErrors.name) errors.name = validationErrors.name[0]
      if (validationErrors.description) errors.description = validationErrors.description[0]
    } else {
      errors.general = err.response?.data?.message || 'Terjadi kesalahan sistem'
    }
  } finally {
    loading.submit = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')" role="dialog" aria-modal="true">
      <div class="modal-content modal-md">
        <div class="modal-header">
          <h3>{{ position ? 'Edit Jabatan' : 'Tambah Jabatan' }}</h3>
          <button type="button" class="btn-close" @click="$emit('close')" aria-label="Tutup modal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        
        <form @submit.prevent="handleSubmit" class="modal-form">
          <div v-if="errors.general" class="alert-error">
            {{ errors.general }}
          </div>

          <div class="form-group" style="margin-bottom: 1rem;">
            <label for="name" class="form-label required">Nama Jabatan</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': errors.name }"
              placeholder="Misal: Administrator"
              required
            >
            <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
          </div>

          <div class="form-group" style="margin-bottom: 1rem;">
            <label for="description" class="form-label">Deskripsi</label>
            <textarea
              id="description"
              v-model="form.description"
              class="form-control"
              :class="{ 'is-invalid': errors.description }"
              placeholder="Misal: Deskripsi lengkap tentang jabatan ini..."
              rows="3"
            ></textarea>
            <div v-if="errors.description" class="invalid-feedback">{{ errors.description }}</div>
          </div>


          <div class="modal-footer">
            <button type="button" class="btn-cancel" @click="$emit('close')" :disabled="loading.submit">
              Batal
            </button>
            <button type="submit" class="btn-submit" :disabled="loading.submit || !form.name.trim()">
              {{ loading.submit ? 'Menyimpan...' : (position ? 'Simpan Perubahan' : 'Simpan') }}
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
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-content.modal-md {
  max-width: 500px;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  position: sticky;
  top: 0;
  background: #ffffff;
  z-index: 1;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.1rem;
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

.btn-close:hover { color: var(--color-text-navy); background: var(--color-page-bg); }

.btn-close svg { width: 20px; height: 20px; }

.modal-form {
  padding: 1.5rem;
}

.alert-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.form-label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.9rem;
  color: var(--color-text-navy);
  transition: all 0.2s;
  width: 100%;
  box-sizing: border-box;
}

textarea.form-control {
  resize: vertical;
  min-height: 80px;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.form-control.is-invalid {
  border-color: #ef4444;
}

.invalid-feedback {
  font-size: 0.8rem;
  color: #ef4444;
}

/* Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1.5rem;
  margin-top: 0.5rem;
  border-top: 1px solid var(--color-border-soft);
}

.btn-cancel {
  background: transparent;
  color: var(--color-text-secondary);
  border: 1px solid var(--color-border-soft);
  padding: 0.55rem 1.25rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
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
  padding: 0.55rem 1.5rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-submit:hover:not(:disabled) { opacity: 0.9; }
.btn-submit:disabled, .btn-cancel:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
