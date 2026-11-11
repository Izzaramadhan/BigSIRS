<script setup>
import { ref, reactive, computed } from 'vue';

const props = defineProps({
  patient: {
    type: Object,
    required: true
  },
  allergyNote: {
    type: String,
    default: ''
  },
  clinicalForm: {
    type: Object,
    required: true
  },
  diagnosisForm: {
    type: Object,
    required: true
  },
  diagnoses: {
    type: Array,
    default: () => []
  },
  icd9List: {
    type: Array,
    default: () => []
  },
  procedures: {
    type: Array,
    default: () => []
  },
  orders: {
    type: Object,
    required: true
  },
  doctors: {
    type: Array,
    default: () => []
  },
  paramedics: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['print-resume', 'complete-examination']);

const sectionsOpen = reactive({
  umum: true,
  vital: true,
  antropometri: true,
  diagnosis: true,
  tindakan: true,
  order: true,
  alergi: true,
  lanjutan: true
});

const doctorError = ref('');

const toggleSection = (key) => {
  sectionsOpen[key] = !sectionsOpen[key];
};

const doctorOptions = computed(() => {
  const list = new Set([...props.doctors]);
  if (props.patient.doctor && props.patient.doctor !== '-') {
    list.add(props.patient.doctor);
  }
  if (props.clinicalForm.resume?.doctor) {
    list.add(props.clinicalForm.resume.doctor);
  }
  return Array.from(list);
});

const parsedBloodPressure = computed(() => {
  const raw = String(props.clinicalForm.vitals?.blood_pressure || '').trim();
  const match = raw.match(/^(\d+)\s*\/\s*(\d+)$/);
  if (match) {
    return { systole: match[1], diastole: match[2] };
  }
  return { systole: raw || '-', diastole: '-' };
});

const physicalSummary = computed(() => {
  const phys = props.clinicalForm.physical || {};
  const parts = [phys.head_neck, phys.thorax, phys.abdomen, phys.extremities].filter(Boolean);
  return parts.length > 0 ? parts.join('; ') : '-';
});

const computedBmi = computed(() => {
  const w = parseFloat(props.clinicalForm.vitals?.weight);
  const hCm = parseFloat(props.clinicalForm.vitals?.height);
  if (!w || !hCm || hCm <= 0) return '-';
  const hM = hCm / 100;
  const bmi = w / (hM * hM);
  let category = 'Normal';
  if (bmi < 18.5) category = 'Kurus';
  else if (bmi >= 25 && bmi < 30) category = 'Berlebih';
  else if (bmi >= 30) category = 'Obesitas';
  return `${bmi.toFixed(1)} kg/m² (${category})`;
});

const handleCompleteClick = () => {
  if (!props.clinicalForm.resume.doctor) {
    doctorError.value = 'Dokter pemeriksa wajib dipilih.';
    return;
  }
  doctorError.value = '';
  emit('complete-examination');
};
</script>

<template>
  <div class="clinical-workspace-panel resume-page-wrap">
    <!-- Top Centered Print Button -->
    <div class="resume-print-top">
      <button type="button" class="btn-print-resume" @click="emit('print-resume')">
        Cetak Resume
      </button>
    </div>

    <!-- 1. PEMERIKSAAN UMUM -->
    <div class="accordion-card">
      <button type="button" class="accordion-header" @click="toggleSection('umum')">
        <span class="accordion-title">PEMERIKSAAN UMUM</span>
        <span class="accordion-toggle-icon">{{ sectionsOpen.umum ? '−' : '+' }}</span>
      </button>
      <div v-show="sectionsOpen.umum" class="accordion-body">
        <div class="resume-kv-list">
          <div class="resume-kv-row">
            <span class="rkv-label">Keluhan Utama</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value">{{ clinicalForm.nursing?.chief_complaint || clinicalForm.soap?.subjective || clinicalForm.bidan?.chief_complaint || '-' }}</span>
          </div>
          <div class="resume-kv-row">
            <span class="rkv-label">Keadaan Umum</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value">{{ clinicalForm.nursing?.general_condition || clinicalForm.physical?.general || '-' }}</span>
          </div>
          <div class="resume-kv-row">
            <span class="rkv-label">Pemeriksaan Fisik</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value">{{ clinicalForm.nursing?.physical_exam_note || physicalSummary }}</span>
          </div>
          <div class="resume-kv-row">
            <span class="rkv-label">Advice</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value">{{ clinicalForm.nursing?.advice || clinicalForm.soap?.plan || clinicalForm.resume?.discharge_advice || '-' }}</span>
          </div>
          <div class="resume-kv-row">
            <span class="rkv-label">Merokok</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value">{{ clinicalForm.nursing?.smoking || 'Tidak' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. VITAL SIGN -->
    <div class="accordion-card">
      <button type="button" class="accordion-header" @click="toggleSection('vital')">
        <span class="accordion-title">VITAL SIGN</span>
        <span class="accordion-toggle-icon">{{ sectionsOpen.vital ? '−' : '+' }}</span>
      </button>
      <div v-show="sectionsOpen.vital" class="accordion-body">
        <h4 class="vital-sub-heading">TEKANAN DARAH</h4>
        <div class="resume-kv-list vital-sub-list">
          <div class="resume-kv-row">
            <span class="rkv-label">Sistole</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value font-mono">{{ parsedBloodPressure.systole }} mm Hg</span>
          </div>
          <div class="resume-kv-row">
            <span class="rkv-label">Diastole</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value font-mono">{{ parsedBloodPressure.diastole }} mm Hg</span>
          </div>
        </div>

        <h4 class="vital-sub-heading vital-sub-heading--spaced">VITAL SIGN</h4>
        <div class="resume-kv-list vital-sub-list">
          <div class="resume-kv-row">
            <span class="rkv-label">Nadi</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value font-mono">{{ clinicalForm.vitals?.heart_rate || '-' }} x/mnt</span>
          </div>
          <div class="resume-kv-row">
            <span class="rkv-label">SPO2</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value font-mono">{{ clinicalForm.vitals?.spo2 || '-' }} %</span>
          </div>
          <div class="resume-kv-row">
            <span class="rkv-label">Pernafasan</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value font-mono">{{ clinicalForm.vitals?.respiratory_rate || '-' }} x/mnt</span>
          </div>
          <div class="resume-kv-row">
            <span class="rkv-label">Suhu</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value font-mono">{{ clinicalForm.vitals?.temperature || '-' }} (&deg; C)</span>
          </div>
          <div class="resume-kv-row">
            <span class="rkv-label">Golongan Darah</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value">{{ patient.blood_type || 'O' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. ANTROPOMETRI -->
    <div class="accordion-card">
      <button type="button" class="accordion-header" @click="toggleSection('antropometri')">
        <span class="accordion-title">ANTROPOMETRI</span>
        <span class="accordion-toggle-icon">{{ sectionsOpen.antropometri ? '−' : '+' }}</span>
      </button>
      <div v-show="sectionsOpen.antropometri" class="accordion-body">
        <div class="resume-kv-list">
          <div class="resume-kv-row">
            <span class="rkv-label">Berat Badan</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value font-mono">{{ clinicalForm.vitals?.weight || '-' }} Kg</span>
          </div>
          <div class="resume-kv-row">
            <span class="rkv-label">Tinggi Badan</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value font-mono">{{ clinicalForm.vitals?.height || '-' }} Cm</span>
          </div>
          <div class="resume-kv-row">
            <span class="rkv-label">BMI / IMT</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value font-mono">{{ computedBmi }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. DIAGNOSIS -->
    <div class="accordion-card">
      <button type="button" class="accordion-header" @click="toggleSection('diagnosis')">
        <span class="accordion-title">DIAGNOSIS</span>
        <span class="accordion-toggle-icon">{{ sectionsOpen.diagnosis ? '−' : '+' }}</span>
      </button>
      <div v-show="sectionsOpen.diagnosis" class="accordion-body">
        <div class="dx-inline-summary">
          <span><strong>Diagnosis Kerja :</strong> {{ diagnosisForm.working_diagnosis || '-' }}</span>
          <span><strong>Diagnosis Akhir :</strong> {{ diagnosisForm.final_diagnosis || '-' }}</span>
        </div>

        <div class="icd-sub-block">
          <div class="icd-sub-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="8" y1="6" x2="21" y2="6"></line>
              <line x1="8" y1="12" x2="21" y2="12"></line>
              <line x1="8" y1="18" x2="21" y2="18"></line>
              <line x1="3" y1="6" x2="3.01" y2="6"></line>
              <line x1="3" y1="12" x2="3.01" y2="12"></line>
              <line x1="3" y1="18" x2="3.01" y2="18"></line>
            </svg>
            <span>ICD-10</span>
          </div>
          <div class="cw-table-wrap">
            <table class="cw-table">
              <thead>
                <tr>
                  <th>ICD</th>
                  <th>Nama</th>
                  <th>Catatan</th>
                  <th>Kasus</th>
                  <th>Kategori</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="diagnoses.length === 0">
                  <td colspan="5" class="empty-cell-center">Data Tidak Ada</td>
                </tr>
                <tr v-for="dx in diagnoses" v-else :key="dx.id">
                  <td class="font-mono font-bold">{{ dx.code }}</td>
                  <td>{{ dx.name }}</td>
                  <td>{{ dx.note || '-' }}</td>
                  <td>{{ dx.case_type || '-' }}</td>
                  <td>{{ dx.category || dx.type || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="icd-sub-block icd-sub-block--last">
          <div class="icd-sub-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="8" y1="6" x2="21" y2="6"></line>
              <line x1="8" y1="12" x2="21" y2="12"></line>
              <line x1="8" y1="18" x2="21" y2="18"></line>
              <line x1="3" y1="6" x2="3.01" y2="6"></line>
              <line x1="3" y1="12" x2="3.01" y2="12"></line>
              <line x1="3" y1="18" x2="3.01" y2="18"></line>
            </svg>
            <span>ICD-9</span>
          </div>
          <div class="cw-table-wrap">
            <table class="cw-table">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="icd9List.length === 0">
                  <td colspan="2" class="empty-cell-center">Data Tidak Ada</td>
                </tr>
                <tr v-for="proc in icd9List" v-else :key="proc.id">
                  <td class="font-mono font-bold">{{ proc.code }}</td>
                  <td>{{ proc.name }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- 5. TINDAKAN -->
    <div class="accordion-card">
      <button type="button" class="accordion-header" @click="toggleSection('tindakan')">
        <span class="accordion-title">TINDAKAN</span>
        <span class="accordion-toggle-icon">{{ sectionsOpen.tindakan ? '−' : '+' }}</span>
      </button>
      <div v-show="sectionsOpen.tindakan" class="accordion-body">
        <div class="cw-table-wrap">
          <table class="cw-table">
            <thead>
              <tr>
                <th>Tindakan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="procedures.length === 0">
                <td class="empty-cell-center">Data Tidak Ada</td>
              </tr>
              <tr v-for="proc in procedures" v-else :key="proc.id">
                <td>
                  <strong>{{ proc.name }}</strong>
                  <span v-if="proc.doctor && proc.doctor !== '-'" class="proc-doc-note"> ({{ proc.doctor }})</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 6. ORDER -->
    <div class="accordion-card">
      <button type="button" class="accordion-header" @click="toggleSection('order')">
        <span class="accordion-title">ORDER</span>
        <span class="accordion-toggle-icon">{{ sectionsOpen.order ? '−' : '+' }}</span>
      </button>
      <div v-show="sectionsOpen.order" class="accordion-body order-blocks-stack">
        <!-- LABORATORIUM -->
        <div class="order-sub-group">
          <h4 class="order-sub-heading">LABORATORIUM</h4>
          <div class="cw-table-wrap">
            <table class="cw-table">
              <thead><tr><th>Pemeriksaan</th></tr></thead>
              <tbody>
                <tr v-if="(orders.lab || []).length === 0">
                  <td class="empty-cell-center">Data Tidak Ada</td>
                </tr>
                <tr v-for="lab in orders.lab || []" v-else :key="lab.id">
                  <td>{{ lab.item }} ({{ lab.category || lab.priority }})</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RADIOLOGI -->
        <div class="order-sub-group">
          <h4 class="order-sub-heading">RADIOLOGI</h4>
          <div class="cw-table-wrap">
            <table class="cw-table">
              <thead><tr><th>Pemeriksaan</th></tr></thead>
              <tbody>
                <tr v-if="(orders.radiology || []).length === 0">
                  <td class="empty-cell-center">Data Tidak Ada</td>
                </tr>
                <tr v-for="rad in orders.radiology || []" v-else :key="rad.id">
                  <td>{{ rad.item }} ({{ rad.category || rad.priority }})</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RESEP -->
        <div class="order-sub-group">
          <h4 class="order-sub-heading">RESEP</h4>
          <div class="cw-table-wrap">
            <table class="cw-table">
              <thead>
                <tr>
                  <th>Nama Obat</th>
                  <th>Signa</th>
                  <th>Aturan Pakai</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="(orders.pharmacy || []).length === 0 && (orders.racikan || []).length === 0">
                  <td colspan="3" class="empty-cell-center">Data Tidak Ada</td>
                </tr>
                <tr v-for="rx in orders.pharmacy || []" :key="rx.id">
                  <td>{{ rx.name }} ({{ rx.qty }} {{ rx.unit }})</td>
                  <td>{{ rx.signa }}</td>
                  <td>{{ rx.usage_rule || 'Sesudah / sesuai anjuran dokter' }}</td>
                </tr>
                <tr v-for="rck in orders.racikan || []" :key="rck.id">
                  <td>Racikan: {{ rck.name }} ({{ rck.qty }} Bungkus)</td>
                  <td>{{ rck.signa }}</td>
                  <td>{{ rck.usage_rule || 'Sesudah makan' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- 7. ALERGI -->
    <div class="accordion-card">
      <button type="button" class="accordion-header" @click="toggleSection('alergi')">
        <span class="accordion-title accordion-title--danger">ALERGI</span>
        <span class="accordion-toggle-icon">{{ sectionsOpen.alergi ? '−' : '+' }}</span>
      </button>
      <div v-show="sectionsOpen.alergi" class="accordion-body">
        <p class="allergy-summary-text">{{ allergyNote || 'Tidak ada catatan alergi.' }}</p>
      </div>
    </div>

    <!-- 8. PENANGANAN LANJUTAN -->
    <div class="accordion-card">
      <button type="button" class="accordion-header" @click="toggleSection('lanjutan')">
        <span class="accordion-title">PENANGANAN LANJUTAN</span>
        <span class="accordion-toggle-icon">{{ sectionsOpen.lanjutan ? '−' : '+' }}</span>
      </button>
      <div v-show="sectionsOpen.lanjutan" class="accordion-body">
        <div class="resume-kv-list">
          <div class="resume-kv-row">
            <span class="rkv-label">Penanganan Lanjutan</span>
            <span class="rkv-colon">:</span>
            <span class="rkv-value">{{ clinicalForm.resume?.disposition || '-' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Assignment Form (Penanganan Lanjutan, Dokter *, Paramedis) -->
    <div class="section-card-box resume-final-form">
      <div class="resume-form-rows">
        <div class="rf-row">
          <label class="cw-label" for="res-disposition">Penanganan Lanjutan</label>
          <div class="rf-control">
            <select id="res-disposition" v-model="clinicalForm.resume.disposition" class="cw-select">
              <option value="">--- Silahkan Pilih ---</option>
              <option value="Pulang / Kontrol Poliklinik">Pulang / Kontrol Poliklinik</option>
              <option value="Rujuk Balik ke FKTP">Rujuk Balik ke FKTP / Puskesmas</option>
              <option value="Rawat Inap (Admisi Ranap)">Anjuran Rawat Inap</option>
              <option value="Rujuk RS Rujukan Lanjutan">Rujuk RS Rujukan Lanjutan</option>
              <option value="Pulang Atas Permintaan Sendiri">Pulang Atas Permintaan Sendiri</option>
            </select>
          </div>
        </div>

        <div class="rf-row">
          <label class="cw-label" for="res-doctor">
            Dokter <span class="cw-req">*</span>
          </label>
          <div class="rf-control">
            <div class="doc-select-wrap">
              <select
                id="res-doctor"
                v-model="clinicalForm.resume.doctor"
                class="cw-select"
                :class="{ 'is-error': doctorError }"
                @change="doctorError = ''"
              >
                <option value="">--- Silahkan Pilih ---</option>
                <option v-for="doc in doctorOptions" :key="doc" :value="doc">{{ doc }}</option>
              </select>
              <button
                v-if="clinicalForm.resume.doctor"
                type="button"
                class="btn-clear-select"
                title="Kosongkan Dokter"
                aria-label="Kosongkan Dokter"
                @click="clinicalForm.resume.doctor = ''"
              >
                &times;
              </button>
            </div>
            <span v-if="doctorError" class="rf-err-text">{{ doctorError }}</span>
          </div>
        </div>

        <div class="rf-row">
          <label class="cw-label" for="res-paramedic">Paramedis</label>
          <div class="rf-control">
            <select id="res-paramedic" v-model="clinicalForm.resume.paramedic" class="cw-select">
              <option value="">--- Silahkan Pilih ---</option>
              <option v-for="pm in paramedics" :key="pm" :value="pm">{{ pm }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Centered Complete Button -->
    <div class="resume-complete-footer">
      <button type="button" class="cw-btn-primary btn-complete-exam" @click="handleCompleteClick">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 11 12 14 22 4"></polyline>
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
        </svg>
        <span>Selesai</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.resume-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.95rem;
}

.resume-print-top {
  display: flex;
  justify-content: center;
}

.btn-print-resume {
  height: 36px;
  padding: 0 1.15rem;
  border: 1px solid #2563eb;
  border-radius: 8px;
  background: #3b82f6;
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: background-color 0.15s ease, transform 0.12s ease;
}

.btn-print-resume:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

/* Accordion Summary Cards */
.accordion-card {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  overflow: hidden;
}

.accordion-header {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.72rem 1.2rem;
  background: #f8fafc;
  border: none;
  border-bottom: 1px solid transparent;
  cursor: pointer;
  font-family: inherit;
  text-align: left;
  transition: background-color 0.15s ease;
}

.accordion-header:hover {
  background: #f1f5f9;
}

.accordion-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: 0.015em;
}

.accordion-title--danger {
  color: #dc2626;
}

.accordion-toggle-icon {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-secondary);
  line-height: 1;
}

.accordion-body {
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--color-border-soft);
  background: #ffffff;
}

/* KV Summary Rows */
.resume-kv-list {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.resume-kv-row {
  display: grid;
  grid-template-columns: 165px 14px 1fr;
  align-items: baseline;
  font-size: 0.86rem;
  line-height: 1.45;
}

.rkv-label,
.rkv-colon {
  color: var(--color-text-secondary);
  font-weight: 500;
}

.rkv-value {
  color: var(--color-text-navy);
}

/* Vital Sign sub-sections */
.vital-sub-heading {
  margin: 0 0 0.45rem 0;
  font-size: 0.84rem;
  font-weight: 700;
  color: var(--color-text-navy);
  text-decoration: underline;
  text-underline-offset: 3px;
}

.vital-sub-heading--spaced {
  margin-top: 0.95rem;
}

.vital-sub-list {
  padding-left: 0.65rem;
}

/* Diagnosis Summary */
.dx-inline-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  font-size: 0.86rem;
  color: var(--color-text-navy);
  margin-bottom: 0.85rem;
}

.icd-sub-block {
  margin-bottom: 0.9rem;
}

.icd-sub-block--last {
  margin-bottom: 0;
}

.icd-sub-label {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.84rem;
  font-weight: 700;
  color: #dc2626;
  margin-bottom: 0.45rem;
}

.icd-sub-label svg {
  width: 14px;
  height: 14px;
}

.empty-cell-center {
  text-align: center;
  color: var(--color-text-secondary);
  padding: 0.8rem !important;
}

.proc-doc-note {
  color: var(--color-text-secondary);
  font-size: 0.82rem;
}

/* Order Summary Groups */
.order-blocks-stack {
  display: flex;
  flex-direction: column;
  gap: 0.95rem;
}

.order-sub-heading {
  margin: 0 0 0.45rem 0;
  font-size: 0.84rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.allergy-summary-text {
  margin: 0;
  font-size: 0.86rem;
  color: var(--color-text-navy);
}

/* Bottom Assignment Form */
.resume-form-rows {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  max-width: 640px;
}

.rf-row {
  display: grid;
  grid-template-columns: 165px 1fr;
  gap: 0.85rem;
  align-items: center;
}

.rf-control {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.doc-select-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.btn-clear-select {
  position: absolute;
  right: 2rem;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: var(--color-text-secondary);
  font-size: 1rem;
  line-height: 1;
  padding: 0.1rem 0.25rem;
  cursor: pointer;
}

.btn-clear-select:hover {
  color: var(--color-danger);
}

.cw-select.is-error {
  border-color: var(--color-danger);
}

.rf-err-text {
  font-size: 0.78rem;
  color: var(--color-danger);
}

.resume-complete-footer {
  display: flex;
  justify-content: center;
  padding-top: 0.25rem;
}

.btn-complete-exam {
  min-width: 124px;
}

.btn-complete-exam svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

@media (max-width: 640px) {
  .resume-kv-row,
  .rf-row {
    grid-template-columns: 1fr;
    gap: 0.3rem;
  }
}
</style>

