<script setup>
import { ref, onMounted } from 'vue';
import { usePositions } from '@/composables/usePositions';
import PositionTable from '@/components/master-data/positions/PositionTable.vue';
import PositionFilters from '@/components/master-data/positions/PositionFilters.vue';
import PositionFormModal from '@/components/master-data/positions/PositionFormModal.vue';
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
  fetchPositions,
  createPosition,
  updatePosition,
  deletePosition,
  setPage
} = usePositions();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedPosition = ref(null);
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
  selectedPosition.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedPosition.value = item;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openDeleteDialog = (item) => {
  selectedPosition.value = item;
  deleteDialogOpen.value = true;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchPositions();
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};
  
  const isEditing = Boolean(selectedPosition.value);
  let result;
  
  if (isEditing) {
    result = await updatePosition(selectedPosition.value.id, payload);
  } else {
    result = await createPosition(payload);
  }
  
  if (result.success) {
    formModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing 
        ? 'Data jabatan berhasil diperbarui.' 
        : 'Data jabatan berhasil ditambahkan.'
    });
    fetchPositions();
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
        message: result.error.response?.data?.message || `Gagal ${isEditing ? 'memperbarui' : 'menambahkan'} jabatan.`
      });
    }
  }
};

const handleDeleteConfirm = async () => {
  const result = await deletePosition(selectedPosition.value.id);
  
  if (result.success) {
    deleteDialogOpen.value = false;
    
    // Pagination adjustment
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }
    
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Data jabatan berhasil dihapus.'
    });
    fetchPositions();
  } else {
    let errorMessage = 'Gagal menghapus jabatan.';
    
    if (result.error.response?.status === 422 || result.error.response?.status === 409) {
      errorMessage = result.error.response.data.message || 'Jabatan tidak dapat dihapus karena masih digunakan oleh data pegawai.';
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
  fetchPositions();
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
      title="Data Jabatan"
      subtitle="Kelola data dasar tingkat jabatan pegawai."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Data Pegawai', active: false },
        { label: 'Jabatan', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Jabatan
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <PositionFilters 
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchPositions"
      />
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error" 
        @retry="fetchPositions" 
      />
      
      <template v-else>
        <MasterDataEmptyState 
          v-if="!loading && items.length === 0" 
          :is-search="!!filters.search"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Jabatan</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <PositionTable 
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
    <PositionFormModal 
      :is-open="formModalOpen"
      :position="selectedPosition"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @submit="handleFormSubmit"
    />
    
    <MasterDataDeleteDialog 
      :is-open="deleteDialogOpen"
      title="Hapus Jabatan?"
      :item-name="selectedPosition ? selectedPosition.name : ''"
      warning-message="Tindakan ini tidak dapat dibatalkan."
      :is-submitting="submitting"
      @close="deleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>
</style>
