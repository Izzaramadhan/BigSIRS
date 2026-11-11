<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  rows: {
    type: Array,
    default: () => []
  },
  idPrefix: {
    type: String,
    default: 'hais'
  }
});

const emit = defineEmits(['remove']);

const perPage = ref(10);
const currentPage = ref(1);
const searchQuery = ref('');
const sortKey = ref('');
const sortDir = ref('asc');

const filteredRows = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  let list = Array.isArray(props.rows) ? props.rows : [];
  if (q) {
    list = list.filter((row) =>
      [row.date, row.variabel, row.value, row.devices, row.note]
        .some((v) => String(v || '').toLowerCase().includes(q))
    );
  }
  if (sortKey.value) {
    const dir = sortDir.value === 'asc' ? 1 : -1;
    list = [...list].sort((a, b) => {
      const va = String(a[sortKey.value] || '').toLowerCase();
      const vb = String(b[sortKey.value] || '').toLowerCase();
      return va.localeCompare(vb) * dir;
    });
  }
  return list;
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
</script>

<template>
  <div class="hais-dt-block">
    <div class="hais-dt-toolbar">
      <div class="hais-records-ctrl">
        <select
          :id="`${idPrefix}-per-page`"
          v-model.number="perPage"
          class="cw-select hais-per-page-select"
          aria-label="Records per page"
        >
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
        <span>records</span>
      </div>

      <div class="hais-search-ctrl">
        <label :for="`${idPrefix}-search`">Search:</label>
        <input
          :id="`${idPrefix}-search`"
          v-model="searchQuery"
          type="search"
          class="cw-input hais-search-input"
          autocomplete="off"
        />
      </div>
    </div>

    <div class="cw-table-wrap">
      <table class="cw-table hais-table" :class="`${idPrefix}-table`">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th class="sortable-th" @click="toggleSort('date')">
              <span>Tanggal</span>
              <span class="sort-icon">{{ getSortSymbol('date') }}</span>
            </th>
            <th class="sortable-th" @click="toggleSort('variabel')">
              <span>Variabel</span>
              <span class="sort-icon">{{ getSortSymbol('variabel') }}</span>
            </th>
            <th class="sortable-th" @click="toggleSort('value')">
              <span>Value</span>
              <span class="sort-icon">{{ getSortSymbol('value') }}</span>
            </th>
            <th class="sortable-th col-act" @click="toggleSort('id')">
              <span>Act</span>
              <span class="sort-icon">{{ getSortSymbol('id') }}</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="paginatedRows.length === 0">
            <td colspan="5" class="hais-empty-cell">No data available in table</td>
          </tr>
          <tr v-for="(row, idx) in paginatedRows" v-else :key="row.id">
            <td class="font-mono col-no">{{ startEntryIndex + idx }}</td>
            <td class="font-mono">{{ row.date }}</td>
            <td class="font-semibold">{{ row.variabel || 'Monitor HAIs' }}</td>
            <td>{{ row.value || row.finding_status || row.note }}</td>
            <td class="col-act">
              <button
                type="button"
                class="cw-btn-danger-icon"
                @click="emit('remove', row.id)"
              >
                Hapus
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="hais-dt-footer">
      <span class="hais-entries-info">
        Showing {{ startEntryIndex }} to {{ endEntryIndex }} of {{ totalEntries }} entries
      </span>
      <div v-if="totalPages > 1" class="hais-pager">
        <button
          type="button"
          class="cw-btn-secondary hais-page-btn"
          :disabled="currentPage <= 1"
          @click="currentPage--"
        >
          Sebelumnya
        </button>
        <button
          type="button"
          class="cw-btn-secondary hais-page-btn"
          :disabled="currentPage >= totalPages"
          @click="currentPage++"
        >
          Berikutnya
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.hais-dt-block {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.hais-dt-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.85rem;
  flex-wrap: wrap;
}

.hais-records-ctrl {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.84rem;
  color: #475569;
  font-weight: 500;
}

.hais-per-page-select {
  width: auto;
  min-width: 66px;
  height: 34px;
  padding: 0.2rem 0.6rem;
  font-size: 0.83rem;
}

.hais-search-ctrl {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.84rem;
  color: #475569;
  font-weight: 500;
}

.hais-search-input {
  width: 190px;
  height: 34px;
  font-size: 0.83rem;
}

.hais-table th {
  font-size: 0.82rem;
  font-weight: 700;
  color: #334155;
  background: #f8fafc;
  white-space: nowrap;
}

.hais-table td {
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
  margin-left: 0.3rem;
  color: #94a3b8;
  font-size: 0.76rem;
}

.col-no {
  width: 54px;
  text-align: center;
}

.col-act {
  width: 82px;
  text-align: center;
}

.hais-empty-cell {
  text-align: center;
  color: #64748b;
  padding: 0.9rem !important;
  font-size: 0.84rem;
}

.hais-dt-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.hais-entries-info {
  font-size: 0.82rem;
  color: #64748b;
}

.hais-pager {
  display: inline-flex;
  gap: 0.45rem;
}

.hais-page-btn {
  height: 30px;
  padding: 0 0.7rem;
  font-size: 0.78rem;
}
</style>

