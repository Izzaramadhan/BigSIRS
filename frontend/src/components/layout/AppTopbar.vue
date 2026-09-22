<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const emit = defineEmits(['toggle-sidebar']);

const authStore = useAuthStore();
const router = useRouter();

const userMenuOpen = ref(false);

const userName = computed(() => {
  if (authStore.user?.name) return authStore.user.name;
  if (authStore.user?.username) return authStore.user.username;
  return 'Pengguna';
});

const userRole = computed(() => {
  return authStore.user?.role || 'Administrator';
});

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value;
};

const closeUserMenu = (e) => {
  if (userMenuOpen.value && !e.target.closest('.user-menu-wrapper')) {
    userMenuOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', closeUserMenu);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeUserMenu);
});

const handleLogout = async () => {
  await authStore.logout();
  router.push({ name: 'login' });
};
</script>

<template>
  <header class="app-topbar">
    <div class="topbar-left">
      <button 
        class="menu-toggle-btn" 
        @click="emit('toggle-sidebar')"
        aria-label="Toggle Sidebar"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>

      <div class="search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input 
          type="search" 
          class="search-input" 
          placeholder="Cari pasien, No. RM, NIK, atau dokter..."
          aria-label="Cari data"
        />
      </div>
    </div>

    <div class="topbar-right">
      <button class="icon-btn" aria-label="Notifikasi">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        <span class="badge">3</span>
      </button>

      <button class="icon-btn" aria-label="Bantuan">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
      </button>

      <div class="divider"></div>

      <div class="user-menu-wrapper">
        <button 
          class="user-profile-btn" 
          @click="toggleUserMenu"
          :aria-expanded="userMenuOpen"
          aria-haspopup="true"
        >
          <div class="user-avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </div>
          <div class="user-info">
            <span class="user-name">{{ userName }}</span>
            <span class="user-role">{{ userRole }}</span>
          </div>
          <svg class="chevron-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>

        <div v-if="userMenuOpen" class="user-dropdown">
          <ul class="dropdown-menu">
            <li>
              <button @click="handleLogout" class="dropdown-item text-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Keluar Aplikasi
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </header>
</template>

<style scoped>
.app-topbar {
  height: var(--topbar-height);
  background: #ffffff;
  border-bottom: 1px solid var(--color-border-soft);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  position: sticky;
  top: 0;
  z-index: 30;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.menu-toggle-btn {
  display: none;
  background: none;
  border: none;
  color: var(--color-text-navy);
  cursor: pointer;
  padding: 0.25rem;
}

.search-wrapper {
  position: relative;
  width: 100%;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.search-input {
  width: 100%;
  background: var(--color-page-bg);
  border: 1px solid transparent;
  border-radius: 20px;
  padding: 0.6rem 1rem 0.6rem 2.5rem;
  font-size: 0.85rem;
  color: var(--color-text-navy);
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  background: #ffffff;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.1);
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.icon-btn {
  background: none;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem;
  border-radius: 50%;
  transition: background 0.2s;
}

.icon-btn:hover {
  background: var(--color-page-bg);
  color: var(--color-primary);
}

.badge {
  position: absolute;
  top: 0;
  right: 0;
  background: var(--color-danger);
  color: white;
  font-size: 0.6rem;
  font-weight: 700;
  min-width: 16px;
  height: 16px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #ffffff;
}

.divider {
  width: 1px;
  height: 32px;
  background: var(--color-border-soft);
}

.user-menu-wrapper {
  position: relative;
}

.user-profile-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  transition: background 0.2s;
}

.user-profile-btn:hover {
  background: var(--color-page-bg);
}

.user-avatar {
  width: 32px;
  height: 32px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.user-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-navy);
  line-height: 1.2;
}

.user-role {
  font-size: 0.7rem;
  color: var(--color-text-secondary);
}

.chevron-icon {
  color: var(--color-text-secondary);
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  width: 200px;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  z-index: 50;
}

.dropdown-menu {
  list-style: none;
  padding: 0.5rem;
  margin: 0;
}

.dropdown-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: none;
  border: none;
  text-align: left;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  border-radius: 6px;
  transition: background 0.2s;
}

.dropdown-item:hover {
  background: var(--color-danger-bg);
}

@media (max-width: 1024px) {
  .menu-toggle-btn {
    display: block;
  }
}

@media (max-width: 640px) {
  .user-info {
    display: none;
  }
  .search-wrapper {
    display: none;
  }
}
</style>
