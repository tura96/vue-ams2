<template>
  <div>
    <div class="content__top">
      <button class="btn btn--primary" @click="addAsset">
        <img src="@/assets/images/icons/mdi_add.svg" alt="Add" class="btn__icon">
        ADD ASSET ITEM
      </button>
    </div>
    
    <div class="content__box">
      <div v-if="loading" class="loading">Loading assets...</div>
      <div v-else-if="error" class="error">{{ error }}</div>
      <div v-else>
        <AssetFilters 
          :status-options="statusOptions"
          :model-options="modelOptions"
          :store-options="storeOptions"
          :location-options="locationOptions"
          :active-filters="filters"
          :assets="assets"
          :selectedAssets="selectedAssets"
          @filter-change="handleFilterChange" 
        />
        <AssetTable 
          :assets="filteredAssets" 
          @edit="editAsset" 
          @delete="openDeleteModal"
          :current-page="currentPage"
          :items-per-page="itemsPerPage"
          :selected-assets="selectedAssets"
          :current-sort-by="sortBy"
          :current-sort-order="sortOrder"
          @select-all="handleSelectAll"
          @select="handleSelect"
          @sort="handleSort" 
        />
      </div>
    </div>
    
    <Pagination 
      :current-page="currentPage" 
      :total-items="totalItems" 
      :items-per-page="itemsPerPage"
      @page-change="handlePageChange"
      @items-per-page-change="handleItemsPerPageChange"
    />
    <!-- Delete Confirmation Modal -->
    <DeleteConfirmationModal
      :is-open="deleteModal.isOpen"
      :message="deleteModal.message"
      :loading="deleteModal.loading"
      @confirm="confirmDelete"
      @cancel="closeDeleteModal"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AssetFilters from '@/components/assets/AssetFilters.vue'
import AssetTable from '@/components/assets/AssetTable.vue'
import Pagination from '@/components/ui/Pagination.vue'
import DeleteConfirmationModal from '@/components/modals/DeleteConfirmationModal.vue'
import { amsApi, type Asset, type AssetListParams } from '../services/amsApi'

// --- Types ---
interface Filters {
  search: string
  status: string
  model: string
  store: string
}

interface Option {
  value: string
  label: string
}

// --- Reactive state ---
const statusOptions = ref<Option[]>([])
const modelOptions = ref<Option[]>([])
const storeOptions = ref<Option[]>([])
const locationOptions = ref<Option[]>([])

// --- Asset state ---
const assets = ref<Asset[]>([])
const selectedAssets = ref<number[]>([])

// --- Filter state ---
const filters = ref<Filters>({
  search: '',
  status: '',
  model: '',
  store: ''
})

// --- Pagination & Sorting state ---
const currentPage = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
const totalPages = ref(0)
const sortBy = ref<string>('date')
const sortOrder = ref<'ASC' | 'DESC'>('DESC')

// --- Loading & Error state ---
const loading = ref(false)
const error = ref<string | null>(null)

// --- Modal states ---
const deleteModal = ref({
  isOpen: false,
  loading: false,
  message: '',
  targetAsset: null as Asset | null
})

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()


// --- Computed ---
const filteredAssets = computed(() => {
  // Client-side filtering for search (can be used as additional filtering on top of server results)
  if (!filters.value.search) return assets.value

  const searchTerm = filters.value.search.toLowerCase()
  return assets.value.filter(asset =>
    asset.asset_tag?.toLowerCase().includes(searchTerm) ||
    asset.model?.toLowerCase().includes(searchTerm) ||
    asset.serial_number?.toLowerCase().includes(searchTerm) ||
    (asset.assigned_to && asset.assigned_to.toLowerCase().includes(searchTerm)) ||
    (asset.location && asset.location.toLowerCase().includes(searchTerm)) ||
    (asset.title && asset.title.toLowerCase().includes(searchTerm))
  )
})

// --- Methods ---
function addAsset() {
  router.push('/items')
}

function editAsset(asset: Asset) {
  router.push(`/items/edit/${asset.id}`)
}

function handleFilterChange(newFilters: Partial<Filters>) {
  filters.value = { ...filters.value, ...newFilters }
  currentPage.value = 1 // Reset to first page when filtering
  loadAssets()
}

function handlePageChange(page: number) {
  currentPage.value = page
  loadAssets()
}

function handleItemsPerPageChange(count: number) {
  itemsPerPage.value = count
  currentPage.value = 1 // Reset to first page when changing items per page
  loadAssets()
}

function handleSort(column: string, order: 'ASC' | 'DESC') {
  sortBy.value = column
  sortOrder.value = order
  currentPage.value = 1 // Reset to first page when sorting
  loadAssets()
}

function handleSelectAll(selectAll: boolean) {
  if (selectAll) {
    selectedAssets.value = assets.value.map(asset => asset.id)
  } else {
    selectedAssets.value = []
  }
}

function handleSelect(assetId: number) {
  const index = selectedAssets.value.indexOf(assetId)
  if (index > -1) {
    selectedAssets.value.splice(index, 1)
  } else {
    selectedAssets.value.push(assetId)
  }
}

async function loadAssets() {
  loading.value = true
  error.value = null
  
  try {
    const params: AssetListParams = {
      page: currentPage.value,
      per_page: itemsPerPage.value,
      sort_by: sortBy.value as any,
      sort_order: sortOrder.value,
    }
    
    // Add filters if they have values
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status as any
    if (filters.value.model) params.model = filters.value.model
    if (filters.value.store) params.location = filters.value.store // Assuming store maps to location
    
    const response = await amsApi.getAssets(params)
    
    if (response.success && response.data) {
      assets.value = response.data
      totalItems.value = response.pagination?.total_items || 0
      totalPages.value = response.pagination?.total_pages || 0
      
      console.log('Fetched assets:', assets.value)
      console.log('Pagination:', response.pagination)
    } else {
      throw new Error('Failed to fetch assets')
    }
  } catch (err: any) {
    error.value = `Failed to load assets: ${err.message}`
    console.error('Fetch error:', err)
    
    // If authentication error, redirect to login
    if (err.message.includes('Authentication') || err.message.includes('Invalid token')) {
      authStore.logout()
      router.push('/login')
    }
  } finally {
    loading.value = false
  }
}

async function loadFilterOptions() {
  try {
    // Load all filter options in parallel
    const [statusesRes, modelsRes, locationsRes , AssignedRes] = await Promise.all([
      amsApi.getStatuses(),
      amsApi.getModels(), 
      amsApi.getLocations(),
      amsApi.getAssigned()
    ])

    // Process statuses (API returns { value: label } object)
    if (statusesRes.success && statusesRes.data) {
      statusOptions.value = Object.entries(statusesRes.data).map(
        ([value, label]) => ({ value, label })
      )
    }

    // Process models (API returns string[])
    if (modelsRes.success && modelsRes.data) {
      modelOptions.value = modelsRes.data.map(m => ({ value: m, label: m }))
    }

    // Process stores (API returns string[])
    if (AssignedRes.success && AssignedRes.data) {
      storeOptions.value = AssignedRes.data.map(s => ({ value: s, label: s }))
    }
    // Process locations (API returns string[])
    if (locationsRes.success && locationsRes.data) {
      locationOptions.value = locationsRes.data.map(s => ({ value: s, label: s }))
    }
  } catch (err: any) {
    console.error('Failed to load filter options:', err)
    error.value = `Failed to load filter options: ${err.message}`
  }
}

// Delete Modal Methods
function openDeleteModal(asset: Asset) {
  deleteModal.value = {
    isOpen: true,
    loading: false,
    message: `Are you sure you want to delete asset ${asset.asset_tag} - ${asset.title}?`,
    targetAsset: asset
  }
}

function closeDeleteModal() {
  deleteModal.value = {
    isOpen: false,
    loading: false,
    message: '',
    targetAsset: null
  }
}

async function confirmDelete() {
  if (!deleteModal.value.targetAsset) return
  
  deleteModal.value.loading = true
  
  try {
    const response = await amsApi.deleteAsset(deleteModal.value.targetAsset.id)
    
    if (response.success) {
      // Remove from local array
      assets.value = assets.value.filter(a => a.id !== deleteModal.value.targetAsset!.id)
      totalItems.value = Math.max(0, totalItems.value - 1)
      
      // Remove from selected if it was selected
      const selectedIndex = selectedAssets.value.indexOf(deleteModal.value.targetAsset.id)
      if (selectedIndex > -1) {
        selectedAssets.value.splice(selectedIndex, 1)
      }
      
      closeDeleteModal()
      
      // Reload to get accurate pagination if needed
      if (assets.value.length === 0 && currentPage.value > 1) {
        currentPage.value = currentPage.value - 1
        await loadAssets()
      }
    }
  } catch (err: any) {
    error.value = `Failed to delete asset: ${err.message}`
    console.error('Delete error:', err)
  } finally {
    deleteModal.value.loading = false
  }
}

// --- Lifecycle ---
onMounted(async () => {
  if (!authStore.isAuthenticated) {
    error.value = 'Please log in to view assets.'
    router.push('/login')
    return
  }

  // Load filter options and assets in parallel
  await Promise.all([
    loadFilterOptions(),
    loadAssets()
  ])
  
  console.log('Route data:', route)
})
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/table.scss" as table;

.loading {
  text-align: center;
  padding: 2rem;
  color: #4a5568;
}
</style>