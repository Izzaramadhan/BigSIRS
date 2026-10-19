<script setup>
import { computed } from 'vue';

const props = defineProps({
  value: {
    type: String,
    required: true
  },
  type: {
    type: String,
    default: 'status' // 'status' | 'payment' | 'customer'
  }
});

const statusClass = computed(() => {
  switch (props.value) {
    case 'Lunas':
      return 'badge-paid';
    case 'Draft':
      return 'badge-draft';
    case 'Dibatalkan':
      return 'badge-cancelled';
    default:
      return 'badge-default';
  }
});

const paymentClass = computed(() => {
  const val = (props.value || '').toLowerCase();
  if (val.includes('qris')) {
    return 'payment-qris';
  }
  if (val.includes('tunai') || val.includes('cash')) {
    return 'payment-cash';
  }
  if (val.includes('bca')) {
    return 'payment-debit-bca';
  }
  if (val.includes('mandiri')) {
    return 'payment-debit-mandiri';
  }
  return 'payment-default';
});

const customerClass = computed(() => {
  const val = (props.value || '').toLowerCase();
  if (val.includes('staf') || val.includes('karyawan')) {
    return 'cust-staff';
  }
  if (val.includes('pasien')) {
    return 'cust-patient';
  }
  return 'cust-general';
});

const badgeClasses = computed(() => {
  if (props.type === 'payment') {
    return ['payment-badge', paymentClass.value];
  }
  if (props.type === 'customer') {
    return ['customer-badge', customerClass.value];
  }
  return ['status-badge', statusClass.value];
});
</script>

<template>
  <span :class="badgeClasses">
    <template v-if="type === 'payment'">
      <svg v-if="value.toLowerCase().includes('qris')" class="payment-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7"></rect>
        <rect x="14" y="3" width="7" height="7"></rect>
        <rect x="14" y="14" width="7" height="7"></rect>
        <rect x="3" y="14" width="7" height="7"></rect>
      </svg>
      <svg v-else-if="value.toLowerCase().includes('tunai') || value.toLowerCase().includes('cash')" class="payment-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="2" y="6" width="20" height="12" rx="2"></rect>
        <circle cx="12" cy="12" r="2"></circle>
      </svg>
      <svg v-else-if="value.toLowerCase().includes('debit')" class="payment-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
        <line x1="1" y1="10" x2="23" y2="10"></line>
      </svg>
      <span>{{ value }}</span>
    </template>

    <template v-else-if="type === 'customer'">
      <span>{{ value }}</span>
    </template>

    <template v-else>
      <span class="status-dot"></span>
      <span>{{ value }}</span>
    </template>
  </span>
</template>

<style scoped>
/* Status Badges */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.76rem;
  font-weight: 600;
  line-height: 1.2;
  white-space: nowrap;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.badge-paid {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.badge-paid .status-dot {
  background: #10b981;
}

.badge-draft {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.badge-draft .status-dot {
  background: #94a3b8;
}

.badge-cancelled {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.badge-cancelled .status-dot {
  background: #ef4444;
}

.badge-default {
  background: #f8fafc;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.badge-default .status-dot {
  background: #94a3b8;
}

/* Payment Badges */
.payment-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.22rem 0.55rem;
  border-radius: 6px;
  font-size: 0.74rem;
  font-weight: 600;
  line-height: 1.2;
  white-space: nowrap;
}

.payment-icon {
  width: 13px;
  height: 13px;
  flex-shrink: 0;
}

.payment-qris {
  background: #e6fffa;
  color: #047481;
  border: 1px solid #b2f5ea;
}

.payment-cash {
  background: #f8fafc;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.payment-debit-bca {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

.payment-debit-mandiri {
  background: #ecfeff;
  color: #0e7490;
  border: 1px solid #a5f3fc;
}

.payment-default {
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #cbd5e1;
}

/* Customer Badges */
.customer-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.12rem 0.4rem;
  border-radius: 4px;
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.cust-general {
  background: #f1f5f9;
  color: #64748b;
}

.cust-staff {
  background: #ccfbf1;
  color: #0f766e;
}

.cust-patient {
  background: #e0e7ff;
  color: #4338ca;
}
</style>
