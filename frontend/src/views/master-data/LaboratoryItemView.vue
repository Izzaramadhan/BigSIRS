<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import LaboratoryItemFormModal from '@/components/master-data/laboratory-items/LaboratoryItemFormModal.vue'
import { useLaboratoryItems } from '@/composables/useLaboratoryItems'
import { laboratoryItemsService } from '@/services/master-data/laboratoryItems.service'

const { items, meta, loading, error, fetchItems } = useLaboratoryItems()
const search = ref('')
const page = ref(1)
const perPage = ref(10)
const formOpen = ref(false)
const selectedItem = ref(null)
const deletingItem = ref(null)
const deleting = ref(false)
const notification = ref('')

const hasSearch = computed(() => search.value.trim().length > 0)
const emptyMessage = computed(() => (hasSearch.value ? 'Item lab tidak ditemukan.' : 'Data item lab belum tersedia.'))

const load = async () => {
  try {
    await fetchItems({ page: page.value, per_page: perPage.value, search: search.value.trim() })
  } catch {
    // Error state is managed by the composable.
  }
}

const openCreate = () => {
  selectedItem.value = null
  formOpen.value = true
}

const openEdit = (item) => {
  selectedItem.value = { ...item }
  formOpen.value = true
}

const handleSaved = async () => {
  formOpen.value = false
  notification.value = selectedItem.value ? 'Item lab berhasil diperbarui.' : 'Item lab berhasil ditambahkan.'
  await load()
  window.setTimeout(() => (notification.value = ''), 3000)
}

const confirmDelete = async () => {
  if (!deletingItem.value || deleting.value) return
  deleting.value = true
  try {
    await laboratoryItemsService.remove(deletingItem.value.id)
    if (items.value.length === 1 && page.value > 1) page.value -= 1
    deletingItem.value = null
    notification.value = 'Item lab berhasil diarsipkan.'
    await load()
    window.setTimeout(() => (notification.value = ''), 3000)
  } finally {
    deleting.value = false
  }
}

const goToPage = (target) => {
  if (target < 1 || target > meta.value.last_page || target === page.value) return
  page.value = target
  load()
}

let searchTimer
watch(search, () => {
  window.clearTimeout(searchTimer)
  searchTimer = window.setTimeout(() => {
    page.value = 1
    load()
  }, 300)
})

watch(perPage, () => {
  page.value = 1
  load()
})

onMounted(load)
</script>

<template>
  <main class="laboratory-items-page">
    <div v-if="notification" class="toast" role="status">{{ notification }}</div>

    <header class="hero">
      <div>
        <nav class="breadcrumbs" aria-label="Breadcrumb">Dashboard <span>/</span> Data Lab <span>/</span> Item Lab</nav>
        <p class="eyebrow">Parameter Pemeriksaan</p>
        <h1>Item Lab</h1>
        <p class="subtitle">Kelola nama parameter, nilai rujukan, dan satuan pemeriksaan laboratorium.</p>
      </div>
      <button type="button" class="button primary" @click="openCreate"><span>+</span> Tambah Item Lab</button>
    </header>

    <section class="content-card">
      <div class="toolbar">
        <label class="search-box">
          <span aria-hidden="true">&#128269;</span>
          <input v-model="search" type="search" placeholder="Cari nama, nilai rujukan, atau satuan..." aria-label="Cari item lab" />
        </label>
        <label class="page-size">
          <span>Tampilkan</span>
          <select v-model="perPage">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
          <span>data</span>
        </label>
      </div>

      <div v-if="error" class="state error-state">
        <strong>Gagal memuat data item lab.</strong>
        <button type="button" class="button secondary" @click="load">Coba Lagi</button>
      </div>

      <div v-else class="table-scroll">
        <table>
          <thead>
            <tr>
              <th class="number">No.</th>
              <th>Nama Item Lab</th>
              <th>Standar Normal / Nilai Rujukan</th>
              <th>Satuan</th>
              <th class="actions">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="state">Memuat data item lab...</td>
            </tr>
            <tr v-else-if="items.length === 0">
              <td colspan="5" class="state">{{ emptyMessage }}</td>
            </tr>
            <tr v-for="(item, index) in items" v-else :key="item.id">
              <td class="number muted">{{ (page - 1) * perPage + index + 1 }}</td>
              <td class="item-name">{{ item.name }}</td>
              <td class="reference-value">{{ item.reference_value || '-' }}</td>
              <td>{{ item.unit || '-' }}</td>
              <td class="actions">
                <div class="action-group">
                  <button type="button" class="action edit" :aria-label="`Edit ${item.name}`" @click="openEdit(item)">Edit</button>
                  <button type="button" class="action delete" :aria-label="`Hapus ${item.name}`" @click="deletingItem = item">Hapus</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <footer v-if="!error && meta.total > 0" class="pagination">
        <p>Menampilkan {{ (page - 1) * perPage + 1 }}-{{ Math.min(page * perPage, meta.total) }} dari {{ meta.total }} data</p>
        <div>
          <button type="button" :disabled="page <= 1" @click="goToPage(page - 1)">Sebelumnya</button>
          <span>{{ page }} / {{ meta.last_page }}</span>
          <button type="button" :disabled="page >= meta.last_page" @click="goToPage(page + 1)">Berikutnya</button>
        </div>
      </footer>
    </section>

    <LaboratoryItemFormModal :is-open="formOpen" :item="selectedItem" @close="formOpen = false" @saved="handleSaved" />

    <Teleport to="body">
      <div v-if="deletingItem" class="dialog-backdrop" @mousedown.self="deletingItem = null">
        <section class="delete-dialog" role="alertdialog" aria-modal="true">
          <div class="warning-icon">!</div>
          <h2>Hapus Item Lab?</h2>
          <p><strong>{{ deletingItem.name }}</strong> akan diarsipkan dari daftar item lab.</p>
          <div>
            <button type="button" class="button secondary" :disabled="deleting" @click="deletingItem = null">Batal</button>
            <button type="button" class="button danger" :disabled="deleting" @click="confirmDelete">{{ deleting ? 'Menghapus...' : 'Hapus' }}</button>
          </div>
        </section>
      </div>
    </Teleport>
  </main>
</template>

<style scoped>
.laboratory-items-page { min-height: 100%; padding: 28px; color: #24465b; background: radial-gradient(circle at 92% 4%, rgba(15,139,141,.13), transparent 28%), linear-gradient(145deg, #f4f8fa 0%, #edf4f5 100%); }
.hero { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; max-width: 1400px; margin: 0 auto 22px; }
.breadcrumbs { margin-bottom: 24px; color: #78909e; font-size: .8rem; }.breadcrumbs span { margin: 0 8px; color: #b1c0c9; }
.eyebrow { margin: 0 0 4px; color: #0f8b8d; font-size: .72rem; font-weight: 900; letter-spacing: .15em; text-transform: uppercase; }
h1 { margin: 0; color: #10364e; font-size: clamp(2rem, 4vw, 3rem); letter-spacing: -.04em; }.subtitle { margin: 8px 0 0; color: #637b89; }
.button { border: 0; border-radius: 10px; padding: 11px 17px; font-weight: 800; cursor: pointer; }.button:disabled { opacity: .55; cursor: not-allowed; }.primary { background: #0f8b8d; color: #fff; box-shadow: 0 10px 24px rgba(15,139,141,.24); }.primary span { margin-right: 5px; font-size: 1.15rem; }.secondary { border: 1px solid #c9d7df; background: #fff; color: #38586b; }.danger { background: #c84242; color: #fff; }
.content-card { max-width: 1400px; margin: auto; overflow: hidden; border: 1px solid rgba(190,207,217,.7); border-radius: 18px; background: rgba(255,255,255,.94); box-shadow: 0 18px 50px rgba(26,58,77,.09); }
.toolbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 20px 22px; border-bottom: 1px solid #e7eef2; }.search-box { display: flex; align-items: center; gap: 10px; width: min(560px, 100%); border: 1px solid #cad7df; border-radius: 11px; padding: 0 13px; background: #f9fbfc; }.search-box input { width: 100%; border: 0; padding: 12px 0; background: transparent; outline: none; color: #17384e; }.page-size { display: flex; align-items: center; gap: 8px; color: #657e8c; font-size: .85rem; }.page-size select { border: 1px solid #cad7df; border-radius: 8px; padding: 8px; background: #fff; }
.table-scroll { overflow-x: auto; }table { width: 100%; border-collapse: collapse; min-width: 790px; }th { padding: 14px 18px; background: #113d56; color: #fff; font-size: .76rem; letter-spacing: .04em; text-align: left; text-transform: uppercase; }td { padding: 16px 18px; border-bottom: 1px solid #e8eef2; vertical-align: top; }tbody tr:hover { background: #f7fbfb; }.number { width: 64px; text-align: center; }.actions { width: 160px; text-align: center; }.item-name { min-width: 260px; color: #123d56; font-weight: 800; }.reference-value { min-width: 240px; white-space: pre-line; }.muted { color: #8195a1; }
.action-group { display: flex; justify-content: center; gap: 7px; }.action { border: 1px solid transparent; border-radius: 8px; padding: 7px 10px; background: transparent; font-weight: 800; cursor: pointer; }.edit { border-color: #a9d8d8; color: #087679; }.delete { border-color: #f0baba; color: #bd3e3e; }
.state { padding: 55px 20px !important; color: #6b8391; text-align: center; }.error-state { display: grid; justify-items: center; gap: 14px; }.pagination { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 17px 22px; color: #6b8290; font-size: .85rem; }.pagination p { margin: 0; }.pagination div { display: flex; align-items: center; gap: 10px; }.pagination button { border: 1px solid #cad7df; border-radius: 8px; padding: 8px 11px; background: #fff; color: #31566a; cursor: pointer; }.pagination button:disabled { opacity: .45; cursor: not-allowed; }
.toast { position: fixed; top: 22px; right: 22px; z-index: 1200; border-radius: 10px; padding: 13px 17px; background: #123d56; color: #fff; box-shadow: 0 12px 30px rgba(14,45,64,.24); }
.dialog-backdrop { position: fixed; inset: 0; z-index: 1150; display: grid; place-items: center; padding: 20px; background: rgba(9,28,47,.55); }.delete-dialog { width: min(430px,100%); border-radius: 18px; padding: 28px; background: #fff; text-align: center; box-shadow: 0 28px 80px rgba(8,35,58,.28); }.delete-dialog h2 { margin: 13px 0 8px; color: #153c53; }.delete-dialog p { color: #657e8c; }.delete-dialog > div:last-child { display: flex; justify-content: center; gap: 10px; margin-top: 22px; }.warning-icon { display: grid; place-items: center; width: 48px; height: 48px; margin: auto; border-radius: 50%; background: #fff0e8; color: #d65a30; font-size: 1.5rem; font-weight: 900; }
@media (max-width: 720px) { .laboratory-items-page { padding: 18px 12px; }.hero { align-items: stretch; flex-direction: column; }.hero .primary { width: 100%; }.toolbar, .pagination { align-items: stretch; flex-direction: column; }.search-box { width: auto; }.page-size { justify-content: flex-end; }.pagination div { justify-content: space-between; } }
</style>
