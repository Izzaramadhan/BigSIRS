<script setup>
import { RouterView } from 'vue-router'
import { useAuthStore } from './stores/auth'

const authStore = useAuthStore()
</script>

<template>
  <div v-if="!authStore.initialized" style="padding: 2rem; text-align: center; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh;">
    Memeriksa sesi...
  </div>
  <RouterView v-else />
</template>

<style>
/* Global styles can be added here if needed, or in main.css */
body {
  margin: 0;
  padding: 0;
}
</style>
