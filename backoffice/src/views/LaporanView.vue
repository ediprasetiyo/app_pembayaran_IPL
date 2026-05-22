<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold">📊 Laporan Keuangan IPL & Kas RT</h2>
        <p class="text-sm text-gray-500">Ringkasan pendapatan, pengeluaran, dan saldo kas perumahan</p>
      </div>
      <div class="flex gap-2">
        <button @click="exportExcel" class="btn-secondary" :disabled="loading">
          <DocumentArrowDownIcon class="w-4 h-4" /> Export Excel
        </button>
        <button @click="exportPdf" class="btn-secondary" :disabled="loading">
          <DocumentTextIcon class="w-4 h-4" /> Export PDF
        </button>
      </div>
    </div>

    <!-- Filter -->
    <div class="card p-4 flex gap-3 flex-wrap items-end">
      <div>
        <label class="label">Tahun Laporan</label>
        <select v-model="tahun" class="input w-auto" @change="fetchAll">
          <option v-for="y in tahunOptions" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
      <div class="ml-auto text-xs text-gray-400 self-end">
        Data per: {{ formatNow }}
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card p-12 text-center">
      <div class="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto" />
    </div>

    <template v-else>
      <!-- ===== SALDO KAS RT (snapshot saat ini) ===== -->
      <div class="card p-6">
        <h3 class="font-semibold text-gray-700 mb-4">💰 Saldo Kas RT Saat Ini</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-gradient-to-br from-emerald-50 to-green-50 border-l-4 border-emerald-500 rounded-lg p-4">
            <p class="text-xs text-gray-600">💰 Saldo IPL Aktif</p>
            <p class="text-2xl font-bold text-emerald-700 mt-1">{{ formatCurrency(kas.ipl?.saldo) }}</p>
            <div class="text-xs space-y-0.5 mt-3 text-gray-600">
              <p>Pemasukan IPL: <span class="font-semibold text-green-700">{{ formatCurrency(kas.ipl?.pemasukan) }}</span></p>
              <p>Pengeluaran IPL: <span class="font-semibold text-red-700">−{{ formatCurrency(kas.ipl?.pengeluaran) }}</span></p>
              <p>Adjustment Manual: <span class="font-semibold" :class="(kas.ipl?.adjustment ?? 0) >= 0 ? 'text-blue-700' : 'text-red-700'">
                {{ (kas.ipl?.adjustment ?? 0) >= 0 ? '+' : '' }}{{ formatCurrency(kas.ipl?.adjustment) }}
              </span></p>
            </div>
          </div>
          <div class="bg-gradient-to-br from-purple-50 to-pink-50 border-l-4 border-purple-500 rounded-lg p-4">
            <p class="text-xs text-gray-600">🕊️ Saldo Uang Kedukaan</p>
            <p class="text-2xl font-bold text-purple-700 mt-1">{{ formatCurrency(kas.kedukaan?.saldo) }}</p>
            <div class="text-xs space-y-0.5 mt-3 text-gray-600">
              <p>Pemasukan: <span class="font-semibold text-green-700">{{ formatCurrency(kas.kedukaan?.pemasukan) }}</span></p>
              <p>Pengeluaran: <span class="font-semibold text-red-700">−{{ formatCurrency(kas.kedukaan?.pengeluaran) }}</span></p>
              <p>Adjustment Manual: <span class="font-semibold" :class="(kas.kedukaan?.adjustment ?? 0) >= 0 ? 'text-blue-700' : 'text-red-700'">
                {{ (kas.kedukaan?.adjustment ?? 0) >= 0 ? '+' : '' }}{{ formatCurrency(kas.kedukaan?.adjustment) }}
              </span></p>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== RINGKASAN TAHUNAN ===== -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="card p-4 border-l-4 border-green-500">
          <p class="text-xs text-gray-500">Pendapatan {{ tahun }}</p>
          <p class="text-xl font-bold text-green-700 mt-1">{{ formatCurrency(totalPendapatan) }}</p>
        </div>
        <div class="card p-4 border-l-4 border-red-500">
          <p class="text-xs text-gray-500">Pengeluaran {{ tahun }}</p>
          <p class="text-xl font-bold text-red-700 mt-1">−{{ formatCurrency(totalPengeluaran) }}</p>
        </div>
        <div class="card p-4 border-l-4 border-blue-500">
          <p class="text-xs text-gray-500">Rata-rata Pendapatan / Bulan</p>
          <p class="text-xl font-bold text-blue-700 mt-1">{{ formatCurrency(rataRata) }}</p>
        </div>
        <div class="card p-4 border-l-4 border-primary-700">
          <p class="text-xs text-gray-500">Net Cash Flow {{ tahun }}</p>
          <p class="text-xl font-bold mt-1" :class="netCashFlow >= 0 ? 'text-emerald-700' : 'text-red-700'">
            {{ netCashFlow >= 0 ? '+' : '' }}{{ formatCurrency(netCashFlow) }}
          </p>
        </div>
      </div>

      <!-- ===== REKAPITULASI BULANAN ===== -->
      <div class="card p-6">
        <h3 class="font-semibold text-gray-700 mb-4">📅 Rekapitulasi per Bulan {{ tahun }}</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="text-left px-3 py-2">Bulan</th>
                <th class="text-right px-3 py-2">Tagihan</th>
                <th class="text-right px-3 py-2">Lunas</th>
                <th class="text-right px-3 py-2">Belum Bayar</th>
                <th class="text-right px-3 py-2">Pendapatan</th>
                <th class="text-right px-3 py-2">Pengeluaran</th>
                <th class="text-right px-3 py-2">Net</th>
                <th class="text-right px-3 py-2">% Bayar</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="row in laporanBulanan" :key="row.bulan">
                <td class="font-medium px-3 py-2">{{ row.nama_bulan }}</td>
                <td class="text-right px-3 py-2">{{ row.total }}</td>
                <td class="text-right text-green-600 font-medium px-3 py-2">{{ row.lunas }}</td>
                <td class="text-right text-yellow-600 px-3 py-2">{{ row.belum_bayar }}</td>
                <td class="text-right font-semibold text-green-700 px-3 py-2">{{ formatCurrency(row.pendapatan) }}</td>
                <td class="text-right font-semibold text-red-700 px-3 py-2">{{ row.pengeluaran > 0 ? '−' + formatCurrency(row.pengeluaran) : '-' }}</td>
                <td class="text-right font-bold px-3 py-2" :class="(row.pendapatan - row.pengeluaran) >= 0 ? 'text-emerald-700' : 'text-red-700'">
                  {{ formatCurrency(row.pendapatan - row.pengeluaran) }}
                </td>
                <td class="text-right px-3 py-2">
                  <div class="flex items-center justify-end gap-2">
                    <div class="w-12 bg-gray-200 rounded-full h-1.5">
                      <div class="bg-primary-600 h-1.5 rounded-full" :style="{ width: row.persen + '%' }" />
                    </div>
                    <span class="text-xs font-medium w-8 text-right">{{ row.persen }}%</span>
                  </div>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50 font-semibold border-t-2">
              <tr>
                <td class="px-3 py-2">TOTAL</td>
                <td class="text-right px-3 py-2">{{ totalTagihan }}</td>
                <td class="text-right text-green-700 px-3 py-2">{{ totalLunas }}</td>
                <td class="text-right text-yellow-700 px-3 py-2">{{ totalBelumBayar }}</td>
                <td class="text-right text-green-700 px-3 py-2">{{ formatCurrency(totalPendapatan) }}</td>
                <td class="text-right text-red-700 px-3 py-2">−{{ formatCurrency(totalPengeluaran) }}</td>
                <td class="text-right px-3 py-2" :class="netCashFlow >= 0 ? 'text-emerald-700' : 'text-red-700'">
                  {{ formatCurrency(netCashFlow) }}
                </td>
                <td class="text-right px-3 py-2">-</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ===== DETAIL PENGELUARAN ===== -->
      <div class="card p-6">
        <h3 class="font-semibold text-gray-700 mb-4">💸 Detail Pengeluaran {{ tahun }} ({{ pengeluaranList.length }} transaksi)</h3>
        <div v-if="pengeluaranList.length === 0" class="text-center py-8 text-gray-400">
          Belum ada pengeluaran tercatat di tahun {{ tahun }}.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="text-left px-3 py-2 whitespace-nowrap" style="min-width: 100px">Tanggal</th>
                <th class="text-left px-3 py-2 whitespace-nowrap" style="min-width: 90px">Sumber</th>
                <th class="text-left px-3 py-2 whitespace-nowrap" style="min-width: 140px">Kategori</th>
                <th class="text-left px-3 py-2" style="min-width: 220px">Keterangan</th>
                <th class="text-right px-3 py-2 whitespace-nowrap" style="min-width: 120px">Nominal</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="p in pengeluaranList" :key="p.id">
                <td class="px-3 py-2 text-xs whitespace-nowrap">{{ formatDate(p.tanggal) }}</td>
                <td class="px-3 py-2 whitespace-nowrap">
                  <span :class="p.sumber_dana === 'ipl' ? 'badge-green' : 'badge-blue'" class="text-xs">
                    {{ p.sumber_dana === 'ipl' ? '💰' : '🕊️' }} {{ p.sumber_dana }}
                  </span>
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-xs">{{ kategoriList[p.kategori] ?? p.kategori }}</td>
                <td class="px-3 py-2 text-xs">{{ p.keterangan ?? '-' }}</td>
                <td class="px-3 py-2 text-right font-semibold text-red-700 whitespace-nowrap">−{{ formatCurrency(p.nominal) }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50 font-semibold border-t-2">
              <tr>
                <td colspan="4" class="px-3 py-2">TOTAL PENGELUARAN</td>
                <td class="text-right text-red-700 px-3 py-2">−{{ formatCurrency(totalPengeluaran) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { DocumentArrowDownIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import api from '@/services/api'
import dayjs from 'dayjs'
import { useSettingsStore } from '@/stores/settings'

const toast = useToast()
const settingsStore = useSettingsStore()

const tahun = ref(new Date().getFullYear())
const tahunOptions = [tahun.value - 2, tahun.value - 1, tahun.value, tahun.value + 1]
const laporanBulanan = ref([])
const pengeluaranList = ref([])
const kas = ref({})
const kategoriList = ref({})
const loading = ref(false)

const namaBulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']

const formatNow = computed(() => dayjs().format('DD MMMM YYYY, HH:mm'))
const totalPendapatan = computed(() => laporanBulanan.value.reduce((s, r) => s + r.pendapatan, 0))
const totalPengeluaran = computed(() => laporanBulanan.value.reduce((s, r) => s + r.pengeluaran, 0))
const totalTagihan = computed(() => laporanBulanan.value.reduce((s, r) => s + r.total, 0))
const totalLunas = computed(() => laporanBulanan.value.reduce((s, r) => s + r.lunas, 0))
const totalBelumBayar = computed(() => laporanBulanan.value.reduce((s, r) => s + r.belum_bayar, 0))
const netCashFlow = computed(() => totalPendapatan.value - totalPengeluaran.value)
const rataRata = computed(() => {
  const bulanAdaPendapatan = laporanBulanan.value.filter((r) => r.pendapatan > 0).length
  return bulanAdaPendapatan > 0 ? totalPendapatan.value / bulanAdaPendapatan : 0
})

async function fetchAll() {
  loading.value = true
  try {
    // Parallel: kas summary, pengeluaran tahun ini, tagihan per bulan
    const [kasRes, pengRes] = await Promise.all([
      api.get('/kas/summary'),
      api.get('/pengeluaran', {
        params: {
          from_date: `${tahun.value}-01-01`,
          to_date: `${tahun.value}-12-31`,
        },
      }),
    ])

    kas.value = kasRes.data
    pengeluaranList.value = pengRes.data.data?.data ?? []
    kategoriList.value = pengRes.data.kategori_list ?? {}

    // Group pengeluaran per bulan
    const pengeluaranPerBulan = {}
    for (const p of pengeluaranList.value) {
      const m = dayjs(p.tanggal).month() + 1
      pengeluaranPerBulan[m] = (pengeluaranPerBulan[m] || 0) + Number(p.nominal)
    }

    // Build laporan per bulan
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
        pengeluaran: pengeluaranPerBulan[bulan] || 0,
        persen: data.length > 0 ? Math.round((lunas / data.length) * 100) : 0,
      })
    }
    laporanBulanan.value = rows
  } catch (e) {
    toast.error('Gagal memuat laporan.')
  } finally {
    loading.value = false
  }
}

// ============ EXPORT EXCEL ============
function exportExcel() {
  const wb = XLSX.utils.book_new()
  const brand = settingsStore.settings.brand_title || 'Perumahan'
  const subtitle = settingsStore.settings.brand_subtitle || ''

  // Sheet 1: Ringkasan
  const ringkasanData = [
    [`Laporan Keuangan IPL & Kas RT - ${brand} ${subtitle}`],
    [`Tahun: ${tahun.value} · Generated: ${formatNow.value}`],
    [],
    ['SALDO KAS SAAT INI'],
    ['Sumber', 'Pemasukan', 'Pengeluaran', 'Adjustment', 'Saldo Aktif'],
    ['IPL (Dana RT)', kas.value.ipl?.pemasukan ?? 0, kas.value.ipl?.pengeluaran ?? 0, kas.value.ipl?.adjustment ?? 0, kas.value.ipl?.saldo ?? 0],
    ['Uang Kedukaan', kas.value.kedukaan?.pemasukan ?? 0, kas.value.kedukaan?.pengeluaran ?? 0, kas.value.kedukaan?.adjustment ?? 0, kas.value.kedukaan?.saldo ?? 0],
    [],
    [`RINGKASAN TAHUN ${tahun.value}`],
    ['Total Pendapatan', totalPendapatan.value],
    ['Total Pengeluaran', totalPengeluaran.value],
    ['Net Cash Flow', netCashFlow.value],
    ['Rata-rata Pendapatan / Bulan', Math.round(rataRata.value)],
    ['Total Tagihan Dibuat', totalTagihan.value],
    ['Total Lunas', totalLunas.value],
    ['Total Belum Bayar', totalBelumBayar.value],
  ]
  const ws1 = XLSX.utils.aoa_to_sheet(ringkasanData)
  ws1['!cols'] = [{ wch: 35 }, { wch: 18 }, { wch: 18 }, { wch: 18 }, { wch: 18 }]
  XLSX.utils.book_append_sheet(wb, ws1, 'Ringkasan')

  // Sheet 2: Rekap per Bulan
  const bulananData = [
    ['Bulan', 'Tagihan Dibuat', 'Lunas', 'Belum Bayar', 'Pendapatan', 'Pengeluaran', 'Net', '% Bayar'],
    ...laporanBulanan.value.map((r) => [
      r.nama_bulan,
      r.total,
      r.lunas,
      r.belum_bayar,
      r.pendapatan,
      r.pengeluaran,
      r.pendapatan - r.pengeluaran,
      r.persen + '%',
    ]),
    ['TOTAL', totalTagihan.value, totalLunas.value, totalBelumBayar.value, totalPendapatan.value, totalPengeluaran.value, netCashFlow.value, '-'],
  ]
  const ws2 = XLSX.utils.aoa_to_sheet(bulananData)
  ws2['!cols'] = [{ wch: 15 }, { wch: 14 }, { wch: 10 }, { wch: 14 }, { wch: 16 }, { wch: 16 }, { wch: 16 }, { wch: 10 }]
  XLSX.utils.book_append_sheet(wb, ws2, 'Rekap Bulanan')

  // Sheet 3: Detail Pengeluaran
  if (pengeluaranList.value.length > 0) {
    const pengeluaranData = [
      ['Tanggal', 'Sumber Dana', 'Kategori', 'Keterangan', 'Nominal', 'Pencatat'],
      ...pengeluaranList.value.map((p) => [
        dayjs(p.tanggal).format('DD/MM/YYYY'),
        p.sumber_dana === 'ipl' ? 'IPL (Dana RT)' : 'Uang Kedukaan',
        kategoriList.value[p.kategori] ?? p.kategori,
        p.keterangan ?? '-',
        Number(p.nominal),
        p.pencatat?.name ?? '-',
      ]),
      ['', '', '', 'TOTAL', totalPengeluaran.value, ''],
    ]
    const ws3 = XLSX.utils.aoa_to_sheet(pengeluaranData)
    ws3['!cols'] = [{ wch: 12 }, { wch: 16 }, { wch: 24 }, { wch: 35 }, { wch: 15 }, { wch: 20 }]
    XLSX.utils.book_append_sheet(wb, ws3, 'Pengeluaran')
  }

  const filename = `Laporan_IPL_${brand.replace(/\s+/g, '_')}_${tahun.value}.xlsx`
  XLSX.writeFile(wb, filename)
  toast.success('Excel berhasil di-download!')
}

// ============ EXPORT PDF ============
function exportPdf() {
  const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })
  const brand = settingsStore.settings.brand_title || 'Perumahan'
  const subtitle = settingsStore.settings.brand_subtitle || ''
  const fmt = (v) => 'Rp ' + new Intl.NumberFormat('id-ID').format(v ?? 0)

  // Header
  doc.setFontSize(16)
  doc.setFont('helvetica', 'bold')
  doc.text(`Laporan Keuangan IPL & Kas RT`, 14, 16)
  doc.setFontSize(11)
  doc.setFont('helvetica', 'normal')
  doc.text(`${brand} ${subtitle}`, 14, 22)
  doc.setFontSize(9)
  doc.setTextColor(100)
  doc.text(`Tahun ${tahun.value} · Generated ${formatNow.value}`, 14, 27)
  doc.setTextColor(0)

  // Saldo Kas
  autoTable(doc, {
    startY: 32,
    head: [['Sumber Dana', 'Pemasukan', 'Pengeluaran', 'Adjustment', 'Saldo Aktif']],
    body: [
      ['IPL (Dana RT)', fmt(kas.value.ipl?.pemasukan), '−' + fmt(kas.value.ipl?.pengeluaran), fmt(kas.value.ipl?.adjustment), fmt(kas.value.ipl?.saldo)],
      ['Uang Kedukaan', fmt(kas.value.kedukaan?.pemasukan), '−' + fmt(kas.value.kedukaan?.pengeluaran), fmt(kas.value.kedukaan?.adjustment), fmt(kas.value.kedukaan?.saldo)],
    ],
    theme: 'striped',
    headStyles: { fillColor: [56, 142, 60] },
    styles: { fontSize: 9 },
    columnStyles: { 1: { halign: 'right' }, 2: { halign: 'right' }, 3: { halign: 'right' }, 4: { halign: 'right' } },
  })

  // Ringkasan Tahunan
  doc.setFontSize(12)
  doc.setFont('helvetica', 'bold')
  doc.text(`Ringkasan Tahun ${tahun.value}`, 14, doc.lastAutoTable.finalY + 10)

  autoTable(doc, {
    startY: doc.lastAutoTable.finalY + 14,
    body: [
      ['Total Pendapatan', fmt(totalPendapatan.value)],
      ['Total Pengeluaran', '−' + fmt(totalPengeluaran.value)],
      ['Net Cash Flow', fmt(netCashFlow.value)],
      ['Rata-rata Pendapatan / Bulan', fmt(rataRata.value)],
    ],
    theme: 'plain',
    styles: { fontSize: 10 },
    columnStyles: { 0: { fontStyle: 'bold' }, 1: { halign: 'right' } },
  })

  // Rekap Bulanan
  doc.addPage()
  doc.setFontSize(13)
  doc.setFont('helvetica', 'bold')
  doc.text(`Rekapitulasi per Bulan ${tahun.value}`, 14, 16)

  autoTable(doc, {
    startY: 22,
    head: [['Bulan', 'Tagihan', 'Lunas', 'Belum', 'Pendapatan', 'Pengeluaran', 'Net', '% Bayar']],
    body: laporanBulanan.value.map((r) => [
      r.nama_bulan,
      r.total,
      r.lunas,
      r.belum_bayar,
      fmt(r.pendapatan),
      r.pengeluaran > 0 ? '−' + fmt(r.pengeluaran) : '-',
      fmt(r.pendapatan - r.pengeluaran),
      r.persen + '%',
    ]),
    foot: [['TOTAL', totalTagihan.value, totalLunas.value, totalBelumBayar.value, fmt(totalPendapatan.value), '−' + fmt(totalPengeluaran.value), fmt(netCashFlow.value), '-']],
    theme: 'grid',
    headStyles: { fillColor: [56, 142, 60] },
    footStyles: { fillColor: [240, 240, 240], textColor: 0, fontStyle: 'bold' },
    styles: { fontSize: 9 },
    columnStyles: {
      1: { halign: 'right' }, 2: { halign: 'right' }, 3: { halign: 'right' },
      4: { halign: 'right' }, 5: { halign: 'right' }, 6: { halign: 'right' }, 7: { halign: 'right' },
    },
  })

  // Detail Pengeluaran
  if (pengeluaranList.value.length > 0) {
    doc.addPage()
    doc.setFontSize(13)
    doc.setFont('helvetica', 'bold')
    doc.text(`Detail Pengeluaran ${tahun.value} (${pengeluaranList.value.length} transaksi)`, 14, 16)

    autoTable(doc, {
      startY: 22,
      head: [['Tanggal', 'Sumber', 'Kategori', 'Keterangan', 'Nominal']],
      body: pengeluaranList.value.map((p) => [
        dayjs(p.tanggal).format('DD/MM/YYYY'),
        p.sumber_dana === 'ipl' ? 'IPL' : 'Kedukaan',
        kategoriList.value[p.kategori] ?? p.kategori,
        (p.keterangan ?? '-').substring(0, 60),
        '−' + fmt(p.nominal),
      ]),
      foot: [['', '', '', 'TOTAL', '−' + fmt(totalPengeluaran.value)]],
      theme: 'grid',
      headStyles: { fillColor: [220, 53, 69] },
      footStyles: { fillColor: [240, 240, 240], textColor: 0, fontStyle: 'bold' },
      styles: { fontSize: 8 },
      columnStyles: { 4: { halign: 'right' } },
    })
  }

  // Footer di setiap page
  const pageCount = doc.internal.getNumberOfPages()
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i)
    doc.setFontSize(8)
    doc.setTextColor(150)
    doc.text(
      `${brand} ${subtitle} · Halaman ${i} dari ${pageCount}`,
      doc.internal.pageSize.getWidth() / 2,
      doc.internal.pageSize.getHeight() - 8,
      { align: 'center' },
    )
  }

  const filename = `Laporan_IPL_${brand.replace(/\s+/g, '_')}_${tahun.value}.pdf`
  doc.save(filename)
  toast.success('PDF berhasil di-download!')
}

const formatCurrency = (v) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v ?? 0)
const formatDate = (d) => dayjs(d).format('DD/MM/YYYY')

onMounted(fetchAll)
</script>
