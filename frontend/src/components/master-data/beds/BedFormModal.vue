<script setup>
import { ref, reactive, watch, computed } from 'vue'
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import { roomsService } from '@/services/master-data/rooms.service'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  bed: {
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

const emit = defineEmits(['close', 'submit', 'toast'])

const form = reactive({
  room_id: '',
  name: '',
  description: '',
})

const clientErrors = reactive({})

const roomOptions = ref([])
const loadingRooms = ref(false)
const roomLoadError = ref(null)

const isSubmitDisabled = computed(() => {
  return loadingRooms.value || roomOptions.value.length === 0
})

const formatRoomLabel = (room) => {
  const roomName = room.name || 'Kamar'
  const wardName = room.ward?.name || 'Tanpa Bangsal'
  const roomClass = room.room_class || room.class
  const className = roomClass?.name || '(Kelas Belum Ditentukan)'

  return `${roomName} - ${wardName} - ${className}`
}

const loadRooms = async () => {
  loadingRooms.value = true
  roomLoadError.value = null
  try {
    const response = await roomsService.getRooms({
      per_page: -1,
      sort: 'name',
      direction: 'asc',
    })
    const dataList = response.data || []
    const items = Array.isArray(dataList) ? dataList : dataList.data || []

    roomOptions.value = items.map((r) => {
      const displayName = r.display_name || formatRoomLabel(r)
      return {
        id: r.id,
        name: displayName,
        label: displayName,
        value: r.id,
      }
    })
  } catch (err) {
    roomLoadError.value = 'Gagal memuat data kamar. Silakan coba kembali.'
    roomOptions.value = []
  } finally {
    loadingRooms.value = false
  }
}

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      loadRooms()
      if (props.bed) {
        form.room_id = props.bed.room_id || props.bed.room?.id || ''
        form.name = props.bed.name || ''
        form.description = props.bed.description || ''
      } else {
        form.room_id = ''
        form.name = ''
        form.description = ''
      }
      Object.keys(clientErrors).forEach((key) => delete clientErrors[key])
    } else {
      form.room_id = ''
      form.name = ''
      form.description = ''
      Object.keys(clientErrors).forEach((key) => delete clientErrors[key])
    }
  },
  { immediate: true },
)

const validateForm = () => {
  Object.keys(clientErrors).forEach((key) => delete clientErrors[key])
  let isValid = true

  if (!form.room_id) {
    clientErrors.room_id = 'Nama kamar wajib dipilih.'
    isValid = false
  }

  if (!form.name || !form.name.trim()) {
    clientErrors.name = 'Nama bed wajib diisi.'
    isValid = false
  } else if (form.name.length > 100) {
    clientErrors.name = 'Nama bed maksimal 100 karakter.'
    isValid = false
  }

  return isValid
}

const handleSubmit = () => {
  if (!validateForm()) return

  emit('submit', {
    room_id: Number(form.room_id),
    name: form.name.trim(),
    description: form.description ? form.description.trim() : null,
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
    :title="bed ? 'Edit Bed' : 'Tambah Bed'"
    :submit-text="bed ? 'Simpan Perubahan' : 'Simpan'"
    :is-submitting="isSubmitting"
    :is-submit-disabled="isSubmitDisabled"
    @close="handleClose"
    @submit="handleSubmit"
  >
    <div class="form-container">
      <!-- Nama Kamar -->
      <div class="form-group">
        <label for="bed-room-select" class="form-label">
          Nama Kamar <span class="required-mark">*</span>
        </label>

        <div v-if="loadingRooms" class="select-loading">
          <svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="10" stroke-width="4" stroke-dasharray="31.4 31.4" />
          </svg>
          <span>Memuat data kamar...</span>
        </div>

        <div v-else-if="roomLoadError" class="alert-box error">
          <span>{{ roomLoadError }}</span>
          <button type="button" class="btn-retry" @click="loadRooms">Coba Lagi</button>
        </div>

        <div v-else-if="roomOptions.length === 0" class="alert-box warning">
          <span>Data Kamar belum tersedia. Silakan buat Data Kamar terlebih dahulu.</span>
        </div>

        <SearchableSelect
          v-else
          id="bed-room-select"
          v-model="form.room_id"
          :options="roomOptions"
          placeholder="Pilih kamar"
          search-placeholder="Cari kamar..."
          label-key="name"
          value-key="id"
          :disabled="isSubmitting"
          :has-error="Boolean(getErrorMessage('room_id'))"
        />

        <span v-if="getErrorMessage('room_id')" class="error-message">
          {{ getErrorMessage('room_id') }}
        </span>
      </div>

      <!-- Nama Bed -->
      <div class="form-group">
        <label for="bed-name-input" class="form-label">
          Nama Bed <span class="required-mark">*</span>
        </label>
        <input autocomplete="off"
          id="bed-name-input"
          v-model="form.name"
          type="text"
          class="form-input"
          :class="{ 'has-error': Boolean(getErrorMessage('name')) }"
          placeholder="Masukkan nama bed"
          maxlength="100"
          :disabled="isSubmitting"
        />
        <span v-if="getErrorMessage('name')" class="error-message">
          {{ getErrorMessage('name') }}
        </span>
      </div>

      <!-- Deskripsi Bed -->
      <div class="form-group">
        <label for="bed-description-input" class="form-label">Deskripsi Bed</label>
        <textarea
          id="bed-description-input"
          v-model="form.description"
          class="form-textarea"
          :class="{ 'has-error': Boolean(getErrorMessage('description')) }"
          placeholder="Masukkan deskripsi bed"
          rows="3"
          :disabled="isSubmitting"
        ></textarea>
        <span v-if="getErrorMessage('description')" class="error-message">
          {{ getErrorMessage('description') }}
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

.form-input.has-error:focus,
.form-textarea.has-error:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.error-message {
  font-size: 0.75rem;
  color: #ef4444;
}

.select-loading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem;
  background-color: var(--color-page-bg, #f8fafc);
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  font-size: 0.875rem;
  color: var(--color-text-secondary, #64748b);
}

.select-loading .spin {
  width: 16px;
  height: 16px;
  animation: spin 1s linear infinite;
}

.alert-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

.alert-box.warning {
  background-color: #fffbeb;
  border: 1px solid #fef3c7;
  color: #b45309;
}

.alert-box.error {
  background-color: #fef2f2;
  border: 1px solid #fee2e2;
  color: #b91c1c;
}

.btn-retry {
  background: none;
  border: none;
  color: var(--color-primary, #0ea5e9);
  font-weight: 500;
  cursor: pointer;
  padding: 0 0.25rem;
}

.btn-retry:hover {
  text-decoration: underline;
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
