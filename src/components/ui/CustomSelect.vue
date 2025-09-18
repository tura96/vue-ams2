<template>
  <div
    class="custom-select"
    :class="{ 'custom-select--open': isOpen, 'custom-select--disabled': disabled }"
    ref="selectContainer"
    tabindex="0"
    @keydown="handleKeydown"
  >
    <div
      class="custom-select__trigger"
      :class="{ 'custom-select__trigger--open': isOpen, 'active': !! props.modelValue }"
      @click.stop="toggleOpen"
    >
      <span
        class="custom-select__value"
        :class="{ 'custom-select__value--placeholder': !props.modelValue }"
      >
        {{ selectedLabel }}
      </span>
      <div class="custom-select__arrow"></div>
    </div>
    <span class="custom-select__label">{{ label }}</span>
    <div class="custom-select__dropdown" :class="{ 'custom-select__dropdown--open': isOpen }" v-if="isOpen">
      <div
        v-for="(option, index) in options"
        :key="option.value"
        class="custom-select__option"
        :class="{ 'custom-select__option--selected': option.value === props.modelValue, 'custom-select__option--highlighted': highlightedIndex === index }"
        :data-value="option.value"
        @click.stop="selectOption(option)"
      >
        {{ option.label }}
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';

interface Option {
  value: string | number;
  label: string;
}

const props = defineProps<{
  modelValue?: string | number;
  label?: string;
  options?: Option[];
  placeholder?: string;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number): void;
}>();

const isOpen = ref(false);
const highlightedIndex = ref(-1);
const selectContainer = ref<HTMLElement | null>(null);

const options = computed(() => props.options ?? []);
const placeholder = computed(() => props.placeholder ?? '');

const selectedLabel = computed(() => {
  const selected = options.value.find(opt => opt.value === props.modelValue);
  return selected ? selected.label : placeholder.value;
});

function toggleOpen(event: MouseEvent) {
  // if (props.disabled) {
    console.log('Debug: Select is disabled, ignoring toggle',event);
  //   return;
  // }
  // isOpen.value = !isOpen.value;
  // if (isOpen.value) {
  //   highlightedIndex.value = options.value.findIndex(opt => opt.value === props.modelValue);
  // } else {
  //   highlightedIndex.value = -1;
  // }
  if (props.disabled) {
    // console.log('Debug: Select is disabled, ignoring toggle', event);
    return;
  }
  
  // Close all other open selects first
  if (!isOpen.value) {
    const allOpenSelects = document.querySelectorAll('.custom-select--open');
    allOpenSelects.forEach(select => {
      if (select !== selectContainer.value) {
        select.classList.remove('custom-select--open');
        const dropdown = select.querySelector('.custom-select__dropdown--open');
        const trigger = select.querySelector('.custom-select__trigger');
        if (dropdown) {
          dropdown.classList.remove('custom-select__dropdown--open');
        }
        if (trigger) {
          trigger.classList.remove('custom-select__dropdown--open');
        }
      }
    });
  }
  
  isOpen.value = !isOpen.value;
  
  if (isOpen.value) {
    highlightedIndex.value = options.value.findIndex(opt => opt.value === props.modelValue);
  } else {
    highlightedIndex.value = -1;
  }
}

function selectOption(option: Option) {
  emit('update:modelValue', option.value);
  isOpen.value = false;
  highlightedIndex.value = -1;
}

function handleKeydown(event: KeyboardEvent) {
  if (props.disabled) return;

  switch (event.key) {
    case 'Enter':
    case ' ':
      event.preventDefault();
      if (isOpen.value) {
        if (highlightedIndex.value >= 0) {
          selectOption(options.value[highlightedIndex.value]);
        }
      } else {
        toggleOpen(new MouseEvent('click'));
      }
      break;
    case 'Escape':
      event.preventDefault();
      isOpen.value = false;
      highlightedIndex.value = -1;
      selectContainer.value?.focus();
      break;
    case 'ArrowDown':
      event.preventDefault();
      navigateOptions(1);
      break;
    case 'ArrowUp':
      event.preventDefault();
      navigateOptions(-1);
      break;
  }
}

function navigateOptions(direction: number) {
  if (!isOpen.value) {
    toggleOpen(new MouseEvent('click'));
    return;
  }

  let newIndex = highlightedIndex.value + direction;
  if (newIndex >= options.value.length) {
    newIndex = 0;
  } else if (newIndex < 0) {
    newIndex = options.value.length - 1;
  }
  highlightedIndex.value = newIndex;
}

function handleClickOutside(event: MouseEvent) {
  if (selectContainer.value && !selectContainer.value.contains(event.target as Node)) {
    // console.log('Debug: Clicked outside, closing dropdown');
    nextTick(() => {
      isOpen.value = false;
      highlightedIndex.value = -1;
      if (props.modelValue) {
        selectContainer.value?.classList.add('active');
      }
    });
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/forms.scss";
@use "/src/assets/styles/components/dropdown.scss";
</style>