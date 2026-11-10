<script setup>
import { ref, reactive, watch, computed, onMounted, onUnmounted } from 'vue';

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
const officerContainerRef = ref(null);
const officerSearch = ref('');
const isOfficerDropdownOpen = ref(false);

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

// Normalize officer list to { id, name, nip }
const normalizedOfficers = computed(() => {
  return props.officers.map((item, index) => {
    if (typeof item === 'object' && item !== null) {
      return {
        id: item.id || index + 1,
        name: item.name || item.officerName || 'Petugas ' + (index + 1),
        nip: item.nip || ''
      };
    }
    return {
      id: index + 1,
      name: String(item),
      nip: ''
    };
  });
});

const filteredOfficers = computed(() => {
  const q = officerSearch.value.trim().toLowerCase();
  if (!q) return normalizedOfficers.value;
  return normalizedOfficers.value.filter(
    (o) => o.name.toLowerCase().includes(q) || (o.nip && o.nip.toLowerCase().includes(q))
  );
});

const selectOfficer = (officer) => {
  formData.officerId = officer.id;
  formData.officerName = officer.name;
  officerSearch.value = officer.name;
  isOfficerDropdownOpen.value = false;
  if (errors.value.officer) {
    delete errors.value.officer;
  }
};

const handleOfficerInput = (e) => {
  const val = e.target.value;
  officerSearch.value = val;
  formData.officerName = val;
  if (errors.value.officer) {
    delete errors.value.officer;
  }
  const match = normalizedOfficers.value.find(
    (o) => o.name.toLowerCase() === val.trim().toLowerCase()
  );
  if (match) {
    formData.officerId = match.id;
  } else {
    formData.officerId = '';
  }
  isOfficerDropdownOpen.value = true;
};

const handleClickOutside = (event) => {
  if (officerContainerRef.value && !officerContainerRef.value.contains(event.target)) {
    isOfficerDropdownOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside);
});

const initForm = () => {
  errors.value = {};
  isOfficerDropdownOpen.value = false;
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
    officerSearch.value = formData.officerName;

    // If officerId is not set, try to match by name
    if (!formData.officerId && formData.officerName) {
      const match = normalizedOfficers.value.find((o) => o.name === formData.officerName);
      if (match) formData.officerId = match.id;
    }

    formData.notes = props.issueToEdit.notes || (props.issueToEdit.purpose !== 'Kebutuhan Internal' ? props.issueToEdit.purpose : '') || '';
  } else {
    formData.date = getTodayDate();
    formData.officerId = '';
    formData.officerName = '';
    officerSearch.value = '';
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
  () => props.issueToEdit,
  () => {
    if (props.isOpen) initForm();
  }
);

const handleReset = () => {
  if (props.issueToEdit) {
    initForm();
  } else {
    formData.date = getTodayDate();
    formData.officerId = '';
    formData.officerName = '';
    officerSearch.value = '';
    formData.notes = '';
    errors.value = {};
    isOfficerDropdownOpen.value = false;
  }
};

const validate = () => {
  const errs = {};
  if (!formData.date) {
    errs.date = 'Tanggal wajib diisi.';
  }
  if (!formData.officerName || !formData.officerName.trim()) {
    errs.officer = 'Nama petugas wajib dipilih atau diisi.';
  }
  errors.value = errs;
  return Object.keys(errs).length === 0;
};

const handleSubmit = () => {
  if (!validate()) return;

  let matchedId = formData.officerId;
  if (!matchedId && formData.officerName) {
    const match = normalizedOfficers.value.find(
      (o) => o.name.toLowerCase() === formData.officerName.trim().toLowerCase()
    );
    if (match) {
      matchedId = match.id;
    }
  }

  const payload = {
    date: formData.date,
    issue_date: formData.date,
    officer_id: matchedId ? Number(matchedId) : null,
    officer_name: formData.officerName ? formData.officerName.trim() : undefined,
    notes: formData.notes ? formData.notes.trim() : '',
    purpose: formData.notes ? formData.notes.trim() : 'Kebutuhan Internal'
  };

  isOfficerDropdownOpen.value = false;
  emit('submit', payload);
};

const handleClose = () => {
  isOfficerDropdownOpen.value = false;
  emit('close');
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="handleClose">
      <div class="modal-card">
        <!-- Modal Header -->
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
            <h3 class="modal-title">
              {{ issueToEdit ? 'Edit Pengeluaran' : 'Tambah Pengeluaran' }}
            </h3>
          </div>
          <button
            type="button"
            class="btn-close"
            :disabled="isSubmitting"
            aria-label="Tutup modal"
            @click="handleClose"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Modal Form Body -->
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

            <!-- 2. Petugas (Searchable Select matching PosSaleFormModal) -->
            <div ref="officerContainerRef" class="form-group relative">
              <label for="form-input-officer" class="form-label">
                Petugas <span class="text-danger">*</span>
              </label>
              <div class="searchable-officer-wrapper">
                <input
                  id="form-input-officer"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors.officer }"
                  :value="officerSearch"
                  placeholder="Ketik atau pilih nama petugas..."
                  autocomplete="off"
                  @input="handleOfficerInput"
                  @focus="isOfficerDropdownOpen = true"
                />
                <button
                  type="button"
                  class="dropdown-toggle-btn"
                  tabindex="-1"
                  @click="isOfficerDropdownOpen = !isOfficerDropdownOpen"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>
              </div>

              <!-- Officer Dropdown List -->
              <div
                v-if="isOfficerDropdownOpen"
                class="officer-dropdown-menu"
              >
                <div
                  v-if="filteredOfficers.length === 0"
                  class="dropdown-empty-item text-muted"
                >
                  {{ officerSearch ? `Gunakan "${officerSearch}" sebagai petugas` : 'Tidak ada data petugas' }}
                </div>
                <div
                  v-for="officer in filteredOfficers"
                  :key="officer.id"
                  class="dropdown-item"
                  :class="{ selected: officer.id === formData.officerId }"
                  @click="selectOfficer(officer)"
                >
                  <span class="officer-name font-semibold text-navy">{{ officer.name }}</span>
                  <span v-if="officer.nip" class="officer-nip text-muted text-xs">({{ officer.nip }})</span>
                </div>
              </div>
              <span v-if="errors.officer" class="invalid-feedback">
                {{ errors.officer }}
              </span>
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
                rows="3"
                maxlength="1000"
                placeholder="Masukkan keperluan atau catatan pengeluaran..."
              ></textarea>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer">
            <button
              type="button"
              class="btn-cancel"
              :disabled="isSubmitting"
              @click="handleClose"
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
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
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
  background-color: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1.25rem;
}

.modal-card {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  overflow: visible;
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
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
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
  position: relative;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  position: relative;
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

.searchable-officer-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.searchable-officer-wrapper .form-control {
  padding-right: 2.25rem;
}

.dropdown-toggle-btn {
  position: absolute;
  right: 0.5rem;
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dropdown-toggle-btn svg {
  width: 16px;
  height: 16px;
}

.officer-dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  max-height: 200px;
  overflow-y: auto;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  z-index: 1050;
}

.dropdown-item {
  padding: 0.65rem 0.85rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  cursor: pointer;
  transition: background 0.15s ease;
}

.dropdown-item:hover {
  background-color: #f1f5f9;
}

.dropdown-item.selected {
  background-color: #f0fdf4;
  color: #087f78;
}

.dropdown-empty-item {
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
}

.form-control,
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
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.15);
}

.form-textarea {
  resize: vertical;
  min-height: 85px;
}

.is-invalid {
  border-color: #ef4444 !important;
}

.invalid-feedback {
  font-size: 0.78rem;
  color: #ef4444;
}

.text-danger {
  color: #ef4444;
}

.text-navy {
  color: #0f172a;
}

.text-muted {
  color: #64748b;
}

.text-xs {
  font-size: 0.75rem;
}

.font-semibold {
  font-weight: 600;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.15rem 1.5rem;
  border-top: 1px solid #f1f5f9;
  background: #f8fafc;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}

.btn-cancel {
  padding: 0.55rem 1.15rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 6px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-cancel:hover:not(:disabled) {
  background: #f1f5f9;
  color: #1e293b;
}

.btn-reset {
  padding: 0.55rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 6px;
  background: transparent;
  border: 1px solid transparent;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-reset:hover:not(:disabled) {
  background: #f1f5f9;
  color: #1e293b;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 6px;
  background-color: var(--color-primary, #087f78);
  border: 1px solid transparent;
  color: #ffffff;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-primary:hover:not(:disabled) {
  background-color: #066660;
}

.btn-primary:disabled,
.btn-cancel:disabled,
.btn-reset:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary svg {
  width: 16px;
  height: 16px;
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
</style>
