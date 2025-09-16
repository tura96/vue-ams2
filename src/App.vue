<template>
  <div v-if="loading" class="loading">Checking authentication...</div>
  <LoginForm v-else-if="!authStore.isAuthenticated" />
  <AppLayout v-else />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import LoginForm from '@/components/ui/LoginForm.vue';
import { useAuthStore } from '../src/stores/auth';

const authStore = useAuthStore();
const loading = ref(true);

async function initialize() {
  loading.value = true;
  try {
    await authStore.validateToken();
  } catch (err) {
    if (import.meta.env.DEV) console.error('Token validation failed:', err);
  } finally {
    loading.value = false;
  }
}

onMounted(initialize);
</script>

<style lang="scss" scoped>
  // @use "./assets/styles/main.scss" as *;
</style>
