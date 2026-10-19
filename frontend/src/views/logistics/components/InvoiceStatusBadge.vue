<script setup>
defineProps({
  status: {
    type: String,
    required: true
  },
  isOverdue: {
    type: Boolean,
    default: false
  }
});
</script>

<template>
  <div class="status-badge-container">
    <span
      class="invoice-badge"
      :class="status === 'Lunas' ? 'badge-lunas' : 'badge-belum-lunas'"
    >
      {{ status }}
    </span>
    <span v-if="status !== 'Lunas' && isOverdue" class="invoice-badge badge-overdue">
      <svg class="overdue-dot" viewBox="0 0 8 8" fill="currentColor">
        <circle cx="4" cy="4" r="3" />
      </svg>
      Lewat Jatuh Tempo
    </span>
  </div>
</template>

<style scoped>
.status-badge-container {
  display: inline-flex;
  flex-direction: column;
  gap: 0.25rem;
  align-items: flex-start;
}

.invoice-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 600;
  line-height: 1.2;
  white-space: nowrap;
}

.badge-lunas {
  background-color: #d1fae5;
  color: #065f46;
}

.badge-belum-lunas {
  background-color: #e2e8f0;
  color: #334155;
}

.badge-overdue {
  background-color: #fee2e2;
  color: #b91c1c;
}

.overdue-dot {
  width: 6px;
  height: 6px;
}
</style>
