<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-lg font-semibold">Tagihan IPL</h2>
        <p class="text-sm text-gray-500">Kelola tagihan IPL bulanan seluruh warga</p>
      </div>
      <div class="flex gap-2">
        <button @click="exportExcel" class="btn-secondary" :disabled="exporting">
          <span v-if="exporting" class="w-4 h-4 border-2 border-gray-600 border-t-transparent rounded-full animate-spin" />
          <ArrowDownTrayIcon v-else class="w-4 h-4" />
          Export Excel
        </button>
        <button @click="generateTagihan" class="btn-primary" :disabled="generating">
          <span v-if="generating" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
          <DocumentPlusIcon v-else class="w-4 h-4" />
          Generate Tagihan Bulan Ini
        </button>
      </div>
    </div>

    <!-- Tab Jenis Tagihan -->
    <div class="flex gap-2 flex-wrap">
      <button
        v-for="tab in jenisTabs"
        :key="tab.value"
        @click="activeJenis = tab.value; fetchData()"
        :class="['px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2',
          activeJenis === tab.value
            ? 'bg-primary-700 text-white shadow-sm'
            : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50']"
      >
        <span>{{ tab.icon }}</span>
        {{ tab.label }}
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4 grid grid-cols-2 md:grid-cols-4 gap-3">
      <div>
        <label class="label">Bulan</label>
        <select v-model="filters.bulan" class="input" @change="fetchData">
          <option value="">Semua Bulan</option>
          <option v-for="(name, num) in bulanOptions" :key="num" :value="num">{{ name }}</option>
        </select>
      </div>
      <div>
        <label class="label">Tahun</label>
        <select v-model="filters.tahun" class="input" @change="fetchData">
          <option v-for="y in tahunOptions" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
      <div>
        <label class="label">Status</label>
        <select v-model="filters.status" class="input" @change="fetchData">
          <option value="">Semua</option>
          <option value="belum_bayar">Belum Bayar</option>
          <option value="sudah_bayar">Lunas</option>
          <option value="terlambat">Terlambat</option>
        </select>
      </div>
      <div class="flex items-end">
        <button @click="resetFilters" class="btn-secondary w-full justify-center">Reset</button>
      </div>
    </div>

    <!-- Summary chips -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <div class="card p-4 flex items-center gap-3 border-l-4 border-green-500">
        <span class="text-2xl">✅</span>
        <div>
          <p class="text-xs text-gray-500">Lunas</p>
          <p class="font-bold text-xl text-green-700">{{ summary.lunas }}</p>
        </div>
      </div>
      <div class="card p-4 flex items-center gap-3 border-l-4 border-yellow-500">
        <span class="text-2xl">⏳</span>
        <div>
          <p class="text-xs text-gray-500">Belum Bayar</p>
          <p class="font-bold text-xl text-yellow-700">{{ summary.belumBayar }}</p>
        </div>
      </div>
      <div class="card p-4 flex items-center gap-3 border-l-4 border-red-500">
        <span class="text-2xl">⚠️</span>
        <div>
          <p class="text-xs text-gray-500">Terlambat</p>
          <p class="font-bold text-xl text-red-700">{{ summary.terlambat }}</p>
        </div>
      </div>
      <div class="card p-4 flex items-center gap-3 border-l-4 border-primary-700">
        <span class="text-2xl">💰</span>
        <div>
          <p class="text-xs text-gray-500">Total Nominal</p>
          <p class="font-bold text-sm text-primary-700">{{ formatCurrency(summary.totalNominal) }}</p>
        </div>
      </div>
    </div>

    <!-- Modal Bayar Manual -->
    <div v-if="bayarModal.show" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="px-5 py-4 border-b flex items-center justify-between">
          <h3 class="font-bold">💰 Catat Pembayaran Manual</h3>
          <button @click="bayarModal.show = false" class="text-gray-400 hover:text-gray-700">✕</button>
        </div>
        <div class="p-5 space-y-4">
          <div class="bg-gray-50 rounded-lg p-3 text-sm">
            <p><strong>Warga:</strong> {{ bayarModal.item?.warga?.user?.name }}</p>
            <p><strong>Tagihan:</strong> {{ jenisLabel(bayarModal.item?.jenis) }} - {{ bayarModal.item?.nama_bulan }} {{ bayarModal.item?.tahun }}</p>
            <p><strong>Nominal:</strong> {{ formatCurrency(bayarModal.item?.total_tagihan ?? 0) }}</p>
          </div>
          <div>
            <label class="label">Metode Pembayaran *</label>
            <select v-model="bayarModal.form.metode" class="input">
              <option value="tunai">💵 Tunai</option>
              <option value="transfer">🏦 Transfer Bank</option>
              <option value="lainnya">📋 Lainnya</option>
            </select>
          </div>
          <div>
            <label class="label">Tanggal Bayar</label>
            <input v-model="bayarModal.form.tanggal_bayar" type="date" class="input" />
          </div>
          <div>
            <label class="label">Catatan (opsional)</label>
            <textarea v-model="bayarModal.form.catatan" class="input" rows="2"
              placeholder="cth: Bayar langsung ke rumah pak Edi" />
          </div>
          <div v-if="bayarModal.error" class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-red-700 text-sm">
            {{ bayarModal.error }}
          </div>
        </div>
        <div class="px-5 py-4 border-t flex gap-3 justify-end">
          <button @click="bayarModal.show = false" class="btn-secondary">Batal</button>
          <button @click="submitBayarManual" class="btn-primary" :disabled="bayarModal.loading">
            <span v-if="bayarModal.loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            <CheckIcon v-else class="w-4 h-4" />
            Catat Pembayaran
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <div v-if="isLoading" class="p-8 text-center">
        <div class="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto" />
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Warga</th>
              <th>Alamat</th>
              <th>Periode</th>
              <th>Nominal</th>
              <th>Jatuh Tempo</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="tagihans.length === 0">
              <td colspan="7" class="text-center py-10 text-gray-400">Tidak ada data tagihan</td>
            </tr>
            <tr v-for="item in tagihans" :key="item.id">
              <td class="font-medium">{{ item.warga?.user?.name }}</td>
              <td class="text-sm text-gray-500">Blok {{ item.warga?.blok }} No. {{ item.warga?.nomor_rumah }}</td>
              <td>
                {{ item.nama_bulan }} {{ item.tahun }}
                <p class="text-xs text-gray-400">{{ jenisLabel(item.jenis) }}</p>
              </td>
              <td class="font-semibold">{{ formatCurrency(item.nominal) }}</td>
              <td class="text-sm">{{ formatDateOnly(item.jatuh_tempo) }}</td>
              <td>
                <span :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
              </td>
              <td>
                <button
                  v-if="canManage && item.status !== 'sudah_bayar'"
                  @click="openBayarManual(item)"
                  class="btn-sm bg-green-50 text-green-700 hover:bg-green-100 border border-green-200 rounded-lg"
                  title="Catat pembayaran manual"
                >
                  <BanknotesIcon class="w-3.5 h-3.5" /> Bayar
                </button>
                <span v-else-if="item.status === 'sudah_bayar'" class="text-xs text-gray-400">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { DocumentPlusIcon, BanknotesIcon, CheckIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const toast = useToast()
const auth = useAuthStore()
const tagihans = ref([])
const isLoading = ref(false)
const generating = ref(false)
const exporting = ref(false)

async function exportExcel() {
  exporting.value = true
  try {
    const params = { ...filters.value }
    if (activeJenis.value) params.jenis = activeJenis.value

    const res = await api.get('/admin/tagihan/export', {
      params,
      responseType: 'blob',
    })

    // Trigger download
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    const tgl = new Date().toISOString().slice(0, 10)
    const jenisLabel = activeJenis.value === 'kedukaan' ? '_kedukaan' :
                       activeJenis.value === 'ipl_bulanan' ? '_ipl' : ''
    link.download = `tagihan${jenisLabel}_${tgl}.csv`
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('File berhasil di-export!')
  } catch {
    toast.error('Gagal export file.')
  } finally {
    exporting.value = false
  }
}

// Hanya super_admin & bendahara yang bisa bayar manual
const canManage = computed(() =>
  ['super_admin', 'bendahara'].includes(auth.user?.role)
)

const bayarModal = ref({
  show: false,
  loading: false,
  item: null,
  error: '',
  form: {
    metode: 'tunai',
    tanggal_bayar: new Date().toISOString().split('T')[0],
    catatan: '',
  },
})

function openBayarManual(item) {
  bayarModal.value = {
    show: true,
    loading: false,
    item,
    error: '',
    form: {
      metode: 'tunai',
      tanggal_bayar: new Date().toISOString().split('T')[0],
      catatan: '',
    },
  }
}

async function submitBayarManual() {
  bayarModal.value.loading = true
  bayarModal.value.error = ''
  try {
    await api.post(`/ipl/tagihan/${bayarModal.value.item.id}/bayar-manual`, bayarModal.value.form)
    toast.success('Pembayaran berhasil dicatat!')
    bayarModal.value.show = false
    await fetchData()
  } catch (e) {
    bayarModal.value.error = e.response?.data?.message ?? 'Gagal mencatat pembayaran.'
  } finally {
    bayarModal.value.loading = false
  }
}

function jenisLabel(jenis) {
  return { ipl_bulanan: 'IPL Bulanan', kedukaan: 'Uang Kedukaan' }[jenis] ?? jenis
}

function formatDateOnly(d) {
  if (!d) return '-'
  try {
    return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch { return d }
}

const activeJenis = ref('') // '' = semua, 'ipl_bulanan', 'kedukaan'
const jenisTabs = [
  { value: '', label: 'Semua', icon: '📋' },
  { value: 'ipl_bulanan', label: 'IPL Bulanan', icon: '🏠' },
  { value: 'kedukaan', label: 'Uang Kedukaan', icon: '🤝' },
]

const filters = ref({
  bulan: new Date().getMonth() + 1,
  tahun: new Date().getFullYear(),
  status: '',
})

const bulanOptions = {
  1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April',
  5: 'Mei', 6: 'Juni', 7: 'Juli', 8: 'Agustus',
  9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember',
}

const tahunOptions = computed(() => {
  const year = new Date().getFullYear()
  return [year - 1, year, year + 1]
})

const summary = computed(() => ({
  lunas: tagihans.value.filter((t) => t.status === 'sudah_bayar').length,
  belumBayar: tagihans.value.filter((t) => t.status === 'belum_bayar').length,
  terlambat: tagihans.value.filter((t) => t.status === 'terlambat').length,
  totalNominal: tagihans.value.reduce((sum, t) => sum + Number(t.total_tagihan ?? t.nominal ?? 0), 0),
}))

async function fetchData() {
  isLoading.value = true
  try {
    const params = { ...filters.value }
    if (activeJenis.value) params.jenis = activeJenis.value
    const res = await api.get('/ipl/tagihan', { params })
    tagihans.value = res.data.data ?? res.data
  } finally {
    isLoading.value = false
  }
}

async function generateTagihan() {
  if (!confirm('Generate tagihan IPL bulan ini untuk semua warga aktif?')) return
  generating.value = true
  try {
    await api.post('/admin/tagihan/generate')
    toast.success('Tagihan berhasil digenerate!')
    await fetchData()
  } catch {
    toast.error('Gagal generate tagihan.')
  } finally {
    generating.value = false
  }
}

function resetFilters() {
  filters.value = { bulan: new Date().getMonth() + 1, tahun: new Date().getFullYear(), status: '' }
  fetchData()
}

const formatCurrency = (v) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v ?? 0)

const statusClass = (s) => ({ sudah_bayar: 'badge-green', belum_bayar: 'badge-yellow', terlambat: 'badge-red' }[s] ?? 'badge-gray')
const statusLabel = (s) => ({ sudah_bayar: 'Lunas', belum_bayar: 'Belum Bayar', terlambat: 'Terlambat' }[s] ?? s)

onMounted(() => fetchData())
</script>
