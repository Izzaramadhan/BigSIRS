<script setup>
import { computed } from 'vue';

const props = defineProps({
  dokter: {
    type: Object,
    required: true
  },
  vitals: {
    type: Object,
    required: true
  },
  soap: {
    type: Object,
    required: true
  },
  doctors: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['save']);

const doctorOptions = computed(() => {
  const list = new Set([...props.doctors]);
  if (props.dokter.dpjp) {
    list.add(props.dokter.dpjp);
  }
  return Array.from(list);
});

const computedBmi = computed(() => {
  const w = parseFloat(props.vitals.weight);
  const hCm = parseFloat(props.vitals.height);
  if (!w || !hCm || hCm <= 0) return '';
  const hM = hCm / 100;
  const bmi = w / (hM * hM);
  return bmi.toFixed(1);
});

const syncBloodPressure = () => {
  const sys = String(props.vitals.systole || '').trim();
  const dia = String(props.vitals.diastole || '').trim();
  if (sys && dia) {
    props.vitals.blood_pressure = `${sys}/${dia}`;
  } else {
    props.vitals.blood_pressure = sys || dia || '';
  }
};

const handleSave = () => {
  syncBloodPressure();
  emit('save');
};
</script>

<template>
  <div class="dokter-assessment-wrap">
    <!-- Top Section: PEMERIKSAAN -->
    <div class="dokter-panel">
      <div class="dokter-panel-header">
        <span class="dokter-panel-title">PEMERIKSAAN</span>
        <span class="cw-pill cw-pill--primary">Asesmen Awal Dokter</span>
      </div>

      <div class="dokter-panel-body">
        <div class="dokter-two-col">
          <!-- Left Column: Tanggal + Tanda Vital + Antropometri -->
          <div class="dokter-col">
            <div class="dokter-h-row">
              <label class="dokter-h-label" for="dokter-date">Tanggal</label>
              <input id="dokter-date" v-model="dokter.date" type="date" class="cw-input font-mono" />
            </div>

            <div class="dokter-h-row">
              <label class="dokter-h-label" for="dokter-sistole">Sistole</label>
              <div class="addon-group">
                <input
                  id="dokter-sistole"
                  v-model="vitals.systole"
                  type="text"
                  class="cw-input font-mono"
                  placeholder="Sistole"
                  @input="syncBloodPressure"
                />
                <span class="addon-suffix">(mm Hg)</span>
              </div>
            </div>

            <div class="dokter-h-row">
              <label class="dokter-h-label" for="dokter-diastole">Diastole</label>
              <div class="addon-group">
                <input
                  id="dokter-diastole"
                  v-model="vitals.diastole"
                  type="text"
                  class="cw-input font-mono"
                  placeholder="Diastole"
                  @input="syncBloodPressure"
                />
                <span class="addon-suffix">(mm Hg)</span>
              </div>
            </div>

            <div class="dokter-h-row">
              <label class="dokter-h-label" for="dokter-nadi">Nadi</label>
              <div class="addon-group">
                <input
                  id="dokter-nadi"
                  v-model="vitals.heart_rate"
                  type="text"
                  class="cw-input font-mono"
                  placeholder="Nadi"
                />
                <span class="addon-suffix">(x/mnt)</span>
              </div>
            </div>

            <div class="dokter-h-row">
              <label class="dokter-h-label" for="dokter-pernafasan">Pernafasan</label>
              <div class="addon-group">
                <input
                  id="dokter-pernafasan"
                  v-model="vitals.respiratory_rate"
                  type="text"
                  class="cw-input font-mono"
                  placeholder="Pernafasan"
                />
                <span class="addon-suffix">(x/mnt)</span>
              </div>
            </div>

            <div class="dokter-h-row">
              <label class="dokter-h-label" for="dokter-spo2">SPO2</label>
              <div class="addon-group">
                <input
                  id="dokter-spo2"
                  v-model="vitals.spo2"
                  type="text"
                  class="cw-input font-mono"
                  placeholder="SPO2"
                />
                <span class="addon-suffix">(%)</span>
              </div>
            </div>

            <div class="dokter-h-row">
              <label class="dokter-h-label" for="dokter-suhu">Suhu</label>
              <div class="addon-group">
                <input
                  id="dokter-suhu"
                  v-model="vitals.temperature"
                  type="text"
                  class="cw-input font-mono"
                  placeholder="Suhu"
                />
                <span class="addon-suffix">(&deg; C)</span>
              </div>
            </div>

            <div class="dokter-h-row">
              <label class="dokter-h-label" for="dokter-bb">Berat Badan</label>
              <div class="addon-group">
                <input
                  id="dokter-bb"
                  v-model="vitals.weight"
                  type="text"
                  class="cw-input font-mono"
                  placeholder="Berat Badan"
                />
                <span class="addon-suffix">(Kg)</span>
              </div>
            </div>

            <div class="dokter-h-row">
              <label class="dokter-h-label" for="dokter-tb">Tinggi Badan</label>
              <div class="addon-group">
                <input
                  id="dokter-tb"
                  v-model="vitals.height"
                  type="text"
                  class="cw-input font-mono"
                  placeholder="Tinggi Badan"
                />
                <span class="addon-suffix">(Cm)</span>
              </div>
            </div>

            <div class="dokter-h-row">
              <label class="dokter-h-label" for="dokter-bmi">BMI / IMT</label>
              <input
                id="dokter-bmi"
                :value="computedBmi"
                type="text"
                readonly
                class="cw-input font-mono readonly-metric-input"
                placeholder="BMI / IMT"
              />
            </div>
          </div>

          <!-- Right Column: Jam + Keluhan + SOAP + Instruksi PPA -->
          <div class="dokter-col">
            <div class="dokter-h-row">
              <label class="dokter-h-label" for="dokter-time">Jam</label>
              <input id="dokter-time" v-model="dokter.time" type="time" class="cw-input font-mono" />
            </div>

            <div class="dokter-h-row dokter-h-row--textarea">
              <label class="dokter-h-label" for="dokter-keluhan">Keluhan</label>
              <textarea
                id="dokter-keluhan"
                v-model="dokter.chief_complaint"
                rows="2"
                class="cw-textarea"
                placeholder="Keluhan Pasien"
              ></textarea>
            </div>

            <div class="dokter-h-row dokter-h-row--textarea">
              <label class="dokter-h-label" for="dokter-subjektif">Subjektif</label>
              <textarea
                id="dokter-subjektif"
                v-model="soap.subjective"
                rows="2"
                class="cw-textarea"
                placeholder="Pemeriksaan Subjektif"
              ></textarea>
            </div>

            <div class="dokter-h-row dokter-h-row--textarea">
              <label class="dokter-h-label" for="dokter-objektif">Objektif</label>
              <textarea
                id="dokter-objektif"
                v-model="soap.objective"
                rows="2"
                class="cw-textarea"
                placeholder="Pemeriksaan Objektif"
              ></textarea>
            </div>

            <div class="dokter-h-row dokter-h-row--textarea">
              <label class="dokter-h-label" for="dokter-assesmen">Assesmen</label>
              <textarea
                id="dokter-assesmen"
                v-model="soap.assessment"
                rows="2"
                class="cw-textarea"
                placeholder="Assesmen"
              ></textarea>
            </div>

            <div class="dokter-h-row dokter-h-row--textarea">
              <label class="dokter-h-label" for="dokter-planning">Planning</label>
              <textarea
                id="dokter-planning"
                v-model="soap.plan"
                rows="2"
                class="cw-textarea"
                placeholder="Planning"
              ></textarea>
            </div>

            <div class="dokter-h-row dokter-h-row--textarea">
              <label class="dokter-h-label" for="dokter-instruksi-ppa">Instruksi PPA</label>
              <textarea
                id="dokter-instruksi-ppa"
                v-model="dokter.ppa_instruction"
                rows="2"
                class="cw-textarea"
                placeholder="Instruksi PPA"
              ></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sub-Panel: DOKTER PENANGGUNG JAWAB -->
    <div class="dokter-panel">
      <div class="dokter-panel-header">
        <span class="dokter-panel-title">DOKTER PENANGGUNG JAWAB</span>
      </div>
      <div class="dokter-panel-body">
        <div class="dpjp-field-wrap">
          <label class="cw-label" for="dokter-dpjp">DPJP</label>
          <div class="dpjp-select-wrap">
            <select id="dokter-dpjp" v-model="dokter.dpjp" class="cw-select dpjp-select">
              <option value="">== Pilih Dokter ==</option>
              <option v-for="doc in doctorOptions" :key="doc" :value="doc">{{ doc }}</option>
            </select>
            <button
              v-if="dokter.dpjp"
              type="button"
              class="btn-clear-dpjp"
              title="Kosongkan DPJP"
              aria-label="Kosongkan DPJP"
              @click="dokter.dpjp = ''"
            >
              &times;
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Centered Save Button Footer -->
    <div class="dokter-submit-footer">
      <button type="button" class="cw-btn-primary btn-save-clinical btn-save-dokter" @click="handleSave">
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
.dokter-assessment-wrap {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.dokter-panel {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  overflow: hidden;
}

.dokter-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding: 0.78rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft);
  flex-wrap: wrap;
}

.dokter-panel-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: 0.015em;
}

.dokter-panel-body {
  padding: 1.15rem 1.25rem;
}

.dokter-two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem 2rem;
  align-items: start;
}

.dokter-col {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.dokter-h-row {
  display: grid;
  grid-template-columns: 115px 1fr;
  gap: 0.75rem;
  align-items: center;
}

.dokter-h-row--textarea {
  align-items: start;
}

.dokter-h-label {
  font-size: 0.84rem;
  font-weight: 600;
  color: var(--color-text-navy);
  text-align: right;
}

.dokter-h-row--textarea .dokter-h-label {
  padding-top: 0.55rem;
}

.dokter-col .cw-textarea {
  min-height: 62px;
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
  min-width: 78px;
  padding: 0 0.7rem;
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

.readonly-metric-input {
  background-color: #f1f5f9 !important;
  color: var(--color-text-secondary) !important;
  cursor: default;
}

/* DPJP Selector */
.dpjp-field-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.dpjp-select-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.dpjp-select {
  padding-right: 2.5rem;
}

.btn-clear-dpjp {
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

.btn-clear-dpjp:hover {
  color: var(--color-danger);
}

.dokter-submit-footer {
  display: flex;
  justify-content: center;
}

.btn-save-dokter {
  min-width: 124px;
}

.btn-save-dokter svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

@media (max-width: 960px) {
  .dokter-two-col {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}

@media (max-width: 640px) {
  .dokter-h-row {
    grid-template-columns: 1fr;
    gap: 0.35rem;
  }

  .dokter-h-label {
    text-align: left;
  }

  .dokter-h-row--textarea .dokter-h-label {
    padding-top: 0;
  }
}
</style>

