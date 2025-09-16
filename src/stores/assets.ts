// src/stores/assets.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { 
  amsApi, 
  type Asset, 
  type AssetCreateData, 
  type AssetListParams, 
  type AssetFilters,
  type AssetSorting,
  type PaginationInfo,
  type AssetStatus 
} from '../services/amsApi'

export const useAssetStore = defineStore('assets', () => {
  // State
  const assets = ref<Asset[]>([])
  const currentAsset = ref<Asset | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref<PaginationInfo>({
    current_page: 1,
    per_page: 10,
    total_items: 0,
    total_pages: 0
  })

  // Filters and Search State
  const filters = ref<AssetFilters>({
    search: '',
    status: '',
    model: '',
    store: '',
    location: '',
    assigned_to: ''
  })

  const sorting = ref<AssetSorting>({
    sort_by: 'date',
    sort_order: 'DESC'
  })

  // Metadata
  const statuses = ref<Record<string, string>>({})
  const models = ref<string[]>([])
  const locations = ref<string[]>([])

  // Selected assets for bulk operations
  const selectedAssets = ref<number[]>([])

  // Getters
  const filteredAssets = computed(() => assets.value)
  const hasAssets = computed(() => assets.value.length > 0)
  const selectedCount = computed(() => selectedAssets.value.length)
  const totalAssets = computed(() => pagination.value.total_items)

  // Status helpers
  const statusOptions = computed(() => 
    Object.entries(statuses.value).map(([key, label]) => ({ key, label }))
  )

  // Actions
  const setLoading = (state: boolean) => {
    loading.value = state
  }

  const setError = (message: string | null) => {
    error.value = message
  }

  const clearError = () => {
    error.value = null
  }

  // Fetch assets with filters, sorting, and pagination
  const fetchAssets = async (params: AssetListParams = {}) => {
    setLoading(true)
    clearError()

    try {
      const requestParams: AssetListParams = {
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
        ...filters.value,
        ...sorting.value,
        ...params
      }

      const response = await amsApi.getAssets(requestParams)

      if (response.success && response.data && response.pagination) {
        assets.value = response.data
        pagination.value = response.pagination
      } else {
        throw new Error('Failed to fetch assets')
      }
    } catch (err: any) {
      setError(err.message || 'Failed to fetch assets')
    } finally {
      setLoading(false)
    }
  }

  // Fetch single asset
  const fetchAsset = async (id: number) => {
    setLoading(true)
    clearError()

    try {
      const response = await amsApi.getAsset(id)

      if (response.success && response.data) {
        currentAsset.value = response.data
      } else {
        throw new Error('Failed to fetch asset')
      }
    } catch (err: any) {
      setError(err.message || 'Failed to fetch asset')
    } finally {
      setLoading(false)
    }
  }

  // Create new asset
  const createAsset = async (assetData: AssetCreateData): Promise<Asset | null> => {
    setLoading(true)
    clearError()

    try {
      const response = await amsApi.createAsset(assetData)

      if (response.success && response.data) {
        // Add to local state
        assets.value.unshift(response.data)
        pagination.value.total_items += 1
        
        return response.data
      } else {
        throw new Error(response.message || 'Failed to create asset')
      }
    } catch (err: any) {
      setError(err.message || 'Failed to create asset')
      throw err
    } finally {
      setLoading(false)
    }
  }

  // Update asset
  const updateAsset = async (id: number, assetData: Partial<AssetCreateData>): Promise<Asset | null> => {
    setLoading(true)
    clearError()

    try {
      const response = await amsApi.updateAsset(id, assetData)

      if (response.success && response.data) {
        // Update in local state
        const index = assets.value.findIndex(asset => asset.id === id)
        if (index !== -1) {
          assets.value[index] = response.data
        }
        
        // Update current asset if it's the one being edited
        if (currentAsset.value?.id === id) {
          currentAsset.value = response.data
        }
        
        return response.data
      } else {
        throw new Error(response.message || 'Failed to update asset')
      }
    } catch (err: any) {
      setError(err.message || 'Failed to update asset')
      throw err
    } finally {
      setLoading(false)
    }
  }

  // Delete asset
  const deleteAsset = async (id: number): Promise<boolean> => {
    setLoading(true)
    clearError()

    try {
      const response = await amsApi.deleteAsset(id)

      if (response.success) {
        // Remove from local state
        assets.value = assets.value.filter(asset => asset.id !== id)
        pagination.value.total_items -= 1
        
        // Clear current asset if it was deleted
        if (currentAsset.value?.id === id) {
          currentAsset.value = null
        }
        
        // Remove from selected assets
        selectedAssets.value = selectedAssets.value.filter(assetId => assetId !== id)
        
        return true
      } else {
        throw new Error(response.message || 'Failed to delete asset')
      }
    } catch (err: any) {
      setError(err.message || 'Failed to delete asset')
      return false
    } finally {
      setLoading(false)
    }
  }

  // Bulk deploy assets
  const bulkDeployAssets = async (assetIds: number[] = selectedAssets.value): Promise<boolean> => {
    if (assetIds.length === 0) {
      setError('No assets selected for deployment')
      return false
    }

    setLoading(true)
    clearError()

    try {
      const response = await amsApi.bulkDeployAssets(assetIds)

      if (response.success) {
        // Update local state - set status to deployed for affected assets
        assets.value = assets.value.map(asset => 
          assetIds.includes(asset.id) 
            ? { ...asset, status: 'deployed' as AssetStatus }
            : asset
        )
        
        // Clear selection
        selectedAssets.value = []
        
        return true
      } else {
        throw new Error(response.message || 'Failed to deploy assets')
      }
    } catch (err: any) {
      setError(err.message || 'Failed to deploy assets')
      return false
    } finally {
      setLoading(false)
    }
  }

  // Filter and Search methods
  const setFilter = (key: keyof AssetFilters, value: string) => {
    if (key === 'status') {
      filters.value[key] = value as AssetStatus
    } else {
      filters.value[key] = value
    }
    pagination.value.current_page = 1 // Reset to first page
  }

  const clearFilters = () => {
    filters.value = {
      search: '',
      status: '',
      model: '',
      store: '',
      location: '',
      assigned_to: ''
    }
    pagination.value.current_page = 1
  }

  const setSorting = (sort_by: AssetSorting['sort_by'], sort_order: AssetSorting['sort_order'] = 'ASC') => {
    sorting.value = { sort_by, sort_order }
    pagination.value.current_page = 1
  }

  // Pagination methods
  const setPage = (page: number) => {
    pagination.value.current_page = page
  }

  const setPerPage = (perPage: number) => {
    pagination.value.per_page = perPage
    pagination.value.current_page = 1
  }

  const nextPage = () => {
    if (pagination.value.current_page < pagination.value.total_pages) {
      pagination.value.current_page += 1
    }
  }

  const previousPage = () => {
    if (pagination.value.current_page > 1) {
      pagination.value.current_page -= 1
    }
  }

  // Selection methods
  const toggleAssetSelection = (assetId: number) => {
    const index = selectedAssets.value.indexOf(assetId)
    if (index > -1) {
      selectedAssets.value.splice(index, 1)
    } else {
      selectedAssets.value.push(assetId)
    }
  }

  const selectAllAssets = () => {
    selectedAssets.value = assets.value.map(asset => asset.id)
  }

  const clearSelection = () => {
    selectedAssets.value = []
  }

  const isAssetSelected = (assetId: number) => {
    return selectedAssets.value.includes(assetId)
  }

  // Metadata methods
  const fetchMetadata = async () => {
    try {
      const [statusesResponse, modelsResponse, locationsResponse] = await Promise.all([
        amsApi.getStatuses(),
        amsApi.getModels(),
        amsApi.getLocations()
      ])

      if (statusesResponse.success && statusesResponse.data) {
        statuses.value = statusesResponse.data
      }

      if (modelsResponse.success && modelsResponse.data) {
        models.value = modelsResponse.data
      }

      if (locationsResponse.success && locationsResponse.data) {
        locations.value = locationsResponse.data
      }
    } catch (err: any) {
      console.error('Failed to fetch metadata:', err)
    }
  }

  // Initialize store
  const initialize = async () => {
    await Promise.all([
      fetchMetadata(),
      fetchAssets()
    ])
  }

  // Reset store
  const resetStore = () => {
    assets.value = []
    currentAsset.value = null
    loading.value = false
    error.value = null
    selectedAssets.value = []
    clearFilters()
  }

  return {
    // State
    assets,
    currentAsset,
    loading,
    error,
    pagination,
    filters,
    sorting,
    statuses,
    models,
    locations,
    selectedAssets,

    // Getters
    filteredAssets,
    hasAssets,
    selectedCount,
    totalAssets,
    statusOptions,

    // Actions
    fetchAssets,
    fetchAsset,
    createAsset,
    updateAsset,
    deleteAsset,
    bulkDeployAssets,
    
    // Filter & Search
    setFilter,
    clearFilters,
    setSorting,
    
    // Pagination
    setPage,
    setPerPage,
    nextPage,
    previousPage,
    
    // Selection
    toggleAssetSelection,
    selectAllAssets,
    clearSelection,
    isAssetSelected,
    
    // Metadata
    fetchMetadata,
    
    // Utility
    initialize,
    resetStore,
    setError,
    clearError
  }
})