<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { 
  formatDateWithAge, 
  formatDateIndo,
  formatDateTimeIndo,
  maskNik, 
  formatGender 
} from '@/utils/patientFormatters';
import { patientService } from '@/services/patient.service';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  patient: {
    type: Object,
    default: () => null
  }
});

const emit = defineEmits(['close', 'select']);

// Tabs State: 'info' | 'riwayat' | 'timeline'
const activeTab = ref('info');

// Riwayat Kunjungan State
const admissionsList = ref([]);
const isAdmissionsLoading = ref(false);
const admissionsError = ref(null);
const admissionsLoadedPatientId = ref(null);

// Timeline Pelayanan State
const timelineEvents = ref([]);
const isTimelineLoading = ref(false);
const timelineError = ref(null);
const timelineLoadedPatientId = ref(null);
const activeTimelineCategory = ref('all');

// Reset on modal open or patient switch
watch(
  [() => props.isOpen, () => props.patient?.id],
  ([newIsOpen, newPatientId], [oldIsOpen, oldPatientId]) => {
    if (newIsOpen) {
      if (!oldIsOpen || newPatientId !== oldPatientId) {
        activeTab.value = 'info';
        activeTimelineCategory.value = 'all';
        // If patient changed, reset cached lists
        if (newPatientId !== admissionsLoadedPatientId.value) {
          admissionsList.value = [];
          admissionsLoadedPatientId.value = null;
        }
        if (newPatientId !== timelineLoadedPatientId.value) {
          timelineEvents.value = [];
          timelineLoadedPatientId.value = null;
        }
      }
    }
  },
  { immediate: true }
);

// Lazy load data when switching tabs
watch(activeTab, (tab) => {
  if (tab === 'riwayat') {
    loadAdmissions();
  } else if (tab === 'timeline') {
    loadTimeline();
  }
});

const loadAdmissions = async (force = false) => {
  const patientId = props.patient?.id;
  if (!patientId) return;
  if (!force && admissionsLoadedPatientId.value === patientId && admissionsList.value.length > 0) {
    return;
  }

  isAdmissionsLoading.value = true;
  admissionsError.value = null;
  try {
    const res = await patientService.getPatientAdmissions(patientId, { per_page: 50 });
    admissionsList.value = res.data?.data || [];
    admissionsLoadedPatientId.value = patientId;
  } catch (err) {
    console.error('Error fetching patient admissions:', err);
    admissionsError.value = err.response?.data?.message || 'Gagal memuat riwayat kunjungan pasien.';
  } finally {
    isAdmissionsLoading.value = false;
  }
};

const loadTimeline = async (force = false) => {
  const patientId = props.patient?.id;
  if (!patientId) return;
  if (!force && timelineLoadedPatientId.value === patientId && timelineEvents.value.length > 0) {
    return;
  }

  isTimelineLoading.value = true;
  timelineError.value = null;
  try {
    const res = await patientService.getPatientTimeline(patientId);
    timelineEvents.value = res.data?.data || [];
    timelineLoadedPatientId.value = patientId;
  } catch (err) {
    console.error('Error fetching patient timeline:', err);
    timelineError.value = err.response?.data?.message || 'Gagal memuat kronologi pelayanan pasien.';
  } finally {
    isTimelineLoading.value = false;
  }
};

// Filtered timeline events by category pill
const filteredTimelineEvents = computed(() => {
  if (activeTimelineCategory.value === 'all') {
    return timelineEvents.value;
  }
  return timelineEvents.value.filter(
    (ev) => ev.type === activeTimelineCategory.value
  );
});

// Category counts for timeline filter pills
const timelineCategoryCounts = computed(() => {
  const counts = {
    all: timelineEvents.value.length,
    pemeriksaan: 0,
    farmasi: 0,
    laboratorium: 0,
    radiologi: 0,
    rawat_inap: 0,
    billing: 0,
    admisi: 0,
  };
  for (const ev of timelineEvents.value) {
    if (counts[ev.type] !== undefined) {
      counts[ev.type]++;
    }
  }
  return counts;
});

// Clean phone display helper
const formatPhone = (phone) => {
  if (!phone || phone === '0' || phone === '-' || phone === '00') {
    return '—';
  }
  return phone;
};

// Keyboard listener: Escape to close
const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.isOpen) {
    emit('close');
  }
};

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <div v-if="isOpen && patient" class="modal-overlay" @click.self="emit('close')">
    <div 
      class="modal-content modal-detail" 
      role="dialog" 
      aria-modal="true" 
      aria-labelledby="patient-detail-title"
    >
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-info">
          <div class="header-badges">
            <span class="rm-tag">{{ patient.medical_record_number || '—' }}</span>
            <span 
              class="status-pill" 
              :class="patient.is_active ? 'pill-active' : 'pill-inactive'"
            >
              {{ patient.is_active ? 'Pasien Aktif' : 'Pasien Tidak Aktif' }}
            </span>
            <span v-if="patient.fast_track" class="badge-fast-track">
              Fast Track
            </span>
          </div>
          <h3 id="patient-detail-title">{{ patient.name }}</h3>
          <p class="header-sub">
            NIK: {{ maskNik(patient.nik) }} &bull; Terdaftar sejak {{ formatDateIndo(patient.registered_at) }}
          </p>
        </div>

        <button 
          type="button" 
          class="btn-close" 
          @click="emit('close')" 
          aria-label="Tutup modal"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Tab Navigation Bar -->
      <div class="detail-tabs-bar" role="tablist" aria-label="Navigasi Detail Pasien">
        <button
          type="button"
          class="tab-btn"
          :class="{ active: activeTab === 'info' }"
          role="tab"
          :aria-selected="activeTab === 'info'"
          aria-controls="tabpanel-info"
          id="tab-info"
          @click="activeTab = 'info'"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          <span>Info Pasien</span>
        </button>

        <button
          type="button"
          class="tab-btn"
          :class="{ active: activeTab === 'riwayat' }"
          role="tab"
          :aria-selected="activeTab === 'riwayat'"
          aria-controls="tabpanel-riwayat"
          id="tab-riwayat"
          @click="activeTab = 'riwayat'"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          <span>Riwayat Kunjungan</span>
          <span v-if="admissionsList.length > 0" class="tab-badge">
            {{ admissionsList.length }}
          </span>
        </button>

        <button
          type="button"
          class="tab-btn"
          :class="{ active: activeTab === 'timeline' }"
          role="tab"
          :aria-selected="activeTab === 'timeline'"
          aria-controls="tabpanel-timeline"
          id="tab-timeline"
          @click="activeTab = 'timeline'"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          <span>Timeline Pelayanan</span>
          <span v-if="timelineEvents.length > 0" class="tab-badge">
            {{ timelineEvents.length }}
          </span>
        </button>
      </div>

      <!-- Modal Body (Content per Tab) -->
      <div class="modal-body detail-modal-body">
        <!-- ==================== TAB 1: INFO PASIEN ==================== -->
        <div 
          v-if="activeTab === 'info'" 
          id="tabpanel-info" 
          role="tabpanel" 
          aria-labelledby="tab-info"
          class="tab-content-panel"
        >
          <!-- Section 1: Identitas Pribadi -->
          <div class="detail-card-section">
            <h4 class="section-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              <span>Identitas Pribadi Pasien</span>
            </h4>

            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Nomor Rekam Medis (RM)</span>
                <span class="info-val font-bold font-primary">{{ patient.medical_record_number || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Nomor Induk Kependudukan (NIK)</span>
                <span class="info-val font-mono">{{ maskNik(patient.nik) }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Nama Lengkap Pasien</span>
                <span class="info-val font-semibold">{{ patient.name || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Jenis Kelamin</span>
                <span class="info-val">{{ formatGender(patient.gender) }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Tempat & Tanggal Lahir</span>
                <span class="info-val">
                  {{ patient.birth_place ? patient.birth_place + ', ' : '' }}{{ formatDateWithAge(patient.birth_date) }}
                </span>
              </div>

              <div class="info-item">
                <span class="info-label">Kebangsaan</span>
                <span class="info-val">{{ patient.nationality || 'WNI' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Golongan Darah</span>
                <span class="info-val">
                  <span v-if="patient.blood_type && patient.blood_type !== '-'" class="badge-blood">
                    {{ patient.blood_type }}
                  </span>
                  <span v-else>—</span>
                </span>
              </div>

              <div class="info-item">
                <span class="info-label">Agama</span>
                <span class="info-val">{{ patient.religion || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Status Perkawinan</span>
                <span class="info-val">{{ patient.marital_status || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Riwayat Alergi</span>
                <span class="info-val">
                  <span 
                    v-if="patient.allergies?.status === 'ada' || patient.allergies?.status === 'ya' || (patient.allergies?.notes && patient.allergies?.notes !== 'Tidak ada alergi')" 
                    class="badge-allergy-alert"
                  >
                    Ada Alergi: {{ patient.allergies?.notes || 'Tercatat' }}
                  </span>
                  <span v-else class="text-muted">
                    Tidak ada catatan alergi
                  </span>
                </span>
              </div>
            </div>
          </div>

          <!-- Section 2: Alamat & Kontak -->
          <div class="detail-card-section">
            <h4 class="section-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <span>Alamat Domisili & Kontak</span>
            </h4>

            <div class="info-grid">
              <div class="info-item full-width">
                <span class="info-label">Alamat Lengkap</span>
                <span class="info-val">{{ patient.address || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Kelurahan / Desa</span>
                <span class="info-val">{{ patient.region?.village?.name || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Kecamatan</span>
                <span class="info-val">{{ patient.region?.district?.name || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Kabupaten / Kota</span>
                <span class="info-val">{{ patient.region?.regency?.name || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Provinsi</span>
                <span class="info-val">{{ patient.region?.province?.name || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Kode Pos</span>
                <span class="info-val">{{ patient.postal_code || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Nomor Telepon / WhatsApp</span>
                <span class="info-val font-mono">{{ formatPhone(patient.phone) }}</span>
              </div>

              <div class="info-item full-width">
                <span class="info-label">Kontak Darurat (Keluarga Terdekat)</span>
                <span class="info-val">
                  <template v-if="patient.emergency_contact?.name">
                    <span class="font-semibold">{{ patient.emergency_contact.name }}</span>
                    <span v-if="patient.emergency_contact.relationship" class="text-secondary">
                      ({{ patient.emergency_contact.relationship }})
                    </span>
                    <span v-if="patient.emergency_contact.phone" class="font-mono text-muted">
                      &bull; Telp: {{ formatPhone(patient.emergency_contact.phone) }}
                    </span>
                  </template>
                  <span v-else class="text-muted">—</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Section 3: Pendidikan & Pekerjaan -->
          <div class="detail-card-section">
            <h4 class="section-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
              </svg>
              <span>Pendidikan & Pekerjaan</span>
            </h4>

            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Jenjang Pendidikan Terakhir</span>
                <span class="info-val">{{ patient.education?.name || patient.education || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Pekerjaan</span>
                <span class="info-val">{{ patient.occupation?.name || patient.occupation || '—' }}</span>
              </div>
            </div>
          </div>

          <!-- Section 4: Jaminan & Penjamin Biaya -->
          <div class="detail-card-section">
            <h4 class="section-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                <line x1="2" y1="10" x2="22" y2="10"></line>
              </svg>
              <span>Jaminan & Asuransi Kesehatan</span>
            </h4>

            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Jenis Penjamin Utama</span>
                <span class="info-val">
                  <span class="badge-guarantor">
                    {{ patient.guarantor?.name || (typeof patient.guarantor === 'string' ? patient.guarantor : 'UMUM / Mandiri') }}
                  </span>
                </span>
              </div>

              <div class="info-item">
                <span class="info-label">Nomor Kartu BPJS / Polis Asuransi</span>
                <span class="info-val font-mono">{{ patient.insurance_number || '—' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Prioritas Pelayanan Fast Track</span>
                <span class="info-val">
                  <span v-if="patient.fast_track" class="text-success font-semibold">
                    Ya (Prioritas Pelayanan)
                  </span>
                  <span v-else class="text-muted">
                    Tidak
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ==================== TAB 2: RIWAYAT KUNJUNGAN ==================== -->
        <div 
          v-else-if="activeTab === 'riwayat'" 
          id="tabpanel-riwayat" 
          role="tabpanel" 
          aria-labelledby="tab-riwayat"
          class="tab-content-panel"
        >
          <!-- Loading State -->
          <div v-if="isAdmissionsLoading" class="loading-state">
            <div class="spinner"></div>
            <span>Memuat riwayat kunjungan pasien...</span>
          </div>

          <!-- Error State -->
          <div v-else-if="admissionsError" class="error-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="error-icon">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <p>{{ admissionsError }}</p>
            <button type="button" class="btn-retry" @click="loadAdmissions(true)">
              Coba Lagi
            </button>
          </div>

          <!-- Empty State -->
          <div v-else-if="admissionsList.length === 0" class="empty-state">
            <div class="empty-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <h4>Belum Ada Riwayat Kunjungan</h4>
            <p>Pasien ini belum pernah tercatat melakukan kunjungan atau admisi ke rumah sakit.</p>
          </div>

          <!-- Data Riwayat List -->
          <div v-else class="admission-cards-list">
            <div 
              v-for="adm in admissionsList" 
              :key="adm.id" 
              class="admission-card"
            >
              <!-- Card Header -->
              <div class="admission-card-header">
                <div class="adm-header-left">
                  <div class="adm-date-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="adm-icon">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span class="adm-date font-bold">
                      {{ formatDateTimeIndo(adm.admission_time) }}
                    </span>
                  </div>
                  <span class="adm-id-code">Admisi #{{ adm.id }}</span>
                </div>

                <div class="adm-header-right">
                  <span 
                    class="badge-status-adm" 
                    :class="adm.status === '2' ? 'status-finished' : 'status-active'"
                  >
                    {{ adm.status_label || (adm.status === '2' ? 'Selesai' : 'Aktif') }}
                  </span>
                  <span v-if="adm.entry_mode" class="badge-entry-mode">
                    {{ adm.entry_mode }}
                  </span>
                  <span class="badge-guarantor-sm">
                    {{ adm.guarantor?.name || 'UMUM' }}
                  </span>
                </div>
              </div>

              <!-- Card Reason -->
              <div v-if="adm.reason" class="admission-reason">
                <span class="reason-label">Alasan / Keluhan:</span>
                <span class="reason-val">{{ adm.reason }}</span>
              </div>

              <!-- Card Visits (Poliklinik Tujuan & Dokter) -->
              <div class="admission-visits-section">
                <div class="visits-title">Daftar Pelayanan & Poliklinik ({{ adm.visits?.length || 0 }})</div>
                
                <div v-if="!adm.visits || adm.visits.length === 0" class="no-visits text-muted">
                  Belum ada pelayanan poliklinik terdaftar pada admisi ini.
                </div>

                <div v-else class="visits-grid">
                  <div 
                    v-for="v in adm.visits" 
                    :key="v.id" 
                    class="visit-item-card"
                  >
                    <div class="visit-top">
                      <div class="visit-poli font-bold">
                        {{ v.polyclinic?.name || 'Poliklinik Rawat Jalan' }}
                      </div>
                      <span 
                        class="visit-status-badge"
                        :class="{
                          'badge-done': v.service_status === 'Ya',
                          'badge-process': v.service_status === 'Proses',
                          'badge-waiting': v.service_status === 'Tidak' || !v.service_status
                        }"
                      >
                        {{ v.service_status_label || (v.service_status === 'Ya' ? 'Selesai' : (v.service_status === 'Proses' ? 'Proses' : 'Menunggu')) }}
                      </span>
                    </div>

                    <div class="visit-doctor text-secondary">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="v-icon">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                      </svg>
                      <span>{{ v.doctor?.name || 'Dokter DPJP belum dialokasikan' }}</span>
                    </div>

                    <div class="visit-footer">
                      <span v-if="v.case_type" class="v-case">
                        Kasus: {{ v.case_type }}
                      </span>
                      <span 
                        class="v-billing-badge"
                        :class="v.billing_status === 'sudah bayar' ? 'billing-paid' : 'billing-unpaid'"
                      >
                        Billing: {{ v.billing_status || 'Belum Lunas' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ==================== TAB 3: TIMELINE PELAYANAN ==================== -->
        <div 
          v-else-if="activeTab === 'timeline'" 
          id="tabpanel-timeline" 
          role="tabpanel" 
          aria-labelledby="tab-timeline"
          class="tab-content-panel"
        >
          <!-- Category Filter Pills -->
          <div class="timeline-filter-bar">
            <button 
              type="button" 
              class="cat-pill" 
              :class="{ active: activeTimelineCategory === 'all' }"
              @click="activeTimelineCategory = 'all'"
            >
              Semua ({{ timelineCategoryCounts.all }})
            </button>
            <button 
              type="button" 
              class="cat-pill pill-pemeriksaan" 
              :class="{ active: activeTimelineCategory === 'pemeriksaan' }"
              @click="activeTimelineCategory = 'pemeriksaan'"
            >
              Pemeriksaan ({{ timelineCategoryCounts.pemeriksaan }})
            </button>
            <button 
              type="button" 
              class="cat-pill pill-farmasi" 
              :class="{ active: activeTimelineCategory === 'farmasi' }"
              @click="activeTimelineCategory = 'farmasi'"
            >
              Farmasi ({{ timelineCategoryCounts.farmasi }})
            </button>
            <button 
              type="button" 
              class="cat-pill pill-lab" 
              :class="{ active: activeTimelineCategory === 'laboratorium' }"
              @click="activeTimelineCategory = 'laboratorium'"
            >
              Laboratorium ({{ timelineCategoryCounts.laboratorium }})
            </button>
            <button 
              type="button" 
              class="cat-pill pill-rad" 
              :class="{ active: activeTimelineCategory === 'radiologi' }"
              @click="activeTimelineCategory = 'radiologi'"
            >
              Radiologi ({{ timelineCategoryCounts.radiologi }})
            </button>
            <button 
              type="button" 
              class="cat-pill pill-ranap" 
              :class="{ active: activeTimelineCategory === 'rawat_inap' }"
              @click="activeTimelineCategory = 'rawat_inap'"
            >
              Rawat Inap ({{ timelineCategoryCounts.rawat_inap }})
            </button>
            <button 
              type="button" 
              class="cat-pill pill-billing" 
              :class="{ active: activeTimelineCategory === 'billing' }"
              @click="activeTimelineCategory = 'billing'"
            >
              Billing ({{ timelineCategoryCounts.billing }})
            </button>
            <button 
              type="button" 
              class="cat-pill pill-admisi" 
              :class="{ active: activeTimelineCategory === 'admisi' }"
              @click="activeTimelineCategory = 'admisi'"
            >
              Admisi ({{ timelineCategoryCounts.admisi }})
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="isTimelineLoading" class="loading-state">
            <div class="spinner"></div>
            <span>Menyusun kronologi pelayanan medis pasien...</span>
          </div>

          <!-- Error State -->
          <div v-else-if="timelineError" class="error-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="error-icon">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <p>{{ timelineError }}</p>
            <button type="button" class="btn-retry" @click="loadTimeline(true)">
              Coba Lagi
            </button>
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredTimelineEvents.length === 0" class="empty-state">
            <div class="empty-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
            </div>
            <h4>Belum Ada Aktivitas Pelayanan</h4>
            <p v-if="timelineEvents.length > 0">
              Tidak ada aktivitas untuk kategori yang dipilih.
            </p>
            <p v-else>
              Belum ada riwayat aktivitas klinis, penunjang, atau billing tercatat di sistem untuk pasien ini.
            </p>
          </div>

          <!-- Vertical Timeline Events List -->
          <div v-else class="vertical-timeline-container">
            <div 
              v-for="ev in filteredTimelineEvents" 
              :key="ev.id" 
              class="timeline-event-row"
              :class="'event-type-' + ev.type"
            >
              <!-- Track Node & Icon -->
              <div class="timeline-track-node">
                <div class="node-icon-circle" :class="'node-' + ev.type">
                  <!-- Admisi Icon -->
                  <svg v-if="ev.type === 'admisi'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                  </svg>
                  <!-- Pemeriksaan Icon -->
                  <svg v-else-if="ev.type === 'pemeriksaan'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"></path>
                    <path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"></path>
                    <circle cx="20" cy="10" r="2"></circle>
                  </svg>
                  <!-- Farmasi Icon -->
                  <svg v-else-if="ev.type === 'farmasi'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
                    <path d="m8.5 8.5 7 7"></path>
                  </svg>
                  <!-- Lab Icon -->
                  <svg v-else-if="ev.type === 'laboratorium'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10 2v7.31L4.1 19.34A2 2 0 0 0 5.8 22h12.4a2 2 0 0 0 1.7-2.66L14 9.31V2"></path>
                    <path d="M8.5 2h7"></path>
                    <path d="M14 9.3a6.5 6.5 0 1 1-4 0"></path>
                  </svg>
                  <!-- Rad Icon -->
                  <svg v-else-if="ev.type === 'radiologi'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                  </svg>
                  <!-- Rawat Inap Icon -->
                  <svg v-else-if="ev.type === 'rawat_inap'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 4v16"></path>
                    <path d="M2 8h18a2 2 0 0 1 2 2v10"></path>
                    <path d="M2 17h20"></path>
                    <path d="M6 8v9"></path>
                  </svg>
                  <!-- Billing Icon -->
                  <svg v-else-if="ev.type === 'billing'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                  </svg>
                </div>
                <div class="node-line"></div>
              </div>

              <!-- Event Card -->
              <div class="timeline-event-card">
                <div class="event-card-top">
                  <div class="event-category-badge" :class="'cat-badge-' + ev.type">
                    {{ ev.category }}
                  </div>
                  <h5 class="event-title">{{ ev.title }}</h5>
                  <span class="event-timestamp">
                    {{ formatDateTimeIndo(ev.timestamp) }}
                  </span>
                </div>

                <div class="event-meta-row">
                  <div class="meta-unit font-semibold">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="meta-icon">
                      <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                      <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                    <span>{{ ev.unit }}</span>
                  </div>

                  <div v-if="ev.practitioner" class="meta-doc text-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="meta-icon">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>{{ ev.practitioner }}</span>
                  </div>

                  <div class="meta-status">
                    <span class="badge-status-generic" :class="'badge-' + (ev.status_variant || 'secondary')">
                      {{ ev.status }}
                    </span>
                  </div>
                </div>

                <div v-if="ev.notes" class="event-notes">
                  {{ ev.notes }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button 
          type="button" 
          class="btn-cancel" 
          @click="emit('close')"
        >
          Tutup
        </button>

        <button 
          type="button" 
          class="btn-primary" 
          @click="emit('select', patient)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>Pilih Pasien / Lanjut Admisi</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-detail {
  max-width: 860px;
  width: 95vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.header-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.header-badges {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.rm-tag {
  background: var(--color-primary);
  color: #ffffff;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}

.status-pill {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
}

.pill-active {
  background: #dcfce7;
  color: #166534;
}

.pill-inactive {
  background: #f1f5f9;
  color: #64748b;
}

.badge-fast-track {
  background: #fef08a;
  color: #854d0e;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  text-transform: uppercase;
}

.header-sub {
  margin: 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

/* Tab Navigation Bar */
.detail-tabs-bar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1.5rem 0;
  border-bottom: 1px solid var(--color-border-soft);
  background: #f8fafc;
}

.tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.65rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  border-radius: 4px 4px 0 0;
}

.tab-btn:hover {
  color: var(--color-primary);
  background: rgba(14, 165, 233, 0.04);
}

.tab-btn.active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
  background: #ffffff;
  font-weight: 700;
}

.tab-icon {
  width: 16px;
  height: 16px;
}

.tab-badge {
  display: inline-block;
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.1rem 0.45rem;
  border-radius: 9999px;
}

.detail-modal-body {
  overflow-y: auto;
  max-height: calc(90vh - 180px);
  padding: 1.25rem 1.5rem;
}

.tab-content-panel {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Card Sections (Tab 1: Info) */
.detail-card-section {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  padding: 1rem 1.25rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.85rem 0;
  font-size: 0.925rem;
  font-weight: 700;
  color: var(--color-text-navy);
  border-bottom: 1px dashed var(--color-border-soft);
  padding-bottom: 0.5rem;
}

.section-title svg {
  width: 16px;
  height: 16px;
  color: var(--color-primary);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.85rem 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-label {
  font-size: 0.725rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.info-val {
  font-size: 0.875rem;
  color: var(--color-text-navy);
  line-height: 1.4;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.font-primary {
  color: var(--color-primary);
}

.badge-blood {
  display: inline-block;
  background: #fee2e2;
  color: #991b1b;
  font-weight: 700;
  padding: 0.1rem 0.45rem;
  border-radius: 4px;
  font-size: 0.8rem;
  width: fit-content;
}

.badge-allergy-alert {
  display: inline-block;
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
}

.badge-guarantor {
  display: inline-block;
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  font-size: 0.825rem;
  width: fit-content;
}

/* Tab 2: Riwayat Kunjungan */
.admission-cards-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.admission-card {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  padding: 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.admission-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 0.65rem;
}

.adm-header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.adm-date-row {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--color-text-navy);
}

.adm-icon {
  width: 15px;
  height: 15px;
  color: var(--color-primary);
}

.adm-id-code {
  font-family: ui-monospace, SFMono-Regular, monospace;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.adm-header-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.badge-status-adm {
  font-size: 0.725rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
}

.status-finished {
  background: #dcfce7;
  color: #166534;
}

.status-active {
  background: #e0f2fe;
  color: #0369a1;
}

.badge-entry-mode {
  background: #f1f5f9;
  color: #475569;
  font-size: 0.725rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  text-transform: capitalize;
}

.badge-guarantor-sm {
  background: #ede9fe;
  color: #5b21b6;
  font-size: 0.725rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}

.admission-reason {
  background: #f8fafc;
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
  font-size: 0.825rem;
}

.reason-label {
  font-weight: 600;
  color: var(--color-text-secondary);
  margin-right: 0.35rem;
}

.reason-val {
  color: var(--color-text-navy);
}

.visits-title {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin-bottom: 0.5rem;
}

.visits-grid {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.visit-item-card {
  background: #fbfcfd;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0.65rem 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.visit-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.visit-poli {
  font-size: 0.875rem;
  color: var(--color-text-navy);
}

.visit-status-badge {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.1rem 0.45rem;
  border-radius: 4px;
}

.badge-done {
  background: #dcfce7;
  color: #166534;
}

.badge-process {
  background: #fef08a;
  color: #854d0e;
}

.badge-waiting {
  background: #f1f5f9;
  color: #64748b;
}

.visit-doctor {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
}

.v-icon {
  width: 14px;
  height: 14px;
}

.visit-footer {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.75rem;
}

.v-case {
  color: var(--color-text-secondary);
}

.v-billing-badge {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.05rem 0.4rem;
  border-radius: 4px;
}

.billing-paid {
  background: #dcfce7;
  color: #166534;
}

.billing-unpaid {
  background: #fee2e2;
  color: #991b1b;
}

/* Tab 3: Timeline Pelayanan */
.timeline-filter-bar {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-wrap: wrap;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.cat-pill {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.3rem 0.65rem;
  border-radius: 9999px;
  border: 1px solid var(--color-border-soft);
  background: #ffffff;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.cat-pill:hover {
  background: #f1f5f9;
  color: var(--color-text-navy);
}

.cat-pill.active {
  background: var(--color-primary);
  color: #ffffff;
  border-color: var(--color-primary);
}

.vertical-timeline-container {
  display: flex;
  flex-direction: column;
  position: relative;
  padding-top: 0.5rem;
}

.timeline-event-row {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  position: relative;
}

.timeline-track-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 32px;
  position: relative;
  align-self: stretch;
}

.node-icon-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  z-index: 2;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
  flex-shrink: 0;
}

.node-icon-circle svg {
  width: 16px;
  height: 16px;
}

.node-line {
  width: 2px;
  background: #e2e8f0;
  flex-grow: 1;
  min-height: 24px;
}

.timeline-event-row:last-child .node-line {
  display: none;
}

/* Category Node Colors */
.node-admisi {
  background: #0284c7;
}

.node-pemeriksaan {
  background: #059669;
}

.node-farmasi {
  background: #7c3aed;
}

.node-laboratorium {
  background: #d97706;
}

.node-radiologi {
  background: #4f46e5;
}

.node-rawat_inap {
  background: #0891b2;
}

.node-billing {
  background: #16a34a;
}

.timeline-event-card {
  flex-grow: 1;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  padding: 0.85rem 1rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.event-card-top {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 0.4rem;
  flex-wrap: wrap;
}

.event-category-badge {
  font-size: 0.675rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.cat-badge-admisi {
  background: #e0f2fe;
  color: #0369a1;
}

.cat-badge-pemeriksaan {
  background: #dcfce7;
  color: #166534;
}

.cat-badge-farmasi {
  background: #ede9fe;
  color: #5b21b6;
}

.cat-badge-laboratorium {
  background: #fef3c7;
  color: #92400e;
}

.cat-badge-radiologi {
  background: #e0e7ff;
  color: #3730a3;
}

.cat-badge-rawat_inap {
  background: #cffafe;
  color: #155e75;
}

.cat-badge-billing {
  background: #ecfdf5;
  color: #065f46;
}

.event-title {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--color-text-navy);
  flex-grow: 1;
}

.event-timestamp {
  font-size: 0.775rem;
  color: var(--color-text-secondary);
  font-family: ui-monospace, SFMono-Regular, monospace;
}

.event-meta-row {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  font-size: 0.8rem;
  margin-bottom: 0.35rem;
  flex-wrap: wrap;
}

.meta-icon {
  width: 14px;
  height: 14px;
  color: var(--color-text-secondary);
}

.meta-unit, .meta-doc {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
}

.badge-status-generic {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.1rem 0.45rem;
  border-radius: 4px;
}

.badge-success {
  background: #dcfce7;
  color: #166534;
}

.badge-warning {
  background: #fef08a;
  color: #854d0e;
}

.badge-primary {
  background: #e0f2fe;
  color: #0369a1;
}

.badge-info {
  background: #e0e7ff;
  color: #3730a3;
}

.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.badge-secondary {
  background: #f1f5f9;
  color: #64748b;
}

.event-notes {
  background: #f8fafc;
  border-radius: 4px;
  padding: 0.4rem 0.65rem;
  font-size: 0.775rem;
  color: var(--color-text-navy);
  margin-top: 0.35rem;
  line-height: 1.4;
}

/* Shared States: Loading, Error, Empty */
.loading-state, .error-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.5rem;
  text-align: center;
  gap: 0.75rem;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e2e8f0;
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-icon {
  width: 36px;
  height: 36px;
  color: #ef4444;
}

.btn-retry {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  padding: 0.35rem 0.85rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

.empty-icon-wrap {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
}

.empty-icon-wrap svg {
  width: 26px;
  height: 26px;
}

.empty-state h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.empty-state p {
  margin: 0;
  font-size: 0.825rem;
  color: var(--color-text-secondary);
  max-width: 400px;
}

@media (max-width: 640px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
  .admission-card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  .event-card-top {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
}
</style>
