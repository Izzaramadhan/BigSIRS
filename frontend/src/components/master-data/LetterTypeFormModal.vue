<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="letterTypeData ? 'Edit Surat' : 'Tambah Surat'"
    :is-submitting="loading"
    size="md"
    @close="$emit('close')"
    @submit="submitForm"
  >
    <div class="modal-form">
      <div v-if="errors.general" class="alert-error">
        {{ errors.general }}
      </div>

      <div class="form-group">
        <label for="name" class="form-label required">Nama Surat</label>
        <input 
          type="text" 
          id="name" 
          v-model="form.name" 
          class="form-control" 
          :class="{ 'is-invalid': fieldError('name') }"
          placeholder="Masukkan nama surat"
          required
        />
        <div v-if="fieldError('name')" class="invalid-feedback">{{ fieldError('name') }}</div>
      </div>

      <div class="form-group">
        <label for="description" class="form-label">Deskripsi</label>
        <textarea 
          id="description" 
          v-model="form.description" 
          class="form-control form-textarea" 
          :class="{ 'is-invalid': fieldError('description') }"
          placeholder="Masukkan deskripsi (opsional)"
          rows="3"
        ></textarea>
        <div v-if="fieldError('description')" class="invalid-feedback">{{ fieldError('description') }}</div>
      </div>
      
      <div class="form-group">
        <label for="legacy_resource" class="form-label">Resource</label>
        <input 
          type="text" 
          id="legacy_resource" 
          v-model="form.legacy_resource" 
          class="form-control" 
          :class="{ 'is-invalid': fieldError('legacy_resource') }"
          placeholder="Masukkan resource"
        />
        <div v-if="fieldError('legacy_resource')" class="invalid-feedback">{{ fieldError('legacy_resource') }}</div>
      </div>
    </div>
  </MasterDataFormModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  letterTypeData: {
    type: Object,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
  name: '',
  description: '',
  legacy_resource: null
});

const isEdit = ref(false);

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.letterTypeData) {
      isEdit.value = true;
      form.value = {
        name: props.letterTypeData.name,
        description: props.letterTypeData.description || '',
        legacy_resource: props.letterTypeData.legacy_resource || null
      };
    } else {
      isEdit.value = false;
      form.value = {
        name: '',
        description: '',
        legacy_resource: null
      };
    }
  }
});

const closeModal = () => {
  emit('close');
};

const submitForm = () => {
  emit('submit', form.value);
};

const fieldError = (field) => props.errors?.[field]?.[0] || null;
</script>

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

.bg-gray-100 {
  background-color: #f3f4f6;
}

.text-gray-500 {
  color: #6b7280;
}
</style>
