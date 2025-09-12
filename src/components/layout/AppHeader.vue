<template>
  <header class="main__header">
    <h1 class="main__title">{{ title }}</h1>
    <div class="main__actions">
      <button class="notification">
        <img 
          src="/assets/images/icons/mdi_notifications-none.svg" 
          alt="Notifications" 
          class="notification__icon" 
        />
      </button>
      <button class="mb mobile__menu" @click="toggleMobileMenu">
        <img 
          src="/assets/images/icons/mdi-hamburger-menu.svg" 
          alt="Menu" 
          class="mobile__icon" 
        />
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
// --- Props ---
defineProps<{
  title?: any
}>()
// const title = 'Test'

// --- Emits ---
const emit = defineEmits<{
  (e: 'toggle-sidebar'): void
}>()

// --- Methods ---
function toggleMobileMenu() {
  emit('toggle-sidebar')
  console.log('click mobile')
}
</script>
