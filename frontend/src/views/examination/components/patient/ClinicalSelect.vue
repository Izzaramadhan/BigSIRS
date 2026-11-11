<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  id: {
    type: String,
    required: true
  },
  modelValue: {
    type: String,
    default: ''
  },
  options: {
    type: Array,
    required: true
  },
  placeholder: {
    type: String,
    default: 'Pilih...'
  }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const containerRef = ref(null);

const normalizedOptions = computed(() =>
  props.options.map((opt) => (typeof opt === 'string' ? { value: opt, label: opt } : opt))
);

const selectedLabel = computed(() => {
  const found = normalizedOptions.value.find((opt) => opt.value === props.modelValue);
  return found ? found.label : props.modelValue || props.placeholder;
});

const toggle = () => {
  isOpen.value = !isOpen.value;
};

const selectOption = (value) => {
  emit('update:modelValue', value);
  isOpen.value = false;
};

const handleDocumentClick = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

const handleKeydown = (e) => {
  if (e.key === 'Escape' && isOpen.value) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleDocumentClick);
  document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick);
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <div ref="containerRef" class="clinical-select-root" :class="{ 'is-open': isOpen }">
    <button
      :id="id"
      type="button"
      class="clinical-select-trigger"
      role="combobox"
      :aria-expanded="isOpen"
      aria-haspopup="listbox"
      @click="toggle"
    >
      <span class="clinical-select-value" :class="{ 'is-placeholder': !modelValue }">
        {{ selectedLabel }}
      </span>
      <svg class="clinical-select-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <polyline points="6 9 12 15 18 9"></polyline>
      </svg>
    </button>

    <div v-if="isOpen" class="clinical-select-popover" role="listbox">
      <button
        v-for="opt in normalizedOptions"
        :key="opt.value"
        type="button"
        role="option"
        class="clinical-select-option"
        :class="{ 'is-selected': opt.value === modelValue }"
        :aria-selected="opt.value === modelValue"
        @click="selectOption(opt.value)"
      >
        <span>{{ opt.label }}</span>
        <svg
          v-if="opt.value === modelValue"
          class="option-check-icon"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2.5"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </button>
    </div>
  </div>
</template>

<style scoped>
.clinical-select-root {
  position: relative;
  width: 100%;
}

.clinical-select-trigger {
  width: 100%;
  height: 40px;
  padding: 0 0.85rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  background: #ffffff;
  color: var(--color-text-navy);
  font-size: 0.875rem;
  font-weight: 400;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  cursor: pointer;
  text-align: left;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.clinical-select-trigger:hover {
  border-color: #cbd5e1;
}

.clinical-select-root.is-open .clinical-select-trigger,
.clinical-select-trigger:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.clinical-select-value {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 400;
  color: var(--color-text-navy);
}

.clinical-select-value.is-placeholder {
  color: var(--color-text-secondary);
}

.clinical-select-chevron {
  width: 16px;
  height: 16px;
  color: var(--color-text-secondary);
  flex-shrink: 0;
  transition: transform 0.18s ease, color 0.15s ease;
}

.clinical-select-root.is-open .clinical-select-chevron {
  transform: rotate(180deg);
  color: var(--color-primary);
}

.clinical-select-popover {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  min-width: 100%;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
  padding: 0.35rem;
  z-index: 60;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.clinical-select-option {
  width: 100%;
  text-align: left;
  background: transparent;
  border: none;
  border-radius: 7px;
  padding: 0.55rem 0.78rem;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--color-text-navy);
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  cursor: pointer;
  transition: background-color 0.12s ease, color 0.12s ease;
}

.clinical-select-option:hover {
  background: #f1f5f9;
  color: var(--color-primary-dark);
}

.clinical-select-option.is-selected {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-weight: 600;
}

.clinical-select-option span {
  font-weight: inherit;
  font-size: inherit;
}

.option-check-icon {
  width: 15px;
  height: 15px;
  color: var(--color-primary);
  flex-shrink: 0;
}
</style>

