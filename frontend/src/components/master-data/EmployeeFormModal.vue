<template>
  <Teleport to="body">
    <div v-if="isOpen" class="employee-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="employee-modal-title" @click.self="close">
      <div class="employee-modal-backdrop" @click="close"></div>
      
      <section class="employee-modal-panel large-modal">
        <header class="employee-modal-header modal-header">
          <div>
            <h3 id="employee-modal-title" class="modal-title">{{ isEditing ? 'Edit Pegawai' : 'Tambah Pegawai' }}</h3>
          <p class="modal-description text-gray-500 mt-1 mb-0">{{ isEditing ? 'Perbarui data pegawai yang dipilih.' : 'Lengkapi data identitas dan kepegawaian.' }}</p>
        </div>
        <button class="btn-close" @click="close" type="button" aria-label="Tutup">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </header>

      <form class="employee-form" @submit.prevent="submitForm">
      <div class="employee-modal-body modal-body">
        <div v-if="isLoadingData" class="flex-center py-12" style="flex-direction: column; height: 300px;">
          <div class="spinner" style="width: 2rem; height: 2rem; margin-bottom: 1rem; border-width: 3px; border-top-color: var(--color-primary);"></div>
          <p class="text-gray-500">Memuat detail pegawai...</p>
        </div>
        <template v-else>
        <!-- Error Alert -->
        <div v-if="error" class="alert alert-danger mb-4">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <div class="alert-content">
            <span class="alert-message">{{ typeof error === 'string' ? error : 'Terdapat kesalahan pada input Anda.' }}</span>
            <ul v-if="typeof error === 'object' && Object.keys(error).length > 0" class="error-list mt-2">
              <li v-for="(errMsgs, field) in error" :key="field">
                {{ errMsgs[0] }}
              </li>
            </ul>
          </div>
        </div>

        
          <h4 class="section-title mb-4">Identitas</h4>
            <div class="form-grid mb-6">
            <div class="form-group">
              <label for="national_id" class="form-label">NIK/No. KTP</label>
              <input 
                id="national_id" 
                v-model="form.national_id" 
                type="text" 
                class="form-input" 
                :class="{ 'is-invalid': hasError('national_id') }"
                placeholder="Masukkan NIK/No. KTP"
                maxlength="20"
              />
              <span v-if="hasError('national_id')" class="error-feedback">{{ getError('national_id') }}</span>
            </div>

            <div class="form-group">
              <label for="name" class="form-label">Nama Lengkap <span class="required-indicator">*</span></label>
              <input 
                id="name" 
                v-model="form.name" 
                type="text" 
                class="form-input" 
                :class="{ 'is-invalid': hasError('name') }"
                placeholder="Masukkan nama lengkap"
                required
              />
              <span v-if="hasError('name')" class="error-feedback">{{ getError('name') }}</span>
            </div>

            <div class="form-group">
              <label for="birth_place" class="form-label">Tempat Lahir</label>
              <input 
                id="birth_place" 
                v-model="form.birth_place" 
                type="text" 
                class="form-input" 
                :class="{ 'is-invalid': hasError('birth_place') }"
                placeholder="Masukkan tempat lahir"
              />
              <span v-if="hasError('birth_place')" class="error-feedback">{{ getError('birth_place') }}</span>
            </div>

            <div class="form-group">
              <label for="birth_date" class="form-label">Tanggal Lahir</label>
              <input 
                id="birth_date" 
                v-model="form.birth_date" 
                type="date" 
                class="form-input" 
                :class="{ 'is-invalid': hasError('birth_date') }"
              />
              <span v-if="hasError('birth_date')" class="error-feedback">{{ getError('birth_date') }}</span>
            </div>

            <div class="form-group">
              <label class="form-label">Jenis Kelamin</label>
              <div class="radio-group">
                <label v-for="opt in genderOptions" :key="opt.id" class="radio-label">
                  <input type="radio" v-model="form.gender" :value="opt.id" name="gender" />
                  <span>{{ opt.name }}</span>
                </label>
              </div>
              <span v-if="hasError('gender')" class="error-feedback">{{ getError('gender') }}</span>
            </div>
            
            <div class="form-group">
              <label class="form-label">Golongan Darah</label>
              <div class="radio-group">
                <label v-for="opt in bloodTypeOptions" :key="opt.id" class="radio-label">
                  <input type="radio" v-model="form.blood_type" :value="opt.id" name="blood_type" />
                  <span>{{ opt.name }}</span>
                </label>
              </div>
              <span v-if="hasError('blood_type')" class="error-feedback">{{ getError('blood_type') }}</span>
            </div>
            
            <div class="form-group">
              <label class="form-label">Agama</label>
              <div class="radio-group flex-wrap">
                <label v-for="opt in religionOptions" :key="opt.id" class="radio-label">
                  <input type="radio" v-model="form.religion" :value="opt.id" name="religion" />
                  <span>{{ opt.name }}</span>
                </label>
              </div>
              <span v-if="hasError('religion')" class="error-feedback">{{ getError('religion') }}</span>
            </div>
            
            <div class="form-group">
              <label class="form-label">Status Perkawinan</label>
              <div class="radio-group flex-wrap">
                <label v-for="opt in maritalStatusOptions" :key="opt.id" class="radio-label">
                  <input type="radio" v-model="form.marital_status" :value="opt.id" name="marital_status" />
                  <span>{{ opt.name }}</span>
                </label>
              </div>
              <span v-if="hasError('marital_status')" class="error-feedback">{{ getError('marital_status') }}</span>
            </div>
            
            <div class="form-group">
              <label class="form-label">Kebangsaan</label>
              <div class="radio-group">
                <label v-for="opt in nationalityOptions" :key="opt.id" class="radio-label">
                  <input type="radio" v-model="form.nationality" :value="opt.id" name="nationality" />
                  <span>{{ opt.name }}</span>
                </label>
              </div>
              <span v-if="hasError('nationality')" class="error-feedback">{{ getError('nationality') }}</span>
            </div>
          </div>

          <h4 class="section-title mb-4 border-t pt-4 mt-2">Alamat & Personal</h4>
            <div class="form-grid mb-6">
            <div class="form-group">
              <label for="address" class="form-label">Alamat</label>
              <textarea 
                id="address" 
                v-model="form.address" 
                class="form-input" 
                rows="3"
                :class="{ 'is-invalid': hasError('address') }"
                placeholder="Masukkan alamat (desa/jalan)"
              ></textarea>
              <span v-if="hasError('address')" class="error-feedback">{{ getError('address') }}</span>
            </div>

            <div class="form-group">
              <label for="village_id" class="form-label">Wilayah</label>
              <AsyncVillageSelect 
                v-model="form.village_id" 
                :initialVillage="employee?.village"
              />
              <span v-if="hasError('village_id')" class="error-feedback">{{ getError('village_id') }}</span>
            </div>
            
            <div class="form-group">
              <label for="allergies" class="form-label">Alergi</label>
              <textarea 
                id="allergies" 
                v-model="form.allergies" 
                class="form-input form-textarea" 
                :class="{ 'is-invalid': hasError('allergies') }"
                placeholder="Masukkan alergi (opsional)"
              ></textarea>
              <span v-if="hasError('allergies')" class="error-feedback">{{ getError('allergies') }}</span>
            </div>
            
            <div class="form-group">
              <label for="phone" class="form-label">No. HP</label>
              <input 
                id="phone" 
                v-model="form.phone" 
                type="text" 
                class="form-input" 
                :class="{ 'is-invalid': hasError('phone') }"
                placeholder="Masukkan nomor HP"
              />
            </div>
            
            <div class="form-group">
              <label for="occupation_id" class="form-label">Pekerjaan</label>
              <SearchableSelect 
                v-model="form.occupation_id" 
                :options="lookupOccupations"
                placeholder="Pilih Pekerjaan"
              />
            </div>
            
            <div class="form-group">
              <label for="education_id" class="form-label">Tingkat Pendidikan</label>
              <SearchableSelect 
                v-model="form.education_id" 
                :options="lookupEducations"
                placeholder="Pilih Pendidikan"
              />
            </div>
          </div>

          <h4 class="section-title mb-4 border-t pt-4 mt-2">Kepegawaian</h4>
            <div class="form-grid mb-6">
            <div class="form-group">
              <label for="code" class="form-label">NIP</label>
              <input 
                id="code" 
                v-model="form.code" 
                type="text" 
                class="form-input" 
                :class="{ 'is-invalid': hasError('code') }"
                placeholder="Masukkan NIP"
              />
              <span v-if="hasError('code')" class="error-feedback">{{ getError('code') }}</span>
            </div>

            <div class="form-group">
              <label for="position_id" class="form-label">Jabatan</label>
              <SearchableSelect 
                v-model="form.position_id" 
                :options="lookupPositions"
                :loading="isPositionsLoading"
                :error="positionsError"
                placeholder="Pilih Jabatan"
              />
              <span v-if="hasError('position_id')" class="error-feedback">{{ getError('position_id') }}</span>
            </div>
            

            

          </div>
                </template>
      </div>

      <footer v-if="!isLoadingData" class="employee-modal-footer modal-footer">
        <div class="footer-left">
          <button type="button" class="btn btn-outline" @click="close" :disabled="loading">
            Batal
          </button>
        </div>
        
        <div class="footer-right">
          <button type="submit" class="btn btn-primary" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            <span>{{ isEditing ? 'Simpan Perubahan' : 'Simpan' }}</span>
          </button>
        </div>
      </footer>
      </form>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch, onUnmounted } from 'vue';
import { useEmployees } from '../../composables/useEmployees';
import employeeService from '../../services/employee.service';
import lookupService from '../../services/lookup.service';
import SearchableSelect from '@/components/common/SearchableSelect.vue';
import AsyncVillageSelect from '@/components/common/AsyncVillageSelect.vue';
const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  employee: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const { createEmployee, updateEmployee } = useEmployees();

const isEditing = ref(false);
const loading = ref(false);
const isLoadingData = ref(false);
const error = ref(null);

const genderOptions = [
  { id: 'L', name: 'Laki-laki' },
  { id: 'P', name: 'Perempuan' }
];

const bloodTypeOptions = [
  { id: 'A', name: 'A' },
  { id: 'B', name: 'B' },
  { id: 'AB', name: 'AB' },
  { id: 'O', name: 'O' },
  { id: 'Unknown', name: 'Tidak Tahu' }
];

const religionOptions = [
  { id: 'Islam', name: 'Islam' },
  { id: 'Kristen', name: 'Kristen' },
  { id: 'Katolik', name: 'Katolik' },
  { id: 'Hindu', name: 'Hindu' },
  { id: 'Buddha', name: 'Buddha' },
  { id: 'Khonghucu', name: 'Khonghucu' }
];

const maritalStatusOptions = [
  { id: 'Belum Kawin', name: 'Belum Kawin' },
  { id: 'Kawin', name: 'Kawin' },
  { id: 'Cerai Hidup', name: 'Cerai Hidup' },
  { id: 'Cerai Mati', name: 'Cerai Mati' }
];

const nationalityOptions = [
  { id: 'WNI', name: 'WNI' },
  { id: 'WNA', name: 'WNA' }
];

// Lookups
const lookupPositions = ref([]);
const isPositionsLoading = ref(false);
const positionsError = ref('');
const lookupOccupations = ref([]);
const lookupEducations = ref([]);

const defaultForm = {
  national_id: '',
  code: '',
  name: '',
  birth_place: '',
  birth_date: '',
  gender: '',
  blood_type: '',
  religion: '',
  marital_status: '',
  nationality: 'WNI',
  address: '',
  village_id: '',
  phone: '',
  allergies: '',
  position_id: '',
  occupation_id: '',
  education_id: '',
  is_active: true
};

const form = reactive({ ...defaultForm });

const hasError = (field) => {
  return error.value && typeof error.value === 'object' && error.value[field];
};

const getError = (field) => {
  if (hasError(field)) {
    return error.value[field][0];
  }
  return '';
};

watch(() => props.isOpen, async (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden';
        error.value = null;
    isLoadingData.value = true;
    
    try {
      if (lookupPositions.value.length === 0) {
        await loadLookups();
      }
      
      if (props.employee) {
        isEditing.value = true;
        // Fetch full employee detail
        const detail = await employeeService.getEmployee(props.employee.id);
        const data = detail.data || detail;
        
        Object.assign(form, {
          ...data,
          position_id: data.position_id || '',
          occupation_id: data.occupation_id || '',
          education_id: data.education_id || '',
          gender: data.gender || '',
          blood_type: data.blood_type || '',
          religion: data.religion || '',
          marital_status: data.marital_status || '',
          nationality: data.nationality || 'WNI',
          village_id: data.village_id || '',
        });
        
      } else {
        isEditing.value = false;
        Object.assign(form, { ...defaultForm });
      }
    } catch (err) {
      console.error('Failed to load employee details', err);
      error.value = { global: ['Gagal memuat detail pegawai.'] };
    } finally {
      isLoadingData.value = false;
    }
  } else {
    document.body.style.overflow = '';
  }
});

onUnmounted(() => {
  document.body.style.overflow = '';
});

const loadLookups = async () => {
  isPositionsLoading.value = true;
  positionsError.value = '';
  try {
    const posRes = await employeeService.getPositions({ is_active: 1 });
    // Assuming backend returns { data: [...] } or just an array
    lookupPositions.value = Array.isArray(posRes.data) ? posRes.data : (Array.isArray(posRes) ? posRes : []);
  } catch (err) {
    console.error("Failed to load positions", err);
    positionsError.value = "Gagal memuat data jabatan.";
  } finally {
    isPositionsLoading.value = false;
  }

  try {
    const [occRes, eduRes] = await Promise.all([
      lookupService.getOccupations(),
      lookupService.getEducations()
    ]);
    lookupOccupations.value = occRes || [];
    lookupEducations.value = eduRes || [];
  } catch (err) {
    console.error("Failed to load other lookups", err);
  }
};




const submitForm = async () => {
  loading.value = true;
  error.value = null;
  
  // Clean empty strings for foreign keys
  const submitData = { ...form };
  if (!submitData.position_id) submitData.position_id = null;
  if (!submitData.occupation_id) submitData.occupation_id = null;
  if (!submitData.education_id) submitData.education_id = null;

  try {
    let success = false;
    
    if (isEditing.value) {
      success = await updateEmployee(props.employee.id, submitData);
    } else {
      success = await createEmployee(submitData);
    }

    if (success) {
      emit('saved');
      close();
    }
  } catch (err) {
    error.value = err;
    
    
  } finally {
    loading.value = false;
  }
};

const close = () => {
  if (!loading.value) {
    emit('close');
  }
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

.error-feedback {
  display: block;
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.375rem;
}

.radio-group {
  display: flex;
  gap: 1.5rem;
  align-items: center;
  min-height: 38px;
}

.radio-group.flex-wrap {
  flex-wrap: wrap;
  gap: 1rem 1.5rem;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: var(--color-text-navy);
  cursor: pointer;
  margin-bottom: 0;
}

.radio-label input[type="radio"] {
  width: 1rem;
  height: 1rem;
  accent-color: var(--color-primary);
  cursor: pointer;
  margin: 0;
}

.required-indicator {
  color: #ef4444;
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
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}

.footer-left,
.footer-right {
  display: flex;
  gap: 0.75rem;
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

<style scoped>
.large-modal {
  max-width: 1000px;
  width: 95%;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
  align-items: start;
}

.full-width {
  grid-column: span 2;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .full-width {
    grid-column: span 1;
  }
}
</style>

<style scoped>
.btn-close svg {
  width: 24px;
  height: 24px;
}

/* Modal Structure */
.employee-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.employee-modal-backdrop {
  position: absolute;
  inset: 0;
  background: rgb(15 23 42 / 55%);
}

.employee-modal-panel {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  width: min(1080px, 100%);
  max-height: calc(100vh - 3rem);
  overflow: hidden;
  border-radius: 0.75rem;
  background: #fff;
  box-shadow: 0 24px 60px rgb(15 23 42 / 25%);
}


.employee-form {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
}
.employee-modal-header,
.employee-modal-footer {
  flex: 0 0 auto;
}

.employee-modal-body {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
}
</style>
