<script setup>
/*
 * Tabel data generik: header, skeleton loading, baris kosong, dan hover.
 *
 * columns: [{ key, label, align?: 'left'|'center'|'right', class?: string }]
 * Isi sel kustom: <template #cell-<key>="{ row, index, value }">
 * Baris kosong:   <template #empty>
 */
defineProps({
  columns: {
    type: Array,
    required: true,
  },
  rows: {
    type: Array,
    required: true,
  },
  rowKey: {
    type: String,
    default: 'id',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  /** Judul tabel untuk pembaca layar (tidak tampil). */
  caption: {
    type: String,
    required: true,
  },
  skeletonRows: {
    type: Number,
    default: 5,
  },
})

const ALIGN = { left: 'text-left', center: 'text-center', right: 'text-right' }
const alignClass = (column) => ALIGN[column.align ?? 'left']
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-line bg-surface">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-sm">
        <caption class="sr-only">
          {{
            caption
          }}
        </caption>

        <thead class="bg-canvas">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              scope="col"
              class="border-b border-line px-5 py-3 text-xs font-semibold tracking-wide whitespace-nowrap text-fg-muted uppercase"
              :class="[alignClass(column), column.class]"
            >
              {{ column.label }}
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-line" :aria-busy="loading || undefined">
          <template v-if="loading">
            <tr v-for="n in skeletonRows" :key="`skeleton-${n}`">
              <td v-for="column in columns" :key="column.key" class="px-5 py-4">
                <div class="h-4 animate-pulse rounded bg-line" />
              </td>
            </tr>
          </template>

          <tr v-else-if="rows.length === 0">
            <td :colspan="columns.length">
              <slot name="empty" />
            </td>
          </tr>

          <template v-else>
            <tr
              v-for="(row, index) in rows"
              :key="row[rowKey]"
              class="transition-colors hover:bg-canvas"
            >
              <td
                v-for="column in columns"
                :key="column.key"
                class="px-5 py-4 text-fg"
                :class="[alignClass(column), column.class]"
              >
                <slot
                  :name="`cell-${column.key}`"
                  :row="row"
                  :index="index"
                  :value="row[column.key]"
                >
                  {{ row[column.key] ?? '—' }}
                </slot>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</template>
