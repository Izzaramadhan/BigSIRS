<script setup>
defineProps({
  columns: {
    type: Number,
    default: 5
  },
  rows: {
    type: Number,
    default: 5
  }
});
</script>

<template>
  <tr v-for="i in rows" :key="`skeleton-${i}`" class="skeleton-row">
    <td v-for="j in columns" :key="`col-${j}`" :class="{'text-center': j === 1 || j === columns}">
      <div v-if="j === 1" class="skeleton-box skeleton-small mx-auto"></div>
      <div v-else-if="j === columns" class="action-buttons">
        <div class="skeleton-box skeleton-circle"></div>
        <div class="skeleton-box skeleton-circle"></div>
      </div>
      <div v-else :class="['skeleton-box', j % 2 === 0 ? 'skeleton-large' : 'skeleton-medium']"></div>
    </td>
  </tr>
</template>

<style scoped>
.skeleton-box {
  height: 16px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
  border-radius: 4px;
}

.skeleton-small { width: 40px; }
.skeleton-medium { width: 80px; }
.skeleton-large { width: 150px; max-width: 100%; }
.skeleton-circle { width: 28px; height: 28px; border-radius: 6px; }

.action-buttons {
  display: flex;
  gap: 0.25rem;
  justify-content: center;
}

.mx-auto {
  margin-left: auto;
  margin-right: auto;
}

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
