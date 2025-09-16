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

// --- Props ---
const props = defineProps<{
  statusOptions: Option[]
  modelOptions: Option[]
  storeOptions: Option[]
  placeholder?: string
  activeFilters: Filters
}>()

// --- Emits ---
const emit = defineEmits<{
  (e: 'filter-change', filters: Filters): void
}>()

// Local reactive filters, initialized with activeFilters
const localFilters = ref<Filters>({
  search: props.activeFilters.search,
  status: props.activeFilters.status,
  model: props.activeFilters.model,
  store: props.activeFilters.store
})

// Sync localFilters with activeFilters when the prop changes
watch(
  () => props.activeFilters,
  (newFilters) => {
    localFilters.value = { ...newFilters }
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
function applyBulkAction() {
  // Placeholder for bulk deploy logic
  console.log('Bulk deploy action triggered with filters:', localFilters.value)
  // Optionally emit an event if the parent needs to handle this
  // emit('bulk-deploy', localFilters.value)
}
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/table.scss";
</style>