<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">💸 Pengeluaran / Kas RT</h2>
        <p class="text-sm text-gray-500">Catat pengeluaran operational RT dari dana IPL atau uang kedukaan</p>
      </div>
      <button @click="openForm()" class="btn-primary">
        <PlusIcon class="w-4 h-4" /> Tambah Pengeluaran
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- IPL -->
      <div class="card p-5 border-l-4 border-emerald-500">
        <p class="text-xs text-gray-500">💰 Saldo IPL Aktif</p>
        <p class="text-2xl font-bold text-emerald-700 mt-1">{{ formatCurrency(summary.ipl?.saldo) }}</p>
        <div class="grid grid-cols-3 gap-2 mt-3 text-xs">
          <div>
            <p class="text-gray-400">Pemasukan</p>
            <p class="font-semibold text-green-700">{{ formatCurrency(summary.ipl?.pemasukan) }}</p>
          </div>
          <div>
            <p class="text-gray-400">Pengeluaran</p>
            <p class="font-semibold text-red-700">−{{ formatCurrency(summary.ipl?.pengeluaran) }}</p>
          </div>
          <div>
            <p class="text-gray-400">Adjustment</p>
            <p class="font-semibold" :class="(summary.ipl?.adjustment ?? 0) >= 0 ? 'text-green-700' : 'text-red-700'">
              {{ (summary.ipl?.adjustment ?? 0) >= 0 ? '+' : '' }}{{ formatCurrency(summary.ipl?.adjustment) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Kedukaan -->
      <div class="card p-5 border-l-4 border-purple-500">
        <p class="text-xs text-gray-500">🕊️ Saldo Uang Kedukaan</p>
        <p class="text-2xl font-bold text-purple-700 mt-1">{{ formatCurrency(summary.kedukaan?.saldo) }}</p>
        <div class="grid grid-cols-3 gap-2 mt-3 text-xs">
          <div>
            <p class="text-gray-400">Pemasukan</p>
            <p class="font-semibold text-green-700">{{ formatCurrency(summary.kedukaan?.pemasukan) }}</p>
          </div>
          <div>
            <p class="text-gray-400">Pengeluaran</p>
            <p class="font-semibold text-red-700">−{{ formatCurrency(summary.kedukaan?.pengeluaran) }}</p>
          </div>
          <div>
            <p class="text-gray-400">Adjustment</p>
            <p class="font-semibold" :class="(summary.kedukaan?.adjustment ?? 0) >= 0 ? 'text-green-700' : 'text-red-700'">
              {{ (summary.kedukaan?.adjustment ?? 0) >= 0 ? '+' : '' }}{{ formatCurrency(summary.kedukaan?.adjustment) }}
            </p>
          </div>
        </div>
        <p v-if="(summary.kedukaan?.saldo ?? 0) <= 0" class="text-xs text-orange-600 mt-2 font-medium">
          ⚠️ Saldo habis — tagihan kedukaan baru akan otomatis dibuat untuk semua warga di bulan berikutnya.
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4 grid grid-cols-2 md:grid-cols-4 gap-3">
      <div>
        <label class="label">Sumber Dana</label>
        <select v-model="filters.sumber_dana" @change="load" class="input">
          <option value="">Semua</option>
          <option value="ipl">💰 Dana IPL</option>
          <option value="kedukaan">🕊️ Uang Kedukaan</option>
        </select>
      </div>
      <div>
        <label class="label">Kategori</label>
        <select v-model="filters.kategori" @change="load" class="input">
          <option value="">Semua</option>
          <option v-for="(label, code) in kategoriList" :key="code" :value="code">{{ label }}</option>
        </select>
      </div>
      <div>
        <label class="label">Dari Tanggal</label>
        <input v-model="filters.from_date" type="date" class="input" @change="load" />
      </div>
      <div>
        <label class="label">Sampai Tanggal</label>
        <input v-model="filters.to_date" type="date" class="input" @change="load" />
      </div>
    </div>

    <!-- Tabel -->
    <div class="card overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <div class="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto" />
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-3 py-3 whitespace-nowrap" style="min-width: 110px">Tanggal</th>
              <th class="text-left px-3 py-3 whitespace-nowrap" style="min-width: 100px">Sumber</th>
              <th class="text-left px-3 py-3 whitespace-nowrap" style="min-width: 160px">Kategori</th>
              <th class="text-left px-3 py-3" style="min-width: 250px">Keterangan</th>
              <th class="text-right px-3 py-3 whitespace-nowrap" style="min-width: 130px">Nominal</th>
              <th class="text-left px-3 py-3 whitespace-nowrap" style="min-width: 120px">Pencatat</th>
              <th class="text-right px-3 py-3 whitespace-nowrap" style="min-width: 120px">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-if="pengeluarans.length === 0">
              <td colspan="7" class="text-center py-10 text-gray-400">Belum ada pengeluaran tercatat.</td>
            </tr>
            <tr v-for="p in pengeluarans" :key="p.id" class="hover:bg-gray-50">
              <td class="px-3 py-3 text-xs whitespace-nowrap">{{ formatDate(p.tanggal) }}</td>
              <td class="px-3 py-3 whitespace-nowrap">
                <span :class="p.sumber_dana === 'ipl' ? 'badge-green' : 'badge-blue'">
                  {{ p.sumber_dana === 'ipl' ? '💰 IPL' : '🕊️ Kedukaan' }}
                </span>
              </td>
              <td class="px-3 py-3 whitespace-nowrap">{{ kategoriList[p.kategori] ?? p.kategori }}</td>
              <td class="px-3 py-3">
                <p class="text-sm">{{ p.keterangan ?? '-' }}</p>
                <p v-if="p.warga" class="text-xs text-gray-400">Untuk: {{ p.warga.user?.name }}</p>
              </td>
              <td class="px-3 py-3 text-right font-semibold text-red-700 whitespace-nowrap">
                −{{ formatCurrency(p.nominal) }}
              </td>
              <td class="px-3 py-3 text-xs text-gray-600 whitespace-nowrap">{{ p.pencatat?.name ?? '-' }}</td>
              <td class="px-3 py-3 text-right whitespace-nowrap">
                <button @click="openForm(p)" class="text-primary-700 hover:underline text-sm mr-3">Edit</button>
                <button v-if="isSuperAdmin" @click="hapus(p)" class="text-red-600 hover:underline text-sm">Hapus</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg my-4">
        <div class="px-5 py-4 border-b flex items-center justify-between">
          <h3 class="font-bold">{{ form.id ? '✏️ Edit Pengeluaran' : '💸 Tambah Pengeluaran' }}</h3>
          <button @click="closeForm" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">Sumber Dana *</label>
              <select v-model="form.sumber_dana" class="input">
                <option value="ipl">💰 Dana IPL</option>
                <option value="kedukaan">🕊️ Uang Kedukaan</option>
              </select>
            </div>
            <div>
              <label class="label">Tanggal *</label>
              <input v-model="form.tanggal" type="date" class="input" />
            </div>
          </div>

          <div>
            <label class="label">Kategori *</label>
            <select v-model="form.kategori" class="input">
              <option value="">— Pilih Kategori —</option>
              <option v-for="(label, code) in kategoriList" :key="code" :value="code">{{ label }}</option>
            </select>
          </div>

          <div>
            <label class="label">Nominal (Rp) *</label>
            <input v-model.number="form.nominal" type="number" class="input" min="1" placeholder="50000" />
          </div>

          <div>
            <label class="label">Keterangan</label>
            <input v-model="form.keterangan" type="text" class="input" maxlength="200"
              placeholder="Cth: Iuran sampah bulan Mei 2026" />
          </div>

          <div v-if="form.sumber_dana === 'kedukaan'">
            <label class="label">Untuk Warga (opsional)</label>
            <select v-model="form.warga_id" class="input">
              <option :value="null">— Tidak spesifik —</option>
              <option v-for="w in wargaList" :key="w.id" :value="w.id">
                {{ w.user?.name }} (Blok {{ w.blok }} No. {{ w.nomor_rumah }})
              </option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Pilih warga yang berduka kalau pengeluaran ini untuk santunan kedukaan.</p>
          </div>

          <div>
            <label class="label">Catatan tambahan (opsional)</label>
            <textarea v-model="form.catatan" class="input" rows="2" maxlength="300"
              placeholder="Catatan internal untuk audit" />
          </div>

          <div v-if="form.sumber_dana === 'kedukaan' && form.nominal > (summary.kedukaan?.saldo ?? 0)"
            class="bg-orange-50 border border-orange-200 rounded p-3 text-xs text-orange-800">
            ⚠️ Nominal melebihi saldo kedukaan. Setelah disimpan, sistem akan auto-reset:
            semua warga akan dapat tagihan kedukaan baru bulan depan untuk mengumpulkan kembali.
          </div>

          <div v-if="formError" class="bg-red-50 border border-red-200 rounded p-3 text-sm text-red-700">
            {{ formError }}
          </div>
        </div>
        <div class="px-5 py-4 border-t flex gap-3 justify-end">
          <button @click="closeForm" class="btn-secondary">Batal</button>
          <button @click="simpan" class="btn-primary" :disabled="saving">
            <span v-if="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            Simpan
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import dayjs from 'dayjs'

const toast = useToast()
const auth = useAuthStore()
const isSuperAdmin = computed(() => auth.user?.role === 'super_admin')

const pengeluarans = ref([])
const summary = ref({})
const kategoriList = ref({})
const wargaList = ref([])
const loading = ref(false)
const saving = ref(false)
const showForm = ref(false)
const formError = ref('')

const filters = ref({ sumber_dana: '', kategori: '', from_date: '', to_date: '' })

const form = ref({
  id: null,
  kategori: '',
  keterangan: '',
  nominal: 0,
  sumber_dana: 'ipl',
  tanggal: dayjs().format('YYYY-MM-DD'),
  warga_id: null,
  catatan: '',
})

async function load() {
  loading.value = true
  try {
    const params = { ...filters.value }
    Object.keys(params).forEach((k) => params[k] === '' && delete params[k])
    const res = await api.get('/pengeluaran', { params })
    pengeluarans.value = res.data.data?.data ?? []
    summary.value = res.data.summary ?? {}
    kategoriList.value = res.data.kategori_list ?? {}
  } catch (e) {
    toast.error('Gagal load pengeluaran.')
  } finally {
    loading.value = false
  }
}

async function loadWarga() {
  try {
    const res = await api.get('/admin/warga', { params: { per_page: 200 } })
    wargaList.value = res.data.data ?? []
  } catch (_) {}
}

function openForm(p = null) {
  formError.value = ''
  if (p) {
    form.value = {
      id: p.id,
      kategori: p.kategori,
      keterangan: p.keterangan ?? '',
      nominal: Number(p.nominal),
      sumber_dana: p.sumber_dana,
      tanggal: dayjs(p.tanggal).format('YYYY-MM-DD'),
      warga_id: p.warga_id,
      catatan: p.catatan ?? '',
    }
  } else {
    form.value = {
      id: null,
      kategori: '',
      keterangan: '',
      nominal: 0,
      sumber_dana: 'ipl',
      tanggal: dayjs().format('YYYY-MM-DD'),
      warga_id: null,
      catatan: '',
    }
  }
  showForm.value = true
}

function closeForm() { showForm.value = false }

async function simpan() {
  if (!form.value.kategori) { formError.value = 'Kategori wajib dipilih.'; return }
  if (!form.value.nominal || form.value.nominal < 1) { formError.value = 'Nominal harus > 0.'; return }
  saving.value = true
  formError.value = ''
  try {
    if (form.value.id) {
      await api.put(`/pengeluaran/${form.value.id}`, form.value)
      toast.success('Pengeluaran diperbarui.')
    } else {
      const res = await api.post('/pengeluaran', form.value)
      toast.success(res.data?.message ?? 'Pengeluaran tercatat.')
      if (res.data?.kedukaan_reset) {
        toast.warning('⚠️ Saldo kedukaan habis. Tagihan kedukaan baru otomatis dibuat untuk semua warga.', { timeout: 8000 })
      }
    }
    closeForm()
    await load()
  } catch (e) {
    const errs = e.response?.data?.errors
    formError.value = errs ? Object.values(errs).flat().join(' ') : (e.response?.data?.message ?? 'Gagal simpan.')
  } finally {
    saving.value = false
  }
}

async function hapus(p) {
  if (!confirm(`Hapus pengeluaran ${p.kategori} Rp ${Number(p.nominal).toLocaleString('id-ID')}?\nAksi ini tidak bisa dibatalkan.`)) return
  try {
    await api.delete(`/pengeluaran/${p.id}`)
    toast.success('Pengeluaran dihapus.')
    await load()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Gagal hapus.')
  }
}

const formatCurrency = (v) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v ?? 0)
const formatDate = (d) => dayjs(d).format('DD/MM/YYYY')

onMounted(() => {
  load()
  loadWarga()
})
</script>
