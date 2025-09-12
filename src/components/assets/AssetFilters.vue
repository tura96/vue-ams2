<template>
  <div class="content__toolbar">
    <SearchInput v-model="filters.search" placeholder="Search" />

    <div class="filters">
      <CustomSelect 
        v-model="filters.status" 
        label="Status" 
        :options="statusOptions" 
      />

      <CustomSelect 
        v-model="filters.model" 
        label="Model" 
        :options="modelOptions" 
      />

      <CustomSelect 
        v-model="filters.store" 
        label="Store" 
        :options="storeOptions" 
      />

      <button class="btn btn--secondary" @click="applyBulkAction">
        BULK DEPLOY
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import CustomSelect from '@/components/ui/CustomSelect.vue'

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

// --- Emits ---
const emit = defineEmits<{
  (e: 'filter-change', filters: Filters): void
}>()

// --- State ---
const filters = ref<Filters>({
  search: '',
  status: '',
  model: '',
  store: ''
})

const statusOptions: Option[] = [
  { value: 'available', label: 'Available' },
  { value: 'deployed', label: 'Deployed' },
  { value: 'maintenance', label: 'Under Maintenance' },
  { value: 'defected', label: 'Defected' }
]

const modelOptions: Option[] = [
  { value: 'L3150', label: 'L3150' },
  { value: 'X1', label: 'ThinkPad X1' },
  { value: 'M404', label: 'M404' }
]

const storeOptions: Option[] = [
  { value: 'it-room', label: 'IT Room' },
  { value: 'office-3', label: 'Office 3' },
  { value: 'office-4', label: 'Office 4' }
]

// --- Watch ---
watch(filters, (newFilters) => {
  emit('filter-change', newFilters)
}, { deep: true })

// --- Methods ---
function applyBulkAction() {
  // Bulk action logic placeholder
  console.log('Bulk deploy action triggered', filters.value)
}
</script>
<style scoped lang="scss">
  @use "/src/assets/styles/components/table.scss";
</style>