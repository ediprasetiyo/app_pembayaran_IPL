<script setup>
import { computed } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const props = defineProps({
  modelValue: { type: [String, Date, null], default: null },
  placeholder: { type: String, default: 'Pilih tanggal' },
  minDate: { type: [String, Date, null], default: null },
  maxDate: { type: [String, Date, null], default: null },
  // 'date' (default) atau 'datetime'
  enableTime: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

// Convert ISO/Date ke Date object untuk vue-datepicker
const dateValue = computed({
  get() {
    if (!props.modelValue) return null
    if (props.modelValue instanceof Date) return props.modelValue
    // ISO string atau "YYYY-MM-DD"
    const d = new Date(props.modelValue)
    return isNaN(d.getTime()) ? null : d
  },
  set(val) {
    if (!val) return emit('update:modelValue', '')
    // Format ke YYYY-MM-DD untuk konsistensi dengan backend Laravel
    const y = val.getFullYear()
    const m = String(val.getMonth() + 1).padStart(2, '0')
    const d = String(val.getDate()).padStart(2, '0')
    emit('update:modelValue', props.enableTime
      ? `${y}-${m}-${d} ${String(val.getHours()).padStart(2,'0')}:${String(val.getMinutes()).padStart(2,'0')}:00`
      : `${y}-${m}-${d}`)
  }
})
</script>

<template>
  <VueDatePicker
    v-model="dateValue"
    :placeholder="placeholder"
    :enable-time-picker="enableTime"
    :min-date="minDate"
    :max-date="maxDate"
    :disabled="disabled"
    :format="enableTime ? 'dd MMM yyyy HH:mm' : 'dd MMM yyyy'"
    locale="id-ID"
    cancel-text="Batal"
    select-text="Pilih"
    :year-range="[1940, new Date().getFullYear() + 5]"
    auto-apply
    :clearable="true"
    text-input
    :teleport="true"
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
