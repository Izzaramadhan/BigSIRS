<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import { LoaderCircle } from 'lucide-vue-next'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import { useRoomOptions } from '@/composables/useRoomOptions'
import { BED_NAME_MAX_LENGTH, createBedForm, toBedPayload, validateBedForm } from './bed.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  bed: {
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
const form = reactive(createBedForm())
const clientErrors = ref({})
const {
  options: roomOptions,
  loading: loadingRooms,
  error: roomError,
  load: loadRooms,
} = useRoomOptions()

const isEdit = computed(() => Boolean(props.bed))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))
const canSubmit = computed(() => !loadingRooms.value && roomOptions.value.length > 0)

watch(
  [() => props.open, () => props.bed],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createBedForm(props.bed))
    clientErrors.value = {}
    loadRooms()
  },
  { immediate: true },
)

watch(
  () => form.room_id,
  () => {
    if (clientErrors.value.room_id) {
      const next = { ...clientErrors.value }
      delete next.room_id
      clientErrors.value = next
    }
  },
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
  if (props.saving || !canSubmit.value) return
  clientErrors.value = validateBedForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toBedPayload(form))
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Bed' : 'Tambah Bed'"
    :persistent="saving"
    @close="emit('close')"
  >
    <form :id="formId" class="flex flex-col gap-5" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseField v-slot="field" label="Nama Kamar" required :error="errors.room_id">
        <div
          v-if="loadingRooms"
          class="flex h-10 items-center gap-2 rounded-lg border border-line bg-canvas px-3.5 text-sm text-fg-muted"
        >
          <LoaderCircle class="size-4 animate-spin" aria-hidden="true" />
          Memuat data kamar…
        </div>

        <BaseAlert v-else-if="roomError" tone="danger">
          {{ roomError }}
          <template #action>
            <BaseButton variant="ghost" size="sm" @click="loadRooms">Coba lagi</BaseButton>
          </template>
        </BaseAlert>

        <BaseAlert v-else-if="roomOptions.length === 0" tone="warning">
          Data kamar belum tersedia. Buat data kamar terlebih dahulu.
        </BaseAlert>

        <SearchableSelect
          v-else
          :id="field.id"
          v-model="form.room_id"
          :options="roomOptions"
          placeholder="Pilih kamar"
          search-placeholder="Cari kamar…"
          :is-invalid="field.invalid"
          :disabled="saving"
        />
      </BaseField>

      <BaseField
        v-slot="field"
        label="Nama Bed"
        required
        :error="errors.name"
        :hint="`Maksimal ${BED_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          placeholder="Contoh: Bed 01"
          :maxlength="BED_NAME_MAX_LENGTH"
          :disabled="saving"
        />
      </BaseField>

      <BaseField v-slot="field" label="Deskripsi" :error="errors.description">
        <BaseTextarea
          v-bind="field"
          v-model="form.description"
          placeholder="Opsional, mis. dekat jendela"
          :disabled="saving"
        />
      </BaseField>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="emit('close')">Batal</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving" :disabled="!canSubmit">
        {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
