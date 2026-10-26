<script setup>
import { reactive, ref, watch, onMounted } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';
import SearchableMultiSelect from '@/components/common/SearchableMultiSelect.vue';
import axios from '@/utils/axios';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  // Support both item and group prop name for maximum compatibility
  group: {
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

const form = reactive({
  name: '',
  radiology_category_id: '',
  radiology_type_id: '',
  activity_type_id: '',
  radiology_item_group_ids: [],
  price: 0,
  interpretation_price: 0,
  is_active: true,
  loinc_code: '',
  loinc_url: ''
});

const clientErrors = reactive({});

// Dropdown options
const categories = ref([]);
const types = ref([]);
const activityTypes = ref([]);
const itemGroups = ref([]);

const isLoadingLookups = ref(false);
let lookupsLoaded = false;

const fetchLookups = async () => {
  if (lookupsLoaded) return;
  isLoadingLookups.value = true;
  try {
    const [catsRes, typesRes, actsRes, igsRes] = await Promise.all([
      axios.get('/lookups/radiology-categories').catch(() => ({ data: [] })),
      axios.get('/lookups/radiology-types').catch(() => ({ data: [] })),
      axios.get('/master-data/activity-types/lookup').catch(() => 
        axios.get('/master-data/activity-types?per_page=200').catch(() => ({ data: [] }))
      ),
      axios.get('/lookups/radiology-item-groups').catch(() => ({ data: [] }))
    ]);

    categories.value = Array.isArray(catsRes.data) ? catsRes.data : (catsRes.data?.data || []);
    types.value = Array.isArray(typesRes.data) ? typesRes.data : (typesRes.data?.data || []);
    activityTypes.value = Array.isArray(actsRes.data) ? actsRes.data : (actsRes.data?.data || []);
    itemGroups.value = Array.isArray(igsRes.data) ? igsRes.data : (igsRes.data?.data || []);
    lookupsLoaded = true;
  } catch (err) {
    console.error('Failed to load lookups', err);
  } finally {
    isLoadingLookups.value = false;
  }
};

const resetForm = () => {
  form.name = '';
  form.radiology_category_id = '';
  form.radiology_type_id = '';
  form.activity_type_id = '';
  form.radiology_item_group_ids = [];
  form.price = 0;
  form.interpretation_price = 0;
  form.is_active = true;
  form.loinc_code = '';
  form.loinc_url = '';
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

const populateForm = (data) => {
  if (!data) return;
  form.name = data.name || '';
  form.radiology_category_id = data.radiology_category_id !== undefined && data.radiology_category_id !== null
    ? String(data.radiology_category_id)
    : (data.category?.id ? String(data.category.id) : '');
  form.radiology_type_id = data.radiology_type_id !== undefined && data.radiology_type_id !== null
    ? String(data.radiology_type_id)
    : (data.type?.id ? String(data.type.id) : '');
  form.activity_type_id = data.activity_type_id !== undefined && data.activity_type_id !== null
    ? String(data.activity_type_id)
    : (data.activity_type?.id ? String(data.activity_type.id) : '');

  if (Array.isArray(data.radiology_item_group_ids)) {
    form.radiology_item_group_ids = data.radiology_item_group_ids.map(Number);
  } else if (Array.isArray(data.item_groups)) {
    form.radiology_item_group_ids = data.item_groups.map((ig) => Number(ig.id));
  } else {
    form.radiology_item_group_ids = [];
  }

  form.price = data.price !== undefined && data.price !== null ? Number(data.price) : 0;
  form.interpretation_price = data.interpretation_price !== undefined && data.interpretation_price !== null
    ? Number(data.interpretation_price)
    : 0;
  form.is_active = data.is_active !== undefined ? Boolean(data.is_active) : true;
  form.loinc_code = data.loinc_code || '';
  form.loinc_url = data.loinc_url || '';

  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      if (!lookupsLoaded) {
        fetchLookups();
      }
      const currentItem = props.group || props.item;
      if (currentItem) {
        populateForm(currentItem);
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
  () => props.group || props.item,
  (newItem) => {
    if (props.isOpen && newItem) {
      populateForm(newItem);
    }
  }
);

onMounted(() => {
  fetchLookups();
});

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

const isValidUrl = (url) => {
  if (!url || typeof url !== 'string' || !url.trim()) return false;
  try {
    const parsed = new URL(url.trim());
    return parsed.protocol === 'http:' || parsed.protocol === 'https:';
  } catch {
    return false;
  }
};

const handleSubmit = () => {
  Object.keys(clientErrors).forEach((k) => delete clientErrors[k]);

  let hasError = false;

  // 1. Nama Grup Radiologi
  if (!form.name || !form.name.trim()) {
    clientErrors.name = 'Nama grup radiologi wajib diisi.';
    hasError = true;
  }

  // 2. Kategori Radiologi
  if (!form.radiology_category_id) {
    clientErrors.radiology_category_id = 'Kategori radiologi wajib dipilih.';
    hasError = true;
  }

  // 3. Tipe Radiologi
  if (!form.radiology_type_id) {
    clientErrors.radiology_type_id = 'Tipe radiologi wajib dipilih.';
    hasError = true;
  }

  // 4. Jenis Kegiatan
  if (!form.activity_type_id) {
    clientErrors.activity_type_id = 'Jenis kegiatan wajib dipilih.';
    hasError = true;
  }

  // 6. Harga
  if (form.price === '' || form.price === null || isNaN(Number(form.price))) {
    clientErrors.price = 'Harga wajib diisi.';
    hasError = true;
  } else if (Number(form.price) < 0) {
    clientErrors.price = 'Harga tidak boleh bernilai negatif.';
    hasError = true;
  }

  // 7. Harga Interpretasi
  if (form.interpretation_price === '' || form.interpretation_price === null || isNaN(Number(form.interpretation_price))) {
    clientErrors.interpretation_price = 'Harga interpretasi wajib diisi.';
    hasError = true;
  } else if (Number(form.interpretation_price) < 0) {
    clientErrors.interpretation_price = 'Harga interpretasi tidak boleh bernilai negatif.';
    hasError = true;
  }

  // 10. Loinc Url
  if (form.loinc_url && form.loinc_url.trim() && !isValidUrl(form.loinc_url.trim())) {
    clientErrors.loinc_url = 'Format URL LOINC tidak valid. Contoh: https://loinc.org/...';
    hasError = true;
  }

  if (hasError) return;

  const payload = {
    name: form.name.trim(),
    radiology_category_id: Number(form.radiology_category_id),
    radiology_type_id: Number(form.radiology_type_id),
    activity_type_id: Number(form.activity_type_id),
    radiology_item_group_ids: Array.isArray(form.radiology_item_group_ids) 
      ? form.radiology_item_group_ids.map(Number) 
      : [],
    price: Number(form.price),
    interpretation_price: Number(form.interpretation_price),
    is_active: Boolean(form.is_active),
    loinc_code: form.loinc_code ? form.loinc_code.trim() : null,
    loinc_url: form.loinc_url ? form.loinc_url.trim() : null
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="(group || item) ? 'Edit Grup Radiologi' : 'Tambah Grup Radiologi'"
    :is-submitting="isSubmitting"
    :submit-text="(group || item) ? 'Simpan Perubahan' : 'Simpan'"
    size="lg"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form-container">
      <div v-if="errors && errors.general" class="alert-error">
        {{ errors.general }}
      </div>

      <div class="form-grid">
        <!-- 1. Nama Grup Radiologi (Baris 1: Full width) -->
        <div class="form-group form-group--full">
          <label for="name" class="form-label required">Nama Grup Radiologi</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Masukkan nama grup radiologi"
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('name')"
          />
          <div v-if="fieldError('name')" class="invalid-feedback">
            {{ fieldError('name') }}
          </div>
        </div>

        <!-- 2. Kategori Radiologi (Baris 2, Kolom 1) -->
        <div class="form-group">
          <label for="radiology_category_id" class="form-label required">Kategori Radiologi</label>
          <select
            id="radiology_category_id"
            v-model="form.radiology_category_id"
            class="form-control form-select"
            :class="{ 'is-invalid': fieldError('radiology_category_id') }"
            :disabled="isSubmitting || isLoadingLookups"
            @change="clearFieldError('radiology_category_id')"
          >
            <option value="" disabled>Pilih kategori radiologi</option>
            <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
              {{ cat.name }}
            </option>
          </select>
          <div v-if="fieldError('radiology_category_id')" class="invalid-feedback">
            {{ fieldError('radiology_category_id') }}
          </div>
        </div>

        <!-- 3. Tipe Radiologi (Baris 2, Kolom 2) -->
        <div class="form-group">
          <label for="radiology_type_id" class="form-label required">Tipe Radiologi</label>
          <select
            id="radiology_type_id"
            v-model="form.radiology_type_id"
            class="form-control form-select"
            :class="{ 'is-invalid': fieldError('radiology_type_id') }"
            :disabled="isSubmitting || isLoadingLookups"
            @change="clearFieldError('radiology_type_id')"
          >
            <option value="" disabled>Pilih tipe radiologi</option>
            <option v-for="typ in types" :key="typ.id" :value="String(typ.id)">
              {{ typ.name }}
            </option>
          </select>
          <div v-if="fieldError('radiology_type_id')" class="invalid-feedback">
            {{ fieldError('radiology_type_id') }}
          </div>
        </div>

        <!-- 4. Jenis Kegiatan (Baris 3, Kolom 1) -->
        <div class="form-group">
          <label for="activity_type_id" class="form-label required">Jenis Kegiatan</label>
          <select
            id="activity_type_id"
            v-model="form.activity_type_id"
            class="form-control form-select"
            :class="{ 'is-invalid': fieldError('activity_type_id') }"
            :disabled="isSubmitting || isLoadingLookups"
            @change="clearFieldError('activity_type_id')"
          >
            <option value="" disabled>Pilih jenis kegiatan</option>
            <option v-for="act in activityTypes" :key="act.id" :value="String(act.id)">
              {{ act.name }}
            </option>
          </select>
          <div v-if="fieldError('activity_type_id')" class="invalid-feedback">
            {{ fieldError('activity_type_id') }}
          </div>
        </div>

        <!-- 5. Kelompok Pemeriksaan (Baris 3, Kolom 2) -->
        <div class="form-group">
          <label for="radiology_item_group_ids" class="form-label">Kelompok Pemeriksaan</label>
          <SearchableMultiSelect
            v-model="form.radiology_item_group_ids"
            :options="itemGroups"
            placeholder="Pilih kelompok pemeriksaan"
            :disabled="isSubmitting || isLoadingLookups"
          />
          <div v-if="fieldError('radiology_item_group_ids')" class="invalid-feedback">
            {{ fieldError('radiology_item_group_ids') }}
          </div>
        </div>

        <!-- 6. Harga (Baris 4, Kolom 1) -->
        <div class="form-group">
          <label for="price" class="form-label required">Harga</label>
          <div class="input-prefix-wrapper">
            <span class="prefix">Rp</span>
            <input autocomplete="off"
              id="price"
              v-model.number="form.price"
              type="number"
              min="0"
              step="any"
              class="form-control input-with-prefix font-mono"
              :class="{ 'is-invalid': fieldError('price') }"
              placeholder="Masukkan harga"
              :disabled="isSubmitting"
              @input="clearFieldError('price')"
            />
          </div>
          <div v-if="fieldError('price')" class="invalid-feedback">
            {{ fieldError('price') }}
          </div>
        </div>

        <!-- 7. Harga Interpretasi (Baris 4, Kolom 2) -->
        <div class="form-group">
          <label for="interpretation_price" class="form-label required">Harga Interpretasi</label>
          <div class="input-prefix-wrapper">
            <span class="prefix">Rp</span>
            <input autocomplete="off"
              id="interpretation_price"
              v-model.number="form.interpretation_price"
              type="number"
              min="0"
              step="any"
              class="form-control input-with-prefix font-mono"
              :class="{ 'is-invalid': fieldError('interpretation_price') }"
              placeholder="Masukkan harga interpretasi"
              :disabled="isSubmitting"
              @input="clearFieldError('interpretation_price')"
            />
          </div>
          <div v-if="fieldError('interpretation_price')" class="invalid-feedback">
            {{ fieldError('interpretation_price') }}
          </div>
        </div>

        <!-- 8. Status Tampil (Baris 5: Full width) -->
        <div class="form-group form-group--full">
          <label class="form-label required">Status Tampil</label>
          <div class="status-radio-group">
            <label class="status-radio-item" :class="{ 'is-selected': form.is_active === true }">
              <input
                id="status_active"
                type="radio"
                v-model="form.is_active"
                :value="true"
                name="radiology_group_status"
                :disabled="isSubmitting"
                @change="clearFieldError('is_active')"
              />
              <span class="status-radio-label">Aktif</span>
            </label>
            <label class="status-radio-item" :class="{ 'is-selected': form.is_active === false }">
              <input
                id="status_inactive"
                type="radio"
                v-model="form.is_active"
                :value="false"
                name="radiology_group_status"
                :disabled="isSubmitting"
                @change="clearFieldError('is_active')"
              />
              <span class="status-radio-label">Nonaktif</span>
            </label>
          </div>
          <div v-if="fieldError('is_active')" class="invalid-feedback">
            {{ fieldError('is_active') }}
          </div>
        </div>

        <!-- 9. Loinc Code (Baris 6, Kolom 1) -->
        <div class="form-group">
          <label for="loinc_code" class="form-label">Loinc Code</label>
          <input
            id="loinc_code"
            v-model="form.loinc_code"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('loinc_code') }"
            placeholder="Masukkan LOINC Code"
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('loinc_code')"
          />
          <div v-if="fieldError('loinc_code')" class="invalid-feedback">
            {{ fieldError('loinc_code') }}
          </div>
        </div>

        <!-- 10. Loinc Url (Baris 6, Kolom 2) -->
        <div class="form-group">
          <label for="loinc_url" class="form-label">Loinc Url</label>
          <input
            id="loinc_url"
            v-model="form.loinc_url"
            type="url"
            class="form-control"
            :class="{ 'is-invalid': fieldError('loinc_url') }"
            placeholder="https://loinc.org/..."
            maxlength="255"
            autocomplete="off"
            :disabled="isSubmitting"
            @input="clearFieldError('loinc_url')"
          />
          <div v-if="fieldError('loinc_url')" class="invalid-feedback">
            {{ fieldError('loinc_url') }}
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
}

.alert-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 6px;
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
}

.form-group--full {
  grid-column: 1 / -1;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #0f172a);
  margin-bottom: 0.375rem;
}

.form-label.required::after {
  content: " *";
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: var(--color-text-navy, #0f172a);
  background-color: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  box-sizing: border-box;
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
  background-color: #f8fafc;
  cursor: not-allowed;
  opacity: 0.75;
}

.input-prefix-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-prefix-wrapper .prefix {
  position: absolute;
  left: 0.875rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-secondary, #64748b);
  pointer-events: none;
  z-index: 1;
}

.input-with-prefix {
  padding-left: 2.25rem;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.invalid-feedback {
  display: block;
  font-size: 0.8125rem;
  color: #ef4444;
  margin-top: 0.375rem;
}

.status-radio-group {
  display: flex;
  gap: 1rem;
  margin-top: 0.25rem;
}

.status-radio-item {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  cursor: pointer;
  background-color: #ffffff;
  transition: all 0.2s;
  user-select: none;
}

.status-radio-item:hover {
  border-color: var(--color-primary, #0f766e);
  background-color: #f0fdfa;
}

.status-radio-item.is-selected {
  border-color: var(--color-primary, #0f766e);
  background-color: #f0fdfa;
}

.status-radio-item input[type="radio"] {
  cursor: pointer;
  accent-color: var(--color-primary, #0f766e);
}

.status-radio-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-navy, #0f172a);
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
