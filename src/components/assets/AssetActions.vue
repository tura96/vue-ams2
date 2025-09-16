<template>
  <div class="actions">
    <button class="actions__btn" @click="$emit('view')">
      <img src="/assets/images/icons/mdi_eye-outline.svg" alt="View" class="actions__icon">
      <span class="actions__tooltip">Detail</span>
    </button>
    <button class="actions__btn" @click="$emit('view')">
      <img src="/assets/images/icons/mdi_edit-outline.svg" alt="Edit" class="actions__icon">
      <span class="actions__tooltip">Edit</span>
    </button>
    <button class="actions__btn" @click="$emit('delete')">
      <img src="/assets/images/icons/mdi_trash-can-outline.svg" alt="Trash" class="actions__icon">
      <span class="actions__tooltip">Delete</span>
    </button>
    <button class="actions__btn" @click="$emit('lock')">
      <img src="/assets/images/icons/mdi_account-lock-open-outline.svg" alt="More" class="actions__icon">
      <span class="actions__tooltip">Lock</span>
    </button>
  </div>
</template>

<script setup lang="ts">
// --- Props ---
// const props = defineProps<{
//   asset: Record<string, any>
// }>()

// --- Emits ---
defineEmits<{
  (e: 'view'): void
  (e: 'edit'): void
  (e: 'delete'): void
  (e: 'lock'): void
}>()
</script>
<style lang="scss" scoped>
  @use "/src/assets/styles/components/status-badges.scss";
</style>
