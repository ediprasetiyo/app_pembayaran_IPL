<template>
  <div class="space-y-4">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">📋 Audit Log</h2>
      <p class="text-sm text-gray-500">Track semua aksi penting: login, pembayaran, perubahan data, dll. Untuk detect fraud / human error.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <div class="card p-4 flex items-center gap-3 border-l-4 border-primary-700">
        <span class="text-2xl">📊</span>
        <div>
          <p class="text-xs text-gray-500">Total Log</p>
          <p class="font-bold text-xl">{{ stats.total ?? 0 }}</p>
        </div>
      </div>
      <div class="card p-4 flex items-center gap-3 border-l-4 border-blue-500">
        <span class="text-2xl">📅</span>
        <div>
          <p class="text-xs text-gray-500">Log Hari Ini</p>
          <p class="font-bold text-xl text-blue-700">{{ stats.today ?? 0 }}</p>
        </div>
      </div>
      <div class="card p-4 flex items-center gap-3 border-l-4 border-yellow-500">
        <span class="text-2xl">⚠️</span>
        <div>
          <p class="text-xs text-gray-500">Warning</p>
          <p class="font-bold text-xl text-yellow-700">{{ stats.by_severity?.warning ?? 0 }}</p>
        </div>
      </div>
      <div class="card p-4 flex items-center gap-3 border-l-4 border-red-500">
        <span class="text-2xl">🚨</span>
        <div>
          <p class="text-xs text-gray-500">Error/Critical</p>
          <p class="font-bold text-xl text-red-700">{{ (stats.by_severity?.error ?? 0) + (stats.by_severity?.critical ?? 0) }}</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4 grid grid-cols-2 md:grid-cols-4 gap-3">
      <div>
        <label class="label">Cari</label>
        <input v-model="filters.search" @input="debouncedLoad" class="input" placeholder="user, deskripsi, action..." />
      </div>
      <div>
        <label class="label">Action</label>
        <select v-model="filters.action" @change="load" class="input">
          <option value="">Semua</option>
          <option value="login">Login</option>
          <option value="login_failed">Login Gagal</option>
          <option value="login_blocked">Login Diblokir</option>
          <option value="payment_success">Pembayaran Sukses</option>
          <option value="payment_failed">Pembayaran Gagal</option>
          <option value="created">Buat Data</option>
          <option value="updated">Update Data</option>
          <option value="deleted">Hapus Data</option>
          <option value="pengaduan_deleted">Hapus Pengaduan</option>
          <option value="settings_updated">Ubah Setting</option>
        </select>
      </div>
      <div>
        <label class="label">Severity</label>
        <select v-model="filters.severity" @change="load" class="input">
          <option value="">Semua</option>
          <option value="info">ℹ️ Info</option>
          <option value="warning">⚠️ Warning</option>
          <option value="error">🚨 Error</option>
          <option value="critical">💀 Critical</option>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button @click="resetFilters" class="btn-secondary flex-1">Reset</button>
        <button @click="cleanup" class="px-3 py-2 text-sm text-red-600 hover:bg-red-50 border border-red-200 rounded-lg" title="Hapus log lama (>90 hari)">
          🧹 Cleanup
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <div v-if="loading" class="p-8 text-center">
        <div class="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto" />
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Waktu</th>
              <th>User</th>
              <th>Action</th>
              <th>Deskripsi</th>
              <th>Severity</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="logs.length === 0">
              <td colspan="6" class="text-center py-10 text-gray-400">Tidak ada log dengan filter ini.</td>
            </tr>
            <tr v-for="log in logs" :key="log.id" :class="rowClass(log.severity)">
              <td class="text-xs text-gray-500 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
              <td>
                <p class="text-sm font-medium">{{ log.user_name ?? '-' }}</p>
                <p class="text-xs text-gray-400">{{ log.user_role ?? '' }}</p>
              </td>
              <td>
                <span class="badge-gray font-mono text-xs">{{ log.action }}</span>
              </td>
              <td class="text-sm max-w-md">{{ log.description ?? '-' }}</td>
              <td>
                <span :class="severityBadge(log.severity)">{{ severityLabel(log.severity) }}</span>
              </td>
              <td class="text-xs text-gray-400 font-mono">{{ log.ip_address ?? '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination" class="px-4 py-3 border-t flex items-center justify-between text-sm">
        <p class="text-gray-500">
          {{ pagination.from }}–{{ pagination.to }} dari {{ pagination.total }} log
        </p>
        <div class="flex gap-2">
          <button @click="goPage(pagination.current_page - 1)" :disabled="!pagination.prev_page_url" class="btn-secondary px-3 py-1 text-sm">‹ Prev</button>
          <span class="px-3 py-1">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button @click="goPage(pagination.current_page + 1)" :disabled="!pagination.next_page_url" class="btn-secondary px-3 py-1 text-sm">Next ›</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import dayjs from 'dayjs'

const toast = useToast()
const logs = ref([])
const stats = ref({})
const loading = ref(false)
const pagination = ref(null)
const page = ref(1)

const filters = ref({ search: '', action: '', severity: '' })

let debounceTimer = null
function debouncedLoad() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { page.value = 1; load() }, 400)
}

async function load() {
  loading.value = true
  try {
    const params = { ...filters.value, page: page.value, per_page: 30 }
    Object.keys(params).forEach((k) => params[k] === '' && delete params[k])
    const [logRes, statsRes] = await Promise.all([
      api.get('/admin/audit-logs', { params }),
      api.get('/admin/audit-logs/stats'),
    ])
    logs.value = logRes.data.data ?? []
    pagination.value = {
      current_page: logRes.data.current_page,
      last_page: logRes.data.last_page,
      total: logRes.data.total,
      from: logRes.data.from,
      to: logRes.data.to,
      prev_page_url: logRes.data.prev_page_url,
      next_page_url: logRes.data.next_page_url,
    }
    stats.value = statsRes.data
  } catch (e) {
    toast.error('Gagal load audit log.')
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  if (p < 1 || p > (pagination.value?.last_page ?? 1)) return
  page.value = p
  load()
}

function resetFilters() {
  filters.value = { search: '', action: '', severity: '' }
  page.value = 1
  load()
}

async function cleanup() {
  if (!confirm('Hapus log lebih lama dari 90 hari? Tidak bisa di-undo.')) return
  try {
    const res = await api.delete('/admin/audit-logs/cleanup?days=90')
    toast.success(res.data?.message ?? 'Cleanup selesai.')
    await load()
  } catch (e) {
    toast.error('Gagal cleanup.')
  }
}

function formatDate(d) {
  return dayjs(d).format('DD/MM/YYYY HH:mm:ss')
}

function severityLabel(s) {
  return { info: 'ℹ️ Info', warning: '⚠️ Warning', error: '🚨 Error', critical: '💀 Critical' }[s] ?? s
}
function severityBadge(s) {
  return {
    info: 'badge-blue',
    warning: 'badge-yellow',
    error: 'badge-red',
    critical: 'badge-red font-bold',
  }[s] ?? 'badge-gray'
}
function rowClass(s) {
  if (s === 'critical' || s === 'error') return 'bg-red-50/50'
  if (s === 'warning') return 'bg-yellow-50/50'
  return ''
}

onMounted(load)
</script>
