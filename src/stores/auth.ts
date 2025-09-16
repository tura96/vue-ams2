// src/stores/auth.ts
import { defineStore } from 'pinia'
import axios from 'axios'
import Cookies from 'js-cookie' // Add this import

interface User {
  id: number
  username: string
  email: string
  displayName: string
  capabilities?: Record<string, boolean>
}

interface LoginResponse {
  success: boolean
  token?: string
  user?: User
  message?: string
}

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  loading: boolean
  error: string | null
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: Cookies.get('ams_user') ? JSON.parse(Cookies.get('ams_user')!) : null,
    token: Cookies.get('ams_token') || null,
    isAuthenticated: !!Cookies.get('ams_token') ,
    loading: false,
    error: null
  }),

  getters: {
    isLoggedIn: (state): boolean => !!state.token ,
    getUser: (state): User | null => state.user,
    getToken: (state): string | null => state.token
  },

  actions: {
    async login(username: string, password: string): Promise<{ success: boolean; user?: User; error?: string }> {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post<LoginResponse>(`${import.meta.env.VITE_API_URL}/ams/v1/auth/login`, {
          username,
          password
        })

        if (response.data.token && response.data.user) {
          const { token, user } = response.data

          this.token = token
          this.user = user
          this.isAuthenticated = true

          // Set cookies for 48 hours
          Cookies.set('ams_token', token, { expires: 2 }) // 2 days = 48 hours
          Cookies.set('ams_user', JSON.stringify(user), { expires: 2 })

          this.setAuthHeader()

          return { success: true, user: this.user }
        } else {
          const errorMessage = response.data.message || 'Login failed 1'
          this.error = errorMessage
          return { success: false, error: errorMessage }
        }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || 'Login failed error'
        this.error = errorMessage
        return { success: false, error: errorMessage }
      } finally {
        this.loading = false
      }
    },

    async logout(): Promise<void> {
      this.user = null
      this.token = null
      this.isAuthenticated = false
      Cookies.remove('ams_token')
      Cookies.remove('ams_user')
      delete axios.defaults.headers.common['Authorization']
    },

    async validateToken(): Promise<boolean> {
      if (!this.token) return false

      try {
        const response = await axios.post<{ success: boolean; user?: User }>(`${import.meta.env.VITE_API_URL}/ams/v1/auth/validate`, {}, {
          headers: {
            Authorization: `Bearer ${this.token}`
          }
        })

        if (response.data.success && response.data.user) {
          this.user = response.data.user
          this.isAuthenticated = true
          Cookies.set('ams_user', JSON.stringify(response.data.user), { expires: 2 })
          this.setAuthHeader()
          return true
        }
      } catch (error) {
        this.logout()
        return false
      }
      return false
    },

    setAuthHeader(): void {
      if (this.token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
      }
    },

    async initializeAuth(): Promise<boolean> {
      if (this.token) {
        const isValid = await this.validateToken()
        return isValid
      }
      return false
    }
  }
})

