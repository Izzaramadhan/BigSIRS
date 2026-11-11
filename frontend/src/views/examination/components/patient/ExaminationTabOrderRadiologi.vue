<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  orders: {
    type: Object,
    required: true
  },
  radiologyCatalog: {
    type: Object,
    default: () => ({ umum: [], koas: [] })
  },
  radiologyHistory: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['save-radiology-order', 'remove-order']);

const selectedCodes = ref([]);
const sectionErrors = ref({ umum: '', koas: '' });

const sectionsConfig = computed(() => [
  {
    key: 'umum',
    title: 'ORDER ITEM RADIOLOGI UMUM/RESIDEN',
    keterangan: 'Umum/Residen',
    btnClass: 'btn-save-rad-umum',
    categories: Array.isArray(props.radiologyCatalog?.umum) ? props.radiologyCatalog.umum : []
  },
  {
    key: 'koas',
    title: 'ORDER ITEM RADIOLOGI KOAS',
    keterangan: 'Koas',
    btnClass: 'btn-save-rad-koas',
    categories: Array.isArray(props.radiologyCatalog?.koas) ? props.radiologyCatalog.koas : []
  }
]);

const catalogFlatMap = computed(() => {
  const map = new Map();
  sectionsConfig.value.forEach((sec) => {
    sec.categories.forEach((cat) => {
      cat.items.forEach((it) => {
        map.set(it.code, {
          code: it.code,
          name: it.name,
          category: cat.category,
          section: sec.key,
          keterangan: sec.keterangan,
          price: it.price || 165000
        });
      });
    });
  });
  return map;
});

const resolveEntryCode = (entry) => {
  if (entry.code && catalogFlatMap.value.has(entry.code)) return entry.code;
  for (const [k, meta] of catalogFlatMap.value.entries()) {
    if (
      meta.name === entry.item &&
      (!entry.category || meta.category === entry.category) &&
      (!entry.section || meta.section === entry.section)
    ) {
      return k;
    }
  }
  return null;
};

// Safe diff hydration so local unsaved toggles in either section are preserved
watch(
  () => props.orders?.radiology,
  (existingRad, prevRad) => {
    const nextCodes = Array.isArray(existingRad) ? existingRad.map(resolveEntryCode).filter(Boolean) : [];
    if (prevRad === undefined) {
      selectedCodes.value = Array.from(new Set(nextCodes));
      return;
    }
    const prevCodes = new Set(Array.isArray(prevRad) ? prevRad.map(resolveEntryCode).filter(Boolean) : []);
    const nextSet = new Set(nextCodes);
    const merged = selectedCodes.value.filter((c) => !prevCodes.has(c) || nextSet.has(c));
    nextCodes.forEach((c) => {
      if (!prevCodes.has(c) && !merged.includes(c)) merged.push(c);
    });
    selectedCodes.value = merged;
  },
  { immediate: true }
);

const selectedRows = computed(() => {
  return selectedCodes.value
    .map((code) => catalogFlatMap.value.get(code))
    .filter(Boolean);
});

const countSelectedBySection = (sectionKey) => {
  return selectedRows.value.filter((row) => row.section === sectionKey).length;
};

const toggleItem = (code, sectionKey) => {
  sectionErrors.value = { ...sectionErrors.value, [sectionKey]: '' };
  if (selectedCodes.value.includes(code)) {
    selectedCodes.value = selectedCodes.value.filter((c) => c !== code);
  } else {
    selectedCodes.value = [...selectedCodes.value, code];
  }
};

const handleRemoveRow = (code) => {
  selectedCodes.value = selectedCodes.value.filter((c) => c !== code);
  const existing = (props.orders?.radiology || []).find(
    (entry) => resolveEntryCode(entry) === code
  );
  if (existing) {
    emit('remove-order', { bucket: 'radiology', id: existing.id });
  }
};

const handleSaveSection = (sec) => {
  const sectionRows = selectedRows.value.filter((row) => row.section === sec.key);
  if (sectionRows.length === 0) {
    sectionErrors.value = {
      ...sectionErrors.value,
      [sec.key]: `Pilih minimal 1 pemeriksaan pada ${sec.title}.`
    };
    return;
  }

  sectionErrors.value = { ...sectionErrors.value, [sec.key]: '' };
  const nowTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  const entries = sectionRows.map((row, idx) => ({
    id: `RAD-${Date.now()}-${idx}-${row.code}`,
    code: row.code,
    item: row.name,
    group: row.name,
    category: row.category,
    section: sec.key,
    note: sec.keterangan,
    price: row.price || 165000,
    priority: 'Rutin',
    ordered_at: nowTime
  }));

  emit('save-radiology-order', {
    section: sec.key,
    sectionTitle: sec.title,
    entries
  });
};
</script>

<template>
  <div class="clinical-workspace-panel rad-page-wrap">
    <!-- Section 1: RIWAYAT RADIOLOGI -->
    <div class="rad-card">
      <div class="rad-card-header">
        <span class="rad-card-title">RIWAYAT RADIOLOGI</span>
      </div>
      <div class="rad-history-body">
        <div v-if="radiologyHistory.length === 0" class="rad-history-empty">
          Belum ada riwayat pemeriksaan radiologi.
        </div>
        <div v-else class="rad-history-list">
          <div v-for="hist in radiologyHistory" :key="hist.id" class="rad-hist-entry">
            <span class="font-mono font-semibold">{{ hist.date }}</span>
            <span>&mdash; {{ hist.unit }} ({{ hist.items.join(', ') }})</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: 5-Column Summary Table (No | Grup | Kategori | Keterangan | Aksi) -->
    <div class="cw-table-wrap rad-summary-wrap">
      <table class="cw-table rad-summary-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Grup</th>
            <th>Kategori</th>
            <th>Keterangan</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="selectedRows.length === 0">
            <td colspan="5" class="rad-empty-cell">Data Tidak Ada</td>
          </tr>
          <tr v-for="(row, idx) in selectedRows" v-else :key="row.code">
            <td class="font-mono col-no">{{ idx + 1 }}</td>
            <td class="font-semibold">{{ row.name }}</td>
            <td>{{ row.category }}</td>
            <td>
              <span
                class="rad-ket-badge"
                :class="row.section === 'koas' ? 'rad-ket-badge--koas' : 'rad-ket-badge--umum'"
              >
                {{ row.keterangan }}
              </span>
            </td>
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

    <!-- Section 3 & 4: ORDER ITEM RADIOLOGI UMUM/RESIDEN & ORDER ITEM RADIOLOGI KOAS -->
    <div
      v-for="sec in sectionsConfig"
      :key="sec.key"
      class="rad-card"
      :data-section="sec.key"
    >
      <div class="rad-card-header">
        <span class="rad-card-title">{{ sec.title }}</span>
        <span v-if="countSelectedBySection(sec.key) > 0" class="cw-pill cw-pill--primary font-mono">
          {{ countSelectedBySection(sec.key) }} Dipilih
        </span>
      </div>

      <div class="rad-card-body">
        <div class="rad-categories-grid">
          <div
            v-for="cat in sec.categories"
            :key="cat.id"
            class="rad-cat-card"
          >
            <div class="rad-cat-heading">{{ cat.category }}</div>

            <div class="rad-items-list">
              <label
                v-for="item in cat.items"
                :key="item.code"
                class="rad-check-row"
              >
                <input
                  type="checkbox"
                  class="rad-checkbox"
                  :value="item.code"
                  :checked="selectedCodes.includes(item.code)"
                  @change="toggleItem(item.code, sec.key)"
                />
                <span class="rad-item-label">{{ item.name }}</span>
              </label>
            </div>
          </div>
        </div>

        <p v-if="sectionErrors[sec.key]" class="rad-err-text">{{ sectionErrors[sec.key] }}</p>

        <div class="rad-section-footer">
          <button
            type="button"
            class="cw-btn-primary btn-save-rad"
            :class="sec.btnClass"
            @click="handleSaveSection(sec)"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
              <polyline points="17 21 17 13 7 13 7 21"></polyline>
              <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            <span>Simpan</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rad-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 1.05rem;
}

.rad-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: visible;
}

.rad-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.8rem 1.25rem;
  border-radius: 8px 8px 0 0;
}

.rad-card-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.rad-history-body {
  padding: 0.9rem 1.25rem;
}

.rad-history-empty {
  color: #94a3b8;
  font-size: 0.85rem;
}

.rad-history-list {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  font-size: 0.85rem;
  color: #334155;
}

.rad-summary-wrap {
  border-radius: 8px;
}

.rad-summary-table th {
  font-size: 0.82rem;
  font-weight: 700;
  color: #334155;
  background: #f1f5f9;
}

.rad-summary-table td {
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

.rad-empty-cell {
  text-align: center;
  color: #94a3b8;
  padding: 0.9rem 1rem !important;
  font-size: 0.85rem;
}

.rad-ket-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.78rem;
  font-weight: 600;
}

.rad-ket-badge--umum {
  background: #f0fdfa;
  color: #0f766e;
  border: 1px solid #99f6e4;
}

.rad-ket-badge--koas {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

.rad-card-body {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.rad-categories-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.15rem;
  align-items: stretch;
}

.rad-cat-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 1rem 1.25rem;
  transition: border-color 0.15s ease;
}

.rad-cat-card:hover {
  border-color: #cbd5e1;
}

.rad-cat-heading {
  font-size: 0.85rem;
  font-weight: 700;
  color: #1e293b;
  letter-spacing: 0.015em;
  margin-bottom: 0.65rem;
}

.rad-items-list {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  padding-left: 1.1rem;
}

.rad-check-row {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  cursor: pointer;
  user-select: none;
  line-height: 1.35;
}

.rad-checkbox {
  width: 15px;
  height: 15px;
  margin: 0;
  flex-shrink: 0;
  accent-color: #0d9488;
  cursor: pointer;
  border-radius: 3px;
}

.rad-item-label {
  font-size: 0.85rem;
  font-weight: 400;
  color: #334155;
}

.rad-err-text {
  color: #dc2626;
  font-size: 0.83rem;
  font-weight: 600;
  margin: 0;
}

.rad-section-footer {
  border-top: 1px solid #f1f5f9;
  padding-top: 1rem;
  display: flex;
  justify-content: center;
}

.btn-save-rad {
  min-width: 118px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  padding: 0 1.4rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.btn-save-rad svg {
  width: 16px;
  height: 16px;
}

@media (max-width: 900px) {
  .rad-categories-grid {
    grid-template-columns: minmax(0, 1fr);
    gap: 0.95rem;
  }
}
</style>

