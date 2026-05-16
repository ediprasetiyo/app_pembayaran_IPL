import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

import App from './App.vue'
import router from './router'
import { useSettingsStore } from './stores/settings'
import './style.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(Toast, {
  timeout: 3000,
  position: 'top-right',
  closeOnClick: true,
})

// Load public settings (branding, theme) di awal — sebelum mount
const settings = useSettingsStore()
settings.loadPublicSettings().finally(() => {
  app.mount('#app')
})
