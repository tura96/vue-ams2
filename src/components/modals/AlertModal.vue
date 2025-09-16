<template>
  <transition name="modal-slide">
    <div v-if="isOpen" class="modal-overlay" @click="handleOverlayClick">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">{{ title || defaultTitle }}</h3>
        </div>
        
        <div class="modal-body">
          <p :class="['alert-message', `alert-message--${type}`]">{{ message }}</p>
        </div>
        
        <div class="modal-footer">
          <button 
            class="btn btn--primary" 
            @click="handleClose"
          >
            OK
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  isOpen: boolean
  message: string
  title?: string
  type?: 'success' | 'error'
}

interface Emits {
  (e: 'close'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const defaultTitle = computed(() => {
  return props.type === 'success' ? 'Success' : 'Error'
})

function handleClose() {
  emit('close')
}

function handleOverlayClick(event: MouseEvent) {
  if (event.target === event.currentTarget) {
    handleClose()
  }
}
</script>

<style scoped lang="scss">
@use "/src/assets/styles/components/forms.scss";

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
  overflow-y: auto;
}

.modal-slide-enter-active,
.modal-slide-leave-active {
  transition: all 0.3s ease;
}

.modal-slide-enter-from,
.modal-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

.modal-slide-enter-to,
.modal-slide-leave-from {
  opacity: 1;
  transform: translateY(0);
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
}

.alert-message {
  margin: 0;
  font-size: 0.875rem;
  
  &--success {
    color: #2f855a;
  }
  
  &--error {
    color: #e53e3e;
  }
}

.modal-footer {
  padding: 0 1.5rem 1.5rem 1.5rem;
  display: flex;
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
  
  &--primary {
    background-color: #17a2b8;
    color: white;
    
    &:hover:not(:disabled) {
      background-color: #138496;
    }
  }
}
</style>