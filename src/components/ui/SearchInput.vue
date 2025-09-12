<template>
  <div class="search input-floating bot0">
    <img 
      src="@/assets/images/icons/mdi_search.svg" 
      alt="Search" 
      class="search__icon" 
    />

    <input 
      type="text" 
      :placeholder="computedPlaceholder" 
      :value="modelValue" 
      @input="onInput"
      class="search__input input-floating__field"
      id="search"
    />

    <label for="search" class="input-floating__label">{{ computedLabel }}</label>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// Define props with TypeScript types
const props = defineProps<{
  modelValue: string;
  placeholder?: string;
  label?: string;
}>();

// Define emits
const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();

// Fallback for label and placeholder using computed properties
const computedLabel = computed(() => props.label || 'Search');
const computedPlaceholder = computed(() => props.placeholder || 'Search...');

// Handle input event
function onInput(event: Event) {
  emit('update:modelValue', (event.target as HTMLInputElement).value);
}
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/forms.scss";
@use "/src/assets/styles/components/dropdown.scss";
</style>