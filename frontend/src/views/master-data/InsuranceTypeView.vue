<script setup>
import { ref, onMounted, watch } from 'vue'
import { useGuarantors } from '@/composables/useGuarantors'
import InsuranceTypeFormModal from '@/components/master-data/insurance-types/InsuranceTypeFormModal.vue'
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
  guarantors,
  meta,
  loading,
  error,
  fetchGuarantors,
  createGuarantor,
  updateGuarantor,
  deleteGuarantor
} = useGuarantors()

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
const selectedGuarantor = ref(null)

const isDeleteModalOpen = ref(false)
const guarantorToDelete = ref(null)

const isSubmitting = ref(false)
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
    await fetchGuarantors({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value
    })
  } catch (err) {
    console.error('Failed to load guarantors:', err)
  }
}

const openAddModal = () => {
  selectedGuarantor.value = null
  formErrors.value = {}
  isFormModalOpen.value = true
}

const openEditModal = (guarantor) => {
  selectedGuarantor.value = { ...guarantor }
  formErrors.value = {}
  isFormModalOpen.value = true
}

const closeFormModal = () => {
  isFormModalOpen.value = false
  selectedGuarantor.value = null
  formErrors.value = {}
}

const handleFormSubmit = async (payload) => {
  formErrors.value = {}
  isSubmitting.value = true

  const isEditing = Boolean(selectedGuarantor.value && selectedGuarantor.value.id)
  try {
    if (isEditing) {
      if (!selectedGuarantor.value?.id) return
      await updateGuarantor(selectedGuarantor.value.id, payload)
    } else {
      await createGuarantor(payload)
    }

    closeFormModal()
    showToast(
      'Berhasil',
      `Data jenis asuransi berhasil ${isEditing ? 'diperbarui' : 'ditambahkan'}.`,
      'success'
    )
    loadData()
  } catch (err) {
    if (err.response?.status === 422) {
      formErrors.value = err.response.data.errors || {}
      showToast(
        isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        `Data jenis asuransi gagal ${isEditing ? 'diperbarui' : 'ditambahkan'}.`,
        'error'
      )
    } else {
      const msg = err.response?.data?.message || `Data jenis asuransi gagal ${isEditing ? 'diperbarui' : 'ditambahkan'}.`
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

const confirmDelete = (guarantor) => {
  guarantorToDelete.value = guarantor
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  guarantorToDelete.value = null
}

const handleDelete = async () => {
  if (!guarantorToDelete.value || !guarantorToDelete.value.id) return

  isSubmitting.value = true
  try {
    await deleteGuarantor(guarantorToDelete.value.id)
    closeDeleteModal()

    if (guarantors.value.length === 1 && currentPage.value > 1) {
      currentPage.value--
    }

    loadData()
    showToast('Berhasil', 'Data jenis asuransi berhasil dihapus.', 'success')
  } catch (err) {
    let errorMessage = 'Data jenis asuransi gagal dihapus.'
    if (err.response?.status === 409) {
      errorMessage = err.response.data.message || 'Data jenis asuransi tidak dapat dihapus karena masih digunakan.'
    } else if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    }
    showToast('Gagal Menghapus', errorMessage, 'error')
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
  return (currentPage.value - 1) * perPage.value + index + 1
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
      title="Jenis Asuransi"
      subtitle="Kelola data dasar jenis asuransi dan penjamin."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Data Dasar', active: false },
        { label: 'Jenis Asuransi', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Jenis Asuransi
        </button>
      </template>
    </MasterDataPageHeader>

    <div class="page-content">
      <div class="filters-container">
        <MasterDataSearchInput
          v-model="searchQuery"
          placeholder="Cari jenis asuransi..."
        />

        <div class="filter-actions">
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
        v-else-if="!loading && guarantors.length === 0"
        title="Data tidak ditemukan"
        message="Belum ada data jenis asuransi yang ditambahkan atau tidak ada yang cocok dengan pencarian Anda."
      />

      <template v-else-if="loading || guarantors.length > 0">
        <div class="table-wrapper">
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="col-no text-center">No</th>
                  <th class="col-name">Jenis Asuransi</th>
                  <th class="col-gov text-center">Asuransi Pemerintah</th>
                  <th class="col-inacbg text-center">ID INACBG</th>
                  <th class="col-actions text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <template v-if="loading">
                  <MasterDataSkeletonRow
                    :columns="5"
                    :rows="5"
                  />
                </template>

                <template v-else>
                  <tr v-for="(item, index) in guarantors" :key="item.id">
                    <td class="col-no text-muted text-center">{{ getRowNumber(index) }}</td>
                    <td class="col-name font-medium text-navy">{{ item.name }}</td>
                    <td class="col-gov text-center">{{ item.is_government ? 'Ya' : 'Tidak' }}</td>
                    <td class="col-inacbg text-center">{{ item.inacbg_id === null || item.inacbg_id === '' ? '—' : item.inacbg_id }}</td>
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
          :pagination="{
            current_page: currentPage,
            last_page: meta.last_page || 1,
            total: meta.total || 0,
            per_page: perPage,
            from: meta.from,
            to: meta.to
          }"
          :loading="loading"
          :item-count="guarantors.length"
          @page-change="handlePageChange"
        />
      </template>
    </div>

    <InsuranceTypeFormModal
      :is-open="isFormModalOpen"
      :insurance-type="selectedGuarantor"
      :is-submitting="isSubmitting"
      :errors="formErrors"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog
      :is-open="isDeleteModalOpen"
      :item-name="guarantorToDelete?.name"
      :is-submitting="isSubmitting"
      title="Hapus Jenis Asuransi"
      item-type="Jenis Asuransi"
      @close="closeDeleteModal"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
@import '@/assets/master-data.css';

.col-no { width: 60px; text-align: center; }
.col-name { min-width: 220px; }
.col-gov { width: 180px; text-align: center; }
.col-inacbg { width: 140px; text-align: center; }
.col-actions { width: 110px; text-align: center; }
</style>
