<script setup>
import { ref, onMounted } from 'vue';
import { usePolyclinics } from '@/composables/usePolyclinics';
import PolyclinicTable from '@/components/master-data/polyclinics/PolyclinicTable.vue';
import PolyclinicFilters from '@/components/master-data/polyclinics/PolyclinicFilters.vue';
import PolyclinicFormModal from '@/components/master-data/polyclinics/PolyclinicFormModal.vue';
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
  serviceTypes,
  fetchPolyclinics,
  fetchServiceTypes,
  createPolyclinic,
  updatePolyclinic,
  archivePolyclinic,
  setPage,
  setSort
} = usePolyclinics();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedPolyclinic = ref(null);
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
  selectedPolyclinic.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedPolyclinic.value = item;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openDeleteDialog = (item) => {
  selectedPolyclinic.value = item;
  deleteDialogOpen.value = true;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchPolyclinics();
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};
  
  const isEditing = Boolean(selectedPolyclinic.value);
  let result;
  
  if (isEditing) {
    result = await updatePolyclinic(selectedPolyclinic.value.id, payload);
  } else {
    result = await createPolyclinic(payload);
  }
  
  if (result.success) {
    formModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing 
        ? 'Data Poliklinik berhasil diperbarui.' 
        : 'Data Poliklinik berhasil ditambahkan.'
    });
    fetchPolyclinics();
  } else {
    if (result.error.response?.status === 422) {
      formErrors.value = result.error.response.data.errors || {};
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message: isEditing 
          ? 'Data Poliklinik gagal diperbarui. Silakan coba lagi.' 
          : 'Data Poliklinik gagal ditambahkan. Silakan periksa kembali data yang dimasukkan.'
      });
    } else if (result.error.response?.status === 409) {
      formErrors.value = { general: 'Terdapat poliklinik turunan, periksa konfigurasi induk.' };
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message: 'Terdapat poliklinik turunan, periksa konfigurasi induk.'
      });
    } else {
      formErrors.value = { general: 'Terjadi kesalahan sistem. Silakan coba lagi.' };
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message: isEditing 
          ? 'Data Poliklinik gagal diperbarui. Silakan coba lagi.' 
          : 'Data Poliklinik gagal ditambahkan. Silakan coba lagi.'
      });
    }
  }
};


const handleDeleteConfirm = async () => {
  const result = await archivePolyclinic(selectedPolyclinic.value.id);
  
  if (result.success) {
    deleteDialogOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Data Poliklinik berhasil diarsipkan.'
    });
    
    // Adjust pagination if needed
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }
    
    fetchPolyclinics();
  } else {
    let errorMessage = 'Data Poliklinik gagal diarsipkan. Silakan coba lagi.';
    
    if (result.error.response?.status === 409) {
      errorMessage = result.error.response.data.message || 'Poliklinik tidak dapat diarsipkan karena masih memiliki poliklinik turunan.';
    } else if (result.error.response?.data?.message) {
      errorMessage = result.error.response.data.message;
    }
    
    showToast({
      type: 'error',
      title: 'Gagal Mengarsipkan',
      message: errorMessage
    });
    deleteDialogOpen.value = false;
  }
};

onMounted(() => {
  fetchPolyclinics();
  fetchServiceTypes();
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
      title="Poliklinik"
      subtitle="Kelola data poliklinik dan kode integrasi pelayanan."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Poliklinik', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Poliklinik
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <PolyclinicFilters 
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchPolyclinics"
      />
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error" 
        @retry="fetchPolyclinics" 
      />
      
      <template v-else>
        <MasterDataEmptyState 
          v-if="!loading && items.length === 0" 
          :is-search="!!filters.search || filters.is_active !== null"
        >
          <template #action v-if="!filters.search && filters.is_active === null">
            <button class="btn-primary" @click="openAddModal">Tambah Poliklinik</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <PolyclinicTable 
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
    <PolyclinicFormModal 
      :is-open="formModalOpen"
      :polyclinic="selectedPolyclinic"
      :service-types="serviceTypes"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @submit="handleFormSubmit"
    />
    
    <MasterDataDeleteDialog 
      :is-open="deleteDialogOpen"
      title="Arsipkan Poliklinik?"
      :item-name="selectedPolyclinic ? `${selectedPolyclinic.code} - ${selectedPolyclinic.name}` : ''"
      warning-message="Poliklinik yang diarsipkan tidak akan tampil pada daftar aktif. Data ini tidak dihapus permanen."
      :is-submitting="submitting"
      @close="deleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>

</style>
