<script setup>
import { reactive, watch } from 'vue'
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  roomClass: {
    type: Object,
    default: null,
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['close', 'submit'])

const form = reactive({
  name: '',
  code: '',
  price: '',
  description: '',
  aplicare_code: '',
  sirs_code: '',
})

const clientErrors = reactive({})

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      if (props.roomClass) {
        form.name = props.roomClass.name || ''
        form.code = props.roomClass.code || ''
        form.price = props.roomClass.price !== null && props.roomClass.price !== undefined
          ? String(props.roomClass.price)
          : ''
        form.description = props.roomClass.description || ''
        form.aplicare_code = props.roomClass.aplicare_code || ''
        form.sirs_code = props.roomClass.sirs_code || ''
      } else {
        form.name = ''
        form.code = ''
        form.price = ''
        form.description = ''
        form.aplicare_code = ''
        form.sirs_code = ''
      }
      Object.keys(clientErrors).forEach((key) => delete clientErrors[key])
    } else {
      form.name = ''
      form.code = ''
      form.price = ''
      form.description = ''
      form.aplicare_code = ''
      form.sirs_code = ''
      Object.keys(clientErrors).forEach((key) => delete clientErrors[key])
    }
  },
  { immediate: true },
)

const formatPriceInput = (event) => {
  let val = event.target.value
  // Allow only digits and periods/commas
  val = val.replace(/[^\d]/g, '')
  if (val === '') {
    form.price = ''
    return
  }
  const num = Number(val)
  form.price = num.toLocaleString('id-ID')
}

const parsePrice = (priceVal) => {
  if (typeof priceVal === 'number') return priceVal
  if (!priceVal) return 0
  const clean = String(priceVal).replace(/[^\d]/g, '')
  return clean ? Number(clean) : 0
}

const validateForm = () => {
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key])
  let isValid = true

  if (!form.name || !form.name.trim()) {
    clientErrors.name = 'Nama kelas wajib diisi.'
    isValid = false
  } else if (form.name.length > 255) {
    clientErrors.name = 'Nama kelas maksimal 255 karakter.'
    isValid = false
  }

  if (!form.code || !form.code.trim()) {
    clientErrors.code = 'Kode kelas wajib diisi.'
    isValid = false
  } else if (form.code.length > 50) {
    clientErrors.code = 'Kode kelas maksimal 50 karakter.'
    isValid = false
  }

  if (form.price === '' || form.price === null || form.price === undefined) {
    clientErrors.price = 'Harga kelas wajib diisi.'
    isValid = false
  } else {
    const numPrice = parsePrice(form.price)
    if (isNaN(numPrice)) {
      clientErrors.price = 'Harga kelas harus berupa angka.'
      isValid = false
    } else if (numPrice < 0) {
      clientErrors.price = 'Harga kelas tidak boleh bernilai negatif.'
      isValid = false
    }
  }

  return isValid
}

const handleSubmit = () => {
  if (!validateForm()) return

  emit('submit', {
    name: form.name.trim(),
    code: form.code.trim(),
    price: parsePrice(form.price),
    description: form.description ? form.description.trim() : null,
    aplicare_code: form.aplicare_code ? form.aplicare_code.trim() : null,
    sirs_code: form.sirs_code ? form.sirs_code.trim() : null,
  })
}

const handleClose = () => {
  emit('close')
}

const getErrorMessage = (field) => {
  if (clientErrors[field]) return clientErrors[field]
  if (props.errors[field]) {
    return Array.isArray(props.errors[field]) ? props.errors[field][0] : props.errors[field]
  }
  return null
}
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="roomClass ? 'Edit Kelas' : 'Tambah Kelas'"
    :submit-text="roomClass ? 'Simpan Perubahan' : 'Simpan'"
    :is-submitting="isSubmitting"
    @close="handleClose"
    @submit="handleSubmit"
  >
    <div class="form-container">
      <!-- 1. Nama Kelas -->
      <div class="form-group">
        <label for="room-class-name-input" class="form-label">
          Nama Kelas <span class="required-mark">*</span>
        </label>
        <input autocomplete="off"
          id="room-class-name-input"
          v-model="form.name"
          type="text"
          class="form-input"
          :class="{ 'has-error': Boolean(getErrorMessage('name')) }"
          placeholder="Masukkan nama kelas"
          maxlength="255"
          :disabled="isSubmitting"
        />
        <span v-if="getErrorMessage('name')" class="error-message">
          {{ getErrorMessage('name') }}
        </span>
      </div>

      <!-- 2. Kode Kelas -->
      <div class="form-group">
        <label for="room-class-code-input" class="form-label">
          Kode Kelas <span class="required-mark">*</span>
        </label>
        <input autocomplete="off"
          id="room-class-code-input"
          v-model="form.code"
          type="text"
          class="form-input"
          :class="{ 'has-error': Boolean(getErrorMessage('code')) }"
          placeholder="Masukkan kode kelas"
          maxlength="50"
          :disabled="isSubmitting"
        />
        <span v-if="getErrorMessage('code')" class="error-message">
          {{ getErrorMessage('code') }}
        </span>
      </div>

      <!-- 3. Harga Kelas -->
      <div class="form-group">
        <label for="room-class-price-input" class="form-label">
          Harga Kelas <span class="required-mark">*</span>
        </label>
        <div class="input-currency-wrapper" :class="{ 'has-error': Boolean(getErrorMessage('price')) }">
          <span class="currency-prefix">Rp</span>
          <input autocomplete="off"
            id="room-class-price-input"
            v-model="form.price"
            type="text"
            class="currency-input"
            placeholder="Masukkan harga kelas"
            :disabled="isSubmitting"
            @input="formatPriceInput"
          />
        </div>
        <span v-if="getErrorMessage('price')" class="error-message">
          {{ getErrorMessage('price') }}
        </span>
      </div>

      <!-- 4. Deskripsi -->
      <div class="form-group">
        <label for="room-class-description-input" class="form-label">Deskripsi</label>
        <textarea
          id="room-class-description-input"
          v-model="form.description"
          class="form-textarea"
          :class="{ 'has-error': Boolean(getErrorMessage('description')) }"
          placeholder="Masukkan deskripsi kelas"
          rows="3"
          :disabled="isSubmitting"
        ></textarea>
        <span v-if="getErrorMessage('description')" class="error-message">
          {{ getErrorMessage('description') }}
        </span>
      </div>

      <!-- 5. Kode Aplicare -->
      <div class="form-group">
        <label for="room-class-aplicare-input" class="form-label">Kode Aplicare</label>
        <input autocomplete="off"
          id="room-class-aplicare-input"
          v-model="form.aplicare_code"
          type="text"
          class="form-input"
          :class="{ 'has-error': Boolean(getErrorMessage('aplicare_code')) }"
          placeholder="Masukkan kode Aplicare"
          maxlength="50"
          :disabled="isSubmitting"
        />
        <span class="helper-text">
          Kode sementara disimpan secara lokal dan belum terintegrasi dengan layanan Aplicare/BPJS.
        </span>
        <span v-if="getErrorMessage('aplicare_code')" class="error-message">
          {{ getErrorMessage('aplicare_code') }}
        </span>
      </div>

      <!-- 6. Kode SIRS -->
      <div class="form-group">
        <label for="room-class-sirs-input" class="form-label">Kode SIRS</label>
        <input autocomplete="off"
          id="room-class-sirs-input"
          v-model="form.sirs_code"
          type="text"
          class="form-input"
          :class="{ 'has-error': Boolean(getErrorMessage('sirs_code')) }"
          placeholder="Masukkan kode SIRS"
          maxlength="50"
          :disabled="isSubmitting"
        />
        <span class="helper-text">
          Kode sementara disimpan secara lokal dan belum terintegrasi dengan layanan SIRS.
        </span>
        <span v-if="getErrorMessage('sirs_code')" class="error-message">
          {{ getErrorMessage('sirs_code') }}
        </span>
      </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.form-container {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-primary, #1e293b);
}

.required-mark {
  color: #ef4444;
}

.form-input,
.form-textarea {
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  background-color: #ffffff;
  color: var(--color-text-primary, #1e293b);
  transition: all 0.2s ease;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary, #0ea5e9);
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}

.form-input.has-error,
.form-textarea.has-error {
  border-color: #ef4444;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.input-currency-wrapper {
  display: flex;
  align-items: center;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  background-color: #ffffff;
  transition: all 0.2s ease;
  overflow: hidden;
}

.input-currency-wrapper:focus-within {
  border-color: var(--color-primary, #0ea5e9);
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}

.input-currency-wrapper.has-error {
  border-color: #ef4444;
}

.currency-prefix {
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  font-weight: 500;
  background-color: var(--color-page-bg, #f8fafc);
  color: var(--color-text-secondary, #64748b);
  border-right: 1px solid var(--color-border-soft, #cbd5e1);
  user-select: none;
}

.currency-input {
  flex: 1;
  border: none;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: var(--color-text-primary, #1e293b);
  background: transparent;
  outline: none;
}

.currency-input:disabled {
  background-color: var(--color-page-bg, #f8fafc);
  cursor: not-allowed;
}

.helper-text {
  font-size: 0.75rem;
  color: var(--color-text-secondary, #64748b);
  line-height: 1.3;
}

.error-message {
  font-size: 0.75rem;
  color: #ef4444;
}
</style>
