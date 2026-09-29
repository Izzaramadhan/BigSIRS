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

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};
</script>

<template>
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th width="5%">No</th>
          <th width="20%" class="sortable" @click="handleSort('name')">
            <div class="th-content">
              Nama Tindakan
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
          <th width="15%">Kategori / ICD-9</th>
          <th width="30%">Tarif (Jenis & Total)</th>
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
            <div class="text-sm">
              <div v-if="item.category" class="font-medium text-navy">{{ item.category.name }}</div>
              <div v-if="item.icd9_cm" class="text-gray-500 text-xs mt-1">ICD: {{ item.icd9_cm.code }}</div>
            </div>
          </td>
          <td>
            <div v-if="!item.tariffs || item.tariffs.length === 0" class="text-red-500 text-sm">
              Belum memiliki tarif
            </div>
            <div v-else class="tariffs-list">
              <div v-for="tariff in item.tariffs" :key="tariff.id" class="tariff-item">
                <span class="tariff-name">{{ tariff.tariff_type?.name || 'Unknown' }}</span>
                <span class="tariff-total">{{ formatCurrency(tariff.total_amount) }}</span>
              </div>
            </div>
          </td>
          <td>
            <button 
              class="status-badge" 
              :class="item.is_visible ? 'active' : 'inactive'"
              @click="$emit('toggle-status', item)"
              title="Klik untuk mengubah status visibilitas"
            >
              {{ item.is_visible ? 'Tampil' : 'Sembunyi' }}
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
.text-xs {
  font-size: 0.75rem;
}

.italic {
  font-style: italic;
}

.fw-medium {
  font-weight: 500;
  color: var(--color-text-navy);
}

.font-medium {
  font-weight: 500;
}
.text-navy {
  color: var(--color-text-navy);
}
.mt-1 {
  margin-top: 0.25rem;
}

/* Tariffs */
.tariffs-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.tariff-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  padding: 0.25rem 0.5rem;
  font-size: 0.8rem;
}

.tariff-name {
  color: #334155;
  font-weight: 500;
}

.tariff-total {
  font-weight: 600;
  color: var(--color-primary);
}

/* Badges */
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
