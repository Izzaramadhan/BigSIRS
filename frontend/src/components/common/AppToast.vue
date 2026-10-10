<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'success', // success | error | warning | info
  },
  title: {
    type: String,
    required: true
  },
  message: {
    type: String,
    required: true
  },
  duration: {
    type: Number,
    default: 3500
  }
});

const emit = defineEmits(['close']);

let timer = null;

const startTimer = () => {
  clearTimer();
  if (props.duration > 0) {
    timer = setTimeout(() => {
      emit('close');
    }, props.duration);
  }
};

const clearTimer = () => {
  if (timer) {
    clearTimeout(timer);
    timer = null;
  }
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    startTimer();
  } else {
    clearTimer();
  }
});

onMounted(() => {
  if (props.show) {
    startTimer();
  }
});

onUnmounted(() => {
  clearTimer();
});

const closeToast = () => {
  clearTimer();
  emit('close');
};
</script>

<template>
  <Transition name="toast">
    <div 
      v-if="show" 
      class="app-toast" 
      :class="`toast-${type}`" 
      :role="type === 'error' ? 'alert' : 'status'" 
      :aria-live="type === 'error' ? 'assertive' : 'polite'"
    >
      <div class="toast-icon">
        <!-- Success Icon -->
        <svg v-if="type === 'success'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        
        <!-- Error Icon -->
        <svg v-else-if="type === 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        
        <!-- Info/Warning fallback -->
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="16" x2="12" y2="12"></line>
          <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
      </div>
      <div class="toast-content">
        <h4 class="toast-title">{{ title }}</h4>
        <p class="toast-message">{{ message }}</p>
      </div>
      <button type="button" class="toast-close" @click="closeToast" aria-label="Tutup">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
  </Transition>
</template>

<style scoped>
.app-toast {
  position: fixed;
  top: 1.5rem;
  right: 1.5rem;
  z-index: 9999;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  width: 380px;
  max-width: calc(100% - 3rem);
  padding: 1rem;
  background-color: #ffffff;
  border-radius: 12px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border-left: 4px solid transparent;
}

.toast-success {
  border-left-color: #087f78;
}
.toast-success .toast-icon {
  color: #087f78;
}

.toast-error {
  border-left-color: var(--color-danger, #ef4444);
}
.toast-error .toast-icon {
  color: var(--color-danger, #ef4444);
}

.toast-icon {
  flex-shrink: 0;
  margin-top: 0.125rem;
}
.toast-icon svg {
  width: 20px;
  height: 20px;
}

.toast-content {
  flex-grow: 1;
}

.toast-title {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243D);
}

.toast-message {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--color-text-secondary, #64748b);
  line-height: 1.4;
}

.toast-close {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  padding: 0;
  margin-left: 0.5rem;
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s;
}

.toast-close:hover {
  background-color: #f1f5f9;
  color: #475569;
}
.toast-close svg {
  width: 16px;
  height: 16px;
}

/* Animations */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

@media (max-width: 640px) {
  .app-toast {
    top: 1rem;
    right: 1rem;
    left: 1rem;
    width: auto;
    max-width: none;
  }
}
</style>
