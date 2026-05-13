<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
      <RouterLink to="/warga" class="btn-secondary btn-sm">
        <ArrowLeftIcon class="w-4 h-4" />
      </RouterLink>
      <h2 class="text-lg font-semibold">{{ isEdit ? 'Edit Data Warga' : 'Tambah Warga Baru' }}</h2>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Identitas Warga -->
      <div class="card p-6 space-y-4">
        <h3 class="font-semibold text-gray-700 border-b pb-2">Identitas Warga</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Nama Lengkap <span class="text-red-500">*</span></label>
            <input v-model="form.name" type="text" class="input" placeholder="Nama sesuai KTP" required />
          </div>
          <div>
            <label class="label">Nomor Telepon <span class="text-red-500">*</span></label>
            <input v-model="form.phone" type="tel" class="input" placeholder="08xxxxxxxxxx" required />
          </div>
          <div v-if="!isEdit">
            <label class="label">Password <span class="text-red-500">*</span></label>
            <input v-model="form.password" type="password" class="input" placeholder="Min. 6 karakter" required />
          </div>
          <div>
            <label class="label">NIK</label>
            <input v-model="form.nik" type="text" class="input" placeholder="16 digit NIK" maxlength="16" />
          </div>
        </div>
      </div>

      <!-- Data Hunian -->
      <div class="card p-6 space-y-4">
        <h3 class="font-semibold text-gray-700 border-b pb-2">Data Hunian</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="label">Nomor Rumah <span class="text-red-500">*</span></label>
            <input v-model="form.nomor_rumah" type="text" class="input" placeholder="cth: E-12" required />
          </div>
          <div>
            <label class="label">RT</label>
            <input v-model="form.rt" type="text" class="input" placeholder="001" />
          </div>
          <div>
            <label class="label">RW</label>
            <input v-model="form.rw" type="text" class="input" placeholder="001" />
          </div>
          <div>
            <label class="label">Status Hunian <span class="text-red-500">*</span></label>
            <select v-model="form.status_hunian" class="input" required>
              <option value="milik">Milik Sendiri</option>
              <option value="sewa">Sewa</option>
              <option value="kontrak">Kontrak</option>
            </select>
          </div>
          <div>
            <label class="label">Tanggal Pindah</label>
            <input v-model="form.tanggal_pindah" type="date" class="input" />
          </div>
        </div>
        <div>
          <label class="label">Alamat Asal</label>
          <textarea v-model="form.alamat_asal" class="input" rows="2" placeholder="Alamat asal sebelum pindah ke sini" />
        </div>
      </div>

      <!-- Anggota Keluarga -->
      <div class="card p-6 space-y-4">
        <div class="flex items-center justify-between border-b pb-2">
          <h3 class="font-semibold text-gray-700">Anggota Keluarga</h3>
          <button type="button" @click="addAnggota" class="btn-secondary btn-sm">
            <PlusIcon class="w-4 h-4" />
            Tambah Anggota
          </button>
        </div>

        <div v-if="form.anggota_keluarga.length === 0" class="text-center py-6 text-gray-400 text-sm">
          Belum ada anggota keluarga. Klik "Tambah Anggota" untuk menambahkan.
        </div>

        <div
          v-for="(anggota, i) in form.anggota_keluarga"
          :key="i"
          class="border border-gray-200 rounded-xl p-4 relative space-y-3"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-primary-700">Anggota {{ i + 1 }}</span>
            <button type="button" @click="removeAnggota(i)" class="text-red-400 hover:text-red-600">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="label">Nama Lengkap <span class="text-red-500">*</span></label>
              <input v-model="anggota.nama" type="text" class="input" required />
            </div>
            <div>
              <label class="label">Hubungan <span class="text-red-500">*</span></label>
              <select v-model="anggota.hubungan" class="input" required>
                <option value="kepala_keluarga">Kepala Keluarga</option>
                <option value="istri">Istri</option>
                <option value="anak">Anak</option>
                <option value="orang_tua">Orang Tua</option>
                <option value="saudara">Saudara</option>
                <option value="lainnya">Lainnya</option>
              </select>
            </div>
            <div>
              <label class="label">Jenis Kelamin <span class="text-red-500">*</span></label>
              <select v-model="anggota.jenis_kelamin" class="input" required>
                <option value="laki_laki">Laki-laki</option>
                <option value="perempuan">Perempuan</option>
              </select>
            </div>
            <div>
              <label class="label">Tanggal Lahir</label>
              <input v-model="anggota.tanggal_lahir" type="date" class="input" />
            </div>
            <div>
              <label class="label">NIK</label>
              <input v-model="anggota.nik" type="text" class="input" placeholder="16 digit NIK" maxlength="16" />
            </div>
            <div>
              <label class="label">Pekerjaan</label>
              <input v-model="anggota.pekerjaan" type="text" class="input" />
            </div>
            <div>
              <label class="label">Pendidikan Terakhir</label>
              <select v-model="anggota.pendidikan" class="input">
                <option value="">Pilih Pendidikan</option>
                <option>SD</option><option>SMP</option><option>SMA/SMK</option>
                <option>D3</option><option>S1</option><option>S2</option><option>S3</option>
              </select>
            </div>
            <div>
              <label class="label">Agama</label>
              <select v-model="anggota.agama" class="input">
                <option value="">Pilih Agama</option>
                <option>Islam</option><option>Kristen</option><option>Katolik</option>
                <option>Hindu</option><option>Buddha</option><option>Konghucu</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-red-700 text-sm">
        {{ error }}
      </div>

      <!-- Actions -->
      <div class="flex gap-3 justify-end">
        <RouterLink to="/warga" class="btn-secondary">Batal</RouterLink>
        <button type="submit" class="btn-primary" :disabled="isLoading">
          <span v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
          {{ isLoading ? 'Menyimpan...' : (isEdit ? 'Perbarui Data' : 'Simpan Warga') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { useWargaStore } from '@/stores/warga'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const store = useWargaStore()

const isEdit = computed(() => !!route.params.id)
const isLoading = ref(false)
const error = ref('')

const form = ref({
  name: '',
  phone: '',
  password: '',
  nik: '',
  nomor_rumah: '',
  blok: 'E',
  rt: '',
  rw: '',
  status_hunian: 'milik',
  tanggal_pindah: '',
  alamat_asal: '',
  anggota_keluarga: [],
})

function addAnggota() {
  form.value.anggota_keluarga.push({
    nama: '', hubungan: 'kepala_keluarga', jenis_kelamin: 'laki_laki',
    tanggal_lahir: '', nik: '', pekerjaan: '', pendidikan: '', agama: '',
  })
}

function removeAnggota(index) {
  form.value.anggota_keluarga.splice(index, 1)
}

async function handleSubmit() {
  error.value = ''
  isLoading.value = true
  try {
    if (isEdit.value) {
      await store.update(route.params.id, form.value)
      toast.success('Data warga berhasil diperbarui!')
    } else {
      await store.create(form.value)
      toast.success('Warga baru berhasil ditambahkan!')
    }
    await router.push('/warga')
  } catch (e) {
    const msg = e.response?.data?.message ?? 'Terjadi kesalahan. Coba lagi.'
    error.value = msg
    const errors = e.response?.data?.errors
    if (errors) {
      error.value = Object.values(errors).flat().join(' ')
    }
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  if (isEdit.value) {
    const warga = await store.fetchOne(route.params.id)
    form.value = {
      name: warga.user?.name ?? '',
      phone: warga.user?.phone ?? '',
      password: '',
      nik: warga.nik ?? '',
      nomor_rumah: warga.nomor_rumah,
      blok: warga.blok,
      rt: warga.rt ?? '',
      rw: warga.rw ?? '',
      status_hunian: warga.status_hunian,
      tanggal_pindah: warga.tanggal_pindah ?? '',
      alamat_asal: warga.alamat_asal ?? '',
      anggota_keluarga: warga.anggota_keluarga ?? [],
    }
  }
})
</script>
