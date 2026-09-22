<script setup>
import { ref } from 'vue';

const emit = defineEmits(['filter', 'reset']);

const filters = ref({
  period: 'Hari Ini',
  polyclinic: 'Semua Poliklinik',
  doctor: 'Semua Dokter Spesialis'
});

const applyFilter = () => {
  emit('filter', filters.value);
};

const resetFilter = () => {
  filters.value = {
    period: 'Hari Ini',
    polyclinic: 'Semua Poliklinik',
    doctor: 'Semua Dokter Spesialis'
  };
  emit('reset');
};

const downloadReport = () => {
  alert('Laporan akan tersedia setelah API dashboard terhubung');
};
</script>

<template>
  <div class="dashboard-filters card">
    <div class="filters-group">
      <div class="filter-item">
        <label for="period" class="filter-label">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          Periode:
        </label>
        <select id="period" v-model="filters.period" class="filter-select">
          <option value="Hari Ini">Hari Ini</option>
          <option value="7 Hari Terakhir">7 Hari Terakhir</option>
          <option value="Bulan Ini">Bulan Ini</option>
        </select>
      </div>
      
      <div class="filter-divider"></div>
      
      <div class="filter-item">
        <label for="polyclinic" class="filter-label">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          Poli:
        </label>
        <select id="polyclinic" v-model="filters.polyclinic" class="filter-select">
          <option value="Semua Poliklinik">Semua Poliklinik (14 Poliklinik)</option>
          <option value="Poli Umum">Poli Umum</option>
          <option value="Poli Anak">Poli Anak</option>
        </select>
      </div>
      
      <div class="filter-divider"></div>
      
      <div class="filter-item">
        <label for="doctor" class="filter-label">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          Dokter:
        </label>
        <select id="doctor" v-model="filters.doctor" class="filter-select">
          <option value="Semua Dokter Spesialis">Semua Dokter Spesialis</option>
          <option value="Dr. A">Dr. A</option>
          <option value="Dr. B">Dr. B</option>
        </select>
      </div>
    </div>
    
    <div class="filters-actions">
      <button class="btn btn-ghost" @click="resetFilter">Reset Filter</button>
      <button class="btn btn-primary" @click="applyFilter">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
        Terapkan
      </button>
      <button class="btn btn-outline" @click="downloadReport">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Unduh Laporan
      </button>
    </div>
  </div>
</template>

<style scoped>
.dashboard-filters {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.filters-group {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.filter-select {
  border: none;
  background: transparent;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-primary-dark);
  cursor: pointer;
  padding: 0.2rem;
}

.filter-select:focus {
  outline: 2px solid var(--color-primary-light);
  border-radius: 4px;
}

.filter-divider {
  width: 1px;
  height: 20px;
  background: var(--color-border-soft);
}

.filters-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.5rem 0.85rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: var(--color-primary);
  color: #fff;
}

.btn-primary:hover {
  background: var(--color-primary-dark);
}

.btn-outline {
  background: #fff;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
}

.btn-outline:hover {
  background: var(--color-page-bg);
}

.btn-ghost {
  background: transparent;
  color: var(--color-text-secondary);
}

.btn-ghost:hover {
  background: var(--color-page-bg);
  color: var(--color-text-navy);
}

@media (max-width: 768px) {
  .dashboard-filters {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filters-actions {
    justify-content: flex-end;
    margin-top: 0.5rem;
  }
  
  .filter-divider {
    display: none;
  }
  
  .filter-item {
    width: 100%;
    justify-content: space-between;
    border-bottom: 1px solid var(--color-page-bg);
    padding-bottom: 0.5rem;
  }
}
</style>
