<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';
import axios from '@/utils/axios';

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
  radiology_item_group_id: '',
  is_active: true
});

const clientErrors = reactive({});

// Lookups for Kelompok Item
const groups = ref([]);
const loadingGroups = ref(false);
const errorGroups = ref(null);

const fetchGroups = async (forceGroupId = null) => {
  loadingGroups.value = true;
  errorGroups.value = null;
  try {
    const params = {};
    if (forceGroupId) {
      params.ids = forceGroupId;
    }
    const response = await axios.get('/lookups/radiology-item-groups', { params });
    const fetched = response.data?.data || response.data || [];
    groups.value = Array.isArray(fetched) ? fetched : [];

    // Ensure item's existing group is available in dropdown even if inactive or missing
    if (props.item?.group && !groups.value.some((g) => g.id === props.item.group.id)) {
      groups.value.push(props.item.group);
    }
  } catch {
    // Fallback to master-data endpoint if lookups fails
    try {
      const fallback = await axios.get('/master-data/radiology-item-groups', {
        params: { per_page: 100 }
      });
      const fetched = fallback.data?.data || fallback.data || [];
      groups.value = Array.isArray(fetched) ? fetched : [];
    } catch {
      errorGroups.value = 'Gagal memuat kelompok item radiologi.';
    }
  } finally {
    loadingGroups.value = false;
  }
};

const resetForm = () => {
  form.name = '';
  form.radiology_item_group_id = '';
  form.is_active = true;
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);
};

const populateForm = (data) => {
  form.name = data.name || '';
  form.radiology_item_group_id = data.radiology_item_group_id !== undefined && data.radiology_item_group_id !== null
    ? Number(data.radiology_item_group_id)
    : '';
  form.is_active = (data.is_active !== undefined && data.is_active !== null)
    ? Boolean(data.is_active)
    : true;
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key]);

  // Ensure groups list contains this item's group
  if (data.radiology_item_group_id) {
    if (data.group && !groups.value.some((g) => g.id === data.group.id)) {
      groups.value.push(data.group);
    }
  }
};

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      fetchGroups(props.item?.radiology_item_group_id);
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
    if (props.isOpen && newItem) {
      populateForm(newItem);
    }
  }
);

onMounted(() => {
  if (groups.value.length === 0) {
    fetchGroups();
  }
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

const validateClient = () => {
  Object.keys(clientErrors).forEach((k) => delete clientErrors[k]);
  let isValid = true;

  if (!form.name || !form.name.trim()) {
    clientErrors.name = 'Nama item radiologi wajib diisi.';
    isValid = false;
  }

  if (!form.radiology_item_group_id) {
    clientErrors.radiology_item_group_id = 'Kelompok item radiologi wajib dipilih.';
    isValid = false;
  }

  return isValid;
};

const handleSubmit = () => {
  if (!validateClient()) return;

  const payload = {
    name: form.name.trim(),
    radiology_item_group_id: Number(form.radiology_item_group_id),
    is_active: Boolean(form.is_active)
  };

  emit('submit', payload);
};
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="item ? 'Edit Item Radiologi' : 'Tambah Item Radiologi'"
    :submit-text="item ? 'Simpan Perubahan' : 'Simpan'"
    :is-submitting="isSubmitting"
    size="md"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form-container">
      <!-- General Error Alert -->
      <div v-if="errors?.general" class="alert-error" role="alert">
        {{ errors.general }}
      </div>

      <div class="form-grid">
        <!-- 1. Nama Item -->
        <div class="form-group form-group--full">
          <label for="name" class="form-label required">Nama Item</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            placeholder="Masukkan nama item radiologi"
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

        <!-- 2. Kelompok Item Radiologi -->
        <div class="form-group form-group--full">
          <label for="radiology_item_group_id" class="form-label required">Kelompok Item Radiologi</label>
          <select
            id="radiology_item_group_id"
            v-model="form.radiology_item_group_id"
            class="form-control form-select"
            :class="{ 'is-invalid': fieldError('radiology_item_group_id') }"
            :disabled="isSubmitting || loadingGroups"
            @change="clearFieldError('radiology_item_group_id')"
            required
          >
            <option value="">-- Pilih Kelompok Item Radiologi --</option>
            <option
              v-for="grp in groups"
              :key="grp.id"
              :value="grp.id"
            >
              {{ grp.name }}
            </option>
          </select>
          <div v-if="loadingGroups" class="field-hint">
            Memuat daftar kelompok item...
          </div>
          <div v-if="errorGroups" class="field-error-hint">
            {{ errorGroups }}
            <button type="button" class="btn-retry" @click="fetchGroups(form.radiology_item_group_id)">Coba Lagi</button>
          </div>
          <div v-if="fieldError('radiology_item_group_id')" class="invalid-feedback">
            {{ fieldError('radiology_item_group_id') }}
          </div>
        </div>

        <!-- 3. Status Tampil -->
        <div class="form-group form-group--full">
          <label class="form-label required">Status Tampil</label>
          <div class="status-radio-group">
            <label class="status-radio-item" :class="{ 'is-selected': form.is_active === true }">
              <input
                id="status_active"
                type="radio"
                v-model="form.is_active"
                :value="true"
                name="radiology_item_status"
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
                name="radiology_item_status"
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
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group--full {
  width: 100%;
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

.form-select {
  cursor: pointer;
}

.invalid-feedback {
  display: block;
  font-size: 0.8125rem;
  color: #ef4444;
  margin-top: 0.375rem;
}

.field-hint {
  font-size: 0.8125rem;
  color: var(--color-text-secondary, #64748b);
  margin-top: 0.25rem;
}

.field-error-hint {
  font-size: 0.8125rem;
  color: #ef4444;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-retry {
  background: none;
  border: none;
  color: var(--color-primary, #0f766e);
  cursor: pointer;
  padding: 0;
  text-decoration: underline;
  font-size: 0.8125rem;
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
</style>
