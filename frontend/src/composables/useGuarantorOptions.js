import { computed, ref } from 'vue'
import lookupService from '@/services/lookup.service'

const SELF_PAY_PATTERN = /^(umum|pasien umum|mandiri|tunai)(\s|$|\/)/i

export function isSelfPayGuarantor(itemOrName) {
  if (!itemOrName) return false
  if (typeof itemOrName === 'object') {
    if (itemOrName.type === 'self_pay' || itemOrName.is_self_pay === true) {
      return true
    }
    return isSelfPayGuarantor(itemOrName.name || itemOrName.label || itemOrName.jenis_asuransi || '')
  }
  return SELF_PAY_PATTERN.test(String(itemOrName).trim())
}

export function normalizeGuarantorOption(item) {
  if (!item) return null
  const name = String(item.name || item.jenis_asuransi || item.label || '').trim()
  if (!name) return null
  const isActive = item.is_active !== false && String(item.status ?? '1') !== '0'
  const selfPay = isSelfPayGuarantor(item)

  return {
    id: item.id !== undefined && item.id !== null ? String(item.id) : `name:${name}`,
    numericId: item.id !== undefined && item.id !== null && !Number.isNaN(Number(item.id)) ? Number(item.id) : null,
    code: item.code || '',
    name,
    type: item.type || (selfPay ? 'self_pay' : 'other'),
    type_label: item.type_label || '',
    is_active: isActive,
    is_historical: Boolean(item.is_historical),
    is_self_pay: selfPay,
    label: isActive ? name : `${name} (Nonaktif / Historis)`,
  }
}

export function findMatchingGuarantor(options, { id, name } = {}) {
  const list = Array.isArray(options) ? options : []
  if (id !== undefined && id !== null && id !== '') {
    const byId = list.find((g) => String(g.id) === String(id))
    if (byId) return byId
  }

  if (name) {
    const target = String(name).trim().toLowerCase()
    const exact = list.find((g) => g.name.toLowerCase() === target)
    if (exact) return exact

    if (isSelfPayGuarantor(target)) {
      const selfPay = list.find((g) => g.is_self_pay)
      if (selfPay) return selfPay
    }

    const partial = list.find(
      (g) => target.includes(g.name.toLowerCase()) || g.name.toLowerCase().includes(target),
    )
    if (partial) return partial
  }

  return null
}

export function mergeSavedGuarantorOption(options, saved) {
  const normalizedList = (Array.isArray(options) ? options : [])
    .map(normalizeGuarantorOption)
    .filter(Boolean)

  if (!saved || (!saved.id && !saved.name)) {
    return normalizedList
  }

  const matched = findMatchingGuarantor(normalizedList, saved)
  if (matched) {
    return normalizedList
  }

  const fallbackName = saved.name ? String(saved.name).trim() : `Penjamin #${saved.id}`
  const historicalOption = normalizeGuarantorOption({
    id: saved.id ?? `historical:${fallbackName}`,
    name: fallbackName,
    type: saved.type || (isSelfPayGuarantor(fallbackName) ? 'self_pay' : 'other'),
    is_active: false,
    is_historical: true,
  })

  return historicalOption ? [historicalOption, ...normalizedList] : normalizedList
}

export function useGuarantorOptions() {
  const guarantors = ref([])
  const loading = ref(false)
  const error = ref('')
  const hasLoaded = ref(false)

  const isEmpty = computed(() => hasLoaded.value && !loading.value && !error.value && guarantors.value.length === 0)

  const fetchGuarantors = async (params = {}) => {
    loading.value = true
    error.value = ''
    try {
      const rows = await lookupService.getGuarantors({ limit: 200, ...params })
      const normalized = (Array.isArray(rows) ? rows : [])
        .map(normalizeGuarantorOption)
        .filter(Boolean)
      guarantors.value = normalized
      hasLoaded.value = true
      return normalized
    } catch {
      error.value = 'Gagal memuat data penjamin dari Master Data.'
      hasLoaded.value = true
      return guarantors.value
    } finally {
      loading.value = false
    }
  }

  const ensureSavedOption = (saved) => mergeSavedGuarantorOption(guarantors.value, saved)

  return {
    guarantors,
    loading,
    error,
    hasLoaded,
    isEmpty,
    fetchGuarantors,
    ensureSavedOption,
  }
}
