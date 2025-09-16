<template>
  <div class="table-container">
    <table class="table">
      <thead class="table__head">
        <tr class="table__row">
          <th class="table__header table__header--checkbox">
            <input
              id="allcheckbox"
              type="checkbox"
              class="checkbox"
              :checked="allSelected"
              @change="toggleSelectAll"
            />
            #
          </th>
          <th class="table__header" style="min-width: 185px;">
            Asset Tag
          </th>
          <th 
            class="table__header table__header--sortable" 
            style="min-width: 185px;"
            @click="sortBy('model')"
          >
            <span>Model</span>
            <img
              src="/assets/images/icons/mdi_sort2.svg"
              alt="Sort"
              class="table__sort-icon"
              :class="getSortIconClass('model')"
            />
          </th>
          <th 
            class="table__header table__header--sortable"
            @click="sortBy('serial_number')"
          >
            <span>Serial No.</span>
            <img
              src="/assets/images/icons/mdi_sort2.svg"
              alt="Sort"
              class="table__sort-icon"
              :class="getSortIconClass('serial_number')"
            />
          </th>
          <th class="table__header">Status</th>
          <th class="table__header">Assigned To</th>
          <th class="table__header">Location</th>
          <th 
            class="table__header table__header--sortable"
            @click="sortBy('warranty_expiry')"
          >
            <span>Warranty Expiry</span>
            <img
              src="/assets/images/icons/mdi_sort2.svg"
              alt="Sort"
              class="table__sort-icon"
              :class="getSortIconClass('warranty_expiry')"
            />
          </th>
          <th class="table__header">Notes</th>
          <th class="table__header">Action</th>
        </tr>
      </thead>
      <tbody class="table__body">
        <tr v-for="(asset) in assets" :key="asset.id" class="table__row">
          <td class="table__cell table__cell--checkbox">
            <input
              type="checkbox"
              class="checkbox"
              :checked="isSelected(asset.id)"
              @change="toggleSelect(asset.id)"
            />
            {{ asset.id }}
          </td>
          <td class="table__cell">
            <span class="asset-tag">{{ asset.asset_tag }}</span>
            <img
              src="/assets/images/icons/mdi_content-copy.svg"
              alt="Copy"
              class="asset-tag__copy"
              @click="copyToClipboard(asset.asset_tag)"
            />
          </td>
          <td class="table__cell">{{ asset.model }}</td>
          <td class="table__cell">{{ asset.serial_number }}</td>
          <td class="table__cell">
            <AssetStatus :status="asset.status" />
          </td>
          <td class="table__cell">{{ asset.assigned_to || '-' }}</td>
          <td class="table__cell">{{ asset.location || '-' }}</td>
          <td class="table__cell">{{ formatDate(asset.warranty_expiry) }}</td>
          <td class="table__cell">{{ asset.notes }}</td>
          <td class="table__cell table__cell--actions">
            <AssetActions
              :asset="asset"
              @view="viewAsset(asset)"
              @edit="$emit('edit', asset)"
              @delete="$emit('delete', asset)"
              @lock="lockAsset(asset)"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import AssetStatus from './AssetStatus.vue'
import AssetActions from './AssetActions.vue'

// Props
const props = defineProps<{
  assets: Array<any>
  currentPage: number
  itemsPerPage: number
  selectedAssets: Array<number | string>
  currentSortBy?: string
  currentSortOrder?: 'ASC' | 'DESC'
}>()

// Emits
const emit = defineEmits<{
  (e: 'select-all', value: boolean): void
  (e: 'select', assetId: number | string): void
  (e: 'edit', asset: any): void
  (e: 'delete', asset: any): void
  (e: 'sort', column: string, order: 'ASC' | 'DESC'): void
}>()

// Router
const router = useRouter()

// Computed
const allSelected = computed(() => {
  return props.assets.length > 0 && props.selectedAssets?.length === props.assets.length
})

// Methods
function toggleSelectAll() {
  emit('select-all', !allSelected.value)
}

function toggleSelect(assetId: number | string) {
  emit('select', assetId)
}

function isSelected(assetId: number | string) {
  return props.selectedAssets?.includes(assetId)
}

function sortBy(column: string) {
  let newOrder: 'ASC' | 'DESC' = 'ASC'
  
  // If clicking the same column, toggle order
  if (props.currentSortBy === column) {
    newOrder = props.currentSortOrder === 'ASC' ? 'DESC' : 'ASC'
  }
  console.log(`Sorting by ${column} in ${newOrder} order`)
  
  emit('sort', column, newOrder)
}

function getSortIconClass(column: string) {
  if (props.currentSortBy !== column) {
    return 'table__sort-icon--neutral'
  }
  
  return props.currentSortOrder === 'ASC' 
    ? 'table__sort-icon--asc' 
    : 'table__sort-icon--desc'
}

function copyToClipboard(text: string) {
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(() => {
      console.log(`Copied to clipboard: ${text}`)
      // You could show a toast notification here
    }).catch(err => {
      console.error('Failed to copy to clipboard:', err)
      fallbackCopy(text)
    })
  } else {
    fallbackCopy(text)
  }
}

function fallbackCopy(text: string) {
  const textArea = document.createElement('textarea')
  textArea.value = text
  textArea.style.position = 'fixed'
  textArea.style.opacity = '0'
  document.body.appendChild(textArea)
  textArea.select()
  
  try {
    document.execCommand('copy')
    console.log(`Copied to clipboard: ${text}`)
  } catch (err) {
    console.error('Fallback copy failed:', err)
  }
  
  document.body.removeChild(textArea)
}

function formatDate(date: string | Date) {
  if (!date) return '-'
  
  try {
    // Handle different date formats
    let dateObj: Date
    
    if (typeof date === 'string') {
      // Handle d-m-Y format from PHP
      if (date.includes('-') && date.length === 10) {
        const parts = date.split('-')
        if (parts.length === 3) {
          // Check if it's d-m-Y or Y-m-d format
          if (parseInt(parts[0]) > 12) {
            // Likely d-m-Y format
            dateObj = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`)
          } else if (parseInt(parts[2]) > 31) {
            // Likely Y-m-d format
            dateObj = new Date(date)
          } else {
            // Could be either, try both
            dateObj = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`)
            if (isNaN(dateObj.getTime())) {
              dateObj = new Date(date)
            }
          }
        } else {
          dateObj = new Date(date)
        }
      } else {
        dateObj = new Date(date)
      }
    } else {
      dateObj = date
    }
    
    if (isNaN(dateObj.getTime())) {
      return date.toString()
    }
    
    // Format as d/m/Y for display
    const day = dateObj.getDate().toString().padStart(2, '0')
    const month = (dateObj.getMonth() + 1).toString().padStart(2, '0')
    const year = dateObj.getFullYear()
    
    return `${day}/${month}/${year}`
  } catch (error) {
    console.error('Date formatting error:', error)
    return date?.toString() || '-'
  }
}

function viewAsset(asset: any) {
  router.push(`/assets/${asset.id}`)
}

function lockAsset(asset: any) {
  // Lock asset logic placeholder
  console.log(`Lock asset ${asset.id}`)
}
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/table.scss";
@use "/src/assets/styles/components/status-badges.scss";

// Additional styles for sorting icons
.table__sort-icon {
  transition: transform 0.2s ease, opacity 0.2s ease;
  cursor: pointer;
  
  &--neutral {
    opacity: 0.5;
  }
  
  &--asc {
    opacity: 1;
    // transform: rotate(0deg);
  }
  
  &--desc {
    opacity: 1;
    // transform: rotate(180deg);
  }
}

.table__header--sortable {
  cursor: pointer;
  user-select: none;
  
  &:hover {
    background-color: rgba(0, 0, 0, 0.05);
  }
}

.asset-tag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.asset-tag__copy {
  cursor: pointer;
  opacity: 0.6;
  transition: opacity 0.2s ease;
  
  &:hover {
    opacity: 1;
  }
}
</style>