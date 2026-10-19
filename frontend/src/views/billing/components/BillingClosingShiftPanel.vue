<script setup>
defineProps({
  shift: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['reconcile', 'handover']);
</script>

<template>
  <div class="card closing-shift-panel" aria-label="Panel Validasi Closing Shift Kasir">
    <div class="panel-inner">
      <div class="panel-left">
        <div class="shift-icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
            <line x1="8" y1="21" x2="16" y2="21"></line>
            <line x1="12" y1="17" x2="12" y2="21"></line>
          </svg>
        </div>
        <div class="shift-info-group">
          <div class="title-row">
            <h3 class="shift-title">{{ shift.shift_title }}</h3>
            <span class="active-badge">Shift Berjalan</span>
          </div>
          <div class="shift-meta-row">
            <span class="meta-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              <span>Jam Operasional: <strong>{{ shift.shift_time }}</strong></span>
            </span>
            <span class="dot-sep">&bull;</span>
            <span class="meta-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              <span>Kasir Penanggung Jawab: <strong>{{ shift.cashier_username }}</strong> ({{ shift.cashier_fullname }})</span>
            </span>
          </div>
        </div>
      </div>

      <div class="panel-right">
        <!-- Button 1: Rekonsiliasi EDC / Bank -->
        <button
          type="button"
          class="btn-secondary btn-shift-action"
          @click="emit('reconcile')"
          aria-label="Buka Rekonsiliasi EDC / Bank"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
            <line x1="1" y1="10" x2="23" y2="10"></line>
          </svg>
          <span>Rekonsiliasi EDC / Bank</span>
        </button>

        <!-- Button 2: Serah Terima Kasir -->
        <button
          type="button"
          class="btn-primary btn-shift-action"
          @click="emit('handover')"
          aria-label="Buka Serah Terima Kasir"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="8.5" cy="7" r="4"></circle>
            <polyline points="17 11 19 13 23 9"></polyline>
          </svg>
          <span>Serah Terima Kasir</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.closing-shift-panel {
  padding: 1.15rem 1.5rem;
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-left: 4px solid var(--color-primary, #087f78);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
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
  gap: 1rem;
  min-width: 0;
}

.shift-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: var(--color-primary-light, #e6f6f5);
  color: var(--color-primary, #087f78);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.shift-icon-wrapper svg {
  width: 22px;
  height: 22px;
}

.shift-info-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.shift-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-navy, #0f172a);
}

.active-badge {
  font-size: 0.7rem;
  font-weight: 700;
  color: #15803d;
  background: #dcfce7;
  padding: 0.15rem 0.5rem;
  border-radius: 20px;
}

.shift-meta-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-size: 0.8rem;
  color: #64748b;
  flex-wrap: wrap;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.meta-item svg {
  width: 14px;
  height: 14px;
  color: #94a3b8;
}

.meta-item strong {
  color: #1e293b;
}

.dot-sep {
  color: #cbd5e1;
}

.panel-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn-shift-action {
  height: 38px;
  padding: 0 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.825rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.btn-shift-action svg {
  width: 16px;
  height: 16px;
}

.btn-secondary {
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.btn-secondary:hover {
  background: #f8fafc;
  color: #0f172a;
  border-color: #94a3b8;
}

.btn-primary {
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: none;
}

.btn-primary:hover {
  background: var(--color-primary-dark, #06635e);
}

@media (max-width: 900px) {
  .panel-inner {
    flex-direction: column;
    align-items: stretch;
  }
  .panel-right {
    justify-content: stretch;
  }
  .btn-shift-action {
    flex: 1;
    justify-content: center;
  }
}
</style>
