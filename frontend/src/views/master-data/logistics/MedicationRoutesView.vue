<script setup>
import { ref, onMounted } from 'vue';
import { useMedicineRoutes } from '@/composables/useMedicineRoutes';
import MedicationRouteTable from './components/MedicationRouteTable.vue';
import MedicationRouteFilters from './components/MedicationRouteFilters.vue';
import MedicationRouteFormModal from './components/MedicationRouteFormModal.vue';
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
  fetchMedicineRoutes,
  createMedicineRoute,
  updateMedicineRoute,
  deleteMedicineRoute,
  setPage,
  setSort
} = useMedicineRoutes();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedRoute = ref(null);
const routeToDelete = ref(null);
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
  selectedRoute.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedRoute.value = { ...item };
  formErrors.value = {};
  formModalOpen.value = true;
};

const openDeleteDialog = (item) => {
  routeToDelete.value = item;
  deleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
  if (submitting.value) return;
  deleteDialogOpen.value = false;
  routeToDelete.value = null;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchMedicineRoutes();
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};

  const isEditing = Boolean(selectedRoute.value);
  let result;

  if (isEditing) {
    result = await updateMedicineRoute(selectedRoute.value.id, payload);
  } else {
    result = await createMedicineRoute(payload);
  }

  if (result.success) {
    formModalOpen.value = false;
    selectedRoute.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing
        ? 'Jalur masuk obat berhasil diperbarui.'
        : 'Jalur masuk obat berhasil ditambahkan.'
    });
    fetchMedicineRoutes();
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
  if (!routeToDelete.value || submitting.value) return;

  const result = await deleteMedicineRoute(routeToDelete.value.id);

  if (result.success) {
    deleteDialogOpen.value = false;
    routeToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Jalur masuk obat berhasil dihapus.'
    });

    // Adjust pagination if deleting the only record on later page
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }

    fetchMedicineRoutes();
  } else {
    let errorMessage = 'Jalur masuk obat gagal dihapus. Silakan coba lagi.';
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
  fetchMedicineRoutes();
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
      title="Jalur Masuk Obat"
      subtitle="Kelola master data jalur masuk obat."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Logistik', active: false },
        { label: 'Jalur Masuk Obat', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Jalur Masuk Obat
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <MedicationRouteFilters
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchMedicineRoutes"
      />

      <MasterDataErrorState
        v-if="error"
        :error="error"
        @retry="fetchMedicineRoutes"
      />

      <template v-else>
        <MasterDataEmptyState
          v-if="!loading && items.length === 0"
          :is-search="!!filters.search"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Jalur Masuk Obat</button>
          </template>
        </MasterDataEmptyState>

        <template v-else>
          <MedicationRouteTable
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
    <MedicationRouteFormModal
      :is-open="formModalOpen"
      :route="selectedRoute"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @submit="handleFormSubmit"
    />

    <MasterDataDeleteDialog
      :is-open="deleteDialogOpen"
      title="Hapus Jalur Masuk Obat"
      :item-name="routeToDelete ? routeToDelete.name : ''"
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
