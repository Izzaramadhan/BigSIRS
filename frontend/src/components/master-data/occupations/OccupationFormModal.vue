<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import {
  createOccupationForm,
  OCCUPATION_NAME_MAX_LENGTH,
  toOccupationPayload,
  validateOccupationForm,
} from './occupation.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  occupation: {
    type: Object,
    default: null,
  },
  saving: {
    type: Boolean,
    default: false,
  },
  /** Error per field dari server (422), sudah dinormalisasi: { name: 'pesan' }. */
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
const form = reactive(createOccupationForm())
const clientErrors = ref({})

const isEdit = computed(() => Boolean(props.occupation))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))

watch(
  [() => props.open, () => props.occupation],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createOccupationForm(props.occupation))
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
  clientErrors.value = validateOccupationForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toOccupationPayload(form))
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Pekerjaan' : 'Tambah Pekerjaan'"
    size="sm"
    :persistent="saving"
    @close="emit('close')"
  >
    <form :id="formId" class="flex flex-col gap-5" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseField
        v-slot="field"
        label="Nama Pekerjaan"
        required
        :error="errors.name"
        :hint="`Maksimal ${OCCUPATION_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          placeholder="Misal: PNS"
          :maxlength="OCCUPATION_NAME_MAX_LENGTH"
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
