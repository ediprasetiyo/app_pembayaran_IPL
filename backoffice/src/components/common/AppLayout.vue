<template>
  <div class="flex h-screen bg-gray-50 overflow-hidden">
    <!-- Sidebar -->
    <aside
      :class="['fixed inset-y-0 left-0 z-50 flex flex-col bg-primary-900 text-white transition-all duration-300',
        sidebarOpen ? 'w-64' : 'w-16']"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-4 py-4 border-b border-primary-800">
        <div class="flex-shrink-0 w-10 h-10 bg-white rounded-lg flex items-center justify-center p-1">
          <img :src="settings.logo_url" :alt="settings.app_name" class="w-full h-full object-contain" />
        </div>
        <transition name="fade">
          <div v-if="sidebarOpen" class="overflow-hidden">
            <p class="font-bold text-sm leading-tight text-white">{{ settings.brand_title }}</p>
            <p class="text-primary-200 text-xs">{{ settings.brand_subtitle }}</p>
          </div>
        </transition>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 space-y-1 px-2">
        <RouterLink
          v-for="item in visibleNavItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-primary-200 hover:bg-primary-800 hover:text-white transition-colors group"
          active-class="bg-primary-700 text-white"
        >
          <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
          <transition name="fade">
            <span v-if="sidebarOpen" class="text-sm font-medium">{{ item.label }}</span>
          </transition>
        </RouterLink>
      </nav>

      <!-- User -->
      <div class="border-t border-primary-800 p-3">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-primary-700 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">
            {{ auth.user?.name?.[0]?.toUpperCase() ?? 'A' }}
          </div>
          <transition name="fade">
            <div v-if="sidebarOpen" class="overflow-hidden flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ auth.user?.name }}</p>
              <p class="text-primary-300 text-xs truncate">{{ auth.user?.phone }}</p>
            </div>
          </transition>
          <button v-if="sidebarOpen" @click="logout" class="text-primary-300 hover:text-white p-1">
            <ArrowRightOnRectangleIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div :class="['flex-1 flex flex-col min-w-0 transition-all duration-300', sidebarOpen ? 'ml-64' : 'ml-16']">
      <!-- Top Bar -->
      <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-4 flex-shrink-0">
        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700">
          <Bars3Icon class="w-5 h-5" />
        </button>
        <div class="flex-1">
          <h1 class="font-semibold text-gray-800">{{ pageTitle }}</h1>
        </div>
        <div class="text-sm text-gray-500">
          {{ currentDate }}
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto p-6">
        <RouterView />
      </main>
    </div>

    <!-- AI Assistant floating chat -->
    <AiAssistant />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  HomeIcon,
  UsersIcon,
  CreditCardIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  ChartBarIcon,
  Bars3Icon,
  ArrowRightOnRectangleIcon,
  ShieldCheckIcon,
  MegaphoneIcon,
  Cog6ToothIcon,
} from '@heroicons/vue/24/outline'
import dayjs from 'dayjs'
import 'dayjs/locale/id'
import AiAssistant from './AiAssistant.vue'
import { useSettingsStore } from '@/stores/settings'
dayjs.locale('id')

const sidebarOpen = ref(true)
const auth = useAuthStore()
const settingsStore = useSettingsStore()
const settings = computed(() => settingsStore.settings)
const route = useRoute()
const router = useRouter()

// Setiap menu punya daftar role yang boleh akses
const navItems = [
  { to: '/', label: 'Dashboard', icon: HomeIcon,
    roles: ['super_admin', 'admin', 'bendahara', 'humas'] },
  { to: '/warga', label: 'Data Warga', icon: UsersIcon,
    roles: ['super_admin', 'admin'] },
  { to: '/tagihan', label: 'Tagihan IPL', icon: DocumentTextIcon,
    roles: ['super_admin', 'admin', 'bendahara'] },
  { to: '/pembayaran', label: 'Pembayaran', icon: CreditCardIcon,
    roles: ['super_admin', 'admin', 'bendahara'] },
  { to: '/pengaduan', label: 'Pengaduan', icon: ExclamationTriangleIcon,
    roles: ['super_admin', 'admin', 'humas'] },
  { to: '/news', label: 'Berita', icon: MegaphoneIcon,
    roles: ['super_admin', 'admin', 'humas'] },
  { to: '/laporan', label: 'Laporan', icon: ChartBarIcon,
    roles: ['super_admin', 'admin', 'bendahara'] },
  { to: '/users', label: 'Manajemen User', icon: ShieldCheckIcon,
    roles: ['super_admin'] },
  { to: '/settings', label: 'Pengaturan', icon: Cog6ToothIcon,
    roles: ['super_admin'] },
]

const visibleNavItems = computed(() => {
  const role = auth.user?.role
  if (!role) return []
  return navItems.filter(item => item.roles.includes(role))
})

const pageTitles = {
  'Dashboard': 'Dashboard',
  'Warga': 'Data Warga',
  'TambahWarga': 'Tambah Warga',
  'DetailWarga': 'Detail Warga',
  'EditWarga': 'Edit Warga',
  'Pembayaran': 'Riwayat Pembayaran',
  'Tagihan': 'Tagihan IPL',
  'Pengaduan': 'Pengaduan Warga',
  'Laporan': 'Laporan',
  'News': 'Berita & Pengumuman',
  'NewsForm': 'Form Berita',
  'Users': 'Manajemen User',
  'UserForm': 'Form User',
}

const pageTitle = computed(() => pageTitles[route.name] ?? 'Back Office')
const currentDate = computed(() => dayjs().format('dddd, D MMMM YYYY'))

async function logout() {
  await auth.logout()
  await router.push('/login')
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
