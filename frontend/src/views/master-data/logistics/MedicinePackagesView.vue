<script setup>
import { ref, onMounted } from 'vue';
import { useMedicinePackages } from '@/composables/useMedicinePackages';
import MedicinePackageTable from './components/MedicinePackageTable.vue';
import MedicinePackageFilters from './components/MedicinePackageFilters.vue';
import MedicinePackageFormModal from './components/MedicinePackageFormModal.vue';
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
  fetchMedicinePackages,
  createMedicinePackage,
  updateMedicinePackage,
  deleteMedicinePackage,
  setPage,
  setSort
} = useMedicinePackages();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedPackage = ref(null);
const packageToDelete = ref(null);
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
  selectedPackage.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedPackage.value = JSON.parse(JSON.stringify(item));
  formErrors.value = {};
  formModalOpen.value = true;
};

const openDeleteDialog = (item) => {
  packageToDelete.value = item;
  deleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
  if (submitting.value) return;
  deleteDialogOpen.value = false;
  packageToDelete.value = null;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchMedicinePackages();
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};

  const isEditing = Boolean(selectedPackage.value);
  let result;

  if (isEditing) {
    result = await updateMedicinePackage(selectedPackage.value.id, payload);
  } else {
    result = await createMedicinePackage(payload);
  }

  if (result.success) {
    formModalOpen.value = false;
    selectedPackage.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing
        ? 'Paket obat berhasil diperbarui.'
        : 'Paket obat berhasil ditambahkan.'
    });
    fetchMedicinePackages();
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
  if (!packageToDelete.value || submitting.value) return;

  const result = await deleteMedicinePackage(packageToDelete.value.id);

  if (result.success) {
    deleteDialogOpen.value = false;
    packageToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Paket obat berhasil dihapus.'
    });

    // Adjust pagination if deleting the only record on later page
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }

    fetchMedicinePackages();
  } else {
    let errorMessage = 'Paket obat gagal dihapus. Silakan coba lagi.';
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
  fetchMedicinePackages();
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
      title="Master Paket Obat"
      subtitle="Kelola komposisi dan master data paket obat."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Logistik', active: false },
        { label: 'Master Paket Obat', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Paket Obat
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <MedicinePackageFilters
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchMedicinePackages"
      />

      <MasterDataErrorState
        v-if="error"
        :error="error"
        @retry="fetchMedicinePackages"
      />

      <template v-else>
        <MasterDataEmptyState
          v-if="!loading && items.length === 0"
          :is-search="!!filters.search"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Paket Obat</button>
          </template>
        </MasterDataEmptyState>

        <template v-else>
          <MedicinePackageTable
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
    <MedicinePackageFormModal
      :is-open="formModalOpen"
      :package-data="selectedPackage"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog
      :is-open="deleteDialogOpen"
      title="Hapus Paket Obat"
      :item-name="packageToDelete ? packageToDelete.name : ''"
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
