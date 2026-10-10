<script setup>
import { ref, onMounted, watch } from 'vue'
import { useDietTypes } from '@/composables/useDietTypes'
import DietTypeFormModal from '@/components/master-data/nutrition-care/DietTypeFormModal.vue'
import AppToast from '@/components/common/AppToast.vue'
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue'
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue'
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue'
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue'
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue'
import MasterDataSearchInput from '@/components/master-data/shared/MasterDataSearchInput.vue'
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue'

const {
  dietTypes,
  meta,
  loading,
  error,
  fetchDietTypes,
  deleteDietType
} = useDietTypes()

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
const perPage = ref(10)

const isFormModalOpen = ref(false)
const selectedDietType = ref(null)

const isDeleteModalOpen = ref(false)
const DietTypeToDelete = ref(null)
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
    await fetchDietTypes({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value
    })
  } catch (err) {
    console.error('Failed to load diet types:', err)
  }
}

const openAddModal = () => {
  selectedDietType.value = null
  isFormModalOpen.value = true
}

const openEditModal = (DietType) => {
  selectedDietType.value = { ...DietType }
  isFormModalOpen.value = true
}

const onFormSuccess = () => {
  const isEditing = Boolean(selectedDietType.value)
  isFormModalOpen.value = false
  loadData()
  showToast({
    type: 'success',
    title: 'Berhasil',
    message: isEditing ? 'Data asuhan gizi berhasil diperbarui.' : 'Data asuhan gizi berhasil ditambahkan.'
  })
}

const confirmDelete = (DietType) => {
  DietTypeToDelete.value = DietType
  deleteError.value = null
  isDeleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!DietTypeToDelete.value) return
  
  try {
    await deleteDietType(DietTypeToDelete.value.id)
    isDeleteModalOpen.value = false
    loadData()
    showToast({ type: 'success', title: 'Berhasil', message: 'Data asuhan gizi berhasil dihapus.' })
  } catch (err) {
    showToast({ 
      type: 'error', 
      title: 'Gagal Menghapus', 
      message: err.response?.data?.message || 'Data asuhan gizi gagal dihapus.' 
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
      title="Asuhan Gizi"
      subtitle="Kelola data dasar Asuhan Gizi."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Data Dasar', active: false },
        { label: 'Asuhan Gizi', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Asuhan Gizi
        </button>
      </template>
    </MasterDataPageHeader>

    <div class="page-content">
      <div class="filters-container">
        <MasterDataSearchInput 
          v-model="searchQuery" 
          placeholder="Cari Asuhan Gizi..." 
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
          v-if="!loading && dietTypes.length === 0" 
          :is-search="!!searchQuery"
        >
          <template #action v-if="!searchQuery">
            <button class="btn-primary" @click="openAddModal">Tambah Asuhan Gizi</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <div class="table-wrapper">
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no">No</th>
                    <th class="col-name">Asuhan Gizi</th>
                    <th class="col-desc">Deskripsi</th>
                    <th class="col-actions text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="loading">
                    <tr v-for="i in 5" :key="`skeleton-${i}`" class="skeleton-row">
                      <td><div class="skeleton-box skeleton-small"></div></td>
                      <td><div class="skeleton-box skeleton-medium"></div></td>
                      <td><div class="skeleton-box skeleton-medium"></div></td>
                      <td><div class="skeleton-box skeleton-circle"></div></td>
                    </tr>
                  </template>

                  <template v-else>
                    <tr v-for="(item, index) in dietTypes" :key="item.id">
                      <td class="col-no text-muted">{{ getRowNumber(index) }}</td>
                      <td class="col-name font-medium text-navy">{{ item.name }}</td>
                      <td class="col-desc text-muted">{{ item.description || '-' }}</td>
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
            :item-count="dietTypes.length"
            @page-change="handlePageChange"
          />
        </template>
      </template>
    </div>
    
    <DietTypeFormModal 
      :is-open="isFormModalOpen"
      :diet-type="selectedDietType"
      @close="isFormModalOpen = false"
      @success="onFormSuccess"
    />

    <MasterDataDeleteDialog 
      :is-open="isDeleteModalOpen"
      title="Hapus Asuhan Gizi?"
      :item-name="DietTypeToDelete?.name || ''"
      warning-message="Tindakan ini tidak dapat dibatalkan."
      :is-submitting="loading"
      @close="isDeleteModalOpen = false"
      @confirm="handleDelete"
    />
  </div>
</template>
