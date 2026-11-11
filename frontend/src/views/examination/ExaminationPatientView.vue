<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import AppToast from '@/components/common/AppToast.vue';
import { useExaminations } from '@/composables/useExaminations';

import './components/patient/patientWorkspace.css';
import ExaminationTabHistory from './components/patient/ExaminationTabHistory.vue';
import ExaminationTabAllergy from './components/patient/ExaminationTabAllergy.vue';
import ExaminationTabClinical from './components/patient/ExaminationTabClinical.vue';
import ExaminationTabDiagnosis from './components/patient/ExaminationTabDiagnosis.vue';
import ExaminationTabProcedures from './components/patient/ExaminationTabProcedures.vue';
import ExaminationTabOrderResep from './components/patient/ExaminationTabOrderResep.vue';
import ExaminationTabOrderLab from './components/patient/ExaminationTabOrderLab.vue';
import ExaminationTabOrderRadiologi from './components/patient/ExaminationTabOrderRadiologi.vue';
import ExaminationTabOperasiSchedule from './components/patient/ExaminationTabOperasiSchedule.vue';
import ExaminationTabOperasiLaporan from './components/patient/ExaminationTabOperasiLaporan.vue';
import ExaminationTabResume from './components/patient/ExaminationTabResume.vue';
import ExaminationTabBilling from './components/patient/ExaminationTabBilling.vue';

const route = useRoute();
const router = useRouter();

const {
  examinations,
  mockDoctors,
  mockMidwives,
  mockBidanPhysicalHistory,
  mockIcd10Catalog,
  mockIcd9Catalog,
  mockClinicalProcedures,
  mockProcedurePackages,
  mockParamedics,
  mockMedicinesCatalog,
  mockPrescriptionPackages,
  mockPrescriptionHistory,
  mockLaboratoryCatalog,
  mockLabDpjpDoctors,
  mockLabClinicalStaff,
  mockLabHistory,
  mockRadiologyCatalog,
  mockRadiologyHistory,
  mockVkSurgeryOptions,
  mockIbsSurgeryOptions,
  getExaminationById,
  saveSessionExaminationData,
  completeExamination
} = useExaminations();

const activeTab = ref('riwayat');
const activeSubTabs = ref({
  pemeriksaan: 'dokter',
  order: 'pharmacy',
  operasi: 'vk'
});
const openDropdown = ref(null);
const tabsNavRef = ref(null);

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

const patientSummary = ref({
  id: 'EXM-0001',
  queue_number: '-',
  medical_record_number: '-',
  patient_name: '-',
  gender: 'L',
  birth_place: 'Yogyakarta',
  birth_date: '',
  address: '-',
  guarantor_type: 'UMUM',
  polyclinic: '-',
  doctor: '-',
  status: 'Sedang Diperiksa',
  admission_date: new Date().toISOString().split('T')[0],
  initial_diagnosis: '-',
  visit_history: []
});

const allergyNote = ref('');
const hasAllergy = computed(() => {
  const clean = allergyNote.value.trim().toLowerCase();
  return clean !== '' && clean !== '-' && clean !== 'tidak ada';
});
const diagnoses = ref([]);
const icd9List = ref([]);
const diagnosisForm = ref({
  working_diagnosis: '',
  final_diagnosis: '',
  procedure_narrative: ''
});
const procedures = ref([]);
const orders = ref({
  pharmacy: [],
  racikan: [],
  lab: [],
  radiology: [],
  consult: []
});
const surgeries = ref({
  requests: [],
  report: {
    pre_op_diagnosis: '',
    post_op_diagnosis: '',
    findings: ''
  }
});

const clinicalForm = ref({
  vitals: {
    systole: '120',
    diastole: '80',
    blood_pressure: '120/80',
    heart_rate: '82',
    temperature: '36.6',
    respiratory_rate: '18',
    spo2: '98',
    weight: '62',
    height: '168',
    waist_circumference: '',
    gcs: 'E4V5M6 (Compos Mentis)'
  },
  dokter: {
    date: new Date().toISOString().split('T')[0],
    time: new Date().toTimeString().slice(0, 5),
    chief_complaint: '',
    ppa_instruction: '',
    dpjp: ''
  },
  soap: {
    subjective: '',
    objective: '',
    assessment: '',
    plan: ''
  },
  physical: {
    general: 'Baik, tampak sakit ringan',
    head_neck: 'Normocephal, konjungtiva anemis (-), sklera ikterik (-)',
    thorax: 'Cor S1S2 reguler, Pulmo vesikuler (+/+), rhonki (-/-)',
    abdomen: 'Supel, bising usus (+) normal, nyeri tekan (-)',
    extremities: 'Akral hangat, CRT < 2 detik, edema (-)',
    localis: '-'
  },
  bidan: {
    date: new Date().toISOString().split('T')[0],
    time: new Date().toTimeString().slice(0, 5),
    chief_complaint: '',
    info_auto: false,
    info_allo: false,
    info_name: '',
    info_relation: '',
    menarche: '',
    cycle: '',
    cycle_regular: false,
    cycle_irregular: false,
    duration: '',
    volume: '',
    menstrual_complaint_none: false,
    menstrual_complaint_yes: false,
    menstrual_complaint_note: '',
    married_yes: false,
    married_no: false,
    marriage_rows: [],
    early_nausea: false,
    early_vomit: false,
    early_bleeding: false,
    early_other: false,
    early_note: '',
    late_dizzy: false,
    late_headache: false,
    late_bleeding: false,
    late_other: false,
    late_note: '',
    g: '',
    p: '',
    a: '',
    hidup: '',
    past_pregnancies: [],
    kb_none: false,
    kb_yes: false,
    kb_method: '',
    kb_duration: '',
    kb_duration_unit: 'Hari',
    gynecology_none: false,
    gynecology_yes: false,
    gynecology_note: '',
    currently_pregnant: false,
    currently_not_pregnant: false,
    hpht: new Date().toISOString().split('T')[0],
    hpl: '',
    pregnancy_order: '',
    anc_none: false,
    anc_yes: false,
    anc_obgyn: false,
    anc_gp: false,
    anc_midwife: false,
    gestational_weeks: '',
    gestational_days: '',
    tfu: '',
    tbj: '',
    djj: '',
    djj_rhythm: 'Teratur',
    his: '',
    lie_longitudinal: false,
    lie_transverse: false,
    lie_oblique: false,
    pres_head: false,
    pres_breech: false,
    pres_other: false,
    pres_note: '',
    pos_puki: false,
    pos_puka: false,
    scar_none: false,
    scar_yes: false,
    scar_note: '',
    dra: '',
    vaginal_exam: '',
    vaginal_discharge: '',
    bleeding_amount: '',
    perineum_intact: false,
    perineum_rupture: false,
    perineum_hecting: false,
    midwife: ''
  },
  nursing: {
    chief_complaint: '',
    smoking: 'Tidak',
    general_condition: 'Compos mentis',
    physical_exam_note: '',
    advice: '',
    external_file_name: ''
  },
  hais: {
    logs: [],
    modules: {
      infus: [],
      kateter: [],
      tirah_baring: [],
      cvp: [],
      ett: [],
      operasi: []
    }
  },
  resume: {
    disposition: '',
    doctor: '',
    paramedic: '',
    next_control_date: '',
    discharge_advice: ''
  }
});

const formatDateDMY = (isoDate) => {
  if (!isoDate) return '-';
  const parts = String(isoDate).split('-');
  if (parts.length !== 3) return isoDate;
  return `${parts[2]}-${parts[1]}-${parts[0]}`;
};

const formatGuarantorLabel = (val) => {
  if (!val) return 'UMUM';
  const lower = String(val).toLowerCase();
  if (lower.includes('umum') || lower.includes('tunai') || lower.includes('mandiri')) {
    return 'UMUM';
  }
  return val;
};

const loadPatientData = () => {
  const record = getExaminationById(route.params.id) || examinations.value[0];
  if (!record) return;

  patientSummary.value = {
    id: record.id,
    queue_number: record.queue_number || '-',
    medical_record_number: record.medical_record_number || '-',
    patient_name: record.patient_name || '-',
    gender: record.gender || 'L',
    birth_place: record.birth_place || 'Yogyakarta',
    birth_date: record.birth_date || '',
    address: record.address || '-',
    guarantor_type: record.guarantor_type || 'UMUM',
    polyclinic: record.polyclinic || '-',
    doctor: record.doctor || '-',
    status: record.status || 'Sedang Diperiksa',
    admission_date: record.admission_date || new Date().toISOString().split('T')[0],
    blood_type: record.blood_type || 'O',
    initial_diagnosis: record.initial_diagnosis || '-',
    visit_history: Array.isArray(record.visit_history) ? record.visit_history : []
  };

  if (typeof record.allergy_note === 'string') {
    allergyNote.value = record.allergy_note;
  } else if (Array.isArray(record.allergies) && record.allergies.length > 0) {
    allergyNote.value = record.allergies
      .map((a) => `${a.substance}${a.reaction && a.reaction !== '-' ? ` (${a.reaction})` : ''}`)
      .join(', ');
  } else {
    allergyNote.value = '';
  }

  diagnoses.value = Array.isArray(record.diagnoses) ? [...record.diagnoses] : [];
  icd9List.value = Array.isArray(record.icd9List) ? [...record.icd9List] : [];
  diagnosisForm.value = {
    working_diagnosis: record.diagnosis_form?.working_diagnosis || '',
    final_diagnosis: record.diagnosis_form?.final_diagnosis || '',
    procedure_narrative: record.diagnosis_form?.procedure_narrative || ''
  };
  procedures.value = Array.isArray(record.procedures) ? [...record.procedures] : [];

  orders.value = {
    pharmacy: Array.isArray(record.orders?.pharmacy) ? [...record.orders.pharmacy] : [],
    racikan: Array.isArray(record.orders?.racikan) ? [...record.orders.racikan] : [],
    lab: Array.isArray(record.orders?.lab) ? [...record.orders.lab] : [],
    radiology: Array.isArray(record.orders?.radiology) ? [...record.orders.radiology] : [],
    consult: Array.isArray(record.orders?.consult) ? [...record.orders.consult] : []
  };

  surgeries.value = {
    requests: Array.isArray(record.surgeries?.requests) ? [...record.surgeries.requests] : [],
    report: {
      pre_op_diagnosis: record.surgeries?.report?.pre_op_diagnosis || '',
      post_op_diagnosis: record.surgeries?.report?.post_op_diagnosis || '',
      findings: record.surgeries?.report?.findings || ''
    }
  };

  clinicalForm.value.soap = {
    subjective: record.soap?.subjective || record.clinical_note || '',
    objective: record.soap?.objective || '',
    assessment: record.soap?.assessment || '',
    plan: record.soap?.plan || ''
  };
  if (record.vitals) {
    clinicalForm.value.vitals = { ...clinicalForm.value.vitals, ...record.vitals };
  }
  const bpParts = String(clinicalForm.value.vitals.blood_pressure || '').split('/');
  if (bpParts.length === 2 && !record.vitals?.systole) {
    clinicalForm.value.vitals.systole = bpParts[0].trim();
    clinicalForm.value.vitals.diastole = bpParts[1].trim();
  }
  if (record.dokter) {
    clinicalForm.value.dokter = { ...clinicalForm.value.dokter, ...record.dokter };
  }
  if (!clinicalForm.value.dokter.chief_complaint && record.clinical_note) {
    clinicalForm.value.dokter.chief_complaint = record.clinical_note;
  }
  if (!clinicalForm.value.dokter.dpjp && record.doctor && record.doctor !== '-') {
    clinicalForm.value.dokter.dpjp = record.doctor;
  }
  if (record.physical) {
    clinicalForm.value.physical = { ...clinicalForm.value.physical, ...record.physical };
  }
  if (record.bidan) {
    clinicalForm.value.bidan = {
      ...clinicalForm.value.bidan,
      ...record.bidan,
      marriage_rows: Array.isArray(record.bidan.marriage_rows) ? [...record.bidan.marriage_rows] : [],
      past_pregnancies: Array.isArray(record.bidan.past_pregnancies) ? [...record.bidan.past_pregnancies] : []
    };
  }
  if (record.nursing) {
    clinicalForm.value.nursing = { ...clinicalForm.value.nursing, ...record.nursing };
  }
  if (record.hais) {
    clinicalForm.value.hais = {
      logs: Array.isArray(record.hais.logs) ? [...record.hais.logs] : [],
      modules: {
        infus: Array.isArray(record.hais.modules?.infus) ? [...record.hais.modules.infus] : [],
        kateter: Array.isArray(record.hais.modules?.kateter) ? [...record.hais.modules.kateter] : [],
        tirah_baring: Array.isArray(record.hais.modules?.tirah_baring) ? [...record.hais.modules.tirah_baring] : [],
        cvp: Array.isArray(record.hais.modules?.cvp) ? [...record.hais.modules.cvp] : [],
        ett: Array.isArray(record.hais.modules?.ett) ? [...record.hais.modules.ett] : [],
        operasi: Array.isArray(record.hais.modules?.operasi) ? [...record.hais.modules.operasi] : []
      }
    };
  }
  if (record.resume) {
    clinicalForm.value.resume = { ...clinicalForm.value.resume, ...record.resume };
  }
  if (!clinicalForm.value.resume.doctor && record.doctor && record.doctor !== '-') {
    clinicalForm.value.resume.doctor = record.doctor;
  }
};

loadPatientData();

watch(() => route.params.id, () => {
  activeTab.value = 'riwayat';
  openDropdown.value = null;
  loadPatientData();
});

const handleDocumentClick = (e) => {
  if (tabsNavRef.value && !tabsNavRef.value.contains(e.target)) {
    openDropdown.value = null;
  }
};

const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    openDropdown.value = null;
  }
};

onMounted(() => {
  document.addEventListener('click', handleDocumentClick);
  document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick);
  document.removeEventListener('keydown', handleKeydown);
});

const tabMenus = {
  pemeriksaan: [
    { key: 'bidan', label: 'ASESMEN AWAL BIDAN' },
    { key: 'dokter', label: 'ASESMEN AWAL DOKTER' },
    { key: 'perawat', label: 'ASESMEN AWAL PERAWAT' },
    { key: 'hais', label: 'MONITOR HAIS' }
  ],
  order: [
    { key: 'pharmacy', label: 'RESEP' },
    { key: 'lab', label: 'LABORATORIUM' },
    { key: 'radiology', label: 'RADIOLOGI' }
  ],
  operasi: [
    { key: 'vk', label: 'VK' },
    { key: 'ibs', label: 'IBS' },
    { key: 'surgery_report', label: 'LAPORAN OPERASI' }
  ]
};

const selectTab = (tabKey) => {
  activeTab.value = tabKey;
  openDropdown.value = null;
};

const toggleDropdown = (groupKey) => {
  openDropdown.value = openDropdown.value === groupKey ? null : groupKey;
};

const selectSubTab = (groupKey, subKey) => {
  activeTab.value = groupKey;
  activeSubTabs.value = {
    ...activeSubTabs.value,
    [groupKey]: subKey
  };
  openDropdown.value = null;
};

const syncSession = (partial) => {
  saveSessionExaminationData(patientSummary.value.id, partial);
};

const handleSaveAllergy = (note) => {
  allergyNote.value = note;
  syncSession({ allergy_note: note });
  showToast({
    type: 'success',
    title: 'Data Alergi Disimpan',
    message: note
      ? `Catatan alergi pasien ${patientSummary.value.patient_name} berhasil diperbarui.`
      : `Catatan alergi pasien ${patientSummary.value.patient_name} telah dikosongkan.`
  });
};

const handleSaveClinical = (sectionLabel) => {
  if (clinicalForm.value.dokter.dpjp) {
    patientSummary.value.doctor = clinicalForm.value.dokter.dpjp;
    clinicalForm.value.resume.doctor = clinicalForm.value.dokter.dpjp;
  }
  if (sectionLabel === 'Asesmen Awal Perawat' && clinicalForm.value.nursing.chief_complaint) {
    clinicalForm.value.dokter.chief_complaint = clinicalForm.value.nursing.chief_complaint;
  }
  syncSession({
    doctor: patientSummary.value.doctor,
    blood_type: patientSummary.value.blood_type,
    vitals: { ...clinicalForm.value.vitals },
    dokter: { ...clinicalForm.value.dokter },
    soap: { ...clinicalForm.value.soap },
    physical: { ...clinicalForm.value.physical },
    bidan: {
      ...clinicalForm.value.bidan,
      marriage_rows: [...clinicalForm.value.bidan.marriage_rows],
      past_pregnancies: [...clinicalForm.value.bidan.past_pregnancies]
    },
    nursing: { ...clinicalForm.value.nursing },
    hais: { logs: [...clinicalForm.value.hais.logs] }
  });
  showToast({
    type: 'success',
    title: 'Catatan Klinis Disimpan',
    message: `Data ${sectionLabel} pasien ${patientSummary.value.patient_name} berhasil diperbarui.`
  });
};

const handleAddDiagnosis = (entry) => {
  diagnoses.value = [...diagnoses.value, entry];
  syncSession({ diagnoses: diagnoses.value });
  showToast({ type: 'success', title: 'Diagnosis Ditambahkan', message: `${entry.code} - ${entry.name}` });
};

const handleRemoveDiagnosis = (id) => {
  diagnoses.value = diagnoses.value.filter((d) => d.id !== id);
  syncSession({ diagnoses: diagnoses.value });
};

const handleAddIcd9 = (entry) => {
  icd9List.value = [...icd9List.value, entry];
  syncSession({ icd9List: icd9List.value });
  showToast({ type: 'success', title: 'ICD-9 Ditambahkan', message: `${entry.code} - ${entry.name}` });
};

const handleRemoveIcd9 = (id) => {
  icd9List.value = icd9List.value.filter((p) => p.id !== id);
  syncSession({ icd9List: icd9List.value });
};

const handleSaveDiagnosis = () => {
  syncSession({
    diagnosis_form: { ...diagnosisForm.value },
    diagnoses: diagnoses.value.map((d) => ({ ...d })),
    icd9List: icd9List.value.map((p) => ({ ...p }))
  });
  showToast({
    type: 'success',
    title: 'Data Diagnosis Disimpan',
    message: `Diagnosis & pengkodean ICD pasien ${patientSummary.value.patient_name} berhasil diperbarui.`
  });
};

const handleAddProcedure = (entry) => {
  procedures.value = [...procedures.value, entry];
  syncSession({ procedures: procedures.value });
  showToast({ type: 'success', title: 'Tindakan Ditambahkan', message: `${entry.name}` });
};

const handleRemoveProcedure = (id) => {
  procedures.value = procedures.value.filter((p) => p.id !== id);
  syncSession({ procedures: procedures.value });
};

const handleSaveProcedures = () => {
  syncSession({
    procedures: procedures.value.map((p) => ({ ...p }))
  });
  showToast({
    type: 'success',
    title: 'Data Tindakan Disimpan',
    message: `Daftar tindakan medis pasien ${patientSummary.value.patient_name} berhasil diperbarui.`
  });
};

const handleAddOrder = ({ bucket, entry }) => {
  if (bucket === 'surgery_requests') {
    surgeries.value = {
      ...surgeries.value,
      requests: [...surgeries.value.requests, entry]
    };
    syncSession({ surgeries: surgeries.value });
  } else {
    orders.value = {
      ...orders.value,
      [bucket]: [...(orders.value[bucket] || []), entry]
    };
    syncSession({ orders: orders.value });
  }
  showToast({ type: 'success', title: 'Permintaan Disimpan', message: 'Order baru telah tercatat pada sesi kunjungan.' });
};

const handleRemoveOrder = ({ bucket, id }) => {
  if (bucket === 'surgery_requests') {
    surgeries.value = {
      ...surgeries.value,
      requests: surgeries.value.requests.filter((i) => i.id !== id)
    };
    syncSession({ surgeries: surgeries.value });
  } else {
    orders.value = {
      ...orders.value,
      [bucket]: (orders.value[bucket] || []).filter((i) => i.id !== id)
    };
    syncSession({ orders: orders.value });
  }
};

const handleSaveResep = () => {
  syncSession({
    orders: {
      ...orders.value,
      pharmacy: (orders.value.pharmacy || []).map((r) => ({ ...r })),
      racikan: (orders.value.racikan || []).map((r) => ({ ...r }))
    }
  });
  showToast({
    type: 'success',
    title: 'Data Resep Disimpan',
    message: `Order resep dan racikan pasien ${patientSummary.value.patient_name} berhasil diperbarui.`
  });
};

const handleSaveLabOrder = ({ entries }) => {
  orders.value = {
    ...orders.value,
    lab: entries.map((e) => ({ ...e }))
  };
  syncSession({ orders: orders.value });
  showToast({
    type: 'success',
    title: 'Data Order Laboratorium Disimpan',
    message: `${entries.length} pemeriksaan laboratorium pasien ${patientSummary.value.patient_name} berhasil disimpan.`
  });
};

const handleSaveRadiologyOrder = ({ section, entries }) => {
  const otherSectionEntries = (orders.value.radiology || []).filter(
    (item) => (item.section || 'umum') !== section
  );
  orders.value = {
    ...orders.value,
    radiology: [...otherSectionEntries, ...entries.map((e) => ({ ...e }))]
  };
  syncSession({ orders: orders.value });
  const label = section === 'koas' ? 'Radiologi Koas' : 'Radiologi Umum/Residen';
  showToast({
    type: 'success',
    title: 'Data Order Radiologi Disimpan',
    message: `${entries.length} pemeriksaan ${label} pasien ${patientSummary.value.patient_name} berhasil disimpan.`
  });
};

const handleSaveSurgeryReport = () => {
  syncSession({ surgeries: surgeries.value });
  showToast({ type: 'success', title: 'Laporan Operasi Disimpan', message: 'Catatan tindakan operasi berhasil diperbarui.' });
};

const handlePrintResume = () => {
  showToast({
    type: 'info',
    title: 'Cetak Resume Medis',
    message: `Menyiapkan dokumen cetak resume medis pasien ${patientSummary.value.patient_name}.`
  });
};

const handleCompleteExam = () => {
  syncSession({ resume: { ...clinicalForm.value.resume } });
  completeExamination(patientSummary.value.id);
  patientSummary.value.status = 'Selesai';
  showToast({
    type: 'success',
    title: 'Pemeriksaan Selesai',
    message: `Status pemeriksaan ${patientSummary.value.patient_name} diperbarui menjadi Selesai.`
  });
};

const statusBadgeClass = computed(() => {
  if (patientSummary.value.status === 'Selesai') return 'status-pill--done';
  if (patientSummary.value.status === 'Dibatalkan') return 'status-pill--cancel';
  return 'status-pill--active';
});

const goBack = () => {
  router.push('/pemeriksaan');
};
</script>

<template>
  <div class="page-container exam-patient-page">
    <!-- Toast Notification -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Standardized Page Header -->
    <MasterDataPageHeader
      title="Pemeriksaan Pasien"
      subtitle="Ringkasan identitas pasien, riwayat kunjungan, dan catatan pemeriksaan klinis terintegrasi."
      :breadcrumbs="[
        { label: 'Home', active: false },
        { label: 'Pemeriksaan', active: false },
        { label: 'Pemeriksaan Pasien', active: true }
      ]"
      badge="Pemeriksaan"
    >
      <template #actions>
        <button
          type="button"
          class="btn-secondary btn-header-back"
          @click="goBack"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
          <span>Kembali ke Pemeriksaan</span>
        </button>
      </template>
    </MasterDataPageHeader>

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
              <p class="card-subtitle">{{ patientSummary.polyclinic }} &bull; {{ patientSummary.doctor }}</p>
            </div>
          </div>

          <div class="header-chips">
            <span class="queue-badge-chip font-mono">No. Antrean: {{ patientSummary.queue_number }}</span>
            <span class="exam-status-pill" :class="statusBadgeClass">{{ patientSummary.status }}</span>
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
              <span class="kv-value">
                <strong>{{ patientSummary.patient_name }}</strong>
                <span class="gender-tag"> ({{ patientSummary.gender }})</span>
              </span>
            </div>
            <div class="kv-row">
              <span class="kv-label">TTL</span>
              <span class="kv-colon">:</span>
              <span class="kv-value">{{ patientSummary.birth_place }}, {{ formatDateDMY(patientSummary.birth_date) }}</span>
            </div>
            <div class="kv-row">
              <span class="kv-label">Alamat</span>
              <span class="kv-colon">:</span>
              <span class="kv-value">{{ patientSummary.address }}</span>
            </div>
            <div class="kv-row">
              <span class="kv-label">Jaminan</span>
              <span class="kv-colon">:</span>
              <span class="kv-value">
                <span class="guarantor-tag">{{ formatGuarantorLabel(patientSummary.guarantor_type) }}</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 2: Tabbed Clinical Workspace -->
      <div class="card tabs-workspace-card">
        <div ref="tabsNavRef" class="clinical-tabs-bar" role="tablist" aria-label="Menu Pemeriksaan Pasien">
          <!-- Tab 1: RIWAYAT -->
          <button
            type="button"
            role="tab"
            class="tab-btn"
            :class="{ 'is-active': activeTab === 'riwayat' }"
            :aria-selected="activeTab === 'riwayat'"
            @click="selectTab('riwayat')"
          >
            RIWAYAT
          </button>

          <!-- Tab 2: ALERGI (Red Alert Styling) -->
          <button
            type="button"
            role="tab"
            class="tab-btn tab-btn--allergy"
            :class="{ 'is-active': activeTab === 'alergi' }"
            :aria-selected="activeTab === 'alergi'"
            @click="selectTab('alergi')"
          >
            <span>ALERGI</span>
            <span v-if="hasAllergy" class="allergy-badge" title="Pasien memiliki catatan alergi">!</span>
          </button>

          <!-- Tab 3: PEMERIKSAAN (Dropdown) -->
          <div class="tab-dropdown-wrap">
            <button
              type="button"
              role="tab"
              class="tab-btn tab-btn--dropdown"
              :class="{ 'is-active': activeTab === 'pemeriksaan', 'is-open': openDropdown === 'pemeriksaan' }"
              :aria-expanded="openDropdown === 'pemeriksaan'"
              @click.stop="toggleDropdown('pemeriksaan')"
            >
              <span>PEMERIKSAAN</span>
              <svg class="tab-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>

            <div v-if="openDropdown === 'pemeriksaan'" class="tab-dropdown-menu" role="menu">
              <button
                v-for="sub in tabMenus.pemeriksaan"
                :key="sub.key"
                type="button"
                role="menuitem"
                class="tab-dropdown-item"
                :class="{ 'is-current': activeTab === 'pemeriksaan' && activeSubTabs.pemeriksaan === sub.key }"
                @click="selectSubTab('pemeriksaan', sub.key)"
              >
                {{ sub.label }}
              </button>
            </div>
          </div>

          <!-- Tab 4: DIAGNOSIS -->
          <button
            type="button"
            role="tab"
            class="tab-btn"
            :class="{ 'is-active': activeTab === 'diagnosis' }"
            :aria-selected="activeTab === 'diagnosis'"
            @click="selectTab('diagnosis')"
          >
            DIAGNOSIS
          </button>

          <!-- Tab 5: TINDAKAN -->
          <button
            type="button"
            role="tab"
            class="tab-btn"
            :class="{ 'is-active': activeTab === 'tindakan' }"
            :aria-selected="activeTab === 'tindakan'"
            @click="selectTab('tindakan')"
          >
            TINDAKAN
          </button>

          <!-- Tab 6: ORDER (Dropdown) -->
          <div class="tab-dropdown-wrap">
            <button
              type="button"
              role="tab"
              class="tab-btn tab-btn--dropdown"
              :class="{ 'is-active': activeTab === 'order', 'is-open': openDropdown === 'order' }"
              :aria-expanded="openDropdown === 'order'"
              @click.stop="toggleDropdown('order')"
            >
              <span>ORDER</span>
              <svg class="tab-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>

            <div v-if="openDropdown === 'order'" class="tab-dropdown-menu" role="menu">
              <button
                v-for="sub in tabMenus.order"
                :key="sub.key"
                type="button"
                role="menuitem"
                class="tab-dropdown-item"
                :class="{ 'is-current': activeTab === 'order' && activeSubTabs.order === sub.key }"
                @click="selectSubTab('order', sub.key)"
              >
                {{ sub.label }}
              </button>
            </div>
          </div>

          <!-- Tab 7: OPERASI (Dropdown) -->
          <div class="tab-dropdown-wrap">
            <button
              type="button"
              role="tab"
              class="tab-btn tab-btn--dropdown"
              :class="{ 'is-active': activeTab === 'operasi', 'is-open': openDropdown === 'operasi' }"
              :aria-expanded="openDropdown === 'operasi'"
              @click.stop="toggleDropdown('operasi')"
            >
              <span>OPERASI</span>
              <svg class="tab-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>

            <div v-if="openDropdown === 'operasi'" class="tab-dropdown-menu" role="menu">
              <button
                v-for="sub in tabMenus.operasi"
                :key="sub.key"
                type="button"
                role="menuitem"
                class="tab-dropdown-item"
                :class="{ 'is-current': activeTab === 'operasi' && activeSubTabs.operasi === sub.key }"
                @click="selectSubTab('operasi', sub.key)"
              >
                {{ sub.label }}
              </button>
            </div>
          </div>

          <!-- Tab 8: RESUME MEDIS -->
          <button
            type="button"
            role="tab"
            class="tab-btn"
            :class="{ 'is-active': activeTab === 'resume' }"
            :aria-selected="activeTab === 'resume'"
            @click="selectTab('resume')"
          >
            RESUME MEDIS
          </button>

          <!-- Tab 9: TAGIHAN -->
          <button
            type="button"
            role="tab"
            class="tab-btn"
            :class="{ 'is-active': activeTab === 'tagihan' }"
            :aria-selected="activeTab === 'tagihan'"
            @click="selectTab('tagihan')"
          >
            TAGIHAN
          </button>
        </div>

        <!-- Tab Content Body -->
        <div class="clinical-tabs-body">
          <ExaminationTabHistory
            v-if="activeTab === 'riwayat'"
            :visit-history="patientSummary.visit_history"
          />

          <ExaminationTabAllergy
            v-else-if="activeTab === 'alergi'"
            :allergy-note="allergyNote"
            @save-allergy="handleSaveAllergy"
          />

          <ExaminationTabClinical
            v-else-if="activeTab === 'pemeriksaan'"
            :sub-tab="activeSubTabs.pemeriksaan"
            :patient="patientSummary"
            :clinical-form="clinicalForm"
            :surgeries="surgeries"
            :default-unit="patientSummary.polyclinic"
            :default-doctor="patientSummary.doctor"
            :doctors="mockDoctors"
            :midwives="mockMidwives"
            :physical-history="mockBidanPhysicalHistory"
            @save-clinical="handleSaveClinical"
          />

          <ExaminationTabDiagnosis
            v-else-if="activeTab === 'diagnosis'"
            :initial-diagnosis="patientSummary.initial_diagnosis"
            :diagnosis-form="diagnosisForm"
            :diagnoses="diagnoses"
            :icd9-list="icd9List"
            :icd10-catalog="mockIcd10Catalog"
            :icd9-catalog="mockIcd9Catalog"
            @add-diagnosis="handleAddDiagnosis"
            @remove-diagnosis="handleRemoveDiagnosis"
            @add-icd9="handleAddIcd9"
            @remove-icd9="handleRemoveIcd9"
            @save-diagnosis="handleSaveDiagnosis"
          />

          <ExaminationTabProcedures
            v-else-if="activeTab === 'tindakan'"
            :procedures="procedures"
            :procedure-catalog="mockClinicalProcedures"
            :package-catalog="mockProcedurePackages"
            :doctors="mockDoctors"
            :paramedics="mockParamedics"
            :default-doctor="patientSummary.doctor"
            @add-procedure="handleAddProcedure"
            @remove-procedure="handleRemoveProcedure"
            @save-procedures="handleSaveProcedures"
          />

          <ExaminationTabOrderResep
            v-else-if="activeTab === 'order' && activeSubTabs.order === 'pharmacy'"
            :orders="orders"
            :medicines-catalog="mockMedicinesCatalog"
            :package-catalog="mockPrescriptionPackages"
            :prescription-history="mockPrescriptionHistory"
            @add-order="handleAddOrder"
            @remove-order="handleRemoveOrder"
            @save-resep="handleSaveResep"
          />

          <ExaminationTabOrderLab
            v-else-if="activeTab === 'order' && activeSubTabs.order === 'lab'"
            :orders="orders"
            :lab-catalog="mockLaboratoryCatalog"
            :doctors="mockDoctors"
            :lab-dpjp-doctors="mockLabDpjpDoctors"
            :lab-staff="mockLabClinicalStaff"
            :lab-history="mockLabHistory"
            :default-doctor="patientSummary.doctor"
            @save-lab-order="handleSaveLabOrder"
            @remove-order="handleRemoveOrder"
          />

          <ExaminationTabOrderRadiologi
            v-else-if="activeTab === 'order' && activeSubTabs.order === 'radiology'"
            :orders="orders"
            :radiology-catalog="mockRadiologyCatalog"
            :radiology-history="mockRadiologyHistory"
            @save-radiology-order="handleSaveRadiologyOrder"
            @remove-order="handleRemoveOrder"
          />

          <ExaminationTabOperasiSchedule
            v-else-if="activeTab === 'operasi' && (activeSubTabs.operasi === 'vk' || activeSubTabs.operasi === 'ibs')"
            :key="activeSubTabs.operasi"
            :unit="activeSubTabs.operasi === 'vk' ? 'VK' : 'IBS'"
            :title="activeSubTabs.operasi === 'vk' ? 'OPERASI VK' : 'IBS'"
            :id-prefix="activeSubTabs.operasi"
            :surgeries="surgeries"
            :doctors="mockDoctors"
            :schedule-options="activeSubTabs.operasi === 'vk' ? mockVkSurgeryOptions : mockIbsSurgeryOptions"
            :default-doctor="patientSummary.doctor"
            @add-order="handleAddOrder"
            @remove-order="handleRemoveOrder"
          />

          <ExaminationTabOperasiLaporan
            v-else-if="activeTab === 'operasi' && activeSubTabs.operasi === 'surgery_report'"
            :surgeries="surgeries"
            @save-surgery-report="handleSaveSurgeryReport"
          />

          <ExaminationTabResume
            v-else-if="activeTab === 'resume'"
            :patient="patientSummary"
            :allergy-note="allergyNote"
            :clinical-form="clinicalForm"
            :diagnosis-form="diagnosisForm"
            :diagnoses="diagnoses"
            :icd9-list="icd9List"
            :procedures="procedures"
            :orders="orders"
            :doctors="mockDoctors"
            :paramedics="mockParamedics"
            @print-resume="handlePrintResume"
            @complete-examination="handleCompleteExam"
          />

          <ExaminationTabBilling
            v-else
            :patient="patientSummary"
            :procedures="procedures"
            :orders="orders"
            @complete-examination="handleCompleteExam"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.exam-patient-page {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  font-size: 0.875rem;
  animation: fadeIn 0.25s ease-out;
}

.exam-patient-page :deep(button),
.exam-patient-page :deep(input),
.exam-patient-page :deep(select),
.exam-patient-page :deep(textarea) {
  font-family: inherit;
}

.exam-patient-page :deep(strong),
.exam-patient-page :deep(b) {
  font-weight: 700;
}

.exam-patient-page :deep(button > span:not(.allergy-badge)) {
  font-weight: inherit;
  font-size: inherit;
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

/* Card 1: DATA PASIEN */
.patient-info-card,
.tabs-workspace-card {
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.tabs-workspace-card {
  overflow: visible;
}

.patient-info-card {
  overflow: hidden;
}

.card-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.05rem 1.5rem;
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
  font-size: 0.84rem;
  color: var(--color-text-secondary);
}

.header-chips {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  flex-wrap: wrap;
}

.queue-badge-chip {
  display: inline-flex;
  align-items: center;
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.82rem;
  font-weight: 700;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
}

.exam-status-pill {
  display: inline-flex;
  align-items: center;
  font-size: 0.8rem;
  font-weight: 700;
  padding: 0.25rem 0.7rem;
  border-radius: 9999px;
}

.status-pill--active {
  background: #eff6ff;
  color: #1d4ed8;
}

.status-pill--done {
  background: #ecfdf5;
  color: #047857;
}

.status-pill--cancel {
  background: #fef2f2;
  color: #dc2626;
}

.patient-summary-body {
  padding: 1.15rem 1.5rem 1.25rem;
  background: #ffffff;
}

.patient-kv-list {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.kv-row {
  display: grid;
  grid-template-columns: 90px 14px 1fr;
  align-items: baseline;
  font-size: 0.875rem;
  line-height: 1.45;
}

.kv-label,
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
  padding: 0.08rem 0.48rem;
  border-radius: 5px;
}

.gender-tag {
  font-style: italic;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.guarantor-tag {
  font-weight: 700;
  color: var(--color-text-navy);
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-variant-numeric: tabular-nums;
}

/* Card 2: Tabs Strip */
.clinical-tabs-bar {
  display: flex;
  align-items: flex-end;
  gap: 0.25rem;
  padding: 0.85rem 1.25rem 0;
  border-bottom: 1px solid var(--color-border-soft);
  background: #f8fafc;
  border-radius: 12px 12px 0 0;
  flex-wrap: wrap;
}

.tab-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.65rem 1rem;
  border: 1px solid transparent;
  border-bottom: none;
  border-radius: 8px 8px 0 0;
  background: transparent;
  color: #0284c7;
  font-size: 0.84rem;
  font-weight: 700;
  letter-spacing: 0.015em;
  line-height: 1.25;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s ease;
  margin-bottom: -1px;
}

.tab-btn > span:not(.allergy-badge) {
  font-weight: 700;
  font-size: inherit;
  color: inherit;
  letter-spacing: inherit;
}

.tab-btn:hover {
  color: var(--color-primary-dark);
  background: rgba(255, 255, 255, 0.7);
}

.tab-btn.is-active {
  background: #ffffff;
  color: var(--color-text-navy);
  border-color: var(--color-border-soft);
  box-shadow: inset 0 2px 0 var(--color-primary);
}

.tab-btn--allergy {
  color: #dc2626;
}

.tab-btn--allergy:hover {
  color: #b91c1c;
}

.tab-btn--allergy.is-active {
  color: #dc2626;
  box-shadow: inset 0 2px 0 #dc2626;
}

.allergy-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 17px;
  height: 17px;
  padding: 0 0.3rem;
  border-radius: 9999px;
  background: #dc2626;
  color: #ffffff;
  font-size: 0.68rem;
  font-weight: 700;
}

.tab-caret {
  width: 13px;
  height: 13px;
  flex-shrink: 0;
  transition: transform 0.16s ease;
}

.tab-btn--dropdown.is-open {
  background: #e9eef5;
  color: #0284c7;
}

.tab-btn--dropdown.is-open .tab-caret {
  transform: rotate(180deg);
}

.tab-dropdown-wrap {
  position: relative;
}

.tab-dropdown-menu {
  position: absolute;
  top: calc(100% + 7px);
  left: 0;
  min-width: 225px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
  padding: 0.45rem;
  z-index: 40;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.tab-dropdown-menu::before,
.tab-dropdown-menu::after {
  content: '';
  position: absolute;
  left: 20px;
  width: 0;
  height: 0;
  border-style: solid;
}

.tab-dropdown-menu::before {
  top: -7px;
  border-width: 0 7px 7px 7px;
  border-color: transparent transparent var(--color-border-soft) transparent;
}

.tab-dropdown-menu::after {
  top: -6px;
  left: 21px;
  border-width: 0 6px 6px 6px;
  border-color: transparent transparent #ffffff transparent;
}

.tab-dropdown-item {
  width: 100%;
  text-align: left;
  background: transparent;
  border: none;
  border-radius: 7px;
  padding: 0.6rem 0.85rem;
  font-size: 0.84rem;
  font-weight: 600;
  letter-spacing: 0.015em;
  color: var(--color-text-secondary);
  cursor: pointer;
  font-family: inherit;
  transition: background-color 0.12s ease, color 0.12s ease;
}

.tab-dropdown-item:hover {
  background: #f1f5f9;
  color: var(--color-primary-dark);
}

.tab-dropdown-item.is-current {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
}

.clinical-tabs-body {
  padding: 1.25rem;
  background: #ffffff;
  border-radius: 0 0 12px 12px;
}

@media (max-width: 768px) {
  .card-header-bar,
  .patient-summary-body,
  .clinical-tabs-body {
    padding: 1rem;
  }

  .clinical-tabs-bar {
    padding: 0.75rem 0.75rem 0;
  }

  .kv-row {
    grid-template-columns: 78px 12px 1fr;
  }
}
</style>
