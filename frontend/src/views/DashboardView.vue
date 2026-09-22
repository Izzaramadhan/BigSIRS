<script setup>
import { ref, onMounted } from 'vue';
import { dashboardService } from '@/services/dashboard';

// Import Components
import DashboardHero from '@/components/dashboard/DashboardHero.vue';
import DashboardFilters from '@/components/dashboard/DashboardFilters.vue';
import MetricCard from '@/components/dashboard/MetricCard.vue';
import VisitTrendChart from '@/components/dashboard/VisitTrendChart.vue';
import GuarantorComposition from '@/components/dashboard/GuarantorComposition.vue';
import PolyclinicDistribution from '@/components/dashboard/PolyclinicDistribution.vue';
import QuickAccess from '@/components/dashboard/QuickAccess.vue';
import DiseaseRanking from '@/components/dashboard/DiseaseRanking.vue';
import ProcedureRanking from '@/components/dashboard/ProcedureRanking.vue';
import PolyclinicOperationTable from '@/components/dashboard/PolyclinicOperationTable.vue';
import AttentionPanel from '@/components/dashboard/AttentionPanel.vue';
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue';
import DashboardEmptyState from '@/components/dashboard/DashboardEmptyState.vue';
import DashboardErrorState from '@/components/dashboard/DashboardErrorState.vue';

const loading = ref(true);
const error = ref(null);
const dashboardData = ref(null);

const fetchData = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await dashboardService.getDashboardData();
    dashboardData.value = response.data;
  } catch (err) {
    if (err.message && err.message.includes('API dashboard belum tersedia')) {
      // Empty state for non-mock development
      dashboardData.value = null;
    } else {
      error.value = err.message || 'Gagal memuat data dashboard.';
    }
  } finally {
    loading.value = false;
  }
};

const handleRefresh = () => {
  fetchData();
};

const handleFilter = (filters) => {
  // In a real app, this would fetch new data based on filters
  console.log('Filters applied:', filters);
};

const handleResetFilter = () => {
  console.log('Filters reset');
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="dashboard-page">
    <DashboardSkeleton v-if="loading" />
    
    <DashboardErrorState v-else-if="error" :error="error" @retry="handleRefresh" />
    
    <DashboardEmptyState v-else-if="!dashboardData" />
    
    <div v-else class="dashboard-content">
      <DashboardHero 
        :last-updated="dashboardData.lastUpdated" 
        @refresh="handleRefresh" 
      />
      
      <DashboardFilters 
        @filter="handleFilter" 
        @reset="handleResetFilter" 
      />
      
      <!-- Metrics Grid -->
      <div class="metrics-grid">
        <MetricCard 
          title="Kunjungan Hari Ini" 
          :value="dashboardData.metrics.kunjunganHariIni" 
          badge-text="↑ 8.4%" 
          subtitle="Dari kemarin"
          icon="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"
          theme="primary"
        />
        <MetricCard 
          title="Pasien Baru" 
          :value="dashboardData.metrics.pasienBaru" 
          subtitle="16.9% total kunjungan"
          icon="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M23 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75"
          theme="info"
        />
        <MetricCard 
          title="Pasien Lama" 
          :value="dashboardData.metrics.pasienLama" 
          subtitle="83.1% total kunjungan"
          icon="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6"
          theme="primary"
        />
        <MetricCard 
          title="Menunggu" 
          :value="dashboardData.metrics.menunggu" 
          badge-text="Perlu segera dilayani"
          icon="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"
          theme="danger"
        />
        <MetricCard 
          title="Sedang Diperiksa" 
          :value="dashboardData.metrics.sedangDiperiksa" 
          subtitle="Aktif di poliklinik"
          icon="M22 12h-4l-3 9L9 3l-3 9H2"
          theme="warning"
        />
        <MetricCard 
          title="Pelayanan Selesai" 
          :value="dashboardData.metrics.pelayananSelesai" 
          badge-text="77.8% Terselesaikan"
          icon="M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3"
          theme="success"
        />
      </div>
      
      <!-- Charts Grid -->
      <div class="charts-grid-main">
        <VisitTrendChart class="chart-large" :data="dashboardData.visitTrend" />
        <GuarantorComposition class="chart-small" :data="dashboardData.guarantorComposition" />
      </div>
      
      <!-- Middle Grid -->
      <div class="middle-grid">
        <PolyclinicDistribution :data="dashboardData.polyclinicDistribution" />
        <QuickAccess />
      </div>
      
      <!-- Ranking Grid -->
      <div class="ranking-grid">
        <DiseaseRanking :data="dashboardData.diseaseRanking" />
        <ProcedureRanking :data="dashboardData.procedureRanking" />
      </div>
      
      <!-- Bottom Grid -->
      <div class="bottom-grid">
        <PolyclinicOperationTable class="bottom-large" :data="dashboardData.polyclinicOperations" />
        <AttentionPanel class="bottom-small" :items="dashboardData.attentionItems" />
      </div>
      
      <!-- Footer -->
      <footer class="dashboard-footer">
        <div class="footer-left">
          &copy; 2018 - {{ new Date().getFullYear() }} BigSirs SIMRS | PT Sisfomedika. Seluruh hak cipta dilindungi undang-undang.
        </div>
        <div class="footer-right">
          <span class="status-item"><span class="dot"></span> Terkoneksi SatuSehat Kemenkes</span>
          <span class="status-item">Bridging BPJS v2.1.0</span>
        </div>
      </footer>
    </div>
  </div>
</template>

<style scoped>
.dashboard-page {
  max-width: 1600px;
  margin: 0 auto;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

.charts-grid-main,
.middle-grid,
.ranking-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.charts-grid-main {
  grid-template-columns: 2fr 1fr;
}

.bottom-grid {
  display: grid;
  grid-template-columns: 3fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.dashboard-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  flex-wrap: wrap;
  gap: 1rem;
}

.footer-right {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.status-item {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.dot {
  width: 6px;
  height: 6px;
  background-color: var(--color-primary);
  border-radius: 50%;
}

@media (max-width: 1440px) {
  .metrics-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 1200px) {
  .bottom-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 1024px) {
  .charts-grid-main,
  .middle-grid,
  .ranking-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .dashboard-footer {
    flex-direction: column;
    text-align: center;
  }
}

@media (max-width: 480px) {
  .metrics-grid {
    grid-template-columns: 1fr;
  }
}
</style>
