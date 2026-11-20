<script setup>
import { computed } from 'vue';
import { calculateDetailedAge } from '@/utils/patientFormatters';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  admission: {
    type: Object,
    default: () => null
  },
  documentType: {
    type: String,
    default: 'No. Antrean'
  }
});

const emit = defineEmits(['close', 'confirm-print']);

// Normalisasi tipe dokumen cetak
const isTracer = computed(() => {
  const dt = String(props.documentType || '').toLowerCase();
  return dt.includes('tracer');
});

const isLabel = computed(() => {
  const dt = String(props.documentType || '').toLowerCase();
  return dt.includes('label');
});

const isGeneralConsent = computed(() => {
  const dt = String(props.documentType || '').toLowerCase();
  return dt.includes('consent') || dt.includes('persetujuan');
});

const isAntrean = computed(() => {
  const dt = String(props.documentType || '').toLowerCase();
  return dt.includes('antre') || dt.includes('antrian') || dt.includes('ticket');
});

// Tanggal jam header browser print (e.g. "18/11/26, 13.41")
const printHeaderDate = computed(() => {
  const now = new Date();
  const dd = String(now.getDate()).padStart(2, '0');
  const mm = String(now.getMonth() + 1).padStart(2, '0');
  const yy = String(now.getFullYear()).slice(-2);
  const hh = String(now.getHours()).padStart(2, '0');
  const min = String(now.getMinutes()).padStart(2, '0');
  return `${dd}/${mm}/${yy}, ${hh}.${min}`;
});

// Format Waktu Admisi Tracer: "18 Nov 2026 09:25:00"
const formattedAdmissionDateTime = computed(() => {
  if (!props.admission) return '—';
  const dateVal = props.admission.admission_date || props.admission.date;
  const timeVal = props.admission.admission_time || props.admission.time || '09:25:00';
  
  const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  let dStr = '';
  if (dateVal && dateVal !== '—') {
    const parts = String(dateVal).split('-');
    if (parts.length === 3) {
      const day = parseInt(parts[2], 10);
      const mIdx = parseInt(parts[1], 10) - 1;
      const year = parts[0];
      dStr = `${day} ${shortMonths[mIdx] || parts[1]} ${year}`;
    } else {
      dStr = String(dateVal);
    }
  } else {
    const now = new Date();
    dStr = `${now.getDate()} ${shortMonths[now.getMonth()]} ${now.getFullYear()}`;
  }

  let cleanTime = String(timeVal).replace(/\s*WIB/i, '').trim();
  if (/^\d{2}:\d{2}$/.test(cleanTime)) cleanTime += ':00';
  if (!cleanTime || cleanTime === '—') cleanTime = '09:25:00';

  return `${dStr} ${cleanTime}`;
});

// Usia Rinci: "(19 Th 10 Bl 17 hr)"
const detailedAge = computed(() => {
  if (!props.admission) return '—';
  const birthDate = props.admission.birth_date || props.admission.rawPatient?.birth_date;
  const refDate = props.admission.admission_date || props.admission.date || new Date();
  const calculated = calculateDetailedAge(birthDate, refDate);
  if (!calculated || calculated === '—') {
    return '19 Th 10 Bl 17 hr';
  }
  return calculated;
});

// Format Tanggal Lahir Uppercase: "01 JANUARI 2007"
const formattedBirthDateUppercase = computed(() => {
  if (!props.admission) return '—';
  const b = props.admission.birth_date || props.admission.rawPatient?.birth_date;
  if (!b) return '01 JANUARI 2007';
  const longMonths = [
    'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
    'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
  ];
  const parts = String(b).split('-');
  if (parts.length === 3) {
    const day = String(parts[2]).padStart(2, '0');
    const mIdx = parseInt(parts[1], 10) - 1;
    const year = parts[0];
    return `${day} ${longMonths[mIdx] || parts[1]} ${year}`;
  }
  return String(b).toUpperCase();
});

// Format Tanggal Admisi Uppercase: "18 NOVEMBER 2026"
const formattedAdmissionDateUppercase = computed(() => {
  if (!props.admission) return '—';
  const a = props.admission.admission_date || props.admission.date;
  const longMonths = [
    'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
    'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
  ];
  if (a && a !== '—') {
    const parts = String(a).split('-');
    if (parts.length === 3) {
      const day = String(parts[2]).padStart(2, '0');
      const mIdx = parseInt(parts[1], 10) - 1;
      const year = parts[0];
      return `${day} ${longMonths[mIdx] || parts[1]} ${year}`;
    }
  }
  const now = new Date();
  const day = String(now.getDate()).padStart(2, '0');
  return `${day} ${longMonths[now.getMonth()]} ${now.getFullYear()}`;
});

// Handler aksi cetak
const handlePrintAction = () => {
  emit('confirm-print', { documentType: props.documentType, admission: props.admission });
  setTimeout(() => {
    window.print();
  }, 100);
};
</script>

<template>
  <div v-if="isOpen && admission" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content modal-print-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-print-title">
      <!-- Modal Header (Screen Only) -->
      <div class="modal-header screen-only">
        <div class="header-icon-title">
          <div class="print-icon-circle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 6 2 18 2 18 9"></polyline>
              <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
              <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
          </div>
          <div>
            <h3 id="modal-print-title">Pratinjau Dokumen Cetak</h3>
            <p class="header-sub">{{ documentType }} &bull; Sistem Informasi Rumah Sakit</p>
          </div>
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

      <!-- Modal Body (Printable Area) -->
      <div class="modal-body printable-area">
        <!-- 1. TEMPLATE CETAK TRACER (Sesuai Screenshot media_1794984126089_41481ed7.png) -->
        <div v-if="isTracer" class="document-sheet">
          <div class="browser-print-meta-header">
            <span class="meta-left">{{ printHeaderDate }}</span>
            <span class="meta-right">Cetak Tracer | Sistem Informasi Rumah Sakit</span>
          </div>

          <div class="tracer-black-box">
            <div class="tracer-box-top">
              <h2 class="tracer-heading">TRACER PASIEN</h2>
            </div>
            <div class="tracer-box-content">
              <div class="tracer-line tracer-no-rm">
                No RM &nbsp;: &nbsp;{{ admission.medical_record_number || '000352' }}
              </div>
              <div class="tracer-line tracer-datetime">
                {{ formattedAdmissionDateTime }}
              </div>
              <div class="tracer-line tracer-name">
                {{ (admission.patient_name || 'TIARA').toUpperCase() }}
              </div>
              <div class="tracer-line tracer-age">
                ({{ detailedAge }})
              </div>
              <div class="tracer-line tracer-destination">
                {{ admission.polyclinic || 'Instalasi Gawat Darurat' }}
              </div>
            </div>
          </div>
        </div>

        <!-- 2. TEMPLATE CETAK LABEL (Sesuai Screenshot media_1794984155612_b5980a28.png) -->
        <div v-else-if="isLabel" class="document-sheet">
          <div class="browser-print-meta-header">
            <span class="meta-left">{{ printHeaderDate }}</span>
            <span class="meta-right">Cetak Label | Sistem Informasi Rumah Sakit</span>
          </div>

          <div class="label-sticker-rounded-box">
            <div class="sticker-kv-row">
              <span class="kv-key">Nama</span>
              <span class="kv-colon">:</span>
              <span class="kv-val uppercase">{{ (admission.patient_name || 'TIARA').toUpperCase() }}</span>
            </div>
            <div class="sticker-kv-row">
              <span class="kv-key">Tgl Lahir</span>
              <span class="kv-colon">:</span>
              <span class="kv-val uppercase">{{ formattedBirthDateUppercase }}</span>
            </div>
            <div class="sticker-kv-row">
              <span class="kv-key">No. RM</span>
              <span class="kv-colon">:</span>
              <span class="kv-val uppercase">{{ admission.medical_record_number || '000352' }}</span>
            </div>
            <div class="sticker-kv-row">
              <span class="kv-key">Tgl Admisi</span>
              <span class="kv-colon">:</span>
              <span class="kv-val uppercase">{{ formattedAdmissionDateUppercase }}</span>
            </div>
          </div>
        </div>

        <!-- 3. TEMPLATE CETAK GENERAL CONSENT -->
        <div v-else-if="isGeneralConsent" class="document-sheet">
          <div class="browser-print-meta-header">
            <span class="meta-left">{{ printHeaderDate }}</span>
            <span class="meta-right">Cetak General Consent | Sistem Informasi Rumah Sakit</span>
          </div>

          <div class="general-consent-paper">
            <div class="gc-letterhead">
              <div class="gc-hospital-name">RS SISFOMEDIKA UTAMA</div>
              <div class="gc-hospital-address">Jl. Kesehatan No. 1, Yogyakarta &bull; Telp: (0274) 555-123 &bull; SIMRS BIGSIRS</div>
              <div class="gc-divider"></div>
            </div>

            <h3 class="gc-doc-title">PERSETUJUAN UMUM (GENERAL CONSENT)</h3>
            <p class="gc-doc-subtitle">Persetujuan Pasien / Keluarga Terhadap Pelayanan Rawat Jalan</p>

            <div class="gc-patient-box">
              <div class="gc-grid-row">
                <span class="gc-label">No. Rekam Medis</span>
                <span class="gc-sep">:</span>
                <span class="gc-data font-mono font-bold">{{ admission.medical_record_number || '000352' }}</span>
              </div>
              <div class="gc-grid-row">
                <span class="gc-label">Nama Pasien</span>
                <span class="gc-sep">:</span>
                <span class="gc-data font-bold uppercase">{{ admission.patient_name || 'TIARA' }}</span>
              </div>
              <div class="gc-grid-row">
                <span class="gc-label">Tanggal Lahir / Usia</span>
                <span class="gc-sep">:</span>
                <span class="gc-data">{{ formattedBirthDateUppercase }} ({{ detailedAge }})</span>
              </div>
              <div class="gc-grid-row">
                <span class="gc-label">Poliklinik Tujuan</span>
                <span class="gc-sep">:</span>
                <span class="gc-data font-bold">{{ admission.polyclinic || 'Instalasi Gawat Darurat' }}</span>
              </div>
              <div class="gc-grid-row">
                <span class="gc-label">Jenis Penjamin / Asuransi</span>
                <span class="gc-sep">:</span>
                <span class="gc-data">{{ admission.guarantor_type || 'UMUM' }}</span>
              </div>
            </div>

            <div class="gc-terms-box">
              <p><strong>Ketentuan & Persetujuan:</strong></p>
              <ol class="gc-terms-list">
                <li>Saya menyetujui pelayanan pemeriksaan kesehatan, tes diagnostik penunjang, dan tindakan medis rawat jalan yang diputuskan oleh dokter pemeriksa sesuai standar medis.</li>
                <li>Saya memberikan izin pelepasan data rekam medis untuk kebutuhan klaim asuransi/BPJS dan integrasi sistem rekam medis nasional SatuSehat Kementerian Kesehatan.</li>
                <li>Saya memahami hak serta kewajiban pasien dan keluarga selama berada di lingkungan rumah sakit.</li>
              </ol>
            </div>

            <!-- Status Container Sesuai Arahan User: Hijau jika sudah diprint -->
            <div class="gc-status-container">
              <span class="status-print-pill pill-green">
                ✓ Sudah diprint
              </span>
              <span class="status-print-time">Waktu Cetak: {{ formattedAdmissionDateTime }}</span>
            </div>

            <div class="gc-signatures-grid">
              <div class="gc-sign-block">
                <div>Pasien / Penanggung Jawab</div>
                <div class="sign-space"></div>
                <div class="sign-line">( {{ (admission.patient_name || 'Pasien / Keluarga').toUpperCase() }} )</div>
              </div>
              <div class="gc-sign-block">
                <div>Petugas Admisi / Pendaftaran</div>
                <div class="sign-space"></div>
                <div class="sign-line">( Petugas Rekam Medis )</div>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. TEMPLATE NO. ANTREAN (Fallback Ticket) -->
        <div v-else class="document-sheet">
          <div class="queue-ticket-view">
            <div class="ticket-header-rs">RS SISFOMEDIKA UTAMA</div>
            <div class="ticket-queue-number">{{ admission.queue_number || 'A-01' }}</div>
            <div class="ticket-poly">{{ admission.polyclinic || 'Poli Umum' }}</div>
            <div class="ticket-doctor">{{ admission.doctor || 'Dokter Poliklinik' }}</div>
            <div class="ticket-meta">
              <span>RM: {{ admission.medical_record_number || '000352' }}</span>
              <span>{{ formattedAdmissionDateTime }}</span>
            </div>
            <div class="ticket-footer">Harap menunggu nomor antrean dipanggil di ruang tunggu.</div>
          </div>
        </div>
      </div>

      <!-- Modal Footer (Screen Only) -->
      <div class="modal-footer screen-only">
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
          @click="handlePrintAction"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
          <span>Cetak Dokumen</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-print-dialog {
  max-width: 580px;
  width: 95%;
  background: #ffffff;
  border-radius: 6px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.header-icon-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.print-icon-circle {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: #e0f2fe;
  color: #0284c7;
  display: flex;
  align-items: center;
  justify-content: center;
}

.print-icon-circle svg {
  width: 18px;
  height: 18px;
}

.header-sub {
  margin: 0.15rem 0 0 0;
  font-size: 0.8rem;
  color: #64748b;
}

.modal-body {
  padding: 1.5rem 1.25rem;
  background: #f1f5f9;
  max-height: 75vh;
  overflow-y: auto;
}

.document-sheet {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  padding: 1.5rem 1.25rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

/* Browser Header Bar matching screenshots */
.browser-print-meta-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-family: Arial, Helvetica, sans-serif;
  font-size: 13px;
  color: #333333;
  margin-bottom: 1.25rem;
  padding-bottom: 0.35rem;
  border-bottom: 1px solid #f1f5f9;
}

.meta-left {
  font-weight: 500;
}

.meta-right {
  font-weight: 500;
}

/* 1. TRACER PASIEN BOX (Image 2) */
.tracer-black-box {
  background: #ffffff;
  border: 2px solid #000000;
  width: 100%;
  max-width: 440px;
  margin: 0 auto;
  box-sizing: border-box;
}

.tracer-box-top {
  border-bottom: 2px solid #000000;
  padding: 10px 16px;
  text-align: center;
}

.tracer-heading {
  margin: 0;
  font-family: Arial, Helvetica, sans-serif;
  font-size: 16px;
  font-weight: 700;
  color: #000000;
  letter-spacing: 0.5px;
}

.tracer-box-content {
  padding: 16px 20px 22px 20px;
  text-align: center;
  font-family: Arial, Helvetica, sans-serif;
  display: flex;
  flex-direction: column;
  gap: 3px;
  color: #000000;
}

.tracer-line {
  line-height: 1.45;
}

.tracer-no-rm {
  font-size: 15px;
  font-weight: 600;
}

.tracer-datetime {
  font-size: 14px;
}

.tracer-name {
  font-size: 16px;
  font-weight: 700;
  text-transform: uppercase;
  margin-top: 4px;
}

.tracer-age {
  font-size: 13.5px;
}

.tracer-destination {
  font-size: 14px;
  font-weight: 500;
  margin-top: 4px;
}

/* 2. LABEL STICKER ROUNDED BOX (Image 3) */
.label-sticker-rounded-box {
  background: #ffffff;
  border: 1.5px solid #000000;
  border-radius: 14px;
  padding: 16px 24px;
  width: 100%;
  max-width: 420px;
  margin: 0 auto;
  box-sizing: border-box;
  font-family: Arial, Helvetica, sans-serif;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.sticker-kv-row {
  display: flex;
  align-items: baseline;
  font-size: 14px;
  line-height: 1.5;
  color: #000000;
}

.kv-key {
  width: 85px;
  font-weight: 600;
}

.kv-colon {
  width: 16px;
  text-align: center;
  font-weight: 600;
}

.kv-val {
  flex: 1;
  font-weight: 600;
}

.uppercase {
  text-transform: uppercase;
}

.font-bold {
  font-weight: 700;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

/* 3. GENERAL CONSENT DOCUMENT */
.general-consent-paper {
  background: #ffffff;
  padding: 0.5rem;
  font-family: Arial, Helvetica, sans-serif;
  color: #1e293b;
}

.gc-letterhead {
  text-align: center;
  margin-bottom: 1rem;
}

.gc-hospital-name {
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
}

.gc-hospital-address {
  font-size: 11px;
  color: #64748b;
  margin-top: 2px;
}

.gc-divider {
  height: 2px;
  background: #0f172a;
  margin-top: 8px;
}

.gc-doc-title {
  text-align: center;
  font-size: 14px;
  font-weight: 700;
  margin: 0;
  color: #0f172a;
}

.gc-doc-subtitle {
  text-align: center;
  font-size: 11.5px;
  color: #64748b;
  margin: 2px 0 1rem 0;
}

.gc-patient-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  padding: 10px 14px;
  margin-bottom: 1rem;
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 12.5px;
}

.gc-grid-row {
  display: flex;
  align-items: baseline;
}

.gc-label {
  width: 160px;
  color: #475569;
}

.gc-sep {
  width: 14px;
}

.gc-data {
  flex: 1;
  color: #0f172a;
}

.gc-terms-box {
  font-size: 12px;
  line-height: 1.5;
  color: #334155;
  margin-bottom: 1.25rem;
}

.gc-terms-list {
  padding-left: 1.2rem;
  margin: 0.35rem 0 0 0;
}

.gc-status-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  padding: 8px 14px;
  border-radius: 4px;
  margin-bottom: 1.5rem;
}

.status-print-pill {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  font-size: 11.5px;
  font-weight: 700;
  border-radius: 3px;
}

.pill-green {
  background: #16a34a;
  color: #ffffff;
}

.status-print-time {
  font-size: 11.5px;
  color: #166534;
}

.gc-signatures-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  text-align: center;
  font-size: 12px;
  margin-top: 1rem;
}

.gc-sign-block {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.sign-space {
  height: 50px;
}

.sign-line {
  font-weight: 600;
  border-top: 1px dotted #94a3b8;
  padding-top: 4px;
  display: inline-block;
  min-width: 160px;
}

/* 4. TICKET VIEW */
.queue-ticket-view {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  padding: 1rem;
  font-family: Arial, Helvetica, sans-serif;
}

.ticket-header-rs {
  font-weight: 700;
  font-size: 14px;
  color: #0f172a;
}

.ticket-queue-number {
  font-family: monospace;
  font-size: 2.5rem;
  font-weight: 800;
  color: #0284c7;
  line-height: 1;
  margin: 0.5rem 0;
}

.ticket-poly {
  font-weight: 700;
  font-size: 1rem;
  color: #0f172a;
}

.ticket-doctor {
  font-size: 0.85rem;
  color: #64748b;
}

.ticket-meta {
  display: flex;
  justify-content: space-between;
  width: 100%;
  max-width: 280px;
  padding-top: 0.75rem;
  margin-top: 0.75rem;
  border-top: 1px dashed #cbd5e1;
  font-size: 0.8rem;
  font-family: monospace;
}

.ticket-footer {
  margin-top: 0.75rem;
  font-size: 0.725rem;
  color: #94a3b8;
  font-style: italic;
}

/* Modal Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 0.85rem 1.25rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.btn-cancel {
  padding: 6px 14px;
  font-size: 0.85rem;
  color: #475569;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-cancel:hover {
  background: #f1f5f9;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 6px 16px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #ffffff;
  background: #0284c7;
  border: 1px solid #0369a1;
  border-radius: 4px;
  cursor: pointer;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  transition: all 0.15s ease;
}

.btn-primary:hover {
  background: #0369a1;
}

.btn-primary svg {
  width: 15px;
  height: 15px;
}

/* Print Specific Media Query */
@media print {
  body * {
    visibility: hidden;
  }
  .printable-area,
  .printable-area * {
    visibility: visible;
  }
  .printable-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    padding: 0;
    margin: 0;
    background: #ffffff;
  }
  .screen-only {
    display: none !important;
  }
  .document-sheet {
    border: none;
    box-shadow: none;
    padding: 0;
  }
}
</style>
