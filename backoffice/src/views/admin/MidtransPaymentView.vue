<template>
  <div class="space-y-4">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">💳 Pembayaran Midtrans</h2>
      <p class="text-sm text-gray-500">Kelola payment method aktif & lihat biaya admin (MDR) dari Midtrans</p>
    </div>

    <!-- Disclaimer -->
    <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-4">
      <div class="flex items-start gap-3">
        <span class="text-xl">⚠️</span>
        <div class="flex-1 text-sm">
          <p class="font-semibold text-yellow-900">Disclaimer Tarif Biaya Admin</p>
          <p class="text-yellow-800 mt-1 leading-relaxed">
            {{ disclaimer || 'Tarif biaya admin di bawah adalah tarif standar Midtrans. Tarif aktual dapat berubah sewaktu-waktu sesuai kebijakan PT Midtrans.' }}
          </p>
          <a v-if="referenceUrl" :href="referenceUrl" target="_blank"
            class="inline-flex items-center gap-1 text-yellow-900 underline font-medium mt-2">
            🔗 Cek tarif terkini di midtrans.com
          </a>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
      <div class="card p-4 flex items-center gap-3 border-l-4 border-primary-700">
        <span class="text-2xl">💳</span>
        <div>
          <p class="text-xs text-gray-500">Total Method</p>
          <p class="font-bold text-xl">{{ total }}</p>
        </div>
      </div>
      <div class="card p-4 flex items-center gap-3 border-l-4 border-green-500">
        <span class="text-2xl">✅</span>
        <div>
          <p class="text-xs text-gray-500">Aktif</p>
          <p class="font-bold text-xl text-green-700">{{ enabledCount }}</p>
        </div>
      </div>
      <div class="card p-4 flex items-center gap-3 border-l-4 border-gray-400">
        <span class="text-2xl">⛔</span>
        <div>
          <p class="text-xs text-gray-500">Nonaktif</p>
          <p class="font-bold text-xl text-gray-600">{{ total - enabledCount }}</p>
        </div>
      </div>
    </div>

    <!-- Bulk actions -->
    <div class="card p-4 flex flex-wrap gap-2 items-center">
      <span class="text-sm font-medium text-gray-700">Aksi cepat:</span>
      <button @click="enableAll" class="btn-secondary text-sm" :disabled="saving">✅ Aktifkan Semua</button>
      <button @click="disableAll" class="btn-secondary text-sm" :disabled="saving">⛔ Nonaktifkan Semua</button>
      <button @click="enableRecommended" class="btn-primary text-sm" :disabled="saving">⭐ Hanya yang Umum</button>
      <p class="text-xs text-gray-400 ml-auto">Yang Umum: BCA/BNI/BRI/Permata VA, GoPay, ShopeePay, QRIS, Kartu Kredit</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card p-12 text-center">
      <div class="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto" />
    </div>

    <!-- Methods grouped by category -->
    <div v-else class="space-y-4">
      <div v-for="(items, cat) in grouped" :key="cat" class="card overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b">
          <h3 class="font-semibold text-gray-700">{{ cat }}</h3>
          <p class="text-xs text-gray-500">{{ items.filter((i) => i.enabled).length }} dari {{ items.length }} aktif</p>
        </div>
        <div class="divide-y">
          <div v-for="m in items" :key="m.code" class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50">
            <!-- Brand logo dengan emoji fallback -->
            <div class="flex-shrink-0 w-12 h-10 flex items-center justify-center bg-gray-50 rounded">
              <img
                v-if="m.logo_url"
                :src="m.logo_url"
                :alt="m.name"
                class="max-w-full max-h-full object-contain"
                @error="$event.target.style.display = 'none'; $event.target.nextElementSibling.style.display='block'"
              />
              <span :style="{ display: m.logo_url ? 'none' : 'block' }" class="text-2xl">{{ m.icon }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-gray-800">{{ m.name }}</p>
              <p class="text-xs font-mono text-gray-400">{{ m.code }}</p>
            </div>
            <div class="text-right flex-shrink-0">
              <span class="inline-block px-2 py-1 rounded-md text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200">
                {{ m.fee_label }}
              </span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 ml-2">
              <input type="checkbox" :checked="m.enabled" @change="toggle(m, $event.target.checked)" class="sr-only peer" :disabled="saving" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-700"></div>
            </label>
          </div>
        </div>
      </div>
    </div>

    <p class="text-xs text-gray-400 text-center pt-4">
      Perubahan langsung diterapkan ke mobile app — saat warga klik "Bayar", hanya method aktif yang akan tampil di Snap UI Midtrans.
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/services/api'

const toast = useToast()
const methods = ref([])
const grouped = ref({})
const total = ref(0)
const enabledCount = ref(0)
const referenceUrl = ref('')
const disclaimer = ref('')
const loading = ref(false)
const saving = ref(false)

async function load() {
  loading.value = true
  try {
    const res = await api.get('/admin/midtrans/methods')
    methods.value = res.data.data
    grouped.value = res.data.grouped
    total.value = res.data.total
    enabledCount.value = res.data.enabled_count
    referenceUrl.value = res.data.reference_url
    disclaimer.value = res.data.disclaimer
  } catch (e) {
    toast.error('Gagal load payment methods.')
  } finally {
    loading.value = false
  }
}

async function toggle(method, enabled) {
  saving.value = true
  try {
    const res = await api.post('/admin/midtrans/methods/toggle', {
      code: method.code,
      enabled,
    })
    toast.success(res.data?.message)
    await load()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Gagal update.')
    await load() // rollback UI
  } finally {
    saving.value = false
  }
}

async function bulkSet(codes) {
  saving.value = true
  try {
    const res = await api.post('/admin/midtrans/methods/bulk', { codes })
    toast.success(res.data?.message)
    await load()
  } catch (e) {
    toast.error('Gagal update.')
  } finally {
    saving.value = false
  }
}

function enableAll() {
  if (!confirm('Aktifkan SEMUA payment method?')) return
  bulkSet(methods.value.map((m) => m.code))
}

function disableAll() {
  if (!confirm('Nonaktifkan SEMUA payment method? Warga tidak bisa bayar via aplikasi.')) return
  bulkSet([])
}

function enableRecommended() {
  if (!confirm('Aktifkan hanya method paling umum?')) return
  bulkSet(['bca_va', 'bni_va', 'bri_va', 'permata_va', 'other_va', 'gopay', 'shopeepay', 'qris', 'credit_card'])
}

onMounted(load)
</script>
