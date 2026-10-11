<script setup>
import { ref, reactive, watch } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';
import {
  convertToSuperscript,
  hasSuperscriptNotation
} from '@/utils/superscript';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  item: {
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
  name: '',
  reference_value: '',
  unit: ''
});

const unitPreview = ref('');
const unitNotice = ref('');
const referenceValuePreview = ref('');
const referenceValueNotice = ref('');

const resetForm = () => {
  form.name = '';
  form.reference_value = '';
  form.unit = '';
  unitPreview.value = '';
  unitNotice.value = '';
  referenceValuePreview.value = '';
  referenceValueNotice.value = '';
};

const populateForm = (data) => {
  form.name = data.name || '';
  form.reference_value = data.reference_value || '';
  form.unit = data.unit || '';
  unitPreview.value = '';
  unitNotice.value = '';
  referenceValuePreview.value = '';
  referenceValueNotice.value = '';
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      if (props.item) {
        populateForm(props.item);
      } else {
        resetForm();
      }
    } else {
      resetForm();
    }
  },
  { immediate: true }
);

watch(
  () => props.item,
  (newItem) => {
    if (props.isOpen) {
      if (newItem) {
        populateForm(newItem);
      } else {
        resetForm();
      }
    }
  }
);

const clearUnitFeedback = () => {
  unitNotice.value = '';
};

const clearReferenceValueFeedback = () => {
  referenceValueNotice.value = '';
};

const handleConvertUnit = () => {
  unitNotice.value = '';
  const currentVal = form.unit || '';
  if (!currentVal.trim()) {
    unitNotice.value = 'Tidak ada format pangkat yang perlu dikonversi.';
    unitPreview.value = '';
    return;
  }

  if (hasSuperscriptNotation(currentVal)) {
    const converted = convertToSuperscript(currentVal);
    form.unit = converted;
    unitPreview.value = converted;
    unitNotice.value = '';
  } else {
    unitNotice.value = 'Tidak ada format pangkat yang perlu dikonversi.';
    if (currentVal.trim()) {
      unitPreview.value = currentVal;
    }
  }
};

const handleConvertReferenceValue = () => {
  referenceValueNotice.value = '';
  const currentVal = form.reference_value || '';
  if (!currentVal.trim()) {
    referenceValueNotice.value = 'Tidak ada format pangkat yang perlu dikonversi.';
    referenceValuePreview.value = '';
    return;
  }

  if (hasSuperscriptNotation(currentVal)) {
    const converted = convertToSuperscript(currentVal);
    form.reference_value = converted;
    referenceValuePreview.value = converted;
    referenceValueNotice.value = '';
  } else {
    referenceValueNotice.value = 'Tidak ada format pangkat yang perlu dikonversi.';
    if (currentVal.trim()) {
      referenceValuePreview.value = currentVal;
    }
  }
};

const handleSubmit = () => {
  const payload = {
    name: form.name.trim(),
    reference_value: form.reference_value.trim() || null,
    unit: form.unit.trim() || null,
    is_active: props.item ? (props.item.is_active !== undefined ? Boolean(props.item.is_active) : true) : true
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="item ? 'Edit Item Lab' : 'Tambah Item Lab'"
    :submit-text="item ? 'Simpan Perubahan' : 'Simpan'"
    :is-submitting="isSubmitting"
    size="md"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div class="form-layout">
      <!-- Nama Item Lab -->
      <div class="form-group">
        <label for="name">
          Nama Item Lab <span class="required">*</span>
        </label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          class="form-input"
          :class="{ 'is-invalid': errors.name }"
          placeholder="Contoh: KOLESTEROL TOTAL, HBSAG, HEMOGLOBIN"
          maxlength="255"
          autocomplete="off"
          :disabled="isSubmitting"
          required
        />
        <span v-if="errors.name" class="error-text">
          {{ errors.name[0] }}
        </span>
      </div>

      <!-- Standar Normal / Nilai Rujukan -->
      <div class="form-group">
        <div class="field-header">
          <label for="reference_value">
            Standar Normal / Nilai Rujukan
          </label>
          <button
            type="button"
            class="btn-convert-pangkat"
            :disabled="isSubmitting"
            title="Convert notasi ^ menjadi pangkat (superscript)"
            @click="handleConvertReferenceValue"
          >
            <span class="icon-sup" aria-hidden="true">x²</span>
            <span>Convert Pangkat</span>
          </button>
        </div>
        <textarea
          id="reference_value"
          v-model="form.reference_value"
          class="form-textarea"
          :class="{ 'is-invalid': errors.reference_value }"
          rows="3"
          placeholder="Contoh: <200, Negatif, 30-70, L: 13-17, P: 12-15"
          :disabled="isSubmitting"
          @input="clearReferenceValueFeedback"
        ></textarea>
        <div v-if="referenceValuePreview" class="conversion-preview">
          <span class="preview-label">Preview:</span>
          <span class="preview-value">{{ referenceValuePreview }}</span>
        </div>
        <p v-if="referenceValueNotice" class="conversion-notice">
          {{ referenceValueNotice }}
        </p>
        <span v-if="errors.reference_value" class="error-text">
          {{ errors.reference_value[0] }}
        </span>
      </div>

      <!-- Satuan -->
      <div class="form-group">
        <div class="field-header">
          <label for="unit">
            Satuan
          </label>
          <button
            type="button"
            class="btn-convert-pangkat"
            :disabled="isSubmitting"
            title="Convert notasi ^ menjadi pangkat (superscript)"
            @click="handleConvertUnit"
          >
            <span class="icon-sup" aria-hidden="true">x²</span>
            <span>Convert Pangkat</span>
          </button>
        </div>
        <input
          id="unit"
          v-model="form.unit"
          type="text"
          class="form-input"
          :class="{ 'is-invalid': errors.unit }"
          placeholder="Contoh: mg/dL, g/dL, %, mm^3, x10^3/uL (boleh kosong)"
          maxlength="255"
          autocomplete="off"
          :disabled="isSubmitting"
          @input="clearUnitFeedback"
        />
        <div v-if="unitPreview" class="conversion-preview">
          <span class="preview-label">Preview:</span>
          <span class="preview-value">{{ unitPreview }}</span>
        </div>
        <p v-if="unitNotice" class="conversion-notice">
          {{ unitNotice }}
        </p>
        <span v-if="errors.unit" class="error-text">
          {{ errors.unit[0] }}
        </span>
      </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.form-layout {
  display: flex;
  flex-direction: column;
  gap: 1.125rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.field-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.form-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #0f172a);
}

.btn-convert-pangkat {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.2rem 0.5rem;
  background: #f8fafc;
  color: #0f766e;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  line-height: 1.2;
  font-family: inherit;
}

.btn-convert-pangkat:hover:not(:disabled) {
  background: #f0fdfa;
  border-color: #0f766e;
  color: #0d9488;
}

.btn-convert-pangkat:focus-visible {
  outline: 2px solid #0f766e;
  outline-offset: 1px;
}

.btn-convert-pangkat:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background: #f1f5f9;
}

.icon-sup {
  display: inline-block;
  font-size: 0.7rem;
  font-weight: 700;
  color: #0f766e;
  background: #ccfbf1;
  padding: 0.05rem 0.25rem;
  border-radius: 3px;
  line-height: 1;
}

.conversion-preview {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.3rem 0.5rem;
  background: #f8fafc;
  border-radius: 4px;
  border: 1px dashed #cbd5e1;
  font-size: 0.75rem;
  color: #475569;
}

.preview-label {
  font-weight: 600;
  color: #0f766e;
}

.preview-value {
  font-family: inherit;
  font-weight: 500;
  color: #0f172a;
}

.conversion-notice {
  margin: 0;
  font-size: 0.75rem;
  color: #64748b;
  font-style: italic;
}

.required {
  color: #ef4444;
}

.form-input,
.form-textarea {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  font-size: 0.875rem;
  color: var(--color-text-navy, #0f172a);
  background: #ffffff;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
  font-family: inherit;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary, #0f766e);
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
}

.form-input:disabled,
.form-textarea:disabled {
  background: #f1f5f9;
  cursor: not-allowed;
  opacity: 0.7;
}

.form-input.is-invalid,
.form-textarea.is-invalid {
  border-color: #ef4444;
}

.form-input.is-invalid:focus,
.form-textarea.is-invalid:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.error-text {
  font-size: 0.75rem;
  color: #ef4444;
}
</style>
