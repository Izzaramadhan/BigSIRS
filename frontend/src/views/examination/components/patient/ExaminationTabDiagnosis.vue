<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  initialDiagnosis: {
    type: String,
    default: ''
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
  icd10Catalog: {
    type: Array,
    default: () => []
  },
  icd9Catalog: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits([
  'add-diagnosis',
  'remove-diagnosis',
  'add-icd9',
  'remove-icd9',
  'save-diagnosis'
]);

const icd10Query = ref('');
const icd9Query = ref('');
const showIcd10Dropdown = ref(false);
const showIcd9Dropdown = ref(false);
const icd10BoxRef = ref(null);
const icd9BoxRef = ref(null);

const filteredIcd10 = computed(() => {
  const q = icd10Query.value.trim().toLowerCase();
  if (!q) return props.icd10Catalog;
  return props.icd10Catalog.filter(
    (item) => item.code.toLowerCase().includes(q) || item.name.toLowerCase().includes(q)
  );
});

const filteredIcd9 = computed(() => {
  const q = icd9Query.value.trim().toLowerCase();
  if (!q) return props.icd9Catalog;
  return props.icd9Catalog.filter(
    (item) => item.code.toLowerCase().includes(q) || item.name.toLowerCase().includes(q)
  );
});

const pickIcd10 = (item) => {
  emit('add-diagnosis', {
    id: `DX-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
    code: item.code,
    name: item.name,
    note: '',
    case_type: 'Kasus Baru',
    type: props.diagnoses.length === 0 ? 'Utama' : 'Sekunder',
    category: 'Diagnosis Kerja'
  });
  icd10Query.value = '';
  showIcd10Dropdown.value = false;
};

const pickIcd9 = (item) => {
  emit('add-icd9', {
    id: `ICD9-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
    code: item.code,
    name: item.name,
    note: ''
  });
  icd9Query.value = '';
  showIcd9Dropdown.value = false;
};

const handleSearchIcd10 = () => {
  if (icd10Query.value.trim() && filteredIcd10.value.length > 0 && showIcd10Dropdown.value) {
    pickIcd10(filteredIcd10.value[0]);
    return;
  }
  showIcd10Dropdown.value = !showIcd10Dropdown.value;
};

const handleSearchIcd9 = () => {
  if (icd9Query.value.trim() && filteredIcd9.value.length > 0 && showIcd9Dropdown.value) {
    pickIcd9(filteredIcd9.value[0]);
    return;
  }
  showIcd9Dropdown.value = !showIcd9Dropdown.value;
};

const handleDocumentClick = (e) => {
  if (icd10BoxRef.value && !icd10BoxRef.value.contains(e.target)) {
    showIcd10Dropdown.value = false;
  }
  if (icd9BoxRef.value && !icd9BoxRef.value.contains(e.target)) {
    showIcd9Dropdown.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
  <div class="clinical-workspace-panel dx-page-wrap">
    <!-- Top Section: DIAGNOSIS Card -->
    <div class="dx-panel">
      <div class="dx-panel-header">
        <span class="dx-panel-title">DIAGNOSIS</span>
      </div>

      <div class="dx-panel-body">
        <!-- Initial Diagnosis Banner -->
        <div class="initial-dx-bar">
          <span class="initial-dx-label">Diagnosis Awal:</span>
          <span class="initial-dx-value">{{ initialDiagnosis || '-' }}</span>
        </div>

        <!-- Stacked Narrative Fields -->
        <div class="dx-narrative-stack">
          <div class="cw-field">
            <label class="cw-label" for="dx-working-diagnosis">Diagnosis Kerja:</label>
            <textarea
              id="dx-working-diagnosis"
              v-model="diagnosisForm.working_diagnosis"
              rows="2"
              class="cw-textarea"
              placeholder="Diagnosis"
            ></textarea>
          </div>

          <div class="cw-field">
            <label class="cw-label" for="dx-final-diagnosis">Diagnosis Akhir:</label>
            <textarea
              id="dx-final-diagnosis"
              v-model="diagnosisForm.final_diagnosis"
              rows="2"
              class="cw-textarea"
              placeholder="Diagnosis (Text Bebas)"
            ></textarea>
          </div>

          <div class="cw-field">
            <label class="cw-label" for="dx-procedure-narrative">Narasi Tindakan:</label>
            <textarea
              id="dx-procedure-narrative"
              v-model="diagnosisForm.procedure_narrative"
              rows="2"
              class="cw-textarea"
              placeholder="Narasi Tindakan"
            ></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Sub-Panel 1: FORM ICD-10 -->
    <div class="dx-panel dx-icd-card">
      <div class="dx-icd-header">
        <svg class="icd-gear-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="3"></circle>
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
        </svg>
        <span class="dx-icd-title">FORM ICD-10</span>
      </div>

      <div class="dx-panel-body">
        <!-- Cari ICD Search Input -->
        <div ref="icd10BoxRef" class="icd-search-wrap icd-search-wrap--compact">
          <div class="icd-input-group">
            <input
              id="cari-icd10"
              v-model="icd10Query"
              type="text"
              class="cw-input icd-search-input"
              placeholder="Cari ICD"
              autocomplete="off"
              @focus="showIcd10Dropdown = true"
              @input="showIcd10Dropdown = true"
              @keydown.enter.prevent="handleSearchIcd10"
            />
            <button
              type="button"
              class="btn-icd-search btn-search-icd10"
              aria-label="Cari ICD-10"
              @click="handleSearchIcd10"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
            </button>
          </div>

          <div v-if="showIcd10Dropdown" class="icd-suggestion-menu">
            <div v-if="filteredIcd10.length === 0" class="icd-suggestion-empty">
              Kode ICD-10 tidak ditemukan.
            </div>
            <button
              v-for="item in filteredIcd10"
              :key="item.code"
              type="button"
              class="icd-suggestion-item icd10-suggestion-item"
              @click="pickIcd10(item)"
            >
              <span class="icd-code-badge font-mono">{{ item.code }}</span>
              <span class="icd-name-text">{{ item.name }}</span>
            </button>
          </div>
        </div>

        <!-- 7-Column ICD-10 Table -->
        <div class="cw-table-wrap icd-table-wrap">
          <table class="cw-table icd10-table">
            <thead>
              <tr>
                <th>ICD</th>
                <th>Nama</th>
                <th>Catatan</th>
                <th>Kasus</th>
                <th>Jenis</th>
                <th>Kategori</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="diagnoses.length === 0">
                <td colspan="7" class="icd-empty-cell">Data Tidak Ada</td>
              </tr>
              <tr v-for="dx in diagnoses" v-else :key="dx.id">
                <td class="font-mono font-bold">{{ dx.code }}</td>
                <td>{{ dx.name }}</td>
                <td>
                  <input
                    v-model="dx.note"
                    type="text"
                    class="cw-input table-inline-input"
                    placeholder="Catatan diagnosis..."
                  />
                </td>
                <td>
                  <select v-model="dx.case_type" class="cw-select table-inline-select">
                    <option value="Kasus Baru">Kasus Baru</option>
                    <option value="Kasus Lama">Kasus Lama</option>
                  </select>
                </td>
                <td>
                  <select v-model="dx.type" class="cw-select table-inline-select">
                    <option value="Utama">Utama</option>
                    <option value="Sekunder">Sekunder</option>
                  </select>
                </td>
                <td>
                  <select v-model="dx.category" class="cw-select table-inline-select">
                    <option value="Diagnosis Kerja">Diagnosis Kerja</option>
                    <option value="Diagnosis Akhir">Diagnosis Akhir</option>
                  </select>
                </td>
                <td>
                  <button type="button" class="cw-btn-danger-icon" @click="emit('remove-diagnosis', dx.id)">
                    Hapus
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Sub-Panel 2: FORM ICD-9 -->
    <div class="dx-panel dx-icd-card">
      <div class="dx-icd-header">
        <svg class="icd-gear-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="3"></circle>
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
        </svg>
        <span class="dx-icd-title">FORM ICD-9</span>
      </div>

      <div class="dx-panel-body">
        <!-- Full-Width Cari ICD-9 Search Input -->
        <div ref="icd9BoxRef" class="icd-search-wrap">
          <div class="icd-input-group">
            <input
              id="cari-icd9"
              v-model="icd9Query"
              type="text"
              class="cw-input icd-search-input"
              placeholder="Cari ICD-9"
              autocomplete="off"
              @focus="showIcd9Dropdown = true"
              @input="showIcd9Dropdown = true"
              @keydown.enter.prevent="handleSearchIcd9"
            />
            <button
              type="button"
              class="btn-icd-search btn-search-icd9"
              aria-label="Cari ICD-9"
              @click="handleSearchIcd9"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
            </button>
          </div>

          <div v-if="showIcd9Dropdown" class="icd-suggestion-menu">
            <div v-if="filteredIcd9.length === 0" class="icd-suggestion-empty">
              Kode ICD-9 tidak ditemukan.
            </div>
            <button
              v-for="item in filteredIcd9"
              :key="item.code"
              type="button"
              class="icd-suggestion-item icd9-suggestion-item"
              @click="pickIcd9(item)"
            >
              <span class="icd-code-badge font-mono">{{ item.code }}</span>
              <span class="icd-name-text">{{ item.name }}</span>
            </button>
          </div>
        </div>

        <!-- ICD-9 Empty / Table State -->
        <div v-if="icd9List.length === 0" class="icd9-empty-bar">
          Tidak Ada Data.
        </div>

        <div v-else class="cw-table-wrap icd-table-wrap">
          <table class="cw-table icd9-table">
            <thead>
              <tr>
                <th>ICD-9</th>
                <th>Nama Prosedur / Tindakan</th>
                <th>Catatan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="proc in icd9List" :key="proc.id">
                <td class="font-mono font-bold">{{ proc.code }}</td>
                <td>{{ proc.name }}</td>
                <td>
                  <input
                    v-model="proc.note"
                    type="text"
                    class="cw-input table-inline-input"
                    placeholder="Catatan prosedur..."
                  />
                </td>
                <td>
                  <button type="button" class="cw-btn-danger-icon" @click="emit('remove-icd9', proc.id)">
                    Hapus
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Centered Save Button Footer -->
    <div class="dx-submit-footer">
      <button type="button" class="cw-btn-primary btn-save-diagnosis" @click="emit('save-diagnosis')">
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
.dx-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.dx-panel {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
}

.dx-panel-header {
  padding: 0.78rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft);
  border-radius: 10px 10px 0 0;
}

.dx-panel-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: 0.015em;
}

.dx-panel-body {
  padding: 1.15rem 1.25rem;
}

.initial-dx-bar {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.65rem 0.95rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  background: #f8fafc;
  font-size: 0.86rem;
  margin-bottom: 1rem;
}

.initial-dx-label {
  font-weight: 700;
  color: var(--color-text-navy);
}

.initial-dx-value {
  color: var(--color-text-secondary);
}

.dx-narrative-stack {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

/* ICD Card Header */
.dx-icd-header {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.85rem 1.25rem 0;
}

.icd-gear-icon {
  width: 17px;
  height: 17px;
  color: #dc2626;
  flex-shrink: 0;
}

.dx-icd-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #dc2626;
  letter-spacing: 0.015em;
}

/* Search Box + Suggestion Popover */
.icd-search-wrap {
  position: relative;
  margin-bottom: 0.9rem;
}

.icd-search-wrap--compact {
  max-width: 340px;
}

.icd-input-group {
  display: flex;
  align-items: stretch;
}

.icd-search-input {
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
}

.btn-icd-search {
  width: 40px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  color: var(--color-text-navy);
  border: 1px solid var(--color-border-soft);
  border-left: none;
  border-radius: 0 8px 8px 0;
  cursor: pointer;
  flex-shrink: 0;
  transition: background-color 0.15s ease;
}

.btn-icd-search:hover {
  background: #f1f5f9;
}

.btn-icd-search svg {
  width: 15px;
  height: 15px;
}

.icd-suggestion-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  min-width: 340px;
  max-height: 220px;
  overflow-y: auto;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 9px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
  padding: 0.35rem;
  z-index: 35;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.icd-suggestion-empty {
  padding: 0.65rem 0.75rem;
  font-size: 0.84rem;
  color: var(--color-text-secondary);
}

.icd-suggestion-item {
  width: 100%;
  text-align: left;
  background: transparent;
  border: none;
  border-radius: 7px;
  padding: 0.55rem 0.7rem;
  display: flex;
  align-items: baseline;
  gap: 0.55rem;
  cursor: pointer;
  font-family: inherit;
  transition: background-color 0.12s ease;
}

.icd-suggestion-item:hover {
  background: var(--color-primary-light);
}

.icd-code-badge {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--color-primary-dark);
  background: var(--color-primary-light);
  padding: 0.08rem 0.45rem;
  border-radius: 4px;
  flex-shrink: 0;
}

.icd-name-text {
  font-size: 0.84rem;
  color: var(--color-text-navy);
}

.icd-empty-cell {
  text-align: center;
  color: var(--color-text-secondary);
  padding: 0.95rem !important;
}

.icd9-empty-bar {
  text-align: center;
  padding: 0.8rem 1rem;
  border-top: 1px solid var(--color-border-soft);
  border-bottom: 1px solid var(--color-border-soft);
  background: #f8fafc;
  color: var(--color-text-secondary);
  font-size: 0.85rem;
}

.table-inline-input,
.table-inline-select {
  height: 34px !important;
  font-size: 0.82rem !important;
  min-width: 120px;
}

.dx-submit-footer {
  display: flex;
  justify-content: center;
  padding-top: 0.2rem;
}

.btn-save-diagnosis {
  min-width: 124px;
}

.btn-save-diagnosis svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

.font-bold {
  font-weight: 700;
}
</style>

