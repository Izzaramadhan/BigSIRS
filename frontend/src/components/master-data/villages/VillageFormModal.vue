<script setup>
import { ref, watch } from 'vue'
import { useVillages } from '@/composables/useVillages'
import LookupService from '@/services/lookup.service'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  village: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'success'])

const { createVillage, updateVillage, loading } = useVillages()

const form = ref({
  code: '',
  name: '',
  regency_id: '',
  district_id: '',
  is_active: true
})

const errors = ref({})
const submitError = ref(null)

const regencies = ref([])
const loadingRegencies = ref(false)

const districts = ref([])
const loadingDistricts = ref(false)

const loadRegencies = async () => {
  loadingRegencies.value = true
  try {
    regencies.value = await LookupService.getRegencies()
  } catch (e) {
    console.error('Failed to load regencies:', e)
  } finally {
    loadingRegencies.value = false
  }
}

const loadDistrictsError = ref(false)

const loadDistricts = async (regencyId) => {
  districts.value = []
  loadDistrictsError.value = false
  if (!regencyId) return
  
  loadingDistricts.value = true
  try {
    districts.value = await LookupService.getDistricts({ regency_id: Number(regencyId) })
  } catch (e) {
    console.error('Failed to load districts:', e)
    loadDistrictsError.value = true
  } finally {
    loadingDistricts.value = false
  }
}

watch(() => props.isOpen, async (newVal) => {
  if (newVal) {
    if (regencies.value.length === 0) {
      await loadRegencies()
    }
    
    if (props.village) {
      form.value = { 
        code: props.village.code || '',
        name: props.village.name,
        regency_id: props.village.regency_id || '',
        district_id: '',
        is_active: props.village.is_active ?? true
      }
      if (props.village.regency_id) {
        await loadDistricts(props.village.regency_id)
        form.value.district_id = props.village.district_id || ''
      }
    } else {
      form.value = {
        code: '',
        name: '',
        regency_id: '',
        district_id: '',
        is_active: true
      }
      districts.value = []
    }
    errors.value = {}
    submitError.value = null
  }
})

const onRegencyChange = async () => {
  form.value.district_id = ''
  await loadDistricts(form.value.regency_id)
}

const validate = () => {
  const newErrors = {}
  
  if (!form.value.name?.trim()) {
    newErrors.name = 'Nama kelurahan wajib diisi'
  }
  
  if (!form.value.regency_id) {
    newErrors.regency_id = 'Kabupaten/Kota wajib dipilih'
  }

  if (!form.value.district_id) {
    newErrors.district_id = 'Kecamatan wajib dipilih'
  }

  errors.value = newErrors
  return Object.keys(newErrors).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return
  
  submitError.value = null
  
  try {
    if (props.village) {
      await updateVillage(props.village.id, {
        code: form.value.code,
        name: form.value.name,
        district_id: form.value.district_id,
        is_active: form.value.is_active
      })
    } else {
      await createVillage({
        code: form.value.code,
        name: form.value.name,
        district_id: form.value.district_id,
        is_active: form.value.is_active
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
            {{ village ? 'Edit Kelurahan' : 'Tambah Kelurahan' }}
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
              <label for="village-regency">Kabupaten/Kota <span class="text-danger">*</span></label>
              <select
                id="village-regency"
                v-model="form.regency_id"
                @change="onRegencyChange"
                class="form-input"
                :class="{ 'has-error': errors.regency_id }"
                :disabled="loadingRegencies"
              >
                <option value="">Pilih Kabupaten/Kota</option>
                <option v-for="regency in regencies" :key="regency.id" :value="regency.id">
                  {{ regency.name }}
                </option>
              </select>
              <span v-if="errors.regency_id" class="error-text">
                {{ Array.isArray(errors.regency_id) ? errors.regency_id[0] : errors.regency_id }}
              </span>
            </div>

            <div class="form-group">
              <label for="village-district">Kecamatan <span class="text-danger">*</span></label>
              <select
                id="village-district"
                v-model="form.district_id"
                class="form-input"
                :class="{ 'has-error': errors.district_id }"
                :disabled="!form.regency_id || loadingDistricts"
              >
                <option value="" v-if="!form.regency_id">Pilih Kabupaten/Kota terlebih dahulu</option>
                <option value="" v-else-if="loadingDistricts">Memuat kecamatan...</option>
                <option value="" v-else-if="loadDistrictsError">Gagal memuat data kecamatan</option>
                <option value="" v-else-if="districts.length === 0">Data kecamatan tidak ditemukan</option>
                <option value="" v-else>Pilih Kecamatan</option>
                
                <option v-for="district in districts" :key="district.id" :value="district.id">
                  {{ district.name }}
                </option>
              </select>
              <span v-if="errors.district_id" class="error-text">
                {{ Array.isArray(errors.district_id) ? errors.district_id[0] : errors.district_id }}
              </span>
            </div>

            <div class="form-group">
              <label for="village-code">Kode Kelurahan</label>
              <input
                id="village-code"
                v-model="form.code"
                type="text"
                class="form-input"
                :class="{ 'has-error': errors.code }"
                placeholder="Masukkan kode kelurahan (opsional)"
              />
              <span v-if="errors.code" class="error-text">
                {{ Array.isArray(errors.code) ? errors.code[0] : errors.code }}
              </span>
            </div>

            <div class="form-group">
              <label for="village-name">Nama Kelurahan <span class="text-danger">*</span></label>
              <input
                id="village-name"
                v-model="form.name"
                type="text"
                class="form-input"
                :class="{ 'has-error': errors.name }"
                placeholder="Masukkan nama kelurahan"
              />
              <span v-if="errors.name" class="error-text">
                {{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}
              </span>
            </div>

            <div class="form-group">
              <label class="checkbox-label">
                <input
                  type="checkbox"
                  v-model="form.is_active"
                  class="form-checkbox"
                />
                Status Aktif
              </label>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-cancel" @click="emit('close')" :disabled="loading">
              Batal
            </button>
            <button type="submit" class="btn-submit" :disabled="loading">
              <span v-if="loading" class="spinner"></span>
              {{ loading ? 'Menyimpan...' : 'Simpan' }}
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

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-weight: normal;
}

.form-checkbox {
  width: 1rem;
  height: 1rem;
  cursor: pointer;
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
