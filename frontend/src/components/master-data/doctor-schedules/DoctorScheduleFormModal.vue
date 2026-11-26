<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { fieldControlClass } from '@/components/ui/fieldControlClass'
import lookupService from '@/services/lookup.service'
import { polyclinicService } from '@/services/polyclinic'
import { normalizeApiError } from '@/utils/http-error'
import {
  createDoctorScheduleForm,
  DAY_OPTIONS,
  ensureSavedDoctorOptions,
  ensureSavedPolyclinicOptions,
  getSafeDoctorScheduleErrorMessage,
  toDoctorSchedulePayload,
  validateDoctorScheduleForm,
} from './doctor-schedule.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  schedule: {
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
const form = reactive(createDoctorScheduleForm())
const clientErrors = ref({})

const rawDoctors = ref([])
const rawPolyclinics = ref([])
const loadingLookups = ref(false)
const lookupError = ref('')

const isEdit = computed(() => Boolean(props.schedule))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))

const doctorOptions = computed(() => ensureSavedDoctorOptions(rawDoctors.value, props.schedule))
const polyclinicOptions = computed(() =>
  ensureSavedPolyclinicOptions(rawPolyclinics.value, props.schedule),
)

const isHolidayValue = computed({
  get: () => (form.is_holiday ? '1' : '0'),
  set: (value) => {
    form.is_holiday = value === '1'
  },
})

function extractArray(response) {
  const payload = response?.data?.data ?? response?.data ?? response ?? []
  return Array.isArray(payload) ? payload : []
}

async function loadLookups(schedule) {
  const savedDocId = schedule?.doctor_id
  const needsDoctorFetch =
    rawDoctors.value.length === 0 ||
    (savedDocId && !rawDoctors.value.some((doc) => Number(doc.id) === Number(savedDocId)))
  const needsPolyFetch = rawPolyclinics.value.length === 0

  if (!needsDoctorFetch && !needsPolyFetch) return

  loadingLookups.value = true
  lookupError.value = ''
  try {
    const doctorParams = { per_page: 500, is_active: 1 }
    if (savedDocId) doctorParams.ids = savedDocId

    const [docRes, polyRes] = await Promise.all([
      needsDoctorFetch ? lookupService.getDoctors(doctorParams) : Promise.resolve(null),
      needsPolyFetch
        ? polyclinicService.getPolyclinics({ per_page: 500, is_active: 1 })
        : Promise.resolve(null),
    ])

    if (docRes) rawDoctors.value = extractArray(docRes)
    if (polyRes) rawPolyclinics.value = extractArray(polyRes)
  } catch (error) {
    lookupError.value = getSafeDoctorScheduleErrorMessage(
      normalizeApiError(error, 'Sebagian data referensi dokter/poliklinik gagal dimuat.'),
      'Sebagian data referensi dokter/poliklinik gagal dimuat.',
    )
  } finally {
    loadingLookups.value = false
  }
}

watch(
  [() => props.open, () => props.schedule],
  ([isOpen, schedule]) => {
    if (!isOpen) return
    clientErrors.value = {}
    Object.assign(form, createDoctorScheduleForm(schedule))
    loadLookups(schedule)
  },
  { immediate: true },
)

function clearFieldError(field) {
  if (!clientErrors.value[field]) return
  const next = { ...clientErrors.value }
  delete next[field]
  clientErrors.value = next
}

function handleSubmit() {
  if (props.saving) return
  const validation = validateDoctorScheduleForm(form)
  clientErrors.value = validation
  if (Object.keys(validation).length > 0) return

  emit('submit', toDoctorSchedulePayload(form))
}

function requestClose() {
  if (!props.saving) emit('close')
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Jadwal Dokter' : 'Tambah Jadwal Dokter'"
    description="Atur jadwal praktik dokter berdasarkan poliklinik, hari, dan jam operasional."
    size="md"
    :persistent="saving"
    @close="requestClose"
  >
    <form :id="formId" class="space-y-4" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseAlert v-if="lookupError" tone="warning">
        {{ lookupError }}
      </BaseAlert>

      <BaseField v-slot="field" label="Nama Dokter" required :error="errors.doctor_id">
        <SearchableSelect
          :id="field.id"
          v-model="form.doctor_id"
          :options="doctorOptions"
          :loading="loadingLookups"
          :is-invalid="field.invalid"
          placeholder="Pilih Dokter"
          search-placeholder="Cari nama dokter atau spesialisasi..."
          @update:model-value="clearFieldError('doctor_id')"
        />
      </BaseField>

      <BaseField v-slot="field" label="Poliklinik" required :error="errors.polyclinic_id">
        <SearchableSelect
          :id="field.id"
          v-model="form.polyclinic_id"
          :options="polyclinicOptions"
          :loading="loadingLookups"
          :is-invalid="field.invalid"
          placeholder="Pilih Poliklinik"
          search-placeholder="Cari nama poliklinik..."
          @update:model-value="clearFieldError('polyclinic_id')"
        />
      </BaseField>

      <BaseField v-slot="field" label="Hari Praktik" required :error="errors.day_of_week">
        <select
          :id="field.id"
          v-model.number="form.day_of_week"
          :aria-invalid="field.invalid || undefined"
          :aria-describedby="field['aria-describedby']"
          :class="fieldControlClass({ invalid: field.invalid })"
          @change="clearFieldError('day_of_week')"
        >
          <option value="">-- Pilih Hari --</option>
          <option v-for="day in DAY_OPTIONS" :key="day.value" :value="day.value">
            {{ day.label }}
          </option>
        </select>
      </BaseField>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <BaseField v-slot="field" label="Jam Mulai" required :error="errors.start_time">
          <BaseInput
            v-bind="field"
            v-model="form.start_time"
            type="time"
            @update:model-value="clearFieldError('start_time')"
          />
        </BaseField>

        <BaseField v-slot="field" label="Jam Selesai" required :error="errors.end_time">
          <BaseInput
            v-bind="field"
            v-model="form.end_time"
            type="time"
            @update:model-value="clearFieldError('end_time')"
          />
        </BaseField>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <BaseField v-slot="field" label="Status (Libur)" required :error="errors.is_holiday">
          <select
            :id="field.id"
            v-model="isHolidayValue"
            :aria-invalid="field.invalid || undefined"
            :aria-describedby="field['aria-describedby']"
            :class="fieldControlClass({ invalid: field.invalid })"
            @change="clearFieldError('is_holiday')"
          >
            <option value="0">Praktik</option>
            <option value="1">Libur</option>
          </select>
        </BaseField>

        <BaseField v-slot="field" label="Kuota Online" :error="errors.online_quota">
          <BaseInput
            v-bind="field"
            v-model="form.online_quota"
            type="number"
            min="0"
            placeholder="Kosongkan jika tidak ada batas kuota"
            @update:model-value="clearFieldError('online_quota')"
          />
        </BaseField>
      </div>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="requestClose">Batal</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving">
        {{ isEdit ? 'Simpan Perubahan' : 'Simpan Jadwal' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
