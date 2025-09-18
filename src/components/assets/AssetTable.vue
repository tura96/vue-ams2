<template>
  <div class="asset-table-wrapper">
    <!-- Loading State -->
    <div v-if="loading" class="empty-state">
      <div class="empty-state__icon">
        <svg class="loading-spinner" viewBox="0 0 24 24" width="48" height="48">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
            <animateTransform attributeName="transform" type="rotate" values="0 12 12;360 12 12" dur="1s" repeatCount="indefinite"/>
          </circle>
        </svg>
      </div>
      <h3 class="empty-state__title">Loading assets...</h3>
      <p class="empty-state__description">Please wait while we fetch your asset data.</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="empty-state empty-state--error">
      <div class="empty-state__icon">
        <svg viewBox="0 0 24 24" width="64" height="64" fill="currentColor">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
        </svg>
      </div>
      <h3 class="empty-state__title">Failed to load assets</h3>
      <p class="empty-state__description">
        There was an error loading your assets. Please check your connection and try again.
      </p>
      <div class="empty-state__actions">
        <button class="btn btn--primary" @click="$emit('retry')">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
            <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
          </svg>
          Retry
        </button>
        <button class="btn btn--outline" @click="refreshPage">
          Refresh Page
        </button>
      </div>
    </div>

    <!-- Empty States -->
    <div v-else-if="assets.length === 0" class="empty-state" :class="emptyStateClass">
      <!-- No Results (Filtered) -->
      <template v-if="hasActiveFilters">
        <div class="empty-state__icon">
          <svg viewBox="0 0 24 24" width="64" height="64" fill="currentColor">
            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
          </svg>
        </div>
        <h3 class="empty-state__title">No assets found</h3>
        <p class="empty-state__description">
          No assets match your current search and filter criteria. 
          Try adjusting your filters or clearing them to see all assets.
        </p>
        <div class="empty-state__actions">
          <button class="btn btn--secondary" @click="$emit('clear-filters')">
            Clear Filters
          </button>
          <button v-if="totalAssetCount&&totalAssetCount > 0" class="btn btn--outline" @click="$emit('view-all')">
            View All Assets ({{ totalAssetCount }})
          </button>
        </div>
      </template>

      <!-- Initial Empty State -->
      <template v-else>
        <div class="empty-state__icon">
          <svg viewBox="0 0 24 24" width="64" height="64" fill="currentColor">
            <path d="M20 6h-2.18c.11-.31.18-.65.18-1a2.996 2.996 0 0 0-5.5-1.65l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1z"/>
          </svg>
        </div>
        <h3 class="empty-state__title">No assets found</h3>
        <p class="empty-state__description">
          No assets match your current search and filter criteria. 
          Try adjusting your filters or clearing them to see all assets.
        </p>
        <div class="empty-state__actions">
        <button class="btn btn--outline" @click="refreshPage">
          Refresh Page
        </button>
        <button class="btn btn--secondary" @click="$emit('clear-filters')">
          Clear Filters
        </button>
        </div>
      </template>
    </div>

    <!-- Regular Table -->
    <div v-else class="table-container">
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
              {{ asset.id || '-'}}
            </td>
            <td class="table__cell">
              <span class="asset-tag">{{ asset.asset_tag || '-'}}</span>
              <img
                src="/assets/images/icons/mdi_content-copy.svg"
                alt="Copy"
                class="asset-tag__copy"
                @click="copyToClipboard(asset.asset_tag)"
              />
            </td>
            <td class="table__cell">{{ asset.model || '-'}}</td>
            <td class="table__cell">{{ asset.serial_number || '-'}}</td>
            <td class="table__cell">
              <AssetStatus :status="asset.status || 'undefined'" />
            </td>
            <td class="table__cell">{{ asset.assigned_to || '-' }}</td>
            <td class="table__cell">{{ asset.location || '-' }}</td>
            <td class="table__cell">{{ formatDate(asset.warranty_expiry) || '-'}}</td>
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
  loading?: boolean
  error?: string | boolean
  hasActiveFilters?: boolean
  totalAssetCount?: number
}>()

// Emits
const emit = defineEmits<{
  (e: 'select-all', value: boolean): void
  (e: 'select', assetId: number | string): void
  (e: 'edit', asset: any): void
  (e: 'delete', asset: any): void
  (e: 'sort', column: string, order: 'ASC' | 'DESC'): void
  (e: 'retry'): void
  (e: 'clear-filters'): void
  (e: 'view-all'): void
  (e: 'add-asset'): void
  (e: 'import-assets'): void
}>()

// Router
const router = useRouter()

// Computed
const allSelected = computed(() => {
  return props.assets.length > 0 && props.selectedAssets?.length === props.assets.length
})

const emptyStateClass = computed(() => {
  return {
    'empty-state--filtered': props.hasActiveFilters,
    'empty-state--initial': !props.hasActiveFilters
  }
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
  } catch (err) {
    console.error('Fallback copy failed:', err)
  }
  
  document.body.removeChild(textArea)
}

function formatDate(date: string | Date) {
  if (!date) return '-'
  
  try {
    let dateObj: Date
    
    if (typeof date === 'string') {
      if (date.includes('-') && date.length === 10) {
        const parts = date.split('-')
        if (parts.length === 3) {
          if (parseInt(parts[0]) > 12) {
            dateObj = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`)
          } else if (parseInt(parts[2]) > 31) {
            dateObj = new Date(date)
          } else {
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
  console.log(`Lock asset ${asset.id}`)
}

function refreshPage() {
  window.location.reload()
}
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/table.scss";
@use "/src/assets/styles/components/status-badges.scss";
</style>