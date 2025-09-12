<template>
  <div class="table-container">
    <div v-if="loading" class="table-loading">
      <div class="loading-spinner"></div>
      <span>Loading data...</span>
    </div>
    
    <table class="table" v-else>
      <thead class="table__head">
        <tr class="table__row">
          <th 
            v-for="column in columns" 
            :key="column.key" 
            :class="[
              'table__header',
              { 
                'table__header--sortable': column.sortable,
                'table__header--sorted': sortedColumn === column.key
              }
            ]"
            @click="column.sortable ? sortTable(column.key) : null"
          >
            <span>{{ column.label }}</span>
            <img 
              v-if="column.sortable" 
              src="/assets/images/icons/mdi_sort2.svg" 
              alt="Sort" 
              class="table__sort-icon"
            >
          </th>
        </tr>
      </thead>
      <tbody class="table__body">
        <tr 
          v-for="(row, index) in sortedData" 
          :key="row.id || index" 
          class="table__row"
          @click="emit('row-click', row)"
        >
          <td 
            v-for="column in columns" 
            :key="column.key" 
            class="table__cell"
          >
            <slot :name="column.key" :value="row[column.key]" :row="row">
              {{ row[column.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
    
    <div v-if="!loading && data.length === 0" class="table-empty">
      <img src="/assets/images/icons/mdi_database-off.svg" alt="No data" class="table-empty__icon">
      <p>No data available</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

// --- Props ---
interface Column {
  key: string
  label: string
  sortable?: boolean
}

const props = defineProps<{
  columns: Column[]
  data: Record<string, any>[]
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'row-click', row: Record<string, any>): void
}>()

// --- State ---
const sortedColumn = ref('')
const sortDirection = ref<'asc' | 'desc'>('asc')

// --- Computed ---
const sortedData = computed(() => {
  if (!sortedColumn.value) return props.data

  return [...props.data].sort((a, b) => {
    let valueA = a[sortedColumn.value]
    let valueB = b[sortedColumn.value]

    if (typeof valueA === 'string') {
      valueA = valueA.toLowerCase()
      valueB = valueB.toLowerCase()
    }

    if (valueA < valueB) return sortDirection.value === 'asc' ? -1 : 1
    if (valueA > valueB) return sortDirection.value === 'asc' ? 1 : -1
    return 0
  })
})

// --- Methods ---
function sortTable(column: string) {
  if (sortedColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortedColumn.value = column
    sortDirection.value = 'asc'
  }
}
</script>
