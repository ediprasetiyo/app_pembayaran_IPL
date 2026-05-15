<template>
  <div class="max-w-2xl space-y-4">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">{{ isEdit ? 'Edit User' : 'Tambah User Baru' }}</h2>
      <p class="text-sm text-gray-500">{{ isEdit ? 'Perbarui data akun pengguna' : 'Buat akun baru untuk admin atau warga' }}</p>
    </div>

    <form @submit.prevent="submit" class="card p-6 space-y-4">
      <div>
        <label class="label">Nama Lengkap *</label>
        <input v-model="form.name" type="text" class="input" required />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">Nomor HP *</label>
          <input v-model="form.phone" type="text" class="input" required placeholder="08xxxx" />
        </div>
        <div>
          <label class="label">Email</label>
          <input v-model="form.email" type="email" class="input" placeholder="opsional" />
        </div>
      </div>

      <div>
        <label class="label">Role *</label>
        <select v-model="form.role" class="input" required :disabled="isEdit && form.id === auth.user.id">
          <option value="super_admin">Super Admin</option>
          <option value="admin">Admin</option>
          <option value="warga">Warga</option>
        </select>
        <p v-if="isEdit && form.id === auth.user.id" class="text-xs text-gray-500 mt-1">
          Tidak bisa mengubah role akun sendiri.
        </p>
      </div>

      <div>
        <label class="label">{{ isEdit ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password *' }}</label>
        <input v-model="form.password" type="password" class="input" :required="!isEdit" minlength="6" />
      </div>

      <div v-if="isEdit" class="flex items-center gap-2">
        <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded" :disabled="form.id === auth.user.id" />
        <label for="is_active" class="text-sm text-gray-700">Akun aktif</label>
      </div>

      <div class="flex items-center justify-end gap-2 pt-4 border-t">
        <RouterLink to="/users" class="btn-secondary">Batal</RouterLink>
        <button type="submit" class="btn-primary" :disabled="saving">
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah User') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const auth = useAuthStore()
const saving = ref(false)

const isEdit = computed(() => !!route.params.id)

const form = ref({
  id: null,
  name: '',
  phone: '',
  email: '',
  role: 'admin',
  password: '',
  is_active: true,
})

async function loadUser() {
  if (!isEdit.value) return
  try {
    const res = await api.get(`/admin/users/${route.params.id}`)
    const u = res.data.user
    form.value = {
      id: u.id,
      name: u.name,
      phone: u.phone,
      email: u.email ?? '',
      role: u.role,
      password: '',
      is_active: u.is_active,
    }
  } catch {
    toast.error('User tidak ditemukan.')
    router.push('/users')
  }
}

async function submit() {
  saving.value = true
  try {
    const payload = { ...form.value }
    if (!payload.password) delete payload.password
    if (!payload.email) delete payload.email

    if (isEdit.value) {
      await api.put(`/admin/users/${form.value.id}`, payload)
      toast.success('User diperbarui.')
    } else {
      await api.post('/admin/users', payload)
      toast.success('User baru ditambahkan.')
    }
    router.push('/users')
  } catch (e) {
    const errors = e.response?.data?.errors
    const msg = errors ? Object.values(errors).flat().join(', ') : (e.response?.data?.message ?? 'Gagal menyimpan.')
    toast.error(msg)
  } finally {
    saving.value = false
  }
}

onMounted(loadUser)
</script>
