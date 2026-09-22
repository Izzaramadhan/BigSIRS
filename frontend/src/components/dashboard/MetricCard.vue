<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  },
  badgeText: {
    type: String,
    default: ''
  },
  icon: {
    type: String,
    default: '' // SVG path
  },
  theme: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'warning', 'success', 'info', 'danger'].includes(v)
  }
});

const formatNumber = (num) => {
  if (typeof num === 'number') {
    return new Intl.NumberFormat('id-ID').format(num);
  }
  return num;
};
</script>

<template>
  <div class="metric-card card">
    <div class="metric-header">
      <h3 class="metric-title">{{ title }}</h3>
      <div v-if="icon" class="metric-icon" :class="`icon-theme-${theme}`">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path :d="icon" />
        </svg>
      </div>
    </div>
    
    <div class="metric-body">
      <div class="metric-value">{{ formatNumber(value) }}</div>
    </div>
    
    <div class="metric-footer" v-if="subtitle || badgeText">
      <span v-if="badgeText" class="metric-badge" :class="`badge-theme-${theme}`">
        {{ badgeText }}
      </span>
      <span v-if="subtitle" class="metric-subtitle">{{ subtitle }}</span>
    </div>
  </div>
</template>

<style scoped>
.metric-card {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.metric-title {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin: 0;
}

.metric-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
}

.icon-theme-primary { background: var(--color-primary-light); color: var(--color-primary); }
.icon-theme-warning { background: #fef3c7; color: #d97706; }
.icon-theme-success { background: #d1fae5; color: #059669; }
.icon-theme-info { background: #dbeafe; color: #2563eb; }
.icon-theme-danger { background: #fee2e2; color: #dc2626; }

.metric-body {
  margin-bottom: 1rem;
  flex: 1;
}

.metric-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text-navy);
  line-height: 1;
}

.metric-footer {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.metric-badge {
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
}

.badge-theme-primary { background: var(--color-primary-light); color: var(--color-primary-dark); }
.badge-theme-warning { background: #fef3c7; color: #b45309; }
.badge-theme-success { background: #d1fae5; color: #047857; }
.badge-theme-info { background: #dbeafe; color: #1d4ed8; }
.badge-theme-danger { background: #fee2e2; color: #b91c1c; }

.metric-subtitle {
  font-size: 0.7rem;
  color: var(--color-text-secondary);
}
</style>
