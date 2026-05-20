<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold">Pengaduan Warga</h2>
        <p class="text-sm text-gray-500">Kelola laporan dan keluhan dari warga</p>
      </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex gap-2 flex-wrap">
      <button
        v-for="tab in statusTabs"
        :key="tab.value"
        @click="activeStatus = tab.value; fetchData()"
        :class="['px-4 py-2 rounded-lg text-sm font-medium transition-colors',
          activeStatus === tab.value
            ? 'bg-primary-700 text-white'
            : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50']"
      >
        {{ tab.label }}
        <span v-if="tab.count" :class="['ml-1.5 px-1.5 py-0.5 rounded-full text-xs',
          activeStatus === tab.value ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600']">
          {{ tab.count }}
        </span>
      </button>
    </div>

    <!-- List -->
    <div v-if="isLoading" class="card p-8 text-center">
      <div class="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto" />
    </div>

    <div v-else-if="pengaduans.length === 0" class="card p-12 text-center text-gray-400">
      <ExclamationTriangleIcon class="w-12 h-12 mx-auto mb-3 opacity-30" />
      <p>Tidak ada pengaduan dengan status ini</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="item in pengaduans"
        :key="item.id"
        class="card p-5 cursor-pointer hover:shadow-md transition-shadow"
        @click="selectedPengaduan = item"
      >
        <div class="flex items-start gap-4">
          <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', kategoriColor(item.kategori)]">
            <span class="text-white text-lg">{{ kategoriEmoji(item.kategori) }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-3">
              <h4 class="font-semibold text-gray-800 leading-tight">{{ item.judul }}</h4>
              <span :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-1">
              {{ item.warga?.user?.name }} · Blok {{ item.warga?.blok }} No. {{ item.warga?.nomor_rumah }}
            </p>
            <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ item.deskripsi }}</p>
            <div class="flex items-center gap-3 mt-2">
              <span class="badge-gray text-xs">{{ kategoriLabel(item.kategori) }}</span>
              <span :class="['badge text-xs', prioritasClass(item.prioritas)]">{{ item.prioritas }}</span>
              <span class="text-xs text-gray-400">{{ formatDate(item.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Foto Preview Lightbox -->
    <div
      v-if="previewFoto"
      @click="previewFoto = null"
      class="fixed inset-0 bg-black/85 z-[60] flex items-center justify-center p-4 cursor-zoom-out"
    >
      <img :src="previewFoto" class="max-w-full max-h-full rounded-lg" @click.stop />
      <button
        @click="previewFoto = null"
        class="absolute top-4 right-4 bg-white/20 hover:bg-white/40 text-white rounded-full p-2"
      >✕</button>
    </div>

    <!-- Detail Modal -->
    <div v-if="selectedPengaduan" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl my-4">
        <div class="p-6 border-b">
          <div class="flex items-start justify-between">
            <h3 class="font-bold text-lg">{{ selectedPengaduan.judul }}</h3>
            <button @click="selectedPengaduan = null" class="text-gray-400 hover:text-gray-600 p-1">✕</button>
          </div>
          <div class="flex gap-2 mt-2">
            <span :class="statusClass(selectedPengaduan.status)">{{ statusLabel(selectedPengaduan.status) }}</span>
            <span class="badge-gray">{{ kategoriLabel(selectedPengaduan.kategori) }}</span>
          </div>
        </div>

        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
          <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ selectedPengaduan.deskripsi }}</p>
          </div>

          <!-- Foto pengaduan -->
          <div v-if="getFotos(selectedPengaduan).length > 0">
            <p class="label">Foto Pendukung ({{ getFotos(selectedPengaduan).length }})</p>
            <div class="grid grid-cols-3 gap-3 mt-2">
              <div
                v-for="(foto, idx) in getFotos(selectedPengaduan)"
                :key="idx"
                @click="previewFoto = foto"
                class="aspect-square rounded-lg overflow-hidden border border-gray-200 cursor-pointer hover:ring-2 hover:ring-primary-500 hover:ring-offset-1 transition-all"
              >
                <img :src="foto" :alt="`Foto ${idx + 1}`" class="w-full h-full object-cover" />
              </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Klik foto untuk preview lebih besar</p>
          </div>

          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-gray-400">Warga</p>
              <p class="font-medium">{{ selectedPengaduan.warga?.user?.name }}</p>
            </div>
            <div>
              <p class="text-gray-400">Alamat</p>
              <p class="font-medium">Blok {{ selectedPengaduan.warga?.blok }} No. {{ selectedPengaduan.warga?.nomor_rumah }}</p>
            </div>
            <div>
              <p class="text-gray-400">Tanggal Lapor</p>
              <p class="font-medium">{{ formatDate(selectedPengaduan.created_at) }}</p>
            </div>
            <div v-if="selectedPengaduan.warga?.user?.phone">
              <p class="text-gray-400">Nomor HP</p>
              <p class="font-medium">{{ selectedPengaduan.warga.user.phone }}</p>
            </div>
          </div>

          <!-- Update Status Form -->
          <div class="border-t pt-4 space-y-3">
            <h4 class="font-semibold">Update Status Pengaduan</h4>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label">Status</label>
                <select v-model="updateForm.status" class="input">
                  <option value="baru">Baru</option>
                  <option value="diproses">Diproses</option>
                  <option value="selesai">Selesai</option>
                  <option value="ditolak">Ditolak</option>
                </select>
              </div>
              <div>
                <label class="label">Prioritas</label>
                <select v-model="updateForm.prioritas" class="input">
                  <option value="rendah">Rendah</option>
                  <option value="sedang">Sedang</option>
                  <option value="tinggi">Tinggi</option>
                </select>
              </div>
            </div>
            <div>
              <label class="label">Keterangan Admin</label>
              <textarea v-model="updateForm.keterangan_admin" class="input" rows="3"
                placeholder="Tambahkan keterangan atau tanggapan untuk warga..." />
            </div>
            <div class="flex gap-3 justify-between items-center">
              <button
                @click="hapusPengaduan(selectedPengaduan)"
                class="px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg border border-red-200 flex items-center gap-1.5"
                :disabled="deleting"
                title="Hapus pengaduan + foto terkait"
              >
                <span v-if="deleting" class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin" />
                <TrashIcon v-else class="w-4 h-4" />
                Hapus
              </button>
              <div class="flex gap-3">
                <button @click="selectedPengaduan = null" class="btn-secondary">Tutup</button>
                <button @click="updateStatus" class="btn-primary" :disabled="updating">
                  <span v-if="updating" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  Perbarui Status
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { ExclamationTriangleIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import dayjs from 'dayjs'

const toast = useToast()
const pengaduans = ref([])
const isLoading = ref(false)
const activeStatus = ref('')
const selectedPengaduan = ref(null)
const previewFoto = ref(null)
const updating = ref(false)
const deleting = ref(false)

async function hapusPengaduan(p) {
  if (!p) return
  if (!confirm(`Hapus pengaduan "${p.judul}"?\n\nFoto terkait juga akan dihapus dari server. Aksi ini tidak bisa dibatalkan.`)) return
  deleting.value = true
  try {
    await api.delete(`/pengaduan/${p.id}`)
    toast.success('Pengaduan berhasil dihapus.')
    selectedPengaduan.value = null
    await fetchData()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Gagal hapus pengaduan.')
  } finally {
    deleting.value = false
  }
}

function getFotos(p) {
  if (!p) return []
  // Backend mengirim foto_urls (full URL) atau foto (path saja)
  const urls = p.foto_urls ?? p.foto ?? []
  if (!Array.isArray(urls)) return []
  return urls.filter(Boolean).map((url) => {
    if (!url) return null
    if (url.startsWith('http')) return url
    // Prepend backend host untuk path relatif
    const apiBase = import.meta.env.VITE_API_URL || ''
    const host = apiBase.replace(/\/api\/v\d+\/?$/, '')
    return `${host}${url}`
  }).filter(Boolean)
}

const updateForm = ref({ status: 'diproses', prioritas: 'sedang', keterangan_admin: '' })

const statusTabs = [
  { label: 'Semua', value: '' },
  { label: 'Baru', value: 'baru' },
  { label: 'Diproses', value: 'diproses' },
  { label: 'Selesai', value: 'selesai' },
  { label: 'Ditolak', value: 'ditolak' },
]

watch(selectedPengaduan, (val) => {
  if (val) {
    updateForm.value = {
      status: val.status,
      prioritas: val.prioritas,
      keterangan_admin: val.keterangan_admin ?? '',
    }
  }
})

async function fetchData() {
  isLoading.value = true
  try {
    const res = await api.get('/pengaduan', { params: activeStatus.value ? { status: activeStatus.value } : {} })
    pengaduans.value = res.data.data
  } finally {
    isLoading.value = false
  }
}

async function updateStatus() {
  updating.value = true
  try {
    await api.put(`/pengaduan/${selectedPengaduan.value.id}`, updateForm.value)
    toast.success('Status pengaduan diperbarui!')
    await fetchData()
    selectedPengaduan.value = null
  } catch {
    toast.error('Gagal memperbarui status.')
  } finally {
    updating.value = false
  }
}

const formatDate = (d) => dayjs(d).format('DD MMM YYYY HH:mm')
const statusClass = (s) => ({ baru: 'badge-yellow', diproses: 'badge-blue', selesai: 'badge-green', ditolak: 'badge-red' }[s] ?? 'badge-gray')
const statusLabel = (s) => ({ baru: 'Baru', diproses: 'Diproses', selesai: 'Selesai', ditolak: 'Ditolak' }[s] ?? s)
const kategoriLabel = (k) => ({ infrastruktur: 'Infrastruktur', kebersihan: 'Kebersihan', keamanan: 'Keamanan', fasilitas: 'Fasilitas', sosial: 'Sosial', lainnya: 'Lainnya' }[k] ?? k)
const kategoriEmoji = (k) => ({ infrastruktur: '🏗', kebersihan: '🧹', keamanan: '🔒', fasilitas: '🏊', sosial: '👥', lainnya: '📋' }[k] ?? '📋')
const kategoriColor = (k) => ({ infrastruktur: 'bg-orange-500', kebersihan: 'bg-green-500', keamanan: 'bg-red-500', fasilitas: 'bg-blue-500', sosial: 'bg-purple-500', lainnya: 'bg-gray-500' }[k] ?? 'bg-gray-500')
const prioritasClass = (p) => ({ rendah: 'bg-gray-100 text-gray-600', sedang: 'bg-yellow-100 text-yellow-700', tinggi: 'bg-red-100 text-red-700' }[p] ?? '')

// Auto-polling: refresh tiap 20 detik kalau modal detail TIDAK terbuka
// (supaya tidak interrupt admin yang lagi update status)
let pollInterval = null
function startPolling() {
  if (pollInterval) return
  pollInterval = setInterval(() => {
    if (!selectedPengaduan.value && document.visibilityState === 'visible') {
      fetchData()
    }
  }, 20000) // 20 detik
}
function stopPolling() {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

onMounted(() => {
  fetchData()
  startPolling()
})
onUnmounted(stopPolling)
</script>
