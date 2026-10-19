<script setup>
import { ref, watch } from 'vue';
import { calculateAge } from '@/mocks/examinations';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  patient: {
    type: Object,
    default: null
  },
  initialTab: {
    type: String,
    default: 'patient' // 'patient', 'queue', 'history'
  }
});

const emit = defineEmits(['close']);

const activeTab = ref(props.initialTab);

watch(
  () => props.initialTab,
  (newTab) => {
    activeTab.value = newTab;
  }
);
</script>

<template>
  <div v-if="isOpen && patient" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="detail-modal-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="header-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </div>
          <div>
            <h3 id="detail-modal-title" class="title">{{ patient.patient_name }}</h3>
            <p class="subtitle font-mono">{{ patient.medical_record_number }} &bull; NIK: {{ patient.nik }}</p>
          </div>
        </div>

        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Tab Navigation -->
      <div class="detail-tabs">
        <button
          type="button"
          class="tab-btn"
          :class="{ 'is-active': activeTab === 'patient' }"
          @click="activeTab = 'patient'"
        >
          Detail Pasien
        </button>
        <button
          type="button"
          class="tab-btn"
          :class="{ 'is-active': activeTab === 'queue' }"
          @click="activeTab = 'queue'"
        >
          Informasi Antrean
        </button>
        <button
          type="button"
          class="tab-btn"
          :class="{ 'is-active': activeTab === 'history' }"
          @click="activeTab = 'history'"
        >
          Riwayat Kunjungan
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <!-- Tab 1: Detail Pasien -->
        <div v-if="activeTab === 'patient'" class="tab-pane">
          <div class="grid-2col">
            <div class="info-group">
              <span class="label">Nama Lengkap</span>
              <span class="val font-semibold">{{ patient.patient_name }}</span>
            </div>
            <div class="info-group">
              <span class="label">Nomor Rekam Medis (RM)</span>
              <span class="val font-mono font-semibold">{{ patient.medical_record_number }}</span>
            </div>
            <div class="info-group">
              <span class="label">NIK (Nomor Induk Kependudukan)</span>
              <span class="val font-mono">{{ patient.nik }}</span>
            </div>
            <div class="info-group">
              <span class="label">Tanggal Lahir / Usia</span>
              <span class="val">{{ patient.birth_date }} ({{ calculateAge(patient.birth_date) }} Tahun)</span>
            </div>
            <div class="info-group">
              <span class="label">Jenis Kelamin</span>
              <span class="val">{{ patient.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
            </div>
            <div class="info-group">
              <span class="label">Jenis Penjamin</span>
              <span class="val">{{ patient.guarantor_type }} &bull; {{ patient.guarantor_detail }}</span>
            </div>
            <div class="info-group">
              <span class="label">Kategori Prioritas Medis</span>
              <span class="val font-semibold" :class="patient.priority === 'Normal' ? 'text-muted' : 'text-amber'">
                {{ patient.priority_note || patient.priority }}
              </span>
            </div>
            <div class="info-group">
              <span class="label">Catatan Pengantar / Klinis Awal</span>
              <span class="val">{{ patient.clinical_note }}</span>
            </div>
          </div>
        </div>

        <!-- Tab 2: Info Antrean -->
        <div v-else-if="activeTab === 'queue'" class="tab-pane">
          <div class="queue-card-hero">
            <div class="queue-number-large">{{ patient.queue_number }}</div>
            <div class="queue-details">
              <span class="queue-poly font-semibold">{{ patient.polyclinic }}</span>
              <span class="queue-room">{{ patient.building }} {{ patient.floor }} &bull; {{ patient.room }}</span>
              <span class="queue-doc">Dokter: {{ patient.doctor }}</span>
            </div>
          </div>

          <div class="grid-2col mt-4">
            <div class="info-group">
              <span class="label">Waktu Masuk Antrean Admisi:</span>
              <span class="val font-mono font-semibold">{{ patient.admission_time }} WIB</span>
            </div>
            <div class="info-group">
              <span class="label">Estimasi Waktu Tunggu:</span>
              <span class="val">{{ patient.wait_time_minutes }} Menit</span>
            </div>
            <div class="info-group">
              <span class="label">Status Antrean Saat Ini:</span>
              <span class="val font-semibold">{{ patient.status }}</span>
            </div>
            <div class="info-group">
              <span class="label">Waktu Selesai (Bila Ada):</span>
              <span class="val font-mono">{{ patient.completed_time ? patient.completed_time + ' WIB' : 'Belum Selesai' }}</span>
            </div>
          </div>
        </div>

        <!-- Tab 3: Riwayat Kunjungan -->
        <div v-else class="tab-pane">
          <div class="history-list">
            <div class="history-item">
              <div class="history-dot current"></div>
              <div class="history-content">
                <div class="history-header">
                  <span class="history-date">Hari Ini &bull; {{ patient.admission_time }} WIB</span>
                  <span class="history-badge badge-active">{{ patient.status }}</span>
                </div>
                <div class="history-body">
                  <strong>{{ patient.polyclinic }}</strong> &bull; {{ patient.doctor }}
                  <p class="history-note">{{ patient.clinical_note }}</p>
                </div>
              </div>
            </div>

            <div class="history-item">
              <div class="history-dot"></div>
              <div class="history-content">
                <div class="history-header">
                  <span class="history-date">14 Hari Lalu &bull; 09:20 WIB</span>
                  <span class="history-badge">Selesai</span>
                </div>
                <div class="history-body">
                  <strong>{{ patient.polyclinic }}</strong> &bull; {{ patient.doctor }}
                  <p class="history-note">Kunjungan kontrol rutin evaluasi terapi poliklinik rawat jalan.</p>
                </div>
              </div>
            </div>

            <div class="history-item">
              <div class="history-dot"></div>
              <div class="history-content">
                <div class="history-header">
                  <span class="history-date">45 Hari Lalu &bull; 10:15 WIB</span>
                  <span class="history-badge">Selesai</span>
                </div>
                <div class="history-body">
                  <strong>Poli Umum</strong> &bull; dr. Budi Santoso, Sp.PD
                  <p class="history-note">Pemeriksaan awal dan rujukan internal spesialis.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn-secondary" @click="emit('close')">
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.modal-card {
  width: 100%;
  max-width: 620px;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  animation: modalScale 0.2s ease-out;
}

@keyframes modalScale {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-header {
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid #e2e8f0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.header-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--color-primary-light, #e6f6f5);
  color: var(--color-primary, #087f78);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon svg {
  width: 20px;
  height: 20px;
}

.title {
  margin: 0 0 0.2rem 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
}

.subtitle {
  margin: 0;
  font-size: 0.8rem;
  color: #64748b;
}

.btn-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 6px;
}

.btn-close:hover {
  color: #0f172a;
  background: #f1f5f9;
}

.btn-close svg {
  width: 20px;
  height: 20px;
}

.detail-tabs {
  display: flex;
  gap: 0.25rem;
  padding: 0.5rem 1.5rem 0;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.tab-btn {
  padding: 0.6rem 0.9rem;
  font-size: 0.825rem;
  font-weight: 600;
  color: #64748b;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  transition: all 0.15s ease;
}

.tab-btn:hover {
  color: #0f172a;
}

.tab-btn.is-active {
  color: var(--color-primary, #087f78);
  border-bottom-color: var(--color-primary, #087f78);
  background: #ffffff;
  border-radius: 6px 6px 0 0;
}

.modal-body {
  padding: 1.5rem;
  max-height: 65vh;
  overflow-y: auto;
}

.grid-2col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem 1.25rem;
}

.mt-4 {
  margin-top: 1rem;
}

.info-group {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.label {
  font-size: 0.72rem;
  color: #64748b;
  font-weight: 600;
}

.val {
  font-size: 0.825rem;
  color: #1e293b;
}

.text-amber {
  color: #b45309;
}

.queue-card-hero {
  background: #f0fdfa;
  border: 1px solid #ccfbf1;
  border-radius: 10px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.queue-number-large {
  font-family: monospace;
  font-size: 2rem;
  font-weight: 800;
  color: var(--color-primary, #087f78);
  background: #ffffff;
  border: 1px solid #99f6e4;
  padding: 0.5rem 1rem;
  border-radius: 8px;
}

.queue-details {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.queue-poly {
  font-size: 1rem;
  color: #0f172a;
}

.queue-room {
  font-size: 0.8rem;
  color: #64748b;
}

.queue-doc {
  font-size: 0.825rem;
  color: var(--color-primary, #087f78);
  font-weight: 500;
}

/* History List */
.history-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: relative;
  padding-left: 1rem;
}

.history-list::before {
  content: '';
  position: absolute;
  top: 8px;
  bottom: 8px;
  left: 17px;
  width: 2px;
  background: #e2e8f0;
}

.history-item {
  display: flex;
  gap: 1rem;
  position: relative;
}

.history-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #cbd5e1;
  margin-top: 5px;
  flex-shrink: 0;
  z-index: 1;
}

.history-dot.current {
  background: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.2);
}

.history-content {
  flex: 1;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.history-date {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
}

.history-badge {
  font-size: 0.7rem;
  padding: 0.15rem 0.45rem;
  background: #e2e8f0;
  color: #475569;
  border-radius: 4px;
  font-weight: 600;
}

.history-badge.badge-active {
  background: var(--color-primary-light, #e6f6f5);
  color: var(--color-primary, #087f78);
}

.history-body {
  font-size: 0.825rem;
  color: #334155;
}

.history-note {
  margin: 0.25rem 0 0 0;
  font-size: 0.775rem;
  color: #64748b;
}

.modal-footer {
  padding: 0.85rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
}

.btn-secondary {
  height: 36px;
  padding: 0 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
  transition: all 0.15s ease;
}

.btn-secondary:hover {
  background: #f1f5f9;
  color: #0f172a;
}
</style>
