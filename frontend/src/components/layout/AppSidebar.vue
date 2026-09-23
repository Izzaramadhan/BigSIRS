<script setup>
import { useRoute } from 'vue-router';

defineProps({
  isOpen: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['close']);

const route = useRoute();

const navigation = [
  {
    label: 'PELAYANAN KLINIS',
    items: [
      { name: 'Dashboard', path: '/dashboard', icon: 'M4 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6zM14 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2V6zM4 16a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2zM14 16a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-2z' },
      { name: 'Pendaftaran', path: '/pendaftaran', icon: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z', disabled: true },
      { name: 'Admisi', path: '/admisi', icon: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8', disabled: true },
      { name: 'Pemeriksaan', path: '/pemeriksaan', icon: 'M22 12h-4l-3 9L9 3l-3 9H2', disabled: true },
      { name: 'Billing Rawat Jalan', path: '/billing', icon: 'M12 1v22 M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6', disabled: true }
    ]
  },
  {
    label: 'LOGISTIK FARMASI',
    items: [
      { name: 'Faktur', path: '/faktur', icon: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6', disabled: true },
      { name: 'Mutasi', path: '/mutasi', icon: 'M8 3v3a2 2 0 0 1-2 2H3 M21 8h-3a2 2 0 0 1-2-2V3 M3 16h3a2 2 0 0 1 2 2v3 M16 21v-3a2 2 0 0 1 2-2h3 M2 12h20 M12 2v20', disabled: true },
      { name: 'Stok Opname', path: '/stok', icon: 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z M3.27 6.96L12 12.01l8.73-5.05 M12 22.08V12', disabled: true },
      { name: 'Pengeluaran Barang', path: '/pengeluaran', icon: 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4 M17 8l-5-5-5 5 M12 3v12', disabled: true },
      { name: 'Penjualan Bebas', path: '/penjualan', icon: 'M9 11l3 3L22 4 M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11', disabled: true }
    ]
  },
  {
    label: 'SISTEM & PENGATURAN',
    items: [
      { name: 'Laporan', path: '/laporan', icon: 'M3 3v18h18 M18 9l-5-5-3 3-5-5', disabled: true },
      {
        name: 'Master Data',
        icon: 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z',
        expanded: false,
        submenu: [
          { name: 'Poliklinik', path: '/master-data/polyclinics' },
          { name: 'Penjamin', path: '/master-data/guarantors', disabled: true }
        ]
      },
      { name: 'Pengaturan', path: '/pengaturan', icon: 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z', disabled: true }
    ]
  }
];

import { ref, watch, onMounted } from 'vue';

const expandedMenus = ref({});

const toggleSubmenu = (menuName) => {
  expandedMenus.value[menuName] = !expandedMenus.value[menuName];
};

const handleMenuClick = (item) => {
  if (item.disabled) {
    return;
  }
  if (item.submenu) {
    toggleSubmenu(item.name);
  }
};

const checkActiveSubmenu = () => {
  navigation.forEach(group => {
    group.items.forEach(item => {
      if (item.submenu) {
        const isActive = item.submenu.some(sub => route.path.startsWith(sub.path));
        if (isActive) {
          expandedMenus.value[item.name] = true;
        }
      }
    });
  });
};

onMounted(() => {
  checkActiveSubmenu();
});

watch(() => route.path, () => {
  checkActiveSubmenu();
});
</script>

<template>
  <aside class="app-sidebar" :class="{ 'app-sidebar--open': isOpen }" aria-label="Sidebar Navigasi">
    <router-link
      to="/dashboard"
      class="sidebar-brand"
      aria-label="BigSirs SIMRS - Dashboard"
    >
      <img
        src="@/assets/logo/logo-sisfo.webp"
        alt=""
        class="sidebar-brand__logo"
      />
      <span class="sidebar-brand__text">
        <strong>BigSirs SIMRS</strong>
        <small>PT SISFOMEDIKA</small>
      </span>
    </router-link>

    <nav class="sidebar-nav">
      <template v-for="(group, idx) in navigation" :key="idx">
        <h3 class="nav-group-title">{{ group.label }}</h3>
        <ul class="nav-list">
          <li v-for="item in group.items" :key="item.name" class="nav-item">
            <!-- Normal Link -->
            <router-link
              v-if="!item.disabled && !item.submenu"
              :to="item.path"
              class="nav-link"
              :class="{ 'nav-link--active': route.path === item.path }"
              :aria-current="route.path === item.path ? 'page' : undefined"
            >
              <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path :d="item.icon" />
              </svg>
              <span>{{ item.name }}</span>
            </router-link>

            <!-- Submenu Parent -->
            <button
              v-else-if="!item.disabled && item.submenu"
              class="nav-link nav-link--submenu"
              :class="{ 'nav-link--active-parent': item.submenu.some(sub => route.path.startsWith(sub.path)) }"
              type="button"
              @click="handleMenuClick(item)"
              :aria-expanded="expandedMenus[item.name]"
            >
              <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path :d="item.icon" />
              </svg>
              <span>{{ item.name }}</span>
              <svg class="submenu-icon" :class="{ 'submenu-icon--open': expandedMenus[item.name] }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>

            <!-- Disabled -->
            <button
              v-else
              class="nav-link nav-link--disabled"
              type="button"
              @click="handleMenuClick(item)"
              title="Fitur belum tersedia"
            >
              <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path :d="item.icon" />
              </svg>
              <span>{{ item.name }}</span>
            </button>

            <!-- Submenu Items -->
            <ul v-if="item.submenu" v-show="expandedMenus[item.name]" class="submenu-list">
              <li v-for="sub in item.submenu" :key="sub.name" class="submenu-item">
                <router-link
                  v-if="!sub.disabled"
                  :to="sub.path"
                  class="submenu-link"
                  :class="{ 'submenu-link--active': route.path === sub.path }"
                >
                  {{ sub.name }}
                </router-link>
                <button
                  v-else
                  class="submenu-link submenu-link--disabled"
                  type="button"
                  title="Fitur belum tersedia"
                >
                  {{ sub.name }}
                </button>
              </li>
            </ul>
          </li>
        </ul>
      </template>
    </nav>

    <div class="sidebar-footer">
      <div class="integration-status">
        <h4 class="integration-title">Status Integrasi Nasional</h4>
        <ul class="integration-list">
          <li>
            <span>SatuSehat Kemenkes</span>
            <span class="status-badge status-online">ONLINE</span>
          </li>
          <li>
            <span>BPJS V-Claim 2.0</span>
            <span class="status-badge status-online">ONLINE</span>
          </li>
        </ul>
      </div>
    </div>
  </aside>

  <div v-if="isOpen" class="sidebar-overlay" @click="emit('close')" aria-hidden="true"></div>
</template>

<style scoped>
.app-sidebar {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  width: var(--sidebar-width);
  background: #ffffff;
  border-right: 1px solid var(--color-border-soft);
  z-index: 40;
  display: flex;
  flex-direction: column;
  transition: transform 0.3s ease;
}

.sidebar-brand {
  height: var(--topbar-height);
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 1.25rem;
  border-bottom: 1px solid var(--color-border-soft);
  min-width: 0;
  text-decoration: none;
}

.sidebar-brand__logo {
  display: block;
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  object-fit: contain;
}

.sidebar-brand__text {
  display: flex;
  min-width: 0;
  flex-direction: column;
  line-height: 1.2;
}

.sidebar-brand__text strong {
  color: var(--color-text-navy);
  font-size: 0.95rem;
  font-weight: 700;
}

.sidebar-brand__text small {
  margin-top: 3px;
  color: var(--color-text-secondary);
  font-size: 0.65rem;
  letter-spacing: 0.05em;
  font-weight: 600;
}

.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem 1rem;
}

.nav-group-title {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  margin-bottom: 0.75rem;
  letter-spacing: 0.05em;
}

.nav-list {
  list-style: none;
  padding: 0;
  margin: 0 0 1.5rem 0;
}

.nav-item {
  margin-bottom: 0.25rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  border-radius: 8px;
  text-decoration: none;
  color: var(--color-text-navy);
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.2s;
  background: transparent;
  border: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  font-family: inherit;
}

.nav-link:hover:not(.nav-link--disabled) {
  background: var(--color-primary-light);
}

.nav-link--active {
  background: var(--color-primary) !important;
  color: #ffffff;
}

.nav-link--disabled {
  color: #94a3b8;
  cursor: not-allowed;
}

.nav-icon {
  width: 18px;
  height: 18px;
}

.nav-link--submenu {
  justify-content: space-between;
}

.nav-link--submenu span {
  flex: 1;
}

.submenu-icon {
  width: 16px;
  height: 16px;
  transition: transform 0.2s ease;
}

.submenu-icon--open {
  transform: rotate(180deg);
}

.nav-link--active-parent {
  color: var(--color-primary);
  background: var(--color-primary-light);
}

.nav-link--active-parent .nav-icon {
  color: var(--color-primary);
}

.submenu-list {
  list-style: none;
  padding: 0;
  margin: 0;
  padding-left: 2.25rem;
  margin-top: 0.25rem;
}

.submenu-item {
  margin-bottom: 0.2rem;
}

.submenu-link {
  display: block;
  padding: 0.5rem 0.75rem;
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  text-decoration: none;
  border-radius: 6px;
  transition: all 0.2s;
  background: transparent;
  border: none;
  width: 100%;
  text-align: left;
  font-family: inherit;
  font-weight: 500;
  cursor: pointer;
}

.submenu-link:hover:not(.submenu-link--disabled) {
  color: var(--color-primary);
  background: var(--color-page-bg);
}

.submenu-link--active {
  color: var(--color-primary);
  font-weight: 600;
  background: var(--color-page-bg);
}

.submenu-link--disabled {
  color: #94a3b8;
  cursor: not-allowed;
}

.sidebar-footer {
  padding: 1.25rem;
  border-top: 1px solid var(--color-border-soft);
}

.integration-status {
  background: var(--color-page-bg);
  border-radius: 8px;
  padding: 0.75rem;
}

.integration-title {
  font-size: 0.7rem;
  color: var(--color-text-secondary);
  margin: 0 0 0.5rem 0;
  font-weight: 600;
}

.integration-list {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 0.75rem;
}

.integration-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
  color: var(--color-text-navy);
}

.status-badge {
  font-size: 0.6rem;
  padding: 0.15rem 0.4rem;
  border-radius: 12px;
  font-weight: 600;
}

.status-online {
  background: #d1fae5;
  color: #059669;
}

.sidebar-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 30;
}

@media (max-width: 1024px) {
  .app-sidebar {
    transform: translateX(-100%);
  }
  .app-sidebar--open {
    transform: translateX(0);
  }
  .sidebar-overlay {
    display: block;
  }
}
</style>
