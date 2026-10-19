<script setup>
import { computed } from 'vue';

const props = defineProps({
  value: {
    type: String,
    required: true
  },
  type: {
    type: String,
    default: 'status' // 'status' | 'purpose'
  }
});

const statusClass = computed(() => {
  switch (props.value) {
    case 'Selesai':
      return 'badge-completed';
    case 'Draft':
      return 'badge-draft';
    case 'Dibatalkan':
      return 'badge-cancelled';
    default:
      return 'badge-default';
  }
});

const purposeClass = computed(() => {
  const val = props.value.toLowerCase();
  if (val.includes('internal')) {
    return 'purpose-internal';
  }
  if (val.includes('rusak')) {
    return 'purpose-damaged';
  }
  if (val.includes('sosial') || val.includes('baksos')) {
    return 'purpose-social';
  }
  if (val.includes('kedaluwarsa') || val.includes('pemusnahan')) {
    return 'purpose-expired';
  }
  if (val.includes('isolasi')) {
    return 'purpose-isolation';
  }
  if (val.includes('penelitian') || val.includes('mutu')) {
    return 'purpose-research';
  }
  return 'purpose-default';
});
</script>

<template>
  <span
    :class="type === 'purpose' ? ['purpose-badge', purposeClass] : ['status-badge', statusClass]"
  >
    <!-- Purpose badge variant -->
    <template v-if="type === 'purpose'">
      <span>{{ value }}</span>
    </template>

    <!-- Document status variant -->
    <template v-else>
      <span class="status-dot"></span>
      <span class="status-text">{{ value }}</span>
    </template>
  </span>
</template>

<style scoped>
/* Document Status Badges */
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

/* Purpose Badges */
.purpose-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.28rem 0.65rem;
  border-radius: 6px;
  font-size: 0.74rem;
  font-weight: 600;
  white-space: nowrap;
  line-height: 1.2;
}

.purpose-internal {
  background: #dbeafe;
  color: #1e40af;
  border: 1px solid #bfdbfe;
}

.purpose-damaged {
  background: #cffafe;
  color: #0e7490;
  border: 1px solid #a5f3fc;
}

.purpose-social {
  background: #ccfbf1;
  color: #0f766e;
  border: 1px solid #99f6e4;
}

.purpose-expired {
  background: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}

.purpose-isolation {
  background: #ede9fe;
  color: #6d28d9;
  border: 1px solid #ddd6fe;
}

.purpose-research {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.purpose-default {
  background: #f3f4f6;
  color: #4b5563;
  border: 1px solid #e5e7eb;
}
</style>
