<script setup>
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
  mode: {
    type: String,
    default: 'start' // 'start' for Mulai Periksa, 'enter' for Masuk Poli
  }
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
  <div v-if="isOpen && patient" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="start-modal-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-title-group">
          <div class="header-icon" :class="mode === 'enter' ? 'icon-blue' : 'icon-teal'">
            <svg v-if="mode === 'enter'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
              <polyline points="10 17 15 12 10 7"></polyline>
              <line x1="15" y1="12" x2="3" y2="12"></line>
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="5 3 19 12 5 21 5 3"></polygon>
            </svg>
          </div>
          <div>
            <h3 id="start-modal-title" class="modal-title">
              {{ mode === 'enter' ? 'Konfirmasi Masuk Poli' : 'Konfirmasi Mulai Pemeriksaan' }}
            </h3>
            <p class="modal-subtitle">
              {{ mode === 'enter' 
                ? 'Pasien telah dipanggil dan siap memasuki ruang periksa poliklinik.' 
                : 'Ubah status pasien antrean menjadi sedang diperiksa oleh dokter.' }}
            </p>
          </div>
        </div>
        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup modal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <!-- Patient Identity Summary Box -->
        <div class="patient-summary-box">
          <div class="summary-top">
            <span class="queue-chip">{{ patient.queue_number }}</span>
            <div class="patient-primary-info">
              <h4 class="patient-name">{{ patient.patient_name }}</h4>
              <span class="patient-sub font-mono">{{ patient.medical_record_number }} &bull; NIK: {{ patient.nik }}</span>
            </div>
          </div>

          <div class="summary-grid">
            <div class="summary-item">
              <span class="item-label">Usia / Gender</span>
              <span class="item-val">{{ calculateAge(patient.birth_date) }} Tahun &bull; {{ patient.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
            </div>
            <div class="summary-item">
              <span class="item-label">Poliklinik Tujuan</span>
              <span class="item-val font-semibold">{{ patient.polyclinic }}</span>
            </div>
            <div class="summary-item">
              <span class="item-label">Ruang Pemeriksaan</span>
              <span class="item-val">{{ patient.building }} {{ patient.floor }} &bull; {{ patient.room }}</span>
            </div>
            <div class="summary-item">
              <span class="item-label">Dokter Pemeriksa</span>
              <span class="item-val font-semibold">{{ patient.doctor }}</span>
            </div>
            <div class="summary-item">
              <span class="item-label">Jenis Penjamin</span>
              <span class="item-val">{{ patient.guarantor_type }} ({{ patient.guarantor_detail }})</span>
            </div>
            <div class="summary-item">
              <span class="item-label">Kategori Prioritas</span>
              <span class="item-val priority-text">{{ patient.priority_note || patient.priority }}</span>
            </div>
          </div>
        </div>

        <!-- Simulation Notice Alert -->
        <div class="simulation-alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <div class="alert-text">
            <strong>Mode Simulasi Frontend:</strong> Tindakan ini hanya memperbarui status pasien di state lokal browser menjadi <em>Sedang Diperiksa</em> tanpa menyimpan ke database rumah sakit.
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button type="button" class="btn-secondary" @click="emit('close')">
          Batal
        </button>
        <button type="button" class="btn-primary" @click="emit('confirm', patient)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>{{ mode === 'enter' ? 'Konfirmasi Masuk Poli' : 'Mulai Pemeriksaan Sekarang' }}</span>
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
  max-width: 580px;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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

.header-title-group {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.header-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-teal {
  background: var(--color-primary-light, #e6f6f5);
  color: var(--color-primary, #087f78);
}

.icon-blue {
  background: #e0f2fe;
  color: #0284c7;
}

.header-icon svg {
  width: 20px;
  height: 20px;
}

.modal-title {
  margin: 0 0 0.25rem 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
}

.modal-subtitle {
  margin: 0;
  font-size: 0.825rem;
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

.modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.patient-summary-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.summary-top {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px dashed #cbd5e1;
}

.queue-chip {
  padding: 0.35rem 0.75rem;
  background: #e0f2fe;
  color: #0369a1;
  font-family: monospace;
  font-size: 1.1rem;
  font-weight: 800;
  border-radius: 8px;
}

.patient-primary-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.patient-name {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.patient-sub {
  font-size: 0.8rem;
  color: #64748b;
}

.summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem 1rem;
}

.summary-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.item-label {
  font-size: 0.72rem;
  color: #64748b;
  font-weight: 600;
}

.item-val {
  font-size: 0.825rem;
  color: #1e293b;
}

.priority-text {
  color: #b45309;
  font-weight: 600;
}

.simulation-alert {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  background: #f0fdfa;
  border: 1px solid #ccfbf1;
  border-radius: 8px;
  color: #0f766e;
  font-size: 0.825rem;
  line-height: 1.4;
}

.simulation-alert svg {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  margin-top: 0.1rem;
  color: #0d9488;
}

.modal-footer {
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-secondary, .btn-primary {
  height: 38px;
  padding: 0 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  transition: all 0.15s ease;
}

.btn-secondary {
  background: #ffffff;
  color: #64748b;
  border: 1px solid #cbd5e1;
}

.btn-secondary:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.btn-primary {
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: none;
}

.btn-primary:hover {
  background: var(--color-primary-dark, #06635e);
}

.btn-primary svg {
  width: 16px;
  height: 16px;
}
</style>
