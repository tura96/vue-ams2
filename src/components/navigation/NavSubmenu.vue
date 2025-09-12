<template>
  <div class="nav__submenu">
    <NavItem 
      :href="'#'" 
      :icon="icon" 
      :label="title" 
      :expandable="true" 
      :expanded="isExpanded"
      @click="toggle"
    />
    
    <div class="nav__sublist" v-if="isExpanded">
      <a v-for="item in items" :key="item.name" 
         :href="item.href" class="nav__subitem"
         :class="{'nav__subitem--active': item.active}"
         @click.prevent="navigateTo(item.href)">
        <img :src="item.icon" :alt="item.name" class="nav__subitem-icon">
        <div class="nav-name">{{ item.name }}</div>
      </a>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, defineProps } from 'vue'
import { useRouter } from 'vue-router'
import NavItem from './NavItem.vue'

interface SubItem {
  href: string
  icon: string
  name: string
  active?: boolean
}

const props = defineProps<{
  title: string
  icon: string
  items: SubItem[]
  expanded?: boolean
}>()

const isExpanded = ref(props.expanded ?? false)
const router = useRouter()

function toggle() {
  isExpanded.value = !isExpanded.value
}

function navigateTo(path: string) {
  router.push(path)
}
</script>
<style scoped lang="scss">
  @use "/src/assets/styles/components/sidebar.scss";
</style>