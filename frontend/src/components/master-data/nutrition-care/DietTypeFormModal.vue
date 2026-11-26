<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import {
  createDietTypeForm,
  DIET_TYPE_DESCRIPTION_MAX_LENGTH,
  DIET_TYPE_NAME_MAX_LENGTH,
  toDietTypePayload,
  validateDietTypeForm,
} from './diet-type.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  dietType: {
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
const form = reactive(createDietTypeForm())
const clientErrors = ref({})

const isEdit = computed(() => Boolean(props.dietType?.id))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))

watch(
  [() => props.open, () => props.dietType],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createDietTypeForm(props.dietType))
    clientErrors.value = {}
  },
  { immediate: true },
)

for (const field of ['name', 'description']) {
  watch(
    () => form[field],
    () => {
      if (clientErrors.value[field]) {
        const next = { ...clientErrors.value }
        delete next[field]
        clientErrors.value = next
      }
    },
  )
}

function handleSubmit() {
  if (props.saving) return
  clientErrors.value = validateDietTypeForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toDietTypePayload(form))
}

function requestClose() {
  if (!props.saving) emit('close')
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Asuhan Gizi' : 'Tambah Asuhan Gizi'"
    size="md"
    :persistent="saving"
    @close="requestClose"
  >
    <form :id="formId" class="flex flex-col gap-5" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseField
        v-slot="field"
        id="name"
        label="Nama Asuhan Gizi"
        required
        :error="errors.name"
        :hint="`Maksimal ${DIET_TYPE_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          placeholder="Masukkan nama Asuhan Gizi"
          :maxlength="DIET_TYPE_NAME_MAX_LENGTH"
        />
      </BaseField>

      <BaseField
        v-slot="field"
        id="description"
        label="Deskripsi"
        :error="errors.description"
        :hint="`Opsional, maksimal ${DIET_TYPE_DESCRIPTION_MAX_LENGTH} karakter.`"
      >
        <BaseTextarea
          v-bind="field"
          v-model="form.description"
          :rows="3"
          placeholder="Masukkan deskripsi (opsional)"
          :maxlength="DIET_TYPE_DESCRIPTION_MAX_LENGTH"
        />
      </BaseField>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="requestClose">Batal</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving">
        {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
