<script setup>


const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  sortConfig: {
    type: Object,
    default: () => ({ column: 'created_at', direction: 'desc' })
  }
})

const emit = defineEmits(['sort', 'edit', 'delete'])

const handleSort = (column) => {
  let direction = 'asc'
  if (props.sortConfig.column === column && props.sortConfig.direction === 'asc') {
    direction = 'desc'
  }
  emit('sort', { column, direction })
}

const getSortIcon = (column) => {
  if (props.sortConfig.column !== column) {
    return 'M7 15l5 5 5-5 M7 9l5-5 5 5'
  }
  return props.sortConfig.direction === 'asc' 
    ? 'M7 15l5-5 5 5' 
    : 'M7 9l5 5 5-5'
}
</script>

<template>
  <div class="table-container">
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
      <span>Memuat data...</span>
    </div>
    
    <table class="table">
      <thead>
        <tr>
          <th class="col-code sortable" @click="handleSort('code')">
            <div class="th-content">
              KODE TINDAKAN
              <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path :d="getSortIcon('code')" />
              </svg>
            </div>
          </th>
          <th class="col-name sortable" @click="handleSort('name')">
            <div class="th-content">
              NAMA TINDAKAN
              <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path :d="getSortIcon('name')" />
              </svg>
            </div>
          </th>
          <th class="col-employees">PEGAWAI TERPETAKAN</th>
          <th class="col-actions">AKSI</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="items.length === 0 && !loading">
          <td colspan="4" class="text-center empty-state">
            Tidak ada data mapping tindakan ditemukan.
          </td>
        </tr>
        <tr v-for="item in items" :key="item.id">
          <td><span class="code-badge">{{ item.procedure.code }}</span></td>
          <td class="font-medium">{{ item.procedure.name }}</td>
          <td>
            <div class="employee-tags">
              <span 
                v-for="emp in item.employees.slice(0, 3)" 
                :key="emp.id"
                class="badge badge-info"
              >
                {{ emp.name }} &mdash; {{ emp.profession }}
              </span>
              <span v-if="item.employees.length > 3" class="badge badge-secondary">
                +{{ item.employees.length - 3 }} lainnya
              </span>
              <span v-if="item.employees.length === 0" class="text-muted">
                Belum ada pegawai
              </span>
            </div>
          </td>
          <td>
            <div class="actions">
              <button 
                class="btn-icon btn-edit" 
                title="Edit Mapping" 
                @click="emit('edit', item)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>
              <button 
                class="btn-icon btn-delete" 
                title="Hapus Mapping" 
                @click="emit('delete', item)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
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
  min-height: 200px;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(2px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 10;
  gap: 1rem;
  color: var(--color-primary);
  font-weight: 500;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-primary-light);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.table th,
.table td {
  padding: 1rem 1.25rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border-soft);
}

.table th {
  background: #f8fafc;
  font-weight: 600;
  color: var(--color-text-secondary);
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.table tbody tr:hover {
  background: #f8fafc;
}

.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
}

.sortable:hover {
  background: #f1f5f9;
}

.th-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sort-icon {
  width: 14px;
  height: 14px;
  opacity: 0.5;
}

.col-code { width: 15%; }
.col-name { width: 35%; }
.col-employees { width: 40%; }
.col-actions { width: 10%; text-align: center; }

.empty-state {
  padding: 3rem !important;
  color: var(--color-text-secondary);
  font-style: italic;
}

.text-center {
  text-align: center;
}

.code-badge {
  background: var(--color-bg-subtle);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-family: monospace;
  font-weight: 600;
  color: var(--color-text-navy);
}

.font-medium {
  font-weight: 500;
  color: var(--color-text-navy);
}

.employee-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.badge {
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-info {
  background-color: var(--color-primary-light);
  color: var(--color-primary);
}

.badge-secondary {
  background-color: var(--color-bg-subtle);
  color: var(--color-text-secondary);
}

.text-muted {
  color: var(--color-text-secondary);
  font-style: italic;
}

.actions {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
}

.btn-icon {
  background: none;
  border: none;
  padding: 0.4rem;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-icon svg {
  width: 16px;
  height: 16px;
}

.btn-edit {
  color: var(--color-primary);
}

.btn-edit:hover {
  background: var(--color-primary-light);
}

.btn-delete {
  color: var(--color-danger);
}

.btn-delete:hover {
  background: #fee2e2;
}
</style>
