<script setup>
import { ref, watch, computed } from 'vue';
import { usePolyclinics } from '@/composables/usePolyclinics';
import SearchableSelect from '@/components/common/SearchableSelect.vue';
import lookupService from '@/services/lookup.service';

const props = defineProps({
  isOpen: Boolean,
  editData: Object,
  loading: Boolean,
  errors: Object
});

const emit = defineEmits(['close', 'save']);

const { items: polyclinics, fetchPolyclinics } = usePolyclinics();
const rawDoctors = ref([]);
const doctorOptions = computed(() => {
  return rawDoctors.value
    .map(doctor => {
      const name = doctor.name;
      const specialization = doctor.specialization_name ?? doctor.specialization?.name ?? '';
      
      if (!doctor.id || !name) return null;
      
      return {
        value: Number(doctor.id),
        label: specialization ? `${name} — ${specialization}` : name,
        name,
        specialization,
        isActive: doctor.is_active === true || doctor.is_active === 1
      };
    })
    .filter(Boolean);
});

const formData = ref({
  doctor_id: '',
  polyclinic_id: '',
  day_of_week: '',
  start_time: '',
  end_time: '',
  is_holiday: false,
  online_quota: ''
});

const dayOptions = [
  { id: 1, name: 'Senin' },
  { id: 2, name: 'Selasa' },
  { id: 3, name: 'Rabu' },
  { id: 4, name: 'Kamis' },
  { id: 5, name: 'Jumat' },
  { id: 6, name: 'Sabtu' },
  { id: 7, name: 'Minggu' }
];

const isEdit = computed(() => !!props.editData);

watch(() => props.isOpen, async (newVal) => {
  if (newVal) {
    const needsFetch = rawDoctors.value.length === 0 || 
      (props.editData?.doctor_id && !rawDoctors.value.find(d => d.id === props.editData.doctor_id));

    if (needsFetch) {
      try {
        const params = { per_page: 500, is_active: 1 };
        if (props.editData?.doctor_id) {
          params.ids = props.editData.doctor_id;
        }
        const response = await lookupService.getDoctors(params);
        rawDoctors.value = response.data || response;
      } catch (err) {
        console.error('Failed to fetch doctors', err);
      }
    }
    if (polyclinics.value.length === 0) await fetchPolyclinics({ per_page: 500, is_active: 1 });

    if (props.editData) {
      formData.value = {
        doctor_id: props.editData.doctor_id || '',
        polyclinic_id: props.editData.polyclinic_id || '',
        day_of_week: props.editData.day_of_week || '',
        start_time: props.editData.start_time || '',
        end_time: props.editData.end_time || '',
        is_holiday: props.editData.is_holiday ?? false,
        online_quota: props.editData.online_quota !== null ? props.editData.online_quota : ''
      };
      
      // Load inactive options if needed, but for simplicity assuming the previously selected exists or will be loaded via a more robust search
      // In a real app we might append the selected doctor/polyclinic if it's inactive and not in the list.
    } else {
      formData.value = {
        doctor_id: '',
        polyclinic_id: '',
        day_of_week: '',
        start_time: '',
        end_time: '',
        is_holiday: false,
        online_quota: ''
      };
    }
  }
}, { immediate: true });

const save = () => {
  const payload = { ...formData.value };
  if (payload.online_quota === '') payload.online_quota = null;
  emit('save', payload);
};

const polyclinicOptions = computed(() => {
  return polyclinics.value.map(p => ({
    id: p.id,
    name: p.name
  }));
});

</script>

<template>
  <div v-if="isOpen" class="modal-backdrop">
    <div class="modal-container">
      <div class="modal-header">
        <h3 class="modal-title">{{ isEdit ? 'Edit Jadwal Dokter' : 'Tambah Jadwal Dokter' }}</h3>
        <button @click="$emit('close')" class="modal-close" :disabled="loading">&times;</button>
      </div>
      
      <div class="modal-body">
        <form @submit.prevent="save" class="form-grid">
          <div class="form-group">
            <label>Nama Dokter <span class="required">*</span></label>
            <SearchableSelect
              v-model="formData.doctor_id"
              :options="doctorOptions"
              placeholder="Pilih Dokter"
            />
            <span class="error-msg" v-if="errors?.doctor_id">{{ errors.doctor_id[0] }}</span>
          </div>

          <div class="form-group">
            <label>Poliklinik <span class="required">*</span></label>
            <SearchableSelect
              v-model="formData.polyclinic_id"
              :options="polyclinicOptions"
              placeholder="Pilih Poliklinik"
            />
            <span class="error-msg" v-if="errors?.polyclinic_id">{{ errors.polyclinic_id[0] }}</span>
          </div>

          <div class="form-group">
            <label>Hari Praktik <span class="required">*</span></label>
            <SearchableSelect
              v-model="formData.day_of_week"
              :options="dayOptions"
              placeholder="Pilih Hari"
            />
            <span class="error-msg" v-if="errors?.day_of_week">{{ errors.day_of_week[0] }}</span>
          </div>

          <div class="form-row">
            <div class="form-group w-50 pr-2">
              <label>Jam Mulai <span class="required">*</span></label>
              <input type="time" v-model="formData.start_time" class="form-control" />
              <span class="error-msg" v-if="errors?.start_time">{{ errors.start_time[0] }}</span>
            </div>
            
            <div class="form-group w-50 pl-2">
              <label>Jam Selesai <span class="required">*</span></label>
              <input type="time" v-model="formData.end_time" class="form-control" />
              <span class="error-msg" v-if="errors?.end_time">{{ errors.end_time[0] }}</span>
            </div>
          </div>

          <div class="form-group">
            <label>Libur</label>
            <select v-model="formData.is_holiday" class="form-control">
              <option :value="false">Tidak</option>
              <option :value="true">Ya</option>
            </select>
            <span class="error-msg" v-if="errors?.is_holiday">{{ errors.is_holiday[0] }}</span>
          </div>

          <div class="form-group">
            <label>Kuota Online</label>
            <input type="number" v-model="formData.online_quota" class="form-control" min="0" />
            <span class="error-msg" v-if="errors?.online_quota">{{ errors.online_quota[0] }}</span>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="$emit('close')" :disabled="loading">Batal</button>
        <button type="button" class="btn btn-primary" @click="save" :disabled="loading">
          {{ loading ? 'Menyimpan...' : (isEdit ? 'Update Jadwal' : 'Simpan Jadwal') }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-container {
  background: white;
  border-radius: 8px;
  width: 500px;
  max-width: 90vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.modal-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
}
.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748b;
}
.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}
.form-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.form-row {
  display: flex;
  width: 100%;
}
.w-50 {
  width: 50%;
}
.pr-2 {
  padding-right: 0.5rem;
}
.pl-2 {
  padding-left: 0.5rem;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.form-group label {
  font-weight: 500;
  color: #475569;
  font-size: 0.875rem;
}
.required {
  color: #ef4444;
}
.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  font-size: 1rem;
}
.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
}
.error-msg {
  color: #ef4444;
  font-size: 0.75rem;
}
.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}
.btn {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  border: none;
}
.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.btn-secondary {
  background-color: #f1f5f9;
  color: #475569;
}
.btn-secondary:hover:not(:disabled) {
  background-color: #e2e8f0;
}
.btn-primary {
  background-color: #3b82f6;
  color: white;
}
.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}
</style>
