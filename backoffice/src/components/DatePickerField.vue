<script setup>
import { computed } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const props = defineProps({
  modelValue: { type: [String, Date, null], default: null },
  placeholder: { type: String, default: 'Pilih tanggal' },
  minDate: { type: [String, Date, null], default: null },
  maxDate: { type: [String, Date, null], default: null },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

/**
 * Parse berbagai format date jadi Date object (LOCAL time, time = 00:00:00)
 * Supported: "YYYY-MM-DD", ISO "2025-01-01T00:00:00.000000Z", Date object
 */
function parseToDate(val) {
  if (!val) return null
  if (val instanceof Date) return val
  if (typeof val === 'string') {
    // Plain YYYY-MM-DD → parse as LOCAL date (hindari timezone shift)
    const ymd = val.match(/^(\d{4})-(\d{2})-(\d{2})/)
    if (ymd) {
      return new Date(parseInt(ymd[1]), parseInt(ymd[2]) - 1, parseInt(ymd[3]))
    }
    // ISO or other format
    const d = new Date(val)
    if (!isNaN(d.getTime())) {
      // Reset ke local midnight untuk hindari time component
      return new Date(d.getFullYear(), d.getMonth(), d.getDate())
    }
  }
  return null
}

const dateValue = computed({
  get() {
    return parseToDate(props.modelValue)
  },
  set(val) {
    if (!val) return emit('update:modelValue', '')
    // STRICT: always YYYY-MM-DD only, NO TIME
    const y = val.getFullYear()
    const m = String(val.getMonth() + 1).padStart(2, '0')
    const d = String(val.getDate()).padStart(2, '0')
    emit('update:modelValue', `${y}-${m}-${d}`)
  }
})
</script>

<template>
  <VueDatePicker
    v-model="dateValue"
    :placeholder="placeholder"
    :enable-time-picker="false"
    :min-date="minDate"
    :max-date="maxDate"
    :disabled="disabled"
    format="dd MMM yyyy"
    locale="id-ID"
    cancel-text="Batal"
    select-text="Pilih"
    :year-range="[1940, new Date().getFullYear() + 5]"
    :year-first="false"
    month-name-format="long"
    auto-apply
    :clearable="true"
    text-input
    :teleport="true"
    :hide-input-icons="false"
  />
</template>

<style>
/* Konsistensi dengan tema Tailwind */
.dp__theme_light {
  --dp-primary-color: #15803d;     /* primary-700 */
  --dp-primary-text-color: #ffffff;
  --dp-border-color: #d1d5db;       /* gray-300 */
  --dp-border-color-hover: #15803d;
  --dp-input-padding: 9px 12px;
  --dp-font-family: inherit;
  --dp-border-radius: 0.5rem;
}
.dp__input {
  font-size: 0.875rem;
  border-color: #d1d5db;
}
.dp__input:focus {
  border-color: #15803d;
  box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.1);
}
</style>
