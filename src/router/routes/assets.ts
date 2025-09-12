export const assetRoutes = [
  {
    path: '/assets',
    name: 'AssetList',
    meta: { title: 'Asset Items' },
    component: () => import('@/views/AssetsView.vue')
  },
  // {
  //   path: '/assets/create',
  //   name: 'AssetCreate',
  //   component: () => import('@/views/assets/AssetCreateView.vue'),
  //   meta: { title: 'Add Asset Item' }
  // },
  // {
  //   path: '/assets/:id',
  //   name: 'AssetDetail',
  //   component: () => import('@/views/assets/AssetDetailView.vue'),
  //   meta: { title: 'Asset Detail' }
  // },
  // {
  //   path: '/assets/:id/edit',
  //   name: 'AssetEdit',
  //   component: () => import('@/views/assets/AssetEditView.vue'),
  //   meta: { title: 'Edit Asset Item' }
  // }
]