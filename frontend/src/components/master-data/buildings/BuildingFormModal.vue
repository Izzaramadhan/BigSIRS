<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import {
  BUILDING_NAME_MAX_LENGTH,
  createBuildingForm,
  toBuildingPayload,
  validateBuildingForm,
} from './building.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  building: {
    type: Object,
    default: null,
  },
  saving: {
    type: Boolean,
    default: false,
  },
  /** Error per field dari server (422), sudah dinormalisasi: { field: 'pesan' }. */
  serverErrors: {
    type: Object,
    default: () => ({}),
  },
  /** Error umum non-field saat submit gagal. */
  generalError: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'submit'])

const formId = useId()
const form = reactive(createBuildingForm())
const clientErrors = ref({})

const isEdit = computed(() => Boolean(props.building))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))

watch(
  [() => props.open, () => props.building],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createBuildingForm(props.building))
    clientErrors.value = {}
  },
  { immediate: true },
)

watch(
  () => form.name,
  () => {
    if (clientErrors.value.name) {
      const next = { ...clientErrors.value }
      delete next.name
      clientErrors.value = next
    }
  },
)

function handleSubmit() {
  if (props.saving) return
  clientErrors.value = validateBuildingForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toBuildingPayload(form))
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Gedung' : 'Tambah Gedung'"
    :persistent="saving"
    @close="emit('close')"
  >
    <form :id="formId" class="flex flex-col gap-5" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseField
        v-slot="field"
        label="Nama Gedung"
        required
        :error="errors.name"
        :hint="`Maksimal ${BUILDING_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          placeholder="Contoh: Gedung Teratai"
          :maxlength="BUILDING_NAME_MAX_LENGTH"
          :disabled="saving"
        />
      </BaseField>

      <BaseField v-slot="field" label="Deskripsi" :error="errors.description">
        <BaseTextarea
          v-bind="field"
          v-model="form.description"
          placeholder="Opsional, mis. gedung ruang operasi terpadu"
          :disabled="saving"
        />
      </BaseField>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="emit('close')">Batal</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving">
        {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
