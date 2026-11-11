<script setup>
import { ref, computed, watch } from 'vue';
import ClinicalSelect from './ClinicalSelect.vue';

const props = defineProps({
  unit: {
    type: String,
    required: true // 'VK' | 'IBS'
  },
  title: {
    type: String,
    required: true // 'OPERASI VK' | 'IBS'
  },
  idPrefix: {
    type: String,
    default: 'vk' // 'vk' | 'ibs'
  },
  surgeries: {
    type: Object,
    required: true
  },
  doctors: {
    type: Array,
    default: () => []
  },
  scheduleOptions: {
    type: Object,
    default: () => ({ specialties: [], classes: [], roomsByClass: {} })
  },
  defaultDoctor: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['add-order', 'remove-order']);

const pad2 = (n) => String(n).padStart(2, '0');
const formatNowDDMMYYYYHHMM = () => {
  const now = new Date();
  return `${pad2(now.getDate())}-${pad2(now.getMonth() + 1)}-${now.getFullYear()} ${pad2(now.getHours())}:${pad2(now.getMinutes())}`;
};

const draft = ref({
  schedule: formatNowDDMMYYYYHHMM(),
  specialty: '',
  doctor: '',
  procedureType: '',
  roomClass: '',
  room: '',
  bed: ''
});

const localErr = ref('');
const perPage = ref(10);
const currentPage = ref(1);
const searchQuery = ref('');
const sortKey = ref('');
const sortDir = ref('asc');

const specialtyOptions = computed(() => props.scheduleOptions?.specialties || []);

const doctorOptions = computed(() => {
  const base = Array.isArray(props.doctors) ? [...props.doctors] : [];
  if (props.defaultDoctor && props.defaultDoctor !== '-' && !base.includes(props.defaultDoctor)) {
    base.unshift(props.defaultDoctor);
  }
  return base;
});

const classOptions = computed(() => props.scheduleOptions?.classes || []);

const roomsForSelectedClass = computed(() => {
  const map = props.scheduleOptions?.roomsByClass || {};
  if (!draft.value.roomClass) return [];
  return Array.isArray(map[draft.value.roomClass]) ? map[draft.value.roomClass] : [];
});

const roomOptions = computed(() => roomsForSelectedClass.value.map((r) => r.name));

const bedOptions = computed(() => {
  const found = roomsForSelectedClass.value.find((r) => r.name === draft.value.room);
  return found && Array.isArray(found.beds) ? found.beds : [];
});

// Cascade reset when Kelas or Kamar/Bangsal changes
watch(
  () => draft.value.roomClass,
  () => {
    if (!roomOptions.value.includes(draft.value.room)) {
      draft.value.room = '';
      draft.value.bed = '';
    }
  }
);

watch(
  () => draft.value.room,
  () => {
    if (!bedOptions.value.includes(draft.value.bed)) {
      draft.value.bed = '';
    }
  }
);

const unitRequests = computed(() => {
  return (props.surgeries?.requests || []).filter((item) => item.unit === props.unit);
});

const filteredRows = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  let rows = unitRequests.value;
  if (q) {
    rows = rows.filter((row) =>
      [row.schedule, row.room, row.bed, row.room_class, row.specialty, row.procedure_name, row.doctor]
        .some((v) => String(v || '').toLowerCase().includes(q))
    );
  }
  if (sortKey.value) {
    const dir = sortDir.value === 'asc' ? 1 : -1;
    rows = [...rows].sort((a, b) => {
      const va = String(a[sortKey.value] || '').toLowerCase();
      const vb = String(b[sortKey.value] || '').toLowerCase();
      return va.localeCompare(vb) * dir;
    });
  }
  return rows;
});

const totalEntries = computed(() => filteredRows.value.length);
const totalPages = computed(() => Math.max(1, Math.ceil(totalEntries.value / perPage.value)));

watch([searchQuery, perPage], () => {
  currentPage.value = 1;
});

watch(totalPages, (tp) => {
  if (currentPage.value > tp) currentPage.value = tp;
});

const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredRows.value.slice(start, start + perPage.value);
});

const startEntryIndex = computed(() => {
  if (totalEntries.value === 0) return 0;
  return (currentPage.value - 1) * perPage.value + 1;
});

const endEntryIndex = computed(() => {
  if (totalEntries.value === 0) return 0;
  return Math.min(currentPage.value * perPage.value, totalEntries.value);
});

const toggleSort = (key) => {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortDir.value = 'asc';
  }
};

const getSortSymbol = (key) => {
  if (sortKey.value !== key) return '↕';
  return sortDir.value === 'asc' ? '↑' : '↓';
};

const handleSave = () => {
  if (!draft.value.schedule.trim()) {
    localErr.value = 'Jadwal Operasi wajib diisi.';
    return;
  }
  if (!draft.value.specialty) {
    localErr.value = 'Spesialisasi wajib dipilih.';
    return;
  }
  if (!draft.value.doctor) {
    localErr.value = 'Dokter wajib dipilih.';
    return;
  }
  if (!draft.value.procedureType.trim()) {
    localErr.value = 'Jenis Tindakan wajib diisi.';
    return;
  }
  if (!draft.value.roomClass) {
    localErr.value = 'Kelas wajib dipilih.';
    return;
  }
  if (!draft.value.room) {
    localErr.value = 'Kamar / Bangsal wajib dipilih.';
    return;
  }
  if (!draft.value.bed) {
    localErr.value = 'Bed wajib dipilih.';
    return;
  }

  localErr.value = '';
  emit('add-order', {
    bucket: 'surgery_requests',
    entry: {
      id: `${props.unit}-${Date.now()}`,
      unit: props.unit,
      schedule: draft.value.schedule.trim(),
      planned_date: draft.value.schedule.trim(),
      specialty: draft.value.specialty,
      doctor: draft.value.doctor,
      operator: draft.value.doctor,
      procedure_name: draft.value.procedureType.trim(),
      room_class: draft.value.roomClass,
      room: draft.value.room,
      bed: draft.value.bed,
      anesthesia: props.unit === 'VK' ? 'VK / Obstetri' : 'Bedah Sentral (IBS)',
      priority: 'Elektif',
      note: `${draft.value.room} - ${draft.value.bed} (${draft.value.roomClass})`
    }
  });

  draft.value = {
    schedule: formatNowDDMMYYYYHHMM(),
    specialty: '',
    doctor: '',
    procedureType: '',
    roomClass: '',
    room: '',
    bed: ''
  };
};
</script>

<template>
  <div class="clinical-workspace-panel ops-page-wrap">
    <div class="ops-card">
      <div class="ops-card-header">
        <span class="ops-card-title">{{ title }}</span>
      </div>

      <div class="ops-card-body">
        <!-- 2-Column Horizontal Form Grid -->
        <div class="ops-form-grid">
          <!-- Left Column -->
          <div class="ops-form-col">
            <div class="ops-field-row">
              <label class="ops-label" :for="`${idPrefix}-schedule`">Jadwal Operasi</label>
              <input
                :id="`${idPrefix}-schedule`"
                v-model="draft.schedule"
                type="text"
                class="cw-input font-mono"
                placeholder="DD-MM-YYYY HH:mm"
              />
            </div>

            <div class="ops-field-row">
              <label class="ops-label" :for="`${idPrefix}-spesialisasi`">
                Pilih Spesialisasi <span class="cw-req">*</span>
              </label>
              <ClinicalSelect
                :id="`${idPrefix}-spesialisasi`"
                v-model="draft.specialty"
                :options="specialtyOptions"
                placeholder="-- silahkan pilih --"
              />
            </div>

            <div class="ops-field-row">
              <label class="ops-label" :for="`${idPrefix}-dokter`">
                Pilih Dokter <span class="cw-req">*</span>
              </label>
              <ClinicalSelect
                :id="`${idPrefix}-dokter`"
                v-model="draft.doctor"
                :options="doctorOptions"
                placeholder="-- silahkan pilih --"
              />
            </div>

            <div class="ops-field-row ops-field-row--top">
              <label class="ops-label" :for="`${idPrefix}-tindakan`">
                Jenis Tindakan <span class="cw-req">*</span>
              </label>
              <textarea
                :id="`${idPrefix}-tindakan`"
                v-model="draft.procedureType"
                rows="2"
                class="cw-textarea ops-textarea"
                :placeholder="`Masukkan jenis tindakan ${unit}...`"
              ></textarea>
            </div>
          </div>

          <!-- Right Column -->
          <div class="ops-form-col">
            <div class="ops-field-row">
              <label class="ops-label" :for="`${idPrefix}-kelas`">
                Pilih Kelas <span class="cw-req">*</span>
              </label>
              <ClinicalSelect
                :id="`${idPrefix}-kelas`"
                v-model="draft.roomClass"
                :options="classOptions"
                placeholder="-- silahkan pilih --"
              />
            </div>

            <div class="ops-field-row">
              <label class="ops-label" :for="`${idPrefix}-kamar`">
                Pilih Kamar / Bangsal <span class="cw-req">*</span>
              </label>
              <ClinicalSelect
                :id="`${idPrefix}-kamar`"
                v-model="draft.room"
                :options="roomOptions"
                placeholder="-- silahkan pilih --"
              />
            </div>

            <div class="ops-field-row">
              <label class="ops-label" :for="`${idPrefix}-bed`">
                Pilih Bed <span class="cw-req">*</span>
              </label>
              <ClinicalSelect
                :id="`${idPrefix}-bed`"
                v-model="draft.bed"
                :options="bedOptions"
                placeholder="-- silahkan pilih --"
              />
            </div>
          </div>
        </div>

        <p v-if="localErr" class="ops-err-text">{{ localErr }}</p>

        <!-- Centered Save Button -->
        <div class="ops-submit-row">
          <button type="button" class="cw-btn-primary btn-save-ops" :class="`btn-save-${idPrefix}`" @click="handleSave">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
              <polyline points="17 21 17 13 7 13 7 21"></polyline>
              <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            <span>Simpan</span>
          </button>
        </div>

        <hr class="ops-divider" />

        <!-- DataTable Toolbar -->
        <div class="ops-table-toolbar">
          <div class="ops-records-ctrl">
            <select
              :id="`${idPrefix}-per-page`"
              v-model.number="perPage"
              class="cw-select ops-per-page-select"
              aria-label="Records per page"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span>records</span>
          </div>

          <div class="ops-search-ctrl">
            <label :for="`${idPrefix}-search`">Search:</label>
            <input
              :id="`${idPrefix}-search`"
              v-model="searchQuery"
              type="search"
              class="cw-input ops-search-input"
              autocomplete="off"
            />
          </div>
        </div>

        <!-- 7-Column Sortable DataTable -->
        <div class="cw-table-wrap">
          <table class="cw-table ops-table" :class="`${idPrefix}-table`">
            <thead>
              <tr>
                <th class="col-no">No.</th>
                <th class="sortable-th" @click="toggleSort('schedule')">
                  <span>Jadwal Operasi</span>
                  <span class="sort-icon">{{ getSortSymbol('schedule') }}</span>
                </th>
                <th class="sortable-th" @click="toggleSort('room')">
                  <span>Kamar</span>
                  <span class="sort-icon">{{ getSortSymbol('room') }}</span>
                </th>
                <th class="sortable-th" @click="toggleSort('specialty')">
                  <span>Poliklinik</span>
                  <span class="sort-icon">{{ getSortSymbol('specialty') }}</span>
                </th>
                <th class="sortable-th" @click="toggleSort('procedure_name')">
                  <span>Jenis Tindakan</span>
                  <span class="sort-icon">{{ getSortSymbol('procedure_name') }}</span>
                </th>
                <th class="sortable-th" @click="toggleSort('doctor')">
                  <span>Dokter</span>
                  <span class="sort-icon">{{ getSortSymbol('doctor') }}</span>
                </th>
                <th class="col-aksi">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="paginatedRows.length === 0">
                <td colspan="7" class="ops-empty-cell">No data available in table</td>
              </tr>
              <tr v-for="(row, idx) in paginatedRows" v-else :key="row.id">
                <td class="font-mono col-no">{{ startEntryIndex + idx }}</td>
                <td class="font-mono">{{ row.schedule }}</td>
                <td>
                  <div class="ops-room-cell">
                    <span class="font-semibold">{{ row.room }}</span>
                    <span class="ops-room-sub">{{ row.bed }} &bull; {{ row.room_class }}</span>
                  </div>
                </td>
                <td>{{ row.specialty }}</td>
                <td>{{ row.procedure_name }}</td>
                <td>{{ row.doctor }}</td>
                <td class="col-aksi">
                  <button
                    type="button"
                    class="cw-btn-danger-icon"
                    @click="emit('remove-order', { bucket: 'surgery_requests', id: row.id })"
                  >
                    Hapus
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Bottom Entries Info & Pagination -->
        <div class="ops-table-footer">
          <span class="ops-entries-info">
            Showing {{ startEntryIndex }} to {{ endEntryIndex }} of {{ totalEntries }} entries
          </span>
          <div v-if="totalPages > 1" class="ops-pager">
            <button
              type="button"
              class="cw-btn-secondary ops-page-btn"
              :disabled="currentPage <= 1"
              @click="currentPage--"
            >
              Sebelumnya
            </button>
            <button
              type="button"
              class="cw-btn-secondary ops-page-btn"
              :disabled="currentPage >= totalPages"
              @click="currentPage++"
            >
              Berikutnya
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ops-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.ops-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: visible;
}

.ops-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.85rem 1.25rem;
  border-radius: 8px 8px 0 0;
}

.ops-card-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.ops-card-body {
  padding: 1.5rem 1.75rem 1.35rem;
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.ops-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 2.25rem;
  align-items: start;
}

.ops-form-col {
  display: flex;
  flex-direction: column;
  gap: 0.95rem;
}

.ops-field-row {
  display: grid;
  grid-template-columns: 165px minmax(0, 1fr);
  gap: 0.85rem;
  align-items: center;
}

.ops-field-row--top {
  align-items: flex-start;
}

.ops-field-row--top .ops-label {
  padding-top: 0.55rem;
}

.ops-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
  text-align: right;
  line-height: 1.35;
}

.ops-textarea {
  min-height: 58px;
  resize: vertical;
}

.ops-err-text {
  color: #dc2626;
  font-size: 0.83rem;
  font-weight: 600;
  text-align: center;
  margin: 0;
}

.ops-submit-row {
  display: flex;
  justify-content: center;
  padding-top: 0.25rem;
}

.btn-save-ops {
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

.btn-save-ops svg {
  width: 16px;
  height: 16px;
}

.ops-divider {
  border: none;
  border-top: 1px solid #e2e8f0;
  margin: 0.35rem 0 0.15rem;
}

.ops-table-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.ops-records-ctrl {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.84rem;
  color: #475569;
  font-weight: 500;
}

.ops-per-page-select {
  width: auto;
  min-width: 68px;
  height: 36px;
  padding: 0.25rem 0.65rem;
  font-size: 0.84rem;
}

.ops-search-ctrl {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  font-size: 0.84rem;
  color: #475569;
  font-weight: 500;
}

.ops-search-input {
  width: 210px;
  height: 36px;
  font-size: 0.84rem;
}

.ops-table th {
  font-size: 0.82rem;
  font-weight: 700;
  color: #334155;
  background: #f8fafc;
  white-space: nowrap;
}

.ops-table td {
  font-size: 0.84rem;
  color: #334155;
  vertical-align: middle;
}

.sortable-th {
  cursor: pointer;
  user-select: none;
}

.sortable-th:hover {
  background: #f1f5f9 !important;
}

.sort-icon {
  margin-left: 0.35rem;
  color: #94a3b8;
  font-size: 0.78rem;
}

.col-no {
  width: 64px;
  text-align: center;
}

.col-aksi {
  width: 96px;
  text-align: center;
}

.ops-empty-cell {
  text-align: center;
  color: #64748b;
  padding: 1rem !important;
  font-size: 0.85rem;
}

.ops-room-cell {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.ops-room-sub {
  font-size: 0.76rem;
  color: #64748b;
}

.ops-table-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.ops-entries-info {
  font-size: 0.82rem;
  color: #64748b;
}

.ops-pager {
  display: inline-flex;
  gap: 0.5rem;
}

.ops-page-btn {
  height: 32px;
  padding: 0 0.75rem;
  font-size: 0.8rem;
}

@media (max-width: 960px) {
  .ops-form-grid {
    grid-template-columns: minmax(0, 1fr);
    gap: 1rem;
  }

  .ops-field-row {
    grid-template-columns: minmax(0, 1fr);
    gap: 0.35rem;
  }

  .ops-label {
    text-align: left;
  }
}
</style>

