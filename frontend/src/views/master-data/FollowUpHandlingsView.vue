<script setup>
import { ref, onMounted, watch } from 'vue'
import { useFollowUpHandlings } from '@/composables/useFollowUpHandlings'
import FollowUpHandlingFormModal from '@/views/master-data/components/FollowUpHandlingFormModal.vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataSearchInput from '@/components/master-data/shared/MasterDataSearchInput.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue';
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue';
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
    <MasterDataPageHeader 
      title="Master Data Penanganan Lanjutan"
      subtitle="Kelola data referensi penanganan lanjutan pasien."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Penanganan Lanjutan', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Data
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <!-- Filters -->
      <div class="filters-container">
        <MasterDataSearchInput 
          v-model="searchQuery" 
          placeholder="Cari penanganan..." 
        />
        
        <div class="filter-actions">
          <select v-model="perPage" class="form-select filter-select" @change="handleSearch">
            <option :value="10">10 / halaman</option>
            <option :value="25">25 / halaman</option>
            <option :value="50">50 / halaman</option>
          </select>
        </div>
      </div>
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error.includes('403') || error.includes('unauthorized') || error.includes('Unauthorized') ? 'Anda tidak memiliki akses untuk melihat data ini.' : 'Gagal memuat data.'" 
        @retry="fetchHandlings({ page: pagination.currentPage, per_page: perPage, search: searchQuery })" 
      />
      
      <template v-else>
        <MasterDataEmptyState 
          v-if="!loading && handlings.length === 0" 
          :is-search="!!searchQuery"
        />
        
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
                    <MasterDataSkeletonRow :columns="3" :rows="5" />
                  </template>

                  <template v-else>
                    <tr v-for="(handling, index) in handlings" :key="handling.id">
                      <td class="col-no text-center text-muted">{{ getRowNumber(index) }}</td>
                      <td class="col-name font-medium text-navy">{{ handling.name }}</td>
                      <td class="actions-cell">
                        <MasterDataActionButtons 
                          @edit="openEditModal(handling)"
                          @delete="confirmDelete(handling)"
                        />
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
          
          <MasterDataPagination 
            :pagination="{
              current_page: pagination.currentPage,
              last_page: pagination.lastPage,
              per_page: perPage,
              total: pagination.total
            }"
            :loading="loading"
            :item-count="handlings.length"
            @page-change="handlePageChange"
          />
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
    <MasterDataDeleteDialog 
      :is-open="isDeleteModalOpen"
      title="Hapus Data"
      :item-name="handlingToDelete?.name"
      warning-message="Tindakan ini tidak dapat dibatalkan."
      :is-submitting="loading"
      @close="isDeleteModalOpen = false"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
.page-container {
  max-width: 100%;
  position: relative;
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

.filter-actions {
  display: flex;
  align-items: center;
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
</style>
