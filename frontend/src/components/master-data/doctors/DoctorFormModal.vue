<script setup>
import { ref, reactive, watch } from 'vue';
import axios from '@/utils/axios';
import AsyncEmployeeSelect from '@/components/common/AsyncEmployeeSelect.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  doctor: {
    type: Object,
    default: null
  },
  isSubmitting: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'submit']);

const form = reactive({
  employee_id: null,
  specialization_id: null,
  str_number: '',
  sip_number: '',
  sip_valid_until: '',
  bpjs_dpjp_code: '',
  ihs_number: '',
  is_active: true,
  signature: null,
  remove_signature: false,
});

const specializations = ref([]);
const employeeSelectRef = ref(null);
const fileInput = ref(null);
const previewImage = ref(null);

const fetchSpecializations = async () => {
  try {
    const response = await axios.get('/lookups/specializations');
    specializations.value = response.data.data || response.data;
  } catch (err) {
    console.error('Failed to load specializations', err);
  }
};

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.doctor) {
      form.employee_id = props.doctor.employee_id;
      form.specialization_id = props.doctor.specialization_id || null;
      form.str_number = props.doctor.str_number || '';
      form.sip_number = props.doctor.sip_number || '';
      form.sip_valid_until = props.doctor.sip_valid_until || '';
      form.bpjs_dpjp_code = props.doctor.bpjs_dpjp_code || '';
      form.ihs_number = props.doctor.ihs_number || '';
      form.is_active = props.doctor.is_active;
      form.signature = null;
      form.remove_signature = false;
      previewImage.value = props.doctor.signature_url || null;

      setTimeout(() => {
        if (employeeSelectRef.value) {
          employeeSelectRef.value.setInitialOptions({
            id: props.doctor.employee_id,
            name: props.doctor.name,
            code: props.doctor.nik,
            profession: 'Dokter'
          });
        }
      }, 100);
    } else {
      form.employee_id = null;
      form.specialization_id = null;
      form.str_number = '';
      form.sip_number = '';
      form.sip_valid_until = '';
      form.bpjs_dpjp_code = '';
      form.ihs_number = '';
      form.is_active = true;
      form.signature = null;
      form.remove_signature = false;
      previewImage.value = null;
      if (employeeSelectRef.value) {
        employeeSelectRef.value.setInitialOptions(null);
      }
    }
    fetchSpecializations();
  }
});

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.signature = file;
    form.remove_signature = false;
    const reader = new FileReader();
    reader.onload = e => {
      previewImage.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const removeSignature = () => {
  form.signature = null;
  form.remove_signature = true;
  previewImage.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const handleSubmit = () => {
  emit('submit', { ...form });
};
</script>

<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">{{ doctor ? 'Edit' : 'Tambah' }} Dokter</h2>
        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup modal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <div v-if="errors.general" class="alert alert-danger">
          {{ errors.general }}
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label required">Pegawai</label>
            <AsyncEmployeeSelect
              v-model="form.employee_id"
              placeholder="Cari pegawai..."
              :multiple="false"
              :error="!!errors.employee_id"
              ref="employeeSelectRef"
            />
            <span v-if="errors.employee_id" class="error-message">{{ errors.employee_id[0] }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Spesialisasi</label>
            <select v-model="form.specialization_id" class="form-input" :class="{ 'has-error': errors.specialization_id }">
              <option :value="null">Pilih Spesialisasi</option>
              <option v-for="spec in specializations" :key="spec.id" :value="spec.id">
                {{ spec.name }}
              </option>
            </select>
            <span v-if="errors.specialization_id" class="error-message">{{ errors.specialization_id[0] }}</span>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">No. STR</label>
            <input 
              type="text" 
              v-model="form.str_number" 
              class="form-input"
              :class="{ 'has-error': errors.str_number }"
            >
            <span v-if="errors.str_number" class="error-message">{{ errors.str_number[0] }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">No. SIP</label>
            <input 
              type="text" 
              v-model="form.sip_number" 
              class="form-input"
              :class="{ 'has-error': errors.sip_number }"
            >
            <span v-if="errors.sip_number" class="error-message">{{ errors.sip_number[0] }}</span>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Berlaku SIP (Sampai)</label>
            <input 
              type="date" 
              v-model="form.sip_valid_until" 
              class="form-input"
              :class="{ 'has-error': errors.sip_valid_until }"
            >
            <span v-if="errors.sip_valid_until" class="error-message">{{ errors.sip_valid_until[0] }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Kode BPJS DPJP</label>
            <input 
              type="text" 
              v-model="form.bpjs_dpjp_code" 
              class="form-input"
              :class="{ 'has-error': errors.bpjs_dpjp_code }"
            >
            <span v-if="errors.bpjs_dpjp_code" class="error-message">{{ errors.bpjs_dpjp_code[0] }}</span>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">IHS Number (SatuSehat)</label>
            <input 
              type="text" 
              v-model="form.ihs_number" 
              class="form-input"
              :class="{ 'has-error': errors.ihs_number }"
            >
            <span v-if="errors.ihs_number" class="error-message">{{ errors.ihs_number[0] }}</span>
          </div>
          
          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.is_active" class="form-checkbox">
              <span class="label-text">Aktif</span>
            </label>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Tanda Tangan</label>
          <input 
            type="file" 
            ref="fileInput"
            @change="handleFileChange" 
            class="form-input"
            accept="image/png, image/jpeg, image/jpg"
            :class="{ 'has-error': errors.signature }"
          >
          <span v-if="errors.signature" class="error-message">{{ errors.signature[0] }}</span>
          
          <div v-if="previewImage" class="signature-preview">
            <img :src="previewImage" alt="Preview Tanda Tangan" />
            <button type="button" class="btn-remove-sig" @click="removeSignature">Hapus</button>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-outline" @click="emit('close')" :disabled="isSubmitting">Batal</button>
          <button type="submit" class="btn-primary" :disabled="isSubmitting || !form.employee_id">
            <template v-if="isSubmitting">
              <span class="spinner"></span> Menyimpan...
            </template>
            <template v-else>Simpan</template>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 1rem;
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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

.btn-close {
  background: transparent;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #f1f5f9;
  color: var(--color-text-navy);
}

.btn-close svg {
  width: 20px;
  height: 20px;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.alert-danger {
  background: #fef2f2;
  color: #991b1b;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  margin-bottom: 1.25rem;
  font-size: 0.9rem;
  border: 1px solid #fecaca;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
  margin-bottom: 1.25rem;
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

.form-label.required::after {
  content: '*';
  color: var(--color-danger);
  margin-left: 0.25rem;
}

.form-input {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  font-size: 0.9rem;
  font-family: inherit;
  transition: border-color 0.2s;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.form-input.has-error {
  border-color: var(--color-danger);
}

.error-message {
  display: block;
  color: var(--color-danger);
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.checkbox-group {
  display: flex;
  align-items: center;
  margin-top: 1.8rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.form-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.label-text {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--color-text-navy);
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-primary, .btn-outline {
  padding: 0.6rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-primary {
  background: var(--color-primary);
  color: #ffffff;
  border: none;
}

.btn-primary:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-outline {
  background: transparent;
  color: var(--color-text-navy);
  border: 1px solid var(--color-border);
}

.btn-outline:hover:not(:disabled) {
  background: #f8fafc;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.8s linear infinite;
}

.signature-preview {
  margin-top: 10px;
  border: 1px dashed var(--color-border);
  padding: 10px;
  display: inline-block;
  text-align: center;
  border-radius: 6px;
}

.signature-preview img {
  max-width: 200px;
  max-height: 100px;
  display: block;
}

.btn-remove-sig {
  margin-top: 5px;
  background: var(--color-danger);
  color: white;
  border: none;
  padding: 2px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.8rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
    gap: 0;
  }
}
</style>
