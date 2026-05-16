<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Manajemen User</h2>
        <p class="text-sm text-gray-500">Kelola akun pengguna dan hak aksesnya</p>
      </div>
      <RouterLink to="/users/baru" class="btn-primary">
        <PlusIcon class="w-4 h-4" /> Tambah User
      </RouterLink>
    </div>

    <!-- Filter -->
    <div class="card p-4 flex flex-wrap items-center gap-3">
      <input v-model="search" @input="loadUsers" type="text" placeholder="Cari nama / nomor HP..." class="input flex-1 min-w-[200px]" />
      <select v-model="role" @change="loadUsers" class="input w-40">
        <option value="">Semua Role</option>
        <option value="super_admin">Super Admin</option>
        <option value="admin">Admin</option>
        <option value="bendahara">Bendahara</option>
        <option value="humas">Humas</option>
        <option value="warga">Warga</option>
      </select>
      <select v-model="status" @change="loadUsers" class="input w-32">
        <option value="">Semua Status</option>
        <option value="aktif">Aktif</option>
        <option value="nonaktif">Non-aktif</option>
      </select>
    </div>

    <!-- Tabel -->
    <div class="card overflow-hidden">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nama</th>
              <th>Nomor HP</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Dibuat</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center py-6 text-gray-500">Memuat...</td>
            </tr>
            <tr v-else-if="users.length === 0">
              <td colspan="7" class="text-center py-6 text-gray-500">Belum ada user.</td>
            </tr>
            <tr v-for="u in users" :key="u.id">
              <td class="font-medium">{{ u.name }}</td>
              <td>{{ u.phone }}</td>
              <td>{{ u.email ?? '-' }}</td>
              <td>
                <span :class="roleClass(u.role)">{{ roleLabel(u.role) }}</span>
              </td>
              <td>
                <span :class="u.is_active ? 'badge-green' : 'badge-gray'">
                  {{ u.is_active ? 'Aktif' : 'Non-aktif' }}
                </span>
              </td>
              <td class="text-gray-500 text-xs">{{ formatDate(u.created_at) }}</td>
              <td class="text-right">
                <RouterLink :to="`/users/${u.id}/edit`" class="text-primary-700 hover:underline text-sm mr-3">Edit</RouterLink>
                <button v-if="u.id !== auth.user.id" @click="hapus(u)" class="text-red-600 hover:underline text-sm">Hapus</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import dayjs from 'dayjs'

const auth = useAuthStore()
const toast = useToast()
const users = ref([])
const loading = ref(false)
const search = ref('')
const role = ref('')
const status = ref('')

async function loadUsers() {
  loading.value = true
  try {
    const res = await api.get('/admin/users', {
      params: { search: search.value, role: role.value, status: status.value },
    })
    users.value = res.data.data ?? res.data
  } catch (e) {
    toast.error('Gagal memuat user.')
  } finally {
    loading.value = false
  }
}

async function hapus(u) {
  if (!confirm(`Hapus user "${u.name}"?`)) return
  try {
    await api.delete(`/admin/users/${u.id}`)
    toast.success('User dihapus.')
    loadUsers()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Gagal hapus.')
  }
}

function roleLabel(r) {
  return {
    super_admin: '👑 Super Admin',
    admin: '⚙️ Admin',
    bendahara: '💰 Bendahara',
    humas: '📣 Humas',
    warga: '👥 Warga',
  }[r] ?? r
}

function roleClass(r) {
  return {
    super_admin: 'badge-red',
    admin: 'badge-blue',
    bendahara: 'badge-green',
    humas: 'badge-yellow',
    warga: 'badge-gray',
  }[r] ?? 'badge-gray'
}

function formatDate(d) {
  return dayjs(d).format('DD MMM YYYY')
}

onMounted(loadUsers)
</script>
