<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  admission: {
    type: Object,
    default: () => null
  },
  polyclinics: {
    type: Array,
    default: () => []
  },
  doctors: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['close', 'save']);

const formData = ref({
  polyclinic: '',
  doctor: '',
  guarantor_type: '',
  guarantor_detail: '',
  notes: ''
});

watch(
  () => props.admission,
  (newVal) => {
    if (newVal) {
      formData.value = {
        polyclinic: newVal.polyclinic || '',
        doctor: newVal.doctor || '',
        guarantor_type: newVal.guarantor_type || 'BPJS Kesehatan',
        guarantor_detail: newVal.guarantor_detail || 'JKN-Non PBI',
        notes: newVal.notes || ''
      };
    }
  },
  { immediate: true }
);

const handleSave = () => {
  emit('save', {
    id: props.admission.id,
    payload: { ...formData.value }
  });
};
</script>

<template>
  <div v-if="isOpen && admission" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content modal-md" role="dialog" aria-modal="true" aria-labelledby="modal-edit-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div>
          <h3 id="modal-edit-title">Edit Data Admisi Rawat Jalan</h3>
          <p class="header-sub">
            {{ admission.patient_name }} &bull; {{ admission.medical_record_number }} (Antrean: {{ admission.queue_number }})
          </p>
        </div>

        <button 
          type="button" 
          class="btn-close" 
          @click="emit('close')" 
          aria-label="Tutup modal"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <form @submit.prevent="handleSave" class="edit-form">
          <div class="form-group">
            <label class="form-label" for="edit-poly">Poliklinik Tujuan</label>
            <select id="edit-poly" v-model="formData.polyclinic" class="form-select" required>
              <option v-for="p in polyclinics" :key="p" :value="p">{{ p }}</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="edit-doctor">Dokter Spesialis</label>
            <select id="edit-doctor" v-model="formData.doctor" class="form-select" required>
              <option v-for="d in doctors" :key="d" :value="d">{{ d }}</option>
            </select>
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label" for="edit-guarantor">Jenis Penjamin</label>
              <select id="edit-guarantor" v-model="formData.guarantor_type" class="form-select">
                <option value="BPJS Kesehatan">BPJS Kesehatan</option>
                <option value="Pasien Umum / Mandiri">Pasien Umum / Mandiri</option>
                <option value="Asuransi Swasta">Asuransi Swasta</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="edit-detail">Detail Penjamin</label>
              <input 
                id="edit-detail" 
                v-model="formData.guarantor_detail" 
                type="text" 
                class="form-input" 
                placeholder="Contoh: JKN-Non PBI / Mandiri Inhealth" 
              />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="edit-notes">Catatan Tambahan Admisi</label>
            <textarea 
              id="edit-notes" 
              v-model="formData.notes" 
              class="form-textarea" 
              rows="3" 
              placeholder="Masukkan keluhan atau catatan pendaftaran..."
            ></textarea>
          </div>
        </form>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button 
          type="button" 
          class="btn-cancel" 
          @click="emit('close')"
        >
          Batal
        </button>

        <button 
          type="button" 
          class="btn-primary" 
          @click="handleSave"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>Simpan Perubahan</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-md {
  max-width: 580px;
}

.header-sub {
  margin: 0.15rem 0 0 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.edit-form {
  display: flex;
  flex-direction: column;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

@media (max-width: 640px) {
  .form-row-2 {
    grid-template-columns: 1fr;
  }
}
</style>
