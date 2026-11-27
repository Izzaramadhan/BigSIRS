<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import admissionService from '@/services/admission.service';
import patientService from '@/services/patient.service';
import lookupService from '@/services/lookup.service';
import { polyclinicService } from '@/services/polyclinic';
import {
  findMatchingGuarantor,
  isSelfPayGuarantor,
  useGuarantorOptions
} from '@/composables/useGuarantorOptions';

const route = useRoute();
const router = useRouter();

const {
  loading: guarantorsLoading,
  error: guarantorsError,
  isEmpty: guarantorsEmpty,
  fetchGuarantors,
  ensureSavedOption
} = useGuarantorOptions();

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

// Master Data State
const polyclinicList = ref([]);
const backendDoctorList = ref([]);
const referralSourceList = ref([]);

const initialPolyclinic = ref('');
const recordVisitId = ref(null);

const normalizeCaseType = (val) => {
  if (!val) return 'Pemeriksaan Kasus Baru';
  const clean = String(val).trim().toLowerCase();
  if (clean.includes('kronis') || clean.includes('akut')) {
    return 'Riwayat Penyakit Kronis atau Akut';
  }
  if (clean.includes('lanjutan') || clean.includes('kontrol') || clean.includes('lama')) {
    return 'Perawatan Lanjutan';
  }
  return 'Pemeriksaan Kasus Baru';
};

const canChangeClinic = ref(true);
const cannotChangeReason = ref('');
const isServiceLocked = ref(false);

const savedGuarantorContext = ref({ id: null, name: '' });

const guarantorOptions = computed(() =>
  ensureSavedOption(savedGuarantorContext.value)
);

const selectedGuarantorOption = computed(() =>
  guarantorOptions.value.find((g) => String(g.id) === String(form.value.guarantor_id)) || null
);

const isSelfPaySelected = computed(() => {
  if (selectedGuarantorOption.value) {
    return selectedGuarantorOption.value.is_self_pay;
  }
  return isSelfPayGuarantor(form.value.guarantor_type);
});

const polyclinicOptions = computed(() => {
  const base = new Set();
  if (polyclinicList.value.length > 0) {
    polyclinicList.value.forEach((p) => {
      const name = p.name || p.nama || p.nama_poliklinik;
      if (name) base.add(name);
    });
  }
  if (form.value.polyclinic) base.add(form.value.polyclinic);
  return Array.from(base);
});

const doctorOptions = computed(() => {
  const base = new Set();
  if (backendDoctorList.value.length > 0) {
    backendDoctorList.value.forEach((d) => {
      const name = d.name || d.nama || d.nama_lengkap;
      if (name) base.add(name);
    });
  }
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
  guarantor_id: '',
  guarantor_type: 'UMUM',
  insurance_number: '',
  nik: '',
  referral_source_id: 3,
  referral_source: 'Datang Sendiri',
  referral_name: '',

  visit_type: 'IGD',
  polyclinic_id: null,
  polyclinic: '',
  service_type: 'Umum',
  case_type: 'Pemeriksaan Kasus Baru',
  doctor_id: null,
  doctor: '',

  family_name: '',
  family_relationship: 'IBU',
  family_phone: '',
  family_address: '',
  notes: ''
});

const errors = ref({});
const isSubmitting = ref(false);
const isLoadingRecord = ref(false);
const loadError = ref('');

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

const unwrapList = (payload) => {
  const inner = payload?.data?.data ?? payload?.data ?? payload;
  return Array.isArray(inner) ? inner : [];
};

// Fetch doctors for clinic
const loadDoctorsForClinic = async (polyId) => {
  if (!polyId) return;
  try {
    const docs = await lookupService.getDoctorsByPolyclinic(polyId);
    backendDoctorList.value = unwrapList(docs);
  } catch {
    backendDoctorList.value = [];
  }
};

// Master Data Loaders
const loadMasterData = async () => {
  try {
    const [, polyclinicRes, referralRes] = await Promise.allSettled([
      fetchGuarantors(savedGuarantorContext.value.id ? { ids: savedGuarantorContext.value.id } : {}),
      polyclinicService.getPolyclinics({ is_active: true }),
      lookupService.getReferralSources()
    ]);

    if (polyclinicRes.status === 'fulfilled') {
      polyclinicList.value = unwrapList(polyclinicRes.value);
    }
    if (referralRes.status === 'fulfilled') {
      referralSourceList.value = unwrapList(referralRes.value);
    }
  } catch {
    // Fail-safe
  }
};

const syncSelectedGuarantorFromMaster = () => {
  const matched = findMatchingGuarantor(guarantorOptions.value, savedGuarantorContext.value);
  if (matched) {
    form.value.guarantor_id = matched.id;
    form.value.guarantor_type = matched.name;
    if (matched.is_self_pay) {
      form.value.insurance_number = '';
    }
  }
};

const fetchBackendRecord = async (options = { syncGuarantorFetch: true }) => {
  const targetId = route.params.id;
  const queryPatientId = route.query.patient_id;
  if (!targetId && !queryPatientId) return;

  let loadedAdmission = false;
  isLoadingRecord.value = true;
  loadError.value = '';

  try {
    if (targetId && admissionService && typeof admissionService.getAdmission === 'function') {
      const res = await admissionService.getAdmission(targetId);
      const record = res.data?.data || res.data;
      if (!record?.id) {
        throw new Error('Data admisi tidak ditemukan di server.');
      }
      loadedAdmission = true;
      recordVisitId.value = record.visit_id || null;
      canChangeClinic.value = record.can_change_clinic !== false;
      cannotChangeReason.value = record.cannot_change_reason || '';

      const vStatus = String(record.service_status || record.status || '').toLowerCase();
      isServiceLocked.value = vStatus === 'selesai' || vStatus === 'ya';

      const pat = record.patient || {};
      patientSummary.value = {
        id: record.id,
        queue_number: record.queue_number || '-',
        medical_record_number: pat.medical_record_number || pat.no_rm || record.medical_record_number || '-',
        patient_name: pat.name || pat.nama || record.patient_name || '-',
        gender: pat.gender || record.gender || 'L',
        birth_place: pat.birth_place || pat.tempat_lahir || record.birth_place || '-',
        birth_date: pat.birth_date || pat.tgl_lahir || record.birth_date || '',
        address: pat.address || pat.alamat || record.address || '-'
      };

      const datePart = record.admission_date ? formatDateDMY(record.admission_date) : '';
      const timePart = record.admission_time ? formatTimeHMS(record.admission_time) : '08:00:00';
      const rawGuarantorName = normalizeGuarantor(record.guarantor_type || record.guarantor);
      savedGuarantorContext.value = {
        id: record.guarantor_id ?? null,
        name: rawGuarantorName
      };

      form.value.admission_datetime = record.admission_datetime || (datePart ? `${datePart} ${timePart}` : timePart);
      form.value.guarantor_id = record.guarantor_id ? String(record.guarantor_id) : '';
      form.value.guarantor_type = rawGuarantorName;
      form.value.insurance_number = isSelfPayGuarantor(rawGuarantorName)
        ? ''
        : (record.insurance_number || record.guarantor_detail || '');
      if (options?.syncGuarantorFetch && record.guarantor_id) {
        await fetchGuarantors({ ids: record.guarantor_id });
      }
      syncSelectedGuarantorFromMaster();
      form.value.nik = record.nik || pat.nik || pat.no_ktp || '';
      form.value.referral_source_id = record.referral_source_id || 3;
      form.value.referral_source = record.referral_source || 'Datang Sendiri';
      form.value.referral_name = record.referral_name || '';
      form.value.visit_type = record.visit_type || 'Rawat Jalan';
      form.value.polyclinic_id = record.polyclinic_id || null;
      form.value.polyclinic = record.polyclinic || '';
      form.value.service_type = record.service_type || 'Umum';
      form.value.case_type = normalizeCaseType(record.case_type);
      form.value.doctor_id = record.doctor_id || null;
      form.value.doctor = record.doctor || '';
      initialPolyclinic.value = form.value.polyclinic;
      form.value.family_name = record.family_name || record.emergency_contact?.name || pat.emergency_contact?.name || '';
      form.value.family_relationship = record.family_relationship || record.emergency_contact?.relationship || pat.emergency_contact?.relationship || 'IBU';
      form.value.family_phone = record.family_phone || record.emergency_contact?.phone || pat.emergency_contact?.phone || '';
      form.value.family_address = record.family_address || record.emergency_contact?.address || pat.emergency_contact?.address || '';
      form.value.notes = record.notes || '';

      if (record.polyclinic_id) {
        loadDoctorsForClinic(record.polyclinic_id);
      }
    }
  } catch (err) {
    const status = err?.response?.status;
    let errMsg = err?.response?.data?.message || err?.message || 'Gagal memuat detail admisi dari server.';
    if (!err?.response && !err?.message?.includes('tidak ditemukan')) {
      errMsg = 'Tidak dapat terhubung ke server untuk memuat detail admisi.';
    } else if (status === 401 || status === 403) {
      errMsg = err?.response?.data?.message || 'Sesi berakhir atau tidak memiliki izin membaca data admisi.';
    }
    loadError.value = errMsg;
    showToast({
      type: 'danger',
      title: 'Gagal Memuat Data Admisi',
      message: errMsg
    });
  }

  // Only hydrate patient context from patientService if explicit query patient_id was provided and summary is incomplete
  if (queryPatientId && (!loadedAdmission || patientSummary.value.patient_name === '-')) {
    try {
      if (patientService && typeof patientService.getPatient === 'function') {
        const pRes = await patientService.getPatient(queryPatientId);
        const pat = pRes.data?.data || pRes.data;
        if (pat) {
          patientSummary.value = {
            id: pat.id,
            queue_number: pat.queue_number || '-',
            medical_record_number: pat.medical_record_number || pat.no_rm || '-',
            patient_name: pat.name || pat.nama || '-',
            gender: pat.gender || 'L',
            birth_place: pat.birth_place || pat.tempat_lahir || '-',
            birth_date: pat.birth_date || pat.tgl_lahir || '',
            address: pat.address || pat.alamat || '-'
          };

          if (pat.nik || pat.no_ktp) form.value.nik = pat.nik || pat.no_ktp;
          if (pat.emergency_contact?.name) form.value.family_name = pat.emergency_contact.name;
          if (pat.emergency_contact?.relationship) form.value.family_relationship = pat.emergency_contact.relationship;
          if (pat.emergency_contact?.phone) form.value.family_phone = pat.emergency_contact.phone;
          if (pat.emergency_contact?.address) form.value.family_address = pat.emergency_contact.address;
          if (pat.guarantor) {
            const patGuarName = normalizeGuarantor(pat.guarantor.name || pat.guarantor);
            savedGuarantorContext.value = {
              id: pat.guarantor.id ?? null,
              name: patGuarName
            };
            form.value.guarantor_id = pat.guarantor.id ? String(pat.guarantor.id) : form.value.guarantor_id;
            form.value.guarantor_type = patGuarName;
            if (pat.guarantor.id) {
              await fetchGuarantors({ ids: pat.guarantor.id });
            }
            syncSelectedGuarantorFromMaster();
          }
          if (pat.insurance_number && !isSelfPaySelected.value) {
            form.value.insurance_number = pat.insurance_number;
          }
        }
      }
    } catch (e) {
      console.warn('Gagal memuat detail pasien untuk edit form:', e);
    }
  }
  isLoadingRecord.value = false;
};

watch(
  () => form.value.guarantor_id,
  () => {
    if (selectedGuarantorOption.value) {
      form.value.guarantor_type = selectedGuarantorOption.value.name;
    }
    if (isSelfPaySelected.value) {
      form.value.insurance_number = '';
    }
    if (errors.value.guarantor_type) {
      delete errors.value.guarantor_type;
    }
  }
);

watch(
  () => form.value.referral_source,
  (val) => {
    if (val === 'Datang Sendiri') {
      form.value.referral_name = '';
      form.value.referral_source_id = 3;
    } else {
      const match = referralSourceList.value.find((r) => (r.name || r.nama) === val);
      if (match) form.value.referral_source_id = match.id;
    }
  }
);

watch(
  () => form.value.polyclinic,
  (val) => {
    const match = polyclinicList.value.find((p) => (p.name || p.nama || p.nama_poliklinik) === val);
    if (match) {
      form.value.polyclinic_id = match.id;
      loadDoctorsForClinic(match.id);
    }
  }
);

watch(
  () => form.value.doctor,
  (val) => {
    const match = backendDoctorList.value.find((d) => (d.name || d.nama || d.nama_lengkap) === val);
    if (match) {
      form.value.doctor_id = match.id;
    }
  }
);

watch(() => route.params.id, async () => {
  await fetchBackendRecord();
});

onMounted(async () => {
  await fetchBackendRecord({ syncGuarantorFetch: false });
  await loadMasterData();
  syncSelectedGuarantorFromMaster();
});

const validateForm = () => {
  const newErrors = {};

  if (!form.value.admission_datetime.trim()) {
    newErrors.admission_datetime = 'Waktu Admisi wajib diisi.';
  }
  if (!form.value.guarantor_id && !form.value.guarantor_type) {
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

const handleUpdate = async () => {
  if (isSubmitting.value) return;

  if (!validateForm()) {
    showToast({
      type: 'warning',
      title: 'Validasi Form',
      message: 'Mohon lengkapi kolom wajib yang ditandai bintang (*).'
    });
    return;
  }

  // Resolve guarantor_id without hardcoded defaults
  const guarantorId = selectedGuarantorOption.value?.numericId ?? (form.value.guarantor_id ? Number(form.value.guarantor_id) : null);
  const resolvedGuarantorName = selectedGuarantorOption.value?.name || form.value.guarantor_type;

  // Resolve polyclinic_id from loaded data or master list
  let polyclinicId = form.value.polyclinic_id;
  if (!polyclinicId) {
    const matchP = polyclinicList.value.find((p) => (p.name || p.nama || p.nama_poliklinik) === form.value.polyclinic);
    polyclinicId = matchP ? matchP.id : null;
  }

  // Resolve doctor_id from loaded data or master list
  let doctorId = form.value.doctor_id;
  if (!doctorId) {
    const matchD = backendDoctorList.value.find((d) => (d.name || d.nama || d.nama_lengkap) === form.value.doctor);
    doctorId = matchD ? matchD.id : null;
  }

  if (!guarantorId || !polyclinicId || !doctorId) {
    if (!guarantorId) errors.value.guarantor_type = 'Referensi penjamin belum valid.';
    if (!polyclinicId) errors.value.polyclinic = 'Referensi poliklinik belum valid.';
    if (!doctorId) errors.value.doctor = 'Referensi dokter belum valid.';
    showToast({
      type: 'warning',
      title: 'Referensi Belum Valid',
      message: 'Pastikan data penjamin, poliklinik, dan dokter valid sebelum menyimpan pembaruan.'
    });
    return;
  }

  // Resolve referral_source_id
  let referralSourceId = form.value.referral_source_id;
  if (!referralSourceId) {
    const matchR = referralSourceList.value.find((r) => (r.name || r.nama) === form.value.referral_source);
    referralSourceId = matchR ? matchR.id : (form.value.referral_source === 'Datang Sendiri' ? 3 : null);
  }

  isSubmitting.value = true;
  errors.value = {};

  const payload = {
    visit_id: recordVisitId.value,
    guarantor_id: Number(guarantorId),
    guarantor_type: resolvedGuarantorName,
    guarantor_detail: isSelfPaySelected.value
      ? 'Mandiri / Pribadi'
      : (form.value.insurance_number || resolvedGuarantorName),
    insurance_number: isSelfPaySelected.value ? '' : form.value.insurance_number,
    referral_source_id: referralSourceId ? Number(referralSourceId) : null,
    referral_source: form.value.referral_source,
    referral_name: form.value.referral_source === 'Datang Sendiri' ? '' : form.value.referral_name.trim(),
    polyclinic_id: Number(polyclinicId),
    doctor_id: Number(doctorId),
    case_type: form.value.case_type,
    notes: form.value.notes || '',
    family_name: form.value.family_name.trim(),
    family_relationship: form.value.family_relationship.trim(),
    family_phone: form.value.family_phone.trim(),
    family_address: form.value.family_address.trim(),
    reason: 'Update data admisi dan visit poliklinik'
  };

  try {
    const targetId = patientSummary.value.id || route.params.id;
    if (!targetId) {
      throw new Error('ID admisi tidak ditemukan.');
    }

    const res = await admissionService.updateAdmission(targetId, payload);
    const updated = res?.data?.data || res?.data;
    if (!updated?.id && !res?.data?.success) {
      throw new Error('Server tidak mengembalikan konfirmasi pembaruan admisi yang valid.');
    }

    showToast({
      type: 'success',
      title: 'Data Admisi Diperbarui',
      message: `Data admisi ${patientSummary.value.patient_name} berhasil diperbarui.`
    });

    setTimeout(() => {
      if (route.query.from === 'pendaftaran') {
        router.push('/pendaftaran');
      } else {
        router.push('/admisi');
      }
    }, 550);
  } catch (err) {
    const status = err?.response?.status;
    if (status === 422 && err.response?.data) {
      const errData = err.response.data;
      const backendErrors = errData.errors || {};
      const mapped = {};
      for (const [k, msgs] of Object.entries(backendErrors)) {
        mapped[k] = Array.isArray(msgs) ? msgs[0] : msgs;
      }
      errors.value = { ...errors.value, ...mapped };
      showToast({
        type: 'danger',
        title: 'Pembatasan / Validasi Gagal',
        message: errData.message || 'Data tidak dapat diperbarui karena pembatasan pelayanan.'
      });
    } else if (status === 401 || status === 403) {
      showToast({
        type: 'danger',
        title: 'Akses Ditolak',
        message: err.response?.data?.message || 'Sesi berakhir atau Anda tidak memiliki izin mengubah admisi.'
      });
    } else if (status === 404) {
      showToast({
        type: 'danger',
        title: 'Admisi Tidak Ditemukan',
        message: err.response?.data?.message || 'Data admisi tidak ditemukan di server.'
      });
    } else if (!err?.response) {
      showToast({
        type: 'danger',
        title: 'Koneksi Bermasalah',
        message: 'Tidak dapat terhubung ke server. Perubahan admisi belum disimpan.'
      });
    } else {
      showToast({
        type: 'danger',
        title: 'Gagal Memperbarui Admisi',
        message: err.response?.data?.message || err.message || 'Terjadi kesalahan sistem saat menyimpan pembaruan admisi.'
      });
    }
  } finally {
    isSubmitting.value = false;
  }
};

const handleCancel = () => {
  if (route.query.from === 'pendaftaran') {
    router.push('/pendaftaran');
  } else {
    router.push('/admisi');
  }
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
        { label: route.query.from === 'pendaftaran' ? 'Pendaftaran Pasien' : 'Data Admisi Pasien', active: false },
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
          <span>{{ route.query.from === 'pendaftaran' ? 'Kembali ke Pendaftaran' : 'Kembali ke Data Admisi' }}</span>
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Main Page Content -->
    <div class="page-content">
      <div v-if="loadError" class="clinic-restriction-banner" role="alert">
        <span>{{ loadError }}</span>
        <button type="button" class="btn-retry-guarantor" @click="fetchBackendRecord">Muat Ulang</button>
      </div>

      <!-- Card 1: DATA PASIEN -->
      <div class="card patient-info-card">
        <div class="card-header-bar patient-info-header">
          <div class="header-left">
            <div class="header-icon-user" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </div>
            <h2 class="card-title patient-card-title">DATA PASIEN</h2>
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
              <span class="kv-value font-mono">{{ patientSummary.medical_record_number }}</span>
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
        <div class="card-header-bar form-edit-header">
          <div class="header-left">
            <div class="header-icon-box-layers" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                <polyline points="2 17 12 22 22 17"></polyline>
                <polyline points="2 12 12 17 22 12"></polyline>
              </svg>
            </div>
            <h2 class="card-title form-edit-title">FORM EDIT</h2>
          </div>

          <div class="header-tools-icons">
            <button type="button" class="btn-card-tool" title="Collapse / Minimize">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <button type="button" class="btn-card-tool" title="Refresh" @click="fetchBackendRecord">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
              </svg>
            </button>
            <button type="button" class="btn-card-tool" title="Close" @click="handleCancel">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>

        <form autocomplete="off" class="edit-admission-form" @submit.prevent="handleUpdate">
          <div class="edit-form-columns">
            <!-- Kolom Kiri: Data Admisi Pasien -->
            <div class="edit-form-col left-col">
              <h3 class="category-title">Data Admisi Pasien</h3>
              <div class="category-box">
                <!-- 1. Waktu Admisi -->
                <div class="h-form-row" :class="{ 'has-error': errors.admission_datetime }">
                  <label class="h-label" for="edit-admission-datetime">
                    Waktu Admisi <span class="required-mark">*</span>
                  </label>
                  <div class="h-control">
                    <input
                      id="edit-admission-datetime"
                      v-model="form.admission_datetime"
                      type="text"
                      class="form-input"
                      placeholder="18-11-2026 09:25:00"
                      autocomplete="off"
                    />
                    <span v-if="errors.admission_datetime" class="error-message">{{ errors.admission_datetime }}</span>
                  </div>
                </div>

                <!-- 2. Jenis Asuransi -->
                <div class="h-form-row" :class="{ 'has-error': errors.guarantor_type }">
                  <label class="h-label" for="edit-guarantor">
                    Jenis Asuransi <span class="required-mark">*</span>
                  </label>
                  <div class="h-control">
                    <div class="select-wrap">
                      <select
                        id="edit-guarantor"
                        v-model="form.guarantor_id"
                        class="form-select custom-select"
                        :disabled="guarantorsLoading && guarantorOptions.length === 0"
                      >
                        <option v-if="guarantorOptions.length === 0" value="">
                          {{ guarantorsLoading ? 'Memuat daftar asuransi...' : '-- Pilih Jenis Asuransi --' }}
                        </option>
                        <option
                          v-for="g in guarantorOptions"
                          :key="g.id"
                          :value="g.id"
                        >
                          {{ g.label }}
                        </option>
                      </select>
                      <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </div>
                    <span v-if="guarantorsLoading" class="guarantor-helper-muted">Memuat data penjamin...</span>
                    <span v-else-if="guarantorsError" class="error-message">
                      {{ guarantorsError }}
                      <button type="button" class="btn-retry-guarantor" @click="fetchGuarantors(savedGuarantorContext.id ? { ids: savedGuarantorContext.id } : {})">Coba Lagi</button>
                    </span>
                    <span v-else-if="guarantorsEmpty && guarantorOptions.length === 0" class="guarantor-helper-muted">
                      Data penjamin Master Data kosong.
                    </span>
                    <span v-if="errors.guarantor_type" class="error-message">{{ errors.guarantor_type }}</span>
                  </div>
                </div>

                <!-- 3. No. Asuransi -->
                <div class="h-form-row">
                  <label class="h-label" for="edit-insurance-number">No. Asuransi</label>
                  <div class="h-control">
                    <input
                      id="edit-insurance-number"
                      v-model="form.insurance_number"
                      type="text"
                      class="form-input"
                      :disabled="isSelfPaySelected"
                      :placeholder="isSelfPaySelected ? 'No Asuransi' : 'Masukkan Nomor Kartu Asuransi / BPJS'"
                      autocomplete="off"
                    />
                  </div>
                </div>

                <!-- 4. NIK / No. KIA -->
                <div class="h-form-row" :class="{ 'has-error': errors.nik }">
                  <label class="h-label" for="edit-nik">NIK / No. KIA</label>
                  <div class="h-control">
                    <input
                      id="edit-nik"
                      v-model="form.nik"
                      type="text"
                      maxlength="16"
                      class="form-input"
                      placeholder="3333332121212345"
                      autocomplete="off"
                    />
                    <span v-if="errors.nik" class="error-message">{{ errors.nik }}</span>
                  </div>
                </div>

                <!-- 5. Asal Rujukan -->
                <div class="h-form-row" :class="{ 'has-error': errors.referral_source }">
                  <label class="h-label" for="edit-referral-source">
                    Asal Rujukan <span class="required-mark">*</span>
                  </label>
                  <div class="h-control">
                    <div class="select-wrap">
                      <select id="edit-referral-source" v-model="form.referral_source" class="form-select custom-select">
                        <option value="Datang Sendiri">Datang Sendiri</option>
                        <option value="FKT Primer">FKT Primer</option>
                        <option value="Puskesmas">Puskesmas</option>
                        <option value="Klinik Pratama">Klinik Pratama</option>
                        <option value="Dokter Praktek Mandiri">Dokter Praktek Mandiri</option>
                        <option value="RS Lain / Rujukan Lanjutan">RS Lain / Rujukan Lanjutan</option>
                        <option value="IGD">IGD</option>
                        <template v-for="r in referralSourceList" :key="r.id">
                          <option
                            v-if="!['Datang Sendiri', 'FKT Primer', 'Puskesmas', 'Klinik Pratama', 'Dokter Praktek Mandiri', 'RS Lain / Rujukan Lanjutan', 'IGD'].includes(r.name || r.nama)"
                            :value="r.name || r.nama"
                          >
                            {{ r.name || r.nama }}
                          </option>
                        </template>
                      </select>
                      <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </div>
                    <span v-if="errors.referral_source" class="error-message">{{ errors.referral_source }}</span>
                  </div>
                </div>

                <!-- 6. Nama Asal Rujukan -->
                <div class="h-form-row" :class="{ 'has-error': errors.referral_name }">
                  <label class="h-label" for="edit-referral-name">Nama Asal Rujukan</label>
                  <div class="h-control">
                    <input
                      id="edit-referral-name"
                      v-model="form.referral_name"
                      type="text"
                      class="form-input"
                      :disabled="form.referral_source === 'Datang Sendiri'"
                      placeholder="Nama Asal Rujukan"
                      autocomplete="off"
                    />
                    <span v-if="errors.referral_name" class="error-message">{{ errors.referral_name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Kolom Kanan: Data Visit Pasien & Data Keluarga Terdekat Pasien -->
            <div class="edit-form-col right-col">
              <!-- Bagian 1: Data Visit Pasien -->
              <h3 class="category-title">Data Visit Pasien</h3>
              <div class="category-box mb-4">
                <!-- Restriction Warning Alert -->
                <div v-if="!canChangeClinic" class="clinic-restriction-banner" role="alert">
                  <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                  <div class="restriction-body">
                    <strong class="restriction-title">Pembatasan Perubahan Pelayanan:</strong>
                    <span class="restriction-desc">{{ cannotChangeReason }}</span>
                  </div>
                </div>

                <!-- 1. Jenis Visit -->
                <div class="h-form-row" :class="{ 'has-error': errors.visit_type }">
                  <label class="h-label" for="edit-visit-type">
                    Jenis Visit <span class="required-mark">*</span>
                  </label>
                  <div class="h-control">
                    <div class="select-wrap">
                      <select id="edit-visit-type" v-model="form.visit_type" class="form-select custom-select">
                        <option value="IGD">IGD</option>
                        <option value="Rawat Jalan">Rawat Jalan</option>
                        <option value="Rawat Inap">Rawat Inap</option>
                        <option value="Penunjang Medis">Penunjang Medis</option>
                      </select>
                      <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </div>
                    <span v-if="errors.visit_type" class="error-message">{{ errors.visit_type }}</span>
                  </div>
                </div>

                <!-- 2. Klinik -->
                <div class="h-form-row" :class="{ 'has-error': errors.polyclinic }">
                  <label class="h-label" for="edit-clinic">
                    Klinik <span class="required-mark">*</span>
                  </label>
                  <div class="h-control">
                    <div class="select-wrap" :class="{ 'is-disabled': !canChangeClinic }">
                      <select
                        id="edit-clinic"
                        v-model="form.polyclinic"
                        class="form-select custom-select"
                        :disabled="!canChangeClinic"
                      >
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

                <!-- 3. Jenis Layanan -->
                <div class="h-form-row">
                  <label class="h-label" for="edit-service-type">
                    Jenis Layanan
                  </label>
                  <div class="h-control">
                    <div class="select-wrap">
                      <select id="edit-service-type" v-model="form.service_type" class="form-select custom-select">
                        <option value="Umum">Umum</option>
                        <option value="Spesialis">Spesialis</option>
                        <option value="Sub-Spesialis">Sub-Spesialis</option>
                        <option value="Gawat Darurat">Gawat Darurat</option>
                      </select>
                      <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </div>
                  </div>
                </div>

                <!-- 4. Kasus -->
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
                        <option value="Akut">Akut</option>
                        <option value="Kronis">Kronis</option>
                        <option value="Riwayat Penyakit Kronis atau Akut">Riwayat Penyakit Kronis atau Akut</option>
                        <option value="Perawatan Lanjutan">Perawatan Lanjutan</option>
                      </select>
                      <svg class="select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </div>
                    <span v-if="errors.case_type" class="error-message">{{ errors.case_type }}</span>
                  </div>
                </div>

                <!-- 5. Dokter Klinik -->
                <div class="h-form-row" :class="{ 'has-error': errors.doctor }">
                  <label class="h-label" for="edit-doctor">
                    Dokter Klinik <span class="required-mark">*</span>
                  </label>
                  <div class="h-control">
                    <div class="select-wrap" :class="{ 'is-disabled': isServiceLocked }">
                      <select
                        id="edit-doctor"
                        v-model="form.doctor"
                        class="form-select custom-select"
                        :disabled="isServiceLocked"
                      >
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

              <!-- Bagian 2: Data Keluarga Terdekat Pasien -->
              <h3 class="category-title">Data Keluarga Terdekat Pasien</h3>
              <div class="category-box">
                <!-- 1. Nama -->
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

                <!-- 2. Hubungan -->
                <div class="h-form-row">
                  <label class="h-label" for="edit-family-relationship">Hubungan</label>
                  <div class="h-control">
                    <input
                      id="edit-family-relationship"
                      v-model="form.family_relationship"
                      type="text"
                      class="form-input"
                      placeholder="IBU"
                      autocomplete="off"
                    />
                  </div>
                </div>

                <!-- 3. No. HP -->
                <div class="h-form-row">
                  <label class="h-label" for="edit-family-phone">No. HP</label>
                  <div class="h-control">
                    <input
                      id="edit-family-phone"
                      v-model="form.family_phone"
                      type="tel"
                      class="form-input"
                      placeholder="089787656782"
                      autocomplete="off"
                    />
                  </div>
                </div>

                <!-- 4. Alamat -->
                <div class="h-form-row">
                  <label class="h-label" for="edit-family-address">Alamat</label>
                  <div class="h-control">
                    <input
                      id="edit-family-address"
                      v-model="form.family_address"
                      type="text"
                      class="form-input"
                      placeholder="PURWOREJO"
                      autocomplete="off"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer Action Bar -->
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

.page-content {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Card Common */
.card {
  background-color: #ffffff;
  border: 1px solid #d2d6de;
  border-radius: 4px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.card-header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1.25rem;
  background-color: #ffffff;
  border-bottom: 1px solid #f4f4f4;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Card 1: DATA PASIEN */
.header-icon-user {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3c8dbc;
  width: 20px;
  height: 20px;
}

.header-icon-user svg {
  width: 18px;
  height: 18px;
}

.patient-card-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #3c8dbc;
  letter-spacing: 0.02em;
}

.queue-badge-chip {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  font-size: 0.8rem;
  font-weight: 700;
  background-color: #f4f6f9;
  color: #555555;
  border: 1px solid #d2d6de;
  border-radius: 3px;
}

.patient-summary-body {
  padding: 1rem 1.25rem;
}

.patient-kv-list {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.kv-row {
  display: flex;
  align-items: baseline;
  font-size: 0.875rem;
}

.kv-label {
  width: 70px;
  color: #333333;
  font-weight: 700;
}

.kv-colon {
  width: 16px;
  color: #555555;
  font-weight: 700;
}

.kv-value {
  color: #333333;
  flex: 1;
}

.font-bold {
  font-weight: 700;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

/* Card 2: FORM EDIT */
.form-edit-header {
  border-bottom: 1px solid #f4f4f4;
  padding: 0.75rem 1.25rem;
}

.header-icon-box-layers {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3c8dbc;
  width: 20px;
  height: 20px;
}

.header-icon-box-layers svg {
  width: 18px;
  height: 18px;
  color: #3c8dbc;
}

.form-edit-title {
  color: #3c8dbc;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  margin: 0;
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
  padding: 4px;
  cursor: pointer;
  border-radius: 3px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.15s, background-color 0.15s;
}

.btn-card-tool:hover {
  color: #333333;
  background-color: #f4f6f9;
}

.btn-card-tool svg {
  width: 14px;
  height: 14px;
}

.edit-admission-form {
  padding: 1.25rem;
}

/* 2-Column Grid Layout */
.edit-form-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  align-items: start;
}

@media (max-width: 960px) {
  .edit-form-columns {
    grid-template-columns: 1fr;
  }
}

.edit-form-col {
  display: flex;
  flex-direction: column;
}

.category-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: #333333;
  margin: 0 0 0.5rem 0.15rem;
}

.category-box {
  background-color: #ffffff;
  border: 1px solid #d2d6de;
  border-radius: 4px;
  padding: 1.25rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.mb-4 {
  margin-bottom: 1.25rem;
}

/* Restriction Warning Alert */
.clinic-restriction-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  background-color: #fffbeb;
  border: 1px solid #fde68a;
  border-left: 4px solid #f59e0b;
  border-radius: 4px;
  padding: 0.65rem 0.85rem;
  margin-bottom: 0.5rem;
  color: #92400e;
  font-size: 0.82rem;
}

.clinic-restriction-banner .alert-icon {
  width: 1.15rem;
  height: 1.15rem;
  flex-shrink: 0;
  stroke: #d97706;
  margin-top: 0.1rem;
}

.clinic-restriction-banner .restriction-body {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.clinic-restriction-banner .restriction-title {
  font-weight: 600;
  color: #78350f;
}

.clinic-restriction-banner .restriction-desc {
  color: #92400e;
  line-height: 1.35;
}

/* Horizontal Form Rows */
.h-form-row {
  display: grid;
  grid-template-columns: 140px 1fr;
  align-items: center;
  gap: 0.75rem;
}

@media (max-width: 640px) {
  .h-form-row {
    grid-template-columns: 1fr;
    gap: 0.25rem;
  }
}

.guarantor-helper-muted {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.78rem;
  color: var(--color-text-secondary);
}

.btn-retry-guarantor {
  margin-left: 0.45rem;
  border: 1px solid currentColor;
  background: transparent;
  color: inherit;
  border-radius: 4px;
  padding: 0.1rem 0.45rem;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
}

.h-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #333333;
  text-align: right;
  padding-right: 0.25rem;
  white-space: nowrap;
}

@media (max-width: 640px) {
  .h-label {
    text-align: left;
    padding-right: 0;
  }
}

.required-mark {
  color: #e74c3c;
  font-weight: bold;
}

.h-control {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  width: 100%;
}

.form-input,
.form-select {
  width: 100%;
  height: 34px;
  padding: 0 0.65rem;
  font-size: 0.85rem;
  color: #333333;
  background-color: #ffffff;
  border: 1px solid #d2d6de;
  border-radius: 2px;
  transition: border-color 0.15s;
  box-sizing: border-box;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #3c8dbc;
}

.form-input:disabled,
.form-select:disabled,
.select-wrap.is-disabled {
  background-color: #eef2f5 !important;
  color: #777777 !important;
  cursor: not-allowed;
  border-color: #d2d6de !important;
}

.form-input::placeholder {
  color: #8c9ca8;
}

.select-wrap {
  position: relative;
  width: 100%;
}

.select-wrap .custom-select {
  appearance: none;
  padding-right: 1.75rem;
}

.select-chevron {
  position: absolute;
  right: 0.65rem;
  top: 50%;
  transform: translateY(-50%);
  width: 0.85rem;
  height: 0.85rem;
  color: #64748b;
  pointer-events: none;
}

.has-error .form-input,
.has-error .form-select {
  border-color: #e74c3c;
}

.error-message {
  font-size: 0.74rem;
  color: #e74c3c;
}

/* Footer Action Bar */
.form-footer-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1.25rem;
  margin-top: 1.5rem;
  border-top: 1px solid #f4f4f4;
}

.btn-batal {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background-color: #ffffff;
  border: 1px solid #d2d6de;
  color: #333333;
  padding: 0.45rem 1rem;
  font-size: 0.85rem;
  font-weight: 500;
  border-radius: 3px;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
}

.btn-batal:hover {
  background-color: #e6e6e6;
  border-color: #adadad;
}

.btn-batal svg {
  width: 13px;
  height: 13px;
}

.btn-update {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background-color: #f39c12;
  border: 1px solid #e08e0b;
  color: #ffffff;
  padding: 0.45rem 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 3px;
  cursor: pointer;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
  transition: all 0.15s ease-in-out;
}

.btn-update:hover:not(:disabled) {
  background-color: #e08e0b;
  border-color: #d58512;
}

.btn-update:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-update svg {
  width: 14px;
  height: 14px;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.9rem;
  font-size: 0.84rem;
  font-weight: 600;
  background-color: #ffffff;
  border: 1px solid #d2d6de;
  border-radius: 4px;
  color: #475569;
  cursor: pointer;
  transition: background-color 0.15s, border-color 0.15s, color 0.15s;
}

.btn-secondary:hover {
  background-color: #f8fafc;
  border-color: #cbd5e1;
  color: #1e293b;
}

.btn-secondary svg {
  width: 1rem;
  height: 1rem;
}
</style>
