<template>
  <div>
    <MasterDataFormModal
      :is-open="modelValue"
      :title="mode === 'edit' ? 'Edit Paket Tindakan' : 'Tambah Paket Tindakan'"
      :is-submitting="loading"
      :is-submit-disabled="loading || isInitializingEdit || form.items.length === 0"
      :submit-text="mode === 'edit' ? 'Simpan Perubahan' : 'Simpan'"
      size="xl"
      @close="close"
      @submit="save"
    >
      <div v-if="loadingDetail" class="text-center py-8 text-gray-500">
        Memuat data paket...
      </div>
      <div v-else-if="detailError" class="text-center py-8">
        <p class="text-danger mb-4">{{ detailError }}</p>
        <button type="button" class="btn-primary" @click="loadPackageDetail(packageId)">Coba Lagi</button>
      </div>
      <div v-else class="form-layout">
        <!-- Header -->
        <div class="form-group">
          <label for="name" class="form-label required">Nama Paket</label>
          <input 
            id="name" 
            class="form-input" 
            :class="{ 'has-error': errors.name }"
            type="text" 
            v-model="form.name" 
            placeholder="Contoh: Paket MCU Dasar"
            required 
          />
          <span v-if="errors.name" class="error-message">{{ errors.name[0] }}</span>
        </div>

        <!-- Items -->
        <div class="items-section">
          <div class="items-header">
            <h4 class="section-title">Item Tindakan</h4>
            <button type="button" class="btn-outline btn-sm" @click="addItem">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              Tambah Tindakan
            </button>
          </div>

          <p v-if="errors.items" class="error-message mb-3">{{ errors.items[0] }}</p>

          <div class="items-list">
            <div v-for="(item, index) in form.items" :key="item._key" class="item-card">
              <div class="item-grid">
                <!-- Tindakan -->
                <div class="form-group mb-0">
                  <label class="form-label required">Tindakan</label>
                  <AsyncProcedureSelect 
                    v-model="item.medical_procedure_id"
                    :initialProcedure="item.procedure"
                    @change="(proc) => onProcedureChange(item, proc)"
                  />
                  <span v-if="errors[`items.${index}.medical_procedure_id`]" class="error-message">{{ errors[`items.${index}.medical_procedure_id`][0] }}</span>
                </div>

                <!-- Jenis Tarif -->
                <div class="form-group mb-0">
                  <label class="form-label">Jenis Tarif (Opsional)</label>
                  <select class="form-select custom-select-height" v-model="item.medical_procedure_tariff_id" @change="onTariffChange(item)" :disabled="!item.medical_procedure_id">
                    <option value="">Pilih jenis tarif</option>
                    <option v-for="tariff in item._available_tariffs || []" :key="tariff.id" :value="tariff.id">
                      {{ tariff.tariff_type?.name }} ({{ formatCurrency(tariff.total_amount) }})
                    </option>
                  </select>
                </div>

                <!-- Harga -->
                <div class="form-group mb-0">
                  <label class="form-label required">Harga Tindakan</label>
                  <input type="number" class="form-input custom-input-height" v-model="item.unit_amount" min="0" required @input="calculateSubtotal(item)" />
                </div>

                <!-- Aksi -->
                <div class="form-group mb-0 action-column">
                  <button 
                    type="button" 
                    class="btn-icon text-danger delete-btn" 
                    @click="requestRemoveItem(index, item)" 
                    title="Hapus item"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                  </button>
                </div>
              </div>
              
              <div class="item-footer">
                <div class="item-subtotal">
                  Subtotal: <span class="font-medium">{{ formatCurrency(item.subtotal_amount) }}</span>
                </div>
              </div>
            </div>

            <div v-if="form.items.length === 0" class="empty-items-state">
              Belum ada item tindakan. Klik "Tambah Tindakan".
            </div>
          </div>
        </div>

        <div class="total-section">
          <span class="total-label">Total Paket:</span>
          <span class="total-amount">{{ formatCurrency(calculateTotalAmount) }}</span>
        </div>
      </div>
    </MasterDataFormModal>

    <!-- Item Delete Confirmation Dialog -->
    <MasterDataDeleteDialog
      :is-open="itemToDelete !== null"
      title="Hapus tindakan dari paket?"
      :item-name="itemToDelete?.procedure?.name || 'Tindakan ini'"
      warning-message="Tindakan ini akan dikeluarkan dari paket setelah perubahan disimpan."
      @close="cancelRemoveItem"
      @confirm="confirmRemoveItem"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import ProcedurePackageService from '@/services/master-data/procedure-packages.service'
import MedicalProcedureService from '@/services/procedure'
import AsyncProcedureSelect from '@/components/common/AsyncProcedureSelect.vue'
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue'
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue'

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
    } else {
      await ProcedurePackageService.createPackage(payload)
    }
    emit('saved')
  } catch (error) {
    if (error.response && error.response.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      console.error(error)
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.form-layout {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.items-section {
  border-top: 1px solid var(--color-border-soft);
  padding-top: 1.5rem;
}

.items-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.section-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.btn-sm {
  padding: 0.4rem 0.8rem;
  font-size: 0.85rem;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.item-card {
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  padding: 1.25rem;
}

.item-grid {
  display: grid;
  grid-template-columns: minmax(0, 4fr) minmax(0, 3fr) minmax(0, 2.2fr) 44px;
  gap: 1rem;
  align-items: end;
}

.custom-select-height,
.custom-input-height {
  height: 36px;
  padding: 0.3rem 0.6rem;
  font-size: 0.85rem;
}

.action-column {
  display: flex;
  justify-content: flex-end;
}

.delete-btn {
  border-color: #fca5a5;
  background: #fef2f2;
  color: var(--color-danger);
}

.delete-btn:hover {
  background: #fee2e2;
}

.item-footer {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px dashed var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
}

.item-subtotal {
  font-size: 0.9rem;
  color: var(--color-text-navy);
}

.empty-items-state {
  text-align: center;
  padding: 2rem;
  color: var(--color-text-secondary);
  border: 1px dashed var(--color-border-soft);
  border-radius: 8px;
  background: #f8fafc;
}

.total-section {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 1rem;
  border-top: 1px solid var(--color-border-soft);
  padding-top: 1.25rem;
  margin-top: 0.5rem;
}

.total-label {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.total-amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.form-label.required::after {
  content: ' *';
  color: var(--color-danger);
}

@media (max-width: 768px) {
  .item-grid {
    grid-template-columns: 1fr;
    align-items: start;
    gap: 0.75rem;
  }
  
  .action-column {
    justify-content: flex-start;
  }
}
</style>
