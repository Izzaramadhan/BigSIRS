<script setup>
defineProps({
  items: {
    type: Array,
    required: true
  },
  pagination: {
    type: Object,
    required: true
  },
  sort: {
    type: Object,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },

});

const emit = defineEmits(['sort', 'view', 'edit', 'delete']);

</script>

<template>
  <div class="table-container" :class="{ 'is-loading': loading }">
    <table class="data-table">
      <thead>
        <tr>
          <th>No</th>
          <th @click="emit('sort', 'name')" class="sortable">
            Nama Dokter
            <span class="sort-icon" v-if="sort.column === 'name'">
              {{ sort.direction === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
          <th>Spesialisasi</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in items" :key="item.id">
          <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
          <td>
            <div class="fw-medium">{{ item.name || '—' }}</div>
          </td>
          <td>{{ item.specialization?.name ?? '—' }}</td>
          <td class="text-right actions-cell">
            <div class="action-group">
              <button class="btn-icon" @click="emit('view', item)" title="Lihat Detail" aria-label="Lihat detail Dokter">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
              <button class="btn-icon" @click="emit('edit', item)" title="Edit" aria-label="Edit Dokter">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>
              
              <button class="btn-icon btn-icon-danger" @click="emit('delete', item)" title="Hapus" aria-label="Hapus Dokter">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  <line x1="10" y1="11" x2="10" y2="17"></line>
                  <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.table-container {
  background: #ffffff;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  overflow-x: auto;
  position: relative;
}

.is-loading {
  opacity: 0.6;
  pointer-events: none;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.data-table th,
.data-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--color-border-soft);
  text-align: left;
}

.data-table th {
  background: var(--color-page-bg);
  font-weight: 600;
  color: var(--color-text-secondary);
  white-space: nowrap;
}

.data-table th.sortable {
  cursor: pointer;
  user-select: none;
}

.data-table th.sortable:hover {
  background: #f1f5f9;
}

.sort-icon {
  display: inline-block;
  margin-left: 0.25rem;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.text-right {
  text-align: right;
}

.actions-cell {
  width: 1%;
  white-space: nowrap;
}

.action-group {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
  justify-content: flex-end;
}

.fw-medium {
  font-weight: 500;
  color: var(--color-text-navy);
}

.text-sm {
  font-size: 0.8rem;
}

.text-secondary {
  color: var(--color-text-secondary);
}

.actions-cell {
  white-space: nowrap;
}

.btn-icon {
  background: transparent;
  border: none;
  color: var(--color-text-secondary);
  padding: 0.4rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-icon svg {
  width: 18px;
  height: 18px;
}

.btn-icon:hover {
  background: var(--color-page-bg);
  color: var(--color-primary);
}

.btn-icon-danger:hover {
  color: #dc2626;
  background: #fee2e2;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 58px;
  min-height: 26px;
  padding: 4px 12px;
  border: 0;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
  white-space: nowrap;
  cursor: pointer;
  transition:
    background-color 160ms ease,
    color 160ms ease,
    box-shadow 160ms ease,
    transform 120ms ease;
}

.status-pill--active {
  color: #047857;
  background: #d1fae5;
}

.status-pill--inactive {
  color: #dc2626;
  background: #fee2e2;
}

.status-pill:hover:not(:disabled) {
  filter: brightness(0.97);
  transform: translateY(-1px);
}

.status-pill:focus-visible {
  outline: 2px solid #0f8f83;
  outline-offset: 2px;
}

.status-pill:disabled {
  cursor: not-allowed;
  opacity: 0.6;
  transform: none;
}
</style>
