<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AppSidebar from '@/components/layout/AppSidebar.vue';
import AppTopbar from '@/components/layout/AppTopbar.vue';

const isSidebarOpen = ref(false);

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};

const closeSidebar = () => {
  isSidebarOpen.value = false;
};

const handleKeydown = (e) => {
  if (e.key === 'Escape' && isSidebarOpen.value) {
    closeSidebar();
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
  
  // Auto open sidebar on desktop
  if (window.innerWidth > 1024) {
    isSidebarOpen.value = true;
  }
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <div class="app-layout">
    <AppSidebar 
      :is-open="isSidebarOpen" 
      @close="closeSidebar" 
    />
    
    <div class="main-wrapper">
      <AppTopbar @toggle-sidebar="toggleSidebar" />
      
      <main class="main-content">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
  width: 100%;
}

.main-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  transition: margin-left 0.3s ease;
}

.main-content {
  flex: 1;
  padding: 1.5rem;
}

@media (min-width: 1025px) {
  .main-wrapper {
    margin-left: var(--sidebar-width);
  }
}

@media (max-width: 640px) {
  .main-content {
    padding: 1rem;
  }
}
</style>
