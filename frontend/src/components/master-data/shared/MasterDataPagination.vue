<script setup>
import { computed } from 'vue';

const props = defineProps({
  pagination: {
    type: Object,
    required: true,
    // Expected structure: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 1, to: 10 }
  },
  loading: {
    type: Boolean,
    default: false
  },
  itemCount: {
    type: Number,
    default: 0
  }
});

defineEmits(['page-change']);

const startItem = computed(() => {
  if (props.pagination.total === 0) return 0;
  if (props.pagination.from !== undefined && props.pagination.from !== null) return props.pagination.from;
  return (props.pagination.current_page - 1) * props.pagination.per_page + 1;
});

const endItem = computed(() => {
  if (props.pagination.total === 0) return 0;
  if (props.pagination.to !== undefined && props.pagination.to !== null) return props.pagination.to;
  return Math.min(props.pagination.current_page * props.pagination.per_page, props.pagination.total);
});

const pageNumbers = computed(() => {
  const current = props.pagination.current_page;
  const last = props.pagination.last_page;
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

  for (let i of range) {
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
  <div class="pagination-container" v-if="pagination && pagination.last_page > 0">
    <div class="pagination-info">
      Menampilkan {{ startItem }} 
      sampai {{ endItem }} 
      dari {{ pagination.total || 0 }} entri
    </div>
    
    <div class="pagination-controls" v-if="pagination.last_page > 0">
      <button 
        class="page-btn" 
        :disabled="pagination.current_page === 1 || loading"
        @click="$emit('page-change', pagination.current_page - 1)"
      >
        Sebelumnya
      </button>
      
      <div class="page-numbers">
        <template v-for="(p, index) in pageNumbers" :key="index">
          <span v-if="p === '...'" class="page-dots">...</span>
          <button 
            v-else
            class="page-btn page-number"
            :class="{ 'active': p === pagination.current_page }"
            @click="p !== pagination.current_page ? $emit('page-change', p) : null"
            :disabled="loading"
          >
            {{ p }}
          </button>
        </template>
      </div>
      
      <button 
        class="page-btn" 
        :disabled="pagination.current_page === pagination.last_page || loading"
        @click="$emit('page-change', pagination.current_page + 1)"
      >
        Selanjutnya
      </button>
    </div>
  </div>
</template>

<style scoped>
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.page-numbers {
  display: flex;
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
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  background: var(--color-page-bg);
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-number {
  min-width: 32px;
  text-align: center;
}

.page-number.active {
  background: var(--color-primary);
  color: #ffffff;
  border-color: var(--color-primary);
}

.page-dots {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
  color: var(--color-text-secondary);
  font-weight: 500;
  padding: 0 0.25rem;
}

@media (max-width: 640px) {
  .pagination-container {
    flex-direction: column;
    align-items: center;
  }
}
</style>
