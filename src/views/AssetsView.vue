<template>
  <div>
    <div class="content__top">
      <button class="btn btn--primary" @click="addAsset">
        <img src="@/assets/images/icons/mdi_add.svg" alt="Add" class="btn__icon">
        ADD ASSET ITEM
      </button>
    </div>
    
    <div class="content__box">
      <AssetFilters @filter-change="handleFilterChange" />
      <AssetTable 
        :assets="filteredAssets" 
        @edit="editAsset" 
        @delete="deleteAsset" 
      />
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
import AssetFilters from '@/components/assets/AssetFilters.vue'
import AssetTable from '@/components/assets/AssetTable.vue'
import Pagination from '@/components/ui/Pagination.vue'

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

// --- State ---
const assets = ref<Asset[]>([])
const filters = ref<Filters>({
  search: '',
  status: '',
  model: '',
  store: ''
})

const currentPage = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)

const router = useRouter()
const route = useRoute()


// --- Computed ---
const filteredAssets = computed(() => {
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

function deleteAsset(asset: Asset) {
  if (confirm(`Are you sure you want to delete asset ${asset.tag}?`)) {
    assets.value = assets.value.filter(a => a.id !== asset.id)
    totalItems.value = assets.value.length
  }
}

function handleFilterChange(newFilters: Partial<Filters>) {
  filters.value = { ...filters.value, ...newFilters }
  currentPage.value = 1
}

function handlePageChange(page: number) {
  currentPage.value = page
}

function handleItemsPerPageChange(count: number) {
  itemsPerPage.value = count
  currentPage.value = 1
}

function loadAssets() {
  assets.value = [
    {
      id: 1,
      tag: 'AST-0001',
      model: 'Dell Latitude 5440',
      serial: 'SN-DL5440-1234',
      status: 'new',
      assignedTo: null,
      location: null,
      warrantyExpiry: '05/08/2025 12:00:00',
      notes: 'Ready to use'
    },
    {
      id: 2,
      tag: 'AST-0002',
      model: 'HP LaserJet Pro M404',
      serial: 'SN-HPLJ-7788',
      status: 'staging',
      assignedTo: 'Store A',
      location: 'Office 3',
      warrantyExpiry: '05/08/2025 12:00:00',
      notes: 'Currently in use for accounting department'
    },
    {
      id: 3,
      tag: 'AST-0003',
      model: 'Samsung Smart Fridge',
      serial: 'SN-SMF-9999',
      status: 'ready',
      assignedTo: 'Store B',
      location: 'Kitchen',
      warrantyExpiry: '05/08/2025 12:00:00',
      notes: 'Cold block broken, waiting for maintenance'
    },
    {
      id: 4,
      tag: 'AST-0004',
      model: 'Lenovo ThinkPad X1',
      serial: 'SN-LTPX1-4567',
      status: 'deployed',
      assignedTo: 'Store C',
      location: 'IT Room',
      warrantyExpiry: '05/08/2025 12:00:00',
      notes: 'Upgrading SSD'
    },
    {
      id: 5,
      tag: 'AST-0005',
      model: 'Epson Scanner L3150',
      serial: 'SN-EPS-3110',
      status: 'defected',
      assignedTo: 'Store C',
      location: 'Office 3',
      warrantyExpiry: '05/08/2025 12:00:00',
      notes: 'Discontinued from June 2024'
    },
    {
      id: 6,
      tag: 'AST-0006',
      model: 'Epson Scanner L3150',
      serial: 'SN-EPS-3110',
      status: 'maintenance',
      assignedTo: 'Store C',
      location: 'Office 3',
      warrantyExpiry: '05/08/2025 12:00:00',
      notes: 'Discontinued from June 2024'
    },
    {
      id: 7,
      tag: 'AST-0007',
      model: 'Epson Scanner L3150',
      serial: 'SN-EPS-3110',
      status: 'archived',
      assignedTo: 'Store C',
      location: 'Office 3',
      warrantyExpiry: '05/08/2025 12:00:00',
      notes: 'Discontinued from June 2024'
    },
    {
      id: 8,
      tag: 'AST-0008',
      model: 'Epson Scanner L3150',
      serial: 'SN-EPS-3110',
      status: 'disposed',
      assignedTo: 'Store C',
      location: 'Office 3',
      warrantyExpiry: '05/08/2025 12:00:00',
      notes: 'Discontinued from June 2024'
    },
    {
      id: 9,
      tag: 'AST-0009',
      model: 'Epson Scanner L3150',
      serial: 'SN-EPS-3110',
      status: 'return',
      assignedTo: 'Store C',
      location: 'Office 3',
      warrantyExpiry: '05/08/2025 12:00:00',
      notes: 'Discontinued from June 2024'
    }
  ]

  totalItems.value = assets.value.length
}

// --- Lifecycle ---
onMounted(() => {
  loadAssets()
  console.log('Route data: ',route)
})
</script>
<style scoped lang="scss">
  @use "/src/assets/styles/components/table.scss" as table;
</style>
