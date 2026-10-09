<script setup>
import { ref, watch, computed, reactive } from 'vue'
import axios from '@/utils/axios'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  packageData: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const form = ref({
  name: '',
  price: 0,
  quantity: 0,
  description: '',
  items: []
})

const state = reactive({
  submitting: false,
  errors: {}
})

const error = ref(null)
const medicineSearch = ref('')
const searchedMedicines = ref([])
const searchTimeout = ref(null)

const isEditing = computed(() => !!props.packageData)

const calculatedTotal = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (Number(item.subtotal) || 0), 0)
})

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      state.errors = {}
      error.value = null
      medicineSearch.value = ''
      searchedMedicines.value = []
      
      if (props.packageData) {
        // Edit mode
        form.value = {
          name: props.packageData.name,
          price: props.packageData.price,
          quantity: props.packageData.quantity,
          description: props.packageData.description || '',
          items: (props.packageData.items || []).map(item => ({
            medicine_id: item.medicine_id,
            medicine_name: item.medicine?.name,
            quantity: item.quantity,
            unit_price: item.unit_price,
            subtotal: item.subtotal
          }))
        }
      } else {
        // Create mode
        form.value = {
          name: '',
          price: 0,
          quantity: 0,
          description: '',
          items: []
        }
      }
    }
  }
)

const formatNumber = (val) => {
  return new Intl.NumberFormat('id-ID').format(val)
}

const onSearchMedicine = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  if (!medicineSearch.value || medicineSearch.value.length < 2) {
    searchedMedicines.value = []
    return
  }
  
  searchTimeout.value = setTimeout(async () => {
    try {
      const response = await axios.get('/master-data/medicines', {
        params: { search: medicineSearch.value, per_page: 10 }
      })
      searchedMedicines.value = response.data.data
    } catch (err) {
      console.error('Error searching medicine:', err)
    }
  }, 300)
}

const addMedicine = (medicine) => {
  // Check duplicate
  const exists = form.value.items.some(item => item.medicine_id === medicine.id)
  if (exists) {
    alert('Obat sudah ada di dalam paket.')
    return
  }
  
  const unitPrice = medicine.selling_price_per_unit || 0
  const qty = 1
  
  form.value.items.push({
    medicine_id: medicine.id,
    medicine_name: medicine.name,
    medicine_unit: medicine.medicine_unit?.name || '',
    quantity: qty,
    unit_price: unitPrice,
    subtotal: qty * unitPrice
  })
  
  // Re-calculate package total
  form.value.price = calculatedTotal.value
  
  medicineSearch.value = ''
  searchedMedicines.value = []
}

const updateSubtotal = (item) => {
  item.subtotal = (Number(item.quantity) || 0) * (Number(item.unit_price) || 0)
  form.value.price = calculatedTotal.value
}

const removeItem = (index) => {
  form.value.items.splice(index, 1)
  form.value.price = calculatedTotal.value
}

const submitForm = async () => {
  if (form.value.items.length === 0) {
    error.value = 'Paket harus memiliki minimal satu obat.'
    return
  }
  
  state.submitting = true
  state.errors = {}
  error.value = null

  try {
    if (isEditing.value) {
      await axios.put(`/master-data/medicine-packages/${props.packageData.id}`, form.value)
    } else {
      await axios.post('/master-data/medicine-packages', form.value)
    }
    emit('saved')
    closeModal()
  } catch (err) {
    if (err.response && err.response.status === 422) {
      state.errors = err.response.data.errors
    } else {
      error.value = err.response?.data?.message || err.message
    }
  } finally {
    state.submitting = false
  }
}

const closeModal = () => {
  if (!state.submitting) {
    emit('close')
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-backdrop">
      <div class="modal-container modal-xl" role="dialog" aria-modal="true">
        <div class="modal-header">
          <h2 class="modal-title">
            {{ isEditing ? 'Edit Paket Obat' : 'Tambah Paket Obat' }}
          </h2>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <div v-if="error" class="alert-error mb-4">
            {{ error }}
          </div>

          <form id="packageForm" @submit.prevent="submitForm">
            <div class="form-row">
              <div class="form-group flex-1">
                <label for="name" class="form-label">Nama Paket <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  id="name" 
                  v-model="form.name" 
                  class="form-control" 
                  :class="{ 'is-invalid': state.errors.name }"
                  required
                />
                <div v-if="state.errors.name" class="invalid-feedback">{{ state.errors.name[0] }}</div>
              </div>
              
              <div class="form-group flex-1" style="position: relative;">
                <label class="form-label">Pencarian Obat</label>
                <div class="search-box">
                  <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                  <input
                    type="text"
                    class="form-control search-input"
                    placeholder="Ketik minimal 2 huruf..."
                    v-model="medicineSearch"
                    @input="onSearchMedicine"
                    :disabled="state.submitting"
                  />
                </div>
                
                <div v-if="searchedMedicines.length > 0" class="autocomplete-list">
                  <button
                    v-for="med in searchedMedicines"
                    :key="med.id"
                    type="button"
                    class="autocomplete-item"
                    @click="addMedicine(med)"
                  >
                    <div class="med-name">{{ med.name }}</div>
                    <div class="med-meta">{{ med.medicine_unit?.name || 'No Unit' }} • Rp {{ formatNumber(med.selling_price_per_unit || 0) }}</div>
                  </button>
                </div>
              </div>
            </div>
            
            <div class="table-container mb-4">
              <div class="table-header">Komposisi Paket</div>
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-name">NAMA OBAT</th>
                    <th class="col-qty text-center">JUMLAH</th>
                    <th class="col-price text-right">HARGA @</th>
                    <th class="col-subtotal text-right">SUB TOTAL</th>
                    <th class="col-action text-center">AKSI</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="form.items.length === 0">
                    <td colspan="5" class="text-center empty-cell">Belum ada obat dalam paket. Cari dan tambahkan obat di atas.</td>
                  </tr>
                  <tr v-for="(item, index) in form.items" :key="index">
                    <td class="font-medium text-navy">
                      {{ item.medicine_name || item.medicine?.name }}
                    </td>
                    <td class="text-center">
                      <input 
                        type="number" 
                        class="form-control text-center input-sm mx-auto" 
                        v-model.number="item.quantity" 
                        min="1" 
                        @input="updateSubtotal(item)"
                        required
                        style="width: 80px;"
                      />
                    </td>
                    <td>
                      <input 
                        type="number" 
                        class="form-control text-right input-sm" 
                        v-model.number="item.unit_price" 
                        min="0" 
                        @input="updateSubtotal(item)"
                        required
                      />
                    </td>
                    <td class="text-right font-medium">Rp {{ formatNumber(item.subtotal) }}</td>
                    <td class="text-center">
                      <button type="button" class="btn-action delete mx-auto" @click="removeItem(index)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <polyline points="3 6 5 6 21 6"></polyline>
                          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="form-row">
              <div class="form-group flex-1">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea 
                  id="description" 
                  v-model="form.description" 
                  class="form-control" 
                  rows="4"
                  placeholder="Tambahkan deskripsi paket..."
                ></textarea>
              </div>

              <div class="form-group flex-1 totals-section">
                <div class="form-group mb-3">
                  <label for="price" class="form-label">Total Harga Paket (Rp) <span class="text-danger">*</span></label>
                  <input 
                    type="number" 
                    id="price" 
                    v-model.number="form.price" 
                    class="form-control text-right font-medium" 
                    :class="{ 'is-invalid': state.errors.price }"
                    required
                  />
                  <div class="total-hint mt-1">Total komposisi: Rp {{ formatNumber(calculatedTotal) }}</div>
                  <div v-if="state.errors.price" class="invalid-feedback">{{ state.errors.price[0] }}</div>
                </div>
                
                <div class="form-group mb-0">
                  <label for="quantity" class="form-label">Kapasitas / Stok Paket</label>
                  <input 
                    type="number" 
                    id="quantity" 
                    v-model.number="form.quantity" 
                    class="form-control text-right" 
                  />
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal" :disabled="state.submitting">
            Batal
          </button>
          <button type="submit" form="packageForm" class="btn btn-primary" :disabled="state.submitting">
            <span v-if="state.submitting" class="spinner"></span>
            {{ isEditing ? 'Simpan Perubahan' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
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
  z-index: 1000;
  padding: 1rem;
}

.modal-container {
  background: #fff;
  border-radius: 8px;
  width: 100%;
  max-width: 600px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.modal-container.modal-xl {
  max-width: 1000px;
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
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.btn-close {
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #334155;
}

.btn-close svg {
  width: 20px;
  height: 20px;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.form-row {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}

.flex-1 {
  flex: 1;
  min-width: 300px;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  font-size: 0.9rem;
  color: var(--color-text-navy);
}

.form-control {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.95rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.1);
}

.form-control.is-invalid {
  border-color: #dc2626;
}

.invalid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.8rem;
  color: #dc2626;
}

.text-danger {
  color: #dc2626;
}

.search-box {
  position: relative;
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  color: var(--color-text-secondary);
}

.search-input {
  padding-left: 2.5rem;
}

.autocomplete-list {
  position: absolute;
  top: 100%;
  left: 0;
  width: 100%;
  background: white;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  margin-top: 4px;
  z-index: 50;
  max-height: 200px;
  overflow-y: auto;
}

.autocomplete-item {
  display: block;
  width: 100%;
  text-align: left;
  padding: 0.75rem 1rem;
  border: none;
  background: none;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background 0.2s;
}

.autocomplete-item:hover {
  background: #f8fafc;
}

.med-name {
  font-weight: 500;
  color: var(--color-text-navy);
  margin-bottom: 2px;
}

.med-meta {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.table-container {
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  overflow: hidden;
}

.table-header {
  background: #f8fafc;
  padding: 0.75rem 1rem;
  font-weight: 600;
  border-bottom: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.data-table th {
  background: #ffffff;
  padding: 0.75rem 1rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid #e2e8f0;
}

.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.input-sm {
  padding: 0.4rem 0.5rem;
  font-size: 0.85rem;
}

.col-name { text-align: left; }
.col-qty { width: 100px; }
.col-price { width: 150px; }
.col-subtotal { width: 150px; }
.col-action { width: 80px; }

.empty-cell {
  padding: 2rem 1rem !important;
  color: #64748b;
  font-style: italic;
}

.btn-action {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  border-radius: 6px;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action svg {
  width: 16px;
  height: 16px;
}

.btn-action.delete:hover { background: #fef2f2; color: #ef4444; }

.totals-section {
  background: #f8fafc;
  padding: 1.25rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.total-hint {
  font-size: 0.8rem;
  color: #64748b;
}

.alert-error {
  background: #fef2f2;
  color: #991b1b;
  padding: 1rem;
  border-radius: 6px;
  font-size: 0.9rem;
  border: 1px solid #fecaca;
}

.text-center { text-align: center; }
.text-right { text-align: right; }
.font-medium { font-weight: 500; }
.text-navy { color: var(--color-text-navy); }
.mx-auto { margin-left: auto; margin-right: auto; }
.mb-4 { margin-bottom: 1.5rem; }
.mb-3 { margin-bottom: 1rem; }
.mb-0 { margin-bottom: 0; }
.mt-1 { margin-top: 0.25rem; }

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

.btn-secondary {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #334155;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #94a3b8;
}

.btn-primary {
  background: var(--color-primary);
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
}

.spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
