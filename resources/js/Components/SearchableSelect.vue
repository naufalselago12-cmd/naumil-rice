<template>
  <div ref="rootRef" class="ss-wrap" :class="{ 'is-open': isOpen }">
    <button
      :id="buttonId"
      type="button"
      class="ss-trigger"
      :class="{
        'is-disabled': disabled,
        'is-open': isOpen,
      }"
      :disabled="disabled"
      :aria-expanded="isOpen ? 'true' : 'false'"
      @click="toggleOpen"
    >
      <span class="ss-trigger-text" :class="{ 'is-placeholder': !selectedLabel }">
        <i v-if="loading" class="fa-solid fa-spinner fa-spin ss-spin"></i>
        {{ loading && options.length === 0 ? 'Memuat data...' : (selectedLabel || placeholder) }}
      </span>
      <i class="fa-solid fa-chevron-down ss-chevron" :class="{ 'is-rotated': isOpen }" aria-hidden="true"></i>
    </button>

    <div
      v-if="isOpen"
      class="ss-dropdown"
      :class="{ 'is-upward': openUpward }"
      @click.stop
    >
      <div class="ss-search-header">
        <div class="ss-search-box">
          <i class="fa-solid fa-magnifying-glass ss-search-icon" aria-hidden="true"></i>
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            class="ss-search-input"
            :placeholder="searchPlaceholder"
            autocorrect="off"
            autocapitalize="none"
            spellcheck="false"
            @keydown.esc.stop.prevent="close"
            @keydown.enter.prevent="selectFirst"
          />
          <button
            v-if="searchQuery"
            type="button"
            class="ss-clear-btn"
            aria-label="Hapus kata kunci"
            @click="searchQuery = ''"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </div>

      <div class="ss-list">
        <div v-if="loading && options.length === 0" class="ss-state">
          <i class="fa-solid fa-spinner fa-spin ss-spin"></i> Mengambil data wilayah...
        </div>
        <div v-else-if="loadError" class="ss-state ss-state-error">
          <div>{{ loadError }}</div>
          <button type="button" class="ss-retry-btn" @click="$emit('retry')">
            <i class="fa-solid fa-rotate-right"></i> Muat Ulang
          </button>
        </div>
        <div v-else-if="filteredOptions.length === 0" class="ss-state">
          {{ options.length === 0 ? emptyMessage : 'Data tidak ditemukan.' }}
        </div>
        <button
          v-for="opt in filteredOptions"
          v-else
          :key="opt[valueKey]"
          type="button"
          class="ss-option"
          :class="{ 'is-selected': String(opt[valueKey]) === String(modelValue) }"
          @click="pickOption(opt)"
        >
          <span class="ss-option-label">{{ opt[labelKey] }}</span>
          <i v-if="String(opt[valueKey]) === String(modelValue)" class="fa-solid fa-check ss-check"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  buttonId: { type: String, default: undefined },
  modelValue: { type: [String, Number, null], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: '-- Pilih --' },
  searchPlaceholder: { type: String, default: 'Ketik untuk mencari...' },
  emptyMessage: { type: String, default: 'Pilihan belum tersedia.' },
  loadError: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  labelKey: { type: String, default: 'name' },
  valueKey: { type: String, default: 'id' },
})

const emit = defineEmits(['update:modelValue', 'select', 'retry'])

const rootRef = ref(null)
const searchInputRef = ref(null)
const isOpen = ref(false)
const openUpward = ref(false)
const searchQuery = ref('')

const selectedOption = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
    return null
  }
  return props.options.find((opt) => String(opt[props.valueKey]) === String(props.modelValue)) || null
})

const selectedLabel = computed(() => {
  return selectedOption.value ? selectedOption.value[props.labelKey] : ''
})

const filteredOptions = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return props.options
  return props.options.filter((opt) =>
    String(opt[props.labelKey] || '').toLowerCase().includes(q)
  )
})

const computePlacement = () => {
  if (!rootRef.value) {
    openUpward.value = false
    return
  }
  const rect = rootRef.value.getBoundingClientRect()
  const modalParent = rootRef.value.closest('.modal-card-custom, .modal-content')
  const bottomLimit = modalParent
    ? Math.min(window.innerHeight, modalParent.getBoundingClientRect().bottom)
    : window.innerHeight
  const topLimit = modalParent
    ? Math.max(0, modalParent.getBoundingClientRect().top)
    : 0
  const spaceBelow = bottomLimit - rect.bottom
  const spaceAbove = rect.top - topLimit
  openUpward.value = spaceBelow < 240 && spaceAbove > spaceBelow
}

const toggleOpen = async () => {
  if (props.disabled) return
  if (isOpen.value) {
    isOpen.value = false
    return
  }
  computePlacement()
  isOpen.value = true
  searchQuery.value = ''
  await nextTick()
  searchInputRef.value?.focus()
}

const close = () => {
  isOpen.value = false
}

const pickOption = (opt) => {
  emit('update:modelValue', opt[props.valueKey])
  emit('select', opt)
  isOpen.value = false
  searchQuery.value = ''
}

const selectFirst = () => {
  if (filteredOptions.value.length > 0) {
    pickOption(filteredOptions.value[0])
  }
}

const onDocumentPointerDown = (e) => {
  if (rootRef.value && !rootRef.value.contains(e.target)) {
    isOpen.value = false
  }
}

watch(() => props.disabled, (val) => {
  if (val) isOpen.value = false
})

onMounted(() => {
  document.addEventListener('pointerdown', onDocumentPointerDown)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocumentPointerDown)
})
</script>

<style scoped>
.ss-wrap {
  position: relative;
  width: 100%;
  min-inline-size: 0;
  z-index: 2;
}

.ss-wrap.is-open {
  z-index: 2200;
}

.ss-trigger {
  width: 100%;
  min-height: 44px;
  padding: 0.55rem 0.85rem;
  background-color: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  font-size: max(0.92rem, 15px);
  color: #0f172a;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  text-align: left;
  cursor: pointer;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
}

.ss-trigger:hover:not(:disabled) {
  border-color: #94a3b8;
}

.ss-trigger:focus-visible {
  outline: 2px solid #e11d48;
  outline-offset: 2px;
}

.ss-trigger.is-disabled {
  background-color: #f1f5f9;
  border-color: #e2e8f0;
  color: #94a3b8;
  cursor: not-allowed;
}

.ss-trigger.is-open {
  border-color: #e11d48;
  box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.14);
}

.ss-trigger-text {
  flex: 1;
  min-inline-size: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 500;
}

.ss-trigger-text.is-placeholder {
  color: #64748b;
  font-weight: 400;
}

.ss-chevron {
  font-size: 0.78rem;
  color: #64748b;
  flex-shrink: 0;
  transition: transform 0.18s ease;
}

.ss-chevron.is-rotated {
  transform: rotate(180deg);
  color: #e11d48;
}

.ss-spin {
  color: #e11d48;
  margin-right: 0.35rem;
}

.ss-dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 6px);
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 12px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.18);
  overflow: hidden;
  z-index: 2201;
}

.ss-dropdown.is-upward {
  top: auto;
  bottom: calc(100% + 6px);
}

.ss-search-header {
  padding: 0.55rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.ss-search-box {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  padding: 0.25rem 0.55rem;
}

.ss-search-box:focus-within {
  border-color: #e11d48;
  box-shadow: 0 0 0 2px rgba(225, 29, 72, 0.12);
}

.ss-search-icon {
  color: #64748b;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.ss-search-input {
  width: 100%;
  min-inline-size: 0;
  min-height: 34px;
  border: none;
  outline: none;
  background: transparent;
  font-size: max(0.9rem, 15px);
  color: #0f172a;
}

.ss-clear-btn {
  border: none;
  background: #f1f5f9;
  color: #475569;
  width: 26px;
  height: 26px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
}

.ss-clear-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.ss-list {
  max-height: 225px;
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-gutter: stable;
}

.ss-state {
  padding: 1rem 0.85rem;
  text-align: center;
  font-size: 0.85rem;
  color: #64748b;
}

.ss-state-error {
  color: #b91c1c;
}

.ss-retry-btn {
  margin-top: 0.5rem;
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
  border: 1px solid #e11d48;
  background: #fff1f2;
  color: #be123c;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

.ss-option {
  width: 100%;
  min-height: 42px;
  padding: 0.55rem 0.85rem;
  border: none;
  border-bottom: 1px solid #f1f5f9;
  background: #ffffff;
  color: #1e293b;
  font-size: 0.88rem;
  text-align: left;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  cursor: pointer;
  transition: background-color 0.12s ease, color 0.12s ease;
}

.ss-option:last-child {
  border-bottom: none;
}

.ss-option:hover {
  background-color: #fff1f2;
  color: #be123c;
}

.ss-option.is-selected {
  background-color: #ffe4e6;
  color: #9f1239;
  font-weight: 700;
}

.ss-option-label {
  flex: 1;
  min-inline-size: 0;
}

.ss-check {
  color: #e11d48;
  flex-shrink: 0;
}
</style>

