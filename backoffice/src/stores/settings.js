import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

const DEFAULT_SETTINGS = {
  app_name: 'IPL Griya Pesona Madani',
  brand_title: 'Griya Pesona',
  brand_subtitle: 'Madani Tenjo',
  logo_url: '/logo.png',
  login_subtitle: 'Back Office Admin Panel',
  footer_text: 'Perumahan Griya Pesona Madani Tenjo - Blok E',
  theme_primary: '#388E3C',
  theme_primary_dark: '#1B5E20',
  theme_primary_light: '#E8F5E9',
  theme_accent: '#FFC107',
}

export const useSettingsStore = defineStore('settings', () => {
  // Load dari localStorage jika ada (instant load tanpa flicker)
  const cached = localStorage.getItem('app_settings')
  const initial = cached ? JSON.parse(cached) : DEFAULT_SETTINGS

  const settings = ref({ ...DEFAULT_SETTINGS, ...initial })
  const isLoaded = ref(false)
  const isLoading = ref(false)

  // Computed getters utk akses cepat
  const appName = computed(() => settings.value.app_name)
  const logoUrl = computed(() => settings.value.logo_url)
  const themePrimary = computed(() => settings.value.theme_primary)

  /**
   * Load public settings dari backend & apply theme
   */
  async function loadPublicSettings() {
    if (isLoading.value) return
    isLoading.value = true
    try {
      const res = await api.get('/settings/public')
      const incoming = res.data.settings ?? {}
      settings.value = { ...DEFAULT_SETTINGS, ...incoming }
      localStorage.setItem('app_settings', JSON.stringify(settings.value))
      isLoaded.value = true
      applyTheme()
    } catch (e) {
      console.warn('Failed to load settings, using defaults', e)
      applyTheme()
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Load ALL settings (admin endpoint) — termasuk yang non-public seperti
   * ipl_amount, kedukaan_amount, admin_whatsapp. Untuk halaman Settings.
   */
  async function loadAllSettings() {
    try {
      const res = await api.get('/admin/settings')
      const flat = res.data.flat ?? []
      // Flatten array ke object key-value
      const incoming = {}
      flat.forEach((s) => { incoming[s.key] = s.value })
      settings.value = { ...DEFAULT_SETTINGS, ...settings.value, ...incoming }
      localStorage.setItem('app_settings', JSON.stringify(settings.value))
      applyTheme()
      return incoming
    } catch (e) {
      console.warn('Failed to load all settings', e)
      return {}
    }
  }

  /**
   * Update setting di backend dan reload
   */
  async function updateSetting(key, value) {
    await api.put(`/admin/settings/${key}`, { value })
    settings.value[key] = value
    localStorage.setItem('app_settings', JSON.stringify(settings.value))
    applyTheme()
  }

  /**
   * Update multi setting sekaligus
   */
  async function updateBulk(payload) {
    await api.post('/admin/settings/bulk', { settings: payload })
    Object.assign(settings.value, payload)
    localStorage.setItem('app_settings', JSON.stringify(settings.value))
    applyTheme()
  }

  /**
   * Upload logo file.
   *
   * Strategi: DIRECT UPLOAD ke Cloudinary dari browser → bypass ModSecurity
   * yang block multipart upload di shared LiteSpeed (IDcloudHost).
   *
   * Flow:
   *  1. Minta signature dari backend (signed)
   *  2. Upload file langsung ke api.cloudinary.com (TIDAK lewat backend kita)
   *  3. Kirim URL hasilnya ke backend untuk disimpan
   */
  async function uploadLogo(file) {
    // 1. Minta signature dari backend
    const sigRes = await api.post('/admin/settings/cloudinary-signature', {
      folder: 'ipl/logos',
    })
    const { cloud_name, api_key, timestamp, signature, folder } = sigRes.data

    // 2. Upload langsung ke Cloudinary (bypass backend kita)
    const fd = new FormData()
    fd.append('file', file)
    fd.append('api_key', api_key)
    fd.append('timestamp', timestamp)
    fd.append('signature', signature)
    fd.append('folder', folder)

    const cloudRes = await fetch(
      `https://api.cloudinary.com/v1_1/${cloud_name}/image/upload`,
      { method: 'POST', body: fd },
    )
    if (!cloudRes.ok) {
      const errBody = await cloudRes.text()
      throw new Error('Cloudinary upload gagal: ' + errBody.substring(0, 200))
    }
    const cloudData = await cloudRes.json()
    const url = cloudData.secure_url

    // 3. Simpan URL ke backend kita
    await api.post('/admin/settings/save-logo-url', { url })

    settings.value.logo_url = url
    localStorage.setItem('app_settings', JSON.stringify(settings.value))
    return url
  }

  /**
   * Apply theme color sebagai CSS variables ke :root
   */
  function applyTheme() {
    const root = document.documentElement
    if (settings.value.theme_primary) {
      root.style.setProperty('--theme-primary', settings.value.theme_primary)
    }
    if (settings.value.theme_primary_dark) {
      root.style.setProperty('--theme-primary-dark', settings.value.theme_primary_dark)
    }
    if (settings.value.theme_primary_light) {
      root.style.setProperty('--theme-primary-light', settings.value.theme_primary_light)
    }
    if (settings.value.theme_accent) {
      root.style.setProperty('--theme-accent', settings.value.theme_accent)
    }
    // Update document title
    if (settings.value.app_name) {
      document.title = settings.value.app_name
    }
  }

  // Apply theme dari cache langsung (instant, tanpa nunggu API)
  applyTheme()

  return {
    settings,
    isLoaded,
    isLoading,
    appName,
    logoUrl,
    themePrimary,
    loadPublicSettings,
    loadAllSettings,
    updateSetting,
    updateBulk,
    uploadLogo,
    applyTheme,
  }
})
