<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-lg font-semibold">Tagihan IPL</h2>
        <p class="text-sm text-gray-500">Kelola tagihan IPL bulanan seluruh warga</p>
      </div>
      <button @click="generateTagihan" class="btn-primary" :disabled="generating">
        <span v-if="generating" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
        <DocumentPlusIcon v-else class="w-4 h-4" />
        Generate Tagihan Bulan Ini
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
    <div class="flex gap-3 flex-wrap">
      <div class="badge-green px-3 py-1.5">✅ Lunas: {{ summary.lunas }}</div>
      <div class="badge-yellow px-3 py-1.5">⏳ Belum Bayar: {{ summary.belumBayar }}</div>
      <div class="badge-red px-3 py-1.5">⚠ Terlambat: {{ summary.terlambat }}</div>
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
              <th>Denda</th>
              <th>Total</th>
              <th>Jatuh Tempo</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="tagihans.length === 0">
              <td colspan="8" class="text-center py-10 text-gray-400">Tidak ada data tagihan</td>
            </tr>
            <tr v-for="item in tagihans" :key="item.id">
              <td class="font-medium">{{ item.warga?.user?.name }}</td>
              <td class="text-sm text-gray-500">Blok {{ item.warga?.blok }} No. {{ item.warga?.nomor_rumah }}</td>
              <td>{{ item.nama_bulan }} {{ item.tahun }}</td>
              <td>{{ formatCurrency(item.nominal) }}</td>
              <td :class="item.denda > 0 ? 'text-red-600 font-medium' : 'text-gray-400'">
                {{ item.denda > 0 ? formatCurrency(item.denda) : '-' }}
              </td>
              <td class="font-semibold">{{ formatCurrency(item.total_tagihan) }}</td>
              <td class="text-sm">{{ item.jatuh_tempo }}</td>
              <td>
                <span :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
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
import { DocumentPlusIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import api from '@/services/api'

const toast = useToast()
const tagihans = ref([])
const isLoading = ref(false)
const generating = ref(false)

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
}))

async function fetchData() {
  isLoading.value = true
  try {
    const res = await api.get('/ipl/tagihan', { params: filters.value })
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
