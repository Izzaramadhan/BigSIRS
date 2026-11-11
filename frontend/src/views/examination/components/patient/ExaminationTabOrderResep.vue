<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import ClinicalSelect from './ClinicalSelect.vue';

const props = defineProps({
  orders: {
    type: Object,
    required: true
  },
  medicinesCatalog: {
    type: Array,
    default: () => []
  },
  packageCatalog: {
    type: Array,
    default: () => []
  },
  prescriptionHistory: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['add-order', 'remove-order', 'save-resep']);

const warehouseOptions = ['Gudang Utama', 'Depo Farmasi Rawat Jalan', 'Depo IGD'];
const modeOptions = ['Satuan', 'Paket'];
const signaOptions = ['3 x 1', '2 x 1', '1 x 1', '3 x 1/2', 'Prn'];
const usageRuleOptions = [
  'Sesudah makan',
  'Sebelum makan',
  'Saat makan',
  'Pagi hari',
  'Malam sebelum tidur',
  'Pemakaian luar'
];

// Top history toggle state
const showHistory = ref(false);

// Top RESEP search & selectors
const resepSearch = ref('');
const resepWarehouse = ref('Gudang Utama');
const resepMode = ref('Satuan');
const showResepSuggestions = ref(false);
const resepSearchRef = ref(null);

// Racikan draft state
const racikanErr = ref('');
const racikanSearch = ref('');
const racikanWarehouse = ref('Gudang Utama');
const showRacikanSuggestions = ref(false);
const racikanSearchRef = ref(null);

const racikanDraft = ref({
  name: '',
  signa: '',
  qty: '',
  usage_rule: '',
  note: '',
  ingredients: []
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(Number(val) || 0);
};

const activeResepCatalog = computed(() => {
  return resepMode.value === 'Paket' ? props.packageCatalog : props.medicinesCatalog;
});

const filteredResepItems = computed(() => {
  const q = resepSearch.value.trim().toLowerCase();
  return activeResepCatalog.value.filter((item) => {
    const matchText = !q || item.code.toLowerCase().includes(q) || item.name.toLowerCase().includes(q);
    return matchText;
  });
});

const filteredRacikanMeds = computed(() => {
  const q = racikanSearch.value.trim().toLowerCase();
  if (!q) return props.medicinesCatalog;
  return props.medicinesCatalog.filter(
    (item) => item.code.toLowerCase().includes(q) || item.name.toLowerCase().includes(q)
  );
});

const racikanComputedPrice = computed(() => {
  const ingSum = racikanDraft.value.ingredients.reduce(
    (sum, item) => sum + (Number(item.qty) || 0) * (Number(item.price) || 0),
    0
  );
  if (ingSum <= 0) return '';
  return formatCurrency(ingSum);
});

const pickResepItem = (item) => {
  emit('add-order', {
    bucket: 'pharmacy',
    entry: {
      id: `RX-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
      code: item.code,
      name: item.name,
      signa: item.defaultSigna || '3 x 1',
      qty: resepMode.value === 'Paket' ? 1 : 10,
      unit: item.unit || 'Tablet',
      usage_rule: item.usageRule || 'Sesudah makan',
      note: '',
      warehouse: resepWarehouse.value,
      mode: resepMode.value,
      price: item.price || 12500
    }
  });
  resepSearch.value = '';
  showResepSuggestions.value = false;
};

const handleSearchResep = () => {
  if (resepSearch.value.trim() && filteredResepItems.value.length > 0 && showResepSuggestions.value) {
    pickResepItem(filteredResepItems.value[0]);
    return;
  }
  showResepSuggestions.value = !showResepSuggestions.value;
};

const pickRacikanIngredient = (item) => {
  const existing = racikanDraft.value.ingredients.find((i) => i.code === item.code);
  if (existing) {
    existing.qty = (Number(existing.qty) || 1) + 1;
  } else {
    racikanDraft.value.ingredients.push({
      id: `ING-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
      code: item.code,
      name: item.name,
      qty: 1,
      unit: item.unit || 'Tablet',
      price: item.price || 3500,
      warehouse: racikanWarehouse.value
    });
  }
  racikanSearch.value = '';
  showRacikanSuggestions.value = false;
};

const handleSearchRacikan = () => {
  if (racikanSearch.value.trim() && filteredRacikanMeds.value.length > 0 && showRacikanSuggestions.value) {
    pickRacikanIngredient(filteredRacikanMeds.value[0]);
    return;
  }
  showRacikanSuggestions.value = !showRacikanSuggestions.value;
};

const removeRacikanIngredient = (id) => {
  racikanDraft.value.ingredients = racikanDraft.value.ingredients.filter((i) => i.id !== id);
};

const handleAddRacikanOrder = () => {
  if (!racikanDraft.value.name.trim()) {
    racikanErr.value = 'Nama racikan wajib diisi sebelum menambahkan ke list order racikan.';
    return;
  }
  if (racikanDraft.value.ingredients.length === 0) {
    racikanErr.value = 'Pilih minimal 1 obat komposisi racikan pada tabel di sebelah kanan.';
    return;
  }
  racikanErr.value = '';

  const totalIngredientCost = racikanDraft.value.ingredients.reduce(
    (sum, item) => sum + (Number(item.qty) || 0) * (Number(item.price) || 0),
    0
  );

  emit('add-order', {
    bucket: 'racikan',
    entry: {
      id: `RCK-${Date.now()}`,
      name: racikanDraft.value.name.trim(),
      signa: racikanDraft.value.signa || '3 x 1',
      qty: Math.max(1, Number(racikanDraft.value.qty) || 1),
      usage_rule: racikanDraft.value.usage_rule || 'Sesudah makan',
      note: racikanDraft.value.note.trim() || '-',
      total_price: totalIngredientCost,
      ingredients: racikanDraft.value.ingredients.map((i) => ({ ...i }))
    }
  });

  racikanDraft.value = {
    name: '',
    signa: '',
    qty: '',
    usage_rule: '',
    note: '',
    ingredients: []
  };
};

const copyHistoryToResep = (hist) => {
  hist.items.forEach((item) => {
    emit('add-order', {
      bucket: 'pharmacy',
      entry: {
        id: `RX-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
        code: item.code,
        name: item.name,
        signa: item.signa,
        qty: item.qty,
        unit: item.unit,
        usage_rule: item.usageRule || 'Sesudah makan',
        note: `Salinan riwayat (${hist.date})`,
        warehouse: 'Gudang Utama',
        mode: 'Satuan',
        price: item.price || 12500
      }
    });
  });
};

const handleDocumentClick = (e) => {
  if (resepSearchRef.value && !resepSearchRef.value.contains(e.target)) {
    showResepSuggestions.value = false;
  }
  if (racikanSearchRef.value && !racikanSearchRef.value.contains(e.target)) {
    showRacikanSuggestions.value = false;
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
  <div class="clinical-workspace-panel resep-page-wrap">
    <!-- Top History Toggle Strip -->
    <div class="history-action-strip">
      <div class="history-btns-row">
        <button
          type="button"
          class="btn-history-show"
          @click="showHistory = true"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="8" y1="6" x2="21" y2="6"></line>
            <line x1="8" y1="12" x2="21" y2="12"></line>
            <line x1="8" y1="18" x2="21" y2="18"></line>
            <line x1="3" y1="6" x2="3.01" y2="6"></line>
            <line x1="3" y1="12" x2="3.01" y2="12"></line>
            <line x1="3" y1="18" x2="3.01" y2="18"></line>
          </svg>
          <span>Tampilkan Riwayat</span>
        </button>

        <button
          type="button"
          class="btn-history-hide"
          @click="showHistory = false"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
          <span>Sembunyikan Riwayat</span>
        </button>
      </div>

      <div v-if="showHistory" class="rx-history-drawer">
        <div v-if="prescriptionHistory.length === 0" class="cw-empty-state">
          Belum ada riwayat order resep terdahulu untuk pasien ini.
        </div>
        <div v-else class="rx-history-cards">
          <div v-for="hist in prescriptionHistory" :key="hist.id" class="rx-hist-item">
            <div class="rx-hist-head">
              <div>
                <span class="font-mono rx-hist-date">{{ hist.date }}</span>
                <span class="rx-hist-unit">({{ hist.unit }})</span>
                <span class="rx-hist-doc">&bull; {{ hist.doctor }}</span>
              </div>
              <button
                type="button"
                class="cw-btn-secondary btn-copy-history"
                @click="copyHistoryToResep(hist)"
              >
                Salin ke Resep
              </button>
            </div>
            <ul class="rx-hist-list">
              <li v-for="(it, idx) in hist.items" :key="idx">
                <strong>{{ it.name }}</strong> &mdash; {{ it.qty }} {{ it.unit }} ({{ it.signa }}, {{ it.usageRule }})
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Panel 1: RESEP -->
    <div class="resep-card">
      <div class="resep-card-header">
        <span class="resep-card-title">RESEP</span>
      </div>

      <div class="resep-card-body">
        <!-- Toolbar Row: Cari Obat + Gudang Utama + Satuan/Paket -->
        <div class="resep-toolbar-row">
          <div ref="resepSearchRef" class="obat-search-box">
            <div class="obat-input-group">
              <input
                id="cari-obat-resep"
                v-model="resepSearch"
                type="text"
                class="cw-input obat-search-input"
                placeholder="Cari Obat"
                autocomplete="off"
                @focus="showResepSuggestions = true"
                @input="showResepSuggestions = true"
                @keydown.enter.prevent="handleSearchResep"
              />
              <button
                type="button"
                class="btn-obat-search btn-search-resep"
                aria-label="Cari Obat Resep"
                @click="handleSearchResep"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
              </button>
            </div>

            <div v-if="showResepSuggestions" class="obat-suggestion-menu">
              <div v-if="filteredResepItems.length === 0" class="obat-suggestion-empty">
                Obat / Paket tidak ditemukan.
              </div>
              <button
                v-for="item in filteredResepItems"
                :key="item.code"
                type="button"
                class="obat-suggestion-item resep-suggestion-item"
                @click="pickResepItem(item)"
              >
                <span class="obat-code-badge font-mono">{{ item.code }}</span>
                <span class="obat-name-text">{{ item.name }}</span>
                <span class="obat-price-hint font-mono">{{ formatCurrency(item.price) }}</span>
              </button>
            </div>
          </div>

          <div class="toolbar-select-col">
            <ClinicalSelect
              id="resep-warehouse-select"
              v-model="resepWarehouse"
              :options="warehouseOptions"
            />
          </div>

          <div class="toolbar-select-col toolbar-select-col--mode">
            <ClinicalSelect
              id="resep-mode-select"
              v-model="resepMode"
              :options="modeOptions"
            />
          </div>
        </div>

        <!-- 7-Column Resep Table -->
        <div class="cw-table-wrap">
          <table class="cw-table resep-table">
            <thead>
              <tr>
                <th>Nama Obat</th>
                <th>Signa</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Aturan Pakai</th>
                <th>Catatan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="orders.pharmacy.length === 0">
                <td colspan="7" class="resep-empty-cell">Data Tidak Ada</td>
              </tr>
              <tr v-for="rx in orders.pharmacy" v-else :key="rx.id">
                <td>
                  <div class="rx-name-cell">
                    <span class="font-semibold">{{ rx.name }}</span>
                    <span class="rx-sub-meta">{{ rx.warehouse || 'Gudang Utama' }} &bull; {{ rx.mode || 'Satuan' }}</span>
                  </div>
                </td>
                <td>
                  <select v-model="rx.signa" class="cw-select table-inline-ctrl" aria-label="Signa">
                    <option v-for="sg in signaOptions" :key="sg" :value="sg">{{ sg }}</option>
                  </select>
                </td>
                <td>
                  <input
                    v-model.number="rx.qty"
                    type="number"
                    min="1"
                    class="cw-input font-mono table-inline-qty"
                    aria-label="Jumlah Obat"
                  />
                </td>
                <td>{{ rx.unit }}</td>
                <td>
                  <select v-model="rx.usage_rule" class="cw-select table-inline-ctrl" aria-label="Aturan Pakai">
                    <option v-for="ur in usageRuleOptions" :key="ur" :value="ur">{{ ur }}</option>
                  </select>
                </td>
                <td>
                  <input
                    v-model="rx.note"
                    type="text"
                    class="cw-input table-inline-ctrl"
                    placeholder="Catatan..."
                    aria-label="Catatan Obat"
                  />
                </td>
                <td>
                  <button
                    type="button"
                    class="cw-btn-danger-icon"
                    @click="emit('remove-order', { bucket: 'pharmacy', id: rx.id })"
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

    <!-- Section 2: List Order Racikan Strip -->
    <div class="resep-card">
      <div class="racikan-strip-header">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3" y="3" width="7" height="7"></rect>
          <rect x="14" y="3" width="7" height="7"></rect>
          <rect x="14" y="14" width="7" height="7"></rect>
          <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span class="racikan-strip-title">List Order Racikan</span>
        <span v-if="orders.racikan?.length" class="cw-pill cw-pill--primary">
          {{ orders.racikan.length }} Racikan
        </span>
      </div>

      <div v-if="orders.racikan?.length > 0" class="resep-card-body">
        <div class="cw-table-wrap">
          <table class="cw-table racikan-list-table">
            <thead>
              <tr>
                <th>Nama Racikan</th>
                <th>Signa</th>
                <th>Jumlah</th>
                <th>Aturan Pakai</th>
                <th>Komposisi Obat</th>
                <th>Estimasi Harga</th>
                <th>Catatan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="rck in orders.racikan" :key="rck.id">
                <td class="font-semibold">{{ rck.name }}</td>
                <td>{{ rck.signa }}</td>
                <td class="font-mono">{{ rck.qty }}</td>
                <td>{{ rck.usage_rule }}</td>
                <td>
                  <div class="racikan-ing-summary">
                    <span v-for="ing in rck.ingredients" :key="ing.id">
                      {{ ing.name }} ({{ ing.qty }} {{ ing.unit }})
                    </span>
                  </div>
                </td>
                <td class="font-mono font-bold">{{ formatCurrency(rck.total_price) }}</td>
                <td>{{ rck.note }}</td>
                <td>
                  <button
                    type="button"
                    class="cw-btn-danger-icon"
                    @click="emit('remove-order', { bucket: 'racikan', id: rck.id })"
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

    <!-- Add Racikan Action Button -->
    <div class="racikan-add-bar">
      <button
        type="button"
        class="btn-add-racikan"
        @click="handleAddRacikanOrder"
      >
        Tambah
      </button>
      <span v-if="racikanErr" class="racikan-err-text">{{ racikanErr }}</span>
    </div>

    <!-- Section 3: FORM RACIKAN -->
    <div class="resep-card">
      <div class="resep-card-header racikan-form-header">
        <div class="header-title-with-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          <span class="resep-card-title">FORM RACIKAN</span>
        </div>
      </div>

      <div class="resep-card-body">
        <div class="racikan-2col-grid">
          <!-- Left Column: Form Inputs -->
          <div class="racikan-left-col">
            <div class="rck-h-row">
              <label class="cw-label" for="racikan-name">Nama</label>
              <input
                id="racikan-name"
                v-model="racikanDraft.name"
                type="text"
                class="cw-input"
                placeholder="Nama Racikan"
              />
            </div>

            <div class="rck-h-row">
              <label class="cw-label" for="racikan-signa">Signa</label>
              <select id="racikan-signa" v-model="racikanDraft.signa" class="cw-select">
                <option value="">-- pilih --</option>
                <option v-for="sg in signaOptions" :key="sg" :value="sg">{{ sg }}</option>
              </select>
            </div>

            <div class="rck-h-row">
              <label class="cw-label" for="racikan-qty">Jumlah</label>
              <input
                id="racikan-qty"
                v-model="racikanDraft.qty"
                type="number"
                min="1"
                class="cw-input font-mono"
                placeholder="Jumlah"
              />
            </div>

            <div class="rck-h-row">
              <label class="cw-label" for="racikan-price">Harga</label>
              <input
                id="racikan-price"
                :value="racikanComputedPrice"
                type="text"
                readonly
                class="cw-input font-mono readonly-price-input"
                placeholder="Harga"
              />
            </div>

            <div class="rck-h-row">
              <label class="cw-label" for="racikan-usage">Aturan Pakai</label>
              <select id="racikan-usage" v-model="racikanDraft.usage_rule" class="cw-select">
                <option value="">---- Silahkan Pilih ----</option>
                <option v-for="ur in usageRuleOptions" :key="ur" :value="ur">{{ ur }}</option>
              </select>
            </div>

            <div class="rck-h-row rck-h-row--top">
              <label class="cw-label" for="racikan-note">Catatan</label>
              <textarea
                id="racikan-note"
                v-model="racikanDraft.note"
                rows="2"
                class="cw-textarea"
                placeholder="Catatan"
              ></textarea>
            </div>
          </div>

          <!-- Right Column: Ingredient Search & Composition Table -->
          <div class="racikan-right-col">
            <div class="racikan-med-toolbar">
              <div ref="racikanSearchRef" class="obat-search-box">
                <div class="obat-input-group">
                  <input
                    id="cari-obat-racikan"
                    v-model="racikanSearch"
                    type="text"
                    class="cw-input obat-search-input"
                    placeholder="Cari Obat"
                    autocomplete="off"
                    @focus="showRacikanSuggestions = true"
                    @input="showRacikanSuggestions = true"
                    @keydown.enter.prevent="handleSearchRacikan"
                  />
                  <button
                    type="button"
                    class="btn-obat-search btn-search-racikan"
                    aria-label="Cari Obat Racikan"
                    @click="handleSearchRacikan"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="11" cy="11" r="8"></circle>
                      <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                  </button>
                </div>

                <div v-if="showRacikanSuggestions" class="obat-suggestion-menu">
                  <div v-if="filteredRacikanMeds.length === 0" class="obat-suggestion-empty">
                    Obat tidak ditemukan.
                  </div>
                  <button
                    v-for="item in filteredRacikanMeds"
                    :key="item.code"
                    type="button"
                    class="obat-suggestion-item racikan-suggestion-item"
                    @click="pickRacikanIngredient(item)"
                  >
                    <span class="obat-code-badge font-mono">{{ item.code }}</span>
                    <span class="obat-name-text">{{ item.name }}</span>
                    <span class="obat-price-hint font-mono">{{ formatCurrency(item.price) }}</span>
                  </button>
                </div>
              </div>

              <div class="toolbar-select-col">
                <ClinicalSelect
                  id="racikan-warehouse-select"
                  v-model="racikanWarehouse"
                  :options="warehouseOptions"
                />
              </div>
            </div>

            <div class="cw-table-wrap">
              <table class="cw-table racikan-comp-table">
                <thead>
                  <tr>
                    <th>OBAT</th>
                    <th>JUMLAH</th>
                    <th>SUB TOTAL</th>
                    <th class="col-hash">#</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="racikanDraft.ingredients.length === 0">
                    <td colspan="4" class="resep-empty-cell">Belum ada obat komposisi racikan dipilih.</td>
                  </tr>
                  <tr v-for="ing in racikanDraft.ingredients" v-else :key="ing.id">
                    <td>
                      <div class="rx-name-cell">
                        <span class="font-semibold">{{ ing.name }}</span>
                        <span class="rx-sub-meta font-mono">{{ formatCurrency(ing.price) }} / {{ ing.unit }}</span>
                      </div>
                    </td>
                    <td>
                      <input
                        v-model.number="ing.qty"
                        type="number"
                        min="1"
                        class="cw-input font-mono table-inline-qty"
                        aria-label="Jumlah Komposisi Obat"
                      />
                    </td>
                    <td class="font-mono font-bold">
                      {{ formatCurrency((Number(ing.qty) || 0) * (Number(ing.price) || 0)) }}
                    </td>
                    <td class="col-hash">
                      <button
                        type="button"
                        class="cw-btn-danger-icon"
                        @click="removeRacikanIngredient(ing.id)"
                      >
                        &minus;
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Centered Save Footer Button -->
    <div class="resep-submit-footer">
      <button type="button" class="cw-btn-primary btn-save-resep" @click="emit('save-resep')">
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
.resep-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 1.05rem;
}

/* Top History Toggle Strip */
.history-action-strip {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  padding: 0.85rem 1.15rem;
}

.history-btns-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.btn-history-show,
.btn-history-hide {
  height: 38px;
  padding: 0 1rem;
  border: none;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  cursor: pointer;
  font-family: inherit;
  transition: opacity 0.15s ease, transform 0.12s ease;
}

.btn-history-show {
  background: #2563eb;
}

.btn-history-hide {
  background: #f59e0b;
}

.btn-history-show:hover,
.btn-history-hide:hover {
  opacity: 0.92;
  transform: translateY(-1px);
}

.btn-history-show svg,
.btn-history-hide svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

.rx-history-drawer {
  margin-top: 0.95rem;
  padding-top: 0.9rem;
  border-top: 1px solid var(--color-border-soft);
}

.rx-history-cards {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.rx-hist-item {
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  padding: 0.8rem 1rem;
}

.rx-hist-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.45rem;
  flex-wrap: wrap;
}

.rx-hist-date {
  font-size: 0.84rem;
  font-weight: 700;
  color: #0284c7;
}

.rx-hist-unit,
.rx-hist-doc {
  font-size: 0.82rem;
  color: var(--color-text-secondary);
  margin-left: 0.35rem;
}

.btn-copy-history {
  height: 32px !important;
  font-size: 0.8rem !important;
  padding: 0 0.75rem !important;
}

.rx-hist-list {
  margin: 0;
  padding-left: 1.25rem;
  font-size: 0.84rem;
  color: var(--color-text-navy);
  line-height: 1.5;
}

/* Shared Resep / Racikan Card */
.resep-card {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  overflow: visible;
}

.resep-card-header,
.racikan-strip-header {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.78rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft);
  border-radius: 10px 10px 0 0;
}

.racikan-strip-header {
  border-radius: 10px;
  border-bottom: none;
}

.resep-card:has(.resep-card-body) .racikan-strip-header {
  border-radius: 10px 10px 0 0;
  border-bottom: 1px solid var(--color-border-soft);
}

.racikan-strip-header svg,
.header-title-with-icon svg {
  width: 16px;
  height: 16px;
  color: var(--color-text-secondary);
  flex-shrink: 0;
}

.header-title-with-icon {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.resep-card-title,
.racikan-strip-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: 0.015em;
}

.racikan-strip-title {
  font-size: 0.88rem;
  font-weight: 600;
}

.resep-card-body {
  padding: 1.15rem 1.25rem;
  border-radius: 0 0 10px 10px;
}

/* Toolbars */
.resep-toolbar-row,
.racikan-med-toolbar {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.obat-search-box {
  position: relative;
  flex: 1 1 290px;
}

.obat-input-group {
  display: flex;
  align-items: stretch;
}

.obat-search-input {
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
}

.btn-obat-search {
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

.btn-obat-search:hover {
  background: #f1f5f9;
}

.btn-obat-search svg {
  width: 15px;
  height: 15px;
}

.obat-suggestion-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  min-width: 320px;
  max-height: 220px;
  overflow-y: auto;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 9px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
  padding: 0.35rem;
  z-index: 45;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.obat-suggestion-empty {
  padding: 0.65rem 0.75rem;
  font-size: 0.84rem;
  color: var(--color-text-secondary);
}

.obat-suggestion-item {
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

.obat-suggestion-item:hover {
  background: var(--color-primary-light);
}

.obat-code-badge {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-primary-dark);
  background: var(--color-primary-light);
  padding: 0.08rem 0.42rem;
  border-radius: 4px;
  flex-shrink: 0;
}

.obat-name-text {
  flex: 1;
  font-size: 0.84rem;
  color: var(--color-text-navy);
}

.obat-price-hint {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  white-space: nowrap;
}

.toolbar-select-col {
  width: 220px;
  flex-shrink: 0;
}

.toolbar-select-col--mode {
  width: 160px;
}

/* Tables */
.resep-empty-cell {
  text-align: center;
  color: var(--color-text-secondary);
  padding: 0.95rem !important;
}

.rx-name-cell {
  display: flex;
  flex-direction: column;
  gap: 0.18rem;
}

.rx-sub-meta {
  font-size: 0.78rem;
  color: var(--color-text-secondary);
}

.table-inline-ctrl {
  height: 34px !important;
  font-size: 0.82rem !important;
  min-width: 120px;
}

.table-inline-qty {
  height: 34px !important;
  font-size: 0.82rem !important;
  width: 72px;
  text-align: center;
}

.racikan-ing-summary {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  font-size: 0.82rem;
}

/* Add Racikan Bar */
.racikan-add-bar {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  flex-wrap: wrap;
}

.btn-add-racikan {
  height: 38px;
  padding: 0 1.15rem;
  background: #10b981;
  color: #ffffff;
  border: 1px solid #059669;
  border-radius: 8px;
  font-size: 0.86rem;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: background-color 0.15s ease, transform 0.12s ease;
}

.btn-add-racikan:hover {
  background: #059669;
  transform: translateY(-1px);
}

.racikan-err-text {
  font-size: 0.82rem;
  font-weight: 500;
  color: var(--color-danger);
}

/* Form Racikan 2-Column Layout */
.racikan-2col-grid {
  display: grid;
  grid-template-columns: minmax(280px, 37%) 1fr;
  gap: 1.5rem;
  align-items: start;
}

.racikan-left-col {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.rck-h-row {
  display: grid;
  grid-template-columns: 95px 1fr;
  gap: 0.75rem;
  align-items: center;
}

.rck-h-row--top {
  align-items: start;
}

.rck-h-row--top .cw-label {
  padding-top: 0.55rem;
}

.readonly-price-input {
  background-color: #f1f5f9 !important;
  color: var(--color-text-secondary) !important;
  cursor: default;
}

.col-hash {
  width: 50px;
  text-align: center;
}

.resep-submit-footer {
  display: flex;
  justify-content: center;
  padding-top: 0.2rem;
}

.btn-save-resep {
  min-width: 124px;
}

.btn-save-resep svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

@media (max-width: 992px) {
  .racikan-2col-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .obat-search-box,
  .toolbar-select-col,
  .toolbar-select-col--mode {
    width: 100%;
    max-width: none;
  }

  .rck-h-row {
    grid-template-columns: 1fr;
    gap: 0.35rem;
  }
}
</style>

