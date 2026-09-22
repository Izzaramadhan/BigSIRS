<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  data: {
    type: Array,
    required: true
  }
});

const searchQuery = ref('');
const statusFilter = ref('Semua Status');

const filteredData = computed(() => {
  return props.data.filter(item => {
    const matchesSearch = item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                          item.doctor.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesStatus = statusFilter.value === 'Semua Status' || item.status === statusFilter.value;
    return matchesSearch && matchesStatus;
  });
});

const getStatusClass = (status) => {
  switch(status) {
    case 'Normal': return 'status-normal';
    case 'Padat': return 'status-padat';
    case 'Lambat': return 'status-lambat';
    default: return '';
  }
};
</script>

<template>
  <div class="operation-table card">
    <div class="card-header">
      <div class="header-text">
        <h3 class="card-title">Operasional Poliklinik Hari Ini</h3>
        <p class="card-subtitle">Status realtime antrean, dokter yang bertugas, dan waktu respons pelayanan.</p>
      </div>
      
      <div class="table-filters">
        <div class="search-box">
          <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input type="text" v-model="searchQuery" placeholder="Cari poliklinik / dokter..." />
        </div>
        <div class="status-select">
          <svg class="filter-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
          <select v-model="statusFilter">
            <option value="Semua Status">Semua Status</option>
            <option value="Normal">Normal</option>
            <option value="Padat">Padat</option>
            <option value="Lambat">Lambat</option>
          </select>
        </div>
      </div>
    </div>
    
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>POLIKLINIK</th>
            <th>DOKTER BERTUGAS</th>
            <th class="text-center">TOTAL</th>
            <th class="text-center">MENUNGGU</th>
            <th class="text-center">PERIKSA</th>
            <th class="text-center">SELESAI</th>
            <th class="text-center">RATA-RATA TUNGGU</th>
            <th class="text-center">STATUS</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in filteredData" :key="item.id">
            <td class="poly-name-col">
              <span class="status-dot" :class="getStatusClass(item.status)"></span>
              {{ item.name }}
            </td>
            <td class="doctor-name">{{ item.doctor }}</td>
            <td class="text-center fw-600">{{ item.total }}</td>
            <td class="text-center fw-600 text-danger" v-if="item.waiting > 10">{{ item.waiting }}</td>
            <td class="text-center fw-600 text-primary" v-else-if="item.waiting > 0">{{ item.waiting }}</td>
            <td class="text-center" v-else>-</td>
            <td class="text-center text-warning fw-600">{{ item.examining }}</td>
            <td class="text-center fw-600 text-success">{{ item.finished }}</td>
            <td class="text-center fw-600">
              <div class="wait-time" :class="{'text-danger': item.avgWait > 30}">
                {{ item.avgWait }}<br/><span class="text-xs">Menit</span>
              </div>
            </td>
            <td class="text-center">
              <span class="status-badge" :class="getStatusClass(item.status)">
                <span class="inner-dot"></span>
                {{ item.status }}
              </span>
            </td>
          </tr>
          <tr v-if="filteredData.length === 0">
            <td colspan="8" class="empty-row">
              Tidak ada data poliklinik yang sesuai dengan pencarian Anda.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div class="card-footer">
      <span class="footer-text">Menampilkan {{ filteredData.length }} dari 14 Poliklinik Aktif</span>
      <button class="btn-link">Buka Konsol Panggilan Poliklinik &rarr;</button>
    </div>
  </div>
</template>

<style scoped>
.operation-table {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
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

.table-filters {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.search-box, .status-select {
  position: relative;
  display: flex;
  align-items: center;
  background: var(--color-page-bg);
  border-radius: 8px;
  padding: 0.4rem 0.75rem;
  border: 1px solid var(--color-border-soft);
}

.search-box input, .status-select select {
  border: none;
  background: transparent;
  padding-left: 0.5rem;
  font-size: 0.75rem;
  color: var(--color-text-navy);
}

.search-box input:focus, .status-select select:focus {
  outline: none;
}

.search-icon, .filter-icon {
  color: var(--color-text-secondary);
}

.table-responsive {
  overflow-x: auto;
  margin: 0 -1.25rem;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 800px;
}

.data-table th {
  text-align: left;
  padding: 0.75rem 1.25rem;
  font-size: 0.7rem;
  color: var(--color-text-secondary);
  font-weight: 600;
  border-bottom: 1px solid var(--color-border-soft);
  white-space: nowrap;
}

.data-table td {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--color-page-bg);
  font-size: 0.8rem;
  color: var(--color-text-navy);
  vertical-align: middle;
}

.data-table tbody tr:hover {
  background-color: #fcfcfd;
}

.poly-name-col {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.doctor-name {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  white-space: nowrap;
}

.text-center { text-align: center; }
.fw-600 { font-weight: 600; }
.text-danger { color: var(--color-danger); }
.text-primary { color: var(--color-primary); }
.text-warning { color: var(--color-warning); }
.text-success { color: #059669; }
.text-xs { font-size: 0.65rem; font-weight: normal; color: var(--color-text-secondary); }

.wait-time {
  line-height: 1.2;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.3rem 0.6rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
}

.inner-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-normal { color: var(--color-primary-dark); }
.status-normal .inner-dot { background-color: var(--color-primary); }
.status-badge.status-normal { background-color: var(--color-primary-light); }

.status-padat { color: #1d4ed8; }
.status-padat .inner-dot { background-color: #3b82f6; }
.status-badge.status-padat { background-color: #dbeafe; }

.status-lambat { color: var(--color-danger); }
.status-lambat .inner-dot { background-color: var(--color-danger); }
.status-badge.status-lambat { background-color: var(--color-danger-bg); }

.empty-row {
  text-align: center;
  color: var(--color-text-secondary);
  padding: 3rem !important;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1.25rem;
  font-size: 0.75rem;
}

.footer-text {
  color: var(--color-text-secondary);
}

.btn-link {
  background: none;
  border: none;
  color: var(--color-primary);
  font-weight: 600;
  cursor: pointer;
}

.btn-link:hover {
  text-decoration: underline;
}
</style>
