import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useWargaStore = defineStore('warga', () => {
  const wargaList = ref([])
  const currentWarga = ref(null)
  const pagination = ref({})
  const isLoading = ref(false)

  async function fetchAll(params = {}) {
    isLoading.value = true
    try {
      const res = await api.get('/warga', { params })
      wargaList.value = res.data.data
      pagination.value = {
        total: res.data.total,
        currentPage: res.data.current_page,
        lastPage: res.data.last_page,
        perPage: res.data.per_page,
      }
    } finally {
      isLoading.value = false
    }
  }

  async function fetchOne(id) {
    const res = await api.get(`/warga/${id}`)
    currentWarga.value = res.data.warga
    return res.data.warga
  }

  async function create(data) {
    const res = await api.post('/warga', data)
    return res.data
  }

  async function update(id, data) {
    const res = await api.put(`/warga/${id}`, data)
    return res.data
  }

  async function addAnggotaKeluarga(wargaId, data) {
    const res = await api.post(`/warga/${wargaId}/keluarga`, data)
    return res.data
  }

  async function updateAnggotaKeluarga(wargaId, anggotaId, data) {
    const res = await api.put(`/warga/${wargaId}/keluarga/${anggotaId}`, data)
    return res.data
  }

  async function deleteAnggotaKeluarga(wargaId, anggotaId) {
    await api.delete(`/warga/${wargaId}/keluarga/${anggotaId}`)
    if (currentWarga.value) {
      currentWarga.value.anggota_keluarga = currentWarga.value.anggota_keluarga
        .filter((a) => a.id !== anggotaId)
    }
  }

  return {
    wargaList, currentWarga, pagination, isLoading,
    fetchAll, fetchOne, create, update,
    addAnggotaKeluarga, updateAnggotaKeluarga, deleteAnggotaKeluarga,
  }
})
