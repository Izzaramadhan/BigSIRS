<script setup>
import { ref, onMounted } from 'vue';
import { useDoctors } from '@/composables/useDoctors';
import DoctorTable from '@/components/master-data/doctors/DoctorTable.vue';
import DoctorFilters from '@/components/master-data/doctors/DoctorFilters.vue';
import DoctorFormModal from '@/components/master-data/doctors/DoctorFormModal.vue';
import DoctorDetailModal from '@/components/master-data/doctors/DoctorDetailModal.vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';
import DoctorService from '@/services/master-data/doctors.service';

const {
  items,
  pagination,
  filters,
  sort,
  loading,
  error,
  fetchDoctors,
  deleteDoctor,
  setPage,
  setSort
} = useDoctors();

const isEditOpen = ref(false);
const isDetailOpen = ref(false);
const deleteDialogOpen = ref(false);
const formErrors = ref({});
const formMode = ref('create');
const selectedDoctorId = ref(null);
const doctorDetail = ref(null);
const detailLoading = ref(false);

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

const openCreateDoctor = () => {
  formMode.value = 'create';
  selectedDoctorId.value = null;
  doctorDetail.value = null;
  formErrors.value = {};
  isEditOpen.value = true;
};

const openDoctorDetail = async (row) => {
  selectedDoctorId.value = row.id;
  isDetailOpen.value = true;
  detailLoading.value = true;
  
  try {
    const response = await DoctorService.getDoctor(row.id);
    const detail = response.data?.data || response.data || response;

    if (!detail?.id) {
      throw new Error('Detail Dokter tidak valid');
    }

    doctorDetail.value = detail;
  } catch (err) {
    showToast({ type: 'error', title: 'Error', message: 'Gagal memuat detail Dokter.' });
    console.error(err);
    isDetailOpen.value = false;
  } finally {
    detailLoading.value = false;
  }
};

const openEditDoctor = async (row) => {
  selectedDoctorId.value = row.id;
  detailLoading.value = true;
  formErrors.value = {};
  
  try {
    const response = await DoctorService.getDoctor(row.id);
    const detail = response.data?.data || response.data || response;

    if (!detail?.id) {
      throw new Error('Detail Dokter tidak valid');
    }

    doctorDetail.value = detail;
    formMode.value = 'edit';
    isEditOpen.value = true;
  } catch (err) {
    showToast({ type: 'error', title: 'Error', message: 'Gagal memuat data Dokter untuk diedit.' });
    console.error(err);
  } finally {
    detailLoading.value = false;
  }
};

const openEditFromDetail = () => {
  if (doctorDetail.value) {
    isDetailOpen.value = false;
    formMode.value = 'edit';
    isEditOpen.value = true;
  }
};

const closeDoctorForm = () => {
  isEditOpen.value = false;
};

const closeDoctorDetail = () => {
  isDetailOpen.value = false;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchDoctors();
};

const handleResetFilters = () => {
  filters.search = '';
  filters.specialization_id = null;
  pagination.current_page = 1;
  fetchDoctors();
};

const handleDoctorSaved = () => {
  isEditOpen.value = false;
  showToast({
    type: 'success',
    title: 'Berhasil',
    message: formMode.value === 'edit' ? 'Data dokter berhasil diperbarui.' : 'Data dokter berhasil ditambahkan.'
  });
  fetchDoctors();
};

const openDeleteDialog = (item) => {
  selectedDoctorId.value = item.id;
  doctorDetail.value = item;
  deleteDialogOpen.value = true;
};

const handleDeleteConfirm = async () => {
  const result = await deleteDoctor(selectedDoctorId.value);
  if (result.success) {
    deleteDialogOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Data dokter berhasil dihapus.'
    });
    // Adjust pagination if needed
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }
    fetchDoctors();
  } else {
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: 'Terjadi kesalahan saat menghapus data dokter.'
    });
    deleteDialogOpen.value = false;
  }
};

onMounted(() => {
  fetchDoctors();
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
      title="Dokter"
      subtitle="Kelola data dokter, spesialisasi, dan informasi profesional lainnya."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Data Dokter', active: false },
        { label: 'Dokter', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openCreateDoctor">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Dokter
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <DoctorFilters 
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @reset="handleResetFilters"
        @refresh="fetchDoctors"
      />
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error" 
        @retry="fetchDoctors" 
      />
      
      <template v-else>
        <MasterDataEmptyState 
          v-if="!loading && items.length === 0" 
          :is-search="!!filters.search || filters.specialization_id !== null"
        >
          <template #action v-if="!filters.search && filters.specialization_id === null">
            <button class="btn-primary" @click="openCreateDoctor">Tambah Dokter</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <DoctorTable 
            :items="items"
            :pagination="pagination"
            :sort="sort"
            :loading="loading"
            @sort="setSort"
            @view="openDoctorDetail"
            @edit="openEditDoctor"
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
    <DoctorFormModal 
      v-if="isEditOpen"
      :key="`${formMode}-${selectedDoctorId ?? 'new'}`"
      :open="isEditOpen"
      :mode="formMode"
      :initial-data="doctorDetail"
      @close="closeDoctorForm"
      @saved="handleDoctorSaved"
    />
    
    <DoctorDetailModal
      v-if="isDetailOpen"
      :open="isDetailOpen"
      :loading="detailLoading"
      :detail="doctorDetail"
      @close="closeDoctorDetail"
      @edit="openEditFromDetail"
    />
    
    <MasterDataDeleteDialog 
      :is-open="deleteDialogOpen"
      title="Hapus Dokter?"
      :item-name="doctorDetail ? doctorDetail.name : ''"
      warning-message="Data dokter yang dihapus mungkin tidak dapat dikembalikan. Lanjutkan?"
      :is-submitting="detailLoading"
      @close="deleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>
/* Inherits global styles or MasterData shared component styles */
</style>
