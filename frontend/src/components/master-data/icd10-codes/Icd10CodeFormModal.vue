<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  code: {
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
  is_medical_history: false,
  is_active: true,
  inacbg_code: '',
  inacbg_name: '',
  class_1_tariff: 0,
  class_2_tariff: 0,
  class_3_tariff: 0
})

const errors = ref({})

onMounted(() => {
  if (props.code) {
    form.value = { ...props.code }
  }
})

const validate = () => {
  errors.value = {}
  if (!form.value.code) errors.value.code = 'Kode wajib diisi'
  if (!form.value.name) errors.value.name = 'Nama diagnosa wajib diisi'
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
        <h3 class="modal-title">{{ code ? 'Edit ICD-10' : 'Tambah ICD-10' }}</h3>
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
            <label class="form-label">Kode ICD-10 <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              v-model="form.code"
              :class="{ 'is-invalid': errors.code }"
              placeholder="Contoh: A00.0"
            >
            <span class="error-text" v-if="errors.code">{{ errors.code }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Nama Diagnosa <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              v-model="form.name"
              :class="{ 'is-invalid': errors.name }"
              placeholder="Nama diagnosa dalam bahasa Indonesia"
            >
            <span class="error-text" v-if="errors.name">{{ errors.name }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Nama Diagnosa (EN)</label>
            <input 
              type="text" 
              class="form-control" 
              v-model="form.english_name"
              placeholder="Nama diagnosa dalam bahasa Inggris"
            >
          </div>

          <div class="form-group">
            <label class="form-label">Deskripsi Diagnosa</label>
            <textarea 
              class="form-control" 
              v-model="form.description"
              rows="2"
              placeholder="Keterangan tambahan (opsional)"
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
          <h4 class="section-title">Tarif INACBG (Opsional)</h4>

          <div class="tariff-grid">
            <div class="form-group">
              <label class="form-label">Kelas 1 (Rp)</label>
              <input 
                type="number" 
                class="form-control" 
                v-model.number="form.class_1_tariff"
                min="0"
              >
            </div>
            <div class="form-group">
              <label class="form-label">Kelas 2 (Rp)</label>
              <input 
                type="number" 
                class="form-control" 
                v-model.number="form.class_2_tariff"
                min="0"
              >
            </div>
            <div class="form-group">
              <label class="form-label">Kelas 3 (Rp)</label>
              <input 
                type="number" 
                class="form-control" 
                v-model.number="form.class_3_tariff"
                min="0"
              >
            </div>
          </div>

          <hr class="section-divider">

          <div class="form-check">
            <input 
              type="checkbox" 
              class="form-checkbox" 
              id="is_medical_history"
              v-model="form.is_medical_history"
            >
            <label class="form-check-label" for="is_medical_history">
              Tandai sebagai Riwayat Penyakit (Medical History)
            </label>
          </div>

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
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
  padding: 1rem;
}

.modal-dialog {
  width: 100%;
  max-width: 600px;
  background: white;
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
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.modal-close {
  background: transparent;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s;
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

.section-divider {
  border: 0;
  border-top: 1px dashed var(--color-border-soft);
  margin: 0.5rem 0;
}

.section-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin-top: -0.5rem;
  margin-bottom: 0;
}

.tariff-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
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

.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  width: 100%;
}

.form-control:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.1);
}

.form-check {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.form-checkbox {
  width: 1rem;
  height: 1rem;
  border-radius: 4px;
  border: 1px solid var(--color-border);
}

.form-check-label {
  font-size: 0.875rem;
  color: var(--color-text-navy);
  cursor: pointer;
}

.text-danger {
  color: #ef4444;
}

.error-text {
  font-size: 0.75rem;
  color: #ef4444;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn {
  padding: 0.6rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border: 1px solid transparent;
  transition: all 0.2s;
  font-family: inherit;
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-primary {
  background: var(--color-primary);
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
}

.btn-secondary {
  background: white;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
}

.btn-secondary:hover:not(:disabled) {
  background: var(--color-page-bg);
}
</style>
