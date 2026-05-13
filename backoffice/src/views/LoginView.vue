<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-900 to-primary-700 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-white rounded-2xl mx-auto flex items-center justify-center mb-4 shadow-lg">
          <span class="text-primary-900 font-bold text-2xl">G</span>
        </div>
        <h1 class="text-white text-2xl font-bold">Griya Pesona Madani</h1>
        <p class="text-primary-200 text-sm mt-1">Back Office Admin Panel</p>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Masuk ke Sistem</h2>

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
    if (data.user?.role !== 'admin') {
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
