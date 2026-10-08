<template>
  <div class="page-container">
    <div class="breadcrumb">
      <span>Home</span>
      <span class="separator">•</span>
      <span>Master Data</span>
      <span class="separator">•</span>
      <span>Data Radiologi</span>
      <span class="separator">•</span>
      <span class="current">Data Item Radiologi</span>
    </div>

    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <svg xmlns="http://www.w3.org/O/svg" class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          Data Item Radiologi
        </h1>
        <p class="page-subtitle">Manajemen Master Data Item Radiologi</p>
      </div>
    </div>

    <!-- Notification Toast -->
    <div v-if="toast.show" class="notification-toast" :class="toast.type === 'success' ? 'toast-success' : 'toast-error'">
      {{ toast.message }}
    </div>

    <div class="card">
      <div class="filters-container">
        <div class="filter-actions">
          <button class="btn-primary" @click="openCreateModal">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah
          </button>
          
          <select v-model="perPage" @change="handlePageChange(1)" class="form-select filter-select">
            <option value="10">10 entries</option>
            <option value="25">25 entries</option>
            <option value="50">50 entries</option>
            <option value="100">100 entries</option>
          </select>
        </div>

        <div class="search-box">
          <input 
            type="text" 
            v-model="searchQuery" 
            @keyup.enter="handleSearch"
            placeholder="Search:" 
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
                <th class="col-no text-center">No.</th>
                <th class="col-name">Item</th>
                <th class="col-name">Kelompok Item</th>
                <th class="col-actions text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <template v-if="loading">
                <tr v-for="i in perPage" :key="`skeleton-${i}`">
                  <td><div class="skeleton-box skeleton-small mx-auto"></div></td>
                  <td><div class="skeleton-box skeleton-large"></div></td>
                  <td><div class="skeleton-box skeleton-large"></div></td>
                  <td>
                    <div class="action-buttons">
                      <div class="skeleton-box skeleton-circle"></div>
                      <div class="skeleton-box skeleton-circle"></div>
                    </div>
                  </td>
                </tr>
              </template>
              
              <template v-else-if="items.length > 0">
                <tr v-for="(item, index) in items" :key="item.id">
                  <td class="text-center">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                  <td class="font-medium text-navy">{{ item.name }}</td>
                  <td>{{ item.group?.name || '-' }}</td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-action edit" title="Edit" @click="openEditModal(item)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                      </button>
                      <button class="btn-action delete" title="Hapus" @click="confirmDelete(item)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <tr v-else>
                <td colspan="4" class="text-center" style="padding: 3rem;">
                  <span class="text-muted">Data item radiologi belum tersedia.</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="pagination-container" v-if="totalItems > 0">
        <div class="pagination-info">
          Showing {{ ((currentPage - 1) * perPage) + 1 }} to {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} entries
        </div>
        
        <div class="pagination-controls">
          <button 
            class="page-btn" 
            :disabled="currentPage === 1"
            @click="handlePageChange(currentPage - 1)"
          >
            &lt;
          </button>
          
          <div class="page-numbers">
            <button 
              v-for="page in totalPages" 
              :key="page"
              class="page-btn page-number"
              :class="{ active: currentPage === page }"
              @click="handlePageChange(page)"
            >
              {{ page }}
            </button>
          </div>
          
          <button 
            class="page-btn" 
            :disabled="currentPage === totalPages"
            @click="handlePageChange(currentPage + 1)"
          >
            &gt;
          </button>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <RadiologyItemModal
      v-if="showModal"
      :show="showModal"
      :item="selectedItem"
      :is-edit="isEdit"
      @close="closeModal"
      @saved="handleSaved"
    />

    <div v-if="showDeleteDialog" class="modal-overlay">
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h3 class="text-danger">Hapus Item Radiologi</h3>
          <button type="button" class="btn-close" @click="closeDeleteDialog">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <p>Apakah Anda yakin ingin menghapus item radiologi <strong>{{ itemToDelete?.name }}</strong>?</p>
          <p class="delete-warning">Data yang dihapus tidak dapat dikembalikan.</p>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="closeDeleteDialog" :disabled="isDeleting">Batal</button>
          <button class="btn-danger" @click="executeDelete" :disabled="isDeleting">
            {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRadiologyItems } from '@/composables/master-data/radiology/useRadiologyItems'
import type { RadiologyItem } from '@/types/radiology'
import RadiologyItemModal from './components/RadiologyItemModal.vue'

const { 
  items, 
  loading, 
  error, 
  currentPage, 
  perPage, 
  totalItems, 
  fetchItems,
  deleteItem
} = useRadiologyItems()

const searchQuery = ref('')
const searchTimeout = ref<number | null>(null)

// Modal state
const showModal = ref(false)
const isEdit = ref(false)
const selectedItem = ref<RadiologyItem | null>(null)

// Delete dialog state
const showDeleteDialog = ref(false)
const itemToDelete = ref<RadiologyItem | null>(null)
const isDeleting = ref(false)

// Toast state
const toast = ref({
  show: false,
  message: '',
  type: 'success'
})

const totalPages = computed(() => {
  return Math.ceil(totalItems.value / perPage.value) || 1
})

const showToast = (message: string, type: 'success' | 'error' = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}

const loadData = () => {
  fetchItems({
    search: searchQuery.value
  })
}

const handlePageChange = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    loadData()
  }
}

const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

watch(searchQuery, (newVal) => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  searchTimeout.value = window.setTimeout(() => {
    handleSearch()
  }, 500)
})

const openCreateModal = () => {
  isEdit.value = false
  selectedItem.value = null
  showModal.value = true
}

const openEditModal = (item: RadiologyItem) => {
  isEdit.value = true
  selectedItem.value = { ...item }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  setTimeout(() => {
    selectedItem.value = null
  }, 200)
}

const handleSaved = (message: string = 'Data berhasil disimpan') => {
  closeModal()
  showToast(message, 'success')
  loadData()
}

const confirmDelete = (item: RadiologyItem) => {
  itemToDelete.value = item
  showDeleteDialog.value = true
}

const closeDeleteDialog = () => {
  showDeleteDialog.value = false
  setTimeout(() => {
    itemToDelete.value = null
  }, 200)
}

const executeDelete = async () => {
  if (!itemToDelete.value) return
  
  isDeleting.value = true
  const success = await deleteItem(itemToDelete.value.id)
  isDeleting.value = false
  
  if (success) {
    showToast('Data berhasil dihapus', 'success')
    closeDeleteDialog()
    if (items.value.length === 1 && currentPage.value > 1) {
      currentPage.value--
    }
    loadData()
  } else {
    showToast(error.value || 'Gagal menghapus data', 'error')
    closeDeleteDialog()
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.page-container { padding: 1.5rem; max-width: 1200px; margin: 0 auto; color: var(--color-text-navy); }
.breadcrumb { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 1.5rem; }
.breadcrumb .separator { color: var(--color-border-soft); }
.breadcrumb .current { color: var(--color-primary); font-weight: 500; }
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; gap: 1rem; }
.header-content { flex: 1; }
.page-title { margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--color-text-navy); display: flex; align-items: center; gap: 0.5rem; }
.title-icon { width: 24px; height: 24px; color: var(--color-primary); }
.page-subtitle { margin: 0.25rem 0 0 0; font-size: 0.9rem; color: var(--color-text-secondary); }
.card { background: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03); border: 1px solid var(--color-border-soft); padding: 1.5rem; }
.btn-primary { background: var(--color-primary); color: #ffffff; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; }
.btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
.btn-primary svg { width: 18px; height: 18px; }
.btn-outline { background: transparent; color: var(--color-primary); border: 1px solid var(--color-primary); padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; font-size: 0.9rem; cursor: pointer; }
.btn-outline:hover { background: var(--color-primary-light); }
.error-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem; background: #fef2f2; border-radius: 8px; color: #991b1b; text-align: center; }
.error-state svg { width: 48px; height: 48px; color: #ef4444; margin-bottom: 1rem; }
.error-state p { margin: 0 0 1rem 0; font-weight: 500; }
.pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.pagination-info { font-size: 0.85rem; color: var(--color-text-secondary); }
.pagination-controls { display: flex; align-items: center; gap: 0.25rem; }
.page-numbers { display: flex; gap: 0.25rem; }
.page-btn { background: #ffffff; border: 1px solid var(--color-border-soft); color: var(--color-text-navy); padding: 0.4rem 0.75rem; border-radius: 4px; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
.page-btn:hover:not(:disabled) { background: var(--color-page-bg); }
.page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.page-number { min-width: 32px; text-align: center; }
.page-number.active { background: #1d4ed8; color: #ffffff; border-color: #1d4ed8; }
.notification-toast { position: fixed; top: 1rem; right: 1rem; padding: 1rem 1.5rem; border-radius: 8px; color: #ffffff; font-weight: 500; font-size: 0.9rem; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); animation: slideIn 0.3s ease-out forwards; }
.toast-success { background-color: #10b981; }
.toast-error { background-color: #ef4444; }
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@media (max-width: 640px) { .page-header { flex-direction: column; } .pagination-container { flex-direction: column; align-items: center; } }
.filters-container { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; justify-content: space-between; align-items: center; }
.search-box { position: relative; flex: 1; min-width: 250px; max-width: 200px; margin-left: auto; }
.search-icon { display: none; }
.search-input { width: 100%; padding: 0.4rem 0.75rem; border: 1px solid var(--color-border-soft); border-radius: 4px; font-size: 0.85rem; transition: all 0.2s; box-sizing: border-box; }
.search-input:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 2px var(--color-primary-light); }
.filter-actions { display: flex; gap: 0.75rem; align-items: center; order: -1; }
.form-select.filter-select { padding: 0.4rem 2rem 0.4rem 0.75rem; border: 1px solid var(--color-border-soft); border-radius: 4px; font-size: 0.85rem; background-color: white; min-width: 70px; }
.form-select.filter-select:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 2px var(--color-primary-light); }
.table-wrapper { width: 100%; }
.table-container { width: 100%; overflow-x: auto; border: 1px solid var(--color-border-soft); background: #ffffff; -webkit-overflow-scrolling: touch; border-radius: 0; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 600px; }
.data-table th { background: #f9fafb; padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #374151; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; white-space: nowrap; }
.data-table th:last-child { border-right: none; }
.data-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; color: #4b5563; vertical-align: middle; }
.data-table td:last-child { border-right: none; }
.data-table tbody tr:nth-child(even) { background-color: #f9fafb; }
.data-table tbody tr:hover { background: #f3f4f6; }
.col-no { width: 48px; }
.col-name { min-width: 180px; }
.col-actions { width: 100px; }
.font-medium { font-weight: 500; }
.text-navy { color: var(--color-text-navy); }
.text-muted { color: #64748b; }
.text-center { text-align: center; }
.mx-auto { margin-left: auto; margin-right: auto; }
.action-buttons { display: flex; gap: 0.25rem; justify-content: center; }
.btn-action { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border: none; background: transparent; border-radius: 4px; color: #ffffff; cursor: pointer; transition: all 0.2s; }
.btn-action svg { width: 14px; height: 14px; }
.btn-action.edit { background: #0ea5e9; }
.btn-action.edit:hover { background: #0284c7; }
.btn-action.delete { background: #ef4444; }
.btn-action.delete:hover { background: #dc2626; }
.skeleton-box { height: 16px; background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: loading 1.5s infinite; border-radius: 4px; }
.skeleton-small { width: 40px; }
.skeleton-medium { width: 80px; }
.skeleton-large { width: 150px; }
.skeleton-circle { width: 24px; height: 24px; border-radius: 50%; }
@keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); display: flex; align-items: center; justify-content: center; z-index: 500; padding: 1rem; backdrop-filter: blur(2px); }
.modal-content { background: #ffffff; border-radius: 8px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); display: flex; flex-direction: column; }
.modal-sm { max-width: 400px; }
.modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border-soft); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: #ffffff; z-index: 10; }
.modal-header h3 { margin: 0; font-size: 1.1rem; font-weight: 600; color: var(--color-text-navy); }
.modal-header .text-danger { color: #ef4444; }
.btn-close { background: transparent; border: none; color: var(--color-text-secondary); cursor: pointer; padding: 0.25rem; border-radius: 6px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
.btn-close:hover { background: var(--color-page-bg); color: var(--color-text-navy); }
.btn-close svg { width: 20px; height: 20px; }
.modal-body { padding: 1.5rem; flex: 1; }
.delete-warning { font-size: 0.85rem; color: #ef4444; margin-top: 0.5rem; font-weight: 500; }
.alert-error { background: #fef2f2; color: #ef4444; padding: 0.75rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500; margin-bottom: 1.5rem; border: 1px solid #f87171; }
.mt-2 { margin-top: 0.5rem; }
.modal-footer { padding: 1.25rem 1.5rem; border-top: 1px solid var(--color-border-soft); display: flex; justify-content: flex-end; gap: 0.75rem; position: sticky; bottom: 0; background: #ffffff; z-index: 10; }
.btn-cancel { padding: 0.5rem 1.25rem; background: #ffffff; border: 1px solid var(--color-border-soft); border-radius: 6px; font-size: 0.9rem; font-weight: 500; color: var(--color-text-secondary); cursor: pointer; transition: all 0.2s; }
.btn-cancel:hover { background: var(--color-page-bg); color: var(--color-text-navy); }
.btn-danger { padding: 0.5rem 1.25rem; background: #ef4444; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; color: #ffffff; cursor: pointer; transition: all 0.2s; }
.btn-danger:hover { background: #dc2626; }
</style>
