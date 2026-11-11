<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import { useAdmissions } from '@/composables/useAdmissions';

const route = useRoute();
const router = useRouter();

const {
  admissionsList,
  mockPolyclinics,
  mockDoctors,
  getAdmissionById,
  saveSessionAdmissionEdit
} = useAdmissions();

const longMonthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const formatDateLong = (isoDate) => {
  if (!isoDate) return '—';
  const parts = String(isoDate).split('-');
  if (parts.length !== 3) return isoDate;
  const year = parts[0];
  const monthIdx = Number(parts[1]) - 1;
  const day = Number(parts[2]);
  return `${day} ${longMonthNames[monthIdx] || parts[1]} ${year}`;
};

const formatDateDMY = (isoDate) => {
  if (!isoDate) return '';
  const parts = String(isoDate).split('-');
  if (parts.length !== 3) return isoDate;
  return `${parts[2]}-${parts[1]}-${parts[0]}`;
};

const formatTimeHMS = (timeStr) => {
  if (!timeStr) return '08:00:00';
  const clean = String(timeStr).replace(/\s*WIB/i, '').trim();
  if (/^\d{2}:\d{2}$/.test(clean)) return `${clean}:00`;
  return clean;
};

const normalizeGuarantor = (val) => {
  if (!val) return 'UMUM';
  const lower = String(val).toLowerCase();
  if (lower.includes('umum') || lower.includes('mandiri') || lower.includes('tunai')) {
    return 'UMUM';
  }
  return val;
};

const polyclinicOptions = computed(() => {
  const base = new Set([
    ...mockPolyclinics,
    'Poliklinik Obstetri dan Ginekologi'
  ]);
  if (form.value.polyclinic) base.add(form.value.polyclinic);
  return Array.from(base);
});

const doctorOptions = computed(() => {
  const base = new Set([...mockDoctors]);
  if (form.value.doctor) base.add(form.value.doctor);
  return Array.from(base);
});

const patientSummary = ref({
  id: null,
  queue_number: '-',
  medical_record_number: '-',
  patient_name: '-',
  gender: 'L',
  birth_place: 'Yogyakarta',
  birth_date: '',
  address: '-'
});

const form = ref({
  admission_datetime: '',
  guarantor_type: 'UMUM',
  insurance_number: '',
  nik: '',
  referral_source: 'FKT Primer',
  referral_name: '',

  visit_type: 'Rawat Jalan',
  polyclinic: '',
  case_type: 'Pemeriksaan Kasus Baru',
  doctor: '',

  family_name: '',
  family_relationship: '',
  family_phone: '',
  family_address: ''
});

const errors = ref({});
const isSubmitting = ref(false);

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

const loadRecord = () => {
  const targetId = route.params.id;
  const record = getAdmissionById(targetId) || admissionsList.value[0];
  if (!record) return;

  patientSummary.value = {
    id: record.id,
    queue_number: record.queue_number || '-',
    medical_record_number: record.medical_record_number || '-',
    patient_name: record.patient_name || '-',
    gender: record.gender || 'L',
    birth_place: record.birth_place || 'Yogyakarta',
    birth_date: record.birth_date || '',
    address: record.address || '-'
  };

  const datePart = formatDateDMY(record.admission_date);
  const timePart = formatTimeHMS(record.admission_time);
  const normalizedGuarantor = normalizeGuarantor(record.guarantor_type);

  form.value = {
    admission_datetime: datePart ? `${datePart} ${timePart}` : timePart,
    guarantor_type: normalizedGuarantor,
    insurance_number: normalizedGuarantor === 'UMUM' ? '' : (record.insurance_number || record.guarantor_detail || ''),
    nik: record.nik || '',
    referral_source: record.referral_source || 'FKT Primer',
    referral_name: record.referral_name || '',

    visit_type: record.visit_type || 'Rawat Jalan',
    polyclinic: record.polyclinic || '',
    case_type: record.case_type || 'Pemeriksaan Kasus Baru',
    doctor: record.doctor || '',

    family_name: record.emergency_contact?.name || '',
    family_relationship: record.emergency_contact?.relationship || 'Saudara',
    family_phone: record.emergency_contact?.phone || '',
    family_address: record.emergency_contact?.address || ''
  };
};

watch(
  () => form.value.guarantor_type,
  (val) => {
    if (val === 'UMUM') {
      form.value.insurance_number = '';
    }
  }
);

watch(() => route.params.id, loadRecord);

onMounted(() => {
  loadRecord();
});

const validateForm = () => {
  const newErrors = {};

  if (!form.value.admission_datetime.trim()) {
    newErrors.admission_datetime = 'Waktu Admisi wajib diisi.';
  }
  if (!form.value.guarantor_type) {
    newErrors.guarantor_type = 'Jenis Asuransi wajib dipilih.';
  }
  if (!form.value.referral_source) {
    newErrors.referral_source = 'Asal Rujukan wajib dipilih.';
  }
  if (form.value.referral_source && form.value.referral_source !== 'Datang Sendiri' && !form.value.referral_name.trim()) {
    newErrors.referral_name = 'Nama Asal Rujukan wajib diisi.';
  }
  if (form.value.nik && !/^\d{16}$/.test(form.value.nik.trim())) {
    newErrors.nik = 'NIK / No. KIA harus 16 digit angka.';
  }
  if (!form.value.visit_type) {
    newErrors.visit_type = 'Jenis Visit wajib dipilih.';
  }
  if (!form.value.polyclinic) {
    newErrors.polyclinic = 'Klinik wajib dipilih.';
  }
  if (!form.value.case_type) {
    newErrors.case_type = 'Kasus wajib dipilih.';
  }
  if (!form.value.doctor) {
    newErrors.doctor = 'Dokter Klinik wajib dipilih.';
  }

  errors.value = newErrors;
  return Object.keys(newErrors).length === 0;
};

const parseAdmissionDateTime = (str, fallbackDate, fallbackTime) => {
  const clean = String(str || '').trim();
  const match = clean.match(/^(\d{2})-(\d{2})-(\d{4})\s+(\d{2}:\d{2})(?::\d{2})?$/);
  if (match) {
    return {
      admission_date: `${match[3]}-${match[2]}-${match[1]}`,
      admission_time: `${match[4]} WIB`
    };
  }
  return {
    admission_date: fallbackDate,
    admission_time: fallbackTime
  };
};

const handleUpdate = () => {
  if (!validateForm()) {
    showToast({
      type: 'warning',
      title: 'Validasi Form',
      message: 'Mohon lengkapi kolom wajib yang ditandai bintang (*).'
    });
    return;
  }

  isSubmitting.value = true;
  const currentRecord = getAdmissionById(patientSummary.value.id) || admissionsList.value[0];
  const parsedTime = parseAdmissionDateTime(
    form.value.admission_datetime,
    currentRecord?.admission_date,
    currentRecord?.admission_time
  );

  const payload = {
    admission_date: parsedTime.admission_date,
    admission_time: parsedTime.admission_time,
    guarantor_type: form.value.guarantor_type,
    guarantor_detail: form.value.guarantor_type === 'UMUM'
      ? 'Mandiri / Pribadi'
      : (form.value.insurance_number || form.value.guarantor_type),
    insurance_number: form.value.insurance_number,
    nik: form.value.nik.trim(),
    referral_source: form.value.referral_source,
    referral_name: form.value.referral_name.trim(),
    visit_type: form.value.visit_type,
    polyclinic: form.value.polyclinic,
    case_type: form.value.case_type,
    doctor: form.value.doctor,
    emergency_contact: {
      name: form.value.family_name.trim(),
      relationship: form.value.family_relationship.trim(),
      phone: form.value.family_phone.trim(),
      address: form.value.family_address.trim()
    }
  };

  saveSessionAdmissionEdit(patientSummary.value.id, payload);
  isSubmitting.value = false;

  showToast({
    type: 'success',
    title: 'Data Admisi Diperbarui',
    message: `Data admisi ${patientSummary.value.patient_name} berhasil diperbarui.`
  });

  setTimeout(() => {
    router.push('/admisi');
  }, 550);
};

const handleCancel = () => {
  router.push('/admisi');
};
</script>

<template>
  <div class="page-container admission-edit-page">
    <!-- Toast Notification -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Page Header -->
    <MasterDataPageHeader
      title="Edit Data Admisi"
      subtitle="Perbarui rincian data admisi pasien, kunjungan poliklinik, asal rujukan, dan data keluarga terdekat."
      :breadcrumbs="[
        { label: 'Home', active: false },
        { label: 'Data Admisi Pasien', active: false },
        { label: 'Edit Data Admisi', active: true }
      ]"
      badge="Admisi Pasien"
    >
      <template #actions>
        <button
          type="button"
          class="btn-secondary btn-header-back"
          @click="handleCancel"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
          <span>Kembali ke Data Admisi</span>
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Main Page Content -->
    <div class="page-content">
      <!-- Card 1: DATA PASIEN -->
      <div class="card patient-info-card">
        <div class="card-header-bar">
          <div class="header-left">
            <div class="header-icon-box" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <div>
              <h2 class="card-title">DATA PASIEN</h2>
              <p class="card-subtitle">Ringkasan identitas pasien yang sedang diperbarui data admisinya</p>
            </div>
          </div>

          <div class="queue-pill-wrap">
            <span class="queue-badge-chip font-mono">No. Antrean: {{ patientSummary.queue_number }}</span>
          </div>
        </div>

        <div class="patient-summary-body">
          <div class="patient-kv-list">
            <div class="kv-row">
              <span class="kv-label">No. RM</span>
              <span class="kv-colon">:</span>
              <span class="kv-value">
                <span class="rm-tag font-mono">{{ patientSummary.medical_record_number }}</span>
              </span>
            </div>
            <div class="kv-row">
              <span class="kv-label">Nama</span>
              <span class="kv-colon">:</span>
              <span class="kv-value font-bold">{{ patientSummary.patient_name }} ({{ patientSummary.gender }})</span>
            </div>
            <div class="kv-row">
              <span class="kv-label">TTL</span>
              <span class="kv-colon">:</span>
              <span class="kv-value">{{ patientSummary.birth_place }}, {{ formatDateLong(patientSummary.birth_date) }}</span>
            </div>
            <div class="kv-row">
              <span class="kv-label">Alamat</span>
              <span class="kv-colon">:</span>
              <span class="kv-value">{{ patientSummary.address }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 2: FORM EDIT -->
      <div class="card form-edit-card">
        <div class="card-header-bar">
          <div class="header-left">
            <div class="header-icon-box" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </div>
            <div>
              <h2 class="card-title">FORM EDIT</h2>
              <p class="card-subtitle">Sesuaikan data admisi, data visit poliklinik, dan keluarga terdekat pasien</p>
            </div>
          </div>

          <div class="header-right">
            <span class="required-hint"><span class="required-mark">*</span> Wajib diisi</span>
          </div>
        </div>

        <form autocomplete="off" class="edit-admission-form" @submit.prevent="handleUpdate">
          <div class="edit-form-grid">
            <!-- Left Column: Data Admisi Pasien -->
            <div class="sub-panel">
              <div class="section-title-bar">
                <span class="section-badge">1</span>
                <span class="section-heading">Data Admisi Pasien</span>
              </div>

              <div class="panel-fields">
                <!-- Waktu Admisi -->
                <div class="h-form-row" :class="{ 'has-error': errors.admission_datetime }">
                  <label class="h-label" for="edit-admission-datetime">
                    Waktu Admisi <span class="required-mark">*</span>
                  </label>
                  <div class="h-control">
                    <input
                      id="edit-admission-datetime"
                      v-model="form.admission_datetime"
                      type="text"
                      class="form-input font-mono"
                      placeholder="DD-MM-YYYY HH:mm:ss"
                      autocomplete="off"
                    />
                    <span v-if="errors.admission_datetime" class="error-message">{{ errors.admission_datetime }}</span>
                  </div>
                </div>

                <!-- Jenis Asuransi -->
                <div class="h-form-row" :class="{ 'has-error': errors.guarantor_type }">
                  <label class="h-label" for="edit-guarantor">
                    Jenis Asuransi <span class="required-mark">*</span>
                  </label>
                  <div class="h-control">
                    <div class="select-wrap">
                      <select id="edit-guarantor" v-model="form.guarantor_type" class="form-select custom-select">
                        <option value="UMUM">UMUM</option>
                        <option value="BPJS Kesehatan">BPJS Kesehatan</option>
                        <option value="BPJS Ketenagakerjaan">BPJS Ketenagakerjaan</option>
                        <option value="Asuransi Swasta">Asuransi Swasta</option>
                      </select>
                      <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </div>
                    <span v-if="errors.guarantor_type" class="error-message">{{ errors.guarantor_type }}</span>
                  </div>
                </div>

                <!-- No. Asuransi -->
                <div class="h-form-row">
                  <label class="h-label" for="edit-insurance-number">No. Asuransi</label>
                  <div class="h-control">
                    <input
                      id="edit-insurance-number"
                      v-model="form.insurance_number"
                      type="text"
                      class="form-input"
                      :disabled="form.guarantor_type === 'UMUM'"
                      :placeholder="form.guarantor_type === 'UMUM' ? 'No Asuransi' : 'Masukkan Nomor Kartu Asuransi / BPJS'"
                      autocomplete="off"
                    />
                  </div>
                </div>

                <!-- NIK / No. KIA -->
                <div class="h-form-row" :class="{ 'has-error': errors.nik }">
                  <label class="h-label" for="edit-nik">NIK / No. KIA</label>
                  <div class="h-control">
                    <input
                      id="edit-nik"
                      v-model="form.nik"
                      type="text"
                      maxlength="16"
                      class="form-input font-mono"
                      placeholder="NIK / No. KIA (16 Digit)"
                      autocomplete="off"
                    />
                    <span v-if="errors.nik" class="error-message">{{ errors.nik }}</span>
                  </div>
                </div>

                <!-- Asal Rujukan -->
                <div class="h-form-row" :class="{ 'has-error': errors.referral_source }">
                  <label class="h-label" for="edit-referral-source">
                    Asal Rujukan <span class="required-mark">*</span>
                  </label>
                  <div class="h-control">
                    <div class="select-wrap">
                      <select id="edit-referral-source" v-model="form.referral_source" class="form-select custom-select">
                        <option value="FKT Primer">FKT Primer</option>
                        <option value="Datang Sendiri">Datang Sendiri</option>
                        <option value="Puskesmas">Puskesmas</option>
                        <option value="Klinik Pratama">Klinik Pratama</option>
                        <option value="Dokter Praktek Mandiri">Dokter Praktek Mandiri</option>
                        <option value="RS Lain / Rujukan Lanjutan">RS Lain / Rujukan Lanjutan</option>
                        <option value="IGD">IGD</option>
                      </select>
                      <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </div>
                    <span v-if="errors.referral_source" class="error-message">{{ errors.referral_source }}</span>
                  </div>
                </div>

                <!-- Nama Asal Rujukan -->
                <div class="h-form-row" :class="{ 'has-error': errors.referral_name }">
                  <label class="h-label" for="edit-referral-name">
                    Nama Asal Rujukan <span v-if="form.referral_source !== 'Datang Sendiri'" class="required-mark">*</span>
                  </label>
                  <div class="h-control">
                    <input
                      id="edit-referral-name"
                      v-model="form.referral_name"
                      type="text"
                      class="form-input"
                      placeholder="Nama Asal Rujukan"
                      autocomplete="off"
                    />
                    <span v-if="errors.referral_name" class="error-message">{{ errors.referral_name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Data Visit Pasien + Data Keluarga Terdekat Pasien -->
            <div class="right-panels-stack">
              <!-- Sub-Panel 2: Data Visit Pasien -->
              <div class="sub-panel">
                <div class="section-title-bar">
                  <span class="section-badge">2</span>
                  <span class="section-heading">Data Visit Pasien</span>
                </div>

                <div class="panel-fields">
                  <!-- Jenis Visit -->
                  <div class="h-form-row" :class="{ 'has-error': errors.visit_type }">
                    <label class="h-label" for="edit-visit-type">
                      Jenis Visit <span class="required-mark">*</span>
                    </label>
                    <div class="h-control">
                      <div class="select-wrap">
                        <select id="edit-visit-type" v-model="form.visit_type" class="form-select custom-select">
                          <option value="Rawat Jalan">Rawat Jalan</option>
                          <option value="Rawat Inap">Rawat Inap</option>
                          <option value="IGD">IGD</option>
                          <option value="Penunjang Medis">Penunjang Medis</option>
                        </select>
                        <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </div>
                      <span v-if="errors.visit_type" class="error-message">{{ errors.visit_type }}</span>
                    </div>
                  </div>

                  <!-- Klinik -->
                  <div class="h-form-row" :class="{ 'has-error': errors.polyclinic }">
                    <label class="h-label" for="edit-clinic">
                      Klinik <span class="required-mark">*</span>
                    </label>
                    <div class="h-control">
                      <div class="select-wrap">
                        <select id="edit-clinic" v-model="form.polyclinic" class="form-select custom-select">
                          <option value="">-- silahkan pilih --</option>
                          <option v-for="poly in polyclinicOptions" :key="poly" :value="poly">{{ poly }}</option>
                        </select>
                        <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </div>
                      <span v-if="errors.polyclinic" class="error-message">{{ errors.polyclinic }}</span>
                    </div>
                  </div>

                  <!-- Kasus -->
                  <div class="h-form-row" :class="{ 'has-error': errors.case_type }">
                    <label class="h-label" for="edit-case-type">
                      Kasus <span class="required-mark">*</span>
                    </label>
                    <div class="h-control">
                      <div class="select-wrap">
                        <select id="edit-case-type" v-model="form.case_type" class="form-select custom-select">
                          <option value="Pemeriksaan Kasus Baru">Pemeriksaan Kasus Baru</option>
                          <option value="Pemeriksaan Kasus Lama">Pemeriksaan Kasus Lama</option>
                          <option value="Kontrol Ulang">Kontrol Ulang</option>
                          <option value="Konsultasi">Konsultasi</option>
                        </select>
                        <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </div>
                      <span v-if="errors.case_type" class="error-message">{{ errors.case_type }}</span>
                    </div>
                  </div>

                  <!-- Dokter Klinik -->
                  <div class="h-form-row" :class="{ 'has-error': errors.doctor }">
                    <label class="h-label" for="edit-doctor">
                      Dokter Klinik <span class="required-mark">*</span>
                    </label>
                    <div class="h-control">
                      <div class="select-wrap">
                        <select id="edit-doctor" v-model="form.doctor" class="form-select custom-select">
                          <option value="">-- silahkan pilih --</option>
                          <option v-for="doc in doctorOptions" :key="doc" :value="doc">{{ doc }}</option>
                        </select>
                        <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </div>
                      <span v-if="errors.doctor" class="error-message">{{ errors.doctor }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Sub-Panel 3: Data Keluarga Terdekat Pasien -->
              <div class="sub-panel">
                <div class="section-title-bar">
                  <span class="section-badge">3</span>
                  <span class="section-heading">Data Keluarga Terdekat Pasien</span>
                </div>

                <div class="panel-fields">
                  <!-- Nama -->
                  <div class="h-form-row">
                    <label class="h-label" for="edit-family-name">Nama</label>
                    <div class="h-control">
                      <input
                        id="edit-family-name"
                        v-model="form.family_name"
                        type="text"
                        class="form-input"
                        placeholder="Nama keluarga terdekat"
                        autocomplete="off"
                      />
                    </div>
                  </div>

                  <!-- Hubungan -->
                  <div class="h-form-row">
                    <label class="h-label" for="edit-family-relationship">Hubungan</label>
                    <div class="h-control">
                      <input
                        id="edit-family-relationship"
                        v-model="form.family_relationship"
                        type="text"
                        class="form-input"
                        placeholder="Contoh: Saudara, Suami, Istri, Orang Tua"
                        autocomplete="off"
                      />
                    </div>
                  </div>

                  <!-- No. HP -->
                  <div class="h-form-row">
                    <label class="h-label" for="edit-family-phone">No. HP</label>
                    <div class="h-control">
                      <input
                        id="edit-family-phone"
                        v-model="form.family_phone"
                        type="tel"
                        class="form-input"
                        placeholder="No. HP keluarga terdekat"
                        autocomplete="off"
                      />
                    </div>
                  </div>

                  <!-- Alamat -->
                  <div class="h-form-row">
                    <label class="h-label" for="edit-family-address">Alamat</label>
                    <div class="h-control">
                      <input
                        id="edit-family-address"
                        v-model="form.family_address"
                        type="text"
                        class="form-input"
                        placeholder="Alamat keluarga terdekat"
                        autocomplete="off"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer Action Bar -->
          <div class="form-footer-bar">
            <button
              type="button"
              class="btn-cancel btn-batal"
              @click="handleCancel"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
              <span>Batal</span>
            </button>

            <button
              type="submit"
              class="btn-primary btn-save btn-update"
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
.admission-edit-page {
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

.btn-header-back {
  height: 40px;
  padding: 0 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: #ffffff;
  color: var(--color-text-navy);
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  font-family: inherit;
  white-space: nowrap;
}

.btn-header-back:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-header-back svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* Shared Card Header */
.patient-info-card,
.form-edit-card {
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  overflow: hidden;
}

.card-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.15rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #ffffff;
  gap: 1rem;
  flex-wrap: wrap;
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
  width: 19px;
  height: 19px;
}

.card-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: -0.01em;
}

.card-subtitle {
  margin: 0.12rem 0 0 0;
  font-size: 0.825rem;
  color: var(--color-text-secondary);
}

.queue-badge-chip {
  display: inline-flex;
  align-items: center;
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.8rem;
  font-weight: 700;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
}

.required-hint {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  font-weight: 500;
}

.required-mark {
  color: var(--color-danger);
  font-weight: 700;
}

/* Patient Summary Card Body */
.patient-summary-body {
  padding: 1.15rem 1.5rem;
  background: #ffffff;
}

.patient-kv-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.kv-row {
  display: grid;
  grid-template-columns: 95px 14px 1fr;
  align-items: baseline;
  font-size: 0.875rem;
  line-height: 1.45;
}

.kv-label {
  font-weight: 600;
  color: var(--color-text-secondary);
}

.kv-colon {
  font-weight: 600;
  color: var(--color-text-secondary);
}

.kv-value {
  color: var(--color-text-navy);
}

.rm-tag {
  display: inline-block;
  background: #f1f5f9;
  color: var(--color-text-navy);
  font-weight: 700;
  font-size: 0.825rem;
  padding: 0.1rem 0.5rem;
  border-radius: 5px;
}

/* Form Edit Grid */
.edit-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  padding: 1.5rem;
  align-items: start;
}

.right-panels-stack {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.sub-panel {
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  padding: 1.25rem 1.35rem;
  background: #ffffff;
}

.section-title-bar {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding-bottom: 0.7rem;
  border-bottom: 1px solid var(--color-border-soft);
  margin-bottom: 1.1rem;
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
  flex-shrink: 0;
}

.section-heading {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.panel-fields {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.h-form-row {
  display: grid;
  grid-template-columns: 155px 1fr;
  gap: 0.75rem;
  align-items: start;
}

.h-label {
  padding-top: 0.58rem;
  font-size: 0.84rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.h-control {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}

.form-input,
.form-select {
  width: 100%;
  height: 40px;
  padding: 0 0.85rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  font-size: 0.875rem;
  color: var(--color-text-navy);
  background-color: #ffffff;
  font-family: inherit;
  box-sizing: border-box;
  transition: all 0.15s ease;
}

.form-input:disabled {
  background-color: #f1f5f9;
  color: #94a3b8;
  cursor: not-allowed;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.select-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.custom-select {
  appearance: none;
  -webkit-appearance: none;
  padding-right: 2.25rem;
  cursor: pointer;
}

.select-chevron {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 15px;
  height: 15px;
  color: var(--color-text-secondary);
  pointer-events: none;
}

.h-form-row.has-error .form-input,
.h-form-row.has-error .form-select {
  border-color: var(--color-danger);
}

.h-form-row.has-error .form-input:focus,
.h-form-row.has-error .form-select:focus {
  box-shadow: 0 0 0 3px var(--color-danger-bg);
}

.error-message {
  font-size: 0.78rem;
  color: var(--color-danger);
}

/* Form Footer Action Bar */
.form-footer-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.15rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  background: #f8fafc;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-batal {
  height: 40px;
  padding: 0 1.25rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  background: #ffffff;
  color: var(--color-text-secondary);
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.btn-batal:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: var(--color-text-navy);
}

.btn-batal svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.btn-update {
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
  font-family: inherit;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(8, 127, 120, 0.2);
  transition: all 0.15s ease;
  white-space: nowrap;
}

.btn-update:hover:not(:disabled) {
  background: var(--color-primary-dark);
  box-shadow: 0 4px 12px rgba(8, 127, 120, 0.28);
  transform: translateY(-1px);
}

.btn-update:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-update svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.font-bold {
  font-weight: 700;
}

@media (max-width: 1024px) {
  .edit-form-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .edit-form-grid {
    padding: 1.15rem;
  }

  .h-form-row {
    grid-template-columns: 1fr;
    gap: 0.35rem;
  }

  .h-label {
    padding-top: 0;
  }

  .form-footer-bar {
    flex-direction: column-reverse;
    align-items: stretch;
  }

  .btn-batal,
  .btn-update {
    width: 100%;
    justify-content: center;
  }
}
</style>

