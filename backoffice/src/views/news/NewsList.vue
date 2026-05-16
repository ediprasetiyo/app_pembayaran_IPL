<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Berita & Pengumuman</h2>
        <p class="text-sm text-gray-500">Kelola berita yang ditampilkan di mobile app warga</p>
      </div>
      <RouterLink to="/news/baru" class="btn-primary">
        <PlusIcon class="w-4 h-4" /> Buat Berita
      </RouterLink>
    </div>

    <!-- Filter -->
    <div class="card p-4 flex flex-wrap items-center gap-3">
      <div class="flex-1 relative min-w-[200px]">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input v-model="search" @input="debouncedLoad" type="text" placeholder="Cari judul berita..." class="input pl-9" />
      </div>
      <select v-model="kategori" @change="loadNews" class="input w-44">
        <option value="">Semua Kategori</option>
        <option value="pengumuman">📢 Pengumuman</option>
        <option value="kegiatan">🎉 Kegiatan</option>
        <option value="informasi">ℹ️ Informasi</option>
        <option value="darurat">⚠️ Darurat</option>
      </select>
    </div>

    <!-- Cards -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="card p-4">
        <div class="h-32 bg-gray-100 rounded animate-pulse mb-3" />
        <div class="h-4 bg-gray-100 rounded animate-pulse mb-2" />
        <div class="h-3 bg-gray-100 rounded w-2/3 animate-pulse" />
      </div>
    </div>

    <div v-else-if="news.length === 0" class="card p-12 text-center">
      <MegaphoneIcon class="w-16 h-16 mx-auto text-gray-300 mb-3" />
      <p class="text-gray-500">Belum ada berita. Klik "Buat Berita" untuk menambahkan.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="n in news" :key="n.id" class="card overflow-hidden hover:shadow-md transition-shadow">
        <!-- Gambar -->
        <div class="h-40 bg-gradient-to-br from-primary-100 to-primary-50 relative overflow-hidden">
          <img v-if="n.gambar" :src="getImageUrl(n.gambar)" :alt="n.judul" class="w-full h-full object-cover" />
          <div v-else class="w-full h-full flex items-center justify-center">
            <span class="text-5xl">{{ kategoriEmoji(n.kategori) }}</span>
          </div>
          <div class="absolute top-2 left-2 flex gap-1">
            <span :class="kategoriClass(n.kategori)" class="text-xs">
              {{ kategoriLabel(n.kategori) }}
            </span>
            <span v-if="n.is_pinned" class="badge bg-yellow-100 text-yellow-800 text-xs">📌 Pinned</span>
          </div>
          <div v-if="!n.is_published" class="absolute top-2 right-2">
            <span class="badge bg-gray-200 text-gray-700 text-xs">Draft</span>
          </div>
        </div>

        <!-- Content -->
        <div class="p-4 space-y-2">
          <h3 class="font-semibold text-gray-800 line-clamp-2">{{ n.judul }}</h3>
          <p class="text-sm text-gray-500 line-clamp-2">{{ n.ringkasan || stripHtml(n.konten) }}</p>
          <div class="flex items-center justify-between pt-2 border-t border-gray-100 text-xs text-gray-400">
            <span>{{ formatDate(n.published_at || n.created_at) }}</span>
            <span>oleh {{ n.pembuat?.name ?? 'Admin' }}</span>
          </div>
          <div class="flex gap-2 pt-2">
            <RouterLink :to="`/news/${n.id}/edit`" class="btn-secondary btn-sm flex-1 justify-center">
              <PencilIcon class="w-3.5 h-3.5" /> Edit
            </RouterLink>
            <button @click="hapus(n)" class="btn-sm bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 rounded-lg px-3">
              <TrashIcon class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon, MegaphoneIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import dayjs from 'dayjs'
import 'dayjs/locale/id'
import relativeTime from 'dayjs/plugin/relativeTime'
dayjs.extend(relativeTime)
dayjs.locale('id')

const toast = useToast()
const news = ref([])
const loading = ref(false)
const search = ref('')
const kategori = ref('')
let timer = null

function debouncedLoad() {
  clearTimeout(timer)
  timer = setTimeout(loadNews, 400)
}

async function loadNews() {
  loading.value = true
  try {
    const res = await api.get('/news', { params: { search: search.value, kategori: kategori.value } })
    news.value = res.data.data ?? res.data
  } catch {
    toast.error('Gagal memuat berita.')
  } finally {
    loading.value = false
  }
}

async function hapus(n) {
  if (!confirm(`Hapus berita "${n.judul}"?`)) return
  try {
    await api.delete(`/news/${n.id}`)
    toast.success('Berita dihapus.')
    loadNews()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Gagal hapus.')
  }
}

function getImageUrl(path) {
  if (!path) return null
  if (path.startsWith('http')) return path
  // Ambil base URL dari env atau fallback ke localhost dev
  const apiBase = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
  // Strip /api/v1 dari URL untuk mendapatkan host saja
  const host = apiBase.replace(/\/api\/v\d+\/?$/, '')
  return `${host}${path}`
}

function stripHtml(html) {
  if (!html) return ''
  // Strip HTML tags & decode entities
  const tmp = document.createElement('div')
  tmp.innerHTML = html
  return (tmp.textContent || tmp.innerText || '').slice(0, 120)
}

function formatDate(d) {
  return d ? dayjs(d).fromNow() : '-'
}

function kategoriLabel(k) {
  return { pengumuman: 'Pengumuman', kegiatan: 'Kegiatan', informasi: 'Informasi', darurat: 'Darurat' }[k] ?? k
}

function kategoriEmoji(k) {
  return { pengumuman: '📢', kegiatan: '🎉', informasi: 'ℹ️', darurat: '⚠️' }[k] ?? '📰'
}

function kategoriClass(k) {
  const m = {
    pengumuman: 'badge bg-blue-100 text-blue-800',
    kegiatan: 'badge bg-green-100 text-green-800',
    informasi: 'badge bg-gray-100 text-gray-800',
    darurat: 'badge bg-red-100 text-red-800',
  }
  return m[k] ?? 'badge bg-gray-100 text-gray-800'
}

onMounted(loadNews)
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
