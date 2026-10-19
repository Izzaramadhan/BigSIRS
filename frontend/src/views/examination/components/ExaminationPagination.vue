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
  <div class="examination-pagination" v-if="total > 0">
    <div class="pagination-left">
      <!-- Per Page Selector (10, 25, 50) -->
      <div class="per-page-group">
        <label for="exam-perpage" class="per-page-label">Tampilkan</label>
        <select
          id="exam-perpage"
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

      <!-- Info Count: "Menampilkan 1–10 dari 55 total antrean terdaftar" -->
      <div class="pagination-indicator">
        Menampilkan <span class="num-bold">{{ from }}–{{ to }}</span> dari <span class="num-bold">{{ total }}</span> total antrean terdaftar
      </div>
    </div>

    <!-- Navigation Controls -->
    <div class="pagination-right">
      <nav class="pagination-nav" aria-label="Navigasi Halaman Pemeriksaan">
        <!-- Prev Button -->
        <button
          type="button"
          class="btn-page btn-prev"
          :disabled="currentPage <= 1 || loading"
          @click="emit('page-change', currentPage - 1)"
          aria-label="Halaman Sebelumnya"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
          <span class="btn-text">Sebelumnya</span>
        </button>

        <!-- Page Numbers -->
        <div class="page-numbers">
          <template v-for="(p, idx) in pageNumbers" :key="idx">
            <span v-if="p === '...'" class="page-ellipsis">&hellip;</span>
            <button
              v-else
              type="button"
              class="btn-page btn-num"
              :class="{ 'is-active': p === currentPage }"
              :disabled="loading"
              @click="emit('page-change', p)"
              :aria-current="p === currentPage ? 'page' : undefined"
            >
              {{ p }}
            </button>
          </template>
        </div>

        <!-- Next Button -->
        <button
          type="button"
          class="btn-page btn-next"
          :disabled="currentPage >= lastPage || loading"
          @click="emit('page-change', currentPage + 1)"
          aria-label="Halaman Berikutnya"
        >
          <span class="btn-text">Berikutnya</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </nav>
    </div>
  </div>
</template>

<style scoped>
.examination-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 0.85rem 1.25rem;
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 10px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
}

.pagination-left {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  flex-wrap: wrap;
}

.per-page-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.per-page-label, .per-page-text {
  font-size: 0.825rem;
  color: var(--color-text-secondary, #64748b);
}

.per-page-select {
  height: 32px;
  padding: 0 0.5rem;
  font-size: 0.825rem;
  font-weight: 600;
  color: var(--color-text-navy, #1e293b);
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  cursor: pointer;
}

.per-page-select:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
}

.pagination-indicator {
  font-size: 0.825rem;
  color: var(--color-text-secondary, #64748b);
}

.num-bold {
  font-weight: 700;
  color: var(--color-text-navy, #1e293b);
}

.pagination-right {
  display: flex;
  align-items: center;
}

.pagination-nav {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.btn-page {
  height: 34px;
  min-width: 34px;
  padding: 0 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  font-size: 0.825rem;
  font-weight: 600;
  color: var(--color-text-navy, #334155);
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-page:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: var(--color-primary, #087f78);
}

.btn-page:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  border-color: #e2e8f0;
}

.btn-num.is-active {
  background: var(--color-primary, #087f78);
  border-color: var(--color-primary, #087f78);
  color: #ffffff;
}

.btn-prev, .btn-next {
  padding: 0 0.75rem;
}

.btn-prev svg, .btn-next svg {
  width: 14px;
  height: 14px;
}

.page-numbers {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.page-ellipsis {
  padding: 0 0.25rem;
  color: #94a3b8;
  font-weight: 700;
}

@media (max-width: 768px) {
  .examination-pagination {
    flex-direction: column;
    align-items: stretch;
  }
  .pagination-left {
    justify-content: space-between;
  }
  .pagination-right {
    justify-content: center;
  }
  .btn-prev .btn-text, .btn-next .btn-text {
    display: none;
  }
}
</style>
