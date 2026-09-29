<script setup>
import { onMounted } from 'vue';
import { useReportGroups } from '@/composables/useReportGroups';

const {
  reportGroups,
  loading,
  error,
  fetchReportGroups
} = useReportGroups();

onMounted(() => {
  fetchReportGroups({ per_page: 100 });
});
</script>

<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">Kelompok Laporan</h1>
        <p class="page-subtitle">Master Data Kelompok Laporan (Read-only sementara)</p>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">
      {{ error }}
    </div>

    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Deskripsi</th>
            <th>Tipe</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="text-center py-4 text-gray-500">Memuat data...</td>
          </tr>
          <tr v-else-if="!reportGroups || reportGroups.length === 0">
            <td colspan="5" class="text-center py-4 text-gray-500">Tidak ada data.</td>
          </tr>
          <tr v-else v-for="(item, index) in reportGroups" :key="item.id">
            <td>{{ index + 1 }}</td>
            <td>{{ item.name }}</td>
            <td>{{ item.description || '-' }}</td>
            <td>{{ item.type || '-' }}</td>
            <td>
              <span :class="['badge', item.is_active ? 'badge-active' : 'badge-inactive']">
                {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-navy);
  margin: 0 0 0.25rem 0;
}

.page-subtitle {
  color: var(--color-text-secondary);
  margin: 0;
  font-size: 0.9rem;
}

.alert {
  padding: 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
}

.alert-danger {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #f87171;
}

.table-container {
  background: #fff;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.data-table th, .data-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border-soft);
}

.data-table th {
  background: #f8fafc;
  font-weight: 600;
  color: var(--color-text-navy);
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}
.badge-active {
  background: #dcfce7;
  color: #166534;
}
.badge-inactive {
  background: #fee2e2;
  color: #991b1b;
}
.text-center { text-align: center; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.text-gray-500 { color: #64748b; }
</style>
