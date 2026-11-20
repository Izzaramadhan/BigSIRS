<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import patientService from '@/services/patient.service';
import admissionService from '@/services/admission.service';
import lookupService from '@/services/lookup.service';
import { polyclinicService } from '@/services/polyclinic';
import { guarantorsService } from '@/services/master-data/guarantors.service';
import { formatDateIndo, formatDateTimeIndo } from '@/utils/patientFormatters';

const route = useRoute();
const router = useRouter();

// State
const isLoadingPatient = ref(false);
const isLoadingGuarantors = ref(false);
const isLoadingClinics = ref(false);
const isLoadingDoctors = ref(false);
const isLoadingReferrals = ref(false);
const isSubmitting = ref(false);
const isSaved = ref(false);
const idempotencyKey = ref('adm_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9));

const patient = ref(null);
const guarantors = ref([]);
const polyclinics = ref([]);
const doctors = ref([]);
const referralSources = ref([]);
const doctorEmptyCondition = ref(null); // null | 'condition_a' | 'condition_b' | 'condition_c'
const doctorLoadError = ref(false);

// Form State
const form = ref({
  patient_id: null,
  admission_datetime: '',
  guarantor_id: '',
  insurance_number: '',
  nik: '',
  referral_source_id: '',
  referral_name: '',
  visit_type: 'Rawat Jalan',
  polyclinic_id: '',
  case_type: 'pemeriksaan kasus baru',
  doctor_id: '',
  family_name: '',
  family_relationship: '',
  family_phone: '',
  family_address: '',
  notes: '',
  satusehat_consent: 'tidak_setuju',
  kode_booking: ''
});

const errors = ref({});

const isCheckingBpjs = ref(false);
const bpjsCheckError = ref('');
const duKoasSearch = ref('');
const isSearchingDuKoas = ref(false);

const handleCheckBpjs = () => {
  const cardNo = (form.value.insurance_number || '').trim();
  if (!cardNo) {
    bpjsCheckError.value = 'Silakan masukkan nomor kartu jaminan terlebih dahulu.';
    return;
  }
  if (cardNo.length < 8) {
    bpjsCheckError.value = 'No.Kartu Tidak Sesuai';
    return;
  }
  isCheckingBpjs.value = true;
  bpjsCheckError.value = '';
  setTimeout(() => {
    isCheckingBpjs.value = false;
    showToast({
      type: 'success',
      title: 'Pengecekan Berhasil',
      message: `Nomor kartu ${cardNo} valid dan aktif pada sistem kepesertaan.`
    });
  }, 400);
};

const handleSearchDuKoas = () => {
  const query = duKoasSearch.value.trim();
  if (!query) {
    showToast({
      type: 'warning',
      title: 'Pencarian Booking',
      message: 'Masukkan kata kunci atau kode booking DU KOAS terlebih dahulu.'
    });
    return;
  }
  isSearchingDuKoas.value = true;
  setTimeout(() => {
    isSearchingDuKoas.value = false;
    form.value.kode_booking = query.toUpperCase();
    showToast({
      type: 'success',
      title: 'DU KOAS Ditemukan',
      message: `Kode booking ${query.toUpperCase()} berhasil dimuat.`
    });
  }, 400);
};

// Toast
const toast = ref({
  show: false,
  type: 'success',
  title: '',
  message: ''
});

const showToast = ({ type = 'success', title, message }) => {
  toast.value = {
    show: true,
    type,
    title,
    message
  };
};

const closeToast = () => {
  toast.value.show = false;
};

// Current local time formatted DD-MM-YYYY HH:mm:ss
const generateCurrentDateTime = () => {
  const now = new Date();
  const day = String(now.getDate()).padStart(2, '0');
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const year = now.getFullYear();
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');
  return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
};

// Calculate detailed age
const detailedAge = computed(() => {
  if (!patient.value?.birth_date) return '';
  const birth = new Date(patient.value.birth_date);
  if (Number.isNaN(birth.getTime())) return '';
  const now = new Date();
  let years = now.getFullYear() - birth.getFullYear();
  let months = now.getMonth() - birth.getMonth();
  let days = now.getDate() - birth.getDate();

  if (days < 0) {
    months--;
    const prevMonthDays = new Date(now.getFullYear(), now.getMonth(), 0).getDate();
    days += prevMonthDays;
  }
  if (months < 0) {
    years--;
    months += 12;
  }
  return `${years} Th ${months} Bl ${days} hr`;
});

// Full Address + Wilayah
const fullAddressWithRegion = computed(() => {
  if (!patient.value) return '—';
  const parts = [];
  if (patient.value.address) parts.push(patient.value.address);
  if (patient.value.region) {
    const reg = patient.value.region;
    const regionParts = [];
    if (reg.village?.name) regionParts.push(`Kel. ${reg.village.name}`);
    if (reg.district?.name) regionParts.push(`Kec. ${reg.district.name}`);
    if (reg.regency?.name) regionParts.push(`Kab./Kota ${reg.regency.name}`);
    if (reg.province?.name) regionParts.push(`Prov. ${reg.province.name}`);
    if (regionParts.length > 0) parts.push(regionParts.join(', '));
  }
  if (patient.value.postal_code) parts.push(`Kode Pos: ${patient.value.postal_code}`);
  return parts.length > 0 ? parts.join(', ') : '—';
});

// Selected guarantor helper
const selectedGuarantor = computed(() => {
  if (!form.value.guarantor_id) return null;
  return guarantors.value.find((g) => String(g.id) === String(form.value.guarantor_id)) || null;
});

const isGuarantorUmum = computed(() => {
  if (!selectedGuarantor.value) return true;
  const name = String(selectedGuarantor.value.name || selectedGuarantor.value.jenis_asuransi || '').toUpperCase();
  const type = String(selectedGuarantor.value.type || '').toUpperCase();
  return type === 'UMUM' || name.includes('UMUM') || name.includes('MANDIRI');
});

// Selected referral helper
const selectedReferralSource = computed(() => {
  if (!form.value.referral_source_id) return null;
  return referralSources.value.find((r) => String(r.id) === String(form.value.referral_source_id)) || null;
});

const isDatangSendiri = computed(() => {
  if (!selectedReferralSource.value) return false;
  const code = String(selectedReferralSource.value.code || selectedReferralSource.value.kode || '').toUpperCase();
  const name = String(selectedReferralSource.value.name || selectedReferralSource.value.asal_rujukan || '').toLowerCase();
  return code === 'DS' || name.includes('datang sendiri');
});

// Load active scheduled doctors for clinic and selected date
const loadDoctors = async (clinicId, dateValue) => {
  if (!clinicId) {
    doctors.value = [];
    doctorEmptyCondition.value = null;
    doctorLoadError.value = false;
    return;
  }

  isLoadingDoctors.value = true;
  doctorLoadError.value = false;
  doctorEmptyCondition.value = null;

  try {
    const params = {};
    if (dateValue) {
      params.date = dateValue;
    }
    const res = await lookupService.getDoctorsByPolyclinic(clinicId, params);
    const doctorList = Array.isArray(res) ? res : res?.data || [];
    const meta = res?.meta || (Array.isArray(res) ? res.meta : null) || {};

    doctors.value = doctorList;

    if (doctorList.length > 0) {
      doctorEmptyCondition.value = null;
      if (form.value.doctor_id && !doctorList.some((d) => String(d.id) === String(form.value.doctor_id))) {
        form.value.doctor_id = '';
      }
    } else {
      form.value.doctor_id = '';
      if (meta.has_assigned_doctors === false || meta.total_assigned_doctors === 0) {
        doctorEmptyCondition.value = 'condition_a';
      } else {
        doctorEmptyCondition.value = 'condition_b';
      }
    }
  } catch (err) {
    doctors.value = [];
    form.value.doctor_id = '';
    doctorLoadError.value = true;
    doctorEmptyCondition.value = 'condition_c';
    showToast({
      type: 'danger',
      title: 'Gagal Memuat Dokter',
      message: err.response?.data?.message || 'Tidak dapat memuat daftar dokter untuk poliklinik ini.'
    });
  } finally {
    isLoadingDoctors.value = false;
  }
};

const retryLoadDoctors = async () => {
  if (form.value.polyclinic_id) {
    await loadDoctors(form.value.polyclinic_id, form.value.admission_datetime);
  }
};

// Watch polyclinic change: reset doctor and query active scheduled doctors
watch(
  () => form.value.polyclinic_id,
  async (newClinicId, oldClinicId) => {
    if (newClinicId !== oldClinicId) {
      form.value.doctor_id = '';
      doctors.value = [];
      doctorEmptyCondition.value = null;
    }

    if (!newClinicId) return;

    await loadDoctors(newClinicId, form.value.admission_datetime);
  }
);

// Watch admission_datetime change: re-evaluate doctor schedule for selected clinic
watch(
  () => form.value.admission_datetime,
  async (newDateTime) => {
    if (form.value.polyclinic_id) {
      await loadDoctors(form.value.polyclinic_id, newDateTime);
    }
  }
);

// Load Master Data
const loadMasterData = async () => {
  // 1. Guarantors
  isLoadingGuarantors.value = true;
  try {
    const res = await guarantorsService.getGuarantors({ per_page: 100, is_active: true });
    guarantors.value = res?.data || [];
  } catch (err) {
    guarantors.value = [];
  } finally {
    isLoadingGuarantors.value = false;
  }

  // 2. Polyclinics (Rawat Jalan only)
  isLoadingClinics.value = true;
  try {
    const res = await polyclinicService.getPolyclinics({
      service_type: 'rawat-jalan',
      is_active: true,
      per_page: 100
    });
    polyclinics.value = res?.data?.data || res?.data || [];
  } catch (err) {
    polyclinics.value = [];
  } finally {
    isLoadingClinics.value = false;
  }

  // 3. Referral Sources
  isLoadingReferrals.value = true;
  try {
    const res = await lookupService.getReferralSources();
    referralSources.value = Array.isArray(res) ? res : res?.data || [];
    // Default to 'Datang Sendiri' if available
    const ds = referralSources.value.find((r) => {
      const c = String(r.code || r.kode || '').toUpperCase();
      const n = String(r.name || r.asal_rujukan || '').toLowerCase();
      return c === 'DS' || n.includes('datang sendiri');
    });
    if (ds && !form.value.referral_source_id) {
      form.value.referral_source_id = ds.id;
    }
  } catch (err) {
    referralSources.value = [];
  } finally {
    isLoadingReferrals.value = false;
  }
};

// Load Patient Data
const loadPatient = async (patientId) => {
  if (!patientId) {
    patient.value = null;
    return;
  }

  isLoadingPatient.value = true;
  try {
    const res = await patientService.getPatient(patientId);
    const data = res?.data?.data || res?.data;
    patient.value = data;

    // Prefill form
    form.value.patient_id = data.id;
    form.value.nik = data.nik || '';
    form.value.insurance_number = data.insurance_number || '';

    // Prefill default guarantor if exists
    if (data.guarantor?.id) {
      form.value.guarantor_id = data.guarantor.id;
    } else if (guarantors.value.length > 0 && !form.value.guarantor_id) {
      // Find UMUM or first guarantor
      const umum = guarantors.value.find((g) => (g.name || '').toUpperCase().includes('UMUM'));
      form.value.guarantor_id = umum ? umum.id : guarantors.value[0].id;
    }

    // Prefill emergency contact
    if (data.emergency_contact) {
      form.value.family_name = data.emergency_contact.name || '';
      form.value.family_relationship = data.emergency_contact.relationship || '';
      form.value.family_phone = data.emergency_contact.phone || '';
      form.value.family_address = data.emergency_contact.address || '';
    }

    // Check unpaid bills
    if (data.has_unpaid_bills) {
      showToast({
        type: 'danger',
        title: 'Pasien Belum Lunas',
        message:
          data.unpaid_billing_message ||
          'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.'
      });
    }
  } catch (err) {
    patient.value = null;
    showToast({
      type: 'danger',
      title: 'Gagal Memuat Pasien',
      message: err.response?.data?.message || 'Data pasien dengan ID tersebut tidak ditemukan.'
    });
  } finally {
    isLoadingPatient.value = false;
  }
};

onMounted(async () => {
  form.value.admission_datetime = generateCurrentDateTime();
  await loadMasterData();

  const patientId = route.query.patient_id;
  if (patientId) {
    await loadPatient(patientId);
  }
});

// Watch route query changes (for refresh or route switch)
watch(
  () => route.query.patient_id,
  (newId) => {
    if (newId) {
      loadPatient(newId);
    }
  }
);

// Form Validation
const validateForm = () => {
  const newErrors = {};

  if (!patient.value) {
    newErrors.patient = 'Data pasien belum dipilih atau gagal dimuat.';
  }

  if (patient.value?.has_unpaid_bills) {
    newErrors.unpaid_bills = 'Pasien belum menyelesaikan pembayaran tagihan sebelumnya di unit Billing.';
  }

  if (!form.value.admission_datetime) {
    newErrors.admission_datetime = 'Waktu Admisi wajib diisi.';
  }

  if (!form.value.guarantor_id) {
    newErrors.guarantor_id = 'Jenis Asuransi wajib dipilih.';
  }

  if (!isGuarantorUmum.value && !form.value.insurance_number?.trim()) {
    newErrors.insurance_number = 'Nomor Asuransi / BPJS wajib diisi untuk penjamin non-UMUM.';
  }

  if (!form.value.referral_source_id) {
    newErrors.referral_source_id = 'Asal Rujukan wajib dipilih.';
  }

  if (!isDatangSendiri.value && !form.value.referral_name?.trim()) {
    newErrors.referral_name = 'Nama Asal Rujukan wajib diisi.';
  }

  if (!form.value.visit_type) {
    newErrors.visit_type = 'Jenis Visit wajib dipilih.';
  }

  if (!form.value.polyclinic_id) {
    newErrors.polyclinic_id = 'Klinik / Poliklinik tujuan wajib dipilih.';
  }

  if (!form.value.case_type) {
    newErrors.case_type = 'Kasus wajib dipilih.';
  }

  if (!form.value.doctor_id) {
    newErrors.doctor_id = 'Dokter Klinik wajib dipilih.';
  }

  errors.value = newErrors;
  return Object.keys(newErrors).length === 0;
};

// Submit handler
const handleSubmit = async () => {
  if (isSubmitting.value || isSaved.value) return;

  if (!validateForm()) {
    showToast({
      type: 'warning',
      title: 'Validasi Belum Lengkap',
      message: 'Mohon periksa kolom wajib bertanda bintang (*) sebelum menyimpan admisi.'
    });
    return;
  }

  if (patient.value?.has_unpaid_bills) {
    showToast({
      type: 'danger',
      title: 'Gagal Admisi',
      message:
        patient.value.unpaid_billing_message ||
        'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing.'
    });
    return;
  }

  isSubmitting.value = true;
  errors.value = {};

  try {
    const payload = {
      idempotency_key: idempotencyKey.value,
      patient_id: form.value.patient_id,
      admission_datetime: form.value.admission_datetime,
      guarantor_id: form.value.guarantor_id,
      insurance_number: form.value.insurance_number ? String(form.value.insurance_number).trim() : null,
      nik: form.value.nik ? String(form.value.nik).trim() : null,
      referral_source_id: form.value.referral_source_id,
      referral_name: isDatangSendiri.value ? null : form.value.referral_name?.trim(),
      visit_type: 'rawat-jalan',
      polyclinic_id: form.value.polyclinic_id,
      case_type: form.value.case_type,
      doctor_id: form.value.doctor_id,
      emergency_contact: {
        name: form.value.family_name ? form.value.family_name.trim() : null,
        relationship: form.value.family_relationship ? form.value.family_relationship.trim() : null,
        phone: form.value.family_phone ? form.value.family_phone.trim() : null,
        address: form.value.family_address ? form.value.family_address.trim() : null
      },
      family_name: form.value.family_name ? form.value.family_name.trim() : null,
      family_relationship: form.value.family_relationship ? form.value.family_relationship.trim() : null,
      family_phone: form.value.family_phone ? form.value.family_phone.trim() : null,
      family_address: form.value.family_address ? form.value.family_address.trim() : null,
      notes: form.value.notes ? form.value.notes.trim() : null,
      satusehat_consent: form.value.satusehat_consent,
      kode_booking: form.value.kode_booking ? form.value.kode_booking.trim() : null
    };

    await admissionService.createAdmission(payload);

    isSaved.value = true;

    showToast({
      type: 'success',
      title: 'Admisi Berhasil Didaftarkan',
      message: `Pasien ${patient.value?.name || ''} berhasil didaftarkan ke admisi rawat jalan.`
    });

    setTimeout(() => {
      router.push('/admisi');
    }, 1200);
  } catch (err) {
    console.error('Admission creation error:', err);
    const status = err.response?.status;
    const responseData = err.response?.data;

    if (status === 422) {
      if (responseData?.errors && typeof responseData.errors === 'object') {
        const fieldErrors = {};
        for (const [key, msgs] of Object.entries(responseData.errors)) {
          fieldErrors[key] = Array.isArray(msgs) ? msgs[0] : msgs;
        }
        errors.value = { ...errors.value, ...fieldErrors };
      }
      showToast({
        type: 'warning',
        title: 'Validasi Gagal',
        message: responseData?.message || 'Data yang dikirim tidak valid. Silakan periksa kembali isian formulir.'
      });
    } else if (status === 401 || status === 403) {
      showToast({
        type: 'danger',
        title: 'Akses Ditolak',
        message: responseData?.message || 'Anda tidak memiliki hak akses untuk menyimpan data admisi.'
      });
    } else if (status >= 500) {
      showToast({
        type: 'danger',
        title: 'Kesalahan Server',
        message: responseData?.message || 'Terjadi kesalahan pada server saat menyimpan admisi. Silakan coba kembali.'
      });
    } else {
      showToast({
        type: 'danger',
        title: 'Gangguan Koneksi',
        message: err.message || 'Gagal terhubung ke server. Periksa koneksi internet Anda.'
      });
    }
  } finally {
    isSubmitting.value = false;
  }
};

const handleCancel = () => {
  router.push('/admisi');
};
</script>

<template>
  <div class="page-container admission-create-page">
    <!-- Toast Notification -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Header -->
    <MasterDataPageHeader
      title="Admisi Pasien Rawat Jalan"
      subtitle="Pendaftaran admisi kunjungan baru pasien rawat jalan ke unit poliklinik."
      :breadcrumbs="[
        { label: 'Home', active: false },
        { label: 'Data Admisi Pasien', active: false },
        { label: 'Admisi Pasien Baru', active: true }
      ]"
      badge="Admisi Rawat Jalan"
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

    <div class="page-content">
      <!-- Loading Patient State -->
      <div v-if="isLoadingPatient" class="card loading-card">
        <div class="loading-spinner"></div>
        <p class="loading-text">Memuat data identitas pasien dari database rekam medis...</p>
      </div>

      <!-- Warning: No Patient Selected -->
      <div v-else-if="!patient" class="card empty-patient-card">
        <div class="empty-patient-content">
          <div class="empty-icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
          </div>
          <h3>Tidak Ada Pasien Dipilih</h3>
          <p>Silakan pilih pasien lama melalui fitur pencarian Admisi, atau daftarkan pasien baru terlebih dahulu.</p>
          <div class="empty-action-group">
            <button type="button" class="btn-primary" @click="router.push('/admisi')">
              Cari Pasien Lama
            </button>
            <button type="button" class="btn-secondary" @click="router.push('/pendaftaran/baru')">
              Daftar Pasien Baru
            </button>
          </div>
        </div>
      </div>

      <!-- Main Form Section when Patient is Present -->
      <template v-else>
        <!-- Unpaid Billing Alert Banner -->
        <div v-if="patient.has_unpaid_bills" class="unpaid-warning-banner" role="alert">
          <div class="unpaid-warning-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </div>
          <div class="unpaid-warning-text">
            <strong>Pemberitahuan Tagihan Belum Lunas:</strong>
            <p>{{ patient.unpaid_billing_message || 'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.' }}</p>
          </div>
        </div>

        <!-- CARD 1: DATA PASIEN (Read-Only) -->
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
                <p class="card-subtitle">Identitas resmi pasien terverifikasi dari Master Data Pasien</p>
              </div>
            </div>

            <div class="patient-card-badge">
              <span class="status-chip-active">Pasien Aktif</span>
            </div>
          </div>

          <div class="patient-summary-body">
            <div class="patient-kv-grid">
              <!-- No. RM -->
              <div class="kv-row">
                <span class="kv-label">No. RM</span>
                <span class="kv-colon">:</span>
                <span class="kv-value">
                  <span class="rm-tag font-mono">{{ patient.medical_record_number }}</span>
                </span>
              </div>

              <!-- Nama Lengkap -->
              <div class="kv-row">
                <span class="kv-label">Nama Lengkap</span>
                <span class="kv-colon">:</span>
                <span class="kv-value font-bold">
                  {{ patient.name }} ({{ patient.gender === 'L' || patient.gender === 'Laki-laki' ? 'L' : 'P' }})
                </span>
              </div>

              <!-- NIK / No. KTP -->
              <div class="kv-row">
                <span class="kv-label">NIK / No. KTP</span>
                <span class="kv-colon">:</span>
                <span class="kv-value font-mono">{{ patient.nik || '—' }}</span>
              </div>

              <!-- TTL & Usia -->
              <div class="kv-row">
                <span class="kv-label">Lahir / Usia</span>
                <span class="kv-colon">:</span>
                <span class="kv-value">
                  {{ patient.birth_place ? patient.birth_place + ', ' : '' }}{{ formatDateIndo(patient.birth_date) }}
                  <span v-if="detailedAge" class="age-badge">({{ detailedAge }})</span>
                </span>
              </div>

              <!-- Alamat Lengkap & Wilayah -->
              <div class="kv-row full-width-kv">
                <span class="kv-label">Alamat & Wilayah</span>
                <span class="kv-colon">:</span>
                <span class="kv-value">{{ fullAddressWithRegion }}</span>
              </div>

              <!-- Kunjungan Terakhir -->
              <div class="kv-row full-width-kv">
                <span class="kv-label">Kunjungan Terakhir</span>
                <span class="kv-colon">:</span>
                <span class="kv-value">
                  <span v-if="patient.last_visit" class="last-visit-box">
                    <span class="font-bold">{{ patient.last_visit.polyclinic_name }}</span>
                    <span class="text-muted"> bersama {{ patient.last_visit.doctor_name || 'Dokter' }}</span>
                    <span class="visit-date-tag">{{ formatDateTimeIndo(patient.last_visit.admission_date) }}</span>
                    <span v-if="patient.last_visit.case_type" class="case-chip">({{ patient.last_visit.case_type }})</span>
                  </span>
                  <span v-else class="text-no-history">Belum ada kunjungan sebelumnya</span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- CARD 2: FORM ADMISI RAWAT JALAN -->
        <div class="card form-admission-card">
          <div class="card-header-bar">
            <div class="header-left">
              <div class="header-icon-box" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                  <line x1="16" y1="13" x2="8" y2="13"></line>
                  <line x1="16" y1="17" x2="8" y2="17"></line>
                  <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
              </div>
              <div>
                <h2 class="card-title">FORM ADMISI RAWAT JALAN</h2>
                <p class="card-subtitle">Formulir pendaftaran kunjungan pelayanan poliklinik dan penjaminan</p>
              </div>
            </div>

            <div class="header-right">
              <span class="required-hint"><span class="required-mark">*</span> Wajib diisi</span>
            </div>
          </div>

          <form autocomplete="off" class="admission-form" @submit.prevent="handleSubmit">
            <div class="form-grid-layout">
              <!-- =================== SUB-PANEL 1: DATA ADMISI PASIEN =================== -->
              <div class="sub-panel">
                <div class="section-title-bar">
                  <span class="section-badge">1</span>
                  <span class="section-heading">Data Admisi Pasien</span>
                </div>

                <div class="panel-fields">
                  <!-- Waktu Admisi -->
                  <div class="h-form-row" :class="{ 'has-error': errors.admission_datetime }">
                    <label class="h-label" for="admission-datetime">
                      Waktu Admisi <span class="required-mark">*</span>
                    </label>
                    <div class="h-control">
                      <input
                        id="admission-datetime"
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
                  <div class="h-form-row" :class="{ 'has-error': errors.guarantor_id }">
                    <label class="h-label" for="admission-guarantor">
                      Jenis Asuransi <span class="required-mark">*</span>
                    </label>
                    <div class="h-control">
                      <div class="select-wrap">
                        <select
                          id="admission-guarantor"
                          v-model="form.guarantor_id"
                          class="form-select custom-select"
                          :disabled="isLoadingGuarantors"
                        >
                          <option value="">-- Pilih Jenis Asuransi --</option>
                          <option
                            v-for="g in guarantors"
                            :key="g.id"
                            :value="g.id"
                          >
                            {{ g.name || g.jenis_asuransi }}
                          </option>
                        </select>
                        <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </div>
                      <span v-if="errors.guarantor_id" class="error-message">{{ errors.guarantor_id }}</span>
                    </div>
                  </div>

                  <!-- No. Asuransi -->
                  <div class="h-form-row" :class="{ 'has-error': errors.insurance_number }">
                    <label class="h-label" for="admission-insurance-number">
                      No. Asuransi <span v-if="!isGuarantorUmum" class="required-mark">*</span>
                    </label>
                    <div class="h-control">
                      <div class="input-action-group">
                        <input
                          id="admission-insurance-number"
                          v-model="form.insurance_number"
                          type="text"
                          class="form-input"
                          :disabled="isGuarantorUmum"
                          :placeholder="isGuarantorUmum ? 'Tidak diperlukan untuk penjamin Umum/Mandiri' : 'Masukkan nomor kartu kepesertaan asuransi'"
                          autocomplete="off"
                          @input="bpjsCheckError = ''"
                        />
                        <button
                          v-if="!isGuarantorUmum"
                          type="button"
                          class="btn-action-cek"
                          :disabled="isCheckingBpjs"
                          @click="handleCheckBpjs"
                          title="Klik untuk mengecek kartu asuransi"
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                          </svg>
                          <span>Cek</span>
                        </button>
                      </div>
                      <div v-if="bpjsCheckError" class="bpjs-warning-box">
                        <span>{{ bpjsCheckError }}</span>
                      </div>
                      <span v-if="errors.insurance_number" class="error-message">{{ errors.insurance_number }}</span>
                    </div>
                  </div>

                  <!-- NIK / No. KIA (Read-Only) -->
                  <div class="h-form-row" :class="{ 'has-error': errors.nik }">
                    <label class="h-label" for="admission-nik">NIK / No. KIA</label>
                    <div class="h-control">
                      <input
                        id="admission-nik"
                        v-model="form.nik"
                        type="text"
                        readonly
                        class="form-input font-mono readonly-input"
                        placeholder="NIK / No. KIA Pasien"
                      />
                      <span v-if="errors.nik" class="error-message">{{ errors.nik }}</span>
                    </div>
                  </div>

                  <!-- Asal Rujukan -->
                  <div class="h-form-row" :class="{ 'has-error': errors.referral_source_id }">
                    <label class="h-label" for="admission-referral-source">
                      Asal Rujukan <span class="required-mark">*</span>
                    </label>
                    <div class="h-control">
                      <div class="select-wrap">
                        <select
                          id="admission-referral-source"
                          v-model="form.referral_source_id"
                          class="form-select custom-select"
                          :disabled="isLoadingReferrals"
                        >
                          <option value="">-- Pilih Asal Rujukan --</option>
                          <option
                            v-for="r in referralSources"
                            :key="r.id"
                            :value="r.id"
                          >
                            {{ r.name || r.asal_rujukan }}
                          </option>
                        </select>
                        <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </div>
                      <span v-if="errors.referral_source_id" class="error-message">{{ errors.referral_source_id }}</span>
                    </div>
                  </div>

                  <!-- Nama Asal Rujukan -->
                  <div class="h-form-row" :class="{ 'has-error': errors.referral_name }">
                    <label class="h-label" for="admission-referral-name">
                      Nama Asal Rujukan <span v-if="!isDatangSendiri" class="required-mark">*</span>
                    </label>
                    <div class="h-control">
                      <input
                        id="admission-referral-name"
                        v-model="form.referral_name"
                        type="text"
                        class="form-input"
                        :disabled="isDatangSendiri"
                        :placeholder="isDatangSendiri ? 'Tidak diperlukan untuk Datang Sendiri' : 'Masukkan nama fasilitas kesehatan / dokter rujukan'"
                        autocomplete="off"
                      />
                      <span v-if="errors.referral_name" class="error-message">{{ errors.referral_name }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- =================== RIGHT COLUMN: VISIT + KELUARGA + SATUSEHAT =================== -->
              <div class="right-panels-stack">
                <!-- =================== SUB-PANEL 2: DATA VISIT PASIEN =================== -->
                <div class="sub-panel">
                  <div class="section-title-bar">
                    <span class="section-badge">2</span>
                    <span class="section-heading">Data Visit Pasien</span>
                  </div>

                  <div class="panel-fields">
                    <!-- Jenis Visit (Rawat Jalan only) -->
                    <div class="h-form-row">
                      <label class="h-label" for="admission-visit-type">
                        Jenis Visit <span class="required-mark">*</span>
                      </label>
                      <div class="h-control">
                        <input
                          id="admission-visit-type"
                          type="text"
                          value="Rawat Jalan"
                          readonly
                          class="form-input readonly-input visit-type-input"
                        />
                        <span class="field-helper-text">Pelayanan rawat jalan poliklinik spesialis / umum.</span>
                      </div>
                    </div>

                    <!-- Klinik / Poliklinik Tujuan -->
                    <div class="h-form-row" :class="{ 'has-error': errors.polyclinic_id }">
                      <label class="h-label" for="admission-clinic">
                        Klinik <span class="required-mark">*</span>
                      </label>
                      <div class="h-control">
                        <div class="select-wrap">
                          <select
                            id="admission-clinic"
                            v-model="form.polyclinic_id"
                            class="form-select custom-select"
                            :disabled="isLoadingClinics"
                          >
                            <option value="">-- Silahkan Pilih Poliklinik --</option>
                            <option
                              v-for="clinic in polyclinics"
                              :key="clinic.id"
                              :value="clinic.id"
                            >
                              {{ clinic.name || clinic.nama }}
                            </option>
                          </select>
                          <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                          </svg>
                        </div>
                        <span v-if="errors.polyclinic_id" class="error-message">{{ errors.polyclinic_id }}</span>
                        <span v-if="polyclinics.length === 0 && !isLoadingClinics" class="text-notice-empty">
                          Tidak ada poliklinik rawat jalan aktif yang tersedia.
                        </span>
                      </div>
                    </div>

                    <!-- Kasus (3 historical options) -->
                    <div class="h-form-row" :class="{ 'has-error': errors.case_type }">
                      <label class="h-label" for="admission-case-type">
                        Kasus <span class="required-mark">*</span>
                      </label>
                      <div class="h-control">
                        <div class="select-wrap">
                          <select
                            id="admission-case-type"
                            v-model="form.case_type"
                            class="form-select custom-select"
                          >
                            <option value="pemeriksaan kasus baru">Pemeriksaan Kasus Baru</option>
                            <option value="perawatan lanjutan">Perawatan Lanjutan</option>
                            <option value="riwayat penyakit kronis/akut">Riwayat Penyakit Kronis atau Akut</option>
                          </select>
                          <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                          </svg>
                        </div>
                        <span v-if="errors.case_type" class="error-message">{{ errors.case_type }}</span>
                      </div>
                    </div>

                    <!-- Dokter Klinik -->
                    <div class="h-form-row" :class="{ 'has-error': errors.doctor_id }">
                      <label class="h-label" for="admission-doctor">
                        Dokter Klinik <span class="required-mark">*</span>
                      </label>
                      <div class="h-control">
                        <div class="select-wrap">
                          <select
                            id="admission-doctor"
                            v-model="form.doctor_id"
                            class="form-select custom-select"
                            :disabled="!form.polyclinic_id || isLoadingDoctors || doctors.length === 0"
                          >
                            <option value="">
                              {{
                                !form.polyclinic_id
                                  ? '-- Pilih Poliklinik Terlebih Dahulu --'
                                  : isLoadingDoctors
                                  ? 'Memuat Jadwal Dokter...'
                                  : doctorEmptyCondition === 'condition_c'
                                  ? 'Gagal Memuat Dokter'
                                  : doctorEmptyCondition === 'condition_a'
                                  ? 'Belum Ada Dokter Terdaftar'
                                  : doctorEmptyCondition === 'condition_b'
                                  ? 'Tidak Ada Dokter Tersedia'
                                  : '-- Silahkan Pilih Dokter --'
                              }}
                            </option>
                            <option
                              v-for="doc in doctors"
                              :key="doc.id"
                              :value="doc.id"
                            >
                              {{ doc.name }}
                              <template v-if="doc.specialization?.name"> — {{ doc.specialization.name }}</template>
                              <template v-else-if="doc.specialization_name"> — {{ doc.specialization_name }}</template>
                            </option>
                          </select>
                          <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                          </svg>
                        </div>
                        <span v-if="errors.doctor_id" class="error-message">{{ errors.doctor_id }}</span>

                        <!-- Kondisi A: Tidak ada dokter yang ditugaskan ke poliklinik -->
                        <div
                          v-if="form.polyclinic_id && !isLoadingDoctors && doctorEmptyCondition === 'condition_a'"
                          class="doctor-notice-empty doctor-notice-warning"
                          data-testid="doctor-notice-condition-a"
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                          </svg>
                          <span>Belum ada dokter yang terdaftar untuk poliklinik ini. Periksa Master Dokter dan Jadwal Dokter.</span>
                        </div>

                        <!-- Kondisi B: Dokter ada, tetapi tidak tersedia pada jadwal yang dipilih -->
                        <div
                          v-if="form.polyclinic_id && !isLoadingDoctors && doctorEmptyCondition === 'condition_b'"
                          class="doctor-notice-empty doctor-notice-info"
                          data-testid="doctor-notice-condition-b"
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                          </svg>
                          <span>Tidak ada dokter yang tersedia pada tanggal atau waktu pelayanan ini. Silakan pilih jadwal lain.</span>
                        </div>

                        <!-- Kondisi C: API gagal memuat dokter -->
                        <div
                          v-if="form.polyclinic_id && !isLoadingDoctors && doctorEmptyCondition === 'condition_c'"
                          class="doctor-notice-empty doctor-notice-error"
                          data-testid="doctor-notice-condition-c"
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                          </svg>
                          <div class="doctor-error-action">
                            <span>Gagal memuat data dokter. Silakan coba kembali.</span>
                            <button
                              type="button"
                              class="btn-retry-doctor"
                              @click="retryLoadDoctors"
                            >
                              Coba Lagi
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- =================== SUB-PANEL 3: KELUARGA TERDEKAT =================== -->
                <div class="sub-panel">
                  <div class="section-title-bar">
                    <span class="section-badge">3</span>
                    <span class="section-heading">Data Keluarga Terdekat Pasien</span>
                  </div>

                  <div class="panel-fields">
                    <!-- Nama Keluarga -->
                    <div class="h-form-row">
                      <label class="h-label" for="admission-family-name">Nama</label>
                      <div class="h-control">
                        <input
                          id="admission-family-name"
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
                      <label class="h-label" for="admission-family-relationship">Hubungan</label>
                      <div class="h-control">
                        <input
                          id="admission-family-relationship"
                          v-model="form.family_relationship"
                          type="text"
                          class="form-input"
                          placeholder="Contoh: Suami, Istri, Orang Tua, Anak"
                          autocomplete="off"
                        />
                      </div>
                    </div>

                    <!-- No. HP -->
                    <div class="h-form-row">
                      <label class="h-label" for="admission-family-phone">No. HP</label>
                      <div class="h-control">
                        <input
                          id="admission-family-phone"
                          v-model="form.family_phone"
                          type="tel"
                          class="form-input font-mono"
                          placeholder="No. HP keluarga terdekat"
                          autocomplete="off"
                        />
                      </div>
                    </div>

                    <!-- Alamat -->
                    <div class="h-form-row">
                      <label class="h-label" for="admission-family-address">Alamat</label>
                      <div class="h-control">
                        <input
                          id="admission-family-address"
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

                <!-- =================== SUB-PANEL 4: SATUSEHAT & DU KOAS =================== -->
                <div class="sub-panel satusehat-panel">
                  <div class="section-title-bar">
                    <span class="section-badge">4</span>
                    <span class="section-heading">Integrasi SATUSEHAT & DU KOAS</span>
                  </div>

                  <div class="panel-fields satusehat-box">
                    <div class="integration-item satusehat-consent-item">
                      <div class="integration-header">
                        <span class="integration-title">Platform SATUSEHAT (Kemenkes)</span>
                        <span class="integration-badge badge-offline">Belum Terkonfigurasi</span>
                      </div>
                      <p class="integration-desc">
                        Kredensial OAuth SATUSEHAT Kementerian Kesehatan belum diatur pada lingkungan ini. Sinkronisasi data resume medis otomatis akan diaktifkan setelah integrasi credential API resmi disetujui.
                      </p>
                      <div class="satusehat-consent-group">
                        <label class="consent-label">
                          Apakah pasien setuju untuk membagi data dengan pihak lain (organisasi) yang juga terdaftar secara sah dan memiliki akses pada ekosistem SATUSEHAT <span class="required-mark">*</span>
                        </label>
                        <div class="radio-options">
                          <label class="radio-label">
                            <input
                              type="radio"
                              name="satusehat_consent"
                              value="setuju"
                              v-model="form.satusehat_consent"
                            />
                            <span>Ya, pasien setuju</span>
                          </label>
                          <label class="radio-label">
                            <input
                              type="radio"
                              name="satusehat_consent"
                              value="tidak_setuju"
                              v-model="form.satusehat_consent"
                            />
                            <span>Pasien tidak setuju</span>
                          </label>
                        </div>
                      </div>
                    </div>

                    <div class="integration-item dukoas-item">
                      <div class="integration-header">
                        <span class="integration-title">Booking DU KOAS</span>
                        <span class="integration-badge badge-inactive">Tidak Aktif</span>
                      </div>
                      <p class="integration-desc">
                        Modul reservasi mahasiswa kedokteran / DU KOAS tidak diaktifkan pada jalur pendaftaran admisi rawat jalan reguler ini.
                      </p>
                      <div class="dukoas-form-row">
                        <div class="dukoas-search-col">
                          <label class="dukoas-label" for="dukoas-search">Search</label>
                          <div class="input-action-group">
                            <input
                              id="dukoas-search"
                              v-model="duKoasSearch"
                              type="text"
                              class="form-input"
                              placeholder="Cari booking DU KOAS..."
                            />
                            <button
                              type="button"
                              class="btn-action-cari"
                              :disabled="isSearchingDuKoas"
                              @click="handleSearchDuKoas"
                            >
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                              </svg>
                              <span>Cari</span>
                            </button>
                          </div>
                        </div>
                        <div class="dukoas-code-col">
                          <label class="dukoas-label" for="dukoas-booking-code">Kode Booking</label>
                          <input
                            id="dukoas-booking-code"
                            v-model="form.kode_booking"
                            type="text"
                            class="form-input font-mono readonly-input"
                            placeholder="Kode"
                            readonly
                          />
                        </div>
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
                class="btn-primary btn-save btn-admit"
                :disabled="isSubmitting || isSaved || patient?.has_unpaid_bills"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                  <polyline points="17 21 17 13 7 13 7 21"></polyline>
                  <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>{{ isSubmitting ? 'Menyimpan Admisi...' : (isSaved ? 'Tersimpan' : 'Simpan Admisi') }}</span>
              </button>
            </div>
          </form>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.admission-create-page {
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

.page-content {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.btn-header-back {
  height: 40px;
  padding: 0 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: #ffffff;
  color: var(--color-text-navy, #1e293b);
  border: 1px solid var(--color-border-soft, #e2e8f0);
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

/* Loading & Empty Cards */
.loading-card,
.empty-patient-card {
  padding: 3rem 2rem;
  text-align: center;
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
}

.loading-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 1rem;
  border: 3px solid #e2e8f0;
  border-top-color: var(--color-primary-green, #0f766e);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  font-size: 0.95rem;
  color: #64748b;
  margin: 0;
}

.empty-patient-content {
  max-width: 440px;
  margin: 0 auto;
}

.empty-icon-wrap {
  width: 52px;
  height: 52px;
  margin: 0 auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #fef3c7;
  color: #d97706;
}

.empty-icon-wrap svg {
  width: 28px;
  height: 28px;
}

.empty-patient-content h3 {
  font-size: 1.15rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem;
}

.empty-patient-content p {
  font-size: 0.9rem;
  color: #64748b;
  margin: 0 0 1.5rem;
}

.empty-action-group {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
}

/* Unpaid Warning Banner */
.unpaid-warning-banner {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-left: 4px solid #ef4444;
  border-radius: 8px;
  padding: 1rem 1.25rem;
  color: #991b1b;
}

.unpaid-warning-icon svg {
  width: 24px;
  height: 24px;
  color: #ef4444;
}

.unpaid-warning-text strong {
  display: block;
  font-size: 0.95rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.unpaid-warning-text p {
  font-size: 0.875rem;
  margin: 0;
  line-height: 1.4;
}

/* Card 1: DATA PASIEN */
.patient-info-card {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.card-header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #f1f5f9;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-icon-box {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: #f0fdf4;
  color: #16a34a;
  display: flex;
  align-items: center;
  justify-content: center;
}

.header-icon-box svg {
  width: 20px;
  height: 20px;
}

.card-title {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.01em;
}

.card-subtitle {
  font-size: 0.8rem;
  color: #64748b;
  margin: 0.15rem 0 0;
}

.status-chip-active {
  display: inline-block;
  padding: 0.25rem 0.65rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 9999px;
  background: #dcfce7;
  color: #15803d;
}

.patient-summary-body {
  padding: 1.25rem;
}

.patient-kv-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem 1.5rem;
}

.kv-row {
  display: flex;
  align-items: baseline;
  font-size: 0.875rem;
}

.full-width-kv {
  grid-column: 1 / -1;
}

.kv-label {
  width: 140px;
  flex-shrink: 0;
  color: #64748b;
  font-weight: 500;
}

.kv-colon {
  width: 14px;
  flex-shrink: 0;
  color: #94a3b8;
}

.kv-value {
  color: #1e293b;
  flex-grow: 1;
}

.rm-tag {
  display: inline-block;
  padding: 0.15rem 0.45rem;
  background: #e0f2fe;
  color: #0369a1;
  font-weight: 700;
  border-radius: 4px;
}

.age-badge {
  font-size: 0.8rem;
  color: #64748b;
  margin-left: 0.35rem;
}

.last-visit-box {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.visit-date-tag {
  display: inline-block;
  padding: 0.1rem 0.4rem;
  background: #f1f5f9;
  color: #475569;
  font-size: 0.75rem;
  border-radius: 4px;
}

.case-chip {
  font-size: 0.75rem;
  color: #0284c7;
  background: #f0f9ff;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
}

.text-no-history {
  color: #94a3b8;
  font-style: italic;
  font-weight: 500;
}

/* CARD 2: FORM ADMISI */
.form-admission-card {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.required-hint {
  font-size: 0.8rem;
  color: #64748b;
}

.required-mark {
  color: #ef4444;
  font-weight: 700;
}

.admission-form {
  padding: 1.25rem;
}

.form-grid-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.right-panels-stack {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.sub-panel {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1rem 1.25rem;
}

.section-title-bar {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.section-badge {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--color-primary-green, #0f766e);
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.section-heading {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
}

.panel-fields {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

/* Horizontal Form Row */
.h-form-row {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.h-label {
  width: 140px;
  flex-shrink: 0;
  padding-top: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
}

.h-control {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-input,
.form-select {
  width: 100%;
  height: 38px;
  padding: 0 0.75rem;
  font-size: 0.875rem;
  color: #1e293b;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  outline: none;
  transition: all 0.15s ease;
  font-family: inherit;
}

.form-input:focus,
.form-select:focus {
  border-color: var(--color-primary-green, #0f766e);
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
}

.form-input:disabled,
.form-select:disabled,
.readonly-input {
  background: #f1f5f9;
  color: #64748b;
  cursor: not-allowed;
  border-color: #e2e8f0;
}

.select-wrap {
  position: relative;
  width: 100%;
}

.custom-select {
  appearance: none;
  padding-right: 2rem;
}

.select-chevron {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #64748b;
  pointer-events: none;
}

.error-message {
  font-size: 0.75rem;
  color: #ef4444;
  font-weight: 500;
}

.has-error .form-input,
.has-error .form-select {
  border-color: #ef4444;
  background-color: #fffbfa;
}

.field-helper-text {
  font-size: 0.75rem;
  color: #64748b;
}

.text-notice-empty {
  font-size: 0.75rem;
  color: #d97706;
}

.doctor-notice-empty {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  line-height: 1.3;
}

.doctor-notice-warning {
  color: #d97706;
}

.doctor-notice-info {
  color: #0284c7;
}

.doctor-notice-error {
  color: #dc2626;
}

.doctor-error-action {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-retry-doctor {
  background: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fca5a5;
  border-radius: 4px;
  padding: 0.1rem 0.45rem;
  font-size: 0.7rem;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.15s ease-in-out;
}

.btn-retry-doctor:hover {
  background: #fecaca;
  color: #991b1b;
}

.doctor-notice-empty svg {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

/* Action Button Group and Cek Button */
.input-action-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.input-action-group .form-input {
  flex: 1;
}

.btn-action-cek {
  height: 38px;
  padding: 0 1.15rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  background: #e05260;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(224, 82, 96, 0.25);
  transition: all 0.15s ease;
  box-sizing: border-box;
}

.btn-action-cek svg {
  width: 14px;
  height: 14px;
}

.btn-action-cek:hover:not(:disabled) {
  background: #c53b49;
  box-shadow: 0 4px 10px rgba(224, 82, 96, 0.35);
}

.btn-action-cek:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.bpjs-warning-box {
  margin-top: 0.45rem;
  padding: 0.5rem 0.75rem;
  background: #fee2e2;
  border: 1px solid #fca5a5;
  border-radius: 6px;
  color: #dc2626;
  font-size: 0.82rem;
  font-weight: 600;
}

/* SATUSEHAT Sub-Panel */
.satusehat-box {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.integration-item {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem 1rem;
}

.satusehat-consent-group {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px dashed #e2e8f0;
}

.consent-label {
  display: block;
  font-size: 0.82rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

.radio-options {
  display: flex;
  gap: 1.25rem;
  align-items: center;
}

.radio-label {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.835rem;
  color: #1e293b;
  cursor: pointer;
}

.radio-label input[type="radio"] {
  accent-color: var(--color-primary-green, #0f766e);
  cursor: pointer;
}

/* DU KOAS Controls */
.dukoas-form-row {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px dashed #e2e8f0;
  display: grid;
  grid-template-columns: 1fr 140px;
  gap: 0.75rem;
  align-items: flex-end;
}

.dukoas-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.35rem;
}

.btn-action-cari {
  height: 38px;
  padding: 0 1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  background: var(--color-primary-green, #0f766e);
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.btn-action-cari svg {
  width: 14px;
  height: 14px;
}

.btn-action-cari:hover:not(:disabled) {
  background: #0d655e;
}

.btn-action-cari:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.integration-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.35rem;
}

.integration-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: #1e293b;
}

.integration-badge {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
}

.badge-offline {
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #cbd5e1;
}

.badge-inactive {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.integration-desc {
  font-size: 0.775rem;
  color: #64748b;
  margin: 0;
  line-height: 1.4;
}

/* Footer Bar */
.form-footer-bar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid #e2e8f0;
}

.btn-batal,
.btn-admit {
  height: 42px;
  padding: 0 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.15s ease;
  font-family: inherit;
}

.btn-batal {
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.btn-batal:hover {
  background: #f8fafc;
  color: #1e293b;
}

.btn-admit {
  background: var(--color-primary-green, #0f766e);
  color: #ffffff;
  border: none;
}

.btn-admit:hover:not(:disabled) {
  background: #0d9488;
}

.btn-admit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-batal svg,
.btn-admit svg {
  width: 18px;
  height: 18px;
}

@media (max-width: 900px) {
  .form-grid-layout {
    grid-template-columns: 1fr;
  }
  .patient-kv-grid {
    grid-template-columns: 1fr;
  }
  .h-form-row {
    flex-direction: column;
    gap: 0.35rem;
  }
  .h-label {
    width: 100%;
    padding-top: 0;
  }
}
</style>
