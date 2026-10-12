<script setup>
import { ref, onMounted } from 'vue';
import { useIcd10Codes } from '@/composables/useIcd10Codes';
import Icd10CodeTable from '@/components/master-data/icd10-codes/Icd10CodeTable.vue';
import Icd10CodeFilters from '@/components/master-data/icd10-codes/Icd10CodeFilters.vue';
import Icd10CodeFormModal from '@/components/master-data/icd10-codes/Icd10CodeFormModal.vue';
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
  sort,
  loading,
  submitting,
  error,
  fetchIcd10Codes,
  createIcd10Code,
  updateIcd10Code,
  deleteIcd10Code,
  setPage,
  setSort
} = useIcd10Codes();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedCode = ref(null);
const codeToDelete = ref(null);
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
  selectedCode.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedCode.value = { ...item };
  formErrors.value = {};
  formModalOpen.value = true;
};

const closeFormModal = () => {
  if (submitting.value) return;
  formModalOpen.value = false;
  selectedCode.value = null;
  formErrors.value = {};
};

const openDeleteDialog = (item) => {
  codeToDelete.value = item;
  deleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
  if (submitting.value) return;
  deleteDialogOpen.value = false;
  codeToDelete.value = null;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchIcd10Codes(1);
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};

  const isEditing = Boolean(selectedCode.value);
  let result;

  if (isEditing) {
    result = await updateIcd10Code(selectedCode.value.id, payload);
  } else {
    result = await createIcd10Code(payload);
  }

  if (result.success) {
    formModalOpen.value = false;
    selectedCode.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing
        ? 'Data ICD-10 berhasil diperbarui.'
        : 'Data ICD-10 berhasil ditambahkan.'
    });
    fetchIcd10Codes(pagination.current_page);
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
  if (!codeToDelete.value || submitting.value) return;

  const result = await deleteIcd10Code(codeToDelete.value.id);

  if (result.success) {
    deleteDialogOpen.value = false;
    codeToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Data ICD-10 berhasil dihapus.'
    });

    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }

    fetchIcd10Codes(pagination.current_page);
  } else {
    let errorMessage = 'Data ICD-10 gagal dihapus. Silakan coba lagi.';
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
  fetchIcd10Codes();
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
      title="ICD-10"
      subtitle="Kelola data klasifikasi penyakit dan diagnosis ICD-10."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Data Diagnosa', active: false },
        { label: 'ICD-10', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah ICD-10
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <Icd10CodeFilters
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="() => fetchIcd10Codes(pagination.current_page)"
      />

      <MasterDataErrorState
        v-if="error"
        :error="error"
        @retry="() => fetchIcd10Codes(pagination.current_page)"
      />

      <template v-else>
        <MasterDataEmptyState
          v-if="!loading && items.length === 0"
          :is-search="!!filters.search"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah ICD-10</button>
          </template>
        </MasterDataEmptyState>

        <template v-else>
          <Icd10CodeTable
            :items="items"
            :pagination="pagination"
            :sort="sort"
            :loading="loading"
            @sort="setSort"
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
    <Icd10CodeFormModal
      :is-open="formModalOpen"
      :code="selectedCode"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog
      :is-open="deleteDialogOpen"
      title="Hapus ICD-10"
      :item-name="codeToDelete ? `${codeToDelete.code} - ${codeToDelete.name}` : ''"
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
