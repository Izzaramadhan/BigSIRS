<script setup>
import { reactive, watch, ref } from 'vue';
import lookupService from '@/services/lookup.service';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  medicine: {
    type: Object,
    default: null
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

const currentMedicine = () => props.medicine || props.item || null;

const form = reactive({
  code: '',
  name: '',
  kfa_code: '',
  function: '',
  medicine_unit_id: '',
  medicine_category_id: '',
  medicine_classification_id: '',
  medicine_route_id: '',
  generic_medicine_id: '',
  description: ''
});

const clientErrors = reactive({});

// Lookup data options
const units = ref([]);
const categories = ref([]);
const classifications = ref([]);
const routes = ref([]);
const generics = ref([]);

const genericSearchQuery = ref('');
const loadingLookups = ref(false);
const loadingGenerics = ref(false);

const fetchLookups = async () => {
  loadingLookups.value = true;
  try {
    const [uns, cats, clss, rts, gens] = await Promise.all([
      lookupService.getMedicineUnits(),
      lookupService.getMedicineCategories(),
      lookupService.getMedicineClassifications(),
      lookupService.getMedicineRoutes(),
      lookupService.getGenericMedicines({ search: '' })
    ]);
    units.value = uns || [];
    categories.value = cats || [];
    classifications.value = clss || [];
    routes.value = rts || [];
    generics.value = gens || [];
  } catch (err) {
    console.error('Gagal memuat referensi obat:', err);
  } finally {
    loadingLookups.value = false;
  }
};

let genericSearchTimeout = null;
const handleSearchGeneric = (event) => {
  const query = event.target.value;
  genericSearchQuery.value = query;
  if (genericSearchTimeout) clearTimeout(genericSearchTimeout);

  genericSearchTimeout = setTimeout(async () => {
    loadingGenerics.value = true;
    try {
      const res = await lookupService.getGenericMedicines({ search: query });
      generics.value = res || [];

      // If form already has a selected generic that is not in the new search results, keep it
      const currentGenId = form.generic_medicine_id;
      if (currentGenId && !generics.value.some((g) => g.id === Number(currentGenId))) {
        const itemObj = currentMedicine();
        if (itemObj?.generic && itemObj.generic.id === Number(currentGenId)) {
          generics.value.unshift(itemObj.generic);
        }
      }
    } catch (err) {
      console.error('Gagal mencari obat generik:', err);
    } finally {
      loadingGenerics.value = false;
    }
  }, 400);
};

const resetForm = () => {
  form.code = '';
  form.name = '';
  form.kfa_code = '';
  form.function = '';
  form.medicine_unit_id = '';
  form.medicine_category_id = '';
  form.medicine_classification_id = '';
  form.medicine_route_id = '';
  form.generic_medicine_id = '';
  form.description = '';
  genericSearchQuery.value = '';
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

const populateForm = (data) => {
  form.code = data.code || '';
  form.name = data.name || '';
  form.kfa_code = data.kfa_code || '';
  form.function = data.function || '';
  form.medicine_unit_id = data.medicine_unit_id ? String(data.medicine_unit_id) : '';
  form.medicine_category_id = data.medicine_category_id ? String(data.medicine_category_id) : '';
  form.medicine_classification_id = data.medicine_classification_id ? String(data.medicine_classification_id) : '';
  form.medicine_route_id = data.medicine_route_id ? String(data.medicine_route_id) : '';
  form.generic_medicine_id = data.generic_medicine_id ? String(data.generic_medicine_id) : '';
  form.description = data.description || '';
  genericSearchQuery.value = '';
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);

  // Ensure selected generic is in generics options
  if (data.generic_medicine_id && data.generic) {
    const exists = generics.value.some((g) => g.id === data.generic_medicine_id);
    if (!exists) {
      generics.value.unshift(data.generic);
    }
  }
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      const med = currentMedicine();
      if (med) {
        populateForm(med);
      } else {
        resetForm();
      }
      fetchLookups().then(() => {
        const currentMed = currentMedicine();
        if (currentMed?.generic_medicine_id && currentMed.generic) {
          const exists = generics.value.some((g) => g.id === currentMed.generic_medicine_id);
          if (!exists) {
            generics.value.unshift(currentMed.generic);
          }
        }
      });
    } else {
      resetForm();
    }
  },
  { immediate: true }
);

watch(
  () => [props.medicine, props.item],
  () => {
    if (props.isOpen) {
      const med = currentMedicine();
      if (med) {
        populateForm(med);
      }
    }
  }
);

const clearFieldError = (field) => {
  if (clientErrors[field]) {
    delete clientErrors[field];
  }
};

const fieldError = (field) => {
  if (clientErrors[field]) {
    return clientErrors[field];
  }
  if (!props.errors || !props.errors[field]) return null;
  return Array.isArray(props.errors[field]) ? props.errors[field][0] : props.errors[field];
};

const validateClient = () => {
  Object.keys(clientErrors).forEach((k) => delete clientErrors[k]);
  let isValid = true;

  if (!form.name || !form.name.trim()) {
    clientErrors.name = 'Nama obat wajib diisi.';
    isValid = false;
  }

  return isValid;
};

const handleSubmit = () => {
  if (props.isSubmitting) return;
  if (!validateClient()) return;

  const payload = {
    code: form.code && form.code.trim() ? form.code.trim() : null,
    name: form.name.trim(),
    kfa_code: form.kfa_code && form.kfa_code.trim() ? form.kfa_code.trim() : null,
    function: form.function && form.function.trim() ? form.function.trim() : null,
    medicine_unit_id: form.medicine_unit_id ? Number(form.medicine_unit_id) : null,
    medicine_category_id: form.medicine_category_id ? Number(form.medicine_category_id) : null,
    medicine_classification_id: form.medicine_classification_id ? Number(form.medicine_classification_id) : null,
    medicine_route_id: form.medicine_route_id ? Number(form.medicine_route_id) : null,
    generic_medicine_id: form.generic_medicine_id ? Number(form.generic_medicine_id) : null,
    description: form.description && form.description.trim() ? form.description.trim() : null
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="currentMedicine() ? 'Edit Obat' : 'Tambah Obat'"
    :submit-text="currentMedicine() ? 'Perbarui' : 'Simpan'"
    :is-submitting="isSubmitting"
    size="lg"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form-container">
      <!-- General Error Alert -->
      <div v-if="errors?.general" class="alert-error" role="alert">
        {{ errors.general }}
      </div>

      <div class="form-grid">
        <!-- 1. Kode -->
        <div class="form-group">
          <label for="code" class="form-label">Kode</label>
          <input
            id="code"
            v-model="form.code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('code') }"
            placeholder="Masukkan kode obat"
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('code')"
          />
          <div v-if="fieldError('code')" class="invalid-feedback">
            {{ fieldError('code') }}
          </div>
        </div>

        <!-- 2. Nama (required) -->
        <div class="form-group">
          <label for="name" class="form-label required">Nama</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Masukkan nama obat"
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('name')"
            required
          />
          <div v-if="fieldError('name')" class="invalid-feedback">
            {{ fieldError('name') }}
          </div>
        </div>

        <!-- 3. Kode KFA -->
        <div class="form-group">
          <label for="kfa_code" class="form-label">Kode KFA</label>
          <input
            id="kfa_code"
            v-model="form.kfa_code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('kfa_code') }"
            placeholder="Masukkan kode KFA"
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('kfa_code')"
          />
          <div v-if="fieldError('kfa_code')" class="invalid-feedback">
            {{ fieldError('kfa_code') }}
          </div>
        </div>

        <!-- 4. Fungsi Obat -->
        <div class="form-group">
          <label for="function" class="form-label">Fungsi Obat</label>
          <input
            id="function"
            v-model="form.function"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('function') }"
            placeholder="Masukkan fungsi obat"
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('function')"
          />
          <div v-if="fieldError('function')" class="invalid-feedback">
            {{ fieldError('function') }}
          </div>
        </div>

        <!-- 5. Satuan -->
        <div class="form-group">
          <label for="medicine_unit_id" class="form-label">Satuan</label>
          <select
            id="medicine_unit_id"
            v-model="form.medicine_unit_id"
            class="form-control form-select"
            :class="{ 'is-invalid': fieldError('medicine_unit_id') }"
            :disabled="isSubmitting || loadingLookups"
            @change="clearFieldError('medicine_unit_id')"
          >
            <option value="">-- Pilih Satuan Obat --</option>
            <option v-for="unit in units" :key="unit.id" :value="String(unit.id)">
              {{ unit.name }}
            </option>
          </select>
          <div v-if="fieldError('medicine_unit_id')" class="invalid-feedback">
            {{ fieldError('medicine_unit_id') }}
          </div>
        </div>

        <!-- 6. Kategori -->
        <div class="form-group">
          <label for="medicine_category_id" class="form-label">Kategori</label>
          <select
            id="medicine_category_id"
            v-model="form.medicine_category_id"
            class="form-control form-select"
            :class="{ 'is-invalid': fieldError('medicine_category_id') }"
            :disabled="isSubmitting || loadingLookups"
            @change="clearFieldError('medicine_category_id')"
          >
            <option value="">-- Pilih Kategori Obat --</option>
            <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
              {{ cat.name }}
            </option>
          </select>
          <div v-if="fieldError('medicine_category_id')" class="invalid-feedback">
            {{ fieldError('medicine_category_id') }}
          </div>
        </div>

        <!-- 7. Golongan -->
        <div class="form-group">
          <label for="medicine_classification_id" class="form-label">Golongan</label>
          <select
            id="medicine_classification_id"
            v-model="form.medicine_classification_id"
            class="form-control form-select"
            :class="{ 'is-invalid': fieldError('medicine_classification_id') }"
            :disabled="isSubmitting || loadingLookups"
            @change="clearFieldError('medicine_classification_id')"
          >
            <option value="">-- Pilih Golongan Obat --</option>
            <option v-for="cls in classifications" :key="cls.id" :value="String(cls.id)">
              {{ cls.name }}
            </option>
          </select>
          <div v-if="fieldError('medicine_classification_id')" class="invalid-feedback">
            {{ fieldError('medicine_classification_id') }}
          </div>
        </div>

        <!-- 8. Jalur Masuk Obat -->
        <div class="form-group">
          <label for="medicine_route_id" class="form-label">Jalur Masuk Obat</label>
          <select
            id="medicine_route_id"
            v-model="form.medicine_route_id"
            class="form-control form-select"
            :class="{ 'is-invalid': fieldError('medicine_route_id') }"
            :disabled="isSubmitting || loadingLookups"
            @change="clearFieldError('medicine_route_id')"
          >
            <option value="">-- Pilih Jalur Masuk Obat --</option>
            <option v-for="rt in routes" :key="rt.id" :value="String(rt.id)">
              {{ rt.name }}
            </option>
          </select>
          <div v-if="fieldError('medicine_route_id')" class="invalid-feedback">
            {{ fieldError('medicine_route_id') }}
          </div>
        </div>

        <!-- 9. Generik (Full Width) -->
        <div class="form-group form-group--full">
          <label for="generic_medicine_id" class="form-label">Generik</label>
          <div class="searchable-lookup">
            <input
              type="text"
              class="form-control form-search-sub"
              placeholder="Cari obat generik..."
              :value="genericSearchQuery"
              :disabled="isSubmitting"
              @input="handleSearchGeneric"
            />
            <select
              id="generic_medicine_id"
              v-model="form.generic_medicine_id"
              class="form-control form-select"
              :class="{ 'is-invalid': fieldError('generic_medicine_id') }"
              :disabled="isSubmitting || loadingLookups"
              @change="clearFieldError('generic_medicine_id')"
            >
              <option value="">-- Pilih Obat Generik --</option>
              <option v-for="gen in generics" :key="gen.id" :value="String(gen.id)">
                {{ gen.name }}
              </option>
            </select>
          </div>
          <div v-if="loadingGenerics" class="lookup-hint">Mencari obat generik...</div>
          <div v-if="fieldError('generic_medicine_id')" class="invalid-feedback">
            {{ fieldError('generic_medicine_id') }}
          </div>
        </div>

        <!-- 10. Deskripsi (Full Width) -->
        <div class="form-group form-group--full">
          <label for="description" class="form-label">Deskripsi</label>
          <textarea
            id="description"
            v-model="form.description"
            class="form-control form-textarea"
            :class="{ 'is-invalid': fieldError('description') }"
            placeholder="Masukkan deskripsi obat"
            rows="3"
            maxlength="1000"
            :disabled="isSubmitting"
            @input="clearFieldError('description')"
          ></textarea>
          <div v-if="fieldError('description')" class="invalid-feedback">
            {{ fieldError('description') }}
          </div>
        </div>
      </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.modal-form-container {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.alert-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
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
  color: var(--color-text-navy, #0f172a);
}

.form-label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  font-size: 0.875rem;
  color: var(--color-text-navy, #0f172a);
  background-color: #ffffff;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.75rem center;
  background-repeat: no-repeat;
  background-size: 1.25rem 1.25rem;
  padding-right: 2.25rem;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary, #0f766e);
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
}

.form-control.is-invalid {
  border-color: #ef4444;
}

.form-control.is-invalid:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.form-control:disabled {
  background-color: #f1f5f9;
  cursor: not-allowed;
  opacity: 0.7;
}

.searchable-lookup {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-search-sub {
  background-color: #f8fafc;
  font-size: 0.825rem;
  padding: 0.5rem 0.75rem;
}

.lookup-hint {
  font-size: 0.75rem;
  color: var(--color-text-secondary, #64748b);
  margin-top: 0.125rem;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.invalid-feedback {
  font-size: 0.8rem;
  color: #ef4444;
  margin-top: 0.25rem;
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
