import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Mapping route name → role yang boleh akses
const routeAccess = {
  Dashboard: ['super_admin', 'admin', 'bendahara', 'humas'],
  Warga: ['super_admin', 'admin'],
  TambahWarga: ['super_admin', 'admin'],
  DetailWarga: ['super_admin', 'admin'],
  EditWarga: ['super_admin', 'admin'],
  Pembayaran: ['super_admin', 'admin', 'bendahara'],
  Tagihan: ['super_admin', 'admin', 'bendahara'],
  Pengaduan: ['super_admin', 'admin', 'humas'],
  News: ['super_admin', 'admin', 'humas'],
  NewsBaru: ['super_admin', 'admin', 'humas'],
  NewsEdit: ['super_admin', 'admin', 'humas'],
  Laporan: ['super_admin', 'admin', 'bendahara'],
  Users: ['super_admin'],
  UserBaru: ['super_admin'],
  UserEdit: ['super_admin'],
  Settings: ['super_admin'],
  Bloks: ['super_admin'],
  AuditLog: ['super_admin'],
  MidtransPayment: ['super_admin'],
  Pengeluaran: ['super_admin', 'admin', 'bendahara'],
}

// Mendapatkan default route berdasarkan role
function getDefaultRoute(role) {
  // Semua role kecuali warga punya akses Dashboard
  if (['super_admin', 'admin', 'bendahara', 'humas'].includes(role)) return '/'
  return '/login'
}

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { public: true },
  },
  {
    path: '/',
    component: () => import('@/components/common/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'Dashboard', component: () => import('@/views/DashboardView.vue') },
      { path: 'warga', name: 'Warga', component: () => import('@/views/warga/WargaList.vue') },
      { path: 'warga/tambah', name: 'TambahWarga', component: () => import('@/views/warga/WargaForm.vue') },
      { path: 'warga/:id', name: 'DetailWarga', component: () => import('@/views/warga/WargaDetail.vue') },
      { path: 'warga/:id/edit', name: 'EditWarga', component: () => import('@/views/warga/WargaForm.vue') },
      { path: 'pembayaran', name: 'Pembayaran', component: () => import('@/views/pembayaran/PembayaranList.vue') },
      { path: 'tagihan', name: 'Tagihan', component: () => import('@/views/pembayaran/TagihanList.vue') },
      { path: 'pengaduan', name: 'Pengaduan', component: () => import('@/views/pengaduan/PengaduanList.vue') },
      { path: 'laporan', name: 'Laporan', component: () => import('@/views/LaporanView.vue') },
      { path: 'news', name: 'News', component: () => import('@/views/news/NewsList.vue') },
      { path: 'news/baru', name: 'NewsBaru', component: () => import('@/views/news/NewsForm.vue') },
      { path: 'news/:id/edit', name: 'NewsEdit', component: () => import('@/views/news/NewsForm.vue') },
      { path: 'users', name: 'Users', component: () => import('@/views/users/UserList.vue') },
      { path: 'users/baru', name: 'UserBaru', component: () => import('@/views/users/UserForm.vue') },
      { path: 'users/:id/edit', name: 'UserEdit', component: () => import('@/views/users/UserForm.vue') },
      { path: 'settings', name: 'Settings', component: () => import('@/views/SettingsView.vue') },
      { path: 'bloks', name: 'Bloks', component: () => import('@/views/admin/BlokList.vue') },
      { path: 'audit-log', name: 'AuditLog', component: () => import('@/views/admin/AuditLogView.vue') },
      { path: 'midtrans-payment', name: 'MidtransPayment', component: () => import('@/views/admin/MidtransPaymentView.vue') },
      { path: 'pengeluaran', name: 'Pengeluaran', component: () => import('@/views/admin/PengeluaranList.vue') },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (!to.meta.public && !auth.token) return '/login'
  if (to.path === '/login' && auth.token) return '/'

  // Load user info kalau belum ada (utk cek role)
  if (auth.token && !auth.user) {
    await auth.fetchMe()
  }

  // Block warga login backoffice
  if (auth.user?.role === 'warga') {
    auth.logout()
    return '/login'
  }

  // Cek akses berdasarkan role
  if (to.name && routeAccess[to.name]) {
    const allowedRoles = routeAccess[to.name]
    if (!allowedRoles.includes(auth.user?.role)) {
      return getDefaultRoute(auth.user?.role)
    }
  }
})

export default router
