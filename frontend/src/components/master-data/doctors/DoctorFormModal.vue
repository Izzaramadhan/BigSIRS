<script setup>
import { ref, reactive, watch, computed, nextTick } from 'vue';
import BaseSelect from '@/components/common/BaseSelect.vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

import LookupService from '@/services/lookup.service';
import doctorService from '@/services/master-data/doctors.service';

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  mode: {
    type: String,
    required: true,
    validator: value => ['create', 'edit'].includes(value)
  },
  initialData: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const isEditMode = computed(() => props.mode === 'edit');
const isSubmitting = ref(false);
const errors = ref({});
const submitError = ref('');

const createEmptyForm = () => ({
  employee_id: null,
  person: {
    national_id: '',
    name: '',
    ihs_number: '',
    birth_place: '',
    birth_date: '',
    gender: '',
    nationality: 'WNI',
    blood_type: '',
    allergies: '',
    religion: '',
    marital_status: '',
    address: '',
    postal_code: '',
    province_id: '',
    regency_id: '',
    district_id: '',
    village_id: '',
    phone: '',
    education_id: '',
    occupation_id: ''
  },
  professional: {
    specialization_id: '',
    str_number: '',
    sip_number: '',
    sip_valid_until: '',
    bpjs_dpjp_code: '',
    is_active: true
  },
  signature: null,
  remove_signature: false,
});

const form = reactive(createEmptyForm());

const lookups = reactive({
  specializations: [],
  provinces: [],
  regencies: [],
  districts: [],
  villages: [],
  educations: [],
  occupations: []
});

const fileInput = ref(null);
const previewImage = ref(null);

const lookupLoading = reactive({
  specializations: false,
  provinces: false,
  regencies: false,
  districts: false,
  villages: false,
  educations: false,
  occupations: false
});

const lookupError = reactive({
  specializations: false,
  provinces: false,
  regencies: false,
  districts: false,
  villages: false,
  educations: false,
  occupations: false
});

const loadFormLookups = async (currentMode) => {
  const specParams = currentMode === 'create' ? { is_active: 1 } : {};
  
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
    fetchWithState('specializations', () => LookupService.getSpecializations(specParams)),
    fetchWithState('provinces', () => LookupService.getProvinces()),
    fetchWithState('educations', () => LookupService.getEducations()),
    fetchWithState('occupations', () => LookupService.getOccupations())
  ]);
};

const isHydrating = ref(false);
const loadingDetail = ref(false);
const detailError = ref('');

const onProvinceChange = async () => {
  if (!isHydrating.value) {
    form.person.regency_id = '';
    form.person.district_id = '';
    form.person.village_id = '';
  }
  lookups.regencies = [];
  lookups.districts = [];
  lookups.villages = [];
  if (form.person.province_id) {
    lookupLoading.regencies = true;
    lookupError.regencies = false;
    try {
      lookups.regencies = await LookupService.getRegencies({ province_id: form.person.province_id });
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
    form.person.district_id = '';
    form.person.village_id = '';
  }
  lookups.districts = [];
  lookups.villages = [];
  if (form.person.regency_id) {
    lookupLoading.districts = true;
    lookupError.districts = false;
    try {
      lookups.districts = await LookupService.getDistricts({ regency_id: form.person.regency_id });
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
    form.person.village_id = '';
  }
  lookups.villages = [];
  if (form.person.district_id) {
    lookupLoading.villages = true;
    lookupError.villages = false;
    try {
      lookups.villages = await LookupService.getVillages({ district_id: form.person.district_id });
    } catch (err) {
      console.error(err);
      lookupError.villages = true;
    } finally {
      lookupLoading.villages = false;
    }
  }
};

const initializeCreateMode = () => {
  Object.assign(form, createEmptyForm());
  lookups.regencies = [];
  lookups.districts = [];
  lookups.villages = [];
  previewImage.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const initializeEditMode = async (detail) => {
  if (!detail?.id) {
    throw new Error('Initial data Edit tidak tersedia');
  }

  isHydrating.value = true;
  
  try {
    form.employee_id = detail.employee_id;
    
    const person = detail.person || detail.employee?.person || detail.employee || {};
    
    if (person) {
      Object.assign(form.person, {
        national_id: person.national_id || '',
        name: person.name || '',
        ihs_number: person.ihs_number || '',
        birth_place: person.birth_place || '',
        birth_date: person.birth_date ? String(person.birth_date).substring(0, 10) : '',
        gender: person.gender || '',
        nationality: person.nationality || 'WNI',
        blood_type: person.blood_type || '',
        allergies: person.allergies || '',
        religion: person.religion || '',
        marital_status: person.marital_status || '',
        address: person.address || '',
        postal_code: person.postal_code || '',
        phone: person.phone || '',
      });
      
      form.person.province_id = person.province_id ? Number(person.province_id) : '';
      if (form.person.province_id) {
        const res = await LookupService.getRegencies({ province_id: form.person.province_id });
        lookups.regencies = res.data || res;
      }
      
      form.person.regency_id = person.regency_id ? Number(person.regency_id) : '';
      if (form.person.regency_id) {
        const res = await LookupService.getDistricts({ regency_id: form.person.regency_id });
        lookups.districts = res.data || res;
      }
      
      form.person.district_id = person.district_id ? Number(person.district_id) : '';
      if (form.person.district_id) {
        const res = await LookupService.getVillages({ district_id: form.person.district_id });
        lookups.villages = res.data || res;
      }
      
      form.person.village_id = person.village_id ? Number(person.village_id) : '';
      form.person.education_id = person.education_id ? Number(person.education_id) : '';
      form.person.occupation_id = person.occupation_id ? Number(person.occupation_id) : '';
    }

    Object.assign(form.professional, {
      specialization_id: detail.specialization_id ? Number(detail.specialization_id) : '',
      str_number: detail.str_number || '',
      sip_number: detail.sip_number || '',
      sip_valid_until: detail.sip_valid_until ? String(detail.sip_valid_until).substring(0, 10) : '',
      bpjs_dpjp_code: detail.bpjs_dpjp_code || '',
      is_active: detail.is_active !== false,
    });
    
    form.signature = null;
    form.remove_signature = false;
    previewImage.value = detail.signature_url || null;
  } finally {
    isHydrating.value = false;
  }
};

watch(
  () => [props.open, props.mode, props.initialData],
  async ([open, mode, initialData]) => {
    if (!open) return;

    detailError.value = '';
    loadingDetail.value = true;
    try {
      await loadFormLookups(mode);
      
      if (mode === 'create') {
        initializeCreateMode();
        return;
      }

      if (mode === 'edit') {
        await initializeEditMode(initialData);
      }
    } catch (err) {
      detailError.value = 'Terjadi kesalahan saat memuat data.';
      console.error(err);
    } finally {
      loadingDetail.value = false;
    }
  },
  { immediate: true }
);

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

const normalizeNullableString = (val) => (val !== null && val !== '') ? String(val) : null;
const toNullableNumber = (val) => (val !== null && val !== '') ? Number(val) : null;

const buildPayload = () => {
  return {
    person: {
      national_id: normalizeNullableString(form.person.national_id),
      name: normalizeNullableString(form.person.name),
      ihs_number: normalizeNullableString(form.person.ihs_number),
      birth_place: normalizeNullableString(form.person.birth_place),
      birth_date: form.person.birth_date || null,
      gender: form.person.gender || null,
      nationality: form.person.nationality || null,
      blood_type: form.person.blood_type || null,
      allergies: normalizeNullableString(form.person.allergies),
      religion: form.person.religion || null,
      marital_status: form.person.marital_status || null,
      address: normalizeNullableString(form.person.address),
      postal_code: normalizeNullableString(form.person.postal_code),
      province_id: toNullableNumber(form.person.province_id),
      regency_id: toNullableNumber(form.person.regency_id),
      district_id: toNullableNumber(form.person.district_id),
      village_id: toNullableNumber(form.person.village_id),
      phone: normalizeNullableString(form.person.phone),
      education_id: toNullableNumber(form.person.education_id),
      occupation_id: toNullableNumber(form.person.occupation_id),
    },
    professional: {
      specialization_id: toNullableNumber(form.professional.specialization_id),
      str_number: normalizeNullableString(form.professional.str_number),
      sip_number: normalizeNullableString(form.professional.sip_number),
      sip_valid_until: form.professional.sip_valid_until || null,
      bpjs_dpjp_code: normalizeNullableString(form.professional.bpjs_dpjp_code),
      is_active: Boolean(form.professional.is_active),
    },
    signature: form.signature,
    remove_signature: form.remove_signature,
    employee_id: form.employee_id
  };
};

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

const handleSubmitError = (error) => {
  if (error.response?.status === 422) {
    errors.value = error.response.data.errors ?? {};
    focusFirstInvalidField();
    return;
  }
  submitError.value = error.response?.data?.message ?? 'Data Dokter gagal disimpan.';
  window.scrollTo({ top: 0, behavior: 'smooth' });
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

const handleSubmit = async () => {
  if (isSubmitting.value) return;

  errors.value = {};
  submitError.value = '';
  isSubmitting.value = true;

  try {
    const payload = buildPayload();

    if (props.mode === 'edit') {
      await doctorService.updateDoctor(props.initialData.id, payload);
    } else {
      await doctorService.createDoctor(payload);
    }

    emit('saved');
  } catch (error) {
    handleSubmitError(error);
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <MasterDataFormModal
    :is-open="open"
    :title="isEditMode ? 'Edit Dokter' : 'Tambah Dokter'"
    :is-submitting="isSubmitting || loadingDetail || !!detailError"
    :submit-text="isEditMode ? 'Simpan Perubahan' : 'Simpan'"
    size="xl"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div v-if="loadingDetail" class="loading-state">
        <svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="23 4 23 10 17 10"></polyline>
          <polyline points="1 20 1 14 7 14"></polyline>
          <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
        </svg>
        <p>Memuat data Dokter...</p>
      </div>

      <div v-else-if="detailError" class="error-state">
        <p>{{ detailError }}</p>
        <div class="actions">
          <button class="btn-outline" @click="emit('close')">Tutup</button>
        </div>
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
          <h3 class="section-title">1. Identitas Person / Pegawai</h3>
          
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label required">Nomor KTP (NIK)</label>
              <input type="text" v-model="form.person.national_id" class="form-input" :class="{ 'has-error': fieldError('person.national_id', 'national_id') }" required maxlength="20">
              <div v-if="fieldError('person.national_id', 'national_id')" class="error-message">{{ fieldError('person.national_id', 'national_id') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label required">Nama Lengkap (beserta gelar)</label>
              <input type="text" v-model="form.person.name" class="form-input" :class="{ 'has-error': fieldError('person.name', 'name') }" required>
              <div v-if="fieldError('person.name', 'name')" class="error-message">{{ fieldError('person.name', 'name') }}</div>
            </div>
          </div>

          <div class="form-grid" >
            <div class="form-group">
              <label class="form-label">Tempat Lahir</label>
              <input type="text" v-model="form.person.birth_place" class="form-input" :class="{ 'has-error': fieldError('person.birth_place', 'birth_place') }">
              <div v-if="fieldError('person.birth_place', 'birth_place')" class="error-message">{{ fieldError('person.birth_place', 'birth_place') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Tanggal Lahir</label>
              <input type="date" v-model="form.person.birth_date" class="form-input" :class="{ 'has-error': fieldError('person.birth_date', 'birth_date') }">
              <div v-if="fieldError('person.birth_date', 'birth_date')" class="error-message">{{ fieldError('person.birth_date', 'birth_date') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label required">Jenis Kelamin</label>
              <select v-model="form.person.gender" class="form-select" :class="{ 'has-error': fieldError('person.gender', 'gender') }" required>
                <option value="">Pilih Jenis Kelamin</option>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
              <div v-if="fieldError('person.gender', 'gender')" class="error-message">{{ fieldError('person.gender', 'gender') }}</div>
            </div>
          </div>

          <div class="form-grid" >
            <div class="form-group">
              <label class="form-label">Golongan Darah</label>
              <select v-model="form.person.blood_type" class="form-select" :class="{ 'has-error': fieldError('person.blood_type', 'blood_type') }">
                <option value="">Pilih Golongan Darah</option>
                <option value="Unknown">Tidak Diketahui</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="AB">AB</option>
                <option value="O">O</option>
              </select>
              <div v-if="fieldError('person.blood_type', 'blood_type')" class="error-message">{{ fieldError('person.blood_type', 'blood_type') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Agama</label>
              <select v-model="form.person.religion" class="form-select" :class="{ 'has-error': fieldError('person.religion', 'religion') }">
                <option value="">Pilih Agama</option>
                <option value="Islam">Islam</option>
                <option value="Kristen">Kristen</option>
                <option value="Katolik">Katolik</option>
                <option value="Hindu">Hindu</option>
                <option value="Budha">Budha</option>
                <option value="Khong Hucu">Khong Hucu</option>
              </select>
              <div v-if="fieldError('person.religion', 'religion')" class="error-message">{{ fieldError('person.religion', 'religion') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Status Perkawinan</label>
              <select v-model="form.person.marital_status" class="form-select" :class="{ 'has-error': fieldError('person.marital_status', 'marital_status') }">
                <option value="">Pilih Status</option>
                <option value="Belum Kawin">Belum Kawin</option>
                <option value="Kawin">Kawin</option>
                <option value="Cerai Hidup">Cerai Hidup</option>
                <option value="Cerai Mati">Cerai Mati</option>
              </select>
              <div v-if="fieldError('person.marital_status', 'marital_status')" class="error-message">{{ fieldError('person.marital_status', 'marital_status') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Kebangsaan</label>
              <select v-model="form.person.nationality" class="form-select" :class="{ 'has-error': fieldError('person.nationality', 'nationality') }">
                <option value="WNI">WNI</option>
                <option value="WNA">WNA</option>
              </select>
              <div v-if="fieldError('person.nationality', 'nationality')" class="error-message">{{ fieldError('person.nationality', 'nationality') }}</div>
            </div>
          </div>

          
          <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Alergi</label>
            <textarea v-model="form.person.allergies" class="form-textarea" rows="2" placeholder="Tuliskan alergi obat, makanan, atau lainnya jika diketahui" :class="{ 'has-error': fieldError('person.allergies', 'allergies') }"></textarea>
            <div v-if="fieldError('person.allergies', 'allergies')" class="error-message">{{ fieldError('person.allergies', 'allergies') }}</div>
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Pendidikan</label>
              <BaseSelect 
                id="education" 
                v-model="form.person.education_id" 
                :options="lookups.educations" 
                :loading="lookupLoading.educations" 
                :hasError="lookupError.educations || !!fieldError('person.education_id', 'education_id')"
                placeholder="Pilih Pendidikan"
                emptyMessage="Data Pendidikan belum tersedia"
              />
              <div v-if="fieldError('person.education_id', 'education_id')" class="error-message">{{ fieldError('person.education_id', 'education_id') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Pekerjaan</label>
              <BaseSelect 
                id="occupation" 
                v-model="form.person.occupation_id" 
                :options="lookups.occupations" 
                :loading="lookupLoading.occupations" 
                :hasError="lookupError.occupations || !!fieldError('person.occupation_id', 'occupation_id')"
                placeholder="Pilih Pekerjaan"
                emptyMessage="Data Pekerjaan belum tersedia"
              />
              <div v-if="fieldError('person.occupation_id', 'occupation_id')" class="error-message">{{ fieldError('person.occupation_id', 'occupation_id') }}</div>
            </div>
          </div>
        </div>

        <!-- Alamat dan Kontak Section -->
        <div class="section-card">
          <h3 class="section-title">2. Alamat & Kontak</h3>
          
          <div class="form-group">
            <label class="form-label">Alamat Lengkap (Jalan/Blok/RT/RW)</label>
            <textarea v-model="form.person.address" class="form-textarea" rows="2" :class="{ 'has-error': fieldError('person.address', 'address') }"></textarea>
            <div v-if="fieldError('person.address', 'address')" class="error-message">{{ fieldError('person.address', 'address') }}</div>
          </div>

          <div class="form-grid" >
            <div class="form-group">
              <label class="form-label">Provinsi</label>
              <BaseSelect 
                id="province" 
                v-model="form.person.province_id" 
                @change="onProvinceChange"
                :options="lookups.provinces" 
                :loading="lookupLoading.provinces" 
                :hasError="lookupError.provinces || !!fieldError('person.province_id', 'province_id')"
                placeholder="Pilih Provinsi"
                emptyMessage="Data Provinsi belum tersedia"
              />
              <div v-if="fieldError('person.province_id', 'province_id')" class="error-message">{{ fieldError('person.province_id', 'province_id') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Kabupaten / Kota</label>
              <BaseSelect 
                id="city" 
                v-model="form.person.regency_id" 
                @change="onRegencyChange"
                :disabled="!form.person.province_id"
                :options="lookups.regencies" 
                :loading="lookupLoading.regencies" 
                :hasError="lookupError.regencies || !!fieldError('person.regency_id', 'regency_id')"
                placeholder="Pilih Kota/Kab"
                emptyMessage="Data Kota/Kab belum tersedia"
              />
              <div v-if="fieldError('person.regency_id', 'regency_id')" class="error-message">{{ fieldError('person.regency_id', 'regency_id') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Kecamatan</label>
              <BaseSelect 
                id="district" 
                v-model="form.person.district_id" 
                @change="onDistrictChange"
                :disabled="!form.person.regency_id"
                :options="lookups.districts" 
                :loading="lookupLoading.districts" 
                :hasError="lookupError.districts || !!fieldError('person.district_id', 'district_id')"
                placeholder="Pilih Kecamatan"
                emptyMessage="Data Kecamatan belum tersedia"
              />
              <div v-if="fieldError('person.district_id', 'district_id')" class="error-message">{{ fieldError('person.district_id', 'district_id') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Kelurahan / Desa</label>
              <BaseSelect 
                id="village" 
                v-model="form.person.village_id" 
                :disabled="!form.person.district_id"
                :options="lookups.villages" 
                :loading="lookupLoading.villages" 
                :hasError="lookupError.villages || !!fieldError('person.village_id', 'village_id')"
                placeholder="Pilih Kelurahan"
                emptyMessage="Data Kelurahan belum tersedia"
              />
              <div v-if="fieldError('person.village_id', 'village_id')" class="error-message">{{ fieldError('person.village_id', 'village_id') }}</div>
            </div>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Kode Pos</label>
              <input type="text" v-model="form.person.postal_code" class="form-input" :class="{ 'has-error': fieldError('person.postal_code', 'postal_code') }" maxlength="10">
              <div v-if="fieldError('person.postal_code', 'postal_code')" class="error-message">{{ fieldError('person.postal_code', 'postal_code') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Nomor HP</label>
              <input type="tel" v-model="form.person.phone" class="form-input" :class="{ 'has-error': fieldError('person.phone', 'phone') }" maxlength="20" placeholder="Contoh: 081234567890">
              <div v-if="fieldError('person.phone', 'phone')" class="error-message">{{ fieldError('person.phone', 'phone') }}</div>
            </div>
          </div>
        </div>

        <!-- Profil Profesional Section -->
        <div class="section-card">
          <h3 class="section-title">3. Profil Profesional (Dokter)</h3>
          
          <div class="form-grid" >
            <div class="form-group">
              <label class="form-label required">Spesialisasi</label>
              <BaseSelect 
                id="specialization" 
                v-model="form.professional.specialization_id" 
                :options="lookups.specializations" 
                :loading="lookupLoading.specializations" 
                :hasError="lookupError.specializations || !!fieldError('professional.specialization_id', 'specialization_id')"
                placeholder="Pilih Spesialisasi"
                emptyMessage="Data Spesialisasi belum tersedia"
                required
              />
              <div v-if="fieldError('professional.specialization_id', 'specialization_id')" class="error-message">{{ fieldError('professional.specialization_id', 'specialization_id') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">No. STR</label>
              <input type="text" v-model="form.professional.str_number" class="form-input" :class="{ 'has-error': fieldError('professional.str_number', 'str_number') }">
              <div v-if="fieldError('professional.str_number', 'str_number')" class="error-message">{{ fieldError('professional.str_number', 'str_number') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">No. SIP</label>
              <input type="text" v-model="form.professional.sip_number" class="form-input" :class="{ 'has-error': fieldError('professional.sip_number', 'sip_number') }">
              <div v-if="fieldError('professional.sip_number', 'sip_number')" class="error-message">{{ fieldError('professional.sip_number', 'sip_number') }}</div>
            </div>
          </div>

          <div class="form-grid" >
            <div class="form-group">
              <label class="form-label">Berlaku SIP (Sampai)</label>
              <input type="date" v-model="form.professional.sip_valid_until" class="form-input" :class="{ 'has-error': fieldError('professional.sip_valid_until', 'sip_valid_until') }">
              <div v-if="fieldError('professional.sip_valid_until', 'sip_valid_until')" class="error-message">{{ fieldError('professional.sip_valid_until', 'sip_valid_until') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Kode BPJS DPJP</label>
              <input type="text" v-model="form.professional.bpjs_dpjp_code" class="form-input" :class="{ 'has-error': fieldError('professional.bpjs_dpjp_code', 'bpjs_dpjp_code') }">
              <div v-if="fieldError('professional.bpjs_dpjp_code', 'bpjs_dpjp_code')" class="error-message">{{ fieldError('professional.bpjs_dpjp_code', 'bpjs_dpjp_code') }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">IHS Number (SatuSehat)</label>
              <input type="text" v-model="form.person.ihs_number" class="form-input" :class="{ 'has-error': fieldError('person.ihs_number', 'ihs_number') }">
              <div v-if="fieldError('person.ihs_number', 'ihs_number')" class="error-message">{{ fieldError('person.ihs_number', 'ihs_number') }}</div>
            </div>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Tanda Tangan</label>
              <input 
                type="file" 
                ref="fileInput"
                @change="handleFileChange" 
                class="form-input"
                accept="image/png, image/jpeg, image/jpg"
                :class="{ 'has-error': fieldError('signature') }"
              >
              <div v-if="fieldError('signature')" class="error-message">{{ fieldError('signature') }}</div>
              
              <div v-if="previewImage" class="signature-preview">
                <img :src="previewImage" alt="Preview Tanda Tangan" />
                <button type="button" class="btn-remove-sig" @click="removeSignature">Hapus</button>
              </div>
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
.signature-preview {
  margin-top: 12px;
  border: 2px dashed #cbd5e1;
  padding: 12px;
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  border-radius: 8px;
  background: #f8fafc;
}
.signature-preview img {
  max-width: 200px;
  max-height: 100px;
  display: block;
  border-radius: 4px;
}
.btn-remove-sig {
  margin-top: 8px;
  background: #fee2e2;
  color: #ef4444;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 500;
}
.btn-remove-sig:hover {
  background: #fca5a5;
  color: #991b1b;
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
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 0;
  color: var(--color-danger);
  gap: 1rem;
}
</style>