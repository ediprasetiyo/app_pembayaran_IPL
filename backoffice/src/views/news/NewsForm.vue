<template>
  <div class="max-w-4xl mx-auto space-y-5">
    <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-4 shadow-sm border border-gray-100">
      <RouterLink to="/news" class="text-gray-400 hover:text-gray-700">
        <ArrowLeftIcon class="w-5 h-5" />
      </RouterLink>
      <div>
        <h1 class="text-lg font-semibold text-gray-800">
          {{ isEdit ? 'Edit Berita' : 'Buat Berita Baru' }}
        </h1>
        <p class="text-xs text-gray-500">Berita ini akan ditampilkan di mobile app warga</p>
      </div>
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <!-- Informasi -->
      <div class="card overflow-hidden">
        <div class="bg-gradient-to-r from-primary-700 to-primary-800 px-5 py-3 text-white">
          <h2 class="font-semibold text-sm">📝 Informasi Berita</h2>
        </div>
        <div class="p-5 space-y-4">
          <div>
            <label class="label">Judul Berita <span class="text-red-500">*</span></label>
            <input v-model="form.judul" type="text" class="input text-base" required placeholder="Contoh: Kerja Bakti Minggu Depan" />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="label">Kategori <span class="text-red-500">*</span></label>
              <select v-model="form.kategori" class="input" required>
                <option value="pengumuman">📢 Pengumuman</option>
                <option value="kegiatan">🎉 Kegiatan</option>
                <option value="informasi">ℹ️ Informasi</option>
                <option value="darurat">⚠️ Darurat</option>
              </select>
            </div>
            <div class="flex items-end gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.is_published" type="checkbox" class="w-4 h-4 rounded" />
                <span class="text-sm">Publikasikan</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.is_pinned" type="checkbox" class="w-4 h-4 rounded" />
                <span class="text-sm">📌 Pinned (di atas)</span>
              </label>
            </div>
          </div>

          <div>
            <label class="label">Ringkasan</label>
            <textarea v-model="form.ringkasan" class="input" rows="2" maxlength="500" placeholder="Ringkasan singkat (max 500 karakter)" />
            <p class="text-xs text-gray-400 mt-1">{{ form.ringkasan.length }}/500 karakter</p>
          </div>

          <div>
            <label class="label">Konten Lengkap <span class="text-red-500">*</span></label>
            <div class="border border-gray-300 rounded-lg overflow-hidden">
              <QuillEditor
                v-model:content="form.konten"
                contentType="html"
                theme="snow"
                :toolbar="quillToolbar"
                placeholder="Tulis isi lengkap berita di sini..."
                class="min-h-[300px] bg-white"
              />
            </div>
            <p class="text-xs text-gray-400 mt-1">
              Gunakan toolbar di atas untuk format teks (tebal, miring, link, list, dll).
            </p>
          </div>
        </div>
      </div>

      <!-- Gambar -->
      <div class="card overflow-hidden">
        <div class="bg-gradient-to-r from-primary-700 to-primary-800 px-5 py-3 text-white">
          <h2 class="font-semibold text-sm">🖼️ Gambar Berita (Opsional)</h2>
        </div>
        <div class="p-5">
          <div v-if="preview || form.existing_image" class="mb-3">
            <img :src="preview || form.existing_image" class="max-w-md w-full rounded-lg shadow-sm border" />
            <button type="button" @click="removeImage" class="text-red-600 text-sm mt-2 hover:underline">
              Hapus gambar
            </button>
          </div>
          <input
            type="file"
            accept="image/jpeg,image/png,image/jpg,image/webp"
            @change="onFileChange"
            class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
          />
          <p class="text-xs text-gray-400 mt-2">Format: JPG, PNG, WebP. Max 3 MB.</p>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-red-700 text-sm">
        {{ error }}
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3 bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
        <RouterLink to="/news" class="btn-secondary">Batal</RouterLink>
        <button type="submit" class="btn-primary" :disabled="saving">
          <span v-if="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Berita' : 'Simpan Berita') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import api from '@/services/api'

const quillToolbar = [
  [{ header: [1, 2, 3, false] }],
  ['bold', 'italic', 'underline', 'strike'],
  [{ list: 'ordered' }, { list: 'bullet' }],
  [{ align: [] }],
  ['blockquote'],
  ['link'],
  [{ color: [] }, { background: [] }],
  ['clean'],
]

const route = useRoute()
const router = useRouter()
const toast = useToast()
const saving = ref(false)
const error = ref('')
const isEdit = computed(() => !!route.params.id)

const form = ref({
  judul: '',
  ringkasan: '',
  konten: '',
  kategori: 'pengumuman',
  is_published: true,
  is_pinned: false,
  gambar: null,
  existing_image: '',
})

const preview = ref(null)

function onFileChange(e) {
  const file = e.target.files[0]
  if (!file) return
  form.value.gambar = file
  const reader = new FileReader()
  reader.onload = (ev) => preview.value = ev.target.result
  reader.readAsDataURL(file)
}

function removeImage() {
  form.value.gambar = null
  form.value.existing_image = ''
  preview.value = null
}

async function loadNews() {
  if (!isEdit.value) return
  try {
    const res = await api.get(`/news/${route.params.id}`)
    const n = res.data.news
    form.value = {
      judul: n.judul,
      ringkasan: n.ringkasan ?? '',
      konten: n.konten,
      kategori: n.kategori,
      is_published: n.is_published,
      is_pinned: n.is_pinned,
      gambar: null,
      existing_image: n.gambar
        ? (n.gambar.startsWith('http')
            ? n.gambar
            : (() => {
                const apiBase = import.meta.env.VITE_API_URL || 'https://ipl-griya-pesona-madani.my.id/api/v1'
                const host = apiBase.replace(/\/api\/v\d+\/?$/, '')
                return `${host}${n.gambar}`
              })())
        : '',
    }
  } catch {
    toast.error('Berita tidak ditemukan.')
    router.push('/news')
  }
}

async function submit() {
  error.value = ''
  saving.value = true
  try {
    const fd = new FormData()
    fd.append('judul', form.value.judul)
    fd.append('ringkasan', form.value.ringkasan)
    fd.append('konten', form.value.konten)
    fd.append('kategori', form.value.kategori)
    fd.append('is_published', form.value.is_published ? '1' : '0')
    fd.append('is_pinned', form.value.is_pinned ? '1' : '0')
    if (form.value.gambar) fd.append('gambar', form.value.gambar)

    if (isEdit.value) {
      fd.append('_method', 'PUT')
      await api.post(`/news/${route.params.id}`, fd)
      toast.success('Berita diperbarui.')
    } else {
      await api.post('/news', fd)
      toast.success('Berita berhasil dibuat.')
    }
    router.push('/news')
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(' ') : (e.response?.data?.message ?? 'Gagal menyimpan.')
  } finally {
    saving.value = false
  }
}

onMounted(loadNews)
</script>
