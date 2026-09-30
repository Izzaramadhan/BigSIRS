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
  }
});

const emit = defineEmits(['sort', 'edit', 'toggle-status', 'delete']);
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
          <th @click="emit('sort', 'str_number')" class="sortable">
            No. STR
            <span class="sort-icon" v-if="sort.column === 'str_number'">
              {{ sort.direction === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
          <th @click="emit('sort', 'sip_number')" class="sortable">
            No. SIP
            <span class="sort-icon" v-if="sort.column === 'sip_number'">
              {{ sort.direction === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
          <th>Spesialisasi</th>
          <th>Status</th>
          <th class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in items" :key="item.id">
          <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
          <td>
            <div class="fw-medium">{{ item.name }}</div>
            <div class="text-sm text-secondary">{{ item.nik }}</div>
          </td>
          <td>{{ item.str_number || '-' }}</td>
          <td>
            <div>{{ item.sip_number || '-' }}</div>
            <div class="text-sm text-secondary" v-if="item.sip_valid_until">
              Berlaku s/d: {{ item.sip_valid_until }}
            </div>
          </td>
          <td>{{ item.specialization || '-' }}</td>
          <td>
            <button 
              class="status-toggle"
              :class="item.is_active ? 'is-active' : 'is-inactive'"
              @click="emit('toggle-status', item)"
              :disabled="loading"
              :title="item.is_active ? 'Nonaktifkan' : 'Aktifkan'"
            >
              {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
            </button>
          </td>
          <td class="text-right actions-cell">
            <button class="btn-icon" @click="emit('edit', item)" title="Edit">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button class="btn-icon btn-icon-danger" @click="emit('delete', item)" title="Hapus">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                <line x1="10" y1="11" x2="10" y2="17"></line>
                <line x1="14" y1="11" x2="14" y2="17"></line>
              </svg>
            </button>
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

.status-toggle {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.status-toggle.is-active {
  background: #d1fae5;
  color: #059669;
}

.status-toggle.is-active:hover {
  background: #a7f3d0;
}

.status-toggle.is-inactive {
  background: #fee2e2;
  color: #dc2626;
}

.status-toggle.is-inactive:hover {
  background: #fecaca;
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
</style>
