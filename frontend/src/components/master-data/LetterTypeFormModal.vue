<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="closeModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">{{ isEdit ? 'Edit Surat' : 'Tambah Surat' }}</h2>
        <button class="btn-close" @click="closeModal" aria-label="Tutup">&times;</button>
      </div>
      
      <form @submit.prevent="submitForm">
        <div class="modal-body">
          <div class="form-group">
            <label for="name" class="form-label">Nama Surat <span class="required">*</span></label>
            <input 
              type="text" 
              id="name" 
              v-model="form.name" 
              class="form-input" 
              :class="{ 'is-invalid': errors.name }"
              placeholder="Masukkan nama surat"
              :disabled="loading"
            />
            <span v-if="errors.name" class="error-message">{{ errors.name[0] }}</span>
          </div>

          <div class="form-group">
            <label for="description" class="form-label">Deskripsi</label>
            <textarea 
              id="description" 
              v-model="form.description" 
              class="form-input form-textarea" 
              :class="{ 'is-invalid': errors.description }"
              placeholder="Masukkan deskripsi (opsional)"
              :disabled="loading"
              rows="3"
            ></textarea>
            <span v-if="errors.description" class="error-message">{{ errors.description[0] }}</span>
          </div>
          
          <div class="form-group" v-if="isEdit && form.legacy_resource">
            <label class="form-label">Resource Legacy</label>
            <input 
              type="text" 
              :value="form.legacy_resource" 
              class="form-input bg-gray-100" 
              disabled
            />
            <small class="text-gray-500">Resource ini berasal dari sistem lama dan tidak dapat diubah (hanya read-only metadata).</small>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal" :disabled="loading">Batal</button>
          <button type="submit" class="btn btn-primary" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            {{ loading ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Simpan') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  letterTypeData: {
    type: Object,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
  name: '',
  description: '',
  legacy_resource: null,
  is_active: true
});

const isEdit = ref(false);

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.letterTypeData) {
      isEdit.value = true;
      form.value = {
        name: props.letterTypeData.name,
        description: props.letterTypeData.description || '',
        legacy_resource: props.letterTypeData.legacy_resource || null,
        is_active: props.letterTypeData.is_active
      };
    } else {
      isEdit.value = false;
      form.value = {
        name: '',
        description: '',
        legacy_resource: null,
        is_active: true
      };
    }
  }
});

const closeModal = () => {
  emit('close');
};

const submitForm = () => {
  emit('submit', form.value);
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  backdrop-filter: blur(2px);
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin: 0;
}

.btn-close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
  transition: color 0.2s;
}

.btn-close:hover {
  color: #ef4444;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group.flex-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0;
  margin-top: 1.5rem;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy);
  margin-bottom: 0.5rem;
}

.required {
  color: #ef4444;
}

.form-input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.875rem;
  color: var(--color-text-navy);
  transition: all 0.2s;
  font-family: inherit;
}

.form-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.1);
}

.form-input:disabled {
  background-color: #f1f5f9;
  cursor: not-allowed;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.is-invalid {
  border-color: #ef4444;
}

.is-invalid:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.error-message {
  display: block;
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.375rem;
}

.bg-gray-100 {
  background-color: #f3f4f6;
}

.text-gray-500 {
  color: #6b7280;
  font-size: 0.75rem;
  display: block;
  margin-top: 0.25rem;
}

/* Toggle Switch */
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

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
}

input:checked + .slider {
  background-color: var(--color-primary);
}

input:focus + .slider {
  box-shadow: 0 0 1px var(--color-primary);
}

input:checked + .slider:before {
  transform: translateX(20px);
}

.slider.round {
  border-radius: 24px;
}

.slider.round:before {
  border-radius: 50%;
}

input:disabled + .slider {
  opacity: 0.5;
  cursor: not-allowed;
}

.toggle-label {
  font-size: 0.875rem;
  color: var(--color-text-navy);
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  background: #f8fafc;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}

.btn {
  padding: 0.625rem 1.25rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #475569;
}

.btn-secondary:hover:not(:disabled) {
  background: #f1f5f9;
  color: #0f172a;
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
  border-top-color: #ffffff;
  animation: spin 0.8s linear infinite;
  margin-right: 0.5rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
