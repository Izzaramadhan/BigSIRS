<script setup>
import { ref, onMounted } from 'vue'
import { useBuildings } from '@/composables/useBuildings'
import BuildingTable from '@/components/master-data/buildings/BuildingTable.vue'
import BuildingFilters from '@/components/master-data/buildings/BuildingFilters.vue'
import BuildingFormModal from '@/components/master-data/buildings/BuildingFormModal.vue'
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue'
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue'
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue'
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue'
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue'
import AppToast from '@/components/common/AppToast.vue'

const {
  items,
  pagination,
  filters,
  loading,
  submitting,
  error,
  fetchBuildings,
  createBuilding,
  updateBuilding,
  deleteBuilding,
  setPage,
} = useBuildings()

const formModalOpen = ref(false)
const deleteDialogOpen = ref(false)
const selectedBuilding = ref(null)
const buildingToDelete = ref(null)
const formErrors = ref({})

const toast = ref({
  show: false,
  type: 'success',
  title: '',
  message: '',
})

const showToast = ({ type = 'success', title, message }) => {
  toast.value = { show: true, type, title, message }
}

const closeToast = () => {
  toast.value.show = false
}

const openAddModal = () => {
  selectedBuilding.value = null
  formErrors.value = {}
  formModalOpen.value = true
}

const openEditModal = (item) => {
  selectedBuilding.value = item
  formErrors.value = {}
  formModalOpen.value = true
}

const closeFormModal = () => {
  formModalOpen.value = false
  selectedBuilding.value = null
  formErrors.value = {}
}

const openDeleteDialog = (item) => {
  buildingToDelete.value = item
  deleteDialogOpen.value = true
}

const closeDeleteDialog = () => {
  deleteDialogOpen.value = false
  buildingToDelete.value = null
}

const handleFilter = ({ key, value }) => {
  filters[key] = value
  pagination.current_page = 1
  fetchBuildings()
}

const handleFormSubmit = async (payload) => {
  formErrors.value = {}

  const isEditing = Boolean(selectedBuilding.value)
  let result

  if (isEditing) {
    result = await updateBuilding(selectedBuilding.value.id, payload)
  } else {
    result = await createBuilding(payload)
  }

  if (result.success) {
    closeFormModal()
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing ? 'Data gedung berhasil diperbarui.' : 'Data gedung berhasil ditambahkan.',
    })
    fetchBuildings()
  } else {
    if (result.error?.response?.status === 422) {
      formErrors.value = result.error.response.data.errors || {}
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message:
          result.error.response.data.message ||
          'Data gedung tidak valid. Silakan periksa kembali form pengisian.',
      })
    } else {
      const errorMsg =
        result.error?.response?.data?.message ||
        `Terjadi kesalahan saat ${isEditing ? 'memperbarui' : 'menambahkan'} data gedung.`
      formErrors.value = { general: errorMsg }
      showToast({
        type: 'error',
        title: 'Gagal',
        message: errorMsg,
      })
    }
  }
}

const handleDeleteConfirm = async () => {
  if (!buildingToDelete.value) return

  const idToDelete = buildingToDelete.value.id
  const result = await deleteBuilding(idToDelete)

  if (result.success) {
    closeDeleteDialog()
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Data gedung berhasil dihapus.',
    })

    // Adjust pagination if deleting the last item on current page
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--
    }

    fetchBuildings()
  } else {
    const errorMsg = result.error?.response?.data?.message || 'Data gedung gagal dihapus.'
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: errorMsg,
    })
    closeDeleteDialog()
  }
}

onMounted(() => {
  fetchBuildings()
})
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

    <!-- Header -->
    <MasterDataPageHeader
      title="Data Gedung"
      subtitle="Kelola data gedung yang digunakan dalam pengaturan ruangan dan tempat tidur rumah sakit."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Data Bed', active: false },
        { label: 'Data Gedung', active: true },
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Gedung
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <BuildingFilters
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchBuildings"
      />

      <MasterDataErrorState v-if="error" :error="error" @retry="fetchBuildings" />

      <template v-else>
        <MasterDataEmptyState v-if="!loading && items.length === 0" :is-search="!!filters.search">
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Gedung</button>
          </template>
        </MasterDataEmptyState>

        <template v-else>
          <BuildingTable
            :items="items"
            :pagination="pagination"
            :loading="loading"
            @edit="openEditModal"
            @delete="openDeleteDialog"
          />

          <MasterDataPagination
            :pagination="pagination"
            :loading="loading"
            :item-count="items.length"
            @page-change="setPage"
          />
        </template>
      </template>
    </div>

    <!-- Modals -->
    <BuildingFormModal
      :is-open="formModalOpen"
      :building="selectedBuilding"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog
      :is-open="deleteDialogOpen"
      title="Hapus Gedung"
      :confirm-message="
        buildingToDelete
          ? `Apakah Anda yakin ingin menghapus gedung “${buildingToDelete.name}”?`
          : ''
      "
      confirm-text="Hapus"
      warning-message="Tindakan ini tidak dapat dibatalkan."
      :is-submitting="submitting"
      @close="closeDeleteDialog"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background-color: var(--color-primary);
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  background-color: var(--color-primary-dark, #0284c7);
}

.btn-primary svg {
  width: 18px;
  height: 18px;
}
</style>
