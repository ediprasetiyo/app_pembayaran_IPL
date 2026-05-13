<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Data Warga</h2>
        <p class="text-sm text-gray-500">Perumahan Griya Pesona Madani - Blok E</p>
      </div>
      <RouterLink to="/warga/tambah" class="btn-primary">
        <PlusIcon class="w-4 h-4" />
        Tambah Warga
      </RouterLink>
    </div>

    <!-- Filters -->
    <div class="card p-4 flex flex-col sm:flex-row gap-3">
      <div class="flex-1 relative">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          v-model="search"
          type="text"
          class="input pl-9"
          placeholder="Cari nama, nomor rumah, atau telepon..."
          @input="debouncedSearch"
        />
      </div>
      <select v-model="statusFilter" class="input w-auto" @change="fetchData">
        <option value="">Semua Status</option>
        <option value="aktif">Aktif</option>
        <option value="tidak_aktif">Tidak Aktif</option>
      </select>
    </div>

    <!-- Table -->
    <div class="card">
      <div v-if="store.isLoading" class="p-8 text-center">
        <div class="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto" />
        <p class="mt-2 text-gray-500 text-sm">Memuat data...</p>
      </div>

      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Warga</th>
              <th>Alamat</th>
              <th>Telepon</th>
              <th>Status Hunian</th>
              <th>Anggota KK</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.wargaList.length === 0">
              <td colspan="8" class="text-center py-12 text-gray-400">
                <UsersIcon class="w-12 h-12 mx-auto mb-2 opacity-30" />
                Tidak ada data warga
              </td>
            </tr>
            <tr v-for="(warga, index) in store.wargaList" :key="warga.id">
              <td class="font-mono text-xs text-gray-400">{{ (store.pagination.currentPage - 1) * store.pagination.perPage + index + 1 }}</td>
              <td>
                <div>
                  <p class="font-medium">{{ warga.user?.name }}</p>
                  <p class="text-xs text-gray-400">{{ warga.nik ?? 'NIK belum diisi' }}</p>
                </div>
              </td>
              <td>
                <span class="font-medium">Blok {{ warga.blok }} No. {{ warga.nomor_rumah }}</span>
                <p class="text-xs text-gray-400">RT {{ warga.rt ?? '-' }} / RW {{ warga.rw ?? '-' }}</p>
              </td>
              <td>{{ warga.user?.phone ?? '-' }}</td>
              <td>
                <span :class="statusHunianClass(warga.status_hunian)">
                  {{ statusHunianLabel(warga.status_hunian) }}
                </span>
              </td>
              <td class="text-center">
                <span class="font-semibold">{{ warga.anggota_keluarga?.length ?? 0 }}</span>
                <span class="text-gray-400 text-xs"> orang</span>
              </td>
              <td>
                <span :class="warga.is_active ? 'badge-green' : 'badge-red'">
                  {{ warga.is_active ? 'Aktif' : 'Tidak Aktif' }}
                </span>
              </td>
              <td>
                <div class="flex items-center gap-2 justify-center">
                  <RouterLink :to="`/warga/${warga.id}`" class="btn-secondary btn-sm">
                    <EyeIcon class="w-3.5 h-3.5" />
                    Detail
                  </RouterLink>
                  <RouterLink :to="`/warga/${warga.id}/edit`" class="btn-secondary btn-sm">
                    <PencilIcon class="w-3.5 h-3.5" />
                  </RouterLink>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="store.pagination.lastPage > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
        <p class="text-sm text-gray-500">
          Menampilkan {{ store.wargaList.length }} dari {{ store.pagination.total }} warga
        </p>
        <div class="flex gap-2">
          <button
            @click="changePage(store.pagination.currentPage - 1)"
            :disabled="store.pagination.currentPage === 1"
            class="btn-secondary btn-sm disabled:opacity-50"
          >
            <ChevronLeftIcon class="w-4 h-4" />
          </button>
          <span class="btn-secondary btn-sm pointer-events-none">
            {{ store.pagination.currentPage }} / {{ store.pagination.lastPage }}
          </span>
          <button
            @click="changePage(store.pagination.currentPage + 1)"
            :disabled="store.pagination.currentPage === store.pagination.lastPage"
            class="btn-secondary btn-sm disabled:opacity-50"
          >
            <ChevronRightIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  PlusIcon, MagnifyingGlassIcon, EyeIcon, PencilIcon,
  UsersIcon, ChevronLeftIcon, ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import { useWargaStore } from '@/stores/warga'

const store = useWargaStore()
const search = ref('')
const statusFilter = ref('')
let searchTimer = null

function debouncedSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(fetchData, 400)
}

async function fetchData(page = 1) {
  await store.fetchAll({
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    page,
  })
}

function changePage(page) {
  if (page < 1 || page > store.pagination.lastPage) return
  fetchData(page)
}

function statusHunianClass(status) {
  return status === 'milik' ? 'badge-blue' : status === 'sewa' ? 'badge-yellow' : 'badge-gray'
}

function statusHunianLabel(status) {
  return { milik: 'Milik', sewa: 'Sewa', kontrak: 'Kontrak' }[status] ?? status
}

onMounted(() => fetchData())
</script>
