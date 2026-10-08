<script setup>
import { ref, onMounted, watch } from 'vue'
import { useMedicines } from '@/composables/master-data/logistics/useMedicines'
import MedicineModal from './components/MedicineModal.vue'

const {
  items,
  loading,
  error,
  pagination,
  fetchItems,
  deleteItem,
  toggleStatus
} = useMedicines()

const searchQuery = ref('')
const showModal = ref(false)
const selectedItem = ref(null)

const handleSearch = () => {
  pagination.value.page = 1
  fetchItems({ search: searchQuery.value })
}

const handlePageChange = (page) => {
  pagination.value.page = page
  fetchItems({ search: searchQuery.value })
}

const handleAdd = () => {
  selectedItem.value = null
  showModal.value = true
}

const handleEdit = (item) => {
  selectedItem.value = item
  showModal.value = true
}

const handleSaved = () => {
  fetchItems({ search: searchQuery.value })
}

const handleDelete = async (item) => {
  if (confirm(`Anda yakin ingin menghapus obat "${item.name}"?`)) {
    try {
      await deleteItem(item.id)
      fetchItems({ search: searchQuery.value })
    } catch {
      // Error handled by composable
    }
  }
}

const handleToggleStatus = async (item) => {
  try {
    await toggleStatus(item)
    // No need to fetch items again as toggleStatus updates the item directly
  } catch {
    // Error handled by composable
  }
}

watch(() => pagination.value.rowsPerPage, () => {
  pagination.value.page = 1
  fetchItems({ search: searchQuery.value })
})

onMounted(() => {
  fetchItems()
})
</script>

<template>
  <div class="page-container">
    <div class="breadcrumb">
      <span>Home</span>
      <span class="separator">•</span>
      <span>Master Data</span>
      <span class="separator">•</span>
      <span>Data Logistik</span>
      <span class="separator">•</span>
      <span class="current">Obat</span>
    </div>

    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <svg xmlns="http://www.w3.org/2000/svg" class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          Obat
        </h1>
        <p class="page-subtitle">Manajemen Master Data Obat</p>
      </div>
    </div>

    <div class="card">
      <div class="filters-container">
        <div class="filter-actions">
          <button class="btn-primary" @click="handleAdd">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Obat
          </button>
          
          <select v-model="pagination.rowsPerPage" class="form-select filter-select">
            <option :value="10">10 data</option>
            <option :value="25">25 data</option>
            <option :value="50">50 data</option>
            <option :value="100">100 data</option>
          </select>
        </div>

        <div class="search-box">
          <input 
            type="text" 
            v-model="searchQuery" 
            @keyup.enter="handleSearch"
            placeholder="Cari nama / kode..." 
            class="search-input"
          >
        </div>
      </div>

      <div v-if="error && !items.length" class="error-state">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <p>{{ error }}</p>
        <button class="btn-outline" @click="handleSearch">Coba Lagi</button>
      </div>

      <div class="table-wrapper">
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th width="50">No</th>
                <th width="150">Kode</th>
                <th>Nama Obat</th>
                <th>Kategori Obat</th>
                <th>Satuan</th>
                <th width="120">Status</th>
                <th width="120" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading && !items.length">
                <td colspan="7" class="text-center py-4">Memuat data...</td>
              </tr>
              <tr v-else-if="!items.length">
                <td colspan="7" class="text-center py-4">Tidak ada data</td>
              </tr>
              <tr v-for="(item, index) in items" :key="item.id">
                <td>{{ (pagination.page - 1) * pagination.rowsPerPage + index + 1 }}</td>
                <td>
                  <div class="code-column">
                    <span class="main-code">{{ item.code || '-' }}</span>
                    <span v-if="item.kfa_code" class="sub-code text-muted text-sm">KFA: {{ item.kfa_code }}</span>
                  </div>
                </td>
                <td class="fw-medium">{{ item.name }}</td>
                <td>{{ item.category?.name || '-' }}</td>
                <td>{{ item.unit?.name || '-' }}</td>
                <td>
                  <label class="toggle-switch">
                    <input 
                      type="checkbox" 
                      :checked="item.is_active"
                      @change="handleToggleStatus(item)"
                    >
                    <span class="toggle-slider"></span>
                  </label>
                </td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-icon btn-edit" @click="handleEdit(item)" title="Edit">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button class="btn-icon btn-delete" @click="handleDelete(item)" title="Hapus">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="pagination-container" v-if="pagination.rowsNumber > 0">
        <div class="pagination-info">
          Menampilkan {{ (pagination.page - 1) * pagination.rowsPerPage + 1 }} - 
          {{ Math.min(pagination.page * pagination.rowsPerPage, pagination.rowsNumber) }} 
          dari {{ pagination.rowsNumber }} data
        </div>
        <div class="pagination-actions">
          <button 
            class="btn-pagination" 
            :disabled="pagination.page === 1"
            @click="handlePageChange(pagination.page - 1)"
          >
            Sebelumnya
          </button>
          <div class="pagination-numbers">
            <template v-for="p in Math.ceil(pagination.rowsNumber / pagination.rowsPerPage)" :key="p">
              <button 
                v-if="Math.abs(p - pagination.page) < 3 || p === 1 || p === Math.ceil(pagination.rowsNumber / pagination.rowsPerPage)"
                class="btn-pagination-number"
                :class="{ active: p === pagination.page }"
                @click="handlePageChange(p)"
              >
                {{ p }}
              </button>
              <span v-else-if="Math.abs(p - pagination.page) === 3" class="pagination-dots">...</span>
            </template>
          </div>
          <button 
            class="btn-pagination" 
            :disabled="pagination.page >= Math.ceil(pagination.rowsNumber / pagination.rowsPerPage)"
            @click="handlePageChange(pagination.page + 1)"
          >
            Selanjutnya
          </button>
        </div>
      </div>
    </div>

    <MedicineModal
      :show="showModal"
      :item="selectedItem"
      @close="showModal = false"
      @saved="handleSaved"
    />
  </div>
</template>

<style scoped>
/* Same CSS structure as RadiologyGroupsView.vue */
.page-container {
  padding: 24px;
  background-color: #f8fafc;
  min-height: calc(100vh - 64px);
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #64748b;
  margin-bottom: 24px;
}

.separator {
  color: #cbd5e1;
}

.current {
  color: #0f172a;
  font-weight: 500;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 24px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 4px 0;
}

.title-icon {
  width: 28px;
  height: 28px;
  color: #3b82f6;
}

.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 14px;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
  padding: 20px;
}

.filters-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  gap: 16px;
  flex-wrap: wrap;
}

.filter-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  background-color: #3b82f6;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary:hover {
  background-color: #2563eb;
}

.btn-primary svg {
  width: 20px;
  height: 20px;
}

.form-select {
  padding: 8px 32px 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  outline: none;
  background-color: white;
  color: #0f172a;
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 8px center;
  background-repeat: no-repeat;
  background-size: 20px 20px;
}

.search-box {
  flex: 1;
  max-width: 300px;
}

.search-input {
  width: 100%;
  padding: 8px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  outline: none;
  transition: border-color 0.2s;
}

.search-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.table-wrapper {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  white-space: nowrap;
}

.data-table th,
.data-table td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.data-table th {
  background-color: #f8fafc;
  font-weight: 600;
  color: #475569;
  font-size: 14px;
}

.data-table td {
  color: #0f172a;
  font-size: 14px;
}

.fw-medium {
  font-weight: 500;
}

.text-center {
  text-align: center !important;
}

/* Toggle Switch */
.toggle-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  transition: .4s;
  border-radius: 24px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .toggle-slider {
  background-color: #10b981;
}

input:checked + .toggle-slider:before {
  transform: translateX(20px);
}

.action-buttons {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon svg {
  width: 18px;
  height: 18px;
}

.btn-edit {
  background-color: #eff6ff;
  color: #3b82f6;
}

.btn-edit:hover {
  background-color: #dbeafe;
}

.btn-delete {
  background-color: #fef2f2;
  color: #ef4444;
}

.btn-delete:hover {
  background-color: #fee2e2;
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.pagination-info {
  color: #64748b;
  font-size: 14px;
}

.pagination-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.btn-pagination {
  padding: 6px 12px;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 6px;
  color: #475569;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-pagination:hover:not(:disabled) {
  background-color: #f8fafc;
  border-color: #cbd5e1;
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-numbers {
  display: flex;
  gap: 4px;
}

.btn-pagination-number {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 6px;
  color: #475569;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-pagination-number:hover {
  background-color: #f8fafc;
}

.btn-pagination-number.active {
  background-color: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.pagination-dots {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  color: #64748b;
}

.code-column {
  display: flex;
  flex-direction: column;
}
</style>
