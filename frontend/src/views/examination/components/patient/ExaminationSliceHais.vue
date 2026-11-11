<script setup>
import { ref, computed, watch } from 'vue';
import ClinicalSelect from './ClinicalSelect.vue';
import HaisLogDataTable from './HaisLogDataTable.vue';

const props = defineProps({
  hais: {
    type: Object,
    required: true
  },
  surgeries: {
    type: Object,
    default: () => ({ requests: [] })
  }
});

const emit = defineEmits(['save']);

const pad2 = (n) => String(n).padStart(2, '0');
const formatNowDMY = () => {
  const now = new Date();
  return `${pad2(now.getDate())}-${pad2(now.getMonth() + 1)}-${now.getFullYear()}`;
};

const infectionSignOptions = [
  'Kemerahan',
  'Purulent dari tempat insisi',
  'Nyeri',
  'Panas',
  'Bengkak',
  'Kultur positif',
  'Dokter menyatakan IDO'
];

const infectionStatus = ref('Tidak ada');
const selectedInfectionSigns = ref([]);

const toggleInfectionSign = (sign) => {
  if (selectedInfectionSigns.value.includes(sign)) {
    selectedInfectionSigns.value = selectedInfectionSigns.value.filter((s) => s !== sign);
  } else {
    selectedInfectionSigns.value = [...selectedInfectionSigns.value, sign];
    infectionStatus.value = 'Ada';
  }
};

watch(infectionStatus, (val) => {
  if (val === 'Tidak ada' && selectedInfectionSigns.value.length > 0) {
    selectedInfectionSigns.value = [];
  }
});

const latestSurgery = computed(() => {
  const list = Array.isArray(props.surgeries?.requests) ? props.surgeries.requests : [];
  return list.length > 0 ? list[list.length - 1] : null;
});

const latestSurgeryName = computed(() => latestSurgery.value?.procedure_name || '');
const latestSurgeryDate = computed(() => latestSurgery.value?.schedule || latestSurgery.value?.planned_date || '');

const handleSaveMainInfection = () => {
  const valueText =
    infectionStatus.value === 'Ada' && selectedInfectionSigns.value.length > 0
      ? `Ada (${selectedInfectionSigns.value.join(', ')})`
      : infectionStatus.value;

  const entry = {
    id: `HAIS-${Date.now()}-${Math.random().toString(36).slice(2, 5)}`,
    date: formatNowDMY(),
    variabel: 'Tanda Infeksi',
    value: valueText
  };

  props.hais.logs = [entry, ...(props.hais.logs || [])];
  emit('save');
};

const removeMainLog = (id) => {
  props.hais.logs = (props.hais.logs || []).filter((item) => item.id !== id);
  emit('save');
};

// 6 Expandable HAIs Device & Procedure Surveillance Modules
const clinicalStatusOptions = ['Ya', 'Tidak'];

const haisModulesConfig = [
  {
    id: 'infus',
    title: '1. TERPASANG INFUS / INTRA VENA',
    signColumnTitle: 'Tanda Klinis Plebitis',
    signs: [
      'Suhu > 38°C',
      'Kemerahan',
      'Bercak',
      'Sakit / Ulkus Sampai Eksudet Purulen'
    ]
  },
  {
    id: 'kateter',
    title: '2. TERPASANG KATETER URINE (DOWER CATETER)',
    signColumnTitle: 'Tanda Klinis ISK',
    signs: [
      { key: 'heading-dewasa', no: '', label: 'Dewasa', isHeading: true, selectable: false },
      { key: 'group-gejala', no: '1.', label: 'Terdapat Suatu Gejala Dari', isGroupHeader: true, selectable: false },
      { key: 'demam', no: '', label: 'a. Demam Suhu > 38°C', selectable: true },
      { key: 'nokturia', no: '', label: 'b. Nokturia', selectable: true },
      { key: 'disuria', no: '', label: 'c. Disuria', selectable: true },
      { key: 'nyeri-suprapubik', no: '', label: 'd. Nyeri Supra Publik', selectable: true },
      { key: 'piuri', no: '2.', label: 'Piuri (Leukosit Urine > 10/ml)', selectable: true },
      {
        key: 'kultur-urin',
        no: '3.',
        label: 'Kultur Urin Positif > 10⁵ CFU/ml',
        subtitle: 'Dengan 1 Atau 2 Jenis Kuman',
        variabelLabel: 'Kultur Urin Positif > 10⁵ CFU/ml Dengan 1 Atau 2 Jenis Kuman',
        selectable: true
      }
    ]
  },
  {
    id: 'tirah_baring',
    title: '3. TIRAH BARING',
    signColumnTitle: 'Tanda Klinis Decubitus',
    signs: [
      'Kemerahan',
      'Melepuh',
      'Lecet / Luka'
    ]
  },
  {
    id: 'cvp',
    title: '4. TERPASANG VENA CENTRAL / PRIMER (CVP)',
    signColumnTitle: 'Tanda Klinis IADP',
    signs: [
      { key: 'heading-umur', no: '', label: 'Umur > 1 th', isHeading: true, selectable: false },
      {
        key: 'patogen-biakan',
        no: '1.',
        label: 'Ditemukan patogen dari biakan spesimen darah dari kateter intravaskuler. Dan darah perifer tidak berkaitan dengan infeksi lain.',
        selectable: true
      },
      {
        key: 'group-gejala-cvp',
        no: '2.',
        label: 'Ditemukan minimal satu gejala:',
        subtitle: '(Tanpa penyebab infeksi lain)',
        isGroupHeader: true,
        selectable: false
      },
      { key: 'cvp-demam', no: '', label: 'a. Demam (suhu > 38°C)', selectable: true },
      { key: 'cvp-menggigil', no: '', label: 'b. Menggigil', selectable: true },
      { key: 'cvp-hipotensi', no: '', label: 'c. Hipotensi', selectable: true }
    ]
  },
  {
    id: 'ett',
    title: '5. TERPASANG ETT / VENTILATOR',
    signColumnTitle: 'Tanda Klinis VAP',
    signs: [
      {
        key: 'group-gejala-ett',
        no: '1.',
        label: 'Ditemukan minimal satu dari gejala:',
        subtitle: '(Tanpa penyebab infeksi lain)',
        isGroupHeader: true,
        selectable: false
      },
      { key: 'ett-demam', no: '', label: 'a. Demam (Suhu > 38°C)', selectable: true },
      { key: 'ett-leukopenia', no: '', label: 'b. Leukopenia (< 4000 WBC/mm³)', selectable: true },
      { key: 'ett-leukositosis', no: '', label: 'c. Leukositosis (> 12.000 SDP/mm³)', selectable: true },
      { key: 'ett-batuk', no: '', label: 'd. Batuk/dyspnoe/tachipnoe', selectable: true },
      {
        key: 'group-disertai-ett',
        no: '2.',
        label: 'Dan disertai 2 dari tanda berikut:',
        isGroupHeader: true,
        selectable: false
      },
      { key: 'ett-sputum', no: '', label: 'a. Timbul onset baru sputum purulent/perubahan sputum', selectable: true },
      { key: 'ett-fio2', no: '', label: 'b. Peningkatan FIO2 (> 0,2 dari sebelumnya)', selectable: true },
      {
        key: 'ett-peep',
        no: '',
        label: 'c. Peningkatan PEEP setiap hari',
        subtitle: '(> 3 cm H2O dari PEEP sebelumnya) selama 2 hari berturut-turut',
        variabelLabel: 'c. Peningkatan PEEP setiap hari (> 3 cm H2O dari PEEP sebelumnya) selama 2 hari berturut-turut',
        selectable: true
      },
      { key: 'ett-kultur', no: '3.', label: 'Kultur Aspirasi Trakheal > 10 mm', selectable: true }
    ]
  },
  {
    id: 'operasi',
    title: '6. TINDAKAN OPERASI',
    signColumnTitle: 'Tanda Klinis IDO',
    hideSignTableHeader: true,
    signs: [
      {
        key: 'heading-ido-superficial',
        no: '',
        label: 'A. IDO SUPERFICIAL',
        subtitle: 'Tanda-tanda klinis:',
        rightDescription: 'Infeksi daerah operasi yang terjadi setelah 30 hari setelah operasi dan mengenai kulit dan jaringan sub kutan; Dengan gejala sbb:',
        isHeading: true,
        selectable: false
      },
      { key: 'ido-sup-nanah', no: '1.', label: 'Nanah purulent dari tempat insisi', variabelLabel: 'IDO Superficial - Nanah purulent dari tempat insisi', selectable: true },
      { key: 'ido-sup-group2', no: '2.', label: 'Atau terdapat paling sedikit satu keadaan berikut', isGroupHeader: true, selectable: false },
      { key: 'ido-sup-bengkak', no: '', label: 'a. Bengkak', variabelLabel: 'IDO Superficial - Bengkak', selectable: true },
      { key: 'ido-sup-kemerahan', no: '', label: 'b. Kemerahan', variabelLabel: 'IDO Superficial - Kemerahan', selectable: true },
      { key: 'ido-sup-nyeri', no: '', label: 'c. Nyeri', variabelLabel: 'IDO Superficial - Nyeri', selectable: true },
      { key: 'ido-sup-panas', no: '', label: 'd. Panas', variabelLabel: 'IDO Superficial - Panas', selectable: true },
      { key: 'ido-sup-kultur', no: '3.', label: 'Kultur/biakan positif dari cairan luka/jaringan yang diambil', variabelLabel: 'IDO Superficial - Kultur/biakan positif dari cairan luka/jaringan', selectable: true },
      { key: 'ido-sup-dr', no: '4.', label: 'Dr menyatakan IDO', variabelLabel: 'IDO Superficial - Dr menyatakan IDO', selectable: true },
      {
        key: 'heading-ido-profunda',
        no: '',
        label: 'B. IDO DALAM (PROFUNDA)',
        subtitle: 'Tanda-tanda klinis:',
        rightDescription: 'Infeksi daerah operasi yang terjadi 30 hari pasca bedah (tanpa implan) atau sampai satu tahun dengan implan dan meliputi jaringan lunak yang dalam (fascia dan otot) dengan kriteria sbb:',
        isHeading: true,
        selectable: false
      },
      { key: 'ido-prof-group1', no: '1.', label: 'Terdapat paling sedikit satu dari:', isGroupHeader: true, selectable: false },
      { key: 'ido-prof-pus', no: '', label: 'a. Pus keluar dari luka insisi dalam (tapi bukan dari organ/rongga)', variabelLabel: 'IDO Profunda - Pus keluar dari luka insisi dalam', selectable: true },
      {
        key: 'ido-prof-dehiscens',
        no: '',
        label: 'b. Insisi dalam spontan mengalami dehiscens/dengan sengaja dibuka, terdapat gejala berikut Demam (> 38 C), nyeri lokal, bila biakan negatif',
        variabelLabel: 'IDO Profunda - Insisi dalam spontan mengalami dehiscens',
        selectable: true
      },
      {
        key: 'ido-prof-abses',
        no: '',
        label: 'c. Ditemukan abses/bukti lain adanya infeksi pada pemeriksaan langsung, pembedahan ulang/pemeriksaan histopatologi/radiologis',
        variabelLabel: 'IDO Profunda - Ditemukan abses/bukti lain adanya infeksi',
        selectable: true
      },
      { key: 'ido-prof-dr', no: '2.', label: 'Dr menyatakan IDO', variabelLabel: 'IDO Profunda - Dr menyatakan IDO', selectable: true },
      {
        key: 'heading-ido-organ',
        no: '',
        label: 'C. IDO ORGAN/RONGGA',
        subtitle: 'Tanda-tanda klinis:',
        rightDescription: 'Infeksi yang terjadi 30 hari post operasi tanpa implan atau satu tahun dengan implant, menyangkut bagian tubuh (kecuali insisi kulit, fasia, lapisan otot yang dibuka/manipulasi selama tindakan operasi), terdapat paling sedikit satu tanda sbb:',
        isHeading: true,
        selectable: false
      },
      { key: 'ido-org-group1', no: '1.', label: 'Paling sedikit ada satu gejala:', isGroupHeader: true, selectable: false },
      { key: 'ido-org-drainase', no: '', label: 'a. Terdapat Drainase purulent yang berasal dari drain yang ditempatkan pada rongga/organ', variabelLabel: 'IDO Organ/Rongga - Terdapat Drainase purulent', selectable: true },
      { key: 'ido-org-biakan', no: '', label: 'b. Biakan/kultur positif dari specimen (cairan dari luka/jaringan organ/rongga terkait)', variabelLabel: 'IDO Organ/Rongga - Biakan/kultur positif dari specimen', selectable: true },
      { key: 'ido-org-tanda', no: '', label: 'c. Adanya tanda infeksi yang dibuktikan dengan langsung/inflasi/histopatologi atau rongten', variabelLabel: 'IDO Organ/Rongga - Adanya tanda infeksi', selectable: true },
      { key: 'ido-org-dr', no: '', label: 'd. Dr menyatakan IDO', variabelLabel: 'IDO Organ/Rongga - Dr menyatakan IDO', selectable: true }
    ]
  }
];

const getNormalizedSigns = (mod) => {
  return (mod.signs || []).map((item, idx) => {
    if (typeof item === 'string') {
      return {
        key: item,
        no: `${idx + 1}.`,
        label: item,
        subtitle: '',
        variabelLabel: item,
        isHeading: false,
        isGroupHeader: false,
        selectable: true
      };
    }
    return {
      key: item.key || item.label,
      no: item.no ?? '',
      label: item.label,
      subtitle: item.subtitle || '',
      variabelLabel: item.variabelLabel || item.label,
      rightDescription: item.rightDescription || '',
      isHeading: Boolean(item.isHeading),
      isGroupHeader: Boolean(item.isGroupHeader),
      selectable: item.selectable !== false
    };
  });
};

const openSectionId = ref(null);
const moduleErrors = ref({});
const moduleSignDrafts = ref(
  Object.fromEntries(
    haisModulesConfig.map((m) => [m.id, m.signs.map(() => '')])
  )
);

const toggleModule = (id) => {
  openSectionId.value = openSectionId.value === id ? null : id;
};

const ensureModulesBucket = () => {
  if (!props.hais.modules || typeof props.hais.modules !== 'object') {
    props.hais.modules = {};
  }
};

const getModuleLogs = (modId) => {
  return Array.isArray(props.hais.modules?.[modId]) ? props.hais.modules[modId] : [];
};

const handleSaveModule = (mod) => {
  ensureModulesBucket();
  const selections = moduleSignDrafts.value[mod.id] || [];
  const rows = getNormalizedSigns(mod);
  const chosenEntries = rows
    .map((row, idx) => ({
      row,
      status: selections[idx]
    }))
    .filter((it) => it.row.selectable && Boolean(it.status));

  if (chosenEntries.length === 0) {
    moduleErrors.value = {
      ...moduleErrors.value,
      [mod.id]: 'Pilih minimal 1 status tanda klinis sebelum menyimpan.'
    };
    return;
  }

  moduleErrors.value = { ...moduleErrors.value, [mod.id]: '' };
  const dateStr = formatNowDMY();
  const newRows = chosenEntries.map((item, idx) => ({
    id: `HAIS-${mod.id.toUpperCase()}-${Date.now()}-${idx}`,
    date: dateStr,
    variabel: item.row.variabelLabel,
    value: item.status
  }));

  const currentList = getModuleLogs(mod.id);
  props.hais.modules[mod.id] = [...newRows, ...currentList];
  moduleSignDrafts.value[mod.id] = mod.signs.map(() => '');
  emit('save');
};

const removeModuleLog = (modId, id) => {
  ensureModulesBucket();
  props.hais.modules[modId] = getModuleLogs(modId).filter((item) => item.id !== id);
  emit('save');
};
</script>

<template>
  <div class="hais-page-wrap">
    <!-- Main Card: MONITORING HAIS -->
    <div class="hais-card">
      <div class="hais-card-header">
        <span class="hais-card-title">MONITORING HAIS</span>
      </div>

      <div class="hais-card-body">
        <div class="hais-split-grid">
          <!-- Left Column: Info + Tanda Infeksi Checklist + Simpan -->
          <div class="hais-left-col">
            <div class="hais-kv-row">
              <span class="hais-kv-label">Jenis Tindakan Operasi</span>
              <span class="hais-kv-colon">:</span>
              <span class="hais-kv-val">{{ latestSurgeryName }}</span>
            </div>

            <div class="hais-kv-row">
              <span class="hais-kv-label">Tanggal Operasi</span>
              <span class="hais-kv-colon">:</span>
              <span class="hais-kv-val font-mono">{{ latestSurgeryDate }}</span>
            </div>

            <div class="hais-kv-row hais-kv-row--top">
              <span class="hais-kv-label">Tanda Infeksi</span>
              <span class="hais-kv-colon">:</span>
              <div class="hais-infeksi-box">
                <div class="hais-radio-row">
                  <label class="hais-radio-opt">
                    <input
                      v-model="infectionStatus"
                      type="radio"
                      name="tanda-infeksi-status"
                      value="Tidak ada"
                      class="hais-radio-input"
                    />
                    <span>Tidak ada</span>
                  </label>

                  <label class="hais-radio-opt">
                    <input
                      v-model="infectionStatus"
                      type="radio"
                      name="tanda-infeksi-status"
                      value="Ada"
                      class="hais-radio-input"
                    />
                    <span>Ada</span>
                  </label>
                </div>

                <div class="hais-signs-stack">
                  <label
                    v-for="sign in infectionSignOptions"
                    :key="sign"
                    class="hais-sign-row"
                  >
                    <input
                      type="checkbox"
                      class="hais-checkbox"
                      :value="sign"
                      :checked="selectedInfectionSigns.includes(sign)"
                      @change="toggleInfectionSign(sign)"
                    />
                    <span>{{ sign }}</span>
                  </label>
                </div>
              </div>
            </div>

            <div class="hais-left-actions">
              <button
                type="button"
                class="cw-btn-primary btn-save-hais-main"
                @click="handleSaveMainInfection"
              >
                Simpan
              </button>
            </div>
          </div>

          <!-- Right Column: Sortable DataTable (No | Tanggal | Variabel | Value | Act) -->
          <div class="hais-right-col">
            <HaisLogDataTable
              id-prefix="hais"
              :rows="hais.logs || []"
              @remove="removeMainLog"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- 6 Expandable HAIs Device & Procedure Accordion Sections -->
    <div
      v-for="mod in haisModulesConfig"
      :key="mod.id"
      class="hais-accordion-card"
      :data-hais-module="mod.id"
    >
      <button
        type="button"
        class="hais-accordion-header"
        :aria-expanded="openSectionId === mod.id"
        @click="toggleModule(mod.id)"
      >
        <span class="hais-accordion-title">{{ mod.title }}</span>
        <span class="hais-accordion-icon">{{ openSectionId === mod.id ? '−' : '+' }}</span>
      </button>

      <div v-if="openSectionId === mod.id" class="hais-accordion-body">
        <!-- Nested Sub-Card Matching Reference Structure -->
        <div class="hais-subcard">
          <div class="hais-subcard-header">
            <span class="hais-subcard-title">{{ mod.title }}</span>
          </div>

          <div class="hais-subcard-body">
            <!-- Top Module History DataTable -->
            <HaisLogDataTable
              :id-prefix="`hais-${mod.id}`"
              :rows="getModuleLogs(mod.id)"
              @remove="(id) => removeModuleLog(mod.id, id)"
            />

            <hr class="hais-subcard-divider" />

            <!-- Clinical Signs Table (No | Tanda Klinis ... | Aksi) -->
            <div class="hais-signs-eval-wrap">
              <table class="cw-table hais-signs-eval-table">
                <thead v-if="!mod.hideSignTableHeader">
                  <tr>
                    <th class="col-sign-no">No</th>
                    <th>{{ mod.signColumnTitle }}</th>
                    <th class="col-sign-aksi">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(signRow, idx) in getNormalizedSigns(mod)" :key="signRow.key">
                    <td class="font-mono col-sign-no">{{ signRow.no }}</td>
                    <td>
                      <span :class="{ 'hais-sign-heading': signRow.isHeading }">{{ signRow.label }}</span>
                      <span v-if="signRow.subtitle" class="hais-sign-subtitle">{{ signRow.subtitle }}</span>
                    </td>
                    <td class="col-sign-aksi">
                      <ClinicalSelect
                        v-if="signRow.selectable"
                        :id="`hais-${mod.id}-sign-${idx}`"
                        v-model="moduleSignDrafts[mod.id][idx]"
                        :options="clinicalStatusOptions"
                        placeholder="-- Pilih Status--"
                      />
                      <span v-else-if="signRow.rightDescription" class="hais-sign-right-desc">
                        {{ signRow.rightDescription }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p v-if="moduleErrors[mod.id]" class="hais-mod-err">{{ moduleErrors[mod.id] }}</p>

            <div class="hais-mod-actions">
              <button
                type="button"
                class="cw-btn-primary btn-save-hais-module"
                @click="handleSaveModule(mod)"
              >
                Simpan
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.hais-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.hais-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: visible;
}

.hais-card-header {
  display: flex;
  align-items: center;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.85rem 1.25rem;
  border-radius: 8px 8px 0 0;
}

.hais-card-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.hais-card-body {
  padding: 1.25rem;
}

.hais-split-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
  gap: 1.75rem;
  align-items: start;
}

.hais-left-col {
  display: flex;
  flex-direction: column;
}

.hais-kv-row {
  display: grid;
  grid-template-columns: 165px 14px minmax(0, 1fr);
  align-items: center;
  padding: 0.58rem 0;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.85rem;
  color: #334155;
}

.hais-kv-row--top {
  align-items: flex-start;
  padding-top: 0.75rem;
  padding-bottom: 0.85rem;
}

.hais-kv-label {
  font-weight: 600;
  color: #1e293b;
}

.hais-kv-colon {
  font-weight: 600;
  color: #64748b;
}

.hais-kv-val {
  color: #334155;
  min-height: 1.25rem;
}

.hais-infeksi-box {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.hais-radio-row {
  display: inline-flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.hais-radio-opt {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  font-weight: 400;
  color: #334155;
  cursor: pointer;
}

.hais-radio-input {
  width: 15px;
  height: 15px;
  margin: 0;
  accent-color: #0d9488;
  cursor: pointer;
}

.hais-signs-stack {
  display: flex;
  flex-direction: column;
  gap: 0.48rem;
}

.hais-sign-row {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  font-size: 0.84rem;
  font-weight: 400;
  color: #334155;
  cursor: pointer;
  user-select: none;
}

.hais-checkbox {
  width: 15px;
  height: 15px;
  margin: 0;
  flex-shrink: 0;
  accent-color: #0d9488;
  cursor: pointer;
  border-radius: 3px;
}

.hais-left-actions {
  padding-top: 1rem;
}

.btn-save-hais-main,
.btn-save-hais-module {
  min-width: 96px;
  height: 38px;
  padding: 0 1.2rem;
  font-size: 0.85rem;
  font-weight: 600;
}

.hais-right-col {
  min-width: 0;
}

/* 6 Accordion Cards */
.hais-accordion-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  overflow: visible;
}

.hais-accordion-header {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f1f5f9;
  border: none;
  padding: 0.78rem 1.2rem;
  cursor: pointer;
  text-align: left;
  border-radius: 6px;
  transition: background-color 0.15s ease;
}

.hais-accordion-header[aria-expanded='true'] {
  border-radius: 6px 6px 0 0;
  border-bottom: 1px solid #e2e8f0;
}

.hais-accordion-header:hover {
  background: #e2e8f0;
}

.hais-accordion-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
  letter-spacing: 0.02em;
}

.hais-accordion-icon {
  font-size: 1rem;
  font-weight: 700;
  color: #64748b;
}

.hais-accordion-body {
  padding: 1.15rem 1.25rem;
}

/* Nested Subcard inside expanded module */
.hais-subcard {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  overflow: visible;
}

.hais-subcard-header {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.75rem 1.15rem;
  border-radius: 6px 6px 0 0;
}

.hais-subcard-title {
  font-size: 0.84rem;
  font-weight: 700;
  color: #334155;
}

.hais-subcard-body {
  padding: 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.hais-subcard-divider {
  border: none;
  border-top: 1px solid #e2e8f0;
  margin: 0.2rem 0;
}

.hais-signs-eval-wrap {
  width: 100%;
  overflow: visible;
}

.hais-signs-eval-table {
  width: 100%;
  border-collapse: collapse;
}

.hais-signs-eval-table th {
  font-size: 0.83rem;
  font-weight: 700;
  color: #334155;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.6rem 0.75rem;
  text-align: left;
}

.hais-signs-eval-table td {
  font-size: 0.85rem;
  color: #334155;
  padding: 0.65rem 0.75rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.col-sign-no {
  width: 68px;
}

.hais-sign-heading {
  font-weight: 700;
  color: #1e293b;
}

.hais-sign-subtitle {
  display: block;
  margin-top: 0.2rem;
  color: #475569;
}

.hais-sign-right-desc {
  display: block;
  font-size: 0.83rem;
  line-height: 1.45;
  color: #334155;
}

.col-sign-aksi {
  width: 440px;
}

.hais-mod-err {
  color: #dc2626;
  font-size: 0.83rem;
  font-weight: 600;
  margin: 0;
}

.hais-mod-actions {
  display: flex;
  justify-content: flex-start;
  padding-top: 0.25rem;
}

@media (max-width: 960px) {
  .hais-split-grid {
    grid-template-columns: minmax(0, 1fr);
    gap: 1.25rem;
  }

  .col-sign-aksi {
    width: 190px;
  }
}
</style>
