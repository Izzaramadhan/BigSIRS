<script setup>
import { ref, onMounted, watch } from 'vue'
import { useEducations } from '@/composables/useEducations'
import EducationFormModal from '@/components/master-data/educations/EducationFormModal.vue'
import AppToast from '@/components/common/AppToast.vue'
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue'
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue'
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue'
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue'
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue'
import MasterDataSearchInput from '@/components/master-data/shared/MasterDataSearchInput.vue'
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue'

const {
  educations,
  meta,
  loading,
  error,
  fetchEducations,
  deleteEducation
} = useEducations()

const toast = ref({
  show: false,
  type: 'success',
  title: '',
  message: ''
})

const showToast = ({ type = 'success', title, message }) => {
  toast.value = { show: true, type, title, message }
}

const closeToast = () => {
  toast.value.show = false
}

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10) // Internal state, we can keep it without UI or set it as needed

const isFormModalOpen = ref(false)
const selectedEducation = ref(null)

const isDeleteModalOpen = ref(false)
const educationToDelete = ref(null)
const deleteError = ref(null)

const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

const handleRefresh = () => {
  loadData()
}

const handlePageChange = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  currentPage.value = page
  loadData()
}

const loadData = async () => {
  try {
    await fetchEducations({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value
    })
  } catch (err) {
    console.error('Failed to load educations:', err)
  }
}

const openAddModal = () => {
  selectedEducation.value = null
  isFormModalOpen.value = true
}

const openEditModal = (education) => {
  selectedEducation.value = { ...education }
  isFormModalOpen.value = true
}

const onFormSuccess = () => {
  const isEditing = Boolean(selectedEducation.value)
  isFormModalOpen.value = false
  loadData()
  showToast({
    type: 'success',
    title: 'Berhasil',
    message: isEditing ? 'Data pendidikan berhasil diperbarui.' : 'Data pendidikan berhasil ditambahkan.'
  })
}

const confirmDelete = (education) => {
  educationToDelete.value = education
  deleteError.value = null
  isDeleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!educationToDelete.value) return
  
  try {
    await deleteEducation(educationToDelete.value.id)
    isDeleteModalOpen.value = false
    loadData()
    showToast({ type: 'success', title: 'Berhasil', message: 'Data pendidikan berhasil dihapus.' })
  } catch (err) {
    showToast({ 
      type: 'error', 
      title: 'Gagal Menghapus', 
      message: err.response?.data?.message || 'Data pendidikan gagal dihapus.' 
    })
    isDeleteModalOpen.value = false
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
      title="Pendidikan"
      subtitle="Kelola data dasar tingkat pendidikan."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Data Dasar', active: false },
        { label: 'Pendidikan', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Pendidikan
        </button>
      </template>
    </MasterDataPageHeader>

    <div class="page-content">
      <div class="filters-container">
        <MasterDataSearchInput 
          v-model="searchQuery" 
          placeholder="Cari tingkat pendidikan..." 
        />

        <div class="filter-selects">
          <div class="filters-actions">
            <button type="button" class="btn-icon" @click="handleRefresh" title="Refresh Data" :disabled="loading">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="{ 'spin': loading }">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error" 
        @retry="loadData" 
      />
      
      <template v-else>
        <MasterDataEmptyState 
          v-if="!loading && educations.length === 0" 
          :is-search="!!searchQuery"
        >
          <template #action v-if="!searchQuery">
            <button class="btn-primary" @click="openAddModal">Tambah Pendidikan</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <div class="table-wrapper">
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no">No</th>
                    <th class="col-name">Pendidikan</th>
                    <th class="col-actions text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="loading">
                    <tr v-for="i in 5" :key="`skeleton-${i}`" class="skeleton-row">
                      <td><div class="skeleton-box skeleton-small"></div></td>
                      <td><div class="skeleton-box skeleton-medium"></div></td>
                      <td><div class="skeleton-box skeleton-circle"></div></td>
                    </tr>
                  </template>

                  <template v-else>
                    <tr v-for="(item, index) in educations" :key="item.id">
                      <td class="col-no text-muted">{{ getRowNumber(index) }}</td>
                      <td class="col-name font-medium text-navy">{{ item.name }}</td>
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
            v-if="meta"
            :pagination="{ current_page: currentPage, last_page: meta.last_page || 1, per_page: perPage, total: meta.total || 0, from: meta.from, to: meta.to }"
            :loading="loading"
            :item-count="educations.length"
            @page-change="handlePageChange"
          />
        </template>
      </template>
    </div>
    
    <EducationFormModal 
      :is-open="isFormModalOpen"
      :education="selectedEducation"
      @close="isFormModalOpen = false"
      @success="onFormSuccess"
    />

    <MasterDataDeleteDialog 
      :is-open="isDeleteModalOpen"
      title="Hapus Pendidikan?"
      :item-name="educationToDelete?.name || ''"
      warning-message="Tindakan ini tidak dapat dibatalkan."
      :is-submitting="loading"
      @close="isDeleteModalOpen = false"
      @confirm="handleDelete"
    />
  </div>
</template>
