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
      id="search"
    />

    <label for="search" class="input-floating__label">{{ computedLabel }}</label>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

// Props
const props = defineProps<{
  modelValue: string;
  placeholder?: string;
  label?: string;
}>();

// Emits
const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();

// Local input ref
const inputRef = ref<HTMLInputElement | null>(null);

// Computed values
const computedLabel = computed(() => props.label || 'Search');
const computedPlaceholder = computed(() => props.placeholder || 'Search...');

// Icon click handler
function onSearchClick() {
  if (inputRef.value) {
    emit('update:modelValue', inputRef.value.value);
    console.log('Search icon clicked, input value emitted:', inputRef.value.value);
  }
}
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/forms.scss";
@use "/src/assets/styles/components/dropdown.scss";
</style>