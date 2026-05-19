<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Date, null], default: null },
  placeholder: { type: String, default: 'dd/mm/yyyy' },
  minDate: { type: [String, Date, null], default: null },
  maxDate: { type: [String, Date, null], default: null },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

/**
 * Convert input ke format YYYY-MM-DD untuk <input type="date">
 * Handle: ISO "2025-01-01T00:00:00.000000Z", "YYYY-MM-DD", Date object
 */
function toDateString(val) {
  if (!val) return ''
  if (typeof val === 'string') {
    // Sudah format YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val
    // ISO atau format lain → parse
    const d = new Date(val)
    if (isNaN(d.getTime())) return ''
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
  }
  if (val instanceof Date) {
    const y = val.getFullYear()
    const m = String(val.getMonth() + 1).padStart(2, '0')
    const day = String(val.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
  }
  return ''
}

const dateValue = computed({
  get() {
    return toDateString(props.modelValue)
  },
  set(val) {
    // val dari <input type="date"> selalu "YYYY-MM-DD" atau empty
    emit('update:modelValue', val || '')
  }
})

const minDateStr = computed(() => toDateString(props.minDate))
const maxDateStr = computed(() => toDateString(props.maxDate))
</script>

<template>
  <input
    type="date"
    class="input"
    v-model="dateValue"
    :placeholder="placeholder"
    :min="minDateStr || undefined"
    :max="maxDateStr || undefined"
    :disabled="disabled"
  />
</template>

<style scoped>
.input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #1f2937;
  background-color: #ffffff;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.input:hover {
  border-color: #15803d;
}
.input:focus {
  outline: none;
  border-color: #15803d;
  box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.1);
}
.input:disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
}
</style>
