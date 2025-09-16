<template>
  <div class="item-detail" v-if="!loading && !error">
    <!-- Form Content -->
    <form class="item-detail__content" autocomplete="off" @submit.prevent>
      <!-- Basic Information Section -->
      <div class="item-detail__section">
        <h2 class="item-detail__section-title">Basic Information</h2>
        
        <div class="item-detail__form-grid">
          <div class="input-floating">
            <input 
              type="text" 
              id="assetTag" 
              class="input-floating__field" 
              :class="{ 'input-floating__field--has-value': asset.asset_tag }"
              placeholder="Asset Tag"
              v-model="asset.asset_tag"
              ref="assetTagInput"
            >
            <label for="assetTag" class="input-floating__label">Asset Tag</label>
          </div>
          
          <CustomSelect 
            v-model="asset.model"
            label="Asset Model"
            :options="modelOptions"
            ref="modelSelect"
          />

          <div class="input-floating">
            <input 
              type="text" 
              id="serialNumber" 
              class="input-floating__field" 
              :class="{ 'input-floating__field--has-value': asset.serial_number }"
              placeholder="Serial Number"
              v-model="asset.serial_number"
              ref="serialInput"
            >
            <label for="serialNumber" class="input-floating__label">Serial Number</label>
          </div>

          <CustomSelect 
            v-model="asset.status"
            label="Status"
            :options="statusOptions"
            ref="statusSelect"
          />
        </div>
      </div>

      <!-- Procurement Info Section -->
      <div class="item-detail__section">
        <h2 class="item-detail__section-title">Procurement Info</h2>
        
        <div class="item-detail__form-grid">
          <div class="input-floating">
            <img src="@/assets/images/icons/mdi_calendar-month-outline.svg" alt="Date" class="date__icon">
            <input 
              type="text" 
              id="purchaseDate" 
              class="input-floating__field inputdate" 
              :class="{ 'input-floating__field--has-value': asset.purchase_date }"
              placeholder="Purchase Date"
              v-model="asset.purchase_date"
              ref="purchaseDateInput"
            >
            <label for="purchaseDate" class="input-floating__label">Purchase Date</label>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" class="btn-reset date-reset" title="Clear" v-if="asset.purchase_date" @click="clearDate('purchase_date')">
              <path d="M15.707 5.70703L11.4141 10L15.707 14.293L14.293 15.707L10 11.4141L5.70703 15.707L4.29297 14.293L8.58594 10L4.29297 5.70703L5.70703 4.29297L10 8.58594L14.293 4.29297L15.707 5.70703Z" fill="#44424B"></path>
            </svg>
          </div>

          <div class="input-floating">
            <input 
              type="number" 
              id="cost" 
              class="input-floating__field" 
              :class="{ 'input-floating__field--has-value': asset.purchase_cost }"
              placeholder="Cost"
              v-model="asset.purchase_cost"
              ref="costInput"
            >
            <label for="cost" class="input-floating__label">Cost</label>
          </div>

          <div class="input-floating">
            <img src="@/assets/images/icons/mdi_calendar-month-outline.svg" alt="Date" class="date__icon">
            <input 
              type="text" 
              id="warrantyExpiry" 
              class="input-floating__field inputdate" 
              :class="{ 'input-floating__field--has-value': asset.warranty_expiry }"
              placeholder="Warranty Expiry"
              v-model="asset.warranty_expiry"
              ref="warrantyInput"
            >
            <label for="warrantyExpiry" class="input-floating__label">Warranty Expiry</label>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" class="btn-reset date-reset" title="Clear" v-if="asset.warranty_expiry" @click="clearDate('warranty_expiry')">
              <path d="M15.707 5.70703L11.4141 10L15.707 14.293L14.293 15.707L10 11.4141L5.70703 15.707L4.29297 14.293L8.58594 10L4.29297 5.70703L5.70703 4.29297L10 8.58594L14.293 4.29297L15.707 5.70703Z" fill="#44424B"></path>
            </svg>
          </div>

          <div class="input-floating">
            <input 
              type="text" 
              id="rfidTag" 
              class="input-floating__field" 
              :class="{ 'input-floating__field--has-value': asset.rfid_tag }"
              placeholder="RFID Tag ID"
              v-model="asset.rfid_tag"
              ref="rfidInput"
            >
            <label for="rfidTag" class="input-floating__label">RFID Tag ID</label>
          </div>
        </div>
      </div>

      <!-- Technical & Deployment Section -->
      <div class="item-detail__section">
        <h2 class="item-detail__section-title">Technical & Deployment</h2>
        
        <div class="item-detail__form-grid">
          <CustomSelect 
            v-model="asset.assigned_to"
            label="Deployed To (Store)"
            :options="storeOptions"
            ref="storeSelect"
          />

          <CustomSelect 
            v-model="asset.location"
            label="Location"
            :options="locationOptions"
            ref="locationSelect"
          />

          <div class="item-detail__form-grid item-detail__form-grid--full">
            <div class="multiselect-floating">
              <textarea 
                id="notes" 
                class="multiselect-floating__field" 
                :class="{ 'multiselect-floating__field--has-value': asset.notes }"
                rows="4" 
                placeholder=" "
                v-model="asset.notes"
                ref="notesInput"
              ></textarea>
              <label for="notes" class="multiselect-floating__label">Notes</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="item-detail__actions">
        <button class="btn btn--third" type="button" @click="goBack">
          BACK
        </button>
        <button class="btn btn--primary submit" type="button" @click="saveAsset">
          SAVE
        </button>
      </div>
    </form>

    <!-- Alert Modal -->
    <AlertModal
      :is-open="isAlertOpen"
      :message="alertMessage"
      :type="alertType"
      @close="closeAlert"
    />
  </div>
  <div v-else-if="loading" class="loading">Loading asset data...</div>
  <div v-else-if="error" class="error">{{ error }}</div>
</template>

<script lang="ts" setup>
import { ref, reactive, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.css'
import '@/assets/styles/items.scss'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import AlertModal from '@/components/modals/AlertModal.vue'
import { amsApi, type Asset, type AssetCreateData } from '../services/amsApi'

interface SelectOption {
  value: string
  label: string
}

const props = defineProps<{
  id?: string | number | null
}>()

const router = useRouter()

const purchaseDateInput = ref<HTMLInputElement | null>(null)
const warrantyInput = ref<HTMLInputElement | null>(null)
const assetTagInput = ref<HTMLInputElement | null>(null)
const serialInput = ref<HTMLInputElement | null>(null)
const costInput = ref<HTMLInputElement | null>(null)
const rfidInput = ref<HTMLInputElement | null>(null)
const notesInput = ref<HTMLTextAreaElement | null>(null)
const modelSelect = ref<InstanceType<typeof CustomSelect> | null>(null)
const statusSelect = ref<InstanceType<typeof CustomSelect> | null>(null)
const storeSelect = ref<InstanceType<typeof CustomSelect> | null>(null)
const locationSelect = ref<InstanceType<typeof CustomSelect> | null>(null)

const loading = ref(true)
const error = ref<string | null>(null)
const isAlertOpen = ref(false)
const alertMessage = ref('')
const alertType = ref<'success' | 'error'>('error')

let purchaseDatePicker: any = null
let warrantyDatePicker: any = null

const asset = reactive<Asset>({
  id: 0,
  title: '',
  description: '',
  asset_tag: '',
  model: '',
  serial_number: '',
  status: 'new',
  assigned_to: '',
  location: '',
  store: '',
  warranty_expiry: '',
  notes: '',
  purchase_date: '',
  purchase_cost: '',
  date_created: '',
  date_modified: '',
  rfid_tag: ''
})

const modelOptions = ref<SelectOption[]>([])
const statusOptions = ref<SelectOption[]>([])
const storeOptions = ref<SelectOption[]>([])
const locationOptions = ref<SelectOption[]>([])

// Fetch metadata for dropdowns
async function fetchMetadata() {
  try {
    const [modelsResponse, statusesResponse, locationsResponse, assignedResponse] = await Promise.all([
      amsApi.getModels(),
      amsApi.getStatuses(),
      amsApi.getLocations(),
      amsApi.getAssigned()
    ])

    if (modelsResponse.success && modelsResponse.data) {
      modelOptions.value = modelsResponse.data.map(model => ({
        value: model,
        label: model
      }))
    }

    if (statusesResponse.success && statusesResponse.data) {
      statusOptions.value = Object.entries(statusesResponse.data).map(([value, label]) => ({
        value,
        label
      }))
    }

    if (locationsResponse.success && locationsResponse.data) {
      locationOptions.value = locationsResponse.data.map(location => ({
        value: location,
        label: location
      }))
    }

    if (assignedResponse.success && assignedResponse.data) {
      storeOptions.value = assignedResponse.data.map(assigned_to => ({
        value: assigned_to,
        label: assigned_to
      }))
    }
  } catch (err) {
    console.error('Error fetching metadata:', err)
    throw new Error('Failed to load dropdown options')
  }
}

// Load asset data if editing
async function loadAssetData() {
  if (!props.id) return
  try {
    const response = await amsApi.getAsset(Number(props.id))
    if (response.success && response.data) {
      Object.assign(asset, {
        ...response.data,
        description: response.data.description || '',
        assigned_to: response.data.assigned_to || '',
        location: response.data.location || '',
        store: response.data.store || '',
        warranty_expiry: response.data.warranty_expiry || '',
        notes: response.data.notes || '',
        purchase_date: response.data.purchase_date || '',
        purchase_cost: response.data.purchase_cost != null ? String(response.data.purchase_cost) : '',
        rfid_tag: response.data.rfid_tag || ''
      })
    } else {
      throw new Error('No asset data returned')
    }
  } catch (err) {
    console.error('Error fetching asset:', err)
    throw err
  }
}

const goBack = () => {
  router.push('/')
}

const showAlert = (message: string, type: 'success' | 'error') => {
  alertMessage.value = message
  alertType.value = type
  isAlertOpen.value = true
}

const closeAlert = () => {
  isAlertOpen.value = false
  alertMessage.value = ''
  alertType.value = 'error' // Reset to default
  // if (alertType.value === 'success') {
  //   router.push('/')
  // }
}

const saveAsset = async () => {
  if (!asset.asset_tag || !asset.model || !asset.serial_number) {
    showAlert('Please fill in all required fields (Asset Tag, Model, Serial Number)', 'error')
    return
  }

  const assetData: AssetCreateData = {
    title: asset.title || `Asset ${asset.asset_tag}`,
    description: asset.description,
    asset_tag: asset.asset_tag,
    model: asset.model,
    serial_number: asset.serial_number,
    status: asset.status,
    assigned_to: asset.assigned_to || undefined,
    location: asset.location || undefined,
    store: asset.store || undefined,
    warranty_expiry: asset.warranty_expiry || undefined,
    notes: asset.notes || undefined,
    purchase_date: asset.purchase_date || undefined,
    purchase_cost: asset.purchase_cost || undefined,
    rfid_tag: asset.rfid_tag || undefined
  }

  try {
    if (props.id) {
      const response = await amsApi.updateAsset(Number(props.id), assetData)
      if (response.success) {
        showAlert('Asset updated successfully!', 'success')
      }
    } else {
      const response = await amsApi.createAsset(assetData)
      if (response.success) {
        showAlert('Asset created successfully!', 'success')
      }
    }
  } catch (error) {
    console.error('Error saving asset:', error)
    showAlert('Failed to save asset', 'error')
  }
}

// Clear date picker field
const clearDate = (field: 'purchase_date' | 'warranty_expiry') => {
  if (field === 'purchase_date') {
    asset.purchase_date = ''
    if (purchaseDatePicker) {
      purchaseDatePicker.clear()
    }
  } else if (field === 'warranty_expiry') {
    asset.warranty_expiry = ''
    if (warrantyDatePicker) {
      warrantyDatePicker.clear()
    }
  }
}

// Initialize flatpickr date pickers
async function initializeFlatpickr() {
  await nextTick() // Ensure DOM is updated
  
  if (purchaseDateInput.value) {
    try {
      purchaseDatePicker = flatpickr(purchaseDateInput.value, {
        dateFormat: 'd-m-Y',
        allowInput: false,
        disableMobile: true,
        onChange: (selectedDates: Date[], dateStr: string) => {
          asset.purchase_date = dateStr
          console.log('Selected purchase_date:', selectedDates)
          if (import.meta.env.DEV) console.log('purchase_date updated:', dateStr)
        }
      })
      if (asset.purchase_date) {
        purchaseDatePicker.setDate(asset.purchase_date, true, 'd-m-Y')
        if (import.meta.env.DEV) console.log('Set purchase_date:', asset.purchase_date)
      }
    } catch (err) {
      console.error('Failed to initialize purchase_date flatpickr:', err)
      error.value = 'Failed to initialize date picker'
    }
  } else {
    if (import.meta.env.DEV) console.warn('purchaseDateInput ref is null')
  }

  if (warrantyInput.value) {
    try {
      warrantyDatePicker = flatpickr(warrantyInput.value, {
        dateFormat: 'd-m-Y',
        allowInput: false,
        disableMobile: true,
        onChange: (selectedDates: Date[], dateStr: string) => {
          asset.warranty_expiry = dateStr
          console.log('Selected purchase_date:', selectedDates)
          if (import.meta.env.DEV) console.log('warranty_expiry updated:', dateStr)
        }
      })
      if (asset.warranty_expiry) {
        warrantyDatePicker.setDate(asset.warranty_expiry, true, 'd-m-Y')
        if (import.meta.env.DEV) console.log('Set warranty_expiry:', asset.warranty_expiry)
      }
    } catch (err) {
      console.error('Failed to initialize warranty_expiry flatpickr:', err)
      error.value = 'Failed to initialize date picker'
    }
  } else {
    if (import.meta.env.DEV) console.warn('warrantyInput ref is null')
  }
}

// Initialize component
onMounted(async () => {
  loading.value = true
  error.value = null
  try {
    await fetchMetadata()
    await loadAssetData()
  } catch (err: any) {
    error.value = `Failed to load data: ${err.message}`
    router.push('/') // Redirect on error
  } finally {
    loading.value = false
  }
})

// Watch for loading state to initialize flatpickr
watch(loading, async (newValue) => {
  if (!newValue && !error.value) {
    await initializeFlatpickr()
  }
})

// Clean up date pickers
onUnmounted(() => {
  if (purchaseDatePicker) {
    purchaseDatePicker.destroy()
    purchaseDatePicker = null
    if (import.meta.env.DEV) console.log('Destroyed purchase_date picker')
  }
  if (warrantyDatePicker) {
    warrantyDatePicker.destroy()
    warrantyDatePicker = null
    if (import.meta.env.DEV) console.log('Destroyed warranty_expiry picker')
  }
})
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/forms.scss";
@use "/src/assets/styles/items.scss";

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