<template>
  <div class="max-w-5xl mx-auto space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-gray-800">⚙️ Pengaturan Aplikasi</h1>
      <p class="text-sm text-gray-500">Kustomisasi branding, tema, dan informasi aplikasi. Cocok untuk white-label.</p>
    </div>

    <!-- Tab -->
    <div class="flex gap-2 flex-wrap border-b border-gray-200">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="activeTab = tab.value"
        :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
          activeTab === tab.value
            ? 'border-primary-700 text-primary-700'
            : 'border-transparent text-gray-600 hover:text-gray-800']"
      >
        {{ tab.icon }} {{ tab.label }}
      </button>
    </div>

    <!-- ===== BRANDING ===== -->
    <div v-if="activeTab === 'branding'" class="space-y-4">
      <!-- Logo -->
      <div class="card p-5">
        <h3 class="font-semibold mb-3">Logo Aplikasi</h3>
        <div class="flex items-start gap-4">
          <div class="w-24 h-24 bg-gray-50 rounded-lg border flex items-center justify-center p-2">
            <img v-if="form.logo_url" :src="getLogoUrl(form.logo_url)" class="max-w-full max-h-full object-contain" />
            <span v-else class="text-gray-300 text-xs">No logo</span>
          </div>
          <div class="flex-1">
            <input
              type="file"
              accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml"
              @change="onLogoUpload"
              :disabled="uploading"
              class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
            />
            <p class="text-xs text-gray-400 mt-2">Format: JPG, PNG, WebP, SVG. Max 2MB. Recommend ukuran square (1:1).</p>
            <p v-if="uploading" class="text-xs text-primary-600 mt-1">Mengupload...</p>
          </div>
        </div>
      </div>

      <!-- Branding fields -->
      <div class="card p-5 space-y-4">
        <h3 class="font-semibold">Nama & Teks Branding</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Nama Aplikasi <span class="text-xs text-gray-400">(document title)</span></label>
            <input v-model="form.app_name" class="input" />
          </div>
          <div>
            <label class="label">Footer Login</label>
            <input v-model="form.footer_text" class="input" />
          </div>
          <div>
            <label class="label">Judul Sidebar (Baris 1)</label>
            <input v-model="form.brand_title" class="input" />
          </div>
          <div>
            <label class="label">Sub-judul Sidebar (Baris 2)</label>
            <input v-model="form.brand_subtitle" class="input" />
          </div>
          <div class="md:col-span-2">
            <label class="label">Subtitle di Login</label>
            <input v-model="form.login_subtitle" class="input" />
          </div>
        </div>
      </div>
    </div>

    <!-- ===== THEME / WARNA ===== -->
    <div v-if="activeTab === 'theme'" class="space-y-4">
      <div class="card p-5 space-y-4">
        <h3 class="font-semibold">Warna Tema</h3>
        <p class="text-xs text-gray-500">Sesuaikan warna utama aplikasi. Perubahan langsung terlihat di sidebar & tombol.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <ColorPicker
            label="Warna Utama (Primary)"
            description="Warna utama tombol, link, accent"
            v-model="form.theme_primary"
          />
          <ColorPicker
            label="Primary Gelap (Sidebar)"
            description="Warna background sidebar"
            v-model="form.theme_primary_dark"
          />
          <ColorPicker
            label="Primary Terang"
            description="Background light/hover"
            v-model="form.theme_primary_light"
          />
          <ColorPicker
            label="Warna Aksen"
            description="Untuk highlight notifikasi"
            v-model="form.theme_accent"
          />
        </div>

        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
          <p class="text-xs font-semibold text-gray-600 mb-2">Preview Warna:</p>
          <div class="flex gap-2 flex-wrap">
            <div class="rounded-lg px-4 py-2 text-white text-sm font-medium" :style="{ background: form.theme_primary }">
              Primary
            </div>
            <div class="rounded-lg px-4 py-2 text-white text-sm font-medium" :style="{ background: form.theme_primary_dark }">
              Primary Dark
            </div>
            <div class="rounded-lg px-4 py-2 text-sm font-medium" :style="{ background: form.theme_primary_light, color: form.theme_primary_dark }">
              Primary Light
            </div>
            <div class="rounded-lg px-4 py-2 text-sm font-medium text-white" :style="{ background: form.theme_accent }">
              Accent
            </div>
          </div>
        </div>

        <!-- Preset themes -->
        <div class="mt-4">
          <p class="text-xs font-semibold text-gray-600 mb-2">Preset Tema:</p>
          <div class="flex gap-2 flex-wrap">
            <button
              v-for="preset in presets"
              :key="preset.name"
              @click="applyPreset(preset)"
              class="rounded-lg px-3 py-1.5 text-xs border border-gray-200 hover:bg-gray-50 flex items-center gap-1.5"
            >
              <span class="w-3 h-3 rounded-full" :style="{ background: preset.theme_primary }"></span>
              {{ preset.name }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== PAGE / HALAMAN ===== -->
    <div v-if="activeTab === 'pages'" class="space-y-4">
      <div class="card p-5 space-y-4">
        <h3 class="font-semibold">Custom Judul Halaman</h3>
        <p class="text-xs text-gray-500">Ubah label tiap halaman di sidebar & header. Bisa untuk lokalisasi/branding.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="page in pageFields" :key="page.key">
            <label class="label">{{ page.label }}</label>
            <input v-model="form[page.key]" class="input" />
          </div>
        </div>
      </div>
    </div>

    <!-- ===== GENERAL ===== -->
    <div v-if="activeTab === 'general'" class="space-y-4">
      <div class="card p-5 space-y-4">
        <h3 class="font-semibold">Pengaturan Umum</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Nominal IPL Bulanan (Rp)</label>
            <input v-model="form.ipl_amount" type="number" class="input" />
          </div>
          <div>
            <label class="label">Nominal Uang Kedukaan (Rp)</label>
            <input v-model="form.kedukaan_amount" type="number" class="input" />
          </div>
          <div class="md:col-span-2">
            <label class="label">Biaya Admin Transaksi (Rp)</label>
            <input v-model="form.biaya_admin" type="number" class="input" placeholder="4500" />
            <p class="text-xs text-gray-400 mt-1">
              Fee Midtrans yang dibebankan ke warga (bukan admin). Rekomendasi: <strong>4500</strong> untuk cover
              VA/QRIS/GoPay. Set <strong>0</strong> kalau mau admin yang tanggung.
            </p>
          </div>
          <div class="md:col-span-2">
            <label class="label">Nomor WhatsApp Admin</label>
            <input v-model="form.admin_whatsapp" class="input" placeholder="6281234567890" />
            <p class="text-xs text-gray-400 mt-1">Format internasional tanpa "+". Cth: 6281234567890</p>
          </div>
        </div>

        <!-- Info + Apply nominal ke tagihan belum_bayar yang sudah ada -->
        <div class="border-t pt-4 mt-2 bg-yellow-50 -mx-5 -mb-5 px-5 py-4 rounded-b-xl">
          <div class="flex items-start gap-3">
            <span class="text-xl">⚠️</span>
            <div class="flex-1">
              <p class="text-sm font-semibold text-yellow-800">Tagihan yang sudah ada TIDAK otomatis update</p>
              <p class="text-xs text-yellow-700 mt-1">
                Setelah simpan nominal baru, tagihan IPL/Kedukaan yang sudah di-generate sebelumnya
                tetap pakai harga lama. Klik tombol di bawah untuk update nominal SEMUA tagihan
                yang masih <strong>"Belum Bayar"</strong> dengan harga baru.
              </p>
              <button
                @click="applyNominalKeTagihan"
                class="mt-3 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm rounded-lg font-medium flex items-center gap-2"
                :disabled="applying"
              >
                <span v-if="applying" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                🔄 Apply Nominal Baru ke Tagihan Belum Bayar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tombol Save -->
    <div class="sticky bottom-4 bg-white rounded-xl shadow-lg border border-gray-100 px-5 py-3 flex items-center justify-between">
      <p class="text-sm text-gray-600">
        <span v-if="hasChanges" class="text-orange-600 font-semibold">⚠ Ada perubahan belum disimpan</span>
        <span v-else>✓ Semua sudah disimpan</span>
      </p>
      <div class="flex gap-2">
        <button @click="reset" class="btn-secondary" :disabled="!hasChanges">Reset</button>
        <button @click="saveAll" class="btn-primary" :disabled="!hasChanges || saving">
          <span v-if="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
          {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h, defineComponent } from 'vue'
import { useToast } from 'vue-toastification'
import { useSettingsStore } from '@/stores/settings'
import api from '@/services/api'

const toast = useToast()
const settings = useSettingsStore()

const activeTab = ref('branding')
const tabs = [
  { value: 'branding', label: 'Branding', icon: '🎨' },
  { value: 'theme', label: 'Tema Warna', icon: '🌈' },
  { value: 'pages', label: 'Halaman', icon: '📄' },
  { value: 'general', label: 'Umum', icon: '⚙️' },
]

const pageFields = [
  { key: 'page_dashboard_title', label: 'Dashboard' },
  { key: 'page_warga_title', label: 'Data Warga' },
  { key: 'page_tagihan_title', label: 'Tagihan IPL' },
  { key: 'page_pembayaran_title', label: 'Pembayaran' },
  { key: 'page_pengaduan_title', label: 'Pengaduan' },
  { key: 'page_berita_title', label: 'Berita' },
  { key: 'page_laporan_title', label: 'Laporan' },
]

const presets = [
  { name: 'Hijau (Default)', theme_primary: '#388E3C', theme_primary_dark: '#1B5E20', theme_primary_light: '#E8F5E9', theme_accent: '#FFC107' },
  { name: 'Biru', theme_primary: '#1976D2', theme_primary_dark: '#0D47A1', theme_primary_light: '#E3F2FD', theme_accent: '#FF9800' },
  { name: 'Merah', theme_primary: '#D32F2F', theme_primary_dark: '#B71C1C', theme_primary_light: '#FFEBEE', theme_accent: '#FFC107' },
  { name: 'Ungu', theme_primary: '#7B1FA2', theme_primary_dark: '#4A148C', theme_primary_light: '#F3E5F5', theme_accent: '#FFC107' },
  { name: 'Oranye', theme_primary: '#F57C00', theme_primary_dark: '#E65100', theme_primary_light: '#FFF3E0', theme_accent: '#1976D2' },
  { name: 'Teal', theme_primary: '#00796B', theme_primary_dark: '#004D40', theme_primary_light: '#E0F2F1', theme_accent: '#FFC107' },
]

const form = ref({ ...settings.settings })
const original = ref({ ...settings.settings })
const saving = ref(false)
const uploading = ref(false)
const applying = ref(false)

async function applyNominalKeTagihan() {
  const msg = `Update nominal di SEMUA tagihan "Belum Bayar"?\n\n` +
    `IPL Bulanan → Rp ${Number(form.value.ipl_amount || 0).toLocaleString('id-ID')}\n` +
    `Uang Kedukaan → Rp ${Number(form.value.kedukaan_amount || 0).toLocaleString('id-ID')}\n\n` +
    `Pastikan nominal di atas sudah benar dan sudah di-Simpan dulu sebelum klik ini.`
  if (!confirm(msg)) return
  applying.value = true
  try {
    const res = await api.post('/admin/settings/update-tagihan-nominal')
    toast.success(res.data?.message || 'Nominal tagihan berhasil di-update.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Gagal update nominal.')
  } finally {
    applying.value = false
  }
}

const hasChanges = computed(() => {
  return JSON.stringify(form.value) !== JSON.stringify(original.value)
})

function getLogoUrl(url) {
  if (!url) return ''
  if (url.startsWith('http') || url.startsWith('/logo')) return url
  // Relative path from API, prepend backend URL
  const apiBase = import.meta.env.VITE_API_URL || ''
  const host = apiBase.replace(/\/api\/v\d+\/?$/, '')
  return `${host}${url}`
}

async function onLogoUpload(e) {
  const file = e.target.files[0]
  if (!file) return

  // Validasi client-side dulu biar gak waste request
  const maxBytes = 2 * 1024 * 1024
  if (file.size > maxBytes) {
    toast.error(`Ukuran logo terlalu besar (${(file.size / 1024 / 1024).toFixed(2)} MB). Max 2 MB.`)
    e.target.value = ''
    return
  }

  uploading.value = true
  try {
    const url = await settings.uploadLogo(file)
    form.value.logo_url = url
    original.value.logo_url = url
    toast.success('Logo berhasil diupload!')
  } catch (err) {
    // Tampilkan detail error supaya gampang diagnose
    const status = err.response?.status
    const msg = err.response?.data?.message
    const errors = err.response?.data?.errors
    let detail = msg ?? err.message
    if (errors && typeof errors === 'object') {
      const firstField = Object.entries(errors)[0]
      if (firstField && Array.isArray(firstField[1])) {
        detail = firstField[1][0]
      }
    }
    toast.error(`Gagal upload logo${status ? ` (${status})` : ''}: ${detail}`, { timeout: 6000 })
    console.error('Logo upload error:', err.response?.data || err)
  } finally {
    uploading.value = false
    e.target.value = ''
  }
}

function applyPreset(preset) {
  form.value.theme_primary = preset.theme_primary
  form.value.theme_primary_dark = preset.theme_primary_dark
  form.value.theme_primary_light = preset.theme_primary_light
  form.value.theme_accent = preset.theme_accent
}

function reset() {
  form.value = { ...original.value }
}

async function saveAll() {
  saving.value = true
  try {
    // Filter hanya field yang berubah
    const changes = {}
    for (const key in form.value) {
      if (form.value[key] !== original.value[key]) {
        changes[key] = form.value[key]
      }
    }
    if (Object.keys(changes).length > 0) {
      await settings.updateBulk(changes)
    }
    original.value = { ...form.value }
    toast.success('Pengaturan berhasil disimpan!')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Gagal menyimpan.')
  } finally {
    saving.value = false
  }
}

// === Inline ColorPicker component ===
const ColorPicker = defineComponent({
  props: ['label', 'description', 'modelValue'],
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    return () => h('div', [
      h('label', { class: 'label' }, props.label),
      h('div', { class: 'flex items-center gap-2' }, [
        h('input', {
          type: 'color',
          value: props.modelValue,
          onInput: (e) => emit('update:modelValue', e.target.value),
          class: 'w-12 h-10 rounded cursor-pointer border border-gray-300',
        }),
        h('input', {
          type: 'text',
          value: props.modelValue,
          onInput: (e) => emit('update:modelValue', e.target.value),
          class: 'input flex-1 font-mono text-sm',
          placeholder: '#388E3C',
        }),
      ]),
      props.description && h('p', { class: 'text-xs text-gray-400 mt-1' }, props.description),
    ])
  },
})

onMounted(async () => {
  // Load public settings dulu (untuk theme apply) + all settings (untuk nominal IPL dll)
  await settings.loadPublicSettings()
  await settings.loadAllSettings()
  form.value = { ...settings.settings }
  original.value = { ...settings.settings }
})
</script>
