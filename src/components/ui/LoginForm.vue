<template>
  <div class="login-form">
    <form @submit.prevent="handleLogin">
      <div class="form-group">
        <label for="username">Username:</label>
        <input
          id="username"
          v-model="credentials.username"
          type="text"
          required
          :disabled="authStore.loading"
        />
      </div>
      
      <div class="form-group">
        <label for="password">Password:</label>
        <input
          id="password"
          v-model="credentials.password"
          type="password"
          required
          :disabled="authStore.loading"
        />
      </div>
      
      <button type="submit" :disabled="authStore.loading">
        {{ authStore.loading ? 'Logging in...' : 'Login' }}
      </button>
      
      <div v-if="authStore.error" class="error" v-html="authStore.error">
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const credentials = reactive({
  username: '',
  password: ''
})

const handleLogin = async () => {
  const result = await authStore.login(credentials.username, credentials.password)
  
  if (result.success) {
    router.push('/dashboard')
  }
}
</script>

<style scoped>
.login-form {
  max-width: 400px;
  margin: 0 auto;
  padding: 2rem;
}

.form-group {
  margin-bottom: 1rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
}

input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

button {
  width: 100%;
  padding: 0.75rem;
  background: #0073aa;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error {
  color: red;
  margin-top: 1rem;
}
</style>