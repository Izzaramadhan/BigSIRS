<template>
  <Teleport to="body">
    <div class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ isEdit ? 'Edit Item Radiologi' : 'Tambah Data Item Radiologi' }}</h3>
          <button type="button" class="btn-close" @click="$emit('close')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleSubmit">
          <div class="modal-body">
            <div v-if="error" class="alert-error">
              {{ error }}
            </div>

            <div class="form-group">
              <label for="name">Nama Item Radiologi <span class="text-danger">*</span></label>
              <input 
                type="text" 
                id="name" 
                v-model="form.name" 
                class="form-input" 
                :class="{ 'is-invalid': validationErrors.name }"
                placeholder="Nama Item Radiologi"
              >
              <div v-if="validationErrors.name" class="invalid-feedback">
                {{ validationErrors.name }}
              </div>
            </div>

            <div class="form-group mt-3">
              <label for="radiology_item_group_id">Kelompok Item Radiologi <span class="text-danger">*</span></label>
              <select 
                id="radiology_item_group_id" 
                v-model="form.radiology_item_group_id" 
                class="form-select"
                :class="{ 'is-invalid': validationErrors.radiology_item_group_id }"
              >
                <option value="">-- silahkan pilih --</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">
                  {{ group.name }}
                </option>
              </select>
              <div v-if="loadingGroups" class="text-muted text-sm mt-1">Memuat kelompok item...</div>
              <div v-if="errorGroups" class="text-danger text-sm mt-1">{{ errorGroups }}</div>
              <div v-if="validationErrors.radiology_item_group_id" class="invalid-feedback">
                {{ validationErrors.radiology_item_group_id }}
              </div>
            </div>

            <div class="form-group mt-3">
              <label for="is_active">Tampil</label>
              <select id="is_active" v-model="form.is_active" class="form-select">
                <option :value="true">Ya</option>
                <option :value="false">Tidak</option>
              </select>
            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn-primary" :disabled="isSubmitting">
              <svg v-if="isSubmitting" class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              Simpan
            </button>
            <button type="button" class="btn-cancel" @click="$emit('close')" :disabled="isSubmitting">
              Batal
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import axios from '@/utils/axios'
import { useRadiologyItems } from '@/composables/master-data/radiology/useRadiologyItems'
import type { RadiologyItem } from '@/types/radiology'

const props = defineProps<{
  show: boolean
  item: RadiologyItem | null
  isEdit: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved', message?: string): void
}>()

const { createItem, updateItem, error: apiError } = useRadiologyItems()

const form = ref({
  name: '',
  radiology_item_group_id: '' as number | '',
  is_active: true
})

const isSubmitting = ref(false)
const error = ref<string | null>(null)
const validationErrors = ref<Record<string, string>>({})

// Lookups
const groups = ref<any[]>([])
const loadingGroups = ref(false)
const errorGroups = ref<string | null>(null)

const fetchGroups = async () => {
  loadingGroups.value = true
  errorGroups.value = null
  try {
    const response = await axios.get('/lookups/radiology-item-groups')
    groups.value = response.data.data || response.data
  } catch (e: any) {
    errorGroups.value = 'Gagal memuat kelompok item radiologi'
  } finally {
    loadingGroups.value = false
  }
}

watch(() => props.item, (newItem) => {
  if (newItem && props.isEdit) {
    form.value = {
      name: newItem.name,
      radiology_item_group_id: newItem.radiology_item_group_id || '',
      is_active: newItem.is_active
    }
  } else {
    form.value = {
      name: '',
      radiology_item_group_id: '',
      is_active: true
    }
  }
  validationErrors.value = {}
  error.value = null
}, { immediate: true })

onMounted(() => {
  fetchGroups()
})

const validateForm = () => {
  const errors: Record<string, string> = {}
  let isValid = true

  if (!form.value.name.trim()) {
    errors.name = 'Nama item radiologi wajib diisi'
    isValid = false
  }

  if (!form.value.radiology_item_group_id) {
    errors.radiology_item_group_id = 'Kelompok item radiologi wajib dipilih'
    isValid = false
  }

  validationErrors.value = errors
  return isValid
}

const handleSubmit = async () => {
  if (!validateForm()) return

  isSubmitting.value = true
  error.value = null
  
  try {
    const payload = {
      name: form.value.name,
      radiology_item_group_id: form.value.radiology_item_group_id,
      is_active: form.value.is_active
    }

    let success = false
    
    if (props.isEdit && props.item) {
      success = await updateItem(props.item.id, payload)
    } else {
      success = await createItem(payload)
    }

    if (success) {
      emit('saved', props.isEdit ? 'Data berhasil diubah' : 'Data berhasil disimpan')
    } else {
      error.value = apiError.value || 'Terjadi kesalahan saat menyimpan data'
    }
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response.data.errors
      Object.keys(errors).forEach(key => {
        validationErrors.value[key] = errors[key][0]
      })
    } else {
      error.value = e.response?.data?.message || 'Terjadi kesalahan sistem'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); display: flex; align-items: center; justify-content: center; z-index: 500; padding: 1rem; backdrop-filter: blur(2px); }
.modal-content { background: #ffffff; border-radius: 8px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); display: flex; flex-direction: column; }
.modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border-soft); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: #ffffff; z-index: 10; }
.modal-header h3 { margin: 0; font-size: 1.1rem; font-weight: 600; color: var(--color-text-navy); }
.btn-close { background: transparent; border: none; color: var(--color-text-secondary); cursor: pointer; padding: 0.25rem; border-radius: 6px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
.btn-close:hover { background: var(--color-page-bg); color: var(--color-text-navy); }
.btn-close svg { width: 20px; height: 20px; }
.modal-body { padding: 1.5rem; flex: 1; }
.alert-error { background: #fef2f2; color: #ef4444; padding: 0.75rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500; margin-bottom: 1.5rem; border: 1px solid #f87171; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 500; color: var(--color-text-navy); }
.text-danger { color: #ef4444; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--color-border-soft); border-radius: 6px; font-size: 0.9rem; transition: all 0.2s; box-sizing: border-box; background-color: #ffffff; }
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px var(--color-primary-light); }
.is-invalid { border-color: #ef4444; }
.is-invalid:focus { box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2); }
.invalid-feedback { color: #ef4444; font-size: 0.8rem; margin-top: 0.25rem; }
.text-muted { color: #64748b; }
.text-sm { font-size: 0.8rem; }
.mt-1 { margin-top: 0.25rem; }
.mt-3 { margin-top: 1rem; }
.modal-footer { padding: 1.25rem 1.5rem; border-top: 1px solid var(--color-border-soft); display: flex; justify-content: flex-end; gap: 0.75rem; position: sticky; bottom: 0; background: #ffffff; z-index: 10; }
.btn-cancel { padding: 0.5rem 1.25rem; background: #ffffff; border: 1px solid var(--color-border-soft); border-radius: 6px; font-size: 0.9rem; font-weight: 500; color: var(--color-text-secondary); cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; }
.btn-cancel:hover:not(:disabled) { background: var(--color-page-bg); color: var(--color-text-navy); }
.btn-primary { padding: 0.5rem 1.25rem; background: #06b6d4; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; color: #ffffff; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; }
.btn-primary:hover:not(:disabled) { background: #0891b2; }
.btn-primary:disabled, .btn-cancel:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-primary svg { width: 18px; height: 18px; }
.spinner { animation: spin 1s linear infinite; }
.opacity-25 { opacity: 0.25; }
.opacity-75 { opacity: 0.75; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
