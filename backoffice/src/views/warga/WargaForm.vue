<template>
  <div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4 bg-white rounded-xl px-5 py-4 shadow-sm border border-gray-100">
      <RouterLink to="/warga" class="text-gray-400 hover:text-gray-700">
        <ArrowLeftIcon class="w-5 h-5" />
      </RouterLink>
      <div class="flex-1">
        <h1 class="text-lg font-semibold text-gray-800">
          {{ isEdit ? 'Edit Data Warga' : 'Tambah Data Warga berdasarkan Kartu Keluarga' }}
        </h1>
        <p class="text-xs text-gray-500 mt-0.5">
          Isi data Kepala Keluarga terlebih dahulu, kemudian tambahkan anggota keluarga yang tertera di KK
        </p>
      </div>
    </div>

    <!-- Stepper Progress -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="flex items-center justify-between">
        <div v-for="(step, i) in steps" :key="i" class="flex items-center flex-1">
          <div class="flex items-center gap-3">
            <div :class="[
              'w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm',
              currentStep > i ? 'bg-primary-700 text-white' :
              currentStep === i ? 'bg-primary-100 text-primary-700 ring-4 ring-primary-50' :
              'bg-gray-100 text-gray-400'
            ]">
              <CheckIcon v-if="currentStep > i" class="w-5 h-5" />
              <span v-else>{{ i + 1 }}</span>
            </div>
            <div class="hidden md:block">
              <p :class="['text-sm font-medium', currentStep >= i ? 'text-gray-800' : 'text-gray-400']">
                {{ step.title }}
              </p>
              <p class="text-xs text-gray-500">{{ step.desc }}</p>
            </div>
          </div>
          <div v-if="i < steps.length - 1" :class="[
            'flex-1 h-0.5 mx-3',
            currentStep > i ? 'bg-primary-700' : 'bg-gray-200'
          ]" />
        </div>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-5">
      <!-- ========= STEP 1: KARTU KELUARGA (KK) ========= -->
      <div v-show="currentStep === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-primary-700 to-primary-800 px-6 py-4">
          <div class="flex items-center gap-3 text-white">
            <IdentificationIcon class="w-6 h-6" />
            <div>
              <h2 class="font-semibold">Data Kartu Keluarga (KK)</h2>
              <p class="text-xs text-primary-100">Informasi sesuai Kartu Keluarga</p>
            </div>
          </div>
        </div>

        <div class="p-6 space-y-5">
          <div>
            <label class="label">Nomor Kartu Keluarga (KK)</label>
            <input
              v-model="form.nomor_kk"
              type="text"
              class="input"
              placeholder="16 digit nomor KK"
              maxlength="16"
              pattern="[0-9]*"
              autocomplete="off"
            />
            <p class="text-xs text-gray-500 mt-1">Sesuai dengan yang tercantum di Kartu Keluarga</p>
          </div>

          <div class="border-t pt-5">
            <h3 class="font-medium text-gray-800 mb-3 text-sm">Data Kepala Keluarga</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label class="label">Nama Lengkap <span class="text-red-500">*</span></label>
                <input v-model="form.name" type="text" class="input" placeholder="Sesuai KTP" required autocomplete="off" />
              </div>
              <div>
                <label class="label">NIK Kepala Keluarga</label>
                <input
                  v-model="form.nik"
                  type="text"
                  class="input"
                  placeholder="16 digit NIK"
                  maxlength="16"
                  autocomplete="off"
                />
              </div>
              <div>
                <label class="label">Tempat Lahir</label>
                <input v-model="form.tempat_lahir" type="text" class="input" placeholder="Kota tempat lahir" autocomplete="off" />
              </div>
              <div>
                <label class="label">Tanggal Lahir</label>
                <DatePickerField v-model="form.tanggal_lahir" placeholder="Pilih tanggal lahir" :max-date="new Date()" />
              </div>
              <!-- Honeypot fields untuk distract Chrome autofill -->
              <div style="position:absolute;left:-9999px;opacity:0;" aria-hidden="true">
                <input type="text" name="fakeusernameremembered" tabindex="-1" autocomplete="username" />
                <input type="password" name="fakepasswordremembered" tabindex="-1" autocomplete="current-password" />
              </div>

              <div>
                <label class="label">Nomor Telepon (untuk login) <span class="text-red-500">*</span></label>
                <input
                  v-model="form.phone"
                  type="tel"
                  class="input"
                  placeholder="08xxxxxxxxxx"
                  required
                  :name="`phone-${randomKey}`"
                  autocomplete="off"
                  readonly
                  @focus="$event.target.removeAttribute('readonly')"
                />
              </div>
              <div>
                <label class="label">
                  Password Login
                  <span v-if="!isEdit" class="text-red-500">*</span>
                  <span v-else class="text-xs text-gray-500 font-normal">(kosongkan jika tidak ingin diubah)</span>
                </label>
                <input
                  v-model="form.password"
                  type="password"
                  class="input"
                  :placeholder="isEdit ? 'Biarkan kosong untuk tidak ubah password' : 'Min. 6 karakter'"
                  :required="!isEdit"
                  minlength="6"
                  :name="`pwd-${randomKey}`"
                  autocomplete="new-password"
                  readonly
                  @focus="$event.target.removeAttribute('readonly')"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ========= STEP 2: DATA HUNIAN ========= -->
      <div v-show="currentStep === 1" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-primary-700 to-primary-800 px-6 py-4">
          <div class="flex items-center gap-3 text-white">
            <HomeIcon class="w-6 h-6" />
            <div>
              <h2 class="font-semibold">Data Hunian</h2>
              <p class="text-xs text-primary-100">Lokasi rumah di Perumahan Griya Pesona Madani</p>
            </div>
          </div>
        </div>

        <div class="p-6 space-y-5">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="label">Nomor Rumah <span class="text-red-500">*</span></label>
              <input v-model="form.nomor_rumah" type="text" class="input" placeholder="cth: E-12" required />
            </div>
            <div>
              <label class="label">RT</label>
              <input v-model="form.rt" type="text" class="input" placeholder="001" maxlength="3" />
            </div>
            <div>
              <label class="label">RW</label>
              <input v-model="form.rw" type="text" class="input" placeholder="001" maxlength="3" />
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
              <DatePickerField v-model="form.tanggal_pindah" placeholder="Pilih tanggal pindah" />
            </div>
          </div>

          <!-- Status Uang Kedukaan -->
          <div class="mt-2 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <label class="flex items-start gap-3 cursor-pointer">
              <input
                v-model="form.uang_kedukaan_dibayar"
                type="checkbox"
                class="mt-1 w-4 h-4 rounded text-primary-700 focus:ring-primary-500"
              />
              <div>
                <p class="text-sm font-medium text-gray-800">
                  Warga lama — Uang Kedukaan sudah pernah dibayar
                </p>
                <p class="text-xs text-gray-600 mt-0.5">
                  Centang jika warga ini sudah tinggal lama dan sudah pernah membayar uang kedukaan Rp 20.000.
                  Jika tidak dicentang, sistem akan otomatis membuat tagihan kedukaan untuk warga baru.
                </p>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- ========= STEP 3: ANGGOTA KELUARGA ========= -->
      <div v-show="currentStep === 2" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-primary-700 to-primary-800 px-6 py-4">
          <div class="flex items-center justify-between text-white">
            <div class="flex items-center gap-3">
              <UsersIcon class="w-6 h-6" />
              <div>
                <h2 class="font-semibold">Anggota Keluarga</h2>
                <p class="text-xs text-primary-100">Tambahkan anggota keluarga yang tertera di KK</p>
              </div>
            </div>
            <button
              type="button"
              @click="addAnggota"
              class="bg-white text-primary-700 px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-primary-50 flex items-center gap-1"
            >
              <PlusIcon class="w-4 h-4" /> Tambah
            </button>
          </div>
        </div>

        <div class="p-6 space-y-4">
          <div
            v-if="form.anggota_keluarga.length === 0"
            class="text-center py-10 border-2 border-dashed border-gray-200 rounded-xl"
          >
            <UserPlusIcon class="w-10 h-10 mx-auto text-gray-300 mb-2" />
            <p class="text-sm text-gray-500">Belum ada anggota keluarga ditambahkan</p>
            <button
              type="button"
              @click="addAnggota"
              class="mt-3 text-sm text-primary-700 hover:underline font-medium"
            >
              + Tambah Anggota Keluarga Pertama
            </button>
          </div>

          <div
            v-for="(anggota, i) in form.anggota_keluarga"
            :key="i"
            class="border border-gray-200 rounded-xl p-5 bg-gray-50 relative"
          >
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-sm font-bold">
                  {{ i + 1 }}
                </div>
                <span class="text-sm font-semibold text-gray-800">Anggota Keluarga {{ i + 1 }}</span>
              </div>
              <button
                type="button"
                @click="removeAnggota(i)"
                class="text-red-400 hover:text-red-600 p-1"
                title="Hapus anggota"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label class="label">Nama Lengkap <span class="text-red-500">*</span></label>
                <input v-model="anggota.nama" type="text" class="input bg-white" required />
              </div>
              <div>
                <label class="label">NIK</label>
                <input
                  v-model="anggota.nik"
                  type="text"
                  class="input bg-white"
                  maxlength="16"
                  placeholder="16 digit NIK"
                />
              </div>
              <div>
                <label class="label">Tanggal Lahir</label>
                <DatePickerField v-model="anggota.tanggal_lahir" placeholder="Pilih tanggal lahir" :max-date="new Date()" />
              </div>
              <div>
                <label class="label">Hubungan dengan KK <span class="text-red-500">*</span></label>
                <select v-model="anggota.hubungan" class="input bg-white" required>
                  <option value="istri">Istri</option>
                  <option value="anak">Anak</option>
                  <option value="orang_tua">Orang Tua</option>
                  <option value="saudara">Saudara</option>
                  <option value="lainnya">Lainnya</option>
                </select>
              </div>
              <div>
                <label class="label">Jenis Kelamin <span class="text-red-500">*</span></label>
                <select v-model="anggota.jenis_kelamin" class="input bg-white" required>
                  <option value="laki_laki">Laki-laki</option>
                  <option value="perempuan">Perempuan</option>
                </select>
              </div>
              <div>
                <label class="label">Pekerjaan</label>
                <input v-model="anggota.pekerjaan" type="text" class="input bg-white" />
              </div>
              <div>
                <label class="label">Pendidikan Terakhir</label>
                <select v-model="anggota.pendidikan" class="input bg-white">
                  <option value="">- Pilih -</option>
                  <option>Belum Sekolah</option>
                  <option>SD</option>
                  <option>SMP</option>
                  <option>SMA/SMK</option>
                  <option>D3</option>
                  <option>S1</option>
                  <option>S2</option>
                  <option>S3</option>
                </select>
              </div>
              <div>
                <label class="label">Agama</label>
                <select v-model="anggota.agama" class="input bg-white">
                  <option value="">- Pilih -</option>
                  <option>Islam</option>
                  <option>Kristen</option>
                  <option>Katolik</option>
                  <option>Hindu</option>
                  <option>Buddha</option>
                  <option>Konghucu</option>
                </select>
              </div>
              <div>
                <label class="label">Status Perkawinan</label>
                <select v-model="anggota.status_perkawinan" class="input bg-white">
                  <option value="">- Pilih -</option>
                  <option>Belum Kawin</option>
                  <option>Kawin</option>
                  <option>Cerai Hidup</option>
                  <option>Cerai Mati</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-red-700 text-sm flex items-start gap-2">
        <ExclamationCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5" />
        <span>{{ error }}</span>
      </div>

      <!-- Navigation Buttons -->
      <div class="flex items-center justify-between bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
        <button
          v-if="currentStep > 0"
          type="button"
          @click="currentStep--"
          class="btn-secondary"
        >
          <ArrowLeftIcon class="w-4 h-4" /> Sebelumnya
        </button>
        <RouterLink v-else to="/warga" class="btn-secondary">
          Batal
        </RouterLink>

        <div class="text-sm text-gray-500">
          Langkah {{ currentStep + 1 }} dari {{ steps.length }}
        </div>

        <button
          v-if="currentStep < steps.length - 1"
          type="button"
          @click="nextStep"
          class="btn-primary"
        >
          Lanjut <ArrowRightIcon class="w-4 h-4" />
        </button>
        <button
          v-else
          type="submit"
          class="btn-primary"
          :disabled="isLoading"
        >
          <span v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
          <CheckIcon v-else class="w-4 h-4" />
          {{ isLoading ? 'Menyimpan...' : (isEdit ? 'Perbarui Data' : 'Simpan Data Warga') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeftIcon, ArrowRightIcon, PlusIcon, TrashIcon, CheckIcon,
  IdentificationIcon, HomeIcon, UsersIcon, UserPlusIcon, ExclamationCircleIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { useWargaStore } from '@/stores/warga'
import DatePickerField from '@/components/DatePickerField.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const store = useWargaStore()

const isEdit = computed(() => !!route.params.id)
const isLoading = ref(false)
const error = ref('')
const currentStep = ref(0)
// Random key untuk attribute name input → bikin Chrome bingung & tidak autofill
const randomKey = Math.random().toString(36).substring(2, 10)

const steps = [
  { title: 'Data KK', desc: 'Kartu Keluarga & Kepala Keluarga' },
  { title: 'Data Hunian', desc: 'Lokasi rumah' },
  { title: 'Anggota Keluarga', desc: 'Tambah anggota dari KK' },
]

const form = ref({
  nomor_kk: '',
  name: '',
  phone: '',
  password: '',
  nik: '',
  tanggal_lahir: '',
  tempat_lahir: '',
  nomor_rumah: '',
  blok: 'E',
  rt: '',
  rw: '',
  status_hunian: 'milik',
  tanggal_pindah: '',
  uang_kedukaan_dibayar: false,
  anggota_keluarga: [],
})

// Helper: format ISO datetime ke YYYY-MM-DD (untuk input date HTML/DatePicker)
function isoToDate(iso) {
  if (!iso) return ''
  // Sudah dalam format YYYY-MM-DD
  if (/^\d{4}-\d{2}-\d{2}$/.test(iso)) return iso
  const d = new Date(iso)
  if (isNaN(d.getTime())) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function nextStep() {
  error.value = ''
  // Validasi step 1
  if (currentStep.value === 0) {
    if (!form.value.name || !form.value.phone || (!isEdit.value && !form.value.password)) {
      error.value = 'Nama, Nomor Telepon, dan Password wajib diisi.'
      return
    }
    if (form.value.phone.length < 10) {
      error.value = 'Nomor telepon minimal 10 digit.'
      return
    }
  }
  // Validasi step 2
  if (currentStep.value === 1) {
    if (!form.value.nomor_rumah) {
      error.value = 'Nomor rumah wajib diisi.'
      return
    }
  }
  currentStep.value++
}

function addAnggota() {
  form.value.anggota_keluarga.push({
    nama: '',
    hubungan: 'istri',
    jenis_kelamin: 'perempuan',
    tanggal_lahir: '',
    nik: '',
    pekerjaan: '',
    pendidikan: '',
    agama: '',
    status_perkawinan: '',
  })
}

function removeAnggota(index) {
  if (!confirm('Hapus anggota keluarga ini?')) return
  form.value.anggota_keluarga.splice(index, 1)
}

async function handleSubmit() {
  error.value = ''
  isLoading.value = true
  try {
    const payload = { ...form.value }
    // Hapus field yang kosong
    if (!payload.nik) delete payload.nik
    if (!payload.nomor_kk) delete payload.nomor_kk
    if (!payload.tanggal_pindah) delete payload.tanggal_pindah
    if (!payload.tanggal_lahir) delete payload.tanggal_lahir
    if (!payload.tempat_lahir) delete payload.tempat_lahir
    // Password optional di edit — hanya kirim kalau diisi
    if (isEdit.value && !payload.password) delete payload.password

    if (isEdit.value) {
      await store.update(route.params.id, payload)
      toast.success('Data warga berhasil diperbarui!')
    } else {
      await store.create(payload)
      toast.success('Data warga berhasil ditambahkan!')
    }
    await router.push('/warga')
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) {
      error.value = Object.values(errors).flat().join(' ')
    } else {
      error.value = e.response?.data?.message ?? 'Terjadi kesalahan. Coba lagi.'
    }
    // Kembali ke step yang relevan jika ada error
    if (error.value.toLowerCase().includes('phone') || error.value.toLowerCase().includes('telepon')) {
      currentStep.value = 0
    }
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  if (isEdit.value) {
    const warga = await store.fetchOne(route.params.id)
    form.value = {
      nomor_kk: warga.nomor_kk ?? '',
      name: warga.user?.name ?? '',
      phone: warga.user?.phone ?? '',
      password: '',
      nik: warga.nik ?? '',
      tanggal_lahir: isoToDate(warga.user?.tanggal_lahir),
      tempat_lahir: warga.user?.tempat_lahir ?? '',
      nomor_rumah: warga.nomor_rumah ?? '',
      blok: warga.blok ?? 'E',
      rt: warga.rt ?? '',
      rw: warga.rw ?? '',
      status_hunian: warga.status_hunian ?? 'milik',
      tanggal_pindah: isoToDate(warga.tanggal_pindah),
      uang_kedukaan_dibayar: !!warga.uang_kedukaan_dibayar,
      // Map anggota keluarga: preserve id + semua field, convert tanggal_lahir
      anggota_keluarga: (warga.anggota_keluarga ?? []).map((a) => ({
        id: a.id,
        nama: a.nama ?? '',
        hubungan: a.hubungan ?? 'anak',
        jenis_kelamin: a.jenis_kelamin ?? 'laki_laki',
        tanggal_lahir: isoToDate(a.tanggal_lahir),
        nik: a.nik ?? '',
        pekerjaan: a.pekerjaan ?? '',
        pendidikan: a.pendidikan ?? '',
        agama: a.agama ?? '',
        status_perkawinan: a.status_perkawinan ?? '',
      })),
    }
  }
})
</script>
