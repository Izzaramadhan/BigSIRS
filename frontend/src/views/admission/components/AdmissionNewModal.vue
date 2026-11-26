<script setup>
import { computed, ref, watch } from 'vue';
import {
  findMatchingGuarantor,
  isSelfPayGuarantor,
  mergeSavedGuarantorOption,
  useGuarantorOptions
} from '@/composables/useGuarantorOptions';

const props = defineProps({
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
  guarantors: {
    type: Array,
    default: () => []
  },
  nextQueueNumber: {
    type: String,
    default: 'A-025'
  },
  patient: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'submit']);

const {
  guarantors: localGuarantors,
  loading: guarantorsLoading,
  error: guarantorsError,
  isEmpty: guarantorsEmpty,
  fetchGuarantors
} = useGuarantorOptions();

const savedGuarantorContext = ref({ id: null, name: 'UMUM' });

const calcAge = (birthDate) => {
  if (!birthDate) return 35;
  const birth = new Date(birthDate);
  if (Number.isNaN(birth.getTime())) return 35;
  const now = new Date();
  let age = now.getFullYear() - birth.getFullYear();
  const m = now.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && now.getDate() < birth.getDate())) age--;
  return Math.max(0, age);
};

const normalizeGuarantorName = (val) => {
  if (!val) return 'UMUM';
  if (isSelfPayGuarantor(val)) return 'UMUM';
  return String(val).trim();
};

const form = ref({
  patient_id: null,
  patient_name: '',
  medical_record_number: '',
  nik: '',
  gender: 'L',
  birth_date: '1990-01-01',
  age: 35,
  phone: '',
  address: '',
  polyclinic: 'Poli Umum',
  doctor: 'dr. Budi Santoso, Sp.PD',
  guarantor_id: '',
  guarantor_type: 'UMUM',
  guarantor_detail: 'Mandiri / Pribadi',
  notes: ''
});

const effectiveMasterGuarantors = computed(() =>
  props.guarantors.length > 0 ? props.guarantors : localGuarantors.value
);

const guarantorOptions = computed(() =>
  mergeSavedGuarantorOption(effectiveMasterGuarantors.value, savedGuarantorContext.value)
);

const selectedGuarantorOption = computed(() =>
  guarantorOptions.value.find((g) => String(g.id) === String(form.value.guarantor_id)) || null
);

const isSelfPaySelected = computed(() => {
  if (selectedGuarantorOption.value) {
    return selectedGuarantorOption.value.is_self_pay;
  }
  return isSelfPayGuarantor(form.value.guarantor_type);
});

const syncSelectedGuarantor = () => {
  const matched = findMatchingGuarantor(guarantorOptions.value, savedGuarantorContext.value);
  if (matched) {
    form.value.guarantor_id = matched.id;
    form.value.guarantor_type = matched.name;
    if (matched.is_self_pay) {
      form.value.guarantor_detail = 'Mandiri / Pribadi';
    }
  } else if (!form.value.guarantor_id && guarantorOptions.value.length > 0) {
    form.value.guarantor_id = guarantorOptions.value[0].id;
    form.value.guarantor_type = guarantorOptions.value[0].name;
  }
};

const resetForm = () => {
  savedGuarantorContext.value = { id: null, name: 'UMUM' };
  form.value = {
    patient_id: null,
    patient_name: '',
    medical_record_number: '',
    nik: '',
    gender: 'L',
    birth_date: '1990-01-01',
    age: 35,
    phone: '',
    address: '',
    polyclinic: 'Poli Umum',
    doctor: 'dr. Budi Santoso, Sp.PD',
    guarantor_id: '',
    guarantor_type: 'UMUM',
    guarantor_detail: 'Mandiri / Pribadi',
    notes: ''
  };
  syncSelectedGuarantor();
};

watch(
  () => [props.isOpen, props.patient],
  async ([open, pat]) => {
    if (!open) return;
    if (pat) {
      const mappedName = normalizeGuarantorName(
        typeof pat.guarantor === 'object' ? pat.guarantor?.name : pat.guarantor
      );
      const patientGuarantorId =
        pat.guarantor_id ?? (typeof pat.guarantor === 'object' ? pat.guarantor?.id : null);
      savedGuarantorContext.value = {
        id: patientGuarantorId ?? null,
        name: mappedName
      };
      form.value = {
        patient_id: pat.id || pat.patient_id || null,
        patient_name: pat.name || '',
        medical_record_number: pat.medical_record_number || '',
        nik: pat.nik || '',
        gender: pat.gender || 'L',
        birth_date: pat.birth_date || '1990-01-01',
        age: calcAge(pat.birth_date),
        phone: pat.phone || '',
        address: pat.address || '',
        polyclinic: props.polyclinics[0] || 'Poli Umum',
        doctor: props.doctors[0] || 'dr. Budi Santoso, Sp.PD',
        guarantor_id: patientGuarantorId ? String(patientGuarantorId) : '',
        guarantor_type: mappedName,
        guarantor_detail:
          isSelfPayGuarantor(mappedName)
            ? 'Mandiri / Pribadi'
            : (pat.insurance_number && pat.insurance_number !== '-' ? pat.insurance_number : 'JKN-Non PBI'),
        notes: ''
      };
      syncSelectedGuarantor();
    } else {
      resetForm();
    }

    if (props.guarantors.length === 0) {
      await fetchGuarantors(savedGuarantorContext.value.id ? { ids: savedGuarantorContext.value.id } : {});
      syncSelectedGuarantor();
    }
  },
  { immediate: true }
);

watch(
  () => form.value.guarantor_id,
  () => {
    if (selectedGuarantorOption.value) {
      form.value.guarantor_type = selectedGuarantorOption.value.name;
      if (selectedGuarantorOption.value.is_self_pay) {
        form.value.guarantor_detail = 'Mandiri / Pribadi';
      }
    }
  }
);

const handleSubmit = () => {
  if (!form.value.patient_name.trim()) return;

  const resolvedName = selectedGuarantorOption.value?.name || form.value.guarantor_type;
  emit('submit', {
    ...form.value,
    guarantor_id: selectedGuarantorOption.value?.numericId ?? (form.value.guarantor_id || null),
    guarantor_type: resolvedName,
    guarantor_detail: isSelfPaySelected.value ? 'Mandiri / Pribadi' : form.value.guarantor_detail
  });
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
        <form autocomplete="off" @submit.prevent="handleSubmit" class="add-admission-form">
          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label" for="add-patient-name">
                Nama Lengkap Pasien <span class="required-mark">*</span>
              </label>
              <input autocomplete="off" 
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
              <input autocomplete="off" 
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
              <input autocomplete="off" 
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
              <input autocomplete="off" 
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
              <select
                id="add-guarantor"
                v-model="form.guarantor_id"
                class="form-select"
                :disabled="guarantorsLoading && guarantorOptions.length === 0"
              >
                <option v-if="guarantorOptions.length === 0" value="">
                  {{ guarantorsLoading ? 'Memuat daftar penjamin...' : '-- Pilih Penjamin --' }}
                </option>
                <option v-for="g in guarantorOptions" :key="g.id" :value="g.id">
                  {{ g.label }}
                </option>
              </select>
              <small v-if="guarantorsLoading" class="guarantor-helper-muted">Memuat data penjamin...</small>
              <small v-else-if="guarantorsError" class="guarantor-helper-error">
                {{ guarantorsError }}
                <button type="button" class="btn-retry-guarantor" @click="fetchGuarantors()">Coba Lagi</button>
              </small>
              <small v-else-if="guarantorsEmpty && guarantorOptions.length === 0" class="guarantor-helper-muted">
                Belum ada data penjamin aktif.
              </small>
            </div>

            <div class="form-group">
              <label class="form-label" for="add-guarantor-detail">Detail Penjamin</label>
              <input autocomplete="off"
                id="add-guarantor-detail"
                v-model="form.guarantor_detail"
                type="text"
                class="form-input"
                :disabled="isSelfPaySelected"
                :placeholder="isSelfPaySelected ? 'Mandiri / Pribadi' : 'Contoh: JKN-Non PBI'"
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

.guarantor-helper-muted {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.guarantor-helper-error {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: var(--color-danger);
}

.btn-retry-guarantor {
  border: 1px solid currentColor;
  background: transparent;
  color: inherit;
  border-radius: 4px;
  padding: 0.08rem 0.4rem;
  font-size: 0.72rem;
  font-weight: 600;
  cursor: pointer;
}

@media (max-width: 640px) {
  .form-row-2,
  .form-row-3 {
    grid-template-columns: 1fr;
  }
}
</style>
