<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import { mockPatients } from '@/mocks/patients';

const router = useRouter();

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

// Form State
const form = ref({
  // Left Column
  nik: '',
  ihs_number: '',
  name: '',
  birth_place: '',
  birth_date: '',
  gender: 'L', // L = LAKI-LAKI, P = PEREMPUAN
  nationality: 'WNI',
  address: '',
  region: '',
  postal_code: '',
  phone: '',
  education: '',

  // Right Column
  has_allergy: 'Tidak Ada',
  allergy_notes: '',
  is_fast_track: 'Tidak',
  blood_type: '-',
  religion: 'ISLAM',
  marital_status: 'BELUM KAWIN',
  occupation: '',
  guarantor: '',
  insurance_number: '',

  // Emergency Contact
  emergency_contact: {
    name: '',
    relationship: '',
    phone: '',
    address: ''
  },

  // Role / Category
  patient_category: 'PASIEN'
});

// UI States
const isSubmitting = ref(false);
const isFetchingIHS = ref(false);
const isSuccessModalOpen = ref(false);
const registeredPatient = ref(null);
const errors = ref({});

// Wilayah Autocomplete Options
const wilayahSearchQuery = ref('');
const isWilayahDropdownOpen = ref(false);
const mockWilayahList = [
  { kelurahan: 'Sosromenduran', kecamatan: 'Gedongtengen', kota: 'Kota Yogyakarta', provinsi: 'D.I. Yogyakarta', kodepos: '55271' },
  { kelurahan: 'Caturtunggal', kecamatan: 'Depok', kota: 'Kabupaten Sleman', provinsi: 'D.I. Yogyakarta', kodepos: '55281' },
  { kelurahan: 'Sinduharjo', kecamatan: 'Ngaglik', kota: 'Kabupaten Sleman', provinsi: 'D.I. Yogyakarta', kodepos: '55581' },
  { kelurahan: 'Trihanggo', kecamatan: 'Gamping', kota: 'Kabupaten Sleman', provinsi: 'D.I. Yogyakarta', kodepos: '55291' },
  { kelurahan: 'Banguntapan', kecamatan: 'Banguntapan', kota: 'Kabupaten Bantul', provinsi: 'D.I. Yogyakarta', kodepos: '55198' },
  { kelurahan: 'Mantrijeron', kecamatan: 'Mantrijeron', kota: 'Kota Yogyakarta', provinsi: 'D.I. Yogyakarta', kodepos: '55143' },
  { kelurahan: 'Kotagede', kecamatan: 'Kotagede', kota: 'Kota Yogyakarta', provinsi: 'D.I. Yogyakarta', kodepos: '55172' },
  { kelurahan: 'Condongcatur', kecamatan: 'Depok', kota: 'Kabupaten Sleman', provinsi: 'D.I. Yogyakarta', kodepos: '55283' },
  { kelurahan: 'Maguwoharjo', kecamatan: 'Depok', kota: 'Kabupaten Sleman', provinsi: 'D.I. Yogyakarta', kodepos: '55282' },
  { kelurahan: 'Sariharjo', kecamatan: 'Ngaglik', kota: 'Kabupaten Sleman', provinsi: 'D.I. Yogyakarta', kodepos: '55581' }
];

const filteredWilayah = computed(() => {
  if (!wilayahSearchQuery.value) return mockWilayahList;
  const q = wilayahSearchQuery.value.toLowerCase();
  return mockWilayahList.filter(w =>
    w.kelurahan.toLowerCase().includes(q) ||
    w.kecamatan.toLowerCase().includes(q) ||
    w.kota.toLowerCase().includes(q)
  );
});

const selectWilayah = (item) => {
  form.value.region = `${item.kelurahan}, ${item.kecamatan}, ${item.kota}`;
  wilayahSearchQuery.value = form.value.region;
  if (!form.value.postal_code) {
    form.value.postal_code = item.kodepos;
  }
  isWilayahDropdownOpen.value = false;
  if (errors.value.region) delete errors.value.region;
};

// Auto Age Calculation
const computedAge = computed(() => {
  if (!form.value.birth_date) return '';
  const birth = new Date(form.value.birth_date);
  const now = new Date();
  if (isNaN(birth.getTime())) return '';

  let years = now.getFullYear() - birth.getFullYear();
  let months = now.getMonth() - birth.getMonth();
  if (months < 0 || (months === 0 && now.getDate() < birth.getDate())) {
    years--;
    months = (months + 12) % 12;
  }
  return `${years} Thn ${months} Bln`;
});

// Tarik Nomor IHS SatuSehat (Mock Service)
const fetchIHS = () => {
  if (!form.value.nik || form.value.nik.length < 16) {
    showToast({
      type: 'warning',
      title: 'NIK Belum Lengkap',
      message: 'Masukkan 16 digit NIK terlebih dahulu untuk menarik No. IHS SatuSehat.'
    });
    return;
  }

  isFetchingIHS.value = true;
  setTimeout(() => {
    isFetchingIHS.value = false;
    const randomIhs = 'P' + Math.floor(1000000000 + Math.random() * 9000000000);
    form.value.ihs_number = randomIhs;
    showToast({
      type: 'success',
      title: 'IHS SatuSehat Terverifikasi',
      message: `Berhasil menarik No. IHS (${randomIhs}) dari Kemenkes SatuSehat.`
    });
  }, 600);
};

// Form Validation
const validateForm = () => {
  const err = {};
  if (!form.value.nik || form.value.nik.trim().length !== 16) {
    err.nik = 'NIK wajib diisi 16 digit angka sesuai KTP/KIA.';
  }
  if (!form.value.name || !form.value.name.trim()) {
    err.name = 'Nama lengkap wajib diisi.';
  }
  if (!form.value.birth_place || !form.value.birth_place.trim()) {
    err.birth_place = 'Tempat lahir wajib diisi.';
  }
  if (!form.value.birth_date) {
    err.birth_date = 'Tanggal lahir wajib diisi.';
  }
  if (!form.value.gender) {
    err.gender = 'Jenis kelamin wajib dipilih.';
  }
  if (!form.value.address || !form.value.address.trim()) {
    err.address = 'Alamat domisili wajib diisi.';
  }
  if (!form.value.region || !form.value.region.trim()) {
    err.region = 'Wilayah wajib dipilih/diisi.';
  }
  if (!form.value.phone || !form.value.phone.trim()) {
    err.phone = 'Nomor HP wajib diisi.';
  }
  if (!form.value.guarantor) {
    err.guarantor = 'Jenis jaminan wajib dipilih.';
  }

  errors.value = err;
  return Object.keys(err).length === 0;
};

// Reset Form
const handleReset = () => {
  form.value = {
    nik: '',
    ihs_number: '',
    name: '',
    birth_place: '',
    birth_date: '',
    gender: 'L',
    nationality: 'WNI',
    address: '',
    region: '',
    postal_code: '',
    phone: '',
    education: '',
    has_allergy: 'Tidak Ada',
    allergy_notes: '',
    is_fast_track: 'Tidak',
    blood_type: '-',
    religion: 'ISLAM',
    marital_status: 'BELUM KAWIN',
    occupation: '',
    guarantor: '',
    insurance_number: '',
    emergency_contact: {
      name: '',
      relationship: '',
      phone: '',
      address: ''
    },
    patient_category: 'PASIEN'
  };
  wilayahSearchQuery.value = '';
  errors.value = {};
};

// Submit Handler
const handleSubmit = () => {
  if (!validateForm()) {
    showToast({
      type: 'danger',
      title: 'Validasi Belum Lengkap',
      message: 'Periksa field bertanda bintang (*) yang belum terisi dengan benar.'
    });
    return;
  }

  isSubmitting.value = true;

  setTimeout(() => {
    isSubmitting.value = false;

    const lastId = mockPatients.length > 0 ? Math.max(...mockPatients.map(p => p.id)) : 0;
    const newId = lastId + 1;
    const paddedNum = String(newId).padStart(6, '0');
    const newRM = `RM-${paddedNum}`;

    const newPatient = {
      id: newId,
      medical_record_number: newRM,
      nik: form.value.nik,
      name: form.value.name,
      birth_place: form.value.birth_place,
      birth_date: form.value.birth_date,
      gender: form.value.gender,
      nationality: form.value.nationality,
      phone: form.value.phone,
      address: form.value.address,
      region: form.value.region,
      postal_code: form.value.postal_code,
      education: form.value.education || '-',
      occupation: form.value.occupation || '-',
      religion: form.value.religion,
      marital_status: form.value.marital_status,
      blood_type: form.value.blood_type,
      guarantor: form.value.guarantor,
      insurance_number: form.value.insurance_number || '-',
      allergies: form.value.has_allergy === 'Ada' ? (form.value.allergy_notes || 'Ada Alergi') : 'Tidak Ada',
      is_fast_track: form.value.is_fast_track === 'Ya',
      ihs_number: form.value.ihs_number || '-',
      emergency_contact: { ...form.value.emergency_contact },
      is_active: true,
      registered_at: new Date().toISOString().split('T')[0]
    };

    mockPatients.unshift(newPatient);
    registeredPatient.value = newPatient;
    isSuccessModalOpen.value = true;

    showToast({
      type: 'success',
      title: 'Pendaftaran Berhasil',
      message: `Pasien ${newPatient.name} telah didaftarkan dengan No. RM ${newRM}.`
    });
  }, 500);
};

const handleBatal = () => {
  if (form.value.nik || form.value.name) {
    if (confirm('Batalkan pengisian data? Perubahan belum disimpan akan hilang.')) {
      router.push('/pendaftaran');
    }
  } else {
    router.push('/pendaftaran');
  }
};

const navigateToAdmission = () => {
  isSuccessModalOpen.value = false;
  router.push('/admisi');
};

const registerAnother = () => {
  isSuccessModalOpen.value = false;
  handleReset();
  showToast({
    type: 'info',
    title: 'Form Siap',
    message: 'Formulir telah dikosongkan untuk pendaftaran pasien berikutnya.'
  });
};
</script>

<template>
  <div class="page-container new-patient-page">
    <!-- Notification Toast System -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Header Section -->
    <MasterDataPageHeader
      title="Pendaftaran Pasien Baru"
      subtitle="Manajemen Data Pasien — Formulir pendaftaran rekam medis dan kelengkapan data sosial pasien baru"
      :breadcrumbs="[
        { label: 'Home', active: false },
        { label: 'Data Pasien', active: false },
        { label: 'Pendaftaran Pasien Baru', active: true }
      ]"
      badge="Registrasi Pasien"
    >
      <template #actions>
        <button
          type="button"
          class="btn-secondary btn-header-action btn-header-back"
          @click="handleBatal"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
          <span>Kembali ke Pencarian</span>
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Main Content Container -->
    <div class="page-content">
      <div class="card form-card-container">
        <!-- Card Header: FORM TAMBAH -->
        <div class="form-card-header">
          <div class="header-left">
            <div class="header-icon-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <line x1="19" y1="8" x2="19" y2="14"></line>
                <line x1="22" y1="11" x2="16" y2="11"></line>
              </svg>
            </div>
            <div>
              <h2 class="card-title">FORM TAMBAH</h2>
              <p class="card-subtitle">Lengkapi formulir registrasi data sosial, kependudukan, penjamin dan kontak darurat pasien</p>
            </div>
          </div>
          <div class="header-right">
            <span class="required-hint"><span class="required-mark">*</span> Wajib diisi</span>
          </div>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="handleSubmit" class="registration-form">
          <div class="form-grid-layout">
            <!-- =================== KOLOM KIRI =================== -->
            <div class="form-col">
              <div class="section-title-bar">
                <span class="section-badge">1</span>
                <span class="section-heading">Data Identitas & Kependudukan</span>
              </div>

              <!-- NIK / No. KTP / KIA -->
              <div class="form-group" :class="{ 'has-error': errors.nik }">
                <div class="label-wrapper">
                  <label class="form-label" for="nik">
                    NIK/No. KTP/KIA <span class="required-mark">*</span>
                  </label>
                  <span class="counter-text" :class="{ 'text-primary font-bold': form.nik.length === 16 }">
                    {{ form.nik.length }}/16 Digit
                  </span>
                </div>
                <input
                  id="nik"
                  v-model="form.nik"
                  type="text"
                  maxlength="16"
                  class="form-input"
                  placeholder="NIK/No. KTP/KIA"
                  autocomplete="off"
                />
                <span v-if="errors.nik" class="error-message">{{ errors.nik }}</span>
              </div>

              <!-- No. IHS -->
              <div class="form-group">
                <label class="form-label" for="ihs">No. IHS</label>
                <div class="input-action-group">
                  <input
                    id="ihs"
                    v-model="form.ihs_number"
                    type="text"
                    class="form-input ihs-input"
                    placeholder="No. IHS"
                    autocomplete="off"
                  />
                  <button
                    type="button"
                    class="btn-refresh-ihs"
                    :disabled="isFetchingIHS"
                    @click="fetchIHS"
                    title="Tarik Nomor IHS dari Kemenkes SatuSehat"
                  >
                    <svg
                      :class="{ 'spin-anim': isFetchingIHS }"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <polyline points="23 4 23 10 17 10"></polyline>
                      <polyline points="1 20 1 14 7 14"></polyline>
                      <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                    <span>Refresh</span>
                  </button>
                </div>
              </div>

              <!-- Nama Lengkap -->
              <div class="form-group" :class="{ 'has-error': errors.name }">
                <label class="form-label" for="name">
                  Nama Lengkap <span class="required-mark">*</span>
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="form-input"
                  placeholder="Nama Lengkap"
                  autocomplete="off"
                />
                <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
              </div>

              <!-- Tempat & Tanggal Lahir -->
              <div class="form-row-2">
                <div class="form-group" :class="{ 'has-error': errors.birth_place }">
                  <label class="form-label" for="birth_place">
                    Tempat Lahir <span class="required-mark">*</span>
                  </label>
                  <input
                    id="birth_place"
                    v-model="form.birth_place"
                    type="text"
                    class="form-input"
                    placeholder="Tempat Lahir"
                    autocomplete="off"
                  />
                  <span v-if="errors.birth_place" class="error-message">{{ errors.birth_place }}</span>
                </div>

                <div class="form-group" :class="{ 'has-error': errors.birth_date }">
                  <div class="label-wrapper">
                    <label class="form-label" for="birth_date">
                      Tanggal Lahir <span class="required-mark">*</span>
                    </label>
                    <span v-if="computedAge" class="age-pill age-badge">{{ computedAge }}</span>
                  </div>
                  <input
                    id="birth_date"
                    v-model="form.birth_date"
                    type="date"
                    class="form-input"
                  />
                  <span v-if="errors.birth_date" class="error-message">{{ errors.birth_date }}</span>
                </div>
              </div>

              <!-- Jenis Kelamin -->
              <div class="form-group" :class="{ 'has-error': errors.gender }">
                <label class="form-label">
                  Jenis Kelamin <span class="required-mark">*</span>
                </label>
                <div class="radio-cards-row">
                  <label class="radio-item" :class="{ 'active': form.gender === 'L' }">
                    <input type="radio" v-model="form.gender" value="L" name="gender" />
                    <span class="radio-indicator"></span>
                    <span class="radio-text">LAKI-LAKI</span>
                  </label>
                  <label class="radio-item" :class="{ 'active': form.gender === 'P' }">
                    <input type="radio" v-model="form.gender" value="P" name="gender" />
                    <span class="radio-indicator"></span>
                    <span class="radio-text">PEREMPUAN</span>
                  </label>
                </div>
                <span v-if="errors.gender" class="error-message">{{ errors.gender }}</span>
              </div>

              <!-- Kebangsaan -->
              <div class="form-group">
                <label class="form-label">Kebangsaan</label>
                <div class="radio-cards-row">
                  <label class="radio-item" :class="{ 'active': form.nationality === 'WNI' }">
                    <input type="radio" v-model="form.nationality" value="WNI" name="nationality" />
                    <span class="radio-indicator"></span>
                    <span class="radio-text">WNI</span>
                  </label>
                  <label class="radio-item" :class="{ 'active': form.nationality === 'WNA' }">
                    <input type="radio" v-model="form.nationality" value="WNA" name="nationality" />
                    <span class="radio-indicator"></span>
                    <span class="radio-text">WNA</span>
                  </label>
                </div>
              </div>

              <!-- Alamat -->
              <div class="form-group" :class="{ 'has-error': errors.address }">
                <label class="form-label" for="address">
                  Alamat <span class="required-mark">*</span>
                </label>
                <textarea
                  id="address"
                  v-model="form.address"
                  class="form-textarea"
                  rows="3"
                  placeholder="Alamat (Desa/Jalan)"
                ></textarea>
                <span v-if="errors.address" class="error-message">{{ errors.address }}</span>
              </div>

              <!-- Wilayah (by Kelurahan/Kecamatan) -->
              <div class="form-group wilayah-picker-group" :class="{ 'has-error': errors.region }">
                <label class="form-label" for="wilayah">
                  Wilayah (by Kelurahan/Kecamatan) <span class="required-mark">*</span>
                </label>
                <div class="search-input-box">
                  <input
                    id="wilayah"
                    v-model="wilayahSearchQuery"
                    type="text"
                    class="form-input"
                    placeholder="Cari Wilayah (by Kelurahan/Kecamatan)"
                    autocomplete="off"
                    @focus="isWilayahDropdownOpen = true"
                    @input="isWilayahDropdownOpen = true"
                  />
                  <svg class="search-trailing-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                </div>

                <!-- Hasil Dropdown Wilayah -->
                <div v-if="isWilayahDropdownOpen" class="wilayah-popover">
                  <div
                    v-for="(w, idx) in filteredWilayah"
                    :key="idx"
                    class="wilayah-row"
                    @click="selectWilayah(w)"
                  >
                    <div class="row-main">
                      <strong>{{ w.kelurahan }}</strong>, Kec. {{ w.kecamatan }}
                    </div>
                    <div class="row-sub">
                      {{ w.kota }}, {{ w.provinsi }} &bull; <span class="kodepos-tag">Kode Pos: {{ w.kodepos }}</span>
                    </div>
                  </div>
                  <div v-if="filteredWilayah.length === 0" class="wilayah-empty-notice">
                    Wilayah tidak ditemukan di daftar cepat. Anda dapat mengetik manual.
                  </div>
                </div>
                <span v-if="errors.region" class="error-message">{{ errors.region }}</span>
              </div>

              <!-- Kode Pos & No. HP -->
              <div class="form-row-2">
                <div class="form-group">
                  <label class="form-label" for="postal_code">Kode Pos</label>
                  <input
                    id="postal_code"
                    v-model="form.postal_code"
                    type="text"
                    maxlength="5"
                    class="form-input"
                    placeholder="Kode Pos"
                    autocomplete="off"
                  />
                </div>

                <div class="form-group" :class="{ 'has-error': errors.phone }">
                  <label class="form-label" for="phone">
                    No. Hp <span class="required-mark">*</span>
                  </label>
                  <input
                    id="phone"
                    v-model="form.phone"
                    type="text"
                    class="form-input"
                    placeholder="No HP"
                    autocomplete="off"
                  />
                  <span v-if="errors.phone" class="error-message">{{ errors.phone }}</span>
                </div>
              </div>

              <!-- Tingkat Pendidikan -->
              <div class="form-group">
                <label class="form-label" for="education">Tingkat Pendidikan</label>
                <select id="education" v-model="form.education" class="form-select">
                  <option value="">-- silahkan pilih --</option>
                  <option value="TIDAK / BELUM SEKOLAH">TIDAK / BELUM SEKOLAH</option>
                  <option value="SD / SEDERAJAT">SD / SEDERAJAT</option>
                  <option value="SMP / MTS">SMP / MTS</option>
                  <option value="SMA / SMK / MA">SMA / SMK / MA</option>
                  <option value="DIPLOMA (D1 - D3)">DIPLOMA (D1 - D3)</option>
                  <option value="SARJANA (D4 / S1)">SARJANA (D4 / S1)</option>
                  <option value="MAGISTER (S2)">MAGISTER (S2)</option>
                  <option value="DOKTOR (S3)">DOKTOR (S3)</option>
                </select>
              </div>
            </div>

            <!-- =================== KOLOM KANAN =================== -->
            <div class="form-col">
              <div class="section-title-bar">
                <span class="section-badge">2</span>
                <span class="section-heading">Data Medis, Sosial & Jaminan</span>
              </div>

              <!-- Alergi -->
              <div class="form-group">
                <label class="form-label">Alergi</label>
                <div class="radio-cards-row">
                  <label class="radio-item" :class="{ 'active': form.has_allergy === 'Tidak Ada' }">
                    <input type="radio" v-model="form.has_allergy" value="Tidak Ada" name="has_allergy" />
                    <span class="radio-indicator"></span>
                    <span class="radio-text">Tidak Ada</span>
                  </label>
                  <label class="radio-item" :class="{ 'active': form.has_allergy === 'Ada' }">
                    <input type="radio" v-model="form.has_allergy" value="Ada" name="has_allergy" />
                    <span class="radio-indicator"></span>
                    <span class="radio-text text-danger font-bold">Ada</span>
                  </label>
                </div>
                <transition name="fade">
                  <div v-if="form.has_allergy === 'Ada'" class="allergy-note-card">
                    <input
                      v-model="form.allergy_notes"
                      type="text"
                      class="form-input"
                      placeholder="Sebutkan jenis alergi (obat, makanan, dll)..."
                    />
                  </div>
                </transition>
              </div>

              <!-- Pasien Fast Track -->
              <div class="form-group">
                <div class="label-wrapper">
                  <label class="form-label">Pasien Fast Track</label>
                  <span v-if="form.is_fast_track === 'Ya'" class="badge-fasttrack">Prioritas Layanan</span>
                </div>
                <div class="radio-cards-row">
                  <label class="radio-item" :class="{ 'active': form.is_fast_track === 'Tidak' }">
                    <input type="radio" v-model="form.is_fast_track" value="Tidak" name="is_fast_track" />
                    <span class="radio-indicator"></span>
                    <span class="radio-text">Tidak</span>
                  </label>
                  <label class="radio-item" :class="{ 'active': form.is_fast_track === 'Ya' }">
                    <input type="radio" v-model="form.is_fast_track" value="Ya" name="is_fast_track" />
                    <span class="radio-indicator"></span>
                    <span class="radio-text text-warning font-bold">Ya</span>
                  </label>
                </div>
              </div>

              <!-- Golongan Darah -->
              <div class="form-group">
                <label class="form-label">Golongan Darah</label>
                <div class="blood-choice-container">
                  <label
                    v-for="bt in ['A', 'B', 'AB', 'O', '-']"
                    :key="bt"
                    class="choice-label blood-choice-item"
                    :class="{ 'active': form.blood_type === bt }"
                  >
                    <input type="radio" v-model="form.blood_type" :value="bt" name="blood_type" />
                    <span class="choice-text">{{ bt === '-' ? '- (Belum Tahu)' : bt }}</span>
                  </label>
                </div>
              </div>

              <!-- Agama -->
              <div class="form-group">
                <label class="form-label">Agama</label>
                <div class="radio-choice-grid">
                  <label
                    v-for="rel in ['ISLAM', 'KRISTEN', 'KATHOLIK', 'HINDU', 'BUDHA', 'KHONG HUCU', 'LAINNYA']"
                    :key="rel"
                    class="choice-label"
                  >
                    <input type="radio" v-model="form.religion" :value="rel" name="religion" />
                    <span class="choice-text">{{ rel }}</span>
                  </label>
                </div>
              </div>

              <!-- Status Perkawinan -->
              <div class="form-group">
                <label class="form-label">Status Perkawinan</label>
                <div class="radio-choice-grid">
                  <label
                    v-for="st in ['BELUM KAWIN', 'KAWIN', 'DUDA', 'CERAI HIDUP', 'CERAI MATI', 'JANDA']"
                    :key="st"
                    class="choice-label"
                  >
                    <input type="radio" v-model="form.marital_status" :value="st" name="marital_status" />
                    <span class="choice-text">{{ st }}</span>
                  </label>
                </div>
              </div>

              <!-- Pekerjaan -->
              <div class="form-group">
                <label class="form-label" for="occupation">Pekerjaan</label>
                <select id="occupation" v-model="form.occupation" class="form-select">
                  <option value="">-- silahkan pilih --</option>
                  <option value="PNS / ASN">PNS / ASN</option>
                  <option value="TNI / POLRI">TNI / POLRI</option>
                  <option value="PEGAWAI BUMN / BUMD">PEGAWAI BUMN / BUMD</option>
                  <option value="PEGAWAI SWASTA">PEGAWAI SWASTA</option>
                  <option value="WIRASWASTA">WIRASWASTA / PEDAGANG</option>
                  <option value="PETANI / PETERNAK / NELAYAN">PETANI / PETERNAK / NELAYAN</option>
                  <option value="BURUH HARIAN LEPAS">BURUH HARIAN LEPAS</option>
                  <option value="IBU RUMAH TANGGA">IBU RUMAH TANGGA</option>
                  <option value="PELAJAR / MAHASISWA">PELAJAR / MAHASISWA</option>
                  <option value="PENSIUNAN">PENSIUNAN</option>
                  <option value="BELUM / TIDAK BEKERJA">BELUM / TIDAK BEKERJA</option>
                  <option value="LAINNYA">LAINNYA</option>
                </select>
              </div>

              <!-- Jenis Jaminan & No. Jaminan -->
              <div class="form-row-2">
                <div class="form-group" :class="{ 'has-error': errors.guarantor }">
                  <label class="form-label" for="guarantor">
                    Jenis Jaminan <span class="required-mark">*</span>
                  </label>
                  <select id="guarantor" v-model="form.guarantor" class="form-select">
                    <option value="">-- silahkan pilih --</option>
                    <option value="UMUM">UMUM (Bayar Mandiri)</option>
                    <option value="BPJS Kesehatan">BPJS Kesehatan (JKN)</option>
                    <option value="BPJS Ketenagakerjaan">BPJS Ketenagakerjaan</option>
                    <option value="Asuransi Swasta">Asuransi Swasta</option>
                    <option value="Jaminan Perusahaan">Jaminan Perusahaan</option>
                    <option value="JAMKESDA">JAMKESDA</option>
                    <option value="LAIN-LAIN">LAIN-LAIN</option>
                  </select>
                  <span v-if="errors.guarantor" class="error-message">{{ errors.guarantor }}</span>
                </div>

                <div class="form-group">
                  <label class="form-label" for="insurance_number">No. Jaminan</label>
                  <input
                    id="insurance_number"
                    v-model="form.insurance_number"
                    type="text"
                    class="form-input"
                    placeholder="No. Jaminan"
                    autocomplete="off"
                  />
                </div>
              </div>

              <!-- Sub-Container: Keluarga Terdekat -->
              <div class="emergency-contact-card emergency-contact-box">
                <div class="subcard-header">
                  <div class="subcard-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                      <circle cx="9" cy="7" r="4"></circle>
                      <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                      <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                  </div>
                  <div>
                    <h3 class="subcard-title">Keluarga Terdekat</h3>
                    <p class="subcard-desc">Kontak penanggung jawab atau keluarga darurat pasien</p>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="emergency_name">Nama Keluarga Terdekat</label>
                  <input
                    id="emergency_name"
                    v-model="form.emergency_contact.name"
                    type="text"
                    class="form-input"
                    placeholder="Nama Keluarga Terdekat"
                    autocomplete="off"
                  />
                </div>

                <div class="form-row-2">
                  <div class="form-group">
                    <label class="form-label" for="emergency_relation">Hubungan Keluarga</label>
                    <select id="emergency_relation" v-model="form.emergency_contact.relationship" class="form-select">
                      <option value="">-- silahkan pilih --</option>
                      <option value="Suami">Suami</option>
                      <option value="Istri">Istri</option>
                      <option value="Orang Tua (Ayah/Ibu)">Orang Tua (Ayah/Ibu)</option>
                      <option value="Anak">Anak</option>
                      <option value="Saudara Kandung">Saudara Kandung</option>
                      <option value="Keluarga / Kerabat">Keluarga / Kerabat</option>
                      <option value="Wali">Wali</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label" for="emergency_phone">No. HP Keluarga Terdekat</label>
                    <input
                      id="emergency_phone"
                      v-model="form.emergency_contact.phone"
                      type="text"
                      class="form-input"
                      placeholder="No. HP Keluarga Terdekat"
                      autocomplete="off"
                    />
                  </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                  <label class="form-label" for="emergency_address">Alamat Keluarga Terdekat</label>
                  <textarea
                    id="emergency_address"
                    v-model="form.emergency_contact.address"
                    class="form-textarea"
                    rows="2"
                    placeholder="Alamat Keluarga Terdekat"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Footer Bar -->
          <div class="form-footer-bar">
            <div class="footer-left-actions">
              <button
                type="button"
                class="btn-cancel btn-batal"
                @click="handleBatal"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                <span>Batal</span>
              </button>

              <button
                type="button"
                class="btn-reset-form btn-ghost-reset"
                @click="handleReset"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                  <path d="M3 3v5h5"></path>
                </svg>
                <span>Reset</span>
              </button>
            </div>

            <div class="footer-right-actions">
              <div class="category-select-container">
                <select v-model="form.patient_category" class="category-dropdown category-select">
                  <option value="PASIEN">PASIEN</option>
                  <option value="PASIEN LUAR">PASIEN LUAR</option>
                  <option value="RUJUKAN">RUJUKAN</option>
                </select>
              </div>

              <button
                type="submit"
                class="btn-primary btn-save btn-simpan"
                :disabled="isSubmitting"
              >
                <svg
                  v-if="!isSubmitting"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                  <polyline points="17 21 17 13 7 13 7 21"></polyline>
                  <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <svg
                  v-else
                  class="spin-anim"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <line x1="12" y1="2" x2="12" y2="6"></line>
                  <line x1="12" y1="18" x2="12" y2="22"></line>
                  <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                  <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                  <line x1="2" y1="12" x2="6" y2="12"></line>
                  <line x1="18" y1="12" x2="22" y2="12"></line>
                  <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                  <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                </svg>
                <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Sukses Pendaftaran -->
    <transition name="fade">
      <div v-if="isSuccessModalOpen" class="modal-overlay" @click.self="isSuccessModalOpen = false">
        <div class="modal-content modal-md" role="dialog" aria-modal="true">
          <div class="modal-header">
            <div class="header-icon-title">
              <div class="success-avatar-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
              </div>
              <div>
                <h3>Pendaftaran Pasien Berhasil!</h3>
                <p class="header-sub">Data rekam medis baru telah tercatat dalam sistem</p>
              </div>
            </div>

            <button 
              type="button" 
              class="btn-close" 
              @click="isSuccessModalOpen = false"
              aria-label="Tutup"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>

          <div class="modal-body" v-if="registeredPatient">
            <div class="rm-banner-card">
              <span class="rm-banner-label">Nomor Rekam Medis (No. RM)</span>
              <span class="rm-banner-number">{{ registeredPatient.medical_record_number }}</span>
            </div>

            <div class="patient-details-grid">
              <div class="detail-cell">
                <span class="cell-label">Nama Pasien:</span>
                <span class="cell-val">{{ registeredPatient.name }}</span>
              </div>
              <div class="detail-cell">
                <span class="cell-label">NIK:</span>
                <span class="cell-val font-mono">{{ registeredPatient.nik }}</span>
              </div>
              <div class="detail-cell">
                <span class="cell-label">Jenis Kelamin:</span>
                <span class="cell-val">{{ registeredPatient.gender === 'L' ? 'Laki-Laki' : 'Perempuan' }}</span>
              </div>
              <div class="detail-cell">
                <span class="cell-label">Jaminan:</span>
                <span class="cell-val guarantor-pill">{{ registeredPatient.guarantor }}</span>
              </div>
              <div class="detail-cell span-full">
                <span class="cell-label">Alamat:</span>
                <span class="cell-val">{{ registeredPatient.address }}</span>
              </div>
            </div>
          </div>

          <div class="modal-footer flex-column">
            <button
              type="button"
              class="btn-primary w-full btn-modal-action"
              @click="navigateToAdmission"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
              </svg>
              <span>Lanjut ke Admisi Rawat Jalan</span>
            </button>

            <button
              type="button"
              class="btn-outline w-full btn-modal-action"
              @click="registerAnother"
            >
              <span>+ Daftarkan Pasien Baru Lainnya</span>
            </button>

            <button
              type="button"
              class="btn-cancel w-full btn-modal-action"
              @click="router.push('/pendaftaran')"
            >
              <span>Kembali ke Pencarian Pasien</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.new-patient-page {
  animation: fadeIn 0.25s ease-out;
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

/* Main Container Card Sesuai Standar master-data.css */
.form-card-container {
  padding: 0;
  overflow: hidden;
  border: 1px solid var(--color-border-soft);
  background: var(--color-card-bg);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

/* Card Header (FORM TAMBAH) */
.form-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #ffffff;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.header-icon-box {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon-box svg {
  width: 20px;
  height: 20px;
}

.card-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: -0.2px;
}

.card-subtitle {
  margin: 0.15rem 0 0 0;
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.header-right {
  display: flex;
  align-items: center;
}

.required-hint {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  font-weight: 500;
}

.required-mark {
  color: var(--color-danger);
  font-weight: bold;
}

/* Form Body & Grid */
.registration-form {
  display: flex;
  flex-direction: column;
}

.form-grid-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2.25rem;
  padding: 1.75rem 1.75rem 2rem 1.75rem;
}

.form-col {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Section Title Bar */
.section-title-bar {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding-bottom: 0.65rem;
  border-bottom: 1px solid var(--color-border-soft);
  margin-bottom: 0.25rem;
}

.section-badge {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
}

.section-heading {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

/* Form Controls */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.label-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin-bottom: 0;
}

.counter-text {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.age-pill {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

/* Inputs, Textareas, Selects */
.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.6rem 0.875rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  font-size: 0.875rem;
  color: var(--color-text-navy);
  background-color: #ffffff;
  transition: all 0.15s ease;
  font-family: inherit;
}

.form-input::placeholder,
.form-textarea::placeholder {
  color: #94a3b8;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.form-group.has-error .form-input,
.form-group.has-error .form-select,
.form-group.has-error .form-textarea {
  border-color: var(--color-danger);
}

.form-group.has-error .form-input:focus,
.form-group.has-error .form-select:focus,
.form-group.has-error .form-textarea:focus {
  box-shadow: 0 0 0 3px var(--color-danger-bg);
}

.error-message {
  font-size: 0.8rem;
  color: var(--color-danger);
  margin-top: 0.2rem;
}

/* Input With Action Button (No. IHS) */
.input-action-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.input-action-group .ihs-input,
.input-action-group .form-input {
  height: 40px;
  flex: 1;
  box-sizing: border-box;
}

.btn-refresh-ihs {
  height: 40px;
  padding: 0 1.15rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.2);
  transition: all 0.15s ease;
  box-sizing: border-box;
}

.btn-refresh-ihs:hover:not(:disabled) {
  background: var(--color-primary-dark);
  box-shadow: 0 4px 12px rgba(8, 127, 120, 0.3);
  transform: translateY(-1px);
}

.btn-refresh-ihs:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none;
}

.btn-refresh-ihs svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

.spin-anim {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Radio Cards Row (Gender & Kebangsaan) */
.radio-cards-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.radio-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.55rem 0.85rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  cursor: pointer;
  background: #ffffff;
  transition: all 0.15s ease;
  user-select: none;
}

.radio-item:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.radio-item.active {
  background: var(--color-primary-light);
  border-color: var(--color-primary);
}

.radio-item input {
  display: none;
}

.radio-indicator {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  position: relative;
  transition: all 0.15s ease;
}

.radio-item.active .radio-indicator {
  border-color: var(--color-primary);
}

.radio-item.active .radio-indicator::after {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 8px;
  height: 8px;
  background: var(--color-primary);
  border-radius: 50%;
}

.radio-text {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

/* Blood Type Choice Container (Selaras dengan Agama & Container Atas) */
.blood-choice-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.35rem 0.5rem;
  padding: 0.38rem 0.55rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  width: 100%;
  box-sizing: border-box;
  min-height: 41px;
}

.blood-choice-item {
  display: inline-flex;
  padding: 0.3rem 0.65rem;
  border: 1px solid transparent;
  transition: all 0.15s ease;
  user-select: none;
}

.blood-choice-item input {
  margin: 0;
}

.blood-choice-item.active {
  background: #ffffff;
  color: var(--color-primary-dark);
  font-weight: 600;
  border-color: rgba(8, 127, 120, 0.28);
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
}

.blood-choice-item.active:hover {
  background: #ffffff;
}

/* Multi Choice Grid (Agama & Status Kawin) */
.radio-choice-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 0.4rem;
  padding: 0.5rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
}

.choice-label {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--color-text-navy);
  cursor: pointer;
  padding: 0.3rem 0.45rem;
  border-radius: 6px;
  transition: background 0.12s;
}

.choice-label:hover {
  background: #edf2f7;
}

.choice-label input {
  accent-color: var(--color-primary);
  cursor: pointer;
}

.choice-text {
  white-space: nowrap;
}

/* Wilayah Popover */
.wilayah-picker-group {
  position: relative;
}

.search-input-box {
  position: relative;
}

.search-trailing-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: var(--color-text-secondary);
  pointer-events: none;
}

.wilayah-popover {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
  z-index: 50;
  max-height: 220px;
  overflow-y: auto;
  margin-top: 4px;
}

.wilayah-row {
  padding: 0.65rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background 0.12s;
}

.wilayah-row:hover {
  background: var(--color-primary-light);
}

.row-main {
  font-size: 0.85rem;
  color: var(--color-text-navy);
}

.row-sub {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  margin-top: 0.15rem;
}

.kodepos-tag {
  color: var(--color-primary-dark);
  font-weight: 600;
}

.wilayah-empty-notice {
  padding: 0.85rem;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  text-align: center;
}

/* Fast Track Badge */
.badge-fasttrack {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}

/* Allergy Note Card */
.allergy-note-card {
  margin-top: 0.4rem;
  padding: 0.5rem;
  background: var(--color-danger-bg);
  border-radius: 8px;
  border: 1px solid #fecaca;
}

/* Emergency Contact Card (Sub-container) */
.emergency-contact-card {
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  margin-top: 0.5rem;
}

.subcard-header {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding-bottom: 0.65rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.subcard-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.subcard-icon svg {
  width: 17px;
  height: 17px;
}

.subcard-title {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.subcard-desc {
  margin: 0.1rem 0 0 0;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

/* Header Back Action Button */
.btn-header-back {
  height: 40px;
  padding: 0 1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.btn-header-back:hover {
  background: #f8fafc;
  color: #0f172a;
  border-color: #94a3b8;
}

.btn-header-back svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* Action Footer Bar */
.form-footer-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.75rem;
  background: #f8fafc;
  border-top: 1px solid var(--color-border-soft);
  flex-wrap: wrap;
  gap: 1rem;
}

.footer-left-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.footer-right-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* Footer Action Buttons */
.btn-cancel,
.btn-batal {
  height: 40px;
  padding: 0 1.25rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
  box-sizing: border-box;
}

.btn-cancel:hover,
.btn-batal:hover {
  background: #f8fafc;
  color: #0f172a;
  border-color: #94a3b8;
}

.btn-cancel svg,
.btn-batal svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

.btn-reset-form,
.btn-ghost-reset {
  height: 40px;
  padding: 0 1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  background: #ffffff;
  color: #64748b;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
  box-sizing: border-box;
}

.btn-reset-form:hover,
.btn-ghost-reset:hover {
  background: #fef2f2;
  color: var(--color-danger);
  border-color: #fecaca;
}

.btn-reset-form svg,
.btn-ghost-reset svg {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.category-select-container {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.category-dropdown,
.category-select {
  height: 40px;
  padding: 0 2.25rem 0 1rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") no-repeat right 0.75rem center / 14px 14px;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-navy);
  cursor: pointer;
  outline: none;
  transition: all 0.15s ease;
  box-sizing: border-box;
}

.category-dropdown:hover,
.category-select:hover {
  border-color: #94a3b8;
}

.category-dropdown:focus,
.category-select:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.btn-save,
.btn-simpan {
  height: 40px;
  padding: 0 1.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.2);
  transition: all 0.15s ease;
  box-sizing: border-box;
}

.btn-save:hover:not(:disabled),
.btn-simpan:hover:not(:disabled) {
  background: var(--color-primary-dark);
  box-shadow: 0 4px 12px rgba(8, 127, 120, 0.3);
  transform: translateY(-1px);
}

.btn-save:disabled,
.btn-simpan:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none;
}

.btn-save svg,
.btn-simpan svg {
  width: 17px;
  height: 17px;
  flex-shrink: 0;
}

/* Modal Styling Sesuai master-data.css */
.btn-close {
  width: 32px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 6px;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-close:hover {
  background: #f1f5f9;
  color: var(--color-text-navy);
}

.btn-close svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.btn-modal-action {
  height: 42px;
  padding: 0 1.25rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  box-sizing: border-box;
}

.btn-modal-action svg {
  width: 17px;
  height: 17px;
  flex-shrink: 0;
}

/* Modal Styling Sesuai master-data.css */
.header-icon-title {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.success-avatar-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.success-avatar-icon svg {
  width: 22px;
  height: 22px;
}

.header-sub {
  margin: 0.15rem 0 0 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.rm-banner-card {
  background: #f0fdfa;
  border: 1px dashed var(--color-primary);
  border-radius: 10px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 1.25rem;
}

.rm-banner-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-primary-dark);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.rm-banner-number {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--color-primary);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.patient-details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.6rem;
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  font-size: 0.85rem;
}

.detail-cell {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.detail-cell.span-full {
  grid-column: 1 / -1;
}

.cell-label {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.cell-val {
  font-weight: 600;
  color: var(--color-text-navy);
}

.guarantor-pill {
  display: inline-block;
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  width: fit-content;
}

.flex-column {
  flex-direction: column;
  gap: 0.6rem;
}

.w-full {
  width: 100%;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.font-bold {
  font-weight: 700;
}

/* Responsiveness */
@media (max-width: 992px) {
  .form-grid-layout {
    grid-template-columns: 1fr;
    gap: 1.75rem;
    padding: 1.25rem;
  }

  .form-footer-bar {
    padding: 1rem 1.25rem;
  }
}

@media (max-width: 640px) {
  .form-row-2,
  .radio-cards-row {
    grid-template-columns: 1fr;
  }

  .form-footer-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .footer-left-actions,
  .footer-right-actions {
    justify-content: stretch;
  }

  .footer-left-actions .btn-cancel,
  .footer-left-actions .btn-reset-form,
  .footer-left-actions .btn-ghost-reset,
  .footer-right-actions .category-select-container,
  .footer-right-actions .category-dropdown,
  .footer-right-actions .btn-save {
    flex: 1;
    width: 100%;
    justify-content: center;
  }
}
</style>
