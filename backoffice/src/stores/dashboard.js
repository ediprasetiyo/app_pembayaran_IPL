import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useDashboardStore = defineStore('dashboard', () => {
  const stats = ref(null)
  const isLoading = ref(false)

  async function fetchStats() {
    isLoading.value = true
    try {
      const res = await api.get('/admin/dashboard')
      stats.value = res.data
    } finally {
      isLoading.value = false
    }
  }

  return { stats, isLoading, fetchStats }
})
