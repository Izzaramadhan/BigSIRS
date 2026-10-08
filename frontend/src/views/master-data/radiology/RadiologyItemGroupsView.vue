<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRadiologyItemGroups } from '@/composables/useRadiologyItemGroups'
import RadiologyItemGroupModal from './components/RadiologyItemGroupModal.vue'

const {
  groups: radiologyItemGroups,
  pagination: meta,
  loading,
  error,
  fetchGroups,
  deleteGroup
} = useRadiologyItemGroups()

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
const selectedGroup = ref(null)

const isDeleteModalOpen = ref(false)
const groupToDelete = ref(null)
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
    await fetchGroups(params)
  } catch (err) {
    console.error('Failed to load radiology item groups:', err)
  }
}

const openAddModal = () => {
  selectedGroup.value = null
  isFormModalOpen.value = true
}

const openEditModal = (group) => {
  selectedGroup.value = { ...group }
  isFormModalOpen.value = true
}

const onFormSuccess = () => {
  isFormModalOpen.value = false
  loadData()
  showToast(
    'Berhasil',
    `Kelompok Item Rad berhasil ${selectedGroup.value ? 'diperbarui' : 'ditambahkan'}`,
    'success'
  )
}

const confirmDelete = (group) => {
  groupToDelete.value = group
  deleteError.value = null
  isDeleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!groupToDelete.value) return
  
  try {
    await deleteGroup(groupToDelete.value.id)
    isDeleteModalOpen.value = false
    if (radiologyItemGroups.value.length === 1 && currentPage.value > 1) {
      currentPage.value--
    }
    loadData()
    showToast('Berhasil', 'Kelompok Item Rad berhasil dihapus', 'success')
  } catch (err) {
    console.error('Failed to delete radiology item group:', err)
    deleteError.value = err.response?.data?.message || 'Gagal menghapus kelompok item rad.'
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
          <span>Data Radiologi</span>
          <span class="separator">/</span>
          <span class="current">Kelompok Item Rad</span>
        </div>
        <h1 class="page-title">Data Kelompok Item Radiologi</h1>
        <p class="page-subtitle">Kelola master data kelompok item radiologi.</p>
      </div>
      
      <div class="header-actions">
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Kelompok Item Rad
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
            placeholder="Cari kelompok item radiologi..." 
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
        <p>Gagal memuat data kelompok item radiologi.</p>
        <button class="btn-outline" @click="loadData">Coba Lagi</button>
      </div>
      
      <template v-else>
        <div v-if="!loading && radiologyItemGroups.length === 0" class="empty-state" style="text-align: center; padding: 4rem 2rem;">
          <p>{{ searchQuery ? 'Kelompok item radiologi tidak ditemukan.' : 'Data kelompok item radiologi belum tersedia.' }}</p>
        </div>
        
        <template v-else>
          <div class="table-wrapper">
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no text-center">No</th>
                    <th class="col-name">Kelompok Item</th>
                    <th>Deskripsi</th>
                    <th class="text-center">Status</th>
                    <th class="col-actions text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="loading">
                    <tr v-for="i in 5" :key="`skeleton-${i}`" class="skeleton-row">
                      <td class="text-center"><div class="skeleton-box skeleton-small mx-auto"></div></td>
                      <td><div class="skeleton-box skeleton-medium"></div></td>
                      <td><div class="skeleton-box skeleton-large"></div></td>
                      <td class="text-center"><div class="skeleton-box skeleton-small mx-auto"></div></td>
                      <td>
                        <div class="action-buttons">
                          <div class="skeleton-box skeleton-circle"></div>
                          <div class="skeleton-box skeleton-circle"></div>
                        </div>
                      </td>
                    </tr>
                  </template>

                  <template v-else>
                    <tr v-for="(item, index) in radiologyItemGroups" :key="item.id">
                      <td class="col-no text-center text-muted">{{ getRowNumber(index) }}</td>
                      <td class="col-name font-medium text-navy">{{ item.name }}</td>
                      <td class="text-muted">{{ item.description || '-' }}</td>
                      <td class="text-center">
                        <span 
                          class="status-badge" 
                          :class="item.is_active ? 'badge-active' : 'badge-inactive'"
                        >
                          {{ item.is_active ? 'AKTIF' : 'NONAKTIF' }}
                        </span>
                      </td>
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
              Showing {{ meta.from || 0 }} to {{ meta.to || 0 }} of {{ meta.total || 0 }} entries
            </div>
            
            <div class="pagination-controls">
              <button 
                class="page-btn" 
                :disabled="currentPage === 1 || loading"
                @click="handlePageChange(currentPage - 1)"
              >
                &lt;
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
                &gt;
              </button>
            </div>
          </div>
        </template>
      </template>
    </div>
    
    <RadiologyItemGroupModal
      :is-open="isFormModalOpen"
      :group-data="selectedGroup"
      @close="isFormModalOpen = false"
      @saved="onFormSuccess"
    />

    <!-- Delete Dialog -->
    <Teleport to="body">
      <div v-if="isDeleteModalOpen" class="modal-overlay" @click.self="isDeleteModalOpen = false">
        <div class="modal-content modal-sm">
          <div class="modal-header">
            <h3 class="text-danger">Hapus Kelompok Item Rad</h3>
            <button class="btn-close" @click="isDeleteModalOpen = false">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
          <div class="modal-body">
            <p>Apakah Anda yakin ingin menghapus kelompok item radiologi <strong>{{ groupToDelete?.name }}</strong>?</p>
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
.page-container { max-width: 100%; position: relative; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.breadcrumbs { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--color-text-secondary); margin-bottom: 0.75rem; }
.separator { color: var(--color-border-soft); }
.current { color: var(--color-primary); font-weight: 500; }
.page-title { margin: 0 0 0.5rem 0; font-size: 1.5rem; font-weight: 700; color: var(--color-text-navy); text-transform: uppercase; }
.page-subtitle { margin: 0; font-size: 0.9rem; color: var(--color-text-secondary); }
.btn-primary { display: flex; align-items: center; gap: 0.5rem; background: #3b82f6; color: #ffffff; border: none; padding: 0.6rem 1.25rem; border-radius: 4px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; }
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
.status-badge { padding: 0.15rem 0.4rem; border-radius: 2px; font-size: 0.7rem; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; text-transform: uppercase; }
.badge-active { color: #166534; }
.badge-inactive { color: #991b1b; }
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
