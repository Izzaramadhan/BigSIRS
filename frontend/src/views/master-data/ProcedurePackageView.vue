<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">Paket Tindakan</h1>
        <p class="page-subtitle">Daftar Paket Tindakan</p>
      </div>
      <button class="btn btn-primary" @click="openCreateModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Tambah Paket
      </button>
    </div>

    <div class="filter-bar">
      <div class="search-box">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input 
          type="text" 
          v-model="search" 
          class="form-control" 
          placeholder="Cari paket atau tindakan..." 
          @keyup.enter="fetchPackages"
        />
      </div>

      <div class="filter-actions">
        <div class="per-page-select">
          <label>Tampilkan:</label>
          <select v-model="perPage" class="form-control" @change="changePerPage">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
    </div>

    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th width="20%">Nama Paket</th>
            <th width="40%">Item Tindakan</th>
            <th width="15%">Total Harga</th>
            <th width="10%">Status</th>
            <th width="15%">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="text-center py-4">Memuat data...</td>
          </tr>
          <tr v-else-if="packages.length === 0">
            <td colspan="5" class="text-center py-4 text-gray-500">Tidak ada data paket tindakan.</td>
          </tr>
          <tr v-else v-for="pkg in packages" :key="pkg.id">
            <td>
              <div class="fw-medium">{{ pkg.name }}</div>
            </td>
            <td>
              <div class="text-sm text-gray-500">
                <span v-if="pkg.items_summary && pkg.items_summary.length">
                  {{ pkg.items_count }} item(s): 
                  <span v-for="(item, i) in pkg.items_summary.slice(0, 3)" :key="item.id">
                    {{ item.procedure_name }}{{ i < Math.min(pkg.items_summary.length, 3) - 1 ? ', ' : '' }}
                  </span>
                  <span v-if="pkg.items_summary.length > 3">...</span>
                </span>
                <span v-else class="text-gray-400 italic">Tidak ada item</span>
              </div>
            </td>
            <td>
              <div class="text-navy font-medium">{{ formatCurrency(pkg.total_amount) }}</div>
            </td>
            <td>
              <span class="status-badge" :class="pkg.is_active ? 'active' : 'inactive'">
                {{ pkg.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <button class="btn-icon text-blue" @click="openEditModal(pkg)" title="Edit Paket" aria-label="Edit Paket">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>
                <button class="btn-icon text-red" @click="openDeleteModal(pkg)" title="Hapus Paket" aria-label="Hapus Paket">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

    <div class="pagination-bar" v-if="Math.ceil(totalRecords / perPage) > 1">
      <div class="pagination-info">
        Menampilkan {{ (currentPage - 1) * perPage + 1 }} - 
        {{ Math.min(currentPage * perPage, totalRecords) }} 
        dari {{ totalRecords }} data
      </div>
      <div class="pagination-controls">
        <button class="btn-page" :disabled="currentPage === 1" @click="changePage(currentPage - 1)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
        <button class="btn-page" :disabled="currentPage === Math.ceil(totalRecords / perPage)" @click="changePage(currentPage + 1)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </div>
    </div>

    <ProcedurePackageFormModal
      v-model="isModalOpen"
      :mode="modalMode"
      :packageId="selectedPackageId"
      @saved="handleSaved"
    />

    <!-- Delete Confirmation Modal -->
    <div v-if="isDeleteModalOpen" class="modal-backdrop" @keydown.esc="closeDeleteModal" tabindex="0" ref="deleteModalRef">
      <div class="modal-container">
        <div class="modal-header">
          <h3 class="modal-title text-red">Hapus Paket Tindakan?</h3>
          <button class="modal-close" @click="closeDeleteModal" :disabled="deleting">&times;</button>
        </div>
        <div class="modal-body">
          <p>Paket <strong>"{{ selectedPackageToDelete?.name }}"</strong> akan diarsipkan dan tidak lagi tampil pada daftar aktif. Tindakan yang berada di dalam paket tidak akan ikut dihapus.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeDeleteModal" :disabled="deleting" ref="btnCancelDelete">Batal</button>
          <button class="btn btn-danger" @click="confirmDelete" :disabled="deleting">
            {{ deleting ? 'Menghapus...' : 'Hapus Paket' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Local Toast Notification -->
    <div v-if="toastMessage" class="toast-notification" :class="toastType">
      {{ toastMessage }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ProcedurePackageService from '@/services/master-data/procedure-packages.service'
import ProcedurePackageFormModal from '@/components/master-data/procedure-packages/ProcedurePackageFormModal.vue'

const packages = ref([])
const loading = ref(false)
const search = ref('')

const toastMessage = ref('')
const toastType = ref('success')

const showToast = (message, type = 'success') => {
  toastMessage.value = message;
  toastType.value = type;
  setTimeout(() => {
    toastMessage.value = '';
  }, 3000);
}

const currentPage = ref(1)
const lastPage = ref(1)
const totalRecords = ref(0)
const perPage = ref(10)

const isModalOpen = ref(false)
const modalMode = ref('create')
const selectedPackageId = ref(null)

const isDeleteModalOpen = ref(false)
const selectedPackageToDelete = ref(null)
const deleting = ref(false)
const deleteModalRef = ref(null)
const btnCancelDelete = ref(null)

const fetchPackages = async () => {
  loading.value = true
  try {
    const { data } = await ProcedurePackageService.getPackages({
      page: currentPage.value,
      per_page: perPage.value,
      search: search.value
    })
    packages.value = data.data
    currentPage.value = data.meta.current_page
    lastPage.value = data.meta.last_page
    totalRecords.value = data.meta.total
  } catch (error) {
    showToast('Gagal mengambil data paket tindakan', 'error')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    currentPage.value = page
    fetchPackages()
  }
}

const openCreateModal = () => {
  modalMode.value = 'create'
  selectedPackageId.value = null
  isModalOpen.value = true
}

const openEditModal = (pkg) => {
  modalMode.value = 'edit'
  selectedPackageId.value = pkg.id
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
  selectedPackageId.value = null
}

const handleSaved = () => {
  fetchPackages()
  closeModal()
}

const openDeleteModal = (pkg) => {
  selectedPackageToDelete.value = pkg;
  isDeleteModalOpen.value = true;
  document.body.classList.add('overflow-hidden');
  // Focus cancel button on next tick
  setTimeout(() => {
    if (btnCancelDelete.value) btnCancelDelete.value.focus();
    else if (deleteModalRef.value) deleteModalRef.value.focus();
  }, 50);
}

const closeDeleteModal = () => {
  if (deleting.value) return;
  isDeleteModalOpen.value = false;
  selectedPackageToDelete.value = null;
  document.body.classList.remove('overflow-hidden');
}

const confirmDelete = async () => {
  if (!selectedPackageToDelete.value || deleting.value) return;
  
  deleting.value = true;
  try {
    await ProcedurePackageService.deleteProcedurePackage(selectedPackageToDelete.value.id);
    
    closeDeleteModal();
    showToast('Paket Tindakan berhasil dihapus.', 'success');
    
    // Adjust pagination if needed
    if (packages.value.length === 1 && currentPage.value > 1) {
      currentPage.value -= 1;
    }
    await fetchPackages();
  } catch (error) {
    if (error.response?.status === 409) {
      showToast(error.response.data.message || 'Paket Tindakan tidak dapat dihapus karena sudah digunakan pada transaksi.', 'error');
    } else {
      showToast('Gagal menghapus Paket Tindakan. Silakan coba lagi.', 'error');
    }
  } finally {
    deleting.value = false;
  }
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value)
}

onMounted(() => {
  fetchPackages()
})
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-navy);
  margin: 0 0 0.25rem 0;
}

.page-subtitle {
  color: var(--color-text-secondary);
  margin: 0;
  font-size: 0.9rem;
}

.alert {
  padding: 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
}

.alert-danger {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #f87171;
}

.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  background: #fff;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
}

.search-box {
  position: relative;
  width: 300px;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #94a3b8;
}

.search-box .form-control {
  padding-left: 2.25rem;
}

.filter-actions {
  display: flex;
  gap: 1rem;
}

.per-page-select {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--color-text-secondary);
  font-size: 0.9rem;
}

.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.1);
}

.btn {
  padding: 0.6rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border: 1px solid transparent;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-primary {
  background: var(--color-primary);
  color: #ffffff;
}

.btn-primary:hover {
  background: var(--color-primary-dark);
}

.btn-primary svg {
  width: 18px;
  height: 18px;
}

.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
}

.pagination-info {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.pagination-controls {
  display: flex;
  gap: 0.25rem;
}

.btn-page {
  min-width: 32px;
  height: 32px;
  padding: 0 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--color-border-soft);
  background: #fff;
  border-radius: 6px;
  font-size: 0.85rem;
  color: var(--color-text-navy);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-page:hover:not(:disabled):not(.active) {
  background: #f1f5f9;
}

.btn-page.active {
  background: var(--color-primary);
  color: #fff;
  border-color: var(--color-primary);
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f8fafc;
}

.btn-page svg {
  width: 16px;
  height: 16px;
}

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

.text-gray-300 { color: #cbd5e1; }
.text-gray-400 { color: #94a3b8; }
.text-gray-500 { color: #64748b; }
.text-red-500 { color: #ef4444; }

.text-sm { font-size: 0.8rem; }
.text-xs { font-size: 0.75rem; }
.italic { font-style: italic; }

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

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: opacity 0.2s;
  display: inline-block;
}

.status-badge:hover { opacity: 0.8; }
.status-badge.active { background: #dcfce7; color: #166534; }
.status-badge.inactive { background: #fee2e2; color: #991b1b; }

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

.btn-icon:hover { background: #f1f5f9; }
.btn-icon svg { width: 18px; height: 18px; }
.text-blue { color: var(--color-primary); }
.text-red { color: #ef4444; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.text-center { text-align: center; }

/* Modal Styles */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(2px);
}

.modal-container {
  background: #ffffff;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 600;
}

.modal-close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  color: #64748b;
  cursor: pointer;
  padding: 0;
}
.modal-close:hover { color: #0f172a; }
.modal-close:disabled { cursor: not-allowed; opacity: 0.5; }

.modal-body {
  padding: 1.5rem;
  font-size: 0.95rem;
  line-height: 1.5;
  color: #334155;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
}
.btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
}
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-danger {
  background: #ef4444;
  color: white;
}
.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}
.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Toast Notification Styles */
.toast-notification {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  color: white;
  font-weight: 500;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  z-index: 2000;
  animation: slideInUp 0.3s ease-out forwards;
}

.toast-notification.success {
  background: #10b981;
}

.toast-notification.error {
  background: #ef4444;
}

@keyframes slideInUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>
