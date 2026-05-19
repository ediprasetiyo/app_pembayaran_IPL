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
/* Modern minimalist date picker theme — inspired by clean design */
.dp__theme_light {
  --dp-primary-color: #15803d;          /* primary-700 */
  --dp-primary-text-color: #ffffff;
  --dp-secondary-color: #94a3b8;
  --dp-text-color: #1e293b;
  --dp-background-color: #ffffff;
  --dp-hover-color: #f0fdf4;
  --dp-hover-text-color: #15803d;
  --dp-hover-icon-color: #15803d;
  --dp-border-color: #e5e7eb;
  --dp-border-color-hover: #15803d;
  --dp-menu-border-color: transparent;
  --dp-disabled-color: #f1f5f9;
  --dp-icon-color: #64748b;
  --dp-success-color: #15803d;
  --dp-danger-color: #dc2626;
  --dp-input-padding: 10px 14px;
  --dp-font-family: inherit;
  --dp-border-radius: 0.625rem;
  --dp-cell-border-radius: 0.5rem;
  --dp-cell-size: 38px;
  --dp-cell-padding: 6px;
  --dp-common-padding: 14px;
  --dp-menu-padding: 12px;
  --dp-button-padding: 8px 14px;
  --dp-action-buttons-padding: 8px 14px;
  --dp-action-button-height: 36px;
  --dp-font-size: 0.875rem;
  --dp-preview-font-size: 0.875rem;
  --dp-time-font-size: 0.875rem;
  --dp-cell-font-size: 0.95rem;
  --dp-month-year-row-height: 40px;
  --dp-month-year-row-button-size: 32px;
}

/* Input field */
.dp__input {
  border: 1px solid #d1d5db !important;
  border-radius: 0.5rem !important;
  padding: 0.625rem 0.875rem !important;
  font-size: 0.875rem !important;
  font-weight: 500;
  color: #1f2937;
  transition: all 0.15s;
}
.dp__input::placeholder {
  color: #9ca3af;
  font-weight: 400;
}
.dp__input:hover {
  border-color: #15803d !important;
}
.dp__input_focus {
  border-color: #15803d !important;
  box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.1) !important;
}

/* Popup menu — clean shadow */
.dp__menu {
  border: none !important;
  border-radius: 1rem !important;
  box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
  padding: 8px !important;
}

/* Cell hover — soft green */
.dp__cell_inner {
  font-weight: 500;
  border-radius: 50% !important;
  transition: all 0.15s;
}
.dp__cell_inner:hover {
  background: #f0fdf4 !important;
  color: #15803d !important;
}

/* Active date — solid green circle */
.dp__active_date {
  background: #15803d !important;
  color: #ffffff !important;
  font-weight: 600;
  box-shadow: 0 4px 6px -1px rgba(21, 128, 61, 0.3);
}

/* Today indicator */
.dp__today {
  border: 2px solid #15803d !important;
}

/* Month/year selector buttons */
.dp__month_year_select {
  font-weight: 600;
  font-size: 0.95rem;
  color: #1f2937;
  border-radius: 0.5rem;
  padding: 0.375rem 0.75rem;
}
.dp__month_year_select:hover {
  background: #f0fdf4;
  color: #15803d;
}

/* Nav arrows */
.dp__inner_nav {
  color: #64748b;
  border-radius: 50%;
  transition: all 0.15s;
}
.dp__inner_nav:hover {
  background: #f0fdf4;
  color: #15803d;
}

/* Year selector grid */
.dp__overlay_cell, .dp__overlay_cell_active {
  font-weight: 500;
  border-radius: 0.5rem !important;
  font-size: 0.9rem;
}
.dp__overlay_cell:hover {
  background: #f0fdf4 !important;
  color: #15803d !important;
}
.dp__overlay_cell_active {
  background: #15803d !important;
  color: white !important;
}

/* Week header */
.dp__calendar_header_item {
  font-weight: 600;
  font-size: 0.75rem;
  color: #6b7280;
  text-transform: uppercase;
}

/* Cell from other month — gray */
.dp__cell_offset {
  color: #d1d5db !important;
}

/* Action buttons */
.dp__action_button {
  border-radius: 0.5rem;
  font-weight: 500;
  font-size: 0.875rem;
}
.dp__action_select {
  background: #15803d;
}
.dp__action_select:hover {
  background: #166534;
}
</style>
