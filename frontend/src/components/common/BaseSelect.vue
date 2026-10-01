<script setup>
defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  options: {
    type: Array,
    required: true
  },
  placeholder: {
    type: String,
    default: 'Pilih...'
  },
  id: {
    type: String,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  hasError: {
    type: Boolean,
    default: false
  },
  emptyMessage: {
    type: String,
    default: 'Data tidak tersedia'
  }
});

const emit = defineEmits(['update:modelValue']);

const handleChange = (event) => {
  const value = event.target.value;
  emit('update:modelValue', value ? Number(value) : '');
};

const getDisplayName = (opt) => {
  if (opt.code && opt.name) {
    return `${opt.code} — ${opt.name}`;
  }
  return opt.name || opt.description || opt.code || 'Unknown';
};

const getStatus = (opt) => {
  if (opt.is_active === false) {
    return ' (Nonaktif)';
  }
  return '';
};
</script>

<template>
  <div class="base-select-wrapper">
    <select
      :id="id"
      :value="modelValue"
      @change="handleChange"
      class="form-input"
      :class="{ 'has-error': hasError, 'is-loading': loading }"
      :disabled="disabled || loading"
    >
      <option value="" disabled selected>{{ loading ? 'Memuat data...' : placeholder }}</option>
      <template v-if="!loading && options.length > 0">
        <option
          v-for="opt in options"
          :key="opt.id"
          :value="opt.id"
        >
          {{ getDisplayName(opt) }}{{ getStatus(opt) }}
        </option>
      </template>
      <template v-else-if="!loading && options.length === 0">
        <option value="" disabled>{{ emptyMessage }}</option>
      </template>
    </select>
  </div>
</template>

<style scoped>
.base-select-wrapper {
  position: relative;
  width: 100%;
}

.form-input {
  width: 100%;
  height: 44px; /* Consistent height 42-44px */
  padding: 0.5rem 2rem 0.5rem 0.75rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  background-color: #fff;
  font-size: 0.95rem;
  color: #0f172a;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 1rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-input:focus {
  outline: none;
  border-color: #0369a1;
  box-shadow: 0 0 0 3px rgba(3, 105, 161, 0.1);
}

.form-input:disabled {
  background-color: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}

.form-input.has-error {
  border-color: #ef4444;
}

.form-input.has-error:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
</style>
