<script setup>
import { ref, watch, onMounted } from 'vue'
import { useMedicines } from '@/composables/master-data/logistics/useMedicines'
import axios from '@/utils/axios'

const props = defineProps({
  show: Boolean,
  item: Object
})

const emit = defineEmits(['close', 'saved'])

const { createItem, updateItem } = useMedicines()

const loading = ref(false)
const errors = ref({})

const form = ref({
  code: '',
  name: '',
  kfa_code: '',
  function: '',
  medicine_unit_id: '',
  medicine_category_id: '',
  medicine_classification_id: '',
  medicine_route_id: '',
  generic_medicine_id: '',
  description: '',
  is_active: true
})

// Options for lookups
const units = ref([])
const categories = ref([])
const classifications = ref([])
const routes = ref([])
const generics = ref([])

const fetchLookups = async () => {
  try {
    const [uns, cats, clss, rts, gens] = await Promise.all([
      axios.get('/lookups/medicine-units'),
      axios.get('/lookups/medicine-categories'),
      axios.get('/lookups/medicine-classifications'),
      axios.get('/lookups/medicine-routes'),
      axios.get('/lookups/generic-medicines', { params: { search: '' } })
    ])
    units.value = uns.data.data || uns.data
    categories.value = cats.data.data || cats.data
    classifications.value = clss.data.data || clss.data
    routes.value = rts.data.data || rts.data
    generics.value = gens.data.data || gens.data
  } catch (e) {
    console.error('Failed to load lookups', e)
  }
}

let searchTimeout = null;
const handleSearchGeneric = async (event) => {
  const search = event.target.value;
  if (searchTimeout) clearTimeout(searchTimeout);
  
  searchTimeout = setTimeout(async () => {
    try {
      const gens = await axios.get('/lookups/generic-medicines', { params: { search } });
      generics.value = gens.data.data || gens.data;
    } catch (e) {
      console.error('Failed to search generic medicines', e);
    }
  }, 500);
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    errors.value = {}
    if (props.item) {
      // Fetch specific generic if it's set to make sure it exists in dropdown
      if (props.item.generic_medicine_id && generics.value.findIndex(g => g.id === props.item.generic_medicine_id) === -1) {
         axios.get('/lookups/generic-medicines', { params: { ids: props.item.generic_medicine_id } })
          .then(res => {
            const data = res.data.data || res.data;
            if (data.length > 0) generics.value.push(data[0]);
          });
      }

      form.value = {
        code: props.item.code || '',
        name: props.item.name,
        kfa_code: props.item.kfa_code || '',
        function: props.item.function || '',
        medicine_unit_id: props.item.medicine_unit_id || '',
        medicine_category_id: props.item.medicine_category_id || '',
        medicine_classification_id: props.item.medicine_classification_id || '',
        medicine_route_id: props.item.medicine_route_id || '',
        generic_medicine_id: props.item.generic_medicine_id || '',
        description: props.item.description || '',
        is_active: props.item.is_active
      }
    } else {
      form.value = {
        code: '',
        name: '',
        kfa_code: '',
        function: '',
        medicine_unit_id: '',
        medicine_category_id: '',
        medicine_classification_id: '',
        medicine_route_id: '',
        generic_medicine_id: '',
        description: '',
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
      medicine_unit_id: form.value.medicine_unit_id || null,
      medicine_category_id: form.value.medicine_category_id || null,
      medicine_classification_id: form.value.medicine_classification_id || null,
      medicine_route_id: form.value.medicine_route_id || null,
      generic_medicine_id: form.value.generic_medicine_id || null,
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
          {{ item ? 'Edit' : 'Tambah' }} Obat
        </h3>
        <button type="button" class="btn-close" @click="emit('close')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="handleSubmit">
          <div class="grid-2">
            <div class="form-group">
              <label>Kode Obat</label>
              <input 
                v-model="form.code"
                type="text" 
                class="form-input"
                :class="{ 'is-invalid': errors.code }"
                placeholder="Kode Obat"
              >
              <div v-if="errors.code" class="invalid-feedback">{{ errors.code[0] }}</div>
            </div>

            <div class="form-group">
              <label>Kode KFA</label>
              <input 
                v-model="form.kfa_code"
                type="text" 
                class="form-input"
                :class="{ 'is-invalid': errors.kfa_code }"
                placeholder="Kode KFA"
              >
              <div v-if="errors.kfa_code" class="invalid-feedback">{{ errors.kfa_code[0] }}</div>
            </div>
          </div>

          <div class="form-group mt-3">
            <label>Nama Obat <span class="text-danger">*</span></label>
            <input 
              v-model="form.name"
              type="text" 
              class="form-input"
              :class="{ 'is-invalid': errors.name }"
              placeholder="Nama Obat"
            >
            <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
          </div>

          <div class="grid-2 mt-3">
            <div class="form-group">
              <label>Kategori Obat</label>
              <select 
                v-model="form.medicine_category_id"
                class="form-select"
                :class="{ 'is-invalid': errors.medicine_category_id }"
              >
                <option value="">-- silahkan pilih --</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
              <div v-if="errors.medicine_category_id" class="invalid-feedback">{{ errors.medicine_category_id[0] }}</div>
            </div>

            <div class="form-group">
              <label>Satuan</label>
              <select 
                v-model="form.medicine_unit_id"
                class="form-select"
                :class="{ 'is-invalid': errors.medicine_unit_id }"
              >
                <option value="">-- silahkan pilih --</option>
                <option v-for="unt in units" :key="unt.id" :value="unt.id">
                  {{ unt.name }}
                </option>
              </select>
              <div v-if="errors.medicine_unit_id" class="invalid-feedback">{{ errors.medicine_unit_id[0] }}</div>
            </div>
          </div>

          <div class="grid-2 mt-3">
            <div class="form-group">
              <label>Golongan Obat</label>
              <select 
                v-model="form.medicine_classification_id"
                class="form-select"
                :class="{ 'is-invalid': errors.medicine_classification_id }"
              >
                <option value="">-- silahkan pilih --</option>
                <option v-for="cls in classifications" :key="cls.id" :value="cls.id">
                  {{ cls.name }}
                </option>
              </select>
              <div v-if="errors.medicine_classification_id" class="invalid-feedback">{{ errors.medicine_classification_id[0] }}</div>
            </div>

            <div class="form-group">
              <label>Jalur Masuk (Rute)</label>
              <select 
                v-model="form.medicine_route_id"
                class="form-select"
                :class="{ 'is-invalid': errors.medicine_route_id }"
              >
                <option value="">-- silahkan pilih --</option>
                <option v-for="rt in routes" :key="rt.id" :value="rt.id">
                  {{ rt.name }}
                </option>
              </select>
              <div v-if="errors.medicine_route_id" class="invalid-feedback">{{ errors.medicine_route_id[0] }}</div>
            </div>
          </div>

          <div class="form-group mt-3">
            <label>Obat Generik</label>
            <div class="searchable-select">
              <input 
                type="text" 
                @input="handleSearchGeneric" 
                placeholder="Cari obat generik..." 
                class="form-input search-input mb-1"
              >
              <select 
                v-model="form.generic_medicine_id"
                class="form-select"
                :class="{ 'is-invalid': errors.generic_medicine_id }"
              >
                <option value="">-- silahkan pilih --</option>
                <option v-for="gen in generics" :key="gen.id" :value="gen.id">
                  {{ gen.name }}
                </option>
              </select>
            </div>
            <div v-if="errors.generic_medicine_id" class="invalid-feedback">{{ errors.generic_medicine_id[0] }}</div>
          </div>
          
          <div class="form-group mt-3">
            <label>Fungsi</label>
            <input 
              v-model="form.function"
              type="text" 
              class="form-input"
              :class="{ 'is-invalid': errors.function }"
              placeholder="Fungsi"
            >
            <div v-if="errors.function" class="invalid-feedback">{{ errors.function[0] }}</div>
          </div>

          <div class="form-group mt-3">
            <label>Deskripsi</label>
            <textarea 
              v-model="form.description"
              class="form-input"
              rows="3"
              :class="{ 'is-invalid': errors.description }"
              placeholder="Deskripsi"
            ></textarea>
            <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] }}</div>
          </div>

          <div class="form-group mt-4">
            <label class="toggle-container">
              <div class="toggle-switch">
                <input 
                  type="checkbox" 
                  v-model="form.is_active"
                >
                <span class="toggle-slider"></span>
              </div>
              <span class="toggle-label">Status Aktif</span>
            </label>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-outline" @click="emit('close')" :disabled="loading">Batal</button>
            <button type="submit" class="btn-primary" :disabled="loading">
              {{ loading ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Same CSS structure as RadiologyGroupModal.vue */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(15, 23, 42, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 50;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 650px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #0f172a;
}

.btn-close {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}

.btn-close svg {
  width: 20px;
  height: 20px;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

label {
  font-size: 14px;
  font-weight: 500;
  color: #475569;
}

.text-danger {
  color: #ef4444;
}

.text-muted {
  color: #64748b;
}

.form-input, .form-select {
  padding: 10px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 14px;
  color: #0f172a;
  outline: none;
  transition: border-color 0.2s;
}

.search-input {
  margin-bottom: 4px;
  background-color: #f8fafc;
}

.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 8px center;
  background-repeat: no-repeat;
  background-size: 20px 20px;
}

.form-input:focus, .form-select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input::placeholder {
  color: #94a3b8;
}

.is-invalid {
  border-color: #ef4444;
}

.is-invalid:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.invalid-feedback {
  font-size: 12px;
  color: #ef4444;
  margin-top: -4px;
}

.mt-3 {
  margin-top: 16px;
}

.mt-4 {
  margin-top: 24px;
}

.mb-1 {
  margin-bottom: 4px;
}

/* Toggle Switch */
.toggle-container {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.toggle-label {
  font-size: 14px;
  font-weight: 500;
  color: #475569;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  transition: .4s;
  border-radius: 24px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .toggle-slider {
  background-color: #10b981;
}

input:checked + .toggle-slider:before {
  transform: translateX(20px);
}

.modal-footer {
  margin-top: 32px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-outline {
  padding: 8px 16px;
  background: white;
  border: 1px solid #cbd5e1;
  color: #475569;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-outline:hover:not(:disabled) {
  background-color: #f8fafc;
  color: #0f172a;
}

.btn-primary {
  padding: 8px 24px;
  background-color: #3b82f6;
  border: none;
  color: white;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
