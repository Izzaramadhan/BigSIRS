<script setup>
import { ref, onMounted, watch } from 'vue'
import { useMedicineUnits } from '@/composables/useMedicineUnits'
import MedicineUnitFormModal from '@/views/master-data/logistics/components/MedicineUnitFormModal.vue'

const {
  medicineUnits,
  meta,
  loading,
  error,
  fetchMedicineUnits,
  deleteMedicineUnit
} = useMedicineUnits()

const notification = ref(null)

const showToast = (title, message, type = 'success') => {
  notification.value = { title, message, type }
  setTimeout(() => {
    notification.value = null
  }, 3000)
}

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)

const isFormModalOpen = ref(false)
const selectedUnit = ref(null)

const isDeleteModalOpen = ref(false)
const unitToDelete = ref(null)
const deleteError = ref(null)

const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

const handlePageChange = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  currentPage.value = page
  loadData()
}

const loadData = async () => {
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value
    }
    await fetchMedicineUnits(params)
  } catch (err) {
    console.error('Failed to load medicine categories:', err)
  }
}

const openAddModal = () => {
  selectedUnit.value = null
  isFormModalOpen.value = true
}

const openEditModal = (unit) => {
  selectedUnit.value = { ...unit }
  isFormModalOpen.value = true
}

const onFormSuccess = () => {
  isFormModalOpen.value = false
  loadData()
  showToast(
    'Berhasil',
    `Satuan Obat berhasil ${selectedUnit.value ? 'diperbarui' : 'ditambahkan'}`,
    'success'
  )
}

const confirmDelete = (unit) => {
  unitToDelete.value = unit
  deleteError.value = null
  isDeleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!unitToDelete.value) return
  
  try {
    const result = await deleteMedicineUnit(unitToDelete.value.id)
    if (result) {
      isDeleteModalOpen.value = false
      if (medicineUnits.value.length === 1 && currentPage.value > 1) {
        currentPage.value--
      }
      loadData()
      showToast('Berhasil', 'Satuan Obat berhasil dihapus', 'success')
    } else {
      deleteError.value = 'Gagal menghapus Satuan Obat.'
    }
  } catch (err) {
    console.error('Failed to delete medicine unit:', err)
  }
}

let searchTimeout
watch(searchQuery, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    handleSearch()
  }, 300)
})

onMounted(() => {
  loadData()
})

const getRowNumber = (index) => {
  return (currentPage.value - 1) * perPage.value + index + 1;
};
</script>

<template>
  <div class="page-container">
    <!-- Notification Toast -->
    <div v-if="notification" class="notification-toast" :class="`toast-${notification.type}`">
      {{ notification.message }}
    </div>

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="breadcrumbs">
          <span>Dashboard</span>
          <span class="separator">/</span>
          <span>Logistik</span>
          <span class="separator">/</span>
          <span class="current">Satuan Obat</span>
        </div>
        <h1 class="page-title">Satuan Obat</h1>
        <p class="page-subtitle">Kelola master data Satuan Obat.</p>
      </div>
      
      <div class="header-actions">
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Satuan Obat
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="page-content">
      <!-- Filters -->
      <div class="filters-container">
        <div class="search-box">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input 
            type="text" 
            class="search-input" 
            placeholder="Cari Satuan Obat..." 
            v-model="searchQuery" 
          />
        </div>
        
        <div class="filter-actions">
          <select v-model="perPage" class="form-select filter-select" @change="handleSearch">
            <option :value="10">10 / halaman</option>
            <option :value="25">25 / halaman</option>
            <option :value="50">50 / halaman</option>
          </select>
        </div>
      </div>
      
      <div v-if="error" class="error-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <p>{{ error }}</p>
        <button class="btn-outline" @click="loadData">Coba Lagi</button>
      </div>
      
      <template v-else>
        <div v-if="!loading && medicineUnits.length === 0" class="empty-state" style="text-align: center; padding: 4rem 2rem;">
          <p>Data tidak ditemukan</p>
        </div>
        
        <template v-else>
          <div class="table-wrapper">
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no text-center">No</th>
                    <th class="col-name">Nama Satuan</th>
                    <th>Deskripsi</th>
                    <th class="col-actions text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="loading">
                    <tr v-for="i in 5" :key="`skeleton-${i}`" class="skeleton-row">
                      <td class="text-center"><div class="skeleton-box skeleton-small mx-auto"></div></td>
                      <td><div class="skeleton-box skeleton-medium"></div></td>
                      <td><div class="skeleton-box skeleton-large"></div></td>
                      <td>
                        <div class="action-buttons">
                          <div class="skeleton-box skeleton-circle"></div>
                          <div class="skeleton-box skeleton-circle"></div>
                        </div>
                      </td>
                    </tr>
                  </template>

                  <template v-else>
                    <tr v-for="(item, index) in medicineUnits" :key="item.id">
                      <td class="col-no text-center text-muted">{{ getRowNumber(index) }}</td>
                      <td class="col-name font-medium text-navy">{{ item.name }}</td>
                      <td class="text-muted">{{ item.description || '-' }}</td>
                      <td class="actions-cell">
                        <div class="action-buttons">
                          <button type="button" class="btn-action edit" @click="openEditModal(item)" aria-label="Edit" title="Edit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                          </button>
                          <button type="button" class="btn-action delete" @click="confirmDelete(item)" aria-label="Hapus" title="Hapus">
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
                  </template>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Pagination -->
          <div class="pagination-container" v-if="meta && meta.last_page > 1">
            <div class="pagination-info">
              Menampilkan {{ meta.from || 0 }} 
              sampai {{ meta.to || 0 }} 
              dari {{ meta.total || 0 }} entri
            </div>
            
            <div class="pagination-controls">
              <button 
                class="page-btn" 
                :disabled="currentPage === 1 || loading"
                @click="handlePageChange(currentPage - 1)"
              >
                Sebelumnya
              </button>
              
              <div class="page-numbers">
                <button 
                  v-for="p in meta.last_page" 
                  :key="p"
                  class="page-btn page-number"
                  :class="{ 'active': p === currentPage }"
                  @click="handlePageChange(p)"
                  :disabled="loading"
                  v-show="p === 1 || p === meta.last_page || Math.abs(p - currentPage) <= 1"
                >
                  {{ p }}
                </button>
              </div>
              
              <button 
                class="page-btn" 
                :disabled="currentPage === meta.last_page || loading"
                @click="handlePageChange(currentPage + 1)"
              >
                Selanjutnya
              </button>
            </div>
          </div>
        </template>
      </template>
    </div>
    
    <MedicineUnitFormModal 
      :is-open="isFormModalOpen"
      :unit="selectedUnit"
      @close="isFormModalOpen = false"
      @saved="onFormSuccess"
    />

    <!-- Delete Dialog -->
    <Teleport to="body">
      <div v-if="isDeleteModalOpen" class="modal-overlay" @click.self="isDeleteModalOpen = false">
        <div class="modal-content modal-sm">
          <div class="modal-header">
            <h3 class="text-danger">Hapus Satuan Obat</h3>
            <button class="btn-close" @click="isDeleteModalOpen = false">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
          <div class="modal-body">
            <p>Apakah Anda yakin ingin menghapus Satuan Obat <strong>{{ unitToDelete?.name }}</strong>?</p>
            <p class="delete-warning">Tindakan ini tidak dapat dibatalkan.</p>
            <div v-if="deleteError" class="alert-error mt-2">
              {{ deleteError }}
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-cancel" @click="isDeleteModalOpen = false">Batal</button>
            <button class="btn-danger" @click="handleDelete">Ya, Hapus</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.page-container {
  max-width: 100%;
  position: relative;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.breadcrumbs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  margin-bottom: 0.75rem;
}

.separator {
  color: var(--color-border-soft);
}

.current {
  color: var(--color-primary);
  font-weight: 500;
}

.page-title {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.page-subtitle {
  margin: 0;
  font-size: 0.9rem;
  color: var(--color-text-secondary);
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  padding: 0.6rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

.btn-primary svg {
  width: 18px;
  height: 18px;
}

.btn-outline {
  background: transparent;
  color: var(--color-primary);
  border: 1px solid var(--color-primary);
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
}

.btn-outline:hover {
  background: var(--color-primary-light);
}

.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  background: #fef2f2;
  border-radius: 8px;
  color: #991b1b;
  text-align: center;
}

.error-state svg {
  width: 48px;
  height: 48px;
  color: #ef4444;
  margin-bottom: 1rem;
}

.error-state p {
  margin: 0 0 1rem 0;
  font-weight: 500;
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
}

.page-btn {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  background: var(--color-page-bg);
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-number {
  min-width: 32px;
  text-align: center;
}

.page-number.active {
  background: var(--color-primary);
  color: #ffffff;
  border-color: var(--color-primary);
}

.notification-toast {
  position: fixed;
  top: 1rem;
  right: 1rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  color: #ffffff;
  font-weight: 500;
  font-size: 0.9rem;
  z-index: 1000;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  animation: slideIn 0.3s ease-out forwards;
}

.toast-success {
  background-color: #10b981;
}

.toast-error {
  background-color: #ef4444;
}

@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

@media (max-width: 640px) {
  .page-header {
    flex-direction: column;
  }
  
  .pagination-container {
    flex-direction: column;
    align-items: center;
  }
}

.filters-container {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
  justify-content: space-between;
  align-items: center;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 250px;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  color: var(--color-text-secondary);
}

.search-input {
  width: 100%;
  padding: 0.5rem 1rem 0.5rem 2.5rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.9rem;
  transition: all 0.2s;
  box-sizing: border-box;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.filter-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.form-select.filter-select {
  padding: 0.5rem 2rem 0.5rem 1rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.9rem;
  background-color: white;
  min-width: 140px;
}

.form-select.filter-select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.table-wrapper {
  width: 100%;
}

.table-container {
  width: 100%;
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  background: #ffffff;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  min-width: 600px;
}

.data-table th {
  background: #f8fafc;
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  white-space: nowrap;
}

.data-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--color-border-soft);
  color: var(--color-text-secondary);
  vertical-align: middle;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.col-no { width: 48px; }
.col-name { min-width: 180px; }
.col-actions { width: 140px; }

.font-medium {
  font-weight: 500;
}

.text-navy {
  color: var(--color-text-navy);
}

.text-muted {
  color: #64748b;
}

.text-center {
  text-align: center;
}

.mx-auto {
  margin-left: auto;
  margin-right: auto;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.badge-active {
  background-color: #dcfce7;
  color: #166534;
}

.badge-inactive {
  background-color: #f3f4f6;
  color: #4b5563;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
  justify-content: center;
}

.btn-action {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  border-radius: 6px;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action svg {
  width: 16px;
  height: 16px;
}

.btn-action.toggle-status:hover { background: #f3f4f6; color: #4b5563; }
.btn-action.edit:hover { background: #e0f2fe; color: #0284c7; }
.btn-action.delete:hover { background: #fef2f2; color: #ef4444; }

/* Skeleton Loading */
.skeleton-box {
  height: 16px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
  border-radius: 4px;
}

.skeleton-small { width: 40px; }
.skeleton-medium { width: 80px; }
.skeleton-large { width: 150px; }
.skeleton-circle { width: 24px; height: 24px; border-radius: 50%; }

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (max-width: 768px) {
  .search-box {
    max-width: 100%;
  }
}

/* Custom modal styles for view level */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 500;
  padding: 1rem;
  backdrop-filter: blur(2px);
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-content.modal-sm {
  max-width: 400px;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  position: sticky;
  top: 0;
  background: #ffffff;
  z-index: 1;
}

.btn-close {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-text-secondary);
  padding: 0.25rem;
  border-radius: 4px;
  display: flex;
  align-items: center;
  transition: all 0.2s;
}

.btn-close:hover { color: var(--color-text-navy); background: var(--color-page-bg); }
.btn-close svg { width: 20px; height: 20px; }

.text-danger {
  color: #ef4444;
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
}

.modal-body {
  padding: 1.5rem;
}

.delete-warning {
  font-size: 0.875rem;
  color: #64748b;
  margin-top: 0.5rem;
  margin-bottom: 0;
}

.alert-error {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
  border-radius: 6px;
  font-size: 0.875rem;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  background: #f8fafc;
  border-radius: 0 0 12px 12px;
}

.btn-cancel {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #334155;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover { background: #f1f5f9; }

.btn-danger {
  background: #ef4444;
  border: none;
  color: #ffffff;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger:hover { background: #dc2626; }
</style>
