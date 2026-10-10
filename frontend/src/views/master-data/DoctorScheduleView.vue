<script setup>
import { ref, onMounted } from 'vue';
import { useDoctorSchedules } from '@/composables/useDoctorSchedules';
import DoctorScheduleTable from '@/components/master-data/doctor-schedules/DoctorScheduleTable.vue';
import DoctorScheduleFilters from '@/components/master-data/doctor-schedules/DoctorScheduleFilters.vue';
import DoctorScheduleFormModal from '@/components/master-data/doctor-schedules/DoctorScheduleFormModal.vue';
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
  fetchSchedules,
  createSchedule,
  updateSchedule,
  deleteSchedule
} = useDoctorSchedules();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedSchedule = ref(null);
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

const setPage = (page) => {
  pagination.current_page = page;
  fetchSchedules();
};

const setSort = ({ column, direction }) => {
  sort.column = column;
  sort.direction = direction;
  fetchSchedules();
};

const openAddModal = () => {
  selectedSchedule.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedSchedule.value = item;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openDeleteDialog = (item) => {
  selectedSchedule.value = item;
  deleteDialogOpen.value = true;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchSchedules();
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};
  
  const isEditing = Boolean(selectedSchedule.value);
  let result;
  
  if (isEditing) {
    result = await updateSchedule(selectedSchedule.value.id, payload);
  } else {
    result = await createSchedule(payload);
  }
  
  if (result.success) {
    formModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing 
        ? 'Jadwal dokter berhasil diperbarui.' 
        : 'Jadwal dokter berhasil ditambahkan.'
    });
    fetchSchedules();
  } else {
    if (result.error?.response?.status === 422) {
      formErrors.value = result.error.response.data.errors || {};
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message: isEditing 
          ? 'Jadwal dokter gagal diperbarui. Silakan coba lagi.' 
          : 'Jadwal dokter gagal ditambahkan. Silakan periksa kembali data yang dimasukkan.'
      });
    } else {
      formErrors.value = { general: 'Terjadi kesalahan sistem. Silakan coba lagi.' };
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message: result.error?.response?.data?.message || 'Terjadi kesalahan sistem. Silakan coba lagi.'
      });
    }
  }
};

const getDayName = (day) => {
  const map = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat', 6: 'Sabtu', 7: 'Minggu' };
  return map[day] || '-';
};

const handleDeleteConfirm = async () => {
  const result = await deleteSchedule(selectedSchedule.value.id);
  
  if (result.success) {
    deleteDialogOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Jadwal dokter berhasil dihapus.'
    });
    
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }
    
    fetchSchedules();
  } else {
    let errorMessage = 'Jadwal dokter gagal dihapus. Silakan coba lagi.';
    
    if (result.error?.response?.data?.message) {
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
  fetchSchedules();
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
      title="Data Jadwal Dokter"
      subtitle="Manajemen jadwal operasional dokter berdasarkan poliklinik."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Data Dokter', active: false },
        { label: 'Jadwal Dokter', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Jadwal Dokter
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <DoctorScheduleFilters 
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchSchedules"
      />
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error" 
        @retry="fetchSchedules" 
      />
      
      <template v-else>
        <MasterDataEmptyState 
          v-if="!loading && items.length === 0" 
          :is-search="!!filters.search"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openAddModal">Tambah Jadwal Dokter</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <DoctorScheduleTable 
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
    <DoctorScheduleFormModal 
      :is-open="formModalOpen"
      :edit-data="selectedSchedule"
      :loading="submitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @save="handleFormSubmit"
    />
    
    <MasterDataDeleteDialog 
      :is-open="deleteDialogOpen"
      title="Hapus Jadwal Dokter?"
      :item-name="selectedSchedule ? `${selectedSchedule.doctor?.name} di ${selectedSchedule.polyclinic?.name} (${getDayName(selectedSchedule.day_of_week)}, ${selectedSchedule.start_time}-${selectedSchedule.end_time})` : ''"
      warning-message="Jadwal dokter ini akan dihapus secara permanen. Pastikan jadwal ini tidak sedang digunakan."
      :is-submitting="submitting"
      @close="deleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>
