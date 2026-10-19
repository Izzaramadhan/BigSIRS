<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    required: true
  },
  type: {
    type: String,
    default: 'session' // 'session' | 'difference'
  },
  variant: {
    type: String,
    default: '' // 'danger' | 'info' | 'success' | 'warning' | 'muted'
  }
});

const sessionClass = computed(() => {
  switch (props.status) {
    case 'Sedang Dihitung':
      return 'badge-counting';
    case 'Menunggu Peninjauan':
      return 'badge-review';
    case 'Selesai':
      return 'badge-completed';
    case 'Draft Sesi':
      return 'badge-draft';
    case 'Dibatalkan':
      return 'badge-cancelled';
    default:
      return 'badge-default';
  }
});

const differenceClass = computed(() => {
  if (props.variant) {
    return `diff-${props.variant}`;
  }
  const text = props.status.toLowerCase();
  if (text.includes('match') || text.includes('0 selisih')) {
    return 'diff-success';
  }
  if (text.includes('adjusted')) {
    return 'diff-warning';
  }
  if (text.includes('belum dihitung') || text === '-') {
    return 'diff-muted';
  }
  return 'diff-danger';
});
</script>

<template>
  <span
    :class="type === 'difference' ? ['diff-badge', differenceClass] : ['status-badge', sessionClass]"
  >
    <!-- Difference icon -->
    <template v-if="type === 'difference'">
      <svg v-if="differenceClass === 'diff-danger'" class="diff-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
        <line x1="12" y1="9" x2="12" y2="13"></line>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
      </svg>
      <svg v-else-if="differenceClass === 'diff-info'" class="diff-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 14 14"></polyline>
      </svg>
      <svg v-else-if="differenceClass === 'diff-success'" class="diff-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
      <svg v-else-if="differenceClass === 'diff-warning'" class="diff-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="4" y1="21" x2="4" y2="14"></line>
        <line x1="4" y1="10" x2="4" y2="3"></line>
        <line x1="12" y1="21" x2="12" y2="12"></line>
        <line x1="12" y1="8" x2="12" y2="3"></line>
        <line x1="20" y1="21" x2="20" y2="16"></line>
        <line x1="20" y1="12" x2="20" y2="3"></line>
        <line x1="1" y1="14" x2="7" y2="14"></line>
        <line x1="9" y1="8" x2="15" y2="8"></line>
        <line x1="17" y1="16" x2="23" y2="16"></line>
      </svg>
      <span>{{ status }}</span>
    </template>

    <!-- Session status -->
    <template v-else>
      <span class="status-dot"></span>
      <span class="status-text">{{ status }}</span>
    </template>
  </span>
</template>

<style scoped>
/* Session Status Badges */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.3rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.76rem;
  font-weight: 600;
  white-space: nowrap;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.badge-counting {
  background: #e0f2fe;
  color: #0369a1;
}
.badge-counting .status-dot {
  background: #0284c7;
}

.badge-review {
  background: #ede9fe;
  color: #5b21b6;
}
.badge-review .status-dot {
  background: #7c3aed;
}

.badge-completed {
  background: #dcfce7;
  color: #15803d;
}
.badge-completed .status-dot {
  background: #16a34a;
}

.badge-draft {
  background: #f1f5f9;
  color: #475569;
}
.badge-draft .status-dot {
  background: #64748b;
}

.badge-cancelled {
  background: #fee2e2;
  color: #b91c1c;
}
.badge-cancelled .status-dot {
  background: #dc2626;
}

.badge-default {
  background: #f3f4f6;
  color: #4b5563;
}
.badge-default .status-dot {
  background: #9ca3af;
}

/* Difference Badges */
.diff-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  font-size: 0.74rem;
  font-weight: 600;
  white-space: nowrap;
}

.diff-icon {
  width: 13px;
  height: 13px;
  flex-shrink: 0;
}

.diff-danger {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.diff-info {
  background: #e0f2fe;
  color: #0284c7;
  border: 1px solid #bae6fd;
}

.diff-success {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.diff-warning {
  background: #fff7ed;
  color: #c2410c;
  border: 1px solid #ffedd5;
}

.diff-muted {
  background: #f8fafc;
  color: #64748b;
  border: 1px solid #e2e8f0;
}
</style>
