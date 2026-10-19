<script setup>
import { computed } from 'vue';

const props = defineProps({
  currentPage: {
    type: Number,
    required: true
  },
  lastPage: {
    type: Number,
    required: true
  },
  perPage: {
    type: Number,
    default: 10
  },
  total: {
    type: Number,
    required: true
  },
  from: {
    type: Number,
    default: 0
  },
  to: {
    type: Number,
    default: 0
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['page-change', 'update:perPage']);

const perPageOptions = [10, 25, 50];

const handlePerPageChange = (event) => {
  emit('update:perPage', Number(event.target.value));
};

const pageNumbers = computed(() => {
  const current = props.currentPage;
  const last = props.lastPage;
  const delta = 1;
  const left = current - delta;
  const right = current + delta;
  const range = [];
  const rangeWithDots = [];
  let l;

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= left && i <= right)) {
      range.push(i);
    }
  }

  for (const i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1);
      } else if (i - l !== 1) {
        rangeWithDots.push('...');
      }
    }
    rangeWithDots.push(i);
    l = i;
  }

  return rangeWithDots;
});
</script>

<template>
  <div class="patient-pagination" v-if="total > 0">
    <div class="pagination-left">
      <!-- Per Page Selector -->
      <div class="per-page-selector">
        <label for="per-page-select" class="per-page-label">Tampilkan</label>
        <select 
          id="per-page-select" 
          :value="perPage" 
          @change="handlePerPageChange"
          class="form-select per-page-select"
          :disabled="loading"
        >
          <option v-for="opt in perPageOptions" :key="opt" :value="opt">
            {{ opt }}
          </option>
        </select>
        <span class="per-page-text">data per halaman</span>
      </div>

      <!-- Item Counter Indicator: "Menampilkan 1–10 dari 24 pasien" -->
      <div class="pagination-indicator">
        Menampilkan <span class="highlight-num">{{ from }}–{{ to }}</span> dari <span class="highlight-num">{{ total }}</span> pasien
      </div>
    </div>

    <!-- Navigation Controls -->
    <div class="pagination-controls" v-if="lastPage > 1">
      <!-- Previous Button -->
      <button 
        type="button" 
        class="page-btn page-btn-nav" 
        :disabled="currentPage === 1 || loading"
        @click="emit('page-change', currentPage - 1)"
        aria-label="Halaman Sebelumnya"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        <span>Sebelumnya</span>
      </button>

      <!-- Page Numbers with Ellipsis -->
      <div class="page-numbers-list">
        <template v-for="(p, idx) in pageNumbers" :key="idx">
          <span v-if="p === '...'" class="page-ellipsis">...</span>
          <button 
            v-else 
            type="button" 
            class="page-btn page-btn-num" 
            :class="{ 'active': p === currentPage }"
            :disabled="loading"
            @click="p !== currentPage ? emit('page-change', p) : null"
            :aria-current="p === currentPage ? 'page' : undefined"
          >
            {{ p }}
          </button>
        </template>
      </div>

      <!-- Next Button -->
      <button 
        type="button" 
        class="page-btn page-btn-nav" 
        :disabled="currentPage === lastPage || loading"
        @click="emit('page-change', currentPage + 1)"
        aria-label="Halaman Selanjutnya"
      >
        <span>Selanjutnya</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    </div>
  </div>
</template>

<style scoped>
.patient-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 1rem 0.25rem 0.5rem 0.25rem;
}

.pagination-left {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.per-page-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.per-page-select {
  width: auto;
  padding: 0.35rem 1.8rem 0.35rem 0.65rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy);
  background-color: #ffffff;
  border: 1px solid var(--color-border-soft);
  cursor: pointer;
}

.pagination-indicator {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.highlight-num {
  font-weight: 600;
  color: var(--color-text-navy);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.page-numbers-list {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.page-btn {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  transition: all 0.2s ease;
  min-height: 34px;
}

.page-btn:hover:not(:disabled) {
  background: var(--color-page-bg);
  border-color: #cbd5e1;
}

.page-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  background: #f8fafc;
}

.page-btn-num {
  min-width: 34px;
  justify-content: center;
  padding: 0.4rem 0.5rem;
}

.page-btn-num.active {
  background: var(--color-primary);
  color: #ffffff;
  border-color: var(--color-primary);
  font-weight: 600;
}

.page-btn-nav svg {
  width: 14px;
  height: 14px;
}

.page-ellipsis {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  color: var(--color-text-secondary);
  font-size: 0.85rem;
}

@media (max-width: 768px) {
  .patient-pagination {
    flex-direction: column;
    align-items: stretch;
  }

  .pagination-left {
    justify-content: space-between;
  }

  .pagination-controls {
    justify-content: center;
    width: 100%;
  }
}

@media (max-width: 480px) {
  .pagination-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
}
</style>
