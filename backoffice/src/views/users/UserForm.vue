<template>
  <div class="max-w-2xl space-y-4">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">{{ isEdit ? 'Edit User' : 'Tambah User Baru' }}</h2>
      <p class="text-sm text-gray-500">{{ isEdit ? 'Perbarui data akun pengguna' : 'Buat akun baru untuk admin atau warga' }}</p>
    </div>

    <form @submit.prevent="submit" class="card p-6 space-y-4" autocomplete="off">
      <!-- Honeypot fields utk distract Chrome autofill -->
      <div style="position:absolute;left:-9999px;opacity:0;" aria-hidden="true">
        <input type="text" name="fakeusernameremembered" tabindex="-1" autocomplete="username" />
        <input type="password" name="fakepasswordremembered" tabindex="-1" autocomplete="current-password" />
      </div>

      <div>
        <label class="label">Nama Lengkap *</label>
        <input v-model="form.name" type="text" class="input" required autocomplete="off" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">Nomor HP *</label>
          <input
            v-model="form.phone"
            type="text"
            class="input"
            required
            placeholder="08xxxx"
            :name="`phone-${randomKey}`"
            autocomplete="off"
            readonly
            @focus="$event.target.removeAttribute('readonly')"
          />
        </div>
        <div>
          <label class="label">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="input"
            placeholder="opsional"
            :name="`email-${randomKey}`"
            autocomplete="off"
          />
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">Role *</label>
          <select v-model="form.role" class="input" required :disabled="isEdit && form.id === auth.user.id">
            <option value="super_admin">👑 Super Admin</option>
            <option value="admin">⚙️ Admin</option>
            <option value="bendahara">💰 Bendahara</option>
            <option value="humas">📣 Humas</option>
            <option value="warga">👥 Warga</option>
          </select>
        </div>
        <div>
          <label class="label">
            Blok
            <span v-if="needBlok" class="text-red-600">*</span>
            <span v-else class="text-gray-400 text-xs">(opsional)</span>
          </label>
          <select v-model="form.blok_id" class="input" :disabled="form.role === 'super_admin'">
            <option :value="null">{{ form.role === 'super_admin' ? 'Semua Blok (akses penuh)' : '— Pilih Blok —' }}</option>
            <option v-for="b in bloks" :key="b.id" :value="b.id">{{ b.kode }} - {{ b.nama }}</option>
          </select>
          <p class="text-xs text-gray-400 mt-1">
            <span v-if="form.role === 'super_admin'">Super Admin otomatis akses semua blok.</span>
            <span v-else>User hanya bisa lihat warga di blok ini.</span>
          </p>
        </div>
      </div>
      <div class="text-xs text-gray-500 space-y-0.5 -mt-2">
        <div><strong>👑 Super Admin:</strong> semua menu + manajemen user + akses SEMUA blok</div>
        <div><strong>⚙️ Admin:</strong> semua menu kecuali user mgmt, scope <strong>1 blok</strong></div>
        <div><strong>💰 Bendahara:</strong> Tagihan, Pembayaran, Laporan, scope <strong>1 blok</strong></div>
        <div><strong>📣 Humas:</strong> Pengaduan, Berita, scope <strong>1 blok</strong></div>
        <div><strong>👥 Warga:</strong> mobile app saja (blok ikut data warga)</div>
      </div>
      <p v-if="isEdit && form.id === auth.user.id" class="text-xs text-gray-500 mt-1">
        Tidak bisa mengubah role akun sendiri.
      </p>

      <div>
        <label class="label">{{ isEdit ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password *' }}</label>
        <div class="relative">
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="input pr-10"
            :required="!isEdit"
            minlength="6"
            :name="`pwd-${randomKey}`"
            autocomplete="new-password"
            readonly
            @focus="$event.target.removeAttribute('readonly')"
          />
          <button
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
            tabindex="-1"
            @click="showPassword = !showPassword"
          >
            {{ showPassword ? '🙈' : '👁️' }}
          </button>
        </div>
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
// Random key untuk bikin Chrome bingung & tidak autofill
const randomKey = Math.random().toString(36).substring(2, 10)
const showPassword = ref(false)

const form = ref({
  id: null,
  name: '',
  phone: '',
  email: '',
  role: 'admin',
  blok_id: null,
  password: '',
  is_active: true,
})

const bloks = ref([])
const needBlok = computed(() => !['super_admin', 'warga'].includes(form.value.role))

async function loadBloks() {
  try {
    const res = await api.get('/admin/bloks')
    bloks.value = (res.data.data ?? []).filter((b) => b.is_active)
  } catch (_) {}
}

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
      blok_id: u.blok_id ?? null,
      password: '',
      is_active: u.is_active,
    }
  } catch {
    toast.error('User tidak ditemukan.')
    router.push('/users')
  }
}

async function submit() {
  if (!(await submitValidate())) return
  saving.value = true
  try {
    const payload = { ...form.value }
    if (!payload.password) delete payload.password
    if (!payload.email) delete payload.email
    // Super admin override blok_id = null
    if (payload.role === 'super_admin') payload.blok_id = null

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

async function submitValidate() {
  if (needBlok.value && !form.value.blok_id) {
    toast.error(`Role ${form.value.role} wajib pilih Blok.`)
    return false
  }
  return true
}

onMounted(() => {
  loadBloks()
  loadUser()
})
</script>
