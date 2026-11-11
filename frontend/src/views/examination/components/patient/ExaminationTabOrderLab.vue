<script setup>
import { ref, computed, watch } from 'vue';
import ClinicalSelect from './ClinicalSelect.vue';

const props = defineProps({
  orders: {
    type: Object,
    required: true
  },
  labCatalog: {
    type: Array,
    default: () => []
  },
  doctors: {
    type: Array,
    default: () => []
  },
  labDpjpDoctors: {
    type: Array,
    default: () => []
  },
  labStaff: {
    type: Array,
    default: () => []
  },
  labHistory: {
    type: Array,
    default: () => []
  },
  defaultDoctor: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['save-lab-order', 'remove-order']);

const selectedCodes = ref([]);
const doctor = ref('');
const labDpjp = ref('');
const labStaffMember = ref('');
const localErr = ref('');

const doctorOptions = computed(() => {
  const base = Array.isArray(props.doctors) ? [...props.doctors] : [];
  if (props.defaultDoctor && props.defaultDoctor !== '-' && !base.includes(props.defaultDoctor)) {
    base.unshift(props.defaultDoctor);
  }
  return base;
});

const leftCategories = computed(() => props.labCatalog.filter((cat) => cat.column === 'left'));
const rightCategories = computed(() => props.labCatalog.filter((cat) => cat.column === 'right'));

const catalogFlatMap = computed(() => {
  const map = new Map();
  props.labCatalog.forEach((cat) => {
    if (cat.items.length === 0) {
      map.set(cat.id, {
        code: cat.id,
        name: cat.category,
        category: cat.category,
        price: 150000
      });
    } else {
      cat.items.forEach((it) => {
        map.set(it.code, {
          code: it.code,
          name: it.name,
          category: cat.category,
          price: it.price || 95000
        });
      });
    }
  });
  return map;
});

const resolveEntryCode = (entry) => {
  if (entry.code && catalogFlatMap.value.has(entry.code)) return entry.code;
  for (const [k, meta] of catalogFlatMap.value.entries()) {
    if (meta.name === entry.item) return k;
  }
  return null;
};

// Hydrate selections & metadata from existing session lab orders on load or external update
watch(
  () => [props.orders?.lab, props.defaultDoctor],
  ([existingLab, defDoc], prevVal) => {
    const prevLab = prevVal?.[0];
    const prevDoc = prevVal?.[1];
    if (!doctor.value || defDoc !== prevDoc) {
      doctor.value = defDoc && defDoc !== '-' ? defDoc : doctorOptions.value[0] || '';
    }

    const nextCodes = Array.isArray(existingLab) ? existingLab.map(resolveEntryCode).filter(Boolean) : [];
    if (!prevVal) {
      selectedCodes.value = Array.from(new Set(nextCodes));
    } else {
      const prevCodes = new Set(Array.isArray(prevLab) ? prevLab.map(resolveEntryCode).filter(Boolean) : []);
      const nextSet = new Set(nextCodes);
      // Apply only external additions/removals so local unsaved toggles stay intact
      const merged = selectedCodes.value.filter((c) => !prevCodes.has(c) || nextSet.has(c));
      nextCodes.forEach((c) => {
        if (!prevCodes.has(c) && !merged.includes(c)) merged.push(c);
      });
      selectedCodes.value = merged;
    }

    if (Array.isArray(existingLab) && existingLab.length > 0) {
      const firstMeta = existingLab.find((e) => e.doctor || e.lab_dpjp || e.lab_staff);
      if (firstMeta) {
        if (firstMeta.doctor) doctor.value = firstMeta.doctor;
        if (firstMeta.lab_dpjp) labDpjp.value = firstMeta.lab_dpjp;
        if (firstMeta.lab_staff) labStaffMember.value = firstMeta.lab_staff;
      }
    }
  },
  { immediate: true }
);

const selectedRows = computed(() => {
  return selectedCodes.value
    .map((code) => catalogFlatMap.value.get(code))
    .filter(Boolean);
});

const getCategoryItemCodes = (cat) => {
  return cat.items.length === 0 ? [cat.id] : cat.items.map((it) => it.code);
};

const isCategoryAllChecked = (cat) => {
  const codes = getCategoryItemCodes(cat);
  return codes.length > 0 && codes.every((c) => selectedCodes.value.includes(c));
};

const isCategoryIndeterminate = (cat) => {
  const codes = getCategoryItemCodes(cat);
  const count = codes.filter((c) => selectedCodes.value.includes(c)).length;
  return count > 0 && count < codes.length;
};

const toggleCategory = (cat) => {
  localErr.value = '';
  const codes = getCategoryItemCodes(cat);
  if (isCategoryAllChecked(cat)) {
    selectedCodes.value = selectedCodes.value.filter((c) => !codes.includes(c));
  } else {
    const next = new Set(selectedCodes.value);
    codes.forEach((c) => next.add(c));
    selectedCodes.value = Array.from(next);
  }
};

const toggleItem = (code) => {
  localErr.value = '';
  if (selectedCodes.value.includes(code)) {
    selectedCodes.value = selectedCodes.value.filter((c) => c !== code);
  } else {
    selectedCodes.value = [...selectedCodes.value, code];
  }
};

const handleRemoveRow = (code) => {
  selectedCodes.value = selectedCodes.value.filter((c) => c !== code);
  const existing = (props.orders?.lab || []).find((entry) => entry.code === code || entry.item === catalogFlatMap.value.get(code)?.name);
  if (existing) {
    emit('remove-order', { bucket: 'lab', id: existing.id });
  }
};

const handleSaveLab = () => {
  if (selectedRows.value.length === 0) {
    localErr.value = 'Pilih minimal 1 pemeriksaan pada daftar ORDER LABORATORIUM.';
    return;
  }
  if (!doctor.value) {
    localErr.value = 'Dokter pengirim wajib dipilih.';
    return;
  }
  if (!labDpjp.value) {
    localErr.value = 'DPJP Laboratorium wajib dipilih.';
    return;
  }
  if (!labStaffMember.value) {
    localErr.value = 'Staf Laboratorium Klinis wajib dipilih.';
    return;
  }

  localErr.value = '';
  const nowTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  const entries = selectedRows.value.map((row, idx) => ({
    id: `LAB-${Date.now()}-${idx}-${row.code}`,
    code: row.code,
    item: row.name,
    group: row.name,
    category: row.category,
    price: row.price || 95000,
    priority: 'Rutin',
    doctor: doctor.value,
    lab_dpjp: labDpjp.value,
    lab_staff: labStaffMember.value,
    note: `${labStaffMember.value} (${labDpjp.value})`,
    ordered_at: nowTime
  }));

  emit('save-lab-order', {
    entries,
    doctor: doctor.value,
    lab_dpjp: labDpjp.value,
    lab_staff: labStaffMember.value
  });
};
</script>

<template>
  <div class="clinical-workspace-panel lab-page-wrap">
    <!-- Section 1: RIWAYAT LABORATORIUM -->
    <div class="lab-card">
      <div class="lab-card-header">
        <span class="lab-card-title">RIWAYAT LABORATORIUM</span>
      </div>
      <div class="lab-history-body">
        <div v-if="labHistory.length === 0" class="lab-history-empty">
          Belum ada riwayat pemeriksaan laboratorium.
        </div>
        <div v-else class="lab-history-list">
          <div v-for="hist in labHistory" :key="hist.id" class="lab-hist-entry">
            <span class="font-mono font-semibold">{{ hist.date }}</span>
            <span>&mdash; {{ hist.unit }} ({{ hist.items.join(', ') }})</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: Selected Order Summary Table (No | Grup | Kategori | Aksi) -->
    <div class="cw-table-wrap lab-summary-wrap">
      <table class="cw-table lab-summary-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Grup</th>
            <th>Kategori</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="selectedRows.length === 0">
            <td colspan="4" class="lab-empty-cell">Data Tidak Ada</td>
          </tr>
          <tr v-for="(row, idx) in selectedRows" v-else :key="row.code">
            <td class="font-mono col-no">{{ idx + 1 }}</td>
            <td class="font-semibold">{{ row.name }}</td>
            <td>{{ row.category }}</td>
            <td class="col-aksi">
              <button
                type="button"
                class="cw-btn-danger-icon"
                @click="handleRemoveRow(row.code)"
              >
                Hapus
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Section 3: ORDER LABORATORIUM -->
    <div class="lab-card">
      <div class="lab-card-header lab-card-header--order">
        <span class="lab-card-title">ORDER LABORATORIUM</span>
        <span v-if="selectedRows.length > 0" class="cw-pill cw-pill--primary font-mono">
          {{ selectedRows.length }} Dipilih
        </span>
      </div>

      <div class="lab-card-body">
        <!-- 2-Column Masonry Category Grid -->
        <div class="lab-columns-grid">
          <div class="lab-masonry-col">
            <div
              v-for="cat in leftCategories"
              :key="cat.id"
              class="lab-cat-card"
            >
              <label class="lab-check-row lab-check-row--master">
                <input
                  type="checkbox"
                  class="lab-checkbox"
                  :checked="isCategoryAllChecked(cat)"
                  :indeterminate.prop="isCategoryIndeterminate(cat)"
                  @change="toggleCategory(cat)"
                />
                <span class="lab-cat-title">{{ cat.category }}</span>
              </label>

              <div v-if="cat.items.length > 0" class="lab-children-list">
                <label
                  v-for="item in cat.items"
                  :key="item.code"
                  class="lab-check-row lab-check-row--child"
                >
                  <input
                    type="checkbox"
                    class="lab-checkbox"
                    :value="item.code"
                    :checked="selectedCodes.includes(item.code)"
                    @change="toggleItem(item.code)"
                  />
                  <span class="lab-item-label">{{ item.name }}</span>
                </label>
              </div>
            </div>
          </div>

          <div class="lab-masonry-col">
            <div
              v-for="cat in rightCategories"
              :key="cat.id"
              class="lab-cat-card"
            >
              <label class="lab-check-row lab-check-row--master">
                <input
                  type="checkbox"
                  class="lab-checkbox"
                  :checked="isCategoryAllChecked(cat)"
                  :indeterminate.prop="isCategoryIndeterminate(cat)"
                  @change="toggleCategory(cat)"
                />
                <span class="lab-cat-title">{{ cat.category }}</span>
              </label>

              <div v-if="cat.items.length > 0" class="lab-children-list">
                <label
                  v-for="item in cat.items"
                  :key="item.code"
                  class="lab-check-row lab-check-row--child"
                >
                  <input
                    type="checkbox"
                    class="lab-checkbox"
                    :value="item.code"
                    :checked="selectedCodes.includes(item.code)"
                    @change="toggleItem(item.code)"
                  />
                  <span class="lab-item-label">{{ item.name }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Required Metadata Selects -->
        <div class="lab-meta-footer">
          <div class="lab-meta-row">
            <div class="lab-meta-field">
              <label class="lab-meta-label" for="lab-doctor-select">
                Dokter <span class="cw-req">*</span>
              </label>
              <div class="lab-meta-control">
                <ClinicalSelect
                  id="lab-doctor-select"
                  v-model="doctor"
                  :options="doctorOptions"
                  placeholder="-- Pilih Dokter --"
                />
              </div>
            </div>
          </div>

          <div class="lab-meta-row lab-meta-row--two">
            <div class="lab-meta-field">
              <label class="lab-meta-label" for="lab-dpjp-select">
                DPJP Laboratorium <span class="cw-req">*</span>
              </label>
              <div class="lab-meta-control">
                <ClinicalSelect
                  id="lab-dpjp-select"
                  v-model="labDpjp"
                  :options="labDpjpDoctors"
                  placeholder="-- Pilih DPJP Laboratorium --"
                />
              </div>
            </div>

            <div class="lab-meta-field">
              <label class="lab-meta-label" for="lab-staff-select">
                Staf Laboratorium Klinis <span class="cw-req">*</span>
              </label>
              <div class="lab-meta-control">
                <ClinicalSelect
                  id="lab-staff-select"
                  v-model="labStaffMember"
                  :options="labStaff"
                  placeholder="-- Pilih Staf Laboratorium Klinis --"
                />
              </div>
            </div>
          </div>

          <p v-if="localErr" class="lab-err-text">{{ localErr }}</p>
        </div>
      </div>
    </div>

    <!-- Centered Save Button -->
    <div class="lab-submit-footer">
      <button type="button" class="cw-btn-primary btn-save-lab" @click="handleSaveLab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
.lab-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 1.05rem;
}

.lab-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: visible;
}

.lab-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.8rem 1.25rem;
  border-radius: 8px 8px 0 0;
}

.lab-card-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.lab-history-body {
  padding: 0.9rem 1.25rem;
}

.lab-history-empty {
  color: #94a3b8;
  font-size: 0.85rem;
}

.lab-history-list {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  font-size: 0.85rem;
  color: #334155;
}

.lab-summary-wrap {
  border-radius: 8px;
}

.lab-summary-table th {
  font-size: 0.82rem;
  font-weight: 700;
  color: #334155;
  background: #f1f5f9;
}

.lab-summary-table td {
  font-size: 0.85rem;
  color: #334155;
  vertical-align: middle;
}

.col-no {
  width: 64px;
  text-align: center;
}

.col-aksi {
  width: 110px;
  text-align: center;
}

.lab-empty-cell {
  text-align: center;
  color: #94a3b8;
  padding: 0.9rem 1rem !important;
  font-size: 0.85rem;
}

.lab-card-body {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.lab-columns-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.15rem;
  align-items: start;
}

.lab-masonry-col {
  display: flex;
  flex-direction: column;
  gap: 0.95rem;
}

.lab-cat-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0.9rem 1.15rem;
  transition: border-color 0.15s ease;
}

.lab-cat-card:hover {
  border-color: #cbd5e1;
}

.lab-check-row {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  cursor: pointer;
  user-select: none;
  line-height: 1.35;
}

.lab-check-row--master {
  width: 100%;
}

.lab-cat-title {
  font-size: 0.875rem;
  font-weight: 700;
  color: #1e293b;
}

.lab-children-list {
  display: flex;
  flex-direction: column;
  gap: 0.42rem;
  padding-left: 1.55rem;
  margin-top: 0.5rem;
}

.lab-item-label {
  font-size: 0.84rem;
  font-weight: 400;
  color: #334155;
}

.lab-checkbox {
  width: 15px;
  height: 15px;
  margin: 0;
  flex-shrink: 0;
  accent-color: #0d9488;
  cursor: pointer;
  border-radius: 3px;
}

.lab-meta-footer {
  border-top: 1px solid #e2e8f0;
  padding-top: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.95rem;
}

.lab-meta-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.5rem;
  align-items: center;
}

.lab-meta-field {
  display: grid;
  grid-template-columns: 185px minmax(0, 1fr);
  gap: 0.75rem;
  align-items: center;
}

.lab-meta-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
  line-height: 1.35;
}

.lab-meta-control {
  min-width: 0;
}

.lab-err-text {
  color: #dc2626;
  font-size: 0.83rem;
  font-weight: 600;
  margin: 0;
}

.lab-submit-footer {
  display: flex;
  justify-content: center;
  padding-top: 0.35rem;
}

.btn-save-lab {
  min-width: 120px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  padding: 0 1.4rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.btn-save-lab svg {
  width: 16px;
  height: 16px;
}

@media (max-width: 960px) {
  .lab-columns-grid,
  .lab-meta-row {
    grid-template-columns: minmax(0, 1fr);
    gap: 0.95rem;
  }

  .lab-meta-field {
    grid-template-columns: minmax(0, 1fr);
    gap: 0.35rem;
  }
}
</style>
