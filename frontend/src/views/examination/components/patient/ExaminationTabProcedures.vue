<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  procedures: {
    type: Array,
    default: () => []
  },
  procedureCatalog: {
    type: Array,
    default: () => []
  },
  packageCatalog: {
    type: Array,
    default: () => []
  },
  doctors: {
    type: Array,
    default: () => []
  },
  paramedics: {
    type: Array,
    default: () => []
  },
  defaultDoctor: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['add-procedure', 'remove-procedure', 'save-procedures']);

const selectedMode = ref('Satuan');
const searchQuery = ref('');
const showSuggestions = ref(false);
const searchBoxRef = ref(null);

const doctorOptions = computed(() => {
  const list = new Set([...props.doctors]);
  if (props.defaultDoctor && props.defaultDoctor !== '-') {
    list.add(props.defaultDoctor);
  }
  return Array.from(list);
});

const activeCatalog = computed(() => {
  return selectedMode.value === 'Paket' ? props.packageCatalog : props.procedureCatalog;
});

const filteredItems = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return activeCatalog.value;
  return activeCatalog.value.filter(
    (item) => item.code.toLowerCase().includes(q) || item.name.toLowerCase().includes(q)
  );
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(Number(val) || 0);
};

const pickItem = (item) => {
  emit('add-procedure', {
    id: `PR-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
    code: item.code,
    name: item.name,
    mode: selectedMode.value,
    paramedic: props.paramedics[0] || 'Perawat Poliklinik',
    doctor: props.defaultDoctor && props.defaultDoctor !== '-' ? props.defaultDoctor : (doctorOptions.value[0] || '-'),
    quantity: 1,
    tariff: item.tariff,
    subtotal: item.tariff
  });
  searchQuery.value = '';
  showSuggestions.value = false;
};

const handleSearchTrigger = () => {
  if (searchQuery.value.trim() && filteredItems.value.length > 0 && showSuggestions.value) {
    pickItem(filteredItems.value[0]);
    return;
  }
  showSuggestions.value = !showSuggestions.value;
};

const handleDocumentClick = (e) => {
  if (searchBoxRef.value && !searchBoxRef.value.contains(e.target)) {
    showSuggestions.value = false;
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
  <div class="clinical-workspace-panel proc-page-wrap">
    <div class="proc-panel">
      <div class="proc-panel-header">
        <span class="proc-panel-title">TINDAKAN</span>
      </div>

      <div class="proc-panel-body">
        <!-- Search & Satuan/Paket Mode Toolbar -->
        <div class="proc-toolbar-row">
          <div ref="searchBoxRef" class="proc-search-box">
            <div class="proc-input-group">
              <input
                id="cari-tindakan"
                v-model="searchQuery"
                type="text"
                class="cw-input proc-search-input"
                placeholder="Cari Tindakan"
                autocomplete="off"
                @focus="showSuggestions = true"
                @input="showSuggestions = true"
                @keydown.enter.prevent="handleSearchTrigger"
              />
              <button
                type="button"
                class="btn-proc-search btn-search-procedure"
                aria-label="Cari Tindakan"
                @click="handleSearchTrigger"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
              </button>
            </div>

            <div v-if="showSuggestions" class="proc-suggestion-menu">
              <div v-if="filteredItems.length === 0" class="proc-suggestion-empty">
                Tindakan ({{ selectedMode }}) tidak ditemukan.
              </div>
              <button
                v-for="item in filteredItems"
                :key="item.code"
                type="button"
                class="proc-suggestion-item"
                @click="pickItem(item)"
              >
                <span class="proc-code-badge font-mono">{{ item.code }}</span>
                <span class="proc-name-text">{{ item.name }}</span>
                <span class="proc-price-hint font-mono">{{ formatCurrency(item.tariff) }}</span>
              </button>
            </div>
          </div>

          <div class="mode-select-box">
            <div class="mode-select-wrap">
              <select
                id="procedure-mode-select"
                v-model="selectedMode"
                class="cw-select mode-select-control"
                aria-label="Jenis Tindakan"
                @change="showSuggestions = false"
              >
                <option value="Satuan">Satuan</option>
                <option value="Paket">Paket</option>
              </select>
              <button
                v-if="selectedMode !== 'Satuan'"
                type="button"
                class="btn-reset-mode"
                title="Kembalikan ke Satuan"
                aria-label="Reset ke Satuan"
                @click="selectedMode = 'Satuan'"
              >
                &times;
              </button>
              <svg class="mode-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </div>
        </div>

        <!-- 4-Column Tindakan Table -->
        <div class="cw-table-wrap">
          <table class="cw-table proc-table">
            <thead>
              <tr>
                <th>Tindakan</th>
                <th>Paramedis</th>
                <th>Dokter</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="procedures.length === 0">
                <td colspan="4" class="proc-empty-cell">Belum ada tindakan yang dipilih.</td>
              </tr>
              <tr v-for="proc in procedures" v-else :key="proc.id">
                <td>
                  <div class="proc-cell-main">
                    <span class="proc-title-line">{{ proc.name }}</span>
                    <div class="proc-meta-line">
                      <span class="cw-pill cw-pill--primary">{{ proc.mode || 'Satuan' }}</span>
                      <span class="font-mono proc-tariff-text">{{ formatCurrency(proc.tariff) }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <select v-model="proc.paramedic" class="cw-select row-select-control" aria-label="Pilih Paramedis">
                    <option value="-">-</option>
                    <option v-for="pm in paramedics" :key="pm" :value="pm">{{ pm }}</option>
                  </select>
                </td>
                <td>
                  <select v-model="proc.doctor" class="cw-select row-select-control" aria-label="Pilih Dokter">
                    <option value="-">-</option>
                    <option v-for="doc in doctorOptions" :key="doc" :value="doc">{{ doc }}</option>
                  </select>
                </td>
                <td>
                  <button
                    type="button"
                    class="cw-btn-danger-icon"
                    @click="emit('remove-procedure', proc.id)"
                  >
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
    <div class="proc-submit-footer">
      <button type="button" class="cw-btn-primary btn-save-procedures" @click="emit('save-procedures')">
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
.proc-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.proc-panel {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
}

.proc-panel-header {
  padding: 0.78rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft);
  border-radius: 10px 10px 0 0;
}

.proc-panel-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: 0.015em;
}

.proc-panel-body {
  padding: 1.15rem 1.25rem;
}

.proc-toolbar-row {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.proc-search-box {
  position: relative;
  flex: 1 1 320px;
  max-width: 440px;
}

.proc-input-group {
  display: flex;
  align-items: stretch;
}

.proc-search-input {
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
}

.btn-proc-search {
  width: 42px;
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

.btn-proc-search:hover {
  background: #f1f5f9;
}

.btn-proc-search svg {
  width: 15px;
  height: 15px;
}

.proc-suggestion-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  min-width: 340px;
  max-height: 230px;
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

.proc-suggestion-empty {
  padding: 0.65rem 0.75rem;
  font-size: 0.84rem;
  color: var(--color-text-secondary);
}

.proc-suggestion-item {
  width: 100%;
  text-align: left;
  background: transparent;
  border: none;
  border-radius: 7px;
  padding: 0.55rem 0.7rem;
  display: flex;
  align-items: center;
  gap: 0.55rem;
  cursor: pointer;
  font-family: inherit;
  transition: background-color 0.12s ease;
}

.proc-suggestion-item:hover {
  background: var(--color-primary-light);
}

.proc-code-badge {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-primary-dark);
  background: var(--color-primary-light);
  padding: 0.08rem 0.42rem;
  border-radius: 4px;
  flex-shrink: 0;
}

.proc-name-text {
  flex: 1;
  font-size: 0.84rem;
  color: var(--color-text-navy);
}

.proc-price-hint {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  white-space: nowrap;
}

/* Mode Selector */
.mode-select-box {
  width: 175px;
  flex-shrink: 0;
}

.mode-select-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.mode-select-control {
  appearance: none;
  -webkit-appearance: none;
  padding-right: 2.5rem !important;
  cursor: pointer;
}

.btn-reset-mode {
  position: absolute;
  right: 1.85rem;
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

.btn-reset-mode:hover {
  color: var(--color-danger);
}

.mode-chevron {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 15px;
  height: 15px;
  color: var(--color-text-secondary);
  pointer-events: none;
}

/* Table Cells */
.proc-empty-cell {
  text-align: center;
  color: var(--color-text-secondary);
  padding: 1rem !important;
}

.proc-cell-main {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.proc-title-line {
  font-weight: 600;
  color: var(--color-text-navy);
}

.proc-meta-line {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.proc-tariff-text {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.row-select-control {
  height: 36px !important;
  font-size: 0.83rem !important;
  min-width: 180px;
}

.proc-submit-footer {
  display: flex;
  justify-content: center;
}

.btn-save-procedures {
  min-width: 124px;
}

.btn-save-procedures svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

@media (max-width: 640px) {
  .proc-search-box,
  .mode-select-box {
    width: 100%;
    max-width: none;
  }
}
</style>

