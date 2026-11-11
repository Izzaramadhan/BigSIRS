<script setup>
defineProps({
  queueNumber: {
    type: String,
    default: 'P001'
  },
  canPrev: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['call', 'prev', 'next']);
</script>

<template>
  <div class="card registration-queue-card">
    <!-- Card Header -->
    <div class="card-header-bar">
      <div class="header-left">
        <div class="header-icon-box" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="8" y1="6" x2="21" y2="6"></line>
            <line x1="8" y1="12" x2="21" y2="12"></line>
            <line x1="8" y1="18" x2="21" y2="18"></line>
            <line x1="3" y1="6" x2="3.01" y2="6"></line>
            <line x1="3" y1="12" x2="3.01" y2="12"></line>
            <line x1="3" y1="18" x2="3.01" y2="18"></line>
          </svg>
        </div>
        <div>
          <h2 class="card-title">NO. ANTRIAN</h2>
          <p class="card-subtitle">Loket panggilan antrean pendaftaran</p>
        </div>
      </div>
    </div>

    <!-- Card Body -->
    <div class="queue-card-body">
      <div class="queue-display-group">
        <div class="queue-number-display" aria-label="Nomor Antrean Aktif">
          {{ queueNumber }}
        </div>
        <button
          type="button"
          class="btn-primary btn-call-queue"
          @click="emit('call')"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
          </svg>
          <span>Panggil</span>
        </button>
      </div>

      <div class="queue-step-actions">
        <button
          type="button"
          class="btn-queue-step btn-prev-queue"
          :disabled="!canPrev"
          @click="emit('prev')"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="19 20 9 12 19 4 19 20"></polygon>
            <line x1="5" y1="19" x2="5" y2="5"></line>
          </svg>
          <span>Prev</span>
        </button>

        <button
          type="button"
          class="btn-queue-step btn-next-queue"
          @click="emit('next')"
        >
          <span>Next</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="5 4 15 12 5 20 5 4"></polygon>
            <line x1="19" y1="5" x2="19" y2="19"></line>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.registration-queue-card {
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.card-header-bar {
  display: flex;
  align-items: center;
  padding: 1.05rem 1.4rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #ffffff;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.header-icon-box {
  width: 36px;
  height: 36px;
  border-radius: 9px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon-box svg {
  width: 18px;
  height: 18px;
}

.card-title {
  margin: 0;
  font-size: 0.975rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: -0.01em;
}

.card-subtitle {
  margin: 0.12rem 0 0 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.queue-card-body {
  padding: 1.25rem 1.4rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.85rem;
}

.queue-display-group {
  display: flex;
  align-items: stretch;
  width: 100%;
}

.queue-number-display {
  flex: 1;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-right: none;
  border-radius: 8px 0 0 8px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-text-navy);
  letter-spacing: 0.04em;
  box-sizing: border-box;
}

.btn-call-queue {
  height: 44px;
  padding: 0 1.15rem;
  border-radius: 0 8px 8px 0;
  border: none;
  background: var(--color-primary);
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
  transition: background-color 0.15s ease;
}

.btn-call-queue:hover {
  background: var(--color-primary-dark);
  transform: none;
}

.btn-call-queue svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.queue-step-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.65rem;
}

.btn-queue-step {
  flex: 1;
  height: 38px;
  padding: 0 0.9rem;
  border-radius: 8px;
  background: #ffffff;
  border: 1px solid rgba(8, 127, 120, 0.35);
  color: var(--color-primary-dark);
  font-size: 0.825rem;
  font-weight: 600;
  font-family: inherit;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.btn-queue-step:hover:not(:disabled) {
  background: var(--color-primary-light);
  border-color: var(--color-primary);
}

.btn-queue-step:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  background: #f8fafc;
  border-color: var(--color-border-soft);
  color: var(--color-text-secondary);
}

.btn-queue-step svg {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}
</style>

