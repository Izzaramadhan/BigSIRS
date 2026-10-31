<script setup>
import { ref, reactive, watch, computed } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  issueToEdit: {
    type: Object,
    default: null
  },
  officers: {
    type: Array,
    default: () => []
  },
  isSubmitting: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'submit']);

const errors = ref({});

const getTodayDate = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const formData = reactive({
  date: getTodayDate(),
  officerId: '',
  officerName: '',
  notes: ''
});

// Normalize officer list to { id, name }
const normalizedOfficers = computed(() => {
  return props.officers.map((item, index) => {
    if (typeof item === 'object' && item !== null) {
      return {
        id: item.id || index + 1,
        name: item.name || item.officerName || 'Petugas ' + (index + 1)
      };
    }
    return {
      id: index + 1,
      name: String(item)
    };
  });
});

const initForm = () => {
  errors.value = {};
  if (props.issueToEdit) {
    let rawDate = props.issueToEdit.issue_date || props.issueToEdit.date || '';
    if (rawDate && rawDate.includes('T')) {
      rawDate = rawDate.split('T')[0];
    } else if (rawDate && rawDate.includes(' ')) {
      rawDate = rawDate.split(' ')[0];
    }
    formData.date = rawDate || getTodayDate();
    formData.officerId = props.issueToEdit.officer_id || props.issueToEdit.officerId || '';
    formData.officerName = props.issueToEdit.officer_name || props.issueToEdit.officerName || '';

    // If officerId is not set, try to match by name
    if (!formData.officerId && formData.officerName) {
      const match = normalizedOfficers.value.find((o) => o.name === formData.officerName);
      if (match) formData.officerId = match.id;
    }

    formData.notes = props.issueToEdit.notes || props.issueToEdit.purpose || '';
  } else {
    formData.date = getTodayDate();
    formData.officerId = normalizedOfficers.value[0]?.id || '';
    formData.officerName = normalizedOfficers.value[0]?.name || '';
    formData.notes = '';
  }
};

watch(
  () => props.isOpen,
  (open) => {
    if (open) initForm();
  },
  { immediate: true }
);

watch(
  () => props.officers,
  () => {
    if (props.isOpen && !formData.officerId && normalizedOfficers.value.length > 0) {
      formData.officerId = normalizedOfficers.value[0].id;
      formData.officerName = normalizedOfficers.value[0].name;
    }
  },
  { immediate: true }
);

watch(
  () => props.issueToEdit,
  () => {
    if (props.isOpen) initForm();
  }
);

const handleOfficerChange = (e) => {
  const id = e.target.value;
  formData.officerId = id;
  const match = normalizedOfficers.value.find((o) => String(o.id) === String(id));
  formData.officerName = match ? match.name : '';
};

const handleReset = () => {
  initForm();
};

const validate = () => {
  const errs = {};
  if (!formData.date) {
    errs.date = 'Tanggal wajib diisi.';
  }
  if (!formData.officerId && !formData.officerName) {
    errs.officer = 'Petugas wajib dipilih.';
  }
  errors.value = errs;
  return Object.keys(errs).length === 0;
};

const handleSubmit = () => {
  if (!validate()) return;

  const payload = {
    date: formData.date,
    issue_date: formData.date,
    officer_id: formData.officerId ? Number(formData.officerId) : null,
    officer_name: formData.officerName || undefined,
    notes: formData.notes ? formData.notes.trim() : '',
    purpose: formData.notes ? formData.notes.trim() : 'Kebutuhan Internal'
  };

  emit('submit', payload);
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-md">
        <!-- Header -->
        <div class="modal-header">
          <div class="header-title-box">
            <span class="header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
            </span>
            <h3 class="modal-title font-bold text-navy">
              {{ issueToEdit ? 'Edit Pengeluaran' : 'Tambah Pengeluaran' }}
            </h3>
          </div>
          <button
            type="button"
            class="btn-close"
            :disabled="isSubmitting"
            aria-label="Tutup"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Body: 3 Fields only -->
        <form @submit.prevent="handleSubmit">
          <div class="modal-body">
            <!-- 1. Tanggal -->
            <div class="form-group">
              <label for="form-input-date" class="form-label">
                Tanggal <span class="text-danger">*</span>
              </label>
              <input
                id="form-input-date"
                v-model="formData.date"
                type="date"
                class="form-control"
                :class="{ 'is-invalid': errors.date }"
                required
              />
              <span v-if="errors.date" class="invalid-feedback">{{ errors.date }}</span>
            </div>

            <!-- 2. Petugas -->
            <div class="form-group">
              <label for="form-input-officer" class="form-label">
                Petugas <span class="text-danger">*</span>
              </label>
              <select
                id="form-input-officer"
                class="form-select"
                :class="{ 'is-invalid': errors.officer }"
                :value="formData.officerId"
                required
                @change="handleOfficerChange"
              >
                <option value="" disabled>-- Pilih Petugas --</option>
                <option
                  v-for="officer in normalizedOfficers"
                  :key="officer.id"
                  :value="officer.id"
                >
                  {{ officer.name }}
                </option>
              </select>
              <span v-if="errors.officer" class="invalid-feedback">{{ errors.officer }}</span>
            </div>

            <!-- 3. Keperluan -->
            <div class="form-group">
              <div class="label-with-counter">
                <label for="form-input-notes" class="form-label">
                  Keperluan
                </label>
                <span class="char-counter text-muted">
                  {{ formData.notes ? formData.notes.length : 0 }} / 1000
                </span>
              </div>
              <textarea
                id="form-input-notes"
                v-model="formData.notes"
                class="form-textarea"
                rows="4"
                maxlength="1000"
                placeholder="Masukkan keperluan atau catatan pengeluaran..."
              ></textarea>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="modal-footer">
            <button
              type="button"
              class="btn-cancel"
              :disabled="isSubmitting"
              @click="$emit('close')"
            >
              Batal
            </button>
            <button
              v-if="!issueToEdit"
              type="button"
              class="btn-reset"
              :disabled="isSubmitting"
              @click="handleReset"
            >
              Reset
            </button>
            <button
              type="submit"
              class="btn-primary"
              :disabled="isSubmitting"
            >
              <svg v-if="isSubmitting" class="spin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="10" stroke-width="4" stroke-dasharray="32" stroke-linecap="round"></circle>
              </svg>
              <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}</span>
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
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 520px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: modalScale 0.2s ease-out;
}

@keyframes modalScale {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.15rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  background: #f8fafc;
}

.header-title-box {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.header-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
}

.header-icon svg {
  width: 18px;
  height: 18px;
}

.modal-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
}

.btn-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.35rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.btn-close:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-close svg {
  width: 18px;
  height: 18px;
}

.modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  max-height: calc(85vh - 140px);
  overflow-y: auto;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
}

.label-with-counter {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.char-counter {
  font-size: 0.75rem;
  color: #94a3b8;
}

.form-control,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.6rem 0.85rem;
  font-size: 0.875rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background-color: #ffffff;
  color: #1e293b;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  box-sizing: border-box;
}

.form-control:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.15);
}

.form-textarea {
  resize: vertical;
  min-height: 90px;
}

.is-invalid {
  border-color: #ef4444 !important;
}

.invalid-feedback {
  font-size: 0.78rem;
  color: #ef4444;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #f1f5f9;
  background: #f8fafc;
}

.btn-cancel,
.btn-reset,
.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  height: 38px;
  padding: 0 1.25rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-cancel {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
}

.btn-cancel:hover:not(:disabled) {
  background: #f1f5f9;
  color: #1e293b;
}

.btn-reset {
  background: transparent;
  border: 1px solid transparent;
  color: #64748b;
}

.btn-reset:hover:not(:disabled) {
  background: #f1f5f9;
  color: #1e293b;
}

.btn-primary {
  background: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  color: #ffffff;
  box-shadow: 0 2px 4px rgba(8, 127, 120, 0.15);
}

.btn-primary:hover:not(:disabled) {
  background: #076d67;
  border-color: #076d67;
}

.btn-primary:disabled,
.btn-cancel:disabled,
.btn-reset:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spin-icon {
  width: 16px;
  height: 16px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.text-danger {
  color: #ef4444;
}
</style>
