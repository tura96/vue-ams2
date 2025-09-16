import { createRouter, createWebHistory } from 'vue-router'
import { useAssetStore } from '../stores/assets'
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

router.beforeEach(async (to, from, next) => {
  const assetStore = useAssetStore()
  let title = to.meta.title || 'Asset Management'

  if (to.name === 'EditAsset' && to.params.id) {
    try {
      const assetId = Number(to.params.id)
      
      // Debug logging
      if (import.meta.env.DEV) {
        console.log('Fetching asset with ID:', assetId)
        console.log('Asset store:', assetStore)
      }
      
      const asset = await assetStore.fetchAssetTitle(assetId)
      
      if (import.meta.env.DEV) {
        console.log('Fetched asset result:', asset)
        console.log('Asset type:', typeof asset)
      }
      
      if (asset) {
        // Asset not found - redirect to assets list or show error
        console.warn(`Asset with ID ${assetId} not found`)
        title = `${asset}`
        // Optionally redirect: next({ name: 'Asset Items' }); return;
      } else {
        title = (asset as any)?.title || `Edit Asset #${assetId}`
      }
      
    } catch (err) {
      console.error('Failed to fetch asset:', err)
      title = 'Edit Asset'
    }
  }

  document.title = `${title} - Asset Management`
  to.meta.dynamicTitle = title
  next()
})

// Update page title based on route meta
// router.beforeEach((to) => {
//   document.title = to.meta.title ? `${to.meta.title} - Asset Management` : 'Asset Management'
//   // next()
// })

export default router