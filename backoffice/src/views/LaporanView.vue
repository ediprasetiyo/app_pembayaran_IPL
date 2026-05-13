<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold">Laporan IPL</h2>
      <p class="text-sm text-gray-500">Ringkasan pendapatan dan pembayaran IPL</p>
    </div>

    <!-- Filter -->
    <div class="card p-4 flex gap-3 flex-wrap">
      <div>
        <label class="label">Tahun</label>
        <select v-model="tahun" class="input w-auto" @change="fetchLaporan">
          <option v-for="y in tahunOptions" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="card p-5 text-center">
        <p class="text-sm text-gray-500">Total Pendapatan {{ tahun }}</p>
        <p class="text-2xl font-bold text-primary-700 mt-1">{{ formatCurrency(totalPendapatan) }}</p>
      </div>
      <div class="card p-5 text-center">
        <p class="text-sm text-gray-500">Rata-rata per Bulan</p>
        <p class="text-2xl font-bold text-blue-700 mt-1">{{ formatCurrency(rataRata) }}</p>
      </div>
      <div class="card p-5 text-center">
        <p class="text-sm text-gray-500">Total Transaksi Sukses</p>
        <p class="text-2xl font-bold text-green-700 mt-1">{{ totalTransaksi }}</p>
      </div>
    </div>

    <!-- Table per Bulan -->
    <div class="card p-6">
      <h3 class="font-semibold text-gray-700 mb-4">Rekapitulasi per Bulan</h3>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Bulan</th>
              <th class="text-right">Tagihan Dibuat</th>
              <th class="text-right">Lunas</th>
              <th class="text-right">Belum Bayar</th>
              <th class="text-right">Total Pendapatan</th>
              <th class="text-right">% Bayar</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in laporanBulanan" :key="row.bulan">
              <td class="font-medium">{{ row.nama_bulan }}</td>
              <td class="text-right">{{ row.total }}</td>
              <td class="text-right text-green-600 font-medium">{{ row.lunas }}</td>
              <td class="text-right text-yellow-600">{{ row.belum_bayar }}</td>
              <td class="text-right font-semibold">{{ formatCurrency(row.pendapatan) }}</td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2">
                  <div class="w-16 bg-gray-200 rounded-full h-1.5">
                    <div class="bg-primary-600 h-1.5 rounded-full" :style="{ width: row.persen + '%' }" />
                  </div>
                  <span class="text-xs font-medium w-8 text-right">{{ row.persen }}%</span>
                </div>
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
import api from '@/services/api'

const tahun = ref(new Date().getFullYear())
const tahunOptions = [tahun.value - 1, tahun.value, tahun.value + 1]
const laporanBulanan = ref([])
const isLoading = ref(false)

const namaBulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']

const totalPendapatan = computed(() => laporanBulanan.value.reduce((s, r) => s + r.pendapatan, 0))
const totalTransaksi = computed(() => laporanBulanan.value.reduce((s, r) => s + r.lunas, 0))
const rataRata = computed(() => {
  const bulanAdaPendapatan = laporanBulanan.value.filter((r) => r.pendapatan > 0).length
  return bulanAdaPendapatan > 0 ? totalPendapatan.value / bulanAdaPendapatan : 0
})

async function fetchLaporan() {
  isLoading.value = true
  try {
    // Build summary from tagihan data
    const rows = []
    for (let bulan = 1; bulan <= 12; bulan++) {
      const res = await api.get('/ipl/tagihan', { params: { bulan, tahun: tahun.value } })
      const data = res.data.data ?? res.data ?? []
      const lunas = data.filter((t) => t.status === 'sudah_bayar').length
      const belumBayar = data.filter((t) => t.status !== 'sudah_bayar').length
      const pendapatan = data
        .filter((t) => t.status === 'sudah_bayar')
        .reduce((s, t) => s + parseFloat(t.total_tagihan ?? t.nominal ?? 0), 0)
      rows.push({
        bulan,
        nama_bulan: namaBulan[bulan - 1],
        total: data.length,
        lunas,
        belum_bayar: belumBayar,
        pendapatan,
        persen: data.length > 0 ? Math.round((lunas / data.length) * 100) : 0,
      })
    }
    laporanBulanan.value = rows
  } finally {
    isLoading.value = false
  }
}

const formatCurrency = (v) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v ?? 0)

onMounted(() => fetchLaporan())
</script>
