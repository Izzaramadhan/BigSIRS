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
import { useFloorOptions } from '@/composables/useFloorOptions'
import {
  createWardForm,
  toWardPayload,
  validateWardForm,
  WARD_NAME_MAX_LENGTH,
} from './ward.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  ward: {
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
const form = reactive(createWardForm())
const clientErrors = ref({})
const {
  options: floorOptions,
  loading: loadingFloors,
  error: floorError,
  load: loadFloors,
} = useFloorOptions()

const isEdit = computed(() => Boolean(props.ward))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))
const canSubmit = computed(
  () => !loadingFloors.value && !floorError.value && floorOptions.value.length > 0,
)

watch(
  [() => props.open, () => props.ward],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createWardForm(props.ward))
    clientErrors.value = {}
    loadFloors()
  },
  { immediate: true },
)

watch(
  () => form.floor_id,
  () => {
    if (clientErrors.value.floor_id) {
      const next = { ...clientErrors.value }
      delete next.floor_id
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
  clientErrors.value = validateWardForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toWardPayload(form))
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Bangsal' : 'Tambah Bangsal'"
    :persistent="saving"
    @close="emit('close')"
  >
    <form :id="formId" class="flex flex-col gap-5" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseField v-slot="field" label="Nama Lantai" required :error="errors.floor_id">
        <div
          v-if="loadingFloors"
          class="flex h-10 items-center gap-2 rounded-lg border border-line bg-canvas px-3.5 text-sm text-fg-muted"
        >
          <LoaderCircle class="size-4 animate-spin" aria-hidden="true" />
          Memuat data lantai…
        </div>

        <BaseAlert v-else-if="floorError" tone="danger">
          {{ floorError }}
          <template #action>
            <BaseButton variant="ghost" size="sm" @click="loadFloors">Coba lagi</BaseButton>
          </template>
        </BaseAlert>

        <BaseAlert v-else-if="floorOptions.length === 0" tone="warning">
          Data Lantai belum tersedia. Silakan tambahkan data pada menu Data Gedung dan Data Lantai
          terlebih dahulu.
        </BaseAlert>

        <SearchableSelect
          v-else
          :id="field.id"
          v-model="form.floor_id"
          :options="floorOptions"
          placeholder="Pilih lantai"
          search-placeholder="Cari lantai atau gedung…"
          empty-text="Tidak ada data lantai ditemukan."
          :is-invalid="field.invalid"
          :disabled="saving"
        />
      </BaseField>

      <BaseField
        v-slot="field"
        label="Nama Bangsal"
        required
        :error="errors.name"
        :hint="`Maksimal ${WARD_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          placeholder="Contoh: Bangsal Melati"
          :maxlength="WARD_NAME_MAX_LENGTH"
          :disabled="saving"
        />
      </BaseField>

      <BaseField v-slot="field" label="Deskripsi" :error="errors.description">
        <BaseTextarea
          v-bind="field"
          v-model="form.description"
          placeholder="Opsional, mis. bangsal perawatan pasien anak"
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
