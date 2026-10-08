<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRadiologyGroups } from '@/composables/master-data/radiology/useRadiologyGroups'
import axios from '@/utils/axios'

const props = defineProps({
  show: Boolean,
  item: Object
})

const emit = defineEmits(['close', 'saved'])

const { createItem, updateItem } = useRadiologyGroups()

const loading = ref(false)
const errors = ref({})

const form = ref({
  name: '',
  radiology_category_id: '' ,
  radiology_type_id: '' ,
  activity_type_id: '' ,
  radiology_item_group_ids: [] ,
  price: 0,
  interpretation_price: 0,
  loinc_code: '',
  loinc_url: '',
  is_active: true
})

// Options for lookups
const categories = ref([])
const types = ref([])
const activityTypes = ref([])
const itemGroups = ref([])

const fetchLookups = async () => {
  try {
    const [cats, typs, acts, igs] = await Promise.all([
      axios.get('/lookups/radiology-categories'),
      axios.get('/lookups/radiology-types'),
      axios.get('/lookups/activity-types'),
      axios.get('/master-data/radiology-item-groups', { params: { per_page: 1000 } })
    ])
    categories.value = cats.data.data || cats.data
    types.value = typs.data.data || typs.data
    activityTypes.value = acts.data.data || acts.data
    itemGroups.value = igs.data.data || igs.data
  } catch (e) {
    console.error('Failed to load lookups', e)
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    errors.value = {}
    if (props.item) {
      form.value = {
        name: props.item.name,
        radiology_category_id: props.item.radiology_category_id || '',
        radiology_type_id: props.item.radiology_type_id || '',
        activity_type_id: props.item.activity_type_id || '',
        radiology_item_group_ids: props.item.radiology_item_group_ids || [],
        price: props.item.price,
        interpretation_price: props.item.interpretation_price,
        loinc_code: props.item.loinc_code || '',
        loinc_url: props.item.loinc_url || '',
        is_active: props.item.is_active
      }
    } else {
      form.value = {
        name: '',
        radiology_category_id: '',
        radiology_type_id: '',
        activity_type_id: '',
        radiology_item_group_ids: [],
        price: 0,
        interpretation_price: 0,
        loinc_code: '',
        loinc_url: '',
        is_active: true
      }
    }
  }
})

onMounted(() => {
  fetchLookups()
})

const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  try {
    const payload = {
      ...form.value,
      radiology_category_id: form.value.radiology_category_id || null,
      radiology_type_id: form.value.radiology_type_id || null,
      activity_type_id: form.value.activity_type_id || null,
    }

    if (props.item) {
      await updateItem(props.item.id, payload)
    } else {
      await createItem(payload)
    }
    emit('saved')
    emit('close')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors
    } else {
      alert(e.response?.data?.message || 'Terjadi kesalahan sistem')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="show" class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h3>
          {{ item ? 'Edit' : 'Tambah' }} Group Radiologi
        </h3>
        <button type="button" class="btn-close" @click="emit('close')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label>Nama Grup Radiologi <span class="text-danger">*</span></label>
            <input 
              v-model="form.name"
              type="text" 
              class="form-input"
              :class="{ 'is-invalid': errors.name }"
              placeholder="Nama Grup Radiologi"
            >
            <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
          </div>

          <div class="form-group mt-3">
            <label>Kategori Radiologi <span class="text-danger">*</span></label>
            <select 
              v-model="form.radiology_category_id"
              class="form-select"
              :class="{ 'is-invalid': errors.radiology_category_id }"
            >
              <option value="">-- silahkan pilih --</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
            <div v-if="errors.radiology_category_id" class="invalid-feedback">{{ errors.radiology_category_id[0] }}</div>
          </div>

          <div class="form-group mt-3">
            <label>Tipe Radiologi <span class="text-danger">*</span></label>
            <select 
              v-model="form.radiology_type_id"
              class="form-select"
              :class="{ 'is-invalid': errors.radiology_type_id }"
            >
              <option value="">-- silahkan pilih --</option>
              <option v-for="typ in types" :key="typ.id" :value="typ.id">
                {{ typ.name }}
              </option>
            </select>
            <div v-if="errors.radiology_type_id" class="invalid-feedback">{{ errors.radiology_type_id[0] }}</div>
          </div>

          <div class="form-group mt-3">
            <label>Jenis Kegiatan <span class="text-danger">*</span></label>
            <select 
              v-model="form.activity_type_id"
              class="form-select"
              :class="{ 'is-invalid': errors.activity_type_id }"
            >
              <option value="">-- silahkan pilih --</option>
              <option v-for="act in activityTypes" :key="act.id" :value="act.id">
                {{ act.name }}
              </option>
            </select>
            <div v-if="errors.activity_type_id" class="invalid-feedback">{{ errors.activity_type_id[0] }}</div>
          </div>

          <div class="form-group mt-3">
            <label>Kelompok Pemeriksaan</label>
            <select 
              v-model="form.radiology_item_group_ids"
              multiple
              class="form-select"
              style="height: 120px;"
              :class="{ 'is-invalid': errors.radiology_item_group_ids }"
            >
              <option v-for="ig in itemGroups" :key="ig.id" :value="ig.id">
                {{ ig.name }}
              </option>
            </select>
            <p class="text-sm text-muted mt-1" style="font-style: italic;">
              *) Silahkan pilih item radiologi, item radiologi bisa lebih dari satu. jika tidak memiliki item radiologi silahkan pilih DEFAULT pada item radiologi.
            </p>
            <div v-if="errors.radiology_item_group_ids" class="invalid-feedback">{{ errors.radiology_item_group_ids[0] }}</div>
          </div>

          <div class="grid-2 mt-3">
            <div class="form-group">
              <label>Harga <span class="text-danger">*</span></label>
              <input 
                v-model.number="form.price"
                type="number" 
                min="0"
                class="form-input"
                :class="{ 'is-invalid': errors.price }"
                placeholder="Harga"
              >
              <div v-if="errors.price" class="invalid-feedback">{{ errors.price[0] }}</div>
            </div>

            <div class="form-group">
              <label>Harga Interpretasi <span class="text-danger">*</span></label>
              <input 
                v-model.number="form.interpretation_price"
                type="number" 
                min="0"
                class="form-input"
                :class="{ 'is-invalid': errors.interpretation_price }"
                placeholder="Harga Interpretasi"
              >
              <div v-if="errors.interpretation_price" class="invalid-feedback">{{ errors.interpretation_price[0] }}</div>
            </div>
          </div>

          <div class="grid-2 mt-3">
            <div class="form-group">
              <label>Loinc Code</label>
              <input 
                v-model="form.loinc_code"
                type="text" 
                class="form-input"
                :class="{ 'is-invalid': errors.loinc_code }"
                placeholder="Loinc Code"
              >
              <div v-if="errors.loinc_code" class="invalid-feedback">{{ errors.loinc_code[0] }}</div>
            </div>

            <div class="form-group">
              <label>Loinc Url</label>
              <input 
                v-model="form.loinc_url"
                type="text" 
                class="form-input"
                :class="{ 'is-invalid': errors.loinc_url }"
                placeholder="Loinc Url"
              >
              <div v-if="errors.loinc_url" class="invalid-feedback">{{ errors.loinc_url[0] }}</div>
            </div>
          </div>

          <div class="form-group mt-3">
            <label>Tampil <span class="text-danger">*</span></label>
            <select 
              v-model="form.is_active"
              class="form-select"
              :class="{ 'is-invalid': errors.is_active }"
            >
              <option :value="true">Ya</option>
              <option :value="false">Tidak</option>
            </select>
            <div v-if="errors.is_active" class="invalid-feedback">{{ errors.is_active[0] }}</div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" @click="emit('close')" :disabled="loading">
          Batal
        </button>
        <button type="button" class="btn-primary" @click="handleSubmit" :disabled="loading">
          <svg v-if="loading" class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
          </svg>
          {{ loading ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); display: flex; align-items: center; justify-content: center; z-index: 500; padding: 1rem; backdrop-filter: blur(2px); }
.modal-content { background: #ffffff; border-radius: 8px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); display: flex; flex-direction: column; }
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
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
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
