import axios from 'axios'
import router from '@/router'

// Production: IDcloudHost. Development: pakai proxy Vite "/api/v1"
const baseURL = import.meta.env.VITE_API_URL || 'https://ipl-griya-pesona-madani.my.id/api/v1'

const api = axios.create({
  baseURL,
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`

  // Auto-set Content-Type:
  // - FormData → biarkan browser/axios set multipart/form-data dengan boundary
  // - Lainnya → application/json
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type']
  } else if (!config.headers['Content-Type']) {
    config.headers['Content-Type'] = 'application/json'
  }
  return config
})

api.interceptors.response.use(
  (res) => res,
  (err) => {
    const status = err.response?.status
    const url = err.config?.url ?? ''

    // Logout endpoint — abaikan semua error
    if (url.includes('/auth/logout')) {
      return Promise.reject(err)
    }

    if (status === 401) {
      localStorage.removeItem('token')
      if (router.currentRoute.value.path !== '/login') {
        router.push('/login')
      }
    }
    return Promise.reject(err)
  }
)

export default api
