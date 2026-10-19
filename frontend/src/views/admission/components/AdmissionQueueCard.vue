<script setup>
defineProps({
  loket: {
    type: Object,
    required: true
  },
  currentQueue: {
    type: String,
    required: true
  },
  nextQueue: {
    type: String,
    required: true
  }
});

const emit = defineEmits([
  'call-current',
  'call-next',
  'prev-queue',
  'next-queue'
]);
</script>

<template>
  <div class="card queue-management-card">
    <div class="queue-card-grid">
      <!-- Area A: Informasi Loket Admisi -->
      <div class="loket-info-section">
        <div class="loket-header">
          <div class="loket-badge-group">
            <span class="loket-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
              </svg>
            </span>
            <span class="badge-status-online">
              <span class="status-pulse"></span>
              {{ loket.status }}
            </span>
          </div>
          <h3 class="loket-title">{{ loket.name }}</h3>
          <p class="loket-sub">{{ loket.description }}</p>
        </div>

        <div class="loket-meta-list">
          <div class="loket-meta-item">
            <span class="meta-label">Petugas Jaga:</span>
            <span class="meta-value font-medium">{{ loket.officer }}</span>
          </div>
          <div class="loket-meta-item">
            <span class="meta-label">Lokasi:</span>
            <span class="meta-value">{{ loket.location }}</span>
          </div>
        </div>

        <div class="audio-indicator-row">
          <div class="audio-pill">
            <span class="audio-dot"></span>
            <span class="audio-text">Audio Online</span>
          </div>
          <span class="audio-mode-label">(Mode Simulasi Frontend)</span>
        </div>
      </div>

      <!-- Area B: Nomor Antrean Dipanggil (Hero Display) -->
      <div class="queue-display-section">
        <div class="queue-display-label">NOMOR DIPANGGIL SAAT INI</div>
        <div class="queue-number-hero">
          {{ currentQueue }}
        </div>

        <div class="queue-nav-buttons">
          <button 
            type="button" 
            class="btn-queue-nav" 
            @click="emit('prev-queue')" 
            title="Nomor Sebelumnya"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <span>Sebelumnya</span>
          </button>

          <button 
            type="button" 
            class="btn-primary btn-call-hero" 
            @click="emit('call-current')"
            title="Panggil Ulang Nomor Antrean Ini"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
              <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
            </svg>
            <span>Panggil Antrean</span>
          </button>

          <button 
            type="button" 
            class="btn-queue-nav" 
            @click="emit('next-queue')" 
            title="Nomor Berikutnya"
          >
            <span>Berikutnya</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      </div>

      <!-- Area C: Informasi Antrean Berikutnya -->
      <div class="next-queue-section">
        <div class="next-queue-card">
          <div class="next-queue-tag">ANTREAN BERIKUTNYA</div>
          <div class="next-queue-number">{{ nextQueue }}</div>
          <p class="next-queue-desc">Pasien siap dipanggil ke Loket 02</p>

          <button 
            type="button" 
            class="btn-call-next" 
            @click="emit('call-next')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
              <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
            </svg>
            <span>Panggil Nomor Ini</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.queue-management-card {
  padding: 1.5rem;
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  margin-bottom: 0.5rem;
}

.queue-card-grid {
  display: grid;
  grid-template-columns: 1.2fr 1.6fr 1fr;
  gap: 1.5rem;
  align-items: center;
}

/* Loket Section */
.loket-info-section {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  border-right: 1px dashed var(--color-border-soft);
  padding-right: 1.5rem;
}

.loket-badge-group {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 0.25rem;
}

.loket-icon {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
}

.loket-icon svg {
  width: 16px;
  height: 16px;
}

.badge-status-online {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #dcfce7;
  color: #166534;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.55rem;
  border-radius: 9999px;
}

.status-pulse {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background-color: #16a34a;
  box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2);
}

.loket-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.loket-sub {
  margin: 0.15rem 0 0 0;
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.loket-meta-list {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.85rem;
  background: #f8fafc;
  padding: 0.75rem;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
}

.loket-meta-item {
  display: flex;
  justify-content: space-between;
}

.meta-label {
  color: var(--color-text-secondary);
}

.meta-value {
  color: var(--color-text-navy);
}

.audio-indicator-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.audio-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #0369a1;
  background: #e0f2fe;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
}

.audio-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #0284c7;
}

.audio-mode-label {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

/* Queue Display Hero */
.queue-display-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 0 0.5rem;
}

.queue-display-label {
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: var(--color-text-secondary);
  margin-bottom: 0.35rem;
}

.queue-number-hero {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 3.25rem;
  font-weight: 800;
  line-height: 1;
  color: var(--color-primary-dark);
  background: linear-gradient(180deg, #f0fdfa 0%, #e6faf7 100%);
  border: 2px solid #a7eedf;
  padding: 0.6rem 2rem;
  border-radius: 16px;
  letter-spacing: 0.05em;
  box-shadow: 0 4px 12px rgba(8, 127, 120, 0.08);
  margin-bottom: 1rem;
}

.queue-nav-buttons {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
  justify-content: center;
}

.btn-queue-nav {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  padding: 0.55rem 0.85rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-queue-nav:hover {
  background: var(--color-page-bg);
  border-color: #cbd5e1;
}

.btn-queue-nav svg {
  width: 16px;
  height: 16px;
}

.btn-call-hero {
  padding: 0.6rem 1.25rem;
  font-size: 0.9rem;
  gap: 0.5rem;
}

.btn-call-hero svg {
  width: 18px;
  height: 18px;
}

/* Next Queue Section */
.next-queue-section {
  border-left: 1px dashed var(--color-border-soft);
  padding-left: 1.5rem;
}

.next-queue-card {
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  padding: 1.15rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.next-queue-tag {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: var(--color-text-secondary);
  margin-bottom: 0.35rem;
}

.next-queue-number {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--color-text-navy);
  margin-bottom: 0.25rem;
}

.next-queue-desc {
  margin: 0 0 0.85rem 0;
  font-size: 0.775rem;
  color: var(--color-text-secondary);
}

.btn-call-next {
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  background: #ffffff;
  color: var(--color-primary);
  border: 1.5px solid var(--color-primary);
  padding: 0.5rem 0.85rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-call-next:hover {
  background: var(--color-primary-light);
}

.btn-call-next svg {
  width: 16px;
  height: 16px;
}

@media (max-width: 1024px) {
  .queue-card-grid {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }

  .loket-info-section {
    border-right: none;
    border-bottom: 1px dashed var(--color-border-soft);
    padding-right: 0;
    padding-bottom: 1.25rem;
  }

  .next-queue-section {
    border-left: none;
    border-top: 1px dashed var(--color-border-soft);
    padding-left: 0;
    padding-top: 1.25rem;
  }
}
</style>
