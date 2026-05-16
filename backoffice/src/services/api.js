import axios from 'axios'
import router from '@/router'

const api = axios.create({
  baseURL: '/api/v1',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
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
