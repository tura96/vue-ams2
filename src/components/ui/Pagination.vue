<!-- Pagination component -->
<template>
  <div class="pagination">
    <div class="pagination__info">
      <span>Request count: {{ totalItems }}</span>
    </div>
    <div class="pagination__controls">
      <span>Items per page:</span>
      <select
        class="pagination__select"
        :value="itemsPerPage"
        @change="handleItemsPerPageChange"
      >
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
      </select>

      <button
        class="pagination__btn pagination__btn--prev"
        :disabled="currentPage === 1"
        @click="goToPage(currentPage - 1)"
      >
        <img
          src="/assets/images/icons/mdi_keyboard-arrow-left.svg"
          alt="Previous"
          class="pagination__icon"
        />
      </button>

      <span class="pagination__current">{{ currentPage }}</span>

      <button
        class="pagination__btn pagination__btn--next"
        :disabled="currentPage === totalPages"
        @click="goToPage(currentPage + 1)"
      >
        <img
          src="/assets/images/icons/mdi_keyboard-arrow-right.svg"
          alt="Next"
          class="pagination__icon"
        />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// --- Props ---
const props = defineProps<{
  currentPage: number
  totalItems: number
  itemsPerPage: number
}>()

// --- Emits ---
const emit = defineEmits<{
  (e: 'page-change', page: number): void
  (e: 'items-per-page-change', itemsPerPage: number): void
}>()

// --- Computed ---
const totalPages = computed(() => {
  return Math.ceil(props.totalItems / props.itemsPerPage)
})

// --- Methods ---
function goToPage(page: number) {
  if (page >= 1 && page <= totalPages.value) {
    emit('page-change', page)
  }
}

function handleItemsPerPageChange(event: Event) {
  const target = event.target as HTMLSelectElement
  emit('items-per-page-change', parseInt(target.value))
}
</script>
<style scoped lang="scss">
  @use "/src/assets/styles/components/table.scss";
</style>