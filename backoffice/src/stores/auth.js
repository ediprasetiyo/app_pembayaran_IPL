import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token'))
  const user = ref(null)

  const isAuthenticated = computed(() => !!token.value)

  async function login(phone, password) {
    const res = await api.post('/auth/login', { phone, password })
    token.value = res.data.token
    user.value = res.data.user
    localStorage.setItem('token', res.data.token)
    return res.data
  }

  async function fetchMe() {
    if (!token.value) return
    try {
      const res = await api.get('/auth/me')
      user.value = res.data.user
    } catch {
      // Token invalid → clear lokal saja, jangan call logout (yang malah error lagi)
      _clearLocal()
    }
  }

  async function logout() {
    // Kalau ada token, panggil API logout (silent — abaikan error)
    if (token.value) {
      try {
        await api.post('/auth/logout')
      } catch (_) {
        // abaikan 401/403/500 — yang penting clear lokal
      }
    }
    _clearLocal()
  }

  function _clearLocal() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
  }

  return { token, user, isAuthenticated, login, logout, fetchMe }
})
