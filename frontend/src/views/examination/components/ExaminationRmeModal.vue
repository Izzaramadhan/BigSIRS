<script setup>
import { ref } from 'vue';
import { calculateAge } from '@/mocks/examinations';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  patient: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close']);

const activeTab = ref('soap');
</script>

<template>
  <div v-if="isOpen && patient" class="modal-backdrop" @click.self="emit('close')">
    <div class="rme-modal-card" role="dialog" aria-modal="true" aria-labelledby="rme-modal-title">
      <!-- RME Header -->
      <div class="rme-header">
        <div class="rme-header-left">
          <div class="rme-badge-group">
            <span class="rme-logo-badge">RME</span>
            <span class="rme-status-tag">Mode Praktik Terpadu</span>
          </div>
          <div>
            <h3 id="rme-modal-title" class="rme-title">Rekam Medis Elektronik (RME) Rawat Jalan</h3>
            <p class="rme-subtitle">Modul pengisian catatan medis klinis terstandar SatuSehat & BPJS</p>
          </div>
        </div>

        <button type="button" class="btn-close" @click="emit('close')" aria-label="Kembali ke Daftar Pemeriksaan">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Patient Banner -->
      <div class="patient-banner">
        <div class="banner-avatar">
          {{ patient.patient_name.charAt(0) }}
        </div>
        <div class="banner-info">
          <div class="banner-name-row">
            <h4 class="banner-patient-name">{{ patient.patient_name }}</h4>
            <span class="banner-queue">{{ patient.queue_number }}</span>
            <span class="banner-rm font-mono">{{ patient.medical_record_number }}</span>
          </div>
          <div class="banner-details-row">
            <span>{{ calculateAge(patient.birth_date) }} Tahun &bull; {{ patient.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
            <span class="dot-sep">&bull;</span>
            <span>NIK: {{ patient.nik }}</span>
            <span class="dot-sep">&bull;</span>
            <span class="text-teal font-semibold">{{ patient.polyclinic }}</span>
            <span class="dot-sep">&bull;</span>
            <span>{{ patient.doctor }}</span>
            <span class="dot-sep">&bull;</span>
            <span class="guarantor-chip">{{ patient.guarantor_type }}</span>
          </div>
        </div>
      </div>

      <!-- Disclaimer Banner -->
      <div class="demo-disclaimer-banner">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="16" x2="12" y2="12"></line>
          <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <span>
          <strong>Placeholder Modul RME:</strong> Form rekam medis lengkap akan diintegrasikan pada fase selanjutnya. Data pada tampilan ini hanya simulasi demonstrasi frontend.
        </span>
      </div>

      <!-- Tab Navigation -->
      <div class="rme-tabs">
        <button
          type="button"
          class="rme-tab-btn"
          :class="{ 'is-active': activeTab === 'soap' }"
          @click="activeTab = 'soap'"
        >
          Anamnesis & SOAP
        </button>
        <button
          type="button"
          class="rme-tab-btn"
          :class="{ 'is-active': activeTab === 'diagnosis' }"
          @click="activeTab = 'diagnosis'"
        >
          Diagnosis (ICD-10)
        </button>
        <button
          type="button"
          class="rme-tab-btn"
          :class="{ 'is-active': activeTab === 'prescription' }"
          @click="activeTab = 'prescription'"
        >
          Resep Obat (E-Prescription)
        </button>
        <button
          type="button"
          class="rme-tab-btn"
          :class="{ 'is-active': activeTab === 'action' }"
          @click="activeTab = 'action'"
        >
          Tindakan Medis & Laboratorium
        </button>
      </div>

      <!-- Tab Content Area -->
      <div class="rme-tab-content">
        <!-- Tab 1: SOAP -->
        <div v-if="activeTab === 'soap'" class="tab-pane">
          <div class="soap-grid">
            <div class="soap-box">
              <div class="soap-label">S &bull; Subjektif (Anamnesis / Keluhan Utama)</div>
              <div class="soap-val">{{ patient.soap?.subjective || patient.clinical_note }}</div>
            </div>
            <div class="soap-box">
              <div class="soap-label">O &bull; Objektif (Pemeriksaan Fisik & Tanda Vital)</div>
              <div class="soap-val">{{ patient.soap?.objective || 'TD: 120/80 mmHg, N: 80x/mnt, RR: 18x/mnt, Suhu: 36.5C.' }}</div>
            </div>
            <div class="soap-box">
              <div class="soap-label">A &bull; Asesmen (Diagnosis Kerja Klinis)</div>
              <div class="soap-val">{{ patient.soap?.assessment || 'Pemeriksaan rutin kontrol poliklinik spesialis.' }}</div>
            </div>
            <div class="soap-box">
              <div class="soap-label">P &bull; Planning (Rencana Terapi & Tindak Lanjut)</div>
              <div class="soap-val">{{ patient.soap?.plan || 'Terapi farmakologis simptomatis, edukasi kontrol 30 hari lagi.' }}</div>
            </div>
          </div>
        </div>

        <!-- Tab 2: Diagnosis ICD-10 -->
        <div v-else-if="activeTab === 'diagnosis'" class="tab-pane">
          <div class="empty-tab-box">
            <div class="tab-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
              </svg>
            </div>
            <h4>Katalog Diagnosis ICD-10 Simulasi</h4>
            <p>I10 - Essential (primary) hypertension &bull; E11 - Non-insulin-dependent diabetes mellitus</p>
            <span class="badge-placeholder">Modul Bridging SatuSehat Siap Terhubung</span>
          </div>
        </div>

        <!-- Tab 3: Resep Obat -->
        <div v-else-if="activeTab === 'prescription'" class="tab-pane">
          <div class="empty-tab-box">
            <div class="tab-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.5 20.5l10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
                <path d="m8.5 8.5 7 7"></path>
              </svg>
            </div>
            <h4>E-Prescription & Farmasi Logistik</h4>
            <p>Terintegrasi dengan Formularium Nasional, BPJS PRB, dan master obat SIMRS BigSIRS.</p>
            <span class="badge-placeholder">Form Resep Siap Terhubung ke Modul Logistik</span>
          </div>
        </div>

        <!-- Tab 4: Tindakan & Lab -->
        <div v-else class="tab-pane">
          <div class="empty-tab-box">
            <div class="tab-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
              </svg>
            </div>
            <h4>Order Pemeriksaan Penunjang (Lab / Radiologi)</h4>
            <p>Permintaan pemeriksaan Darah Lengkap, Foto Thorax, atau USG Klinis.</p>
            <span class="badge-placeholder">Koneksi LIS / RIS Simulasi</span>
          </div>
        </div>
      </div>

      <!-- RME Footer -->
      <div class="rme-footer">
        <button type="button" class="btn-primary" @click="emit('close')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          <span>Kembali ke Daftar Pemeriksaan</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1.5rem;
}

.rme-modal-card {
  width: 100%;
  max-width: 860px;
  max-height: 90vh;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  display: flex;
  flex-direction: column;
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

.rme-header {
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid #e2e8f0;
}

.rme-header-left {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
}

.rme-badge-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  align-items: center;
}

.rme-logo-badge {
  background: var(--color-primary, #087f78);
  color: #ffffff;
  font-weight: 800;
  font-size: 0.75rem;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  letter-spacing: 0.05em;
}

.rme-status-tag {
  font-size: 0.65rem;
  font-weight: 600;
  color: #0369a1;
  background: #e0f2fe;
  padding: 0.1rem 0.4rem;
  border-radius: 3px;
  white-space: nowrap;
}

.rme-title {
  margin: 0 0 0.2rem 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: #0f172a;
}

.rme-subtitle {
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

.patient-banner {
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.banner-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--color-primary-light, #e6f6f5);
  color: var(--color-primary, #087f78);
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.banner-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}

.banner-name-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.banner-patient-name {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.banner-queue {
  background: #e0f2fe;
  color: #0369a1;
  font-family: monospace;
  font-size: 0.85rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}

.banner-rm {
  font-size: 0.825rem;
  color: #475569;
}

.banner-details-row {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.8rem;
  color: #64748b;
  flex-wrap: wrap;
}

.dot-sep {
  color: #cbd5e1;
}

.text-teal {
  color: var(--color-primary, #087f78);
}

.guarantor-chip {
  background: #f1f5f9;
  color: #334155;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  font-weight: 500;
}

.demo-disclaimer-banner {
  padding: 0.65rem 1.5rem;
  background: #fefce8;
  border-bottom: 1px solid #fef08a;
  color: #854d0e;
  font-size: 0.775rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.demo-disclaimer-banner svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  color: #ca8a04;
}

.rme-tabs {
  display: flex;
  gap: 0.25rem;
  padding: 0.5rem 1.5rem 0;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  overflow-x: auto;
}

.rme-tab-btn {
  padding: 0.65rem 1rem;
  font-size: 0.825rem;
  font-weight: 600;
  color: #64748b;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.rme-tab-btn:hover {
  color: #0f172a;
}

.rme-tab-btn.is-active {
  color: var(--color-primary, #087f78);
  border-bottom-color: var(--color-primary, #087f78);
  background: #ffffff;
  border-radius: 6px 6px 0 0;
}

.rme-tab-content {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.soap-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

.soap-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
}

.soap-label {
  font-size: 0.775rem;
  font-weight: 700;
  color: var(--color-primary, #087f78);
  margin-bottom: 0.4rem;
  letter-spacing: 0.02em;
}

.soap-val {
  font-size: 0.85rem;
  color: #334155;
  line-height: 1.5;
}

.empty-tab-box {
  text-align: center;
  padding: 2.5rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.tab-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: #f1f5f9;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.5rem;
}

.tab-icon svg {
  width: 24px;
  height: 24px;
}

.empty-tab-box h4 {
  margin: 0;
  font-size: 1rem;
  color: #0f172a;
}

.empty-tab-box p {
  margin: 0;
  font-size: 0.825rem;
  color: #64748b;
  max-width: 450px;
}

.badge-placeholder {
  margin-top: 0.5rem;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-primary, #087f78);
  background: var(--color-primary-light, #e6f6f5);
  padding: 0.25rem 0.65rem;
  border-radius: 20px;
}

.rme-footer {
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
}

.btn-primary {
  height: 38px;
  padding: 0 1.25rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #ffffff;
  background: var(--color-primary, #087f78);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-primary:hover {
  background: var(--color-primary-dark, #06635e);
}

.btn-primary svg {
  width: 16px;
  height: 16px;
}
</style>
