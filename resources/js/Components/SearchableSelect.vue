<template>
  <div ref="rootRef" class="searchable-select-wrap position-relative">
    <button
      type="button"
      class="searchable-select-trigger form-select text-start d-flex align-items-center justify-content-between"
      :class="{
        'is-disabled': disabled || loading,
        'is-open': isOpen,
      }"
      :disabled="disabled"
      @click="toggleOpen"
    >
      <span class="text-truncate" :class="{ 'text-muted': !selectedLabel }">
        <i v-if="loading" class="fa-solid fa-spinner fa-spin me-1 text-primary"></i>
        {{ loading ? 'Memuat...' : (selectedLabel || placeholder) }}
      </span>
    </button>

    <div v-if="isOpen" class="searchable-select-dropdown shadow-lg border rounded-3 bg-white">
      <div class="p-2 border-bottom bg-light sticky-top">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white border-end-0 text-muted">
            <i class="fa-solid fa-magnifying-glass"></i>
          </span>
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            class="form-control border-start-0 shadow-none"
            :placeholder="searchPlaceholder"
            @keydown.esc.stop.prevent="close"
            @keydown.enter.prevent="selectFirst"
          />
          <button
            v-if="searchQuery"
            type="button"
            class="btn btn-outline-secondary"
            @click="searchQuery = ''"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </div>

      <div class="searchable-select-list">
        <div v-if="loading" class="p-3 text-center small text-muted">
          <i class="fa-solid fa-spinner fa-spin me-1"></i> Mengambil data wilayah...
        </div>
        <div v-else-if="filteredOptions.length === 0" class="p-3 text-center small text-muted">
          Data tidak ditemukan.
        </div>
        <button
          v-for="opt in filteredOptions"
          v-else
          :key="opt[valueKey]"
          type="button"
          class="searchable-select-option w-100 text-start border-0 px-3 py-2 small d-flex align-items-center justify-content-between"
          :class="{ 'is-selected': String(opt[valueKey]) === String(modelValue) }"
          @click="pickOption(opt)"
        >
          <span>{{ opt[labelKey] }}</span>
          <i v-if="String(opt[valueKey]) === String(modelValue)" class="fa-solid fa-check text-primary"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number, null], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: '-- Pilih --' },
  searchPlaceholder: { type: String, default: 'Ketik untuk mencari...' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  labelKey: { type: String, default: 'name' },
  valueKey: { type: String, default: 'id' },
})

const emit = defineEmits(['update:modelValue', 'select'])

const rootRef = ref(null)
const searchInputRef = ref(null)
const isOpen = ref(false)
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

const toggleOpen = async () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    searchQuery.value = ''
    await nextTick()
    searchInputRef.value?.focus()
  }
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

const onDocumentClick = (e) => {
  if (rootRef.value && !rootRef.value.contains(e.target)) {
    isOpen.value = false
  }
}

watch(() => props.disabled, (val) => {
  if (val) isOpen.value = false
})

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
})
</script>

<style scoped>
.searchable-select-trigger {
  min-height: 40px;
  background-color: #fff;
  cursor: pointer;
}
.searchable-select-trigger.is-disabled {
  background-color: #f1f5f9;
  cursor: not-allowed;
  opacity: 0.75;
}
.searchable-select-trigger.is-open {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 0.2rem rgba(14, 165, 233, 0.18);
}
.searchable-select-dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 4px);
  z-index: 1090;
  overflow: hidden;
}
.searchable-select-list {
  max-height: 220px;
  overflow-y: auto;
}
.searchable-select-option {
  background: transparent;
  color: #1e293b;
  transition: background-color 0.12s ease;
}
.searchable-select-option:hover {
  background-color: #f0f9ff;
  color: #0369a1;
}
.searchable-select-option.is-selected {
  background-color: #e0f2fe;
  color: #0c4a6e;
  font-weight: 700;
}
</style>

