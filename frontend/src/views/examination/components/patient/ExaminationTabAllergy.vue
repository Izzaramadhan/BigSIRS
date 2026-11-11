<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  allergyNote: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['save-allergy']);

const draftNote = ref(props.allergyNote || '');

watch(
  () => props.allergyNote,
  (val) => {
    draftNote.value = val || '';
  }
);

const handleSave = () => {
  emit('save-allergy', draftNote.value.trim());
};
</script>

<template>
  <div class="clinical-workspace-panel">
    <div class="allergy-panel-card">
      <!-- Section Header matching reference bar in modern style -->
      <div class="allergy-card-header">
        <div class="allergy-header-left">
          <span class="allergy-icon-pill" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </span>
          <h3 class="allergy-card-title">ALERGI</h3>
        </div>
        <span class="allergy-helper-tag">Riwayat Alergi Obat / Makanan / Lingkungan</span>
      </div>

      <!-- Horizontal Field Row Body -->
      <div class="allergy-form-body">
        <div class="allergy-input-box">
          <div class="allergy-h-row">
            <label class="allergy-h-label" for="allergy-note">Alergi</label>
            <div class="allergy-h-control">
              <textarea
                id="allergy-note"
                v-model="draftNote"
                rows="3"
                class="cw-textarea allergy-textarea"
                placeholder="Contoh: Debu, Amoxicillin, Makanan laut (kosongkan jika tidak ada alergi)"
              ></textarea>
              <span class="allergy-hint">
                Catat zat pemicu alergi pasien agar menjadi perhatian pada peresepan dan tindakan medis.
              </span>
            </div>
          </div>
        </div>

        <!-- Centered Save Button -->
        <div class="allergy-submit-bar">
          <button type="button" class="cw-btn-primary btn-save-allergy" @click="handleSave">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
              <polyline points="17 21 17 13 7 13 7 21"></polyline>
              <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            <span>Simpan</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.allergy-panel-card {
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  background: #ffffff;
  overflow: hidden;
}

.allergy-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding: 0.8rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft);
  flex-wrap: wrap;
}

.allergy-header-left {
  display: flex;
  align-items: center;
  gap: 0.55rem;
}

.allergy-icon-pill {
  width: 26px;
  height: 26px;
  border-radius: 7px;
  background: #fef2f2;
  color: #dc2626;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.allergy-icon-pill svg {
  width: 15px;
  height: 15px;
}

.allergy-card-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: 0.015em;
}

.allergy-helper-tag {
  font-size: 0.82rem;
  color: var(--color-text-secondary);
}

.allergy-form-body {
  padding: 1.25rem;
  background: #ffffff;
}

.allergy-input-box {
  border: 1px solid var(--color-border-soft);
  border-radius: 9px;
  padding: 1.15rem 1.25rem;
  background: #ffffff;
}

.allergy-h-row {
  max-width: 720px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 90px 1fr;
  gap: 1rem;
  align-items: start;
}

.allergy-h-label {
  padding-top: 0.55rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy);
  text-align: right;
}

.allergy-h-control {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.allergy-textarea {
  min-height: 76px;
}

.allergy-hint {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.allergy-submit-bar {
  display: flex;
  justify-content: center;
  margin-top: 1.15rem;
}

.btn-save-allergy {
  min-width: 118px;
}

.btn-save-allergy svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

@media (max-width: 640px) {
  .allergy-h-row {
    grid-template-columns: 1fr;
    gap: 0.4rem;
  }

  .allergy-h-label {
    text-align: left;
    padding-top: 0;
  }
}
</style>
