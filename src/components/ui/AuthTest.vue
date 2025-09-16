<template>
  <div class="auth-test">
    <div class="test-section">
      <h2>WordPress JWT Authentication Test</h2>
      
      <!-- Environment Info -->
      <div class="info-panel">
        <h3>Configuration</h3>
        <p><strong>API URL:</strong> {{ apiUrl }}</p>
        <p><strong>Token:</strong> {{ authStore.token ? 'Present' : 'None' }}</p>
        <p><strong>User:</strong> {{ authStore.user?.username || 'Not logged in' }}</p>
        <p><strong>Authenticated:</strong> {{ authStore.isAuthenticated ? 'Yes' : 'No' }}</p>
      </div>

      <!-- Login Test -->
      <div class="test-panel" v-if="!authStore.isLoggedIn">
        <h3>Test Login</h3>
        <form @submit.prevent="testLogin">
          <div class="form-row">
            <input 
              v-model="testCredentials.username" 
              type="text" 
              placeholder="Username" 
              required 
            />
            <input 
              v-model="testCredentials.password" 
              type="password" 
              placeholder="Password" 
              required 
            />
            <button type="submit" :disabled="testing">
              {{ testing ? 'Testing...' : 'Test Login' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Logout Test -->
      <div class="test-panel" v-if="authStore.isLoggedIn">
        <h3>Logged In</h3>
        <p>Welcome, {{ authStore.user?.displayName }}!</p>
        <button @click="authStore.logout()" class="btn-danger">
          Logout
        </button>
      </div>

      <!-- API Tests -->
      <div class="test-panel">
        <h3>API Endpoint Tests</h3>
        <div class="test-buttons">
          <button @click="testEndpoint('/ams/v1/auth/login', 'POST')" :disabled="testing">
            Test AMS Login (POST)
          </button>
          <button @click="testEndpoint('/ams/v1/auth/validate', 'POST')" :disabled="!authStore.token || testing">
            Test AMS Token Validate (POST)
          </button>
          <button @click="testEndpoint('/ams/v1/assets')" :disabled="!authStore.token || testing">
            Test Get Assets (GET)
          </button>
          <button @click="testEndpoint('/ams/v1/assets/88')" :disabled="!authStore.token || testing">
            Test Get Asset #88 (GET)
          </button>
          <button @click="testEndpoint('/ams/v1/assets', 'POST', { title: 'Test Asset', description: 'Created from UI' })" :disabled="!authStore.token || testing">
            Test Create Asset (POST)
          </button>
          <button @click="testEndpoint('/ams/v1/assets/88', 'PUT', { title: 'Updated Asset' })" :disabled="!authStore.token || testing">
            Test Update Asset #88 (PUT)
          </button>
          <button @click="testEndpoint('/ams/v1/assets/88', 'DELETE')" :disabled="!authStore.token || testing">
            Test Delete Asset #88 (DELETE)
          </button>
          <button @click="testEndpoint('/ams/v1/assets/bulk-deploy', 'POST', { asset_ids: [1,2] })" :disabled="!authStore.token || testing">
            Test Bulk Deploy (POST)
          </button>
          <button @click="testEndpoint('/ams/v1/metadata/statuses')" :disabled="!authStore.token || testing">
            Test Get Statuses (GET)
          </button>
          <button @click="testEndpoint('/ams/v1/metadata/models')" :disabled="!authStore.token || testing">
            Test Get Models (GET)
          </button>
          <button @click="testEndpoint('/ams/v1/metadata/locations')" :disabled="!authStore.token || testing">
            Test Get Locations (GET)
          </button>
        </div>
      </div>

      <!-- Results -->
      <div class="results-panel" v-if="lastResult">
        <h3>Last Test Result</h3>
        <div class="result-status" :class="lastResult.success ? 'success' : 'error'">
          {{ lastResult.success ? 'SUCCESS' : 'ERROR' }}
        </div>
        <div class="result-data">
          <pre>{{ JSON.stringify(lastResult.data, null, 2) }}</pre>
        </div>
      </div>

      <!-- Error Display -->
      <div class="error-panel" v-if="authStore.error">
        <h3>Authentication Error</h3>
        <p>{{ authStore.error }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
// import { useAssetStore } from '@/stores/assets'
import axios from 'axios'

// Composables
const authStore = useAuthStore()

// Reactive data
const testCredentials = ref({
  username: '',
  password: ''
})

const testing = ref(false)
const lastResult = ref<any>(null)

// Computed
const apiUrl = computed(() => import.meta.env.VITE_API_URL)

// Methods
const testLogin = async () => {
  testing.value = true
  lastResult.value = null
  
  try {
    const result = await authStore.login(
      testCredentials.value.username,
      testCredentials.value.password
    )
    
    lastResult.value = {
      success: result.success,
      data: result.success ? result.user : result.error
    }
  } catch (error) {
    lastResult.value = {
      success: false,
      data: error
    }
  } finally {
    testing.value = false
  }
}

const testEndpoint = async (endpoint: string, method: string = 'GET', body?: any) => {
  testing.value = true
  lastResult.value = null

  try {
    const url = `${import.meta.env.VITE_API_URL}${endpoint}`
    const headers: any = {
      'Content-Type': 'application/json'
    }

    if (authStore.token) {
      headers.Authorization = `Bearer ${authStore.token}`
    }

    let response
    if (method === 'POST') {
      response = await axios.post(url, body || {}, { headers })
    } else if (method === 'PUT') {
      response = await axios.put(url, body || {}, { headers })
    } else if (method === 'DELETE') {
      response = await axios.delete(url, { headers })
    } else {
      response = await axios.get(url, { headers })
    }

    lastResult.value = {
      success: true,
      data: {
        status: response.status,
        headers: response.headers,
        data: response.data
      }
    }
  } catch (error: any) {
    lastResult.value = {
      success: false,
      data: {
        message: error.message,
        status: error.response?.status,
        data: error.response?.data
      }
    }
  } finally {
    testing.value = false
  }
}
</script>

<style scoped>
.auth-test {
  max-width: 1000px;
  margin: 0 auto;
  padding: 2rem;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.test-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-panel,
.test-panel,
.results-panel,
.error-panel {
  background: white;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.info-panel {
  background: #f8f9fa;
}

.error-panel {
  background: #fff5f5;
  border-color: #feb2b2;
  color: #c53030;
}

.info-panel h3,
.test-panel h3,
.results-panel h3,
.error-panel h3 {
  margin: 0 0 1rem 0;
  color: #2d3748;
  font-size: 1.2rem;
}

.info-panel p {
  margin: 0.5rem 0;
  color: #4a5568;
}

.form-row {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.form-row input {
  flex: 1;
  min-width: 150px;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
}

.form-row input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-row button,
.test-buttons button,
.btn-danger {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.form-row button,
.test-buttons button {
  background: #3b82f6;
  color: white;
}

.form-row button:hover:not(:disabled),
.test-buttons button:hover:not(:disabled) {
  background: #2563eb;
}

.btn-danger {
  background: #dc2626;
  color: white;
}

.btn-danger:hover {
  background: #b91c1c;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.test-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.results-panel {
  background: #f7fafc;
}

.result-status {
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: bold;
  margin-bottom: 1rem;
}

.result-status.success {
  background: #c6f6d5;
  color: #276749;
}

.result-status.error {
  background: #fed7d7;
  color: #c53030;
}

.result-data pre {
  background: #2d3748;
  color: #e2e8f0;
  padding: 1rem;
  border-radius: 6px;
  overflow-x: auto;
  font-size: 0.9rem;
  line-height: 1.4;
  margin: 0;
}

@media (max-width: 768px) {
  .auth-test {
    padding: 1rem;
  }
  
  .form-row {
    flex-direction: column;
  }
  
  .form-row input {
    min-width: auto;
  }
  
  .test-buttons {
    flex-direction: column;
  }
}
</style>