<template>
  <div class="app-layout" :class="{ 'sidebar-open': !isSidebarCollapsed }">
    <AppSidebar 
      :is-collapsed="isSidebarCollapsed"
      @toggle="toggleSidebar"
    />
    <div class="sidebar-overlay" @click="toggleSidebar"></div>
    
    <div class="app-main" :class="{ 'sidebar-open': !isSidebarCollapsed }">
      <AppHeader 
        :title="currentRouteTitle"
        @toggle-sidebar="toggleSidebar"
        :is-sidebar-collapsed="isSidebarCollapsed"
      />
      <main class="app-content">
         <router-view />
      </main>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed } from 'vue'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import { useRoute } from 'vue-router';

const isSidebarCollapsed = ref(true)

const route = useRoute();

// Compute the current route's title from meta data, fallback to 'Asset Management'
const currentRouteTitle = computed(() => route.meta.title || 'Asset Management');

function toggleSidebar() {
  console.log('toggleSidebar called, current isSidebarCollapsed:', isSidebarCollapsed.value);
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
  console.log('New isSidebarCollapsed:', isSidebarCollapsed.value);
}
</script>

<style lang="scss" scoped>
  @use "/src/assets/styles/base.scss";
  
.app-layout {
  display: flex;
  min-height: 100vh;
  @media (max-width: 768px) {
    flex-direction: column;
  } 
}

.app-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  @media screen and (max-width: 1200px) {
    &.sidebar-open{
      max-height: 100vh;
      overflow: hidden;
    }
  }
}

.app-content {
  flex: 1;
  padding: 34px;
  overflow-y: auto;
  background: var(--bg-secondary);
  @media screen and (max-width: 1200px) {
    margin-top: 80px;
  }
}
@media screen and (max-width: 1200px) {
    .sidebar-open{
      .sidebar-overlay{
        position: fixed;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: lightgray;
        opacity: 0.5;
        z-index: 1;
      }
    }
  }
</style>