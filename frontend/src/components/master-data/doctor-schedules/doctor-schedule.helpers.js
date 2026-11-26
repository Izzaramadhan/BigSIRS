/*
 * Fungsi murni modul Jadwal Dokter: format hari/jam, pengelompokan rekap mingguan,
 * opsi dropdown Dokter/Poliklinik, factory form, validasi, pembentuk payload, dan sanitasi error.
 */

export const EMPTY = '—'

export const DAY_OPTIONS = [
  { value: 1, label: 'Senin' },
  { value: 2, label: 'Selasa' },
  { value: 3, label: 'Rabu' },
  { value: 4, label: 'Kamis' },
  { value: 5, label: 'Jumat' },
  { value: 6, label: 'Sabtu' },
  { value: 7, label: 'Minggu' },
]

export const DAY_NAME_MAP = Object.fromEntries(DAY_OPTIONS.map((item) => [item.value, item.label]))
const DAY_NAME_REVERSE = Object.fromEntries(
  DAY_OPTIONS.map((item) => [item.label.toLowerCase(), item.value]),
)

const TIME_PATTERN = /^([01]\d|2[0-3]):[0-5]\d$/
const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Normalisasi input hari (angka 1..7 atau nama hari) ke angka 1..7 atau `''`. */
export function normalizeDayOfWeek(day) {
  if (day === null || day === undefined || day === '') return ''
  const num = Number(day)
  if (Number.isInteger(num) && num >= 1 && num <= 7) return num
  const text = String(day).trim().toLowerCase()
  return DAY_NAME_REVERSE[text] ?? ''
}

/** Format label hari praktik Indonesia atau tanda kosong bila tidak valid. */
export function formatDayName(day) {
  const num = normalizeDayOfWeek(day)
  return num ? DAY_NAME_MAP[num] : EMPTY
}

/** Format waktu ke `HH:mm` tanpa detik. */
export function formatScheduleTime(timeStr) {
  if (!timeStr) return ''
  const trimmed = String(timeStr).trim()
  if (!trimmed) return ''
  return trimmed.slice(0, 5)
}

/** Format rentang jam `HH:mm – HH:mm`. */
export function formatScheduleTimeRange(startTime, endTime) {
  const start = formatScheduleTime(startTime)
  const end = formatScheduleTime(endTime)
  if (!start && !end) return EMPTY
  return `${start || EMPTY} – ${end || EMPTY}`
}

/** Ringkasan singkat jadwal untuk konfirmasi dialog hapus. */
export function formatScheduleSummary(schedule) {
  if (!schedule) return 'jadwal ini'
  const doctorName = schedule.doctor?.name?.trim() || 'Dokter'
  const polyName = schedule.polyclinic?.name?.trim() || 'Poliklinik'
  const dayLabel = formatDayName(schedule.day_of_week)
  const timeRange = formatScheduleTimeRange(schedule.start_time, schedule.end_time)
  return `${doctorName} di ${polyName} (${dayLabel}, ${timeRange})`
}

/** Ubah objek dokter dari lookup API menjadi opsi SearchableSelect. */
export function toDoctorOption(doctor) {
  if (!doctor?.id) return null
  const name = String(doctor.name ?? doctor.full_name ?? '').trim()
  if (!name) return null
  const specializationRaw =
    doctor.specialization_name ??
    (typeof doctor.specialization === 'string'
      ? doctor.specialization
      : doctor.specialization?.name) ??
    ''
  const specialization = String(specializationRaw).trim()

  return {
    value: Number(doctor.id),
    label: specialization ? `${name} — ${specialization}` : name,
    name,
    specialization,
    isActive: doctor.is_active === true || doctor.is_active === 1 || doctor.is_active === undefined,
  }
}

/** Pastikan dokter yang tersimpan pada jadwal mode Edit selalu ada dalam opsi dropdown. */
export function ensureSavedDoctorOptions(rawDoctors = [], savedSchedule = null) {
  const mapped = (Array.isArray(rawDoctors) ? rawDoctors : [])
    .map(toDoctorOption)
    .filter(Boolean)

  const savedDoctorId = savedSchedule?.doctor_id ? Number(savedSchedule.doctor_id) : null
  if (savedDoctorId && !mapped.some((opt) => opt.value === savedDoctorId)) {
    const fallbackOpt = toDoctorOption({
      id: savedDoctorId,
      name: savedSchedule.doctor?.name || `Dokter #${savedDoctorId}`,
      specialization: savedSchedule.doctor?.specialization ?? '',
      is_active: false,
    })
    if (fallbackOpt) mapped.unshift(fallbackOpt)
  }

  return mapped
}

/** Ubah objek poliklinik menjadi opsi SearchableSelect dan jaga opsi tersimpan saat Edit. */
export function ensureSavedPolyclinicOptions(rawPolyclinics = [], savedSchedule = null) {
  const mapped = (Array.isArray(rawPolyclinics) ? rawPolyclinics : [])
    .filter((poly) => poly?.id && poly?.name)
    .map((poly) => ({
      value: Number(poly.id),
      label: String(poly.name).trim(),
    }))

  const savedPolyId = savedSchedule?.polyclinic_id ? Number(savedSchedule.polyclinic_id) : null
  if (savedPolyId && !mapped.some((opt) => opt.value === savedPolyId)) {
    mapped.unshift({
      value: savedPolyId,
      label: savedSchedule.polyclinic?.name?.trim() || `Poliklinik #${savedPolyId}`,
    })
  }

  return mapped
}

/**
 * Kelompokkan daftar jadwal mentah menjadi rekap mingguan per Poliklinik -> Dokter -> Hari (1..7).
 */
export function buildScheduleRecapGroups(schedules = []) {
  if (!Array.isArray(schedules) || schedules.length === 0) return []

  const polyMap = new Map()

  for (const item of schedules) {
    const polyId = item?.polyclinic_id
    const docId = item?.doctor_id
    if (!polyId || !docId) continue

    if (!polyMap.has(polyId)) {
      polyMap.set(polyId, {
        id: polyId,
        name: item.polyclinic?.name?.trim() || `Poliklinik #${polyId}`,
        doctorsMap: new Map(),
      })
    }

    const polyEntry = polyMap.get(polyId)
    if (!polyEntry.doctorsMap.has(docId)) {
      polyEntry.doctorsMap.set(docId, {
        id: docId,
        name: item.doctor?.name?.trim() || `Dokter #${docId}`,
        specialization:
          typeof item.doctor?.specialization === 'string'
            ? item.doctor.specialization.trim()
            : item.doctor?.specialization?.name?.trim() || '',
        days: { 1: [], 2: [], 3: [], 4: [], 5: [], 6: [], 7: [] },
      })
    }

    const docEntry = polyEntry.doctorsMap.get(docId)
    const day = normalizeDayOfWeek(item.day_of_week)
    if (day && docEntry.days[day]) {
      docEntry.days[day].push(item)
    }
  }

  const result = []
  for (const poly of polyMap.values()) {
    const doctors = Array.from(poly.doctorsMap.values()).map((doc) => {
      for (let d = 1; d <= 7; d += 1) {
        doc.days[d].sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''))
      }
      return doc
    })

    doctors.sort((a, b) => a.name.localeCompare(b.name, 'id'))

    if (doctors.length > 0) {
      result.push({
        id: poly.id,
        name: poly.name,
        doctors,
      })
    }
  }

  result.sort((a, b) => a.name.localeCompare(b.name, 'id'))
  return result
}

/** Inisialisasi state form tambah/edit jadwal dokter. */
export function createDoctorScheduleForm(schedule = null) {
  return {
    doctor_id: schedule?.doctor_id ? Number(schedule.doctor_id) : '',
    polyclinic_id: schedule?.polyclinic_id ? Number(schedule.polyclinic_id) : '',
    day_of_week: normalizeDayOfWeek(schedule?.day_of_week),
    start_time: formatScheduleTime(schedule?.start_time),
    end_time: formatScheduleTime(schedule?.end_time),
    is_holiday: Boolean(schedule?.is_holiday ?? false),
    online_quota:
      schedule?.online_quota !== null &&
      schedule?.online_quota !== undefined &&
      schedule?.online_quota !== ''
        ? Number(schedule.online_quota)
        : '',
  }
}

/**
 * Validasi client-side form jadwal dokter selaras Store/UpdateDoctorScheduleRequest.
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validateDoctorScheduleForm(form) {
  const errors = {}
  const doctorId = form?.doctor_id
  const polyId = form?.polyclinic_id
  const day = normalizeDayOfWeek(form?.day_of_week)
  const start = formatScheduleTime(form?.start_time)
  const end = formatScheduleTime(form?.end_time)

  if (doctorId === '' || doctorId === null || doctorId === undefined) {
    errors.doctor_id = 'Dokter wajib dipilih.'
  }

  if (polyId === '' || polyId === null || polyId === undefined) {
    errors.polyclinic_id = 'Poliklinik wajib dipilih.'
  }

  if (!day) {
    errors.day_of_week = 'Hari praktik wajib dipilih.'
  }

  if (!start) {
    errors.start_time = 'Jam mulai wajib diisi.'
  } else if (!TIME_PATTERN.test(start)) {
    errors.start_time = 'Format jam mulai harus HH:mm.'
  }

  if (!end) {
    errors.end_time = 'Jam selesai wajib diisi.'
  } else if (!TIME_PATTERN.test(end)) {
    errors.end_time = 'Format jam selesai harus HH:mm.'
  } else if (TIME_PATTERN.test(start) && end <= start) {
    errors.end_time = 'Jam selesai harus lebih besar dari jam mulai.'
  }

  if (
    form?.online_quota !== '' &&
    form?.online_quota !== null &&
    form?.online_quota !== undefined
  ) {
    const quota = Number(form.online_quota)
    if (!Number.isInteger(quota) || quota < 0) {
      errors.online_quota = 'Kuota online harus berupa bilangan bulat minimal 0.'
    }
  }

  return errors
}

/** Bentuk payload bersih untuk endpoint POST/PUT `/master-data/doctor-schedules`. */
export function toDoctorSchedulePayload(form) {
  const rawQuota = form?.online_quota
  const hasQuota = rawQuota !== '' && rawQuota !== null && rawQuota !== undefined

  return {
    doctor_id: Number(form.doctor_id),
    polyclinic_id: Number(form.polyclinic_id),
    day_of_week: Number(normalizeDayOfWeek(form.day_of_week)),
    start_time: formatScheduleTime(form.start_time),
    end_time: formatScheduleTime(form.end_time),
    is_holiday: Boolean(form?.is_holiday),
    online_quota: hasQuota ? Number(rawQuota) : null,
  }
}

/**
 * Pastikan pesan error API tidak membocorkan detail SQL atau stack trace.
 */
export function getSafeDoctorScheduleErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
