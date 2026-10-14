<script setup>
import { reactive, watch } from 'vue';
import icd10Service from '@/services/master-data/icd10.service';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  code: {
    type: Object,
    default: null
  },
  isSubmitting: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'submit']);

const form = reactive({
  code: '',
  name: '',
  english_name: '',
  description: '',
  inacbg_code: '',
  inacbg_name: '',
  class_1_tariff: '',
  class_2_tariff: '',
  class_3_tariff: '',
  is_medical_history: false
});

const populateForm = (data) => {
  form.code = data?.code || '';
  form.name = data?.name || '';
  form.english_name = data?.english_name || '';
  form.description = data?.description || '';
  form.inacbg_code = data?.inacbg_code || '';
  form.inacbg_name = data?.inacbg_name || '';
  form.class_1_tariff = data?.class_1_tariff ?? '';
  form.class_2_tariff = data?.class_2_tariff ?? '';
  form.class_3_tariff = data?.class_3_tariff ?? '';
  form.is_medical_history = Boolean(data?.is_medical_history);
};

const resetForm = () => {
  form.code = '';
  form.name = '';
  form.english_name = '';
  form.description = '';
  form.inacbg_code = '';
  form.inacbg_name = '';
  form.class_1_tariff = '';
  form.class_2_tariff = '';
  form.class_3_tariff = '';
  form.is_medical_history = false;
};

watch(() => props.isOpen, async (isOpen) => {
  if (!isOpen) {
    resetForm();
    return;
  }

  if (props.code) {
    if (props.code.id) {
      try {
        const response = await icd10Service.getIcd10Code(props.code.id);
        populateForm(response?.data || props.code);
      } catch (err) {
        console.error('Failed to load fresh ICD-10 data:', err);
        populateForm(props.code);
      }
    } else {
      populateForm(props.code);
    }
  } else {
    resetForm();
  }
}, { immediate: true });

const parseTariff = (val) => {
  if (val === '' || val === null || val === undefined) return 0;
  const num = typeof val === 'string' ? Number(val.replace(/[^0-9.-]/g, '')) : Number(val);
  return isNaN(num) ? 0 : Math.max(0, num);
};

const handleSubmit = () => {
  const payload = {
    code: form.code.trim().toUpperCase(),
    name: form.name.trim(),
    english_name: form.english_name.trim() || null,
    description: form.description.trim() || null,
    inacbg_code: form.inacbg_code.trim() || null,
    inacbg_name: form.inacbg_name.trim() || null,
    class_1_tariff: parseTariff(form.class_1_tariff),
    class_2_tariff: parseTariff(form.class_2_tariff),
    class_3_tariff: parseTariff(form.class_3_tariff),
    is_medical_history: Boolean(form.is_medical_history)
  };

  emit('submit', payload);
};

const fieldError = (field) => {
  const err = props.errors?.[field];
  if (Array.isArray(err)) return err[0];
  if (typeof err === 'string') return err;
  return null;
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="code ? 'Edit ICD-10' : 'Tambah ICD-10'"
    :submit-text="code ? 'Perbarui' : 'Simpan'"
    :is-submitting="isSubmitting"
    size="lg"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
      <!-- General Error Alert -->
      <div v-if="errors.general" class="alert-error">
        {{ errors.general }}
      </div>

      <div class="form-grid">
        <!-- 1. Kode Diagnosa -->
        <div class="form-group">
          <label for="code" class="form-label required">Kode Diagnosa</label>
          <input
            id="code"
            v-model="form.code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('code') }"
            placeholder="Contoh: A00.0"
            maxlength="50"
            @input="form.code = form.code.toUpperCase()"
            required
          >
          <div v-if="fieldError('code')" class="invalid-feedback">{{ fieldError('code') }}</div>
        </div>

        <!-- 2. Nama Diagnosa -->
        <div class="form-group">
          <label for="name" class="form-label required">Nama Diagnosa</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Nama diagnosa (Bahasa Indonesia)"
            maxlength="255"
            required
          >
          <div v-if="fieldError('name')" class="invalid-feedback">{{ fieldError('name') }}</div>
        </div>

        <!-- 3. Nama Diagnosa (EN) -->
        <div class="form-group form-group--full">
          <label for="english_name" class="form-label">Nama Diagnosa (EN)</label>
          <input
            id="english_name"
            v-model="form.english_name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('english_name') }"
            placeholder="Nama diagnosa dalam bahasa Inggris (opsional)"
            maxlength="255"
          >
          <div v-if="fieldError('english_name')" class="invalid-feedback">{{ fieldError('english_name') }}</div>
        </div>

        <!-- 4. Deskripsi Diagnosa -->
        <div class="form-group form-group--full">
          <label for="description" class="form-label">Deskripsi Diagnosa</label>
          <textarea
            id="description"
            v-model="form.description"
            class="form-control form-textarea"
            :class="{ 'is-invalid': fieldError('description') }"
            placeholder="Keterangan atau deskripsi tambahan diagnosa (opsional)"
            rows="3"
          ></textarea>
          <div v-if="fieldError('description')" class="invalid-feedback">{{ fieldError('description') }}</div>
        </div>

        <!-- Section: Mapping INACBG -->
        <div class="form-group">
          <label for="inacbg_code" class="form-label">Kode INACBG</label>
          <input
            id="inacbg_code"
            v-model="form.inacbg_code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('inacbg_code') }"
            placeholder="Contoh: A-4-10-I"
            maxlength="100"
          >
          <div v-if="fieldError('inacbg_code')" class="invalid-feedback">{{ fieldError('inacbg_code') }}</div>
        </div>

        <!-- 6. Nama INACBG -->
        <div class="form-group">
          <label for="inacbg_name" class="form-label">Nama INACBG</label>
          <input
            id="inacbg_name"
            v-model="form.inacbg_name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('inacbg_name') }"
            placeholder="Deskripsi kelompok INACBG (opsional)"
          >
          <div v-if="fieldError('inacbg_name')" class="invalid-feedback">{{ fieldError('inacbg_name') }}</div>
        </div>

        <!-- Section: Tarif Kelas 1, 2, 3 -->
        <div class="tariff-section form-group--full">
          <div class="tariff-header">
            <span class="tariff-title">Tarif Kelas INACBG</span>
            <span class="tariff-subtitle">Nominal tarif per kelas perawatan (dalam Rupiah)</span>
          </div>
          <div class="tariff-inputs-grid">
            <!-- 7. Tarif Kelas 1 -->
            <div class="form-group">
              <label for="class_1_tariff" class="form-label">Tarif Kelas 1</label>
              <div class="input-currency-wrapper">
                <span class="currency-prefix">Rp</span>
                <input
                  id="class_1_tariff"
                  v-model="form.class_1_tariff"
                  type="number"
                  min="0"
                  step="1000"
                  class="form-control currency-input"
                  :class="{ 'is-invalid': fieldError('class_1_tariff') }"
                  placeholder="0"
                >
              </div>
              <div v-if="fieldError('class_1_tariff')" class="invalid-feedback">{{ fieldError('class_1_tariff') }}</div>
            </div>

            <!-- 8. Tarif Kelas 2 -->
            <div class="form-group">
              <label for="class_2_tariff" class="form-label">Tarif Kelas 2</label>
              <div class="input-currency-wrapper">
                <span class="currency-prefix">Rp</span>
                <input
                  id="class_2_tariff"
                  v-model="form.class_2_tariff"
                  type="number"
                  min="0"
                  step="1000"
                  class="form-control currency-input"
                  :class="{ 'is-invalid': fieldError('class_2_tariff') }"
                  placeholder="0"
                >
              </div>
              <div v-if="fieldError('class_2_tariff')" class="invalid-feedback">{{ fieldError('class_2_tariff') }}</div>
            </div>

            <!-- 9. Tarif Kelas 3 -->
            <div class="form-group">
              <label for="class_3_tariff" class="form-label">Tarif Kelas 3</label>
              <div class="input-currency-wrapper">
                <span class="currency-prefix">Rp</span>
                <input
                  id="class_3_tariff"
                  v-model="form.class_3_tariff"
                  type="number"
                  min="0"
                  step="1000"
                  class="form-control currency-input"
                  :class="{ 'is-invalid': fieldError('class_3_tariff') }"
                  placeholder="0"
                >
              </div>
              <div v-if="fieldError('class_3_tariff')" class="invalid-feedback">{{ fieldError('class_3_tariff') }}</div>
            </div>
          </div>
        </div>

        <!-- 10. Riwayat Penyakit -->
        <div class="form-group form-group--full">
          <div class="checkbox-row">
            <input
              id="is_medical_history"
              v-model="form.is_medical_history"
              type="checkbox"
              class="form-checkbox"
            >
            <label for="is_medical_history" class="checkbox-label">
              Tandai sebagai riwayat penyakit
            </label>
          </div>
          <div v-if="fieldError('is_medical_history')" class="invalid-feedback">{{ fieldError('is_medical_history') }}</div>
        </div>
      </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.modal-form {
  padding: 0.25rem 0;
}

.alert-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  margin-bottom: 1.25rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-group--full {
  grid-column: 1 / -1;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #17243D);
}

.form-label.required::after {
  content: ' *';
  color: var(--color-danger, #ef4444);
}

.form-control {
  width: 100%;
  padding: 0.55rem 0.75rem;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  font-size: 0.875rem;
  font-family: inherit;
  color: var(--color-text-navy, #17243D);
  background-color: #ffffff;
  transition: all 0.2s;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.12);
}

.form-control.is-invalid {
  border-color: var(--color-danger, #ef4444);
}

.form-control.is-invalid:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.form-textarea {
  resize: vertical;
  min-height: 70px;
}

.invalid-feedback {
  font-size: 0.75rem;
  color: var(--color-danger, #ef4444);
  margin-top: 0.125rem;
}

.tariff-section {
  background: var(--color-page-bg, #f8fafc);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 10px;
  padding: 1rem;
}

.tariff-header {
  margin-bottom: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.tariff-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243D);
}

.tariff-subtitle {
  font-size: 0.75rem;
  color: var(--color-text-secondary, #64748b);
}

.tariff-inputs-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.input-currency-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.currency-prefix {
  position: absolute;
  left: 0.75rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-text-secondary, #64748b);
  pointer-events: none;
}

.currency-input {
  padding-left: 2.25rem;
}

.checkbox-row {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  cursor: pointer;
  user-select: none;
  padding: 0.5rem 0;
}

.form-checkbox {
  width: 1.125rem;
  height: 1.125rem;
  accent-color: var(--color-primary, #087f78);
  cursor: pointer;
  border-radius: 4px;
}

.checkbox-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #17243D);
  cursor: pointer;
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .tariff-inputs-grid {
    grid-template-columns: 1fr;
  }
}
</style>
