<template>
  <div class="content__toolbar">
    <SearchInput v-model="filters.search" :placeholder="Search" />

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

// --- Props ---
const props = defineProps<{
  statusOptions: Option[]
  modelOptions: Option[]
  storeOptions: Option[]
  placeholder?: string
}>()

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

// --- Watch ---
watch(filters, (newFilters) => {
  emit('filter-change', newFilters)
}, { deep: true })

// --- Methods ---
function applyBulkAction() {
  console.log('Bulk deploy action triggered', props)
}
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/table.scss";
</style>
