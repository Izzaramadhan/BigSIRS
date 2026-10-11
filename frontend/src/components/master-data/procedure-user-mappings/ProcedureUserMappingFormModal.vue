<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useProcedureUserMappings } from '@/composables/useProcedureUserMappings'
import ProcedureUserMappingService from '@/services/master-data/procedure-user-mappings.service'
import AsyncProcedureSelect from '@/components/common/AsyncProcedureSelect.vue'
import AsyncEmployeeSelect from '@/components/common/AsyncEmployeeSelect.vue'
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: true
  },
  modelValue: {
    type: Boolean,
    default: undefined
  },
  id: {
    type: [Number, String],
    default: null
  },
  item: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved', 'update:modelValue'])

const isModalOpen = computed(() => {
  return props.modelValue !== undefined ? props.modelValue : props.isOpen
})

const isEditMode = computed(() => !!props.id)

const { createMapping, updateMapping, submitting } = useProcedureUserMappings()

const form = ref({
  procedure_id: '',
  employee_ids: []
})

const initialProcedure = ref(null)
const formErrors = ref({})
const generalError = ref('')
const loadingDetail = ref(false)
const employeeSelectRef = ref(null)

const resetForm = () => {
  form.value = {
    procedure_id: '',
    employee_ids: []
  }
  initialProcedure.value = null
  formErrors.value = {}
  generalError.value = ''
  if (employeeSelectRef.value) {
    employeeSelectRef.value.setInitialOptions([])
  }
}

const loadDetail = async (id) => {
  loadingDetail.value = true
  generalError.value = ''
  try {
    const data = await ProcedureUserMappingService.getMapping(id)
    form.value.procedure_id = data.procedure?.id || id
    initialProcedure.value = data.procedure || null
    form.value.employee_ids = data.employees?.map(e => e.id) || []
    
    await nextTick()
    if (employeeSelectRef.value && data.employees) {
      employeeSelectRef.value.setInitialOptions(data.employees)
    }
  } catch (err) {
    console.error('Error loading detail mapping:', err)
    generalError.value = err.response?.data?.message || 'Gagal memuat detail mapping tindakan user.'
  } finally {
    loadingDetail.value = false
  }
}

watch(
  () => [isModalOpen.value, props.id],
  async ([open, currentId]) => {
    if (open) {
      resetForm()
      if (currentId) {
        if (props.item && props.item.id === currentId) {
          form.value.procedure_id = props.item.procedure?.id || currentId
          initialProcedure.value = props.item.procedure || null
          form.value.employee_ids = props.item.employees?.map(e => e.id) || []
          await nextTick()
          if (employeeSelectRef.value && props.item.employees) {
            employeeSelectRef.value.setInitialOptions(props.item.employees)
          }
        }
        await loadDetail(currentId)
      }
    } else {
      resetForm()
    }
  },
  { immediate: true }
)

const validateForm = () => {
  const errors = {}
  if (!form.value.procedure_id) {
    errors.procedure_id = 'Tindakan wajib dipilih.'
  }
  if (!form.value.employee_ids || form.value.employee_ids.length === 0) {
    errors.employee_ids = 'Minimal satu user / pegawai wajib dipilih.'
  }
  formErrors.value = errors
  return Object.keys(errors).length === 0
}

const handleClose = () => {
  if (submitting.value) return
  resetForm()
  emit('close')
  emit('update:modelValue', false)
}

const handleSubmit = async () => {
  generalError.value = ''
  formErrors.value = {}

  if (!validateForm()) return

  const payload = {
    procedure_id: form.value.procedure_id,
    employee_ids: form.value.employee_ids
  }

  let result
  if (isEditMode.value) {
    result = await updateMapping(props.id, payload)
  } else {
    result = await createMapping(payload)
  }

  if (result.success) {
    emit('saved', isEditMode.value ? 'edit' : 'create')
    handleClose()
  } else {
    const err = result.error
    if (err.response?.status === 422) {
      const respErrors = err.response.data?.errors
      if (respErrors) {
        formErrors.value = {
          procedure_id: respErrors.procedure_id ? respErrors.procedure_id[0] : null,
          employee_ids: respErrors.employee_ids ? respErrors.employee_ids[0] : null
        }
      }
      generalError.value = err.response.data?.message || 'Data yang dimasukkan tidak valid.'
    } else {
      generalError.value = err.response?.data?.message || 'Terjadi kesalahan sistem saat menyimpan data.'
    }
  }
}
</script>

<template>
  <MasterDataFormModal
    :is-open="isModalOpen"
    :title="isEditMode ? 'Edit Mapping Tindakan User' : 'Tambah Mapping Tindakan User'"
    :is-submitting="submitting"
    :is-submit-disabled="loadingDetail || (!isEditMode && !form.procedure_id) || form.employee_ids.length === 0"
    :submit-text="isEditMode ? 'Simpan Perubahan' : 'Simpan'"
    size="md"
    @close="handleClose"
    @submit="handleSubmit"
  >
    <div v-if="loadingDetail" class="text-center py-8 text-secondary">
      Memuat detail mapping...
    </div>

    <div v-else class="modal-form-content">
      <div v-if="generalError" class="alert alert-danger mb-4" role="alert">
        {{ generalError }}
      </div>

      <!-- Tindakan -->
      <div class="form-group">
        <label class="form-label required">Tindakan</label>
        <AsyncProcedureSelect 
          v-model="form.procedure_id"
          :initial-procedure="initialProcedure"
          placeholder="Cari tindakan..."
          :error="!!formErrors.procedure_id"
          :disabled="isEditMode"
        />
        <span v-if="formErrors.procedure_id" class="error-message">
          {{ formErrors.procedure_id }}
        </span>
        <span v-if="isEditMode" class="form-hint">
          Tindakan tidak dapat diubah pada mode edit. Hapus mapping jika salah.
        </span>
      </div>

      <!-- User / Pegawai -->
      <div class="form-group">
        <label class="form-label required">User (Pegawai Medis / Paramedis)</label>
        <AsyncEmployeeSelect 
          ref="employeeSelectRef"
          v-model="form.employee_ids"
          placeholder="Cari nama atau NIP pegawai..."
          :error="!!formErrors.employee_ids"
          :multiple="true"
        />
        <span v-if="formErrors.employee_ids" class="error-message">
          {{ formErrors.employee_ids }}
        </span>
        <span class="form-hint">
          Dapat memilih lebih dari satu pegawai untuk tindakan ini.
        </span>
      </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.modal-form-content {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  margin-bottom: 0;
}

.form-label {
  display: block;
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--color-text-navy, #0f172a);
  margin-bottom: 0.5rem;
}

.form-label.required::after {
  content: ' *';
  color: var(--color-danger, #ef4444);
}

.form-hint {
  display: block;
  font-size: 0.8rem;
  color: var(--color-text-secondary, #64748b);
  margin-top: 0.35rem;
}

.error-message {
  display: block;
  font-size: 0.8rem;
  color: var(--color-danger, #ef4444);
  margin-top: 0.35rem;
}

.alert {
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

.alert-danger {
  background-color: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.text-center {
  text-align: center;
}

.text-secondary {
  color: #64748b;
}

.py-8 {
  padding-top: 2rem;
  padding-bottom: 2rem;
}

.mb-4 {
  margin-bottom: 1rem;
}
</style>
