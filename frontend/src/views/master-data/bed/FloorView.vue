<script setup>
import { ref, onMounted } from 'vue'
import { useFloors } from '@/composables/useFloors'
import FloorTable from '@/components/master-data/floors/FloorTable.vue'
import FloorFilters from '@/components/master-data/floors/FloorFilters.vue'
import FloorFormModal from '@/components/master-data/floors/FloorFormModal.vue'
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
  fetchFloors,
  createFloor,
  updateFloor,
  deleteFloor,
  setPage,
} = useFloors()

const formModalOpen = ref(false)
const deleteDialogOpen = ref(false)
const selectedFloor = ref(null)
const floorToDelete = ref(null)
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
  selectedFloor.value = null
  formErrors.value = {}
  formModalOpen.value = true
}

const openEditModal = (item) => {
  selectedFloor.value = item
  formErrors.value = {}
  formModalOpen.value = true
}

const closeFormModal = () => {
  formModalOpen.value = false
  selectedFloor.value = null
  formErrors.value = {}
}

const openDeleteDialog = (item) => {
  floorToDelete.value = item
  deleteDialogOpen.value = true
}

const closeDeleteDialog = () => {
  deleteDialogOpen.value = false
  floorToDelete.value = null
}

const handleFilter = ({ key, value }) => {
  filters[key] = value
  pagination.current_page = 1
  fetchFloors()
}

const handleFormSubmit = async (payload) => {
  formErrors.value = {}

  const isEditing = Boolean(selectedFloor.value)
  let result

  if (isEditing) {
    result = await updateFloor(selectedFloor.value.id, payload)
  } else {
    result = await createFloor(payload)
  }

  if (result.success) {
    closeFormModal()
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing ? 'Data lantai berhasil diperbarui.' : 'Data lantai berhasil ditambahkan.',
    })
    fetchFloors()
  } else {
    if (result.error?.response?.status === 422) {
      formErrors.value = result.error.response.data.errors || {}
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message:
          result.error.response.data.message ||
          'Data lantai tidak valid. Silakan periksa kembali form pengisian.',
      })
    } else {
      const errorMsg =
        result.error?.response?.data?.message ||
        `Terjadi kesalahan saat ${isEditing ? 'memperbarui' : 'menambahkan'} data lantai.`
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
  if (!floorToDelete.value) return

  const idToDelete = floorToDelete.value.id
  const result = await deleteFloor(idToDelete)

  if (result.success) {
    closeDeleteDialog()
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Data lantai berhasil dihapus.',
    })

    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--
    }

    fetchFloors()
  } else {
    const errorMsg = result.error?.response?.data?.message || 'Data lantai gagal dihapus.'
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: errorMsg,
    })
    closeDeleteDialog()
  }
}

onMounted(() => {
  fetchFloors()
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
      title="Data Lantai"
      subtitle="Kelola data lantai berdasarkan gedung untuk mendukung pengaturan ruangan dan tempat tidur rumah sakit."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Data Bed', active: false },
        { label: 'Data Lantai', active: true },
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
          Tambah Lantai
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <FloorFilters
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchFloors"
      />

      <MasterDataErrorState v-if="error" :error="error" @retry="fetchFloors" />

      <template v-else>
        <MasterDataEmptyState v-if="!loading && items.length === 0" :is-search="!!filters.search">
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Lantai</button>
          </template>
        </MasterDataEmptyState>

        <template v-else>
          <FloorTable
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
    <FloorFormModal
      :is-open="formModalOpen"
      :floor="selectedFloor"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog
      :is-open="deleteDialogOpen"
      :is-deleting="submitting"
      title="Hapus Lantai"
      :confirm-message="
        floorToDelete
          ? `Apakah Anda yakin ingin menghapus lantai “${floorToDelete.name}” dari “${floorToDelete.building?.name || 'Gedung'}”?`
          : ''
      "
      confirm-text="Hapus"
      @close="closeDeleteDialog"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>
.page-container {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-content {
  margin-top: 1.5rem;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background-color: var(--color-primary);
  color: #ffffff;
  padding: 0.625rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn-primary:hover {
  background-color: var(--color-primary-dark);
}

.btn-primary svg {
  width: 18px;
  height: 18px;
}
</style>
