<template>
  <span class="status" :class="statusClass">{{ statusText }}</span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// --- Props ---
const props = defineProps<{
  status: string
}>()

// --- Computed ---
const statusClass = computed(() => `status--${props.status}`)

const statusText = computed(() => {
  const statusMap: Record<string, string> = {
    new: 'New',
    staging: 'Staging',
    ready: 'Ready to deploy',
    deployed: 'Deployed',
    defected: 'Defected',
    maintenance: 'Under Maintenance',
    archived: 'Archived',
    disposed: 'Disposed',
    return: 'Returned to Client'
  }
  return statusMap[props.status] || props.status
})
</script>
<style scoped lang="scss">
  @use "/src/assets/styles/components/status-badges.scss";
</style>