<script setup>
import { reactive, watch, ref } from 'vue';
import { polyclinicService } from '@/services/polyclinic';
import lookupService from '@/services/lookup.service';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  polyclinic: { type: Object, default: null },
  serviceTypes: { type: Array, default: () => [] },
  isSubmitting: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) }
});

const emit = defineEmits(['close', 'submit']);

const form = reactive({
  code: '',
  name: '',
  service_type: '',
  description: '',
  is_visible: true,
  is_online_visible: false,
  quota: 0,
  jkn_quota: 0,
  warehouse_id: '',
});

const warehouses = ref([]);
const loadingWarehouses = ref(false);

const populateForm = (data) => {
  form.code = data.code || '';
  form.name = data.name || '';
  form.service_type = data.service_type || '';
  form.description = data.description || '';
  form.is_visible = data.is_visible !== undefined ? data.is_visible : true;
  form.is_online_visible = data.is_online_visible !== undefined ? data.is_online_visible : false;
  form.quota = data.quota ?? 0;
  form.jkn_quota = data.jkn_quota ?? 0;
  form.warehouse_id = data.warehouse_id || '';
};

const resetForm = () => {
  form.code = '';
  form.name = '';
  form.service_type = '';
  form.description = '';
  form.is_visible = true;
  form.is_online_visible = false;
  form.quota = 0;
  form.jkn_quota = 0;
  form.warehouse_id = '';
};

watch(() => props.isOpen, async (isOpen) => {
  if (!isOpen) return;

  if (props.polyclinic) {
    // Load fresh from API on edit
    try {
      const response = await polyclinicService.getPolyclinic(props.polyclinic.id);
      populateForm(response.data.data);
    } catch (err) {
      console.error(err);
      populateForm(props.polyclinic);
    }
  } else {
    resetForm();
  }

  // Load warehouses
  loadingWarehouses.value = true;
  try {
    warehouses.value = await lookupService.getWarehouses();
  } catch (err) {
    console.error('Failed to load warehouses', err);
  } finally {
    loadingWarehouses.value = false;
  }
}, { immediate: true });

const handleSubmit = () => {
  const payload = {
    code: form.code.trim().toUpperCase(),
    name: form.name,
    service_type: form.service_type,
    description: form.description.trim() || null,
    is_visible: form.is_visible,
    is_online_visible: form.is_online_visible,
    quota: parseInt(form.quota) || 0,
    jkn_quota: parseInt(form.jkn_quota) || 0,
    warehouse_id: form.warehouse_id || null,
  };
  emit('submit', payload);
};

const fieldError = (field) => props.errors?.[field]?.[0] || null;
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="polyclinic ? 'Edit Poliklinik' : 'Tambah Poliklinik'"
    :is-submitting="isSubmitting"
    size="md"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
          <!-- General error -->
          <div v-if="errors.general" class="alert-error">
            {{ errors.general }}
          </div>

          <div class="form-grid">
            <!-- 1. Kode -->
            <div class="form-group">
              <label for="code" class="form-label required">Kode</label>
              <input
                id="code"
                v-model="form.code"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': fieldError('code') }"
                placeholder="Contoh: OP001"
                maxlength="30"
                @input="form.code = form.code.toUpperCase()"
                required
              >
              <div v-if="fieldError('code')" class="invalid-feedback">{{ fieldError('code') }}</div>
            </div>

            <!-- 2. Nama -->
            <div class="form-group">
              <label for="name" class="form-label required">Nama Poliklinik</label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': fieldError('name') }"
                placeholder="Nama lengkap poliklinik"
                maxlength="150"
                required
              >
              <div v-if="fieldError('name')" class="invalid-feedback">{{ fieldError('name') }}</div>
            </div>

            <!-- 3. Jenis Layanan -->
            <div class="form-group form-group--full">
              <label for="service_type" class="form-label required">Jenis Layanan</label>
              <select
                id="service_type"
                v-model="form.service_type"
                class="form-control form-select"
                :class="{ 'is-invalid': fieldError('service_type') }"
                required
              >
                <option value="">-- Pilih Jenis Layanan --</option>
                <option v-for="st in serviceTypes" :key="st.value" :value="st.value">
                  {{ st.label }}
                </option>
              </select>
              <div v-if="fieldError('service_type')" class="invalid-feedback">{{ fieldError('service_type') }}</div>
            </div>

            <!-- 4. Deskripsi -->
            <div class="form-group form-group--full">
              <label for="description" class="form-label">Deskripsi</label>
              <textarea
                id="description"
                v-model="form.description"
                class="form-control form-textarea"
                :class="{ 'is-invalid': fieldError('description') }"
                placeholder="Keterangan singkat poliklinik (opsional)"
                rows="3"
                maxlength="255"
              ></textarea>
              <div v-if="fieldError('description')" class="invalid-feedback">{{ fieldError('description') }}</div>
            </div>

            <!-- 5 & 10. Tampil + Tampil Online -->
            <div class="form-group">
              <span class="form-label">Opsi Tampil</span>
              <div class="toggle-group">
                <label class="toggle-row">
                  <input id="is_visible" type="checkbox" v-model="form.is_visible" class="toggle-input">
                  <span class="toggle-track"></span>
                  <span class="toggle-label-text">Tampil di Antrian</span>
                </label>
                <label class="toggle-row">
                  <input id="is_online_visible" type="checkbox" v-model="form.is_online_visible" class="toggle-input">
                  <span class="toggle-track"></span>
                  <span class="toggle-label-text">Tampil Online</span>
                </label>
              </div>
            </div>

            <!-- 6. Gudang Default -->
            <div class="form-group">
              <label for="warehouse_id" class="form-label">Gudang Default</label>
              <select
                id="warehouse_id"
                v-model="form.warehouse_id"
                class="form-control form-select"
                :class="{ 'is-invalid': fieldError('warehouse_id') }"
                :disabled="loadingWarehouses"
              >
                <option value="">Pilih Gudang Default</option>
                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                  {{ wh.code ? `${wh.code} — ${wh.name}` : wh.name }}
                </option>
              </select>
              <div v-if="loadingWarehouses" class="text-xs text-muted mt-1">Memuat daftar gudang...</div>
              <div v-else-if="fieldError('warehouse_id')" class="invalid-feedback">{{ fieldError('warehouse_id') }}</div>
            </div>

            <!-- 7. Kuota -->
            <div class="form-group">
              <label for="quota" class="form-label">Kuota</label>
              <input
                id="quota"
                v-model.number="form.quota"
                type="number"
                class="form-control"
                :class="{ 'is-invalid': fieldError('quota') }"
                min="0"
                placeholder="0"
              >
              <div v-if="fieldError('quota')" class="invalid-feedback">{{ fieldError('quota') }}</div>
            </div>

            <!-- 8. Kuota JKN -->
            <div class="form-group">
              <label for="jkn_quota" class="form-label">Kuota JKN</label>
              <input
                id="jkn_quota"
                v-model.number="form.jkn_quota"
                type="number"
                class="form-control"
                :class="{ 'is-invalid': fieldError('jkn_quota') }"
                min="0"
                placeholder="0"
              >
              <div v-if="fieldError('jkn_quota')" class="invalid-feedback">{{ fieldError('jkn_quota') }}</div>
            </div>
          </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>


.alert-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}



/* Toggle styles */
.toggle-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0.5rem 0;
}

.toggle-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  cursor: pointer;
}

.toggle-input {
  display: none;
}

.toggle-track {
  width: 38px;
  height: 22px;
  background: #cbd5e1;
  border-radius: 999px;
  position: relative;
  transition: background 0.2s;
  flex-shrink: 0;
}

.toggle-track::after {
  content: '';
  position: absolute;
  width: 16px;
  height: 16px;
  background: #ffffff;
  border-radius: 50%;
  top: 3px;
  left: 3px;
  transition: transform 0.2s;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.toggle-input:checked + .toggle-track {
  background: var(--color-primary);
}

.toggle-input:checked + .toggle-track::after {
  transform: translateX(16px);
}

.toggle-label-text {
  font-size: 0.875rem;
  color: var(--color-text-navy);
}



@media (max-width: 600px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .form-group--full {
    grid-column: auto;
  }
}
</style>
