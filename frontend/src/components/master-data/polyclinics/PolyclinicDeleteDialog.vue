<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  polyclinic: {
    type: Object,
    default: null
  },
  isSubmitting: {
    type: Boolean,
    default: false
  }
});

defineEmits(['close', 'confirm']);
</script>

<template>
  <div v-if="isOpen" class="modal-backdrop">
    <div class="modal-content delete-modal" role="dialog" aria-labelledby="delete-modal-title">
      <div class="delete-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
          <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
        </svg>
      </div>
      
      <h3 id="delete-modal-title" class="delete-title">Arsipkan Poliklinik?</h3>
      
      <div class="delete-desc">
        <p>Poliklinik yang diarsipkan tidak akan tampil pada daftar aktif.</p>
        <p>Data ini tidak dihapus permanen.</p>
      </div>
      
      <div v-if="polyclinic" class="delete-target">
        <span class="target-code">{{ polyclinic.code }}</span>
        <span class="target-name">{{ polyclinic.name }}</span>
      </div>
      
      <div class="delete-actions">
        <button 
          type="button" 
          class="btn-cancel" 
          @click="$emit('close')"
          :disabled="isSubmitting"
        >
          Batal
        </button>
        <button 
          type="button" 
          class="btn-danger" 
          @click="$emit('confirm')"
          :disabled="isSubmitting"
        >
          {{ isSubmitting ? 'Memproses...' : 'Arsipkan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(15, 23, 42, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  padding: 1.5rem;
  text-align: center;
}

.delete-icon {
  width: 48px;
  height: 48px;
  background: #fef3c7;
  color: #d97706;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.delete-icon svg {
  width: 24px;
  height: 24px;
}

.delete-title {
  margin: 0 0 0.5rem 0;
  color: var(--color-text-navy);
  font-size: 1.1rem;
}

.delete-desc {
  color: var(--color-text-secondary);
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

.delete-desc p {
  margin: 0.25rem 0;
}

.delete-target {
  background: var(--color-page-bg);
  padding: 0.75rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.target-code {
  display: block;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-primary);
}

.target-name {
  display: block;
  font-size: 0.95rem;
  font-weight: 500;
  color: var(--color-text-navy);
}

.delete-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
}

.btn-cancel {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  border: 1px solid var(--color-border-soft);
  background: #ffffff;
  color: var(--color-text-navy);
  cursor: pointer;
  flex: 1;
}

.btn-cancel:hover:not(:disabled) {
  background: var(--color-page-bg);
}

.btn-danger {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  border: none;
  background: #d97706; /* Warning amber color */
  color: #ffffff;
  cursor: pointer;
  flex: 1;
}

.btn-danger:hover:not(:disabled) {
  background: #b45309;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
