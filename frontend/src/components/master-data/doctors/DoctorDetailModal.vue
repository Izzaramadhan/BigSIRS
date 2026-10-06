<script setup>
import { computed } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  detail: {
    type: Object,
    default: () => null
  }
});

const emit = defineEmits(['close', 'edit']);

const person = computed(() => {
  return props.detail?.person || props.detail?.employee?.person || props.detail?.employee || {};
});

const professional = computed(() => {
  return props.detail?.professional || props.detail || {};
});

const formatString = (val) => val || '—';

const formatDate = (dateString) => {
  if (!dateString) return '—';
  try {
    return new Intl.DateTimeFormat('id-ID', {
      day: '2-digit',
      month: 'long',
      year: 'numeric'
    }).format(new Date(dateString));
  } catch {
    return dateString;
  }
};

const formatGender = (gender) => {
  if (gender === 'L') return 'Laki-laki';
  if (gender === 'P') return 'Perempuan';
  return '—';
};
</script>

<template>
  <div v-if="open" class="modal-backdrop">
    <div class="modal-container detail-modal">
      <div class="modal-header">
        <h2 class="modal-title">Detail Dokter</h2>
        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <div class="modal-body" v-if="loading">
        <div class="loading-state">
          <span class="spinner"></span>
          <p>Memuat detail dokter...</p>
        </div>
      </div>

      <div class="modal-body" v-else-if="detail">
        <div class="detail-section">
          <h3>Identitas Person/Pegawai</h3>
          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">NIK/KTP</span>
              <span class="detail-value">{{ formatString(person.national_id) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Nama Lengkap</span>
              <span class="detail-value">{{ formatString(person.name) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Tempat Lahir</span>
              <span class="detail-value">{{ formatString(person.birth_place) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Tanggal Lahir</span>
              <span class="detail-value">{{ formatDate(person.birth_date) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Jenis Kelamin</span>
              <span class="detail-value">{{ formatGender(person.gender) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Golongan Darah</span>
              <span class="detail-value">{{ formatString(person.blood_type) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Agama</span>
              <span class="detail-value">{{ formatString(person.religion) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Status Perkawinan</span>
              <span class="detail-value">{{ formatString(person.marital_status) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Kebangsaan</span>
              <span class="detail-value">{{ formatString(person.nationality) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Pendidikan</span>
              <span class="detail-value">{{ formatString(person.education?.name) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Pekerjaan</span>
              <span class="detail-value">{{ formatString(person.occupation?.name) }}</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <h3>Alamat dan Kontak</h3>
          <div class="detail-grid">
            <div class="detail-item full-width">
              <span class="detail-label">Alamat Lengkap</span>
              <span class="detail-value">{{ formatString(person.address) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Provinsi</span>
              <span class="detail-value">{{ formatString(person.province?.name) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Kabupaten/Kota</span>
              <span class="detail-value">{{ formatString(person.city?.name) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Kecamatan</span>
              <span class="detail-value">{{ formatString(person.district?.name) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Kelurahan</span>
              <span class="detail-value">{{ formatString(person.village?.name) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Kode Pos</span>
              <span class="detail-value">{{ formatString(person.postal_code) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Nomor HP/Telepon</span>
              <span class="detail-value">{{ formatString(person.phone) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Email</span>
              <span class="detail-value">{{ formatString(person.email) }}</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <h3>Profil Profesional Dokter</h3>
          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">Spesialisasi</span>
              <span class="detail-value">{{ formatString(professional.specialization?.name || props.detail?.specialization) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Nomor STR</span>
              <span class="detail-value">{{ formatString(professional.str_number) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Nomor SIP</span>
              <span class="detail-value">{{ formatString(professional.sip_number) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Masa Berlaku SIP</span>
              <span class="detail-value">{{ formatDate(professional.sip_valid_until) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Kode DPJP BPJS</span>
              <span class="detail-value">{{ formatString(professional.bpjs_dpjp_code) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Nomor IHS (SatuSehat)</span>
              <span class="detail-value">{{ formatString(person.ihs_number) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Status</span>
              <span class="detail-value">
                <span class="status-badge" :class="professional.is_active ? 'active' : 'inactive'">
                  {{ professional.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-body" v-else>
        <div class="empty-state">
          <p>Gagal memuat detail dokter.</p>
          <button class="btn-primary" @click="emit('close')">Tutup</button>
        </div>
      </div>

      <div class="modal-footer" v-if="!loading && detail">
        <button type="button" class="btn-secondary" @click="emit('close')">Tutup</button>
        <button type="button" class="btn-primary" @click="emit('edit')">Edit Dokter</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 1rem;
}

.detail-modal {
  width: 100%;
  max-width: 800px;
  max-height: 90vh;
  background: #ffffff;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: modal-enter 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modal-enter {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #ffffff;
  border-radius: 12px 12px 0 0;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.btn-close {
  background: transparent;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-close:hover {
  background: var(--color-page-bg);
  color: var(--color-text-navy);
}

.btn-close svg {
  width: 20px;
  height: 20px;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.detail-section {
  margin-bottom: 2rem;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.detail-section h3 {
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-label {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  font-weight: 500;
}

.detail-value {
  font-size: 0.95rem;
  color: var(--color-text-navy);
  font-weight: 500;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.active {
  background: #d1fae5;
  color: #059669;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #dc2626;
}

.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: var(--color-text-secondary);
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid var(--color-border-soft);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  background: #f8fafc;
  border-radius: 0 0 12px 12px;
}

.btn-secondary, .btn-primary {
  padding: 0.625rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
}

.btn-secondary:hover {
  background: #f1f5f9;
}

.btn-primary {
  background: var(--color-primary);
  border: 1px solid var(--color-primary);
  color: #ffffff;
}

.btn-primary:hover {
  background: var(--color-primary-dark, #3b82f6);
}

@media (max-width: 640px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
