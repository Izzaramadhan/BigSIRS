<script setup>
import { ref, onMounted, watch } from 'vue'
import { useOccupations } from '@/composables/useOccupations'
import OccupationFormModal from '@/components/master-data/occupations/OccupationFormModal.vue'
import AppToast from '@/components/common/AppToast.vue'
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue'
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue'
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue'
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue'
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue'
import MasterDataSearchInput from '@/components/master-data/shared/MasterDataSearchInput.vue'
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue'

const {
  occupations,
  meta,
  loading,
  error,
  fetchOccupations,
  deleteOccupation
} = useOccupations()

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
const selectedOccupation = ref(null)

const isDeleteModalOpen = ref(false)
const occupationToDelete = ref(null)
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
    await fetchOccupations({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value
    })
  } catch (err) {
    console.error('Failed to load Occupations:', err)
  }
}

const openAddModal = () => {
  selectedOccupation.value = null
  isFormModalOpen.value = true
}

const openEditModal = (occupation) => {
  selectedOccupation.value = { ...occupation }
  isFormModalOpen.value = true
}

const onFormSuccess = () => {
  const isEditing = Boolean(selectedOccupation.value)
  isFormModalOpen.value = false
  loadData()
  showToast({
    type: 'success',
    title: 'Berhasil',
    message: isEditing ? 'Data Pekerjaan berhasil diperbarui.' : 'Data Pekerjaan berhasil ditambahkan.'
  })
}

const confirmDelete = (occupation) => {
  occupationToDelete.value = occupation
  deleteError.value = null
  isDeleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!occupationToDelete.value) return
  
  try {
    await deleteOccupation(occupationToDelete.value.id)
    isDeleteModalOpen.value = false
    loadData()
    showToast({ type: 'success', title: 'Berhasil', message: 'Data Pekerjaan berhasil dihapus.' })
  } catch (err) {
    showToast({ 
      type: 'error', 
      title: 'Gagal Menghapus', 
      message: err.response?.data?.message || 'Data Pekerjaan gagal dihapus.' 
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
      title="Pekerjaan"
      subtitle="Kelola data dasar tingkat Pekerjaan."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Data Dasar', active: false },
        { label: 'Pekerjaan', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Pekerjaan
        </button>
      </template>
    </MasterDataPageHeader>

    <div class="page-content">
      <div class="filters-container">
        <MasterDataSearchInput 
          v-model="searchQuery" 
          placeholder="Cari tingkat Pekerjaan..." 
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
          v-if="!loading && occupations.length === 0" 
          :is-search="!!searchQuery"
        >
          <template #action v-if="!searchQuery">
            <button class="btn-primary" @click="openAddModal">Tambah Pekerjaan</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <div class="table-wrapper">
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no">No</th>
                    <th class="col-name">Pekerjaan</th>
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
                    <tr v-for="(item, index) in occupations" :key="item.id">
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
            :item-count="occupations.length"
            @page-change="handlePageChange"
          />
        </template>
      </template>
    </div>
    
    <OccupationFormModal 
      :is-open="isFormModalOpen"
      :occupation="selectedOccupation"
      @close="isFormModalOpen = false"
      @success="onFormSuccess"
    />

    <MasterDataDeleteDialog 
      :is-open="isDeleteModalOpen"
      title="Hapus Pekerjaan?"
      :item-name="occupationToDelete?.name || ''"
      warning-message="Tindakan ini tidak dapat dibatalkan."
      :is-submitting="loading"
      @close="isDeleteModalOpen = false"
      @confirm="handleDelete"
    />
  </div>
</template>
