<script setup>
import { useSirsBedAvailability } from '@/composables/useSirsBedAvailability'
import SirsBedAvailabilityTable from '@/components/master-data/sirs-bed-availability/SirsBedAvailabilityTable.vue'
import SirsBedAvailabilityToolbar from '@/components/master-data/sirs-bed-availability/SirsBedAvailabilityToolbar.vue'
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue'
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue'
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue'
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue'

// Prototipe Frontend: secara default data kosong (empty array)
const {
  paginatedRows,
  loading,
  error,
  filters,
  perPage,
  pagination,
  total,
  setPage,
  setPerPage,
  setSearch,
  refresh,
} = useSirsBedAvailability()
</script>

<template>
  <div class="page-container">
    <!-- Header Halaman -->
    <MasterDataPageHeader
      title="Ketersediaan Bed SIRS"
      subtitle="Menampilkan informasi ketersediaan tempat tidur untuk kebutuhan pelaporan SIRS."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Data Bed', active: false },
        { label: 'Ketersediaan Bed SIRS', active: true },
      ]"
    />

    <div class="page-content">
      <!-- Information Banner Persiapan Integrasi -->
      <div class="info-banner" role="status">
        <div class="info-banner__icon">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
        </div>
        <div class="info-banner__text">
          Halaman ini masih dalam tahap persiapan dan belum terintegrasi dengan layanan SIRS.
        </div>
      </div>

      <!-- Main Card -->
      <div class="card-main">
        <div class="card-header">
          <h2 class="card-title">DAFTAR KETERSEDIAAN BED SIRS</h2>
        </div>

        <div class="card-body">
          <!-- Toolbar (Pilihan Entries, Pencarian, Refresh) -->
          <SirsBedAvailabilityToolbar
            :search="filters.search"
            :per-page="perPage"
            :loading="loading"
            @update:search="setSearch"
            @update:per-page="setPerPage"
            @refresh="refresh"
          />

          <!-- Error State -->
          <MasterDataErrorState v-if="error" :error="error" @retry="refresh" />

          <!-- Content: Empty State atau Data Table -->
          <template v-else>
            <!-- Kondisi Data Kosong -->
            <MasterDataEmptyState
              v-if="!loading && total === 0"
              :is-search="Boolean(filters.search)"
              title="Belum ada data ketersediaan Bed SIRS"
              message="Data akan ditampilkan setelah sumber data dan alur integrasi SIRS ditentukan."
            />

            <!-- Tabel & Pagination jika data tersedia atau saat loading -->
            <template v-else>
              <SirsBedAvailabilityTable
                :items="paginatedRows"
                :pagination="pagination"
                :loading="loading"
              />

              <MasterDataPagination
                :pagination="pagination"
                :loading="loading"
                :item-count="paginatedRows.length"
                @page-change="setPage"
              />
            </template>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-container {
  padding: 1.5rem 2rem;
  min-height: 100vh;
  background-color: var(--color-page-bg, #f8fafc);
}

.page-content {
  margin-top: 1.5rem;
}

/* Info Banner */
.info-banner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  margin-bottom: 1.25rem;
  background-color: #f0fdfa;
  border: 1px solid #ccfbf1;
  border-radius: 8px;
  color: #0f766e;
  font-size: 0.85rem;
  line-height: 1.4;
}

.info-banner__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  color: #0d9488;
}

.info-banner__icon svg {
  width: 18px;
  height: 18px;
}

.info-banner__text {
  font-weight: 500;
}

/* Card Main */
.card-main {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.05),
    0 1px 2px -1px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.card-header {
  padding: 1.125rem 1.5rem;
  background-color: #ffffff;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.card-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy, #1e293b);
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.card-body {
  padding: 1.5rem;
}

@media (max-width: 768px) {
  .page-container {
    padding: 1rem;
  }

  .card-body {
    padding: 1rem;
  }
}
</style>
