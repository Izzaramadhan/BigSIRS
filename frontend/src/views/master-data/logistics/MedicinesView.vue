<script setup>
import { ref, onMounted } from 'vue';
import { useMedicines } from '@/composables/useMedicines';
import MedicineTable from './components/MedicineTable.vue';
import MedicineFilters from './components/MedicineFilters.vue';
import MedicineFormModal from './components/MedicineFormModal.vue';
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
  fetchMedicines,
  createMedicine,
  updateMedicine,
  deleteMedicine,
  setPage,
  setSort
} = useMedicines();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedMedicine = ref(null);
const medicineToDelete = ref(null);
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
  selectedMedicine.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedMedicine.value = { ...item };
  formErrors.value = {};
  formModalOpen.value = true;
};

const openDeleteDialog = (item) => {
  medicineToDelete.value = item;
  deleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
  if (submitting.value) return;
  deleteDialogOpen.value = false;
  medicineToDelete.value = null;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchMedicines();
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};

  const isEditing = Boolean(selectedMedicine.value);
  let result;

  if (isEditing) {
    result = await updateMedicine(selectedMedicine.value.id, payload);
  } else {
    result = await createMedicine(payload);
  }

  if (result.success) {
    formModalOpen.value = false;
    selectedMedicine.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing
        ? 'Obat berhasil diperbarui.'
        : 'Obat berhasil ditambahkan.'
    });
    fetchMedicines();
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
  if (!medicineToDelete.value || submitting.value) return;

  const result = await deleteMedicine(medicineToDelete.value.id);

  if (result.success) {
    deleteDialogOpen.value = false;
    medicineToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Obat berhasil dihapus.'
    });

    // Adjust pagination if deleting the only record on later page
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }

    fetchMedicines();
  } else {
    let errorMessage = 'Obat gagal dihapus. Silakan coba lagi.';
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
  fetchMedicines();
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
      title="Obat"
      subtitle="Kelola master data obat."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Logistik', active: false },
        { label: 'Obat', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Obat
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <MedicineFilters
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchMedicines"
      />

      <MasterDataErrorState
        v-if="error"
        :error="error"
        @retry="fetchMedicines"
      />

      <template v-else>
        <MasterDataEmptyState
          v-if="!loading && items.length === 0"
          :is-search="!!filters.search"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Obat</button>
          </template>
        </MasterDataEmptyState>

        <template v-else>
          <MedicineTable
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
    <MedicineFormModal
      :is-open="formModalOpen"
      :medicine="selectedMedicine"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog
      :is-open="deleteDialogOpen"
      title="Hapus Obat"
      :item-name="medicineToDelete ? medicineToDelete.name : ''"
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
