<template>
  <div :class="['ai-mascot', { 'ai-mascot--float': animated }]" :style="{ width: size + 'px', height: size + 'px' }">
    <svg viewBox="0 0 100 100" class="w-full h-full">
      <defs>
        <linearGradient :id="gradientId" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#2E7D32" />
          <stop offset="100%" stop-color="#1B5E20" />
        </linearGradient>
      </defs>

      <!-- Antena -->
      <line x1="50" y1="20" x2="50" y2="9" stroke="#1B5E20" stroke-width="3" stroke-linecap="round" />
      <circle cx="50" cy="7" r="4" fill="#81C784" :class="{ 'ai-mascot__antenna': animated }" />

      <!-- Badan -->
      <circle cx="50" cy="56" r="32" :fill="`url(#${gradientId})`" />

      <!-- Mata -->
      <g :class="{ 'ai-mascot__eyes': animated }">
        <circle cx="39" cy="53" r="5" fill="white" />
        <circle cx="61" cy="53" r="5" fill="white" />
      </g>

      <!-- Senyum -->
      <path d="M40 66 Q50 73 60 66" stroke="white" stroke-width="3" fill="none" stroke-linecap="round" />
    </svg>
  </div>
</template>

<script setup>
defineProps({
  size: { type: Number, default: 40 },
  animated: { type: Boolean, default: true },
})

// ID unik per instance biar gradient tidak bentrok kalau dipakai berkali-kali di 1 halaman
const gradientId = `aiMascotGradient-${Math.random().toString(36).slice(2, 9)}`
</script>

<style scoped>
.ai-mascot--float {
  animation: ai-mascot-float 3s ease-in-out infinite;
}

@keyframes ai-mascot-float {
  0%, 100% { transform: translateY(0) rotate(-3deg); }
  50% { transform: translateY(-6px) rotate(3deg); }
}

.ai-mascot__antenna {
  transform-box: fill-box;
  transform-origin: center;
  animation: ai-mascot-antenna 1.6s ease-in-out infinite;
}

@keyframes ai-mascot-antenna {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(1.35); }
}

.ai-mascot__eyes circle {
  transform-box: fill-box;
  transform-origin: center;
  animation: ai-mascot-blink 4s ease-in-out infinite;
}

@keyframes ai-mascot-blink {
  0%, 90%, 100% { transform: scaleY(1); }
  95% { transform: scaleY(0.1); }
}
</style>
