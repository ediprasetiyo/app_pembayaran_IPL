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
            <p class="text-xs text-gray-400 mt-2">Format: PNG (transparent recommended), JPG, WebP, SVG. Max 2MB.</p>
            <div class="text-xs text-gray-500 mt-2 space-y-0.5 bg-blue-50 border border-blue-200 rounded-md p-2">
              <p class="font-semibold text-blue-800">📐 Rekomendasi ukuran logo:</p>
              <p>• <strong>512 × 512 px</strong> (square, paling fleksibel) — direkomendasikan</p>
              <p>• <strong>1024 × 1024 px</strong> (high-res untuk display besar)</p>
              <p>• Aspect ratio 1:1 (persegi) — supaya tampil rapi di splash, header, sidebar, dan launcher icon</p>
              <p>• Background transparan (PNG) supaya menyatu dengan tema warna apapun</p>
              <p class="text-blue-700 italic">Catatan: logo dengan teks panjang (banner-style) akan terpotong di Android 12+ splash screen circular mask.</p>
            </div>
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

    <!-- ===== NOTIFIKASI ===== -->
    <div v-if="activeTab === 'notif'" class="space-y-4">
      <div class="card p-5 space-y-4">
        <div>
          <h3 class="font-semibold">🔔 Template Notifikasi</h3>
          <p class="text-xs text-gray-500 mt-1">
            Kustomisasi judul + pesan notifikasi yang dikirim ke warga / admin via Push Notification (FCM) & in-app.
            Pakai placeholder seperti <code class="bg-gray-100 px-1 rounded">{nama}</code>,
            <code class="bg-gray-100 px-1 rounded">{bulan}</code>,
            <code class="bg-gray-100 px-1 rounded">{nominal}</code> — akan otomatis di-replace saat kirim.
          </p>
        </div>

        <!-- Pilih target test recipient -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 flex items-center gap-3 flex-wrap">
          <span class="text-xs font-semibold text-purple-900">🎯 Test kirim ke:</span>
          <select v-model.number="testTargetUserId" class="input flex-1 min-w-[200px] text-sm">
            <option :value="null">Akun saya (yang login skrg)</option>
            <option v-for="u in usersWithFcm" :key="u.id" :value="u.id">
              {{ u.name }} ({{ u.phone }}) — {{ u.role }}
            </option>
          </select>
          <p class="text-xs text-purple-700">User harus sudah login mobile & izinkan notifikasi.</p>
        </div>

        <!-- Placeholder helper -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs">
          <p class="font-semibold text-blue-900 mb-1">📌 Placeholder yang tersedia:</p>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-1 text-blue-800">
            <div v-for="(desc, key) in placeholders" :key="key">
              <code class="bg-white px-1.5 py-0.5 rounded font-semibold">{{ '{' + key + '}' }}</code>
              <span class="ml-1 text-gray-600">{{ desc }}</span>
            </div>
          </div>
        </div>

        <div v-if="loadingTpl" class="text-center py-8">
          <div class="w-6 h-6 border-2 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto" />
        </div>

        <div v-else class="space-y-5">
          <div v-for="(tpl, key) in notifTemplates" :key="key" class="border-l-4 border-primary-500 bg-gray-50 rounded-r-lg p-4">
            <div class="flex items-center justify-between mb-2 gap-2 flex-wrap">
              <h4 class="font-semibold text-sm">{{ notifLabel(key) }}</h4>
              <div class="flex gap-2">
                <button @click="testNotif(key)"
                  class="text-xs text-blue-600 hover:bg-blue-50 px-2 py-1 rounded border border-blue-200"
                  :disabled="testingKey === key"
                  title="Kirim test ke HP & in-app notif Anda">
                  <span v-if="testingKey === key" class="inline-block w-3 h-3 border-2 border-blue-500 border-t-transparent rounded-full animate-spin align-middle" />
                  🧪 Test Kirim
                </button>
                <button v-if="tpl.custom" @click="resetNotifTemplate(key)" class="text-xs text-red-600 hover:underline">
                  🔄 Reset
                </button>
              </div>
            </div>
            <div class="grid grid-cols-1 gap-3">
              <div>
                <label class="label">Judul Notifikasi</label>
                <input v-model="notifEdit[key].judul" type="text" class="input" maxlength="100"
                  :placeholder="tpl.default.judul" />
                <p class="text-xs text-gray-400 mt-1">Default: <em>{{ tpl.default.judul }}</em></p>
              </div>
              <div>
                <label class="label">Isi Pesan</label>
                <textarea v-model="notifEdit[key].pesan" class="input" rows="2" maxlength="300"
                  :placeholder="tpl.default.pesan" />
                <p class="text-xs text-gray-400 mt-1">Default: <em>{{ tpl.default.pesan }}</em></p>
              </div>
              <!-- Preview -->
              <div class="bg-white rounded-md p-3 border border-gray-200">
                <p class="text-xs text-gray-400 mb-1">👀 Preview (dengan contoh data):</p>
                <p class="font-semibold text-sm">🔔 {{ previewRender(notifEdit[key].judul || tpl.default.judul) }}</p>
                <p class="text-xs text-gray-700 mt-1">{{ previewRender(notifEdit[key].pesan || tpl.default.pesan) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button @click="saveNotifTemplates" class="btn-primary" :disabled="savingTpl">
            <span v-if="savingTpl" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            🔔 Simpan Template Notifikasi
          </button>
        </div>
      </div>
    </div>

    <!-- Tombol Save -->
    <div v-if="activeTab !== 'notif'" class="sticky bottom-4 bg-white rounded-xl shadow-lg border border-gray-100 px-5 py-3 flex items-center justify-between">
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
  { value: 'notif', label: 'Notifikasi', icon: '🔔' },
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

// Notif templates state
const notifTemplates = ref({})
const notifEdit = ref({})
const placeholders = ref({})
const loadingTpl = ref(false)
const savingTpl = ref(false)
const testingKey = ref(null)
const testTargetUserId = ref(null)
const usersWithFcm = ref([])

async function loadUsersWithFcm() {
  try {
    const res = await api.get('/admin/users', { params: { per_page: 100, status: 'aktif' } })
    const all = res.data?.data ?? []
    // Filter user yang punya fcm_token (perlu mobile login)
    usersWithFcm.value = all.filter((u) => u.fcm_token && u.fcm_token.length > 10)
  } catch (_) {}
}

const NOTIF_LABELS = {
  pembayaran_sukses: '💰 Pembayaran Sukses (ke Warga)',
  reminder_tagihan: '⏰ Reminder Tagihan (ke Warga)',
  tagihan_terlambat: '⚠️ Tagihan Terlambat (ke Warga)',
  pengaduan_baru: '📨 Pengaduan Baru (ke Admin)',
  pengaduan_update: '✅ Update Status Pengaduan (ke Warga)',
}

function notifLabel(key) {
  return NOTIF_LABELS[key] ?? key
}

// Sample data untuk preview
const SAMPLE_DATA = {
  nama: 'Pak Edi',
  bulan: 'Mei',
  tahun: '2026',
  nominal: '65.000',
  tanggal: '10/05/2026',
  denda: '3.250',
  judul: 'Sampah belum diangkut',
  status: 'sedang diproses',
  kategori: 'kebersihan',
}

function previewRender(template) {
  let out = template || ''
  for (const [key, value] of Object.entries(SAMPLE_DATA)) {
    out = out.split('{' + key + '}').join(value)
  }
  return out
}

async function loadNotifTemplates() {
  loadingTpl.value = true
  try {
    const res = await api.get('/admin/settings/notif-templates')
    notifTemplates.value = res.data.templates
    placeholders.value = res.data.placeholders
    // Init edit form dengan effective values
    notifEdit.value = {}
    for (const [key, tpl] of Object.entries(res.data.templates)) {
      notifEdit.value[key] = {
        judul: tpl.effective.judul,
        pesan: tpl.effective.pesan,
      }
    }
  } catch (e) {
    toast.error('Gagal load template notifikasi.')
  } finally {
    loadingTpl.value = false
  }
}

async function saveNotifTemplates() {
  savingTpl.value = true
  try {
    await api.post('/admin/settings/notif-templates', { templates: notifEdit.value })
    toast.success('Template notifikasi berhasil disimpan!')
    await loadNotifTemplates()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Gagal simpan template.')
  } finally {
    savingTpl.value = false
  }
}

function resetNotifTemplate(key) {
  const def = notifTemplates.value[key]?.default
  if (!def) return
  notifEdit.value[key] = { judul: def.judul, pesan: def.pesan }
  toast.info('Template di-reset ke default. Klik Simpan untuk konfirmasi.')
}

async function testNotif(event) {
  testingKey.value = event
  try {
    const payload = { event }
    if (testTargetUserId.value) payload.target_user_id = testTargetUserId.value
    const res = await api.post('/admin/settings/notif-test', payload)
    toast.success(res.data?.message ?? 'Test notif terkirim! Cek HP & in-app.', { timeout: 5000 })
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Gagal kirim test.', { timeout: 6000 })
  } finally {
    testingKey.value = null
  }
}

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
  // Load notif templates + daftar user yg bisa di-test (punya FCM token)
  loadNotifTemplates()
  loadUsersWithFcm()
})
</script>
