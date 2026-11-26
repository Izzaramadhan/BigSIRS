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
  },
  guarantors: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['close', 'save']);

const {
  guarantors: localGuarantors,
  loading: guarantorsLoading,
  error: guarantorsError,
  isEmpty: guarantorsEmpty,
  fetchGuarantors
} = useGuarantorOptions();

const savedGuarantorContext = ref({ id: null, name: '' });

const formData = ref({
  polyclinic: '',
  doctor: '',
  guarantor_id: '',
  guarantor_type: '',
  guarantor_detail: '',
  notes: ''
});

const effectiveMasterGuarantors = computed(() =>
  props.guarantors.length > 0 ? props.guarantors : localGuarantors.value
);

const guarantorOptions = computed(() =>
  mergeSavedGuarantorOption(effectiveMasterGuarantors.value, savedGuarantorContext.value)
);

const selectedGuarantorOption = computed(() =>
  guarantorOptions.value.find((g) => String(g.id) === String(formData.value.guarantor_id)) || null
);

const isSelfPaySelected = computed(() => {
  if (selectedGuarantorOption.value) {
    return selectedGuarantorOption.value.is_self_pay;
  }
  return isSelfPayGuarantor(formData.value.guarantor_type);
});

const syncSelectedGuarantor = () => {
  const matched = findMatchingGuarantor(guarantorOptions.value, savedGuarantorContext.value);
  if (matched) {
    formData.value.guarantor_id = matched.id;
    formData.value.guarantor_type = matched.name;
    if (matched.is_self_pay) {
      formData.value.guarantor_detail = 'Mandiri / Pribadi';
    }
  }
};

watch(
  () => [props.isOpen, props.admission],
  async ([open, newVal]) => {
    if (newVal) {
      const normalizedName =
        newVal.guarantor_type === 'Pasien Umum / Mandiri' ? 'UMUM' : (newVal.guarantor_type || 'UMUM');
      savedGuarantorContext.value = {
        id: newVal.guarantor_id ?? null,
        name: normalizedName
      };
      formData.value = {
        polyclinic: newVal.polyclinic || '',
        doctor: newVal.doctor || '',
        guarantor_id: newVal.guarantor_id ? String(newVal.guarantor_id) : '',
        guarantor_type: normalizedName,
        guarantor_detail: newVal.guarantor_detail || 'Mandiri / Pribadi',
        notes: newVal.notes || ''
      };
      syncSelectedGuarantor();
    }
    if (open && props.guarantors.length === 0) {
      await fetchGuarantors(newVal?.guarantor_id ? { ids: newVal.guarantor_id } : {});
      syncSelectedGuarantor();
    }
  },
  { immediate: true }
);

watch(
  () => formData.value.guarantor_id,
  () => {
    if (selectedGuarantorOption.value) {
      formData.value.guarantor_type = selectedGuarantorOption.value.name;
      if (selectedGuarantorOption.value.is_self_pay) {
        formData.value.guarantor_detail = 'Mandiri / Pribadi';
      }
    }
  }
);

const handleSave = () => {
  const resolvedName = selectedGuarantorOption.value?.name || formData.value.guarantor_type;
  emit('save', {
    id: props.admission.id,
    payload: {
      ...formData.value,
      guarantor_id: selectedGuarantorOption.value?.numericId ?? (formData.value.guarantor_id || null),
      guarantor_type: resolvedName,
      guarantor_detail: isSelfPaySelected.value ? 'Mandiri / Pribadi' : formData.value.guarantor_detail
    }
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
        <form autocomplete="off" @submit.prevent="handleSave" class="edit-form">
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
              <select
                id="edit-guarantor"
                v-model="formData.guarantor_id"
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
              <label class="form-label" for="edit-detail">Detail Penjamin</label>
              <input autocomplete="off"
                id="edit-detail"
                v-model="formData.guarantor_detail"
                type="text"
                class="form-input"
                :disabled="isSelfPaySelected"
                :placeholder="isSelfPaySelected ? 'Mandiri / Pribadi' : 'Contoh: JKN-Non PBI / Mandiri Inhealth'"
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
  .form-row-2 {
    grid-template-columns: 1fr;
  }
}
</style>
