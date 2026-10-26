<script setup>
import { ref, reactive, watch } from 'vue'
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import { buildingsService } from '@/services/master-data/buildings.service'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  floor: {
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
  building_id: '',
  name: '',
  description: '',
})

const buildingOptions = ref([])
const loadingBuildings = ref(false)
const buildingLoadError = ref(null)

const loadBuildings = async () => {
  loadingBuildings.value = true
  buildingLoadError.value = null
  try {
    const response = await buildingsService.getBuildings({
      per_page: -1,
      sort: 'name',
      direction: 'asc',
    })
    const dataList = response.data || []
    const items = Array.isArray(dataList) ? dataList : dataList.data || []
    buildingOptions.value = items.map((b) => ({
      id: b.id,
      name: b.name,
      label: b.name,
      value: b.id,
    }))
  } catch (err) {
    buildingLoadError.value = 'Gagal memuat data gedung. Silakan coba kembali.'
    buildingOptions.value = []
  } finally {
    loadingBuildings.value = false
  }
}

const populateForm = (data) => {
  form.building_id = data.building_id || data.building?.id || ''
  form.name = data.name || ''
  form.description = data.description || ''
}

const resetForm = () => {
  form.building_id = ''
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

    await loadBuildings()

    if (props.floor) {
      populateForm(props.floor)
    } else {
      resetForm()
    }
  },
  { immediate: true },
)

const handleSubmit = () => {
  const payload = {
    building_id: form.building_id ? Number(form.building_id) : '',
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
    :title="floor ? 'Edit Lantai' : 'Tambah Lantai'"
    :submit-text="floor ? 'Simpan Perubahan' : 'Simpan'"
    :is-submitting="isSubmitting"
    size="md"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
      <!-- General error alert -->
      <div v-if="errors.general" class="alert-error">
        {{ errors.general }}
      </div>

      <!-- Notice when no buildings available -->
      <div v-if="!loadingBuildings && buildingOptions.length === 0" class="alert-warning">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
          <strong>Data Gedung belum tersedia.</strong>
          <p class="alert-desc">
            Silakan tambahkan data pada menu <em>Data Gedung</em> terlebih dahulu sebelum menambahkan data lantai.
          </p>
        </div>
      </div>

      <!-- Field 1: Nama Gedung -->
      <div class="form-group">
        <label for="floor-building-id" class="form-label required">Nama Gedung</label>
        <SearchableSelect
          id="floor-building-id"
          v-model="form.building_id"
          :options="buildingOptions"
          :loading="loadingBuildings"
          :disabled="loadingBuildings || buildingOptions.length === 0"
          placeholder="Pilih gedung"
          search-placeholder="Cari nama gedung..."
          empty-text="Tidak ada data gedung ditemukan."
          :is-invalid="!!fieldError('building_id')"
        />
        <div v-if="fieldError('building_id')" class="invalid-feedback">
          {{ fieldError('building_id') }}
        </div>
      </div>

      <!-- Field 2: Nama Lantai -->
      <div class="form-group">
        <label for="floor-name" class="form-label required">Nama Lantai</label>
        <input autocomplete="off"
          id="floor-name"
          v-model="form.name"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': fieldError('name') }"
          placeholder="Masukkan nama lantai"
          required
        />
        <div v-if="fieldError('name')" class="invalid-feedback">
          {{ fieldError('name') }}
        </div>
      </div>

      <!-- Field 3: Deskripsi Lantai -->
      <div class="form-group">
        <label for="floor-description" class="form-label">Deskripsi Lantai</label>
        <textarea
          id="floor-description"
          v-model="form.description"
          class="form-control form-textarea"
          :class="{ 'is-invalid': fieldError('description') }"
          placeholder="Masukkan deskripsi lantai"
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
