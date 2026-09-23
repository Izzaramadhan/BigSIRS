<script setup>
import { reactive, watch } from 'vue';
import { polyclinicService } from '@/services/polyclinic';

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
  bpjs_code: '',
});

const populateForm = (data) => {
  form.code = data.code || '';
  form.name = data.name || '';
  form.service_type = data.service_type || '';
  form.description = data.description || '';
  form.is_visible = data.is_visible !== undefined ? data.is_visible : true;
  form.is_online_visible = data.is_online_visible !== undefined ? data.is_online_visible : false;
  form.quota = data.quota ?? 0;
  form.jkn_quota = data.jkn_quota ?? 0;
  form.bpjs_code = data.bpjs_code || '';
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
  form.bpjs_code = '';
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
});

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
    bpjs_code: form.bpjs_code.trim() || null,
  };
  emit('submit', payload);
};

const fieldError = (field) => props.errors?.[field]?.[0] || null;
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')" role="dialog" aria-modal="true">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ polyclinic ? 'Edit Poliklinik' : 'Tambah Poliklinik' }}</h3>
          <button type="button" class="btn-close" @click="$emit('close')" aria-label="Tutup modal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        
        <form @submit.prevent="handleSubmit" class="modal-form">
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

            <!-- 6. Gudang Default (pending) -->
            <div class="form-group">
              <label class="form-label">Gudang Default</label>
              <input
                type="text"
                class="form-control"
                disabled
                value=""
                placeholder="Master Gudang belum tersedia"
                title="Field ini akan aktif setelah Master Gudang diimplementasikan"
              >
              <p class="field-note">Menunggu implementasi Master Gudang.</p>
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

            <!-- 9. Kode BPJS -->
            <div class="form-group form-group--full">
              <label for="bpjs_code" class="form-label">Kode BPJS</label>
              <input
                id="bpjs_code"
                v-model="form.bpjs_code"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': fieldError('bpjs_code') }"
                placeholder="Kode bridging BPJS (opsional)"
                maxlength="50"
              >
              <div v-if="fieldError('bpjs_code')" class="invalid-feedback">{{ fieldError('bpjs_code') }}</div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-cancel" @click="$emit('close')" :disabled="isSubmitting">
              Batal
            </button>
            <button type="submit" class="btn-submit" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="spinner"></span>
              {{ isSubmitting ? 'Menyimpan...' : (polyclinic ? 'Simpan Perubahan' : 'Tambah Poliklinik') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 500;
  padding: 1rem;
  backdrop-filter: blur(2px);
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  position: sticky;
  top: 0;
  background: #ffffff;
  z-index: 1;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.btn-close {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-text-secondary);
  padding: 0.25rem;
  border-radius: 4px;
  display: flex;
  align-items: center;
  transition: all 0.2s;
}

.btn-close:hover { color: var(--color-text-navy); background: var(--color-page-bg); }

.btn-close svg { width: 20px; height: 20px; }

.modal-form {
  padding: 1.5rem;
}

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

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-group--full {
  grid-column: 1 / -1;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.form-label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.9rem;
  color: var(--color-text-navy);
  transition: all 0.2s;
  width: 100%;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.form-control.is-invalid {
  border-color: #ef4444;
}

.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  padding-right: 2.5rem;
  cursor: pointer;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-control:disabled {
  background: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}

.invalid-feedback {
  font-size: 0.8rem;
  color: #ef4444;
}

.field-note {
  font-size: 0.78rem;
  color: var(--color-text-secondary);
  margin: 0;
  font-style: italic;
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

/* Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1.5rem;
  margin-top: 0.5rem;
  border-top: 1px solid var(--color-border-soft);
}

.btn-cancel {
  background: transparent;
  color: var(--color-text-secondary);
  border: 1px solid var(--color-border-soft);
  padding: 0.55rem 1.25rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover:not(:disabled) {
  background: var(--color-page-bg);
}

.btn-submit {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  padding: 0.55rem 1.5rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-submit:hover:not(:disabled) { opacity: 0.9; }
.btn-submit:disabled, .btn-cancel:disabled { opacity: 0.6; cursor: not-allowed; }

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
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
