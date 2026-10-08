<script setup>
import { ref, watch } from 'vue'
import { useRadiologyCategories } from '@/composables/useRadiologyCategories'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  category: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const { createCategory, updateCategory, loading } = useRadiologyCategories()

const form = ref({
  name: '',
  description: '',
  is_active: true,
  loinc_code: '',
  loinc_url: '',
  snomed_code: '',
  snomed_url: ''
})

const errors = ref({})
const globalError = ref(null)

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.category) {
      form.value = { ...props.category }
    } else {
      form.value = {
        name: '',
        description: '',
        is_active: true,
        loinc_code: '',
        loinc_url: '',
        snomed_code: '',
        snomed_url: ''
      }
    }
    errors.value = {}
    globalError.value = null
  }
})

const validateForm = () => {
  const newErrors = {}
  
  if (!form.value.name?.trim()) {
    newErrors.name = 'Kategori Radiologi wajib diisi'
  }
  
  errors.value = newErrors
  return Object.keys(newErrors).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  globalError.value = null
  
  const payload = {
    name: form.value.name,
    description: form.value.description,
    is_active: form.value.is_active === true || form.value.is_active === '1',
    loinc_code: form.value.loinc_code,
    loinc_url: form.value.loinc_url,
    snomed_code: form.value.snomed_code,
    snomed_url: form.value.snomed_url
  }
  
  let result
  if (props.category?.id) {
    result = await updateCategory(props.category.id, payload)
  } else {
    result = await createCategory(payload)
  }
  
  if (result.success) {
    emit('saved')
  } else {
    if (result.errors) {
      errors.value = result.errors
    }
    globalError.value = result.error || 'Terjadi kesalahan saat menyimpan data.'
  }
}

const close = () => {
  if (!loading.value) {
    emit('close')
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="close">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ category ? 'Edit' : 'Tambah' }} Data Kategori Radiologi</h3>
          <button class="btn-close" @click="close" :disabled="loading">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
        
        <form @submit.prevent="handleSubmit" class="modal-form">
          <div class="modal-body">
            <div v-if="globalError" class="alert-error">
              {{ globalError }}
            </div>
            
            <div class="form-group">
              <label class="form-label" for="category_name">
                Kategori Radiologi <span class="required">*</span>
              </label>
              <input 
                id="category_name"
                type="text" 
                class="form-input" 
                :class="{ 'is-invalid': errors.name }"
                v-model="form.name"
                placeholder="Kategori Radiologi"
                :disabled="loading"
              />
              <span v-if="errors.name" class="error-message">{{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}</span>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="category_description">Deskripsi Kategori Radiologi</label>
              <textarea 
                id="category_description"
                class="form-input form-textarea" 
                :class="{ 'is-invalid': errors.description }"
                v-model="form.description"
                placeholder="Deskripsi"
                rows="3"
                :disabled="loading"
              ></textarea>
              <span v-if="errors.description" class="error-message">{{ Array.isArray(errors.description) ? errors.description[0] : errors.description }}</span>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="category_status">Tampil</label>
              <select 
                id="category_status"
                class="form-select" 
                v-model="form.is_active"
                :disabled="loading"
              >
                <option :value="true">Ya</option>
                <option :value="false">Tidak</option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="loinc_code">Loinc Code</label>
              <input 
                id="loinc_code"
                type="text" 
                class="form-input" 
                :class="{ 'is-invalid': errors.loinc_code }"
                v-model="form.loinc_code"
                placeholder="Loinc Code"
                :disabled="loading"
              />
              <span v-if="errors.loinc_code" class="error-message">{{ Array.isArray(errors.loinc_code) ? errors.loinc_code[0] : errors.loinc_code }}</span>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="loinc_url">Loinc Url</label>
              <input 
                id="loinc_url"
                type="text" 
                class="form-input" 
                :class="{ 'is-invalid': errors.loinc_url }"
                v-model="form.loinc_url"
                placeholder="Loinc Url"
                :disabled="loading"
              />
              <span v-if="errors.loinc_url" class="error-message">{{ Array.isArray(errors.loinc_url) ? errors.loinc_url[0] : errors.loinc_url }}</span>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="snomed_code">Snomed Code</label>
              <input 
                id="snomed_code"
                type="text" 
                class="form-input" 
                :class="{ 'is-invalid': errors.snomed_code }"
                v-model="form.snomed_code"
                placeholder="Snomed Code"
                :disabled="loading"
              />
              <span v-if="errors.snomed_code" class="error-message">{{ Array.isArray(errors.snomed_code) ? errors.snomed_code[0] : errors.snomed_code }}</span>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="snomed_url">Snomed Url</label>
              <input 
                id="snomed_url"
                type="text" 
                class="form-input" 
                :class="{ 'is-invalid': errors.snomed_url }"
                v-model="form.snomed_url"
                placeholder="Snomed Url"
                :disabled="loading"
              />
              <span v-if="errors.snomed_url" class="error-message">{{ Array.isArray(errors.snomed_url) ? errors.snomed_url[0] : errors.snomed_url }}</span>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn-cancel" @click="close" :disabled="loading">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              Batal
            </button>
            <button type="submit" class="btn-primary" :disabled="loading">
              <svg v-if="loading" class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              {{ category ? 'Simpan Perubahan' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); display: flex; align-items: center; justify-content: center; z-index: 500; padding: 1rem; backdrop-filter: blur(2px); }
.modal-content { background: #ffffff; border-radius: 8px; width: 100%; max-width: 650px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
.modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border-soft); display: flex; justify-content: space-between; align-items: center; background: #ffffff; border-radius: 8px 8px 0 0; }
.modal-header h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e3a8a; text-transform: uppercase; }
.btn-close { background: transparent; border: none; color: var(--color-text-secondary); cursor: pointer; padding: 0.25rem; border-radius: 6px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
.btn-close:hover:not(:disabled) { background: var(--color-page-bg); color: var(--color-text-navy); }
.btn-close:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-close svg { width: 20px; height: 20px; }
.modal-form { display: flex; flex-direction: column; flex: 1; overflow: hidden; }
.modal-body { padding: 1.5rem; flex: 1; overflow-y: auto; }
.form-group { margin-bottom: 1.25rem; display: flex; align-items: center; }
.form-label { flex: 0 0 250px; text-align: right; margin-right: 1.5rem; font-size: 0.9rem; font-weight: 500; color: #374151; }
.required { color: #ef4444; margin-left: 0.25rem; }
.form-input, .form-select { flex: 1; padding: 0.5rem 0.75rem; border: 1px solid var(--color-border-soft); border-radius: 4px; font-size: 0.9rem; transition: all 0.2s; background: #ffffff; color: var(--color-text-navy); width: 100%; box-sizing: border-box; }
.form-input:focus, .form-select:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px var(--color-primary-light); }
.form-input:disabled, .form-select:disabled { background: #f3f4f6; color: #94a3b8; cursor: not-allowed; }
.is-invalid { border-color: #ef4444; }
.is-invalid:focus { box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2); }
.error-message { font-size: 0.75rem; color: #ef4444; margin-top: 0.25rem; display: block; margin-left: calc(250px + 1.5rem); }
.form-textarea { resize: vertical; min-height: 80px; }
.alert-error { background: #fef2f2; color: #ef4444; padding: 0.75rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500; margin-bottom: 1.5rem; border: 1px solid #f87171; }
.modal-footer { padding: 1.25rem 1.5rem; border-top: 1px solid var(--color-border-soft); display: flex; justify-content: flex-start; gap: 0.75rem; background: #ffffff; border-radius: 0 0 8px 8px; justify-content: center; }
.btn-cancel { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1.25rem; background: #ffffff; border: 1px solid var(--color-border-soft); border-radius: 4px; font-size: 0.9rem; font-weight: 500; color: var(--color-text-secondary); cursor: pointer; transition: all 0.2s; }
.btn-cancel:hover:not(:disabled) { background: #f3f4f6; color: var(--color-text-navy); }
.btn-cancel:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-cancel svg { width: 16px; height: 16px; }
.btn-primary { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1.25rem; background: #06b6d4; border: none; border-radius: 4px; font-size: 0.9rem; font-weight: 500; color: #ffffff; cursor: pointer; transition: all 0.2s; }
.btn-primary:hover:not(:disabled) { background: #0891b2; }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-primary svg { width: 16px; height: 16px; }
.spinner { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@media (max-width: 640px) { .form-group { flex-direction: column; align-items: flex-start; } .form-label { text-align: left; margin-bottom: 0.5rem; flex: 0 0 auto; width: 100%; } .error-message { margin-left: 0; } }
</style>
