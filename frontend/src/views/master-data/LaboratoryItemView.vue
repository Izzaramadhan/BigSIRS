<script setup>
import { ref, onMounted } from 'vue';
import { useLaboratoryItems } from '@/composables/useLaboratoryItems';
import LaboratoryItemTable from '@/components/master-data/laboratory-items/LaboratoryItemTable.vue';
import LaboratoryItemFilters from '@/components/master-data/laboratory-items/LaboratoryItemFilters.vue';
import LaboratoryItemFormModal from '@/components/master-data/laboratory-items/LaboratoryItemFormModal.vue';
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
  fetchLaboratoryItems,
  createLaboratoryItem,
  updateLaboratoryItem,
  deleteLaboratoryItem,
  setPage,
  setSort
} = useLaboratoryItems();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedItem = ref(null);
const itemToDelete = ref(null);
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
  selectedItem.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedItem.value = { ...item };
  formErrors.value = {};
  formModalOpen.value = true;
};

const openDeleteDialog = (item) => {
  itemToDelete.value = item;
  deleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
  if (submitting.value) return;
  deleteDialogOpen.value = false;
  itemToDelete.value = null;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchLaboratoryItems();
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};
  
  const isEditing = Boolean(selectedItem.value);
  let result;
  
  if (isEditing) {
    result = await updateLaboratoryItem(selectedItem.value.id, payload);
  } else {
    result = await createLaboratoryItem(payload);
  }
  
  if (result.success) {
    formModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing 
        ? 'Item lab berhasil diperbarui.' 
        : 'Item lab berhasil ditambahkan.'
    });
    fetchLaboratoryItems();
  } else {
    if (result.error?.response?.status === 422) {
      formErrors.value = result.error.response.data.errors || {};
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message: result.error.response.data.message || 'Silakan periksa kembali data yang dimasukkan.'
      });
    } else {
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message: result.error?.response?.data?.message || 'Terjadi kesalahan sistem. Silakan coba lagi.'
      });
    }
  }
};

const handleDeleteConfirm = async () => {
  if (!itemToDelete.value) return;

  const result = await deleteLaboratoryItem(itemToDelete.value.id);
  
  if (result.success) {
    deleteDialogOpen.value = false;
    itemToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Item lab berhasil dihapus.'
    });
    
    // Adjust pagination if deleting the only record on later page
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }
    
    fetchLaboratoryItems();
  } else {
    let errorMessage = 'Item lab gagal dihapus. Silakan coba lagi.';
    if (result.error?.response?.data?.message) {
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
  fetchLaboratoryItems();
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
      title="Item Lab"
      subtitle="Kelola master data item dan nilai rujukan pemeriksaan laboratorium."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Data Lab', active: false },
        { label: 'Item Lab', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Item Lab
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <LaboratoryItemFilters 
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchLaboratoryItems"
      />
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error" 
        @retry="fetchLaboratoryItems" 
      />
      
      <template v-else>
        <MasterDataEmptyState 
          v-if="!loading && items.length === 0" 
          :is-search="Boolean(filters.search)"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Item Lab</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <LaboratoryItemTable 
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
    <LaboratoryItemFormModal 
      :is-open="formModalOpen"
      :item="selectedItem"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @submit="handleFormSubmit"
    />
    
    <MasterDataDeleteDialog 
      :is-open="deleteDialogOpen"
      title="Hapus Item Lab"
      :item-name="itemToDelete ? itemToDelete.name : ''"
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
