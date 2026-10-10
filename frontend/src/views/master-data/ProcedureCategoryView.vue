<script setup>
import { ref, onMounted } from 'vue';
import { useProcedureCategories } from '@/composables/useProcedureCategories';
import ProcedureCategoryTable from '@/components/master-data/procedure-categories/ProcedureCategoryTable.vue';
import ProcedureCategoryFilters from '@/components/master-data/procedure-categories/ProcedureCategoryFilters.vue';
import ProcedureCategoryFormModal from '@/components/master-data/procedure-categories/ProcedureCategoryFormModal.vue';
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
  fetchProcedureCategories,
  createProcedureCategory,
  updateProcedureCategory,
  archiveProcedureCategory,
  setPage,
  setSort
} = useProcedureCategories();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedCategory = ref(null);
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
  selectedCategory.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedCategory.value = item;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openDeleteDialog = (item) => {
  selectedCategory.value = item;
  deleteDialogOpen.value = true;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchProcedureCategories();
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};
  
  const isEditing = Boolean(selectedCategory.value);
  let result;
  
  if (isEditing) {
    result = await updateProcedureCategory(selectedCategory.value.id, payload);
  } else {
    result = await createProcedureCategory(payload);
  }
  
  if (result.success) {
    formModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing 
        ? 'Kategori tindakan berhasil diperbarui.' 
        : 'Kategori tindakan berhasil ditambahkan.'
    });
    fetchProcedureCategories();
  } else {
    if (result.error.response?.status === 422) {
      formErrors.value = result.error.response.data.errors || {};
      showToast({
        type: 'error',
        title: 'Validasi Gagal',
        message: 'Mohon periksa kembali form pengisian.'
      });
    } else {
      showToast({
        type: 'error',
        title: 'Gagal',
        message: result.error.response?.data?.message || `Gagal ${isEditing ? 'memperbarui' : 'menambahkan'} kategori tindakan.`
      });
    }
  }
};

const handleDeleteConfirm = async () => {
  const result = await archiveProcedureCategory(selectedCategory.value.id);
  
  if (result.success) {
    deleteDialogOpen.value = false;
    
    // Pagination adjustment
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }
    
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Kategori tindakan berhasil dihapus.'
    });
    fetchProcedureCategories();
  } else {
    let errorMessage = 'Gagal menghapus kategori tindakan.';
    
    if (result.error.response?.status === 422 || result.error.response?.status === 409) {
      errorMessage = result.error.response.data.message || 'Kategori tindakan tidak dapat dihapus karena masih digunakan oleh data tindakan.';
    } else if (result.error.response?.data?.message) {
      errorMessage = result.error.response.data.message;
    }
    
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: errorMessage
    });
    deleteDialogOpen.value = false;
  }
};

onMounted(() => {
  fetchProcedureCategories();
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
      title="Kategori Tindakan"
      subtitle="Kelola master data kategori tindakan."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Tindakan', active: false },
        { label: 'Kategori', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Kategori Tindakan
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <ProcedureCategoryFilters 
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchProcedureCategories"
      />
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error" 
        @retry="fetchProcedureCategories" 
      />
      
      <template v-else>
        <MasterDataEmptyState 
          v-if="!loading && items.length === 0" 
          :is-search="!!filters.search"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Kategori Tindakan</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <ProcedureCategoryTable 
            :items="items"
            :pagination="pagination"
            :sort-config="sort"
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
    <ProcedureCategoryFormModal 
      :is-open="formModalOpen"
      :category="selectedCategory"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @submit="handleFormSubmit"
    />
    
    <MasterDataDeleteDialog 
      :is-open="deleteDialogOpen"
      title="Hapus Kategori Tindakan?"
      :item-name="selectedCategory ? selectedCategory.name : ''"
      warning-message="Tindakan ini tidak dapat dibatalkan. Kategori ini mungkin terkait dengan data tindakan lainnya."
      :is-submitting="submitting"
      @close="deleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>
</style>
