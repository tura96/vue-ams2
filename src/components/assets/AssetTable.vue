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
          <th class="table__header table__header--sortable" style="min-width: 185px;">
            <span>Model</span>
            <img
              src="/assets/images/icons/mdi_sort2.svg"
              alt="Sort"
              class="table__sort-icon"
              @click="sortBy('model')"
            />
          </th>
          <th class="table__header table__header--sortable">
            <span>Serial No.</span>
            <img
              src="/assets/images/icons/mdi_sort2.svg"
              alt="Sort"
              class="table__sort-icon"
              @click="sortBy('serial')"
            />
          </th>
          <th class="table__header">Status</th>
          <th class="table__header">Assigned To</th>
          <th class="table__header">Location</th>
          <th class="table__header table__header--sortable">
            <span>Warranty Expiry</span>
            <img
              src="/assets/images/icons/mdi_sort2.svg"
              alt="Sort"
              class="table__sort-icon"
              @click="sortBy('warranty')"
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
            <!-- {{ (currentPage - 1) * itemsPerPage + index + 1 }} -->
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
}>()

// Emits
const emit = defineEmits<{
  (e: 'select-all', value: boolean): void
  (e: 'select', assetId: number | string): void
  (e: 'edit', asset: any): void
  (e: 'delete', asset: any): void
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
  // Sorting logic placeholder
  console.log(`Sort by ${column}`)
}

function copyToClipboard(text: string) {
  // navigator.clipboard.writeText(text)
  console.log(`Copied to clipboard: ${text}`)
}

function formatDate(date: string | Date) {
  // Date formatting logic placeholder
  return date
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
</style>