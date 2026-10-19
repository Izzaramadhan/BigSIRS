<script setup>
import { ref } from 'vue';

defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  polyclinics: {
    type: Array,
    default: () => []
  },
  doctors: {
    type: Array,
    default: () => []
  },
  nextQueueNumber: {
    type: String,
    default: 'A-025'
  }
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
  patient_name: '',
  medical_record_number: '',
  nik: '',
  gender: 'L',
  age: 35,
  polyclinic: 'Poli Umum',
  doctor: 'dr. Budi Santoso, Sp.PD',
  guarantor_type: 'BPJS Kesehatan',
  guarantor_detail: 'JKN-Non PBI',
  notes: ''
});

const resetForm = () => {
  form.value = {
    patient_name: '',
    medical_record_number: '',
    nik: '',
    gender: 'L',
    age: 35,
    polyclinic: 'Poli Umum',
    doctor: 'dr. Budi Santoso, Sp.PD',
    guarantor_type: 'BPJS Kesehatan',
    guarantor_detail: 'JKN-Non PBI',
    notes: ''
  };
};

const handleSubmit = () => {
  if (!form.value.patient_name.trim()) return;

  emit('submit', { ...form.value });
  resetForm();
};
</script>

<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content modal-md" role="dialog" aria-modal="true" aria-labelledby="modal-add-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-icon-title">
          <div class="header-avatar-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <line x1="19" y1="8" x2="19" y2="14"></line>
              <line x1="22" y1="11" x2="16" y2="11"></line>
            </svg>
          </div>
          <div>
            <h3 id="modal-add-title">Tambah Admisi Rawat Jalan</h3>
            <p class="header-sub">Nomor Antrean Berikutnya: <strong class="text-primary font-mono">{{ nextQueueNumber }}</strong></p>
          </div>
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
        <form @submit.prevent="handleSubmit" class="add-admission-form">
          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label" for="add-patient-name">
                Nama Lengkap Pasien <span class="required-mark">*</span>
              </label>
              <input 
                id="add-patient-name" 
                v-model="form.patient_name" 
                type="text" 
                class="form-input" 
                placeholder="Contoh: Raditya Pratama" 
                required 
              />
            </div>

            <div class="form-group">
              <label class="form-label" for="add-patient-rm">
                Nomor Rekam Medis (RM)
              </label>
              <input 
                id="add-patient-rm" 
                v-model="form.medical_record_number" 
                type="text" 
                class="form-input font-mono" 
                placeholder="Otomatis / Contoh: RM-000280" 
              />
            </div>
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label class="form-label" for="add-patient-nik">NIK Pasien</label>
              <input 
                id="add-patient-nik" 
                v-model="form.nik" 
                type="text" 
                class="form-input font-mono" 
                placeholder="3471xxxxxxxxxxxx" 
              />
            </div>

            <div class="form-group">
              <label class="form-label" for="add-patient-gender">Jenis Kelamin</label>
              <select id="add-patient-gender" v-model="form.gender" class="form-select">
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="add-patient-age">Usia (Th)</label>
              <input 
                id="add-patient-age" 
                v-model.number="form.age" 
                type="number" 
                class="form-input" 
                min="0" 
                max="120" 
              />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label" for="add-poly">
                Poliklinik Tujuan <span class="required-mark">*</span>
              </label>
              <select id="add-poly" v-model="form.polyclinic" class="form-select" required>
                <option v-for="p in polyclinics" :key="p" :value="p">{{ p }}</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="add-doctor">
                Dokter DPJP <span class="required-mark">*</span>
              </label>
              <select id="add-doctor" v-model="form.doctor" class="form-select" required>
                <option v-for="d in doctors" :key="d" :value="d">{{ d }}</option>
              </select>
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label" for="add-guarantor">Jenis Penjamin</label>
              <select id="add-guarantor" v-model="form.guarantor_type" class="form-select">
                <option value="BPJS Kesehatan">BPJS Kesehatan</option>
                <option value="Pasien Umum / Mandiri">Pasien Umum / Mandiri</option>
                <option value="Asuransi Swasta">Asuransi Swasta</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="add-guarantor-detail">Detail Penjamin</label>
              <input 
                id="add-guarantor-detail" 
                v-model="form.guarantor_detail" 
                type="text" 
                class="form-input" 
                placeholder="Contoh: JKN-Non PBI" 
              />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="add-notes">Catatan / Keluhan Pasien</label>
            <textarea 
              id="add-notes" 
              v-model="form.notes" 
              class="form-textarea" 
              rows="2" 
              placeholder="Keluhan pendaftaran pasien..."
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
          @click="handleSubmit"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>Daftarkan Admisi</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-md {
  max-width: 620px;
}

.header-icon-title {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.header-avatar-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-avatar-icon svg {
  width: 20px;
  height: 20px;
}

.header-sub {
  margin: 0.15rem 0 0 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.add-admission-form {
  display: flex;
  flex-direction: column;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-row-3 {
  display: grid;
  grid-template-columns: 1.5fr 1fr 0.8fr;
  gap: 1rem;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.text-primary {
  color: var(--color-primary);
}

@media (max-width: 640px) {
  .form-row-2,
  .form-row-3 {
    grid-template-columns: 1fr;
  }
}
</style>
