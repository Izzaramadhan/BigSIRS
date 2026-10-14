<script setup>
import { computed } from 'vue';

const props = defineProps({
  schedules: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  }
});

const emit = defineEmits(['retry']);

const formatTime = (timeStr) => {
  if (!timeStr) return '';
  return timeStr.substring(0, 5);
};

const formatTimeRange = (start, end) => {
  return `${formatTime(start)} – ${formatTime(end)}`;
};

const groupedData = computed(() => {
  if (!props.schedules || props.schedules.length === 0) return [];

  const polyMap = new Map();

  for (const item of props.schedules) {
    const polyId = item.polyclinic_id;
    if (!polyId) continue;

    if (!polyMap.has(polyId)) {
      polyMap.set(polyId, {
        id: polyId,
        name: item.polyclinic?.name || `Poliklinik #${polyId}`,
        doctorsMap: new Map()
      });
    }

    const polyEntry = polyMap.get(polyId);
    const docId = item.doctor_id;
    if (!docId) continue;

    if (!polyEntry.doctorsMap.has(docId)) {
      polyEntry.doctorsMap.set(docId, {
        id: docId,
        name: item.doctor?.name || `Dokter #${docId}`,
        specialization: item.doctor?.specialization || '',
        days: { 1: [], 2: [], 3: [], 4: [], 5: [], 6: [], 7: [] }
      });
    }

    const docEntry = polyEntry.doctorsMap.get(docId);
    const day = Number(item.day_of_week);
    if (docEntry.days[day]) {
      docEntry.days[day].push(item);
    }
  }

  const result = [];
  for (const poly of polyMap.values()) {
    const doctors = Array.from(poly.doctorsMap.values()).map(doc => {
      for (let d = 1; d <= 7; d++) {
        doc.days[d].sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''));
      }
      return doc;
    });

    doctors.sort((a, b) => a.name.localeCompare(b.name, 'id'));

    if (doctors.length > 0) {
      result.push({
        id: poly.id,
        name: poly.name,
        doctors
      });
    }
  }

  result.sort((a, b) => a.name.localeCompare(b.name, 'id'));

  return result;
});
</script>

<template>
  <div class="recap-card">
    <div class="recap-header">
      <div class="header-text">
        <h3 class="recap-title">Data Jadwal Dokter</h3>
        <p class="recap-subtitle">Rekapitulasi jadwal praktik mingguan dokter per poliklinik.</p>
      </div>
      <div v-if="loading" class="recap-badge-loading">
        <span class="spinner-small"></span>
        <span>Memuat rekap...</span>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="recap-state error-state">
      <div class="state-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      </div>
      <div class="state-content">
        <p class="state-text">{{ error }}</p>
        <button type="button" class="btn-retry" @click="emit('retry')">
          Coba Lagi
        </button>
      </div>
    </div>

    <!-- Loading State (when no data yet) -->
    <div v-else-if="loading && groupedData.length === 0" class="recap-state loading-state">
      <div class="spinner"></div>
      <p class="state-text">Memuat data rekap jadwal dokter...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="groupedData.length === 0" class="recap-state empty-state">
      <div class="state-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="36" height="36">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
      </div>
      <p class="empty-title">Belum ada jadwal dokter</p>
      <p class="state-text">Data jadwal dokter yang ditambahkan akan otomatis terangkum dalam tabel rekap ini.</p>
    </div>

    <!-- Table -->
    <div v-else class="table-wrapper">
      <div class="table-container">
        <table class="data-table recap-table">
          <thead>
            <tr>
              <th rowspan="2" class="col-polyclinic">Nama Poliklinik</th>
              <th rowspan="2" class="col-doctor">Nama Dokter</th>
              <th colspan="7" class="col-schedule-header text-center">Jadwal Hari</th>
            </tr>
            <tr>
              <th class="col-day text-center">Senin</th>
              <th class="col-day text-center">Selasa</th>
              <th class="col-day text-center">Rabu</th>
              <th class="col-day text-center">Kamis</th>
              <th class="col-day text-center">Jumat</th>
              <th class="col-day text-center">Sabtu</th>
              <th class="col-day text-center">Minggu</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="poly in groupedData" :key="poly.id">
              <tr v-for="(doc, docIndex) in poly.doctors" :key="`${poly.id}-${doc.id}`">
                <!-- Polyclinic Name with rowspan across all doctors in this polyclinic -->
                <td 
                  v-if="docIndex === 0" 
                  :rowspan="poly.doctors.length" 
                  class="cell-polyclinic"
                >
                  <span class="poly-name">{{ poly.name }}</span>
                </td>
                
                <!-- Doctor Name -->
                <td class="cell-doctor">
                  <div class="doctor-info">
                    <span class="doctor-name">{{ doc.name }}</span>
                    <span v-if="doc.specialization" class="doctor-spec text-muted">{{ doc.specialization }}</span>
                  </div>
                </td>

                <!-- Days 1 to 7 (Senin to Minggu) -->
                <td v-for="day in 7" :key="day" class="cell-day text-center">
                  <template v-if="doc.days[day] && doc.days[day].length > 0">
                    <div class="sessions-list">
                      <div 
                        v-for="session in doc.days[day]" 
                        :key="session.id" 
                        class="session-chip"
                        :class="{ 'is-holiday': session.is_holiday }"
                      >
                        <span class="time-range">{{ formatTimeRange(session.start_time, session.end_time) }}</span>
                        <span v-if="session.is_holiday" class="holiday-pill" title="Jadwal Libur">Libur</span>
                      </div>
                    </div>
                  </template>
                  <span v-else class="text-muted cell-empty">—</span>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.recap-card {
  background: var(--color-card-bg, #ffffff);
  border-radius: 12px;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  margin-top: 1.5rem;
  overflow: hidden;
}

.recap-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft, #cbd5e1);
  background: #ffffff;
}

.header-text {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.recap-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-navy, #1e293b);
}

.recap-subtitle {
  margin: 0;
  font-size: 0.85rem;
  color: var(--color-text-secondary, #64748b);
}

.recap-badge-loading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.82rem;
  color: var(--color-text-secondary, #64748b);
  background: #f1f5f9;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
}

.spinner-small {
  width: 14px;
  height: 14px;
  border: 2px solid #cbd5e1;
  border-top-color: var(--color-primary, #0284c7);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

/* States */
.recap-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.5rem;
  text-align: center;
  gap: 0.75rem;
}

.state-icon {
  color: #94a3b8;
}

.state-text {
  font-size: 0.875rem;
  color: var(--color-text-secondary, #64748b);
  margin: 0;
  max-width: 480px;
}

.empty-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-navy, #1e293b);
  margin: 0;
}

.error-state {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  margin: 1.5rem;
  padding: 1.5rem;
  color: #dc2626;
}

.error-state .state-icon {
  color: #dc2626;
}

.error-state .state-text {
  color: #991b1b;
  font-weight: 500;
}

.btn-retry {
  margin-top: 0.75rem;
  background: #ffffff;
  color: #dc2626;
  border: 1px solid #fca5a5;
  padding: 0.45rem 1rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-retry:hover {
  background: #dc2626;
  color: #ffffff;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #e2e8f0;
  border-top-color: var(--color-primary, #0284c7);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Table styles */
.table-wrapper {
  width: 100%;
}

.table-container {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.recap-table {
  border-collapse: collapse;
  width: 100%;
  min-width: 980px;
}

.recap-table th {
  background: #f8fafc;
  color: var(--color-text-navy, #1e293b);
  font-weight: 600;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  padding: 0.75rem 0.85rem;
  font-size: 0.85rem;
}

.col-schedule-header {
  background: #f1f5f9 !important;
  color: var(--color-text-navy, #1e293b);
  font-weight: 700 !important;
  letter-spacing: 0.02em;
}

.col-polyclinic {
  min-width: 160px;
  width: 18%;
  vertical-align: middle;
}

.col-doctor {
  min-width: 180px;
  width: 22%;
  vertical-align: middle;
}

.col-day {
  min-width: 105px;
  width: 8.5%;
}

.recap-table td {
  border: 1px solid var(--color-border-soft, #e2e8f0);
  padding: 0.75rem 0.85rem;
  vertical-align: middle;
  font-size: 0.85rem;
  background: #ffffff;
}

.cell-polyclinic {
  background: #fbfcfe !important;
  font-weight: 600;
  color: var(--color-text-navy, #1e293b);
  vertical-align: top !important;
  padding-top: 1rem !important;
  word-break: break-word;
}

.poly-name {
  display: inline-block;
  line-height: 1.4;
}

.cell-doctor {
  font-weight: 500;
  color: var(--color-text-navy, #1e293b);
  word-break: break-word;
}

.doctor-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.doctor-name {
  line-height: 1.35;
}

.doctor-spec {
  font-size: 0.75rem;
}

.cell-day {
  vertical-align: top;
  padding: 0.6rem 0.5rem;
}

.cell-empty {
  display: inline-block;
  padding: 0.25rem 0;
  font-weight: 500;
}

.sessions-list {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  align-items: center;
}

.session-chip {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 0.15rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 0.3rem 0.5rem;
  border-radius: 6px;
  width: 100%;
  box-sizing: border-box;
  transition: all 0.15s;
}

.session-chip .time-range {
  font-size: 0.8rem;
  font-family: var(--font-mono, monospace);
  font-weight: 600;
  color: var(--color-text-navy, #0f172a);
  white-space: nowrap;
}

/* Distinct Holiday / Libur styling */
.session-chip.is-holiday {
  background: #fff1f2;
  border-color: #fecdd3;
}

.session-chip.is-holiday .time-range {
  color: #be123c;
  text-decoration: line-through;
  opacity: 0.85;
}

.holiday-pill {
  display: inline-block;
  background: #fee2e2;
  color: #b91c1c;
  font-size: 0.68rem;
  font-weight: 700;
  padding: 0.05rem 0.4rem;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.text-center {
  text-align: center;
}

.text-muted {
  color: var(--color-text-secondary, #94a3b8);
}
</style>
