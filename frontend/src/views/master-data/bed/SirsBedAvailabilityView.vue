<script setup>
import { computed } from 'vue'
import { BedDouble, CircleAlert, SearchX } from 'lucide-vue-next'
import SirsBedAvailabilityTable from '@/components/master-data/sirs-bed-availability/SirsBedAvailabilityTable.vue'
import SirsBedAvailabilityToolbar from '@/components/master-data/sirs-bed-availability/SirsBedAvailabilityToolbar.vue'
import { getSafeSirsErrorMessage } from '@/components/master-data/sirs-bed-availability/sirs-bed-availability.helpers'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useSirsBedAvailability } from '@/composables/useSirsBedAvailability'

// Prototipe Frontend: secara default data kosong (empty array) tanpa pemanggilan API palsu.
const {
  paginatedRows,
  loading,
  error,
  filters,
  perPage,
  pagination,
  setPage,
  setPerPage,
  setSearch,
  refresh,
} = useSirsBedAvailability()

const safeError = computed(() =>
  error.value
    ? getSafeSirsErrorMessage(error.value, 'Data ketersediaan Bed SIRS gagal dimuat.')
    : '',
)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Ketersediaan Bed SIRS"
      description="Menampilkan informasi ketersediaan tempat tidur untuk kebutuhan pelaporan SIRS."
      :breadcrumbs="['Dashboard', 'Master Data', 'Data Bed', 'Ketersediaan Bed SIRS']"
    />

    <BaseAlert tone="info">
      Halaman ini masih dalam tahap persiapan dan belum terintegrasi dengan layanan SIRS.
    </BaseAlert>

    <section aria-labelledby="sirs-bed-section-title" class="flex flex-col gap-4">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h2
          id="sirs-bed-section-title"
          class="text-xs font-semibold tracking-wider text-fg-muted uppercase"
        >
          Daftar Ketersediaan Bed SIRS
        </h2>
      </div>

      <SirsBedAvailabilityToolbar
        :search="filters.search"
        :per-page="perPage"
        :loading="loading"
        @update:search="setSearch"
        @update:per-page="setPerPage"
        @refresh="refresh"
      />

      <div v-if="safeError" class="rounded-xl border border-line bg-surface">
        <BaseEmptyState
          :icon="CircleAlert"
          tone="danger"
          title="Data ketersediaan Bed SIRS gagal dimuat"
          :description="safeError"
        >
          <template #action>
            <BaseButton variant="secondary" @click="refresh">Coba lagi</BaseButton>
          </template>
        </BaseEmptyState>
      </div>

      <template v-else>
        <SirsBedAvailabilityTable
          :items="paginatedRows"
          :pagination="pagination"
          :loading="loading"
        >
          <template #empty>
            <BaseEmptyState
              v-if="filters.search"
              :icon="SearchX"
              title="Data ketersediaan tidak ditemukan"
              :description="`Tidak ada data kelas atau ruang yang cocok dengan “${filters.search}”.`"
            />
            <BaseEmptyState
              v-else
              :icon="BedDouble"
              title="Belum ada data ketersediaan Bed SIRS"
              description="Data akan ditampilkan setelah sumber data dan alur integrasi SIRS ditentukan."
            />
          </template>
        </SirsBedAvailabilityTable>

        <BasePagination
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :total="pagination.total"
          :from="pagination.from"
          :to="pagination.to"
          :disabled="loading"
          @change="setPage"
        />
      </template>
    </section>
  </div>
</template>
