<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-emerald-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-6">
        <div class="bg-white rounded-2xl shadow-md p-5 inline-block">
          <img src="/logo.png" alt="Griya Pesona Madani" class="h-24 mx-auto" />
        </div>
        <p class="text-gray-500 text-xs mt-3 tracking-widest uppercase">
          Back Office Admin Panel
        </p>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Masuk ke Sistem</h2>
        <p class="text-sm text-gray-500 mb-6">Silakan login dengan akun admin Anda</p>

        <form @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="label">Nomor Telepon</label>
            <input
              v-model="form.phone"
              type="tel"
              class="input"
              placeholder="08xxxxxxxxxx"
              required
            />
          </div>

          <div>
            <label class="label">Password</label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPass ? 'text' : 'password'"
                class="input pr-10"
                placeholder="••••••••"
                required
              />
              <button
                type="button"
                @click="showPass = !showPass"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"
              >
                <EyeIcon v-if="!showPass" class="w-4 h-4" />
                <EyeSlashIcon v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-red-700 text-sm">
            {{ error }}
          </div>

          <button type="submit" class="btn-primary w-full justify-center py-3" :disabled="isLoading">
            <span v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            <span>{{ isLoading ? 'Memproses...' : 'Masuk' }}</span>
          </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-6">
          Perumahan Griya Pesona Madani Tenjo - Blok E
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const form = ref({ phone: '', password: '' })
const showPass = ref(false)
const isLoading = ref(false)
const error = ref('')

async function handleLogin() {
  error.value = ''
  isLoading.value = true
  try {
    const data = await auth.login(form.value.phone, form.value.password)
    if (!['admin', 'super_admin'].includes(data.user?.role)) {
      auth.logout()
      error.value = 'Akun ini tidak memiliki akses admin.'
      return
    }
    await router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Login gagal. Periksa nomor telepon dan password.'
  } finally {
    isLoading.value = false
  }
}
</script>
