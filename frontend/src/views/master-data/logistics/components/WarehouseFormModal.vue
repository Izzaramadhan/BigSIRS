<script setup>
import { reactive, watch } from 'vue';
import { useWarehouses } from '@/composables/useWarehouses';

const props = defineProps({
  isOpen: Boolean,
  warehouse: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const { createWarehouse, updateWarehouse, error } = useWarehouses();

const form = reactive({
  name: '',
  code: '',
  description: '',
});

const state = reactive({
  submitting: false,
  errors: {}
});

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    state.errors = {};
    if (props.warehouse) {
      form.name = props.warehouse.name || '';
      form.code = props.warehouse.code && props.warehouse.code !== '-' ? props.warehouse.code : '';
      form.description = props.warehouse.description && props.warehouse.description !== '-' ? props.warehouse.description : '';
    } else {
      form.name = '';
      form.code = '';
      form.description = '';
    }
  }
}, { immediate: true });

const handleSubmit = async () => {
  state.submitting = true;
  state.errors = {};

  const payload = {
    name: form.name.trim(),
    code: form.code && form.code.trim() ? form.code.trim() : null,
    description: form.description && form.description.trim() ? form.description.trim() : null,
  };

  try {
    let success = false;
    if (props.warehouse) {
      success = await updateWarehouse(props.warehouse.id, payload);
    } else {
      success = await createWarehouse(payload);
    }
    
    if (success) {
      emit('saved');
      emit('close');
    } else if (error.value && typeof error.value === 'object') {
        state.errors = error.value;
    }
  } catch (err) {
    if (err.response && err.response.status === 422) {
      state.errors = err.response.data.errors;
    } else {
      console.error(err);
    }
  } finally {
    state.submitting = false;
  }
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-backdrop">
      <div class="modal-container" role="dialog" aria-modal="true" :aria-labelledby="warehouse ? 'edit-warehouse-title' : 'add-warehouse-title'">
        <div class="modal-header">
          <h2 class="modal-title" :id="warehouse ? 'edit-warehouse-title' : 'add-warehouse-title'">
            {{ warehouse ? 'Edit Gudang' : 'Tambah Gudang' }}
          </h2>
          <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <form id="warehouseForm" @submit.prevent="handleSubmit">
            
            <div class="form-group">
              <label for="name" class="form-label">Nama Gudang <span class="text-danger">*</span></label>
              <input 
                type="text" 
                id="name" 
                v-model="form.name" 
                class="form-control" 
                :class="{ 'is-invalid': state.errors.name }"
                required
                maxlength="255"
              />
              <div v-if="state.errors.name" class="invalid-feedback">
                {{ state.errors.name[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="code" class="form-label">Kode Gudang</label>
              <input 
                type="text" 
                id="code" 
                v-model="form.code" 
                class="form-control" 
                :class="{ 'is-invalid': state.errors.code }"
                maxlength="50"
              />
              <div v-if="state.errors.code" class="invalid-feedback">
                {{ state.errors.code[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="description" class="form-label">Deskripsi</label>
              <textarea 
                id="description" 
                v-model="form.description" 
                class="form-control" 
                :class="{ 'is-invalid': state.errors.description }"
                rows="3"
              ></textarea>
              <div v-if="state.errors.description" class="invalid-feedback">
                {{ state.errors.description[0] }}
              </div>
            </div>

          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="emit('close')" :disabled="state.submitting">
            Batal
          </button>
          <button type="submit" form="warehouseForm" class="btn btn-primary" :disabled="state.submitting">
            <span v-if="state.submitting" class="spinner"></span>
            Simpan
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
  background-color: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
  animation: fadeIn 0.2s ease-out;
}

.modal-container {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  animation: slideUp 0.3s ease-out;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.btn-close {
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-close svg {
  width: 20px;
  height: 20px;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #ef4444;
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

.form-label {
  display: block;
  font-weight: 500;
  color: #475569;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.text-danger {
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 0.9375rem;
  line-height: 1.5;
  color: #1e293b;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
  outline: 0;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-control.is-invalid {
  border-color: #ef4444;
}

.form-control.is-invalid:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

textarea.form-control {
  min-height: 100px;
  resize: vertical;
}

.invalid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #ef4444;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  background-color: #f8fafc;
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 500;
  padding: 0.625rem 1.25rem;
  font-size: 0.875rem;
  border-radius: 8px;
  transition: all 0.2s;
  cursor: pointer;
  border: 1px solid transparent;
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  color: #475569;
  background-color: white;
  border-color: #cbd5e1;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #f1f5f9;
  color: #1e293b;
}

.btn-primary {
  color: white;
  background-color: #3b82f6;
  box-shadow: 0 1px 2px rgba(59, 130, 246, 0.2);
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3), 0 2px 4px -1px rgba(59, 130, 246, 0.2);
}

.spinner {
  display: inline-block;
  width: 1rem;
  height: 1rem;
  margin-right: 0.5rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { 
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to { 
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
