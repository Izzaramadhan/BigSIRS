<script setup>
import { ref, onMounted } from 'vue'
import { useWards } from '@/composables/useWards'
import WardTable from '@/components/master-data/wards/WardTable.vue'
import WardFilters from '@/components/master-data/wards/WardFilters.vue'
import WardFormModal from '@/components/master-data/wards/WardFormModal.vue'
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
  fetchWards,
  createWard,
  updateWard,
  deleteWard,
  setPage,
} = useWards()

const formModalOpen = ref(false)
const deleteDialogOpen = ref(false)
const selectedWard = ref(null)
const wardToDelete = ref(null)
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
  selectedWard.value = null
  formErrors.value = {}
  formModalOpen.value = true
}

const openEditModal = (item) => {
  selectedWard.value = item
  formErrors.value = {}
  formModalOpen.value = true
}

const closeFormModal = () => {
  formModalOpen.value = false
  selectedWard.value = null
  formErrors.value = {}
}

const openDeleteDialog = (item) => {
  wardToDelete.value = item
  deleteDialogOpen.value = true
}

const closeDeleteDialog = () => {
  deleteDialogOpen.value = false
  wardToDelete.value = null
}

const handleFilter = ({ key, value }) => {
  filters[key] = value
  pagination.current_page = 1
  fetchWards()
}

const handleFormSubmit = async (payload) => {
  formErrors.value = {}

  const isEditing = Boolean(selectedWard.value)
  let result

  if (isEditing) {
    result = await updateWard(selectedWard.value.id, payload)
  } else {
    result = await createWard(payload)
  }

  if (result.success) {
    closeFormModal()
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing ? 'Data bangsal berhasil diperbarui.' : 'Data bangsal berhasil ditambahkan.',
    })
    fetchWards()
  } else {
    if (result.error?.response?.status === 422) {
      formErrors.value = result.error.response.data.errors || {}
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message:
          result.error.response.data.message ||
          'Data bangsal tidak valid. Silakan periksa kembali form pengisian.',
      })
    } else {
      const errorMsg =
        result.error?.response?.data?.message ||
        `Terjadi kesalahan saat ${isEditing ? 'memperbarui' : 'menambahkan'} data bangsal.`
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
  if (!wardToDelete.value) return

  const idToDelete = wardToDelete.value.id
  const result = await deleteWard(idToDelete)

  if (result.success) {
    closeDeleteDialog()
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Data bangsal berhasil dihapus.',
    })

    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--
    }

    fetchWards()
  } else {
    const errorMsg = result.error?.response?.data?.message || 'Data bangsal gagal dihapus.'
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: errorMsg,
    })
    closeDeleteDialog()
  }
}

const getDeleteConfirmMessage = (ward) => {
  if (!ward) return ''
  const floorDisplay =
    ward.floor_display_name ||
    (ward.floor
      ? ward.floor.building?.name
        ? `${ward.floor.name} - ${ward.floor.building.name}`
        : ward.floor.name
      : 'Lantai')
  return `Apakah Anda yakin ingin menghapus bangsal “${ward.name}” dari “${floorDisplay}”?`
}

onMounted(() => {
  fetchWards()
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
      title="Data Bangsal"
      subtitle="Kelola data bangsal berdasarkan lantai dan gedung untuk mendukung pengaturan ruangan serta tempat tidur rumah sakit."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Data Bed', active: false },
        { label: 'Data Bangsal', active: true },
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
          Tambah Bangsal
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <WardFilters
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchWards"
      />

      <MasterDataErrorState v-if="error" :error="error" @retry="fetchWards" />

      <template v-else>
        <MasterDataEmptyState v-if="!loading && items.length === 0" :is-search="!!filters.search">
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Bangsal</button>
          </template>
        </MasterDataEmptyState>

        <template v-else>
          <WardTable
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
    <WardFormModal
      :is-open="formModalOpen"
      :ward="selectedWard"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog
      :is-open="deleteDialogOpen"
      :is-deleting="submitting"
      title="Hapus Bangsal"
      :confirm-message="getDeleteConfirmMessage(wardToDelete)"
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
