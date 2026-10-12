<script setup>
import { ref, onMounted } from 'vue';
import { useTariffComponents } from '@/composables/useTariffComponents';
import TariffComponentTable from '@/components/master-data/tariff-components/TariffComponentTable.vue';
import TariffComponentFilters from '@/components/master-data/tariff-components/TariffComponentFilters.vue';
import TariffComponentFormModal from '@/components/master-data/tariff-components/TariffComponentFormModal.vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';

const {
  tariffComponents,
  totalItems,
  currentPage,
  perPage,
  loading,
  error,
  fetchTariffComponents,
  createTariffComponent,
  updateTariffComponent,
  deleteTariffComponent
} = useTariffComponents();

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
  fetchTariffComponents({
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
      await updateTariffComponent(selectedItem.value.id, payload);
    } else {
      await createTariffComponent(payload);
    }
    
    formModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing 
        ? 'Komponen berhasil diperbarui.' 
        : 'Komponen berhasil ditambahkan.'
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
        message: err.response?.data?.message || `Gagal ${isEditing ? 'memperbarui' : 'menambahkan'} komponen.`
      });
    }
  } finally {
    isSubmitting.value = false;
  }
};

const handleDeleteConfirm = async () => {
  isSubmitting.value = true;
  try {
    await deleteTariffComponent(selectedItem.value.id);
    deleteDialogOpen.value = false;
    
    // Pagination adjustment
    if (tariffComponents.value.length === 1 && currentPage.value > 1) {
      currentPage.value--;
    }
    
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Komponen berhasil dihapus.'
    });
    loadData();
  } catch (err) {
    let errorMessage = 'Gagal menghapus komponen.';
    
    if (err.response?.status === 409 || err.response?.status === 422) {
      errorMessage = err.response?.data?.message || 'Komponen tarif tidak dapat dihapus karena masih digunakan.';
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
      title="Komponen Tindakan"
      subtitle="Kelola master data komponen pembentuk tarif."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Tindakan', active: false },
        { label: 'Komponen', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Komponen
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <TariffComponentFilters 
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
          v-if="!loading && tariffComponents.length === 0" 
          :is-search="!!filters.search"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Komponen</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <TariffComponentTable 
            :items="tariffComponents"
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
            :item-count="tariffComponents.length"
            @page-change="setPage"
          />
        </template>
      </template>
    </div>

    <!-- Modals -->
    <TariffComponentFormModal 
      :is-open="formModalOpen"
      :edit-data="selectedItem"
      :is-submitting="isSubmitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @submit="handleFormSubmit"
    />
    
    <MasterDataDeleteDialog 
      :is-open="deleteDialogOpen"
      title="Hapus Komponen Tindakan?"
      :item-name="selectedItem ? selectedItem.name : ''"
      warning-message="Tindakan ini tidak dapat dibatalkan. Pastikan komponen ini tidak terhubung dengan paket tindakan lain."
      :is-submitting="isSubmitting"
      @close="deleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>
</style>
