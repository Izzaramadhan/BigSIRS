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
  }
});

const emit = defineEmits(['close', 'print-simulated']);
</script>

<template>
  <div v-if="isOpen && patient" class="modal-backdrop" @click.self="emit('close')">
    <div class="resume-modal-card" role="dialog" aria-modal="true" aria-labelledby="resume-title">
      <!-- Header -->
      <div class="resume-header">
        <div class="header-left">
          <div class="resume-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
          </div>
          <div>
            <h3 id="resume-title" class="title">Pratinjau Resume Medis Rawat Jalan</h3>
            <p class="subtitle">Ringkasan pelayanan klinis rawat jalan pasien selesai periksa</p>
          </div>
        </div>

        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Demo Disclaimer Alert -->
      <div class="demo-bar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="16" x2="12" y2="12"></line>
          <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <span>
          <strong>Dokumen Simulasi:</strong> Data resume medis ini merupakan representasi fiktif untuk keperluan demonstrasi antarmuka dan belum terhubung ke sistem arsip rekam medis nyata.
        </span>
      </div>

      <!-- Resume Paper Body -->
      <div class="resume-body">
        <div class="paper-sheet">
          <!-- Hospital Letterhead / Header -->
          <div class="paper-header">
            <div class="hospital-info">
              <h4 class="hospital-name">RS SISFOMEDIKA UTAMA</h4>
              <p class="hospital-address">Jl. Kesehatan No. 128, Jakarta Pusat &bull; Telp. (021) 555-0199</p>
            </div>
            <div class="doc-badge">RESUME MEDIS RAWAT JALAN</div>
          </div>

          <div class="divider-line"></div>

          <!-- Patient Identity Section -->
          <div class="section-block">
            <h5 class="section-title">I. IDENTITAS PASIEN & KUNJUNGAN</h5>
            <div class="info-grid">
              <div class="info-cell">
                <span class="cell-label">No. Rekam Medis:</span>
                <span class="cell-val font-mono font-semibold">{{ patient.medical_record_number }}</span>
              </div>
              <div class="info-cell">
                <span class="cell-label">No. Antrean:</span>
                <span class="cell-val font-mono font-semibold">{{ patient.queue_number }}</span>
              </div>
              <div class="info-cell">
                <span class="cell-label">Nama Lengkap Pasien:</span>
                <span class="cell-val font-semibold">{{ patient.patient_name }}</span>
              </div>
              <div class="info-cell">
                <span class="cell-label">NIK:</span>
                <span class="cell-val font-mono">{{ patient.nik }}</span>
              </div>
              <div class="info-cell">
                <span class="cell-label">Usia / Jenis Kelamin:</span>
                <span class="cell-val">{{ calculateAge(patient.birth_date) }} Thn / {{ patient.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
              </div>
              <div class="info-cell">
                <span class="cell-label">Jenis Penjamin:</span>
                <span class="cell-val">{{ patient.guarantor_type }} ({{ patient.guarantor_detail }})</span>
              </div>
              <div class="info-cell">
                <span class="cell-label">Poliklinik Tujuan:</span>
                <span class="cell-val font-semibold">{{ patient.polyclinic }}</span>
              </div>
              <div class="info-cell">
                <span class="cell-label">Dokter Pemeriksa:</span>
                <span class="cell-val font-semibold">{{ patient.doctor }}</span>
              </div>
              <div class="info-cell">
                <span class="cell-label">Waktu Masuk Admisi:</span>
                <span class="cell-val font-mono">{{ patient.admission_time }} WIB</span>
              </div>
              <div class="info-cell">
                <span class="cell-label">Waktu Selesai Periksa:</span>
                <span class="cell-val font-mono">{{ patient.completed_time || '10:45' }} WIB</span>
              </div>
            </div>
          </div>

          <!-- Clinical Findings Section -->
          <div class="section-block">
            <h5 class="section-title">II. RINGKASAN KLINIS & ANAMNESIS</h5>
            <p class="summary-paragraph">
              <strong>Keluhan Utama & Riwayat:</strong> {{ patient.soap?.subjective || patient.clinical_note }}
            </p>
            <p class="summary-paragraph">
              <strong>Tanda Vital:</strong> {{ patient.soap?.objective || 'TD: 120/80 mmHg, Nadi: 80x/mnt, Pernapasan: 18x/mnt, Suhu: 36.6C.' }}
            </p>
          </div>

          <!-- Diagnosis Section -->
          <div class="section-block">
            <h5 class="section-title">III. DIAGNOSIS KERJA / DIAGNOSIS AKHIR</h5>
            <div class="diagnosis-box">
              <div class="diag-item">
                <span class="diag-type">Diagnosis Utama:</span>
                <span class="diag-text font-semibold">{{ patient.soap?.assessment || 'Kontrol Hipertensi Primer Terkontrol Baik (ICD-10: I10)' }}</span>
              </div>
              <div class="diag-item">
                <span class="diag-type">Diagnosis Sekunder:</span>
                <span class="diag-text text-muted">- (Tidak ada komplikasi penyerta)</span>
              </div>
            </div>
          </div>

          <!-- Therapy & Plan Section -->
          <div class="section-block">
            <h5 class="section-title">IV. TERAPI & INSTRUKSI TINDAK LANJUT</h5>
            <p class="summary-paragraph">
              <strong>Instruksi Dokter:</strong> {{ patient.soap?.plan || 'Lanjutkan obat rutin amlodipine 5mg 1x1 tab pagi, kurangi konsumsi garam, kontrol kembali 30 hari lagi atau bila keluhan berulang.' }}
            </p>
          </div>

          <!-- Doctor Sign Area -->
          <div class="sign-block">
            <div class="sign-date">Jakarta, {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</div>
            <div class="sign-space"></div>
            <div class="sign-doctor font-semibold">{{ patient.doctor }}</div>
            <div class="sign-sip text-muted text-xs">SIP: 446.1/1042/Dinkes/2024</div>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="resume-footer">
        <button type="button" class="btn-secondary" @click="emit('close')">
          Tutup
        </button>
        <button type="button" class="btn-primary" @click="emit('print-simulated', patient)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
          <span>Cetak Dokumen Resume</span>
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

.resume-modal-card {
  width: 100%;
  max-width: 780px;
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

.resume-header {
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

.resume-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #dcfce7;
  color: #16a34a;
  display: flex;
  align-items: center;
  justify-content: center;
}

.resume-icon svg {
  width: 22px;
  height: 22px;
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

.demo-bar {
  padding: 0.6rem 1.5rem;
  background: #fefce8;
  border-bottom: 1px solid #fef08a;
  color: #854d0e;
  font-size: 0.775rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.demo-bar svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  color: #ca8a04;
}

.resume-body {
  padding: 1.5rem;
  overflow-y: auto;
  background: #f8fafc;
  flex: 1;
}

.paper-sheet {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 1.75rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.paper-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.hospital-name {
  margin: 0 0 0.25rem 0;
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--color-primary, #087f78);
  letter-spacing: 0.04em;
}

.hospital-address {
  margin: 0;
  font-size: 0.775rem;
  color: #64748b;
}

.doc-badge {
  padding: 0.35rem 0.75rem;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #334155;
  border-radius: 6px;
}

.divider-line {
  height: 2px;
  background: #334155;
}

.section-block {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.section-title {
  margin: 0;
  font-size: 0.8rem;
  font-weight: 700;
  color: #1e293b;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 0.25rem;
  letter-spacing: 0.03em;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem 1rem;
  font-size: 0.8rem;
}

.info-cell {
  display: flex;
  gap: 0.4rem;
}

.cell-label {
  color: #64748b;
  min-width: 130px;
}

.cell-val {
  color: #1e293b;
}

.summary-paragraph {
  margin: 0;
  font-size: 0.825rem;
  color: #334155;
  line-height: 1.45;
}

.diagnosis-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.825rem;
}

.diag-item {
  display: flex;
  gap: 0.5rem;
}

.diag-type {
  min-width: 130px;
  color: #64748b;
  font-weight: 600;
}

.sign-block {
  align-self: flex-end;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 1rem;
}

.sign-date {
  font-size: 0.8rem;
  color: #475569;
}

.sign-space {
  height: 50px;
}

.sign-doctor {
  font-size: 0.85rem;
  color: #0f172a;
}

.resume-footer {
  padding: 1rem 1.5rem;
  background: #ffffff;
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
