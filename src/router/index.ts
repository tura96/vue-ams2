import { createRouter, createWebHistory } from 'vue-router'
import AssetsView from '@/views/AssetsView.vue'
import AssetsForm from '@/views/AssetsForm.vue'

const routes = [
  {
    path: '/',
    name: 'Dashboard',
    component: AssetsView,
    meta: { title: 'Asset Items' }
  },
  {
    path: '/items',
    name: 'Assets',
    component: AssetsForm,
    meta: { title: 'Add asset Item' }
  },
  // {
  //   path: '/tickets',
  //   name: 'Tickets',
  //   component: ServiceTicketsView,
  //   meta: { title: 'Service Tickets' }
  // }
]

// const router = createRouter({
//   history: createWebHistory(),
//   routes
// })
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL), // ✅ make sure BASE_URL is used
  routes,
})

// Update page title based on route meta
router.beforeEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} - Asset Management` : 'Asset Management'
  // next()
})

export default router