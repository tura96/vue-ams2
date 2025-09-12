<template>
  <a :href="href" class="nav__item" :class="{
    'nav__item--expandable': expandable,
    'nav__item--expanded': isExpanded
  }" @click.prevent="handleClick">
    <div class="nav-dropdown-left" :class="{
    'expan': !expandable}" >
      <img :src="icon" :alt="label" class="nav__item-icon">
      <div class="nav-name">{{ label }}</div>
    </div>
    <img v-if="expandable" 
         src="/assets/images/icons/mdi_keyboard-arrow-right.svg" 
         :alt="isExpanded ? 'Collapse' : 'Expand'" 
         class="nav__item-expand">
  </a>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps<{
  href: string
  icon: string
  label: string
  expandable?: boolean
  expanded?: boolean
}>()

const emit = defineEmits<{
  (e: 'click'): void
}>()

const router = useRouter()
const isExpanded = ref(props.expanded ?? false)

function handleClick() {
  if (props.expandable) {
    isExpanded.value = !isExpanded.value
    emit('click')
  } else if (props.href && props.href !== '#') {
    router.push(props.href)
  }
}
</script>
<style scoped lang="scss">
  @use "/src/assets/styles/components/sidebar.scss";
</style>