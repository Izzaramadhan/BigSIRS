<script setup>
import { reactive, watch } from 'vue';
import icd9cmService from '@/services/master-data/icd9cm.service';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  item: {
    type: Object,
    default: null
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
  inacbg_code: '',
  inacbg_name: '',
  class_1_tariff: '',
  class_2_tariff: '',
  class_3_tariff: ''
});

const populateForm = (data) => {
  form.code = data?.code || '';
  form.name = data?.name || '';
  form.inacbg_code = data?.inacbg_code || '';
  form.inacbg_name = data?.inacbg_name || '';
  form.class_1_tariff = data?.class_1_tariff ?? '';
  form.class_2_tariff = data?.class_2_tariff ?? '';
  form.class_3_tariff = data?.class_3_tariff ?? '';
};

const resetForm = () => {
  form.code = '';
  form.name = '';
  form.inacbg_code = '';
  form.inacbg_name = '';
  form.class_1_tariff = '';
  form.class_2_tariff = '';
  form.class_3_tariff = '';
};

watch(() => props.isOpen, async (isOpen) => {
  if (!isOpen) {
    resetForm();
    return;
  }

  const targetItem = props.item || props.code;
  if (targetItem) {
    if (targetItem.id) {
      try {
        const response = await icd9cmService.getDetail(targetItem.id);
        populateForm(response?.data || targetItem);
      } catch (err) {
        console.error('Failed to load fresh ICD-9 data:', err);
        populateForm(targetItem);
      }
    } else {
      populateForm(targetItem);
    }
  } else {
    resetForm();
  }
}, { immediate: true });

const parseTariff = (val) => {
  if (val === '' || val === null || val === undefined) return null;
  const num = typeof val === 'string' ? Number(val.replace(/[^0-9.-]/g, '')) : Number(val);
  return isNaN(num) ? null : Math.max(0, num);
};

const handleSubmit = () => {
  const payload = {
    code: form.code.trim(),
    name: form.name.trim(),
    inacbg_code: form.inacbg_code.trim() || null,
    inacbg_name: form.inacbg_name.trim() || null,
    class_1_tariff: parseTariff(form.class_1_tariff),
    class_2_tariff: parseTariff(form.class_2_tariff),
    class_3_tariff: parseTariff(form.class_3_tariff)
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
    :title="item ? 'Edit ICD-9' : 'Tambah ICD-9'"
    :submit-text="item ? 'Perbarui' : 'Simpan'"
    :is-submitting="isSubmitting"
    size="md"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
      <!-- General Error Alert -->
      <div v-if="errors.general" class="alert-error">
        {{ errors.general }}
      </div>

      <div class="form-grid">
        <!-- 1. Kode -->
        <div class="form-group form-group--full">
          <label for="code" class="form-label required">Kode</label>
          <input
            id="code"
            v-model="form.code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('code') }"
            placeholder="Contoh: 87.44"
            maxlength="50"
            required
          >
          <div v-if="fieldError('code')" class="invalid-feedback">{{ fieldError('code') }}</div>
        </div>

        <!-- 2. Nama -->
        <div class="form-group form-group--full">
          <label for="name" class="form-label required">Nama</label>
          <textarea
            id="name"
            v-model="form.name"
            class="form-control form-textarea"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Nama lengkap prosedur ICD-9"
            rows="3"
            maxlength="255"
            required
          ></textarea>
          <div v-if="fieldError('name')" class="invalid-feedback">{{ fieldError('name') }}</div>
        </div>

        <!-- 3. Kode INACBG -->
        <div class="form-group">
          <label for="inacbg_code" class="form-label">Kode INACBG</label>
          <input
            id="inacbg_code"
            v-model="form.inacbg_code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('inacbg_code') }"
            placeholder="Contoh: U-1-01-I"
            maxlength="100"
          >
          <div v-if="fieldError('inacbg_code')" class="invalid-feedback">{{ fieldError('inacbg_code') }}</div>
        </div>

        <!-- 4. Nama INACBG -->
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
            <!-- 5. Tarif Kelas 1 -->
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

            <!-- 6. Tarif Kelas 2 -->
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

            <!-- 7. Tarif Kelas 3 -->
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

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .tariff-inputs-grid {
    grid-template-columns: 1fr;
  }
}
</style>
