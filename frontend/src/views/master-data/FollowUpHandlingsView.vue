<script setup>
import { ref, onMounted } from 'vue';
import { useFollowUpHandlings } from '@/composables/useFollowUpHandlings';
import FollowUpHandlingTable from '@/components/master-data/follow-up-handlings/FollowUpHandlingTable.vue';
import FollowUpHandlingFilters from '@/components/master-data/follow-up-handlings/FollowUpHandlingFilters.vue';
import FollowUpHandlingFormModal from '@/components/master-data/follow-up-handlings/FollowUpHandlingFormModal.vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';

const {
  items,
  pagination,
  filters,
  loading,
  submitting,
  error,
  fetchHandlings,
  createHandling,
  updateHandling,
  deleteHandling,
  setPage
} = useFollowUpHandlings();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedHandling = ref(null);
const handlingToDelete = ref(null);
const formErrors = ref({});

const toast = ref({
  show: false,
  type: 'success',
  title: '',
  message: ''
});

const showToast = ({ type = 'success', title, message }) => {
  toast.value = { show: true, type, title, message };
};

const closeToast = () => {
  toast.value.show = false;
};

const openAddModal = () => {
  selectedHandling.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedHandling.value = { ...item };
  formErrors.value = {};
  formModalOpen.value = true;
};

const closeFormModal = () => {
  if (submitting.value) return;
  formModalOpen.value = false;
  selectedHandling.value = null;
  formErrors.value = {};
};

const openDeleteDialog = (item) => {
  handlingToDelete.value = item;
  deleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
  if (submitting.value) return;
  deleteDialogOpen.value = false;
  handlingToDelete.value = null;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchHandlings(1);
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};

  const isEditing = Boolean(selectedHandling.value);
  let result;

  if (isEditing) {
    result = await updateHandling(selectedHandling.value.id, payload);
  } else {
    result = await createHandling(payload);
  }

  if (result.success) {
    formModalOpen.value = false;
    selectedHandling.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing
        ? 'Penanganan lanjutan berhasil diperbarui.'
        : 'Penanganan lanjutan berhasil ditambahkan.'
    });
    fetchHandlings(pagination.current_page);
  } else {
    if (result.errors || result.error?.response?.status === 422) {
      formErrors.value = result.errors || result.error?.response?.data?.errors || {};
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message: result.error?.response?.data?.message || (typeof result.error === 'string' ? result.error : 'Silakan periksa kembali data yang dimasukkan.')
      });
    } else {
      const msg = typeof result.error === 'string'
        ? result.error
        : (result.error?.response?.data?.message || 'Terjadi kesalahan sistem. Silakan coba lagi.');
      formErrors.value = { general: msg };
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message: msg
      });
    }
  }
};

const handleDeleteConfirm = async () => {
  if (!handlingToDelete.value || submitting.value) return;

  const result = await deleteHandling(handlingToDelete.value.id);

  if (result.success) {
    deleteDialogOpen.value = false;
    handlingToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Penanganan lanjutan berhasil dihapus.'
    });

    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }

    fetchHandlings(pagination.current_page);
  } else {
    let errorMessage = 'Penanganan lanjutan gagal dihapus. Silakan coba lagi.';
    if (typeof result.error === 'string') {
      errorMessage = result.error;
    } else if (result.error?.response?.data?.message) {
      errorMessage = result.error.response.data.message;
    }

    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: errorMessage
    });
  }
};

onMounted(() => {
  fetchHandlings();
});
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
      title="Penanganan Lanjutan"
      subtitle="Kelola data referensi penanganan lanjutan pasien."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Penanganan Lanjutan', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Penanganan Lanjutan
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <FollowUpHandlingFilters
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="() => fetchHandlings(pagination.current_page)"
      />

      <MasterDataErrorState
        v-if="error"
        :error="error"
        @retry="() => fetchHandlings(pagination.current_page)"
      />

      <template v-else>
        <MasterDataEmptyState
          v-if="!loading && items.length === 0"
          :is-search="!!filters.search"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Penanganan Lanjutan</button>
          </template>
        </MasterDataEmptyState>

        <template v-else>
          <FollowUpHandlingTable
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
    <FollowUpHandlingFormModal
      :is-open="formModalOpen"
      :item="selectedHandling"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog
      :is-open="deleteDialogOpen"
      title="Hapus Penanganan Lanjutan"
      :item-name="handlingToDelete?.name"
      warning-message="Pastikan data yang dipilih sudah benar. Data yang dihapus tidak dapat dikembalikan."
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
  gap: 24px;
}

.page-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
</style>
