<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  isOpen: Boolean,
  editData: {
    type: Object,
    default: null
  },
  loading: Boolean,
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'save']);

const form = reactive({
  name: '',
  description: '',
  is_active: true
});

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.editData) {
      form.name = props.editData.name || '';
      form.description = props.editData.description || '';
      form.is_active = props.editData.is_active ?? true;
    } else {
      form.name = '';
      form.description = '';
      form.is_active = true;
    }
  }
}, { immediate: true });

const handleSubmit = () => {
  const payload = {
    ...form,
    name: form.name.trim(),
    description: form.description ? form.description.trim() : null
  };
  emit('save', payload);
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-backdrop">
      <div class="modal-container" role="dialog" aria-modal="true" :aria-labelledby="editData ? 'edit-component-title' : 'add-component-title'">
        <div class="modal-header">
          <h2 class="modal-title" :id="editData ? 'edit-component-title' : 'add-component-title'">
            {{ editData ? 'Edit Komponen' : 'Tambah Komponen' }}
          </h2>
          <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <form id="componentForm" @submit.prevent="handleSubmit">
            
            <div v-if="errors.general" class="alert alert-danger mb-4">
              {{ errors.general }}
            </div>
            
            <div class="form-group">
              <label for="name" class="form-label">Nama Komponen <span class="text-danger">*</span></label>
              <input 
                type="text" 
                id="name" 
                v-model="form.name" 
                class="form-control" 
                :class="{ 'is-invalid': errors.name }"
                required
                maxlength="255"
              />
              <div v-if="errors.name" class="invalid-feedback">
                {{ errors.name[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="description" class="form-label">Deskripsi</label>
              <textarea 
                id="description" 
                v-model="form.description" 
                class="form-control" 
                :class="{ 'is-invalid': errors.description }"
                rows="3"
              ></textarea>
              <div v-if="errors.description" class="invalid-feedback">
                {{ errors.description[0] }}
              </div>
            </div>

          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="emit('close')" :disabled="loading">
            Batal
          </button>
          <button type="submit" form="componentForm" class="btn btn-primary" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
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
  max-width: 500px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
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
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.1);
}

.form-control.is-invalid {
  border-color: #dc2626;
}

.form-control.is-invalid:focus {
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
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

.mb-4 {
  margin-bottom: 1rem;
}

.alert-danger {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #f87171;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
