<template>
  <div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard
        v-for="card in statCards"
        :key="card.label"
        v-bind="card"
        :loading="store.isLoading"
      />
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Payment Chart -->
      <div class="card p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-gray-800">Pembayaran Bulan Ini</h3>
          <span class="text-sm text-gray-500">{{ currentMonth }}</span>
        </div>
        <div class="h-48 flex items-center justify-center">
          <Doughnut v-if="chartData" :data="chartData" :options="chartOptions" />
          <div v-else class="w-full h-full bg-gray-100 rounded-lg animate-pulse" />
        </div>
      </div>

      <!-- Summary -->
      <div class="card p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Ringkasan</h3>
        <div v-if="stats" class="space-y-3">
          <SummaryRow label="Total Warga Aktif" :value="stats.total_warga" icon="👥" />
          <SummaryRow label="Sudah Bayar" :value="stats.sudah_bayar" icon="✅" color="text-green-600" />
          <SummaryRow label="Belum Bayar" :value="stats.belum_bayar" icon="⏳" color="text-yellow-600" />
          <div class="border-t pt-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Total Pendapatan</span>
              <span class="font-bold text-primary-700">{{ formatCurrency(stats.total_pendapatan) }}</span>
            </div>
          </div>
          <div class="bg-primary-50 rounded-lg p-3">
            <div class="text-xs text-primary-600 mb-1">Persentase Pembayaran</div>
            <div class="w-full bg-primary-200 rounded-full h-2">
              <div
                class="bg-primary-600 rounded-full h-2 transition-all duration-500"
                :style="{ width: `${stats.persentase_bayar}%` }"
              />
            </div>
            <div class="text-right text-xs text-primary-700 mt-1 font-semibold">
              {{ stats.persentase_bayar }}%
            </div>
          </div>
        </div>
        <div v-else class="space-y-3">
          <div v-for="i in 4" :key="i" class="h-8 bg-gray-100 rounded animate-pulse" />
        </div>
      </div>
    </div>

    <!-- Uang Kedukaan Card -->
    <div class="card overflow-hidden">
      <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4 text-white">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🕊️</span>
          <div>
            <h3 class="font-semibold">Dana Uang Kedukaan</h3>
            <p class="text-xs text-purple-100">Dana yang terkumpul untuk membantu warga yang berduka cita</p>
          </div>
        </div>
      </div>
      <div class="p-6">
        <div v-if="stats?.kedukaan" class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="md:col-span-2 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-5 border border-purple-100">
            <p class="text-xs text-gray-600 mb-1">Total Dana Terkumpul</p>
            <p class="text-3xl font-bold text-purple-700">
              {{ formatCurrency(stats.kedukaan.total_dana) }}
            </p>
            <p class="text-xs text-gray-500 mt-2">
              Tarif: {{ formatCurrency(stats.kedukaan.tarif_per_warga) }} / warga (sekali bayar)
            </p>
          </div>
          <div class="bg-green-50 rounded-xl p-5 border border-green-100">
            <p class="text-xs text-gray-600 mb-1">Sudah Bayar</p>
            <p class="text-2xl font-bold text-green-700">{{ stats.kedukaan.warga_sudah_bayar }}</p>
            <p class="text-xs text-gray-500 mt-1">warga</p>
          </div>
          <div class="bg-amber-50 rounded-xl p-5 border border-amber-100">
            <p class="text-xs text-gray-600 mb-1">Belum Bayar</p>
            <p class="text-2xl font-bold text-amber-700">{{ stats.kedukaan.warga_belum_bayar }}</p>
            <p class="text-xs text-gray-500 mt-1">warga</p>
          </div>
        </div>
        <div v-else class="space-y-3">
          <div class="h-20 bg-gray-100 rounded-xl animate-pulse" />
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card p-6">
      <h3 class="font-semibold text-gray-800 mb-4">Aksi Cepat</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <RouterLink
          v-for="action in quickActions"
          :key="action.label"
          :to="action.to"
          class="flex flex-col items-center gap-2 p-4 bg-gray-50 rounded-xl hover:bg-primary-50 hover:text-primary-700 transition-colors group"
        >
          <div :class="['w-10 h-10 rounded-xl flex items-center justify-center text-white text-lg', action.bg]">
            {{ action.emoji }}
          </div>
          <span class="text-sm font-medium text-center">{{ action.label }}</span>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, defineComponent, h } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { useDashboardStore } from '@/stores/dashboard'
import dayjs from 'dayjs'
import 'dayjs/locale/id'
dayjs.locale('id')

ChartJS.register(ArcElement, Tooltip, Legend)

// Sub-components defined inline
const StatCard = defineComponent({
  props: ['label', 'value', 'icon', 'color', 'sub', 'loading'],
  setup(props) {
    return () => h('div', { class: 'card p-5' }, [
      h('div', { class: 'flex items-start justify-between' }, [
        h('div', [
          h('p', { class: 'text-sm text-gray-500 mb-1' }, props.label),
          props.loading
            ? h('div', { class: 'h-8 w-24 bg-gray-100 rounded animate-pulse' })
            : h('p', { class: 'text-2xl font-bold text-gray-800' }, props.value ?? '-'),
          props.sub && h('p', { class: 'text-xs text-gray-400 mt-1' }, props.sub),
        ]),
        h('div', { class: `text-2xl` }, props.icon),
      ])
    ])
  }
})

const SummaryRow = defineComponent({
  props: ['label', 'value', 'icon', 'color'],
  setup(props) {
    return () => h('div', { class: 'flex items-center justify-between' }, [
      h('div', { class: 'flex items-center gap-2' }, [
        h('span', props.icon),
        h('span', { class: 'text-sm text-gray-600' }, props.label),
      ]),
      h('span', { class: `font-semibold ${props.color || ''}` }, props.value ?? '-'),
    ])
  }
})

const store = useDashboardStore()
const stats = computed(() => store.stats)

const currentMonth = computed(() => dayjs().format('MMMM YYYY'))

const statCards = computed(() => [
  { label: 'Total Warga', value: stats.value?.total_warga, icon: '👥', color: 'bg-blue-500' },
  { label: 'Sudah Bayar', value: stats.value?.sudah_bayar, icon: '✅', color: 'bg-green-500', sub: 'bulan ini' },
  { label: 'Belum Bayar', value: stats.value?.belum_bayar, icon: '⏳', color: 'bg-yellow-500', sub: 'bulan ini' },
  { label: 'Pendapatan', value: formatCurrency(stats.value?.total_pendapatan), icon: '💰', color: 'bg-emerald-500' },
])

const chartData = computed(() => {
  if (!stats.value) return null
  return {
    labels: ['Sudah Bayar', 'Belum Bayar'],
    datasets: [{
      data: [stats.value.sudah_bayar, stats.value.belum_bayar],
      backgroundColor: ['#388e3c', '#ffa726'],
      borderWidth: 0,
    }]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
  cutout: '65%',
}

const quickActions = [
  { label: 'Tambah Warga', to: '/warga/tambah', emoji: '➕', bg: 'bg-blue-500' },
  { label: 'Lihat Tagihan', to: '/tagihan', emoji: '📄', bg: 'bg-yellow-500' },
  { label: 'Pengaduan', to: '/pengaduan', emoji: '📢', bg: 'bg-red-500' },
  { label: 'Laporan', to: '/laporan', emoji: '📊', bg: 'bg-purple-500' },
]

function formatCurrency(value) {
  if (value == null) return '-'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

onMounted(() => store.fetchStats())
</script>
