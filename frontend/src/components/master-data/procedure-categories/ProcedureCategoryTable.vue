<script setup>

const props = defineProps({
  items: Array,
  loading: Boolean,
  sortConfig: Object,
  pagination: Object
});

const emit = defineEmits(['sort', 'edit', 'delete', 'toggle-status']);

const sortIcon = (column) => {
  if (props.sortConfig.column !== column) return 'M7 16l5-5 5 5';
  return props.sortConfig.direction === 'asc' ? 'M7 16l5-5 5 5' : 'M7 10l5 5 5-5';
};
</script>

<template>
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th width="60">No</th>
          <th @click="emit('sort', 'name')" class="sortable">
            <div class="th-content">
              Kategori Tindakan
              <svg class="sort-icon" :class="{ active: sortConfig.column === 'name' }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path :d="sortIcon('name')" />
              </svg>
            </div>
          </th>
          <th>Deskripsi</th>
          <th @click="emit('sort', 'is_active')" class="sortable text-center" width="100">
            <div class="th-content justify-center">
              Status
              <svg class="sort-icon" :class="{ active: sortConfig.column === 'is_active' }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path :d="sortIcon('is_active')" />
              </svg>
            </div>
          </th>
          <th class="text-right" width="140">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="5" class="text-center py-4">
            <div class="loading-state">
              <span class="spinner-border"></span>
              Memuat data...
            </div>
          </td>
        </tr>
        <tr v-else-if="!items || items.length === 0">
          <td colspan="5" class="text-center py-4 text-muted">
            Tidak ada data kategori tindakan.
          </td>
        </tr>
        <tr v-else v-for="(item, index) in items" :key="item.id">
          <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
          <td class="font-medium">{{ item.name }}</td>
          <td class="text-muted">{{ item.description || '-' }}</td>
          <td class="text-center">
            <button 
              class="status-toggle" 
              :class="item.is_active ? 'status-active' : 'status-inactive'"
              @click="emit('toggle-status', item)"
              :title="item.is_active ? 'Nonaktifkan' : 'Aktifkan'"
            >
              {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
            </button>
          </td>
          <td class="actions-cell">
            <button class="btn-action edit" @click="emit('edit', item)" title="Edit">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button class="btn-action delete" @click="emit('delete', item)" title="Hapus">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
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
  overflow-x: auto;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  background: #fff;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.data-table th,
.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
}

.data-table th {
  background: #f8fafc;
  font-weight: 600;
  text-align: left;
  color: var(--color-text-secondary);
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: #f1f5f9;
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background: #f1f5f9;
}

.th-content {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.justify-center {
  justify-content: center;
}

.sort-icon {
  width: 16px;
  height: 16px;
  opacity: 0.3;
}

.sort-icon.active {
  opacity: 1;
  color: var(--color-primary);
}

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.py-4 {
  padding-top: 2rem !important;
  padding-bottom: 2rem !important;
}

.font-medium {
  font-weight: 500;
}

.text-muted {
  color: var(--color-text-secondary);
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: var(--color-text-secondary);
}

.spinner-border {
  display: inline-block;
  width: 1rem;
  height: 1rem;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spinner-border .75s linear infinite;
}

@keyframes spinner-border {
  to { transform: rotate(360deg); }
}

.status-toggle {
  border: none;
  background: none;
  padding: 0.25rem 0.6rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.status-active {
  background: #d1fae5;
  color: #059669;
}

.status-active:hover {
  background: #a7f3d0;
}

.status-inactive {
  background: #fee2e2;
  color: #dc2626;
}

.status-inactive:hover {
  background: #fecaca;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.btn-action {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 6px;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action svg {
  width: 16px;
  height: 16px;
}

.btn-action.edit:hover {
  background: var(--color-primary-light);
  color: var(--color-primary);
}

.btn-action.delete:hover {
  background: #fee2e2;
  color: #dc2626;
}
</style>
