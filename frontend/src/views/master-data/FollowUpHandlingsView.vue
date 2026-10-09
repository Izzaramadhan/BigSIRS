<script setup>
import { ref, onMounted, watch } from 'vue'
import { useFollowUpHandlings } from '@/composables/useFollowUpHandlings'
import FollowUpHandlingFormModal from '@/views/master-data/components/FollowUpHandlingFormModal.vue'

const {
  handlings,
  loading,
  error,
  pagination,
  fetchHandlings,
  createHandling,
  updateHandling,
  deleteHandling
} = useFollowUpHandlings()

const notification = ref(null)

const showToast = (title, message, type = 'success') => {
  notification.value = { title, message, type }
  setTimeout(() => {
    notification.value = null
  }, 3000)
}

const isFormModalOpen = ref(false)
const selectedHandling = ref(null)

const isDeleteModalOpen = ref(false)
const handlingToDelete = ref(null)
const deleteError = ref(null)

const searchQuery = ref('')
const perPage = ref(10)

const handleSearch = () => {
  fetchHandlings({ page: 1, per_page: perPage.value, search: searchQuery.value })
}

const handlePageChange = (page) => {
  if (page < 1 || page > pagination.value.lastPage) return
  fetchHandlings({ page, per_page: perPage.value, search: searchQuery.value })
}

const openAddModal = () => {
  selectedHandling.value = null
  isFormModalOpen.value = true
}

const openEditModal = (handling) => {
  selectedHandling.value = JSON.parse(JSON.stringify(handling))
  isFormModalOpen.value = true
}

const onFormSubmit = async (formData) => {
  try {
    if (selectedHandling.value) {
      await updateHandling(selectedHandling.value.id, formData)
    } else {
      await createHandling(formData)
    }
    isFormModalOpen.value = false
    fetchHandlings({ page: pagination.value.currentPage, per_page: perPage.value, search: searchQuery.value })
    showToast(
      'Berhasil',
      `Penanganan lanjutan berhasil ${selectedHandling.value ? 'diperbarui' : 'ditambahkan'}`,
      'success'
    )
  } catch (err) {
    console.error('Failed to save handling:', err)
  }
}

const confirmDelete = (handling) => {
  handlingToDelete.value = handling
  deleteError.value = null
  isDeleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!handlingToDelete.value) return
  
  try {
    await deleteHandling(handlingToDelete.value.id)
    isDeleteModalOpen.value = false
    if (handlings.value.length === 1 && pagination.value.currentPage > 1) {
      pagination.value.currentPage--
    }
    fetchHandlings({ page: pagination.value.currentPage, per_page: perPage.value, search: searchQuery.value })
    showToast('Berhasil', 'Penanganan lanjutan berhasil dihapus', 'success')
  } catch (err) {
    deleteError.value = err.response?.data?.message || 'Gagal menghapus penanganan lanjutan.'
    console.error('Failed to delete handling:', err)
  }
}

let searchTimeout
watch(searchQuery, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    handleSearch()
  }, 500)
})

onMounted(() => {
  fetchHandlings({ per_page: perPage.value })
})

const getRowNumber = (index) => {
  if (pagination.value.perPage === 'all') return index + 1
  return (pagination.value.currentPage - 1) * pagination.value.perPage + index + 1
}

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
          <span>Master Data</span>
          <span class="separator">/</span>
          <span class="current">Penanganan Lanjutan</span>
        </div>
        <h1 class="page-title">Master Data Penanganan Lanjutan</h1>
        <p class="page-subtitle">Kelola data referensi penanganan lanjutan pasien.</p>
      </div>
      
      <div class="header-actions">
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Data
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
            placeholder="Cari penanganan..." 
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
        <p>{{ error.includes('403') || error.includes('unauthorized') || error.includes('Unauthorized') ? 'Anda tidak memiliki akses untuk melihat data ini.' : 'Gagal memuat data.' }}</p>
        <button class="btn-outline" @click="fetchHandlings({ page: pagination.currentPage, per_page: perPage, search: searchQuery })">Coba Lagi</button>
      </div>
      
      <template v-else>
        <div v-if="!loading && handlings.length === 0" class="empty-state" style="text-align: center; padding: 4rem 2rem;">
          <p>Data tidak ditemukan</p>
        </div>
        
        <template v-else>
          <div class="table-wrapper">
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no text-center">No</th>
                    <th class="col-name">Nama</th>
                    <th class="col-actions text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="loading">
                    <tr v-for="i in 5" :key="`skeleton-${i}`" class="skeleton-row">
                      <td class="text-center"><div class="skeleton-box skeleton-small mx-auto"></div></td>
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
                    <tr v-for="(handling, index) in handlings" :key="handling.id">
                      <td class="col-no text-center text-muted">{{ getRowNumber(index) }}</td>
                      <td class="col-name font-medium text-navy">{{ handling.name }}</td>
                      <td class="actions-cell">
                        <div class="action-buttons">
                          <button type="button" class="btn-action edit" @click="openEditModal(handling)" aria-label="Edit" title="Edit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                          </button>
                          <button type="button" class="btn-action delete" @click="confirmDelete(handling)" aria-label="Hapus" title="Hapus">
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
          <div class="pagination-container" v-if="!loading && pagination.lastPage > 1">
            <div class="pagination-info">
              Menampilkan <span class="font-medium">{{ handlings.length }}</span> dari <span class="font-medium">{{ pagination.total }}</span> data
            </div>
            
            <div class="pagination-controls">
              <button 
                class="btn-pagination" 
                :disabled="pagination.currentPage === 1"
                @click="handlePageChange(pagination.currentPage - 1)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Sebelumnya
              </button>
              
              <div class="pagination-pages">
                <button 
                  v-for="page in pagination.lastPage" 
                  :key="page"
                  class="btn-page"
                  :class="{ 'active': page === pagination.currentPage }"
                  @click="handlePageChange(page)"
                >
                  {{ page }}
                </button>
              </div>
              
              <button 
                class="btn-pagination" 
                :disabled="pagination.currentPage === pagination.lastPage"
                @click="handlePageChange(pagination.currentPage + 1)"
              >
                Selanjutnya
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </button>
            </div>
          </div>
        </template>
      </template>
    </div>

    <FollowUpHandlingFormModal 
      :is-open="isFormModalOpen"
      :title="selectedHandling ? 'Edit Penanganan Lanjutan' : 'Tambah Penanganan Lanjutan'"
      :initial-data="selectedHandling || {}"
      :loading="loading"
      @close="isFormModalOpen = false"
      @submit="onFormSubmit"
    />

    <!-- Delete Confirmation Modal -->
    <div v-if="isDeleteModalOpen" class="modal-overlay" @click.self="isDeleteModalOpen = false">
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h3 class="modal-title text-danger">Hapus Data</h3>
          <button type="button" class="btn-close" @click="isDeleteModalOpen = false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        
        <div class="modal-body">
          <div class="delete-confirmation">
            <div class="warning-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
            </div>
            <p>Apakah Anda yakin ingin menghapus data <strong>{{ handlingToDelete?.name }}</strong>?</p>
            <p class="text-muted small">Tindakan ini tidak dapat dibatalkan.</p>
            
            <div v-if="deleteError" class="alert alert-danger mt-3">
              {{ deleteError }}
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="isDeleteModalOpen = false" :disabled="loading">Batal</button>
          <button type="button" class="btn btn-danger" @click="handleDelete" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            <span v-else>Hapus</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-container {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.notification-toast {
  position: fixed;
  top: 1.5rem;
  right: 1.5rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  background: white;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  z-index: 1100;
  animation: slideInRight 0.3s ease-out forwards;
  font-weight: 500;
}

.toast-success {
  border-left: 4px solid #10b981;
  color: #047857;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.breadcrumbs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #64748b;
  margin-bottom: 0.75rem;
}

.breadcrumbs .separator {
  color: #cbd5e1;
}

.breadcrumbs .current {
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
  color: #64748b;
  font-size: 0.95rem;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background-color: var(--color-primary);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.15);
}

.btn-primary svg {
  width: 18px;
  height: 18px;
}

.btn-primary:hover {
  background-color: #1d4ed8;
  box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
  transform: translateY(-1px);
}

.page-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--color-border-soft);
  overflow: hidden;
}

.filters-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem;
  border-bottom: 1px solid var(--color-border-soft);
  gap: 1rem;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 250px;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 0.875rem;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #94a3b8;
}

.search-input {
  width: 100%;
  padding: 0.625rem 1rem 0.625rem 2.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  color: var(--color-text-navy);
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.filter-select {
  padding: 0.625rem 2rem 0.625rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  color: var(--color-text-navy);
  background-color: white;
  cursor: pointer;
}

.table-wrapper {
  overflow-x: auto;
  width: 100%;
}

.table-container {
  min-width: 800px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.data-table th {
  padding: 1rem 1.25rem;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
  background-color: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft);
}

.data-table td {
  padding: 1rem 1.25rem;
  font-size: 0.9rem;
  border-bottom: 1px solid var(--color-border-soft);
  vertical-align: middle;
}

.data-table tbody tr:hover {
  background-color: #f8fafc;
}

.col-no {
  width: 80px;
}

.col-name {
  width: auto;
}

.col-actions {
  width: 120px;
}

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.text-muted {
  color: #64748b;
}

.text-navy {
  color: var(--color-text-navy);
}

.font-medium {
  font-weight: 500;
}

.action-buttons {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action svg {
  width: 16px;
  height: 16px;
}

.btn-action.edit {
  color: #0ea5e9;
  background-color: #e0f2fe;
}

.btn-action.edit:hover {
  background-color: #0284c7;
  color: white;
}

.btn-action.delete {
  color: #ef4444;
  background-color: #fee2e2;
}

.btn-action.delete:hover {
  background-color: #dc2626;
  color: white;
}

.pagination-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem;
  border-top: 1px solid var(--color-border-soft);
}

.pagination-info {
  font-size: 0.875rem;
  color: #64748b;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.btn-pagination {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy);
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-pagination svg {
  width: 16px;
  height: 16px;
}

.btn-pagination:hover:not(:disabled) {
  background-color: #f8fafc;
  border-color: #cbd5e1;
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-pages {
  display: flex;
  gap: 0.25rem;
}

.btn-page {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid transparent;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-page:hover:not(.active) {
  background-color: #f1f5f9;
}

.btn-page.active {
  background-color: var(--color-primary);
  color: white;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.modal-sm {
  max-width: 400px;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.modal-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.text-danger {
  color: #dc2626;
}

.btn-close {
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.btn-close svg {
  width: 20px;
  height: 20px;
}

.btn-close:hover {
  background: #f1f5f9;
  color: var(--color-text-navy);
}

.modal-body {
  padding: 1.5rem;
}

.delete-confirmation {
  text-align: center;
}

.warning-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background-color: #fee2e2;
  color: #dc2626;
  margin: 0 auto 1rem;
}

.warning-icon svg {
  width: 24px;
  height: 24px;
}

.delete-confirmation p {
  margin: 0 0 0.5rem 0;
  color: var(--color-text-navy);
}

.small {
  font-size: 0.875rem;
}

.mt-3 {
  margin-top: 1rem;
}

.alert {
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  text-align: left;
}

.alert-danger {
  background-color: #fee2e2;
  border: 1px solid #f87171;
  color: #b91c1c;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  background-color: #f8fafc;
  border-bottom-left-radius: 8px;
  border-bottom-right-radius: 8px;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  border: 1px solid transparent;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: white;
  border-color: var(--color-border-soft);
  color: var(--color-text-navy);
}

.btn-secondary:hover:not(:disabled) {
  background-color: #f1f5f9;
}

.btn-danger {
  background-color: #dc2626;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background-color: #b91c1c;
}

.spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Skeleton Loading */
.skeleton-row {
  background-color: white;
}

.skeleton-box {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
  border-radius: 4px;
  height: 20px;
}

.skeleton-small { width: 24px; }
.skeleton-medium { width: 120px; }
.skeleton-large { width: 200px; }
.skeleton-circle { 
  width: 32px; 
  height: 32px; 
  border-radius: 6px; 
}
.mx-auto { margin-left: auto; margin-right: auto; }

@keyframes skeleton-loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
  }
  
  .header-actions {
    width: 100%;
  }
  
  .btn-primary {
    width: 100%;
    justify-content: center;
  }
  
  .filters-container {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-box {
    max-width: 100%;
  }
  
  .pagination-container {
    flex-direction: column;
    gap: 1rem;
  }
}
</style>
