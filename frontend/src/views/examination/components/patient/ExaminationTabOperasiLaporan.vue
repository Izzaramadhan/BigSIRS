<script setup>
defineProps({
  surgeries: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['save-surgery-report']);
</script>

<template>
  <div class="clinical-workspace-panel oprep-page-wrap">
    <div class="oprep-card">
      <div class="oprep-card-header">
        <span class="oprep-card-title">LAPORAN OPERASI</span>
      </div>

      <div class="oprep-card-body">
        <textarea
          id="laporan-operasi-text"
          v-model="surgeries.report.findings"
          rows="10"
          class="cw-textarea oprep-textarea"
          aria-label="Laporan Operasi"
          placeholder="Tuliskan ringkasan laporan tindakan operasi, prosedur, dan temuan klinis..."
        ></textarea>
      </div>
    </div>

    <div class="oprep-submit-footer">
      <button
        type="button"
        class="cw-btn-primary btn-save-oprep"
        @click="emit('save-surgery-report')"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        <span>Simpan</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.oprep-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.oprep-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.oprep-card-header {
  display: flex;
  align-items: center;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.85rem 1.25rem;
}

.oprep-card-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.oprep-card-body {
  padding: 1.25rem;
}

.oprep-textarea {
  width: 100%;
  min-height: 220px;
  font-size: 0.875rem;
  line-height: 1.6;
  resize: vertical;
}

.oprep-submit-footer {
  display: flex;
  justify-content: center;
}

.btn-save-oprep {
  min-width: 118px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  padding: 0 1.4rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.btn-save-oprep svg {
  width: 16px;
  height: 16px;
}
</style>

