<template>
  <div v-if="isOpen" class="modal-overlay" @click="handleOverlayClick">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Confirmation</h3>
      </div>
      
      <div class="modal-body">
        <p>{{ message }}</p>
      </div>
      
      <div class="modal-footer">
        <button 
          class="btn btn--secondary" 
          @click="emit('cancel')"
          :disabled="loading"
        >
          CANCEL
        </button>
        <button 
          class="btn btn--danger" 
          @click="emit('confirm')"
          :disabled="loading"
        >
          <span v-if="loading">Deleting...</span>
          <span v-else>CONFIRM</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  isOpen: boolean
  message: string
  loading?: boolean
}

interface Emits {
  (e: 'confirm'): void
  (e: 'cancel'): void
}

defineProps<Props>()
// defineEmits<Emits>()

// const props = defineProps<Props>()
const emit = defineEmits<Emits>() 

const handleOverlayClick = (event: MouseEvent) => {
  // Close modal when clicking on overlay (outside the modal content)
  if (event.target === event.currentTarget) {
    emit('cancel')
  }
}
</script>

<style scoped lang="scss">
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  max-width: 400px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1.5rem 1.5rem 0 1.5rem;
  border-bottom: 1px dotted #e2e8f0;
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
  color: #1a202c;
}

.modal-body {
  padding: 1.5rem;
  
  p {
    margin: 0;
    color: #4a5568;
    line-height: 1.5;
  }
}

.modal-footer {
  padding: 0 1.5rem 1.5rem 1.5rem;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
  
  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  
  &--secondary {
    background-color: #e2e8f0;
    color: #4a5568;
    
    &:hover:not(:disabled) {
      background-color: #cbd5e0;
    }
  }
  
  &--danger {
    background-color: #e53e3e;
    color: white;
    
    &:hover:not(:disabled) {
      background-color: #c53030;
    }
  }
}
</style>