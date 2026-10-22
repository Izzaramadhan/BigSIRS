<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  initialName: {
    type: String,
    default: '',
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
  errorMessage: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'submit'])

const nameInput = ref('')
const inputError = ref('')

watch(
  () => [props.isOpen, props.initialName],
  ([newOpen, newName]) => {
    if (newOpen) {
      nameInput.value = newName ? newName.trim() : ''
      inputError.value = ''
    } else {
      nameInput.value = ''
      inputError.value = ''
    }
  },
  { immediate: true },
)

const handleInput = () => {
  inputError.value = ''
}

const handleConfirm = () => {
  if (props.isSubmitting) return

  const normalized = nameInput.value.trim().replace(/\s+/g, ' ')
  if (!normalized) {
    inputError.value = 'Nama kelas wajib diisi.'
    return
  }

  if (normalized.length > 255) {
    inputError.value = 'Nama kelas maksimal 255 karakter.'
    return
  }

  emit('submit', normalized)
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="class-dialog-overlay" @click.self="$emit('close')">
      <div class="class-dialog-card" role="dialog" aria-modal="true" aria-labelledby="dialog-title">
        <!-- Header -->
        <div class="dialog-header">
          <h3 id="dialog-title" class="dialog-title">Tambah Kelas Baru</h3>
          <button
            type="button"
            class="dialog-close-btn"
            aria-label="Tutup"
            :disabled="isSubmitting"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="dialog-body">
          <p class="dialog-info">
            Data kelas &ldquo;<strong>{{ initialName }}</strong>&rdquo; belum tersedia. Tambahkan sebagai data kelas baru?
          </p>

          <div class="dialog-field">
            <label for="room_class_new_name" class="field-label">
              Nama Kelas <span class="text-danger">*</span>
            </label>
            <input
              id="room_class_new_name"
              v-model="nameInput"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': inputError || errorMessage }"
              placeholder="Masukkan nama kelas"
              maxlength="255"
              autocomplete="off"
              :disabled="isSubmitting"
              @input="handleInput"
              @keydown.enter.prevent="handleConfirm"
            />
            <div v-if="inputError || errorMessage" class="field-error">
              {{ inputError || errorMessage }}
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="dialog-footer">
          <button
            type="button"
            class="btn-cancel"
            :disabled="isSubmitting"
            @click="$emit('close')"
          >
            Batal
          </button>
          <button
            type="button"
            class="btn-primary"
            :disabled="isSubmitting"
            @click="handleConfirm"
          >
            <span v-if="isSubmitting" class="spinner-sm"></span>
            {{ isSubmitting ? 'Menyimpan...' : 'Tambah Kelas' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.class-dialog-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1200;
  padding: 1rem;
}

.class-dialog-card {
  background: #ffffff;
  border-radius: 8px;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: dialogFadeIn 0.15s ease-out;
}

@keyframes dialogFadeIn {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.dialog-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.dialog-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-text-navy, #0f172a);
  margin: 0;
}

.dialog-close-btn {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.dialog-close-btn:hover:not(:disabled) {
  color: #0f172a;
  background: #f1f5f9;
}

.dialog-body {
  padding: 1.25rem;
}

.dialog-info {
  font-size: 0.9rem;
  color: #475569;
  line-height: 1.45;
  margin: 0 0 1rem 0;
}

.dialog-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.field-label {
  font-size: 0.85rem;
  font-weight: 500;
  color: #1e293b;
}

.text-danger {
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  font-size: 0.9rem;
  color: #0f172a;
  box-sizing: border-box;
  transition: border-color 0.15s, box-shadow 0.15s;
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

.field-error {
  font-size: 0.8rem;
  color: #ef4444;
}

.dialog-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 0.875rem 1.25rem;
  background: #f8fafc;
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
}

.btn-cancel {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #475569;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-cancel:hover:not(:disabled) {
  background: #f1f5f9;
}

.btn-primary {
  padding: 0.5rem 1.15rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #ffffff;
  background: var(--color-primary, #0f766e);
  border: 1px solid transparent;
  border-radius: 6px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: background 0.15s;
}

.btn-primary:hover:not(:disabled) {
  background: #0d655e;
}

.btn-primary:disabled,
.btn-cancel:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner-sm {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #ffffff;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
