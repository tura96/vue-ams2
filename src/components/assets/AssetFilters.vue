<template>
  <div class="content__toolbar">
    <SearchInput 
      v-model="localFilters.search" 
      :placeholder="placeholder || 'Search'" 
    />

    <div class="filters">
      <CustomSelect 
        v-model="localFilters.status" 
        label="Status" 
        :options="statusOptions" 
      />

      <CustomSelect 
        v-model="localFilters.model" 
        label="Model" 
        :options="modelOptions" 
      />

      <CustomSelect 
        v-model="localFilters.store" 
        label="Store" 
        :options="storeOptions" 
      />

      <button 
        class="btn btn--secondary" 
        @click="openBulkDeployModal"

      >
        BULK DEPLOY
      </button>
    </div>
  </div>

  <!-- Bulk Deploy Modal -->
  <BulkDeployModal
    :is-open="bulkDeployModal.isOpen"
    :selected-assets="selectedAssetsData"
    :store-options="storeOptions"
    :location-options="locationOptions"
    :loading="bulkDeployModal.loading"
    @confirm="confirmBulkDeploy"
    @cancel="closeBulkDeployModal"
  />

  <!-- Error/Success Messages -->
  <div v-if="errorMessage" class="error-message">
    {{ errorMessage }}
  </div>
  <div v-if="successMessage" class="success-message">
    {{ successMessage }}
  </div>

</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import BulkDeployModal from '@/components/modals/BulkDeployModal.vue'
import { amsApi, type Asset } from '../../services/amsApi'
import { useAssetStore } from '../../stores/assets'

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

interface BulkDeployData {
  store: string
  location: string
  notes?: string
}

// --- Modal states ---
const bulkDeployModal = ref({
  isOpen: false,
  loading: false
})

const selectedAssetsData = ref<Asset[]>([])
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)

// --- Props ---
const props = defineProps<{
  statusOptions: Option[]
  modelOptions: Option[]
  storeOptions: Option[]
  locationOptions: Option[]
  selectedAssets: number[]
  assets: Asset[] | undefined // Allow undefined
  placeholder?: string
  activeFilters: Filters
}>()

// --- Emits ---
const emit = defineEmits<{
  (e: 'filter-change', filters: Filters): void
  (e: 'bulk-deploy-success'): void
}>()

// Local reactive filters, initialized with activeFilters
const localFilters = ref<Filters>({
  search: props.activeFilters.search,
  status: validateOption(props.activeFilters.status, props.statusOptions),
  model: validateOption(props.activeFilters.model, props.modelOptions),
  store: validateOption(props.activeFilters.store, props.storeOptions)
})

// Validate filter values against options
function validateOption(value: string, options: Option[]): string {
  return options.some(opt => opt.value === value) ? value : ''
}

// Compute selected assets data
const selectedAssetsDataComputed = computed(() => {
  if (!props.assets) {
    if (import.meta.env.DEV) console.warn('props.assets is undefined')
    return []
  }
  const result = props.assets.filter(asset => 
    props.selectedAssets.includes(asset.id)
  )
  if (result.length !== props.selectedAssets.length) {
    if (import.meta.env.DEV) {
      console.warn(
        'Some selected assets not found in props.assets:',
        props.selectedAssets.filter(id => !props.assets!.some(asset => asset.id === id))
      )
    }
  }
  return result
})

// Sync selectedAssetsData
watch(
  () => [props.assets, props.selectedAssets],
  () => {
    // if (import.meta.env.DEV) console.log('props.assets:', props.assets)

    selectedAssetsData.value = selectedAssetsDataComputed.value
  },
  { deep: true, immediate: true }
)

// Sync localFilters with activeFilters when the prop changes
watch(
  () => props.activeFilters,
  (newFilters) => {
    localFilters.value = {
      search: newFilters.search,
      status: validateOption(newFilters.status, props.statusOptions),
      model: validateOption(newFilters.model, props.modelOptions),
      store: validateOption(newFilters.store, props.storeOptions)
    }
  },
  { deep: true }
)

// Emit filter changes when localFilters changes
watch(
  localFilters,
  (newFilters) => {
    emit('filter-change', { ...newFilters })
  },
  { deep: true }
)

// --- Methods ---
const assetStore = useAssetStore()

// Bulk Deploy Modal Methods
function openBulkDeployModal() {
  if (selectedAssetsData.value.length === 0) {
    errorMessage.value = 'Please select at least one asset to deploy'
    setTimeout(() => (errorMessage.value = null), 3000)
    return
  }
  // console.log('Opening Bulk Deploy Modal with assets:', selectedAssetsData.value)
  bulkDeployModal.value.isOpen = true
}

function closeBulkDeployModal() {
  bulkDeployModal.value = {
    isOpen: false,
    loading: false
  }
  errorMessage.value = null
  successMessage.value = null
}

async function confirmBulkDeploy(deployData: BulkDeployData) {
  bulkDeployModal.value.loading = true
  errorMessage.value = null
  successMessage.value = null

  try {
    const response = await amsApi.bulkDeployAssets(props.selectedAssets)
    
    if (response.success) {
      // Update asset store
      props.selectedAssets.forEach(id => {
        assetStore.updateAsset(id, {
          ...deployData,
          assigned_to: deployData.store || '',
          location: deployData.location || ''
        })
      })

      closeBulkDeployModal()
      successMessage.value = 'Assets deployed successfully'
      emit('bulk-deploy-success')
      setTimeout(() => (successMessage.value = null), 3000)
    } else {
      throw new Error(response.message || 'Failed to deploy assets')
    }
  } catch (err: any) {
    errorMessage.value = `Bulk deploy failed: ${err.message}`
    setTimeout(() => (errorMessage.value = null), 5000)
  } finally {
    bulkDeployModal.value.loading = false
  }
}
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/table.scss";
.error-message{
  text-align: center;
  color: red;
  padding: 12px;
}
.success-message{
  text-align: center;
  color: green;
  padding: 12px;
}
</style>