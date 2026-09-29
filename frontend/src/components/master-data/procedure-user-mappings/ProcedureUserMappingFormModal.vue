<script setup>
import { ref, computed, onMounted } from 'vue'
import { useProcedureUserMappings } from '@/composables/useProcedureUserMappings'
import ProcedureUserMappingService from '@/services/master-data/procedure-user-mappings.service'
import AsyncProcedureSelect from '@/components/common/AsyncProcedureSelect.vue'
import AsyncEmployeeSelect from '@/components/common/AsyncEmployeeSelect.vue'

const props = defineProps({
  id: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const { createMapping, updateMapping, loading, error } = useProcedureUserMappings()

const isEditMode = computed(() => !!props.id)

const form = ref({
  procedure_id: '',
  employee_ids: []
})

const formErrors = ref({})
const employeeSelectRef = ref(null)

const validateForm = () => {
  const errors = {}
  if (!form.value.procedure_id) errors.procedure_id = 'Tindakan wajib dipilih'
  if (!form.value.employee_ids || form.value.employee_ids.length === 0) {
    errors.employee_ids = 'Minimal satu pegawai wajib dipilih'
  }
  formErrors.value = errors
  return Object.keys(errors).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return

  try {
    if (isEditMode.value) {
      await updateMapping(props.id, form.value)
    } else {
      await createMapping(form.value)
    }
    emit('saved')
  } catch {
    // Error is handled in composable, but we might want to catch it to stop modal closing
  }
}

onMounted(async () => {
  if (isEditMode.value) {
    try {
      loading.value = true
      const data = await ProcedureUserMappingService.getMapping(props.id)
      form.value.procedure_id = data.procedure.id
      form.value.employee_ids = data.employees.map(e => e.id)
      
      // Inject selected employees data to employeeSelectRef
      if (employeeSelectRef.value) {
        employeeSelectRef.value.setInitialOptions(data.employees)
      }
    } catch (err) {
      console.error(err)
    } finally {
      loading.value = false
    }
  }
})
</script>

<template>
  <div class="modal-backdrop">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title">
            {{ isEditMode ? 'Edit Mapping Tindakan' : 'Tambah Mapping Tindakan' }}
          </h3>
          <button class="close-btn" @click="$emit('close')">×</button>
        </div>
        
        <div class="modal-body">
          <div v-if="error" class="alert alert-danger mb-4">
            {{ error }}
          </div>

          <form @submit.prevent="handleSubmit" class="form">
            <div class="form-group">
              <label class="form-label">Tindakan <span class="required">*</span></label>
              <AsyncProcedureSelect 
                v-model="form.procedure_id"
                placeholder="Pilih Tindakan..."
                :error="!!formErrors.procedure_id"
                :disabled="isEditMode"
              />
              <span v-if="formErrors.procedure_id" class="error-text">{{ formErrors.procedure_id }}</span>
              <span v-if="isEditMode" class="help-text text-secondary mt-1">Tindakan tidak dapat diubah pada mode edit. Hapus mapping jika salah.</span>
            </div>

            <div class="form-group">
              <label class="form-label">Pegawai (Medis / Paramedis) <span class="required">*</span></label>
              <AsyncEmployeeSelect 
                ref="employeeSelectRef"
                v-model="form.employee_ids"
                placeholder="Cari dan pilih pegawai..."
                :error="!!formErrors.employee_ids"
                :multiple="true"
              />
              <span v-if="formErrors.employee_ids" class="error-text">{{ formErrors.employee_ids }}</span>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="$emit('close')" :disabled="loading">
            Batal
          </button>
          <button class="btn btn-primary" @click="handleSubmit" :disabled="loading">
            {{ loading ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease;
}

.modal-dialog {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: slideUp 0.3s ease;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.modal-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: color 0.2s;
}

.close-btn:hover {
  color: var(--color-danger);
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin-bottom: 0.5rem;
}

.required {
  color: var(--color-danger);
}

.error-text {
  color: var(--color-danger);
  font-size: 0.8rem;
  margin-top: 0.3rem;
  display: block;
}

.help-text {
  font-size: 0.75rem;
  display: block;
}

.mt-1 {
  margin-top: 0.25rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  background: #f8fafc;
  border-radius: 0 0 12px 12px;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
