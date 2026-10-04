<script setup>
import { ref, onMounted } from 'vue';
import { useDoctorSchedules } from '@/composables/useDoctorSchedules';
import DoctorScheduleFormModal from '@/components/master-data/doctor-schedules/DoctorScheduleFormModal.vue';
import { useDoctors } from '@/composables/useDoctors';
import { usePolyclinics } from '@/composables/usePolyclinics';

const {
  items,
  pagination,
  filters,
  loading,
  submitting,
  error,
  fetchSchedules,
  createSchedule,
  updateSchedule,
  deleteSchedule,
  resetFilters
} = useDoctorSchedules();

const { fetchDoctors } = useDoctors();
const { fetchPolyclinics } = usePolyclinics();

const isModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const editingItem = ref(null);
const deletingItem = ref(null);
const formErrors = ref({});

onMounted(async () => {
  fetchSchedules();
  fetchDoctors({ per_page: 500 });
  fetchPolyclinics({ per_page: 500 });
});

const openAddModal = () => {
  editingItem.value = null;
  formErrors.value = {};
  isModalOpen.value = true;
};

const openEditModal = (item) => {
  editingItem.value = { ...item };
  formErrors.value = {};
  isModalOpen.value = true;
};

const confirmDelete = (item) => {
  if (confirm(`Apakah Anda yakin ingin menghapus jadwal ini?`)) {
    deletingItem.value = item;
    handleDelete();
  }
};

const handleSave = async (payload) => {
  formErrors.value = {};
  let res;
  if (editingItem.value) {
    res = await updateSchedule(editingItem.value.id, payload);
  } else {
    res = await createSchedule(payload);
  }

  if (res.success) {
    isModalOpen.value = false;
    fetchSchedules();
  } else {
    if (res.error?.response?.status === 422) {
      formErrors.value = res.error.response.data.errors;
    } else {
      alert(res.error?.response?.data?.message || 'Terjadi kesalahan.');
    }
  }
};

const handleDelete = async () => {
  if (!deletingItem.value) return;
  const res = await deleteSchedule(deletingItem.value.id);
  if (res.success) {
    isDeleteDialogOpen.value = false;
    deletingItem.value = null;
    fetchSchedules();
  } else {
    alert(res.error?.response?.data?.message || 'Gagal menghapus data.');
  }
};

const handlePageChange = (page) => {
  pagination.current_page = page;
  fetchSchedules();
};

const getDayName = (day) => {
  const map = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat', 6: 'Sabtu', 7: 'Minggu' };
  return map[day] || '-';
};
</script>

<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Data Jadwal Dokter</h1>
        <p class="page-subtitle">Manajemen Master Data</p>
      </div>
      <button @click="openAddModal" class="btn btn-primary">
        <i class="icon-plus">+</i> Tambah
      </button>
    </div>

    <div class="card">
      <div class="filters">
        <div class="filter-group">
          <label>Pencarian</label>
          <input 
            type="text" 
            v-model="filters.search" 
            placeholder="Cari Dokter/Poliklinik"
            class="form-control"
            @keyup.enter="fetchSchedules"
          />
        </div>
        
        <div class="filter-group">
          <label>Hari</label>
          <select v-model="filters.day_of_week" class="form-control" @change="fetchSchedules">
            <option :value="null">Semua Hari</option>
            <option value="1">Senin</option>
            <option value="2">Selasa</option>
            <option value="3">Rabu</option>
            <option value="4">Kamis</option>
            <option value="5">Jumat</option>
            <option value="6">Sabtu</option>
            <option value="7">Minggu</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Libur</label>
          <select v-model="filters.is_holiday" class="form-control" @change="fetchSchedules">
            <option :value="null">Semua</option>
            <option value="1">Ya</option>
            <option value="0">Tidak</option>
          </select>
        </div>

        <div class="filter-actions">
          <button @click="fetchSchedules" class="btn btn-secondary">Cari</button>
          <button @click="resetFilters" class="btn btn-outline">Reset</button>
        </div>
      </div>

      <div v-if="error" class="alert alert-danger">
        {{ error }}
        <button @click="fetchSchedules" class="btn btn-sm btn-outline ml-2">Coba Lagi</button>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th width="5%">No</th>
              <th>Nama Dokter</th>
              <th>Hari Praktik</th>
              <th>Jam Mulai</th>
              <th>Jam Selesai</th>
              <th>Poliklinik</th>
              <th>Libur</th>
              <th width="10%">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center py-4">Memuat data...</td>
            </tr>
            <tr v-else-if="items.length === 0">
              <td colspan="8" class="text-center py-4 text-muted">Tidak ada data jadwal dokter.</td>
            </tr>
            <tr v-else v-for="(item, index) in items" :key="item.id">
              <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
              <td>{{ item.doctor?.name || '-' }}</td>
              <td>{{ getDayName(item.day_of_week) }}</td>
              <td>{{ item.start_time }}</td>
              <td>{{ item.end_time }}</td>
              <td>{{ item.polyclinic?.name || '-' }}</td>
              <td>
                <span class="badge" :class="item.is_holiday ? 'badge-danger' : 'badge-success'">
                  {{ item.is_holiday ? 'Ya' : 'Tidak' }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button @click="openEditModal(item)" class="btn-icon text-primary" title="Edit">
                    ✏️
                  </button>
                  <button @click="confirmDelete(item)" class="btn-icon text-danger" title="Hapus">
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination-container" v-if="pagination.total > 0">
        <div class="pagination-info">
          Menampilkan {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} - 
          {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
          dari {{ pagination.total }} data
        </div>
        
        <div class="pagination-controls">
          <button 
            class="page-btn" 
            :disabled="pagination.current_page <= 1"
            @click="handlePageChange(pagination.current_page - 1)"
          >
            &laquo;
          </button>
          
          <button 
            v-for="p in pagination.last_page" 
            :key="p"
            class="page-btn"
            :class="{ active: p === pagination.current_page }"
            @click="handlePageChange(p)"
          >
            {{ p }}
          </button>
          
          <button 
            class="page-btn" 
            :disabled="pagination.current_page >= pagination.last_page"
            @click="handlePageChange(pagination.current_page + 1)"
          >
            &raquo;
          </button>
        </div>
      </div>
    </div>

    <DoctorScheduleFormModal
      :is-open="isModalOpen"
      :edit-data="editingItem"
      :loading="submitting"
      :errors="formErrors"
      @close="isModalOpen = false"
      @save="handleSave"
    />

  </div>
</template>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 0.25rem 0;
}

.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.875rem;
}

.card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  padding: 1.5rem;
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 200px;
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #475569;
}

.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  font-size: 0.875rem;
}

.filter-actions {
  display: flex;
  gap: 0.5rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  font-size: 0.875rem;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-secondary {
  background-color: #64748b;
  color: white;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #cbd5e1;
  color: #475569;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.table-responsive {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th {
  background-color: #f8fafc;
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: #475569;
  font-size: 0.875rem;
  border-bottom: 2px solid #e2e8f0;
}

.table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  color: #1e293b;
  font-size: 0.875rem;
}

.badge {
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-success {
  background-color: #dcfce7;
  color: #166534;
}

.badge-danger {
  background-color: #fee2e2;
  color: #991b1b;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  opacity: 0.7;
}

.btn-icon:hover {
  opacity: 1;
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.pagination-info {
  color: #64748b;
  font-size: 0.875rem;
}

.pagination-controls {
  display: flex;
  gap: 0.25rem;
}

.page-btn {
  padding: 0.25rem 0.75rem;
  border: 1px solid #cbd5e1;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  color: #475569;
}

.page-btn.active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.text-center { text-align: center; }
.text-muted { color: #94a3b8; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.ml-2 { margin-left: 0.5rem; }
</style>
