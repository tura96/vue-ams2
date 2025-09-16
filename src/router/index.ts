import { createRouter, createWebHistory } from 'vue-router'
import AssetsView from '@/views/AssetsView.vue'
import AssetsForm from '@/views/AssetsForm.vue'
import LoginForm from '@/components/ui/LoginForm.vue'
import Dashboard from '@/components/ui/Dashboard.vue'
import AuthTest from '@/components/ui/AuthTest.vue'


const routes = [
  {
    path: '/',
    name: 'Asset Items',
    component: AssetsView,
    meta: { title: 'Asset Items' }
  },
  {
    path: '/items',
    name: 'Assets',
    component: AssetsForm,
    meta: { title: 'Add asset Item' }
  },
   {
    path: '/assets/:id',
    name: 'EditAsset',
    component: AssetsForm,
    meta: { title: 'Edit Asset' },
    props: true // Pass route params as props to the component
  },
  {
    path: '/login',
    name: 'Login ',
    component: LoginForm,
    meta: { title: 'Login Form' }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { title: 'Dashboard' }
  },
  {
    path: '/authtest',
    name: 'AuthTest',
    component: AuthTest,
    meta: { title: 'AuthTest' }
  }
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