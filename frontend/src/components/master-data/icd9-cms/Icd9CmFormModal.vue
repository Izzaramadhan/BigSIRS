<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  item: {
    type: Object,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'submit'])

const form = ref({
  code: '',
  name: '',
  english_name: '',
  description: '',
  is_active: true,
  inacbg_code: '',
  inacbg_name: ''
})

const errors = ref({})

onMounted(() => {
  if (props.item) {
    form.value = { ...props.item }
  }
})

const validate = () => {
  errors.value = {}
  if (!form.value.code) errors.value.code = 'Kode wajib diisi'
  if (!form.value.name) errors.value.name = 'Nama prosedur wajib diisi'
  return Object.keys(errors.value).length === 0
}

const onSubmit = () => {
  if (!validate()) return
  emit('submit', { ...form.value })
}
</script>

<template>
  <div class="modal-backdrop">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title">{{ item ? 'Edit ICD-9-CM' : 'Tambah ICD-9-CM' }}</h3>
        <button class="modal-close" @click="emit('close')" :disabled="loading">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Kode ICD-9-CM <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              v-model="form.code"
              :class="{ 'is-invalid': errors.code }"
              placeholder="Contoh: 00.00"
            >
            <span class="error-text" v-if="errors.code">{{ errors.code }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Nama Prosedur <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              v-model="form.name"
              :class="{ 'is-invalid': errors.name }"
              placeholder="Nama prosedur dalam bahasa Indonesia"
            >
            <span class="error-text" v-if="errors.name">{{ errors.name }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Nama Prosedur (EN)</label>
            <input 
              type="text" 
              class="form-control" 
              v-model="form.english_name"
              placeholder="Nama prosedur dalam bahasa Inggris"
            >
          </div>

          <div class="form-group">
            <label class="form-label">Deskripsi Tambahan</label>
            <textarea 
              class="form-control" 
              v-model="form.description"
              rows="2"
              placeholder="Keterangan opsional"
            ></textarea>
          </div>
          
          <hr class="section-divider">

          <div class="form-group">
            <label class="form-label">Kode INACBG</label>
            <input 
              type="text" 
              class="form-control" 
              v-model="form.inacbg_code"
              placeholder="Kode mapping INACBG"
            >
          </div>

          <div class="form-group">
            <label class="form-label">Nama INACBG</label>
            <input 
              type="text" 
              class="form-control" 
              v-model="form.inacbg_name"
              placeholder="Deskripsi mapping INACBG"
            >
          </div>

          <hr class="section-divider">

          <div class="form-check">
            <input 
              type="checkbox" 
              class="form-checkbox" 
              id="is_active"
              v-model="form.is_active"
            >
            <label class="form-check-label" for="is_active">
              Status Aktif
            </label>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" @click="emit('close')" :disabled="loading">
          Batal
        </button>
        <button class="btn btn-primary" @click="onSubmit" :disabled="loading">
          {{ loading ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
  backdrop-filter: blur(4px);
}

.modal-dialog {
  background: white;
  width: 100%;
  max-width: 600px;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.modal-close {
  background: transparent;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
  background: var(--color-page-bg);
  color: var(--color-text-navy);
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.form-grid {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy);
}

.text-danger {
  color: #ef4444;
}

.form-control {
  padding: 0.625rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.95rem;
  font-family: inherit;
  transition: all 0.2s;
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.1);
}

.form-control.is-invalid {
  border-color: #ef4444;
}

.form-control.is-invalid:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

textarea.form-control {
  resize: vertical;
  min-height: 80px;
}

.error-text {
  font-size: 0.75rem;
  color: #ef4444;
}

.section-divider {
  border: none;
  border-top: 1px solid var(--color-border-soft);
  margin: 0.5rem 0;
}

.form-check {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.form-checkbox {
  width: 1.25rem;
  height: 1.25rem;
  border-radius: 4px;
  border: 1px solid var(--color-border-soft);
  accent-color: var(--color-primary);
  cursor: pointer;
}

.form-check-label {
  font-size: 0.95rem;
  color: var(--color-text-navy);
  cursor: pointer;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  background: #f8fafc;
  border-radius: 0 0 12px 12px;
}

.btn {
  padding: 0.625rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.btn-secondary {
  background: white;
  border-color: var(--color-border);
  color: var(--color-text-navy);
}

.btn-secondary:hover:not(:disabled) {
  background: var(--color-page-bg);
}

.btn-primary {
  background: var(--color-primary);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
