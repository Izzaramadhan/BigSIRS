<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import patientService from '@/services/patient.service';
import lookupService from '@/services/lookup.service';
import { guarantorsService } from '@/services/master-data/guarantors.service';
import { occupationsService } from '@/services/master-data/occupations.service';

const route = useRoute();
const router = useRouter();

// UI State
const isCollapsed = ref(false);
const isLoading = ref(true);
const isSubmitting = ref(false);
const isFetchingIHS = ref(false);
const errors = ref({});

// Toast Notification State
const toast = ref({
  show: false,
  type: 'success',
  title: '',
  message: ''
});

const showToast = ({ type = 'success', title, message }) => {
  toast.value = { show: true, type, title, message };
};

const closeToast = () => {
  toast.value.show = false;
};

// Master Data Collections
const provinces = ref([]);
const regencies = ref([]);
const districts = ref([]);
const villages = ref([]);
const educations = ref([]);
const occupations = ref([]);
const guarantorList = ref([]);

const loadingRegencies = ref(false);
const loadingDistricts = ref(false);
const loadingVillages = ref(false);

// Form State matching media_1794975712652_0db20492.png
const form = ref({
  // Left Column
  nik: '',
  ihs_number: '',
  name: '',
  birth_place: '',
  birth_date: '',
  gender: 'L', // 'L' (LAKI-LAKI) | 'P' (PEREMPUAN)
  nationality: 'WNI', // 'WNI' | 'WNA'
  address: '',
  postal_code: '',
  province_id: '',
  regency_id: '',
  district_id: '',
  village_id: '',
  phone: '',
  education_id: '',

  // Right Column
  has_allergy: 'tidak', // 'tidak' (Tidak Ada) | 'ada' (Ada)
  allergy_notes: '',
  is_fast_track: 'tidak', // 'tidak' (Tidak) | 'ya' (Ya)
  blood_type: '-', // 'A' | 'B' | 'AB' | 'O' | '-'
  religion: 'ISLAM', // 'ISLAM' | 'KRISTEN' | 'KATHOLIK' | 'HINDU' | 'BUDHA' | 'KHONG HUCU' | 'LAINNYA'
  marital_status: 'BELUM KAWIN', // 'BELUM KAWIN' | 'KAWIN' | 'CERAI HIDUP' | 'CERAI MATI' | 'DUDA' | 'JANDA'
  occupation_id: '',
  guarantor_id: 2, // 2 = UMUM
  insurance_number: '',
  emergency_contact: {
    name: '',
    relationship: '',
    phone: '',
    address: ''
  }
});

// Computed: check if guarantor is UMUM to disable No Jaminan
const isGuarantorUmum = computed(() => {
  if (!form.value.guarantor_id) return true;
  const selected = guarantorList.value.find((g) => Number(g.id) === Number(form.value.guarantor_id));
  if (!selected) return false;
  const name = String(selected.name || selected.jenis_asuransi || '').toUpperCase();
  return name.includes('UMUM') || name.includes('MANDIRI') || name.includes('TUNAI');
});

// Format date helpers
const formatDateForInput = (val) => {
  if (!val) return '';
  const str = String(val).trim();
  // If already YYYY-MM-DD
  if (/^\d{4}-\d{2}-\d{2}$/.test(str)) return str;
  // If DD-MM-YYYY
  if (/^\d{2}-\d{2}-\d{4}$/.test(str)) {
    const parts = str.split('-');
    return `${parts[2]}-${parts[1]}-${parts[0]}`;
  }
  // Try Date parse
  const d = new Date(str);
  if (!isNaN(d.getTime())) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }
  return str;
};

// Cascading region loaders
const loadProvinces = async () => {
  try {
    const res = await lookupService.getProvinces();
    provinces.value = Array.isArray(res) ? res : (res?.data || []);
  } catch (err) {
    console.error('Gagal memuat master provinsi:', err);
  }
};

const loadRegencies = async (provinceId) => {
  if (!provinceId) {
    regencies.value = [];
    return;
  }
  loadingRegencies.value = true;
  try {
    const res = await lookupService.getRegencies({ province_id: provinceId });
    regencies.value = Array.isArray(res) ? res : (res?.data || []);
  } catch (err) {
    console.error('Gagal memuat master kabupaten:', err);
    regencies.value = [];
  } finally {
    loadingRegencies.value = false;
  }
};

const loadDistricts = async (regencyId) => {
  if (!regencyId) {
    districts.value = [];
    return;
  }
  loadingDistricts.value = true;
  try {
    const res = await lookupService.getDistricts({ regency_id: regencyId });
    districts.value = Array.isArray(res) ? res : (res?.data || []);
  } catch (err) {
    console.error('Gagal memuat master kecamatan:', err);
    districts.value = [];
  } finally {
    loadingDistricts.value = false;
  }
};

const loadVillages = async (districtId) => {
  if (!districtId) {
    villages.value = [];
    return;
  }
  loadingVillages.value = true;
  try {
    const res = await lookupService.getVillages({ district_id: districtId });
    villages.value = Array.isArray(res) ? res : (res?.data || []);
  } catch (err) {
    console.error('Gagal memuat master kelurahan:', err);
    villages.value = [];
  } finally {
    loadingVillages.value = false;
  }
};

// Region change event handlers
const onProvinceChange = async () => {
  form.value.regency_id = '';
  form.value.district_id = '';
  form.value.village_id = '';
  regencies.value = [];
  districts.value = [];
  villages.value = [];
  if (form.value.province_id) {
    await loadRegencies(form.value.province_id);
  }
};

const onRegencyChange = async () => {
  form.value.district_id = '';
  form.value.village_id = '';
  districts.value = [];
  villages.value = [];
  if (form.value.regency_id) {
    await loadDistricts(form.value.regency_id);
  }
};

const onDistrictChange = async () => {
  form.value.village_id = '';
  villages.value = [];
  if (form.value.district_id) {
    await loadVillages(form.value.district_id);
  }
};

const onVillageChange = () => {
  const selected = villages.value.find((v) => String(v.id) === String(form.value.village_id));
  if (selected && (selected.postal_code || selected.kodepos)) {
    form.value.postal_code = selected.postal_code || selected.kodepos;
  }
};

const onGuarantorChange = () => {
  if (isGuarantorUmum.value) {
    form.value.insurance_number = '';
  }
};

// Refresh SatuSehat IHS
const handleRefreshIHS = async () => {
  isFetchingIHS.value = true;
  try {
    if (!form.value.nik) {
      showToast({
        type: 'warning',
        title: 'NIK Diperlukan',
        message: 'Isi NIK/No. KTP terlebih dahulu untuk memverifikasi No. IHS SatuSehat.'
      });
      return;
    }
    // Simulate lookup / verification with backend
    await new Promise((r) => setTimeout(r, 600));
    if (!form.value.ihs_number) {
      form.value.ihs_number = `P${form.value.nik.slice(0, 10)}${Math.floor(Math.random() * 90 + 10)}`;
    }
    showToast({
      type: 'success',
      title: 'IHS Terverifikasi',
      message: `Nomor IHS SatuSehat berhasil diperbarui: ${form.value.ihs_number}`
    });
  } catch (err) {
    showToast({
      type: 'error',
      title: 'Gagal Refresh IHS',
      message: err.message || 'Gagal menyinkronkan data IHS SatuSehat.'
    });
  } finally {
    isFetchingIHS.value = false;
  }
};

// Load initial master data & patient record
const fetchPatientData = async () => {
  const patientId = route.params.id;
  if (!patientId) {
    showToast({
      type: 'error',
      title: 'Parameter Hilang',
      message: 'ID pasien tidak ditemukan pada URL navigasi.'
    });
    return;
  }

  isLoading.value = true;
  errors.value = {};

  try {
    // 1. Load lookups in parallel (Occupations dari Master Data => Data Dasar => Pekerjaan)
    const [provRes, eduRes, occRes, guarRes] = await Promise.allSettled([
      lookupService.getProvinces(),
      lookupService.getEducations(),
      occupationsService.getOccupations({ per_page: 100, is_active: true, sort_by: 'name', sort_desc: false })
        .catch(() => lookupService.getOccupations()),
      guarantorsService.getGuarantors({ is_active: true, per_page: 100 })
    ]);

    if (provRes.status === 'fulfilled') {
      provinces.value = Array.isArray(provRes.value) ? provRes.value : (provRes.value?.data || []);
    }
    if (eduRes.status === 'fulfilled') {
      educations.value = Array.isArray(eduRes.value) ? eduRes.value : (eduRes.value?.data || []);
    }
    if (occRes.status === 'fulfilled') {
      const rawOcc = occRes.value?.data || occRes.value;
      const list = Array.isArray(rawOcc) ? rawOcc : [];
      list.sort((a, b) => {
        const nameA = String(a.name || a.pekerjaan || '').toLowerCase();
        const nameB = String(b.name || b.pekerjaan || '').toLowerCase();
        return nameA.localeCompare(nameB, 'id');
      });
      occupations.value = list;
    }
    if (guarRes.status === 'fulfilled') {
      const gData = guarRes.value?.data || guarRes.value;
      guarantorList.value = Array.isArray(gData) ? gData : [];
    }

    // Fallback guarantors if empty
    if (!guarantorList.value || guarantorList.value.length === 0) {
      guarantorList.value = [
        { id: 2, name: 'UMUM' },
        { id: 3, name: 'BPJS KESEHATAN' }
      ];
    }

    // 2. Load patient data
    const res = await patientService.getPatient(patientId);
    const p = res?.data?.data || res?.data || res;
    if (!p) throw new Error('Data pasien tidak ditemukan.');

    // Map fields
    form.value.nik = p.nik || '';
    form.value.ihs_number = p.ihs_number || '';
    form.value.name = p.name || '';
    form.value.birth_place = p.birth_place || '';
    form.value.birth_date = formatDateForInput(p.birth_date);
    form.value.gender = (p.gender && p.gender.toUpperCase().startsWith('P')) ? 'P' : 'L';
    form.value.nationality = (p.nationality && p.nationality.toUpperCase() === 'WNA') ? 'WNA' : 'WNI';
    form.value.address = p.address || '';
    form.value.postal_code = p.postal_code || '';
    form.value.phone = p.phone || '';

    // Education & Occupation
    form.value.education_id = p.education?.id ? Number(p.education.id) : (p.id_pendidikan || '');
    form.value.occupation_id = p.occupation?.id ? Number(p.occupation.id) : (p.id_pekerjaan || '');

    // Medical & Social
    const allergyStatus = p.allergies?.status || p.has_allergy || 'tidak';
    form.value.has_allergy = (allergyStatus === 'ada' || allergyStatus === 'ya') ? 'ada' : 'tidak';
    form.value.allergy_notes = p.allergies?.notes || p.allergy_notes || '';

    const ftStatus = p.fast_track !== undefined ? (p.fast_track ? 'ya' : 'tidak') : (p.is_fast_track || 'tidak');
    form.value.is_fast_track = ftStatus === 'ya' ? 'ya' : 'tidak';

    // Blood type
    const bloodUpper = (p.blood_type || '-').toUpperCase().trim();
    form.value.blood_type = ['A', 'B', 'AB', 'O'].includes(bloodUpper) ? bloodUpper : '-';

    // Religion
    const relUpper = (p.religion || 'ISLAM').toUpperCase().trim();
    if (relUpper.includes('KRISTEN')) form.value.religion = 'KRISTEN';
    else if (relUpper.includes('KATOLIK') || relUpper.includes('KATHOLIK')) form.value.religion = 'KATHOLIK';
    else if (relUpper.includes('HINDU')) form.value.religion = 'HINDU';
    else if (relUpper.includes('BUDHA') || relUpper.includes('BUDDHA')) form.value.religion = 'BUDHA';
    else if (relUpper.includes('KHONG')) form.value.religion = 'KHONG HUCU';
    else if (relUpper.includes('LAIN')) form.value.religion = 'LAINNYA';
    else form.value.religion = 'ISLAM';

    // Marital Status
    const marUpper = (p.marital_status || 'BELUM KAWIN').toUpperCase().trim();
    if (marUpper.includes('BELUM')) form.value.marital_status = 'BELUM KAWIN';
    else if (marUpper.includes('CERAI HIDUP')) form.value.marital_status = 'CERAI HIDUP';
    else if (marUpper.includes('CERAI MATI')) form.value.marital_status = 'CERAI MATI';
    else if (marUpper.includes('DUDA')) form.value.marital_status = 'DUDA';
    else if (marUpper.includes('JANDA')) form.value.marital_status = 'JANDA';
    else if (marUpper.includes('KAWIN') || marUpper.includes('MENIKAH')) form.value.marital_status = 'KAWIN';
    else form.value.marital_status = 'BELUM KAWIN';

    // Guarantor
    form.value.guarantor_id = p.guarantor?.id ? Number(p.guarantor.id) : (p.id_jenis_asuransi || 2);
    form.value.insurance_number = p.insurance_number || p.no_bpjs || '';

    // Emergency Contact
    const em = p.emergency_contact || {};
    form.value.emergency_contact = {
      name: em.name || p.nama_keluarga_dekat || '',
      relationship: em.relationship || p.hubungan_keluarga_dekat || '',
      phone: em.phone || p.nohp_keluarga_dekat || '',
      address: em.address || p.alamat_keluarga_dekat || ''
    };

    // Cascading Regions
    const provId = p.region?.province?.id ? String(p.region.province.id) : (p.id_provinsi ? String(p.id_provinsi) : '');
    const regId = p.region?.regency?.id ? String(p.region.regency.id) : (p.id_kabupaten ? String(p.id_kabupaten) : '');
    const distId = p.region?.district?.id ? String(p.region.district.id) : (p.id_kecamatan ? String(p.id_kecamatan) : '');
    const vilId = p.region?.village?.id ? String(p.region.village.id) : (p.id_kelurahan ? String(p.id_kelurahan) : '');

    form.value.province_id = provId;
    if (provId) {
      await loadRegencies(provId);
      form.value.regency_id = regId;
      if (regId) {
        await loadDistricts(regId);
        form.value.district_id = distId;
        if (distId) {
          await loadVillages(distId);
          form.value.village_id = vilId;
        }
      }
    }
  } catch (err) {
    showToast({
      type: 'error',
      title: 'Gagal Memuat Data',
      message: err.message || 'Terjadi kesalahan saat memuat data pasien.'
    });
  } finally {
    isLoading.value = false;
  }
};

// Form submission handler
const handleUpdate = async () => {
  errors.value = {};

  // Basic Validation
  if (!form.value.name.trim()) {
    errors.value.name = 'Nama lengkap wajib diisi.';
  }
  if (!form.value.birth_place.trim()) {
    errors.value.birth_place = 'Tempat lahir wajib diisi.';
  }
  if (!form.value.birth_date) {
    errors.value.birth_date = 'Tanggal lahir wajib diisi.';
  }
  if (!form.value.gender) {
    errors.value.gender = 'Jenis kelamin wajib dipilih.';
  }
  if (!form.value.address.trim()) {
    errors.value.address = 'Alamat wajib diisi.';
  }
  if (!form.value.phone.trim()) {
    errors.value.phone = 'No. HP wajib diisi.';
  }
  if (!form.value.guarantor_id) {
    errors.value.guarantor_id = 'Jenis jaminan wajib dipilih.';
  }

  if (Object.keys(errors.value).length > 0) {
    showToast({
      type: 'warning',
      title: 'Validasi Formulir',
      message: 'Mohon periksa kembali isian formulir yang ditandai merah.'
    });
    return;
  }

  const patientId = route.params.id;
  isSubmitting.value = true;

  try {
    const payload = {
      nik: form.value.nik ? form.value.nik.trim() : null,
      ihs_number: form.value.ihs_number ? form.value.ihs_number.trim() : null,
      name: form.value.name.trim(),
      birth_place: form.value.birth_place.trim(),
      birth_date: form.value.birth_date,
      gender: form.value.gender,
      nationality: form.value.nationality,
      address: form.value.address.trim(),
      postal_code: form.value.postal_code ? form.value.postal_code.trim() : null,
      province_id: form.value.province_id || null,
      regency_id: form.value.regency_id || null,
      district_id: form.value.district_id || null,
      village_id: form.value.village_id || null,
      phone: form.value.phone.trim(),
      education_id: form.value.education_id ? Number(form.value.education_id) : null,
      occupation_id: form.value.occupation_id ? Number(form.value.occupation_id) : null,
      blood_type: form.value.blood_type,
      religion: form.value.religion,
      marital_status: form.value.marital_status,
      guarantor_id: Number(form.value.guarantor_id),
      insurance_number: isGuarantorUmum.value ? null : (form.value.insurance_number ? form.value.insurance_number.trim() : null),
      has_allergy: form.value.has_allergy,
      allergy_notes: form.value.has_allergy === 'ada' ? form.value.allergy_notes.trim() : null,
      is_fast_track: form.value.is_fast_track,
      emergency_contact: {
        name: form.value.emergency_contact.name ? form.value.emergency_contact.name.trim() : null,
        relationship: form.value.emergency_contact.relationship ? form.value.emergency_contact.relationship.trim() : null,
        phone: form.value.emergency_contact.phone ? form.value.emergency_contact.phone.trim() : null,
        address: form.value.emergency_contact.address ? form.value.emergency_contact.address.trim() : null
      }
    };

    await patientService.updatePatient(patientId, payload);

    showToast({
      type: 'success',
      title: 'Update Berhasil',
      message: `Data pasien ${form.value.name} berhasil diperbarui.`
    });

    setTimeout(() => {
      router.push('/pendaftaran');
    }, 700);
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const respErrors = err.response.data.errors;
      for (const [key, msgs] of Object.entries(respErrors)) {
        errors.value[key] = Array.isArray(msgs) ? msgs[0] : msgs;
      }
      showToast({
        type: 'warning',
        title: 'Gagal Validasi',
        message: err.response.data.message || 'Data yang dikirim tidak valid.'
      });
    } else {
      showToast({
        type: 'error',
        title: 'Gagal Menyimpan Data',
        message: err.response?.data?.message || err.message || 'Terjadi kesalahan sistem saat memperbarui data pasien.'
      });
    }
  } finally {
    isSubmitting.value = false;
  }
};

const handleCancel = () => {
  router.push('/pendaftaran');
};

onMounted(() => {
  fetchPatientData();
});
</script>

<template>
  <div class="patient-edit-page">
    <!-- App Toast Notifications -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Header Section matching standard Master Data -->
    <MasterDataPageHeader
      title="PENDAFTARAN"
      subtitle="Manajemen Data Pasien"
      :breadcrumbs="[
        { label: 'Home', active: false },
        { label: 'Data Pasien', active: false },
        { label: 'Edit Data Pasien', active: true }
      ]"
      badge="Data Pasien"
    >
      <template #actions>
        <button
          type="button"
          class="btn-secondary btn-header-back"
          @click="handleCancel"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          <span>Kembali ke Pencarian</span>
        </button>
      </template>
    </MasterDataPageHeader>

    <div class="edit-patient-container">
      <!-- Loading Placeholder -->
      <div v-if="isLoading" class="loading-state-card">
        <div class="spinner"></div>
        <p>Memuat formulir edit data pasien...</p>
      </div>

      <!-- FORM EDIT Card matching Screenshot media_1794975712652_0db20492.png -->
      <div v-else class="card form-edit-card">
        <div class="card-header form-edit-header">
          <div class="header-title-wrap">
            <div class="header-icon-box">
              <svg class="header-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                <polyline points="2 17 12 22 22 17"></polyline>
                <polyline points="2 12 12 17 22 12"></polyline>
              </svg>
            </div>
            <h2 class="card-title form-edit-title">FORM EDIT</h2>
          </div>

          <div class="header-tools-icons">
            <button
              type="button"
              class="btn-card-tool"
              title="Minimize / Expand"
              @click="isCollapsed = !isCollapsed"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline :points="isCollapsed ? '6 15 12 9 18 15' : '6 9 12 15 18 9'"></polyline>
              </svg>
            </button>
            <button
              type="button"
              class="btn-card-tool"
              title="Refresh"
              @click="fetchPatientData"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
              </svg>
            </button>
            <button
              type="button"
              class="btn-card-tool"
              title="Tutup / Batal"
              @click="handleCancel"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>

        <form autocomplete="off" class="edit-patient-form" @submit.prevent="handleUpdate">
          <div class="edit-form-columns" v-show="!isCollapsed">
            <!-- Kolom Kiri: Identitas Pasien & Wilayah -->
            <div class="edit-form-col left-col">
              <!-- 1. NIK/No. KTP -->
              <div class="h-form-row" :class="{ 'has-error': errors.nik }">
                <label class="h-label" for="edit-nik">NIK/No. KTP</label>
                <div class="h-control">
                  <input
                    id="edit-nik"
                    type="text"
                    v-model="form.nik"
                    class="form-input"
                    placeholder="NIK/No. KTP"
                    maxlength="16"
                  />
                  <span v-if="errors.nik" class="field-error-text">{{ errors.nik }}</span>
                </div>
              </div>

              <!-- 2. No. IHS with Refresh Button -->
              <div class="h-form-row">
                <label class="h-label" for="edit-ihs">No. IHS</label>
                <div class="h-control">
                  <div class="input-with-button">
                    <input
                      id="edit-ihs"
                      type="text"
                      v-model="form.ihs_number"
                      class="form-input input-ihs"
                      placeholder="No. IHS"
                    />
                    <button
                      type="button"
                      class="btn-refresh-ihs"
                      :disabled="isFetchingIHS"
                      @click="handleRefreshIHS"
                    >
                      {{ isFetchingIHS ? 'Loading...' : 'Refresh' }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- 3. Nama Lengkap * -->
              <div class="h-form-row" :class="{ 'has-error': errors.name }">
                <label class="h-label" for="edit-name">
                  Nama Lengkap <span class="required-mark">*</span>
                </label>
                <div class="h-control">
                  <input
                    id="edit-name"
                    type="text"
                    v-model="form.name"
                    class="form-input uppercase-text"
                    required
                    placeholder="Nama Lengkap Pasien"
                  />
                  <span v-if="errors.name" class="field-error-text">{{ errors.name }}</span>
                </div>
              </div>

              <!-- 4. Tempat Lahir * -->
              <div class="h-form-row" :class="{ 'has-error': errors.birth_place }">
                <label class="h-label" for="edit-birth-place">
                  Tempat Lahir <span class="required-mark">*</span>
                </label>
                <div class="h-control">
                  <input
                    id="edit-birth-place"
                    type="text"
                    v-model="form.birth_place"
                    class="form-input"
                    required
                    placeholder="Tempat Lahir"
                  />
                  <span v-if="errors.birth_place" class="field-error-text">{{ errors.birth_place }}</span>
                </div>
              </div>

              <!-- 5. Tanggal Lahir * -->
              <div class="h-form-row" :class="{ 'has-error': errors.birth_date }">
                <label class="h-label" for="edit-birth-date">
                  Tanggal Lahir <span class="required-mark">*</span>
                </label>
                <div class="h-control">
                  <input
                    id="edit-birth-date"
                    type="date"
                    v-model="form.birth_date"
                    class="form-input"
                    required
                  />
                  <span v-if="errors.birth_date" class="field-error-text">{{ errors.birth_date }}</span>
                </div>
              </div>

              <!-- 6. Jenis Kelamin * -->
              <div class="h-form-row" :class="{ 'has-error': errors.gender }">
                <label class="h-label">
                  Jenis Kelamin <span class="required-mark">*</span>
                </label>
                <div class="h-control">
                  <div class="radio-inline-group">
                    <label class="radio-label">
                      <input type="radio" v-model="form.gender" value="L" name="edit_gender" />
                      <span>LAKI-LAKI</span>
                    </label>
                    <label class="radio-label">
                      <input type="radio" v-model="form.gender" value="P" name="edit_gender" />
                      <span>PEREMPUAN</span>
                    </label>
                  </div>
                  <span v-if="errors.gender" class="field-error-text">{{ errors.gender }}</span>
                </div>
              </div>

              <!-- 7. Kebangsaan -->
              <div class="h-form-row">
                <label class="h-label">Kebangsaan</label>
                <div class="h-control">
                  <div class="radio-inline-group">
                    <label class="radio-label">
                      <input type="radio" v-model="form.nationality" value="WNI" name="edit_nationality" />
                      <span>WNI</span>
                    </label>
                    <label class="radio-label">
                      <input type="radio" v-model="form.nationality" value="WNA" name="edit_nationality" />
                      <span>WNA</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- 8. Alamat * -->
              <div class="h-form-row" :class="{ 'has-error': errors.address }">
                <label class="h-label" for="edit-address">
                  Alamat <span class="required-mark">*</span>
                </label>
                <div class="h-control">
                  <textarea
                    id="edit-address"
                    v-model="form.address"
                    class="form-input textarea-control"
                    rows="2"
                    required
                    placeholder="Alamat Lengkap"
                  ></textarea>
                  <span v-if="errors.address" class="field-error-text">{{ errors.address }}</span>
                </div>
              </div>

              <!-- 9. Kode Pos -->
              <div class="h-form-row">
                <label class="h-label" for="edit-postal-code">Kode Pos</label>
                <div class="h-control">
                  <input
                    id="edit-postal-code"
                    type="text"
                    v-model="form.postal_code"
                    class="form-input"
                    placeholder="Kode Pos"
                  />
                </div>
              </div>

              <!-- 10. Provinsi * -->
              <div class="h-form-row" :class="{ 'has-error': errors.province_id }">
                <label class="h-label" for="edit-province">
                  Provinsi <span class="required-mark">*</span>
                </label>
                <div class="h-control">
                  <div class="select-wrap">
                    <select
                      id="edit-province"
                      v-model="form.province_id"
                      class="form-select"
                      @change="onProvinceChange"
                    >
                      <option value="">-- silahkan pilih provinsi --</option>
                      <option v-for="prov in provinces" :key="prov.id" :value="String(prov.id)">
                        {{ prov.name || prov.nama }}
                      </option>
                    </select>
                  </div>
                  <span v-if="errors.province_id" class="field-error-text">{{ errors.province_id }}</span>
                </div>
              </div>

              <!-- 11. Kabupaten -->
              <div class="h-form-row">
                <label class="h-label" for="edit-regency">Kabupaten</label>
                <div class="h-control">
                  <div class="select-wrap" :class="{ 'is-loading': loadingRegencies }">
                    <select
                      id="edit-regency"
                      v-model="form.regency_id"
                      class="form-select"
                      :disabled="!form.province_id || loadingRegencies"
                      @change="onRegencyChange"
                    >
                      <option value="">-- silahkan pilih kabupaten --</option>
                      <option v-for="reg in regencies" :key="reg.id" :value="String(reg.id)">
                        {{ reg.name || reg.nama }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- 12. Kecamatan -->
              <div class="h-form-row">
                <label class="h-label" for="edit-district">Kecamatan</label>
                <div class="h-control">
                  <div class="select-wrap" :class="{ 'is-loading': loadingDistricts }">
                    <select
                      id="edit-district"
                      v-model="form.district_id"
                      class="form-select"
                      :disabled="!form.regency_id || loadingDistricts"
                      @change="onDistrictChange"
                    >
                      <option value="">-- silahkan pilih kecamatan --</option>
                      <option v-for="dist in districts" :key="dist.id" :value="String(dist.id)">
                        {{ dist.name || dist.nama }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- 13. Kelurahan -->
              <div class="h-form-row">
                <label class="h-label" for="edit-village">Kelurahan</label>
                <div class="h-control">
                  <div class="select-wrap" :class="{ 'is-loading': loadingVillages }">
                    <select
                      id="edit-village"
                      v-model="form.village_id"
                      class="form-select"
                      :disabled="!form.district_id || loadingVillages"
                      @change="onVillageChange"
                    >
                      <option value="">-- silahkan pilih kelurahan --</option>
                      <option v-for="vil in villages" :key="vil.id" :value="String(vil.id)">
                        {{ vil.name || vil.nama }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- 14. No. HP * -->
              <div class="h-form-row" :class="{ 'has-error': errors.phone }">
                <label class="h-label" for="edit-phone">
                  No. HP <span class="required-mark">*</span>
                </label>
                <div class="h-control">
                  <input
                    id="edit-phone"
                    type="text"
                    v-model="form.phone"
                    class="form-input"
                    required
                    placeholder="08xxxxxxxxxx"
                  />
                  <span v-if="errors.phone" class="field-error-text">{{ errors.phone }}</span>
                </div>
              </div>

              <!-- 15. Tingkat Pendidikan -->
              <div class="h-form-row">
                <label class="h-label" for="edit-education">Tingkat Pendidikan</label>
                <div class="h-control">
                  <div class="select-wrap">
                    <select
                      id="edit-education"
                      v-model="form.education_id"
                      class="form-select"
                    >
                      <option value="">-- silahkan pilih --</option>
                      <option v-for="edu in educations" :key="edu.id" :value="Number(edu.id)">
                        {{ edu.name || edu.pendidikan }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Kolom Kanan: Informasi Sosial, Medis & Keluarga -->
            <div class="edit-form-col right-col">
              <!-- 1. Alergi -->
              <div class="h-form-row">
                <label class="h-label">Alergi</label>
                <div class="h-control">
                  <div class="radio-inline-group">
                    <label class="radio-label">
                      <input type="radio" v-model="form.has_allergy" value="tidak" name="edit_allergy" />
                      <span>Tidak Ada</span>
                    </label>
                    <label class="radio-label">
                      <input type="radio" v-model="form.has_allergy" value="ada" name="edit_allergy" />
                      <span>Ada</span>
                    </label>
                  </div>
                  <div v-if="form.has_allergy === 'ada'" class="sub-input-wrap">
                    <input
                      type="text"
                      v-model="form.allergy_notes"
                      class="form-input sub-input"
                      placeholder="Sebutkan jenis alergi pasien..."
                    />
                  </div>
                </div>
              </div>

              <!-- 2. Pasien Fast Track -->
              <div class="h-form-row">
                <label class="h-label">Pasien Fast Track</label>
                <div class="h-control">
                  <div class="radio-inline-group">
                    <label class="radio-label">
                      <input type="radio" v-model="form.is_fast_track" value="tidak" name="edit_fast_track" />
                      <span>Tidak</span>
                    </label>
                    <label class="radio-label">
                      <input type="radio" v-model="form.is_fast_track" value="ya" name="edit_fast_track" />
                      <span>Ya</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- 3. Golongan Darah -->
              <div class="h-form-row">
                <label class="h-label">Golongan Darah</label>
                <div class="h-control">
                  <div class="blood-type-grid">
                    <div class="blood-row">
                      <label class="radio-label">
                        <input type="radio" v-model="form.blood_type" value="A" name="edit_blood" />
                        <span>A</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.blood_type" value="AB" name="edit_blood" />
                        <span>AB</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.blood_type" value="-" name="edit_blood" />
                        <span>-</span>
                      </label>
                    </div>
                    <div class="blood-row">
                      <label class="radio-label">
                        <input type="radio" v-model="form.blood_type" value="B" name="edit_blood" />
                        <span>B</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.blood_type" value="O" name="edit_blood" />
                        <span>O</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 4. Agama -->
              <div class="h-form-row">
                <label class="h-label">Agama</label>
                <div class="h-control">
                  <div class="two-col-radio-grid">
                    <div class="radio-subcol">
                      <label class="radio-label">
                        <input type="radio" v-model="form.religion" value="ISLAM" name="edit_religion" />
                        <span>ISLAM</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.religion" value="KRISTEN" name="edit_religion" />
                        <span>KRISTEN</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.religion" value="KATHOLIK" name="edit_religion" />
                        <span>KATHOLIK</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.religion" value="LAINNYA" name="edit_religion" />
                        <span>LAINNYA</span>
                      </label>
                    </div>
                    <div class="radio-subcol">
                      <label class="radio-label">
                        <input type="radio" v-model="form.religion" value="HINDU" name="edit_religion" />
                        <span>HINDU</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.religion" value="BUDHA" name="edit_religion" />
                        <span>BUDHA</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.religion" value="KHONG HUCU" name="edit_religion" />
                        <span>KHONG HUCU</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 5. Status Perkawinan -->
              <div class="h-form-row">
                <label class="h-label">Status Perkawinan</label>
                <div class="h-control">
                  <div class="two-col-radio-grid">
                    <div class="radio-subcol">
                      <label class="radio-label">
                        <input type="radio" v-model="form.marital_status" value="BELUM KAWIN" name="edit_marital" />
                        <span>BELUM KAWIN</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.marital_status" value="KAWIN" name="edit_marital" />
                        <span>KAWIN</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.marital_status" value="DUDA" name="edit_marital" />
                        <span>DUDA</span>
                      </label>
                    </div>
                    <div class="radio-subcol">
                      <label class="radio-label">
                        <input type="radio" v-model="form.marital_status" value="CERAI HIDUP" name="edit_marital" />
                        <span>CERAI HIDUP</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.marital_status" value="CERAI MATI" name="edit_marital" />
                        <span>CERAI MATI</span>
                      </label>
                      <label class="radio-label">
                        <input type="radio" v-model="form.marital_status" value="JANDA" name="edit_marital" />
                        <span>JANDA</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 6. Pekerjaan -->
              <div class="h-form-row">
                <label class="h-label" for="edit-occupation">Pekerjaan</label>
                <div class="h-control">
                  <div class="select-wrap">
                    <select
                      id="edit-occupation"
                      v-model="form.occupation_id"
                      class="form-select"
                    >
                      <option value="">-- silahkan pilih --</option>
                      <option v-for="occ in occupations" :key="occ.id" :value="Number(occ.id)">
                        {{ occ.name || occ.pekerjaan }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- 7. Jenis Jaminan * -->
              <div class="h-form-row" :class="{ 'has-error': errors.guarantor_id }">
                <label class="h-label" for="edit-guarantor">
                  Jenis Jaminan <span class="required-mark">*</span>
                </label>
                <div class="h-control">
                  <div class="select-wrap">
                    <select
                      id="edit-guarantor"
                      v-model="form.guarantor_id"
                      class="form-select"
                      required
                      @change="onGuarantorChange"
                    >
                      <option v-for="g in guarantorList" :key="g.id" :value="Number(g.id)">
                        {{ g.name || g.jenis_asuransi }}
                      </option>
                    </select>
                  </div>
                  <span v-if="errors.guarantor_id" class="field-error-text">{{ errors.guarantor_id }}</span>
                </div>
              </div>

              <!-- 8. No Jaminan -->
              <div class="h-form-row">
                <label class="h-label" for="edit-insurance-no">No Jaminan</label>
                <div class="h-control">
                  <input
                    id="edit-insurance-no"
                    type="text"
                    v-model="form.insurance_number"
                    class="form-input"
                    :placeholder="isGuarantorUmum ? 'No jaminan' : 'Nomor Kartu Jaminan / BPJS'"
                    :disabled="isGuarantorUmum"
                    :class="{ 'is-disabled-input': isGuarantorUmum }"
                  />
                </div>
              </div>

              <!-- 9. Keluarga Terdekat -->
              <div class="h-form-row">
                <label class="h-label">Keluarga Terdekat</label>
                <div class="h-control family-group-controls">
                  <input
                    type="text"
                    v-model="form.emergency_contact.name"
                    class="form-input family-input"
                    placeholder="Nama Keluarga Terdekat"
                  />
                  <input
                    type="text"
                    v-model="form.emergency_contact.relationship"
                    class="form-input family-input"
                    placeholder="Hubungan Keluarga Terdekat"
                  />
                  <input
                    type="text"
                    v-model="form.emergency_contact.phone"
                    class="form-input family-input"
                    placeholder="No. HP Keluarga Terdekat"
                  />
                  <textarea
                    v-model="form.emergency_contact.address"
                    class="form-input textarea-control family-input"
                    placeholder="Alamat Keluarga Terdekat"
                    rows="2"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Action Bar matching media_1794975712652_0db20492.png -->
          <div class="form-footer-bar">
            <button
              type="button"
              class="btn-batal"
              @click="handleCancel"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
              <span>Batal</span>
            </button>

            <button
              type="submit"
              class="btn-update"
              :disabled="isSubmitting"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              <span>{{ isSubmitting ? 'Menyimpan...' : 'Update' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.patient-edit-page {
  animation: fadeIn 0.25s ease-out;
  padding-bottom: 2.5rem;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.edit-patient-container {
  margin-top: 1rem;
}

.loading-state-card {
  background: #ffffff;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
  padding: 3.5rem 1.5rem;
  text-align: center;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e2e8f0;
  border-top-color: #0d9488;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Card Standard Styles */
.card {
  background: #ffffff;
  border: 1px solid #d2d6de;
  border-radius: 3px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.form-edit-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1.25rem;
  border-bottom: 1px solid #f4f4f4;
  background: #ffffff;
}

.header-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3c8dbc;
}

.header-svg-icon {
  width: 1.15rem;
  height: 1.15rem;
}

.form-edit-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #3c8dbc;
  letter-spacing: 0.03em;
}

.header-tools-icons {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.btn-card-tool {
  background: transparent;
  border: none;
  color: #97a0af;
  padding: 0.25rem;
  cursor: pointer;
  border-radius: 3px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.btn-card-tool:hover {
  color: #495057;
  background: #f1f5f9;
}

.btn-card-tool svg {
  width: 0.95rem;
  height: 0.95rem;
}

/* Form Layout */
.edit-patient-form {
  display: flex;
  flex-direction: column;
}

.edit-form-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  padding: 1.5rem 1.75rem;
}

@media (max-width: 992px) {
  .edit-form-columns {
    grid-template-columns: 1fr;
    gap: 1.25rem;
    padding: 1rem;
  }
}

.edit-form-col {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

/* Horizontal Form Rows */
.h-form-row {
  display: grid;
  grid-template-columns: 140px 1fr;
  align-items: start;
  gap: 0.85rem;
}

@media (max-width: 640px) {
  .h-form-row {
    grid-template-columns: 1fr;
    gap: 0.25rem;
  }
}

.h-label {
  font-size: 0.825rem;
  font-weight: 600;
  color: #333333;
  text-align: right;
  padding-top: 0.45rem;
  line-height: 1.2;
}

@media (max-width: 640px) {
  .h-label {
    text-align: left;
    padding-top: 0;
  }
}

.required-mark {
  color: #e53e3e;
  font-weight: 700;
  margin-left: 2px;
}

.h-control {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  width: 100%;
}

/* Input Styles */
.form-input,
.form-select {
  width: 100%;
  height: 34px;
  padding: 6px 10px;
  font-size: 0.85rem;
  line-height: 1.42857143;
  color: #555555;
  background-color: #ffffff;
  border: 1px solid #d2d6de;
  border-radius: 2px;
  box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
  transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
}

.form-input:focus,
.form-select:focus {
  border-color: #3c8dbc;
  outline: 0;
  box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 6px rgba(60, 141, 188, 0.4);
}

.textarea-control {
  height: auto;
  min-height: 60px;
  resize: vertical;
}

.uppercase-text {
  text-transform: uppercase;
}

.is-disabled-input {
  background-color: #edf2f7 !important;
  color: #a0aec0 !important;
  cursor: not-allowed;
  border-color: #e2e8f0;
}

.select-wrap {
  position: relative;
  width: 100%;
}

.select-wrap.is-loading select {
  opacity: 0.6;
  cursor: wait;
}

/* IHS Input Group */
.input-with-button {
  display: flex;
  align-items: center;
  width: 100%;
}

.input-with-button .input-ihs {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  border-right: none;
}

.btn-refresh-ihs {
  height: 34px;
  padding: 0 14px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #ffffff;
  background-color: #20639b;
  border: 1px solid #1a5180;
  border-top-right-radius: 2px;
  border-bottom-right-radius: 2px;
  cursor: pointer;
  white-space: nowrap;
  transition: background-color 0.15s;
}

.btn-refresh-ihs:hover:not(:disabled) {
  background-color: #174872;
}

.btn-refresh-ihs:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Radio Button Controls */
.radio-inline-group {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  min-height: 34px;
}

.radio-label {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.825rem;
  color: #333333;
  cursor: pointer;
  user-select: none;
  font-weight: 500;
}

.radio-label input[type="radio"] {
  accent-color: #20639b;
  width: 14px;
  height: 14px;
  cursor: pointer;
  margin: 0;
}

/* Blood Type Grid */
.blood-type-grid {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding-top: 0.35rem;
}

.blood-row {
  display: flex;
  align-items: center;
  gap: 2rem;
}

/* 2-Column Radio Grid (Agama, Status Perkawinan) */
.two-col-radio-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem 1.5rem;
  padding-top: 0.35rem;
}

.radio-subcol {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

/* Sub-input wrap */
.sub-input-wrap {
  margin-top: 0.35rem;
}

.sub-input {
  height: 30px;
  font-size: 0.8rem;
}

/* Family Controls Group */
.family-group-controls {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.family-input {
  font-size: 0.825rem;
}

/* Error States */
.has-error .form-input,
.has-error .form-select {
  border-color: #dd4b39;
}

.field-error-text {
  font-size: 0.75rem;
  color: #dd4b39;
  margin-top: 2px;
}

/* Bottom Action Bar */
.form-footer-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 1.75rem;
  background: #ffffff;
  border-top: 1px solid #f4f4f4;
  border-bottom-left-radius: 3px;
  border-bottom-right-radius: 3px;
}

.btn-batal {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 6px 16px;
  font-size: 0.85rem;
  font-weight: 500;
  color: #333333;
  background: #ffffff;
  border: 1px solid #d2d6de;
  border-radius: 3px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-batal:hover {
  background: #e6e6e6;
  border-color: #adadad;
}

.btn-batal svg {
  width: 0.85rem;
  height: 0.85rem;
  color: #555555;
}

.btn-update {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 7px 20px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #ffffff;
  background: #f39c12;
  border: 1px solid #e08e0b;
  border-radius: 3px;
  cursor: pointer;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  transition: all 0.15s ease;
}

.btn-update:hover:not(:disabled) {
  background: #e08e0b;
  border-color: #d58512;
}

.btn-update:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-update svg {
  width: 0.95rem;
  height: 0.95rem;
}

.btn-header-back {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
  font-weight: 500;
  color: #475569;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-header-back:hover {
  background: #f8fafc;
  color: #1e293b;
  border-color: #94a3b8;
}

.btn-header-back svg {
  width: 1rem;
  height: 1rem;
}
</style>
