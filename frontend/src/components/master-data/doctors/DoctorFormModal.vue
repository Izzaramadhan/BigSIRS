<script setup>
import { computed, nextTick, onBeforeUnmount, reactive, ref, useId, watch } from 'vue'
import { LoaderCircle, Trash2, Upload, X } from 'lucide-vue-next'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import { fieldControlClass } from '@/components/ui/fieldControlClass'
import lookupService from '@/services/lookup.service'
import doctorService from '@/services/master-data/doctors.service'
import { normalizeApiError } from '@/utils/http-error'
import {
  BLOOD_TYPE_OPTIONS,
  createDoctorForm,
  DOCTOR_DPJP_MAX_LENGTH,
  DOCTOR_IHS_MAX_LENGTH,
  DOCTOR_NAME_MAX_LENGTH,
  DOCTOR_NATIONAL_ID_MAX_LENGTH,
  DOCTOR_PHONE_MAX_LENGTH,
  DOCTOR_POSTAL_CODE_MAX_LENGTH,
  DOCTOR_SIP_MAX_LENGTH,
  DOCTOR_STR_MAX_LENGTH,
  GENDER_OPTIONS,
  getSafeDoctorErrorMessage,
  hasDoctorDetailPayload,
  MARITAL_STATUS_OPTIONS,
  NATIONALITY_OPTIONS,
  RELIGION_OPTIONS,
  resolveDoctorFieldError,
  toDoctorPayload,
  validateDoctorForm,
  validateDoctorSignatureFile,
} from './doctor.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  doctor: {
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
const form = reactive(createDoctorForm())
const clientErrors = ref({})

const loadingDetail = ref(false)
const detailError = ref('')
const loadingLookups = ref(false)
const lookupError = ref('')

const specializationOptions = ref([])
const educationOptions = ref([])
const occupationOptions = ref([])
const provinceOptions = ref([])
const regencyOptions = ref([])
const districtOptions = ref([])
const villageOptions = ref([])

const loadingRegencies = ref(false)
const loadingDistricts = ref(false)
const loadingVillages = ref(false)

const fileInputRef = ref(null)
const newPreviewUrl = ref('')
const objectPreviewUrl = ref('')
let isHydrating = false
let hydrationToken = 0

const isEdit = computed(() => Boolean(props.doctor))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))

const religionSelectOptions = computed(() => {
  const current = form.person.religion
  if (current && !RELIGION_OPTIONS.includes(current)) {
    return [...RELIGION_OPTIONS, current]
  }
  return RELIGION_OPTIONS
})

const activePreviewUrl = computed(() => {
  if (newPreviewUrl.value) return newPreviewUrl.value
  if (!form.remove_signature && form.signature_url) return form.signature_url
  return ''
})

function fieldErr(...keys) {
  return resolveDoctorFieldError(errors.value, ...keys)
}

function clearFieldError(...keys) {
  const next = { ...clientErrors.value }
  let changed = false
  for (const key of keys) {
    if (key in next) {
      delete next[key]
      changed = true
    }
  }
  if (changed) clientErrors.value = next
}

function revokeObjectPreview() {
  if (
    objectPreviewUrl.value &&
    typeof URL !== 'undefined' &&
    typeof URL.revokeObjectURL === 'function'
  ) {
    URL.revokeObjectURL(objectPreviewUrl.value)
  }
  objectPreviewUrl.value = ''
}

function resetPreviewState() {
  revokeObjectPreview()
  newPreviewUrl.value = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
}

function toArray(payload) {
  if (Array.isArray(payload)) return payload
  return Array.isArray(payload?.data) ? payload.data : []
}

async function ensureStaticLookups(editMode) {
  loadingLookups.value = true
  lookupError.value = ''
  try {
    const specParams = editMode ? {} : { is_active: 1 }
    const [specializations, educations, occupations, provinces] = await Promise.all([
      lookupService.getSpecializations(specParams),
      lookupService.getEducations(),
      lookupService.getOccupations(),
      lookupService.getProvinces(),
    ])
    specializationOptions.value = toArray(specializations)
    educationOptions.value = toArray(educations)
    occupationOptions.value = toArray(occupations)
    provinceOptions.value = toArray(provinces)
  } catch (error) {
    lookupError.value = getSafeDoctorErrorMessage(
      normalizeApiError(error, 'Sebagian data referensi gagal dimuat.'),
      'Sebagian data referensi gagal dimuat.',
    )
  } finally {
    loadingLookups.value = false
  }
}

async function loadRegencies(provinceId) {
  if (!provinceId) {
    regencyOptions.value = []
    return
  }
  loadingRegencies.value = true
  try {
    regencyOptions.value = toArray(await lookupService.getRegencies({ province_id: provinceId }))
  } catch {
    regencyOptions.value = []
  } finally {
    loadingRegencies.value = false
  }
}

async function loadDistricts(regencyId) {
  if (!regencyId) {
    districtOptions.value = []
    return
  }
  loadingDistricts.value = true
  try {
    districtOptions.value = toArray(await lookupService.getDistricts({ regency_id: regencyId }))
  } catch {
    districtOptions.value = []
  } finally {
    loadingDistricts.value = false
  }
}

async function loadVillages(districtId) {
  if (!districtId) {
    villageOptions.value = []
    return
  }
  loadingVillages.value = true
  try {
    villageOptions.value = toArray(await lookupService.getVillages({ district_id: districtId }))
  } catch {
    villageOptions.value = []
  } finally {
    loadingVillages.value = false
  }
}

watch(
  () => form.person.province_id,
  async (newVal) => {
    if (isHydrating) return
    form.person.regency_id = ''
    form.person.district_id = ''
    form.person.village_id = ''
    regencyOptions.value = []
    districtOptions.value = []
    villageOptions.value = []
    await loadRegencies(newVal)
  },
)

watch(
  () => form.person.regency_id,
  async (newVal) => {
    if (isHydrating) return
    form.person.district_id = ''
    form.person.village_id = ''
    districtOptions.value = []
    villageOptions.value = []
    await loadDistricts(newVal)
  },
)

watch(
  () => form.person.district_id,
  async (newVal) => {
    if (isHydrating) return
    form.person.village_id = ''
    villageOptions.value = []
    await loadVillages(newVal)
  },
)

function assignFormState(nextForm) {
  form.employee_id = nextForm.employee_id
  Object.assign(form.person, nextForm.person)
  Object.assign(form.professional, nextForm.professional)
  form.signature = nextForm.signature
  form.signature_url = nextForm.signature_url
  form.remove_signature = nextForm.remove_signature
}

async function initializeModal(isOpen, doctor) {
  const token = ++hydrationToken
  if (!isOpen) {
    resetPreviewState()
    return
  }

  clientErrors.value = {}
  detailError.value = ''
  resetPreviewState()
  regencyOptions.value = []
  districtOptions.value = []
  villageOptions.value = []

  if (!doctor?.id) {
    isHydrating = true
    assignFormState(createDoctorForm())
    await ensureStaticLookups(false)
    await nextTick()
    if (token === hydrationToken) isHydrating = false
    return
  }

  loadingDetail.value = true
  isHydrating = true
  try {
    const detailPromise = hasDoctorDetailPayload(doctor)
      ? Promise.resolve(doctor)
      : doctorService.getDoctor(doctor.id)

    const [, detail] = await Promise.all([ensureStaticLookups(true), detailPromise])
    if (token !== hydrationToken || !props.open) return

    const hydrated = createDoctorForm(detail, specializationOptions.value)
    assignFormState(hydrated)

    await Promise.all([
      loadRegencies(hydrated.person.province_id),
      loadDistricts(hydrated.person.regency_id),
      loadVillages(hydrated.person.district_id),
    ])
  } catch (error) {
    if (token !== hydrationToken) return
    detailError.value = getSafeDoctorErrorMessage(
      normalizeApiError(error, 'Detail data dokter gagal dimuat.'),
      'Detail data dokter gagal dimuat.',
    )
  } finally {
    if (token === hydrationToken) {
      loadingDetail.value = false
      await nextTick()
      isHydrating = false
    }
  }
}

watch(
  [() => props.open, () => props.doctor],
  ([isOpen, doctor]) => {
    initializeModal(isOpen, doctor)
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  revokeObjectPreview()
})

function setPreviewFromFile(file) {
  revokeObjectPreview()
  if (typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function') {
    const url = URL.createObjectURL(file)
    objectPreviewUrl.value = url
    newPreviewUrl.value = url
    return
  }

  if (typeof FileReader !== 'undefined') {
    const reader = new FileReader()
    reader.onload = (event) => {
      newPreviewUrl.value = String(event.target?.result ?? '')
    }
    reader.readAsDataURL(file)
  }
}

function handleFileChange(event) {
  const file = event.target?.files?.[0]
  if (!file) return

  const fileError = validateDoctorSignatureFile(file)
  if (fileError) {
    clientErrors.value = { ...clientErrors.value, signature: fileError }
    if (event.target) event.target.value = ''
    return
  }

  clearFieldError('signature')
  form.signature = file
  form.remove_signature = false
  setPreviewFromFile(file)
}

function cancelNewFile() {
  form.signature = null
  clearFieldError('signature')
  resetPreviewState()
}

function removeSignature() {
  form.signature = null
  form.remove_signature = true
  resetPreviewState()
}

function undoRemoveSignature() {
  form.remove_signature = false
}

function handleSubmit() {
  if (props.saving || loadingDetail.value || detailError.value) return
  const validation = validateDoctorForm(form)
  clientErrors.value = validation
  if (Object.keys(validation).length > 0) return

  emit('submit', toDoctorPayload(form))
}

function requestClose() {
  if (!props.saving) emit('close')
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Data Dokter' : 'Tambah Data Dokter'"
    description="Lengkapi identitas person/pegawai, alamat dan kontak, serta profil profesional dokter."
    size="lg"
    :persistent="saving"
    @close="requestClose"
  >
    <div v-if="loadingDetail" class="flex items-center justify-center gap-2 py-12 text-sm text-fg-muted">
      <LoaderCircle class="size-4 animate-spin text-primary" aria-hidden="true" />
      <span>Memuat detail data dokter...</span>
    </div>

    <form v-else :id="formId" class="space-y-6" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="detailError" tone="danger">
        {{ detailError }}
      </BaseAlert>

      <BaseAlert v-else-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseAlert v-if="lookupError" tone="warning">
        {{ lookupError }}
      </BaseAlert>

      <section class="space-y-4">
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-fg">
          1. Identitas Person / Pegawai
        </h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <BaseField
            v-slot="field"
            label="Nomor KTP (NIK)"
            :error="fieldErr('person.national_id', 'national_id')"
          >
            <BaseInput
              v-bind="field"
              v-model="form.person.national_id"
              placeholder="16 digit NIK"
              :maxlength="DOCTOR_NATIONAL_ID_MAX_LENGTH"
              @update:model-value="clearFieldError('person.national_id', 'national_id')"
            />
          </BaseField>

          <BaseField
            v-slot="field"
            label="Nama Lengkap (beserta gelar)"
            required
            :error="fieldErr('person.name', 'name')"
          >
            <BaseInput
              v-bind="field"
              v-model="form.person.name"
              placeholder="Contoh: dr. Budi Santoso, Sp.A"
              :maxlength="DOCTOR_NAME_MAX_LENGTH"
              @update:model-value="clearFieldError('person.name', 'name')"
            />
          </BaseField>

          <BaseField
            v-slot="field"
            label="Tempat Lahir"
            :error="fieldErr('person.birth_place', 'birth_place')"
          >
            <BaseInput
              v-bind="field"
              v-model="form.person.birth_place"
              placeholder="Contoh: Surabaya"
            />
          </BaseField>

          <BaseField
            v-slot="field"
            label="Tanggal Lahir"
            :error="fieldErr('person.birth_date', 'birth_date')"
          >
            <BaseInput v-bind="field" v-model="form.person.birth_date" type="date" />
          </BaseField>

          <BaseField
            v-slot="field"
            label="Jenis Kelamin"
            required
            :error="fieldErr('person.gender', 'gender')"
          >
            <select
              :id="field.id"
              v-model="form.person.gender"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
              @change="clearFieldError('person.gender', 'gender')"
            >
              <option value="">-- Pilih Jenis Kelamin --</option>
              <option v-for="item in GENDER_OPTIONS" :key="item.value" :value="item.value">
                {{ item.label }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="field"
            label="Golongan Darah"
            :error="fieldErr('person.blood_type', 'blood_type')"
          >
            <select
              :id="field.id"
              v-model="form.person.blood_type"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih Golongan Darah --</option>
              <option v-for="item in BLOOD_TYPE_OPTIONS" :key="item" :value="item">
                {{ item === 'Unknown' ? 'Tidak Diketahui' : item }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="field" label="Agama" :error="fieldErr('person.religion', 'religion')">
            <select
              :id="field.id"
              v-model="form.person.religion"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih Agama --</option>
              <option v-for="item in religionSelectOptions" :key="item" :value="item">
                {{ item }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="field"
            label="Status Perkawinan"
            :error="fieldErr('person.marital_status', 'marital_status')"
          >
            <select
              :id="field.id"
              v-model="form.person.marital_status"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih Status --</option>
              <option v-for="item in MARITAL_STATUS_OPTIONS" :key="item" :value="item">
                {{ item }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="field"
            label="Kebangsaan"
            :error="fieldErr('person.nationality', 'nationality')"
          >
            <select
              :id="field.id"
              v-model="form.person.nationality"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option v-for="item in NATIONALITY_OPTIONS" :key="item" :value="item">
                {{ item }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="field"
            label="Alergi"
            :error="fieldErr('person.allergies', 'allergies')"
            class="sm:col-span-2"
          >
            <BaseTextarea
              v-bind="field"
              v-model="form.person.allergies"
              :rows="2"
              placeholder="Tuliskan alergi obat, makanan, atau lainnya jika diketahui"
            />
          </BaseField>

          <BaseField
            v-slot="field"
            label="Pendidikan"
            :error="fieldErr('person.education_id', 'education_id')"
          >
            <select
              :id="field.id"
              v-model="form.person.education_id"
              :disabled="loadingLookups"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih Pendidikan --</option>
              <option v-for="edu in educationOptions" :key="edu.id" :value="edu.id">
                {{ edu.name }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="field"
            label="Pekerjaan"
            :error="fieldErr('person.occupation_id', 'occupation_id')"
          >
            <select
              :id="field.id"
              v-model="form.person.occupation_id"
              :disabled="loadingLookups"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih Pekerjaan --</option>
              <option v-for="occ in occupationOptions" :key="occ.id" :value="occ.id">
                {{ occ.name }}
              </option>
            </select>
          </BaseField>
        </div>
      </section>

      <section class="space-y-4">
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-fg">
          2. Alamat &amp; Kontak
        </h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <BaseField
            v-slot="field"
            label="Alamat Lengkap (Jalan/Blok/RT/RW)"
            :error="fieldErr('person.address', 'address')"
            class="sm:col-span-2"
          >
            <BaseTextarea v-bind="field" v-model="form.person.address" :rows="2" />
          </BaseField>

          <BaseField
            v-slot="field"
            label="Provinsi"
            :error="fieldErr('person.province_id', 'province_id')"
          >
            <select
              :id="field.id"
              v-model="form.person.province_id"
              :disabled="loadingLookups"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih Provinsi --</option>
              <option v-for="prov in provinceOptions" :key="prov.id" :value="prov.id">
                {{ prov.name }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="field"
            label="Kabupaten / Kota"
            :error="fieldErr('person.regency_id', 'regency_id')"
          >
            <select
              :id="field.id"
              v-model="form.person.regency_id"
              :disabled="!form.person.province_id || loadingRegencies"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">
                {{ loadingRegencies ? 'Memuat kabupaten/kota...' : '-- Pilih Kota/Kab --' }}
              </option>
              <option v-for="reg in regencyOptions" :key="reg.id" :value="reg.id">
                {{ reg.name }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="field"
            label="Kecamatan"
            :error="fieldErr('person.district_id', 'district_id')"
          >
            <select
              :id="field.id"
              v-model="form.person.district_id"
              :disabled="!form.person.regency_id || loadingDistricts"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">
                {{ loadingDistricts ? 'Memuat kecamatan...' : '-- Pilih Kecamatan --' }}
              </option>
              <option v-for="dist in districtOptions" :key="dist.id" :value="dist.id">
                {{ dist.name }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="field"
            label="Kelurahan / Desa"
            :error="fieldErr('person.village_id', 'village_id')"
          >
            <select
              :id="field.id"
              v-model="form.person.village_id"
              :disabled="!form.person.district_id || loadingVillages"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">
                {{ loadingVillages ? 'Memuat kelurahan/desa...' : '-- Pilih Kelurahan --' }}
              </option>
              <option v-for="vil in villageOptions" :key="vil.id" :value="vil.id">
                {{ vil.name }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="field"
            label="Kode Pos"
            :error="fieldErr('person.postal_code', 'postal_code')"
          >
            <BaseInput
              v-bind="field"
              v-model="form.person.postal_code"
              placeholder="Contoh: 60111"
              :maxlength="DOCTOR_POSTAL_CODE_MAX_LENGTH"
              @update:model-value="clearFieldError('person.postal_code', 'postal_code')"
            />
          </BaseField>

          <BaseField v-slot="field" label="Nomor HP" :error="fieldErr('person.phone', 'phone')">
            <BaseInput
              v-bind="field"
              v-model="form.person.phone"
              type="tel"
              placeholder="Contoh: 081234567890"
              :maxlength="DOCTOR_PHONE_MAX_LENGTH"
              @update:model-value="clearFieldError('person.phone', 'phone')"
            />
          </BaseField>
        </div>
      </section>

      <section class="space-y-4">
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-fg">
          3. Profil Profesional (Dokter)
        </h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <BaseField
            v-slot="field"
            label="Spesialisasi"
            required
            :error="fieldErr('professional.specialization_id', 'specialization_id')"
          >
            <select
              :id="field.id"
              v-model="form.professional.specialization_id"
              :disabled="loadingLookups"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
              @change="clearFieldError('professional.specialization_id', 'specialization_id')"
            >
              <option value="">
                {{ loadingLookups ? 'Memuat spesialisasi...' : '-- Pilih Spesialisasi --' }}
              </option>
              <option v-for="spec in specializationOptions" :key="spec.id" :value="spec.id">
                {{ spec.name }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="field"
            label="No. STR"
            :error="fieldErr('professional.str_number', 'str_number')"
          >
            <BaseInput
              v-bind="field"
              v-model="form.professional.str_number"
              :maxlength="DOCTOR_STR_MAX_LENGTH"
              @update:model-value="clearFieldError('professional.str_number', 'str_number')"
            />
          </BaseField>

          <BaseField
            v-slot="field"
            label="No. SIP"
            :error="fieldErr('professional.sip_number', 'sip_number')"
          >
            <BaseInput
              v-bind="field"
              v-model="form.professional.sip_number"
              :maxlength="DOCTOR_SIP_MAX_LENGTH"
              @update:model-value="clearFieldError('professional.sip_number', 'sip_number')"
            />
          </BaseField>

          <BaseField
            v-slot="field"
            label="Berlaku SIP (Sampai)"
            :error="fieldErr('professional.sip_valid_until', 'sip_valid_until')"
          >
            <BaseInput v-bind="field" v-model="form.professional.sip_valid_until" type="date" />
          </BaseField>

          <BaseField
            v-slot="field"
            label="Kode BPJS DPJP"
            :error="fieldErr('professional.bpjs_dpjp_code', 'bpjs_dpjp_code')"
          >
            <BaseInput
              v-bind="field"
              v-model="form.professional.bpjs_dpjp_code"
              :maxlength="DOCTOR_DPJP_MAX_LENGTH"
              @update:model-value="clearFieldError('professional.bpjs_dpjp_code', 'bpjs_dpjp_code')"
            />
          </BaseField>

          <BaseField
            v-slot="field"
            label="IHS Number (SatuSehat)"
            :error="fieldErr('person.ihs_number', 'ihs_number')"
          >
            <BaseInput
              v-bind="field"
              v-model="form.person.ihs_number"
              :maxlength="DOCTOR_IHS_MAX_LENGTH"
            />
          </BaseField>

          <BaseField
            v-slot="field"
            label="Tanda Tangan"
            hint="Format PNG atau JPG/JPEG, maksimal 2MB."
            :error="fieldErr('signature')"
            class="sm:col-span-2"
          >
            <div class="space-y-3">
              <div
                v-if="activePreviewUrl"
                class="flex flex-col items-start gap-3 rounded-lg border border-line bg-canvas p-3 sm:flex-row sm:items-center sm:justify-between"
              >
                <img
                  :src="activePreviewUrl"
                  alt="Preview tanda tangan"
                  class="max-h-24 max-w-full rounded border border-line bg-surface object-contain p-1"
                />

                <div class="flex flex-wrap gap-2">
                  <BaseButton
                    v-if="form.signature"
                    variant="secondary"
                    size="sm"
                    @click="cancelNewFile"
                  >
                    <X class="size-4" aria-hidden="true" />
                    Batalkan Pilihan
                  </BaseButton>
                  <BaseButton
                    v-else-if="form.signature_url"
                    variant="ghost-danger"
                    size="sm"
                    @click="removeSignature"
                  >
                    <Trash2 class="size-4" aria-hidden="true" />
                    Hapus Tanda Tangan
                  </BaseButton>
                </div>
              </div>

              <div
                v-else-if="form.remove_signature && form.signature_url"
                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-line bg-canvas px-3.5 py-2.5 text-xs text-fg-muted"
              >
                <span>Tanda tangan tersimpan akan dihapus saat data disimpan.</span>
                <BaseButton variant="secondary" size="sm" @click="undoRemoveSignature">
                  Batal Hapus
                </BaseButton>
              </div>

              <div class="flex flex-wrap items-center gap-3">
                <input
                  :id="field.id"
                  ref="fileInputRef"
                  type="file"
                  accept="image/png,image/jpeg,image/jpg"
                  class="block w-full text-sm text-fg-muted file:mr-3 file:cursor-pointer file:rounded-lg file:border file:border-line-strong file:bg-surface file:px-3.5 file:py-2 file:text-sm file:font-medium file:text-fg hover:file:bg-canvas"
                  :aria-describedby="field['aria-describedby']"
                  @change="handleFileChange"
                />
                <span
                  v-if="form.signature"
                  class="inline-flex items-center gap-1.5 text-xs text-fg-muted"
                >
                  <Upload class="size-3.5" aria-hidden="true" />
                  {{ form.signature.name }}
                </span>
              </div>
            </div>
          </BaseField>
        </div>
      </section>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="requestClose">Batal</BaseButton>
      <BaseButton
        type="submit"
        :form="formId"
        :loading="saving"
        :disabled="saving || loadingDetail || Boolean(detailError)"
      >
        {{ isEdit ? 'Simpan Perubahan' : 'Simpan Data' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
