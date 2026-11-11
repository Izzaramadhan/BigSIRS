<script setup>
import { ref } from 'vue';
import ClinicalSelect from './ClinicalSelect.vue';

const props = defineProps({
  patient: {
    type: Object,
    required: true
  },
  vitals: {
    type: Object,
    required: true
  },
  nursing: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['save']);

const fileInputRef = ref(null);

const bloodTypes = ['A', 'B', 'AB', 'O', '- (Belum Tahu)'];
const smokingOptions = ['Tidak', 'Ya', 'Mantan Perokok'];
const generalConditionOptions = ['Compos mentis', 'Apatis', 'Somnolen', 'Sopor', 'Koma'];

const syncBloodPressure = () => {
  const sys = String(props.vitals.systole || '').trim();
  const dia = String(props.vitals.diastole || '').trim();
  if (sys && dia) {
    props.vitals.blood_pressure = `${sys}/${dia}`;
  } else {
    props.vitals.blood_pressure = sys || dia || '';
  }
};

const handleFileChange = (event) => {
  const file = event.target?.files?.[0];
  props.nursing.external_file_name = file ? file.name : '';
};

const clearFile = () => {
  props.nursing.external_file_name = '';
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
};

const handleSave = () => {
  syncBloodPressure();
  emit('save');
};
</script>

<template>
  <div class="perawat-assessment-wrap">
    <!-- Panel 1: TEKANAN DARAH & VITAL SIGN -->
    <div class="perawat-panel">
      <div class="perawat-panel-header">
        <span class="perawat-panel-title">TEKANAN DARAH</span>
        <span class="cw-pill cw-pill--primary">Asesmen Awal Perawat</span>
      </div>

      <div class="perawat-panel-body">
        <!-- Tekanan Darah Row -->
        <div class="bp-split-row">
          <label class="cw-label" for="nurse-sistole">Tekanan Darah</label>
          <div class="bp-inputs-pair">
            <input
              id="nurse-sistole"
              v-model="vitals.systole"
              type="text"
              class="cw-input font-mono"
              placeholder="Sistole"
              @input="syncBloodPressure"
            />
            <span class="bp-slash">/</span>
            <div class="addon-group">
              <input
                id="nurse-diastole"
                v-model="vitals.diastole"
                type="text"
                class="cw-input font-mono"
                placeholder="Diastole"
                @input="syncBloodPressure"
              />
              <span class="addon-suffix">mmHg</span>
            </div>
          </div>
        </div>

        <!-- Inner Sub-section: VITAL SIGN -->
        <div class="inner-sub-section">
          <h4 class="vital-section-heading">VITAL SIGN</h4>

          <div class="vital-3col-grid">
            <div class="inline-metric-row">
              <label class="cw-label" for="nurse-nadi">Nadi</label>
              <div class="addon-group">
                <input
                  id="nurse-nadi"
                  v-model="vitals.heart_rate"
                  type="text"
                  class="cw-input font-mono"
                />
                <span class="addon-suffix">x/mnt</span>
              </div>
            </div>

            <div class="inline-metric-row">
              <label class="cw-label" for="nurse-suhu">Suhu</label>
              <div class="addon-group">
                <input
                  id="nurse-suhu"
                  v-model="vitals.temperature"
                  type="text"
                  class="cw-input font-mono"
                />
                <span class="addon-suffix">&deg; C</span>
              </div>
            </div>

            <div class="inline-metric-row">
              <label class="cw-label" for="nurse-spo2">SPO2</label>
              <div class="addon-group">
                <input
                  id="nurse-spo2"
                  v-model="vitals.spo2"
                  type="text"
                  class="cw-input font-mono"
                />
                <span class="addon-suffix">%</span>
              </div>
            </div>

            <div class="inline-metric-row">
              <label class="cw-label" for="nurse-pernafasan">Pernafasan</label>
              <div class="addon-group">
                <input
                  id="nurse-pernafasan"
                  v-model="vitals.respiratory_rate"
                  type="text"
                  class="cw-input font-mono"
                />
                <span class="addon-suffix">x/mnt</span>
              </div>
            </div>

            <div class="inline-metric-row">
              <label class="cw-label" for="nurse-blood-type">Golongan Darah</label>
              <ClinicalSelect
                id="nurse-blood-type"
                v-model="patient.blood_type"
                :options="bloodTypes"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Panel 2: ANTROPOMETRI -->
    <div class="perawat-panel">
      <div class="perawat-panel-header">
        <span class="perawat-panel-title">ANTROPOMETRI</span>
      </div>

      <div class="perawat-panel-body">
        <div class="antro-2col-grid">
          <div class="inline-metric-row">
            <label class="cw-label" for="nurse-bb">Berat Badan</label>
            <div class="addon-group">
              <input
                id="nurse-bb"
                v-model="vitals.weight"
                type="text"
                class="cw-input font-mono"
              />
              <span class="addon-suffix">Kg</span>
            </div>
          </div>

          <div class="inline-metric-row">
            <label class="cw-label" for="nurse-tb">Tinggi Badan</label>
            <div class="addon-group">
              <input
                id="nurse-tb"
                v-model="vitals.height"
                type="text"
                class="cw-input font-mono"
              />
              <span class="addon-suffix">Cm</span>
            </div>
          </div>

          <div class="inline-metric-row">
            <label class="cw-label" for="nurse-waist">Lingkar Pinggang</label>
            <div class="addon-group">
              <input
                id="nurse-waist"
                v-model="vitals.waist_circumference"
                type="text"
                class="cw-input font-mono"
              />
              <span class="addon-suffix">Cm</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Panel 3: PEMERIKSAAN AWAL -->
    <div class="perawat-panel">
      <div class="perawat-panel-header">
        <span class="perawat-panel-title">PEMERIKSAAN AWAL</span>
      </div>

      <div class="perawat-panel-body">
        <!-- Row 1: Keluhan Utama + Status Merokok -->
        <div class="awal-top-grid">
          <div class="awal-field-row awal-field-row--top">
            <label class="cw-label" for="nurse-complaint">Keluhan Utama :</label>
            <textarea
              id="nurse-complaint"
              v-model="nursing.chief_complaint"
              rows="2"
              class="cw-textarea"
              placeholder="Tuliskan keluhan utama saat datang..."
            ></textarea>
          </div>

          <div class="awal-field-row">
            <label class="cw-label" for="nurse-smoking">Status Merokok :</label>
            <ClinicalSelect
              id="nurse-smoking"
              v-model="nursing.smoking"
              :options="smokingOptions"
            />
          </div>
        </div>

        <!-- Row 2: Keadaan Umum + Pemeriksaan Fisik + Advice -->
        <div class="awal-bottom-grid">
          <div class="awal-field-row">
            <label class="cw-label" for="nurse-general">Keadaan Umum :</label>
            <ClinicalSelect
              id="nurse-general"
              v-model="nursing.general_condition"
              :options="generalConditionOptions"
            />
          </div>

          <div class="awal-field-row awal-field-row--top">
            <label class="cw-label" for="nurse-physical-exam">Pemeriksaan Fisik :</label>
            <textarea
              id="nurse-physical-exam"
              v-model="nursing.physical_exam_note"
              rows="2"
              class="cw-textarea"
            ></textarea>
          </div>

          <div class="awal-field-row awal-field-row--top">
            <label class="cw-label" for="nurse-advice">Advice :</label>
            <textarea
              id="nurse-advice"
              v-model="nursing.advice"
              rows="2"
              class="cw-textarea"
            ></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Panel 4: PEMERIKSAAN EKSTERNAL -->
    <div class="perawat-panel">
      <div class="perawat-panel-header">
        <span class="perawat-panel-title">PEMERIKSAAN EKSTERNAL</span>
      </div>

      <div class="perawat-panel-body">
        <div class="external-file-box">
          <input
            id="nurse-external-file"
            ref="fileInputRef"
            type="file"
            class="sr-file-input"
            @change="handleFileChange"
          />
          <label for="nurse-external-file" class="btn-pick-file">Pilih File</label>
          <span class="file-status-text">
            {{ nursing.external_file_name || 'Tidak ada file yang dipilih' }}
          </span>
          <button
            v-if="nursing.external_file_name"
            type="button"
            class="btn-clear-file"
            title="Hapus file"
            @click="clearFile"
          >
            &times;
          </button>
        </div>
      </div>
    </div>

    <!-- Centered Save Button Footer -->
    <div class="perawat-submit-footer">
      <button type="button" class="cw-btn-primary btn-save-clinical btn-save-perawat" @click="handleSave">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        <span>Simpan</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.perawat-assessment-wrap {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.perawat-panel {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  overflow: visible;
}

.perawat-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding: 0.78rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft);
  border-radius: 10px 10px 0 0;
  flex-wrap: wrap;
}

.perawat-panel-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: 0.015em;
}

.perawat-panel-body {
  padding: 1.15rem 1.25rem;
  border-radius: 0 0 10px 10px;
}

/* Tekanan Darah Split Row */
.bp-split-row {
  display: grid;
  grid-template-columns: 145px minmax(0, 440px);
  gap: 0.85rem;
  align-items: center;
  margin-bottom: 1.15rem;
}

.bp-inputs-pair {
  display: grid;
  grid-template-columns: 1fr auto 1.25fr;
  gap: 0.55rem;
  align-items: center;
}

.bp-slash {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-secondary);
}

.inner-sub-section {
  padding-top: 0.25rem;
}

.vital-section-heading {
  margin: 0 0 0.85rem 0;
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: 0.015em;
}

.vital-3col-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.9rem 1.35rem;
}

.antro-2col-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem 1.5rem;
  max-width: 780px;
}

.inline-metric-row {
  display: grid;
  grid-template-columns: 135px 1fr;
  gap: 0.75rem;
  align-items: center;
}

.addon-group {
  display: flex;
  align-items: stretch;
  width: 100%;
}

.addon-group .cw-input {
  flex: 1;
  min-width: 0;
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.addon-suffix {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 58px;
  padding: 0 0.65rem;
  height: 40px;
  background: #f1f5f9;
  border: 1px solid var(--color-border-soft);
  border-left: none;
  border-radius: 0 8px 8px 0;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  white-space: nowrap;
  box-sizing: border-box;
}

/* Pemeriksaan Awal Layout */
.awal-top-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1rem 1.35rem;
  margin-bottom: 1rem;
  align-items: start;
}

.awal-bottom-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem 1.25rem;
  align-items: start;
}

.awal-field-row {
  display: grid;
  grid-template-columns: 135px 1fr;
  gap: 0.7rem;
  align-items: center;
}

.awal-field-row--top {
  align-items: start;
}

.awal-field-row--top .cw-label {
  padding-top: 0.5rem;
}

/* External File Upload */
.external-file-box {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.85rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  background: #ffffff;
}

.sr-file-input {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

.btn-pick-file {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 32px;
  padding: 0 0.85rem;
  background: #f1f5f9;
  color: var(--color-text-navy);
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background-color 0.15s ease;
}

.btn-pick-file:hover {
  background: #e2e8f0;
}

.file-status-text {
  font-size: 0.84rem;
  color: var(--color-text-secondary);
}

.btn-clear-file {
  margin-left: auto;
  background: transparent;
  border: none;
  color: var(--color-text-secondary);
  font-size: 1.05rem;
  line-height: 1;
  cursor: pointer;
}

.btn-clear-file:hover {
  color: var(--color-danger);
}

.perawat-submit-footer {
  display: flex;
  justify-content: center;
}

.btn-save-perawat {
  min-width: 124px;
}

.btn-save-perawat svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

@media (max-width: 1100px) {
  .vital-3col-grid,
  .awal-top-grid,
  .awal-bottom-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .bp-split-row,
  .antro-2col-grid,
  .inline-metric-row,
  .awal-field-row {
    grid-template-columns: 1fr;
    gap: 0.35rem;
  }
}
</style>

