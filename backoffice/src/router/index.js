import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

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
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/DashboardView.vue'),
      },
      {
        path: 'warga',
        name: 'Warga',
        component: () => import('@/views/warga/WargaList.vue'),
      },
      {
        path: 'warga/tambah',
        name: 'TambahWarga',
        component: () => import('@/views/warga/WargaForm.vue'),
      },
      {
        path: 'warga/:id',
        name: 'DetailWarga',
        component: () => import('@/views/warga/WargaDetail.vue'),
      },
      {
        path: 'warga/:id/edit',
        name: 'EditWarga',
        component: () => import('@/views/warga/WargaForm.vue'),
      },
      {
        path: 'pembayaran',
        name: 'Pembayaran',
        component: () => import('@/views/pembayaran/PembayaranList.vue'),
      },
      {
        path: 'tagihan',
        name: 'Tagihan',
        component: () => import('@/views/pembayaran/TagihanList.vue'),
      },
      {
        path: 'pengaduan',
        name: 'Pengaduan',
        component: () => import('@/views/pengaduan/PengaduanList.vue'),
      },
      {
        path: 'laporan',
        name: 'Laporan',
        component: () => import('@/views/LaporanView.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (!to.meta.public && !auth.token) return '/login'
  if (to.path === '/login' && auth.token) return '/'
})

export default router
