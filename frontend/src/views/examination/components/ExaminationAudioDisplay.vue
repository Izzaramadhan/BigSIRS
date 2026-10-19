<script setup>
defineProps({
  lastCalledPatient: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['replay-audio']);
</script>

<template>
  <div class="card audio-display-panel" aria-label="Display Pemanggil Suara Poliklinik">
    <div class="panel-inner">
      <div class="panel-left">
        <div class="speaker-icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
          </svg>
        </div>
        <div class="panel-info">
          <span class="panel-sublabel">DISPLAY PEMANGGIL SUARA POLIKLINIK</span>
          <h3 class="panel-title">Speaker Ruang Antrean Poliklinik Aktif</h3>
          <p class="panel-desc">
            Simulasi display antrean — belum terhubung ke perangkat audio maupun SMS Gateway.
          </p>
          <div class="panel-meta" v-if="lastCalledPatient">
            <span class="meta-badge">
              Panggilan Terakhir: <strong>{{ lastCalledPatient.queue_number }}</strong> &bull; {{ lastCalledPatient.patient_name }} ({{ lastCalledPatient.polyclinic }})
            </span>
          </div>
        </div>
      </div>

      <div class="panel-right">
        <button
          type="button"
          class="btn-replay"
          @click="emit('replay-audio')"
          aria-label="Bunyikan Ulang Panggilan"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
          </svg>
          <span>Bunyikan Ulang Panggilan</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.audio-display-panel {
  background: linear-gradient(135deg, #087f78 0%, #06635e 100%);
  border: 1px solid rgba(8, 127, 120, 0.3);
  border-radius: 12px;
  color: #ffffff;
  padding: 1.15rem 1.5rem;
  box-shadow: 0 4px 14px rgba(8, 127, 120, 0.15);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.audio-display-panel:hover {
  box-shadow: 0 6px 20px rgba(8, 127, 120, 0.22);
}

.panel-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1.25rem;
}

.panel-left {
  display: flex;
  align-items: center;
  gap: 1.15rem;
  min-width: 0;
}

.speaker-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #ffffff;
}

.speaker-icon-wrapper svg {
  width: 24px;
  height: 24px;
}

.panel-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.panel-sublabel {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #99f6e4;
  margin-bottom: 0.2rem;
}

.panel-title {
  margin: 0 0 0.25rem 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #ffffff;
}

.panel-desc {
  margin: 0;
  font-size: 0.825rem;
  color: #ccfbf1;
  line-height: 1.35;
}

.panel-meta {
  margin-top: 0.5rem;
}

.meta-badge {
  display: inline-block;
  font-size: 0.775rem;
  color: #ffffff;
  background: rgba(0, 0, 0, 0.2);
  padding: 0.2rem 0.6rem;
  border-radius: 4px;
}

.panel-right {
  display: flex;
  align-items: center;
}

.btn-replay {
  height: 40px;
  padding: 0 1.25rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #065f46;
  background: #ffffff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-replay:hover {
  background: #f0fdf4;
  color: #047857;
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.btn-replay svg {
  width: 16px;
  height: 16px;
  color: #087f78;
}

@media (max-width: 768px) {
  .panel-inner {
    flex-direction: column;
    align-items: stretch;
  }
  .panel-right {
    justify-content: stretch;
  }
  .btn-replay {
    width: 100%;
    justify-content: center;
  }
}
</style>
