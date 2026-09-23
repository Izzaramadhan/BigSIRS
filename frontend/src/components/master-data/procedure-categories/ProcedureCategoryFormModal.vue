<script setup>
import { reactive, watch } from 'vue';
import { procedureCategoryService } from '@/services/procedureCategory';

const props = defineProps({
  isOpen: Boolean,
  category: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const form = reactive({
  name: '',
  description: '',
  is_active: true
});

const state = reactive({
  submitting: false,
  errors: {}
});

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    state.errors = {};
    if (props.category) {
      form.name = props.category.name || '';
      form.description = props.category.description || '';
      form.is_active = props.category.is_active ?? true;
    } else {
      form.name = '';
      form.description = '';
      form.is_active = true;
    }
  }
}, { immediate: true });

const handleSubmit = async () => {
  state.submitting = true;
  state.errors = {};

  const payload = {
    name: form.name.trim(),
    description: form.description && form.description.trim() ? form.description.trim() : null,
    is_active: form.is_active
  };

  try {
    if (props.category) {
      await procedureCategoryService.updateProcedureCategory(props.category.id, payload);
    } else {
      await procedureCategoryService.createProcedureCategory(payload);
    }
    emit('saved');
    emit('close');
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
      <div class="modal-container" role="dialog" aria-modal="true" :aria-labelledby="category ? 'edit-category-title' : 'add-category-title'">
        <div class="modal-header">
          <h2 class="modal-title" :id="category ? 'edit-category-title' : 'add-category-title'">
            {{ category ? 'Edit Kategori Tindakan' : 'Tambah Kategori Tindakan' }}
          </h2>
          <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <form id="categoryForm" @submit.prevent="handleSubmit">
            
            <div class="form-group">
              <label for="name" class="form-label">Kategori Tindakan <span class="text-danger">*</span></label>
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
          <button type="submit" form="categoryForm" class="btn btn-primary" :disabled="state.submitting">
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

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
