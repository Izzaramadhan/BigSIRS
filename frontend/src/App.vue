<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import AppLayout from './layouts/AppLayout.vue'

const authStore = useAuthStore()
const route = useRoute()

const isAuthRoute = computed(() => {
  return route.meta.requiresAuth === true
})
</script>

<template>
  <div v-if="!authStore.initialized" class="loading-screen">
    Memeriksa sesi...
  </div>
  
  <template v-else>
    <AppLayout v-if="isAuthRoute">
      <RouterView />
    </AppLayout>
    <RouterView v-else />
  </template>
</template>

<style>
/* Global styles */
body {
  margin: 0;
  padding: 0;
}
.loading-screen {
  padding: 2rem;
  text-align: center;
  font-family: sans-serif;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
}
</style>
