<template>
  <div v-if="modelValue" class="modal-backdrop">
    <div class="modal-container modal-xl">
      <div class="modal-header">
        <h3 class="modal-title">{{ mode === 'edit' ? 'Edit Paket Tindakan' : 'Tambah Paket Tindakan' }}</h3>
        <button class="modal-close" @click="close">&times;</button>
      </div>
      
      <div class="modal-body">
        <div v-if="loadingDetail" class="text-center py-8 text-gray-500">
          Memuat data paket...
        </div>
        <div v-else-if="detailError" class="text-center py-8">
          <p class="text-red-500 mb-4">{{ detailError }}</p>
          <button type="button" class="btn btn-primary" @click="loadPackageDetail(packageId)">Coba Lagi</button>
        </div>
        <form v-else @submit.prevent="save" class="form-layout">
          <div class="space-y-4">
            <!-- Header -->
            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label for="name">Nama Paket <span class="required">*</span></label>
                <input id="name" class="form-control" type="text" v-model="form.name" required />
                <p v-if="errors.name" class="error-feedback mt-1">{{ errors.name[0] }}</p>
              </div>
              <div class="flex items-center mt-6">
                <label class="flex items-center" style="cursor: pointer;">
                  <input type="checkbox" style="margin-right: 0.5rem;" v-model="form.is_active" />
                  <span class="text-sm">Status Aktif</span>
                </label>
              </div>
            </div>

            <!-- Items -->
            <div class="mt-4 pt-4" style="border-top: 1px solid var(--color-border-soft);">
              <div class="flex justify-between items-center mb-4">
                <h3 class="section-title" style="margin: 0; border: none; padding: 0;">Item Tindakan</h3>
                <button type="button" class="btn btn-primary btn-sm" @click="addItem">
                  Tambah Tindakan
                </button>
              </div>

              <p v-if="errors.items" class="error-feedback mb-2">{{ errors.items[0] }}</p>

              <div class="space-y-3">
                <div v-for="(item, index) in form.items" :key="item._key" class="p-3 bg-slate-50" style="border: 1px solid var(--color-border-soft); border-radius: 6px; padding: 1rem; margin-bottom: 1rem;">
                  <div class="grid gap-3" style="grid-template-columns: 5fr 4fr 3fr;">
                    <!-- Tindakan -->
                    <div class="form-group" style="margin-bottom: 0;">
                      <label class="text-xs">Tindakan <span class="required">*</span></label>
                      <AsyncProcedureSelect 
                        v-model="item.medical_procedure_id"
                        :initialProcedure="item.procedure"
                        @change="(proc) => onProcedureChange(item, proc)"
                      />
                      <p v-if="errors[`items.${index}.medical_procedure_id`]" class="error-feedback mt-1">{{ errors[`items.${index}.medical_procedure_id`][0] }}</p>
                    </div>

                    <!-- Jenis Tarif -->
                    <div class="form-group" style="margin-bottom: 0;">
                      <label class="text-xs">Jenis Tarif (Opsional)</label>
                      <select class="form-control form-control-sm" v-model="item.medical_procedure_tariff_id" @change="onTariffChange(item)" :disabled="!item.medical_procedure_id">
                        <option value="">Pilih Tarif</option>
                        <option v-for="tariff in item._available_tariffs || []" :key="tariff.id" :value="tariff.id">
                          {{ tariff.tariff_type?.name }} ({{ formatCurrency(tariff.total_amount) }})
                        </option>
                      </select>
                    </div>

                    <!-- Unit Amount -->
                    <div class="form-group" style="margin-bottom: 0;">
                      <label class="text-xs">Harga (Snapshot) <span class="required">*</span></label>
                      <input type="number" class="form-control form-control-sm" v-model="item.unit_amount" min="0" required @input="calculateSubtotal(item)" />
                    </div>
                  </div>
                  
                  <div class="mt-3 flex justify-between items-center pt-3" style="border-top: 1px dashed var(--color-border-soft);">
                    <button 
                      type="button" 
                      class="btn btn-sm btn-icon text-red flex items-center gap-1 item-remove-button" 
                      style="color: #ef4444; background: #fee2e2; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.85rem;"
                      @click="requestRemoveItem(index, item)" 
                      :aria-label="`Hapus ${item.procedure?.name || 'tindakan'}`"
                      title="Hapus Tindakan"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                      Hapus Item
                    </button>
                    <div class="text-right text-sm font-bold text-navy">
                      Subtotal: {{ formatCurrency(item.subtotal_amount) }}
                    </div>
                  </div>
                </div>

                <div v-if="form.items.length === 0" class="text-center py-4 text-gray-500" style="border: 1px dashed var(--color-border-soft); border-radius: 6px;">
                  Belum ada item tindakan. Klik "Tambah Tindakan".
                </div>
              </div>
            </div>

            <div class="mt-4 pt-4 text-right" style="border-top: 1px solid var(--color-border-soft);">
              <span class="text-lg font-bold text-navy">Total: {{ formatCurrency(calculateTotalAmount) }}</span>
            </div>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="modal-footer" v-if="!loadingDetail && !detailError">
        <button class="btn btn-secondary" @click="close" type="button" :disabled="loading">Batal</button>
        <button class="btn btn-primary" @click="save" :disabled="loading || isInitializingEdit || form.items.length === 0">
          {{ loading ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
      <div class="modal-footer" v-else>
        <button class="btn btn-secondary" @click="close" type="button">Tutup</button>
      </div>
    </div>

    <!-- Item Delete Confirmation Dialog -->
    <div v-if="itemToDelete !== null" class="modal-backdrop" style="z-index: 1050;">
      <div class="modal-container" style="max-width: 400px;">
        <div class="modal-header">
          <h3 class="modal-title text-red">Hapus tindakan dari paket?</h3>
          <button class="modal-close" @click="cancelRemoveItem" type="button">&times;</button>
        </div>
        <div class="modal-body">
          <p>Tindakan <strong>"{{ itemToDelete.procedure?.name || 'Item ini' }}"</strong> akan dikeluarkan dari paket ini setelah perubahan disimpan.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" @click="cancelRemoveItem">Batal</button>
          <button class="btn-danger btn" type="button" @click="confirmRemoveItem">Hapus Tindakan</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import ProcedurePackageService from '@/services/master-data/procedure-packages.service'
import MedicalProcedureService from '@/services/procedure'
import AsyncProcedureSelect from '@/components/common/AsyncProcedureSelect.vue'

const props = defineProps({
  mode: {
    type: String,
    default: 'create'
  },
  packageId: {
    type: [Number, String],
    default: null
  },
  modelValue: Boolean
})

const emit = defineEmits(['update:modelValue', 'saved'])

const loading = ref(false)
const loadingDetail = ref(false)
const detailError = ref('')
const errors = ref({})
const isInitializingEdit = ref(false)

const form = ref({
  name: '',
  is_active: true,
  items: []
})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0)
}

const fetchProcedureDetailForTariffs = async (item, procedureId) => {
  try {
    const { data } = await MedicalProcedureService.getProcedure(procedureId);
    item._available_tariffs = data.data.tariffs || [];
    
    // Auto-select tariff if only 1 exists and none selected yet
    if (!isInitializingEdit.value && !item.medical_procedure_tariff_id && item._available_tariffs.length === 1) {
      item.medical_procedure_tariff_id = item._available_tariffs[0].id;
      item.unit_amount = Number(item._available_tariffs[0].total_amount) || 0;
      calculateSubtotal(item);
    }
  } catch (err) {
    console.error("Failed to fetch procedure tariffs", err);
  }
}

const onProcedureChange = async (item, proc) => {
  if (isInitializingEdit.value) return;

  item.medical_procedure_tariff_id = ''
  item.unit_amount = 0
  item.procedure = proc || null;
  item._available_tariffs = [];
  
  if (item.medical_procedure_id) {
    // If proc was provided by the select component and has tariffs, use them directly
    if (proc && proc.tariffs) {
      item._available_tariffs = proc.tariffs;
      if (item._available_tariffs.length === 1) {
        item.medical_procedure_tariff_id = item._available_tariffs[0].id;
        item.unit_amount = Number(item._available_tariffs[0].total_amount) || 0;
      }
    } else {
      await fetchProcedureDetailForTariffs(item, item.medical_procedure_id)
    }
  }
  calculateSubtotal(item)
}

const onTariffChange = (item) => {
  if (isInitializingEdit.value) return;

  if (item.medical_procedure_tariff_id && item._available_tariffs) {
    const selectedTariff = item._available_tariffs.find(t => t.id === item.medical_procedure_tariff_id);
    if (selectedTariff) {
      item.unit_amount = Number(selectedTariff.total_amount) || 0;
    }
  }
  calculateSubtotal(item);
}

const addItem = () => {
  form.value.items.push({
    id: null,
    medical_procedure_id: '',
    procedure: null,
    medical_procedure_tariff_id: '',
    _available_tariffs: [],
    quantity: 1,
    unit_amount: 0,
    subtotal_amount: 0,
    sort_order: form.value.items.length + 1,
    // Add a local unique key for rendering safely if needed
    _key: Date.now() + Math.random()
  })
}

const itemToDelete = ref(null)
const itemToDeleteIndex = ref(-1)

const requestRemoveItem = (index, item) => {
  if (item.id) {
    // Already saved, ask for confirmation
    itemToDelete.value = item
    itemToDeleteIndex.value = index
  } else {
    // New item, remove immediately
    form.value.items.splice(index, 1)
  }
}

const cancelRemoveItem = () => {
  itemToDelete.value = null
  itemToDeleteIndex.value = -1
}

const confirmRemoveItem = () => {
  if (itemToDeleteIndex.value > -1) {
    form.value.items.splice(itemToDeleteIndex.value, 1)
  }
  cancelRemoveItem()
}

const calculateSubtotal = (item) => {
  item.subtotal_amount = (item.quantity || 0) * (item.unit_amount || 0)
}

const calculateTotalAmount = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (Number(item.subtotal_amount) || 0), 0)
})

const resetForm = () => {
  errors.value = {}
  detailError.value = ''
  itemToDelete.value = null
  itemToDeleteIndex.value = -1
  form.value = {
    name: '',
    is_active: true,
    items: []
  }
}

const loadPackageDetail = async (id) => {
  if (!id) {
    detailError.value = 'ID Paket tidak valid (kosong)'
    return
  }

  loadingDetail.value = true
  detailError.value = ''
  isInitializingEdit.value = true
  
  try {
    const { data } = await ProcedurePackageService.getPackage(id)
    const detail = data.data
    
    form.value.name = detail.name ?? ''
    form.value.is_active = Boolean(detail.is_active)
    
    if (detail.items) {
      form.value.items = detail.items.map((item, index) => ({
        id: item.id,
        medical_procedure_id: item.medical_procedure_id,
        procedure: item.procedure || null,
        medical_procedure_tariff_id: item.medical_procedure_tariff_id,
        tariff: item.tariff || null,
        quantity: Number(item.quantity ?? 1),
        unit_amount: Number(item.unit_amount ?? 0),
        subtotal_amount: Number(item.subtotal_amount ?? 0),
        sort_order: item.sort_order || index + 1,
        _available_tariffs: [],
        _key: Date.now() + Math.random()
      }))

      for (const item of form.value.items) {
        if (item.medical_procedure_id) {
          await fetchProcedureDetailForTariffs(item, item.medical_procedure_id)
        }
      }
    } else {
      form.value.items = []
    }
  } catch (error) {
    console.error(error)
    detailError.value = 'Gagal memuat detail paket.'
  } finally {
    loadingDetail.value = false
    isInitializingEdit.value = false
  }
}

watch(
  () => [props.modelValue, props.mode, props.packageId],
  async ([open, mode, id]) => {
    if (!open) return

    resetForm()

    if (mode === 'edit' && id) {
      await loadPackageDetail(id)
    }
  }
)

const close = () => {
  emit('update:modelValue', false)
}

const save = async () => {
  const validItems = form.value.items.filter(item => item.medical_procedure_id)
  
  if (validItems.length === 0) {
    errors.value = { items: ['Paket harus memiliki minimal satu tindakan yang valid'] }
    return
  }

  loading.value = true
  errors.value = {}
  
  const payload = {
    ...form.value,
    items: validItems
  }
  
  try {
    if (props.mode === 'edit') {
      await ProcedurePackageService.updatePackage(props.packageId, payload)
      alert('Paket berhasil diperbarui')
    } else {
      await ProcedurePackageService.createPackage(payload)
      alert('Paket berhasil ditambahkan')
    }
    emit('saved')
  } catch (error) {
    if (error.response && error.response.status === 422) {
      errors.value = error.response.data.errors || {}
      alert('Periksa kembali inputan Anda')
    } else {
      alert('Terjadi kesalahan pada server')
    }
  } finally {
    loading.value = false
  }
}
</script>

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
  z-index: 1000;
  backdrop-filter: blur(2px);
}

.modal-container {
  background: #ffffff;
  border-radius: 12px;
  width: 90%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
.modal-xl {
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
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.modal-close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  color: #64748b;
  cursor: pointer;
  padding: 0;
  transition: color 0.2s;
}

.modal-close:hover {
  color: #0f172a;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Grid layout utility */
.grid { display: grid; }
.grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.gap-4 { gap: 1rem; }
.gap-3 { gap: 0.75rem; }
.mt-4 { margin-top: 1rem; }
.mt-6 { margin-top: 1.5rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-1 { margin-top: 0.25rem; }
.pt-4 { padding-top: 1rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-3 { margin-bottom: 0.75rem; }
.mb-4 { margin-bottom: 1rem; }
.flex { display: flex; }
.justify-between { justify-content: space-between; }
.items-center { align-items: center; }

.section-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin-top: 0;
  margin-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 0.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.4rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--color-text-navy);
}

.required { color: #ef4444; }

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

.form-control-sm {
  padding: 0.4rem 0.6rem;
  font-size: 0.85rem;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.1);
}

.is-invalid {
  border-color: #f87171 !important;
}

.error-feedback {
  color: #ef4444;
  font-size: 0.8rem;
  margin-top: 0.25rem;
  display: block;
}

.text-navy { color: var(--color-text-navy); }
.font-bold { font-weight: 700; }
.text-sm { font-size: 0.875rem; }
.text-xs { font-size: 0.75rem; }
.text-gray-500 { color: #64748b; }
.text-right { text-align: right !important; }
.text-center { text-align: center !important; }
.text-primary { color: var(--color-primary); }

/* Buttons */
.btn {
  padding: 0.6rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  font-family: inherit;
  border: 1px solid transparent;
}

.btn-sm {
  padding: 0.4rem 0.8rem;
  font-size: 0.8rem;
}

.btn-primary {
  background: var(--color-primary);
  color: #ffffff;
}
.btn-primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
}
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
  border-color: #cbd5e1;
}
.btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-icon {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.4rem;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
  font-size: 1.25rem;
  line-height: 1;
}

.btn-icon:hover { background: #f1f5f9; }
.text-red { color: #ef4444; }
.bg-slate-50 { background-color: #f8fafc; }
</style>
