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
      <!-- <div v-else-if="error" class="error">{{ error }}</div> -->
      <div v-else>
        <AssetFilters 
        :status-options="statusOptions"
        :model-options="modelOptions"
        :store-options="storeOptions"
        @filter-change="handleFilterChange" 
        />
        <AssetTable 
          :assets="filteredAssets" 
          @edit="editAsset" 
          @delete="deleteAsset" 
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
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth' // Import auth store from AuthTest.vue
import axios from 'axios'
import AssetFilters from '@/components/assets/AssetFilters.vue'
import AssetTable from '@/components/assets/AssetTable.vue'
import Pagination from '@/components/ui/Pagination.vue'
import { amsApi } from '../services/amsApi'


// --- Types ---
interface Asset {
  id: number
  tag: string
  model: string
  serial: string
  status: string
  assignedTo: string | null
  location: string | null
  warrantyExpiry: string
  notes: string
}

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

// --- Reactive state for options ---
const statusOptions = ref<Option[]>([])
const modelOptions = ref<Option[]>([])
const storeOptions = ref<Option[]>([])

// --- State ---
const assets = ref<Asset[]>([])
const filters = ref<Filters>({
  search: '',
  status: '',
  model: '',
  store: ''
})
const loading = ref(false)
const error = ref<string | null>(null)
const currentPage = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore() // Initialize auth store

// --- Computed ---
const apiUrl = computed(() => import.meta.env.VITE_API_URL) // Base URL from AuthTest.vue

const filteredAssets = computed(() => {
  // Client-side filtering (optional, depending on API capabilities)
  if (!filters.value.search) return assets.value

  const searchTerm = filters.value.search.toLowerCase()
  return assets.value.filter(asset =>
    asset.tag.toLowerCase().includes(searchTerm) ||
    asset.model.toLowerCase().includes(searchTerm) ||
    asset.serial.toLowerCase().includes(searchTerm) ||
    (asset.assignedTo && asset.assignedTo.toLowerCase().includes(searchTerm)) ||
    (asset.location && asset.location.toLowerCase().includes(searchTerm))
  )
})

// --- Methods ---
function addAsset() {
  router.push('/items')
}

function editAsset(asset: Asset) {
  router.push(`/items/edit/${asset.id}`)
}

async function deleteAsset(asset: Asset) {
  if (confirm(`Are you sure you want to delete asset ${asset.tag}?`)) {
    try {
      loading.value = true
      error.value = null
      const headers = authStore.token ? { Authorization: `Bearer ${authStore.token}` } : {}
      await axios.delete(`${apiUrl.value}/ams/v1/assets/${asset.id}`, { headers })
      assets.value = assets.value.filter(a => a.id !== asset.id)
      totalItems.value = assets.value.length
    } catch (err: any) {
      error.value = `Failed to delete asset: ${err.message}`
      console.error('Delete error:', err)
    } finally {
      loading.value = false
    }
  }
}

function handleFilterChange(newFilters: Partial<Filters>) {
  filters.value = { ...filters.value, ...newFilters }
  currentPage.value = 1
  loadAssets()
}

function handlePageChange(page: number) {
  currentPage.value = page
  loadAssets()
}

function handleItemsPerPageChange(count: number) {
  itemsPerPage.value = count
  currentPage.value = 1
  loadAssets()
}

async function loadAssets() {
  loading.value = true
  error.value = null
  try {
    const headers = authStore.token ? { Authorization: `Bearer ${authStore.token}` } : {}
    const response = await axios.get(`${apiUrl.value}/ams/v1/assets`, {
      headers,
      params: {
        page: currentPage.value,
        limit: itemsPerPage.value,
        search: filters.value.search || undefined,
        status: filters.value.status || undefined,
        model: filters.value.model || undefined,
        store: filters.value.store || undefined
      }
    })
    assets.value = response.data.data // Adjust based on API response structure
    console.log('Fetched assets:', assets)
    totalItems.value = response.data.total || response.data.length
  } catch (err: any) {
    error.value = `Failed to load assets: ${err.message}`
    console.error('Fetch error:', err)
  } finally {
    loading.value = false
  }
}

// --- Lifecycle ---
onMounted(() => {
  if (!authStore.isAuthenticated) {
    error.value = 'Please log in to view assets.'
    router.push('/login') // Redirect to login if not authenticated
  } else {
    loadAssets()
  }
  console.log('Route data:', route)
})

onMounted(async () => {
  try {
    // Fetch statuses (API returns { value: label } object)
    const statusesRes = await amsApi.getStatuses()
    if (statusesRes.success && statusesRes.data) {
      statusOptions.value = Object.entries(statusesRes.data).map(
        ([value, label]) => ({ value, label })
      )
    }

    // Fetch models (API returns string[])
    const modelsRes = await amsApi.getModels()
    if (modelsRes.success && modelsRes.data) {
      modelOptions.value = modelsRes.data.map(m => ({ value: m, label: m }))
    }

    // Fetch locations/stores (API returns string[])
    const storesRes = await amsApi.getLocations()
    if (storesRes.success && storesRes.data) {
      storeOptions.value = storesRes.data.map(s => ({ value: s, label: s }))
    }
  } catch (err) {
    console.error('Failed to load filter options:', err)
  }
})

</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/table.scss" as table;

.loading {
  text-align: center;
  padding: 2rem;
  color: #4a5568;
}

.error {
  text-align: center;
  padding: 2rem;
  color: #c53030;
  background: #fff5f5;
  border: 1px solid #feb2b2;
  border-radius: 8px;
}
</style>