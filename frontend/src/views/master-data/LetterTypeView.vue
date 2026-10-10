<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useLetterTypes } from '@/composables/useLetterTypes'
import LetterTypeFormModal from '@/components/master-data/LetterTypeFormModal.vue'

import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue'
import MasterDataSearchInput from '@/components/master-data/shared/MasterDataSearchInput.vue'
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue'
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue'
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue'
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue'
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue'
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue'
import AppToast from '@/components/common/AppToast.vue'

const {
  letterTypes,
  loading,
  error,
  meta,
  fetchLetterTypes,
  createLetterType,
  updateLetterType,
  deleteLetterType
} = useLetterTypes()

const toast = ref({
  show: false,
  type: 'success',
  title: '',
  message: ''
})

const showToast = (title, message, type = 'success') => {
  toast.value = { show: true, title, message, type }
}

const closeToast = () => {
  toast.value.show = false
}

// Filter & Pagination State
const perPage = ref(10)
const currentPage = ref(1)
const searchQuery = ref('')

const isModalOpen = ref(false)
const selectedItem = ref(null)

const isDeleteModalOpen = ref(false)
const itemToDelete = ref(null)

const formLoading = ref(false)
const formErrors = ref({})

const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

const handlePageChange = (page) => {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return
  currentPage.value = page
  loadData()
}

const loadData = async () => {
  try {
    await fetchLetterTypes({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value
    })
  } catch (err) {
    console.error('Failed to load letter types:', err)
  }
}

onMounted(() => {
  loadData()
})

let searchTimeout
watch(searchQuery, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    handleSearch()
  }, 300)
})

const getRowNumber = (index) => {
  return (currentPage.value - 1) * perPage.value + index + 1
}

const openAddModal = () => {
  selectedItem.value = null
  formErrors.value = {}
  isModalOpen.value = true
}

const openEditModal = (item) => {
  selectedItem.value = { ...item }
  formErrors.value = {}
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

const handleFormSubmit = async (formData) => {
  formLoading.value = true
  formErrors.value = {}
  
  const isEditing = Boolean(selectedItem.value)
  try {
    if (isEditing) {
      await updateLetterType(selectedItem.value.id, formData)
    } else {
      await createLetterType(formData)
    }
    
    isModalOpen.value = false
    showToast(
      'Berhasil',
      `Data surat berhasil ${isEditing ? 'diperbarui' : 'ditambahkan'}.`,
      'success'
    )
    loadData()
  } catch (err) {
    if (err.response?.status === 422) {
      formErrors.value = err.response.data.errors || {}
      showToast(
        isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        `Data surat gagal ${isEditing ? 'diperbarui' : 'ditambahkan'}.`,
        'error'
      )
    } else {
      const msg = err.response?.data?.message || `Data surat gagal ${isEditing ? 'diperbarui' : 'ditambahkan'}.`
      formErrors.value = { general: msg }
      showToast(
        isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        msg,
        'error'
      )
    }
  } finally {
    formLoading.value = false
  }
}

const confirmDelete = (item) => {
  itemToDelete.value = item
  formErrors.value = {}
  isDeleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!itemToDelete.value) return
  
  formLoading.value = true
  
  try {
    await deleteLetterType(itemToDelete.value.id)
    isDeleteModalOpen.value = false
    
    if (letterTypes.value.length === 1 && currentPage.value > 1) {
      currentPage.value--
    }
    
    loadData()
    showToast('Berhasil', 'Data surat berhasil dihapus.', 'success')
  } catch (err) {
    let errorMessage = 'Data surat gagal dihapus.'
    
    if (err.response?.status === 409) {
      errorMessage = err.response.data.message || 'Data surat tidak dapat dihapus karena masih digunakan.'
    } else if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    }
    
    showToast(
      'Gagal Menghapus',
      errorMessage,
      'error'
    )
  } finally {
    formLoading.value = false
  }
}

const viewLegacy = (item) => {
  if (item.legacy_resource) {
    const baseUrl = import.meta.env.VITE_LEGACY_BASE_URL || '/simrs_lama/'
    window.open(`${baseUrl}${item.legacy_resource}`, '_blank')
  }
}
</script>

<template>
  <div class="page-container">
    <AppToast 
      :show="toast.show" 
      :type="toast.type" 
      :title="toast.title" 
      :message="toast.message" 
      @close="closeToast" 
    />

    <MasterDataPageHeader 
      title="Surat-Surat"
      subtitle="Kelola data dasar Surat-Surat"
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Data Dasar', active: false },
        { label: 'Surat-Surat', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Surat
        </button>
      </template>
    </MasterDataPageHeader>

    <div class="page-content">
      <div class="filters-container">
        <MasterDataSearchInput 
          v-model="searchQuery" 
          placeholder="Cari nama, deskripsi, atau resource..." 
        />
        <div class="filter-controls">
          <button 
            type="button" 
            class="btn-icon" 
            @click="loadData" 
            title="Refresh Data"
            :disabled="loading"
            aria-label="Refresh data"
          >
            <svg :class="{ 'spin': loading }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="23 4 23 10 17 10"></polyline>
              <polyline points="1 20 1 14 7 14"></polyline>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
          </button>
        </div>
      </div>
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error" 
        @retry="loadData" 
      />
      
      <MasterDataEmptyState 
        v-if="!loading && letterTypes.length === 0" 
        title="Data tidak ditemukan"
        message="Belum ada data surat atau tidak ada yang cocok dengan pencarian Anda."
      />
      
      <template v-if="!error && (loading || letterTypes.length > 0)">
          <div class="table-wrapper">
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no">No</th>
                    <th class="col-name">Nama</th>
                    <th class="col-desc">Deskripsi</th>
                    <th class="col-resource">Resource</th>
                    <th class="col-actions text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="loading">
                    <MasterDataSkeletonRow 
                      v-for="i in 5" 
                      :key="`skeleton-${i}`"
                      :columns="5" 
                    />
                  </template>

                  <template v-else>
                    <tr v-for="(item, index) in letterTypes" :key="item.id">
                      <td class="col-no text-muted">{{ getRowNumber(index) }}</td>
                      <td class="col-name font-medium text-navy">{{ item.name }}</td>
                      <td class="col-desc text-muted">{{ item.description || '-' }}</td>
                      <td class="col-resource text-muted">
                        <span v-if="item.legacy_resource" class="code-text">{{ item.legacy_resource }}</span>
                        <span v-else>-</span>
                      </td>
                      <td class="actions-cell">
                        <div class="action-buttons">
                          <button v-if="item.legacy_resource" type="button" class="btn-action view-legacy" @click="viewLegacy(item)" aria-label="Lihat Format Lama" title="Lihat Format Lama">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <line x1="8" y1="6" x2="21" y2="6"></line>
                              <line x1="8" y1="12" x2="21" y2="12"></line>
                              <line x1="8" y1="18" x2="21" y2="18"></line>
                              <line x1="3" y1="6" x2="3.01" y2="6"></line>
                              <line x1="3" y1="12" x2="3.01" y2="12"></line>
                              <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                          </button>

                          <MasterDataActionButtons 
                            :show-toggle="false"
                            @edit="openEditModal(item)"
                            @delete="confirmDelete(item)"
                          />
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
          
          <MasterDataPagination 
            v-if="meta && meta.last_page > 1"
            :pagination="{
              current_page: currentPage,
              last_page: meta.last_page,
              total: meta.total,
              per_page: perPage,
              from: meta.from,
              to: meta.to
            }"
            :loading="loading"
            :item-count="letterTypes.length"
            @page-change="handlePageChange"
          />
        </template>
    </div>
    
    <LetterTypeFormModal 
      :is-open="isModalOpen"
      :letter-type-data="selectedItem"
      :loading="formLoading"
      :errors="formErrors"
      @close="closeModal"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog 
      :is-open="isDeleteModalOpen"
      :item-name="itemToDelete?.name"
      :is-deleting="formLoading"
      item-type="Surat"
      @close="isDeleteModalOpen = false"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
@import '@/assets/master-data.css';

.col-no { width: 60px; text-align: center; }
.col-name { min-width: 200px; }
.col-desc { min-width: 250px; }
.col-resource { min-width: 150px; }
.col-actions { width: 140px; text-align: center; }

.filters-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

.filter-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover:not(:disabled) {
  background: var(--color-page-bg);
  color: var(--color-text-navy);
}

.btn-icon svg {
  width: 18px;
  height: 18px;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.code-text {
  font-family: monospace;
  background: #f1f5f9;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
  font-size: 0.8rem;
  color: #334155;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
  justify-content: center;
  align-items: center;
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

.btn-action.view-legacy:hover { background: #dbeafe; color: #2563eb; }
</style>
