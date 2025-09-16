<template>
  <div class="item-detail">
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
              placeholder="assetTag"
              v-model="asset.tag"
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
              placeholder="serialNumber"
              v-model="asset.serial"
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
              placeholder="Purchase Date"
              v-model="asset.purchaseDate"
              ref="purchaseDateInput"
            >
            <label for="purchaseDate" class="input-floating__label">Purchase Date</label>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" class="btn-reset date-reset" title="Clear" v-if="asset.purchaseDate" @click="clearDate('purchaseDate')">
              <path d="M15.707 5.70703L11.4141 10L15.707 14.293L14.293 15.707L10 11.4141L5.70703 15.707L4.29297 14.293L8.58594 10L4.29297 5.70703L5.70703 4.29297L10 8.58594L14.293 4.29297L15.707 5.70703Z" fill="#44424B"></path>
            </svg>
          </div>

          <div class="input-floating">
            <input 
              type="number" 
              id="cost" 
              class="input-floating__field" 
              placeholder="cost"
              v-model="asset.cost"
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
              placeholder="Warranty Expiry"
              v-model="asset.warrantyExpiry"
              ref="warrantyInput"
            >
            <label for="warrantyExpiry" class="input-floating__label">Warranty Expiry</label>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" class="btn-reset date-reset" title="Clear" v-if="asset.warrantyExpiry" @click="clearDate('warrantyExpiry')">
              <path d="M15.707 5.70703L11.4141 10L15.707 14.293L14.293 15.707L10 11.4141L5.70703 15.707L4.29297 14.293L8.58594 10L4.29297 5.70703L5.70703 4.29297L10 8.58594L14.293 4.29297L15.707 5.70703Z" fill="#44424B"></path>
            </svg>
          </div>

          <div class="input-floating">
            <input 
              type="text" 
              id="rfidTag" 
              class="input-floating__field" 
              placeholder="rfidTag"
              v-model="asset.rfidTag"
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
            v-model="asset.store"
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
  </div>
</template>

<script lang="ts" setup>
import { ref, reactive, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import flatpickr from 'flatpickr';
import "flatpickr/dist/flatpickr.css";
import "@/assets/styles/items.scss";
import CustomSelect from '@/components/ui/CustomSelect.vue'

interface Asset {
  tag: string
  model: string
  serial: string
  status: string
  purchaseDate: string
  cost: string | number
  warrantyExpiry: string
  rfidTag: string
  store: string
  location: string
  notes: string
}

const props = defineProps<{
  assetId?: string | number | null
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

let purchaseDatePicker: any = null
let warrantyDatePicker: any = null

const asset = reactive<Asset>({
  tag: '',
  model: '',
  serial: '',
  status: '',
  purchaseDate: '',
  cost: '',
  warrantyExpiry: '',
  rfidTag: '',
  store: '',
  location: '',
  notes: ''
})

const modelOptions = [
  { value: 'dell-latitude-5440', label: 'Dell Latitude 5440' },
  { value: 'hp-laserjet-m404', label: 'HP LaserJet Pro M404' },
  { value: 'lenovo-thinkpad-x1', label: 'Lenovo ThinkPad X1' },
  { value: 'pc', label: 'PC' }
]

const statusOptions = [
  { value: 'available', label: 'Available' },
  { value: 'deployed', label: 'Deployed' },
  { value: 'maintenance', label: 'Under Maintenance' },
  { value: 'defected', label: 'Defected' }
]

const storeOptions = [
  { value: 'store-a', label: 'Store A' },
  { value: 'store-b', label: 'Store B' },
  { value: 'store-c', label: 'Store C' },
  { value: 'store-d', label: 'Store D' }
]

const locationOptions = [
  { value: 'office-3', label: 'Office 3' },
  { value: 'office-4', label: 'Office 4' },
  { value: 'it-room', label: 'IT Room' },
  { value: 'kitchen', label: 'Kitchen' }
]

// Update field classes based on value
const updateFieldClasses = () => {
  // Text inputs
  const textInputs = [
    { element: assetTagInput.value, value: asset.tag, className: 'input-floating__field--has-value' },
    { element: serialInput.value, value: asset.serial, className: 'input-floating__field--has-value' },
    { element: costInput.value, value: asset.cost, className: 'input-floating__field--has-value' },
    { element: rfidInput.value, value: asset.rfidTag, className: 'input-floating__field--has-value' },
    { element: purchaseDateInput.value, value: asset.purchaseDate, className: 'input-floating__field--has-value' },
    { element: warrantyInput.value, value: asset.warrantyExpiry, className: 'input-floating__field--has-value' },
    { element: notesInput.value, value: asset.notes, className: 'multiselect-floating__field--has-value' }
  ]
  
  textInputs.forEach(({ element, value, className }) => {
    if (element) {
      if (value) {
        element.classList.add(className)
      } else {
        element.classList.remove(className)
      }
    }
  })
  
  // Select inputs
  const selectInputs = [
    { element: modelSelect.value, value: asset.model },
    { element: statusSelect.value, value: asset.status },
    { element: storeSelect.value, value: asset.store },
    { element: locationSelect.value, value: asset.location }
  ]
  
  selectInputs.forEach(({ element, value }) => {
    if (element) {
      const selectElement = element.$el?.querySelector('.select-floating__field')
      if (selectElement) {
        if (value) {
          selectElement.classList.add('select-floating__field--has-value')
        } else {
          selectElement.classList.remove('select-floating__field--has-value')
        }
      }
    }
  })
}

// Watch for changes in asset properties
watch(() => asset, () => {
  updateFieldClasses()
  // console.log('New value: ',newValue)
}, { deep: true })

// Load asset data if editing
const loadAssetData = () => {
  if (props.assetId) {
    // In a real app, this would be an API call
    const sampleData: Asset = {
      tag: 'AST-0001',
      model: 'dell-latitude-5440',
      serial: 'SN-DL5440-1234',
      status: 'available',
      purchaseDate: '2023-05-15',
      cost: '1200',
      warrantyExpiry: '2025-05-15',
      rfidTag: 'RFID-001',
      store: 'store-a',
      location: 'office-3',
      notes: 'Ready to use'
    }
    Object.assign(asset, sampleData)
    
    // Update classes after data is loaded
    nextTick(() => {
      updateFieldClasses()
    })
  }
}

const goBack = () => {
  // router.back()
  router.push('/')
}

const saveAsset = () => {
  // Validate required fields
  if (!asset.tag || !asset.model || !asset.serial) {
    alert('Please fill in all required fields')
    return
  }

  // Prepare data for saving
  const assetData = {
    ...asset,
    id: props.assetId || Date.now() // Generate ID if new asset
  }

  // In a real app, this would be an API call
  console.log('Saving asset:', assetData)
  
  // Simulate API call
  setTimeout(() => {
    alert(props.assetId ? 'Asset updated successfully!' : 'Asset created successfully!')
    // router.push('/assets')
  }, 500)
}

// Clear date picker field
const clearDate = (field: 'purchaseDate' | 'warrantyExpiry') => {
  if (field === 'purchaseDate') {
    asset.purchaseDate = ''
    if (purchaseDateInput.value) {
      purchaseDateInput.value.value = ''
      purchaseDatePicker.clear()
      purchaseDatePicker.updateValue('')
    }
  } else if (field === 'warrantyExpiry') {
    asset.warrantyExpiry = ''
    if (warrantyInput.value) {
      warrantyInput.value.value = ''
      warrantyDatePicker.clear()
      warrantyDatePicker.updateValue('')
    }
  }
  updateFieldClasses()
}

// Initialize date pickers and event listeners
onMounted(() => {
  loadAssetData()

  // Initialize Flatpickr for date inputs
  if (purchaseDateInput.value) {
    purchaseDatePicker = flatpickr(purchaseDateInput.value, {
      dateFormat: 'd-m-Y',
      allowInput: false,
      disableMobile: true,
      onChange: function(selectedDates: Date[], dateStr: string) {
        console.log(selectedDates)
        asset.purchaseDate = dateStr
        updateFieldClasses()
      }
    })
  }

  if (warrantyInput.value) {
    warrantyDatePicker = flatpickr(warrantyInput.value, {
      dateFormat: 'd-m-Y',
      allowInput: false,
      disableMobile: true,
      onChange: function(selectedDates: Date[], dateStr: string) {
        console.log(selectedDates)
        asset.warrantyExpiry = dateStr
        updateFieldClasses()
      }
    })
  }
  
  // Add input event listeners to text fields
  const textInputs = [
    assetTagInput.value,
    serialInput.value,
    costInput.value,
    rfidInput.value,
    notesInput.value
  ].filter(Boolean) as HTMLInputElement[]
  
  textInputs.forEach(input => {
    input.addEventListener('input', updateFieldClasses)
  })
})

// Clean up date pickers and event listeners
onUnmounted(() => {
  if (purchaseDatePicker) {
    purchaseDatePicker.destroy()
  }
  if (warrantyDatePicker) {
    warrantyDatePicker.destroy()
  }
  
  // Remove input event listeners
  const textInputs = [
    assetTagInput.value,
    serialInput.value,
    costInput.value,
    rfidInput.value,
    notesInput.value
  ].filter(Boolean) as HTMLInputElement[]
  
  textInputs.forEach(input => {
    input.removeEventListener('input', updateFieldClasses)
  })
})
</script>
<style scoped lang="scss">
  @use "/src/assets/styles/components/forms.scss";
  @use "/src/assets/styles/items.scss";
</style>