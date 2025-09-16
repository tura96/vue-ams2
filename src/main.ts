import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { useAuthStore } from './stores/auth.ts'


router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  console.log('Data: ', from)
  if (to.meta.requiresAuth) {
    if (!authStore.token) {
      next('/login')
      return
    }
    
    const isValid = await authStore.validateToken()
    if (!isValid) {
      next('/login')
      return
    }
  }
  
  next()
})

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.mount('#app')

// Initialize auth on app start
const authStore = useAuthStore()
authStore.initializeAuth()