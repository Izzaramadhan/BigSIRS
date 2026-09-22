<script setup>
defineProps({
  items: {
    type: Array,
    default: () => [
      { id: 1, title: 'Pendaftaran Pasien', subtitle: 'Antrean Baru', icon: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z' },
      { id: 2, title: 'Pasien Baru', subtitle: 'Cetak No. RM', icon: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8' },
      { id: 3, title: 'Antrean Poli', subtitle: 'Panggil Pasien', icon: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M23 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75' },
      { id: 4, title: 'Input EMR Poli', subtitle: 'SOAP & Resep', icon: 'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z' },
      { id: 5, title: 'Kasir Rawat Jalan', subtitle: 'Kwitansi & Bayar', icon: 'M12 1v22 M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6' },
      { id: 6, title: 'Laporan RL 5.1', subtitle: 'Pemeriksaan Khusus', icon: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8' }
    ]
  }
});

const handleClick = () => {
  // Navigation or alert logic
};
</script>

<template>
  <div class="quick-access card">
    <div class="card-header">
      <h3 class="card-title">Akses Cepat Rawat Jalan</h3>
      <p class="card-subtitle">Pintasan ke modul operasional front-desk hingga klinis.</p>
    </div>
    
    <div class="quick-grid">
      <button 
        v-for="(item, index) in items" 
        :key="item.id"
        class="quick-btn"
        :class="`bg-color-${index % 2}`"
        @click="handleClick(item)"
      >
        <div class="quick-icon-wrapper">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path :d="item.icon" />
          </svg>
        </div>
        <div class="quick-text">
          <span class="quick-title">{{ item.title }}</span>
          <span class="quick-desc">{{ item.subtitle }}</span>
        </div>
      </button>
    </div>
    
    <div class="quick-footer">
      <div class="integration-pill">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>Bridging Antrean Online BPJS v2.0 Aktif</span>
      </div>
      <span class="status-online">TERKONEKSI</span>
    </div>
  </div>
</template>

<style scoped>
.quick-access {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.card-header {
  margin-bottom: 1.25rem;
}

.card-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-navy);
  margin: 0 0 0.25rem 0;
}

.card-subtitle {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  margin: 0;
}

.quick-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.25rem;
  flex: 1;
}

.quick-btn {
  border: 1px solid transparent;
  border-radius: 8px;
  padding: 1rem 0.75rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  border-color: var(--color-primary-light);
}

.bg-color-0 {
  background-color: #f8fafc;
}

.bg-color-1 {
  background-color: var(--color-primary-light);
}

.quick-icon-wrapper {
  color: var(--color-primary);
  margin-bottom: 0.25rem;
}

.quick-text {
  display: flex;
  flex-direction: column;
}

.quick-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-navy);
  line-height: 1.2;
  margin-bottom: 0.2rem;
}

.quick-desc {
  font-size: 0.65rem;
  color: var(--color-text-secondary);
}

.quick-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 1rem;
  border-top: 1px dashed var(--color-border-soft);
  font-size: 0.75rem;
}

.integration-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--color-text-secondary);
}

.status-online {
  font-weight: 700;
  color: var(--color-primary);
  font-size: 0.7rem;
}

@media (max-width: 640px) {
  .quick-grid {
    grid-template-columns: 1fr;
  }
}
</style>
