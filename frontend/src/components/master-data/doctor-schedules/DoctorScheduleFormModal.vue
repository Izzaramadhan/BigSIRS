<script setup>
import { ref, watch, computed } from 'vue';
import { usePolyclinics } from '@/composables/usePolyclinics';
import SearchableSelect from '@/components/common/SearchableSelect.vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';
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

const polyclinicOptions = computed(() => {
  return polyclinics.value.map(p => ({
    value: p.id,
    label: p.name
  }));
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
  { value: 1, label: 'Senin' },
  { value: 2, label: 'Selasa' },
  { value: 3, label: 'Rabu' },
  { value: 4, label: 'Kamis' },
  { value: 5, label: 'Jumat' },
  { value: 6, label: 'Sabtu' },
  { value: 7, label: 'Minggu' }
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
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="isEdit ? 'Edit Jadwal Dokter' : 'Tambah Jadwal Dokter'"
    :is-submitting="loading"
    size="md"
    @close="emit('close')"
    @submit="save"
  >
    <div class="modal-form-wrapper">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label required">Nama Dokter</label>
          <SearchableSelect
            v-model="formData.doctor_id"
            :options="doctorOptions"
            placeholder="Pilih Dokter"
            :has-error="!!errors?.doctor_id"
          />
          <div class="error-message" v-if="errors?.doctor_id">{{ errors.doctor_id[0] }}</div>
        </div>

        <div class="form-group">
          <label class="form-label required">Poliklinik</label>
          <SearchableSelect
            v-model="formData.polyclinic_id"
            :options="polyclinicOptions"
            placeholder="Pilih Poliklinik"
            :has-error="!!errors?.polyclinic_id"
          />
          <div class="error-message" v-if="errors?.polyclinic_id">{{ errors.polyclinic_id[0] }}</div>
        </div>

        <div class="form-group">
          <label class="form-label required">Hari Praktik</label>
          <SearchableSelect
            v-model="formData.day_of_week"
            :options="dayOptions"
            placeholder="Pilih Hari"
            :has-error="!!errors?.day_of_week"
          />
          <div class="error-message" v-if="errors?.day_of_week">{{ errors.day_of_week[0] }}</div>
        </div>

        <div class="form-row" style="display: flex; gap: 1rem;">
          <div class="form-group" style="flex: 1;">
            <label class="form-label required">Jam Mulai</label>
            <input 
              type="time" 
              v-model="formData.start_time" 
              class="form-input" 
              :class="{ 'has-error': !!errors?.start_time }"
            />
            <div class="error-message" v-if="errors?.start_time">{{ errors.start_time[0] }}</div>
          </div>
          
          <div class="form-group" style="flex: 1;">
            <label class="form-label required">Jam Selesai</label>
            <input 
              type="time" 
              v-model="formData.end_time" 
              class="form-input"
              :class="{ 'has-error': !!errors?.end_time }"
            />
            <div class="error-message" v-if="errors?.end_time">{{ errors.end_time[0] }}</div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label required">Status (Libur)</label>
          <select 
            v-model="formData.is_holiday" 
            class="form-select"
            :class="{ 'has-error': !!errors?.is_holiday }"
          >
            <option :value="false">Praktik</option>
            <option :value="true">Libur</option>
          </select>
          <div class="error-message" v-if="errors?.is_holiday">{{ errors.is_holiday[0] }}</div>
        </div>

        <div class="form-group">
          <label class="form-label">Kuota Online</label>
          <input 
            type="number" 
            v-model="formData.online_quota" 
            class="form-input" 
            min="0" 
            placeholder="Kosongkan jika tidak ada batas kuota"
            :class="{ 'has-error': !!errors?.online_quota }"
          />
          <div class="error-message" v-if="errors?.online_quota">{{ errors.online_quota[0] }}</div>
        </div>
      </div>
    </div>
  </MasterDataFormModal>
</template>
