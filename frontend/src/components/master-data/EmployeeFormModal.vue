<script setup>
import { ref, reactive, watch, nextTick } from 'vue';
import { useEmployees } from '@/composables/useEmployees';
import employeeService from '@/services/employee.service';
import lookupService from '@/services/lookup.service';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';
import BaseSelect from '@/components/common/BaseSelect.vue';

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
const isSubmitting = ref(false);
const isLoadingData = ref(false);
const isHydrating = ref(false);
const errors = ref({});
const submitError = ref(null);

const lookupPositions = ref([]);
const isPositionsLoading = ref(false);
const positionsError = ref('');

const lookups = reactive({
  occupations: [],
  educations: [],
  provinces: [],
  regencies: [],
  districts: [],
  villages: []
});

const lookupLoading = reactive({
  occupations: false,
  educations: false,
  provinces: false,
  regencies: false,
  districts: false,
  villages: false
});

const lookupError = reactive({
  occupations: false,
  educations: false,
  provinces: false,
  regencies: false,
  districts: false,
  villages: false
});

const defaultForm = {
  national_id: '',
  code: '',
  name: '',
  birth_place: '',
  birth_date: '',
  blood_type: '',
  religion: '',
  marital_status: '',
  nationality: 'WNI',
  address: '',
  allergies: '',
  position_id: '',
  occupation_id: '',
  education_id: '',
  province_id: '',
  regency_id: '',
  district_id: '',
  village_id: '',
  postal_code: '',
  phone: ''
};

const form = reactive({ ...defaultForm });

const loadInitialLookups = async () => {
  isPositionsLoading.value = true;
  positionsError.value = '';
  try {
    const posRes = await employeeService.getPositions({ is_active: 1 });
    lookupPositions.value = Array.isArray(posRes.data) ? posRes.data : (Array.isArray(posRes) ? posRes : []);
  } catch (err) {
    console.error("Failed to load positions", err);
    positionsError.value = "Gagal memuat data jabatan.";
  } finally {
    isPositionsLoading.value = false;
  }

  const fetchWithState = async (key, fetchFn) => {
    lookupLoading[key] = true;
    lookupError[key] = false;
    try {
      lookups[key] = await fetchFn();
    } catch (err) {
      console.error(`Gagal memuat ${key}:`, err);
      lookupError[key] = true;
    } finally {
      lookupLoading[key] = false;
    }
  };

  await Promise.all([
    fetchWithState('occupations', () => lookupService.getOccupations()),
    fetchWithState('educations', () => lookupService.getEducations()),
    fetchWithState('provinces', () => lookupService.getProvinces())
  ]);
};

const onProvinceChange = async () => {
  if (!isHydrating.value) {
    form.regency_id = '';
    form.district_id = '';
    form.village_id = '';
  }
  lookups.regencies = [];
  lookups.districts = [];
  lookups.villages = [];
  if (form.province_id) {
    lookupLoading.regencies = true;
    lookupError.regencies = false;
    try {
      const res = await lookupService.getRegencies({ province_id: form.province_id });
      lookups.regencies = res.data || res;
    } catch (err) {
      console.error(err);
      lookupError.regencies = true;
    } finally {
      lookupLoading.regencies = false;
    }
  }
};

const onRegencyChange = async () => {
  if (!isHydrating.value) {
    form.district_id = '';
    form.village_id = '';
  }
  lookups.districts = [];
  lookups.villages = [];
  if (form.regency_id) {
    lookupLoading.districts = true;
    lookupError.districts = false;
    try {
      const res = await lookupService.getDistricts({ regency_id: form.regency_id });
      lookups.districts = res.data || res;
    } catch (err) {
      console.error(err);
      lookupError.districts = true;
    } finally {
      lookupLoading.districts = false;
    }
  }
};

const onDistrictChange = async () => {
  if (!isHydrating.value) {
    form.village_id = '';
  }
  lookups.villages = [];
  if (form.district_id) {
    lookupLoading.villages = true;
    lookupError.villages = false;
    try {
      const res = await lookupService.getVillages({ district_id: form.district_id });
      lookups.villages = res.data || res;
    } catch (err) {
      console.error(err);
      lookupError.villages = true;
    } finally {
      lookupLoading.villages = false;
    }
  }
};

watch(() => props.isOpen, async (newVal) => {
  if (!newVal) return;
  
  errors.value = {};
  submitError.value = null;
  isLoadingData.value = true;
  isHydrating.value = true;
  
  try {
    if (lookupPositions.value.length === 0 || lookups.provinces.length === 0) {
      await loadInitialLookups();
    }
    
    if (props.employee) {
      isEditing.value = true;
      const detail = await employeeService.getEmployee(props.employee.id);
      const data = detail.data || detail;
      
      Object.assign(form, {
        national_id: data.national_id || '',
        code: data.code || '',
        name: data.name || '',
        birth_place: data.birth_place || '',
        birth_date: data.birth_date ? String(data.birth_date).substring(0, 10) : '',
        blood_type: data.blood_type || '',
        religion: data.religion || '',
        marital_status: data.marital_status || '',
        nationality: data.nationality || 'WNI',
        address: data.address || '',
        allergies: data.allergies || '',
        position_id: data.position_id ? Number(data.position_id) : '',
        occupation_id: data.occupation_id ? Number(data.occupation_id) : '',
        education_id: data.education_id ? Number(data.education_id) : '',
        postal_code: data.postal_code || '',
        phone: data.phone || ''
      });

      form.province_id = data.province_id ? String(data.province_id) : '';
      if (form.province_id) {
        const res = await lookupService.getRegencies({ province_id: form.province_id });
        lookups.regencies = res.data || res;
      }
      
      form.regency_id = data.regency_id ? String(data.regency_id) : '';
      if (form.regency_id) {
        const res = await lookupService.getDistricts({ regency_id: form.regency_id });
        lookups.districts = res.data || res;
      }
      
      form.district_id = data.district_id ? String(data.district_id) : '';
      if (form.district_id) {
        const res = await lookupService.getVillages({ district_id: form.district_id });
        lookups.villages = res.data || res;
      }
      
      form.village_id = data.village_id ? String(data.village_id) : '';

    } else {
      isEditing.value = false;
      Object.assign(form, { ...defaultForm });
      lookups.regencies = [];
      lookups.districts = [];
      lookups.villages = [];
    }
  } catch (err) {
    console.error('Failed to load employee details', err);
    submitError.value = 'Gagal memuat detail pegawai.';
  } finally {
    isLoadingData.value = false;
    isHydrating.value = false;
  }
});

const focusFirstInvalidField = () => {
  nextTick(() => {
    const errorEl = document.querySelector('.has-error');
    if (errorEl) {
      errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (typeof errorEl.focus === 'function') {
        errorEl.focus();
      }
    }
  });
};

const fieldError = (...keys) => {
  for (const key of keys) {
    const messages = errors.value?.[key];
    if (Array.isArray(messages) && messages.length) {
      return messages[0];
    }
  }
  return '';
};

const toNullableNumber = (val) => {
  if (val === '' || val === null || val === undefined) return null;
  const num = Number(val);
  return isNaN(num) ? null : num;
};

const toNullableString = (val) => {
  return (val === '' || val === null || val === undefined) ? null : String(val);
};

const handleSubmit = async () => {
  if (isSubmitting.value) return;

  errors.value = {};
  submitError.value = '';
  isSubmitting.value = true;
  
  const submitData = { 
    ...form,
    position_id: toNullableNumber(form.position_id),
    occupation_id: toNullableNumber(form.occupation_id),
    education_id: toNullableNumber(form.education_id),
    province_id: toNullableString(form.province_id),
    regency_id: toNullableString(form.regency_id),
    district_id: toNullableString(form.district_id),
    village_id: toNullableString(form.village_id),
    phone: toNullableString(form.phone)
  };

  try {
    let result;
    if (isEditing.value) {
      result = await updateEmployee(props.employee.id, submitData);
    } else {
      result = await createEmployee(submitData);
    }

    if (result.success) {
      emit('saved');
    } else {
      if (result.error?.response?.status === 422) {
        errors.value = result.error.response.data.errors ?? {};
        focusFirstInvalidField();
      } else {
        submitError.value = result.error?.response?.data?.message ?? 'Data Pegawai gagal disimpan.';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    }
  } catch (err) {
    submitError.value = 'Terjadi kesalahan sistem.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="isEditing ? 'Edit Pegawai' : 'Tambah Pegawai'"
    :is-submitting="isSubmitting || isLoadingData"
    :submit-text="isEditing ? 'Simpan Perubahan' : 'Simpan'"
    size="xl"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div v-if="isLoadingData" class="loading-state">
      <svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="23 4 23 10 17 10"></polyline>
        <polyline points="1 20 1 14 7 14"></polyline>
        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
      </svg>
      <p>Memuat data Pegawai...</p>
    </div>

    <div v-else class="form-content">
      <div v-if="submitError" class="alert alert-danger" style="background: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        {{ submitError }}
      </div>
      <div v-if="errors.general" class="alert alert-danger" style="background: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        {{ errors.general }}
      </div>

      <!-- Identitas Person Section -->
      <div class="section-card">
        <h3 class="section-title">1. Identitas Pribadi</h3>
        
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label required">Nomor KTP (NIK)</label>
            <input type="text" v-model="form.national_id" class="form-input" :class="{ 'has-error': fieldError('national_id') }" required maxlength="20">
            <div v-if="fieldError('national_id')" class="error-message">{{ fieldError('national_id') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label required">Nama Lengkap</label>
            <input type="text" v-model="form.name" class="form-input" :class="{ 'has-error': fieldError('name') }" required>
            <div v-if="fieldError('name')" class="error-message">{{ fieldError('name') }}</div>
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Tempat Lahir</label>
            <input type="text" v-model="form.birth_place" class="form-input" :class="{ 'has-error': fieldError('birth_place') }">
            <div v-if="fieldError('birth_place')" class="error-message">{{ fieldError('birth_place') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Tanggal Lahir</label>
            <input type="date" v-model="form.birth_date" class="form-input" :class="{ 'has-error': fieldError('birth_date') }">
            <div v-if="fieldError('birth_date')" class="error-message">{{ fieldError('birth_date') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Golongan Darah</label>
            <select v-model="form.blood_type" class="form-select" :class="{ 'has-error': fieldError('blood_type') }">
              <option value="">Pilih Golongan Darah</option>
              <option value="Unknown">Tidak Diketahui</option>
              <option value="A">A</option>
              <option value="B">B</option>
              <option value="AB">AB</option>
              <option value="O">O</option>
            </select>
            <div v-if="fieldError('blood_type')" class="error-message">{{ fieldError('blood_type') }}</div>
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Agama</label>
            <select v-model="form.religion" class="form-select" :class="{ 'has-error': fieldError('religion') }">
              <option value="">Pilih Agama</option>
              <option value="Islam">Islam</option>
              <option value="Kristen">Kristen</option>
              <option value="Katolik">Katolik</option>
              <option value="Hindu">Hindu</option>
              <option value="Buddha">Buddha</option>
              <option value="Khonghucu">Khonghucu</option>
            </select>
            <div v-if="fieldError('religion')" class="error-message">{{ fieldError('religion') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Status Perkawinan</label>
            <select v-model="form.marital_status" class="form-select" :class="{ 'has-error': fieldError('marital_status') }">
              <option value="">Pilih Status</option>
              <option value="Belum Kawin">Belum Kawin</option>
              <option value="Kawin">Kawin</option>
              <option value="Cerai Hidup">Cerai Hidup</option>
              <option value="Cerai Mati">Cerai Mati</option>
            </select>
            <div v-if="fieldError('marital_status')" class="error-message">{{ fieldError('marital_status') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Kebangsaan</label>
            <select v-model="form.nationality" class="form-select" :class="{ 'has-error': fieldError('nationality') }">
              <option value="WNI">WNI</option>
              <option value="WNA">WNA</option>
            </select>
            <div v-if="fieldError('nationality')" class="error-message">{{ fieldError('nationality') }}</div>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label class="form-label">Alergi</label>
          <textarea v-model="form.allergies" class="form-textarea" rows="2" placeholder="Tuliskan alergi obat, makanan, atau lainnya jika diketahui" :class="{ 'has-error': fieldError('allergies') }"></textarea>
          <div v-if="fieldError('allergies')" class="error-message">{{ fieldError('allergies') }}</div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Pendidikan</label>
            <BaseSelect 
              id="education" 
              v-model="form.education_id" 
              :options="lookups.educations" 
              :loading="lookupLoading.educations" 
              :hasError="lookupError.educations || !!fieldError('education_id')"
              placeholder="Pilih Pendidikan"
              emptyMessage="Data Pendidikan belum tersedia"
            />
            <div v-if="fieldError('education_id')" class="error-message">{{ fieldError('education_id') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Pekerjaan</label>
            <BaseSelect 
              id="occupation" 
              v-model="form.occupation_id" 
              :options="lookups.occupations" 
              :loading="lookupLoading.occupations" 
              :hasError="lookupError.occupations || !!fieldError('occupation_id')"
              placeholder="Pilih Pekerjaan"
              emptyMessage="Data Pekerjaan belum tersedia"
            />
            <div v-if="fieldError('occupation_id')" class="error-message">{{ fieldError('occupation_id') }}</div>
          </div>
        </div>
      </div>

      <!-- Alamat dan Kontak Section -->
      <div class="section-card">
        <h3 class="section-title">2. Alamat & Kontak</h3>
        
        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label class="form-label">Alamat Lengkap (Jalan/Blok/RT/RW)</label>
          <textarea v-model="form.address" class="form-textarea" rows="2" :class="{ 'has-error': fieldError('address') }"></textarea>
          <div v-if="fieldError('address')" class="error-message">{{ fieldError('address') }}</div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Provinsi</label>
            <BaseSelect 
              id="province" 
              v-model="form.province_id" 
              @change="onProvinceChange"
              :options="lookups.provinces" 
              :loading="lookupLoading.provinces" 
              :hasError="lookupError.provinces || !!fieldError('province_id')"
              placeholder="Pilih Provinsi"
              emptyMessage="Data Provinsi belum tersedia"
            />
            <div v-if="fieldError('province_id')" class="error-message">{{ fieldError('province_id') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Kabupaten / Kota</label>
            <BaseSelect 
              id="city" 
              v-model="form.regency_id" 
              @change="onRegencyChange"
              :disabled="!form.province_id"
              :options="lookups.regencies" 
              :loading="lookupLoading.regencies" 
              :hasError="lookupError.regencies || !!fieldError('regency_id')"
              placeholder="Pilih Kota/Kab"
              emptyMessage="Data Kota/Kab belum tersedia"
            />
            <div v-if="fieldError('regency_id')" class="error-message">{{ fieldError('regency_id') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Kecamatan</label>
            <BaseSelect 
              id="district" 
              v-model="form.district_id" 
              @change="onDistrictChange"
              :disabled="!form.regency_id"
              :options="lookups.districts" 
              :loading="lookupLoading.districts" 
              :hasError="lookupError.districts || !!fieldError('district_id')"
              placeholder="Pilih Kecamatan"
              emptyMessage="Data Kecamatan belum tersedia"
            />
            <div v-if="fieldError('district_id')" class="error-message">{{ fieldError('district_id') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Kelurahan / Desa</label>
            <BaseSelect 
              id="village" 
              v-model="form.village_id" 
              :disabled="!form.district_id"
              :options="lookups.villages" 
              :loading="lookupLoading.villages" 
              :hasError="lookupError.villages || !!fieldError('village_id')"
              placeholder="Pilih Kelurahan"
              emptyMessage="Data Kelurahan belum tersedia"
            />
            <div v-if="fieldError('village_id')" class="error-message">{{ fieldError('village_id') }}</div>
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Kode Pos</label>
            <input type="text" v-model="form.postal_code" class="form-input" :class="{ 'has-error': fieldError('postal_code') }" maxlength="10">
            <div v-if="fieldError('postal_code')" class="error-message">{{ fieldError('postal_code') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Nomor HP</label>
            <input type="tel" v-model="form.phone" class="form-input" :class="{ 'has-error': fieldError('phone') }" maxlength="20" placeholder="Contoh: 081234567890">
            <div v-if="fieldError('phone')" class="error-message">{{ fieldError('phone') }}</div>
          </div>
        </div>
      </div>

      <!-- Informasi Kepegawaian Section -->
      <div class="section-card">
        <h3 class="section-title">3. Informasi Kepegawaian</h3>
        
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label required">NIP</label>
            <input type="text" v-model="form.code" class="form-input" :class="{ 'has-error': fieldError('code') }" placeholder="Masukkan NIP" required>
            <div v-if="fieldError('code')" class="error-message">{{ fieldError('code') }}</div>
          </div>
          <div class="form-group">
            <label class="form-label required">Jabatan</label>
            <BaseSelect 
              id="position" 
              v-model="form.position_id" 
              :options="lookupPositions" 
              :loading="isPositionsLoading" 
              :hasError="!!positionsError || !!fieldError('position_id')"
              placeholder="Pilih jabatan"
              emptyMessage="Data Jabatan belum tersedia"
              required
            />
            <div v-if="fieldError('position_id')" class="error-message">{{ fieldError('position_id') }}</div>
          </div>
        </div>
      </div>

    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.form-content {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.section-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  border: 1px solid var(--color-border-soft);
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.section-card:last-child {
  margin-bottom: 0;
}
.section-title {
  margin-top: 0;
  margin-bottom: 1.25rem;
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--color-primary);
  border-bottom: 1px solid var(--color-border-soft);
  padding-bottom: 0.5rem;
}
.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.25rem;
  margin-bottom: 1.25rem;
}
@media (min-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr 1fr;
  }
}
.form-group {
  margin-bottom: 0;
}
.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy);
  margin-bottom: 0.5rem;
}
.form-label.required::after {
  content: " *";
  color: #ef4444;
}
.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.875rem;
  color: var(--color-text-navy);
  background-color: #ffffff;
  transition: all 0.2s;
  font-family: inherit;
}
.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  padding-right: 2.5rem;
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}
.form-input.has-error,
.form-select.has-error,
.form-textarea.has-error {
  border-color: #ef4444;
}
.form-input.has-error:focus,
.form-select.has-error:focus,
.form-textarea.has-error:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
.form-textarea {
  resize: vertical;
  min-height: 80px;
}
.error-message {
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.25rem;
}
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 0;
  color: var(--color-text-secondary);
}
.loading-state svg {
  width: 28px;
  height: 28px;
  margin-bottom: 1rem;
  color: var(--color-primary);
}
.spin {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
