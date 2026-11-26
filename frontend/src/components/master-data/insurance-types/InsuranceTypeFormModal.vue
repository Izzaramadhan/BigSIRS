<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import {
  createInsuranceTypeForm,
  INACBG_ID_MAX_LENGTH,
  INSURANCE_NAME_MAX_LENGTH,
  toInsuranceTypePayload,
  validateInsuranceTypeForm,
} from './insurance-type.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  insuranceType: {
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
const form = reactive(createInsuranceTypeForm())
const clientErrors = ref({})

const isEdit = computed(() => Boolean(props.insuranceType?.id))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))

watch(
  [() => props.open, () => props.insuranceType],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createInsuranceTypeForm(props.insuranceType))
    clientErrors.value = {}
  },
  { immediate: true },
)

for (const field of ['name', 'is_government', 'inacbg_id']) {
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
  clientErrors.value = validateInsuranceTypeForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toInsuranceTypePayload(form, props.insuranceType))
}

function requestClose() {
  if (!props.saving) emit('close')
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Jenis Asuransi' : 'Tambah Jenis Asuransi'"
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
        label="Jenis Asuransi"
        required
        :error="errors.name"
        :hint="`Maksimal ${INSURANCE_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          placeholder="Misal: BPJS KESEHATAN"
          :maxlength="INSURANCE_NAME_MAX_LENGTH"
        />
      </BaseField>

      <fieldset class="flex flex-col gap-2">
        <legend class="text-sm font-medium text-fg">
          Asuransi Pemerintah <span class="text-danger" aria-hidden="true">*</span>
        </legend>

        <div class="flex items-center gap-6 pt-1">
          <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-fg">
            <input
              v-model="form.is_government"
              type="radio"
              :value="false"
              class="size-4 cursor-pointer accent-primary"
            />
            <span>Tidak</span>
          </label>
          <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-fg">
            <input
              v-model="form.is_government"
              type="radio"
              :value="true"
              class="size-4 cursor-pointer accent-primary"
            />
            <span>Ya</span>
          </label>
        </div>

        <p v-if="errors.is_government" class="text-xs text-danger" role="alert">
          {{ errors.is_government }}
        </p>
      </fieldset>

      <BaseField
        v-slot="field"
        id="inacbg_id"
        label="ID INACBG"
        :error="errors.inacbg_id"
        :hint="`Opsional, maksimal ${INACBG_ID_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.inacbg_id"
          placeholder="Misal: 3"
          :maxlength="INACBG_ID_MAX_LENGTH"
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
