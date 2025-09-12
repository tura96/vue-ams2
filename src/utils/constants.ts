export const ASSET_STATUSES = {
  NEW: 'new',
  STAGING: 'staging', 
  READY: 'ready',
  DEPLOYED: 'deployed',
  DEFECTED: 'defected',
  MAINTENANCE: 'maintenance',
  ARCHIVED: 'archived',
  DISPOSED: 'disposed',
  RETURN: 'return'
} as const

export const ITEMS_PER_PAGE_OPTIONS = [10, 25, 50, 100]

export const API_ENDPOINTS = {
  ASSETS: '/assets',
  ASSET_MODELS: '/asset-models',
  CATEGORIES: '/categories',
  MANUFACTURERS: '/manufacturers',
  STORES: '/stores'
} as const