<template>
  <div class="dashboard-skeleton">
    <!-- Hero Skeleton -->
    <div class="skeleton-card skeleton-hero"></div>
    
    <!-- Filter Skeleton -->
    <div class="skeleton-card skeleton-filter"></div>
    
    <!-- Metrics Skeleton -->
    <div class="skeleton-grid-6">
      <div v-for="i in 6" :key="i" class="skeleton-card skeleton-metric"></div>
    </div>
    
    <!-- Charts Skeleton -->
    <div class="skeleton-grid-2">
      <div class="skeleton-card skeleton-chart-large"></div>
      <div class="skeleton-card skeleton-chart-small"></div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-skeleton {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.skeleton-card {
  background: #ffffff;
  border-radius: 12px;
  position: relative;
  overflow: hidden;
}

.skeleton-card::after {
  content: "";
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  transform: translateX(-100%);
  background-image: linear-gradient(90deg, rgba(255, 255, 255, 0) 0, rgba(232, 237, 243, 0.5) 20%, rgba(255, 255, 255, 0) 60%);
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  100% {
    transform: translateX(100%);
  }
}

.skeleton-hero {
  height: 200px;
  background-color: #f1f5f9;
}

.skeleton-filter {
  height: 60px;
  background-color: #f1f5f9;
}

.skeleton-grid-6 {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1.5rem;
}

.skeleton-metric {
  height: 120px;
  background-color: #f1f5f9;
}

.skeleton-grid-2 {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
}

.skeleton-chart-large {
  height: 350px;
  background-color: #f1f5f9;
}

.skeleton-chart-small {
  height: 350px;
  background-color: #f1f5f9;
}

@media (max-width: 1024px) {
  .skeleton-grid-2 {
    grid-template-columns: 1fr;
  }
}
</style>
