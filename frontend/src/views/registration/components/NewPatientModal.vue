<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true
  }
});

const emit = defineEmits(['close']);
</script>

<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content modal-md" role="dialog" aria-modal="true" aria-labelledby="new-patient-modal-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-icon-title">
          <div class="header-avatar-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <line x1="19" y1="8" x2="19" y2="14"></line>
              <line x1="22" y1="11" x2="16" y2="11"></line>
            </svg>
          </div>
          <div>
            <h3 id="new-patient-modal-title">Registrasi Pasien Baru</h3>
            <p class="header-sub">Formulir pendaftaran pasien rekam medis baru</p>
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

      <!-- Modal Body -->
      <div class="modal-body">
        <div class="placeholder-notice-card">
          <div class="notice-badge">Tahap Frontend Placeholder</div>
          <h4>Fitur Registrasi Lengkap Segera Hadir</h4>
          <p>
            Modul pendaftaran pasien baru lengkap (termasuk validasi NIK Dukcapil, verifikasi BPJS V-Claim 2.0, serta Master Patient Index SatuSehat Kemenkes) sedang dipersiapkan untuk fase implementasi berikutnya.
          </p>
        </div>

        <!-- Preview Form Skeleton Fields -->
        <div class="preview-fields-group">
          <div class="preview-field-item">
            <label class="form-label">Nomor Induk Kependudukan (NIK)</label>
            <input autocomplete="off" 
              type="text" 
              class="form-input disabled-preview" 
              placeholder="Contoh: 347101xxxxxxxxxx" 
              disabled 
            />
          </div>

          <div class="preview-field-item">
            <label class="form-label">Nama Lengkap Pasien Sesuai KTP</label>
            <input autocomplete="off" 
              type="text" 
              class="form-input disabled-preview" 
              placeholder="Nama lengkap pasien..." 
              disabled 
            />
          </div>

          <div class="preview-grid-2">
            <div class="preview-field-item">
              <label class="form-label">Jenis Kelamin</label>
              <select class="form-select disabled-preview" disabled>
                <option>Pilih jenis kelamin</option>
              </select>
            </div>

            <div class="preview-field-item">
              <label class="form-label">Tanggal Lahir</label>
              <input autocomplete="off" type="date" class="form-input disabled-preview" disabled />
            </div>
          </div>

          <div class="preview-field-item">
            <label class="form-label">Penjamin Biaya Layanan</label>
            <select class="form-select disabled-preview" disabled>
              <option>BPJS Kesehatan / Umum / Asuransi Rekanan</option>
            </select>
          </div>
        </div>

        <div class="helper-tips-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <p>
            Saat ini Anda dapat melakukan pencarian data dan simulasi pemilihan pasien menggunakan daftar rekam medis yang tersedia pada halaman ini.
          </p>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button 
          type="button" 
          class="btn-primary" 
          @click="emit('close')"
        >
          Mengerti & Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-md {
  max-width: 580px;
}

.header-icon-title {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.header-avatar-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-avatar-icon svg {
  width: 22px;
  height: 22px;
}

.header-sub {
  margin: 0.15rem 0 0 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.placeholder-notice-card {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  padding: 1.15rem;
  margin-bottom: 1.25rem;
}

.notice-badge {
  display: inline-block;
  background: #dcfce7;
  color: #15803d;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  margin-bottom: 0.5rem;
}

.placeholder-notice-card h4 {
  margin: 0 0 0.35rem 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #14532d;
}

.placeholder-notice-card p {
  margin: 0;
  font-size: 0.825rem;
  color: #166534;
  line-height: 1.5;
}

.preview-fields-group {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  margin-bottom: 1.25rem;
}

.preview-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.disabled-preview {
  background-color: #f8fafc;
  border-color: #e2e8f0;
  color: #94a3b8;
  cursor: not-allowed;
}

.helper-tips-box {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  padding: 0.75rem 1rem;
}

.helper-tips-box svg {
  width: 18px;
  height: 18px;
  color: var(--color-primary);
  flex-shrink: 0;
  margin-top: 2px;
}

.helper-tips-box p {
  margin: 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  line-height: 1.4;
}

@media (max-width: 640px) {
  .preview-grid-2 {
    grid-template-columns: 1fr;
  }
}
</style>
