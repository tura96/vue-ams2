import { defineStore } from 'pinia'
import Cookies from 'js-cookie'

interface UIState {
  isSidebarCollapsed: boolean
}

export const useUIStore = defineStore('ui', {
  state: (): UIState => ({
    // restore from cookie if exists, otherwise default = false
    isSidebarCollapsed: Cookies.get('ams_sidebar') === 'true'
  }),

  getters: {
    sidebarState: (state) => state.isSidebarCollapsed
  },

  actions: {
    toggleSidebar() {
      this.isSidebarCollapsed = !this.isSidebarCollapsed
      Cookies.set('ams_sidebar', String(this.isSidebarCollapsed), { expires: 7 }) // persist 7 days
    },
    setSidebar(value: boolean) {
      this.isSidebarCollapsed = value
      Cookies.set('ams_sidebar', String(value), { expires: 7 })
    }
  }
})
