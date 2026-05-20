<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">🏘️ Manajemen Blok</h2>
        <p class="text-sm text-gray-500">Kelola blok perumahan (A, B, C, D, dst). Hanya super admin yang bisa edit.</p>
      </div>
      <button @click="openForm()" class="btn-primary">
        <PlusIcon class="w-4 h-4" /> Tambah Blok
      </button>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
      <div class="card p-4 flex items-center gap-3 border-l-4 border-primary-700">
        <span class="text-2xl">🏘️</span>
        <div>
          <p class="text-xs text-gray-500">Total Blok</p>
          <p class="font-bold text-xl">{{ summary.total }}</p>
        </div>
      </div>
      <div class="card p-4 flex items-center gap-3 border-l-4 border-green-500">
        <span class="text-2xl">✅</span>
        <div>
          <p class="text-xs text-gray-500">Blok Aktif</p>
          <p class="font-bold text-xl text-green-700">{{ summary.aktif }}</p>
        </div>
      </div>
      <div class="card p-4 flex items-center gap-3 border-l-4 border-blue-500">
        <span class="text-2xl">👥</span>
        <div>
          <p class="text-xs text-gray-500">Total Warga (semua blok)</p>
          <p class="font-bold text-xl text-blue-700">{{ totalWarga }}</p>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <div v-if="loading" class="p-8 text-center">
        <div class="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto" />
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Kode</th>
              <th>Nama Blok</th>
              <th>Deskripsi</th>
              <th class="text-center">Jumlah Rumah</th>
              <th class="text-center">Warga Aktif / Total</th>
              <th>Status</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="bloks.length === 0">
              <td colspan="7" class="text-center py-8 text-gray-400">Belum ada blok terdaftar.</td>
            </tr>
            <tr v-for="b in bloks" :key="b.id">
              <td class="font-mono font-bold">{{ b.kode }}</td>
              <td>{{ b.nama }}</td>
              <td class="text-sm text-gray-500">{{ b.deskripsi || '-' }}</td>
              <td class="text-center">{{ b.jumlah_rumah ?? '-' }}</td>
              <td class="text-center">
                <span class="font-semibold text-green-700">{{ b.jumlah_warga_aktif }}</span>
                <span class="text-gray-400"> / {{ b.jumlah_warga_total }}</span>
              </td>
              <td>
                <span :class="b.is_active ? 'badge-green' : 'badge-gray'">
                  {{ b.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="text-right">
                <button @click="openForm(b)" class="text-primary-700 hover:underline text-sm mr-3">Edit</button>
                <button @click="hapus(b)" class="text-red-600 hover:underline text-sm">Hapus</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="px-5 py-4 border-b flex items-center justify-between">
          <h3 class="font-bold">{{ form.id ? '✏️ Edit Blok' : '➕ Tambah Blok' }}</h3>
          <button @click="closeForm" class="text-gray-400 hover:text-gray-700">✕</button>
        </div>
        <div class="p-5 space-y-4">
          <div>
            <label class="label">Kode Blok *</label>
            <input v-model="form.kode" class="input" placeholder="A / B / C / E / E1" maxlength="10" />
            <p class="text-xs text-gray-400 mt-1">Singkat, huruf besar. Cth: A, B, C, E, E-Tahap2</p>
          </div>
          <div>
            <label class="label">Nama Blok *</label>
            <input v-model="form.nama" class="input" placeholder="Blok A" maxlength="100" />
          </div>
          <div>
            <label class="label">Deskripsi (opsional)</label>
            <textarea v-model="form.deskripsi" class="input" rows="2" placeholder="Cth: Blok awal — perumahan tahap 1" />
          </div>
          <div>
            <label class="label">Jumlah Rumah (opsional)</label>
            <input v-model.number="form.jumlah_rumah" type="number" class="input" min="0" />
          </div>
          <div>
            <label class="flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" />
              <span class="text-sm">Blok Aktif</span>
            </label>
          </div>
          <div v-if="formError" class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-red-700 text-sm">
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
import api from '@/services/api'

const toast = useToast()
const bloks = ref([])
const loading = ref(false)
const summary = ref({ total: 0, aktif: 0 })

const showForm = ref(false)
const saving = ref(false)
const formError = ref('')
const form = ref({ id: null, kode: '', nama: '', deskripsi: '', jumlah_rumah: null, is_active: true })

const totalWarga = computed(() =>
  bloks.value.reduce((sum, b) => sum + (b.jumlah_warga_total || 0), 0),
)

async function load() {
  loading.value = true
  try {
    const res = await api.get('/admin/bloks')
    bloks.value = res.data.data ?? []
    summary.value = { total: res.data.total, aktif: res.data.aktif }
  } catch (e) {
    toast.error('Gagal load data blok.')
  } finally {
    loading.value = false
  }
}

function openForm(blok = null) {
  formError.value = ''
  if (blok) {
    form.value = {
      id: blok.id,
      kode: blok.kode,
      nama: blok.nama,
      deskripsi: blok.deskripsi ?? '',
      jumlah_rumah: blok.jumlah_rumah,
      is_active: blok.is_active,
    }
  } else {
    form.value = { id: null, kode: '', nama: '', deskripsi: '', jumlah_rumah: null, is_active: true }
  }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
}

async function simpan() {
  saving.value = true
  formError.value = ''
  try {
    if (form.value.id) {
      await api.put(`/admin/bloks/${form.value.id}`, form.value)
      toast.success('Blok diperbarui.')
    } else {
      await api.post('/admin/bloks', form.value)
      toast.success('Blok ditambahkan.')
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

async function hapus(blok) {
  if (!confirm(`Hapus Blok ${blok.kode} - ${blok.nama}?\nBlok ini harus kosong (tidak ada warga) dulu.`)) return
  try {
    await api.delete(`/admin/bloks/${blok.id}`)
    toast.success('Blok dihapus.')
    await load()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Gagal hapus.')
  }
}

onMounted(load)
</script>
