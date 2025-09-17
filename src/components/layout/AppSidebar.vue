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

      <!-- Render menu items dynamically -->
      <template v-for="item in navItems" :key="item.label">
        <!-- If item has submenu, render as NavSubmenu -->
        <NavSubmenu 
          v-if="item.submenu && item.submenu.length > 0"
          :title="item.label" 
          :icon="item.icon"
          :items="getSubmenuWithActiveState(item.submenu)"
          :expanded="item.expanded || false"
        />
        <!-- Otherwise render as regular NavItem -->
        <NavItem 
          v-else
          :href="item.href" 
          :icon="item.icon" 
          :label="item.label"
        />
      </template>
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
import { useRoute } from 'vue-router'
import { useUIStore } from '../../stores/ui'
import NavItem from '@/components/navigation/NavItem.vue';
import NavSubmenu from '@/components/navigation/NavSubmenu.vue';

// Import icons
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

const userName = ref('Neo');
const userRole = ref('Requester');

// Router setup
const route = useRoute()

// use the UI store
const uiStore = useUIStore()
const { isSidebarCollapsed } = storeToRefs(uiStore)

function toggleSidebar() {
  uiStore.toggleSidebar()
}

// Function to add active state to submenu items based on current route
const getSubmenuWithActiveState = (submenuItems: any[]) => {
  return submenuItems.map(item => {
    let isActive = route.path === item.href;
    
    // Check if item has activePatterns and match against current route
    if (item.activePatterns && Array.isArray(item.activePatterns)) {
      isActive = isActive || item.activePatterns.some((pattern: string) => {
        // Convert pattern to regex, replacing :id with [0-9]+ for numeric IDs
        const regexPattern = pattern.replace(/:id/g, '[0-9]+');
        const regex = new RegExp(`^${regexPattern}$`);
        return regex.test(route.path);
      });
    }
    
    return {
      ...item,
      active: isActive
    };
  });
}

// Define menu structure with submenu support
const navItems = [
  { href: '/dashboard', icon: dashboardIcon, label: 'Dashboard' },
  { href: '#', icon: ticketIcon, label: 'Service Tickets' },
  { href: '#', icon: calendarIcon, label: "Engineer's Daily Schedule" },
  
  // Statistical Report with submenu
  { 
    icon: reportIcon, 
    label: 'Statistical Report',
    expanded: false,
    submenu: [
      { href: '/reports/daily', icon: reportIcon, name: 'Daily Reports' },
      { href: '/reports/monthly', icon: reportIcon, name: 'Monthly Reports' },
      { href: '/reports/yearly', icon: reportIcon, name: 'Yearly Reports' },
    ]
  },
  
  // System Manager with submenu
  { 
    icon: systemIcon, 
    label: 'System Manager',
    expanded: false,
    submenu: [
      { href: '/system/logs', icon: systemIcon, name: 'System Logs' },
      { href: '/system/backup', icon: systemIcon, name: 'Backup & Restore' },
      { href: '/system/maintenance', icon: systemIcon, name: 'Maintenance' },
    ]
  },
  
  // Settings with submenu
  { 
    icon: settingsIcon, 
    label: 'Settings',
    expanded: false,
    submenu: [
      { href: '/settings/general', icon: settingsIcon, name: 'General Settings' },
      { href: '/settings/notifications', icon: settingsIcon, name: 'Notifications' },
      { href: '/settings/security', icon: settingsIcon, name: 'Security' },
    ]
  },
  
  // User Management with submenu
  { 
    icon: userGroupIcon, 
    label: 'User Management',
    expanded: false,
    submenu: [
      { href: '/users/list', icon: userGroupIcon, name: 'User List' },
      { href: '/users/roles', icon: userGroupIcon, name: 'Roles & Permissions' },
      { href: '/users/groups', icon: userGroupIcon, name: 'User Groups' },
    ]
  },
  
  // Material Request with submenu
  { 
    icon: materialRequestIcon, 
    label: 'Material Request',
    expanded: false,
    submenu: [
      { href: '/materials/requests', icon: materialRequestIcon, name: 'Request List' },
      { href: '/materials/approval', icon: materialRequestIcon, name: 'Approval Queue' },
      { href: '/materials/history', icon: materialRequestIcon, name: 'Request History' },
    ]
  },
  
  // Asset Management with submenu (expanded by default)
  { 
    icon: mdiAssetManagement, 
    label: 'Asset Management',
    expanded: true,
    submenu: [
      { href: '/assets/dashboard', icon: dashboardIcon, name: 'Dashboard' },
      { href: '/assets/models', icon: assetModelIcon, name: 'Asset Model' },
      { href: '/assets/items', icon: assetItemIcon, name: 'Asset Items', activePatterns: ['/assets/:id'] },
      { href: '/assets/locations', icon: storeIcon, name: 'Store And Location' },
      { href: '/assets/categories', icon: categoryIcon, name: 'Category' },
      { href: '/assets/manufacturers', icon: manufacturerIcon, name: 'Manufacturer' },
    ]
  },
];
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/sidebar.scss";
</style>