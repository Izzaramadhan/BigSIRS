<script setup>
import { ref, onMounted } from 'vue'
import { useRoomClasses } from '@/composables/useRoomClasses'
import RoomClassTable from '@/components/master-data/room-classes/RoomClassTable.vue'
import RoomClassFilters from '@/components/master-data/room-classes/RoomClassFilters.vue'
import RoomClassFormModal from '@/components/master-data/room-classes/RoomClassFormModal.vue'
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
  fetchRoomClasses,
  createRoomClass,
  updateRoomClass,
  deleteRoomClass,
  setPage,
} = useRoomClasses()

const formModalOpen = ref(false)
const deleteDialogOpen = ref(false)
const selectedRoomClass = ref(null)
const roomClassToDelete = ref(null)
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
  selectedRoomClass.value = null
  formErrors.value = {}
  formModalOpen.value = true
}

const openEditModal = (item) => {
  selectedRoomClass.value = item
  formErrors.value = {}
  formModalOpen.value = true
}

const closeFormModal = () => {
  formModalOpen.value = false
  selectedRoomClass.value = null
  formErrors.value = {}
}

const openDeleteDialog = (item) => {
  roomClassToDelete.value = item
  deleteDialogOpen.value = true
}

const closeDeleteDialog = () => {
  deleteDialogOpen.value = false
  roomClassToDelete.value = null
}

const handleFilter = ({ key, value }) => {
  filters[key] = value
  pagination.current_page = 1
  fetchRoomClasses()
}

const handleFormSubmit = async (payload) => {
  formErrors.value = {}

  const isEditing = Boolean(selectedRoomClass.value)
  let result

  if (isEditing) {
    result = await updateRoomClass(selectedRoomClass.value.id, payload)
  } else {
    result = await createRoomClass(payload)
  }

  if (result.success) {
    closeFormModal()
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing ? 'Data kelas berhasil diperbarui.' : 'Data kelas berhasil ditambahkan.',
    })
    fetchRoomClasses()
  } else {
    if (result.error?.response?.status === 422) {
      formErrors.value = result.error.response.data.errors || {}
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message:
          result.error.response.data.message ||
          'Data kelas tidak valid. Silakan periksa kembali form pengisian.',
      })
    } else {
      const errorMsg =
        result.error?.response?.data?.message ||
        `Terjadi kesalahan saat ${isEditing ? 'memperbarui' : 'menambahkan'} data kelas.`
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
  if (!roomClassToDelete.value) return

  const idToDelete = roomClassToDelete.value.id
  const result = await deleteRoomClass(idToDelete)

  if (result.success) {
    closeDeleteDialog()
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Data kelas berhasil dihapus.',
    })

    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--
    }

    fetchRoomClasses()
  } else {
    const errorMsg =
      result.error?.response?.data?.message || 'Data kelas gagal dihapus.'
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: errorMsg,
    })
    closeDeleteDialog()
  }
}

const getDeleteConfirmMessage = (roomClass) => {
  if (!roomClass) return ''
  return `Apakah Anda yakin ingin menghapus kelas “${roomClass.name}”?`
}

onMounted(() => {
  fetchRoomClasses()
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
      title="Data Kelas"
      subtitle="Kelola kelas perawatan, harga, dan kode referensi untuk mendukung pengaturan kamar serta tempat tidur rumah sakit."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Data Bed', active: false },
        { label: 'Data Kelas', active: true },
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
          Tambah Kelas
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <RoomClassFilters
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchRoomClasses"
      />

      <MasterDataErrorState v-if="error" :error="error" @retry="fetchRoomClasses" />

      <template v-else>
        <MasterDataEmptyState v-if="!loading && items.length === 0" :is-search="!!filters.search">
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Kelas</button>
          </template>
        </MasterDataEmptyState>

        <template v-else>
          <RoomClassTable
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
    <RoomClassFormModal
      :is-open="formModalOpen"
      :room-class="selectedRoomClass"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog
      :is-open="deleteDialogOpen"
      :is-deleting="submitting"
      title="Hapus Kelas"
      :confirm-message="getDeleteConfirmMessage(roomClassToDelete)"
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
