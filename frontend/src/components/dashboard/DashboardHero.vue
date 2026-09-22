<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

defineProps({
  lastUpdated: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['refresh']);

const authStore = useAuthStore();

const userName = computed(() => {
  if (authStore.user?.name) return authStore.user.name;
  if (authStore.user?.username) return authStore.user.username;
  return 'Pengguna';
});

const currentDate = computed(() => {
  const date = new Date();
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  return date.toLocaleDateString('id-ID', options);
});
</script>

<template>
  <div class="dashboard-hero">
    <div class="hero-content">
      <div class="hero-date-badge">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        <span>{{ currentDate }} &bull; Shift Pagi (07.00 - 14.00 WIB)</span>
      </div>
      
      <div class="hero-greeting">
        <p class="greeting-text">Selamat Datang,</p>
        <h1 class="greeting-name">{{ userName }}</h1>
      </div>
      
      <p class="hero-description">
        Pantau kunjungan dan pelayanan rawat jalan dengan mudah, cepat, dan terintegrasi. Seluruh sinkronisasi data antrean poliklinik dan bridging rujukan BPJS berjalan optimal.
      </p>
      
      <div class="hero-footer">
        <span class="last-updated">
          <span class="status-dot"></span>
          Data terakhir diperbarui pukul {{ lastUpdated }} WIB
        </span>
        <button class="refresh-btn" @click="emit('refresh')" aria-label="Refresh Data">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
        </button>
      </div>
    </div>
    
    <div class="hero-illustration">
      <img src="@/assets/dashboard/dashboard-background.webp" alt="" class="illustration-img" />
    </div>
  </div>
</template>

<style scoped>
.dashboard-hero {
  background: var(--color-card-bg);
  border: 1px solid var(--color-border-soft);
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  position: relative;
  margin-bottom: 1.5rem;
}

.hero-content {
  flex: 1;
  padding: 2rem;
  position: relative;
  z-index: 2;
}

.hero-date-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--color-page-bg);
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-primary-dark);
  margin-bottom: 1.5rem;
  border: 1px solid var(--color-border-soft);
}

.hero-greeting {
  margin-bottom: 1rem;
}

.greeting-text {
  font-size: 1rem;
  color: var(--color-text-secondary);
  margin: 0;
}

.greeting-name {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-primary);
  margin: 0;
  line-height: 1.2;
}

.hero-description {
  color: var(--color-text-secondary);
  font-size: 0.9rem;
  max-width: 600px;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.hero-footer {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.last-updated {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-primary);
}

.status-dot {
  width: 8px;
  height: 8px;
  background-color: var(--color-primary);
  border-radius: 50%;
  display: inline-block;
  box-shadow: 0 0 0 2px var(--color-primary-light);
}

.refresh-btn {
  background: none;
  border: none;
  color: var(--color-primary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.4rem;
  border-radius: 50%;
  transition: background 0.2s;
}

.refresh-btn:hover {
  background: var(--color-primary-light);
}

.hero-illustration {
  flex: 0 0 40%;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding-right: 2rem;
}

.illustration-img {
  max-width: 100%;
  height: auto;
  max-height: 200px;
  object-fit: contain;
}

@media (max-width: 768px) {
  .dashboard-hero {
    flex-direction: column;
  }
  
  .hero-illustration {
    flex: none;
    padding: 0 2rem 2rem;
    justify-content: center;
  }
  
  .illustration-img {
    max-height: 150px;
  }
  
  .greeting-name {
    font-size: 1.5rem;
  }
}
</style>
