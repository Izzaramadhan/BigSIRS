<script setup>
import { ref, onMounted, watch } from 'vue'
import { useActivityTypes } from '@/composables/useActivityTypes'
import ActivityTypeFormModal from '@/components/master-data/activity-type/ActivityTypeFormModal.vue'
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
  activityTypes,
  meta,
  loading,
  error,
  fetchActivityTypes,
  createActivityType,
  updateActivityType,
  deleteActivityType
} = useActivityTypes()

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

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)

const isFormModalOpen = ref(false)
const selectedActivityType = ref(null)

const isDeleteModalOpen = ref(false)
const activityTypeToDelete = ref(null)

const isSubmitting = ref(false)
const formErrors = ref({})

const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

const handlePageChange = (page) => {
  if (page < 1 || page > meta.value?.last_page) return
  currentPage.value = page
  loadData()
}

const handlePerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  currentPage.value = 1
  loadData()
}

const loadData = async () => {
  try {
    await fetchActivityTypes({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value
    })
  } catch (err) {
    console.error('Failed to load activity types:', err)
  }
}

const openAddModal = () => {
  selectedActivityType.value = null
  isFormModalOpen.value = true
}

const openEditModal = (activityType) => {
  selectedActivityType.value = { ...activityType }
  isFormModalOpen.value = true
}

const handleFormSubmit = async (payload) => {
  formErrors.value = {}
  isSubmitting.value = true
  
  const isEditing = Boolean(selectedActivityType.value)
  try {
    if (isEditing) {
      await updateActivityType(selectedActivityType.value.id, payload)
    } else {
      await createActivityType(payload)
    }
    
    isFormModalOpen.value = false
    showToast(
      'Berhasil',
      `Data jenis kegiatan berhasil ${isEditing ? 'diperbarui' : 'ditambahkan'}.`,
      'success'
    )
    loadData()
  } catch (err) {
    if (err.response?.status === 422) {
      formErrors.value = err.response.data.errors || {}
      showToast(
        isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        `Data jenis kegiatan gagal ${isEditing ? 'diperbarui' : 'ditambahkan'}.`,
        'error'
      )
    } else {
      const msg = err.response?.data?.message || `Data jenis kegiatan gagal ${isEditing ? 'diperbarui' : 'ditambahkan'}.`
      formErrors.value = { general: msg }
      showToast(
        isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        msg,
        'error'
      )
    }
  } finally {
    isSubmitting.value = false
  }
}

const confirmDelete = (activityType) => {
  activityTypeToDelete.value = activityType
  formErrors.value = {}
  isDeleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!activityTypeToDelete.value) return
  
  isSubmitting.value = true
  
  try {
    await deleteActivityType(activityTypeToDelete.value.id)
    isDeleteModalOpen.value = false
    
    // Adjust pagination if needed
    if (activityTypes.value.length === 1 && currentPage.value > 1) {
      currentPage.value--
    }
    
    loadData()
    showToast('Berhasil', 'Data jenis kegiatan berhasil dihapus.', 'success')
  } catch (err) {
    let errorMessage = 'Data jenis kegiatan gagal dihapus.'
    
    if (err.response?.status === 409) {
      errorMessage = err.response.data.message || 'Data jenis kegiatan tidak dapat dihapus karena masih digunakan.'
    } else if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    }
    
    showToast(
      'Gagal Menghapus',
      errorMessage,
      'error'
    )
  } finally {
    isSubmitting.value = false
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
    <AppToast 
      :show="toast.show" 
      :type="toast.type" 
      :title="toast.title" 
      :message="toast.message" 
      @close="closeToast" 
    />

    <MasterDataPageHeader 
      title="Jenis Kegiatan"
      subtitle="Kelola data dasar Jenis Kegiatan"
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Data Dasar', active: false },
        { label: 'Jenis Kegiatan', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Jenis Kegiatan
        </button>
      </template>
    </MasterDataPageHeader>

    <div class="page-content">
      <div class="filters-container">
        <MasterDataSearchInput 
          v-model="searchQuery" 
          placeholder="Cari Jenis Kegiatan atau Induk..." 
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
        v-if="!loading && activityTypes.length === 0" 
        title="Data tidak ditemukan"
        message="Belum ada data jenis kegiatan yang ditambahkan atau tidak ada yang cocok dengan pencarian Anda."
      />
      
      <template v-if="!error && (loading || activityTypes.length > 0)">
          <div class="table-wrapper">
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no">No</th>
                    <th class="col-name">Nama Jenis Kegiatan</th>
                    <th class="col-desc">Induk</th>
                    <th class="col-actions text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="loading">
                    <MasterDataSkeletonRow 
                      v-for="i in 5" 
                      :key="`skeleton-${i}`"
                      :columns="4" 
                    />
                  </template>

                  <template v-else>
                    <tr v-for="(item, index) in activityTypes" :key="item.id">
                      <td class="col-no text-muted">{{ getRowNumber(index) }}</td>
                      <td class="col-name font-medium text-navy">
                        <span :style="{ marginLeft: item.parent_id ? '20px' : '0px' }">
                          {{ item.name }}
                        </span>
                      </td>
                      <td class="col-desc text-muted">{{ item.parent ? item.parent.name : '-' }}</td>
                      <td class="actions-cell">
                        <MasterDataActionButtons 
                          :show-toggle="false"
                          @edit="openEditModal(item)"
                          @delete="confirmDelete(item)"
                        />
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
            :item-count="activityTypes.length"
            @page-change="handlePageChange"
          />
        </template>
    </div>
    
    <ActivityTypeFormModal 
      :is-open="isFormModalOpen"
      :activityType="selectedActivityType"
      :is-submitting="isSubmitting"
      :errors="formErrors"
      @close="isFormModalOpen = false"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog 
      :is-open="isDeleteModalOpen"
      :item-name="activityTypeToDelete?.name"
      :is-deleting="isSubmitting"
      item-type="Jenis Kegiatan"
      @close="isDeleteModalOpen = false"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
@import '@/assets/master-data.css';

.col-no { width: 60px; text-align: center; }
.col-name { min-width: 250px; }
.col-desc { min-width: 200px; }
.col-actions { width: 120px; text-align: center; }

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
</style>
