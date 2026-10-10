<script setup>
import { ref, onMounted } from 'vue';
import { useTariffTypes } from '@/composables/useTariffTypes';
import TariffTypeTable from '@/components/master-data/tariff-types/TariffTypeTable.vue';
import TariffTypeFilters from '@/components/master-data/tariff-types/TariffTypeFilters.vue';
import TariffTypeFormModal from '@/components/master-data/tariff-types/TariffTypeFormModal.vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';

const {
  tariffTypes,
  totalItems,
  currentPage,
  perPage,
  loading,
  error,
  fetchTariffTypes,
  createTariffType,
  updateTariffType,
  deleteTariffType
} = useTariffTypes();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedItem = ref(null);
const formErrors = ref({});
const isSubmitting = ref(false);
const toast = ref({
  show: false,
  type: 'success',
  title: '',
  message: ''
});

const filters = ref({
  search: ''
});

const sort = ref({
  column: 'created_at',
  direction: 'desc'
});

const showToast = ({ type = 'success', title, message }) => {
  toast.value = { show: true, type, title, message };
};

const closeToast = () => {
  toast.value.show = false;
};

const openAddModal = () => {
  selectedItem.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedItem.value = item;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openDeleteDialog = (item) => {
  selectedItem.value = item;
  deleteDialogOpen.value = true;
};

const handleFilter = ({ key, value }) => {
  filters.value[key] = value;
  currentPage.value = 1;
  loadData();
};

const handleSort = ({ column, direction }) => {
  sort.value.column = column;
  sort.value.direction = direction;
  loadData();
};

const setPage = (page) => {
  if (page >= 1 && page <= Math.ceil(totalItems.value / perPage.value)) {
    currentPage.value = page;
    loadData();
  }
};

const loadData = () => {
  fetchTariffTypes({
    search: filters.value.search,
    sort_by: sort.value.column,
    sort_dir: sort.value.direction
  });
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};
  isSubmitting.value = true;
  
  const isEditing = Boolean(selectedItem.value);
  
  try {
    if (isEditing) {
      await updateTariffType(selectedItem.value.id, payload);
    } else {
      await createTariffType(payload);
    }
    
    formModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing 
        ? 'Jenis tarif berhasil diperbarui.' 
        : 'Jenis tarif berhasil ditambahkan.'
    });
    loadData();
  } catch (err) {
    if (err.response?.status === 422) {
      formErrors.value = err.response.data.errors || {};
      showToast({
        type: 'error',
        title: 'Validasi Gagal',
        message: 'Mohon periksa kembali form pengisian.'
      });
    } else {
      showToast({
        type: 'error',
        title: 'Gagal',
        message: err.response?.data?.message || `Gagal ${isEditing ? 'memperbarui' : 'menambahkan'} jenis tarif.`
      });
    }
  } finally {
    isSubmitting.value = false;
  }
};

const handleDeleteConfirm = async () => {
  isSubmitting.value = true;
  try {
    await deleteTariffType(selectedItem.value.id);
    deleteDialogOpen.value = false;
    
    // Pagination adjustment
    if (tariffTypes.value.length === 1 && currentPage.value > 1) {
      currentPage.value--;
    }
    
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Jenis tarif berhasil dihapus.'
    });
    loadData();
  } catch (err) {
    let errorMessage = 'Gagal menghapus jenis tarif.';
    
    if (err.response?.status === 409 || err.response?.status === 422) {
      errorMessage = err.response?.data?.message || 'Jenis tarif tidak dapat dihapus karena masih digunakan.';
    } else if (err.response?.data?.message) {
      errorMessage = err.response.data.message;
    }
    
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: errorMessage
    });
    deleteDialogOpen.value = false;
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(() => {
  loadData();
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
      title="Jenis Tarif"
      subtitle="Kelola master data jenis tarif dan persentase komponennya."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Tindakan', active: false },
        { label: 'Jenis Tarif', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Jenis Tarif
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <TariffTypeFilters 
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="loadData"
      />
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error" 
        @retry="loadData" 
      />
      
      <template v-else>
        <MasterDataEmptyState 
          v-if="!loading && tariffTypes.length === 0" 
          :is-search="!!filters.search"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Jenis Tarif</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <TariffTypeTable 
            :items="tariffTypes"
            :pagination="{ current_page: currentPage, per_page: perPage, total: totalItems }"
            :sort-config="sort"
            :loading="loading"
            @sort="handleSort"
            @edit="openEditModal"
            @delete="openDeleteDialog"
          />
          
          <MasterDataPagination 
            :pagination="{ current_page: currentPage, last_page: Math.ceil(totalItems / perPage), per_page: perPage, total: totalItems }"
            :loading="loading"
            :item-count="tariffTypes.length"
            @page-change="setPage"
          />
        </template>
      </template>
    </div>

    <!-- Modals -->
    <TariffTypeFormModal 
      :is-open="formModalOpen"
      :edit-data="selectedItem"
      :is-submitting="isSubmitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @submit="handleFormSubmit"
    />
    
    <MasterDataDeleteDialog 
      :is-open="deleteDialogOpen"
      title="Hapus Jenis Tarif?"
      :item-name="selectedItem ? selectedItem.name : ''"
      warning-message="Tindakan ini tidak dapat dibatalkan. Pastikan jenis tarif ini tidak terhubung dengan tindakan lain."
      :is-submitting="isSubmitting"
      @close="deleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>
