<template>
  <div v-if="warga" class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <RouterLink to="/warga" class="btn-secondary btn-sm">
          <ArrowLeftIcon class="w-4 h-4" />
        </RouterLink>
        <div>
          <h2 class="text-lg font-semibold">{{ warga.user?.name }}</h2>
          <p class="text-sm text-gray-500">Blok {{ warga.blok }} No. {{ warga.nomor_rumah }}</p>
        </div>
      </div>
      <RouterLink :to="`/warga/${warga.id}/edit`" class="btn-primary">
        <PencilIcon class="w-4 h-4" />
        Edit Data
      </RouterLink>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Identitas -->
      <div class="card p-6 space-y-3">
        <h3 class="font-semibold text-gray-700 border-b pb-2">Identitas</h3>
        <InfoRow label="Nama" :value="warga.user?.name" />
        <InfoRow label="Telepon" :value="warga.user?.phone" />
        <InfoRow label="NIK" :value="warga.nik ?? '-'" />
        <InfoRow label="Status" :value="warga.is_active ? 'Aktif' : 'Tidak Aktif'" />
      </div>

      <!-- Hunian -->
      <div class="card p-6 space-y-3">
        <h3 class="font-semibold text-gray-700 border-b pb-2">Data Hunian</h3>
        <InfoRow label="Alamat" :value="`Blok ${warga.blok} No. ${warga.nomor_rumah}`" />
        <InfoRow label="RT / RW" :value="`${warga.rt ?? '-'} / ${warga.rw ?? '-'}`" />
        <InfoRow label="Status Hunian" :value="statusHunianLabel(warga.status_hunian)" />
        <InfoRow label="Tgl. Pindah" :value="warga.tanggal_pindah ?? '-'" />
        <InfoRow label="Alamat Asal" :value="warga.alamat_asal ?? '-'" />
      </div>
    </div>

    <!-- Anggota Keluarga -->
    <div class="card p-6">
      <div class="flex items-center justify-between border-b pb-3 mb-4">
        <h3 class="font-semibold text-gray-700">
          Anggota Keluarga
          <span class="ml-2 badge-blue">{{ warga.anggota_keluarga?.length ?? 0 }} orang</span>
        </h3>
        <button @click="showAddAnggota = true" class="btn-secondary btn-sm">
          <PlusIcon class="w-4 h-4" />
          Tambah
        </button>
      </div>

      <div v-if="!warga.anggota_keluarga?.length" class="text-center py-8 text-gray-400 text-sm">
        Belum ada data anggota keluarga
      </div>

      <div class="space-y-3">
        <div
          v-for="anggota in warga.anggota_keluarga"
          :key="anggota.id"
          class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
        >
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-semibold text-sm">
              {{ anggota.nama[0]?.toUpperCase() }}
            </div>
            <div>
              <p class="font-medium text-sm">{{ anggota.nama }}</p>
              <div class="flex gap-2 mt-0.5">
                <span class="badge-gray text-xs">{{ hubunganLabel(anggota.hubungan) }}</span>
                <span class="text-xs text-gray-400">{{ anggota.jenis_kelamin === 'laki_laki' ? '♂ Laki-laki' : '♀ Perempuan' }}</span>
                <span v-if="anggota.pekerjaan" class="text-xs text-gray-400">· {{ anggota.pekerjaan }}</span>
              </div>
            </div>
          </div>
          <button @click="deleteAnggota(anggota.id)" class="text-red-400 hover:text-red-600 p-1">
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Tagihan -->
    <div class="card p-6">
      <h3 class="font-semibold text-gray-700 border-b pb-3 mb-4">Riwayat Tagihan IPL</h3>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Periode</th>
              <th>Nominal</th>
              <th>Denda</th>
              <th>Jatuh Tempo</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!warga.tagihan?.length">
              <td colspan="5" class="text-center py-6 text-gray-400">Belum ada tagihan</td>
            </tr>
            <tr v-for="tagihan in warga.tagihan" :key="tagihan.id">
              <td>{{ tagihan.nama_bulan }} {{ tagihan.tahun }}</td>
              <td>{{ formatCurrency(tagihan.nominal) }}</td>
              <td>{{ tagihan.denda > 0 ? formatCurrency(tagihan.denda) : '-' }}</td>
              <td>{{ tagihan.jatuh_tempo }}</td>
              <td>
                <span :class="tagihanStatusClass(tagihan.status)">
                  {{ tagihanStatusLabel(tagihan.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Add Anggota -->
    <div v-if="showAddAnggota" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-4">
        <h3 class="font-bold text-lg">Tambah Anggota Keluarga</h3>
        <div class="grid grid-cols-2 gap-3">
          <div class="col-span-2">
            <label class="label">Nama <span class="text-red-500">*</span></label>
            <input v-model="anggotaForm.nama" class="input" required />
          </div>
          <div>
            <label class="label">Hubungan</label>
            <select v-model="anggotaForm.hubungan" class="input">
              <option value="kepala_keluarga">Kepala Keluarga</option>
              <option value="istri">Istri</option>
              <option value="anak">Anak</option>
              <option value="orang_tua">Orang Tua</option>
              <option value="saudara">Saudara</option>
              <option value="lainnya">Lainnya</option>
            </select>
          </div>
          <div>
            <label class="label">Jenis Kelamin</label>
            <select v-model="anggotaForm.jenis_kelamin" class="input">
              <option value="laki_laki">Laki-laki</option>
              <option value="perempuan">Perempuan</option>
            </select>
          </div>
          <div>
            <label class="label">Tanggal Lahir</label>
            <input v-model="anggotaForm.tanggal_lahir" type="date" class="input" />
          </div>
          <div>
            <label class="label">Pekerjaan</label>
            <input v-model="anggotaForm.pekerjaan" class="input" />
          </div>
        </div>
        <div class="flex gap-3 justify-end pt-2">
          <button @click="showAddAnggota = false" class="btn-secondary">Batal</button>
          <button @click="submitAddAnggota" class="btn-primary" :disabled="addingAnggota">
            <span v-if="addingAnggota" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            Simpan
          </button>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="flex items-center justify-center h-64">
    <div class="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin" />
  </div>
</template>

<script setup>
import { ref, onMounted, defineComponent, h } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeftIcon, PencilIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { useWargaStore } from '@/stores/warga'

const InfoRow = defineComponent({
  props: ['label', 'value'],
  setup(props) {
    return () => h('div', { class: 'flex justify-between items-start gap-4' }, [
      h('span', { class: 'text-sm text-gray-500 flex-shrink-0' }, props.label),
      h('span', { class: 'text-sm font-medium text-right' }, props.value ?? '-'),
    ])
  }
})

const route = useRoute()
const toast = useToast()
const store = useWargaStore()
const warga = ref(null)
const showAddAnggota = ref(false)
const addingAnggota = ref(false)

const anggotaForm = ref({
  nama: '', hubungan: 'kepala_keluarga', jenis_kelamin: 'laki_laki',
  tanggal_lahir: '', pekerjaan: '',
})

const formatCurrency = (v) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v ?? 0)

const statusHunianLabel = (s) => ({ milik: 'Milik Sendiri', sewa: 'Sewa', kontrak: 'Kontrak' }[s] ?? s)
const hubunganLabel = (h) => ({
  kepala_keluarga: 'Kepala KK', istri: 'Istri', anak: 'Anak',
  orang_tua: 'Orang Tua', saudara: 'Saudara', lainnya: 'Lainnya',
}[h] ?? h)

const tagihanStatusClass = (s) => ({
  sudah_bayar: 'badge-green', belum_bayar: 'badge-yellow', terlambat: 'badge-red',
}[s] ?? 'badge-gray')

const tagihanStatusLabel = (s) => ({
  sudah_bayar: 'Lunas', belum_bayar: 'Belum Bayar', terlambat: 'Terlambat',
}[s] ?? s)

async function submitAddAnggota() {
  if (!anggotaForm.value.nama) return
  addingAnggota.value = true
  try {
    await store.addAnggotaKeluarga(warga.value.id, anggotaForm.value)
    warga.value = await store.fetchOne(route.params.id)
    showAddAnggota.value = false
    toast.success('Anggota keluarga ditambahkan!')
    anggotaForm.value = { nama: '', hubungan: 'kepala_keluarga', jenis_kelamin: 'laki_laki', tanggal_lahir: '', pekerjaan: '' }
  } catch {
    toast.error('Gagal menambahkan anggota keluarga.')
  } finally {
    addingAnggota.value = false
  }
}

async function deleteAnggota(anggotaId) {
  if (!confirm('Hapus anggota keluarga ini?')) return
  try {
    await store.deleteAnggotaKeluarga(warga.value.id, anggotaId)
    warga.value = await store.fetchOne(route.params.id)
    toast.success('Anggota keluarga dihapus.')
  } catch {
    toast.error('Gagal menghapus.')
  }
}

onMounted(async () => {
  warga.value = await store.fetchOne(route.params.id)
})
</script>
