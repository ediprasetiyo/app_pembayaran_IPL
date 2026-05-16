<template>
  <!-- Floating Button -->
  <button
    v-if="!isOpen"
    @click="isOpen = true"
    class="fixed bottom-6 right-6 z-40 bg-gradient-to-br from-primary-600 to-primary-800 hover:from-primary-700 hover:to-primary-900 text-white rounded-full shadow-lg w-14 h-14 flex items-center justify-center transition-transform hover:scale-110"
    title="Buka AI Assistant"
  >
    <SparklesIcon class="w-6 h-6" />
    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
      AI
    </span>
  </button>

  <!-- Chat Panel -->
  <transition name="slide-up">
    <div
      v-if="isOpen"
      class="fixed bottom-6 right-6 z-40 bg-white rounded-2xl shadow-2xl w-[400px] max-w-[calc(100vw-3rem)] flex flex-col"
      style="height: 600px; max-height: calc(100vh - 6rem)"
    >
      <!-- Header -->
      <div class="bg-gradient-to-r from-primary-700 to-primary-900 text-white rounded-t-2xl px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
            <SparklesIcon class="w-5 h-5" />
          </div>
          <div>
            <p class="font-semibold text-sm">AI Assistant</p>
            <p class="text-xs text-primary-100">Asisten cerdas Griya Pesona</p>
          </div>
        </div>
        <button @click="isOpen = false" class="hover:bg-white/20 rounded p-1.5">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Messages -->
      <div ref="messagesEl" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
        <!-- Welcome Message -->
        <div v-if="messages.length === 0" class="space-y-3">
          <div class="bg-white rounded-2xl p-3 shadow-sm">
            <p class="text-sm">👋 Halo! Saya AI Assistant. Bisa bantu:</p>
            <ul class="text-xs text-gray-600 mt-2 space-y-1">
              <li>📋 Cek warga yang belum bayar</li>
              <li>💰 Lihat total pendapatan</li>
              <li>📢 Pengaduan yang perlu ditangani</li>
              <li>💡 Saran solusi pengaduan</li>
              <li>📊 Ringkasan data</li>
            </ul>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="s in suggestions"
              :key="s"
              @click="ask(s)"
              class="text-xs bg-white hover:bg-primary-50 hover:border-primary-300 border border-gray-200 rounded-lg px-3 py-2 text-left transition-colors"
            >
              {{ s }}
            </button>
          </div>
        </div>

        <!-- Chat Messages -->
        <template v-for="(msg, i) in messages" :key="i">
          <!-- User Message -->
          <div v-if="msg.role === 'user'" class="flex justify-end">
            <div class="bg-primary-700 text-white rounded-2xl rounded-tr-sm px-4 py-2 max-w-[85%] text-sm">
              {{ msg.content }}
            </div>
          </div>

          <!-- AI Message -->
          <div v-else class="flex gap-2 max-w-full">
            <div class="w-7 h-7 bg-primary-100 rounded-full flex-shrink-0 flex items-center justify-center mt-1">
              <SparklesIcon class="w-4 h-4 text-primary-700" />
            </div>
            <div class="bg-white rounded-2xl rounded-tl-sm shadow-sm overflow-hidden max-w-[85%]">
              <!-- Pesan teks utama -->
              <div class="px-3 py-2 text-sm whitespace-pre-wrap leading-relaxed" v-html="formatMarkdown(msg.text)"></div>

              <!-- List warga -->
              <div v-if="msg.data?.type === 'list_warga' && msg.data?.data?.length" class="bg-gray-50 p-2 space-y-1.5 border-t">
                <div v-for="(w, idx) in msg.data.data" :key="idx" class="bg-white rounded-lg p-2 text-xs">
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                      <p class="font-medium text-gray-800 truncate">{{ w.nama }}</p>
                      <p class="text-gray-500 text-[10px]">{{ w.alamat }}</p>
                      <p v-if="w.phone" class="text-gray-500 text-[10px]">📞 {{ w.phone }}</p>
                    </div>
                    <p class="font-semibold text-primary-700 text-xs whitespace-nowrap">
                      {{ formatRp(w.nominal) }}
                    </p>
                  </div>
                </div>
                <div v-if="msg.data.total_nominal" class="bg-primary-50 rounded-lg p-2 text-xs flex justify-between font-semibold">
                  <span>Total:</span>
                  <span class="text-primary-700">{{ formatRp(msg.data.total_nominal) }}</span>
                </div>
              </div>

              <!-- List pengaduan -->
              <div v-if="msg.data?.type === 'list_pengaduan' && msg.data?.data?.length" class="bg-gray-50 p-2 space-y-1.5 border-t">
                <div v-for="(p, idx) in msg.data.data" :key="idx" class="bg-white rounded-lg p-2 text-xs">
                  <p class="font-medium text-gray-800">{{ p.judul }}</p>
                  <div class="flex gap-2 mt-1 text-[10px] text-gray-500">
                    <span>📁 {{ p.kategori }}</span>
                    <span>👤 {{ p.warga }}</span>
                    <span>📅 {{ p.tanggal }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- Loading -->
        <div v-if="loading" class="flex gap-2">
          <div class="w-7 h-7 bg-primary-100 rounded-full flex-shrink-0 flex items-center justify-center">
            <SparklesIcon class="w-4 h-4 text-primary-700" />
          </div>
          <div class="bg-white rounded-2xl rounded-tl-sm shadow-sm px-3 py-2.5 flex gap-1">
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms" />
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms" />
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms" />
          </div>
        </div>
      </div>

      <!-- Input -->
      <div class="border-t bg-white p-3 rounded-b-2xl">
        <form @submit.prevent="ask()" class="flex gap-2">
          <input
            v-model="input"
            type="text"
            placeholder="Tanya apa saja..."
            class="flex-1 px-3 py-2 border border-gray-200 rounded-full text-sm focus:outline-none focus:border-primary-500"
            :disabled="loading"
          />
          <button
            type="submit"
            class="bg-primary-700 hover:bg-primary-800 disabled:opacity-50 text-white rounded-full w-9 h-9 flex items-center justify-center"
            :disabled="loading || !input.trim()"
          >
            <PaperAirplaneIcon class="w-4 h-4" />
          </button>
        </form>
        <p class="text-[10px] text-gray-400 text-center mt-1.5">
          AI Assistant • Powered by Griya Pesona Madani
        </p>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { SparklesIcon, XMarkIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline'
import api from '@/services/api'

const isOpen = ref(false)
const input = ref('')
const loading = ref(false)
const messages = ref([])
const messagesEl = ref(null)

const suggestions = [
  'Siapa yang belum bayar?',
  'Pengaduan baru?',
  'Total pendapatan?',
  'Ringkasan bulan ini',
]

async function ask(question) {
  const q = question ?? input.value.trim()
  if (!q || loading.value) return

  messages.value.push({ role: 'user', content: q })
  input.value = ''
  loading.value = true
  await scrollDown()

  try {
    const res = await api.post('/ai/ask', { question: q })
    messages.value.push({
      role: 'ai',
      text: res.data.answer.text,
      data: res.data.answer,
    })
  } catch {
    messages.value.push({
      role: 'ai',
      text: '⚠️ Maaf, terjadi kesalahan. Coba lagi nanti.',
    })
  } finally {
    loading.value = false
    await scrollDown()
  }
}

async function scrollDown() {
  await nextTick()
  if (messagesEl.value) {
    messagesEl.value.scrollTop = messagesEl.value.scrollHeight
  }
}

function formatMarkdown(text) {
  if (!text) return ''
  return text
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.+?)\*/g, '<em>$1</em>')
    .replace(/\n/g, '<br/>')
}

function formatRp(v) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v ?? 0)
}
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.25s ease;
}
.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
