<script setup>
import { computed } from 'vue'
import { CalendarDays, LoaderCircle } from 'lucide-vue-next'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import {
  buildScheduleRecapGroups,
  DAY_OPTIONS,
  EMPTY,
  formatScheduleTimeRange,
} from './doctor-schedule.helpers'

const props = defineProps({
  schedules: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['retry'])

const groupedData = computed(() => buildScheduleRecapGroups(props.schedules))
</script>

<template>
  <section class="overflow-hidden rounded-xl border border-line bg-surface">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
      <div>
        <h2 class="text-base font-semibold text-fg">Data Jadwal Dokter</h2>
        <p class="mt-0.5 text-xs text-fg-muted">
          Rekapitulasi jadwal praktik mingguan dokter per poliklinik.
        </p>
      </div>

      <div v-if="loading" class="inline-flex items-center gap-2 text-xs text-fg-muted">
        <LoaderCircle class="size-4 animate-spin text-primary" aria-hidden="true" />
        <span>Memuat rekap...</span>
      </div>
    </header>

    <div v-if="error" class="p-5">
      <BaseAlert tone="danger">
        {{ error }}
        <template #action>
          <BaseButton variant="secondary" size="sm" @click="emit('retry')">Coba Lagi</BaseButton>
        </template>
      </BaseAlert>
    </div>

    <div
      v-else-if="loading && groupedData.length === 0"
      class="flex items-center justify-center gap-2 px-6 py-12 text-sm text-fg-muted"
    >
      <LoaderCircle class="size-5 animate-spin text-primary" aria-hidden="true" />
      <span>Memuat data rekap jadwal dokter...</span>
    </div>

    <BaseEmptyState
      v-else-if="groupedData.length === 0"
      :icon="CalendarDays"
      title="Belum ada jadwal dokter"
      description="Data jadwal dokter yang ditambahkan akan otomatis terangkum dalam tabel rekap ini."
    />

    <div v-else class="overflow-x-auto" :class="{ 'opacity-60': loading }">
      <table class="w-full border-collapse text-sm">
        <caption class="sr-only">
          Rekapitulasi jadwal praktik mingguan dokter per poliklinik
        </caption>

        <thead class="bg-canvas">
          <tr>
            <th
              rowspan="2"
              scope="col"
              class="min-w-44 border-r border-b border-line px-4 py-3 text-left text-xs font-semibold tracking-wide text-fg-muted uppercase"
            >
              Nama Poliklinik
            </th>
            <th
              rowspan="2"
              scope="col"
              class="min-w-48 border-r border-b border-line px-4 py-3 text-left text-xs font-semibold tracking-wide text-fg-muted uppercase"
            >
              Nama Dokter
            </th>
            <th
              colspan="7"
              scope="colgroup"
              class="border-b border-line px-4 py-2 text-center text-xs font-semibold tracking-wide text-fg-muted uppercase"
            >
              Jadwal Hari
            </th>
          </tr>
          <tr>
            <th
              v-for="day in DAY_OPTIONS"
              :key="day.value"
              scope="col"
              class="min-w-28 border-b border-line px-3 py-2 text-center text-xs font-semibold tracking-wide text-fg-muted uppercase"
            >
              {{ day.label }}
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-line" :aria-busy="loading || undefined">
          <template v-for="poly in groupedData" :key="poly.id">
            <tr
              v-for="(doc, docIndex) in poly.doctors"
              :key="`${poly.id}-${doc.id}`"
              class="transition-colors hover:bg-canvas"
            >
              <td
                v-if="docIndex === 0"
                :rowspan="poly.doctors.length"
                class="border-r border-line bg-canvas/50 px-4 py-3 align-top font-semibold text-fg"
              >
                {{ poly.name }}
              </td>

              <td class="border-r border-line px-4 py-3 align-top">
                <div class="flex flex-col">
                  <span class="font-medium text-fg">{{ doc.name }}</span>
                  <span v-if="doc.specialization" class="text-xs text-fg-muted">
                    {{ doc.specialization }}
                  </span>
                </div>
              </td>

              <td
                v-for="day in DAY_OPTIONS"
                :key="day.value"
                class="px-3 py-3 text-center align-top"
              >
                <div
                  v-if="doc.days[day.value] && doc.days[day.value].length > 0"
                  class="flex flex-col items-center gap-1.5"
                >
                  <div
                    v-for="session in doc.days[day.value]"
                    :key="session.id"
                    class="inline-flex flex-wrap items-center justify-center gap-1.5 rounded-md border px-2 py-1 font-mono text-xs font-medium whitespace-nowrap"
                    :class="
                      session.is_holiday
                        ? 'border-danger/30 bg-danger-subtle text-danger-strong'
                        : 'border-primary/20 bg-primary-subtle text-primary-strong'
                    "
                  >
                    <span>{{ formatScheduleTimeRange(session.start_time, session.end_time) }}</span>
                    <span
                      v-if="session.is_holiday"
                      class="rounded bg-danger px-1.5 py-0.5 font-sans text-[10px] font-semibold text-white uppercase"
                      title="Jadwal Libur"
                    >
                      Libur
                    </span>
                  </div>
                </div>
                <span v-else class="text-fg-muted">{{ EMPTY }}</span>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </section>
</template>
