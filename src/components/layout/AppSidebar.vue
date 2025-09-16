<template>
  <aside class="sidebar" :class="{ 'open': !isSidebarCollapsed, 'close': isSidebarCollapsed }">
    <nav class="sidebar__nav">
      <div class="sidebar__header">
        <div class="sidebar__logo">
          <img src="@/assets/images/logo-stpl.png" alt="Logo" class="logo-svg">
        </div>
      </div>
      
      <div class="sidebar__user">
        <div class="sidebar__user-ava">
          <img src="@/assets/images/icons/mdi_user-circle-outline.svg" 
               alt="Avatar" class="sidebar__user-svg">
        </div>
        <div class="sidebar__user-info">
          <span class="sidebar__user-name">{{ userName }}</span>
          <span class="sidebar__user-role">{{ userRole }}</span>
        </div>
        <img src="@/assets/images/icons/mdi_keyboard-arrow-right.svg" 
             alt="Expand" class="sidebar__user-toggle">
      </div>

      <NavItem v-for="item in navItems" :key="item.label" v-bind="item" />
      
      <NavSubmenu 
        title="Asset Management" 
        :icon="mdiAssetManagement"
        :items="assetSubItems"
        :expanded="true"
      />
    </nav>
    
    <div class="sidebar__collapse">
      <button class="sidebar__collapse-btn" @click="toggleSidebar">
        <img src="@/assets/images/icons/mdi_arrow-left-drop-circle-outline.svg" 
             alt="Collapse Menu" class="sidebar__collapse-icon" :class="{ 'close' : isSidebarCollapsed  }">
             <div class="nav-name">
               {{ isSidebarCollapsed ? 'Expand' : 'Collapse' }} Menu
             </div>
      </button>
    </div>
  </aside>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import { storeToRefs } from 'pinia'
import { useUIStore } from '../../stores/ui'
import NavItem from '@/components/navigation/NavItem.vue';
import NavSubmenu from '@/components/navigation/NavSubmenu.vue';

// Import icons for navItems and assetSubItems
import dashboardIcon from '@/assets/images/icons/mdi_view-dashboard-outline.svg';
import ticketIcon from '@/assets/images/icons/mdi_ticket-outline.svg';
import calendarIcon from '@/assets/images/icons/mdi_calendar-range-outline.svg';
import reportIcon from '@/assets/images/icons/mdi_report-box-outline.svg';
import systemIcon from '@/assets/images/icons/mdi_system.svg';
import settingsIcon from '@/assets/images/icons/mdi_settings-outline.svg';
import userGroupIcon from '@/assets/images/icons/mdi_user-group-outline.svg';
import materialRequestIcon from '@/assets/images/icons/mdi_file-report-outline.svg';
import mdiAssetManagement from '@/assets/images/icons/mdi_asset-management.svg';
import assetModelIcon from '@/assets/images/icons/mdi-asset-model.svg';
import assetItemIcon from '@/assets/images/icons/mdi_asset-item.svg';
import storeIcon from '@/assets/images/icons/material-symbols_store-outline-rounded.svg';
import categoryIcon from '@/assets/images/icons/mdi_category-outline.svg';
import manufacturerIcon from '@/assets/images/icons/material-symbols_precision-manufacturing-outline-rounded.svg';

// const isCollapsed = ref(false);
const userName = ref('Neo');
const userRole = ref('Requester');

// // Define props
// const props = defineProps<{
//   isCollapsed: boolean;
// }>();
// console.log('Header prop: ', props)

// // Define emits
// const emit = defineEmits<{
//   (e: 'toggle'): void;
// }>();

// use the UI store
const uiStore = useUIStore()
const { isSidebarCollapsed } = storeToRefs(uiStore)

function toggleSidebar() {
  uiStore.toggleSidebar()
}

const navItems = [
  { href: '/dashboard', icon: dashboardIcon, label: 'Dashboard' },
  { href: '#', icon: ticketIcon, label: 'Service Tickets' },
  { href: '#', icon: calendarIcon, label: "Engineer's Daily Schedule" },
  { href: '#', icon: reportIcon, label: 'Statistical Report', expandable: true },
  { href: '#', icon: systemIcon, label: 'System Manager', expandable: true },
  { href: '#', icon: settingsIcon, label: 'Settings', expandable: true },
  { href: '#', icon: userGroupIcon, label: 'User Management', expandable: true },
  { href: '#', icon: materialRequestIcon, label: 'Material Request', expandable: true },
];

const assetSubItems = [
  { href: '#', icon: dashboardIcon, name: 'Dashboard' },
  { href: '#', icon: assetModelIcon, name: 'Asset Model' },
  { href: '/', icon: assetItemIcon, name: 'Asset Items', active: true },
  { href: '#', icon: storeIcon, name: 'Store And Location' },
  { href: '#', icon: categoryIcon, name: 'Category' },
  { href: '#', icon: manufacturerIcon, name: 'Manufacturer' },
];

// function toggleSidebar() {
//   isCollapsed.value = !isCollapsed.value;
// }

// function toggleSidebar() {
//   emit('toggle');
// }
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/sidebar.scss";
</style>