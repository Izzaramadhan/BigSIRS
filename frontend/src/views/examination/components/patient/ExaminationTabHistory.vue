<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  visitHistory: {
    type: Array,
    default: () => []
  }
});

const selectedHistoryId = ref(null);

const selectedVisit = computed(() => {
  if (!selectedHistoryId.value) return null;
  return props.visitHistory.find((item) => item.id === selectedHistoryId.value) || null;
});

const selectVisit = (id) => {
  selectedHistoryId.value = selectedHistoryId.value === id ? null : id;
};
</script>

<template>
  <div class="history-split-box">
    <!-- Left Column: Chronological Visit List -->
    <div class="history-timeline-pane" aria-label="Daftar Riwayat Kunjungan Pasien">
      <div v-if="visitHistory.length === 0" class="history-empty-list">
        Belum ada riwayat kunjungan tercatat untuk pasien ini.
      </div>

      <button
        v-for="item in visitHistory"
        :key="item.id"
        type="button"
        class="history-row-btn"
        :class="{ 'is-selected': selectedHistoryId === item.id }"
        @click="selectVisit(item.id)"
      >
        <span class="history-datetime font-mono">{{ item.visit_datetime }}</span>
        <span class="history-units">
          {{ item.units.map((u) => `( ${u} )`).join(' ') }}
        </span>
      </button>
    </div>

    <!-- Right Column: Selected Historical Encounter Detail -->
    <div class="history-detail-pane">
      <div v-if="selectedVisit" class="history-detail-card">
        <div class="detail-top-bar">
          <div>
            <span class="detail-time-label font-mono">{{ selectedVisit.visit_datetime }}</span>
            <h3 class="detail-unit-heading">{{ selectedVisit.units.join(' / ') }}</h3>
          </div>
          <span class="detail-doctor-chip">{{ selectedVisit.doctor }}</span>
        </div>

        <div class="detail-grid">
          <div class="detail-block">
            <span class="detail-label">S • Subjektif</span>
            <p class="detail-text">{{ selectedVisit.subjective }}</p>
          </div>
          <div class="detail-block">
            <span class="detail-label">O • Objektif & Tanda Vital</span>
            <p class="detail-text">{{ selectedVisit.objective }}</p>
          </div>
          <div class="detail-block">
            <span class="detail-label">A • Asesmen & Diagnosis</span>
            <p class="detail-text">
              <strong>{{ selectedVisit.diagnosis }}</strong><br />
              {{ selectedVisit.assessment }}
            </p>
          </div>
          <div class="detail-block">
            <span class="detail-label">P • Tindakan & Terapi</span>
            <p class="detail-text">
              <span v-if="selectedVisit.procedures?.length">
                Tindakan: {{ selectedVisit.procedures.join(', ') }}.<br />
              </span>
              <span v-if="selectedVisit.prescriptions?.length">
                Resep: {{ selectedVisit.prescriptions.join(', ') }}.<br />
              </span>
              {{ selectedVisit.notes }}
            </p>
          </div>
        </div>
      </div>

      <div v-else class="history-detail-idle">
        <span class="idle-hint">
          Pilih salah satu waktu kunjungan di sebelah kiri untuk menampilkan ringkasan catatan medis kunjungan tersebut.
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.history-split-box {
  display: grid;
  grid-template-columns: minmax(290px, 34%) 1fr;
  min-height: 295px;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  background: #ffffff;
  overflow: hidden;
}

.history-timeline-pane {
  border-right: 1px solid var(--color-border-soft);
  padding: 0.9rem 0.65rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  background: #ffffff;
}

.history-empty-list {
  padding: 1rem;
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.history-row-btn {
  width: 100%;
  text-align: left;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  padding: 0.58rem 0.72rem;
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 0.45rem;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s ease;
}

.history-row-btn:hover {
  background: #f8fafc;
  border-color: #e2e8f0;
}

.history-row-btn.is-selected {
  background: var(--color-primary-light);
  border-color: rgba(13, 148, 136, 0.28);
}

.history-datetime {
  font-size: 0.85rem;
  font-weight: 600;
  color: #0284c7;
  white-space: nowrap;
}

.history-units {
  font-size: 0.78rem;
  font-weight: 500;
  color: #475569;
  letter-spacing: 0.01em;
}

.history-detail-pane {
  padding: 1.2rem 1.35rem;
  background: #ffffff;
  display: flex;
  flex-direction: column;
}

.history-detail-idle {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 240px;
  text-align: center;
  padding: 1.5rem;
}

.idle-hint {
  font-size: 0.86rem;
  color: #94a3b8;
  max-width: 390px;
  line-height: 1.5;
}

.history-detail-card {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--color-border-soft);
  flex-wrap: wrap;
}

.detail-time-label {
  font-size: 0.82rem;
  color: #0284c7;
  font-weight: 600;
}

.detail-unit-heading {
  margin: 0.15rem 0 0 0;
  font-size: 0.96rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.detail-doctor-chip {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-primary-dark);
  background: var(--color-primary-light);
  padding: 0.25rem 0.7rem;
  border-radius: 9999px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
}

.detail-block {
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  padding: 0.8rem 0.95rem;
}

.detail-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-primary-dark);
  margin-bottom: 0.32rem;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.detail-text {
  margin: 0;
  font-size: 0.86rem;
  color: var(--color-text-navy);
  line-height: 1.5;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-variant-numeric: tabular-nums;
}

@media (max-width: 900px) {
  .history-split-box {
    grid-template-columns: 1fr;
  }

  .history-timeline-pane {
    border-right: none;
    border-bottom: 1px solid var(--color-border-soft);
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>

