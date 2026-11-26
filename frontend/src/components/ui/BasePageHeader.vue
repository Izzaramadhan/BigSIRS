<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  /** Label breadcrumb berurutan; item terakhir = halaman aktif. */
  breadcrumbs: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <header class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <nav v-if="breadcrumbs.length" aria-label="Breadcrumb" class="mb-2">
        <ol class="flex list-none flex-wrap items-center gap-2 p-0 text-xs text-fg-muted">
          <li v-for="(crumb, index) in breadcrumbs" :key="crumb" class="flex items-center gap-2">
            <span
              :class="{ 'font-medium text-primary': index === breadcrumbs.length - 1 }"
              :aria-current="index === breadcrumbs.length - 1 ? 'page' : undefined"
            >
              {{ crumb }}
            </span>
            <span v-if="index < breadcrumbs.length - 1" aria-hidden="true">/</span>
          </li>
        </ol>
      </nav>
      <h1 class="text-2xl font-bold text-fg">{{ title }}</h1>
      <p v-if="description" class="mt-1 text-sm text-fg-muted">{{ description }}</p>
    </div>

    <div v-if="$slots.actions" class="flex items-center gap-3">
      <slot name="actions" />
    </div>
  </header>
</template>
