<template>
  <div class="search input-floating bot0">
    <img 
      src="@/assets/images/icons/mdi_search.svg" 
      alt="Search" 
      class="search__icon" 
      @click="onSearchClick"
    />

    <input 
      type="text" 
      :placeholder="computedPlaceholder" 
      :value="modelValue" 
      class="search__input input-floating__field"
      :class="{ active: isActive }"
      id="search"
      ref="inputRef"

    />

    <label for="search" class="input-floating__label">{{ computedLabel }}</label>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

// Props
const props = defineProps<{
  modelValue: string
  placeholder?: string
  label?: string
}>()

// Emits
const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()
// Track active state (input has value or is focused)
const isActive = ref(!!props.modelValue)

// Local input ref
const inputRef = ref<HTMLInputElement | null>(null)

// Computed values
const computedLabel = computed(() => props.label || 'Search')
const computedPlaceholder = computed(() => props.placeholder || 'Search...')

// Input event handler for real-time updates
function onInput(event: Event) {
  const input = event.target as HTMLInputElement
  emit('update:modelValue', input.value)
}

// Icon click handler
function onSearchClick() {
  if (inputRef.value) {
    emit('update:modelValue', inputRef.value.value)
    // Optional: Focus the input for better UX
    inputRef.value.focus()
  }
}
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/forms.scss";
@use "/src/assets/styles/components/dropdown.scss";
</style>