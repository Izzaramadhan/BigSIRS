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
  district_id: ''
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
        district_id: ''
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
        district_id: ''
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
        district_id: form.value.district_id
      })
    } else {
      await createVillage({
        code: form.value.code,
        name: form.value.name,
        district_id: form.value.district_id
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


