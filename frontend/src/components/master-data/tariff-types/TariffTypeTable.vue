<script setup>
const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  sortConfig: {
    type: Object,
    default: () => ({ column: 'created_at', direction: 'desc' })
  }
});

const emit = defineEmits(['sort', 'edit', 'delete', 'toggle-status']);

const handleSort = (column) => {
  let direction = 'asc';
  if (props.sortConfig.column === column && props.sortConfig.direction === 'asc') {
    direction = 'desc';
  }
  emit('sort', { column, direction });
};

const getSortIcon = (column) => {
  if (props.sortConfig.column !== column) return 'none';
  return props.sortConfig.direction === 'asc' ? 'up' : 'down';
};

const formatPercentage = (val) => {
  // If it's a whole number, don't show decimals
  return Number(val) % 1 === 0 ? Number(val) : Number(val).toFixed(2);
};
</script>

<template>
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th width="5%">No</th>
          <th width="15%" class="sortable" @click="handleSort('name')">
            <div class="th-content">
              Jenis Tarif
              <span class="sort-icon" :class="getSortIcon('name')">
                <svg v-if="getSortIcon('name') === 'up'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
                <svg v-else-if="getSortIcon('name') === 'down'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-300"><polyline points="7 15 12 20 17 15"></polyline><polyline points="7 9 12 4 17 9"></polyline></svg>
              </span>
            </div>
          </th>
          <th width="10%" class="sortable" @click="handleSort('code')">
            <div class="th-content">
              Kode
              <span class="sort-icon" :class="getSortIcon('code')">
                <svg v-if="getSortIcon('code') === 'up'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
                <svg v-else-if="getSortIcon('code') === 'down'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-300"><polyline points="7 15 12 20 17 15"></polyline><polyline points="7 9 12 4 17 9"></polyline></svg>
              </span>
            </div>
          </th>
          <th width="30%">Komponen</th>
          <th width="20%">Deskripsi</th>
          <th width="10%">Status</th>
          <th width="10%">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="7" class="text-center py-4">Memuat data...</td>
        </tr>
        <tr v-else-if="items.length === 0">
          <td colspan="7" class="text-center py-4 text-gray-500">Tidak ada data ditemukan</td>
        </tr>
        <tr v-else v-for="(item, index) in items" :key="item.id">
          <td>{{ index + 1 }}</td>
          <td>
            <div class="fw-medium">{{ item.name }}</div>
          </td>
          <td>
            <span v-if="item.code">{{ item.code }}</span>
            <span v-else class="text-gray-400 italic text-sm">Tidak ada</span>
          </td>
          <td>
            <div v-if="!item.components || item.components.length === 0" class="text-red-500 text-sm">
              Belum memiliki komponen
            </div>
            <div v-else class="components-list">
              <!-- Show up to 3 components as chips -->
              <div v-for="comp in item.components.slice(0, 3)" :key="comp.id" class="comp-chip">
                <span class="comp-name" :class="{'line-through text-gray-400': comp.component && !comp.component.is_active}">
                  {{ comp.component ? comp.component.name : 'Unknown' }}
                </span>
                <span class="comp-pct">{{ formatPercentage(comp.percentage) }}%</span>
              </div>
              
              <div v-if="item.components.length > 3" class="comp-chip more-chip">
                +{{ item.components.length - 3 }} lainnya
              </div>
            </div>
          </td>
          <td>
            <div class="text-sm truncate-text" :title="item.description">{{ item.description || '-' }}</div>
          </td>
          <td>
            <button 
              class="status-badge" 
              :class="item.is_active ? 'active' : 'inactive'"
              @click="$emit('toggle-status', item)"
              title="Klik untuk mengubah status"
            >
              {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
            </button>
          </td>
          <td>
            <div class="action-buttons">
              <button class="btn-icon text-blue" @click="$emit('edit', item)" title="Edit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>
              <button class="btn-icon text-red" @click="$emit('delete', item)" title="Hapus">
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
  background: #fff;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.data-table th,
.data-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border-soft);
}

.data-table th {
  background: #f8fafc;
  font-weight: 600;
  color: var(--color-text-navy);
  white-space: nowrap;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.th-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background: #f1f5f9;
}

.sort-icon {
  display: flex;
  flex-direction: column;
}

.sort-icon svg {
  width: 14px;
  height: 14px;
}

.sort-icon.up, .sort-icon.down {
  color: var(--color-primary);
}

.text-gray-300 {
  color: #cbd5e1;
}

.text-gray-400 {
  color: #94a3b8;
}

.text-gray-500 {
  color: #64748b;
}

.text-red-500 {
  color: #ef4444;
}

.text-sm {
  font-size: 0.8rem;
}

.italic {
  font-style: italic;
}

.fw-medium {
  font-weight: 500;
  color: var(--color-text-navy);
}

.truncate-text {
  max-width: 250px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Components Chips */
.components-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.comp-chip {
  display: inline-flex;
  align-items: center;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 0.15rem 0.5rem;
  font-size: 0.8rem;
}

.comp-name {
  color: #334155;
  margin-right: 0.4rem;
  max-width: 120px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.comp-pct {
  font-weight: 600;
  color: var(--color-primary);
}
.more-chip {
  background: #e2e8f0;
  color: #475569;
  font-weight: 500;
}

.line-through {
  text-decoration: line-through;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 600;
}
.mt-1 {
  margin-top: 0.25rem;
}

/* Status Button */
.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: opacity 0.2s;
}

.status-badge:hover {
  opacity: 0.8;
}

.status-badge.active {
  background: #dcfce7;
  color: #166534;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #991b1b;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.4rem;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.btn-icon:hover {
  background: #f1f5f9;
}

.btn-icon svg {
  width: 18px;
  height: 18px;
}

.text-blue {
  color: var(--color-primary);
}

.text-red {
  color: #ef4444;
}

.py-4 {
  padding-top: 1rem;
  padding-bottom: 1rem;
}

.text-center {
  text-align: center;
}
</style>
