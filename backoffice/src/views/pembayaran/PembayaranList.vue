<template>
  <div class="space-y-4">
    <div>
      <h2 class="text-lg font-semibold">Riwayat Pembayaran</h2>
      <p class="text-sm text-gray-500">Semua transaksi pembayaran IPL via Midtrans</p>
    </div>

    <div class="card p-4 flex flex-col sm:flex-row gap-3">
      <select v-model="filters.status" class="input w-auto" @change="fetchData">
        <option value="">Semua Status</option>
        <option value="success">Berhasil</option>
        <option value="pending">Pending</option>
        <option value="failed">Gagal</option>
        <option value="expired">Expired</option>
      </select>
    </div>

    <div class="card">
      <div v-if="isLoading" class="p-8 text-center">
        <div class="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto" />
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Warga</th>
              <th>Periode IPL</th>
              <th>Nominal</th>
              <th>Metode</th>
              <th>Status</th>
              <th>Tanggal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="pembayarans.length === 0">
              <td colspan="7" class="text-center py-10 text-gray-400">Tidak ada data pembayaran</td>
            </tr>
            <tr v-for="item in pembayarans" :key="item.id">
              <td class="font-mono text-xs text-gray-500">{{ item.order_id }}</td>
              <td class="font-medium">{{ item.warga?.user?.name }}</td>
              <td class="text-sm">{{ item.tagihan?.nama_bulan }} {{ item.tagihan?.tahun }}</td>
              <td class="font-semibold">{{ formatCurrency(item.nominal) }}</td>
              <td class="text-sm text-gray-500">{{ item.midtrans_payment_type ?? '-' }}</td>
              <td>
                <span :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
              </td>
              <td class="text-sm text-gray-500">{{ formatDate(item.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import dayjs from 'dayjs'

const pembayarans = ref([])
const isLoading = ref(false)
const filters = ref({ status: '' })

async function fetchData() {
  isLoading.value = true
  try {
    const res = await api.get('/ipl/pembayaran', { params: filters.value })
    pembayarans.value = res.data.data ?? []
  } finally {
    isLoading.value = false
  }
}

const formatCurrency = (v) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v ?? 0)
const formatDate = (d) => dayjs(d).format('DD/MM/YYYY HH:mm')
const statusClass = (s) => ({ success: 'badge-green', pending: 'badge-yellow', failed: 'badge-red', expired: 'badge-gray', cancel: 'badge-gray' }[s] ?? 'badge-gray')
const statusLabel = (s) => ({ success: 'Berhasil', pending: 'Pending', failed: 'Gagal', expired: 'Expired', cancel: 'Dibatalkan' }[s] ?? s)

// Auto-polling: refresh tiap 20 detik supaya pembayaran baru otomatis muncul
let pollInterval = null
onMounted(() => {
  fetchData()
  pollInterval = setInterval(() => {
    if (document.visibilityState === 'visible') fetchData()
  }, 20000)
})
onUnmounted(() => { if (pollInterval) clearInterval(pollInterval) })
</script>
