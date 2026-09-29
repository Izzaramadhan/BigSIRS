<script setup>
defineProps({
  items: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['edit', 'toggle-status', 'delete'])

const confirmDelete = (item) => {
  if (confirm(`Apakah Anda yakin ingin menghapus ICD-9-CM "${item.code} - ${item.name}"?\nPenghapusan ini tidak akan menghapus riwayat prosedur pasien.`)) {
    emit('delete', item.id)
  }
}
</script>

<template>
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th width="10%">Kode</th>
          <th width="25%">Nama Prosedur</th>
          <th width="20%">Nama (EN)</th>
          <th width="15%">INACBG</th>
          <th width="15%">Deskripsi</th>
          <th width="10%">Status</th>
          <th width="5%" class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="7" class="text-center py-4">Memuat data...</td>
        </tr>
        <tr v-else-if="!items.length">
          <td colspan="7" class="text-center py-4 text-secondary">Tidak ada data ICD-9-CM</td>
        </tr>
        <tr v-else v-for="item in items" :key="item.id">
          <td>
            <span class="font-medium text-primary">{{ item.code }}</span>
          </td>
          <td>
            <div class="font-medium">{{ item.name }}</div>
          </td>
          <td class="text-secondary">
            {{ item.english_name || '-' }}
          </td>
          <td>
            <div v-if="item.inacbg_code">
              <span class="badge badge-gray mb-1">{{ item.inacbg_code }}</span>
              <div class="text-xs text-secondary line-clamp-1" :title="item.inacbg_name">
                {{ item.inacbg_name }}
              </div>
            </div>
            <span v-else class="text-secondary">-</span>
          </td>
          <td>
            <div class="text-sm text-secondary line-clamp-2" :title="item.description">
              {{ item.description || '-' }}
            </div>
          </td>
          <td>
            <button 
              class="status-toggle"
              :class="item.is_active ? 'status-active' : 'status-inactive'"
              @click="emit('toggle-status', item.id, !item.is_active)"
              :disabled="loading"
            >
              <span class="status-dot"></span>
              {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
            </button>
          </td>
          <td class="text-right">
            <div class="action-buttons">
              <button 
                class="btn-icon" 
                title="Edit"
                @click="emit('edit', item)"
                :disabled="loading"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>
              <button 
                class="btn-icon text-danger" 
                title="Hapus"
                @click="confirmDelete(item)"
                :disabled="loading"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon">
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
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.data-table th,
.data-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.data-table th {
  background: var(--color-page-bg);
  font-weight: 600;
  color: var(--color-text-navy);
  font-size: 0.875rem;
}

.data-table tbody tr {
  transition: background-color 0.2s;
}

.data-table tbody tr:hover {
  background-color: var(--color-page-bg);
}

.font-medium {
  font-weight: 500;
}

.text-primary {
  color: var(--color-primary);
}

.text-secondary {
  color: var(--color-text-secondary);
}

.text-danger {
  color: #ef4444;
}

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.py-4 {
  padding-top: 1rem;
  padding-bottom: 1rem;
}

.mt-1 {
  margin-top: 0.25rem;
}

.mb-1 {
  margin-bottom: 0.25rem;
}

.text-xs {
  font-size: 0.75rem;
}

.text-sm {
  font-size: 0.875rem;
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-gray {
  background: var(--color-page-bg);
  color: var(--color-text-navy);
  border: 1px solid var(--color-border-soft);
}

.status-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.status-active {
  background: #ecfdf5;
  color: #059669;
}

.status-active:hover {
  background: #d1fae5;
}

.status-active .status-dot {
  background: #10b981;
}

.status-inactive {
  background: #fef2f2;
  color: #dc2626;
}

.status-inactive:hover {
  background: #fee2e2;
}

.status-inactive .status-dot {
  background: #ef4444;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft);
  background: white;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover:not(:disabled) {
  background: var(--color-page-bg);
  color: var(--color-primary);
  border-color: var(--color-border);
}

.btn-icon.text-danger:hover:not(:disabled) {
  color: #ef4444;
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.icon {
  width: 1rem;
  height: 1rem;
}
</style>
