<script setup>
import { ref, reactive, watch, computed } from 'vue'
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import { floorsService } from '@/services/master-data/floors.service'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  ward: {
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
  floor_id: '',
  name: '',
  description: '',
})

const floorOptions = ref([])
const loadingFloors = ref(false)
const floorLoadError = ref(null)

const isSubmitDisabled = computed(() => {
  return loadingFloors.value || floorOptions.value.length === 0
})

const loadFloors = async () => {
  loadingFloors.value = true
  floorLoadError.value = null
  try {
    const response = await floorsService.getFloors({
      per_page: -1,
      sort: 'name',
      direction: 'asc',
    })
    const dataList = response.data || []
    const items = Array.isArray(dataList) ? dataList : dataList.data || []
    floorOptions.value = items.map((f) => {
      let displayName = f.display_name
      if (!displayName) {
        if (f.building?.name) {
          displayName = `${f.name} - ${f.building.name}`
        } else {
          displayName = f.name
        }
      }
      return {
        id: f.id,
        name: displayName,
        label: displayName,
        value: f.id,
        building_id: f.building_id || f.building?.id,
        building_name: f.building?.name,
      }
    })
  } catch (err) {
    floorLoadError.value = 'Gagal memuat data lantai. Silakan coba kembali.'
    floorOptions.value = []
  } finally {
    loadingFloors.value = false
  }
}

const populateForm = (data) => {
  form.floor_id = data.floor_id || data.floor?.id || ''
  form.name = data.name || ''
  form.description = data.description || ''
}

const resetForm = () => {
  form.floor_id = ''
  form.name = ''
  form.description = ''
}

watch(
  () => props.isOpen,
  async (isOpen) => {
    if (!isOpen) {
      resetForm()
      return
    }

    await loadFloors()

    if (props.ward) {
      populateForm(props.ward)
    } else {
      resetForm()
    }
  },
  { immediate: true },
)

const handleSubmit = () => {
  if (isSubmitDisabled.value) return

  const payload = {
    floor_id: form.floor_id ? Number(form.floor_id) : '',
    name: form.name.trim(),
    description: form.description ? form.description.trim() : null,
  }
  emit('submit', payload)
}

const fieldError = (field) => props.errors?.[field]?.[0] || null
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="ward ? 'Edit Bangsal' : 'Tambah Bangsal'"
    :submit-text="ward ? 'Simpan Perubahan' : 'Simpan'"
    :is-submitting="isSubmitting"
    :is-submit-disabled="isSubmitDisabled"
    size="md"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
      <!-- General error alert -->
      <div v-if="errors.general" class="alert-error">
        {{ errors.general }}
      </div>

      <!-- Notice when no floors available -->
      <div v-if="!loadingFloors && floorOptions.length === 0" class="alert-warning">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
          <strong>Data Lantai belum tersedia.</strong>
          <p class="alert-desc">
            Silakan tambahkan data pada menu <em>Data Gedung</em> dan <em>Data Lantai</em> terlebih dahulu sebelum menambahkan data bangsal.
          </p>
        </div>
      </div>

      <!-- Field 1: Nama Lantai -->
      <div class="form-group">
        <label for="ward-floor-id" class="form-label required">Nama Lantai</label>
        <SearchableSelect
          id="ward-floor-id"
          v-model="form.floor_id"
          :options="floorOptions"
          :loading="loadingFloors"
          :disabled="loadingFloors || floorOptions.length === 0"
          placeholder="Pilih lantai"
          search-placeholder="Cari lantai atau gedung..."
          empty-text="Tidak ada data lantai ditemukan."
          :is-invalid="!!fieldError('floor_id')"
        />
        <div v-if="fieldError('floor_id')" class="invalid-feedback">
          {{ fieldError('floor_id') }}
        </div>
      </div>

      <!-- Field 2: Nama Bangsal -->
      <div class="form-group">
        <label for="ward-name" class="form-label required">Nama Bangsal</label>
        <input
          id="ward-name"
          v-model="form.name"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': fieldError('name') }"
          placeholder="Masukkan nama bangsal"
          required
        />
        <div v-if="fieldError('name')" class="invalid-feedback">
          {{ fieldError('name') }}
        </div>
      </div>

      <!-- Field 3: Deskripsi Bangsal -->
      <div class="form-group">
        <label for="ward-description" class="form-label">Deskripsi Bangsal</label>
        <textarea
          id="ward-description"
          v-model="form.description"
          class="form-control form-textarea"
          :class="{ 'is-invalid': fieldError('description') }"
          placeholder="Masukkan deskripsi bangsal"
          rows="3"
        ></textarea>
        <div v-if="fieldError('description')" class="invalid-feedback">
          {{ fieldError('description') }}
        </div>
      </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.alert-error {
  background-color: #fee2e2;
  border-left: 4px solid #ef4444;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  font-size: 0.9rem;
}

.alert-warning {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  background-color: #fffbeb;
  border: 1px solid #fef3c7;
  border-left: 4px solid #f59e0b;
  color: #92400e;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

.alert-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  margin-top: 2px;
}

.alert-desc {
  margin: 0.25rem 0 0 0;
  font-size: 0.825rem;
  color: #78350f;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 500;
  font-size: 0.9rem;
  color: var(--color-text-navy);
}

.required::after {
  content: ' *';
  color: #ef4444;
}

.form-control {
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.95rem;
  transition: all 0.2s;
  background-color: #fff;
  width: 100%;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.is-invalid {
  border-color: #ef4444;
}

.is-invalid:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.invalid-feedback {
  font-size: 0.8rem;
  color: #ef4444;
  margin-top: 0.25rem;
}
</style>
