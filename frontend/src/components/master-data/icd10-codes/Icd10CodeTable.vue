<script setup>
defineProps({
  codes: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['edit', 'status-change', 'delete'])

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value || 0)
}

const confirmDelete = (code) => {
  if (confirm(`Apakah Anda yakin ingin menghapus ICD-10 "${code.code} - ${code.name}"?\nPenghapusan ini tidak akan menghapus riwayat diagnosis pasien.`)) {
    emit('delete', code.id)
  }
}
</script>

<template>
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th width="10%">Kode</th>
          <th width="25%">Nama Diagnosa</th>
          <th width="20%">Nama (EN)</th>
          <th width="15%">INACBG</th>
          <th width="15%">Tarif Kelas (1/2/3)</th>
          <th width="10%">Status</th>
          <th width="5%" class="text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="7" class="text-center py-4">Memuat data...</td>
        </tr>
        <tr v-else-if="!codes.length">
          <td colspan="7" class="text-center py-4 text-secondary">Tidak ada data ICD-10</td>
        </tr>
        <tr v-else v-for="item in codes" :key="item.id">
          <td>
            <span class="font-medium text-primary">{{ item.code }}</span>
          </td>
          <td>
            <div class="font-medium">{{ item.name }}</div>
            <div class="text-xs text-secondary mt-1 line-clamp-1" :title="item.description" v-if="item.description">
              {{ item.description }}
            </div>
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
            <div class="tariff-list">
              <div class="tariff-item" title="Kelas 1">
                <span class="tariff-label">K1</span>
                <span>{{ formatCurrency(item.class_1_tariff) }}</span>
              </div>
              <div class="tariff-item" title="Kelas 2">
                <span class="tariff-label">K2</span>
                <span>{{ formatCurrency(item.class_2_tariff) }}</span>
              </div>
              <div class="tariff-item" title="Kelas 3">
                <span class="tariff-label">K3</span>
                <span>{{ formatCurrency(item.class_3_tariff) }}</span>
              </div>
            </div>
          </td>
          <td>
            <button 
              class="status-toggle"
              :class="item.is_active ? 'status-active' : 'status-inactive'"
              @click="emit('status-change', item.id, !item.is_active)"
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
  font-weight: 600;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: var(--color-page-bg);
}

.data-table td {
  font-size: 0.875rem;
  color: var(--color-text-navy);
  vertical-align: top;
}

.data-table tbody tr:hover {
  background-color: var(--color-page-bg);
}

.text-center { text-align: center; }
.text-right { text-align: right; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }

.font-medium { font-weight: 500; }
.text-primary { color: var(--color-primary); }
.text-secondary { color: var(--color-text-secondary); }
.text-xs { font-size: 0.75rem; }
.text-danger { color: #ef4444; }
.mt-1 { margin-top: 0.25rem; }
.mb-1 { margin-bottom: 0.25rem; }
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.badge {
  display: inline-block;
  padding: 0.15rem 0.4rem;
  font-size: 0.7rem;
  font-weight: 600;
  border-radius: 4px;
}
.badge-gray {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.tariff-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.75rem;
}
.tariff-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.tariff-label {
  background: #e2e8f0;
  color: var(--color-text-secondary);
  padding: 0.1rem 0.3rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.65rem;
  min-width: 20px;
  text-align: center;
}

.status-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-active {
  background: #ecfdf5;
  color: #059669;
}
.status-active .status-dot {
  background: #10b981;
  box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}
.status-active:hover {
  background: #d1fae5;
}

.status-inactive {
  background: #fef2f2;
  color: #dc2626;
}
.status-inactive .status-dot {
  background: #ef4444;
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
}
.status-inactive:hover {
  background: #fee2e2;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  background: transparent;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #f1f5f9;
  color: var(--color-primary);
}

.btn-icon.text-danger:hover {
  color: #ef4444;
  background: #fef2f2;
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.icon {
  width: 16px;
  height: 16px;
}
</style>
